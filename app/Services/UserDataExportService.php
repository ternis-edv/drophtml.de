<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Site;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UserDataExportService
{
    /**
     * Build a machine-readable export of the user's personal data
     * (GDPR Art. 20 – right to data portability / DSGVO Art. 20).
     *
     * Secrets (password hashes, two-factor secrets and recovery codes, OAuth
     * tokens, API token values, site passwords, webhook secrets) are never
     * exported. Visitor analytics (views) are excluded because they contain
     * personal data of third parties.
     */
    public function export(User $user): array
    {
        $sites = $user->sites()
            ->withTrashed()
            ->with([
                'domains' => fn ($query) => $query->withTrashed(),
                'deployments' => fn ($query) => $query->withTrashed(),
                'redirects' => fn ($query) => $query->withTrashed(),
            ])
            ->orderBy('created_at')
            ->get();

        return [
            'meta' => [
                'format' => 'drophtml-personal-data-export',
                'format_version' => 1,
                'generated_at' => now()->toIso8601String(),
                'legal_basis' => 'GDPR Art. 20 (DSGVO Art. 20) – right to data portability',
                'notes' => [
                    'Authentication secrets (password hashes, two-factor secrets and recovery codes), OAuth tokens, API token values, site passwords and webhook secrets are not included.',
                    'Visitor analytics data (views) is excluded because it contains personal data of third parties.',
                ],
            ],
            'account' => $this->accountPayload($user),
            'api_tokens' => $user->tokens()
                ->get()
                ->map(fn ($token) => [
                    'id' => $token->id,
                    'name' => $token->name,
                    'abilities' => $token->abilities,
                    'last_used_at' => $token->last_used_at?->toIso8601String(),
                    'expires_at' => $token->expires_at?->toIso8601String(),
                    'created_at' => $token->created_at?->toIso8601String(),
                ])
                ->values()
                ->all(),
            'sites' => $sites->map(fn (Site $site) => $this->sitePayload($site))->values()->all(),
            'media_assets' => $user->mediaAssets()
                ->withTrashed()
                ->orderBy('created_at')
                ->get()
                ->map(fn ($asset) => [
                    'id' => $asset->id,
                    'site_id' => $asset->site_id,
                    'original_name' => $asset->original_name,
                    'file_type' => $asset->file_type,
                    'mime_type' => $asset->mime_type,
                    'size' => $asset->size,
                    'public_url' => $asset->public_url,
                    'metadata' => $asset->metadata,
                    'created_at' => $asset->created_at?->toIso8601String(),
                    'deleted_at' => $asset->deleted_at?->toIso8601String(),
                ])
                ->values()
                ->all(),
            'activity_logs' => $this->activityLogQuery($user)
                ->orderBy('created_at')
                ->get()
                ->map(fn (ActivityLog $log) => [
                    'id' => $log->id,
                    'site_id' => $log->site_id,
                    'action' => $log->action,
                    'description' => $log->description,
                    'ip_address' => $log->ip_address,
                    'created_at' => $log->created_at?->toIso8601String(),
                ])
                ->values()
                ->all(),
        ];
    }

    /**
     * Summary counts of the records an export would contain.
     *
     * @return array<string, int>
     */
    public function summary(User $user): array
    {
        return [
            'sites' => $user->sites()->withTrashed()->count(),
            'media_assets' => $user->mediaAssets()->withTrashed()->count(),
            'activity_logs' => $this->activityLogQuery($user)->count(),
            'api_tokens' => $user->tokens()->count(),
        ];
    }

    /**
     * Stream the export as a pretty-printed JSON download.
     */
    public function download(User $user): StreamedResponse
    {
        $json = json_encode(
            $this->export($user),
            JSON_PRETTY_PRINT
                | JSON_UNESCAPED_SLASHES
                | JSON_UNESCAPED_UNICODE
                | JSON_INVALID_UTF8_SUBSTITUTE
        );

        return response()->streamDownload(
            fn () => print $json,
            'drophtml-dsgvo-export-'.now()->format('Y-m-d').'.json',
            ['Content-Type' => 'application/json']
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function accountPayload(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'email_verified_at' => $user->email_verified_at?->toIso8601String(),
            'password_set_at' => $user->password_set_at?->toIso8601String(),
            'storage_limit_bytes' => $user->storage_limit_bytes,
            'connected_providers' => [
                'email' => filled($user->password_set_at),
                'github' => filled($user->github_id),
                'ternis' => filled($user->ternis_id),
            ],
            'linked_account_ids' => [
                'github_id' => $user->github_id,
                'ternis_id' => $user->ternis_id,
            ],
            'two_factor_authentication' => [
                'enabled' => filled($user->two_factor_secret),
                'confirmed_at' => $user->two_factor_confirmed_at
                    ? Carbon::parse($user->two_factor_confirmed_at)->toIso8601String()
                    : null,
            ],
            'created_at' => $user->created_at?->toIso8601String(),
            'updated_at' => $user->updated_at?->toIso8601String(),
            'deleted_at' => $user->deleted_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function sitePayload(Site $site): array
    {
        return [
            'id' => $site->id,
            'slug' => $site->slug,
            'original_name' => $site->original_name,
            'entry_path' => $site->entry_path,
            'status' => $site->status,
            'views' => $site->views,
            'storage_bytes' => $site->storage_bytes,
            'is_permanent' => $site->is_permanent,
            'is_spa' => $site->is_spa,
            'expires_at' => $site->expires_at?->toIso8601String(),
            'is_password_protected' => $site->is_password_protected,
            'github_repo_full_name' => $site->github_repo_full_name,
            'github_branch' => $site->github_branch,
            'auto_deploy' => $site->auto_deploy,
            'github_webhook_id' => $site->github_webhook_id,
            'webhook_url' => $site->webhook_url,
            'created_at' => $site->created_at?->toIso8601String(),
            'updated_at' => $site->updated_at?->toIso8601String(),
            'deleted_at' => $site->deleted_at?->toIso8601String(),
            'domains' => $site->domains
                ->map(fn ($domain) => [
                    'id' => $domain->id,
                    'domain' => $domain->domain,
                    'is_custom' => $domain->is_custom,
                    'verified_at' => $domain->verified_at?->toIso8601String(),
                    'created_at' => $domain->created_at?->toIso8601String(),
                    'deleted_at' => $domain->deleted_at?->toIso8601String(),
                ])
                ->values()
                ->all(),
            'deployments' => $site->deployments
                ->map(fn ($deployment) => [
                    'id' => $deployment->id,
                    'status' => $deployment->status,
                    'commit_hash' => $deployment->commit_hash,
                    'commit_message' => $deployment->commit_message,
                    'source' => $deployment->source,
                    'metadata' => $deployment->metadata,
                    'created_at' => $deployment->created_at?->toIso8601String(),
                    'updated_at' => $deployment->updated_at?->toIso8601String(),
                    'deleted_at' => $deployment->deleted_at?->toIso8601String(),
                ])
                ->values()
                ->all(),
            'redirects' => $site->redirects
                ->map(fn ($redirect) => [
                    'id' => $redirect->id,
                    'from_path' => $redirect->from_path,
                    'to_url' => $redirect->to_url,
                    'status_code' => $redirect->status_code,
                    'is_active' => $redirect->is_active,
                    'hit_count' => $redirect->hit_count,
                    'last_accessed_at' => $redirect->last_accessed_at?->toIso8601String(),
                    'description' => $redirect->description,
                    'created_at' => $redirect->created_at?->toIso8601String(),
                    'updated_at' => $redirect->updated_at?->toIso8601String(),
                    'deleted_at' => $redirect->deleted_at?->toIso8601String(),
                ])
                ->values()
                ->all(),
        ];
    }

    /**
     * Activity logs created by the user, plus logs attached to their sites
     * even when no user was recorded at the time of the event.
     */
    private function activityLogQuery(User $user): Builder
    {
        $siteIds = $user->sites()->withTrashed()->pluck('id');

        return ActivityLog::withTrashed()
            ->where(fn (Builder $query) => $query
                ->where('user_id', $user->id)
                ->orWhereIn('site_id', $siteIds));
    }
}
