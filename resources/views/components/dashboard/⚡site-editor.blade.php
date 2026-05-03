<?php

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\Site;
use Illuminate\Support\Facades\Storage;
use App\Models\ActivityLog;

new class extends Component
{
    use WithFileUploads;

    public $siteId;
    public $site;
    public $files = [];
    public $currentFile = null;
    public $fileContent = '';
    
    public $newFileName = '';
    public $uploadFile = null;
    
    public function mount($siteId)
    {
        $this->siteId = $siteId;
        $this->site = auth()->user()->sites()->findOrFail($this->siteId);
        $this->loadFiles();
    }
    
    public function loadFiles()
    {
        if (Storage::disk('public')->exists($this->site->path)) {
            // Get all files recursively if needed, but let's stick to flat for now
            $this->files = array_map(function ($file) {
                return basename($file);
            }, Storage::disk('public')->files($this->site->path));
        }
    }

    public function selectFile($filename)
    {
        $this->currentFile = $filename;
        $path = "{$this->site->path}/{$filename}";
        
        if (Storage::disk('public')->exists($path)) {
            $this->fileContent = Storage::disk('public')->get($path);
        }
    }

    public function saveFile()
    {
        if (!$this->currentFile) return;

        $path = "{$this->site->path}/{$this->currentFile}";
        Storage::disk('public')->put($path, $this->fileContent);
        
        ActivityLog::create([
            'user_id' => auth()->id(),
            'site_id' => $this->site->id,
            'action' => 'file_edited',
            'description' => "Edited file: {$this->currentFile}",
            'ip_address' => request()->ip(),
        ]);

        Flux::toast('File saved successfully.');
    }

    public function createFile()
    {
        $this->validate(['newFileName' => 'required|string']);
        
        if (!str_contains($this->newFileName, '.')) {
            $this->newFileName .= '.html';
        }

        $path = "{$this->site->path}/{$this->newFileName}";
        
        if (Storage::disk('public')->exists($path)) {
            Flux::toast(variant: 'danger', text: 'File already exists.');
            return;
        }

        Storage::disk('public')->put($path, '');
        
        ActivityLog::create([
            'user_id' => auth()->id(),
            'site_id' => $this->site->id,
            'action' => 'file_created',
            'description' => "Created file: {$this->newFileName}",
            'ip_address' => request()->ip(),
        ]);

        $this->loadFiles();
        $this->selectFile($this->newFileName);
        $this->newFileName = '';
        Flux::modal('create-file-modal')->close();
        Flux::toast('File created.');
    }

    public function deleteFile($filename)
    {
        $path = "{$this->site->path}/{$filename}";
        Storage::disk('public')->delete($path);
        
        ActivityLog::create([
            'user_id' => auth()->id(),
            'site_id' => $this->site->id,
            'action' => 'file_deleted',
            'description' => "Deleted file: {$filename}",
            'ip_address' => request()->ip(),
        ]);

        if ($this->currentFile === $filename) {
            $this->currentFile = null;
            $this->fileContent = '';
        }

        $this->loadFiles();
        Flux::toast('File deleted.');
    }

    public function updatedUploadFile()
    {
        if (!$this->uploadFile) return;

        $filename = $this->uploadFile->getClientOriginalName();
        $path = "{$this->site->path}/{$filename}";
        
        $this->uploadFile->storeAs($this->site->path, $filename, 'public');
        
        ActivityLog::create([
            'user_id' => auth()->id(),
            'site_id' => $this->site->id,
            'action' => 'file_uploaded_to_manager',
            'description' => "Uploaded file to manager: {$filename}",
            'ip_address' => request()->ip(),
        ]);

        $this->loadFiles();
        $this->selectFile($filename);
        $this->uploadFile = null;
        Flux::toast('File uploaded.');
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
                <flux:breadcrumbs.item>Editor</flux:breadcrumbs.item>
            </flux:breadcrumbs>
            <h1 class="text-2xl font-bold mt-2">File Manager</h1>
        </div>
        <flux:button :href="'/s/' . $site->slug" target="_blank" icon="arrow-top-right-on-square">View Live</flux:button>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <!-- File Browser -->
        <div class="md:col-span-1 space-y-4">
            <flux:card>
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-semibold text-sm uppercase text-zinc-500 tracking-wider">Files</h3>
                    <div class="flex gap-1">
                        <flux:modal.trigger name="create-file-modal">
                            <flux:button variant="ghost" size="xs" icon="plus" />
                        </flux:modal.trigger>
                        <flux:button variant="ghost" size="xs" icon="arrow-up-tray" @click="$refs.mgrUploadInput.click()" />
                        <input type="file" class="hidden" x-ref="mgrUploadInput" wire:model="uploadFile" />
                    </div>
                </div>

                <ul class="space-y-1">
                    @forelse($files as $file)
                        <li class="group flex items-center justify-between gap-2">
                            <button 
                                wire:click="selectFile('{{ $file }}')"
                                class="flex-1 text-left px-3 py-2 rounded-md text-sm truncate {{ $currentFile === $file ? 'bg-zinc-100 dark:bg-zinc-800 font-medium' : 'hover:bg-zinc-50 dark:hover:bg-zinc-800/50' }}"
                            >
                                <flux:icon.document-text class="inline-block w-4 h-4 mr-2 text-zinc-400" />
                                {{ $file }}
                            </button>
                            <flux:dropdown>
                                <flux:button variant="ghost" size="xs" icon="ellipsis-vertical" class="opacity-0 group-hover:opacity-100 transition-opacity" />
                                <flux:menu>
                                    <flux:menu.item icon="trash" wire:click="deleteFile('{{ $file }}')" wire:confirm="Delete this file?" class="text-red-600">Delete</flux:menu.item>
                                </flux:menu>
                            </flux:dropdown>
                        </li>
                    @empty
                        <div class="text-sm text-zinc-500 py-4 text-center">No files found.</div>
                    @endforelse
                </ul>
            </flux:card>
        </div>

        <!-- Editor Area -->
        <flux:card class="md:col-span-3 flex flex-col min-h-[600px]">
            @if($currentFile)
                <div class="flex items-center justify-between mb-4 pb-4 border-b border-zinc-200 dark:border-zinc-800">
                    <div class="font-medium flex items-center gap-2">
                        <flux:icon.document-text class="w-5 h-5 text-zinc-400" />
                        {{ $currentFile }}
                    </div>
                    <div class="flex gap-2">
                        <flux:button wire:click="saveFile" variant="primary" size="sm" icon="check">Save Changes</flux:button>
                    </div>
                </div>
                
                <div class="flex-1 relative">
                    <textarea 
                        wire:model="fileContent" 
                        class="w-full h-full min-h-[500px] p-4 font-mono text-sm bg-zinc-50 dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-lg focus:ring-2 focus:ring-accent outline-none resize-y"
                        spellcheck="false"
                    ></textarea>
                </div>
            @else
                <div class="flex-1 flex flex-col items-center justify-center text-zinc-500">
                    <flux:icon.document class="w-12 h-12 mb-4 text-zinc-300 dark:text-zinc-700" />
                    <p>Select a file to edit or create a new one.</p>
                </div>
            @endif
        </flux:card>
    </div>

    <!-- Create File Modal -->
    <flux:modal name="create-file-modal" class="md:w-[400px]">
        <form wire:submit="createFile" class="space-y-6">
            <div>
                <flux:heading size="lg">New File</flux:heading>
                <flux:subheading>Enter a name for your new file.</flux:subheading>
            </div>

            <flux:input wire:model="newFileName" placeholder="index.html" autofocus />

            <div class="flex gap-2 justify-end">
                <flux:modal.close>
                    <flux:button variant="ghost">Cancel</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary">Create File</flux:button>
            </div>
        </form>
    </flux:modal>
</div>