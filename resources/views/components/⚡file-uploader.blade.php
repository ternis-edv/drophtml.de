<?php

use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Str;
use App\Models\Site;
use Illuminate\Support\Facades\Storage;

new class extends Component
{
    use WithFileUploads;

    public $file;
    public $uploading = false;
    public $message = '';

    public function updatedFile()
    {
        $this->validate([
            'file' => 'required|max:51200', // 50MB max
        ]);

        $this->upload();
    }

    public function upload()
    {
        $this->uploading = true;
        
        try {
            $originalName = $this->file->getClientOriginalName();
            $extension = $this->file->getClientOriginalExtension();
            
            $slug = Str::random(8);
            $path = "sites/{$slug}";

            if (strtolower($extension) === 'zip') {
                $zip = new \ZipArchive;
                if ($zip->open($this->file->getRealPath()) === TRUE) {
                    $extractPath = storage_path("app/public/{$path}");
                    if (!file_exists($extractPath)) {
                        mkdir($extractPath, 0755, true);
                    }
                    
                    $zip->extractTo($extractPath);
                    $zip->close();

                    // Automatically hoist contents if the ZIP contains a single root directory
                    $items = array_diff(scandir($extractPath), ['.', '..']);
                    if (count($items) === 1) {
                        $innerDir = $extractPath . '/' . array_shift($items);
                        if (is_dir($innerDir)) {
                            $files = array_diff(scandir($innerDir), ['.', '..']);
                            foreach ($files as $file) {
                                rename("{$innerDir}/{$file}", "{$extractPath}/{$file}");
                            }
                            rmdir($innerDir);
                        }
                    }
                } else {
                    throw new \Exception('Could not open ZIP file');
                }
            } else {
                // Save single file
                $this->file->storeAs($path, $originalName, 'public');
                
                // If it's a single html file but not named index.html, 
                // we might want to allow accessing it directly or rename it.
                // For now, SiteController handles index.html default.
            }
            
            $site = Site::create([
                'slug' => $slug,
                'original_name' => $originalName,
                'path' => $path,
                'user_id' => auth()->id(),
                'expires_at' => auth()->check() ? now()->addDays(30) : now()->addHours(24),
            ]);

            if (auth()->check()) {
                \App\Models\ActivityLog::create([
                    'user_id' => auth()->id(),
                    'site_id' => $site->id,
                    'action' => 'site_uploaded',
                    'description' => "Uploaded site: {$originalName}",
                    'ip_address' => request()->ip(),
                ]);
            }
            
            $host = parse_url(config('app.url'), PHP_URL_HOST);
            $scheme = parse_url(config('app.url'), PHP_URL_SCHEME) ?? 'http';
            
            $pathUrl = url("/s/{$slug}");
            $subdomainUrl = ($host && $host !== 'localhost') ? "{$scheme}://{$slug}.{$host}" : null;

            $this->message = "Site published!";
            if ($subdomainUrl) {
                $this->message .= " Access it at: <a href='{$subdomainUrl}' class='underline' target='_blank'>{$subdomainUrl}</a> or <a href='{$pathUrl}' class='underline' target='_blank'>{$pathUrl}</a>";
            } else {
                $this->message .= " Access it at: <a href='{$pathUrl}' class='underline' target='_blank'>{$pathUrl}</a>";
            }
            
            $this->dispatch('site-published', slug: $slug);
        } catch (\Exception $e) {
            $this->message = "Error: " . $e->getMessage();
        }

        $this->uploading = false;
    }
};
?>

<div 
    x-data="{ 
        dragging: false,
        uploading: false,
        progress: 0,
        handleDrop(e) {
            let files = e.dataTransfer.files;
            if (files.length > 0) {
                this.uploadFiles(files[0]);
            }
        },
        handleSelect(e) {
            let files = e.target.files;
            if (files.length > 0) {
                this.uploadFiles(files[0]);
            }
        },
        uploadFiles(file) {
            this.uploading = true;
            this.progress = 0;
            @this.upload('file', file, (uploadedName) => {
                this.uploading = false;
                @this.upload(); // Trigger the backend upload logic
            }, () => {
                this.uploading = false;
                Flux.toast({ variant: 'danger', text: 'Upload failed.' });
            }, (event) => {
                this.progress = event.detail.progress;
            });
        }
    }"
    @dragover.prevent="dragging = true"
    @dragleave.prevent="dragging = false"
    @drop.prevent="dragging = false; handleDrop($event)"
    class="relative"
>
    <div
        :class="{ 'border-blue-500 bg-blue-50 dark:bg-blue-900/20': dragging, 'border-gray-300 dark:border-gray-700': !dragging }"
        class="flex flex-col items-center justify-center w-full h-64 border-2 border-dashed rounded-lg cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
        @click="$refs.fileInput.click()"
    >
        <div class="flex flex-col items-center justify-center pt-5 pb-6">
            <template x-if="!uploading">
                <div class="flex flex-col items-center justify-center">
                    <flux:icon.cloud-arrow-up class="w-12 h-12 mb-4 text-zinc-400" />
                    <p class="mb-2 text-sm text-gray-500 dark:text-gray-400 font-medium">Click or drag and drop your files</p>
                    <p class="text-xs text-gray-400 dark:text-gray-500">HTML, ZIP or Folder (max. 50MB)</p>
                </div>
            </template>

            <template x-if="uploading">
                <div class="flex flex-col items-center justify-center w-full px-12">
                    <div class="w-full bg-zinc-200 dark:bg-zinc-700 rounded-full h-2 mb-4 overflow-hidden">
                        <div class="bg-blue-600 h-2 transition-all duration-300" :style="`width: ${progress}%`"></div >
                    </div>
                    <p class="text-sm text-zinc-500">Uploading... <span x-text="progress"></span>%</p>
                </div>
            </template>
        </div>
        <input type="file" class="hidden" x-ref="fileInput" @change="handleSelect($event)" />
    </div>

    @if ($message)
        <div class="mt-4 p-4 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300 rounded-lg shadow-sm border border-green-200 dark:border-green-800 flex items-center justify-between">
            <div class="flex-1">
                {!! $message !!}
            </div>
            <flux:button variant="ghost" size="sm" icon="x-mark" wire:click="$set('message', '')" />
        </div>
    @endif
</div>