<?php

use App\Models\Site;
use App\Services\ActivityLogService;
use App\Services\SiteDeploymentService;
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

    public $oldFileName = '';
    public $newRenameName = '';
    public $fileFilter = '';
    public $previewMode = 'split'; // 'editor', 'split', 'preview'
    public $viewportMode = 'desktop'; // 'desktop', 'tablet', 'mobile'

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

    public function setPreviewMode($mode)
    {
        $this->previewMode = in_array($mode, ['editor', 'split', 'preview'], true) ? $mode : 'split';
    }

    public function setViewportMode($viewport)
    {
        $this->viewportMode = in_array($viewport, ['desktop', 'tablet', 'mobile'], true) ? $viewport : 'desktop';
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
        if (! $this->currentFile || $this->isBinary) {
            return;
        }

        $path = "{$this->site->path}/{$this->currentFile}";
        Storage::disk('public')->put($path, $this->fileContent);

        $activityLogService->log(
            auth()->user(),
            $this->site,
            'file_edited',
            "Edited file: {$this->currentFile} on {$this->site->slug}"
        );

        $this->dispatch('file-saved');
        Flux::toast('File saved successfully.');
    }

    public function createFile(SiteDeploymentService $deploymentService, ActivityLogService $activityLogService)
    {
        $this->validate([
            'newFileName' => 'required|string|max:255',
        ]);

        $name = ltrim(str_replace(['../', '..\\'], '', trim($this->newFileName)), '/');

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
        $path = "{$this->site->path}/{$filename}";
        Storage::disk('public')->delete($path);

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

        $filename = $deploymentService->sanitizeFileName($this->uploadFile->getClientOriginalName());
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if ($deploymentService->isDangerousExtension($ext)) {
            Flux::toast(variant: 'danger', text: "Uploading .{$ext} files is not permitted for security reasons.");
            $this->uploadFile = null;

            return;
        }

        $path = "{$this->site->path}/{$filename}";
        $this->uploadFile->storeAs($this->site->path, $filename, 'public');

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

        $cleanNew = ltrim(str_replace(['../', '..\\'], '', trim($this->newRenameName)), '/');

        if ($this->oldFileName === $cleanNew) {
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

        $activityLogService->log(
            auth()->user(),
            $this->site,
            'file_renamed',
            "Renamed file from {$this->oldFileName} to {$cleanNew}"
        );

        if ($this->currentFile === $this->oldFileName) {
            $this->currentFile = cleanNew;
        }

        $this->loadFiles();
        Flux::modal('rename-file-modal')->close();
        Flux::toast('File renamed.');
    }

    public function render()
    {
        $filteredFiles = $this->files;
        if (! empty($this->fileFilter)) {
            $filter = strtolower(trim($this->fileFilter));
            $filteredFiles = array_filter($this->files, fn ($f) => str_contains(strtolower($f), $filter));
        }

        return view('components.dashboard.⚡site-editor', [
            'filteredFiles' => $filteredFiles,
        ]);
    }
};
?>

<div 
    x-data="{
        previewTimestamp: Date.now(),
        editorStats: { lines: 1, chars: 0 },
        updateStats(e) {
            const val = e ? e.target.value : ($refs.codeTextarea ? $refs.codeTextarea.value : '');
            this.editorStats.lines = val ? val.split('\n').length : 1;
            this.editorStats.chars = val ? val.length : 0;
        },
        handleTab(e) {
            if (e.key === 'Tab') {
                e.preventDefault();
                const start = e.target.selectionStart;
                const end = e.target.selectionEnd;
                e.target.value = e.target.value.substring(0, start) + '  ' + e.target.value.substring(end);
                e.target.selectionStart = e.target.selectionEnd = start + 2;
                e.target.dispatchEvent(new Event('input'));
                this.updateStats(e);
            }
        },
        reloadPreview() {
            this.previewTimestamp = Date.now();
        }
    }"
    x-init="$nextTick(() => updateStats())"
    @file-saved.window="reloadPreview(); updateStats()"
    class="space-y-4"
>
    <!-- Subnavigation Header -->
    <x-site-subnav :site="$site" current="editor" />

    <!-- Mode Switcher Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-1">
        <div class="flex items-center gap-2.5">
            @if($currentFile)
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-xl bg-zinc-100 dark:bg-zinc-800/80 border border-zinc-200 dark:border-zinc-700/80 text-xs font-mono">
                    <flux:icon.document-text class="size-3.5 text-indigo-500" />
                    <span class="font-semibold text-zinc-900 dark:text-zinc-100">{{ $currentFile }}</span>
                    @if($isBinary)
                        <span class="text-[10px] px-1.5 py-0.5 rounded bg-zinc-200 dark:bg-zinc-700 text-zinc-600 dark:text-zinc-300 font-sans">Binary</span>
                    @else
                        <span class="text-[10px] px-1.5 py-0.5 rounded bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-400 font-sans">Editable</span>
                    @endif
                </div>
            @endif
        </div>

        <div class="flex items-center gap-2 self-end sm:self-auto">
            <div class="inline-flex rounded-xl p-1 bg-zinc-100 dark:bg-zinc-800/80 border border-zinc-200 dark:border-zinc-700/80 text-xs font-medium">
                <button 
                    wire:click="setPreviewMode('editor')" 
                    class="px-3 py-1 rounded-lg transition-all cursor-pointer {{ $previewMode === 'editor' ? 'bg-white dark:bg-zinc-900 text-indigo-600 dark:text-indigo-400 font-semibold shadow-xs' : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100' }}"
                >
                    <span class="inline-flex items-center gap-1.5">
                        <flux:icon.code-bracket class="size-3.5" />
                        <span>Code Only</span>
                    </span>
                </button>
                <button 
                    wire:click="setPreviewMode('split')" 
                    class="px-3 py-1 rounded-lg transition-all cursor-pointer {{ $previewMode === 'split' ? 'bg-white dark:bg-zinc-900 text-indigo-600 dark:text-indigo-400 font-semibold shadow-xs' : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100' }}"
                >
                    <span class="inline-flex items-center gap-1.5">
                        <flux:icon.arrows-right-left class="size-3.5" />
                        <span>Split View</span>
                    </span>
                </button>
                <button 
                    wire:click="setPreviewMode('preview')" 
                    class="px-3 py-1 rounded-lg transition-all cursor-pointer {{ $previewMode === 'preview' ? 'bg-white dark:bg-zinc-900 text-indigo-600 dark:text-indigo-400 font-semibold shadow-xs' : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100' }}"
                >
                    <span class="inline-flex items-center gap-1.5">
                        <flux:icon.eye class="size-3.5" />
                        <span>Live Preview</span>
                    </span>
                </button>
            </div>
        </div>
    </div>

    <!-- Studio Layout -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 min-h-[720px]">
        <!-- File Explorer Sidebar (3 Cols) -->
        <div class="lg:col-span-3 flex flex-col space-y-3">
            <div class="glass-card rounded-2xl p-3.5 flex flex-col h-full">
                <!-- Explorer Toolbar -->
                <div class="flex items-center justify-between pb-3 mb-2 border-b border-zinc-100 dark:border-zinc-800">
                    <div class="flex items-center gap-2">
                        <flux:icon.folder class="size-4 text-indigo-500" />
                        <span class="text-xs font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Explorer</span>
                        <span class="text-[10px] font-mono px-1.5 py-0.5 rounded-full bg-zinc-100 dark:bg-zinc-800 text-zinc-500 font-semibold">{{ count($files) }}</span>
                    </div>

                    <div class="flex items-center gap-1">
                        <flux:modal.trigger name="create-file-modal">
                            <button 
                                type="button" 
                                class="p-1.5 rounded-lg text-zinc-500 hover:text-indigo-600 dark:hover:text-indigo-400 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors cursor-pointer" 
                                title="New File"
                            >
                                <flux:icon.plus class="size-4" />
                            </button>
                        </flux:modal.trigger>

                        <button 
                            type="button" 
                            @click="$refs.mgrUploadInput.click()" 
                            class="p-1.5 rounded-lg text-zinc-500 hover:text-indigo-600 dark:hover:text-indigo-400 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors cursor-pointer" 
                            title="Upload File"
                        >
                            <flux:icon.arrow-up-tray class="size-4" />
                        </button>
                        <input type="file" class="hidden" x-ref="mgrUploadInput" wire:model="uploadFile" />
                    </div>
                </div>

                <!-- Filter search input -->
                <div class="relative mb-3">
                    <flux:icon.magnifying-glass class="size-3.5 text-zinc-400 absolute left-2.5 top-2.5 pointer-events-none" />
                    <input 
                        wire:model.live.debounce.150ms="fileFilter" 
                        type="text" 
                        placeholder="Filter files..." 
                        class="w-full pl-8 pr-7 py-1.5 text-xs rounded-xl bg-zinc-50 dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 text-zinc-800 dark:text-zinc-200 placeholder-zinc-400 focus:outline-none focus:ring-1 focus:ring-indigo-500 transition-colors"
                    />
                    @if($fileFilter)
                        <button 
                            type="button" 
                            wire:click="$set('fileFilter', '')" 
                            class="absolute right-2.5 top-2 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200"
                        >
                            <flux:icon.x-mark class="size-3.5" />
                        </button>
                    @endif
                </div>

                <!-- File List Tree -->
                <div class="flex-1 overflow-y-auto max-h-[600px] space-y-1 pr-1 custom-scrollbar">
                    @forelse($filteredFiles as $file)
                        @php
                            $isSelected = ($currentFile === $file);
                            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                        @endphp
                        <div class="group flex items-center justify-between rounded-xl px-2.5 py-1.5 text-xs transition-all {{ $isSelected ? 'bg-indigo-50 dark:bg-indigo-950/40 text-indigo-600 dark:text-indigo-400 font-semibold ring-1 ring-indigo-500/20 shadow-xs' : 'text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100/70 dark:hover:bg-zinc-800/60' }}">
                            <button 
                                type="button"
                                wire:click="selectFile('{{ $file }}')" 
                                class="flex-1 flex items-center gap-2 text-left truncate cursor-pointer"
                                title="{{ $file }}"
                            >
                                @if(in_array($ext, ['html', 'htm']))
                                    <flux:icon.code-bracket class="size-4 text-indigo-500 shrink-0" />
                                @elseif($ext === 'css')
                                    <flux:icon.hashtag class="size-4 text-pink-500 shrink-0" />
                                @elseif(in_array($ext, ['js', 'mjs', 'ts']))
                                    <flux:icon.bolt class="size-4 text-amber-500 shrink-0" />
                                @elseif(in_array($ext, ['png', 'jpg', 'jpeg', 'gif', 'svg', 'webp', 'ico']))
                                    <flux:icon.photo class="size-4 text-emerald-500 shrink-0" />
                                @elseif(in_array($ext, ['json', 'xml', 'yaml', 'yml']))
                                    <flux:icon.circle-stack class="size-4 text-cyan-500 shrink-0" />
                                @elseif(in_array($ext, ['md', 'txt']))
                                    <flux:icon.document-text class="size-4 text-violet-500 shrink-0" />
                                @else
                                    <flux:icon.document class="size-4 text-zinc-400 shrink-0" />
                                @endif
                                <span class="truncate font-mono text-[12px]">{{ $file }}</span>
                            </button>

                            <flux:dropdown>
                                <button type="button" class="p-1 rounded-md text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-200 opacity-0 group-hover:opacity-100 transition-opacity cursor-pointer">
                                    <flux:icon.ellipsis-vertical class="size-3.5" />
                                </button>
                                <flux:menu>
                                    <flux:menu.item icon="pencil-square" wire:click="startRename('{{ $file }}')">Rename</flux:menu.item>
                                    <flux:menu.item icon="trash" wire:click="deleteFile('{{ $file }}')" wire:confirm="Are you sure you want to delete '{{ $file }}'?" class="text-rose-600">Delete</flux:menu.item>
                                </flux:menu>
                            </flux:dropdown>
                        </div>
                    @empty
                        <div class="text-center py-10 px-3">
                            <flux:icon.magnifying-glass class="size-6 text-zinc-300 dark:text-zinc-700 mx-auto mb-2" />
                            <p class="text-xs text-zinc-500">No matching files</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Main Workspace (Editor + Live Preview) (9 Cols) -->
        <div class="lg:col-span-9 flex flex-col space-y-3">
            @if($currentFile)
                <div class="glass-card rounded-2xl flex-1 flex flex-col p-4">
                    <!-- Editor / Preview Toolbar -->
                    <div class="flex items-center justify-between pb-3 mb-3 border-b border-zinc-100 dark:border-zinc-800">
                        <div class="flex items-center gap-2 font-mono text-xs">
                            <span class="font-bold text-zinc-900 dark:text-zinc-100 flex items-center gap-1.5">
                                <flux:icon.document-text class="size-4 text-indigo-500" />
                                {{ $currentFile }}
                            </span>
                            @if($isBinary)
                                <span class="text-[10px] font-sans px-2 py-0.5 rounded-full bg-zinc-100 dark:bg-zinc-800 text-zinc-500 font-medium">Binary Asset</span>
                            @else
                                <span class="text-zinc-300 dark:text-zinc-700">&bull;</span>
                                <span class="text-zinc-400 dark:text-zinc-500 text-[11px] font-sans flex items-center gap-1">
                                    <kbd class="px-1 py-0.5 rounded bg-zinc-100 dark:bg-zinc-800 text-[10px] font-mono border border-zinc-200 dark:border-zinc-700">⌘S</kbd> to save
                                </span>
                            @endif
                        </div>

                        <div class="flex items-center gap-2">
                            @if($previewMode !== 'editor')
                                <!-- Viewport Controls -->
                                <div class="inline-flex rounded-xl p-0.5 bg-zinc-100 dark:bg-zinc-800/80 border border-zinc-200 dark:border-zinc-700 text-xs">
                                    <button 
                                        wire:click="setViewportMode('desktop')" 
                                        class="p-1.5 rounded-lg transition-colors cursor-pointer {{ $viewportMode === 'desktop' ? 'bg-white dark:bg-zinc-700 text-indigo-600 dark:text-indigo-400 shadow-xs' : 'text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300' }}" 
                                        title="Desktop Viewport"
                                    >
                                        <flux:icon.computer-desktop class="size-3.5" />
                                    </button>
                                    <button 
                                        wire:click="setViewportMode('tablet')" 
                                        class="p-1.5 rounded-lg transition-colors cursor-pointer {{ $viewportMode === 'tablet' ? 'bg-white dark:bg-zinc-700 text-indigo-600 dark:text-indigo-400 shadow-xs' : 'text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300' }}" 
                                        title="Tablet (768px)"
                                    >
                                        <flux:icon.device-tablet class="size-3.5" />
                                    </button>
                                    <button 
                                        wire:click="setViewportMode('mobile')" 
                                        class="p-1.5 rounded-lg transition-colors cursor-pointer {{ $viewportMode === 'mobile' ? 'bg-white dark:bg-zinc-700 text-indigo-600 dark:text-indigo-400 shadow-xs' : 'text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300' }}" 
                                        title="Mobile (375px)"
                                    >
                                        <flux:icon.device-phone-mobile class="size-3.5" />
                                    </button>
                                </div>
                            @endif

                            @if(!$isBinary)
                                <flux:button 
                                    wire:click="saveFile" 
                                    variant="primary" 
                                    size="sm" 
                                    class="bg-indigo-600 hover:bg-indigo-500 text-white shadow-xs rounded-xl"
                                >
                                    <flux:icon.check class="size-3.5 mr-1" />
                                    Save
                                </flux:button>
                            @endif
                        </div>
                    </div>

                    <!-- Work Area -->
                    <div class="flex-1 grid {{ $previewMode === 'split' ? 'grid-cols-1 lg:grid-cols-2 gap-4' : 'grid-cols-1' }} min-h-[600px]">
                        <!-- Code Editor Pane -->
                        @if($previewMode !== 'preview')
                            <div 
                                class="flex flex-col h-full relative border border-zinc-200 dark:border-zinc-800 rounded-xl overflow-hidden bg-white dark:bg-[#0c0c0e] shadow-inner"
                                x-data="{
                                    init() {
                                        window.addEventListener('keydown', (e) => {
                                            if ((e.metaKey || e.ctrlKey) && e.key === 's') {
                                                e.preventDefault();
                                                @this.call('saveFile');
                                            }
                                        });
                                    }
                                }"
                            >
                                @if($isBinary)
                                    <div class="flex-1 flex flex-col items-center justify-center p-8 bg-zinc-50 dark:bg-zinc-900/50 text-center">
                                        @if(in_array(strtolower(pathinfo($currentFile, PATHINFO_EXTENSION)), ['png', 'jpg', 'jpeg', 'gif', 'svg', 'webp', 'ico']))
                                            <div class="p-3 bg-white dark:bg-zinc-950 rounded-xl border border-zinc-200 dark:border-zinc-800 shadow-sm mb-4">
                                                <img src="{{ Storage::disk('public')->url("{$site->path}/{$currentFile}") }}" alt="{{ $currentFile }}" class="max-h-72 max-w-full rounded-lg object-contain" />
                                            </div>
                                            <p class="text-xs font-mono text-zinc-500">{{ $currentFile }}</p>
                                        @else
                                            <div class="size-16 rounded-2xl bg-zinc-100 dark:bg-zinc-800 text-zinc-400 flex items-center justify-center mb-3">
                                                <flux:icon.document class="size-8" />
                                            </div>
                                            <p class="text-sm font-semibold text-zinc-800 dark:text-zinc-200">{{ $currentFile }}</p>
                                            <p class="text-xs text-zinc-400 max-w-xs mt-1 mb-4">Binary file format cannot be directly edited as plain text.</p>
                                            <a href="{{ Storage::disk('public')->url("{$site->path}/{$currentFile}") }}" download="{{ basename($currentFile) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-zinc-200 dark:bg-zinc-800 hover:bg-zinc-300 dark:hover:bg-zinc-700 text-xs font-medium transition-colors">
                                                <flux:icon.arrow-down-tray class="size-3.5" /> Download Asset
                                            </a>
                                        @endif
                                    </div>
                                @else
                                    <textarea 
                                        x-ref="codeTextarea"
                                        wire:model="fileContent" 
                                        @input="updateStats($event)"
                                        @keydown="handleTab($event)"
                                        class="w-full flex-1 p-4 font-mono text-xs md:text-sm bg-transparent text-zinc-900 dark:text-zinc-100 border-none outline-none resize-none leading-relaxed selection:bg-indigo-500 selection:text-white custom-scrollbar min-h-[550px]"
                                        spellcheck="false"
                                        placeholder="Start typing your code..."
                                    ></textarea>

                                    <!-- Code Status Footer Bar -->
                                    <div class="px-4 py-2 border-t border-zinc-100 dark:border-zinc-800/80 bg-zinc-50 dark:bg-zinc-950/80 flex items-center justify-between text-[11px] font-mono text-zinc-400">
                                        <div class="flex items-center gap-3">
                                            <span class="flex items-center gap-1">
                                                <span class="size-1.5 rounded-full bg-indigo-500"></span>
                                                <span class="uppercase">{{ pathinfo($currentFile, PATHINFO_EXTENSION) ?: 'text' }}</span>
                                            </span>
                                            <span>UTF-8</span>
                                        </div>
                                        <div class="flex items-center gap-3">
                                            <span x-text="`${editorStats.lines} lines`"></span>
                                            <span class="text-zinc-300 dark:text-zinc-700">&bull;</span>
                                            <span x-text="`${editorStats.chars} chars`"></span>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @endif

                        <!-- Live Interactive Preview Pane -->
                        @if($previewMode !== 'editor')
                            <div class="flex flex-col h-full border border-zinc-200 dark:border-zinc-800 rounded-xl overflow-hidden bg-zinc-100 dark:bg-zinc-950 items-center justify-center p-3">
                                <!-- Browser Mock Frame -->
                                <div 
                                    class="h-full bg-white dark:bg-zinc-900 rounded-xl shadow-lg border border-zinc-200 dark:border-zinc-800 overflow-hidden transition-all duration-300 w-full flex flex-col"
                                    :style="`max-width: ${'{{ $viewportMode }}' === 'mobile' ? '375px' : ('{{ $viewportMode }}' === 'tablet' ? '768px' : '100%')}`"
                                >
                                    <!-- Browser Top Bar Mock -->
                                    <div class="px-3 py-2 bg-zinc-100 dark:bg-zinc-800/90 border-b border-zinc-200 dark:border-zinc-700 flex items-center justify-between gap-2">
                                        <div class="flex items-center gap-1.5">
                                            <span class="size-2.5 rounded-full bg-rose-400/80 inline-block"></span>
                                            <span class="size-2.5 rounded-full bg-amber-400/80 inline-block"></span>
                                            <span class="size-2.5 rounded-full bg-emerald-400/80 inline-block"></span>
                                        </div>

                                        <div class="flex-1 max-w-sm mx-auto px-2 py-0.5 rounded-lg bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 text-[11px] font-mono text-zinc-500 truncate text-center">
                                            <span>/s/{{ $site->slug }}/{{ (str_ends_with(strtolower($currentFile), '.html') ? $currentFile : '') }}</span>
                                        </div>

                                        <div class="flex items-center gap-1">
                                            <button 
                                                type="button" 
                                                @click="reloadPreview()" 
                                                class="p-1 rounded text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-200 transition-colors cursor-pointer"
                                                title="Reload Preview"
                                            >
                                                <flux:icon.arrow-path class="size-3.5" />
                                            </button>
                                            <a 
                                                :href="`{{ url('/s/' . $site->slug . '/' . (str_ends_with(strtolower($currentFile), '.html') ? $currentFile : '')) }}`" 
                                                target="_blank" 
                                                class="p-1 rounded text-zinc-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors"
                                                title="Open in new tab"
                                            >
                                                <flux:icon.arrow-top-right-on-square class="size-3.5" />
                                            </a>
                                        </div>
                                    </div>

                                    <!-- Iframe Preview -->
                                    <iframe 
                                        :src="`{{ url('/s/' . $site->slug . '/' . (str_ends_with(strtolower($currentFile), '.html') ? $currentFile : '')) }}?_t=${previewTimestamp}`"
                                        class="w-full flex-1 border-none min-h-[530px]"
                                        title="Live Site Preview"
                                    ></iframe>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            @else
                <div class="glass-card rounded-2xl flex-1 flex flex-col items-center justify-center text-center py-28 px-4">
                    <div class="size-16 rounded-2xl bg-indigo-50 dark:bg-indigo-950/40 text-indigo-500 flex items-center justify-center mb-4 ring-1 ring-indigo-500/20">
                        <flux:icon.code-bracket-square class="size-8" />
                    </div>
                    <h3 class="text-base font-bold text-zinc-800 dark:text-zinc-200">No File Selected</h3>
                    <p class="text-xs text-zinc-400 max-w-xs mt-1.5 mb-5">Pick a file from the explorer on the left or create a new HTML/CSS file to begin editing.</p>
                    <flux:modal.trigger name="create-file-modal">
                        <flux:button variant="primary" size="sm" class="rounded-xl">
                            <flux:icon.plus class="size-4 mr-1" />
                            Create New File
                        </flux:button>
                    </flux:modal.trigger>
                </div>
            @endif
        </div>
    </div>

    <!-- Modals -->
    <flux:modal name="create-file-modal" class="md:w-[420px]">
        <form wire:submit="createFile" class="space-y-5">
            <div>
                <flux:heading size="lg">Create New File</flux:heading>
                <flux:subheading>Enter a file path (e.g. <code>about.html</code>, <code>css/styles.css</code>, or <code>js/main.js</code>).</flux:subheading>
            </div>

            <flux:input wire:model="newFileName" placeholder="about.html" autofocus required />

            <div class="flex gap-2 justify-end">
                <flux:modal.close>
                    <flux:button variant="ghost">Cancel</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary">Create File</flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="rename-file-modal" class="md:w-[420px]">
        <form wire:submit="renameFile" class="space-y-5">
            <div>
                <flux:heading size="lg">Rename File</flux:heading>
                <flux:subheading>Rename <code>{{ $oldFileName }}</code> to a new file name.</flux:subheading>
            </div>

            <flux:input wire:model="newRenameName" autofocus required />

            <div class="flex gap-2 justify-end">
                <flux:modal.close>
                    <flux:button variant="ghost">Cancel</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary">Rename</flux:button>
            </div>
        </form>
    </flux:modal>
</div>