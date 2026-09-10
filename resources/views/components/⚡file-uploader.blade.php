<?php

use App\Services\SiteDeploymentService;
use Livewire\Component;
use Livewire\WithFileUploads;

new class extends Component
{
    use WithFileUploads;

    public $file;
    public $uploading = false;
    public $message = '';
    public $errorMessage = '';

    public function updatedFile(SiteDeploymentService $deploymentService)
    {
        $this->validate([
            'file' => 'required|max:51200', // 50MB max
        ]);

        $this->upload($deploymentService);
    }

    public function upload(SiteDeploymentService $deploymentService)
    {
        $this->uploading = true;
        $this->message = '';
        $this->errorMessage = '';

        try {
            $originalName = $this->file->getClientOriginalName();
            $user = auth()->user();

            $site = $deploymentService->createSiteFromUpload(
                $this->file,
                $originalName,
                $user
            );

            $host = parse_url(config('app.url'), PHP_URL_HOST);
            $scheme = parse_url(config('app.url'), PHP_URL_SCHEME) ?? 'http';

            $pathUrl = url("/s/{$site->slug}");
            $subdomainUrl = ($host && $host !== 'localhost') ? "{$scheme}://{$site->slug}.{$host}" : null;

            $this->message = "Site published successfully!";
            if ($subdomainUrl) {
                $this->message .= " Access it at: <a href='{$subdomainUrl}' class='underline font-medium' target='_blank'>{$subdomainUrl}</a> or <a href='{$pathUrl}' class='underline font-medium' target='_blank'>{$pathUrl}</a>";
            } else {
                $this->message .= " Access it at: <a href='{$pathUrl}' class='underline font-medium' target='_blank'>{$pathUrl}</a>";
            }

            $this->dispatch('site-published', slug: $site->slug);
            $this->reset('file');
        } catch (\Throwable $e) {
            $this->errorMessage = $e->getMessage();
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
        init() {
            window.addEventListener('files-dropped', (e) => {
                if (e.detail.files && e.detail.files.length > 0) {
                    this.uploadFiles(e.detail.files[0]);
                }
            });
        },
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

    @if ($errorMessage)
        <div class="mt-4 p-4 bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300 rounded-lg shadow-sm border border-red-200 dark:border-red-800 flex items-center justify-between">
            <div class="flex-1 text-sm font-medium">
                {{ $errorMessage }}
            </div>
            <flux:button variant="ghost" size="sm" icon="x-mark" wire:click="$set('errorMessage', '')" />
        </div>
    @endif
</div>