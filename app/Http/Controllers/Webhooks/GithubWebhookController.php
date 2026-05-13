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
        
        // Basic verification: Check if it's a push to the default branch
        if (!isset($payload['repository']['full_name']) || !isset($payload['ref'])) {
            return response()->json(['message' => 'Invalid payload'], 400);
        }

        $repoFullName = $payload['repository']['full_name'];
        $ref = $payload['ref'];
        $defaultBranch = $payload['repository']['default_branch'];

        if ($ref !== "refs/heads/{$defaultBranch}") {
            return response()->json(['message' => 'Not a push to default branch'], 200);
        }

        $sites = Site::where('github_repo_full_name', $repoFullName)
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

            $fullName = $site->github_repo_full_name;
            $branch = $site->github_branch;
            $token = $user->github_token;

            $archiveUrl = "https://api.github.com/repos/{$fullName}/zipball/{$branch}";
            $response = Http::withToken($token)->get($archiveUrl);

            if ($response->failed()) throw new \Exception("Failed to download archive");

            $tempPath = storage_path("app/temp_webhook_{$site->slug}.zip");
            file_put_contents($tempPath, $response->body());

            // Extract to public storage
            $zip = new \ZipArchive;
            if ($zip->open($tempPath) === TRUE) {
                $extractPath = storage_path("app/public/{$site->path}");
                
                // Clear old files
                Storage::disk('public')->deleteDirectory($site->path);
                mkdir($extractPath, 0755, true);

                $zip->extractTo($extractPath);
                $zip->close();

                // Hoisting logic
                $items = array_diff(scandir($extractPath), ['.', '..']);
                if (count($items) === 1) {
                    $innerDir = $extractPath . '/' . array_shift($items);
                    if (is_dir($innerDir)) {
                        $files = array_diff(scandir($innerDir), ['.', '..']);
                        foreach ($files as $file) {
                            rename("{$innerDir}/{$file}", "{$extractPath}/{$file}");
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
                'description' => "Auto-deployed from GitHub: {$repoFullName}",
                'ip_address' => '127.0.0.1',
            ]);

        } catch (\Exception $e) {
            $site->deployments()->create([
                'status' => 'failed',
                'source' => 'github_webhook',
                'commit_message' => "Auto-deploy failed: " . $e->getMessage(),
            ]);
        }
    }
}
