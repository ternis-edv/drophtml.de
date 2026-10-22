<?php

use App\Models\Site;
use App\Services\ActivityLogService;
use App\Services\GitHubService;
use App\Services\SiteDeploymentService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Component;

new class extends Component
{
    public $repositories = [];
    public $loading = true;
    public $error = null;
    public $deploying = false;
    public $search = '';
    public $autoDeploy = true;

    public function mount(GitHubService $gitHubService)
    {
        $this->fetchRepositories($gitHubService);
    }

    public function fetchRepositories(GitHubService $gitHubService)
    {
        $this->loading = true;
        $this->error = null;

        try {
            $token = auth()->user()->github_token;

            if (! $token) {
                $this->error = 'GitHub account not connected or token expired. Please reconnect your account.';
                $this->loading = false;

                return;
            }

            $this->repositories = $gitHubService->getUserRepositories($token, 100);
        } catch (\Throwable $e) {
            $this->error = $e->getMessage();
        }

        $this->loading = false;
    }

    public function deploy(
        string $fullName,
        GitHubService $gitHubService,
        SiteDeploymentService $deploymentService,
        ActivityLogService $activityLogService
    ) {
        $this->deploying = true;

        $tempPath = null;
        try {
            $token = auth()->user()->github_token;
            $repo = collect($this->repositories)->firstWhere('full_name', $fullName);

            if (! $repo) {
                throw new \Exception('Repository not found.');
            }

            $slug = Str::random(8);
            $path = "sites/{$slug}";
            $defaultBranch = $repo['default_branch'] ?? 'main';

            $tempPath = storage_path("app/temp_{$slug}.zip");
            $gitHubService->downloadRepositoryArchive($fullName, $defaultBranch, $token, $tempPath);

            $extractPath = Storage::disk('public')->path($path);
            Storage::disk('public')->makeDirectory($path);
            $deploymentService->extractZipSafely($tempPath, $extractPath);
            $entryPath = $deploymentService->detectEntryPath($path);

            $site = Site::create([
                'slug' => $slug,
                'original_name' => $repo['name'],
                'path' => $path,
                'entry_path' => $entryPath,
                'user_id' => auth()->id(),
                'expires_at' => now()->addDays(30),
                'github_repo_full_name' => $fullName,
                'github_branch' => $defaultBranch,
                'auto_deploy' => $this->autoDeploy,
            ]);

            $site->deployments()->create([
                'status' => 'success',
                'source' => 'github_manual',
                'commit_hash' => $defaultBranch,
                'commit_message' => "Initial deployment from GitHub ({$fullName})",
            ]);

            if ($this->autoDeploy) {
                $gitHubService->setupWebhook($site, $token);
            }

            $activityLogService->log(
                auth()->user(),
                $site,
                'github_deployed',
                "Deployed repository: {$fullName} ({$defaultBranch})"
            );

            Flux::toast('Repository deployed successfully!');
            $this->dispatch('site-published', slug: $slug);
        } catch (\Throwable $e) {
            Flux::toast(variant: 'danger', text: $e->getMessage());
        } finally {
            if ($tempPath && file_exists($tempPath)) {
                @unlink($tempPath);
            }
        }

        $this->deploying = false;
    }

    public function render()
    {
        $filteredRepos = collect($this->repositories)
            ->filter(fn ($repo) => empty($this->search) || str_contains(strtolower($repo['full_name'] ?? ''), strtolower($this->search)))
            ->values();

        return view('components.dashboard.⚡github-deploy', [
            'filteredRepos' => $filteredRepos,
        ]);
    }
};
?>

<div class="space-y-4">
    @if ($error)
        <div class="p-4 bg-red-50 dark:bg-red-950/40 text-red-600 dark:text-red-300 rounded-xl border border-red-200/80 dark:border-red-900/60 text-sm shadow-xs">
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
                <flux:icon.arrow-path class="w-8 h-8 animate-spin text-accent" />
            </div>
        @else
            @forelse($filteredRepos as $repo)
                <div class="flex items-center justify-between p-3 border border-zinc-200 dark:border-zinc-800 rounded-xl hover:border-accent/30 hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors duration-150">
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
        <div class="fixed inset-0 bg-zinc-950/70 backdrop-blur-sm z-50 flex items-center justify-center flex-col gap-4 text-white">
            <flux:icon.arrow-path class="w-12 h-12 animate-spin text-brand-300" />
            <p class="font-bold text-lg">Deploying repository...</p>
            <p class="text-sm text-zinc-400">Downloading archive, extracting files, and configuring site</p>
        </div>
    @endif
</div>