<?php

use App\Models\Site;
use App\Services\StorageUsageService;
use Flux\Flux;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Storage & Usage')] class extends Component {
    public function recalculateAll(StorageUsageService $storageUsageService)
    {
        foreach (auth()->user()->sites as $site) {
            $storageUsageService->calculateSiteStorageBytes($site);
        }
        Flux::toast('Storage usage recalculated across all sites.');
    }

    public function recalculateSite(string $siteId, StorageUsageService $storageUsageService)
    {
        $site = auth()->user()->sites()->findOrFail($siteId);
        $storageUsageService->calculateSiteStorageBytes($site);
        Flux::toast("Recalculated storage for '{$site->slug}'.");
    }

    public function render(StorageUsageService $storageUsageService)
    {
        $user = auth()->user();
        $stats = $storageUsageService->getUserStorageStats($user);
        $largestFiles = $storageUsageService->getLargestFiles($user, 6);

        return view('pages.settings.⚡storage', [
            'stats' => $stats,
            'largestFiles' => $largestFiles,
        ]);
    }
};
?>

<section class="w-full">
    @include('partials.settings-heading')

    <flux:heading class="sr-only">{{ __('Storage & Usage') }}</flux:heading>

    <x-pages::settings.layout :heading="__('Storage & Usage')" :subheading="__('Monitor cloud assets, site code bundles, rollback snapshots, and account quotas')" :max-width="'max-w-4xl'">
        <div class="space-y-8 my-4">
            <!-- Quota Status Banner -->
            @if($stats['is_over_quota'])
                <div class="p-4 dashboard-rounded dashboard-danger-tint border dashboard-danger-border dashboard-danger flex items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <flux:icon.exclamation-triangle class="size-5 dashboard-danger shrink-0" />
                        <div>
                            <p class="font-medium text-sm tracking-tight">Storage Quota Exceeded</p>
                            <p class="text-xs dashboard-danger mt-0.5">
                                Your account is currently using {{ $stats['formatted']['total_used'] }} of your {{ $stats['formatted']['quota'] }} quota. New uploads and site deployments will be blocked until space is freed.
                            </p>
                        </div>
                    </div>
                </div>
            @elseif($stats['is_near_quota'])
                <div class="p-4 dashboard-rounded dashboard-warning-tint border dashboard-warning-border dashboard-warning flex items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <flux:icon.exclamation-circle class="size-5 dashboard-warning shrink-0" />
                        <div>
                            <p class="font-medium text-sm tracking-tight">Approaching Storage Limit</p>
                            <p class="text-xs dashboard-warning mt-0.5">
                                You have consumed {{ $stats['used_percentage'] }}% of your available storage quota. Consider deleting unused deployments or assets.
                            </p>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Storage Overview Meter Card -->
            <div class="dashboard-panel p-5 sm:p-6 space-y-5">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div>
                        <div class="text-xs font-medium uppercase tracking-wider dashboard-muted">Account Storage Capacity</div>
                        <div class="flex items-baseline gap-2 mt-1">
                            <span class="text-2xl sm:dashboard-stat-value font-mono">{{ $stats['formatted']['total_used'] }}</span>
                            <span class="text-sm font-medium dashboard-muted">/ {{ $stats['formatted']['quota'] }} ({{ $stats['used_percentage'] }}% used)</span>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            wire:click="recalculateAll"
                            wire:loading.attr="disabled"
                            class="dashboard-button px-3 py-1.5 text-xs"
                        >
                            <flux:icon.arrow-path wire:loading.class="animate-spin" class="size-3.5" />
                            <span>Recalculate Storage</span>
                        </button>
                    </div>
                </div>

                <!-- Multi-segment progress bar -->
                @php
                    $mediaPct = $stats['quota_bytes'] > 0 ? ($stats['breakdown']['cloud_media_bytes'] / $stats['quota_bytes']) * 100 : 0;
                    $codePct = $stats['quota_bytes'] > 0 ? ($stats['breakdown']['site_code_bytes'] / $stats['quota_bytes']) * 100 : 0;
                    $snapPct = $stats['quota_bytes'] > 0 ? ($stats['breakdown']['snapshots_bytes'] / $stats['quota_bytes']) * 100 : 0;
                @endphp
                <div class="space-y-2">
                    <div class="h-3.5 w-full rounded-full dashboard-surface overflow-hidden flex ">
                        <div style="width: {{ $mediaPct }}%" class="dashboard-accent-tint transition-all duration-500" title="Cloud Media: {{ $stats['formatted']['cloud_media'] }}"></div>
                        <div style="width: {{ $codePct }}%" class="dashboard-accent-tint transition-all duration-500" title="Hosted Code: {{ $stats['formatted']['site_code'] }}"></div>
                        <div style="width: {{ $snapPct }}%" class="dashboard-accent-tint transition-all duration-500" title="Snapshots: {{ $stats['formatted']['snapshots'] }}"></div>
                    </div>

                    <div class="flex flex-wrap items-center gap-4 text-xs font-medium pt-1">
                        <div class="flex items-center gap-1.5">
                            <span class="size-2.5 rounded-full dashboard-accent-tint"></span>
                            <span class="dashboard-muted">Cloud Media:</span>
                            <span class="font-medium dashboard-text font-mono">{{ $stats['formatted']['cloud_media'] }}</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span class="size-2.5 rounded-full dashboard-accent-tint"></span>
                            <span class="dashboard-muted">Hosted Code:</span>
                            <span class="font-medium dashboard-text font-mono">{{ $stats['formatted']['site_code'] }}</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span class="size-2.5 rounded-full dashboard-accent-tint "></span>
                            <span class="dashboard-muted">Snapshots:</span>
                            <span class="font-medium dashboard-text font-mono">{{ $stats['formatted']['snapshots'] }}</span>
                        </div>
                        <div class="flex items-center gap-1.5 ml-auto dashboard-muted">
                            <span>Available:</span>
                            <span class="font-medium dashboard-text font-mono">{{ $stats['formatted']['remaining'] }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- File Type Distribution Grid -->
            <div>
                <div class="flex items-center gap-2 pb-3 mb-4 border-b dashboard-border">
                    <flux:icon.chart-pie class="size-4.5 dashboard-accent " />
                    <h3 class="dashboard-section-title  text-sm  ">Storage Distribution by File Category</h3>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
                    @foreach($stats['file_types'] as $key => $ft)
                        <div class="dashboard-panel p-3.5 flex flex-col justify-between">
                            <div class="text-[11px] font-medium dashboard-muted uppercase tracking-wide truncate">{{ $ft['label'] }}</div>
                            <div class="mt-2 font-mono font-medium text-base dashboard-text">{{ $ft['formatted'] }}</div>
                            <div class="text-[10px] dashboard-muted mt-0.5">{{ $ft['count'] }} {{ Str::plural('item', $ft['count']) }}</div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Per-Site Storage Consumption Table -->
            <div>
                <div class="flex items-center justify-between pb-3 mb-4 border-b dashboard-border">
                    <div class="flex items-center gap-2">
                        <flux:icon.squares-2x2 class="size-4.5 dashboard-accent " />
                        <h3 class="dashboard-section-title  text-sm  ">Site Storage Breakdown</h3>
                    </div>
                    <span class="text-xs dashboard-muted">{{ count($stats['sites']) }} {{ Str::plural('site', count($stats['sites'])) }} total</span>
                </div>

                @if(empty($stats['sites']))
                    <div class="p-8 text-center dashboard-rounded dashboard-surface border dashboard-border">
                        <flux:icon.globe-alt class="size-8 mx-auto dashboard-muted mb-2" />
                        <p class="text-sm font-medium dashboard-text">No sites deployed yet</p>
                        <p class="text-xs dashboard-muted mt-1">Deploy a site to begin tracking site code and cloud media usage.</p>
                    </div>
                @else
                    <div class="dashboard-panel overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead class="dashboard-surface dashboard-muted uppercase font-medium text-[10px] tracking-wider border-b dashboard-border">
                                <tr>
                                    <th class="px-4 py-3">Site</th>
                                    <th class="px-3 py-3">Code Bundle</th>
                                    <th class="px-3 py-3">Cloud Media</th>
                                    <th class="px-3 py-3">Snapshots</th>
                                    <th class="px-3 py-3">Total Footprint</th>
                                    <th class="px-3 py-3">% of Used</th>
                                    <th class="px-4 py-3 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y dashboard-divide font-medium">
                                @foreach($stats['sites'] as $s)
                                    <tr class="dashboard-hover-surface transition-colors">
                                        <td class="px-4 py-3">
                                            <div class="font-medium tracking-tight dashboard-text flex items-center gap-1.5">
                                                <span>{{ $s['original_name'] }}</span>
                                                <span class="text-[10px] font-mono dashboard-muted">({{ $s['slug'] }})</span>
                                            </div>
                                        </td>
                                        <td class="px-3 py-3 font-mono dashboard-text">{{ $s['formatted_code'] }}</td>
                                        <td class="px-3 py-3 font-mono dashboard-accent  font-medium">{{ $s['formatted_media'] }}</td>
                                        <td class="px-3 py-3 font-mono dashboard-text">{{ $s['formatted_snapshots'] }}</td>
                                        <td class="px-3 py-3 font-mono font-medium dashboard-text">{{ $s['formatted_total'] }}</td>
                                        <td class="px-3 py-3 font-mono dashboard-muted">{{ $s['percentage_of_used'] }}%</td>
                                        <td class="px-4 py-3 text-right">
                                            <div class="inline-flex items-center gap-1.5">
                                                <a
                                                    href="{{ route('dashboard.sites.media', $s['id']) }}"
                                                    class="p-1 dashboard-control-rounded dashboard-hover-surface dashboard-muted dashboard-hover-text transition-colors"
                                                    title="View Media"
                                                >
                                                    <flux:icon.photo class="size-3.5" />
                                                </a>
                                                <a
                                                    href="{{ route('dashboard.sites.settings', $s['id']) }}"
                                                    class="p-1 dashboard-control-rounded dashboard-hover-surface dashboard-muted dashboard-hover-text transition-colors"
                                                    title="Site Settings"
                                                >
                                                    <flux:icon.cog-6-tooth class="size-3.5" />
                                                </a>
                                                <button
                                                    type="button"
                                                    wire:click="recalculateSite('{{ $s['id'] }}')"
                                                    class="p-1 dashboard-control-rounded dashboard-hover-surface dashboard-muted dashboard-hover-text transition-colors cursor-pointer"
                                                    title="Recalculate site files"
                                                >
                                                    <flux:icon.arrow-path class="size-3.5" />
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            <!-- Top Largest Files -->
            @if(!empty($largestFiles))
                <div>
                    <div class="flex items-center gap-2 pb-3 mb-4 border-b dashboard-border">
                        <flux:icon.document class="size-4.5 dashboard-accent " />
                        <h3 class="dashboard-section-title  text-sm  ">Top Storage Consumers</h3>
                    </div>

                    <div class="space-y-2">
                        @foreach($largestFiles as $f)
                            <div class="dashboard-panel p-3 flex items-center justify-between gap-3 text-xs">
                                <div class="flex items-center gap-2.5 min-w-0">
                                    <div class="size-7 dashboard-control-rounded dashboard-surface flex items-center justify-center shrink-0">
                                        <flux:icon.document class="size-3.5 dashboard-muted" />
                                    </div>
                                    <div class="min-w-0">
                                        <div class="font-medium dashboard-text truncate">{{ $f['name'] }}</div>
                                        <div class="text-[10px] dashboard-muted truncate">{{ $f['location'] }} &bull; {{ $f['type'] }}</div>
                                    </div>
                                </div>
                                <div class="flex items-center gap-3 shrink-0">
                                    <span class="font-mono font-medium dashboard-text">{{ $f['formatted_size'] }}</span>
                                    @if(!empty($f['url']))
                                        <a href="{{ $f['url'] }}" target="_blank" class="p-1 rounded dashboard-muted dashboard-accent  transition-colors">
                                            <flux:icon.arrow-top-right-on-square class="size-3.5" />
                                        </a>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </x-pages::settings.layout>
</section>
