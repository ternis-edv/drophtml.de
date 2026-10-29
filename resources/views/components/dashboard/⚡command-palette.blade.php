<?php

use App\Models\Site;
use Livewire\Component;

new class extends Component
{
    public $isOpen = false;
    public $search = '';

    protected $listeners = [
        'open-command-palette' => 'openPalette',
        'close-command-palette' => 'closePalette',
    ];

    public function openPalette()
    {
        $this->isOpen = true;
        $this->search = '';
    }

    public function closePalette()
    {
        $this->isOpen = false;
        $this->search = '';
    }

    public function with()
    {
        $user = auth()->user();
        $query = trim($this->search);

        $sites = $user ? $user->sites()
            ->when($query !== '', function ($q) use ($query) {
                $q->where(function ($sub) use ($query) {
                    $sub->where('slug', 'like', "%{$query}%")
                        ->orWhere('original_name', 'like', "%{$query}%");
                });
            })
            ->latest()
            ->limit(6)
            ->get(['id', 'slug', 'original_name', 'status', 'views']) : collect();

        return [
            'matchedSites' => $sites,
        ];
    }
};
?>

<div
    x-data="{
        open: @entangle('isOpen'),
        init() {
            this.$watch('open', (value) => {
                if (value) this.$nextTick(() => this.$refs.commandInput?.focus());
            });
        },
        close() {
            this.open = false;
            $wire.closePalette();
        }
    }"
    x-show="open"
    x-cloak
    @keydown.escape.window="if (open) close()"
    @keydown.window="if (($event.metaKey || $event.ctrlKey) && $event.key.toLowerCase() === 'k') { $event.preventDefault(); open ? close() : $wire.openPalette() }"
    x-trap.inert.noscroll="open"
    aria-label="Search dashboard"
    class="relative z-50"
    role="dialog"
    aria-modal="true"
>
    <!-- Backdrop with blur -->
    <div
        x-show="open"
        x-transition:enter="ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @click="close()"
        class="dashboard-command-backdrop"
    ></div>

    <div class="fixed inset-0 z-10 overflow-y-auto p-4 sm:p-6 md:p-20 flex items-start justify-center">
        <div
            x-show="open"
            x-transition:enter="ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95 -translate-y-2"
            x-transition:enter-end="opacity-100 scale-100 translate-y-0"
            x-transition:leave="ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100 translate-y-0"
            x-transition:leave-end="opacity-0 scale-95 -translate-y-2"
            class="dashboard-command-dialog mx-auto w-full max-w-2xl divide-y dashboard-divide overflow-hidden"
        >
            <!-- Search Input Header -->
            <div class="relative flex items-center px-4 py-3 sm:px-6">
                <flux:icon.magnifying-glass class="pointer-events-none size-5 dashboard-accent shrink-0" />
                <input
                    type="text"
                    x-ref="commandInput"
                    wire:model.live.debounce.150ms="search"
                    placeholder="Type a command or search sites..."
                    aria-label="{{ __('Search sites and commands') }}"
                    role="combobox"
                    aria-expanded="true"
                    aria-controls="command-palette-results"
                    class="h-11 w-full border-0 bg-transparent pl-3.5 pr-10 text-sm dashboard-text placeholder-zinc-400  focus:outline-hidden focus:ring-0"
                />
                <button
                    type="button"
                    @click="close()"
                    aria-label="{{ __('Close command palette') }}"
                    class="dashboard-muted dashboard-hover-text p-1 rounded-md cursor-pointer"
                >
                    <kbd class="hidden sm:inline-block px-1.5 py-0.5 text-[10px] font-mono font-medium dashboard-muted dashboard-raised border dashboard-border rounded">ESC</kbd>
                </button>
            </div>

            <div id="command-palette-results" class="max-h-96 scroll-py-2 overflow-y-auto p-3 text-sm dashboard-text divide-y dashboard-divide custom-scrollbar">
                <!-- Matching Sites Section -->
                @if($matchedSites->isNotEmpty())
                    <div class="py-2">
                        <div class="px-3 pb-2 text-[11px] font-medium uppercase tracking-wider dashboard-muted">
                            Sites ({{ $matchedSites->count() }})
                        </div>
                        <div class="space-y-1">
                            @foreach($matchedSites as $site)
                                <div class="flex items-center justify-between dashboard-control-rounded px-3 py-2 dashboard-text dashboard-hover-surface transition-colors group">
                                    <a href="{{ route('dashboard.sites.edit', $site) }}" @click="close()" class="flex items-center gap-2.5 flex-1 min-w-0">
                                        <div class="size-2 rounded-full {{ $site->status === 'active' ? 'dashboard-success-solid' : 'dashboard-warning-solid' }}"></div>
                                        <div class="truncate">
                                            <span class="font-medium text-sm dashboard-text">{{ $site->original_name ?: $site->slug }}</span>
                                            <span class="text-xs dashboard-muted ml-1.5 font-mono">({{ $site->slug }})</span>
                                        </div>
                                    </a>

                                    <div class="flex items-center gap-1 opacity-80 group-hover:opacity-100">
                                        <a href="{{ url('/s/' . $site->slug) }}" target="_blank" rel="noopener" class="p-1.5 dashboard-control-rounded dashboard-muted dashboard-accent dashboard-accent-tint transition-colors" title="Live Site" aria-label="{{ __('Open live site') }}">
                                            <flux:icon.arrow-top-right-on-square class="size-3.5" />
                                        </a>
                                        <a href="{{ route('dashboard.sites.edit', $site) }}" @click="close()" class="p-1.5 dashboard-control-rounded dashboard-muted dashboard-accent dashboard-accent-tint transition-colors" title="Code Editor">
                                            <flux:icon.pencil-square class="size-3.5" />
                                        </a>
                                        <a href="{{ route('dashboard.sites.stats', $site) }}" wire:navigate @click="close()" class="p-1.5 dashboard-control-rounded dashboard-muted dashboard-accent dashboard-accent-tint transition-colors" title="Analytics">
                                            <flux:icon.chart-bar-square class="size-3.5" />
                                        </a>
                                        <a href="{{ route('dashboard.sites.deployments', $site) }}" wire:navigate @click="close()" class="p-1.5 dashboard-control-rounded dashboard-muted dashboard-accent dashboard-accent-tint transition-colors" title="Deployments">
                                            <flux:icon.archive-box class="size-3.5" />
                                        </a>
                                        <a href="{{ route('dashboard.sites.settings', $site) }}" wire:navigate @click="close()" class="p-1.5 dashboard-control-rounded dashboard-muted dashboard-accent dashboard-accent-tint transition-colors" title="Settings">
                                            <flux:icon.cog class="size-3.5" />
                                        </a>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Navigation Shortcuts -->
                <div class="py-2">
                    <div class="px-3 pb-2 text-[11px] font-medium uppercase tracking-wider dashboard-muted">
                        Quick Navigation
                    </div>
                    <div class="space-y-0.5">
                        <a href="{{ route('dashboard.sites.upload') }}" wire:navigate @click="close()" class="flex items-center gap-3 dashboard-control-rounded px-3 py-2 dashboard-text dashboard-hover-surface dashboard-hover-text transition-colors">
                            <flux:icon.arrow-up-tray class="size-4 dashboard-accent" />
                            <span class="flex-1 font-medium text-xs">Deploy / Upload New Site</span>
                            <kbd class="text-[10px] dashboard-muted font-mono px-1.5 py-0.5 rounded dashboard-raised">Upload</kbd>
                        </a>

                        <a href="{{ route('dashboard.media') }}" wire:navigate @click="close()" class="flex items-center gap-3 dashboard-control-rounded px-3 py-2 dashboard-text dashboard-hover-surface dashboard-hover-text transition-colors">
                            <flux:icon.photo class="size-4 dashboard-accent" />
                            <span class="flex-1 font-medium text-xs">Cloud Media Storage</span>
                            <kbd class="text-[10px] dashboard-muted font-mono px-1.5 py-0.5 rounded dashboard-raised">Media</kbd>
                        </a>

                        <a href="{{ route('dashboard') }}" wire:navigate @click="close()" class="flex items-center gap-3 dashboard-control-rounded px-3 py-2 dashboard-text dashboard-hover-surface dashboard-hover-text transition-colors">
                            <flux:icon.home class="size-4 dashboard-accent" />
                            <span class="flex-1 font-medium text-xs">Sites Overview Dashboard</span>
                            <kbd class="text-[10px] dashboard-muted font-mono px-1.5 py-0.5 rounded dashboard-raised">Home</kbd>
                        </a>

                        <a href="{{ route('dashboard.activity') }}" wire:navigate @click="close()" class="flex items-center gap-3 dashboard-control-rounded px-3 py-2 dashboard-text dashboard-hover-surface dashboard-hover-text transition-colors">
                            <flux:icon.clipboard-document-list class="size-4 dashboard-accent" />
                            <span class="flex-1 font-medium text-xs">Platform Activity Log</span>
                            <kbd class="text-[10px] dashboard-muted font-mono px-1.5 py-0.5 rounded dashboard-raised">Logs</kbd>
                        </a>

                        <a href="{{ route('profile.edit') }}" wire:navigate @click="close()" class="flex items-center gap-3 dashboard-control-rounded px-3 py-2 dashboard-text dashboard-hover-surface dashboard-hover-text transition-colors">
                            <flux:icon.cog class="size-4 dashboard-accent" />
                            <span class="flex-1 font-medium text-xs">Account Settings & API Tokens</span>
                            <kbd class="text-[10px] dashboard-muted font-mono px-1.5 py-0.5 rounded dashboard-raised">API</kbd>
                        </a>

                        <a href="/docs/api" @click="close()" class="flex items-center gap-3 dashboard-control-rounded px-3 py-2 dashboard-text dashboard-hover-surface dashboard-hover-text transition-colors">
                            <flux:icon.document-text class="size-4 dashboard-accent" />
                            <span class="flex-1 font-medium text-xs">REST API Documentation</span>
                            <flux:icon.arrow-top-right-on-square class="size-3 dashboard-muted" />
                        </a>

                        <a href="https://github.com/ternis-edv/drophtml.de" target="_blank" @click="close()" class="flex items-center gap-3 dashboard-control-rounded px-3 py-2 dashboard-text dashboard-hover-surface dashboard-hover-text transition-colors">
                            <flux:icon.folder-git-2 class="size-4 dashboard-accent" />
                            <span class="flex-1 font-medium text-xs">GitHub Repository</span>
                            <flux:icon.arrow-top-right-on-square class="size-3 dashboard-muted" />
                        </a>
                    </div>
                </div>

                <!-- Theme Switching -->
                <div class="py-2">
                    <div class="px-3 pb-2 text-[11px] font-medium uppercase tracking-wider dashboard-muted">
                        Appearance & Theme
                    </div>
                    <div class="grid grid-cols-3 gap-1.5 px-2">
                        <button
                            type="button"
                            @click="$flux.appearance = 'light'; close()"
                            class="flex items-center justify-center gap-2 py-2 px-3 dashboard-control-rounded text-xs font-medium dashboard-text dashboard-hover-surface transition-colors cursor-pointer"
                        >
                            <flux:icon.sun class="size-3.5 dashboard-muted" />
                            <span>Light</span>
                        </button>

                        <button
                            type="button"
                            @click="$flux.appearance = 'dark'; close()"
                            class="flex items-center justify-center gap-2 py-2 px-3 dashboard-control-rounded text-xs font-medium dashboard-text dashboard-hover-surface transition-colors cursor-pointer"
                        >
                            <flux:icon.moon class="size-3.5 dashboard-accent" />
                            <span>Dark</span>
                        </button>

                        <button
                            type="button"
                            @click="$flux.appearance = 'system'; close()"
                            class="flex items-center justify-center gap-2 py-2 px-3 dashboard-control-rounded text-xs font-medium dashboard-text dashboard-hover-surface transition-colors cursor-pointer"
                        >
                            <flux:icon.computer-desktop class="size-3.5 dashboard-muted" />
                            <span>System</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Footer Tips -->
            <div class="flex items-center justify-between px-5 py-3 dashboard-surface text-[11px] dashboard-muted">
                <div class="flex items-center gap-3">
                    <span class="inline-flex items-center gap-1">
                        <kbd class="font-mono dashboard-muted px-1 py-0.5 rounded dashboard-surface text-[10px]">⌘K</kbd> / <kbd class="font-mono dashboard-muted px-1 py-0.5 rounded dashboard-surface text-[10px]">Ctrl+K</kbd> toggle
                    </span>
                    <span class="inline-flex items-center gap-1">
                        <kbd class="font-mono dashboard-muted px-1 py-0.5 rounded dashboard-surface text-[10px]">Esc</kbd> close
                    </span>
                </div>
                <span class="font-mono text-[10px]">DropHTML Spotlight</span>
            </div>
        </div>
    </div>
</div>
