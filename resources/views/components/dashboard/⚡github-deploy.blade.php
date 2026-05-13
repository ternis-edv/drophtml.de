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
                $this->error = "GitHub token not found. Please reconnect your account.";
                $this->loading = false;
                return;
            }

            // Fetch user repos
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
            
            // Download archive
            $archiveUrl = "https://api.github.com/repos/{$fullName}/zipball/{$repo['default_branch']}";
            $response = Http::withToken($token)->get($archiveUrl);
            
            if ($response->failed()) {
                throw new \Exception("Failed to download repository archive.");
            }

            $tempPath = storage_path("app/temp_{$slug}.zip");
            file_put_contents($tempPath, $response->body());

            // Extract
            $zip = new \ZipArchive;
            if ($zip->open($tempPath) === TRUE) {
                // GitHub ZIPs contain a root folder, we need to extract contents carefully
                $extractPath = storage_path("app/public/{$path}");
                if (!file_exists($extractPath)) {
                    mkdir($extractPath, 0755, true);
                }
                
                $zip->extractTo($extractPath);
                $zip->close();
                
                // Move contents up one level if there's only one directory (GitHub's default)
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
                if (Storage::disk('public')->exists("{$path}/index.html")) {
                    $entryPath = 'index.html';
                } else {
                    $files = Storage::disk('public')->files($path);
                    $htmlFiles = array_filter($files, fn($f) => str_ends_with(strtolower($f), '.html'));
                    if (!empty($htmlFiles)) {
                        $entryPath = basename(reset($htmlFiles));
                    }
                }
            } else {
                throw new \Exception('Could not extract repository.');
            }

            unlink($tempPath);

            $site = Site::create([
                'slug' => $slug,
                'original_name' => $repo['name'],
                'path' => $path,
                'entry_path' => $entryPath,
                'user_id' => auth()->id(),
                'expires_at' => now()->addDays(30),
                'github_repo_full_name' => $fullName,
                'github_branch' => $repo['default_branch'],
                'auto_deploy' => $this->autoDeploy,
            ]);

            // Track deployment
            $site->deployments()->create([
                'status' => 'success',
                'source' => 'github_manual',
                'commit_hash' => $repo['default_branch'],
                'commit_message' => "Initial deployment from GitHub",
            ]);

            if ($this->autoDeploy) {
                $this->setupWebhook($fullName, $token);
            }

            ActivityLog::create([
                'user_id' => auth()->id(),
                'site_id' => $site->id,
                'action' => 'github_deployed',
                'description' => "Deployed repository: {$fullName}",
                'ip_address' => request()->ip(),
            ]);

            Flux::toast("Repository deployed successfully!");
            $this->dispatch('site-published', slug: $slug);
        } catch (\Exception $e) {
            Flux::toast(variant: 'danger', text: $e->getMessage());
        }

        $this->deploying = false;
    }

    public function setupWebhook($fullName, $token)
    {
        try {
            $webhookUrl = url('/api/webhooks/github');
            
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
                Site::where('github_repo_full_name', $fullName)
                    ->where('user_id', auth()->id())
                    ->latest()
                    ->first()
                    ?->update(['github_webhook_id' => $hookData['id'] ?? null]);
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning("Failed to setup GitHub webhook for {$fullName}: " . $e->getMessage());
        }
    }

    public function render()
    {
        $filteredRepos = collect($this->repositories)
            ->filter(fn($repo) => empty($this->search) || str_contains(strtolower($repo['full_name']), strtolower($this->search)))
            ->values();

        return view('components.dashboard.⚡github-deploy', [
            'filteredRepos' => $filteredRepos,
        ]);
    }
};
?>

<div class="space-y-4">
    @if ($error)
        <div class="p-4 bg-red-50 text-red-600 rounded-lg border border-red-100">
            {{ $error }}
        </div>
    @endif

    <div class="relative">
        <flux:input wire:model.live="search" placeholder="Search repositories..." icon="magnifying-glass" />
    </div>

    <div class="flex items-center gap-2 py-2">
        <flux:checkbox wire:model="autoDeploy" :label="__('Auto-deploy on push (default branch)')" />
    </div>

    <div class="max-h-[400px] overflow-y-auto space-y-2 pr-2">
        @if ($loading)
            <div class="flex items-center justify-center py-12">
                <flux:icon.loading class="w-8 h-8" />
            </div>
        @else
            @forelse($filteredRepos as $repo)
                <div class="flex items-center justify-between p-3 border border-zinc-200 dark:border-zinc-800 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors">
                    <div class="flex items-center gap-3">
                        <flux:icon.folder class="w-5 h-5 text-zinc-400" />
                        <div>
                            <div class="font-medium text-sm">{{ $repo['full_name'] }}</div>
                            <div class="text-xs text-zinc-500">{{ $repo['description'] ?? 'No description' }}</div>
                        </div>
                    </div>
                    <flux:button 
                        wire:click="deploy('{{ $repo['full_name'] }}')" 
                        size="sm" 
                        variant="primary"
                        :disabled="$deploying"
                    >
                        Deploy
                    </flux:button>
                </div>
            @empty
                <div class="text-center py-12 text-zinc-500">
                    No repositories found.
                </div>
            @endforelse
        @endif
    </div>

    @if ($deploying)
        <div class="fixed inset-0 bg-white/50 dark:bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center flex-col gap-4">
            <flux:icon.loading class="w-12 h-12 text-blue-600" />
            <p class="font-bold">Deploying repository...</p>
            <p class="text-sm text-zinc-500">Downloading and extracting files</p>
        </div>
    @endif
</div>