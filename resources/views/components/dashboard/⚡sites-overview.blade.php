<?php

use App\Models\Domain;
use App\Services\ActivityLogService;
use App\Services\SiteDeploymentService;
use App\Services\StorageUsageService;
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

        if (! in_array($days, [7, 30], true)) {
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

    public function placeholder()
    {
        return view('components.dashboard.overview-placeholder');
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
        $storageStats = app(StorageUsageService::class)->getUserStorageStats(auth()->user());

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

        // Cache only the default overview, never a filtered result or a Livewire snapshot.
        $cachePreview = $this->search === '' && $this->statusFilter === 'all'
            && $this->sortBy === 'created_at' && $this->sortDir === 'desc' && $sites->currentPage() === 1
            ? [
                'stats' => [
                    ['label' => 'Total sites', 'value' => number_format($totalSites)],
                    ['label' => 'Page views', 'value' => number_format($totalViews)],
                    ['label' => 'Custom domains', 'value' => number_format($totalDomains)],
                    ['label' => 'Storage & quota', 'value' => $storageStats['formatted']['total_used']],
                ],
                'sites' => $sites->map(fn ($site) => [
                    'id' => $site->id, 'name' => $site->original_name ?: $site->slug,
                    'slug' => $site->slug, 'status' => ucfirst($site->status),
                ])->values()->all(),
            ] : null;

        return view('components.dashboard.⚡sites-overview', [
            'cachePreview' => $cachePreview,
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
    @if($cachePreview !== null)
        <div hidden wire:key="overview-cache-{{ sha1(json_encode($cachePreview)) }}"
            x-init="window.drophtmlDashboardCache?.write({{ \Illuminate\Support\Js::from((string) auth()->id()) }}, {{ \Illuminate\Support\Js::from($cachePreview) }})"></div>
    @endif
    <!-- Drag overlay -->
    <div
        x-show="dragging"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="fixed inset-0 z-50 flex items-center justify-center dashboard-inset pointer-events-none"
        style="display: none;"
    >
        <div class="p-10 border-2 border-dashed dashboard-accent-border dashboard-rounded dashboard-surface dashboard-text flex flex-col items-center gap-4 max-w-md text-center">
            <div class="size-16 dashboard-rounded dashboard-accent-tint dashboard-accent flex items-center justify-center animate-bounce">
                <flux:icon.cloud-arrow-up class="size-8" />
            </div>
            <div>
                <div class="text-xl font-medium dashboard-text">Drop to Deploy Site</div>
                <p class="text-sm dashboard-muted mt-1">Single HTML document or complete ZIP archive</p>
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
    <x-dashboard.page-heading :title="$greeting.', '.auth()->user()->name" description="Manage your sites, publish updates, and keep your media in one place." eyebrow="YOUR WORKSPACE">

                <flux:modal.trigger name="upload-site-modal">
                    <flux:button data-dashboard-variant="primary" variant="primary" size="base" icon="cloud-arrow-up" class="font-medium">
                        Deploy New Site
                    </flux:button>
                </flux:modal.trigger>

                @if(auth()->user()->github_id)
                    <flux:modal.trigger name="github-deploy-modal">
                        <flux:button data-dashboard-variant="outline" variant="outline" size="base">
                            <svg class="size-4 mr-1.5 dashboard-text" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path fill-rule="evenodd" d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.531 1.032 1.531 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0022 12.017C22 6.484 17.522 2 12 2z" clip-rule="evenodd" /></svg>
                            GitHub Sync
                        </flux:button>
                    </flux:modal.trigger>
                @endif

                <a href="{{ route('dashboard.media') }}" wire:navigate class="studio-button">
                    <flux:icon.photo class="size-3.5" aria-hidden="true" />
                    <span>Media library</span>
                </a>
                </x-dashboard.page-heading>

    <!-- 4 KPI Stat Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Stat 1: Total Sites -->
        <div class="dashboard-panel dashboard-panel-interactive p-5 relative overflow-hidden group">
            <div class="flex items-center justify-between">
                <div>
                    <span class="dashboard-stat-label">Total Sites</span>
                    <div class="dashboard-stat-value">{{ number_format($totalSites) }}</div>
                </div>
                <div class="dashboard-icon">
                    <flux:icon.globe-alt class="size-5" aria-hidden="true" />
                </div>
            </div>
            <div class="mt-3 text-xs dashboard-muted flex items-center gap-1.5">
                <span class="size-2 rounded-full dashboard-success-solid" aria-hidden="true"></span>
                <span>Active Edge Deployments</span>
            </div>
        </div>

        <!-- Stat 2: Total Page Views -->
        <div class="dashboard-panel dashboard-panel-interactive p-5 relative overflow-hidden group">
            <div class="flex items-center justify-between">
                <div>
                    <span class="dashboard-stat-label">Total Page Views</span>
                    <div class="dashboard-stat-value">{{ number_format($totalViews) }}</div>
                </div>
                <div class="dashboard-icon">
                    <flux:icon.eye class="size-5" aria-hidden="true" />
                </div>
            </div>
            <div class="mt-3 text-xs dashboard-muted flex items-center gap-1.5">
                <flux:icon.chart-bar class="size-3.5 dashboard-muted" aria-hidden="true" />
                <span>Across all deployed sites</span>
            </div>
        </div>

        <!-- Stat 3: Connected Domains -->
        <div class="dashboard-panel dashboard-panel-interactive p-5 relative overflow-hidden group">
            <div class="flex items-center justify-between">
                <div>
                    <span class="dashboard-stat-label">Custom Domains</span>
                    <div class="dashboard-stat-value">{{ number_format($totalDomains) }}</div>
                </div>
                <div class="dashboard-icon">
                    <flux:icon.link class="size-5" aria-hidden="true" />
                </div>
            </div>
            <div class="mt-3 text-xs dashboard-muted flex items-center gap-1.5">
                <flux:icon.shield-check class="size-3.5 dashboard-success" aria-hidden="true" />
                <span>Automatic SSL provisioned</span>
            </div>
        </div>

        <!-- Stat 4: Storage & Quota -->
        @php
            $storagePct = min(100, max(0, (int) $storageStats['used_percentage']));
        @endphp
        <a href="{{ route('settings.storage') }}" wire:navigate class="dashboard-panel dashboard-panel-interactive p-5 relative overflow-hidden group block" aria-label="{{ __('Storage and quota') }}">
            <div class="flex items-center justify-between">
                <div>
                    <span class="dashboard-stat-label">Storage &amp; Quota</span>
                    <div class="dashboard-stat-value font-mono">{{ $storageStats['formatted']['total_used'] }}</div>
                </div>
                <div class="dashboard-icon dashboard-accent-border dashboard-accent-tint dashboard-accent group-hover:scale-105 transition-transform duration-200">
                    <flux:icon.circle-stack class="size-5" aria-hidden="true" />
                </div>
            </div>
            <div class="mt-3 space-y-2">
                <span class="dashboard-meter dashboard-meter-lg block">
                    <span class="dashboard-meter-fill block {{ $storagePct >= 90 ? 'is-danger' : ($storagePct >= 75 ? 'is-warn' : '') }}" style="width: {{ $storagePct }}%"></span>
                </span>
                <div class="flex items-center justify-between text-xs dashboard-muted">
                    <span class="tabular-nums truncate">
                        {{ $storagePct }}% of {{ $storageStats['formatted']['quota'] }} used
                    </span>
                    <span class="dashboard-accent  group-hover:underline font-medium inline-flex items-center gap-1">
                        <span>Manage</span>
                        <span aria-hidden="true">&rarr;</span>
                    </span>
                </div>
            </div>
        </a>
    </div>

    <!-- Main Container: Search, Filters & Content -->
    <div class="dashboard-panel p-5 sm:p-7">
        <!-- Filter and Search Header -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
            <!-- Search & Filter Chips -->
            <div class="flex flex-1 flex-wrap items-center gap-3 min-w-0">
                <div class="relative w-full sm:w-80">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none dashboard-muted">
                        <span wire:loading.remove wire:target="search"><flux:icon.magnifying-glass class="size-4" /></span>
                        <span wire:loading wire:target="search"><flux:icon.arrow-path class="size-4 animate-spin" /></span>
                    </div>
                    <input
                        wire:model.live.debounce.250ms="search"
                        type="search"
                        aria-label="{{ __('Search sites by name, slug or domain') }}"
                        placeholder="Search sites, slugs, domains..."
                        class="w-full pl-9 pr-8 py-2 text-xs sm:text-sm dashboard-control-rounded dashboard-surface border dashboard-border dashboard-text placeholder-zinc-400    dashboard-accent-border transition-all duration-150 [&::-webkit-search-cancel-button]:appearance-none"
                    />
                    @if($search)
                        <button wire:click="$set('search', '')" class="absolute inset-y-0 right-0 pr-2.5 flex items-center dashboard-muted dashboard-hover-text cursor-pointer" aria-label="{{ __('Clear search') }}">
                            <flux:icon.x-mark class="size-4" />
                        </button>
                    @endif
                </div>

                <!-- Filter Pills -->
                <div class="dashboard-tabs" role="group" aria-label="{{ __('Filter sites by status') }}">
                    <button type="button" wire:click="setFilter('all')" aria-pressed="{{ $statusFilter === 'all' ? 'true' : 'false' }}" class="dashboard-tab">
                        All <span class="opacity-60 tabular-nums">({{ $totalSites }})</span>
                    </button>
                    <button type="button" wire:click="setFilter('active')" aria-pressed="{{ $statusFilter === 'active' ? 'true' : 'false' }}" class="dashboard-tab">
                        Active
                    </button>
                    <button type="button" wire:click="setFilter('permanent')" aria-pressed="{{ $statusFilter === 'permanent' ? 'true' : 'false' }}" class="dashboard-tab">
                        Permanent
                    </button>
                    <button type="button" wire:click="setFilter('expiring')" aria-pressed="{{ $statusFilter === 'expiring' ? 'true' : 'false' }}" class="dashboard-tab">
                        Expiring
                        @if($expiringCount > 0)
                            <span class="dashboard-count {{ $statusFilter === 'expiring' ? 'dashboard-accent-tint' : 'dashboard-warning-solid' }}">{{ $expiringCount }}</span>
                        @endif
                    </button>
                </div>
            </div>

            <!-- View Switcher & Action Buttons -->
            <div class="flex items-center gap-2.5 shrink-0">
                <span class="hidden sm:inline-flex items-center text-xs font-medium dashboard-muted tabular-nums" aria-live="polite" role="status">
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
                    <flux:button data-dashboard-variant="ghost" variant="ghost" size="sm" icon="bars-arrow-down" title="{{ __('Sort sites') }}">
                        <span class="hidden sm:inline">Sort</span>
                    </flux:button>
                    <flux:menu>
                        <flux:menu.heading>Sort sites by</flux:menu.heading>
                        @foreach($sortOptions as [$col, $dir, $label])
                            <flux:menu.item
                                icon="{{ $sortBy === $col && $sortDir === $dir ? 'check' : 'minus' }}"
                                wire:click="setSort('{{ $col }}', '{{ $dir }}')"
                                @class(['font-medium dashboard-accent ' => $sortBy === $col && $sortDir === $dir])
                            >
                                {{ $label }}
                            </flux:menu.item>
                        @endforeach
                    </flux:menu>
                </flux:dropdown>

                <!-- Grid / Table View Switcher -->
                <div class="hidden sm:inline-flex dashboard-control-rounded p-1 dashboard-raised border dashboard-border" role="group" aria-label="{{ __('Change result layout') }}">
                    <button type="button" @click="setViewMode('grid')" :aria-pressed="viewMode === 'grid'" :class="viewMode === 'grid' ? 'dashboard-surface  dashboard-accent ' : 'dashboard-muted dashboard-hover-text '" class="p-1.5 dashboard-control-rounded transition-colors duration-150 cursor-pointer" title="Grid View" aria-label="{{ __('Grid view') }}">
                        <flux:icon.squares-2x2 class="size-4" />
                    </button>
                    <button type="button" @click="setViewMode('table')" :aria-pressed="viewMode === 'table'" :class="viewMode === 'table' ? 'dashboard-surface  dashboard-accent ' : 'dashboard-muted dashboard-hover-text '" class="p-1.5 dashboard-control-rounded transition-colors duration-150 cursor-pointer" title="Dense Table View" aria-label="{{ __('Table view') }}">
                        <flux:icon.bars-3-bottom-left class="size-4" />
                    </button>
                </div>

                <flux:modal.trigger name="upload-site-modal">
                    <flux:button data-dashboard-variant="primary" variant="primary" size="sm" icon="plus">
                        Deploy Site
                    </flux:button>
                </flux:modal.trigger>
            </div>
        </div>

        <!-- In-flight request indicator -->
        <div wire:loading class="dashboard-meter is-indeterminate mb-5" role="progressbar" aria-label="{{ __('Loading sites') }}">
            <div class="dashboard-meter-fill"></div>
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

                <livewire:file-uploader :dashboard="true" />
            </div>
        </flux:modal>

        <!-- Delete Confirmation Modal -->
        <flux:modal name="confirm-site-deletion" class="md:w-[440px]">
            <div class="space-y-6">
                <div class="flex items-start gap-3">
                    <span class="size-9 dashboard-control-rounded dashboard-danger-tint dashboard-danger flex items-center justify-center  shrink-0">
                        <flux:icon.exclamation-triangle class="size-4.5" />
                    </span>
                    <div>
                        <flux:heading size="lg" class="tracking-tight">Delete this site?</flux:heading>
                        <flux:subheading class="mt-1">
                            <span class="font-medium dashboard-text" x-text="confirmDeleteName"></span>
                            and all of its files, custom domains, and analytics will be permanently removed. This action cannot be undone.
                        </flux:subheading>
                    </div>
                </div>

                <div class="flex justify-end gap-2">
                    <flux:modal.close>
                        <flux:button data-dashboard-variant="outline" variant="outline">Cancel</flux:button>
                    </flux:modal.close>

                    <flux:button data-dashboard-variant="danger"
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
            <div class="py-16 flex flex-col items-center justify-center text-center">
                <div class="size-16 dashboard-rounded dashboard-accent-tint dashboard-accent flex items-center justify-center mb-4 border dashboard-accent-border">
                    <flux:icon.cloud-arrow-up class="size-8" aria-hidden="true" />
                </div>
                @if($search)
                    <h3 class="dashboard-section-title   ">No matching sites found</h3>
                    <p class="text-xs sm:text-sm dashboard-muted max-w-sm mt-1 mb-6 leading-relaxed">
                        We couldn't find any sites matching '{{ $search }}'. Check for typos or reset your filter.
                    </p>
                    <flux:button data-dashboard-variant="outline" wire:click="$set('search', '')" variant="outline" size="sm">Clear Search</flux:button>
                @elseif($statusFilter !== 'all')
                    <h3 class="dashboard-section-title   ">No sites in this view</h3>
                    <p class="text-xs sm:text-sm dashboard-muted max-w-sm mt-1 mb-6 leading-relaxed">
                        None of your sites currently match the selected filter. Try a different filter or deploy a new site.
                    </p>
                    <flux:button data-dashboard-variant="outline" wire:click="setFilter('all')" variant="outline" size="sm">Show All Sites</flux:button>
                @else
                    <h3 class="dashboard-section-title   ">No static sites deployed yet</h3>
                    <p class="text-xs sm:text-sm dashboard-muted max-w-sm mt-1 mb-6 leading-relaxed">
                        Drag and drop an HTML or ZIP file anywhere on this screen, or launch an upload modal.
                    </p>
                    <flux:modal.trigger name="upload-site-modal">
                        <flux:button data-dashboard-variant="primary" variant="primary" size="sm" icon="plus">Deploy Your First Site</flux:button>
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
                            x-data="dashboardContextMenu" tabindex="0" aria-haspopup="menu" x-on:contextmenu="showContext($event)" x-on:keydown="if ($event.key === 'ContextMenu' || ($event.shiftKey && $event.key === 'F10')) showContext($event)"

                            class="group dashboard-panel dashboard-panel-interactive dashboard-surface p-5 flex flex-col justify-between relative overflow-hidden"
                        >
                            <x-dashboard.site-context-actions :site="$site" />
                            <div>
                                <!-- Header Row -->
                                <div class="flex items-start justify-between gap-3 mb-3">
                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-center gap-2">
                                            <div class="size-8 dashboard-control-rounded dashboard-accent-tint dashboard-accent border dashboard-accent-border flex items-center justify-center font-medium text-xs shrink-0">
                                                {{ strtoupper(substr($site->original_name ?: $site->slug, 0, 1)) }}
                                            </div>
                                            <h3 class="dashboard-section-title  text-sm  truncate" title="{{ $site->original_name ?: $site->slug }}">
                                                {{ $site->original_name ?: $site->slug }}
                                            </h3>
                                            @if($site->github_repo_full_name)
                                                <x-dashboard.badge size="xs" color="zinc">GitHub</x-dashboard.badge>
                                            @endif
                                        </div>

                                        <div class="flex items-center gap-1.5 mt-1 text-xs dashboard-muted">
                                            <a href="/s/{{ $site->slug }}" target="_blank" rel="noopener" class="font-mono dashboard-accent  hover:underline truncate max-w-[190px]">
                                                /s/{{ $site->slug }}
                                            </a>
                                            <button
                                                type="button"
                                                @click="copyText({{ \Illuminate\Support\Js::from(url('/s/' . $site->slug)) }}, {{ \Illuminate\Support\Js::from($site->id) }})"
                                                class="dashboard-muted dashboard-hover-text p-0.5 rounded transition-colors duration-150 cursor-pointer"
                                                title="Copy live URL"
                                                aria-label="{{ __('Copy live URL for') }} {{ $site->slug }}"
                                            >
                                                <flux:icon.clipboard x-show="copiedId !== {{ \Illuminate\Support\Js::from($site->id) }}" class="size-3.5" />
                                                <flux:icon.check x-show="copiedId === {{ \Illuminate\Support\Js::from($site->id) }}" x-cloak class="size-3.5 dashboard-success" />
                                            </button>
                                        </div>
                                    </div>

                                    <div class="flex items-center gap-1 shrink-0">
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-medium {{ $site->status === 'active' ? 'dashboard-success-tint dashboard-success  border dashboard-success-border' : 'dashboard-danger-tint dashboard-danger  border dashboard-danger-border' }}">
                                            <span class="size-1.5 rounded-full {{ $site->status === 'active' ? 'dashboard-success-solid ' : 'dashboard-danger-solid' }}"></span>
                                            {{ ucfirst($site->status) }}
                                        </span>

                                        <flux:dropdown>
                                            <flux:button data-dashboard-variant="ghost" variant="ghost" size="xs" icon="ellipsis-vertical" class="dashboard-control-rounded cursor-pointer" aria-label="{{ __('More actions for') }} {{ $site->slug }}" />
                                            <flux:menu class="w-48">
                                                <flux:menu.item icon="pencil-square" :href="route('dashboard.sites.edit', $site->id)">Code Studio</flux:menu.item>
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
                                                <flux:menu.item icon="trash" @click="askDelete({{ \Illuminate\Support\Js::from($site->id) }}, {{ \Illuminate\Support\Js::from($site->original_name ?: $site->slug) }})" class="dashboard-danger">Delete Site</flux:menu.item>
                                            </flux:menu>
                                        </flux:dropdown>
                                    </div>
                                </div>

                                <!-- Custom Domain Tag (if present) -->
                                @if($site->domains->count() > 0)
                                    <div class="mb-3 flex items-center gap-1.5 px-2.5 py-1 dashboard-control-rounded dashboard-raised border dashboard-border text-xs">
                                        <flux:icon.globe-alt class="size-3.5 dashboard-accent shrink-0" />
                                        <a href="https://{{ $site->domains->first()->domain }}" target="_blank" rel="noopener" class="truncate font-mono font-medium dashboard-text hover:underline">
                                            {{ $site->domains->first()->domain }}
                                        </a>
                                    </div>
                                @endif

                                <!-- Meta Details Grid -->
                                <div class="grid grid-cols-2 gap-2 py-2.5 my-2 border-y dashboard-border text-xs">
                                    <div>
                                        <span class="dashboard-muted">Total Views:</span>
                                        <span class="font-medium dashboard-text ml-1">{{ number_format($site->views) }}</span>
                                    </div>
                                    <div class="text-right">
                                        <span class="dashboard-muted">Security:</span>
                                        @if($site->is_password_protected)
                                            <span class="font-medium dashboard-warning ml-1 inline-flex items-center gap-1">
                                                <flux:icon.lock-closed class="size-3" /> Password
                                            </span>
                                        @else
                                            <span class="font-medium dashboard-muted ml-1">Public</span>
                                        @endif
                                    </div>
                                </div>

                                <!-- Expiry Status Indicator -->
                                <div class="text-xs mb-3 space-y-1.5">
                                    @if($site->is_permanent)
                                        <div class="flex items-center gap-1.5 dashboard-accent  font-medium">
                                            <flux:icon.sparkles class="size-3.5" aria-hidden="true" />
                                            <span>Permanent (Never expires)</span>
                                        </div>
                                    @elseif($site->expires_at)
                                        <div class="flex items-center justify-between dashboard-muted">
                                            <span>Expires in:</span>
                                            <span class="font-medium {{ $site->expires_at->isPast() ? 'dashboard-danger' : ($site->expires_at->diffInHours() < 24 ? 'dashboard-warning' : 'dashboard-text ') }}">
                                                {{ $site->expires_at->isPast() ? 'Expired' : $site->expires_at->diffForHumans(null, true) }}
                                            </span>
                                        </div>
                                        @if($lifetimePct !== null)
                                            <span class="dashboard-meter block" title="{{ __('Lifetime remaining in the current 30-day cycle') }}">
                                                <span class="dashboard-meter-fill block {{ $site->expires_at->isPast() ? 'is-danger' : ($lifetimePct <= 25 ? 'is-warn' : 'is-ok') }}" style="width: {{ $site->expires_at->isPast() ? 100 : max($lifetimePct, 4) }}%"></span>
                                            </span>
                                        @endif
                                    @else
                                        <span class="dashboard-muted">—</span>
                                    @endif
                                </div>
                            </div>

                            <!-- Card Action Buttons -->
                            <div class="flex items-center justify-between gap-2 pt-3 border-t dashboard-border">
                                <a href="/s/{{ $site->slug }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium dashboard-control-rounded dashboard-surface dashboard-hover-surface dashboard-text border dashboard-border transition-all duration-150">
                                    <flux:icon.arrow-top-right-on-square class="size-3.5" aria-hidden="true" />
                                    Preview
                                </a>
                                <div class="flex items-center gap-1">
                                    <flux:button data-dashboard-variant="outline" :href="route('dashboard.sites.edit', $site->id)" variant="ghost" size="xs" icon="pencil-square" title="Code Studio" aria-label="{{ __('Open Code Studio for') }} {{ $site->slug }}" class="dashboard-control-rounded" />
                                    <flux:button data-dashboard-variant="outline" :href="route('dashboard.sites.media', $site->id)" wire:navigate variant="ghost" size="xs" icon="photo" title="Media Assets" aria-label="{{ __('Media assets for') }} {{ $site->slug }}" class="dashboard-control-rounded" />
                                    <flux:button data-dashboard-variant="outline" :href="route('dashboard.sites.stats', $site->id)" wire:navigate variant="ghost" size="xs" icon="chart-bar" title="Analytics" aria-label="{{ __('Analytics for') }} {{ $site->slug }}" class="dashboard-control-rounded" />
                                    <flux:button data-dashboard-variant="outline" :href="route('dashboard.sites.domains', $site->id)" wire:navigate variant="ghost" size="xs" icon="globe-alt" title="Domains" aria-label="{{ __('Domains for') }} {{ $site->slug }}" class="dashboard-control-rounded" />
                                    <flux:button data-dashboard-variant="outline" :href="route('dashboard.sites.settings', $site->id)" wire:navigate variant="ghost" size="xs" icon="cog-6-tooth" title="Settings" aria-label="{{ __('Settings for') }} {{ $site->slug }}" class="dashboard-control-rounded" />
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
                    class="overflow-x-auto dashboard-control-rounded border dashboard-border dashboard-surface"
                >
                    <flux:table>
                        <flux:table.columns>
                            <flux:table.column>
                                <button
                                    type="button"
                                    wire:click="sortBy('original_name')"
                                    aria-label="{{ __('Sort by name') }}"
                                    @class(['inline-flex items-center gap-1 font-medium cursor-pointer transition-colors duration-150 dashboard-accent', 'dashboard-accent' => $sortBy === 'original_name'])
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
                                    @class(['inline-flex items-center gap-1 font-medium cursor-pointer transition-colors duration-150 dashboard-accent', 'dashboard-accent' => $sortBy === 'views'])
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
                                    @class(['inline-flex items-center gap-1 font-medium cursor-pointer transition-colors duration-150 dashboard-accent', 'dashboard-accent' => $sortBy === 'expires_at'])
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
                                <flux:table.row x-data="dashboardContextMenu" tabindex="0" aria-haspopup="menu" x-on:contextmenu="showContext($event)" x-on:keydown="if ($event.key === 'ContextMenu' || ($event.shiftKey && $event.key === 'F10')) showContext($event)" wire:key="site-row-{{ $site->id }}" class="dashboard-hover-surface transition-colors duration-150">
                                    <flux:table.cell>
                                        <div class="font-medium flex items-center gap-2 dashboard-text">
                                            {{ $site->original_name ?: 'Unnamed Site' }}
                                            @if($site->github_repo_full_name)
                                                <x-dashboard.badge size="xs" color="zinc">GitHub</x-dashboard.badge>
                                            @endif
                                        </div>
                                        <div class="text-xs dashboard-muted flex items-center gap-2 mt-0.5">
                                            <a href="/s/{{ $site->slug }}" target="_blank" rel="noopener" class="hover:underline dashboard-accent  font-mono">/s/{{ $site->slug }}</a>
                                            @if($site->domains->count() > 0)
                                                <span class="dashboard-muted">&bull;</span>
                                                <span class="dashboard-muted font-medium">{{ $site->domains->first()->domain }}</span>
                                            @endif
                                        </div>
                                    </flux:table.cell>
                                    <flux:table.cell class="font-medium dashboard-text">
                                        {{ number_format($site->views) }}
                                    </flux:table.cell>
                                    <flux:table.cell>
                                        @if($site->is_permanent)
                                            <x-dashboard.badge size="sm" color="indigo">Permanent</x-dashboard.badge>
                                        @elseif($site->expires_at)
                                            <span class="text-xs font-medium {{ $site->expires_at->isPast() ? 'dashboard-danger' : ($site->expires_at->diffInHours() < 24 ? 'dashboard-warning' : 'dashboard-muted ') }}">
                                                {{ $site->expires_at->diffForHumans(null, true) }}
                                            </span>
                                        @else
                                            <span class="dashboard-muted">—</span>
                                        @endif
                                    </flux:table.cell>
                                    <flux:table.cell>
                                        @if($site->is_password_protected)
                                            <x-dashboard.badge size="sm" color="amber" icon="lock-closed">Protected</x-dashboard.badge>
                                        @else
                                            <x-dashboard.badge size="sm" color="zinc">Public</x-dashboard.badge>
                                        @endif
                                    </flux:table.cell>
                                    <flux:table.cell>
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-medium {{ $site->status === 'active' ? 'dashboard-success-tint dashboard-success ' : 'dashboard-danger-tint dashboard-danger ' }}">
                                            {{ ucfirst($site->status) }}
                                        </span>
                                    </flux:table.cell>
                                    <flux:table.cell class="text-right">
                                        <div class="flex items-center justify-end gap-1">
                                            <flux:button data-dashboard-variant="ghost" variant="ghost" size="xs" icon="clipboard" @click="copyText({{ \Illuminate\Support\Js::from(url('/s/' . $site->slug)) }}, {{ \Illuminate\Support\Js::from($site->id) }})" title="Copy URL" aria-label="{{ __('Copy live URL for') }} {{ $site->slug }}" />
                                            <flux:button data-dashboard-variant="outline" :href="route('dashboard.sites.edit', $site->id)" variant="ghost" size="xs" icon="pencil-square" title="Code Studio" aria-label="{{ __('Open Code Studio for') }} {{ $site->slug }}" />
                                            <flux:button data-dashboard-variant="outline" :href="route('dashboard.sites.stats', $site->id)" wire:navigate variant="ghost" size="xs" icon="chart-bar" title="Analytics" aria-label="{{ __('Analytics for') }} {{ $site->slug }}" />
                                            <x-dashboard.site-context-actions :site="$site" />
                                            <flux:dropdown position="bottom" align="end">
                                                <flux:button data-dashboard-variant="ghost" variant="ghost" size="xs" icon="ellipsis-horizontal" aria-label="{{ __('More actions for') }} {{ $site->slug }}" />
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
                                                    <flux:menu.item icon="trash" @click="askDelete({{ \Illuminate\Support\Js::from($site->id) }}, {{ \Illuminate\Support\Js::from($site->original_name ?: $site->slug) }})" class="dashboard-danger">Delete</flux:menu.item>
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
                <div class="mt-6 pt-5 border-t dashboard-border">
                    {{ $sites->links() }}
                </div>
            @endif
        @endif
    </div>
</div>
