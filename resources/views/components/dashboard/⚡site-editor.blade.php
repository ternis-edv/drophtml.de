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
        handleTab(e) {
            if (e.key === 'Tab') {
                e.preventDefault();
                const start = e.target.selectionStart;
                const end = e.target.selectionEnd;
                e.target.value = e.target.value.substring(0, start) + '  ' + e.target.value.substring(end);
                e.target.selectionStart = e.target.selectionEnd = start + 2;
                e.target.dispatchEvent(new Event('input'));
            }
        },
        reloadPreview() {
            this.previewTimestamp = Date.now();
        }
    }"
    @file-saved.window="reloadPreview()"
    class="space-y-4"
>
    <!-- Header & Action Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <flux:breadcrumbs>
                <flux:breadcrumbs.item :href="route('dashboard')" wire:navigate>Dashboard</flux:breadcrumbs.item>
                <flux:breadcrumbs.item>{{ $site->original_name ?: $site->slug }}</flux:breadcrumbs.item>
                <flux:breadcrumbs.item>Code Editor</flux:breadcrumbs.item>
            </flux:breadcrumbs>
            <div class="flex items-center gap-2.5 mt-1.5">
                <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100">Live Code Studio</h1>
                @if($currentFile)
                    <flux:badge size="sm" color="zinc" class="font-mono">{{ $currentFile }}</flux:badge>
                @endif
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <!-- View Mode Switcher -->
            <div class="inline-flex rounded-lg p-1 bg-zinc-100 dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 text-xs">
                <button wire:click="setPreviewMode('editor')" class="px-2.5 py-1 font-medium rounded-md transition-colors {{ $previewMode === 'editor' ? 'bg-white dark:bg-zinc-900 text-indigo-600 dark:text-indigo-400 shadow-xs' : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900' }}">Code Only</button>
                <button wire:click="setPreviewMode('split')" class="px-2.5 py-1 font-medium rounded-md transition-colors {{ $previewMode === 'split' ? 'bg-white dark:bg-zinc-900 text-indigo-600 dark:text-indigo-400 shadow-xs' : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900' }}">Split View</button>
                <button wire:click="setPreviewMode('preview')" class="px-2.5 py-1 font-medium rounded-md transition-colors {{ $previewMode === 'preview' ? 'bg-white dark:bg-zinc-900 text-indigo-600 dark:text-indigo-400 shadow-xs' : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900' }}">Live Preview</button>
            </div>

            <!-- Download Site Archive -->
            <a href="{{ route('dashboard.sites.download', $site->id) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg bg-zinc-100 hover:bg-zinc-200 dark:bg-zinc-800 dark:hover:bg-zinc-700 text-zinc-800 dark:text-zinc-200 transition-colors" title="Download complete site as ZIP archive">
                <flux:icon.arrow-down-tray class="size-3.5" />
                Export ZIP
            </a>

            <!-- Live Site Link -->
            <a href="/s/{{ $site->slug }}" target="_blank" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg bg-zinc-100 hover:bg-zinc-200 dark:bg-zinc-800 dark:hover:bg-zinc-700 text-zinc-800 dark:text-zinc-200 transition-colors">
                <flux:icon.arrow-top-right-on-square class="size-3.5" />
                Open Live Site
            </a>
        </div>
    </div>

    <!-- Studio Layout -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 min-h-[700px]">
        <!-- File Explorer Sidebar (3 Cols) -->
        <div class="lg:col-span-3 flex flex-col space-y-3">
            <flux:card class="flex-1 flex flex-col p-3.5">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-bold uppercase tracking-wider text-zinc-400">Explorer ({{ count($files) }})</span>
                    <div class="flex items-center gap-1">
                        <flux:modal.trigger name="create-file-modal">
                            <button type="button" class="p-1 rounded text-zinc-400 hover:text-indigo-600 dark:hover:text-indigo-400 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors" title="New File">
                                <flux:icon.plus class="size-4" />
                            </button>
                        </flux:modal.trigger>
                        <button type="button" @click="$refs.mgrUploadInput.click()" class="p-1 rounded text-zinc-400 hover:text-indigo-600 dark:hover:text-indigo-400 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors" title="Upload Asset">
                            <flux:icon.arrow-up-tray class="size-4" />
                        </button>
                        <input type="file" class="hidden" x-ref="mgrUploadInput" wire:model="uploadFile" />
                    </div>
                </div>

                <!-- Filter search input -->
                <div class="relative mb-2">
                    <input 
                        wire:model.live.debounce.150ms="fileFilter" 
                        type="text" 
                        placeholder="Filter files..." 
                        class="w-full pl-7 pr-2 py-1 text-xs rounded-md bg-zinc-50 dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 text-zinc-800 dark:text-zinc-200 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                    />
                    <flux:icon.magnifying-glass class="size-3 text-zinc-400 absolute left-2 top-2" />
                </div>

                <!-- File List Tree -->
                <div class="flex-1 overflow-y-auto max-h-[600px] space-y-0.5 pr-0.5">
                    @forelse($filteredFiles as $file)
                        <div class="group flex items-center justify-between rounded-md px-2 py-1.5 text-xs transition-colors {{ $currentFile === $file ? 'bg-indigo-50 dark:bg-indigo-950/50 text-indigo-600 dark:text-indigo-400 font-semibold' : 'text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100/70 dark:hover:bg-zinc-800/60' }}">
                            <button 
                                wire:click="selectFile('{{ $file }}')" 
                                class="flex-1 flex items-center gap-2 text-left truncate cursor-pointer"
                                title="{{ $file }}"
                            >
                                @if(str_ends_with(strtolower($file), '.html') || str_ends_with(strtolower($file), '.htm'))
                                    <flux:icon.code-bracket class="size-3.5 text-indigo-500 shrink-0" />
                                @elseif(str_ends_with(strtolower($file), '.css'))
                                    <flux:icon.hashtag class="size-3.5 text-pink-500 shrink-0" />
                                @elseif(str_ends_with(strtolower($file), '.js') || str_ends_with(strtolower($file), '.mjs'))
                                    <flux:icon.bolt class="size-3.5 text-amber-500 shrink-0" />
                                @elseif(in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), ['png', 'jpg', 'jpeg', 'gif', 'svg', 'webp', 'ico']))
                                    <flux:icon.photo class="size-3.5 text-emerald-500 shrink-0" />
                                @else
                                    <flux:icon.document-text class="size-3.5 text-zinc-400 shrink-0" />
                                @endif
                                <span class="truncate font-mono">{{ $file }}</span>
                            </button>

                            <flux:dropdown>
                                <button type="button" class="p-1 rounded text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-200 opacity-0 group-hover:opacity-100 transition-opacity">
                                    <flux:icon.ellipsis-vertical class="size-3" />
                                </button>
                                <flux:menu>
                                    <flux:menu.item icon="pencil-square" wire:click="startRename('{{ $file }}')">Rename</flux:menu.item>
                                    <flux:menu.item icon="trash" wire:click="deleteFile('{{ $file }}')" wire:confirm="Are you sure you want to delete '{{ $file }}'?" class="text-rose-600">Delete</flux:menu.item>
                                </flux:menu>
                            </flux:dropdown>
                        </div>
                    @empty
                        <div class="text-center py-6 text-xs text-zinc-400">
                            No files found.
                        </div>
                    @endforelse
                </div>
            </flux:card>
        </div>

        <!-- Main Workspace (Editor + Live Preview) (9 Cols) -->
        <div class="lg:col-span-9 flex flex-col space-y-3">
            @if($currentFile)
                <flux:card class="flex-1 flex flex-col p-4">
                    <!-- Editor / Preview Toolbar -->
                    <div class="flex items-center justify-between pb-3 mb-3 border-b border-zinc-100 dark:border-zinc-800">
                        <div class="flex items-center gap-2 font-mono text-xs">
                            <span class="font-bold text-zinc-900 dark:text-zinc-100">{{ $currentFile }}</span>
                            @if($isBinary)
                                <flux:badge size="xs" color="zinc">Binary Asset</flux:badge>
                            @else
                                <span class="text-zinc-400">&bull;</span>
                                <span class="text-zinc-400">Cmd+S to Save</span>
                            @endif
                        </div>

                        <div class="flex items-center gap-2">
                            @if($previewMode !== 'editor')
                                <!-- Viewport Controls -->
                                <div class="inline-flex rounded-lg p-0.5 bg-zinc-100 dark:bg-zinc-800 text-xs">
                                    <button wire:click="setViewportMode('desktop')" class="p-1 rounded transition-colors {{ $viewportMode === 'desktop' ? 'bg-white dark:bg-zinc-700 text-indigo-600 shadow-xs' : 'text-zinc-400 hover:text-zinc-600' }}" title="Desktop Viewport">
                                        <flux:icon.computer-desktop class="size-3.5" />
                                    </button>
                                    <button wire:click="setViewportMode('tablet')" class="p-1 rounded transition-colors {{ $viewportMode === 'tablet' ? 'bg-white dark:bg-zinc-700 text-indigo-600 shadow-xs' : 'text-zinc-400 hover:text-zinc-600' }}" title="Tablet (768px)">
                                        <flux:icon.device-tablet class="size-3.5" />
                                    </button>
                                    <button wire:click="setViewportMode('mobile')" class="p-1 rounded transition-colors {{ $viewportMode === 'mobile' ? 'bg-white dark:bg-zinc-700 text-indigo-600 shadow-xs' : 'text-zinc-400 hover:text-zinc-600' }}" title="Mobile (375px)">
                                        <flux:icon.device-phone-mobile class="size-3.5" />
                                    </button>
                                </div>
                            @endif

                            @if(!$isBinary)
                                <flux:button wire:click="saveFile" variant="primary" size="sm" class="bg-indigo-600 hover:bg-indigo-500 text-white shadow-xs">
                                    <flux:icon.check class="size-3.5 mr-1" />
                                    Save
                                </flux:button>
                            @endif
                        </div>
                    </div>

                    <!-- Work Area -->
                    <div class="flex-1 grid {{ $previewMode === 'split' ? 'grid-cols-1 lg:grid-cols-2 gap-4' : 'grid-cols-1' }} min-h-[580px]">
                        <!-- Code Editor Pane -->
                        @if($previewMode !== 'preview')
                            <div 
                                class="flex flex-col h-full relative border border-zinc-200 dark:border-zinc-800 rounded-lg overflow-hidden"
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
                                            <img src="{{ Storage::disk('public')->url("{$site->path}/{$currentFile}") }}" alt="{{ $currentFile }}" class="max-h-72 max-w-full rounded-lg border border-zinc-200 dark:border-zinc-700 shadow-sm mb-4 object-contain bg-checkered" />
                                            <p class="text-xs font-mono text-zinc-500">{{ $currentFile }}</p>
                                        @else
                                            <flux:icon.document class="size-16 text-zinc-400 mb-3" />
                                            <p class="text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ $currentFile }}</p>
                                            <p class="text-xs text-zinc-400 max-w-xs mt-1 mb-4">Binary file format cannot be directly edited as plain text.</p>
                                            <a href="{{ Storage::disk('public')->url("{$site->path}/{$currentFile}") }}" download="{{ basename($currentFile) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-zinc-200 dark:bg-zinc-800 hover:bg-zinc-300 dark:hover:bg-zinc-700 text-xs font-medium transition-colors">
                                                <flux:icon.arrow-down-tray class="size-3.5" /> Download Asset
                                            </a>
                                        @endif
                                    </div>
                                @else
                                    <textarea 
                                        wire:model="fileContent" 
                                        @keydown="handleTab($event)"
                                        class="w-full h-full min-h-[580px] p-4 font-mono text-xs md:text-sm bg-zinc-50 dark:bg-[#0c0c0e] text-zinc-900 dark:text-zinc-100 border-none outline-none resize-none leading-relaxed selection:bg-indigo-500 selection:text-white"
                                        spellcheck="false"
                                        placeholder="Start typing your code..."
                                    ></textarea>
                                @endif
                            </div>
                        @endif

                        <!-- Live Interactive Preview Pane -->
                        @if($previewMode !== 'editor')
                            <div class="flex flex-col h-full border border-zinc-200 dark:border-zinc-800 rounded-lg overflow-hidden bg-zinc-100 dark:bg-zinc-950 items-center justify-center p-2">
                                <div 
                                    class="h-full bg-white dark:bg-zinc-900 rounded-md shadow-sm border border-zinc-200 dark:border-zinc-800 overflow-hidden transition-all duration-300 w-full"
                                    :style="`max-width: ${'{{ $viewportMode }}' === 'mobile' ? '375px' : ('{{ $viewportMode }}' === 'tablet' ? '768px' : '100%')}`"
                                >
                                    <iframe 
                                        :src="`{{ url('/s/' . $site->slug . '/' . (str_ends_with(strtolower($currentFile), '.html') ? $currentFile : '')) }}?_t=${previewTimestamp}`"
                                        class="w-full h-full border-none min-h-[560px]"
                                        title="Live Site Preview"
                                    ></iframe>
                                </div>
                            </div>
                        @endif
                    </div>
                </flux:card>
            @else
                <flux:card class="flex-1 flex flex-col items-center justify-center text-center py-24">
                    <div class="size-16 rounded-2xl bg-zinc-100 dark:bg-zinc-800 text-zinc-400 flex items-center justify-center mb-4">
                        <flux:icon.code-bracket-square class="size-8" />
                    </div>
                    <h3 class="text-base font-bold text-zinc-800 dark:text-zinc-200">No File Selected</h3>
                    <p class="text-xs text-zinc-400 max-w-xs mt-1 mb-4">Pick a file from the explorer on the left or create a new file to start editing.</p>
                </flux:card>
            @endif
        </div>
    </div>

    <!-- Modals -->
    <flux:modal name="create-file-modal" class="md:w-[420px]">
        <form wire:submit="createFile" class="space-y-5">
            <div>
                <flux:heading size="lg">Create New File</flux:heading>
                <flux:subheading>Enter a file path (e.g. <code>about.html</code>, <code>styles.css</code>, or <code>js/main.js</code>).</flux:subheading>
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