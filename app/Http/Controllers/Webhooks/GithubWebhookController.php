<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\Site;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use App\Models\ActivityLog;

class GithubWebhookController extends Controller
{
    public function handle(Request $request)
    {
        $payload = $request->all();
        
        // Basic verification
        if (!isset($payload['repository']['full_name']) || !isset($payload['ref'])) {
            return response()->json(['message' => 'Invalid payload'], 400);
        }

        $repoFullName = $payload['repository']['full_name'];
        $ref = $payload['ref'];
        $branch = str_replace('refs/heads/', '', $ref);

        $sites = Site::where('github_repo_full_name', $repoFullName)
            ->where('github_branch', $branch)
            ->where('auto_deploy', true)
            ->get();

        foreach ($sites as $site) {
            $this->deploySite($site, $payload);
        }

        return response()->json(['message' => 'Deployments triggered'], 200);
    }

    protected function deploySite(Site $site, array $payload)
    {
        try {
            $user = $site->user;
            if (!$user || !$user->github_token) return;

            $tempPath = $this->downloadArchive($site, $user->github_token);
            $this->extractAndProcessArchive($tempPath, $site);

            if (file_exists($tempPath)) {
                unlink($tempPath);
            }

            $this->logDeployment($site, $payload);

        } catch (\Exception $e) {
            $this->logDeployment($site, $payload, $e);
        }
    }

    protected function downloadArchive(Site $site, string $token): string
    {
        $fullName = $site->github_repo_full_name;
        $branch = $site->github_branch;

        $archiveUrl = "https://api.github.com/repos/{$fullName}/zipball/{$branch}";
        $response = Http::withToken($token)->get($archiveUrl);

        if ($response->failed()) {
            throw new \Exception("Failed to download archive");
        }

        $tempPath = storage_path("app/temp_webhook_{$site->slug}.zip");
        file_put_contents($tempPath, $response->body());

        return $tempPath;
    }

    protected function extractAndProcessArchive(string $tempPath, Site $site): void
    {
        $zip = new \ZipArchive;
        if ($zip->open($tempPath) === TRUE) {
            $extractPath = Storage::disk('public')->path($site->path);

            // Clear old files
            Storage::disk('public')->deleteDirectory($site->path);
            Storage::disk('public')->makeDirectory($site->path);

            $zip->extractTo($extractPath);
            $zip->close();

            $this->hoistDirectory($extractPath);
            $this->determineAndUpdateEntryPath($site);
        }
    }

    protected function hoistDirectory(string $extractPath): void
    {
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
    }

    protected function determineAndUpdateEntryPath(Site $site): void
    {
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

    protected function logDeployment(Site $site, array $payload, ?\Exception $e = null): void
    {
        if ($e) {
            $site->deployments()->create([
                'status' => 'failed',
                'source' => 'github_webhook',
                'commit_message' => "Auto-deploy failed: " . $e->getMessage(),
            ]);
            return;
        }

        $site->deployments()->create([
            'status' => 'success',
            'source' => 'github_webhook',
            'commit_hash' => $payload['after'] ?? null,
            'commit_message' => $payload['head_commit']['message'] ?? 'Auto-deploy from push',
            'metadata' => $payload,
        ]);

        ActivityLog::create([
            'user_id' => $site->user->id,
            'site_id' => $site->id,
            'action' => 'github_auto_deployed',
            'description' => "Auto-deployed from GitHub: {$site->github_repo_full_name} ({$site->github_branch})",
            'ip_address' => request()->ip(),
        ]);
    }
}
