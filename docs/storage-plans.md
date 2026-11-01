# Storage plan proposal

Status: proposal only. Account quotas and available storage providers are unchanged.

## Free storage

Recommend 100 MiB (104,857,600 bytes) of managed storage per free account: a middle
ground between 50 MiB for lightweight HTML projects and 150 MiB for more media-heavy
sites. The existing usage service already totals site code, managed media and
snapshots and checks upload/deployment quotas. Current accounts default to 1 GiB
through both the database column and service fallback.

Before rollout, add an explicit server-side plan/entitlement model. Use one quota
resolver for plan defaults and administrator overrides; changing only the service
constant will not affect database defaults or existing users. Audit every write path
(including snapshots and concurrent uploads), reserve capacity atomically, and
reconcile actual storage usage. Show the allowance before upload. Notify existing
accounts over the new allowance and provide a grace period; retain their existing
files and block growth rather than deleting content. Update database defaults,
existing account policy, UI copy and API documentation together.

## Customer-owned S3 storage (paid only)

Make custom S3-compatible media storage a paid entitlement, enforced on connection
creation, edits, uploads and API calls. No free accounts may configure or use it.
Billing/subscription support does not currently exist, so establish authoritative
paid status first. A larger quota is not proof of payment.

Implement per-account connections with encrypted credentials and redacted responses,
configurable bucket/region/endpoint/public asset URL and path-style support. Build
isolated disks at runtime; never mutate global filesystem configuration in workers.
Restrict custom endpoints against SSRF, including private networks, redirects and DNS
rebinding. Verify access using a temporary write/read/delete probe. Use least-privilege
credentials and tenant-specific prefixes; never allow connections to application
backup storage or access to other tenants' objects.

Persist the connection identity on each asset so old assets remain readable when a
connection changes. Provide explicit copy/verify/switch migrations with rollback;
never silently move or delete objects. Define public URL or signed URL behavior for
hosted HTML. On downgrade, block new external writes and offer a migration/grace
period without breaking existing sites. Keep site-code and snapshot quotas on managed
storage; report external media usage separately. Customer bucket backups and costs
remain the customer's responsibility, clearly explained in settings.

Required coverage before launch: free-account denial through UI and API, tenant
isolation, credential secrecy, endpoint validation, connection failures, migration,
downgrade and quota races.
