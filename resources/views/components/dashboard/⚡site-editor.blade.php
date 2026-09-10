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

    public function mount($siteId)
    {
        $this->siteId = $siteId;
        $this->site = auth()->user()->sites()->findOrFail($this->siteId);
        $this->loadFiles();

        if (! empty($this->files)) {
            // Default to index.html if available, else first file
            if (in_array('index.html', $this->files)) {
                $this->selectFile('index.html');
            } else {
                $this->selectFile($this->files[0]);
            }
        }
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

        // Ensure directory exists
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
            $this->currentFile = $cleanNew;
        }

        $this->loadFiles();
        Flux::modal('rename-file-modal')->close();
        Flux::toast('File renamed.');
    }

    public function render()
    {
        return view('components.dashboard.⚡site-editor');
    }
};
?>

<div>
    <div class="mb-6 flex items-center justify-between">
        <div>
            <flux:breadcrumbs>
                <flux:breadcrumbs.item :href="route('dashboard')" wire:navigate>Dashboard</flux:breadcrumbs.item>
                <flux:breadcrumbs.item>{{ $site->original_name ?: $site->slug }}</flux:breadcrumbs.item>
                <flux:breadcrumbs.item>File Manager</flux:breadcrumbs.item>
            </flux:breadcrumbs>
            <h1 class="text-2xl font-bold mt-2">File Manager & Editor</h1>
        </div>
        <div class="flex gap-2">
            <flux:button :href="'/s/' . $site->slug" target="_blank" icon="arrow-top-right-on-square" variant="ghost">View Live Site</flux:button>
            @if($currentFile)
                <flux:button :href="'/s/' . $site->slug . '/' . $currentFile" target="_blank" icon="eye" variant="ghost">Preview Current File</flux:button>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <!-- File Browser Sidebar -->
        <div class="md:col-span-1 space-y-4">
            <flux:card>
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-semibold text-xs uppercase text-zinc-500 tracking-wider">Site Files ({{ count($files) }})</h3>
                    <div class="flex gap-1">
                        <flux:modal.trigger name="create-file-modal">
                            <flux:button variant="ghost" size="xs" icon="plus" tooltip="New File" />
                        </flux:modal.trigger>
                        <flux:button variant="ghost" size="xs" icon="arrow-up-tray" @click="$refs.mgrUploadInput.click()" tooltip="Upload File" />
                        <input type="file" class="hidden" x-ref="mgrUploadInput" wire:model="uploadFile" />
                    </div>
                </div>

                <div class="max-h-[600px] overflow-y-auto pr-1">
                    <ul class="space-y-1">
                        @forelse($files as $file)
                            <li class="group flex items-center justify-between gap-1 rounded-md transition-colors {{ $currentFile === $file ? 'bg-indigo-50 dark:bg-indigo-950/40 text-indigo-600 dark:text-indigo-400 font-medium' : 'hover:bg-zinc-50 dark:hover:bg-zinc-800/60 text-zinc-700 dark:text-zinc-300' }}">
                                <button 
                                    wire:click="selectFile('{{ $file }}')"
                                    class="flex-1 text-left px-3 py-2 text-sm truncate flex items-center gap-2 cursor-pointer"
                                    title="{{ $file }}"
                                >
                                    @if(str_ends_with(strtolower($file), '.html') || str_ends_with(strtolower($file), '.htm'))
                                        <flux:icon.code-bracket class="w-4 h-4 shrink-0 text-indigo-400" />
                                    @elseif(str_ends_with(strtolower($file), '.css'))
                                        <flux:icon.hashtag class="w-4 h-4 shrink-0 text-pink-400" />
                                    @elseif(str_ends_with(strtolower($file), '.js'))
                                        <flux:icon.bolt class="w-4 h-4 shrink-0 text-amber-400" />
                                    @elseif(in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), ['png', 'jpg', 'jpeg', 'gif', 'svg', 'webp', 'ico']))
                                        <flux:icon.photo class="w-4 h-4 shrink-0 text-emerald-400" />
                                    @else
                                        <flux:icon.document-text class="w-4 h-4 shrink-0 text-zinc-400" />
                                    @endif
                                    <span class="truncate font-mono text-xs">{{ $file }}</span>
                                </button>

                                <flux:dropdown>
                                    <flux:button variant="ghost" size="xs" icon="ellipsis-vertical" class="opacity-0 group-hover:opacity-100 transition-opacity" />
                                    <flux:menu>
                                        <flux:menu.item icon="pencil-square" wire:click="startRename('{{ $file }}')">Rename</flux:menu.item>
                                        <flux:menu.item icon="trash" wire:click="deleteFile('{{ $file }}')" wire:confirm="Are you sure you want to delete '{{ $file }}'?" class="text-red-600">Delete</flux:menu.item>
                                    </flux:menu>
                                </flux:dropdown>
                            </li>
                        @empty
                            <div class="text-xs text-zinc-500 py-8 text-center">
                                No files found.<br>
                                <button class="mt-2 text-indigo-500 underline" @click="$refs.mgrUploadInput.click()">Upload a file</button>
                            </div>
                        @endforelse
                    </ul>
                </div>
            </flux:card>
        </div>

        <!-- Editor Area -->
        <flux:card class="md:col-span-3 flex flex-col min-h-[650px] p-4">
            @if($currentFile)
                <div class="flex items-center justify-between mb-4 pb-3 border-b border-zinc-200 dark:border-zinc-800">
                    <div class="font-medium flex items-center gap-2 font-mono text-sm">
                        <flux:icon.document-text class="w-4 h-4 text-indigo-400" />
                        <span>{{ $currentFile }}</span>
                        @if($isBinary)
                            <flux:badge size="xs" color="zinc">Binary / Asset File</flux:badge>
                        @endif
                    </div>
                    <div class="flex gap-2 items-center">
                        @if(!$isBinary)
                            <flux:button wire:click="saveFile" variant="primary" size="sm" icon="check">Save Changes</flux:button>
                        @endif
                    </div>
                </div>
                
                @if($isBinary)
                    <div class="flex-1 flex flex-col items-center justify-center p-8 bg-zinc-50 dark:bg-zinc-900 rounded-lg border border-zinc-200 dark:border-zinc-800">
                        @if(in_array(strtolower(pathinfo($currentFile, PATHINFO_EXTENSION)), ['png', 'jpg', 'jpeg', 'gif', 'svg', 'webp', 'ico']))
                            <img src="{{ Storage::disk('public')->url("{$site->path}/{$currentFile}") }}" alt="{{ $currentFile }}" class="max-h-80 max-w-full rounded border border-zinc-200 dark:border-zinc-700 shadow-sm mb-4 object-contain" />
                            <p class="text-xs font-mono text-zinc-500">{{ $currentFile }}</p>
                        @else
                            <flux:icon.document class="w-16 h-16 text-zinc-400 mb-4" />
                            <p class="text-sm font-medium mb-1">{{ $currentFile }}</p>
                            <p class="text-xs text-zinc-500 mb-4">Binary file format cannot be directly edited in text editor.</p>
                            <a href="{{ Storage::disk('public')->url("{$site->path}/{$currentFile}") }}" download="{{ basename($currentFile) }}" class="inline-flex items-center gap-2 px-3 py-1.5 bg-zinc-200 dark:bg-zinc-800 hover:bg-zinc-300 dark:hover:bg-zinc-700 rounded text-xs font-medium">
                                <flux:icon.arrow-down-tray class="w-3.5 h-3.5" /> Download File
                            </a>
                        @endif
                    </div>
                @else
                    <div 
                        class="flex-1 relative" 
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
                        <textarea 
                            wire:model="fileContent" 
                            class="w-full h-full min-h-[580px] p-4 font-mono text-xs md:text-sm bg-zinc-50 dark:bg-[#0c0c0e] text-zinc-800 dark:text-zinc-200 border border-zinc-200 dark:border-zinc-800 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none resize-none leading-relaxed"
                            spellcheck="false"
                            placeholder="Start typing your code here..."
                        ></textarea>
                    </div>
                @endif
            @else
                <div class="flex-1 flex flex-col items-center justify-center text-zinc-500 py-24">
                    <flux:icon.document-text class="w-12 h-12 mb-4 text-zinc-300 dark:text-zinc-700" />
                    <p class="text-sm">Select a file from the sidebar to edit or create a new file.</p>
                </div>
            @endif
        </flux:card>
    </div>

    <!-- Create File Modal -->
    <flux:modal name="create-file-modal" class="md:w-[420px]">
        <form wire:submit="createFile" class="space-y-6">
            <div>
                <flux:heading size="lg">Create New File</flux:heading>
                <flux:subheading>Enter a file name or path (e.g. <code>about.html</code> or <code>css/styles.css</code>).</flux:subheading>
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

    <!-- Rename File Modal -->
    <flux:modal name="rename-file-modal" class="md:w-[420px]">
        <form wire:submit="renameFile" class="space-y-6">
            <div>
                <flux:heading size="lg">Rename File</flux:heading>
                <flux:subheading>Enter a new name for <code>{{ $oldFileName }}</code>.</flux:subheading>
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