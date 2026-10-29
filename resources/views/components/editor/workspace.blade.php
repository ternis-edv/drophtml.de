<div class="studio" x-data="codeStudio($wire)" @keydown.window="shortcut($event)" @file-saved.window="saved()" @beforeunload.window="if (dirty) { $event.preventDefault(); $event.returnValue = ''; }">
    <header class="studio-header">
        <a href="{{ route('dashboard') }}" class="studio-brand" @click="if (!canLeave()) $event.preventDefault()" aria-label="Back to dashboard"><img src="/favicon.svg" alt="" /> <span>drop<span class="studio-muted">html</span></span></a>
        <span class="studio-divider"></span>
        <div class="studio-project"><span class="studio-project-name">{{ $site->original_name ?: $site->slug }}</span><span class="studio-label">Code Studio</span></div>
        <div class="studio-header-actions">
            <flux:dropdown>
                <button type="button" class="studio-button studio-navigation-trigger" aria-label="Navigate site workspace"><flux:icon.squares-2x2 /> <span>Navigate</span><flux:icon.chevron-down /></button>
                <flux:menu>
                    @foreach([
                        ['dashboard.sites.media', 'photo', 'Media library'],
                        ['dashboard.sites.stats', 'chart-bar', 'Analytics'],
                        ['dashboard.sites.deployments', 'archive-box', 'Deployments'],
                        ['dashboard.sites.domains', 'globe-alt', 'Domains'],
                        ['dashboard.sites.redirects', 'arrows-right-left', 'Redirects'],
                        ['dashboard.sites.settings', 'cog-6-tooth', 'Site settings'],
                    ] as [$destination, $icon, $label])
                        <flux:menu.item :href="route($destination, $site)" :icon="$icon" @click="if (!canLeave()) $event.preventDefault()">{{ $label }}</flux:menu.item>
                    @endforeach
                    <flux:menu.separator />
                    <flux:menu.item :href="route('dashboard')" icon="arrow-left" @click="if (!canLeave()) $event.preventDefault()">All sites</flux:menu.item>
                </flux:menu>
            </flux:dropdown>
            <span class="studio-save-state" role="status"><i :class="{ 'is-dirty': dirty }"></i><span x-text="saving ? 'Saving…' : (dirty ? 'Unsaved changes' : 'All changes saved')"></span></span>
            <a class="studio-button studio-visit" href="{{ route('dashboard.sites.media', $site) }}" @click="if (!canLeave()) $event.preventDefault()"><flux:icon.photo />Media library</a>
            <a class="studio-button studio-visit" href="{{ url('/s/'.$site->slug.'/') }}" target="_blank" rel="noopener">Visit site <flux:icon.arrow-top-right-on-square /></a>
            <button class="studio-button studio-button-primary" type="button" @click="save()" :disabled="saving || !$wire.currentFile || $wire.isBinary"><flux:icon.arrow-up-tray /><span x-text="saving ? 'Saving…' : 'Save changes'"></span></button>
        </div>
    </header>

    <div class="studio-body-grid" :class="{ 'explorer-hidden': !explorerOpen }">
        <nav class="studio-rail" aria-label="Studio navigation">
            <button class="studio-rail-button" :class="{ 'is-active': explorerOpen && $wire.explorerTab === 'code' }" @click="explorerOpen = true; $wire.setExplorerTab('code')" title="Files" aria-label="Files"><flux:icon.folder /></button>
            <button class="studio-rail-button" :class="{ 'is-active': explorerOpen && $wire.explorerTab === 'assets' }" @click="explorerOpen = true; $wire.setExplorerTab('assets')" title="Cloud media" aria-label="Cloud media"><flux:icon.photo /></button>
            <span class="studio-rail-spacer"></span>
            <a class="studio-rail-button" href="{{ route('dashboard.sites.settings', $site) }}" @click="if (!canLeave()) $event.preventDefault()" title="Site settings" aria-label="Site settings"><flux:icon.cog-6-tooth /></a>
            <a class="studio-rail-button" href="{{ route('dashboard') }}" @click="if (!canLeave()) $event.preventDefault()" title="Exit studio" aria-label="Exit studio"><flux:icon.arrow-left /></a>
        </nav>

        <aside class="studio-explorer" aria-label="File explorer" x-show="explorerOpen">
            <div class="studio-pane-heading"><span>{{ $explorerTab === 'code' ? 'EXPLORER' : 'CLOUD MEDIA' }}</span><button class="studio-icon-button" @click="explorerOpen = false" aria-label="Hide explorer"><flux:icon.chevron-double-left /></button></div>
            <div class="studio-explorer-project"><flux:icon.chevron-down /><span>{{ $site->slug }}</span><span class="studio-count">{{ $explorerTab === 'code' ? count($files) : $cloudAssets->count() }}</span></div>
            @if($explorerTab === 'code')
                <div class="studio-file-tools">
                    <label class="studio-search"><flux:icon.magnifying-glass /><input aria-label="Filter files" wire:model.live.debounce.150ms="fileFilter" placeholder="Find a file…" /></label>
                    <button class="studio-icon-button" @click="if (canLeave()) $flux.modal('create-file-modal').show()" aria-label="New file" title="New file"><flux:icon.document-plus /></button>
                    <button class="studio-icon-button" @click="if (canLeave()) $refs.fileUpload.click()" aria-label="Upload file" title="Upload file"><flux:icon.arrow-up-tray /></button>
                    <input type="file" class="hidden" x-ref="fileUpload" wire:model="uploadFile" aria-label="Upload file to site" />
                </div>
                <div class="studio-file-list">
                    @forelse($filteredFiles as $file)
                        @php($ext = strtolower(pathinfo($file, PATHINFO_EXTENSION)))
                        <div wire:key="file-{{ $file }}" class="studio-file-row {{ $currentFile === $file ? 'is-active' : '' }}">
                            <button class="studio-file" @click="selectFile({{ \Illuminate\Support\Js::from($file) }})" title="{{ $file }}" @if($currentFile === $file) aria-current="true" @endif>
                                <span class="studio-file-type type-{{ $ext }}">{{ match($ext) { 'html', 'htm' => '<>', 'css' => '#', 'js', 'mjs' => 'JS', 'json' => '{}', default => '·' } }}</span><span>{{ $file }}</span>
                            </button>
                            <flux:dropdown>
                                <button class="studio-file-menu studio-icon-button" aria-label="Actions for {{ $file }}"><flux:icon.ellipsis-horizontal /></button>
                                <flux:menu>
                                    <flux:menu.item icon="pencil-square" @click="renamePath = {{ \Illuminate\Support\Js::from($file) }}; $wire.startRename(renamePath)">Rename</flux:menu.item>
                                    <flux:menu.item data-dashboard-tone="danger" icon="trash" @click="if (canLeave() && confirm('Delete this file permanently?')) $wire.deleteFile({{ \Illuminate\Support\Js::from($file) }})" variant="danger">Delete</flux:menu.item>
                                </flux:menu>
                            </flux:dropdown>
                        </div>
                    @empty
                        <div class="studio-explorer-empty">{{ $fileFilter ? 'No files match your search.' : 'Your workspace is empty. Create or upload a file to begin.' }}</div>
                    @endforelse
                </div>
                <div class="studio-explorer-note"><flux:icon.information-circle /><p>Changes go live when you save.<br><span>Preview shows your saved site.</span></p></div>
            @else
                <div class="studio-media-tools"><p>Reusable assets, served from the cloud.</p><button class="studio-button" @click="$refs.cloudUpload.click()"><flux:icon.plus />Upload media</button><input type="file" class="hidden" x-ref="cloudUpload" wire:model="cloudUploadFile" aria-label="Upload cloud media" /></div>
                <div class="studio-file-list studio-assets">
                    @forelse($cloudAssets as $asset)
                        <article class="studio-asset" x-data="mediaLibrary" wire:key="asset-{{ $asset->id }}">
                            @if($asset->isImage())<img src="{{ $asset->resolved_url }}" alt="{{ $asset->original_name }}" />@else<flux:icon.document />@endif
                            <strong title="{{ $asset->original_name }}">{{ $asset->original_name }}</strong><small>{{ $asset->formatted_size }}</small>
                            <div><button class="studio-button" @click="$wire.insertAssetSnippet({{ \Illuminate\Support\Js::from($asset->html_tag) }})" :disabled="!$wire.currentFile || $wire.isBinary">Insert tag</button><button class="studio-button" data-url="{{ $asset->resolved_url }}" @click="copy($el.dataset.url, 'url')" aria-label="Copy URL for {{ $asset->original_name }}"><span x-text="copied === 'url' ? 'Copied!' : 'Copy URL'"></span></button></div>
                            <details class="studio-embed-details"><summary>HTML embed code</summary><textarea readonly x-ref="embedCode" aria-label="HTML embed for {{ $asset->original_name }}" @click="$el.select()">{{ $asset->html_tag }}</textarea><button class="studio-button" @click="copy($refs.embedCode.value, 'html')"><span x-text="copied === 'html' ? 'Copied!' : 'Copy HTML'"></span></button></details><p class="studio-copy-error" x-show="copyError" x-text="copyError" role="status"></p>
                        </article>
                    @empty
                        <div class="studio-explorer-empty"><flux:icon.cloud-arrow-up /><strong>A home for your media</strong><p>Upload images, videos, and documents, then insert them into your code.</p></div>
                    @endforelse
                </div>
            @endif
            <div class="studio-upload-status" wire:loading wire:target="uploadFile,cloudUploadFile">Uploading file…</div>
            @error('uploadFile')<p class="studio-error">{{ $message }}</p>@enderror
            @error('cloudUploadFile')<p class="studio-error">{{ $message }}</p>@enderror
        </aside>

        <main id="studio-workspace" class="studio-workspace" tabindex="-1">
            <div class="studio-workspace-toolbar">
                <div class="studio-breadcrumb"><button class="studio-icon-button" x-show="!explorerOpen" @click="explorerOpen = true" aria-label="Show explorer"><flux:icon.folder /></button><span class="studio-label">Workspace</span><flux:icon.chevron-right /><span>{{ $currentFile ?: 'Getting started' }}</span></div>
                <div class="studio-segment" role="group" aria-label="Workspace view">
                    @foreach(['editor' => 'Code', 'split' => 'Split', 'preview' => 'Preview'] as $mode => $label)
                        <button @click="mode = '{{ $mode }}'" :aria-pressed="mode === '{{ $mode }}'" :class="{ 'is-active': mode === '{{ $mode }}' }">{{ $label }}</button>
                    @endforeach
                </div>
            </div>
            <div class="studio-panes" :data-mode="mode">
                <section class="studio-code-pane" x-show="mode !== 'preview'" aria-label="Code editor">
                    <div class="studio-document-tab"><span class="studio-file-type">&lt;&gt;</span><span>{{ $currentFile ?: 'Welcome' }}</span><span class="studio-dirty-dot" x-show="dirty" title="Unsaved changes"></span><button class="studio-icon-button" @click="find()" x-show="!!$wire.currentFile && !$wire.isBinary" aria-label="Find in file" title="Find in file (⌘/Ctrl F)"><flux:icon.magnifying-glass /></button></div>
                    <div class="studio-code-host" x-ref="editor" wire:ignore x-show="!!$wire.currentFile && !$wire.isBinary"></div>
                    @if($currentFile && $isBinary)
                        <div class="studio-empty studio-binary">
                            @if(in_array(strtolower(pathinfo($currentFile, PATHINFO_EXTENSION)), ['png','jpg','jpeg','gif','webp','ico']))
                                <img src="{{ Storage::disk('public')->url($site->path.'/'.$currentFile) }}" alt="{{ $currentFile }}" />
                            @else<flux:icon.document />@endif
                            <h2>{{ basename($currentFile) }}</h2><p>This asset is available in your site bundle.</p>
                            <a class="studio-button" href="{{ Storage::disk('public')->url($site->path.'/'.$currentFile) }}" download>Download asset</a>
                            <button class="studio-button" wire:click="migrateCurrentFileToCloud" wire:confirm="Move this asset to Cloud Asset Storage? It will be removed from the site bundle."><flux:icon.cloud-arrow-up />Move to cloud media</button>
                        </div>
                    @elseif(!$currentFile)
                        <div class="studio-empty"><div class="studio-empty-icon"><flux:icon.code-bracket /></div><span class="studio-label">YOUR NEXT IDEA STARTS HERE</span><h1>Make something<br>worth sharing.</h1><p>Open a file from the explorer, or start with a new page. Your site is just a save away.</p><flux:modal.trigger name="create-file-modal"><button class="studio-button studio-button-primary"><flux:icon.plus />Create your first file</button></flux:modal.trigger></div>
                    @endif
                </section>
                <section class="studio-preview-pane" x-show="mode !== 'editor'" aria-label="Site preview">
                    <div class="studio-preview-toolbar"><span class="studio-label"><i></i>PREVIEW</span><div class="studio-segment" role="group" aria-label="Preview device">
                        @foreach(['desktop' => 'computer-desktop', 'tablet' => 'device-tablet', 'mobile' => 'device-phone-mobile'] as $device => $icon)
                            <button @click="viewport = '{{ $device }}'" :aria-pressed="viewport === '{{ $device }}'" :class="{ 'is-active': viewport === '{{ $device }}' }" aria-label="{{ ucfirst($device) }} preview" title="{{ ucfirst($device) }}{{ $device === 'tablet' ? ' (768px)' : ($device === 'mobile' ? ' (375px)' : '') }}"><flux:icon :name="$icon" /></button>
                        @endforeach
                    </div><button class="studio-icon-button" @click="reloadPreview()" aria-label="Reload preview" title="Reload preview"><flux:icon.arrow-path /></button></div>
                    @php($previewUrl = url('/s/'.$site->slug.'/'.(in_array(strtolower(pathinfo($currentFile ?? '', PATHINFO_EXTENSION)), ['html', 'htm']) ? $currentFile : '')))
                    <div class="studio-preview-address"><flux:icon.globe-alt /><span>{{ $site->slug }} / {{ in_array(strtolower(pathinfo($currentFile ?? '', PATHINFO_EXTENSION)), ['html', 'htm']) ? $currentFile : '' }}</span><a href="{{ $previewUrl }}" target="_blank" rel="noopener" aria-label="Open preview in new tab"><flux:icon.arrow-top-right-on-square /></a></div>
                    <div class="studio-preview-canvas" :data-viewport="viewport">
                        <div class="studio-preview-frame" :style="{ width: viewport === 'mobile' ? '375px' : (viewport === 'tablet' ? '768px' : '100%') }">
                            <iframe :src="{{ \Illuminate\Support\Js::from($previewUrl) }} + '?_studio=' + previewTimestamp" title="Saved site preview" sandbox="allow-scripts allow-same-origin allow-forms allow-popups allow-modals allow-downloads"></iframe>
                        </div>
                    </div>
                    <div class="studio-preview-caption"><span x-text="viewport === 'desktop' ? 'Responsive viewport' : (viewport === 'tablet' ? '768 px' : '375 px')"></span><span x-text="dirty ? 'Save to update preview' : 'Showing saved version'"></span></div>
                </section>
            </div>
            <footer class="studio-statusbar"><div><span class="studio-status-indicator"></span><span x-text="dirty ? 'Modified' : 'Ready'"></span><span class="studio-status-hint">⌘ / Ctrl S to save</span></div><div><span x-text="`Ln ${line}, Col ${column}`"></span><span x-text="`${lines} lines`"></span><span>UTF-8</span><span>{{ strtoupper(pathinfo($currentFile ?? '', PATHINFO_EXTENSION) ?: 'text') }}</span></div></footer>
        </main>
    </div>
    <!-- Modals -->
    <flux:modal name="create-file-modal" class="md:w-[420px]">
        <form wire:submit="createFile" class="space-y-5">
            <div>
                <flux:heading size="lg">Create New File</flux:heading>
                <flux:subheading>Enter a file path (e.g. <code>about.html</code>, <code>css/styles.css</code>, or <code>js/main.js</code>).</flux:subheading>
            </div>

            <flux:input label="File path" wire:model="newFileName" placeholder="about.html" autofocus required />

            <div class="flex gap-2 justify-end">
                <flux:modal.close>
                    <flux:button data-dashboard-variant="ghost" variant="ghost">Cancel</flux:button>
                </flux:modal.close>
                <flux:button data-dashboard-variant="primary" type="submit" variant="primary">Create File</flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="rename-file-modal" class="md:w-[420px]">
        <form wire:submit="renameFile" class="space-y-5">
            <div>
                <flux:heading size="lg">Rename File</flux:heading>
                <flux:subheading>Rename <code>{{ $oldFileName }}</code> to a new file name.</flux:subheading>
            </div>

            <flux:input label="New file path" wire:model="newRenameName" autofocus required />

            <div class="flex gap-2 justify-end">
                <flux:modal.close>
                    <flux:button data-dashboard-variant="ghost" variant="ghost">Cancel</flux:button>
                </flux:modal.close>
                <flux:button data-dashboard-variant="primary" type="submit" variant="primary">Rename</flux:button>
            </div>
        </form>
    </flux:modal>
</div>