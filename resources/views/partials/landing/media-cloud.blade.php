<section id="media-cloud" class="py-24 relative z-10 overflow-hidden reveal" x-intersect.once="$el.classList.add('is-shown')">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative">
        
        <!-- Header -->
        <div class="text-center mb-16">
            <span class="eyebrow mb-4">
                <flux:icon.cloud-arrow-up class="w-3.5 h-3.5" />
                <span>New: Static Media Cloud Hosting</span>
            </span>
            <h2 class="text-3xl md:text-4xl font-bold tracking-tight text-zinc-900 dark:text-white mb-4">
                Heavy Assets Don't Belong in Your Zip Bundles
            </h2>
            <p class="text-zinc-500 dark:text-zinc-400 max-w-2xl mx-auto text-base">
                Images, 4K videos, and PDFs shouldn't slow down your site deployments. Store static media in high-speed cloud asset storage or keep them in-code — switch with a single click.
            </p>
        </div>

        <script>
            (function() {
                const initMediaCloud = () => {
                    Alpine.data('mediaCloudShowcase', () => ({
                        mode: 'asset_storage',
                        snippetType: 'html',
                        copied: false,
                        getSnippet() {
                            if (this.mode === 'asset_storage') {
                                if (this.snippetType === 'html') return '<img src="https://assets.drophtml.de/media/sites/k7x2q/hero-banner.webp" alt="Hero Showcase" loading="lazy" class="rounded-2xl shadow-xl">';
                                if (this.snippetType === 'video') return '<video controls autoplay muted loop src="https://assets.drophtml.de/media/sites/k7x2q/product-demo-4k.mp4" class="w-full rounded-xl"></video>';
                                if (this.snippetType === 'markdown') return '![Hero Showcase](https://assets.drophtml.de/media/sites/k7x2q/hero-banner.webp)';
                                return 'https://assets.drophtml.de/media/sites/k7x2q/hero-banner.webp';
                            } else {
                                if (this.snippetType === 'html') return '<img src="/assets/hero-banner.webp" alt="Hero Showcase" loading="lazy">';
                                if (this.snippetType === 'video') return '<video controls src="/assets/product-demo-4k.mp4"></video>';
                                if (this.snippetType === 'markdown') return '![Hero Showcase](/assets/hero-banner.webp)';
                                return 'https://drophtml.de/s/k7x2q/assets/hero-banner.webp';
                            }
                        },
                        copySnippet() {
                            navigator.clipboard.writeText(this.getSnippet());
                            this.copied = true;
                            setTimeout(() => this.copied = false, 2000);
                        }
                    }));
                };

                if (window.Alpine) {
                    initMediaCloud();
                } else {
                    document.addEventListener('alpine:init', initMediaCloud);
                }
            })();
        </script>

        <!-- Interactive Architecture & Comparison Showcase -->
        <div 
            x-data="mediaCloudShowcase"
            class="rounded-3xl bg-white dark:bg-zinc-900/50 border border-zinc-200 dark:border-zinc-800 overflow-hidden mb-16"
        >
            <!-- Top Controls Bar -->
            <div class="px-6 py-4 bg-zinc-100/90 dark:bg-zinc-900/90 border-b border-zinc-200 dark:border-zinc-800 flex flex-col sm:flex-row items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <span class="text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Storage Architecture:</span>
                    <div class="flex bg-zinc-200 dark:bg-zinc-800 p-1 rounded-xl gap-1 text-xs font-semibold">
                        <button 
                            @click="mode = 'asset_storage'" 
                            :class="mode === 'asset_storage' ? 'bg-accent text-white' : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white'"
                            class="px-3.5 py-1.5 rounded-lg transition-all flex items-center gap-1.5 cursor-pointer"
                        >
                            <flux:icon.cloud class="w-3.5 h-3.5" />
                            <span>Cloud Asset Storage</span>
                            <span class="px-1.5 py-0.2 rounded text-[10px] bg-white/20 text-white font-semibold">Recommended</span>
                        </button>
                        <button 
                            @click="mode = 'code'" 
                            :class="mode === 'code' ? 'bg-zinc-800 text-white dark:bg-zinc-700' : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white'"
                            class="px-3.5 py-1.5 rounded-lg transition-all flex items-center gap-1.5 cursor-pointer"
                        >
                            <flux:icon.folder class="w-3.5 h-3.5" />
                            <span>In-Code Asset (Site Bundle)</span>
                        </button>
                    </div>
                </div>

                <div class="flex items-center gap-2 text-xs text-zinc-500 dark:text-zinc-400">
                    <flux:icon.arrow-path-rounded-square class="w-4 h-4 text-accent" />
                    <span>1-Click Bi-directional Migration</span>
                </div>
            </div>

            <!-- Comparison Body -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 p-6 md:p-10 items-center">
                <!-- Left Details / Impact (5 cols) -->
                <div class="lg:col-span-5 space-y-6">
                    <div class="space-y-2">
                        <div class="inline-flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wider" :class="mode === 'asset_storage' ? 'text-accent dark:text-accent-content' : 'text-zinc-500 dark:text-zinc-400'">
                            <span x-show="mode === 'asset_storage'"><flux:icon.cloud class="w-3.5 h-3.5" /></span>
                            <span x-show="mode === 'code'"><flux:icon.folder class="w-3.5 h-3.5" /></span>
                            <span x-text="mode === 'asset_storage' ? 'High Speed Cloud Object Storage' : 'Bundled In Static Archive'"></span>
                        </div>
                        <h3 class="text-2xl md:text-3xl font-bold text-zinc-900 dark:text-white">
                            <span x-show="mode === 'asset_storage'">Blazing Deployments & Infinite Asset Scale</span>
                            <span x-show="mode === 'code'">Self-Contained Portable Site Archive</span>
                        </h3>
                        <p class="text-sm text-zinc-600 dark:text-zinc-400 leading-relaxed">
                            <span x-show="mode === 'asset_storage'">
                                Assets stream directly from dedicated high-speed cloud object storage with global edge caching. Your site deployments stay microscopic, unzipping in milliseconds without hitting memory limits.
                            </span>
                            <span x-show="mode === 'code'">
                                Assets live directly within your site folder alongside <code>index.html</code>. Ideal for offline exports or ultra-light sites with only a favicon and CSS.
                            </span>
                        </p>
                    </div>

                    <!-- Metrics Grid -->
                    <div class="grid grid-cols-2 gap-3 pt-2">
                        <div class="p-3.5 rounded-2xl bg-zinc-100/80 dark:bg-zinc-900/60 border border-zinc-200/80 dark:border-zinc-800">
                            <div class="text-[11px] text-zinc-500 font-medium">Deploy ZIP Size</div>
                            <div class="text-xl font-bold mt-1" :class="mode === 'asset_storage' ? 'text-emerald-600 dark:text-emerald-400' : 'text-amber-600 dark:text-amber-400'">
                                <span x-text="mode === 'asset_storage' ? '42 KB' : '88.4 MB'"></span>
                            </div>
                            <div class="text-[10px] text-zinc-400 mt-0.5" x-text="mode === 'asset_storage' ? 'Zero binary bloat' : 'Heavy zip payload'"></div>
                        </div>

                        <div class="p-3.5 rounded-2xl bg-zinc-100/80 dark:bg-zinc-900/60 border border-zinc-200/80 dark:border-zinc-800">
                            <div class="text-[11px] text-zinc-500 font-medium">Deployment Speed</div>
                            <div class="text-xl font-bold mt-1" :class="mode === 'asset_storage' ? 'text-emerald-600 dark:text-emerald-400' : 'text-zinc-700 dark:text-zinc-300'">
                                <span x-text="mode === 'asset_storage' ? '640 ms' : '4,200 ms'"></span>
                            </div>
                            <div class="text-[10px] text-zinc-400 mt-0.5" x-text="mode === 'asset_storage' ? 'Near instantaneous' : 'Includes file extraction'"></div>
                        </div>

                        <div class="p-3.5 rounded-2xl bg-zinc-100/80 dark:bg-zinc-900/60 border border-zinc-200/80 dark:border-zinc-800">
                            <div class="text-[11px] text-zinc-500 font-medium">Video / PDF Streaming</div>
                            <div class="text-xl font-bold mt-1" :class="mode === 'asset_storage' ? 'text-accent dark:text-accent-content' : 'text-zinc-700 dark:text-zinc-300'">
                                <span x-text="mode === 'asset_storage' ? 'Byte-Range' : 'Web Server'"></span>
                            </div>
                            <div class="text-[10px] text-zinc-400 mt-0.5" x-text="mode === 'asset_storage' ? 'Fast seeking & scrub' : 'Standard buffer'"></div>
                        </div>

                        <div class="p-3.5 rounded-2xl bg-zinc-100/80 dark:bg-zinc-900/60 border border-zinc-200/80 dark:border-zinc-800">
                            <div class="text-[11px] text-zinc-500 font-medium">Switch Between Modes</div>
                            <div class="text-xl font-bold text-accent dark:text-accent-content mt-1">
                                1-Click
                            </div>
                            <div class="text-[10px] text-zinc-400 mt-0.5">Automated background copy</div>
                        </div>
                    </div>
                </div>

                <!-- Right Interactive Asset Card & Code Generator (7 cols) -->
                <div class="lg:col-span-7 bg-zinc-900 dark:bg-zinc-950 rounded-2xl border border-zinc-800 p-6 font-mono text-xs relative overflow-hidden">
                    
                    <!-- Asset Header Preview -->
                    <div class="flex items-center justify-between pb-4 border-b border-zinc-800">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-accent/15 text-indigo-300 border border-accent/30 flex items-center justify-center">
                                <flux:icon.photo class="w-5 h-5" />
                            </div>
                            <div>
                                <div class="font-sans font-bold text-white text-sm">hero-banner.webp</div>
                                <div class="text-[11px] text-zinc-400">1.2 MB &bull; image/webp &bull; 1920 &times; 1080</div>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <span 
                                class="px-2.5 py-1 rounded-full text-[10px] font-sans font-semibold"
                                :class="mode === 'asset_storage' ? 'bg-accent/20 text-indigo-300 border border-accent/40' : 'bg-zinc-800 text-zinc-400 border border-zinc-700'"
                                x-text="mode === 'asset_storage' ? 'Cloud Asset Storage' : 'Code Repository'"
                            ></span>
                        </div>
                    </div>

                    <!-- Live URL Pill -->
                    <div class="my-4 bg-zinc-950 p-3 rounded-xl border border-zinc-800/80 flex items-center justify-between gap-2">
                        <div class="truncate text-zinc-400">
                            <span class="text-indigo-300 select-none">GET</span>
                            <span class="text-zinc-200 select-all" x-text="mode === 'asset_storage' ? 'https://assets.drophtml.de/media/sites/k7x2q/hero-banner.webp' : 'https://drophtml.de/s/k7x2q/assets/hero-banner.webp'"></span>
                        </div>
                        <span class="px-2 py-0.5 rounded text-[10px] font-sans bg-emerald-950/60 text-emerald-400 shrink-0">200 OK &bull; 304 Cached</span>
                    </div>

                    <!-- Snippet Selector Bar -->
                    <div class="pt-2">
                        <div class="flex items-center justify-between mb-2">
                            <div class="flex items-center gap-1 font-sans text-xs">
                                <button 
                                    @click="snippetType = 'html'"
                                    :class="snippetType === 'html' ? 'bg-zinc-800 text-white' : 'text-zinc-400 hover:text-white'"
                                    class="px-2.5 py-1 rounded-lg transition-colors cursor-pointer"
                                >
                                    &lt;img&gt; HTML Tag
                                </button>
                                <button 
                                    @click="snippetType = 'video'"
                                    :class="snippetType === 'video' ? 'bg-zinc-800 text-white' : 'text-zinc-400 hover:text-white'"
                                    class="px-2.5 py-1 rounded-lg transition-colors cursor-pointer"
                                >
                                    &lt;video&gt; HTML Tag
                                </button>
                                <button 
                                    @click="snippetType = 'markdown'"
                                    :class="snippetType === 'markdown' ? 'bg-zinc-800 text-white' : 'text-zinc-400 hover:text-white'"
                                    class="px-2.5 py-1 rounded-lg transition-colors cursor-pointer"
                                >
                                    Markdown
                                </button>
                                <button 
                                    @click="snippetType = 'url'"
                                    :class="snippetType === 'url' ? 'bg-zinc-800 text-white' : 'text-zinc-400 hover:text-white'"
                                    class="px-2.5 py-1 rounded-lg transition-colors cursor-pointer"
                                >
                                    Direct URL
                                </button>
                            </div>

                            <button 
                                @click="copySnippet()"
                                class="flex items-center gap-1 text-[11px] font-sans px-2.5 py-1 rounded bg-accent hover:bg-accent/90 text-white transition-colors cursor-pointer"
                            >
                                <flux:icon.clipboard class="w-3 h-3" />
                                <span x-text="copied ? 'Copied!' : 'Copy Snippet'"></span>
                            </button>
                        </div>

                        <!-- Snippet Code Block -->
                        <div class="bg-zinc-950/70 p-4 rounded-xl border border-zinc-800 text-zinc-200 overflow-x-auto leading-relaxed text-xs">
                            <pre><code x-text="getSnippet()"></code></pre>
                        </div>
                    </div>

                    <!-- Footer Migration simulator -->
                    <div class="mt-4 pt-4 border-t border-zinc-800 flex items-center justify-between text-[11px] font-sans text-zinc-400">
                        <span class="flex items-center gap-1.5">
                            <flux:icon.bolt class="w-3.5 h-3.5 text-indigo-300" />
                            <span>Switching is lossless: file checksum & metadata preserved</span>
                        </span>
                        <button 
                            @click="mode = (mode === 'asset_storage' ? 'code' : 'asset_storage')"
                            class="text-indigo-300 hover:text-indigo-200 font-semibold underline cursor-pointer"
                            x-text="mode === 'asset_storage' ? 'Simulate 1-Click Move to Code &rarr;' : 'Simulate 1-Click Move to Cloud Storage &rarr;'"
                        ></button>
                    </div>

                </div>
            </div>
        </div>

        <!-- 4 Grid Features -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            
            <div class="p-6 rounded-2xl bg-white dark:bg-zinc-900/50 border border-zinc-200 dark:border-zinc-800 hover:border-accent/40 transition-colors duration-150">
                <div class="w-10 h-10 rounded-xl bg-accent/10 dark:bg-accent/15 text-accent dark:text-accent-content flex items-center justify-center mb-4">
                    <flux:icon.bolt class="w-5 h-5" />
                </div>
                <h4 class="font-bold text-zinc-900 dark:text-white text-base mb-2">Zero Bundle Bloat</h4>
                <p class="text-xs text-zinc-500 dark:text-zinc-400 leading-relaxed">
                    Keep your HTML site zips under 100KB for lightning deploys while serving gigabytes of media from dedicated cloud storage.
                </p>
            </div>

            <div class="p-6 rounded-2xl bg-white dark:bg-zinc-900/50 border border-zinc-200 dark:border-zinc-800 hover:border-accent/40 transition-colors duration-150">
                <div class="w-10 h-10 rounded-xl bg-accent/10 dark:bg-accent/15 text-accent dark:text-accent-content flex items-center justify-center mb-4">
                    <flux:icon.film class="w-5 h-5" />
                </div>
                <h4 class="font-bold text-zinc-900 dark:text-white text-base mb-2">High-Def 4K & Audio</h4>
                <p class="text-xs text-zinc-500 dark:text-zinc-400 leading-relaxed">
                    Full HTTP 206 partial range streaming for smooth video scrubbing, background loops, podcast audio, and high-res PDFs.
                </p>
            </div>

            <div class="p-6 rounded-2xl bg-white dark:bg-zinc-900/50 border border-zinc-200 dark:border-zinc-800 hover:border-accent/40 transition-colors duration-150">
                <div class="w-10 h-10 rounded-xl bg-accent/10 dark:bg-accent/15 text-accent dark:text-accent-content flex items-center justify-center mb-4">
                    <flux:icon.arrow-path-rounded-square class="w-5 h-5" />
                </div>
                <h4 class="font-bold text-zinc-900 dark:text-white text-base mb-2">1-Click Migration</h4>
                <p class="text-xs text-zinc-500 dark:text-zinc-400 leading-relaxed">
                    Changed your mind? Promote files found in your site code to cloud asset storage or pull cloud files into your repository with one click.
                </p>
            </div>

            <div class="p-6 rounded-2xl bg-white dark:bg-zinc-900/50 border border-zinc-200 dark:border-zinc-800 hover:border-accent/40 transition-colors duration-150">
                <div class="w-10 h-10 rounded-xl bg-accent/10 dark:bg-accent/15 text-accent dark:text-accent-content flex items-center justify-center mb-4">
                    <flux:icon.code-bracket-square class="w-5 h-5" />
                </div>
                <h4 class="font-bold text-zinc-900 dark:text-white text-base mb-2">REST API & Code Studio</h4>
                <p class="text-xs text-zinc-500 dark:text-zinc-400 leading-relaxed">
                    Upload assets directly in Code Studio with instant code snippet paste, or automate via <code class="px-1 py-0.5 rounded bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300 font-mono">POST /api/media</code>.
                </p>
            </div>

        </div>

    </div>
</section>
