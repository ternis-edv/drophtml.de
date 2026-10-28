# Media storage

Managed assets use `{asset UUID}.{normalized extension}` as their object key.
Ownership and optional site membership live in `media_assets`, not R2 folders.
Site-code files keep readable paths. New contents should get a new asset record
and UUID, rather than overwrite a cached object.

The schema uses `storage_key`, `storage_location`, `disk`, and `extension`.
Legacy `path` and `storage_driver` remain model/API aliases. The `public_url`
column is retained solely for legacy records; new assets resolve their URL from
current configuration. Existing duplicate legacy keys prevent adding a global
unique key constraint during this rollout; new UUID keys are unique by asset ID.

## Production rollout

Deploy code and run the schema migration together, using maintenance mode to
avoid old workers querying renamed columns. Restart queue workers afterwards.
The schema migration does not move or delete any objects.

Configure the R2 disk and a public bucket delivery domain, for example:

```
MEDIA_DISK_DRIVER=s3
MEDIA_PUBLIC_URL=https://assets.drophtml.de
```

Keep the existing R2 credentials, bucket and endpoint. The S3 disk root is now
empty. Local storage continues to use `storage/app/public` and `/storage` URLs.

Set `MEDIA_LEGACY_ROOT=/var/www/drophtml.de/storage/app/public` if the old
production installation path differs from the current application path. Legacy
reads first look at the recorded key and then under this historical prefix.
Rebuild the config cache after changing environment variables.

Inspect the migration plan, then copy and verify existing assets:

```
php artisan media:migrate-keys
php artisan media:migrate-keys --apply
```

Each copy is verified using SHA-256 before its database record is updated.
Conflicting destination contents cause an error and leave the record unchanged.
The command is resumable and retains original objects, plus their keys and URLs
in metadata. It never deletes originals or rewrites published site contents.
Old working embeds continue to use the retained objects; already malformed URLs
are not repaired in previously published HTML by this command. New copies of
media snippets use the UUID URL. Do not remove legacy objects until references
have been migrated separately.

Rolling back the schema restores the old column names, but does not move objects
back. Prefer rolling forward after UUID uploads or migration have begun.
