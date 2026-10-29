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
        <div class="dashboard-panel p-6 sm:p-7 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h2 class="dashboard-section-title dashboard-section-title flex items-center gap-2.5">
                    <span class="dashboard-icon" aria-hidden="true">
                        <flux:icon.archive-box class="size-4.5" />
                    </span>
                    <span>Deployment Snapshots &amp; Rollback History</span>
                </h2>
                <p class="dashboard-lede mt-1.5 max-w-xl">
                    Every deployment automatically captures an immutable file archive. You can instantly restore your site to any previous point in time with zero downtime.
                </p>
            </div>

            <button
                type="button"
                wire:click="createManualSnapshot"
                class="dashboard-button dashboard-button-primary px-4 py-2 text-xs shrink-0"
                aria-label="{{ __('Take snapshot now') }}"
            >
                <flux:icon.camera class="size-4" />
                <span>Take Snapshot Now</span>
            </button>
        </div>

        <!-- Deployments List -->
        @if($deployments->isEmpty())
            <div class="dashboard-panel text-center py-16 px-4">
                <div class="dashboard-icon-lg mx-auto mb-3.5" aria-hidden="true">
                    <flux:icon.archive-box class="size-6" />
                </div>
                <h3 class="dashboard-section-title   ">No Deployment Snapshots Yet</h3>
                <p class="dashboard-lede mt-1 max-w-md mx-auto">
                    Take a manual snapshot of your active files now to start recording deployment history and enable instant rollbacks.
                </p>
                <div class="mt-5">
                    <button
                        type="button"
                        wire:click="createManualSnapshot"
                        class="dashboard-button dashboard-button-primary px-4 py-2 text-xs"
                    >
                        <flux:icon.camera class="size-4" />
                        <span>Create Initial Snapshot</span>
                    </button>
                </div>
            </div>
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
                    <div
                        class="dashboard-panel dashboard-panel-interactive  p-5 {{ $isActive ? '   dashboard-success-border dashboard-success-tint ' : '' }}"

                    >
                        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                            <!-- Left: Status, Info, Message -->
                            <div class="space-y-2.5 flex-1 min-w-0">
                                <div class="flex items-center gap-2.5 flex-wrap">
                                    @if($isActive)
                                        <span class="inline-flex items-center gap-1.5 px-3 py-0.5 rounded-full text-xs font-medium dashboard-success-tint dashboard-success border dashboard-success-border">
                                            <span class="size-2 rounded-full dashboard-success-solid" aria-hidden="true"></span>
                                            Active Release
                                        </span>
                                    @endif

                                    <!-- Source Badge -->
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 dashboard-control-rounded text-xs font-medium dashboard-raised dashboard-text">
                                        @if($source === 'github_webhook')
                                            <flux:icon.folder-git-2 class="size-3.5 dashboard-muted" />
                                            GitHub Push
                                        @elseif($source === 'upload')
                                            <flux:icon.arrow-up-tray class="size-3.5 dashboard-accent" />
                                            ZIP Upload
                                        @elseif($source === 'editor')
                                            <flux:icon.pencil-square class="size-3.5 dashboard-warning" />
                                            Code Studio
                                        @elseif($source === 'rollback')
                                            <flux:icon.arrow-path class="size-3.5 dashboard-accent" />
                                            Rollback
                                        @elseif($source === 'api')
                                            <flux:icon.command-line class="size-3.5 dashboard-accent" />
                                            CLI / API
                                        @else
                                            <flux:icon.camera class="size-3.5 dashboard-muted" />
                                            Manual Snapshot
                                        @endif
                                    </span>

                                    <!-- Deployment Status -->
                                    <span class="text-xs font-mono font-medium px-2 py-0.5 rounded-md {{ $deployment->status === 'success' ? 'dashboard-success  dashboard-success-tint ' : 'text-rose-700 dark:text-rose-400 bg-rose-50 dark:bg-rose-950/40' }}">
                                        {{ ucfirst($deployment->status) }}
                                    </span>

                                    <!-- Deployment ID -->
                                    <span class="text-xs font-mono dashboard-muted">
                                        #{{ substr($deployment->id, 0, 8) }}
                                    </span>
                                </div>

                                <div class="text-sm font-medium dashboard-text truncate">
                                    {{ $deployment->commit_message ?: 'Deployment #' . substr($deployment->id, 0, 8) }}
                                </div>

                                <div class="flex items-center gap-4 text-xs dashboard-muted flex-wrap font-mono">
                                    <span class="flex items-center gap-1.5" title="{{ $deployment->created_at->format('Y-m-d H:i:s T') }}">
                                        <flux:icon.clock class="size-3.5 dashboard-muted" />
                                        {{ $deployment->created_at->diffForHumans() }}
                                    </span>

                                    @if($fileCount !== null)
                                        <span class="flex items-center gap-1.5">
                                            <flux:icon.document-duplicate class="size-3.5 dashboard-muted" />
                                            {{ $fileCount }} {{ Str::plural('file', $fileCount) }}
                                        </span>
                                    @endif

                                    @if($totalBytes !== null)
                                        <span class="flex items-center gap-1.5">
                                            <flux:icon.circle-stack class="size-3.5 dashboard-muted" />
                                            {{ $this->formatBytes($totalBytes) }}
                                        </span>
                                    @endif

                                    @if($deployment->commit_hash)
                                        <span class="text-[11px] dashboard-raised px-1.5 py-0.5 rounded border dashboard-border">
                                            {{ substr($deployment->commit_hash, 0, 7) }}
                                        </span>
                                    @endif
                                </div>
                            </div>

                            <!-- Right: Actions -->
                            <div class="flex items-center gap-2 pt-3 lg:pt-0 border-t lg:border-t-0 dashboard-border">
                                @if(!$isActive && $deployment->status === 'success')
                                    <button
                                        type="button"
                                        wire:click="rollback('{{ $deployment->id }}')"
                                        wire:confirm="Are you sure you want to rollback to deployment #{{ substr($deployment->id, 0, 8) }}? This will restore your active site files to this snapshot."
                                        class="inline-flex items-center gap-1.5 px-3.5 py-1.5 text-xs font-medium dashboard-control-rounded dashboard-accent-tint dashboard-accent border dashboard-accent-border transition-colors cursor-pointer"
                                        title="Restore site files to this version"
                                        aria-label="{{ __('Rollback to deployment') }} #{{ substr($deployment->id, 0, 8) }}"
                                    >
                                        <flux:icon.arrow-path class="size-3.5" />
                                        <span>Rollback</span>
                                    </button>
                                @endif

                                @if(!empty($metadata['snapshot_path']))
                                    <a
                                        href="{{ route('dashboard.sites.deployments.download', [$site, $deployment]) }}"
                                        class="dashboard-button px-3.5 py-1.5 text-xs"
                                        title="Download snapshot ZIP archive"
                                        aria-label="{{ __('Download snapshot ZIP for') }} #{{ substr($deployment->id, 0, 8) }}"
                                    >
                                        <flux:icon.arrow-down-tray class="size-3.5" />
                                        <span>Snapshot ZIP</span>
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
