<?php

use App\Models\Deployment;
use App\Models\Site;
use App\Services\SiteDeploymentService;
use Flux\Flux;
use Livewire\Component;

new class extends Component
{
    public $siteId;
    public $site;
    public $isRollingBack = false;
    public $selectedDeploymentId = null;

    public function mount($siteId)
    {
        $this->siteId = $siteId;
        $this->site = auth()->user()->sites()->findOrFail($this->siteId);
    }

    public function createManualSnapshot(SiteDeploymentService $deploymentService)
    {
        $deployment = $this->site->deployments()->create([
            'status' => 'success',
            'source' => 'manual',
            'commit_message' => 'Manual snapshot created from dashboard',
            'metadata' => [
                'manual' => true,
            ],
        ]);

        $deploymentService->createDeploymentSnapshot($this->site, $deployment);

        Flux::toast('Manual deployment snapshot created successfully.');
        $this->site->refresh();
    }

    public function rollback(string $deploymentId, SiteDeploymentService $deploymentService)
    {
        try {
            $targetDeployment = $this->site->deployments()->findOrFail($deploymentId);
            $newDeployment = $deploymentService->rollbackToDeployment($this->site, $targetDeployment, auth()->user());

            Flux::toast('Site successfully rolled back to deployment #' . substr($targetDeployment->id, 0, 8));
            $this->site->refresh();
        } catch (\Throwable $e) {
            Flux::toast('Rollback failed: ' . $e->getMessage(), variant: 'danger');
        }
    }

    public function formatBytes(?int $bytes, int $precision = 2): string
    {
        if (! $bytes || $bytes <= 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $power = min((int) floor(log($bytes, 1024)), count($units) - 1);

        return round($bytes / pow(1024, $power), $precision) . ' ' . $units[$power];
    }

    public function with()
    {
        $deployments = $this->site->deployments()->latest()->get();
        $latestSuccessfulId = $deployments->firstWhere('status', 'success')?->id;

        return [
            'deployments' => $deployments,
            'latestSuccessfulId' => $latestSuccessfulId,
        ];
    }
};
?>

<div>
    <!-- Subnavigation Header -->
    <x-site-subnav :site="$site" current="deployments" />

    <div class="space-y-6">
        <!-- Top Info / Action Banner -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 p-5 rounded-2xl bg-gradient-to-r from-zinc-50 to-white dark:from-zinc-900/60 dark:to-zinc-900/20 border border-zinc-200/80 dark:border-zinc-800">
            <div>
                <h2 class="text-base font-semibold text-zinc-900 dark:text-zinc-100 flex items-center gap-2">
                    <flux:icon.archive-box class="size-5 text-indigo-500" />
                    <span>Deployment Snapshots & Rollback History</span>
                </h2>
                <p class="text-xs text-zinc-500 mt-1 max-w-xl">
                    Every deployment automatically captures an immutable file archive. You can instantly restore your site to any previous point in time.
                </p>
            </div>

            <button 
                type="button" 
                wire:click="createManualSnapshot" 
                class="inline-flex items-center justify-center gap-2 px-4 py-2 text-xs font-semibold rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white shadow-xs transition-colors cursor-pointer"
            >
                <flux:icon.camera class="size-4" />
                <span>Take Snapshot Now</span>
            </button>
        </div>

        <!-- Deployments List -->
        @if($deployments->isEmpty())
            <flux:card class="text-center py-12 px-4">
                <div class="size-12 rounded-2xl bg-zinc-100 dark:bg-zinc-800/80 text-zinc-400 flex items-center justify-center mx-auto mb-3">
                    <flux:icon.archive-box class="size-6" />
                </div>
                <h3 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">No Deployment Snapshots Yet</h3>
                <p class="text-xs text-zinc-500 mt-1 max-w-md mx-auto">
                    Take a manual snapshot of your active files now to start recording deployment history and enable instant rollbacks.
                </p>
                <div class="mt-4">
                    <button 
                        type="button" 
                        wire:click="createManualSnapshot" 
                        class="inline-flex items-center gap-2 px-4 py-2 text-xs font-semibold rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white shadow-xs transition-colors cursor-pointer"
                    >
                        <flux:icon.camera class="size-4" />
                        <span>Create Initial Snapshot</span>
                    </button>
                </div>
            </flux:card>
        @else
            <div class="space-y-3">
                @foreach($deployments as $index => $deployment)
                    @php
                        $isActive = ($deployment->id === $latestSuccessfulId);
                        $metadata = $deployment->metadata ?? [];
                        $fileCount = $metadata['file_count'] ?? null;
                        $totalBytes = $metadata['total_bytes'] ?? null;
                        $source = $deployment->source ?: 'manual';
                    @endphp
                    <flux:card class="transition-all hover:border-zinc-300 dark:hover:border-zinc-700 p-4 sm:p-5 {{ $isActive ? 'ring-2 ring-emerald-500/20 dark:ring-emerald-500/30 border-emerald-500/30' : '' }}">
                        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                            <!-- Left: Status, Info, Message -->
                            <div class="space-y-2 flex-1 min-w-0">
                                <div class="flex items-center gap-2.5 flex-wrap">
                                    @if($isActive)
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-400 border border-emerald-200/60 dark:border-emerald-800/40">
                                            <span class="size-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                            Active Release
                                        </span>
                                    @endif

                                    <!-- Source Badge -->
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[11px] font-medium bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300">
                                        @if($source === 'github_webhook')
                                            <flux:icon.folder-git-2 class="size-3 text-zinc-500" />
                                            GitHub Push
                                        @elseif($source === 'upload')
                                            <flux:icon.arrow-up-tray class="size-3 text-indigo-500" />
                                            ZIP Upload
                                        @elseif($source === 'editor')
                                            <flux:icon.pencil-square class="size-3 text-amber-500" />
                                            Code Studio
                                        @elseif($source === 'rollback')
                                            <flux:icon.arrow-path class="size-3 text-purple-500" />
                                            Rollback
                                        @elseif($source === 'api')
                                            <flux:icon.command-line class="size-3 text-blue-500" />
                                            CLI / API
                                        @else
                                            <flux:icon.camera class="size-3 text-zinc-500" />
                                            Manual Snapshot
                                        @endif
                                    </span>

                                    <!-- Deployment Status -->
                                    <span class="text-[11px] font-mono px-1.5 py-0.5 rounded {{ $deployment->status === 'success' ? 'text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/30' : 'text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-950/30' }}">
                                        {{ ucfirst($deployment->status) }}
                                    </span>

                                    <!-- Deployment ID -->
                                    <span class="text-xs font-mono text-zinc-400 dark:text-zinc-500">
                                        #{{ substr($deployment->id, 0, 8) }}
                                    </span>
                                </div>

                                <div class="text-sm font-medium text-zinc-900 dark:text-zinc-100 truncate">
                                    {{ $deployment->commit_message ?: 'Deployment #' . substr($deployment->id, 0, 8) }}
                                </div>

                                <div class="flex items-center gap-4 text-xs text-zinc-500 dark:text-zinc-400 flex-wrap">
                                    <span class="flex items-center gap-1" title="{{ $deployment->created_at->format('Y-m-d H:i:s T') }}">
                                        <flux:icon.clock class="size-3.5 text-zinc-400" />
                                        {{ $deployment->created_at->diffForHumans() }}
                                    </span>

                                    @if($fileCount !== null)
                                        <span class="flex items-center gap-1">
                                            <flux:icon.document-duplicate class="size-3.5 text-zinc-400" />
                                            {{ $fileCount }} {{ Str::plural('file', $fileCount) }}
                                        </span>
                                    @endif

                                    @if($totalBytes !== null)
                                        <span class="flex items-center gap-1">
                                            <flux:icon.circle-stack class="size-3.5 text-zinc-400" />
                                            {{ $this->formatBytes($totalBytes) }}
                                        </span>
                                    @endif

                                    @if($deployment->commit_hash)
                                        <span class="font-mono text-[11px] bg-zinc-100 dark:bg-zinc-800 px-1.5 py-0.5 rounded">
                                            {{ substr($deployment->commit_hash, 0, 7) }}
                                        </span>
                                    @endif
                                </div>
                            </div>

                            <!-- Right: Actions -->
                            <div class="flex items-center gap-2 pt-2 lg:pt-0 border-t lg:border-t-0 border-zinc-100 dark:border-zinc-800">
                                @if(!$isActive && $deployment->status === 'success')
                                    <button 
                                        type="button" 
                                        wire:click="rollback('{{ $deployment->id }}')" 
                                        wire:confirm="Are you sure you want to rollback to deployment #{{ substr($deployment->id, 0, 8) }}? This will replace your active site files."
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg bg-zinc-100 hover:bg-purple-50 dark:bg-zinc-800 dark:hover:bg-purple-950/30 text-zinc-800 hover:text-purple-600 dark:text-zinc-200 dark:hover:text-purple-400 border border-zinc-200 dark:border-zinc-700 hover:border-purple-200 dark:hover:border-purple-800 transition-colors cursor-pointer"
                                        title="Restore site files to this version"
                                    >
                                        <flux:icon.arrow-path class="size-3.5" />
                                        <span>Rollback</span>
                                    </button>
                                @endif

                                @if(!empty($metadata['snapshot_path']))
                                    <a 
                                        href="{{ route('dashboard.sites.deployments.download', [$site, $deployment]) }}" 
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg bg-zinc-100 hover:bg-zinc-200 dark:bg-zinc-800 dark:hover:bg-zinc-700 text-zinc-800 dark:text-zinc-200 transition-colors"
                                        title="Download snapshot ZIP archive"
                                    >
                                        <flux:icon.arrow-down-tray class="size-3.5" />
                                        <span>Snapshot ZIP</span>
                                    </a>
                                @endif
                            </div>
                        </div>
                    </flux:card>
                @endforeach
            </div>
        @endif
    </div>
</div>
