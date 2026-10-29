    @if($publishedSlug)
        <!-- Success State Card -->
        <div class="p-6 dashboard-rounded dashboard-success-tint  border dashboard-success-border  text-center space-y-4">
            <div class="size-14 rounded-full dashboard-success-tint dashboard-success  flex items-center justify-center mx-auto">
                <flux:icon.check-circle class="size-8" />
            </div>

            <div>
                <h3 class="dashboard-section-title     ">Site Published Successfully!</h3>
                <p class="text-xs dashboard-muted  mt-1">Your static site is deployed and ready to share worldwide.</p>
            </div>

            <div class="p-3 dashboard-surface  dashboard-control-rounded border dashboard-success-border  flex items-center justify-between gap-3 text-xs font-mono">
                <a href="{{ $publishedUrl }}" target="_blank" class="dashboard-accent  hover:underline truncate">
                    {{ $publishedUrl }}
                </a>
                <button
                    type="button"
                    @click="navigator.clipboard.writeText('{{ $publishedUrl }}'); Flux.toast('URL copied to clipboard!')"
                    class="p-1.5 rounded-md dashboard-hover-surface  dashboard-muted dashboard-hover-text  transition-colors shrink-0"
                    title="Copy URL"
                >
                    <flux:icon.clipboard class="size-4" />
                </button>
            </div>

            <div class="flex flex-wrap items-center justify-center gap-2 pt-2">
                <a href="{{ $publishedUrl }}" target="_blank" class="dashboard-button dashboard-button-primary text-xs px-4 py-2">
                    <flux:icon.arrow-top-right-on-square class="size-3.5" />
                    Open Live Site
                </a>
                @if(auth()->check() && $publishedId)
                    <a href="{{ route('dashboard.sites.edit', $publishedId) }}" class="dashboard-button text-xs px-4 py-2">
                        <flux:icon.code-bracket class="size-3.5" />
                        Code Editor
                    </a>
                @endif
                <button wire:click="resetUpload" class="inline-flex items-center gap-1.5 px-3 py-2 dashboard-control-rounded dashboard-muted dashboard-hover-text  text-xs font-medium transition-colors cursor-pointer">
                    Deploy Another Site
                </button>
            </div>
        </div>
    @else
        <!-- Upload Dropzone -->
        <div
            :class="{
                'dashboard-accent-border dashboard-accent-tint  scale-[1.01] ': dragging,
                'dashboard-border  dashboard-surface  dashboard-accent-border  dashboard-hover-surface ': !dragging
            }"
            class="flex flex-col items-center justify-center w-full min-h-[260px] p-8 border-2 border-dashed dashboard-rounded cursor-pointer transition-all duration-200"
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
                    <div class="size-14 dashboard-rounded dashboard-accent-tint  dashboard-accent  flex items-center justify-center mb-4">
                        <flux:icon.cloud-arrow-up class="size-7" />
                    </div>
                    <div class="text-sm font-medium tracking-tight dashboard-text ">
                        Drop your HTML or ZIP here
                    </div>
                    <p class="text-xs dashboard-muted  mt-1 max-w-xs">
                        Or click to browse. Supports <code class="font-mono dashboard-accent  font-medium">.html</code> files or complete <code class="font-mono dashboard-accent  font-medium">.zip</code> archives up to 50MB
                    </p>

                    <div class="flex items-center gap-2 mt-4 text-[11px] dashboard-muted ">
                        <span class="px-2 py-0.5 rounded-full dashboard-surface  border dashboard-border ">Instant SSL</span>
                        <span class="px-2 py-0.5 rounded-full dashboard-surface  border dashboard-border ">Edge CDN</span>
                        <span class="px-2 py-0.5 rounded-full dashboard-surface  border dashboard-border ">Automatic Unpack</span>
                    </div>
                </div>
            </template>

            <template x-if="uploading">
                <div class="flex flex-col items-center justify-center w-full max-w-sm px-4">
                    <div class="size-12 rounded-full dashboard-accent-tint  dashboard-accent  flex items-center justify-center mb-3 animate-spin">
                        <flux:icon.arrow-path class="size-6" />
                    </div>
                    <div class="text-xs font-medium dashboard-text  mb-2">Deploying package... <span x-text="progress"></span>%</div>
                    <div class="w-full dashboard-surface  rounded-full h-2 overflow-hidden">
                        <div class="dashboard-accent-tint h-2 transition-all duration-200 rounded-full" :style="`width: ${progress}%`"></div>
                    </div>
                </div>
            </template>

            <input type="file" class="hidden" x-ref="fileInput" @change="handleSelect($event)" accept=".html,.htm,.zip" />
        </div>
    @endif

    @if ($errorMessage)
        <div class="mt-4 p-3.5 dashboard-danger-tint  dashboard-danger  dashboard-control-rounded border dashboard-danger-border  flex items-center justify-between text-xs">
            <span>{{ $errorMessage }}</span>
            <button type="button" wire:click="$set('errorMessage', '')" class="dashboard-danger dashboard-danger  cursor-pointer">
                <flux:icon.x-mark class="size-4" />
            </button>
        </div>
    @endif
