<?php

use App\Models\MediaAsset;
use App\Models\Site;
use App\Services\ActivityLogService;
use App\Services\MediaStorageService;
use Livewire\Component;
use Livewire\WithFileUploads;

new class extends Component
{
    use WithFileUploads;

    public $siteId = null;
    public $site = null;
    public $selectedSiteId = null;

    public $uploadFiles = [];
    public $storageDestination = 'asset_storage'; // 'asset_storage' (Cloud Asset Storage) or 'code' (Site repository)
    public $customSubdir = '';

    public $search = '';
    public $typeFilter = 'all'; // all, image, video, document, audio
    public $driverFilter = 'all'; // all, asset_storage, code
    public $viewMode = 'grid'; // grid, list

    public $previewAssetId = null;
    public $previewAsset = null;

    public function mount(?string $siteId = null)
    {
        $this->siteId = $siteId;
        if ($this->siteId) {
            $this->site = auth()->user()->sites()->findOrFail($this->siteId);
            $this->selectedSiteId = $this->siteId;
        }
    }

    public function setViewMode($mode)
    {
        $this->viewMode = in_array($mode, ['grid', 'list'], true) ? $mode : 'grid';
    }

    public function setTypeFilter($type)
    {
        $this->typeFilter = in_array($type, ['all', 'image', 'video', 'document', 'audio'], true) ? $type : 'all';
    }

    public function setDriverFilter($driver)
    {
        $this->driverFilter = in_array($driver, ['all', 'asset_storage', 'code'], true) ? $driver : 'all';
    }

    public function uploadMedia(MediaStorageService $mediaStorageService)
    {
        $this->validate([
            'uploadFiles' => 'required|array|min:1',
            'uploadFiles.*' => 'file|max:102400', // 100MB max per file
            'storageDestination' => 'required|in:asset_storage,code',
            'selectedSiteId' => 'nullable|exists:sites,id',
            'customSubdir' => 'nullable|string|max:60',
        ]);

        $site = null;
        if ($this->selectedSiteId) {
            $site = auth()->user()->sites()->find($this->selectedSiteId);
        } elseif ($this->site) {
            $site = $this->site;
        }

        if ($this->storageDestination === 'code' && ! $site) {
            Flux::toast(variant: 'danger', text: 'Please choose a site to store files in the code repository.');

            return;
        }

        $count = 0;
        foreach ($this->uploadFiles as $file) {
            try {
                $mediaStorageService->store(
                    $file,
                    auth()->user(),
                    $site,
                    $this->storageDestination,
                    $this->customSubdir
                );
                $count++;
            } catch (\Exception $e) {
                Flux::toast(variant: 'danger', text: "Error uploading {$file->getClientOriginalName()}: {$e->getMessage()}");
            }
        }

        $this->uploadFiles = [];
        $targetLabel = $this->storageDestination === 'asset_storage' ? 'Cloud Asset Storage' : 'Site Code';
        Flux::toast("Successfully uploaded {$count} file(s) to {$targetLabel}!");
        Flux::modal('upload-media-modal')->close();
    }

    public function deleteAsset(string $id, MediaStorageService $mediaStorageService)
    {
        $asset = auth()->user()->mediaAssets()->findOrFail($id);
        $name = $asset->original_name;
        $mediaStorageService->delete($asset, auth()->user());

        if ($this->previewAssetId === $id) {
            $this->previewAssetId = null;
            $this->previewAsset = null;
            Flux::modal('preview-media-modal')->close();
        }

        Flux::toast("Media '{$name}' deleted.");
    }

    public function migrateAsset(string $id, string $targetDriver, ?string $targetSiteId, MediaStorageService $mediaStorageService)
    {
        $asset = auth()->user()->mediaAssets()->findOrFail($id);
        $targetSite = null;

        if ($targetSiteId) {
            $targetSite = auth()->user()->sites()->find($targetSiteId);
        } elseif ($this->site) {
            $targetSite = $this->site;
        } elseif ($asset->site) {
            $targetSite = $asset->site;
        }

        if ($targetDriver === 'code' && ! $targetSite) {
            Flux::toast(variant: 'danger', text: 'Target site is required to migrate asset to Code.');

            return;
        }

        try {
            $mediaStorageService->migrate($asset, $targetDriver, $targetSite, auth()->user());
            $targetName = $targetDriver === 'asset_storage' ? 'Cloud Asset Storage' : 'Site Code';
            Flux::toast("Asset migrated to {$targetName} successfully.");
        } catch (\Exception $e) {
            Flux::toast(variant: 'danger', text: "Migration failed: {$e->getMessage()}");
        }
    }

    public function copyAssetToCode(string $id, string $targetSiteId, MediaStorageService $mediaStorageService)
    {
        $asset = auth()->user()->mediaAssets()->findOrFail($id);
        $site = auth()->user()->sites()->findOrFail($targetSiteId);

        try {
            $relPath = $mediaStorageService->copyToCode($asset, $site);
            Flux::toast("Copied to {$site->slug}/{$relPath}");
        } catch (\Exception $e) {
            Flux::toast(variant: 'danger', text: "Copy failed: {$e->getMessage()}");
        }
    }

    public function showPreview(string $id)
    {
        $this->previewAssetId = $id;
        $this->previewAsset = auth()->user()->mediaAssets()->findOrFail($id);
        Flux::modal('preview-media-modal')->show();
    }

    public function render(MediaStorageService $mediaStorageService)
    {
        $user = auth()->user();
        $userSites = $user->sites()->orderBy('original_name')->get(['id', 'slug', 'original_name']);

        $stats = $mediaStorageService->getStorageStats($user, $this->site);

        $query = $user->mediaAssets()->with('site')->latest();

        if ($this->siteId) {
            $query->where('site_id', $this->siteId);
        }

        if ($this->typeFilter !== 'all') {
            $query->where('file_type', $this->typeFilter);
        }

        if ($this->driverFilter !== 'all') {
            $query->where('storage_driver', $this->driverFilter);
        }

        if (! empty($this->search)) {
            $term = trim($this->search);
            $query->where(function ($q) use ($term) {
                $q->where('original_name', 'like', "%{$term}%")
                  ->orWhere('path', 'like', "%{$term}%");
            });
        }

        $assets = $query->paginate(24);

        return view('components.dashboard.⚡media-manager', [
            'assets' => $assets,
            'stats' => $stats,
            'userSites' => $userSites,
        ]);
    }
};
?>

<div class="space-y-6">
    @if($site)
        <x-site-subnav :site="$site" current="media" />
    @else
        <!-- Header & Breadcrumbs for Global Media Storage -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <flux:breadcrumbs>
                    <flux:breadcrumbs.item :href="route('dashboard')" wire:navigate>Dashboard</flux:breadcrumbs.item>
                    <flux:breadcrumbs.item>Static Media Hosting</flux:breadcrumbs.item>
                </flux:breadcrumbs>
                <div class="flex items-center gap-3 mt-1.5 flex-wrap">
                    <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100 flex items-center gap-2.5">
                        <flux:icon.photo class="size-6 text-accent" />
                        <span>Static Media Hosting</span>
                    </h1>
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-accent/[0.07] text-accent dark:text-brand-300 border border-accent/20">
                        <span class="size-1.5 rounded-full bg-accent animate-pulse"></span>
                        {{ $stats['provider'] }}
                    </span>
                </div>
                <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1 max-w-2xl">
                    High-performance static object storage with global edge distribution. Store images, videos, and PDFs with zero code bundle bloat, or toggle placement directly into your site repository.
                </p>
            </div>

            <div class="flex items-center gap-2">
                <flux:modal.trigger name="upload-media-modal">
                    <flux:button variant="primary" icon="arrow-up-tray" class="rounded-xl shadow-xs cursor-pointer">
                        Upload Media
                    </flux:button>
                </flux:modal.trigger>
            </div>
        </div>
    @endif

    <!-- Storage Metric Cards -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3.5">
        <div class="glass-card rounded-2xl p-4 flex items-center gap-3.5">
            <div class="size-10 rounded-xl bg-accent/[0.08] dark:bg-accent/15 text-accent dark:text-brand-300 flex items-center justify-center shrink-0">
                <flux:icon.folder class="size-5" />
            </div>
            <div>
                <p class="text-[11px] font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Total Hosted</p>
                <div class="flex items-baseline gap-1.5">
                    <span class="text-lg font-bold tracking-tight text-zinc-900 dark:text-zinc-100">{{ $stats['total_count'] }}</span>
                    <span class="text-xs font-mono text-zinc-400">files</span>
                </div>
            </div>
        </div>

        <div class="glass-card rounded-2xl p-4 flex items-center gap-3.5">
            <div class="size-10 rounded-xl bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-300 flex items-center justify-center shrink-0">
                <flux:icon.cloud-arrow-up class="size-5" />
            </div>
            <div>
                <p class="text-[11px] font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Storage Used</p>
                <div class="flex items-baseline gap-1.5">
                    @php
                        $bytes = $stats['total_bytes'];
                        $formatted = $bytes >= 1073741824 ? number_format($bytes / 1073741824, 2).' GB' : ($bytes >= 1048576 ? number_format($bytes / 1048576, 1).' MB' : number_format($bytes / 1024, 0).' KB');
                    @endphp
                    <span class="text-lg font-bold tracking-tight text-zinc-900 dark:text-zinc-100">{{ $formatted }}</span>
                </div>
            </div>
        </div>

        <div class="glass-card rounded-2xl p-4 flex items-center gap-3.5">
            <div class="size-10 rounded-xl bg-cyan-50 dark:bg-cyan-950/40 text-cyan-600 dark:text-cyan-400 flex items-center justify-center shrink-0">
                <flux:icon.sparkles class="size-5" />
            </div>
            <div>
                <p class="text-[11px] font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Cloud Asset Storage</p>
                <div class="flex items-baseline gap-1.5">
                    <span class="text-lg font-bold tracking-tight text-zinc-900 dark:text-zinc-100">{{ $stats['cloud_count'] }}</span>
                    <span class="text-xs text-zinc-400 font-mono">in cloud</span>
                </div>
            </div>
        </div>

        <div class="glass-card rounded-2xl p-4 flex items-center gap-3.5">
            <div class="size-10 rounded-xl bg-zinc-800 dark:bg-zinc-700 text-white flex items-center justify-center shrink-0">
                <flux:icon.code-bracket class="size-5" />
            </div>
            <div>
                <p class="text-[11px] font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">In Code</p>
                <div class="flex items-baseline gap-1.5">
                    <span class="text-lg font-bold tracking-tight text-zinc-900 dark:text-zinc-100">{{ $stats['code_count'] }}</span>
                    <span class="text-xs text-zinc-400 font-mono">in bundles</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter & Toolbar Bar -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 pb-1">
        <!-- Type Pill Tabs -->
        <div class="flex items-center gap-1.5 overflow-x-auto pb-1 sm:pb-0">
            <button 
                type="button" 
                wire:click="setTypeFilter('all')" 
                class="px-3 py-1.5 rounded-xl text-xs font-semibold cursor-pointer transition-all {{ $typeFilter === 'all' ? 'bg-accent text-white shadow-xs dark:bg-indigo-500' : 'bg-zinc-100 dark:bg-zinc-800/80 text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100' }}"
            >
                All ({{ $stats['total_count'] }})
            </button>
            <button 
                type="button" 
                wire:click="setTypeFilter('image')" 
                class="px-3 py-1.5 rounded-xl text-xs font-semibold cursor-pointer transition-all inline-flex items-center gap-1.5 {{ $typeFilter === 'image' ? 'bg-accent text-white shadow-xs dark:bg-indigo-500' : 'bg-zinc-100 dark:bg-zinc-800/80 text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100' }}"
            >
                <flux:icon.photo class="size-3.5" />
                Images ({{ $stats['images_count'] }})
            </button>
            <button 
                type="button" 
                wire:click="setTypeFilter('video')" 
                class="px-3 py-1.5 rounded-xl text-xs font-semibold cursor-pointer transition-all inline-flex items-center gap-1.5 {{ $typeFilter === 'video' ? 'bg-accent text-white shadow-xs dark:bg-indigo-500' : 'bg-zinc-100 dark:bg-zinc-800/80 text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100' }}"
            >
                <flux:icon.film class="size-3.5" />
                Videos ({{ $stats['videos_count'] }})
            </button>
            <button 
                type="button" 
                wire:click="setTypeFilter('document')" 
                class="px-3 py-1.5 rounded-xl text-xs font-semibold cursor-pointer transition-all inline-flex items-center gap-1.5 {{ $typeFilter === 'document' ? 'bg-accent text-white shadow-xs dark:bg-indigo-500' : 'bg-zinc-100 dark:bg-zinc-800/80 text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100' }}"
            >
                <flux:icon.document-text class="size-3.5" />
                PDFs & Docs ({{ $stats['docs_count'] }})
            </button>
            <button 
                type="button" 
                wire:click="setTypeFilter('audio')" 
                class="px-3 py-1.5 rounded-xl text-xs font-semibold cursor-pointer transition-all inline-flex items-center gap-1.5 {{ $typeFilter === 'audio' ? 'bg-accent text-white shadow-xs dark:bg-indigo-500' : 'bg-zinc-100 dark:bg-zinc-800/80 text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100' }}"
            >
                <flux:icon.musical-note class="size-3.5" />
                Audio ({{ $stats['audio_count'] }})
            </button>
        </div>

        <!-- Search & Storage Filters -->
        <div class="flex items-center gap-2">
            <!-- Search -->
            <div class="relative w-48 sm:w-60">
                <flux:icon.magnifying-glass class="size-3.5 text-zinc-400 absolute left-3 top-2.5 pointer-events-none" />
                <input 
                    type="text" 
                    wire:model.live.debounce.150ms="search" 
                    placeholder="Search media..." 
                    class="w-full pl-8 pr-7 py-1.5 text-xs rounded-xl bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 text-zinc-800 dark:text-zinc-200 placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-accent/40 transition-colors"
                />
            </div>

            <!-- Storage Driver Selector Filter -->
            <select 
                wire:model.live="driverFilter" 
                class="py-1.5 px-2.5 text-xs rounded-xl bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 text-zinc-800 dark:text-zinc-200 focus:outline-none focus:ring-2 focus:ring-accent/40"
            >
                <option value="all">All Storage</option>
                <option value="asset_storage">Cloud Asset Storage</option>
                <option value="code">In Code</option>
            </select>

            <!-- View Toggle -->
            <div class="inline-flex rounded-xl p-0.5 bg-zinc-100 dark:bg-zinc-800/80 border border-zinc-200 dark:border-zinc-700 text-xs">
                <button 
                    wire:click="setViewMode('grid')" 
                    class="p-1.5 rounded-lg transition-colors cursor-pointer {{ $viewMode === 'grid' ? 'bg-white dark:bg-zinc-800 text-accent dark:text-brand-300 shadow-xs' : 'text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300' }}" 
                    title="Grid View"
                >
                    <flux:icon.squares-2x2 class="size-3.5" />
                </button>
                <button 
                    wire:click="setViewMode('list')" 
                    class="p-1.5 rounded-lg transition-colors cursor-pointer {{ $viewMode === 'list' ? 'bg-white dark:bg-zinc-800 text-accent dark:text-brand-300 shadow-xs' : 'text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300' }}" 
                    title="List View"
                >
                    <flux:icon.bars-3 class="size-3.5" />
                </button>
            </div>
        </div>
    </div>

    <!-- Media Assets Content -->
    @if($assets->isEmpty())
        <div class="glass-card rounded-2xl p-16 text-center flex flex-col items-center justify-center">
            <div class="size-16 rounded-2xl bg-accent/[0.07] dark:bg-accent/15 text-accent dark:text-brand-300 flex items-center justify-center mb-4 ring-1 ring-accent/20">
                <flux:icon.photo class="size-8" />
            </div>
            <h3 class="text-base font-bold tracking-tight text-zinc-800 dark:text-zinc-200">No media assets found</h3>
            <p class="text-xs text-zinc-500 dark:text-zinc-400 max-w-sm mt-1.5 mb-5">
                Upload your images, videos, audio, or PDFs to fast Cloud Asset Storage or bundle them directly in your site's code.
            </p>
            <flux:modal.trigger name="upload-media-modal">
                <flux:button variant="primary" icon="arrow-up-tray" class="rounded-xl shadow-xs cursor-pointer">
                    Upload Your First Media File
                </flux:button>
            </flux:modal.trigger>
        </div>
    @elseif($viewMode === 'grid')
        <!-- Grid View -->
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4">
            @foreach($assets as $asset)
                <div class="group relative rounded-2xl overflow-hidden border border-zinc-200/80 dark:border-zinc-800 bg-white dark:bg-zinc-900/60 shadow-xs hover:shadow-md hover:border-zinc-300 dark:hover:border-zinc-700 transition-all duration-200 flex flex-col">
                    <!-- Thumbnail / Preview Area -->
                    <div 
                        wire:click="showPreview('{{ $asset->id }}')" 
                        class="aspect-video w-full bg-zinc-100 dark:bg-zinc-800/60 relative overflow-hidden flex items-center justify-center cursor-pointer select-none"
                    >
                        @if($asset->isImage())
                            <img src="{{ $asset->resolved_url }}" alt="{{ $asset->original_name }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-200" loading="lazy" />
                        @elseif($asset->isVideo())
                            <div class="size-10 rounded-full bg-black/50 text-white flex items-center justify-center backdrop-blur-xs">
                                <flux:icon.play class="size-5 ml-0.5" />
                            </div>
                        @elseif($asset->isPdf())
                            <div class="flex flex-col items-center gap-1 text-zinc-500 dark:text-zinc-400">
                                <flux:icon.document-text class="size-8" />
                                <span class="text-[9px] font-bold uppercase tracking-wider">PDF</span>
                            </div>
                        @elseif($asset->isAudio())
                            <div class="flex flex-col items-center gap-1 text-zinc-500 dark:text-zinc-400">
                                <flux:icon.musical-note class="size-8" />
                                <span class="text-[9px] font-bold uppercase tracking-wider">Audio</span>
                            </div>
                        @else
                            <flux:icon.document class="size-8 text-zinc-400" />
                        @endif

                        <!-- Storage Type Badge -->
                        <div class="absolute top-2 left-2">
                            @if($asset->storage_driver === 'asset_storage')
                                <span class="px-1.5 py-0.5 rounded-md text-[9px] font-bold tracking-wider uppercase bg-accent text-white shadow-xs">
                                    Cloud Asset
                                </span>
                            @else
                                <span class="px-1.5 py-0.5 rounded-md text-[9px] font-bold tracking-wider uppercase bg-zinc-800/90 dark:bg-zinc-700/90 text-white shadow-xs">
                                    In Code
                                </span>
                            @endif
                        </div>

                        <!-- File Size Badge -->
                        <div class="absolute bottom-2 right-2">
                            <span class="px-1.5 py-0.5 rounded-md text-[10px] font-mono font-medium bg-black/60 text-white backdrop-blur-xs">
                                {{ $asset->formatted_size }}
                            </span>
                        </div>
                    </div>

                    <!-- File Details & Action Menu -->
                    <div class="p-3 flex-1 flex flex-col justify-between">
                        <div class="min-w-0">
                            <p class="text-xs font-semibold text-zinc-900 dark:text-zinc-100 truncate" title="{{ $asset->original_name }}">
                                {{ $asset->original_name }}
                            </p>
                            @if($asset->site)
                                <p class="text-[10px] text-zinc-400 truncate flex items-center gap-1 mt-0.5">
                                    <flux:icon.globe-alt class="size-3 shrink-0" />
                                    <span>{{ $asset->site->original_name ?: $asset->site->slug }}</span>
                                </p>
                            @else
                                <p class="text-[10px] text-zinc-400 truncate mt-0.5">Global User Asset</p>
                            @endif
                        </div>

                        <div class="flex items-center justify-between pt-2.5 mt-2 border-t border-zinc-100 dark:border-zinc-800/80">
                            <!-- Copy CDN URL Button -->
                            <button 
                                type="button" 
                                x-data="{ copied: false }" 
                                @click="
                                    navigator.clipboard.writeText('{{ $asset->resolved_url }}');
                                    copied = true;
                                    setTimeout(() => copied = false, 1500);
                                "
                                class="inline-flex items-center gap-1 text-[11px] font-medium text-accent dark:text-brand-300 hover:underline cursor-pointer"
                            >
                                <flux:icon.link class="size-3" />
                                <span x-text="copied ? 'Copied!' : 'Copy URL'"></span>
                            </button>

                            <!-- Dropdown Actions -->
                            <flux:dropdown>
                                <button type="button" class="p-1 rounded-md text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-200 cursor-pointer">
                                    <flux:icon.ellipsis-vertical class="size-3.5" />
                                </button>
                                <flux:menu>
                                    <flux:menu.item icon="eye" wire:click="showPreview('{{ $asset->id }}')">Preview File</flux:menu.item>
                                    
                                    <flux:menu.item 
                                        icon="clipboard-document" 
                                        x-on:click="navigator.clipboard.writeText(`{{ addslashes($asset->html_tag) }}`); $flux.toast('HTML embed code copied to clipboard!')"
                                    >
                                        Copy HTML Tag
                                    </flux:menu.item>

                                    <flux:menu.item 
                                        icon="code-bracket" 
                                        x-on:click="navigator.clipboard.writeText(`{{ addslashes($asset->markdown_tag) }}`); $flux.toast('Markdown copied to clipboard!')"
                                    >
                                        Copy Markdown
                                    </flux:menu.item>

                                    <flux:menu.separator />

                                    @if($asset->storage_driver === 'code')
                                        <flux:menu.item 
                                            icon="cloud-arrow-up" 
                                            wire:click="migrateAsset('{{ $asset->id }}', 'asset_storage', null)"
                                        >
                                            Migrate to Cloud Asset Storage
                                        </flux:menu.item>
                                    @elseif($asset->storage_driver === 'asset_storage' && $asset->site_id)
                                        <flux:menu.item 
                                            icon="arrow-down-tray" 
                                            wire:click="migrateAsset('{{ $asset->id }}', 'code', '{{ $asset->site_id }}')"
                                        >
                                            Move to Site Code Bundle
                                        </flux:menu.item>
                                    @endif

                                    <flux:menu.item 
                                        icon="arrow-down-tray" 
                                        href="{{ $asset->resolved_url }}" 
                                        download="{{ $asset->original_name }}"
                                        target="_blank"
                                    >
                                        Download
                                    </flux:menu.item>

                                    <flux:menu.separator />

                                    <flux:menu.item 
                                        icon="trash" 
                                        wire:click="deleteAsset('{{ $asset->id }}')" 
                                        wire:confirm="Are you sure you want to delete '{{ $asset->original_name }}'? This cannot be undone." 
                                        class="text-red-600 dark:text-red-400"
                                    >
                                        Delete Asset
                                    </flux:menu.item>
                                </flux:menu>
                            </flux:dropdown>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <!-- List / Table View -->
        <div class="glass-card rounded-2xl overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-zinc-600 dark:text-zinc-400">
                    <thead class="bg-zinc-50 dark:bg-zinc-900/80 text-[11px] uppercase tracking-wider font-semibold text-zinc-500 border-b border-zinc-200/80 dark:border-zinc-800">
                        <tr>
                            <th class="px-4 py-3">Asset</th>
                            <th class="px-4 py-3">Storage Engine</th>
                            <th class="px-4 py-3">Site</th>
                            <th class="px-4 py-3">Size</th>
                            <th class="px-4 py-3">Uploaded</th>
                            <th class="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                        @foreach($assets as $asset)
                            <tr class="hover:bg-zinc-50/70 dark:hover:bg-zinc-800/40 transition-colors">
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <div class="size-9 rounded-lg bg-zinc-100 dark:bg-zinc-800 overflow-hidden flex items-center justify-center shrink-0">
                                            @if($asset->isImage())
                                                <img src="{{ $asset->resolved_url }}" alt="" class="size-full object-cover" />
                                            @elseif($asset->isVideo())
                                                <flux:icon.film class="size-4 text-cyan-600 dark:text-cyan-400" />
                                            @elseif($asset->isPdf())
                                                <flux:icon.document-text class="size-4 text-zinc-500 dark:text-zinc-400" />
                                            @else
                                                <flux:icon.document class="size-4 text-zinc-400" />
                                            @endif
                                        </div>
                                        <div class="min-w-0">
                                            <p class="font-semibold text-zinc-900 dark:text-zinc-100 truncate max-w-xs cursor-pointer hover:text-accent transition-colors" wire:click="showPreview('{{ $asset->id }}')">
                                                {{ $asset->original_name }}
                                            </p>
                                            <p class="text-[10px] text-zinc-400 font-mono">{{ $asset->mime_type }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    @if($asset->storage_driver === 'asset_storage')
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-accent/[0.07] text-accent dark:text-brand-300 border border-accent/20">
                                            <flux:icon.cloud-arrow-up class="size-3" />
                                            Cloud Asset
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-300 border border-zinc-200 dark:border-zinc-700">
                                            <flux:icon.code-bracket class="size-3" />
                                            In Code
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    @if($asset->site)
                                        <span class="text-xs text-zinc-800 dark:text-zinc-200">{{ $asset->site->original_name ?: $asset->site->slug }}</span>
                                    @else
                                        <span class="text-zinc-400 italic">Global</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 font-mono text-zinc-700 dark:text-zinc-300">
                                    {{ $asset->formatted_size }}
                                </td>
                                <td class="px-4 py-3">
                                    {{ $asset->created_at->diffForHumans() }}
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <button 
                                            type="button" 
                                            x-data="{ copied: false }" 
                                            @click="
                                                navigator.clipboard.writeText('{{ $asset->resolved_url }}');
                                                copied = true;
                                                setTimeout(() => copied = false, 1500);
                                            "
                                            class="p-1.5 rounded-lg text-zinc-400 hover:text-accent dark:hover:text-brand-300 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors"
                                            title="Copy URL"
                                        >
                                            <flux:icon.link class="size-3.5" />
                                        </button>

                                        <button 
                                            type="button" 
                                            wire:click="showPreview('{{ $asset->id }}')" 
                                            class="p-1.5 rounded-lg text-zinc-400 hover:text-zinc-800 dark:hover:text-zinc-100 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors"
                                            title="Preview"
                                        >
                                            <flux:icon.eye class="size-3.5" />
                                        </button>

                                        <button 
                                            type="button" 
                                            wire:click="deleteAsset('{{ $asset->id }}')" 
                                            wire:confirm="Delete '{{ $asset->original_name }}'?" 
                                            class="p-1.5 rounded-lg text-zinc-400 hover:text-red-600 dark:hover:text-red-400 hover:bg-red-50 dark:hover:bg-red-950/30 transition-colors"
                                            title="Delete"
                                        >
                                            <flux:icon.trash class="size-3.5" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <!-- Pagination -->
    <div class="mt-4">
        {{ $assets->links() }}
    </div>

    <!-- Upload Media Modal -->
    <flux:modal name="upload-media-modal" class="md:w-[540px]">
        <form wire:submit="uploadMedia" class="space-y-5">
            <div>
                <flux:heading size="lg">Upload Static Media</flux:heading>
                <flux:subheading>Upload images, videos, or PDFs powered by high-speed cloud object storage.</flux:subheading>
            </div>

            <!-- Storage Choice Toggle -->
            <div class="space-y-2">
                <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300">Choose Storage Destination</label>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <label class="relative flex flex-col p-3 rounded-xl border cursor-pointer transition-colors {{ $storageDestination === 'asset_storage' ? 'border-accent bg-accent/[0.04] dark:bg-accent/10 ring-1 ring-accent' : 'border-zinc-200 dark:border-zinc-800 hover:bg-zinc-50 dark:hover:bg-zinc-900' }}">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-xs font-bold text-zinc-900 dark:text-zinc-100 flex items-center gap-1.5">
                                <flux:icon.cloud-arrow-up class="size-4 text-cyan-600 dark:text-cyan-400" />
                                <span>Cloud Asset Storage</span>
                            </span>
                            <input type="radio" wire:model.live="storageDestination" value="asset_storage" class="text-accent" />
                        </div>
                        <p class="text-[11px] text-zinc-500 dark:text-zinc-400">Zero bundle bloat, global CDN, optimized for video streaming & high-res images.</p>
                    </label>

                    <label class="relative flex flex-col p-3 rounded-xl border cursor-pointer transition-colors {{ $storageDestination === 'code' ? 'border-accent bg-accent/[0.04] dark:bg-accent/10 ring-1 ring-accent' : 'border-zinc-200 dark:border-zinc-800 hover:bg-zinc-50 dark:hover:bg-zinc-900' }}">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-xs font-bold text-zinc-900 dark:text-zinc-100 flex items-center gap-1.5">
                                <flux:icon.code-bracket class="size-4 text-zinc-500 dark:text-zinc-400" />
                                <span>Site Code Bundle</span>
                            </span>
                            <input type="radio" wire:model.live="storageDestination" value="code" class="text-accent" />
                        </div>
                        <p class="text-[11px] text-zinc-500 dark:text-zinc-400">Stored directly in site files repository, included in ZIP backups and rollback snapshots.</p>
                    </label>
                </div>
            </div>

            <!-- Site Selection (required if code, optional if asset_storage) -->
            @if(!$site)
                <div>
                    <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1">
                        Link to Site {{ $storageDestination === 'code' ? '(Required)' : '(Optional)' }}
                    </label>
                    <select 
                        wire:model="selectedSiteId" 
                        class="w-full text-xs rounded-xl bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 py-2 px-3 text-zinc-800 dark:text-zinc-200 focus:outline-none focus:ring-2 focus:ring-accent/40"
                        {{ $storageDestination === 'code' ? 'required' : '' }}
                    >
                        <option value="">-- No specific site (Global Asset) --</option>
                        @foreach($userSites as $userSite)
                            <option value="{{ $userSite->id }}">{{ $userSite->original_name ?: $userSite->slug }} ({{ $userSite->slug }})</option>
                        @endforeach
                    </select>
                </div>
            @endif

            <!-- File Upload Dropzone -->
            <div>
                <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1">Select File(s)</label>
                <div 
                    x-data="{ isDropping: false }"
                    @dragover.prevent="isDropping = true"
                    @dragleave.prevent="isDropping = false"
                    @drop.prevent="isDropping = false; $refs.fileInput.files = $event.dataTransfer.files; $refs.fileInput.dispatchEvent(new Event('change'))"
                    class="border-2 border-dashed rounded-2xl p-6 text-center cursor-pointer transition-colors"
                    :class="isDropping ? 'border-accent bg-accent/[0.05] dark:bg-accent/10' : 'border-zinc-300 dark:border-zinc-700 hover:border-accent/60 dark:hover:border-accent/50'"
                    @click="$refs.fileInput.click()"
                >
                    <input type="file" x-ref="fileInput" wire:model="uploadFiles" multiple class="hidden" />
                    <flux:icon.arrow-up-tray class="size-8 mx-auto text-zinc-400 mb-2" />
                    <p class="text-xs font-semibold text-zinc-800 dark:text-zinc-200">
                        Click or drag files here to upload
                    </p>
                    <p class="text-[11px] text-zinc-400 mt-1">
                        Images (PNG, JPG, SVG, WebP), Videos (MP4, WebM), Audio (MP3), Documents (PDF) up to 100MB each
                    </p>
                </div>

                @if(!empty($uploadFiles))
                    <div class="mt-2.5 space-y-1">
                        <p class="text-[11px] font-semibold text-zinc-500">Selected for upload ({{ count($uploadFiles) }}):</p>
                        @foreach($uploadFiles as $file)
                            <div class="flex items-center justify-between text-xs px-2.5 py-1 rounded-lg bg-zinc-100 dark:bg-zinc-800 font-mono">
                                <span class="truncate">{{ $file->getClientOriginalName() }}</span>
                                <span class="text-[10px] text-zinc-400">{{ number_format($file->getSize() / 1024, 1) }} KB</span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="flex gap-2 justify-end pt-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Cancel</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary" wire:loading.attr="disabled" class="cursor-pointer">
                    <span wire:loading.remove>Upload Media</span>
                    <span wire:loading>Uploading...</span>
                </flux:button>
            </div>
        </form>
    </flux:modal>

    <!-- Preview Media Modal -->
    <flux:modal name="preview-media-modal" class="md:w-[680px]">
        @if($previewAsset)
            <div class="space-y-4">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <flux:heading size="lg" class="truncate max-w-md">{{ $previewAsset->original_name }}</flux:heading>
                        <flux:subheading>
                            {{ $previewAsset->storage_driver === 'asset_storage' ? 'Cloud Asset Storage' : 'Site Code' }} &bull; {{ $previewAsset->formatted_size }} &bull; {{ $previewAsset->mime_type }}
                        </flux:subheading>
                    </div>
                </div>

                <!-- Preview Display -->
                <div class="rounded-xl overflow-hidden bg-zinc-950 flex items-center justify-center min-h-[260px] max-h-[420px] p-2">
                    @if($previewAsset->isImage())
                        <img src="{{ $previewAsset->resolved_url }}" alt="{{ $previewAsset->original_name }}" class="max-h-[380px] max-w-full object-contain rounded-lg" />
                    @elseif($previewAsset->isVideo())
                        <video controls preload="metadata" class="max-h-[380px] max-w-full rounded-lg">
                            <source src="{{ $previewAsset->resolved_url }}" type="{{ $previewAsset->mime_type }}">
                        </video>
                    @elseif($previewAsset->isAudio())
                        <div class="p-6 w-full text-center">
                            <flux:icon.musical-note class="size-12 text-zinc-400 mx-auto mb-3" />
                            <audio controls class="w-full">
                                <source src="{{ $previewAsset->resolved_url }}" type="{{ $previewAsset->mime_type }}">
                            </audio>
                        </div>
                    @elseif($previewAsset->isPdf())
                        <div class="p-8 text-center">
                            <flux:icon.document-text class="size-16 text-zinc-400 mx-auto mb-3" />
                            <p class="text-sm text-zinc-300 font-semibold">{{ $previewAsset->original_name }}</p>
                            <a href="{{ $previewAsset->resolved_url }}" target="_blank" class="btn-primary text-xs px-4 py-2 mt-4">
                                <flux:icon.arrow-top-right-on-square class="size-3.5" />
                                Open PDF in new tab
                            </a>
                        </div>
                    @else
                        <div class="p-8 text-center text-zinc-400">
                            <flux:icon.document class="size-16 mx-auto mb-3" />
                            <p class="text-sm font-semibold">{{ $previewAsset->original_name }}</p>
                        </div>
                    @endif
                </div>

                <!-- Ready Embed Snippets -->
                <div class="space-y-2">
                    <div class="flex items-center justify-between text-xs">
                        <span class="font-bold text-zinc-700 dark:text-zinc-300">Embed Snippets</span>
                    </div>

                    <!-- CDN URL -->
                    <div class="flex items-center gap-2 p-2 rounded-xl bg-zinc-100 dark:bg-zinc-800 text-xs font-mono">
                        <span class="text-zinc-400 shrink-0 text-[10px] uppercase font-bold">URL</span>
                        <input type="text" readonly value="{{ $previewAsset->resolved_url }}" class="flex-1 bg-transparent border-none text-zinc-900 dark:text-zinc-100 text-xs focus:ring-0" />
                        <button 
                            type="button"
                            x-data="{ copied: false }"
                            @click="navigator.clipboard.writeText('{{ $previewAsset->resolved_url }}'); copied = true; setTimeout(() => copied = false, 1500)"
                            class="px-2.5 py-1 rounded-lg bg-white dark:bg-zinc-700 text-zinc-700 dark:text-zinc-200 text-xs font-sans font-semibold cursor-pointer shadow-2xs"
                        >
                            <span x-text="copied ? 'Copied!' : 'Copy'"></span>
                        </button>
                    </div>

                    <!-- HTML Tag -->
                    <div class="flex items-center gap-2 p-2 rounded-xl bg-zinc-100 dark:bg-zinc-800 text-xs font-mono">
                        <span class="text-zinc-400 shrink-0 text-[10px] uppercase font-bold">HTML</span>
                        <input type="text" readonly value="{{ $previewAsset->html_tag }}" class="flex-1 bg-transparent border-none text-zinc-900 dark:text-zinc-100 text-xs focus:ring-0" />
                        <button 
                            type="button"
                            x-data="{ copied: false }"
                            @click="navigator.clipboard.writeText(`{{ addslashes($previewAsset->html_tag) }}`); copied = true; setTimeout(() => copied = false, 1500)"
                            class="px-2.5 py-1 rounded-lg bg-white dark:bg-zinc-700 text-zinc-700 dark:text-zinc-200 text-xs font-sans font-semibold cursor-pointer shadow-2xs"
                        >
                            <span x-text="copied ? 'Copied!' : 'Copy'"></span>
                        </button>
                    </div>
                </div>

                <div class="flex items-center justify-between pt-3 border-t border-zinc-200 dark:border-zinc-800">
                    <flux:button 
                        variant="danger" 
                        size="sm" 
                        wire:click="deleteAsset('{{ $previewAsset->id }}')" 
                        wire:confirm="Are you sure you want to delete this asset?"
                    >
                        Delete Asset
                    </flux:button>

                    <div class="flex gap-2">
                        <flux:modal.close>
                            <flux:button variant="ghost" size="sm">Close</flux:button>
                        </flux:modal.close>
                    </div>
                </div>
            </div>
        @endif
    </flux:modal>
</div>
