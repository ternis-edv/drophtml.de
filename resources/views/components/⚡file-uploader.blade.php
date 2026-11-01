<?php

use App\Services\SiteDeploymentService;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Component;
use Livewire\WithFileUploads;

new class extends Component
{
    use WithFileUploads;

    #[\Livewire\Attributes\Locked]
    public bool $dashboard = false;

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
        // Consume the named "uploads" rate limiter (20/min per user or IP)
        // manually, since Livewire updates don't pass through route middleware.
        $limit = RateLimiter::limiter('uploads')(request());

        if (RateLimiter::tooManyAttempts($limit->key, $limit->maxAttempts)) {
            $this->errorMessage = 'Too many uploads. Please wait a minute before trying again.';

            return;
        }
        RateLimiter::hit($limit->key, $limit->decaySeconds);

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
            if (this.uploading || this.$wire.uploading) return;
            this.uploading = true;
            this.progress = 0;
            @this.upload('file', file, (uploadedName) => {
                this.uploading = false;
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
    @if($dashboard)
        @include('components.dashboard.upload-form')
    @else
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
        <div class="lp-dropzone"
            :class="{ 'is-dragging': dragging }"
            role="button" tabindex="0"
            aria-label="Choose an HTML or ZIP file to deploy"
            :aria-busy="uploading || $wire.uploading"
            @keydown.enter.prevent="if (!uploading && !$wire.uploading) $refs.fileInput.click()"
            @keydown.space.prevent="if (!uploading && !$wire.uploading) $refs.fileInput.click()"
            @click="if (!uploading && !$wire.uploading) $refs.fileInput.click()"
        >
            <template x-if="!uploading && !$wire.uploading">
                <div>
                    <span class="lp-dropzone__icon" style="margin-inline: auto"><flux:icon.arrow-up-tray /></span>
                    <strong>Drop your HTML or ZIP here</strong>
                    <p>A single page or your whole static project.</p>
                    <span class="lp-dropzone__browse">Choose a file <span aria-hidden="true">&nbsp;↗</span></span>
                    <p>Your file publishes as soon as you choose it.</p>
                </div>
            </template>
            <template x-if="uploading || $wire.uploading">
                <div class="lp-dropzone__progress" role="status" aria-live="polite">
                    <strong x-text="progress < 100 ? 'Uploading your files…' : 'Publishing your site…'"></strong>
                    <p x-text="progress < 100 ? progress + '% uploaded' : 'Hang tight. Your live link is on its way.'"></p>
                    <progress max="100" :value="progress" aria-label="File upload progress"></progress>
                </div>
            </template>
            <input type="file" class="hidden" x-ref="fileInput" @click.stop @change="handleSelect($event); $event.target.value = ''" accept=".html,.htm,.zip" />
        </div>
    @endif

    @if ($errorMessage)
        <div role="alert" class="mt-4 p-3.5 bg-red-50 dark:bg-red-950/40 text-red-700 dark:text-red-300 rounded-xl border border-red-200 dark:border-red-800 flex items-center justify-between text-xs">
            <span>{{ $errorMessage }}</span>
            <button type="button" aria-label="Dismiss upload error" wire:click="$set('errorMessage', '')" class="text-red-500 hover:text-red-700 dark:hover:text-red-300 cursor-pointer">
                <flux:icon.x-mark class="size-4" />
            </button>
        </div>
    @endif
    @endif
</div>
