<?php

use App\Models\MediaAsset;
use App\Models\Site;
use App\Services\ActivityLogService;
use App\Services\MediaStorageService;
use App\Services\SiteDeploymentService;
use App\Services\SiteServingService;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

new class extends Component
{
    use WithFileUploads;

    public $siteId;
    public $site;
    public $files = [];
    public $currentFile = null;
    public $fileContent = '';
    public $isBinary = false;

    public $newFileName = '';
    public $uploadFile = null;
    public $cloudUploadFile = null;
    public $explorerTab = 'code'; // 'code' or 'assets'

    public $oldFileName = '';
    public $newRenameName = '';
    public $fileFilter = '';

    public function setExplorerTab($tab)
    {
        $this->explorerTab = in_array($tab, ['code', 'assets'], true) ? $tab : 'code';
    }

    public function mount($siteId)
    {
        $this->siteId = $siteId;
        $this->site = auth()->user()->sites()->findOrFail($this->siteId);
        $this->loadFiles();

        if (! empty($this->files)) {
            if (in_array('index.html', $this->files)) {
                $this->selectFile('index.html');
            } else {
                $this->selectFile($this->files[0]);
            }
        }
    }

    /**
     * Normalize a client-supplied relative file path; reject anything that
     * escapes the site directory (traversal segments, protocol-relative or
     * leading-slash tricks).
     */
    private function safeRelativePath(?string $filename): ?string
    {
        if ($filename === null || $filename === '') {
            return null;
        }

        $segments = [];
        foreach (explode('/', str_replace('\\', '/', $filename)) as $segment) {
            if ($segment === '' || $segment === '.') {
                continue;
            }

            if ($segment === '..') {
                return null;
            }

            $segments[] = $segment;
        }

        return $segments === [] ? null : implode('/', $segments);
    }

    public function loadFiles()
    {
        if (Storage::disk('public')->exists($this->site->path)) {
            $prefix = rtrim($this->site->path, '/').'/';
            $allFiles = Storage::disk('public')->allFiles($this->site->path);

            $this->files = array_values(array_map(function ($file) use ($prefix) {
                return str_starts_with($file, $prefix) ? substr($file, strlen($prefix)) : $file;
            }, $allFiles));

            sort($this->files);
        } else {
            $this->files = [];
        }
    }

    public function selectFile($filename)
    {
        $filename = $this->safeRelativePath($filename);

        if ($filename === null) {
            return;
        }

        $this->currentFile = $filename;
        $path = "{$this->site->path}/{$filename}";

        if (Storage::disk('public')->exists($path)) {
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            $binaryExtensions = ['png', 'jpg', 'jpeg', 'gif', 'webp', 'ico', 'woff', 'woff2', 'ttf', 'eot', 'zip', 'pdf', 'mp4', 'webm', 'mp3'];

            if (in_array($ext, $binaryExtensions)) {
                $this->isBinary = true;
                $this->fileContent = '';
            } else {
                $this->isBinary = false;
                $this->fileContent = Storage::disk('public')->get($path);
            }
        }
    }

    public function saveFile(ActivityLogService $activityLogService)
    {
        $currentFile = $this->safeRelativePath($this->currentFile ?? '');

        if ($currentFile === null || $this->isBinary) {
            return;
        }

        $this->currentFile = $currentFile;
        $path = "{$this->site->path}/{$currentFile}";
        Storage::disk('public')->put($path, $this->fileContent);

        if ($currentFile === 'index.html' || (empty($this->site->entry_path) && str_ends_with(strtolower($currentFile), '.html'))) {
            $this->site->update(['entry_path' => $currentFile]);
        }

        $activityLogService->log(
            auth()->user(),
            $this->site,
            'file_edited',
            "Edited file: {$currentFile} on {$this->site->slug}"
        );

        $this->dispatch('file-saved');
        Flux::toast('File saved successfully.');
    }

    public function createFile(SiteDeploymentService $deploymentService, ActivityLogService $activityLogService)
    {
        $this->validate([
            'newFileName' => 'required|string|max:255',
        ]);

        $name = $this->safeRelativePath($this->newFileName);

        if ($name === null) {
            Flux::toast(variant: 'danger', text: 'This file name is not permitted.');

            return;
        }

        if (! str_contains(basename($name), '.')) {
            $name .= '.html';
        }

        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        if ($deploymentService->isDangerousExtension($ext)) {
            Flux::toast(variant: 'danger', text: "Creating .{$ext} files is not permitted for security reasons.");

            return;
        }

        $path = "{$this->site->path}/{$name}";

        if (Storage::disk('public')->exists($path)) {
            Flux::toast(variant: 'danger', text: 'A file with this name already exists.');

            return;
        }

        $dir = dirname($path);
        if (! Storage::disk('public')->exists($dir)) {
            Storage::disk('public')->makeDirectory($dir);
        }

        $defaultContent = str_ends_with(strtolower($name), '.html') || str_ends_with(strtolower($name), '.htm')
            ? "<!DOCTYPE html>\n<html lang=\"en\">\n<head>\n    <meta charset=\"UTF-8\">\n    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">\n    <title>New Page</title>\n</head>\n<body>\n    <h1>Hello World</h1>\n</body>\n</html>"
            : '';

        Storage::disk('public')->put($path, $defaultContent);

        if ($name === 'index.html' || (empty($this->site->entry_path) && str_ends_with(strtolower($name), '.html'))) {
            $this->site->update(['entry_path' => $name]);
        }

        $activityLogService->log(
            auth()->user(),
            $this->site,
            'file_created',
            "Created file: {$name}"
        );

        $this->loadFiles();
        $this->selectFile($name);
        $this->newFileName = '';
        Flux::modal('create-file-modal')->close();
        Flux::toast('File created successfully.');
    }

    public function deleteFile($filename, ActivityLogService $activityLogService)
    {
        $filename = $this->safeRelativePath($filename);

        if ($filename === null) {
            Flux::toast(variant: 'danger', text: 'This file name is not permitted.');

            return;
        }

        $path = "{$this->site->path}/{$filename}";
        Storage::disk('public')->delete($path);

        if ($this->site->entry_path === $filename) {
            $this->site->update(['entry_path' => SiteServingService::detectEntryPath($this->site->path)]);
        }

        $activityLogService->log(
            auth()->user(),
            $this->site,
            'file_deleted',
            "Deleted file: {$filename}"
        );

        if ($this->currentFile === $filename) {
            $this->currentFile = null;
            $this->fileContent = '';
            $this->isBinary = false;
        }

        $this->loadFiles();

        if (empty($this->currentFile) && ! empty($this->files)) {
            $this->selectFile($this->files[0]);
        }

        Flux::toast('File deleted.');
    }

    public function updatedUploadFile(SiteDeploymentService $deploymentService, ActivityLogService $activityLogService)
    {
        if (! $this->uploadFile) {
            return;
        }

        $filename = $this->safeRelativePath($deploymentService->sanitizeFileName($this->uploadFile->getClientOriginalName()));

        if ($filename === null) {
            Flux::toast(variant: 'danger', text: 'This file name is not permitted.');
            $this->uploadFile = null;

            return;
        }
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if ($deploymentService->isDangerousExtension($ext)) {
            Flux::toast(variant: 'danger', text: "Uploading .{$ext} files is not permitted for security reasons.");
            $this->uploadFile = null;

            return;
        }

        $path = "{$this->site->path}/{$filename}";
        $this->uploadFile->storeAs($this->site->path, $filename, 'public');

        if ($filename === 'index.html' || (empty($this->site->entry_path) && str_ends_with(strtolower($filename), '.html'))) {
            $this->site->update(['entry_path' => $filename]);
        }

        $activityLogService->log(
            auth()->user(),
            $this->site,
            'file_uploaded_to_manager',
            "Uploaded file to manager: {$filename}"
        );

        $this->loadFiles();
        $this->selectFile($filename);
        $this->uploadFile = null;
        Flux::toast('File uploaded successfully.');
    }

    public function startRename($filename)
    {
        $this->oldFileName = $filename;
        $this->newRenameName = $filename;
        Flux::modal('rename-file-modal')->show();
    }

    public function renameFile(SiteDeploymentService $deploymentService, ActivityLogService $activityLogService)
    {
        $this->validate(['newRenameName' => 'required|string|max:255']);

        $originalFileName = $this->oldFileName;
        $this->oldFileName = $this->safeRelativePath($this->oldFileName);

        if ($this->oldFileName === null) {
            Flux::toast(variant: 'danger', text: 'This file name is not permitted.');

            return;
        }

        $cleanNew = $this->safeRelativePath($this->newRenameName);

        if ($cleanNew === null) {
            Flux::toast(variant: 'danger', text: 'This file name is not permitted.');

            return;
        }

        if ($originalFileName === $cleanNew) {
            Flux::modal('rename-file-modal')->close();

            return;
        }

        $ext = strtolower(pathinfo($cleanNew, PATHINFO_EXTENSION));
        if ($deploymentService->isDangerousExtension($ext)) {
            Flux::toast(variant: 'danger', text: "Renaming to .{$ext} is not permitted for security reasons.");

            return;
        }

        $oldPath = "{$this->site->path}/{$this->oldFileName}";
        $newPath = "{$this->site->path}/{$cleanNew}";

        if (Storage::disk('public')->exists($newPath)) {
            Flux::toast(variant: 'danger', text: 'Target file name already exists.');

            return;
        }

        $dir = dirname($newPath);
        if (! Storage::disk('public')->exists($dir)) {
            Storage::disk('public')->makeDirectory($dir);
        }

        Storage::disk('public')->move($oldPath, $newPath);

        if ($this->site->entry_path === $this->oldFileName) {
            $this->site->update(['entry_path' => SiteServingService::detectEntryPath($this->site->path)]);
        } elseif (empty($this->site->entry_path) && str_ends_with(strtolower($cleanNew), '.html')) {
            $this->site->update(['entry_path' => $cleanNew]);
        }

        $activityLogService->log(
            auth()->user(),
            $this->site,
            'file_renamed',
            "Renamed file from {$this->oldFileName} to {$cleanNew}"
        );

        if ($this->currentFile === $this->oldFileName) {
            $this->currentFile = $cleanNew;
        }

        $this->loadFiles();
        Flux::modal('rename-file-modal')->close();
        Flux::toast('File renamed.');
    }

    public function updatedCloudUploadFile(MediaStorageService $mediaStorageService)
    {
        if (! $this->cloudUploadFile) {
            return;
        }

        try {
            $asset = $mediaStorageService->store(
                $this->cloudUploadFile,
                auth()->user(),
                $this->site,
                'asset_storage'
            );
            $this->cloudUploadFile = null;
            Flux::toast("Uploaded to Cloud Asset Storage: {$asset->original_name}");
        } catch (\Exception $e) {
            Flux::toast(variant: 'danger', text: "Cloud upload failed: {$e->getMessage()}");
            $this->cloudUploadFile = null;
        }
    }

    public function insertAssetSnippet($snippet)
    {
        if ($this->isBinary || ! $this->currentFile) {
            Flux::toast('Please select an editable HTML/CSS file first.');

            return;
        }

        $this->fileContent .= "\n".$snippet;
        Flux::toast('Asset snippet inserted into code editor!');
    }

    public function migrateCurrentFileToCloud(MediaStorageService $mediaStorageService)
    {
        if (! $this->currentFile || ! $this->isBinary) {
            return;
        }

        try {
            $asset = $mediaStorageService->promoteCodeFileToAssetStorage(
                $this->site,
                $this->currentFile,
                auth()->user(),
                true
            );

            $oldName = $this->currentFile;
            $this->currentFile = null;
            $this->isBinary = false;
            $this->fileContent = '';
            $this->loadFiles();

            if (! empty($this->files)) {
                $this->selectFile(in_array('index.html', $this->files) ? 'index.html' : $this->files[0]);
            }

            Flux::toast("Migrated '{$oldName}' to Cloud Asset Storage!");
        } catch (\Exception $e) {
            Flux::toast(variant: 'danger', text: "Migration failed: {$e->getMessage()}");
        }
    }

    public function render()
    {
        $filteredFiles = $this->files;
        if (! empty($this->fileFilter)) {
            $filter = strtolower(trim($this->fileFilter));
            $filteredFiles = array_filter($this->files, fn ($f) => str_contains(strtolower($f), $filter));
        }

        $cloudAssets = $this->site->mediaAssets()->assetStorage()->latest()->get();

        return view('components.dashboard.⚡site-editor', [
            'filteredFiles' => $filteredFiles,
            'cloudAssets' => $cloudAssets,
        ]);
    }
};
?>

@include('components.editor.workspace')
