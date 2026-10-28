<?php

use App\Services\MediaStorageService;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

new class extends Component
{
    use WithFileUploads, WithPagination;

    #[Locked]
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
        $this->resetPage();
        $this->typeFilter = in_array($type, ['all', 'image', 'video', 'document', 'audio'], true) ? $type : 'all';
    }

    public function setDriverFilter($driver)
    {
        $this->resetPage();
        $this->driverFilter = in_array($driver, ['all', 'asset_storage', 'code'], true) ? $driver : 'all';
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedTypeFilter()
    {
        $this->resetPage();
    }

    public function updatedDriverFilter()
    {
        $this->resetPage();
    }

    private function assetQuery()
    {
        return auth()->user()->mediaAssets()->when($this->siteId, fn ($query) => $query->where('site_id', $this->siteId));
    }

    public function uploadMedia(MediaStorageService $mediaStorageService)
    {
        $this->validate([
            'uploadFiles' => 'required|array|min:1',
            'uploadFiles.*' => 'file|max:102400', // 100MB max per file
            'storageDestination' => 'required|in:asset_storage,code',
            'selectedSiteId' => ['nullable', Rule::exists('sites', 'id')->where('user_id', auth()->id())],
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
        $failed = [];
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
            } catch (Exception $e) {
                $failed[] = $file;
                Flux::toast(variant: 'danger', text: "Error uploading {$file->getClientOriginalName()}: {$e->getMessage()}");
            }
        }

        $this->uploadFiles = $failed;
        $targetLabel = $this->storageDestination === 'asset_storage' ? 'Cloud Asset Storage' : 'Site Code';
        if ($count) {
            Flux::toast("Uploaded {$count} file(s) to {$targetLabel}.");
        }
        $this->resetPage();
        if (empty($failed)) {
            Flux::modal('upload-media-modal')->close();
        }
    }

    public function deleteAsset(string $id, MediaStorageService $mediaStorageService)
    {
        $asset = $this->assetQuery()->findOrFail($id);
        $name = $asset->original_name;
        $mediaStorageService->delete($asset, auth()->user());

        if ($this->previewAssetId === $id) {
            $this->previewAssetId = null;
            $this->previewAsset = null;
        }

        $this->resetPage();
        Flux::toast("Media '{$name}' deleted.");
    }

    public function migrateAsset(string $id, string $targetDriver, ?string $targetSiteId, MediaStorageService $mediaStorageService)
    {
        $asset = $this->assetQuery()->findOrFail($id);
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
            if ($this->previewAssetId === $id) {
                $this->previewAsset = $asset->fresh();
            }
            $targetName = $targetDriver === 'asset_storage' ? 'Cloud Asset Storage' : 'Site Code';
            Flux::toast("Asset migrated to {$targetName} successfully.");
        } catch (Exception $e) {
            Flux::toast(variant: 'danger', text: "Migration failed: {$e->getMessage()}");
        }
    }

    public function copyAssetToCode(string $id, string $targetSiteId, MediaStorageService $mediaStorageService)
    {
        $asset = $this->assetQuery()->findOrFail($id);
        $site = auth()->user()->sites()->findOrFail($targetSiteId);

        try {
            $relPath = $mediaStorageService->copyToCode($asset, $site);
            Flux::toast("Copied to {$site->slug}/{$relPath}");
        } catch (Exception $e) {
            Flux::toast(variant: 'danger', text: "Copy failed: {$e->getMessage()}");
        }
    }

    public function closePreview()
    {
        $this->previewAssetId = null;
        $this->previewAsset = null;
    }

    public function showPreview(string $id)
    {
        $this->previewAssetId = $id;
        $this->previewAsset = $this->assetQuery()->findOrFail($id);
        $this->dispatch('media-preview-opened');
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
            $query->whereIn('file_type', $this->typeFilter === 'document' ? ['document', 'other'] : [$this->typeFilter]);
        }

        if ($this->driverFilter !== 'all') {
            $query->where('storage_location', $this->driverFilter);
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

@include('components.media.library')
