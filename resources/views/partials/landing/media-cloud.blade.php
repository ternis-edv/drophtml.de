<section id="media-cloud" class="py-24 relative z-10 overflow-hidden">
    <!-- Ambient glow behind media cloud -->
    <div class="absolute -top-40 right-1/4 w-[500px] h-[500px] bg-cyan-500/10 dark:bg-cyan-500/15 rounded-full blur-[120px] pointer-events-none"></div>
    <div class="absolute -bottom-20 left-1/4 w-[400px] h-[400px] bg-indigo-500/10 dark:bg-indigo-500/15 rounded-full blur-[100px] pointer-events-none"></div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative">
        
        <!-- Header -->
        <div class="text-center mb-16">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full glass-panel mb-4 border border-cyan-500/30 text-cyan-700 dark:text-cyan-300 text-xs font-semibold uppercase tracking-wider">
                <flux:icon.cloud-arrow-up class="w-3.5 h-3.5 text-cyan-500" />
                <span>New: Static Media Cloud Hosting</span>
            </div>
            <h2 class="text-3xl md:text-5xl font-extrabold mb-4 text-zinc-900 dark:text-white tracking-tight">
                Heavy Assets Don't Belong in Your Zip Bundles
            </h2>
            <p class="text-zinc-600 dark:text-zinc-400 max-w-2xl mx-auto text-base md:text-lg">
                Images, 4K videos, and PDFs shouldn't slow down your site deployments. Store static media in high-speed S3/R2 asset storage or keep them in-code — switch with a single click.
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
            class="glass-panel rounded-3xl border border-zinc-200 dark:border-zinc-800 shadow-2xl overflow-hidden mb-16"
        >
            <!-- Top Controls Bar -->
            <div class="px-6 py-4 bg-zinc-100/90 dark:bg-zinc-900/90 border-b border-zinc-200 dark:border-zinc-800 flex flex-col sm:flex-row items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <span class="text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Storage Architecture:</span>
                    <div class="flex bg-zinc-200 dark:bg-zinc-800 p-1 rounded-xl gap-1 text-xs font-semibold">
                        <button 
                            @click="mode = 'asset_storage'" 
                            :class="mode === 'asset_storage' ? 'bg-cyan-600 text-white shadow-sm' : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white'"
                            class="px-3.5 py-1.5 rounded-lg transition-all flex items-center gap-1.5 cursor-pointer"
                        >
                            <flux:icon.cloud class="w-3.5 h-3.5" />
                            <span>Cloud Asset Storage (S3/R2)</span>
                            <span class="px-1.5 py-0.2 rounded text-[10px] bg-white/20 text-white font-bold">Recommended</span>
                        </button>
                        <button 
                            @click="mode = 'code'" 
                            :class="mode === 'code' ? 'bg-zinc-800 text-white dark:bg-zinc-700 shadow-sm' : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white'"
                            class="px-3.5 py-1.5 rounded-lg transition-all flex items-center gap-1.5 cursor-pointer"
                        >
                            <flux:icon.folder class="w-3.5 h-3.5" />
                            <span>In-Code Asset (Site Bundle)</span>
                        </button>
                    </div>
                </div>

                <div class="flex items-center gap-2 text-xs text-zinc-500 dark:text-zinc-400">
                    <flux:icon.arrow-path-rounded-square class="w-4 h-4 text-cyan-500" />
                    <span>1-Click Bi-directional Migration</span>
                </div>
            </div>

            <!-- Comparison Body -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 p-6 md:p-10 items-center">
                <!-- Left Details / Impact (5 cols) -->
                <div class="lg:col-span-5 space-y-6">
                    <div class="space-y-2">
                        <div class="inline-flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider" :class="mode === 'asset_storage' ? 'text-cyan-600 dark:text-cyan-400' : 'text-zinc-600 dark:text-zinc-400'">
                            <span x-text="mode === 'asset_storage' ? '🚀 High Speed Cloud Object Storage' : '📁 Bundled In Static Archive'"></span>
                        </div>
                        <h3 class="text-2xl md:text-3xl font-bold text-zinc-900 dark:text-white">
                            <span x-show="mode === 'asset_storage'">Blazing Deployments & Infinite Asset Scale</span>
                            <span x-show="mode === 'code'">Self-Contained Portable Site Archive</span>
                        </h3>
                        <p class="text-sm text-zinc-600 dark:text-zinc-400 leading-relaxed">
                            <span x-show="mode === 'asset_storage'">
                                Assets stream directly from AWS S3 or Cloudflare R2 with global edge caching. Your site deployments stay microscopic, unzipping in milliseconds without hitting memory limits.
                            </span>
                            <span x-show="mode === 'code'">
                                Assets live directly within your site folder alongside <code>index.html</code>. Ideal for offline exports or ultra-light sites with only a favicon and CSS.
                            </span>
                        </p>
                    </div>

                    <!-- Metrics Grid -->
                    <div class="grid grid-cols-2 gap-3 pt-2">
                        <div class="p-3.5 rounded-2xl bg-zinc-100/80 dark:bg-zinc-900/80 border border-zinc-200/80 dark:border-zinc-800">
                            <div class="text-[11px] text-zinc-500 font-medium">Deploy ZIP Size</div>
                            <div class="text-xl font-black mt-1" :class="mode === 'asset_storage' ? 'text-emerald-600 dark:text-emerald-400' : 'text-amber-600 dark:text-amber-400'">
                                <span x-text="mode === 'asset_storage' ? '42 KB' : '88.4 MB'"></span>
                            </div>
                            <div class="text-[10px] text-zinc-400 mt-0.5" x-text="mode === 'asset_storage' ? 'Zero binary bloat' : 'Heavy zip payload'"></div>
                        </div>

                        <div class="p-3.5 rounded-2xl bg-zinc-100/80 dark:bg-zinc-900/80 border border-zinc-200/80 dark:border-zinc-800">
                            <div class="text-[11px] text-zinc-500 font-medium">Deployment Speed</div>
                            <div class="text-xl font-black mt-1" :class="mode === 'asset_storage' ? 'text-emerald-600 dark:text-emerald-400' : 'text-zinc-700 dark:text-zinc-300'">
                                <span x-text="mode === 'asset_storage' ? '640 ms' : '4,200 ms'"></span>
                            </div>
                            <div class="text-[10px] text-zinc-400 mt-0.5" x-text="mode === 'asset_storage' ? 'Near instantaneous' : 'Includes file extraction'"></div>
                        </div>

                        <div class="p-3.5 rounded-2xl bg-zinc-100/80 dark:bg-zinc-900/80 border border-zinc-200/80 dark:border-zinc-800">
                            <div class="text-[11px] text-zinc-500 font-medium">Video / PDF Streaming</div>
                            <div class="text-xl font-black mt-1" :class="mode === 'asset_storage' ? 'text-cyan-600 dark:text-cyan-400' : 'text-zinc-700 dark:text-zinc-300'">
                                <span x-text="mode === 'asset_storage' ? 'Byte-Range' : 'Web Server'"></span>
                            </div>
                            <div class="text-[10px] text-zinc-400 mt-0.5" x-text="mode === 'asset_storage' ? 'Fast seeking & scrub' : 'Standard buffer'"></div>
                        </div>

                        <div class="p-3.5 rounded-2xl bg-zinc-100/80 dark:bg-zinc-900/80 border border-zinc-200/80 dark:border-zinc-800">
                            <div class="text-[11px] text-zinc-500 font-medium">Switch Between Modes</div>
                            <div class="text-xl font-black text-indigo-600 dark:text-indigo-400 mt-1">
                                1-Click
                            </div>
                            <div class="text-[10px] text-zinc-400 mt-0.5">Automated background copy</div>
                        </div>
                    </div>
                </div>

                <!-- Right Interactive Asset Card & Code Generator (7 cols) -->
                <div class="lg:col-span-7 bg-zinc-900 dark:bg-[#070709] rounded-2xl border border-zinc-800 p-6 font-mono text-xs shadow-2xl relative overflow-hidden">
                    
                    <!-- Asset Header Preview -->
                    <div class="flex items-center justify-between pb-4 border-b border-zinc-800">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-cyan-500/10 text-cyan-400 border border-cyan-500/30 flex items-center justify-center">
                                <flux:icon.photo class="w-5 h-5" />
                            </div>
                            <div>
                                <div class="font-sans font-bold text-white text-sm">hero-banner.webp</div>
                                <div class="text-[11px] text-zinc-400">1.2 MB &bull; image/webp &bull; 1920 &times; 1080</div>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <span 
                                class="px-2.5 py-1 rounded-full text-[10px] font-sans font-bold"
                                :class="mode === 'asset_storage' ? 'bg-cyan-950/80 text-cyan-300 border border-cyan-500/30' : 'bg-zinc-800 text-zinc-400 border border-zinc-700'"
                                x-text="mode === 'asset_storage' ? 'S3 / R2 Cloud Asset' : 'Code Repository'"
                            ></span>
                        </div>
                    </div>

                    <!-- Live URL Pill -->
                    <div class="my-4 bg-zinc-950 p-3 rounded-xl border border-zinc-800/80 flex items-center justify-between gap-2">
                        <div class="truncate text-zinc-400">
                            <span class="text-cyan-400 select-none">GET</span>
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
                                class="flex items-center gap-1 text-[11px] font-sans px-2.5 py-1 rounded bg-cyan-600 hover:bg-cyan-500 text-white transition-colors cursor-pointer"
                            >
                                <flux:icon.clipboard class="w-3 h-3" />
                                <span x-text="copied ? 'Copied!' : 'Copy Snippet'"></span>
                            </button>
                        </div>

                        <!-- Snippet Code Block -->
                        <div class="bg-black/60 p-4 rounded-xl border border-zinc-800 text-cyan-200 overflow-x-auto leading-relaxed text-xs">
                            <pre><code x-text="getSnippet()"></code></pre>
                        </div>
                    </div>

                    <!-- Footer Migration simulator -->
                    <div class="mt-4 pt-4 border-t border-zinc-800 flex items-center justify-between text-[11px] font-sans text-zinc-400">
                        <span class="flex items-center gap-1.5">
                            <flux:icon.bolt class="w-3.5 h-3.5 text-yellow-400" />
                            <span>Switching is lossless: file checksum & metadata preserved</span>
                        </span>
                        <button 
                            @click="mode = (mode === 'asset_storage' ? 'code' : 'asset_storage')"
                            class="text-cyan-400 hover:text-cyan-300 font-semibold underline cursor-pointer"
                            x-text="mode === 'asset_storage' ? 'Simulate 1-Click Move to Code &rarr;' : 'Simulate 1-Click Move to S3/R2 &rarr;'"
                        ></button>
                    </div>

                </div>
            </div>
        </div>

        <!-- 4 Grid Features -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            
            <div class="glass-panel p-6 rounded-2xl border border-zinc-200 dark:border-zinc-800 hover:border-cyan-500/40 transition-colors">
                <div class="w-10 h-10 rounded-xl bg-cyan-100 dark:bg-cyan-950/60 text-cyan-600 dark:text-cyan-400 flex items-center justify-center mb-4">
                    <flux:icon.bolt class="w-5 h-5" />
                </div>
                <h4 class="font-bold text-zinc-900 dark:text-white text-base mb-2">Zero Bundle Bloat</h4>
                <p class="text-xs text-zinc-600 dark:text-zinc-400 leading-relaxed">
                    Keep your HTML site zips under 100KB for lightning deploys while serving gigabytes of media from dedicated cloud storage.
                </p>
            </div>

            <div class="glass-panel p-6 rounded-2xl border border-zinc-200 dark:border-zinc-800 hover:border-cyan-500/40 transition-colors">
                <div class="w-10 h-10 rounded-xl bg-indigo-100 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 flex items-center justify-center mb-4">
                    <flux:icon.film class="w-5 h-5" />
                </div>
                <h4 class="font-bold text-zinc-900 dark:text-white text-base mb-2">High-Def 4K & Audio</h4>
                <p class="text-xs text-zinc-600 dark:text-zinc-400 leading-relaxed">
                    Full HTTP 206 partial range streaming for smooth video scrubbing, background loops, podcast audio, and high-res PDFs.
                </p>
            </div>

            <div class="glass-panel p-6 rounded-2xl border border-zinc-200 dark:border-zinc-800 hover:border-cyan-500/40 transition-colors">
                <div class="w-10 h-10 rounded-xl bg-purple-100 dark:bg-purple-950/60 text-purple-600 dark:text-purple-400 flex items-center justify-center mb-4">
                    <flux:icon.arrow-path-rounded-square class="w-5 h-5" />
                </div>
                <h4 class="font-bold text-zinc-900 dark:text-white text-base mb-2">1-Click Migration</h4>
                <p class="text-xs text-zinc-600 dark:text-zinc-400 leading-relaxed">
                    Changed your mind? Promote files found in your site code to S3/R2 or pull cloud files into your repository with one click.
                </p>
            </div>

            <div class="glass-panel p-6 rounded-2xl border border-zinc-200 dark:border-zinc-800 hover:border-cyan-500/40 transition-colors">
                <div class="w-10 h-10 rounded-xl bg-emerald-100 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 flex items-center justify-center mb-4">
                    <flux:icon.code-bracket-square class="w-5 h-5" />
                </div>
                <h4 class="font-bold text-zinc-900 dark:text-white text-base mb-2">REST API & Code Studio</h4>
                <p class="text-xs text-zinc-600 dark:text-zinc-400 leading-relaxed">
                    Upload assets directly in Code Studio with instant code snippet paste, or automate via <code>POST /api/media</code>.
                </p>
            </div>

        </div>

    </div>
</section>
