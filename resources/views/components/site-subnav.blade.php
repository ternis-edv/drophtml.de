@props([
    'site',
    'current' => 'editor',
])

@php
    $tabTitles = [
        'editor' => 'Code Studio',
        'media' => 'Media Assets',
        'stats' => 'Site Analytics',
        'deployments' => 'Deployments & Rollbacks',
        'domains' => 'Custom Domains',
        'settings' => 'Site Settings',
    ];
    $currentTitle = $tabTitles[$current] ?? ucfirst($current);
@endphp

<div class="mb-8 space-y-4">
    <!-- Top Site Hero Header Card -->
    <div class="glass-card rounded-3xl p-5 sm:p-6 border border-zinc-200/80 dark:border-zinc-800 shadow-xs relative overflow-hidden">
        <div class="absolute top-0 right-0 w-64 h-32 bg-indigo-500/5 dark:bg-indigo-500/10 rounded-full blur-2xl pointer-events-none"></div>

        <div class="relative z-10 flex flex-col lg:flex-row lg:items-center justify-between gap-5">
            <!-- Left: Breadcrumbs & Site Info -->
            <div class="space-y-2">
                <flux:breadcrumbs>
                    <flux:breadcrumbs.item :href="route('dashboard')" wire:navigate>Dashboard</flux:breadcrumbs.item>
                    <flux:breadcrumbs.item>{{ $site->original_name ?: $site->slug }}</flux:breadcrumbs.item>
                    <flux:breadcrumbs.item>{{ $currentTitle }}</flux:breadcrumbs.item>
                </flux:breadcrumbs>

                <div class="flex items-center gap-3.5 flex-wrap pt-1">
                    <div class="size-10 rounded-2xl bg-gradient-to-tr from-indigo-600/20 to-purple-600/20 text-indigo-600 dark:text-indigo-400 flex items-center justify-center font-black text-sm shrink-0 border border-indigo-500/30 shadow-xs">
                        {{ strtoupper(substr($site->original_name ?: $site->slug, 0, 1)) }}
                    </div>

                    <div>
                        <div class="flex items-center gap-2.5 flex-wrap">
                            <h1 class="text-xl sm:text-2xl font-black tracking-tight text-zinc-900 dark:text-zinc-100">
                                {{ $site->original_name ?: $site->slug }}
                            </h1>
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $site->status === 'active' ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20' : 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20' }}">
                                <span class="size-1.5 rounded-full {{ $site->status === 'active' ? 'bg-emerald-500 animate-pulse' : 'bg-amber-500' }}"></span>
                                {{ ucfirst($site->status) }}
                            </span>
                            @if($site->is_permanent)
                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-purple-500/10 text-purple-600 dark:text-purple-300 border border-purple-500/20">
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
                                title="Copy URL"
                            >
                                <flux:icon.clipboard class="size-3.5" />
                            </button>
                            @if($site->domains->count() > 0)
                                <span>&bull;</span>
                                <a href="http://{{ $site->domains->first()->domain }}" target="_blank" class="text-indigo-600 dark:text-indigo-400 hover:underline">
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
                    class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-bold rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white transition-all shadow-xs hover:shadow-md cursor-pointer"
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
            <nav class="flex space-x-1 sm:space-x-2 overflow-x-auto pb-1" aria-label="Tabs">
                <a 
                    href="{{ route('dashboard.sites.edit', $site) }}" 
                    wire:navigate 
                    class="inline-flex items-center gap-2 py-2 px-3.5 text-xs sm:text-sm font-semibold rounded-xl transition-all whitespace-nowrap {{ $current === 'editor' ? 'bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 border border-indigo-500/20 shadow-2xs' : 'text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-200 hover:bg-zinc-100 dark:hover:bg-zinc-800/60' }}"
                >
                    <flux:icon.pencil-square class="size-4" />
                    <span>Code Studio</span>
                </a>

                <a 
                    href="{{ route('dashboard.sites.media', $site) }}" 
                    wire:navigate 
                    class="inline-flex items-center gap-2 py-2 px-3.5 text-xs sm:text-sm font-semibold rounded-xl transition-all whitespace-nowrap {{ $current === 'media' ? 'bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 border border-indigo-500/20 shadow-2xs' : 'text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-200 hover:bg-zinc-100 dark:hover:bg-zinc-800/60' }}"
                >
                    <flux:icon.photo class="size-4" />
                    <span class="flex items-center gap-1.5">
                        <span>Media Assets</span>
                        <span class="px-1.5 py-0.2 rounded text-[9px] font-bold bg-cyan-500/10 text-cyan-600 dark:text-cyan-400 border border-cyan-500/20">Cloud</span>
                    </span>
                </a>

                <a 
                    href="{{ route('dashboard.sites.stats', $site) }}" 
                    wire:navigate 
                    class="inline-flex items-center gap-2 py-2 px-3.5 text-xs sm:text-sm font-semibold rounded-xl transition-all whitespace-nowrap {{ $current === 'stats' ? 'bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 border border-indigo-500/20 shadow-2xs' : 'text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-200 hover:bg-zinc-100 dark:hover:bg-zinc-800/60' }}"
                >
                    <flux:icon.chart-bar-square class="size-4" />
                    <span>Site Analytics</span>
                </a>

                <a 
                    href="{{ route('dashboard.sites.deployments', $site) }}" 
                    wire:navigate 
                    class="inline-flex items-center gap-2 py-2 px-3.5 text-xs sm:text-sm font-semibold rounded-xl transition-all whitespace-nowrap {{ $current === 'deployments' ? 'bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 border border-indigo-500/20 shadow-2xs' : 'text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-200 hover:bg-zinc-100 dark:hover:bg-zinc-800/60' }}"
                >
                    <flux:icon.archive-box class="size-4" />
                    <span>Deployments & Rollbacks</span>
                </a>

                <a 
                    href="{{ route('dashboard.sites.domains', $site) }}" 
                    wire:navigate 
                    class="inline-flex items-center gap-2 py-2 px-3.5 text-xs sm:text-sm font-semibold rounded-xl transition-all whitespace-nowrap {{ $current === 'domains' ? 'bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 border border-indigo-500/20 shadow-2xs' : 'text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-200 hover:bg-zinc-100 dark:hover:bg-zinc-800/60' }}"
                >
                    <flux:icon.globe-alt class="size-4" />
                    <span>Custom Domains</span>
                </a>

                <a 
                    href="{{ route('dashboard.sites.settings', $site) }}" 
                    wire:navigate 
                    class="inline-flex items-center gap-2 py-2 px-3.5 text-xs sm:text-sm font-semibold rounded-xl transition-all whitespace-nowrap {{ $current === 'settings' ? 'bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 border border-indigo-500/20 shadow-2xs' : 'text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-200 hover:bg-zinc-100 dark:hover:bg-zinc-800/60' }}"
                >
                    <flux:icon.cog class="size-4" />
                    <span>Settings</span>
                </a>
            </nav>
        </div>
    </div>
</div>
