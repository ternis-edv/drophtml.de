@props([
    'site',
    'current' => 'editor',
])

@php
    $tabTitles = [
        'editor' => 'Code Studio',
        'stats' => 'Site Analytics',
        'deployments' => 'Deployments & Rollbacks',
        'domains' => 'Custom Domains',
        'settings' => 'Site Settings',
    ];
    $currentTitle = $tabTitles[$current] ?? ucfirst($current);
@endphp

<div class="mb-6 space-y-4">
    <!-- Breadcrumbs & Status Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <flux:breadcrumbs>
                <flux:breadcrumbs.item :href="route('dashboard')" wire:navigate>Dashboard</flux:breadcrumbs.item>
                <flux:breadcrumbs.item>{{ $site->original_name ?: $site->slug }}</flux:breadcrumbs.item>
                <flux:breadcrumbs.item>{{ $currentTitle }}</flux:breadcrumbs.item>
            </flux:breadcrumbs>
            <div class="flex items-center gap-3 mt-1.5 flex-wrap">
                <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100">
                    {{ $currentTitle }} <span class="text-zinc-400 dark:text-zinc-500 font-normal text-lg">({{ $site->original_name ?: $site->slug }})</span>
                </h1>
                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium {{ $site->status === 'active' ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-400 border border-emerald-200/60 dark:border-emerald-800/40' : 'bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-400 border border-amber-200/60 dark:border-amber-800/40' }}">
                    <span class="size-1.5 rounded-full {{ $site->status === 'active' ? 'bg-emerald-500 animate-pulse' : 'bg-amber-500' }}"></span>
                    {{ ucfirst($site->status) }}
                </span>
                @if($site->is_permanent)
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium bg-purple-50 text-purple-700 dark:bg-purple-950/40 dark:text-purple-300 border border-purple-200/60 dark:border-purple-800/40">
                        Permanent
                    </span>
                @elseif($site->expires_at)
                    <span class="text-xs text-zinc-500 dark:text-zinc-400">
                        Expires {{ $site->expires_at->diffForHumans() }}
                    </span>
                @endif
            </div>
        </div>

        <!-- Quick Actions on Top Right -->
        <div class="flex items-center gap-2">
            <a href="{{ url('/s/' . $site->slug) }}" target="_blank" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg bg-zinc-100 hover:bg-zinc-200 dark:bg-zinc-800 dark:hover:bg-zinc-700 text-zinc-800 dark:text-zinc-200 transition-colors shadow-xs">
                <flux:icon.arrow-top-right-on-square class="size-3.5" />
                <span>Visit Live Site</span>
            </a>
            <a href="{{ route('dashboard.sites.download', $site) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg bg-zinc-100 hover:bg-zinc-200 dark:bg-zinc-800 dark:hover:bg-zinc-700 text-zinc-800 dark:text-zinc-200 transition-colors shadow-xs" title="Download current files as ZIP">
                <flux:icon.arrow-down-tray class="size-3.5" />
                <span class="hidden sm:inline">Download ZIP</span>
            </a>
        </div>
    </div>

    <!-- Navigation Tab Bar -->
    <div class="border-b border-zinc-200 dark:border-zinc-800">
        <nav class="-mb-px flex space-x-2 sm:space-x-4 overflow-x-auto" aria-label="Tabs">
            <a 
                href="{{ route('dashboard.sites.edit', $site) }}" 
                wire:navigate 
                class="inline-flex items-center gap-2 border-b-2 py-2.5 px-3 text-xs sm:text-sm font-medium transition-colors whitespace-nowrap {{ $current === 'editor' ? 'border-indigo-600 text-indigo-600 dark:border-indigo-400 dark:text-indigo-400' : 'border-transparent text-zinc-500 hover:border-zinc-300 hover:text-zinc-700 dark:text-zinc-400 dark:hover:border-zinc-600 dark:hover:text-zinc-200' }}"
            >
                <flux:icon.pencil-square class="size-4" />
                <span>Code Studio</span>
            </a>

            <a 
                href="{{ route('dashboard.sites.stats', $site) }}" 
                wire:navigate 
                class="inline-flex items-center gap-2 border-b-2 py-2.5 px-3 text-xs sm:text-sm font-medium transition-colors whitespace-nowrap {{ $current === 'stats' ? 'border-indigo-600 text-indigo-600 dark:border-indigo-400 dark:text-indigo-400' : 'border-transparent text-zinc-500 hover:border-zinc-300 hover:text-zinc-700 dark:text-zinc-400 dark:hover:border-zinc-600 dark:hover:text-zinc-200' }}"
            >
                <flux:icon.chart-bar-square class="size-4" />
                <span>Visitor Analytics</span>
            </a>

            <a 
                href="{{ route('dashboard.sites.deployments', $site) }}" 
                wire:navigate 
                class="inline-flex items-center gap-2 border-b-2 py-2.5 px-3 text-xs sm:text-sm font-medium transition-colors whitespace-nowrap {{ $current === 'deployments' ? 'border-indigo-600 text-indigo-600 dark:border-indigo-400 dark:text-indigo-400' : 'border-transparent text-zinc-500 hover:border-zinc-300 hover:text-zinc-700 dark:text-zinc-400 dark:hover:border-zinc-600 dark:hover:text-zinc-200' }}"
            >
                <flux:icon.archive-box class="size-4" />
                <span>Deployments & Rollbacks</span>
            </a>

            <a 
                href="{{ route('dashboard.sites.domains', $site) }}" 
                wire:navigate 
                class="inline-flex items-center gap-2 border-b-2 py-2.5 px-3 text-xs sm:text-sm font-medium transition-colors whitespace-nowrap {{ $current === 'domains' ? 'border-indigo-600 text-indigo-600 dark:border-indigo-400 dark:text-indigo-400' : 'border-transparent text-zinc-500 hover:border-zinc-300 hover:text-zinc-700 dark:text-zinc-400 dark:hover:border-zinc-600 dark:hover:text-zinc-200' }}"
            >
                <flux:icon.globe-alt class="size-4" />
                <span>Custom Domains</span>
            </a>

            <a 
                href="{{ route('dashboard.sites.settings', $site) }}" 
                wire:navigate 
                class="inline-flex items-center gap-2 border-b-2 py-2.5 px-3 text-xs sm:text-sm font-medium transition-colors whitespace-nowrap {{ $current === 'settings' ? 'border-indigo-600 text-indigo-600 dark:border-indigo-400 dark:text-indigo-400' : 'border-transparent text-zinc-500 hover:border-zinc-300 hover:text-zinc-700 dark:text-zinc-400 dark:hover:border-zinc-600 dark:hover:text-zinc-200' }}"
            >
                <flux:icon.cog class="size-4" />
                <span>Settings</span>
            </a>
        </nav>
    </div>
</div>
