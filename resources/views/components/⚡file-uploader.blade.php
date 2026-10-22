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
    public $publishedSlug = null;
    public $publishedUrl = null;
    public $publishedId = null;

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

            $this->publishedSlug = $site->slug;
            $this->publishedId = $site->id;
            $this->publishedUrl = url("/s/{$site->slug}");

            $this->message = "Site published successfully! Access it at: <a href='{$this->publishedUrl}' class='underline font-medium' target='_blank'>{$this->publishedUrl}</a>";

            $this->dispatch('site-published', slug: $site->slug);
            $this->reset('file');
        } catch (\Throwable $e) {
            $this->errorMessage = $e->getMessage();
        }

        $this->uploading = false;
    }

    public function resetUpload()
    {
        $this->publishedSlug = null;
        $this->publishedUrl = null;
        $this->publishedId = null;
        $this->message = '';
        $this->errorMessage = '';
        $this->reset('file');
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
                @this.upload();
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
    @if($publishedSlug)
        <!-- Success State Card -->
        <div class="p-6 rounded-2xl bg-emerald-50/70 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-800/60 text-center space-y-4">
            <div class="size-14 rounded-full bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center mx-auto">
                <flux:icon.check-circle class="size-8" />
            </div>

            <div>
                <h3 class="text-lg font-bold tracking-tight text-zinc-900 dark:text-zinc-100">Site Published Successfully!</h3>
                <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">Your static site is deployed and ready to share worldwide.</p>
            </div>

            <div class="p-3 bg-white dark:bg-zinc-900 rounded-xl border border-emerald-200 dark:border-emerald-800/80 flex items-center justify-between gap-3 text-xs font-mono">
                <a href="{{ $publishedUrl }}" target="_blank" class="text-accent dark:text-brand-300 hover:underline truncate">
                    {{ $publishedUrl }}
                </a>
                <button 
                    type="button" 
                    @click="navigator.clipboard.writeText('{{ $publishedUrl }}'); Flux.toast('URL copied to clipboard!')" 
                    class="p-1.5 rounded-md hover:bg-zinc-100 dark:hover:bg-zinc-800 text-zinc-500 hover:text-zinc-900 dark:hover:text-zinc-100 transition-colors shrink-0"
                    title="Copy URL"
                >
                    <flux:icon.clipboard class="size-4" />
                </button>
            </div>

            <div class="flex flex-wrap items-center justify-center gap-2 pt-2">
                <a href="{{ $publishedUrl }}" target="_blank" class="btn-primary text-xs px-4 py-2">
                    <flux:icon.arrow-top-right-on-square class="size-3.5" />
                    Open Live Site
                </a>
                @if(auth()->check() && $publishedId)
                    <a href="{{ route('dashboard.sites.edit', $publishedId) }}" class="btn-secondary text-xs px-4 py-2">
                        <flux:icon.code-bracket class="size-3.5" />
                        Code Editor
                    </a>
                @endif
                <button wire:click="resetUpload" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg text-zinc-500 hover:text-zinc-900 dark:hover:text-zinc-100 text-xs font-medium transition-colors cursor-pointer">
                    Deploy Another Site
                </button>
            </div>
        </div>
    @else
        <!-- Upload Dropzone -->
        <div
            :class="{ 
                'border-accent bg-accent/[0.05] dark:bg-accent/10 scale-[1.01] shadow-md': dragging, 
                'border-zinc-300 dark:border-zinc-700 bg-zinc-50/60 dark:bg-zinc-900/60 hover:border-accent/60 dark:hover:border-accent/50 hover:bg-zinc-100/50 dark:hover:bg-zinc-800/40': !dragging 
            }"
            class="flex flex-col items-center justify-center w-full min-h-[260px] p-8 border-2 border-dashed rounded-2xl cursor-pointer transition-all duration-200"
            role="button"
            tabindex="0"
            aria-label="Choose an HTML or ZIP file to deploy"
            :aria-busy="uploading"
            @keydown.enter.prevent="if (!uploading) $refs.fileInput.click()"
            @keydown.space.prevent="if (!uploading) $refs.fileInput.click()"
            @click="if (!uploading) $refs.fileInput.click()"
        >
            <template x-if="!uploading">
                <div class="flex flex-col items-center justify-center text-center">
                    <div class="size-14 rounded-2xl bg-accent/[0.08] dark:bg-accent/15 text-accent dark:text-brand-300 flex items-center justify-center mb-4">
                        <flux:icon.cloud-arrow-up class="size-7" />
                    </div>
                    <div class="text-sm font-bold tracking-tight text-zinc-800 dark:text-zinc-200">
                        Drop your HTML or ZIP here
                    </div>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1 max-w-xs">
                        Or click to browse. Supports <code class="font-mono text-accent dark:text-brand-300 font-semibold">.html</code> files or complete <code class="font-mono text-accent dark:text-brand-300 font-semibold">.zip</code> archives up to 50MB
                    </p>
                    
                    <div class="flex items-center gap-2 mt-4 text-[11px] text-zinc-500 dark:text-zinc-400">
                        <span class="px-2 py-0.5 rounded-full bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700">Instant SSL</span>
                        <span class="px-2 py-0.5 rounded-full bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700">Edge CDN</span>
                        <span class="px-2 py-0.5 rounded-full bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700">Automatic Unpack</span>
                    </div>
                </div>
            </template>

            <template x-if="uploading">
                <div class="flex flex-col items-center justify-center w-full max-w-sm px-4">
                    <div class="size-12 rounded-full bg-accent/[0.08] dark:bg-accent/15 text-accent dark:text-brand-300 flex items-center justify-center mb-3 animate-spin">
                        <flux:icon.arrow-path class="size-6" />
                    </div>
                    <div class="text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-2">Deploying package... <span x-text="progress"></span>%</div>
                    <div class="w-full bg-zinc-200 dark:bg-zinc-800 rounded-full h-2 overflow-hidden">
                        <div class="bg-accent h-2 transition-all duration-200 rounded-full" :style="`width: ${progress}%`"></div>
                    </div>
                </div>
            </template>

            <input type="file" class="hidden" x-ref="fileInput" @change="handleSelect($event)" accept=".html,.htm,.zip" />
        </div>
    @endif

    @if ($errorMessage)
        <div class="mt-4 p-3.5 bg-red-50 dark:bg-red-950/40 text-red-700 dark:text-red-300 rounded-xl border border-red-200 dark:border-red-800 flex items-center justify-between text-xs">
            <span>{{ $errorMessage }}</span>
            <button type="button" wire:click="$set('errorMessage', '')" class="text-red-500 hover:text-red-700 dark:hover:text-red-300 cursor-pointer">
                <flux:icon.x-mark class="size-4" />
            </button>
        </div>
    @endif
</div>