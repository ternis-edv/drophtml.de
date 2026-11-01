# Application backups

Spatie Laravel Backup writes exclusively to the private `backups` disk, whose R2
bucket is fixed to `drophtml-backup`. Media bucket settings cannot redirect backups.
The disk uses dedicated `BACKUP_R2_*` credentials/endpoint when supplied, otherwise
it uses `R2_*`. Grant that token object read/write/delete access to the backup bucket.
Keep public bucket access disabled. Set `BACKUP_ARCHIVE_PASSWORD` for archive
encryption and `BACKUP_MAIL_TO` for operational notifications.

Deployment requirements:

- Run Laravel's scheduler every minute (`php artisan schedule:run`).
- Install PHP ZIP and the database dump utility for the configured database
  (`mysqldump`, `pg_dump`, or `sqlite3`). Provide local space for archive creation.
- Run `php artisan backup:run`, then `php artisan backup:list` and
  `php artisan backup:monitor` to verify production credentials and the first backup.
- Restore a downloaded archive into an isolated environment and verify database,
  site files and snapshots before relying on the recovery procedure.

The schedule runs backup at 01:30, cleanup at 04:00 and monitoring at 05:00 in the
application timezone. Archives include application files, local `storage/app`
(including sites and snapshots), and the configured database. Dependencies, Git
history, logs and framework cache are excluded. Archives are verified after creation.
Retention uses Spatie's published defaults (including a 5 GB cleanup target).
Review this target against real backup sizes and recovery requirements.

Remote media objects are **not** copied by Laravel Backup's file-source backup.
Protect the media bucket separately and include it in restore drills. Database rows
alone cannot recover deleted R2 objects. Preserve the application key securely for
restoring encrypted application data.
