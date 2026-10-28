<div class="media-library" x-data="mediaLibrary" @media-preview-opened.window="$nextTick(() => { if (window.innerWidth < 1100) $refs.inspector?.scrollIntoView({ behavior: 'auto', block: 'start' }); })">
    @if($site)
        <x-site-subnav :site="$site" current="media" />
    @else
        <div class="site-page-heading"><div class="site-page-eyebrow">YOUR CREATIVE TOOLKIT</div><h1>Media library</h1><p>All your images, videos, audio, and documents. Ready for their next appearance.</p><span class="sr-only">Static Media Hosting</span></div>
    @endif
    <div class="media-summary">
        <div><span class="media-summary-label">TOTAL ASSETS</span><strong>{{ $stats['total_count'] }}<small>files</small></strong></div>
        <div><span class="media-summary-label">STORAGE USED</span><strong>{{ \App\Models\MediaAsset::formatBytes($stats['total_bytes']) }}</strong></div>
        <div><span class="media-summary-label">CLOUD / SITE BUNDLE</span><strong>{{ $stats['cloud_count'] }}<small>/</small>{{ $stats['code_count'] }}</strong></div>
        <flux:modal.trigger name="upload-media-modal"><button class="studio-button studio-button-primary"><flux:icon.arrow-up-tray />Upload media</button></flux:modal.trigger>
    </div>
    <div class="media-toolbar">
        <div class="media-type-filters" role="group" aria-label="Filter media by type">
            @foreach(['all' => 'All assets', 'image' => 'Images', 'video' => 'Video', 'audio' => 'Audio', 'document' => 'Documents'] as $type => $label)
                <button wire:click="setTypeFilter('{{ $type }}')" aria-pressed="{{ $typeFilter === $type ? 'true' : 'false' }}">{{ $label }}</button>
            @endforeach
        </div>
        <div class="media-search-tools">
            <label class="studio-search"><flux:icon.magnifying-glass /><input wire:model.live.debounce.200ms="search" aria-label="Search media by name or path" placeholder="Search your library…" /></label>
            <select class="media-select" wire:model.live="driverFilter" aria-label="Filter by storage engine"><option value="all">All storage</option><option value="asset_storage">Cloud storage</option><option value="code">Site bundle</option></select>
            <div class="studio-segment" role="group" aria-label="Media layout"><button wire:click="setViewMode('grid')" aria-pressed="{{ $viewMode === 'grid' ? 'true' : 'false' }}" class="{{ $viewMode === 'grid' ? 'is-active' : '' }}" aria-label="Grid view"><flux:icon.squares-2x2 /></button><button wire:click="setViewMode('list')" aria-pressed="{{ $viewMode === 'list' ? 'true' : 'false' }}" class="{{ $viewMode === 'list' ? 'is-active' : '' }}" aria-label="List view"><flux:icon.bars-3 /></button></div>
        </div>
    </div>
    <div class="media-feedback" role="status" aria-live="polite"><span x-show="copied" x-cloak>Copied to clipboard.</span><span x-text="copyError" class="media-copy-error"></span><span wire:loading wire:target="search,driverFilter,setTypeFilter,gotoPage,nextPage,previousPage">Updating library…</span></div>
    <div class="media-workspace">
        <section class="media-results" aria-label="Media assets">
            <div class="media-results-heading"><span>{{ $assets->total() }} {{ $assets->total() === 1 ? 'asset' : 'assets' }}{{ $search || $typeFilter !== 'all' || $driverFilter !== 'all' ? ' matching your filters' : ' in your library' }}</span><span>SELECT AN ASSET TO PREVIEW & EMBED</span></div>
            <div class="media-grid {{ $viewMode === 'list' ? 'media-list' : '' }}">
                @forelse($assets as $asset)
                    <article class="media-card {{ $previewAssetId === $asset->id ? 'is-selected' : '' }}" wire:key="media-{{ $asset->id }}">
                        <button class="media-thumbnail" wire:click="showPreview('{{ $asset->id }}')" aria-label="Preview {{ $asset->original_name }}" aria-pressed="{{ $previewAssetId === $asset->id ? 'true' : 'false' }}">
                            @if($asset->isImage())
                                <img src="{{ $asset->resolved_url }}" alt="" loading="lazy" />
                            @else
                                <div class="media-file-art type-{{ $asset->file_type }}"><flux:icon :name="$asset->isVideo() ? 'play' : ($asset->isAudio() ? 'musical-note' : 'document-text')" /><span>{{ strtoupper(pathinfo($asset->original_name, PATHINFO_EXTENSION) ?: 'FILE') }}</span></div>
                            @endif
                            <span class="media-storage-badge">{{ $asset->storage_driver === 'code' ? 'SITE BUNDLE' : 'CLOUD' }}</span>
                        </button>
                        <div class="media-card-info"><button wire:click="showPreview('{{ $asset->id }}')" title="{{ $asset->original_name }}">{{ $asset->original_name }}</button><div><span>{{ $asset->formatted_size }}</span><span>·</span><span>{{ $asset->site?->original_name ?: ($asset->site?->slug ?? 'Shared asset') }}</span></div></div>
                        <div class="media-card-actions">
                            <button class="media-copy-link" data-url="{{ $asset->resolved_url }}" @click="copy($el.dataset.url, '{{ $asset->id }}')" aria-label="Copy URL for {{ $asset->original_name }}"><flux:icon.link /><span x-text="copied === '{{ $asset->id }}' ? 'Copied!' : 'Copy URL'"></span></button>
                            <button class="studio-icon-button" wire:click="showPreview('{{ $asset->id }}')" aria-label="Embed {{ $asset->original_name }}" title="Preview & embed"><flux:icon.code-bracket /></button>
                        </div>
                    </article>
                @empty
                    <div class="media-empty"><span class="media-empty-symbol"><flux:icon.photo /></span><h2>{{ $search || $typeFilter !== 'all' || $driverFilter !== 'all' ? 'No matching assets' : 'Make room for your next idea.' }}</h2><p>{{ $search || $typeFilter !== 'all' || $driverFilter !== 'all' ? 'Try a different search or reset your filters.' : 'Bring your images, videos, and documents together. Upload once, then copy a link or embed them anywhere.' }}</p>
                        @if($search || $typeFilter !== 'all' || $driverFilter !== 'all')<button class="studio-button" @click="$wire.set('search', ''); $wire.setTypeFilter('all'); $wire.setDriverFilter('all')">Reset filters</button>@else<flux:modal.trigger name="upload-media-modal"><button class="studio-button studio-button-primary"><flux:icon.plus />Upload your first asset</button></flux:modal.trigger>@endif
                    </div>
                @endforelse
            </div>
            <div class="media-pagination">{{ $assets->links() }}</div>
        </section>
        <aside class="media-inspector" x-ref="inspector" aria-label="Asset details and embed code">
            @if($previewAsset)
                <div wire:key="inspector-{{ $previewAsset->id }}" x-data="{ snippet: 'html' }" x-init="copied = null; copyError = ''">
                    <div class="media-inspector-heading"><span>ASSET INSPECTOR</span><button class="studio-icon-button" wire:click="closePreview" aria-label="Close asset inspector"><flux:icon.x-mark /></button></div>
                    <div class="media-inspector-preview">
                        @if($previewAsset->isImage())<img src="{{ $previewAsset->resolved_url }}" alt="{{ $previewAsset->original_name }}" />
                        @elseif($previewAsset->isVideo())<video controls preload="metadata" src="{{ $previewAsset->resolved_url }}" aria-label="Video preview"></video>
                        @elseif($previewAsset->isAudio())<div class="media-audio-preview"><flux:icon.musical-note /><audio controls preload="metadata" src="{{ $previewAsset->resolved_url }}" aria-label="Audio preview"></audio></div>
                        @else<div class="media-document-preview"><flux:icon.document-text /><span>{{ $previewAsset->isPdf() ? 'PDF document' : 'Downloadable file' }}</span><a class="studio-button" href="{{ $previewAsset->resolved_url }}" target="_blank" rel="noopener">Open file<flux:icon.arrow-top-right-on-square /></a></div>@endif
                    </div>
                    <div class="media-inspector-content">
                        <h2>{{ $previewAsset->original_name }}</h2>
                        <div class="media-detail-grid"><div><span>FILE TYPE</span><strong>{{ $previewAsset->mime_type ?: strtoupper(pathinfo($previewAsset->original_name, PATHINFO_EXTENSION)) }}</strong></div><div><span>SIZE</span><strong>{{ $previewAsset->formatted_size }}</strong></div><div><span>STORAGE</span><strong>{{ $previewAsset->storage_driver === 'code' ? 'Site bundle' : 'Cloud storage' }}</strong></div><div><span>UPLOADED</span><strong>{{ $previewAsset->created_at->format('M j, Y') }}</strong></div></div>
                        <div class="media-snippet-heading"><label for="asset-public-url">Public URL</label><button @click="copy($refs.assetUrl.value, 'inspector-url')" class="media-copy-link"><flux:icon.clipboard-document /><span x-text="copied === 'inspector-url' ? 'Copied!' : 'Copy URL'"></span></button></div>
                        <input id="asset-public-url" class="media-url-input" x-ref="assetUrl" readonly value="{{ $previewAsset->resolved_url }}" @click="$el.select()" />
                        <div class="media-embed-section"><div class="media-snippet-heading"><span>Use in your project</span><span class="media-language-label" x-text="snippet === 'html' ? 'HTML' : 'MARKDOWN'"></span></div>
                            <div class="media-type-filters media-snippet-tabs" role="group" aria-label="Embed format"><button @click="snippet = 'html'" :aria-pressed="snippet === 'html'">HTML embed</button><button @click="snippet = 'markdown'" :aria-pressed="snippet === 'markdown'">Markdown</button></div>
                            <textarea class="media-snippet-code" readonly x-show="snippet === 'html'" x-ref="htmlSnippet" aria-label="HTML embed code" spellcheck="false" @click="$el.select()">{{ $previewAsset->html_tag }}</textarea>
                            <textarea class="media-snippet-code" readonly x-show="snippet === 'markdown'" x-cloak x-ref="markdownSnippet" aria-label="Markdown embed code" spellcheck="false" @click="$el.select()">{{ $previewAsset->markdown_tag }}</textarea>
                            <button class="studio-button media-copy-snippet" @click="copy(snippet === 'html' ? $refs.htmlSnippet.value : $refs.markdownSnippet.value, 'snippet-' + snippet)"><flux:icon.clipboard-document /><span x-text="copied === 'snippet-' + snippet ? 'Copied!' : (snippet === 'html' ? 'Copy HTML embed' : 'Copy Markdown')"></span></button>
                            <p class="media-embed-help">{{ $previewAsset->isImage() ? 'Paste this image tag into your page. Add descriptive alt text to make it accessible.' : ($previewAsset->isVideo() ? 'A ready-to-play video element with native playback controls.' : ($previewAsset->isAudio() ? 'An audio player with native playback controls.' : 'A download link you can add to any page.')) }}</p>
                        </div>
                        <div class="media-inspector-actions"><a class="studio-button" href="{{ $previewAsset->resolved_url }}" target="_blank" rel="noopener" download="{{ $previewAsset->original_name }}"><flux:icon.arrow-down-tray />Download</a>
                            <flux:dropdown><button class="studio-button" aria-label="Asset actions">More<flux:icon.chevron-down /></button><flux:menu>
                                @if($previewAsset->storage_driver === 'code')<flux:menu.item icon="cloud-arrow-up" wire:click="migrateAsset('{{ $previewAsset->id }}', 'asset_storage', null)" wire:confirm="Move this asset to cloud storage? Its public URL will change; update existing embeds after moving.">Move to cloud storage</flux:menu.item>
                                @elseif($previewAsset->site_id)<flux:menu.item icon="code-bracket" wire:click="migrateAsset('{{ $previewAsset->id }}', 'code', '{{ $previewAsset->site_id }}')" wire:confirm="Move this asset into the site bundle? Its public URL will change; update existing embeds after moving.">Move to site bundle</flux:menu.item>@endif
                                <flux:menu.item icon="trash" variant="danger" wire:click="deleteAsset('{{ $previewAsset->id }}')" wire:confirm="Delete this asset permanently? Existing embeds will stop working.">Delete asset</flux:menu.item>
                            </flux:menu></flux:dropdown>
                        </div>
                    </div>
                </div>
            @else
                <div class="media-inspector-heading"><span>ASSET INSPECTOR</span><flux:icon.cursor-arrow-rays /></div><div class="media-inspector-placeholder"><div class="media-empty-symbol"><flux:icon.code-bracket /></div><h2>From asset to embed.</h2><p>Select a file to see its preview, copy its public URL, or grab a ready-to-use HTML tag.</p><div class="media-example-tag">&lt;img src="your-next-idea.webp"<br> &nbsp;alt="Something worth sharing" /&gt;</div><span>IMAGES · VIDEO · AUDIO · DOCUMENTS</span></div>
            @endif
        </aside>
    </div>
    <!-- Upload Media Modal -->
    <flux:modal name="upload-media-modal" class="md:w-[540px]">
        <form wire:submit="uploadMedia" class="space-y-5">
            <div>
                <flux:heading size="lg">Upload Static Media</flux:heading>
                <flux:subheading>Upload images, videos, or PDFs powered by high-speed cloud object storage.</flux:subheading>
            </div>

            <!-- Storage Choice Toggle -->
            <div class="space-y-2">
                <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300">Choose Storage Destination</label>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <label class="relative flex flex-col p-3 rounded-xl border cursor-pointer transition-colors {{ $storageDestination === 'asset_storage' ? 'border-accent bg-accent/[0.04] dark:bg-accent/10 ring-1 ring-accent' : 'border-zinc-200 dark:border-zinc-800 hover:bg-zinc-50 dark:hover:bg-zinc-900' }}">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-xs font-bold text-zinc-900 dark:text-zinc-100 flex items-center gap-1.5">
                                <flux:icon.cloud-arrow-up class="size-4 text-cyan-600 dark:text-cyan-400" aria-hidden="true" />
                                <span>Cloud Asset Storage</span>
                            </span>
                            <input type="radio" wire:model.live="storageDestination" value="asset_storage" class="text-accent" aria-label="{{ __('Cloud Asset Storage') }}" />
                        </div>
                        <p class="text-[11px] text-zinc-500 dark:text-zinc-400">Zero bundle bloat, global CDN, optimized for video streaming & high-res images.</p>
                    </label>

                    <label class="relative flex flex-col p-3 rounded-xl border cursor-pointer transition-colors {{ $storageDestination === 'code' ? 'border-accent bg-accent/[0.04] dark:bg-accent/10 ring-1 ring-accent' : 'border-zinc-200 dark:border-zinc-800 hover:bg-zinc-50 dark:hover:bg-zinc-900' }}">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-xs font-bold text-zinc-900 dark:text-zinc-100 flex items-center gap-1.5">
                                <flux:icon.code-bracket class="size-4 text-zinc-500 dark:text-zinc-400" aria-hidden="true" />
                                <span>Site Code Bundle</span>
                            </span>
                            <input type="radio" wire:model.live="storageDestination" value="code" class="text-accent" aria-label="{{ __('Site Code Bundle') }}" />
                        </div>
                        <p class="text-[11px] text-zinc-500 dark:text-zinc-400">Stored directly in site files repository, included in ZIP backups and rollback snapshots.</p>
                    </label>
                </div>
            </div>

            <!-- Site Selection (required if code, optional if asset_storage) -->
            @if(!$site)
                <div>
                    <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1">
                        Link to Site {{ $storageDestination === 'code' ? '(Required)' : '(Optional)' }}
                    </label>
                    <select
                        wire:model="selectedSiteId"
                        aria-label="{{ __('Link to site') }}"
                        class="w-full text-xs rounded-xl bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 py-2 px-3 text-zinc-800 dark:text-zinc-200 focus:outline-none focus:ring-2 focus:ring-accent/40"
                        {{ $storageDestination === 'code' ? 'required' : '' }}
                    >
                        <option value="">-- No specific site (Global Asset) --</option>
                        @foreach($userSites as $userSite)
                            <option value="{{ $userSite->id }}">{{ $userSite->original_name ?: $userSite->slug }} ({{ $userSite->slug }})</option>
                        @endforeach
                    </select>
                </div>
            @endif

            <flux:input label="Folder (optional)" wire:model="customSubdir" placeholder="campaigns/summer" />
            <flux:error name="uploadFiles" />
            <flux:error name="uploadFiles.*" />
            <flux:error name="selectedSiteId" />
            <!-- File Upload Dropzone -->
            <div>
                <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1">Select File(s)</label>
                <div
                    x-data="{ isDropping: false }"
                    @dragover.prevent="isDropping = true"
                    @dragleave.prevent="isDropping = false"
                    @drop.prevent="isDropping = false; $refs.fileInput.files = $event.dataTransfer.files; $refs.fileInput.dispatchEvent(new Event('change'))"
                    role="button"
                    tabindex="0"
                    aria-label="{{ __('Select files to upload — click or drag and drop') }}"
                    @keydown.enter.prevent="$refs.fileInput.click()"
                    @keydown.space.prevent="$refs.fileInput.click()"
                    @click="$refs.fileInput.click()"
                    class="border-2 border-dashed rounded-2xl p-8 text-center cursor-pointer transition-all duration-200"
                    :class="isDropping ? 'border-accent bg-accent/[0.06] dark:bg-accent/10 scale-[1.01] shadow-md' : 'border-zinc-300 dark:border-zinc-700 hover:border-accent/60 dark:hover:border-accent/50 hover:bg-zinc-50 dark:hover:bg-zinc-900/40'"
                >
                    <input type="file" x-ref="fileInput" wire:model="uploadFiles" multiple class="hidden" aria-label="{{ __('Select media files') }}" />
                    <span class="icon-tile icon-tile-lg mx-auto mb-3 transition-transform duration-200" :class="isDropping ? 'scale-110' : ''">
                        <flux:icon.arrow-up-tray class="size-6" aria-hidden="true" />
                    </span>
                    <p class="text-xs font-semibold text-zinc-800 dark:text-zinc-200">
                        Click or drag files here to upload
                    </p>
                    <p class="text-[11px] text-zinc-400 mt-1">
                        Images (PNG, JPG, SVG, WebP), Videos (MP4, WebM), Audio (MP3), Documents (PDF) up to 100MB each
                    </p>
                </div>

                @if(!empty($uploadFiles))
                    <div class="mt-2.5 space-y-1">
                        <p class="text-[11px] font-semibold text-zinc-500">Selected for upload ({{ count($uploadFiles) }}):</p>
                        @foreach($uploadFiles as $file)
                            <div class="flex items-center justify-between text-xs px-2.5 py-1 rounded-lg bg-zinc-100 dark:bg-zinc-800 font-mono">
                                <span class="truncate">{{ $file->getClientOriginalName() }}</span>
                                <span class="text-[10px] text-zinc-400">{{ number_format($file->getSize() / 1024, 1) }} KB</span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="flex flex-wrap gap-2 justify-end pt-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Cancel</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="uploadMedia,uploadFiles" class="cursor-pointer">
                    <span wire:loading.remove wire:target="uploadMedia,uploadFiles">Upload Media</span>
                    <span wire:loading wire:target="uploadMedia,uploadFiles" class="inline-flex items-center gap-2">
                        <flux:icon.arrow-path class="size-4 animate-spin" aria-hidden="true" />
                        Uploading...
                    </span>
                </flux:button>
            </div>
        </form>
    </flux:modal>


</div>
