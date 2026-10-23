@props([
    'site',
    'current' => 'editor',
])

@php
    $tabTitles = [
        'editor' => 'Code Studio',
        'media' => 'Media Assets',
        'redirects' => 'Redirect URLs',
        'stats' => 'Site Analytics',
        'deployments' => 'Deployments & Rollbacks',
        'domains' => 'Custom Domains',
        'settings' => 'Site Settings',
    ];
    $currentTitle = $tabTitles[$current] ?? ucfirst($current);

    $tabs = [
        ['key' => 'editor', 'route' => 'dashboard.sites.edit', 'icon' => 'pencil-square', 'label' => 'Code Studio'],
        ['key' => 'media', 'route' => 'dashboard.sites.media', 'icon' => 'photo', 'label' => 'Media Assets', 'badge' => 'Cloud'],
        ['key' => 'redirects', 'route' => 'dashboard.sites.redirects', 'icon' => 'arrows-right-left', 'label' => 'Redirects'],
        ['key' => 'stats', 'route' => 'dashboard.sites.stats', 'icon' => 'chart-bar-square', 'label' => 'Site Analytics'],
        ['key' => 'deployments', 'route' => 'dashboard.sites.deployments', 'icon' => 'archive-box', 'label' => 'Deployments & Rollbacks'],
        ['key' => 'domains', 'route' => 'dashboard.sites.domains', 'icon' => 'globe-alt', 'label' => 'Custom Domains'],
        ['key' => 'settings', 'route' => 'dashboard.sites.settings', 'icon' => 'cog', 'label' => 'Settings'],
    ];
@endphp

<div class="mb-8 space-y-4">
    <!-- Top Site Hero Header Card -->
    <div class="panel rounded-3xl p-5 sm:p-6 relative overflow-hidden rise-in">
        <div class="absolute top-0 right-0 w-64 h-32 bg-accent/5 dark:bg-accent/10 rounded-full blur-2xl pointer-events-none"></div>

        <div class="relative z-10 flex flex-col lg:flex-row lg:items-center justify-between gap-5">
            <!-- Left: Breadcrumbs & Site Info -->
            <div class="space-y-2">
                <flux:breadcrumbs>
                    <flux:breadcrumbs.item :href="route('dashboard')" wire:navigate>Dashboard</flux:breadcrumbs.item>
                    <flux:breadcrumbs.item>{{ $site->original_name ?: $site->slug }}</flux:breadcrumbs.item>
                    <flux:breadcrumbs.item>{{ $currentTitle }}</flux:breadcrumbs.item>
                </flux:breadcrumbs>

                <div class="flex items-center gap-3.5 flex-wrap pt-1">
                    <div class="icon-tile shrink-0" aria-hidden="true">
                        {{ strtoupper(substr($site->original_name ?: $site->slug, 0, 1)) }}
                    </div>

                    <div>
                        <div class="flex items-center gap-2.5 flex-wrap">
                            <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100">
                                {{ $site->original_name ?: $site->slug }}
                            </h1>
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $site->status === 'active' ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20' : 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20' }}">
                                <span class="size-1.5 rounded-full {{ $site->status === 'active' ? 'bg-emerald-500 animate-pulse' : 'bg-amber-500' }}"></span>
                                {{ ucfirst($site->status) }}
                            </span>
                            @if($site->is_permanent)
                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-accent/10 text-accent dark:text-brand-300 border border-accent/20">
                                    <flux:icon.sparkles class="size-3" /> Permanent
                                </span>
                            @elseif($site->expires_at)
                                <span class="text-xs text-zinc-500 dark:text-zinc-400 font-medium">
                                    Expires {{ $site->expires_at->diffForHumans() }}
                                </span>
                            @endif
                        </div>

                        <div class="flex items-center gap-2 mt-0.5 text-xs text-zinc-500 dark:text-zinc-400 font-mono">
                            <span>/s/{{ $site->slug }}</span>
                            <button
                                type="button"
                                @click="navigator.clipboard.writeText('{{ url('/s/' . $site->slug) }}'); Flux.toast('Live URL copied to clipboard!')"
                                class="text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-200 p-0.5 rounded transition-colors cursor-pointer"
                                title="Copy live URL"
                                aria-label="Copy live URL to clipboard"
                            >
                                <flux:icon.clipboard class="size-3.5" />
                            </button>
                            @if($site->domains->count() > 0)
                                <span aria-hidden="true">&bull;</span>
                                <a href="http://{{ $site->domains->first()->domain }}" target="_blank" rel="noopener" class="text-accent dark:text-brand-300 hover:underline">
                                    {{ $site->domains->first()->domain }}
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right: Quick Action Buttons -->
            <div class="flex items-center gap-2.5 shrink-0 flex-wrap">
                <a
                    href="{{ url('/s/' . $site->slug) }}"
                    target="_blank"
                    rel="noopener"
                    class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-semibold rounded-xl bg-accent hover:bg-accent/90 text-white transition-all shadow-xs hover:shadow-md cursor-pointer"
                >
                    <flux:icon.arrow-top-right-on-square class="size-3.5" />
                    <span>Visit Live Site</span>
                </a>
                <a
                    href="{{ route('dashboard.sites.download', $site) }}"
                    class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold rounded-xl bg-white dark:bg-zinc-800 hover:bg-zinc-100 dark:hover:bg-zinc-700 text-zinc-800 dark:text-zinc-200 border border-zinc-200/80 dark:border-zinc-700 transition-colors shadow-2xs"
                    title="Download current site files as ZIP"
                >
                    <flux:icon.arrow-down-tray class="size-3.5" />
                    <span>Download ZIP</span>
                </a>
            </div>
        </div>

        <!-- Navigation Tab Bar -->
        <div class="mt-6 pt-4 border-t border-zinc-200/60 dark:border-zinc-800/80">
            <nav class="flex space-x-1 sm:space-x-2 overflow-x-auto pb-1" aria-label="Site sections">
                @foreach ($tabs as $tab)
                    @php $isActive = $current === $tab['key']; @endphp
                    <a
                        href="{{ route($tab['route'], $site) }}"
                        wire:navigate
                        @if ($isActive) aria-current="page" @endif
                        @class([
                            'subnav-tab',
                            'is-active' => $isActive,
                        ])
                    >
                        @switch($tab['icon'])
                            @case('pencil-square')
                                <flux:icon.pencil-square class="size-4" />
                                @break
                            @case('photo')
                                <flux:icon.photo class="size-4" />
                                @break
                            @case('arrows-right-left')
                                <flux:icon.arrows-right-left class="size-4" />
                                @break
                            @case('chart-bar-square')
                                <flux:icon.chart-bar-square class="size-4" />
                                @break
                            @case('archive-box')
                                <flux:icon.archive-box class="size-4" />
                                @break
                            @case('globe-alt')
                                <flux:icon.globe-alt class="size-4" />
                                @break
                            @case('cog')
                                <flux:icon.cog class="size-4" />
                                @break
                        @endswitch
                        <span class="flex items-center gap-1.5">
                            <span>{{ $tab['label'] }}</span>
                            @if (! empty($tab['badge']))
                                <span class="px-1.5 py-0.5 rounded-full text-[9px] font-bold bg-accent/10 text-accent dark:text-brand-300 border border-accent/20">{{ $tab['badge'] }}</span>
                            @endif
                        </span>
                    </a>
                @endforeach
            </nav>
        </div>
    </div>
</div>
