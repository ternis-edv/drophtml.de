<?php

use App\Models\Domain;
use App\Services\ActivityLogService;
use App\Services\SiteDeploymentService;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    public $search = '';
    public $statusFilter = 'all'; // all, active, suspended, permanent, expiring
    public $sortBy = 'created_at'; // created_at, original_name, views, expires_at
    public $sortDir = 'desc';

    private const SORTABLE_COLUMNS = ['created_at', 'original_name', 'views', 'expires_at'];

    #[On('site-published')]
    #[On('site-deleted')]
    public function refresh() {}

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function setFilter($filter)
    {
        $this->statusFilter = in_array($filter, ['all', 'active', 'suspended', 'permanent', 'expiring'], true) ? $filter : 'all';
        $this->resetPage();
    }

    public function sortBy($column)
    {
        if (! in_array($column, self::SORTABLE_COLUMNS, true)) {
            return;
        }

        if ($this->sortBy === $column) {
            $this->sortDir = $this->sortDir === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDir = $column === 'original_name' ? 'asc' : 'desc';
        }

        $this->resetPage();
    }

    /**
     * Deterministic sort used by the "Sort by" menu (no direction toggling),
     * so a menu label always matches the resulting order.
     */
    public function setSort($column, $dir = null)
    {
        if (! in_array($column, self::SORTABLE_COLUMNS, true)) {
            return;
        }

        $this->sortBy = $column;
        $this->sortDir = in_array($dir, ['asc', 'desc'], true)
            ? $dir
            : ($column === 'original_name' ? 'asc' : 'desc');

        $this->resetPage();
    }

    public function deleteSite($id, SiteDeploymentService $deploymentService)
    {
        $site = auth()->user()->sites()->findOrFail($id);
        $deploymentService->deleteSite($site, auth()->user());

        $this->dispatch('site-deleted');
        Flux::toast('Site deleted successfully.');
    }

    public function duplicateSite($id, SiteDeploymentService $deploymentService)
    {
        $site = auth()->user()->sites()->findOrFail($id);
        $cloned = $deploymentService->duplicateSite($site, auth()->user());
        Flux::toast("Site cloned as '{$cloned->slug}'!");
    }

    public function extendExpiry($id, $days, ActivityLogService $activityLogService)
    {
        $days = (int) $days;

        if ( ! in_array($days, [7, 30], true)) {
            $days = 7;
        }

        $site = auth()->user()->sites()->findOrFail($id);

        $site->expires_at = ($site->expires_at && $site->expires_at->isFuture())
            ? $site->expires_at->addDays($days)
            : now()->addDays($days);

        $site->save();

        $activityLogService->log(
            auth()->user(),
            $site,
            'expiry_extended',
            "Extended expiry by {$days} days for site: {$site->slug}"
        );

        Flux::toast("Expiry extended by {$days} days.");
    }

    public function togglePermanent($id, ActivityLogService $activityLogService)
    {
        $site = auth()->user()->sites()->findOrFail($id);
        $site->is_permanent = ! $site->is_permanent;
        if ($site->is_permanent) {
            $site->expires_at = null;
        } else {
            $site->expires_at = now()->addDays(30);
        }
        $site->save();

        $activityLogService->log(
            auth()->user(),
            $site,
            'site_updated',
            $site->is_permanent ? "Marked site as permanent: {$site->slug}" : "Set expiry on site: {$site->slug}"
        );

        Flux::toast($site->is_permanent ? 'Site marked as permanent.' : 'Site expiry reset to 30 days.');
    }

    public function render()
    {
        // KPI stats via SQL aggregates — no longer loads every site into memory.
        $totalSites = auth()->user()->sites()->count();
        $totalViews = (int) auth()->user()->sites()->sum('views');
        $totalDomains = Domain::whereIn('site_id', auth()->user()->sites()->select('id'))->count();
        $expiringCount = auth()->user()->sites()
            ->where('is_permanent', false)
            ->whereNotNull('expires_at')
            ->where('expires_at', '>=', now())
            ->where('expires_at', '<=', now()->addDays(7))
            ->count();
        $storageStats = app(\App\Services\StorageUsageService::class)->getUserStorageStats(auth()->user());

        $query = auth()->user()->sites()->with('domains');

        if (! empty($this->search)) {
            $s = '%'.trim($this->search).'%';
            $query->where(function ($q) use ($s) {
                $q->where('slug', 'like', $s)
                  ->orWhere('original_name', 'like', $s)
                  ->orWhereHas('domains', fn ($dq) => $dq->where('domain', 'like', $s));
            });
        }

        if ($this->statusFilter === 'active') {
            $query->where('status', 'active');
        } elseif ($this->statusFilter === 'suspended') {
            $query->where('status', 'suspended');
        } elseif ($this->statusFilter === 'permanent') {
            $query->where('is_permanent', true);
        } elseif ($this->statusFilter === 'expiring') {
            $query->where('is_permanent', false)
                  ->whereNotNull('expires_at')
                  ->where('expires_at', '<=', now()->addDays(7));
        }

        $query->orderBy($this->sortBy, $this->sortDir);

        $sites = $query->paginate(12);

        // If the current page is now empty (e.g. last item on the page was deleted), snap back to the last valid page.
        if ($sites->isEmpty() && $sites->currentPage() > 1) {
            $this->setPage($sites->lastPage());
            $sites = $query->paginate(12);
        }

        return view('components.dashboard.⚡sites-overview', [
            'sites' => $sites,
            'totalSites' => $totalSites,
            'totalViews' => $totalViews,
            'totalDomains' => $totalDomains,
            'expiringCount' => $expiringCount,
            'storageStats' => $storageStats,
        ]);
    }
};
?>
<div
    x-data="{
        dragging: false,
        copiedId: null,
        confirmDeleteId: null,
        confirmDeleteName: '',
        viewMode: localStorage.getItem('drophtml_view_mode') || 'grid',
        setViewMode(mode) {
            this.viewMode = mode;
            localStorage.setItem('drophtml_view_mode', mode);
        },
        askDelete(id, name) {
            this.confirmDeleteId = id;
            this.confirmDeleteName = name;
            Flux.modal('confirm-site-deletion').show();
        },
        copyText(text, id) {
            const fallback = () => {
                const ta = document.createElement('textarea');
                ta.value = text;
                ta.setAttribute('readonly', '');
                ta.style.position = 'fixed';
                ta.style.opacity = '0';
                document.body.appendChild(ta);
                ta.select();
                document.execCommand('copy');
                document.body.removeChild(ta);
            };

            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(text).catch(fallback);
            } else {
                fallback();
            }

            this.copiedId = id;
            setTimeout(() => { if (this.copiedId === id) this.copiedId = null; }, 1600);
            Flux.toast('URL copied to clipboard!');
        },
        handleGlobalDrop(e) {
            this.dragging = false;
            let files = e.dataTransfer.files;
            if (files.length > 0) {
                Flux.modal('upload-site-modal').show();
                setTimeout(() => {
                    window.dispatchEvent(new CustomEvent('files-dropped', { detail: { files: files } }));
                }, 200);
            }
        }
    }"
    @dragover.prevent.window="dragging = true"
    @dragenter.prevent.window="dragging = true"
    @dragleave.prevent.window="if (event.clientX <= 0 || event.clientY <= 0 || event.clientX >= window.innerWidth || event.clientY >= window.innerHeight) dragging = false"
    @drop.prevent.window="handleGlobalDrop($event)"
    class="relative space-y-6"
>
    <!-- Drag overlay -->
    <div
        x-show="dragging"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="fixed inset-0 z-50 flex items-center justify-center bg-zinc-950/80 backdrop-blur-md pointer-events-none"
        style="display: none;"
    >
        <div class="p-10 border-2 border-dashed border-accent rounded-3xl bg-zinc-900/90 text-white flex flex-col items-center gap-4 max-w-md text-center shadow-xl">
            <div class="size-16 rounded-2xl bg-accent/20 text-brand-300 flex items-center justify-center animate-bounce">
                <flux:icon.cloud-arrow-up class="size-8" />
            </div>
            <div>
                <div class="text-xl font-bold text-white">Drop to Deploy Site</div>
                <p class="text-sm text-zinc-400 mt-1">Single HTML document or complete ZIP archive</p>
            </div>
        </div>
    </div>

    <!-- Welcome Banner -->
    @php
        $greeting = match (true) {
            now()->hour < 5 => 'Burning the midnight oil',
            now()->hour < 12 => 'Good morning',
            now()->hour < 18 => 'Good afternoon',
            default => 'Good evening',
        };
    @endphp
    <div class="rise-in panel relative overflow-hidden p-6 sm:p-8">
        <div class="absolute -top-24 -right-16 w-72 h-72 bg-accent/[0.07] rounded-full blur-3xl pointer-events-none"></div>

        <div class="relative z-10 flex flex-col lg:flex-row lg:items-center justify-between gap-6">
            <div class="space-y-2.5 max-w-2xl">
                <div class="flex flex-wrap items-center gap-x-3 gap-y-1.5">
                    <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-accent/[0.06] border border-accent/20 text-accent dark:text-accent-content text-xs font-semibold">
                        <span class="size-1.5 rounded-full bg-emerald-500 animate-pulse" aria-hidden="true"></span>
                        <span>Edge Cloud Operational</span>
                    </span>
                    <span class="text-xs font-medium text-zinc-400 dark:text-zinc-500" title="{{ now()->toDayDateTimeString() }}">
                        {{ now()->isoFormat('dddd, D MMMM YYYY') }}
                    </span>
                </div>
                <h1 class="page-title">
                    {{ $greeting }}, {{ auth()->user()->name }}
                </h1>
                <p class="page-lede">
                    Deploy static sites with automated SSL, manage static media with zero-bloat cloud asset storage, and inspect real-time traffic across global Edge CDN nodes.
                </p>
            </div>

            <!-- Quick Actions -->
            <div class="flex flex-wrap items-center gap-2.5 shrink-0">
                <flux:modal.trigger name="upload-site-modal">
                    <flux:button variant="primary" size="base" icon="cloud-arrow-up" class="font-bold">
                        Deploy New Site
                    </flux:button>
                </flux:modal.trigger>

                @if(auth()->user()->github_id)
                    <flux:modal.trigger name="github-deploy-modal">
                        <flux:button variant="outline" size="base">
                            <svg class="size-4 mr-1.5 text-zinc-700 dark:text-zinc-300" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path fill-rule="evenodd" d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.531 1.032 1.531 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0022 12.017C22 6.484 17.522 2 12 2z" clip-rule="evenodd" /></svg>
                            GitHub Sync
                        </flux:button>
                    </flux:modal.trigger>
                @endif

                <a href="{{ route('dashboard.media') }}" wire:navigate class="inline-flex items-center gap-2 px-3.5 py-2.5 text-xs font-semibold rounded-xl bg-cyan-50 dark:bg-cyan-950/40 border border-cyan-500/30 text-cyan-700 dark:text-cyan-300 hover:bg-cyan-100 dark:hover:bg-cyan-900/40 transition-all duration-150 hover:-translate-y-0.5 hover:shadow-md cursor-pointer focus-visible:outline-cyan-600">
                    <flux:icon.photo class="size-3.5" aria-hidden="true" />
                    <span>Media Cloud</span>
                </a>
            </div>
        </div>
    </div>

    <!-- 4 KPI Stat Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Stat 1: Total Sites -->
        <div class="panel panel-interactive p-5 relative overflow-hidden group" style="--i: 0">
            <div class="flex items-center justify-between">
                <div>
                    <span class="stat-label">Total Sites</span>
                    <div class="stat-value">{{ number_format($totalSites) }}</div>
                </div>
                <div class="icon-tile">
                    <flux:icon.globe-alt class="size-5" aria-hidden="true" />
                </div>
            </div>
            <div class="mt-3 text-xs text-zinc-500 dark:text-zinc-400 flex items-center gap-1.5">
                <span class="size-2 rounded-full bg-emerald-500 animate-pulse" aria-hidden="true"></span>
                <span>Active Edge Deployments</span>
            </div>
        </div>

        <!-- Stat 2: Total Page Views -->
        <div class="panel panel-interactive p-5 relative overflow-hidden group" style="--i: 1">
            <div class="flex items-center justify-between">
                <div>
                    <span class="stat-label">Total Page Views</span>
                    <div class="stat-value">{{ number_format($totalViews) }}</div>
                </div>
                <div class="icon-tile">
                    <flux:icon.eye class="size-5" aria-hidden="true" />
                </div>
            </div>
            <div class="mt-3 text-xs text-zinc-500 dark:text-zinc-400 flex items-center gap-1.5">
                <flux:icon.chart-bar class="size-3.5 text-zinc-400" aria-hidden="true" />
                <span>Across all deployed sites</span>
            </div>
        </div>

        <!-- Stat 3: Connected Domains -->
        <div class="panel panel-interactive p-5 relative overflow-hidden group" style="--i: 2">
            <div class="flex items-center justify-between">
                <div>
                    <span class="stat-label">Custom Domains</span>
                    <div class="stat-value">{{ number_format($totalDomains) }}</div>
                </div>
                <div class="icon-tile">
                    <flux:icon.link class="size-5" aria-hidden="true" />
                </div>
            </div>
            <div class="mt-3 text-xs text-zinc-500 dark:text-zinc-400 flex items-center gap-1.5">
                <flux:icon.shield-check class="size-3.5 text-emerald-500" aria-hidden="true" />
                <span>Automatic SSL provisioned</span>
            </div>
        </div>

        <!-- Stat 4: Storage & Quota -->
        @php
            $storagePct = min(100, max(0, (int) $storageStats['used_percentage']));
        @endphp
        <a href="{{ route('settings.storage') }}" wire:navigate class="panel panel-interactive p-5 relative overflow-hidden group block" style="--i: 3" aria-label="{{ __('Storage and quota') }}">
            <div class="flex items-center justify-between">
                <div>
                    <span class="stat-label">Storage &amp; Quota</span>
                    <div class="stat-value font-mono">{{ $storageStats['formatted']['total_used'] }}</div>
                </div>
                <div class="icon-tile border-cyan-500/20 bg-cyan-500/10 text-cyan-600 dark:text-cyan-400 group-hover:scale-105 transition-transform duration-200">
                    <flux:icon.circle-stack class="size-5" aria-hidden="true" />
                </div>
            </div>
            <div class="mt-3 space-y-2">
                <span class="meter meter-lg block">
                    <span class="meter-fill block {{ $storagePct >= 90 ? 'is-danger' : ($storagePct >= 75 ? 'is-warn' : '') }}" style="width: {{ $storagePct }}%"></span>
                </span>
                <div class="flex items-center justify-between text-xs text-zinc-500 dark:text-zinc-400">
                    <span class="tabular-nums truncate">
                        {{ $storagePct }}% of {{ $storageStats['formatted']['quota'] }} used
                    </span>
                    <span class="text-accent dark:text-accent-content group-hover:underline font-semibold inline-flex items-center gap-1">
                        <span>Manage</span>
                        <span aria-hidden="true">&rarr;</span>
                    </span>
                </div>
            </div>
        </a>
    </div>

    <!-- Main Container: Search, Filters & Content -->
    <div class="panel p-5 sm:p-7">
        <!-- Filter and Search Header -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
            <!-- Search & Filter Chips -->
            <div class="flex flex-1 flex-wrap items-center gap-3 min-w-0">
                <div class="relative w-full sm:w-80">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-zinc-400">
                        <span wire:loading.remove wire:target="search"><flux:icon.magnifying-glass class="size-4" /></span>
                        <span wire:loading wire:target="search"><flux:icon.arrow-path class="size-4 animate-spin" /></span>
                    </div>
                    <input 
                        wire:model.live.debounce.250ms="search" 
                        type="search"
                        aria-label="{{ __('Search sites by name, slug or domain') }}"
                        placeholder="Search sites, slugs, domains..." 
                        class="w-full pl-9 pr-8 py-2 text-xs sm:text-sm rounded-xl bg-zinc-50 dark:bg-zinc-800/70 border border-zinc-200 dark:border-zinc-700 text-zinc-900 dark:text-zinc-100 placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-accent focus:border-accent transition-all duration-150 [&::-webkit-search-cancel-button]:appearance-none"
                    />
                    @if($search)
                        <button wire:click="$set('search', '')" class="absolute inset-y-0 right-0 pr-2.5 flex items-center text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200 cursor-pointer" aria-label="{{ __('Clear search') }}">
                            <flux:icon.x-mark class="size-4" />
                        </button>
                    @endif
                </div>

                <!-- Filter Pills -->
                <div class="pill-group" role="group" aria-label="{{ __('Filter sites by status') }}">
                    <button type="button" wire:click="setFilter('all')" aria-pressed="{{ $statusFilter === 'all' ? 'true' : 'false' }}" class="pill">
                        All <span class="opacity-60 tabular-nums">({{ $totalSites }})</span>
                    </button>
                    <button type="button" wire:click="setFilter('active')" aria-pressed="{{ $statusFilter === 'active' ? 'true' : 'false' }}" class="pill">
                        Active
                    </button>
                    <button type="button" wire:click="setFilter('permanent')" aria-pressed="{{ $statusFilter === 'permanent' ? 'true' : 'false' }}" class="pill">
                        Permanent
                    </button>
                    <button type="button" wire:click="setFilter('expiring')" aria-pressed="{{ $statusFilter === 'expiring' ? 'true' : 'false' }}" class="pill">
                        Expiring
                        @if($expiringCount > 0)
                            <span class="count-badge {{ $statusFilter === 'expiring' ? 'bg-accent' : 'bg-amber-500' }}">{{ $expiringCount }}</span>
                        @endif
                    </button>
                </div>
            </div>

            <!-- View Switcher & Action Buttons -->
            <div class="flex items-center gap-2.5 shrink-0">
                <span class="hidden sm:inline-flex items-center text-xs font-semibold text-zinc-400 tabular-nums" aria-live="polite" role="status">
                    {{ $sites->total() }} {{ \Illuminate\Support\Str::plural('site', $sites->total()) }}
                </span>

                <!-- Sort menu (works in both grid and table view) -->
                @php
                    $sortOptions = [
                        ['created_at', 'desc', 'Newest first'],
                        ['created_at', 'asc', 'Oldest first'],
                        ['original_name', 'asc', 'Name A–Z'],
                        ['original_name', 'desc', 'Name Z–A'],
                        ['views', 'desc', 'Most viewed'],
                        ['views', 'asc', 'Least viewed'],
                        ['expires_at', 'asc', 'Expiring soonest'],
                        ['expires_at', 'desc', 'Expiring latest'],
                    ];
                @endphp
                <flux:dropdown position="bottom" align="end">
                    <flux:button variant="ghost" size="sm" icon="bars-arrow-down" title="{{ __('Sort sites') }}">
                        <span class="hidden sm:inline">Sort</span>
                    </flux:button>
                    <flux:menu>
                        <flux:menu.heading>Sort sites by</flux:menu.heading>
                        @foreach($sortOptions as [$col, $dir, $label])
                            <flux:menu.item
                                icon="{{ $sortBy === $col && $sortDir === $dir ? 'check' : 'minus' }}"
                                wire:click="setSort('{{ $col }}', '{{ $dir }}')"
                                @class(['font-semibold text-accent dark:text-brand-300' => $sortBy === $col && $sortDir === $dir])
                            >
                                {{ $label }}
                            </flux:menu.item>
                        @endforeach
                    </flux:menu>
                </flux:dropdown>

                <!-- Grid / Table View Switcher -->
                <div class="hidden sm:inline-flex rounded-xl p-1 bg-zinc-100 dark:bg-zinc-950/60 border border-zinc-200 dark:border-zinc-800" role="group" aria-label="{{ __('Change result layout') }}">
                    <button type="button" @click="setViewMode('grid')" :aria-pressed="viewMode === 'grid'" :class="viewMode === 'grid' ? 'bg-white dark:bg-zinc-800 text-accent shadow-xs' : 'text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-200'" class="p-1.5 rounded-lg transition-colors duration-150 cursor-pointer" title="Grid View" aria-label="{{ __('Grid view') }}">
                        <flux:icon.squares-2x2 class="size-4" />
                    </button>
                    <button type="button" @click="setViewMode('table')" :aria-pressed="viewMode === 'table'" :class="viewMode === 'table' ? 'bg-white dark:bg-zinc-800 text-accent shadow-xs' : 'text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-200'" class="p-1.5 rounded-lg transition-colors duration-150 cursor-pointer" title="Dense Table View" aria-label="{{ __('Table view') }}">
                        <flux:icon.bars-3-bottom-left class="size-4" />
                    </button>
                </div>

                <flux:modal.trigger name="upload-site-modal">
                    <flux:button variant="primary" size="sm" icon="plus">
                        Deploy Site
                    </flux:button>
                </flux:modal.trigger>
            </div>
        </div>

        <!-- In-flight request indicator -->
        <div wire:loading class="meter is-indeterminate mb-5 fade-in" role="progressbar" aria-label="{{ __('Loading sites') }}">
            <div class="meter-fill"></div>
        </div>

        <!-- Modals -->
        @if(auth()->user()->github_id)
            <flux:modal name="github-deploy-modal" class="md:w-[700px]">
                <div class="space-y-6">
                    <div>
                        <flux:heading size="lg">Deploy from GitHub</flux:heading>
                        <flux:subheading>Select a repository to deploy as a new site with automatic webhooks.</flux:subheading>
                    </div>

                    <livewire:dashboard.github-deploy @site-published="Flux.modal('github-deploy-modal').close()" />
                </div>
            </flux:modal>
        @endif

        <flux:modal name="upload-site-modal" class="md:w-[600px]">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">Deploy New Site</flux:heading>
                    <flux:subheading>Upload a standalone HTML document or complete ZIP archive.</flux:subheading>
                </div>

                <livewire:file-uploader />
            </div>
        </flux:modal>

        <!-- Delete Confirmation Modal -->
        <flux:modal name="confirm-site-deletion" class="md:w-[440px]">
            <div class="space-y-6">
                <div class="flex items-start gap-3">
                    <span class="size-9 rounded-xl bg-red-50 dark:bg-red-950/40 text-red-600 dark:text-red-400 flex items-center justify-center ring-1 ring-red-500/20 shrink-0">
                        <flux:icon.exclamation-triangle class="size-4.5" />
                    </span>
                    <div>
                        <flux:heading size="lg" class="tracking-tight">Delete this site?</flux:heading>
                        <flux:subheading class="mt-1">
                            <span class="font-semibold text-zinc-700 dark:text-zinc-300" x-text="confirmDeleteName"></span>
                            and all of its files, custom domains, and analytics will be permanently removed. This action cannot be undone.
                        </flux:subheading>
                    </div>
                </div>

                <div class="flex justify-end gap-2">
                    <flux:modal.close>
                        <flux:button variant="outline">Cancel</flux:button>
                    </flux:modal.close>

                    <flux:button
                        variant="danger"
                        wire:loading.attr="disabled"
                        @click="Flux.modal('confirm-site-deletion').close(); $wire.deleteSite(confirmDeleteId)"
                    >
                        Delete Site
                    </flux:button>
                </div>
            </div>
        </flux:modal>

        <!-- Empty State -->
        @if($sites->isEmpty())
            <div class="rise-in py-16 flex flex-col items-center justify-center text-center">
                <div class="size-16 rounded-2xl bg-accent/10 text-accent flex items-center justify-center mb-4 border border-accent/20 shadow-xs">
                    <flux:icon.cloud-arrow-up class="size-8" aria-hidden="true" />
                </div>
                @if($search)
                    <h3 class="text-lg font-bold text-zinc-900 dark:text-zinc-100">No matching sites found</h3>
                    <p class="text-xs sm:text-sm text-zinc-500 dark:text-zinc-400 max-w-sm mt-1 mb-6 leading-relaxed">
                        We couldn't find any sites matching '{{ $search }}'. Check for typos or reset your filter.
                    </p>
                    <flux:button wire:click="$set('search', '')" variant="outline" size="sm">Clear Search</flux:button>
                @elseif($statusFilter !== 'all')
                    <h3 class="text-lg font-bold text-zinc-900 dark:text-zinc-100">No sites in this view</h3>
                    <p class="text-xs sm:text-sm text-zinc-500 dark:text-zinc-400 max-w-sm mt-1 mb-6 leading-relaxed">
                        None of your sites currently match the selected filter. Try a different filter or deploy a new site.
                    </p>
                    <flux:button wire:click="setFilter('all')" variant="outline" size="sm">Show All Sites</flux:button>
                @else
                    <h3 class="text-lg font-bold text-zinc-900 dark:text-zinc-100">No static sites deployed yet</h3>
                    <p class="text-xs sm:text-sm text-zinc-500 dark:text-zinc-400 max-w-sm mt-1 mb-6 leading-relaxed">
                        Drag and drop an HTML or ZIP file anywhere on this screen, or launch an upload modal.
                    </p>
                    <flux:modal.trigger name="upload-site-modal">
                        <flux:button variant="primary" size="sm" icon="plus">Deploy Your First Site</flux:button>
                    </flux:modal.trigger>
                @endif
            </div>
        @else
            <!-- Content: dimmed while any Livewire request is in flight -->
            <div wire:loading.class="opacity-50 pointer-events-none" wire:loading.attr="aria-busy" class="transition-opacity duration-150">
                <!-- 1. Card Grid View -->
                <div
                    x-show="viewMode === 'grid'"
                    x-cloak
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 translate-y-1"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    x-transition:leave="transition ease-in duration-150"
                    x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                    class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5"
                >
                    @foreach ($sites as $site)
                        @php
                            $daysLeft = null;
                            $lifetimePct = null;
                            if (! $site->is_permanent && $site->expires_at) {
                                $daysLeft = $site->expires_at->isFuture()
                                    ? (int) ceil(now()->diffInSeconds($site->expires_at) / 86400)
                                    : 0;
                                $lifetimePct = max(0, min(100, (int) round(($daysLeft / 30) * 100)));
                            }
                        @endphp
                        <div
                            wire:key="site-card-{{ $site->id }}"
                            style="--i: {{ $loop->index }}"
                            class="rise-in group panel panel-interactive bg-zinc-50/80 dark:bg-zinc-950/40 p-5 flex flex-col justify-between relative overflow-hidden"
                        >
                            <div>
                                <!-- Header Row -->
                                <div class="flex items-start justify-between gap-3 mb-3">
                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-center gap-2">
                                            <div class="size-8 rounded-xl bg-accent/10 text-accent border border-accent/20 flex items-center justify-center font-bold text-xs shrink-0">
                                                {{ strtoupper(substr($site->original_name ?: $site->slug, 0, 1)) }}
                                            </div>
                                            <h3 class="font-bold text-sm text-zinc-900 dark:text-zinc-100 truncate" title="{{ $site->original_name ?: $site->slug }}">
                                                {{ $site->original_name ?: $site->slug }}
                                            </h3>
                                            @if($site->github_repo_full_name)
                                                <flux:badge size="xs" color="zinc">GitHub</flux:badge>
                                            @endif
                                        </div>

                                        <div class="flex items-center gap-1.5 mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                                            <a href="/s/{{ $site->slug }}" target="_blank" rel="noopener" class="font-mono text-accent dark:text-accent-content hover:underline truncate max-w-[190px]">
                                                /s/{{ $site->slug }}
                                            </a>
                                            <button 
                                                type="button" 
                                                @click="copyText(@js(url('/s/' . $site->slug)), @js($site->id))" 
                                                class="text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-200 p-0.5 rounded transition-colors duration-150 cursor-pointer" 
                                                title="Copy live URL"
                                                aria-label="{{ __('Copy live URL for') }} {{ $site->slug }}"
                                            >
                                                <flux:icon.clipboard x-show="copiedId !== @js($site->id)" class="size-3.5" />
                                                <flux:icon.check x-show="copiedId === @js($site->id)" x-cloak class="size-3.5 text-emerald-500" />
                                            </button>
                                        </div>
                                    </div>

                                    <div class="flex items-center gap-1 shrink-0">
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold {{ $site->status === 'active' ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20' : 'bg-red-500/10 text-red-600 dark:text-red-400 border border-red-500/20' }}">
                                            <span class="size-1.5 rounded-full {{ $site->status === 'active' ? 'bg-emerald-500 animate-pulse' : 'bg-red-500' }}"></span>
                                            {{ ucfirst($site->status) }}
                                        </span>

                                        <flux:dropdown>
                                            <flux:button variant="ghost" size="xs" icon="ellipsis-vertical" class="rounded-lg cursor-pointer" aria-label="{{ __('More actions for') }} {{ $site->slug }}" />
                                            <flux:menu class="w-48">
                                                <flux:menu.item icon="pencil-square" :href="route('dashboard.sites.edit', $site->id)" wire:navigate>Code Studio</flux:menu.item>
                                                <flux:menu.item icon="photo" :href="route('dashboard.sites.media', $site->id)" wire:navigate>Media Assets</flux:menu.item>
                                                <flux:menu.item icon="chart-bar" :href="route('dashboard.sites.stats', $site->id)" wire:navigate>Analytics</flux:menu.item>
                                                <flux:menu.item icon="archive-box" :href="route('dashboard.sites.deployments', $site->id)" wire:navigate>Deployments</flux:menu.item>
                                                <flux:menu.item icon="globe-alt" :href="route('dashboard.sites.domains', $site->id)" wire:navigate>Custom Domains</flux:menu.item>
                                                <flux:menu.item icon="cog-6-tooth" :href="route('dashboard.sites.settings', $site->id)" wire:navigate>Settings</flux:menu.item>
                                                <flux:menu.separator />
                                                <flux:menu.item icon="arrow-down-tray" :href="route('dashboard.sites.download', $site->id)">Download ZIP</flux:menu.item>
                                                <flux:menu.item icon="document-duplicate" wire:click="duplicateSite('{{ $site->id }}')">Duplicate Site</flux:menu.item>
                                                <flux:menu.separator />
                                                <flux:menu.submenu icon="clock">
                                                    <flux:menu.heading>Extend Lifetime</flux:menu.heading>
                                                    <flux:menu.item wire:click="extendExpiry('{{ $site->id }}', 7)">+7 Days</flux:menu.item>
                                                    <flux:menu.item wire:click="extendExpiry('{{ $site->id }}', 30)">+30 Days</flux:menu.item>
                                                    <flux:menu.item wire:click="togglePermanent('{{ $site->id }}')">{{ $site->is_permanent ? 'Set 30d Expiry' : 'Make Permanent' }}</flux:menu.item>
                                                </flux:menu.submenu>
                                                <flux:menu.separator />
                                                <flux:menu.item icon="trash" @click="askDelete(@js($site->id), @js($site->original_name ?: $site->slug))" class="text-red-600 dark:text-red-400">Delete Site</flux:menu.item>
                                            </flux:menu>
                                        </flux:dropdown>
                                    </div>
                                </div>

                                <!-- Custom Domain Tag (if present) -->
                                @if($site->domains->count() > 0)
                                    <div class="mb-3 flex items-center gap-1.5 px-2.5 py-1 rounded-xl bg-zinc-100/90 dark:bg-zinc-800/80 border border-zinc-200/60 dark:border-zinc-700/60 text-xs">
                                        <flux:icon.globe-alt class="size-3.5 text-accent shrink-0" />
                                        <a href="https://{{ $site->domains->first()->domain }}" target="_blank" rel="noopener" class="truncate font-mono font-medium text-zinc-700 dark:text-zinc-300 hover:underline">
                                            {{ $site->domains->first()->domain }}
                                        </a>
                                    </div>
                                @endif

                                <!-- Meta Details Grid -->
                                <div class="grid grid-cols-2 gap-2 py-2.5 my-2 border-y border-zinc-200/60 dark:border-zinc-800/60 text-xs">
                                    <div>
                                        <span class="text-zinc-400">Total Views:</span>
                                        <span class="font-bold text-zinc-800 dark:text-zinc-200 ml-1">{{ number_format($site->views) }}</span>
                                    </div>
                                    <div class="text-right">
                                        <span class="text-zinc-400">Security:</span>
                                        @if($site->is_password_protected)
                                            <span class="font-semibold text-amber-600 dark:text-amber-400 ml-1 inline-flex items-center gap-1">
                                                <flux:icon.lock-closed class="size-3" /> Password
                                            </span>
                                        @else
                                            <span class="font-medium text-zinc-500 ml-1">Public</span>
                                        @endif
                                    </div>
                                </div>

                                <!-- Expiry Status Indicator -->
                                <div class="text-xs mb-3 space-y-1.5">
                                    @if($site->is_permanent)
                                        <div class="flex items-center gap-1.5 text-accent dark:text-accent-content font-semibold">
                                            <flux:icon.sparkles class="size-3.5" aria-hidden="true" />
                                            <span>Permanent (Never expires)</span>
                                        </div>
                                    @elseif($site->expires_at)
                                        <div class="flex items-center justify-between text-zinc-500 dark:text-zinc-400">
                                            <span>Expires in:</span>
                                            <span class="font-semibold {{ $site->expires_at->isPast() ? 'text-red-500' : ($site->expires_at->diffInHours() < 24 ? 'text-amber-500' : 'text-zinc-700 dark:text-zinc-300') }}">
                                                {{ $site->expires_at->isPast() ? 'Expired' : $site->expires_at->diffForHumans(null, true) }}
                                            </span>
                                        </div>
                                        @if($lifetimePct !== null)
                                            <span class="meter block" title="{{ __('Lifetime remaining in the current 30-day cycle') }}">
                                                <span class="meter-fill block {{ $site->expires_at->isPast() ? 'is-danger' : ($lifetimePct <= 25 ? 'is-warn' : 'is-ok') }}" style="width: {{ $site->expires_at->isPast() ? 100 : max($lifetimePct, 4) }}%"></span>
                                            </span>
                                        @endif
                                    @else
                                        <span class="text-zinc-400">—</span>
                                    @endif
                                </div>
                            </div>

                            <!-- Card Action Buttons -->
                            <div class="flex items-center justify-between gap-2 pt-3 border-t border-zinc-200/60 dark:border-zinc-800/60">
                                <a href="/s/{{ $site->slug }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-xl bg-white dark:bg-zinc-800 hover:bg-zinc-50 dark:hover:bg-zinc-700 text-zinc-800 dark:text-zinc-200 border border-zinc-200 dark:border-zinc-700 transition-all duration-150 shadow-xs hover:shadow-md hover:-translate-y-0.5">
                                    <flux:icon.arrow-top-right-on-square class="size-3.5" aria-hidden="true" />
                                    Preview
                                </a>
                                <div class="flex items-center gap-1">
                                    <flux:button :href="route('dashboard.sites.edit', $site->id)" wire:navigate variant="ghost" size="xs" icon="pencil-square" title="Code Studio" aria-label="{{ __('Open Code Studio for') }} {{ $site->slug }}" class="rounded-lg" />
                                    <flux:button :href="route('dashboard.sites.media', $site->id)" wire:navigate variant="ghost" size="xs" icon="photo" title="Media Assets" aria-label="{{ __('Media assets for') }} {{ $site->slug }}" class="rounded-lg" />
                                    <flux:button :href="route('dashboard.sites.stats', $site->id)" wire:navigate variant="ghost" size="xs" icon="chart-bar" title="Analytics" aria-label="{{ __('Analytics for') }} {{ $site->slug }}" class="rounded-lg" />
                                    <flux:button :href="route('dashboard.sites.domains', $site->id)" wire:navigate variant="ghost" size="xs" icon="globe-alt" title="Domains" aria-label="{{ __('Domains for') }} {{ $site->slug }}" class="rounded-lg" />
                                    <flux:button :href="route('dashboard.sites.settings', $site->id)" wire:navigate variant="ghost" size="xs" icon="cog-6-tooth" title="Settings" aria-label="{{ __('Settings for') }} {{ $site->slug }}" class="rounded-lg" />
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- 2. Dense Table View -->
                <div
                    x-show="viewMode === 'table'"
                    x-cloak
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 translate-y-1"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    x-transition:leave="transition ease-in duration-150"
                    x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                    class="overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white/50 dark:bg-zinc-900/40"
                >
                    <flux:table>
                        <flux:table.columns>
                            <flux:table.column>
                                <button
                                    type="button"
                                    wire:click="sortBy('original_name')"
                                    aria-label="{{ __('Sort by name') }}"
                                    @class(['inline-flex items-center gap-1 font-semibold cursor-pointer transition-colors duration-150 hover:text-accent', 'text-accent' => $sortBy === 'original_name'])
                                >
                                    Name &amp; Slug
                                    <flux:icon.chevron-up class="size-3 transition-transform duration-200 @if($sortBy === 'original_name') {{ $sortDir === 'asc' ? '' : 'rotate-180' }} @else opacity-30 @endif" />
                                </button>
                            </flux:table.column>
                            <flux:table.column>
                                <button
                                    type="button"
                                    wire:click="sortBy('views')"
                                    aria-label="{{ __('Sort by views') }}"
                                    @class(['inline-flex items-center gap-1 font-semibold cursor-pointer transition-colors duration-150 hover:text-accent', 'text-accent' => $sortBy === 'views'])
                                >
                                    Views
                                    <flux:icon.chevron-up class="size-3 transition-transform duration-200 @if($sortBy === 'views') {{ $sortDir === 'asc' ? '' : 'rotate-180' }} @else opacity-30 @endif" />
                                </button>
                            </flux:table.column>
                            <flux:table.column>
                                <button
                                    type="button"
                                    wire:click="sortBy('expires_at')"
                                    aria-label="{{ __('Sort by expiry') }}"
                                    @class(['inline-flex items-center gap-1 font-semibold cursor-pointer transition-colors duration-150 hover:text-accent', 'text-accent' => $sortBy === 'expires_at'])
                                >
                                    Expiry Status
                                    <flux:icon.chevron-up class="size-3 transition-transform duration-200 @if($sortBy === 'expires_at') {{ $sortDir === 'asc' ? '' : 'rotate-180' }} @else opacity-30 @endif" />
                                </button>
                            </flux:table.column>
                            <flux:table.column>Security</flux:table.column>
                            <flux:table.column>Status</flux:table.column>
                            <flux:table.column class="text-right">Actions</flux:table.column>
                        </flux:table.columns>

                        <flux:table.rows>
                            @foreach ($sites as $site)
                                <flux:table.row wire:key="site-row-{{ $site->id }}" class="hover:bg-zinc-50/50 dark:hover:bg-zinc-800/40 transition-colors duration-150">
                                    <flux:table.cell>
                                        <div class="font-semibold flex items-center gap-2 text-zinc-900 dark:text-zinc-100">
                                            {{ $site->original_name ?: 'Unnamed Site' }}
                                            @if($site->github_repo_full_name)
                                                <flux:badge size="xs" color="zinc">GitHub</flux:badge>
                                            @endif
                                        </div>
                                        <div class="text-xs text-zinc-500 flex items-center gap-2 mt-0.5">
                                            <a href="/s/{{ $site->slug }}" target="_blank" rel="noopener" class="hover:underline text-accent dark:text-accent-content font-mono">/s/{{ $site->slug }}</a>
                                            @if($site->domains->count() > 0)
                                                <span class="text-zinc-400">&bull;</span>
                                                <span class="text-zinc-400 font-medium">{{ $site->domains->first()->domain }}</span>
                                            @endif
                                        </div>
                                    </flux:table.cell>
                                    <flux:table.cell class="font-bold text-zinc-800 dark:text-zinc-200">
                                        {{ number_format($site->views) }}
                                    </flux:table.cell>
                                    <flux:table.cell>
                                        @if($site->is_permanent)
                                            <flux:badge size="sm" color="indigo">Permanent</flux:badge>
                                        @elseif($site->expires_at)
                                            <span class="text-xs font-semibold {{ $site->expires_at->isPast() ? 'text-red-500' : ($site->expires_at->diffInHours() < 24 ? 'text-amber-500' : 'text-zinc-600 dark:text-zinc-400') }}">
                                                {{ $site->expires_at->diffForHumans(null, true) }}
                                            </span>
                                        @else
                                            <span class="text-zinc-400">—</span>
                                        @endif
                                    </flux:table.cell>
                                    <flux:table.cell>
                                        @if($site->is_password_protected)
                                            <flux:badge size="sm" color="amber" icon="lock-closed">Protected</flux:badge>
                                        @else
                                            <flux:badge size="sm" color="zinc">Public</flux:badge>
                                        @endif
                                    </flux:table.cell>
                                    <flux:table.cell>
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold {{ $site->status === 'active' ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400' : 'bg-red-500/10 text-red-600 dark:text-red-400' }}">
                                            {{ ucfirst($site->status) }}
                                        </span>
                                    </flux:table.cell>
                                    <flux:table.cell class="text-right">
                                        <div class="flex items-center justify-end gap-1">
                                            <flux:button variant="ghost" size="xs" icon="clipboard" @click="copyText(@js(url('/s/' . $site->slug)), @js($site->id))" title="Copy URL" aria-label="{{ __('Copy live URL for') }} {{ $site->slug }}" />
                                            <flux:button :href="route('dashboard.sites.edit', $site->id)" wire:navigate variant="ghost" size="xs" icon="pencil-square" title="Code Studio" aria-label="{{ __('Open Code Studio for') }} {{ $site->slug }}" />
                                            <flux:button :href="route('dashboard.sites.stats', $site->id)" wire:navigate variant="ghost" size="xs" icon="chart-bar" title="Analytics" aria-label="{{ __('Analytics for') }} {{ $site->slug }}" />
                                            <flux:dropdown position="bottom" align="end">
                                                <flux:button variant="ghost" size="xs" icon="ellipsis-horizontal" aria-label="{{ __('More actions for') }} {{ $site->slug }}" />
                                                <flux:menu>
                                                    <flux:menu.item icon="arrow-top-right-on-square" href="/s/{{ $site->slug }}" target="_blank" rel="noopener">View Live</flux:menu.item>
                                                    <flux:menu.item icon="photo" :href="route('dashboard.sites.media', $site->id)" wire:navigate>Media Assets</flux:menu.item>
                                                    <flux:menu.item icon="globe-alt" :href="route('dashboard.sites.domains', $site->id)" wire:navigate>Domains</flux:menu.item>
                                                    <flux:menu.item icon="cog-6-tooth" :href="route('dashboard.sites.settings', $site->id)" wire:navigate>Settings</flux:menu.item>
                                                    <flux:menu.separator />
                                                    <flux:menu.item icon="arrow-down-tray" :href="route('dashboard.sites.download', $site->id)">Download ZIP</flux:menu.item>
                                                    <flux:menu.item icon="document-duplicate" wire:click="duplicateSite('{{ $site->id }}')">Duplicate Site</flux:menu.item>
                                                    <flux:menu.separator />
                                                    <flux:menu.item icon="clock" wire:click="extendExpiry('{{ $site->id }}', 7)">+7 Days Expiry</flux:menu.item>
                                                    <flux:menu.item icon="sparkles" wire:click="togglePermanent('{{ $site->id }}')">{{ $site->is_permanent ? 'Set Expiry (30d)' : 'Make Permanent' }}</flux:menu.item>
                                                    <flux:menu.separator />
                                                    <flux:menu.item icon="trash" @click="askDelete(@js($site->id), @js($site->original_name ?: $site->slug))" class="text-red-600 dark:text-red-400">Delete</flux:menu.item>
                                                </flux:menu>
                                            </flux:dropdown>
                                        </div>
                                    </flux:table.cell>
                                </flux:table.row>
                            @endforeach
                        </flux:table.rows>
                    </flux:table>
                </div>
            </div>

            @if($sites->hasPages())
                <div class="mt-6 pt-5 border-t border-zinc-200/60 dark:border-zinc-800/60">
                    {{ $sites->links() }}
                </div>
            @endif
        @endif
    </div>
</div>
