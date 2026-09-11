<?php

use App\Models\MediaAsset;
use App\Models\Site;
use App\Services\ActivityLogService;
use App\Services\SiteDeploymentService;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component
{
    public $search = '';
    public $statusFilter = 'all'; // all, active, suspended, permanent, expiring

    #[On('site-published')]
    #[On('site-deleted')]
    public function refresh() {}

    public function setFilter($filter)
    {
        $this->statusFilter = in_array($filter, ['all', 'active', 'suspended', 'permanent', 'expiring'], true) ? $filter : 'all';
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
        $allSites = auth()->user()->sites()->with('domains')->latest()->get();

        $query = auth()->user()->sites()->with('domains')->latest();

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

        $sites = $query->get();

        $totalViews = $allSites->sum('views');
        $totalSites = $allSites->count();
        $totalDomains = $allSites->pluck('domains')->flatten()->count();
        $expiringCount = $allSites->filter(fn ($s) => ! $s->is_permanent && $s->expires_at && $s->expires_at->diffInDays(now()) <= 7 && $s->expires_at->isFuture())->count();
        $totalMediaAssets = auth()->user()->mediaAssets()->count();
        $totalMediaBytes = (int) auth()->user()->mediaAssets()->sum('size');
        $formattedMediaBytes = MediaAsset::formatBytes($totalMediaBytes);
        $storageStats = app(\App\Services\StorageUsageService::class)->getUserStorageStats(auth()->user());

        return view('components.dashboard.⚡sites-overview', [
            'sites' => $sites,
            'totalSites' => $totalSites,
            'totalViews' => $totalViews,
            'totalDomains' => $totalDomains,
            'expiringCount' => $expiringCount,
            'totalMediaAssets' => $totalMediaAssets,
            'totalMediaBytes' => $totalMediaBytes,
            'formattedMediaBytes' => $formattedMediaBytes,
            'storageStats' => $storageStats,
        ]);
    }
};
?>
<div
    x-data="{
        dragging: false,
        viewMode: localStorage.getItem('drophtml_view_mode') || 'grid',
        setViewMode(mode) {
            this.viewMode = mode;
            localStorage.setItem('drophtml_view_mode', mode);
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
        <div class="p-10 border-2 border-dashed border-indigo-500 rounded-3xl bg-zinc-900/90 text-white flex flex-col items-center gap-4 max-w-md text-center shadow-2xl">
            <div class="size-16 rounded-2xl bg-indigo-500/20 text-indigo-400 flex items-center justify-center animate-bounce">
                <flux:icon.cloud-arrow-up class="size-8" />
            </div>
            <div>
                <div class="text-xl font-bold text-white">Drop to Deploy Site</div>
                <p class="text-sm text-zinc-400 mt-1">Single HTML document or complete ZIP archive</p>
            </div>
        </div>
    </div>

    <!-- Welcome Hero Banner -->
    <div class="relative overflow-hidden rounded-3xl border border-zinc-200/80 dark:border-zinc-800/80 bg-gradient-to-br from-white via-zinc-50 to-indigo-50/40 dark:from-[#0d0d12] dark:via-[#09090c] dark:to-[#131124] p-6 sm:p-8 shadow-xs">
        <div class="absolute -top-20 right-0 w-80 h-80 bg-indigo-500/10 dark:bg-indigo-500/15 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -bottom-20 right-48 w-60 h-60 bg-cyan-500/10 dark:bg-cyan-500/15 rounded-full blur-3xl pointer-events-none"></div>

        <div class="relative z-10 flex flex-col lg:flex-row lg:items-center justify-between gap-6">
            <div class="space-y-2 max-w-2xl">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-indigo-50 dark:bg-indigo-950/50 border border-indigo-500/20 text-indigo-600 dark:text-indigo-400 text-xs font-semibold">
                    <span class="size-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span>Edge Cloud Operational</span>
                </div>
                <h1 class="text-2xl sm:text-3xl font-black tracking-tight text-zinc-900 dark:text-white">
                    Welcome back, {{ auth()->user()->name }}
                </h1>
                <p class="text-xs sm:text-sm text-zinc-600 dark:text-zinc-400 leading-relaxed">
                    Deploy static sites with automated SSL, manage static media with zero-bloat cloud asset storage, and inspect real-time traffic across global Edge CDN nodes.
                </p>
            </div>

            <!-- Quick Action Buttons in Banner -->
            <div class="flex flex-wrap items-center gap-2.5 shrink-0">
                <flux:modal.trigger name="upload-site-modal">
                    <flux:button variant="primary" size="base" icon="cloud-arrow-up" class="rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold shadow-md shadow-indigo-500/20 cursor-pointer">
                        Deploy New Site
                    </flux:button>
                </flux:modal.trigger>

                @if(auth()->user()->github_id)
                    <flux:modal.trigger name="github-deploy-modal">
                        <flux:button variant="ghost" size="base" class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white/80 dark:bg-zinc-800/80 hover:bg-zinc-100 dark:hover:bg-zinc-700 font-semibold cursor-pointer">
                            <svg class="size-4 mr-1.5 text-zinc-700 dark:text-zinc-300" fill="currentColor" viewBox="0 0 24 24"><path fill-rule="evenodd" d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.531 1.032 1.531 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0022 12.017C22 6.484 17.522 2 12 2z" clip-rule="evenodd" /></svg>
                            GitHub Sync
                        </flux:button>
                    </flux:modal.trigger>
                @endif

                <a href="{{ route('dashboard.media') }}" wire:navigate class="inline-flex items-center gap-2 px-3.5 py-2 text-xs font-semibold rounded-xl bg-cyan-50 dark:bg-cyan-950/40 border border-cyan-500/30 text-cyan-700 dark:text-cyan-300 hover:bg-cyan-100 dark:hover:bg-cyan-900/40 transition-colors cursor-pointer">
                    <flux:icon.photo class="size-3.5" />
                    <span>Media Cloud</span>
                </a>
            </div>
        </div>
    </div>

    <!-- 4 KPI Stat Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Stat 1: Total Sites -->
        <div class="glass-card rounded-2xl p-5 border border-zinc-200/80 dark:border-zinc-800 relative overflow-hidden group hover:border-indigo-500/40 transition-all">
            <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-indigo-500 to-purple-500"></div>
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Total Sites</span>
                    <div class="text-2xl sm:text-3xl font-black text-zinc-900 dark:text-white mt-1">{{ number_format($totalSites) }}</div>
                </div>
                <div class="p-3 rounded-xl bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 border border-indigo-500/20">
                    <flux:icon.globe-alt class="size-5" />
                </div>
            </div>
            <div class="mt-3 text-xs text-zinc-500 dark:text-zinc-400 flex items-center gap-1.5">
                <span class="size-2 rounded-full bg-emerald-500 animate-pulse"></span>
                <span>Active Edge Deployments</span>
            </div>
        </div>

        <!-- Stat 2: Total Page Views -->
        <div class="glass-card rounded-2xl p-5 border border-zinc-200/80 dark:border-zinc-800 relative overflow-hidden group hover:border-purple-500/40 transition-all">
            <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-purple-500 to-pink-500"></div>
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Total Page Views</span>
                    <div class="text-2xl sm:text-3xl font-black text-purple-600 dark:text-purple-400 mt-1">{{ number_format($totalViews) }}</div>
                </div>
                <div class="p-3 rounded-xl bg-purple-500/10 text-purple-600 dark:text-purple-400 border border-purple-500/20">
                    <flux:icon.eye class="size-5" />
                </div>
            </div>
            <div class="mt-3 text-xs text-zinc-500 dark:text-zinc-400 flex items-center gap-1.5">
                <flux:icon.chart-bar class="size-3.5 text-zinc-400" />
                <span>Across all deployed sites</span>
            </div>
        </div>

        <!-- Stat 3: Connected Domains -->
        <div class="glass-card rounded-2xl p-5 border border-zinc-200/80 dark:border-zinc-800 relative overflow-hidden group hover:border-emerald-500/40 transition-all">
            <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-emerald-500 to-teal-500"></div>
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Custom Domains</span>
                    <div class="text-2xl sm:text-3xl font-black text-emerald-600 dark:text-emerald-400 mt-1">{{ number_format($totalDomains) }}</div>
                </div>
                <div class="p-3 rounded-xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20">
                    <flux:icon.link class="size-5" />
                </div>
            </div>
            <div class="mt-3 text-xs text-zinc-500 dark:text-zinc-400 flex items-center gap-1.5">
                <flux:icon.shield-check class="size-3.5 text-emerald-500" />
                <span>Automatic SSL provisioned</span>
            </div>
        </div>

        <!-- Stat 4: Storage & Quota -->
        <a href="{{ route('settings.storage') }}" wire:navigate class="glass-card rounded-2xl p-5 border border-zinc-200/80 dark:border-zinc-800 relative overflow-hidden group hover:border-cyan-500/40 transition-all block cursor-pointer">
            <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-cyan-500 to-indigo-500"></div>
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Storage &amp; Quota</span>
                    <div class="text-2xl sm:text-3xl font-black text-cyan-600 dark:text-cyan-400 mt-1 font-mono">{{ $storageStats['formatted']['total_used'] }}</div>
                </div>
                <div class="p-3 rounded-xl bg-cyan-500/10 text-cyan-600 dark:text-cyan-400 border border-cyan-500/20 group-hover:scale-105 transition-transform">
                    <flux:icon.circle-stack class="size-5" />
                </div>
            </div>
            <div class="mt-3 text-xs text-zinc-500 dark:text-zinc-400 flex items-center justify-between">
                <span class="flex items-center gap-1.5 truncate">
                    <span>of {{ $storageStats['formatted']['quota'] }} ({{ $storageStats['used_percentage'] }}%)</span>
                </span>
                <span class="text-indigo-600 dark:text-indigo-400 group-hover:underline font-semibold flex items-center gap-1">
                    <span>Manage</span>
                    <span>&rarr;</span>
                </span>
            </div>
        </a>
    </div>

    <!-- Main Container: Search, Filters & Content -->
    <div class="glass-card rounded-3xl border border-zinc-200/80 dark:border-zinc-800 p-5 sm:p-7 shadow-xs">
        <!-- Filter and Search Header -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
            <!-- Search & Filter Chips -->
            <div class="flex flex-1 flex-wrap items-center gap-3">
                <div class="relative w-full sm:w-80">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-zinc-400">
                        <flux:icon.magnifying-glass class="size-4" />
                    </div>
                    <input 
                        wire:model.live.debounce.250ms="search" 
                        type="text" 
                        placeholder="Search sites, slugs, domains..." 
                        class="w-full pl-9 pr-8 py-2 text-xs sm:text-sm rounded-xl bg-zinc-50 dark:bg-zinc-800/70 border border-zinc-200/80 dark:border-zinc-700/80 text-zinc-900 dark:text-zinc-100 placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-all"
                    />
                    @if($search)
                        <button wire:click="$set('search', '')" class="absolute inset-y-0 right-0 pr-2.5 flex items-center text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200 cursor-pointer">
                            <flux:icon.x-mark class="size-4" />
                        </button>
                    @endif
                </div>

                <!-- Filter Pills -->
                <div class="inline-flex rounded-xl p-1 bg-zinc-100 dark:bg-zinc-800/70 border border-zinc-200/80 dark:border-zinc-700/80 text-xs">
                    <button wire:click="setFilter('all')" class="px-3 py-1 font-semibold rounded-lg transition-all cursor-pointer {{ $statusFilter === 'all' ? 'bg-white dark:bg-zinc-900 text-indigo-600 dark:text-indigo-400 shadow-2xs' : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white' }}">All ({{ $totalSites }})</button>
                    <button wire:click="setFilter('active')" class="px-3 py-1 font-semibold rounded-lg transition-all cursor-pointer {{ $statusFilter === 'active' ? 'bg-white dark:bg-zinc-900 text-emerald-600 dark:text-emerald-400 shadow-2xs' : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white' }}">Active</button>
                    <button wire:click="setFilter('permanent')" class="px-3 py-1 font-semibold rounded-lg transition-all cursor-pointer {{ $statusFilter === 'permanent' ? 'bg-white dark:bg-zinc-900 text-purple-600 dark:text-purple-400 shadow-2xs' : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white' }}">Permanent</button>
                    <button wire:click="setFilter('expiring')" class="px-3 py-1 font-semibold rounded-lg transition-all cursor-pointer {{ $statusFilter === 'expiring' ? 'bg-white dark:bg-zinc-900 text-amber-600 dark:text-amber-400 shadow-2xs' : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white' }}">
                        Expiring
                        @if($expiringCount > 0)
                            <span class="ml-1 px-1.5 py-0.2 rounded-full bg-amber-500 text-white text-[10px] font-bold">{{ $expiringCount }}</span>
                        @endif
                    </button>
                </div>
            </div>

            <!-- View Switcher & Action Buttons -->
            <div class="flex items-center gap-2.5 shrink-0">
                <!-- Grid / Table View Switcher -->
                <div class="hidden sm:inline-flex rounded-xl p-1 bg-zinc-100 dark:bg-zinc-800/70 border border-zinc-200/80 dark:border-zinc-700/80">
                    <button type="button" @click="setViewMode('grid')" :class="viewMode === 'grid' ? 'bg-white dark:bg-zinc-900 text-indigo-600 dark:text-indigo-400 shadow-2xs' : 'text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-200'" class="p-1.5 rounded-lg transition-colors cursor-pointer" title="Grid View">
                        <flux:icon.squares-2x2 class="size-4" />
                    </button>
                    <button type="button" @click="setViewMode('table')" :class="viewMode === 'table' ? 'bg-white dark:bg-zinc-900 text-indigo-600 dark:text-indigo-400 shadow-2xs' : 'text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-200'" class="p-1.5 rounded-lg transition-colors cursor-pointer" title="Dense Table View">
                        <flux:icon.bars-3-bottom-left class="size-4" />
                    </button>
                </div>

                <flux:modal.trigger name="upload-site-modal">
                    <flux:button variant="primary" size="sm" icon="plus" class="rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-semibold shadow-xs cursor-pointer">
                        Deploy Site
                    </flux:button>
                </flux:modal.trigger>
            </div>
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

        <!-- Empty State -->
        @if($sites->isEmpty())
            <div class="py-16 flex flex-col items-center justify-center text-center">
                <div class="size-16 rounded-2xl bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 flex items-center justify-center mb-4 border border-indigo-500/20 shadow-sm">
                    <flux:icon.cloud-arrow-up class="size-8" />
                </div>
                <h3 class="text-lg font-bold text-zinc-900 dark:text-zinc-100">
                    {{ $search ? 'No matching sites found' : 'No static sites deployed yet' }}
                </h3>
                <p class="text-xs sm:text-sm text-zinc-500 dark:text-zinc-400 max-w-sm mt-1 mb-6 leading-relaxed">
                    {{ $search ? "We couldn't find any sites matching '{$search}'. Check for typos or reset your filter." : 'Drag and drop an HTML or ZIP file anywhere on this screen, or launch an upload modal.' }}
                </p>
                <div class="flex items-center gap-3">
                    @if($search)
                        <flux:button wire:click="$set('search', '')" variant="ghost" size="sm" class="rounded-xl">Clear Search</flux:button>
                    @endif
                    <flux:modal.trigger name="upload-site-modal">
                        <flux:button variant="primary" size="sm" icon="plus" class="rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold shadow-sm">Deploy Your First Site</flux:button>
                    </flux:modal.trigger>
                </div>
            </div>
        @else
            <!-- 1. Card Grid View -->
            <div x-show="viewMode === 'grid'" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
                @foreach ($sites as $site)
                    <div class="group rounded-2xl border border-zinc-200/80 dark:border-zinc-800/80 bg-zinc-50/60 dark:bg-zinc-900/60 p-5 transition-all hover:shadow-xl hover:border-indigo-500/40 dark:hover:border-indigo-500/40 flex flex-col justify-between backdrop-blur-xs relative overflow-hidden">
                        <div>
                            <!-- Header Row -->
                            <div class="flex items-start justify-between gap-3 mb-3">
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center gap-2">
                                        <div class="size-8 rounded-xl bg-gradient-to-tr from-indigo-600/20 to-purple-600/20 text-indigo-600 dark:text-indigo-400 flex items-center justify-center font-black text-xs shrink-0 border border-indigo-500/30">
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
                                        <a href="/s/{{ $site->slug }}" target="_blank" class="font-mono text-indigo-600 dark:text-indigo-400 hover:underline truncate max-w-[190px]">
                                            /s/{{ $site->slug }}
                                        </a>
                                        <button 
                                            type="button" 
                                            @click="navigator.clipboard.writeText('{{ url('/s/' . $site->slug) }}'); Flux.toast('URL copied to clipboard!')" 
                                            class="text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-200 p-0.5 rounded transition-colors cursor-pointer" 
                                            title="Copy live URL"
                                        >
                                            <flux:icon.clipboard class="size-3.5" />
                                        </button>
                                    </div>
                                </div>

                                <div class="flex items-center gap-1 shrink-0">
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold {{ $site->status === 'active' ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20' : 'bg-red-500/10 text-red-600 dark:text-red-400 border border-red-500/20' }}">
                                        <span class="size-1.5 rounded-full {{ $site->status === 'active' ? 'bg-emerald-500 animate-pulse' : 'bg-red-500' }}"></span>
                                        {{ ucfirst($site->status) }}
                                    </span>

                                    <flux:dropdown>
                                        <flux:button variant="ghost" size="xs" icon="ellipsis-vertical" class="rounded-lg cursor-pointer" />
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
                                            <flux:menu.item icon="trash" wire:click="deleteSite('{{ $site->id }}')" wire:confirm="Delete '{{ $site->original_name ?: $site->slug }}'? This action cannot be undone." class="text-rose-600">Delete Site</flux:menu.item>
                                        </flux:menu>
                                    </flux:dropdown>
                                </div>
                            </div>

                            <!-- Custom Domain Tag (if present) -->
                            @if($site->domains->count() > 0)
                                <div class="mb-3 flex items-center gap-1.5 px-2.5 py-1 rounded-xl bg-zinc-100/90 dark:bg-zinc-800/80 border border-zinc-200/60 dark:border-zinc-700/60 text-xs">
                                    <flux:icon.globe-alt class="size-3.5 text-indigo-500 shrink-0" />
                                    <a href="http://{{ $site->domains->first()->domain }}" target="_blank" class="truncate font-mono font-medium text-zinc-700 dark:text-zinc-300 hover:underline">
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
                            <div class="text-xs mb-3">
                                @if($site->is_permanent)
                                    <div class="flex items-center gap-1.5 text-purple-600 dark:text-purple-400 font-semibold">
                                        <flux:icon.sparkles class="size-3.5" />
                                        <span>Permanent (Never expires)</span>
                                    </div>
                                @elseif($site->expires_at)
                                    <div class="flex items-center justify-between text-zinc-500 dark:text-zinc-400">
                                        <span>Expires in:</span>
                                        <span class="font-semibold {{ $site->expires_at->isPast() ? 'text-red-500' : ($site->expires_at->diffInHours() < 24 ? 'text-amber-500' : 'text-zinc-700 dark:text-zinc-300') }}">
                                            {{ $site->expires_at->isPast() ? 'Expired' : $site->expires_at->diffForHumans(null, true) }}
                                        </span>
                                    </div>
                                @else
                                    <span class="text-zinc-400">—</span>
                                @endif
                            </div>
                        </div>

                        <!-- Card Action Buttons -->
                        <div class="flex items-center justify-between gap-2 pt-3 border-t border-zinc-200/60 dark:border-zinc-800/60">
                            <a href="/s/{{ $site->slug }}" target="_blank" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-xl bg-white dark:bg-zinc-800 hover:bg-zinc-100 dark:hover:bg-zinc-700 text-zinc-800 dark:text-zinc-200 border border-zinc-200/80 dark:border-zinc-700 transition-colors shadow-2xs">
                                <flux:icon.arrow-top-right-on-square class="size-3.5" />
                                Preview
                            </a>
                            <div class="flex items-center gap-1">
                                <flux:button :href="route('dashboard.sites.edit', $site->id)" wire:navigate variant="ghost" size="xs" icon="pencil-square" title="Code Studio" class="rounded-lg" />
                                <flux:button :href="route('dashboard.sites.media', $site->id)" wire:navigate variant="ghost" size="xs" icon="photo" title="Media Assets" class="rounded-lg" />
                                <flux:button :href="route('dashboard.sites.stats', $site->id)" wire:navigate variant="ghost" size="xs" icon="chart-bar" title="Analytics" class="rounded-lg" />
                                <flux:button :href="route('dashboard.sites.domains', $site->id)" wire:navigate variant="ghost" size="xs" icon="globe-alt" title="Domains" class="rounded-lg" />
                                <flux:button :href="route('dashboard.sites.settings', $site->id)" wire:navigate variant="ghost" size="xs" icon="cog-6-tooth" title="Settings" class="rounded-lg" />
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- 2. Dense Table View -->
            <div x-show="viewMode === 'table'" class="overflow-x-auto rounded-2xl border border-zinc-200/80 dark:border-zinc-800">
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>Name & Slug</flux:table.column>
                        <flux:table.column>Views</flux:table.column>
                        <flux:table.column>Expiry Status</flux:table.column>
                        <flux:table.column>Security</flux:table.column>
                        <flux:table.column>Status</flux:table.column>
                        <flux:table.column class="text-right">Actions</flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @foreach ($sites as $site)
                            <flux:table.row class="hover:bg-zinc-50/50 dark:hover:bg-zinc-800/40 transition-colors">
                                <flux:table.cell>
                                    <div class="font-semibold flex items-center gap-2 text-zinc-900 dark:text-zinc-100">
                                        {{ $site->original_name ?: 'Unnamed Site' }}
                                        @if($site->github_repo_full_name)
                                            <flux:badge size="xs" color="zinc">GitHub</flux:badge>
                                        @endif
                                    </div>
                                    <div class="text-xs text-zinc-500 flex items-center gap-2 mt-0.5">
                                        <a href="/s/{{ $site->slug }}" target="_blank" class="hover:underline text-indigo-500 dark:text-indigo-400 font-mono">/s/{{ $site->slug }}</a>
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
                                        <flux:badge size="sm" color="purple">Permanent</flux:badge>
                                    @elseif($site->expires_at)
                                        <span class="text-xs font-semibold {{ $site->expires_at->isPast() ? 'text-rose-500' : ($site->expires_at->diffInHours() < 24 ? 'text-amber-500' : 'text-zinc-600 dark:text-zinc-400') }}">
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
                                        <flux:button variant="ghost" size="xs" icon="clipboard" @click="navigator.clipboard.writeText('{{ url('/s/' . $site->slug) }}'); Flux.toast('URL copied!')" title="Copy URL" />
                                        <flux:button :href="route('dashboard.sites.edit', $site->id)" wire:navigate variant="ghost" size="xs" icon="pencil-square" title="Code Studio" />
                                        <flux:button :href="route('dashboard.sites.stats', $site->id)" wire:navigate variant="ghost" size="xs" icon="chart-bar" title="Analytics" />
                                        <flux:dropdown>
                                            <flux:button variant="ghost" size="xs" icon="ellipsis-horizontal" />
                                            <flux:menu>
                                                <flux:menu.item icon="arrow-top-right-on-square" href="/s/{{ $site->slug }}" target="_blank">View Live</flux:menu.item>
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
                                                <flux:menu.item icon="trash" wire:click="deleteSite('{{ $site->id }}')" wire:confirm="Are you sure you want to delete this site?" class="text-rose-600">Delete</flux:menu.item>
                                            </flux:menu>
                                        </flux:dropdown>
                                    </div>
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            </div>
        @endif
    </div>
</div>