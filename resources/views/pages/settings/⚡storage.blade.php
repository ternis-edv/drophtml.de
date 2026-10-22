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
                <div class="p-4 rounded-2xl bg-red-500/10 border border-red-500/30 text-red-800 dark:text-red-200 flex items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <flux:icon.exclamation-triangle class="size-5 text-red-600 dark:text-red-400 shrink-0" />
                        <div>
                            <p class="font-bold text-sm tracking-tight">Storage Quota Exceeded</p>
                            <p class="text-xs text-red-700/80 dark:text-red-300/80 mt-0.5">
                                Your account is currently using {{ $stats['formatted']['total_used'] }} of your {{ $stats['formatted']['quota'] }} quota. New uploads and site deployments will be blocked until space is freed.
                            </p>
                        </div>
                    </div>
                </div>
            @elseif($stats['is_near_quota'])
                <div class="p-4 rounded-2xl bg-amber-500/10 border border-amber-500/30 text-amber-800 dark:text-amber-200 flex items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <flux:icon.exclamation-circle class="size-5 text-amber-600 dark:text-amber-400 shrink-0" />
                        <div>
                            <p class="font-bold text-sm tracking-tight">Approaching Storage Limit</p>
                            <p class="text-xs text-amber-700/80 dark:text-amber-300/80 mt-0.5">
                                You have consumed {{ $stats['used_percentage'] }}% of your available storage quota. Consider deleting unused deployments or assets.
                            </p>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Storage Overview Meter Card -->
            <div class="p-5 sm:p-6 rounded-2xl bg-zinc-50 dark:bg-zinc-950/40 border border-zinc-200 dark:border-zinc-800 space-y-5">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div>
                        <div class="text-xs font-semibold uppercase tracking-wider text-zinc-500">Account Storage Capacity</div>
                        <div class="flex items-baseline gap-2 mt-1">
                            <span class="text-2xl sm:text-3xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100 font-mono">{{ $stats['formatted']['total_used'] }}</span>
                            <span class="text-sm font-medium text-zinc-500">/ {{ $stats['formatted']['quota'] }} ({{ $stats['used_percentage'] }}% used)</span>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            wire:click="recalculateAll"
                            wire:loading.attr="disabled"
                            class="btn-secondary px-3 py-1.5 text-xs"
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
                    <div class="h-3.5 w-full rounded-full bg-zinc-200 dark:bg-zinc-800 overflow-hidden flex shadow-inner">
                        <div style="width: {{ $mediaPct }}%" class="bg-accent transition-all duration-500" title="Cloud Media: {{ $stats['formatted']['cloud_media'] }}"></div>
                        <div style="width: {{ $codePct }}%" class="bg-accent/60 transition-all duration-500" title="Hosted Code: {{ $stats['formatted']['site_code'] }}"></div>
                        <div style="width: {{ $snapPct }}%" class="bg-accent/30 transition-all duration-500" title="Snapshots: {{ $stats['formatted']['snapshots'] }}"></div>
                    </div>

                    <div class="flex flex-wrap items-center gap-4 text-xs font-medium pt-1">
                        <div class="flex items-center gap-1.5">
                            <span class="size-2.5 rounded-full bg-accent"></span>
                            <span class="text-zinc-600 dark:text-zinc-400">Cloud Media:</span>
                            <span class="font-bold text-zinc-900 dark:text-zinc-100 font-mono">{{ $stats['formatted']['cloud_media'] }}</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span class="size-2.5 rounded-full bg-accent/60"></span>
                            <span class="text-zinc-600 dark:text-zinc-400">Hosted Code:</span>
                            <span class="font-bold text-zinc-900 dark:text-zinc-100 font-mono">{{ $stats['formatted']['site_code'] }}</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span class="size-2.5 rounded-full bg-accent/30 ring-1 ring-accent/40"></span>
                            <span class="text-zinc-600 dark:text-zinc-400">Snapshots:</span>
                            <span class="font-bold text-zinc-900 dark:text-zinc-100 font-mono">{{ $stats['formatted']['snapshots'] }}</span>
                        </div>
                        <div class="flex items-center gap-1.5 ml-auto text-zinc-500 dark:text-zinc-400">
                            <span>Available:</span>
                            <span class="font-bold text-zinc-700 dark:text-zinc-300 font-mono">{{ $stats['formatted']['remaining'] }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- File Type Distribution Grid -->
            <div>
                <div class="flex items-center gap-2 pb-3 mb-4 border-b border-zinc-100 dark:border-zinc-800">
                    <flux:icon.chart-pie class="size-4.5 text-accent dark:text-accent-content" />
                    <h3 class="font-bold text-sm tracking-tight text-zinc-900 dark:text-zinc-100">Storage Distribution by File Category</h3>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
                    @foreach($stats['file_types'] as $key => $ft)
                        <div class="p-3.5 rounded-xl bg-zinc-50 dark:bg-zinc-950/40 border border-zinc-200 dark:border-zinc-800 flex flex-col justify-between">
                            <div class="text-[11px] font-semibold text-zinc-500 uppercase tracking-wide truncate">{{ $ft['label'] }}</div>
                            <div class="mt-2 font-mono font-bold text-base text-zinc-900 dark:text-zinc-100">{{ $ft['formatted'] }}</div>
                            <div class="text-[10px] text-zinc-400 mt-0.5">{{ $ft['count'] }} {{ Str::plural('item', $ft['count']) }}</div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Per-Site Storage Consumption Table -->
            <div>
                <div class="flex items-center justify-between pb-3 mb-4 border-b border-zinc-100 dark:border-zinc-800">
                    <div class="flex items-center gap-2">
                        <flux:icon.squares-2x2 class="size-4.5 text-accent dark:text-accent-content" />
                        <h3 class="font-bold text-sm tracking-tight text-zinc-900 dark:text-zinc-100">Site Storage Breakdown</h3>
                    </div>
                    <span class="text-xs text-zinc-500">{{ count($stats['sites']) }} {{ Str::plural('site', count($stats['sites'])) }} total</span>
                </div>

                @if(empty($stats['sites']))
                    <div class="p-8 text-center rounded-2xl bg-zinc-50 dark:bg-zinc-950/40 border border-zinc-200 dark:border-zinc-800">
                        <flux:icon.globe-alt class="size-8 mx-auto text-zinc-400 mb-2" />
                        <p class="text-sm font-semibold text-zinc-700 dark:text-zinc-300">No sites deployed yet</p>
                        <p class="text-xs text-zinc-500 mt-1">Deploy a site to begin tracking site code and cloud media usage.</p>
                    </div>
                @else
                    <div class="overflow-x-auto rounded-2xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900/60">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-zinc-50/80 dark:bg-zinc-800/60 text-zinc-500 uppercase font-semibold text-[10px] tracking-wider border-b border-zinc-200/70 dark:border-zinc-800">
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
                            <tbody class="divide-y divide-zinc-200/60 dark:divide-zinc-800/60 font-medium">
                                @foreach($stats['sites'] as $s)
                                    <tr class="hover:bg-zinc-50/50 dark:hover:bg-zinc-800/40 transition-colors">
                                        <td class="px-4 py-3">
                                            <div class="font-bold tracking-tight text-zinc-900 dark:text-zinc-100 flex items-center gap-1.5">
                                                <span>{{ $s['original_name'] }}</span>
                                                <span class="text-[10px] font-mono text-zinc-400">({{ $s['slug'] }})</span>
                                            </div>
                                        </td>
                                        <td class="px-3 py-3 font-mono text-zinc-700 dark:text-zinc-300">{{ $s['formatted_code'] }}</td>
                                        <td class="px-3 py-3 font-mono text-accent dark:text-accent-content font-semibold">{{ $s['formatted_media'] }}</td>
                                        <td class="px-3 py-3 font-mono text-zinc-700 dark:text-zinc-300">{{ $s['formatted_snapshots'] }}</td>
                                        <td class="px-3 py-3 font-mono font-bold text-zinc-900 dark:text-zinc-100">{{ $s['formatted_total'] }}</td>
                                        <td class="px-3 py-3 font-mono text-zinc-500">{{ $s['percentage_of_used'] }}%</td>
                                        <td class="px-4 py-3 text-right">
                                            <div class="inline-flex items-center gap-1.5">
                                                <a 
                                                    href="{{ route('dashboard.sites.media', $s['id']) }}" 
                                                    class="p-1 rounded-lg hover:bg-zinc-200 dark:hover:bg-zinc-700 text-zinc-500 hover:text-zinc-900 dark:hover:text-zinc-100 transition-colors"
                                                    title="View Media"
                                                >
                                                    <flux:icon.photo class="size-3.5" />
                                                </a>
                                                <a 
                                                    href="{{ route('dashboard.sites.settings', $s['id']) }}" 
                                                    class="p-1 rounded-lg hover:bg-zinc-200 dark:hover:bg-zinc-700 text-zinc-500 hover:text-zinc-900 dark:hover:text-zinc-100 transition-colors"
                                                    title="Site Settings"
                                                >
                                                    <flux:icon.cog-6-tooth class="size-3.5" />
                                                </a>
                                                <button
                                                    type="button"
                                                    wire:click="recalculateSite('{{ $s['id'] }}')"
                                                    class="p-1 rounded-lg hover:bg-zinc-200 dark:hover:bg-zinc-700 text-zinc-500 hover:text-zinc-900 dark:hover:text-zinc-100 transition-colors cursor-pointer"
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
                    <div class="flex items-center gap-2 pb-3 mb-4 border-b border-zinc-100 dark:border-zinc-800">
                        <flux:icon.document class="size-4.5 text-accent dark:text-accent-content" />
                        <h3 class="font-bold text-sm tracking-tight text-zinc-900 dark:text-zinc-100">Top Storage Consumers</h3>
                    </div>

                    <div class="space-y-2">
                        @foreach($largestFiles as $f)
                            <div class="p-3 rounded-xl bg-zinc-50 dark:bg-zinc-950/40 border border-zinc-200 dark:border-zinc-800 flex items-center justify-between gap-3 text-xs">
                                <div class="flex items-center gap-2.5 min-w-0">
                                    <div class="size-7 rounded-lg bg-zinc-200/60 dark:bg-zinc-800 flex items-center justify-center shrink-0">
                                        <flux:icon.document class="size-3.5 text-zinc-500" />
                                    </div>
                                    <div class="min-w-0">
                                        <div class="font-semibold text-zinc-900 dark:text-zinc-100 truncate">{{ $f['name'] }}</div>
                                        <div class="text-[10px] text-zinc-400 truncate">{{ $f['location'] }} &bull; {{ $f['type'] }}</div>
                                    </div>
                                </div>
                                <div class="flex items-center gap-3 shrink-0">
                                    <span class="font-mono font-bold text-zinc-900 dark:text-zinc-100">{{ $f['formatted_size'] }}</span>
                                    @if(!empty($f['url']))
                                        <a href="{{ $f['url'] }}" target="_blank" class="p-1 rounded text-zinc-400 hover:text-accent dark:hover:text-accent-content transition-colors">
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
