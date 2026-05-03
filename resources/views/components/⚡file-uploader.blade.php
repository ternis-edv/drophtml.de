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
                    $zip->extractTo(storage_path("app/public/{$path}"));
                    $zip->close();
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
            
            Site::create([
                'slug' => $slug,
                'original_name' => $originalName,
                'path' => $path,
            ]);
            
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
    x-data="{ dragging: false }"
    @dragover.prevent="dragging = true"
    @dragleave.prevent="dragging = false"
    @drop.prevent="dragging = false"
    class="relative"
>
    <div
        :class="{ 'border-blue-500 bg-blue-50 dark:bg-blue-900/20': dragging, 'border-gray-300 dark:border-gray-700': !dragging }"
        class="flex flex-col items-center justify-center w-full h-64 border-2 border-dashed rounded-lg cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
    >
        <label for="dropzone-file" class="flex flex-col items-center justify-center w-full h-full cursor-pointer">
            <div class="flex flex-col items-center justify-center pt-5 pb-6">
                <svg class="w-10 h-10 mb-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                </svg>
                <p class="mb-2 text-sm text-gray-500 dark:text-gray-400"><span class="font-semibold">Click to upload</span> or drag and drop</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">HTML, ZIP or Folder (max. 50MB)</p>
            </div>
            <input id="dropzone-file" type="file" class="hidden" wire:model="file" />
        </label>
    </div>

    @if ($uploading)
        <div class="mt-4 text-center text-blue-500">
            Uploading and publishing...
        </div>
    @endif

    @if ($message)
        <div class="mt-4 p-4 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300 rounded-lg">
            {!! $message !!}
        </div>
    @endif
</div>