<?php

use Livewire\Component;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Models\Site;
use App\Models\ActivityLog;

new class extends Component
{
    public $repositories = [];
    public $loading = true;
    public $error = null;
    public $deploying = false;
    public $search = '';
    public $autoDeploy = true;

    public function mount()
    {
        $this->fetchRepositories();
    }

    public function fetchRepositories()
    {
        $this->loading = true;
        $this->error = null;

        try {
            $token = auth()->user()->github_token;
            
            if (!$token) {
                $this->error = "GitHub account not connected or token expired. Please reconnect your account.";
                $this->loading = false;
                return;
            }

            // Fetch user repos (up to 100 recent)
            $response = Http::withToken($token)->get('https://api.github.com/user/repos', [
                'sort' => 'updated',
                'per_page' => 100,
            ]);

            if ($response->failed()) {
                throw new \Exception("Failed to fetch repositories: " . ($response->json()['message'] ?? $response->body()));
            }

            $this->repositories = $response->json();
        } catch (\Exception $e) {
            $this->error = $e->getMessage();
        }

        $this->loading = false;
    }

    public function deploy($fullName)
    {
        $this->deploying = true;
        
        try {
            $token = auth()->user()->github_token;
            $repo = collect($this->repositories)->firstWhere('full_name', $fullName);
            
            if (!$repo) throw new \Exception("Repository not found.");

            $slug = Str::random(8);
            $path = "sites/{$slug}";
            $defaultBranch = $repo['default_branch'] ?? 'main';
            
            // Download archive
            $archiveUrl = "https://api.github.com/repos/{$fullName}/zipball/{$defaultBranch}";
            $response = Http::withToken($token)->get($archiveUrl);
            
            if ($response->failed()) {
                throw new \Exception("Failed to download repository archive. Please check permissions.");
            }

            $tempPath = storage_path("app/temp_{$slug}.zip");
            file_put_contents($tempPath, $response->body());

            // Extract
            $zip = new \ZipArchive;
            if ($zip->open($tempPath) === TRUE) {
                $extractPath = Storage::disk('public')->path($path);
                Storage::disk('public')->makeDirectory($path);
                
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
            } else {
                throw new \Exception('Could not extract repository archive.');
            }

            if (file_exists($tempPath)) {
                unlink($tempPath);
            }

            $site = Site::create([
                'slug' => $slug,
                'original_name' => $repo['name'],
                'path' => $path,
                'user_id' => auth()->id(),
                'expires_at' => now()->addDays(30),
                'github_repo_full_name' => $fullName,
                'github_branch' => $defaultBranch,
                'auto_deploy' => $this->autoDeploy,
            ]);

            // Track deployment
            $site->deployments()->create([
                'status' => 'success',
                'source' => 'github_manual',
                'commit_hash' => $defaultBranch,
                'commit_message' => "Initial deployment from GitHub ({$fullName})",
            ]);

            if ($this->autoDeploy) {
                $this->setupWebhook($site, $token);
            }

            ActivityLog::create([
                'user_id' => auth()->id(),
                'site_id' => $site->id,
                'action' => 'github_deployed',
                'description' => "Deployed repository: {$fullName} ({$defaultBranch})",
                'ip_address' => request()->ip(),
            ]);

            Flux::toast("Repository deployed successfully!");
            $this->dispatch('site-published', slug: $slug);
        } catch (\Exception $e) {
            Flux::toast(variant: 'danger', text: $e->getMessage());
        }

        $this->deploying = false;
    }

    public function setupWebhook(Site $site, $token)
    {
        try {
            $fullName = $site->github_repo_full_name;
            $webhookUrl = url('/webhooks/github');
            
            $response = Http::withToken($token)->post("https://api.github.com/repos/{$fullName}/hooks", [
                'name' => 'web',
                'active' => true,
                'events' => ['push'],
                'config' => [
                    'url' => $webhookUrl,
                    'content_type' => 'json',
                    'insecure_ssl' => '0',
                ],
            ]);

            if ($response->successful()) {
                $hookData = $response->json();
                $site->update(['github_webhook_id' => $hookData['id'] ?? null]);
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning("Failed to setup GitHub webhook for {$site->slug}: " . $e->getMessage());
        }
    }

    public function render()
    {
        $filteredRepos = collect($this->repositories)
            ->filter(fn($repo) => empty($this->search) || str_contains(strtolower($repo['full_name'] ?? ''), strtolower($this->search)))
            ->values();

        return view('components.dashboard.⚡github-deploy', [
            'filteredRepos' => $filteredRepos,
        ]);
    }
};
?>

<div class="space-y-4">
    @if ($error)
        <div class="p-4 bg-red-50 dark:bg-red-950/40 text-red-600 dark:text-red-300 rounded-lg border border-red-200 dark:border-red-800 text-sm">
            {{ $error }}
        </div>
    @endif

    <div class="relative">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Search your repositories..." icon="magnifying-glass" />
    </div>

    <div class="flex items-center gap-2 py-2">
        <flux:checkbox wire:model="autoDeploy" label="Auto-deploy on push to default branch (Webhook)" />
    </div>

    <div class="max-h-[400px] overflow-y-auto space-y-2 pr-1">
        @if ($loading)
            <div class="flex items-center justify-center py-12">
                <flux:icon.arrow-path class="w-8 h-8 animate-spin text-indigo-500" />
            </div>
        @else
            @forelse($filteredRepos as $repo)
                <div class="flex items-center justify-between p-3 border border-zinc-200 dark:border-zinc-800 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors">
                    <div class="flex items-center gap-3 truncate mr-4">
                        <flux:icon.folder class="w-5 h-5 text-zinc-400 shrink-0" />
                        <div class="truncate">
                            <div class="font-medium text-sm text-zinc-900 dark:text-white truncate">{{ $repo['full_name'] }}</div>
                            <div class="text-xs text-zinc-500 truncate">{{ $repo['description'] ?? 'Branch: ' . ($repo['default_branch'] ?? 'main') }}</div>
                        </div>
                    </div>
                    <flux:button 
                        wire:click="deploy('{{ $repo['full_name'] }}')" 
                        size="sm" 
                        variant="primary"
                        :disabled="$deploying"
                        class="shrink-0"
                    >
                        Deploy
                    </flux:button>
                </div>
            @empty
                <div class="text-center py-12 text-zinc-500 text-sm">
                    No repositories found matching your search.
                </div>
            @endforelse
        @endif
    </div>

    @if ($deploying)
        <div class="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center flex-col gap-4 text-white">
            <flux:icon.arrow-path class="w-12 h-12 animate-spin text-indigo-400" />
            <p class="font-bold text-lg">Deploying repository...</p>
            <p class="text-sm text-zinc-400">Downloading archive, extracting files, and configuring site</p>
        </div>
    @endif
</div>