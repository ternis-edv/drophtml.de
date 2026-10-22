<?php

namespace App\Jobs;

use App\Models\Site;
use App\Services\ActivityLogService;
use App\Services\GitHubService;
use App\Services\SiteDeploymentService;
use App\Services\SiteServingService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class DeployGitHubRepositoryJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Site $site,
        public array $payload = []
    ) {}

    /**
     * Execute the job.
     */
    public function handle(
        GitHubService $gitHubService,
        SiteDeploymentService $deploymentService,
        ActivityLogService $activityLogService
    ): void {
        $user = $this->site->user;
        if (! $user || ! $user->github_token) {
            Log::warning("Cannot auto-deploy site {$this->site->slug}: user has no GitHub token.");

            return;
        }

        $fullName = $this->site->github_repo_full_name;
        $branch = $this->site->github_branch ?: 'main';
        $token = $user->github_token;
        $tempPath = storage_path("app/temp_webhook_{$this->site->slug}_".uniqid().'.zip');

        try {
            // 1. Download zip archive
            $gitHubService->downloadRepositoryArchive($fullName, $branch, $token, $tempPath);

            // 2. Clear old files and extract safely
            Storage::disk('public')->deleteDirectory($this->site->path);
            Storage::disk('public')->makeDirectory($this->site->path);

            $destination = Storage::disk('public')->path($this->site->path);
            $deploymentService->extractZipSafely($tempPath, $destination);

            $this->site->update(['entry_path' => SiteServingService::detectEntryPath($this->site->path)]);

            // 3. Record deployment
            $deployment = $this->site->deployments()->create([
                'status' => 'success',
                'source' => 'github_webhook',
                'commit_hash' => $this->payload['after'] ?? null,
                'commit_message' => $this->payload['head_commit']['message'] ?? 'Auto-deploy from push',
                'metadata' => $this->payload,
            ]);

            $deploymentService->createDeploymentSnapshot($this->site, $deployment);

            // 4. Log activity
            $activityLogService->log(
                $user,
                $this->site,
                'github_auto_deployed',
                "Auto-deployed from GitHub: {$fullName} ({$branch})"
            );
        } catch (Throwable $e) {
            Log::error("GitHub auto-deploy failed for {$this->site->slug}: ".$e->getMessage());

            $this->site->deployments()->create([
                'status' => 'failed',
                'source' => 'github_webhook',
                'commit_message' => 'Auto-deploy failed: '.$e->getMessage(),
                'metadata' => ['error' => $e->getMessage()],
            ]);
        } finally {
            if (file_exists($tempPath)) {
                @unlink($tempPath);
            }
        }
    }
}
