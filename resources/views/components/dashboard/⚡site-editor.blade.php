<?php

use Livewire\Component;
use App\Models\Site;
use Illuminate\Support\Facades\Storage;

new class extends Component
{
    public $siteId;
    public $site;
    public $files = [];
    public $currentFile = null;
    public $fileContent = '';
    
    public function mount($siteId)
    {
        $this->siteId = $siteId;
        $this->site = auth()->user()->sites()->findOrFail($this->siteId);
        $this->loadFiles();
    }
    
    public function loadFiles()
    {
        if (Storage::disk('public')->exists($this->site->path)) {
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
        
        \App\Models\ActivityLog::create([
            'user_id' => auth()->id(),
            'site_id' => $this->site->id,
            'action' => 'file_edited',
            'description' => "Edited file: {$this->currentFile}",
            'ip_address' => request()->ip(),
        ]);

        Flux::toast('File saved successfully.');
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
                <flux:breadcrumbs.item :href="route('dashboard')">Dashboard</flux:breadcrumbs.item>
                <flux:breadcrumbs.item>{{ $site->original_name ?: $site->slug }}</flux:breadcrumbs.item>
                <flux:breadcrumbs.item>Editor</flux:breadcrumbs.item>
            </flux:breadcrumbs>
            <h1 class="text-2xl font-bold mt-2">File Editor</h1>
        </div>
        <flux:button :href="'/s/' . $site->slug" target="_blank" icon="arrow-top-right-on-square">View Live</flux:button>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <!-- File Browser -->
        <flux:card class="md:col-span-1">
            <h3 class="font-semibold mb-4 text-sm uppercase text-zinc-500 tracking-wider">Files</h3>
            <ul class="space-y-1">
                @forelse($files as $file)
                    <li>
                        <button 
                            wire:click="selectFile('{{ $file }}')"
                            class="w-full text-left px-3 py-2 rounded-md text-sm {{ $currentFile === $file ? 'bg-zinc-100 dark:bg-zinc-800 font-medium' : 'hover:bg-zinc-50 dark:hover:bg-zinc-800/50' }}"
                        >
                            <flux:icon.document-text class="inline-block w-4 h-4 mr-2 text-zinc-400" />
                            {{ $file }}
                        </button>
                    </li>
                @empty
                    <div class="text-sm text-zinc-500">No files found.</div>
                @endforelse
            </ul>
        </flux:card>

        <!-- Editor Area -->
        <flux:card class="md:col-span-3 flex flex-col min-h-[500px]">
            @if($currentFile)
                <div class="flex items-center justify-between mb-4 pb-4 border-b border-zinc-200 dark:border-zinc-800">
                    <div class="font-medium flex items-center gap-2">
                        <flux:icon.document-text class="w-5 h-5 text-zinc-400" />
                        {{ $currentFile }}
                    </div>
                    <flux:button wire:click="saveFile" variant="primary" size="sm" icon="check">Save Changes</flux:button>
                </div>
                
                <div class="flex-1 relative">
                    <!-- Basic textarea editor. Consider integrating Monaco or CodeMirror later -->
                    <textarea 
                        wire:model="fileContent" 
                        class="w-full h-full min-h-[400px] p-4 font-mono text-sm bg-zinc-50 dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-lg focus:ring-2 focus:ring-accent outline-none resize-y"
                        spellcheck="false"
                    ></textarea>
                </div>
            @else
                <div class="flex-1 flex flex-col items-center justify-center text-zinc-500">
                    <flux:icon.document class="w-12 h-12 mb-4 text-zinc-300 dark:text-zinc-700" />
                    <p>Select a file from the sidebar to start editing.</p>
                </div>
            @endif
        </flux:card>
    </div>
</div>