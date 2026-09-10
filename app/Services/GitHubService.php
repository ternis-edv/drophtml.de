<?php

namespace App\Services;

use App\Models\Site;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class GitHubService
{
    /**
     * Fetch repositories accessible to the user with the given token.
     */
    public function getUserRepositories(string $token, int $perPage = 100): array
    {
        $response = Http::withToken($token)->get('https://api.github.com/user/repos', [
            'sort' => 'updated',
            'per_page' => $perPage,
        ]);

        if ($response->failed()) {
            throw new RuntimeException('Failed to fetch GitHub repositories: '.($response->json('message') ?? $response->body()));
        }

        return $response->json() ?? [];
    }

    /**
     * Download repository archive (zipball) from GitHub into a destination file.
     */
    public function downloadRepositoryArchive(string $fullName, string $branch, string $token, string $destinationZipPath): void
    {
        $archiveUrl = "https://api.github.com/repos/{$fullName}/zipball/{$branch}";
        $response = Http::withToken($token)->get($archiveUrl);

        if ($response->failed()) {
            throw new RuntimeException("Failed to download archive for {$fullName} ({$branch}) from GitHub.");
        }

        $dir = dirname($destinationZipPath);
        if (! is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        file_put_contents($destinationZipPath, $response->body());
    }

    /**
     * Setup push webhook on a GitHub repository.
     */
    public function setupWebhook(Site $site, string $token): ?int
    {
        $fullName = $site->github_repo_full_name;
        if (! $fullName) {
            return null;
        }

        $webhookUrl = url('/webhooks/github');
        $secret = config('services.github.webhook_secret');

        $config = [
            'url' => $webhookUrl,
            'content_type' => 'json',
            'insecure_ssl' => '0',
        ];

        if (! empty($secret)) {
            $config['secret'] = $secret;
        }

        try {
            $response = Http::withToken($token)->post("https://api.github.com/repos/{$fullName}/hooks", [
                'name' => 'web',
                'active' => true,
                'events' => ['push'],
                'config' => $config,
            ]);

            if ($response->successful()) {
                $hookId = $response->json('id');
                $site->update(['github_webhook_id' => $hookId]);

                return $hookId;
            }

            Log::warning("GitHub webhook creation failed for {$fullName}: ".$response->body());
        } catch (\Exception $e) {
            Log::error("Exception setting up GitHub webhook for {$fullName}: ".$e->getMessage());
        }

        return null;
    }

    /**
     * Remove push webhook from a GitHub repository.
     */
    public function removeWebhook(Site $site, string $token): bool
    {
        $fullName = $site->github_repo_full_name;
        $hookId = $site->github_webhook_id;

        if (! $fullName || ! $hookId) {
            return false;
        }

        try {
            $response = Http::withToken($token)->delete("https://api.github.com/repos/{$fullName}/hooks/{$hookId}");
            $site->update(['github_webhook_id' => null]);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error("Exception deleting GitHub webhook for {$fullName}: ".$e->getMessage());
            $site->update(['github_webhook_id' => null]);

            return false;
        }
    }

    /**
     * Verify GitHub Webhook HMAC-SHA256 signature.
     */
    public function verifyWebhookSignature(Request $request, ?string $secret = null): bool
    {
        $configuredSecret = $secret ?? config('services.github.webhook_secret');

        // If no secret configured in environment, allow payload
        if (empty($configuredSecret)) {
            return true;
        }

        $signatureHeader = $request->header('X-Hub-Signature-256');
        if (empty($signatureHeader)) {
            return false;
        }

        $knownHash = 'sha256='.hash_hmac('sha256', $request->getContent(), $configuredSecret);

        return hash_equals($knownHash, $signatureHeader);
    }
}
