<?php

namespace App\Jobs;

use App\Models\Site;
use App\Models\ActivityLog;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Exception;

class DeployGithubSiteJob implements ShouldQueue
{
    use Queueable;

    protected $site;
    protected $payload;

    /**
     * Create a new job instance.
     */
    public function __construct(Site $site, array $payload)
    {
        $this->site = $site;
        $this->payload = $payload;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $site = $this->site;
        $payload = $this->payload;

        try {
            $user = $site->user;
            if (!$user || !$user->github_token) return;

            $fullName = $site->github_repo_full_name;
            $branch = $site->github_branch;
            $token = $user->github_token;

            $archiveUrl = "https://api.github.com/repos/{$fullName}/zipball/{$branch}";
            $response = Http::withToken($token)->get($archiveUrl);

            if ($response->failed()) throw new Exception("Failed to download archive");

            $tempPath = storage_path("app/temp_webhook_{$site->slug}.zip");
            file_put_contents($tempPath, $response->body());

            // Extract to public storage
            $zip = new \ZipArchive;
            if ($zip->open($tempPath) === TRUE) {
                $extractPath = Storage::disk('public')->path($site->path);

                // Clear old files
                Storage::disk('public')->deleteDirectory($site->path);
                Storage::disk('public')->makeDirectory($site->path);

                $zip->extractTo($extractPath);
                $zip->close();

                // Hoisting logic: if ZIP contains a single top-level directory, move its contents up
                $items = array_diff(scandir($extractPath), ['.', '..']);
                if (count($items) === 1) {
                    $innerDir = $extractPath . DIRECTORY_SEPARATOR . array_shift($items);
                    if (is_dir($innerDir)) {
                        $files = array_diff(scandir($innerDir), ['.', '..']);
                        foreach ($files as $file) {
                            rename("{$innerDir}" . DIRECTORY_SEPARATOR . "{$file}", "{$extractPath}" . DIRECTORY_SEPARATOR . "{$file}");
                        }
                        rmdir($innerDir);
                    }
                }

                // Determine entry path
                $entryPath = null;
                if (Storage::disk('public')->exists("{$site->path}/index.html")) {
                    $entryPath = 'index.html';
                } else {
                    $files = Storage::disk('public')->files($site->path);
                    $htmlFiles = array_filter($files, fn($f) => str_ends_with(strtolower($f), '.html'));
                    if (!empty($htmlFiles)) {
                        $entryPath = basename(reset($htmlFiles));
                    }
                }
                $site->update(['entry_path' => $entryPath]);
            }

            unlink($tempPath);

            $site->deployments()->create([
                'status' => 'success',
                'source' => 'github_webhook',
                'commit_hash' => $payload['after'] ?? null,
                'commit_message' => $payload['head_commit']['message'] ?? 'Auto-deploy from push',
                'metadata' => $payload,
            ]);

            ActivityLog::create([
                'user_id' => $user->id,
                'site_id' => $site->id,
                'action' => 'github_auto_deployed',
                'description' => "Auto-deployed from GitHub: {$fullName} ({$branch})",
                'ip_address' => request()->ip(), // Might be null or generic in a job context
            ]);

        } catch (Exception $e) {
            $site->deployments()->create([
                'status' => 'failed',
                'source' => 'github_webhook',
                'commit_message' => "Auto-deploy failed: " . $e->getMessage(),
            ]);
        }
    }
}
