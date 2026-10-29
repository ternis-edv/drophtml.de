# Site error pages

The DropHTML application 404 and hosted-site error pages are separate. Hosted errors keep visitors on the relevant website, with its name, requested path (without query parameters), and a site-home link when appropriate. They are self-contained, so a custom domain does not need to serve DropHTML assets.

## Plans and rollout

Run `php artisan migrate` before deploying this change.

All existing accounts default to `free`. There is no billing integration in this repository. The new `users.plan` field is a server-managed entitlement and is deliberately not mass assignable or editable through profile/API requests. Assign confirmed paid access from the server:

```sh
php artisan accounts:set-plan customer@example.com paid
php artisan accounts:set-plan customer@example.com free
```

Unknown plan values do not grant paid access. Do not infer payment from storage quotas. A future billing integration should update this entitlement only after verifying subscription state.

## Per-site editor

Open a site → **Error pages**, or use **Manage error pages** in its settings. Paid owners can create individual 404, 403, 500 and 503 pages. The editor includes a starter template, an HTML source editor, an isolated live preview, enable/disable controls and removal of saved overrides. Source is limited to 100,000 characters per page. Saving publishes the page; changing the starter alone does not publish it.

The preview disables scripts and uses sample path values. Published pages are static HTML (never Blade or PHP). Inline CSS and site-relative assets work; a base tag points at the current hosted site's home URL.

Available placeholders, escaped as HTML when substituted:

- `{{site_name}}`: original site name, falling back to the slug.
- `{{site_url}}`: website home on the current path URL, subdomain or custom domain.
- `{{status_code}}`: actual HTTP error status.
- `{{requested_path}}`: requested path, without query parameters.

## Fallback and lifecycle

For 404 responses the order is:

1. Enabled saved override, if the site owner currently has a paid plan.
2. Root `404.html` from the deployment, on **all plans**, preserving the pre-existing free feature.
3. DropHTML's site-specific default error page.

For 403, 500 and 503 responses, an enabled paid override is used before the site-specific default. Password-protected websites still show their normal 401 unlock prompt first. Dashboard, API, authentication and other application errors do not use a hosted site's templates. JSON clients continue to receive JSON errors. Redirect rules and SPA routing still take priority over a missing-page fallback.

Responses preserve their HTTP error status, use `no-store, private` and `noindex, nofollow`, and never return 304 for an error page. A 503 includes a retry hint. Legacy `404.html` reads enforce the site directory boundary, including symlinks.

Overrides are separate from deployment files, so redeploys and rollbacks do not overwrite them. They are copied when duplicating a site, included in its owner's data export and removed when deleting the site. A downgrade retains the saved source but stops serving paid overrides immediately; upgrading restores them. Site ownership is checked on editor actions, and the paid entitlement is checked both on writes and public rendering.
