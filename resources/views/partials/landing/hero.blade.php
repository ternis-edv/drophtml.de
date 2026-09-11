<section class="relative pt-32 pb-16 lg:pt-44 lg:pb-24 overflow-hidden">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10 text-center">
        <div x-data="{ 
            headlines: [
                'Ship HTML in seconds.',
                'Stream media on S3 / Cloudflare R2.',
                'Automate with GitHub CI/CD webhooks.',
                'Lock client staging behind passwords.'
            ],
            currentHeadlineIndex: 0,
            init() {
                setInterval(() => {
                    this.currentHeadlineIndex = (this.currentHeadlineIndex + 1) % this.headlines.length;
                }, 3000);
            }
        }">
            <div class="flex flex-col items-center">
                
                <!-- Announcement Pill -->
                <a href="#media-cloud" class="inline-flex items-center gap-2.5 px-4 py-1.5 rounded-full glass-panel mb-8 border border-indigo-500/30 text-indigo-600 dark:text-indigo-300 text-xs font-semibold hover:bg-indigo-500/10 transition-all shadow-xs group">
                    <span class="flex size-2 relative">
                        <span class="animate-ping absolute inline-flex size-full rounded-full bg-emerald-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full size-2 bg-emerald-500"></span>
                    </span>
                    <span><strong>Engine 2.0 Released:</strong> S3 / Cloudflare R2 Static Media Hosting</span>
                    <flux:icon.arrow-right class="size-3 text-indigo-500 group-hover:translate-x-0.5 transition-transform" />
                </a>

                <!-- Main Heading -->
                <h1 class="text-4xl sm:text-6xl lg:text-7xl font-black tracking-tight mb-6 text-zinc-900 dark:text-white leading-[1.12] max-w-5xl">
                    Instant static site hosting<br class="hidden sm:inline" />
                    &amp; static media cloud.<br/>
                    <span 
                        class="text-transparent bg-clip-text bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-500 dark:from-indigo-400 dark:via-purple-400 dark:to-pink-400 transition-all duration-500 inline-block min-h-[1.2em]"
                        x-text="headlines[currentHeadlineIndex]"
                    >Ship HTML in seconds.</span>
                </h1>
                
                <!-- Subtitle -->
                <p class="text-base sm:text-lg md:text-xl text-zinc-600 dark:text-zinc-400 max-w-3xl mx-auto mb-10 leading-relaxed font-normal">
                    Drag, drop, and publish HTML or ZIP files in seconds. Connect GitHub repositories with zero configuration, host heavy images, videos, and PDFs on high-speed S3/R2 object storage, protect staging behind passwords, and map custom domains with automated SSL.
                </p>

                <!-- Primary CTAs -->
                <div class="flex flex-col sm:flex-row items-center gap-4 mb-14">
                    <flux:button 
                        :href="route('register')" 
                        variant="primary" 
                        class="rounded-2xl px-8 py-3.5 text-sm sm:text-base font-bold bg-indigo-600 hover:bg-indigo-500 text-white shadow-xl shadow-indigo-500/25 dark:shadow-indigo-600/30 transition-all hover:scale-105 border-none cursor-pointer"
                    >
                        <flux:icon.rocket-launch class="size-4 mr-2" /> Start Deploying for Free
                    </flux:button>
                    
                    <flux:button 
                        href="#demo" 
                        variant="ghost" 
                        class="rounded-2xl px-7 py-3.5 text-sm sm:text-base font-semibold border border-zinc-300 dark:border-zinc-700 hover:bg-zinc-100 dark:hover:bg-zinc-800 text-zinc-800 dark:text-zinc-200 transition-all cursor-pointer"
                    >
                        <flux:icon.play class="size-4 mr-2 text-indigo-500" /> Explore Live Studio
                    </flux:button>
                </div>

                <!-- Trust Badges Row -->
                <div class="flex flex-wrap items-center justify-center gap-3 text-xs font-semibold text-zinc-600 dark:text-zinc-400 mb-16">
                    <div class="flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl bg-white/70 dark:bg-zinc-900/60 border border-zinc-200/80 dark:border-zinc-800 shadow-2xs">
                        <flux:icon.bolt class="size-3.5 text-amber-500" /> &lt;15ms Global Edge TTFB
                    </div>
                    <div class="flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl bg-white/70 dark:bg-zinc-900/60 border border-zinc-200/80 dark:border-zinc-800 shadow-2xs">
                        <flux:icon.cloud-arrow-up class="size-3.5 text-cyan-500" /> Cloudflare R2 / S3 Powered
                    </div>
                    <div class="flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl bg-white/70 dark:bg-zinc-900/60 border border-zinc-200/80 dark:border-zinc-800 shadow-2xs">
                        <flux:icon.shield-check class="size-3.5 text-emerald-500" /> Free Auto Let's Encrypt SSL
                    </div>
                    <div class="flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl bg-white/70 dark:bg-zinc-900/60 border border-zinc-200/80 dark:border-zinc-800 shadow-2xs">
                        <flux:icon.lock-closed class="size-3.5 text-purple-500" /> Password Locked Previews
                    </div>
                </div>

                <!-- Hero Mockup Window -->
                <div class="w-full max-w-5xl rounded-2xl p-2 sm:p-3 glass-panel border border-zinc-200/80 dark:border-zinc-800 shadow-2xl relative overflow-hidden group">
                    <div class="bg-white dark:bg-[#0c0c0e] rounded-xl border border-zinc-200/80 dark:border-zinc-800/80 overflow-hidden text-left">
                        <!-- Browser Bar -->
                        <div class="px-4 py-3 bg-zinc-100/90 dark:bg-zinc-900/90 border-b border-zinc-200/80 dark:border-zinc-800 flex items-center justify-between gap-3">
                            <div class="flex items-center gap-1.5">
                                <span class="size-3 rounded-full bg-rose-400 inline-block"></span>
                                <span class="size-3 rounded-full bg-amber-400 inline-block"></span>
                                <span class="size-3 rounded-full bg-emerald-400 inline-block"></span>
                            </div>

                            <div class="flex-1 max-w-md mx-auto px-3 py-1 rounded-lg bg-white dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 text-xs font-mono text-zinc-600 dark:text-zinc-400 flex items-center justify-between">
                                <div class="flex items-center gap-1.5 truncate">
                                    <flux:icon.lock-closed class="size-3 text-emerald-500 shrink-0" />
                                    <span class="text-zinc-900 dark:text-zinc-100 font-semibold">https://drophtml.de/s/hyperdrive</span>
                                </div>
                                <span class="text-[10px] px-1.5 py-0.5 rounded bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 font-sans font-bold">200 OK</span>
                            </div>

                            <div class="flex items-center gap-2 text-xs font-mono text-zinc-400 hidden sm:flex">
                                <span>SSL Valid</span>
                                <span>&bull;</span>
                                <span class="text-indigo-500">Edge Cached</span>
                            </div>
                        </div>

                        <!-- Mock Site Content Banner -->
                        <div class="p-6 sm:p-8 bg-gradient-to-b from-zinc-50 to-white dark:from-[#0c0c0e] dark:to-[#070709]">
                            <div class="flex flex-col md:flex-row items-center justify-between gap-6 border-b border-zinc-100 dark:border-zinc-800/80 pb-6 mb-6">
                                <div>
                                    <div class="inline-flex items-center gap-2 px-2.5 py-1 rounded-full bg-indigo-50 dark:bg-indigo-950/40 text-indigo-600 dark:text-indigo-400 text-xs font-mono font-semibold mb-2">
                                        git:main &bull; commit #a8f3b2 &bull; 0.74s build
                                    </div>
                                    <h3 class="text-xl sm:text-2xl font-black text-zinc-900 dark:text-white tracking-tight">
                                        Next-Gen Static Sites Without Server Overheads
                                    </h3>
                                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">
                                        Deployed via Webhook from GitHub &bull; Assets hosted on Cloudflare R2 &bull; 0ms cold starts
                                    </p>
                                </div>
                                <div class="flex items-center gap-2 shrink-0">
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 text-xs font-bold border border-emerald-500/20">
                                        <span class="size-1.5 rounded-full bg-emerald-500 animate-ping"></span>
                                        Live on Global Edge
                                    </span>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-xs">
                                <div class="p-4 rounded-xl bg-zinc-100/70 dark:bg-zinc-900/60 border border-zinc-200/60 dark:border-zinc-800/80">
                                    <span class="text-zinc-400 font-mono text-[10px] uppercase block mb-1">Code Bundle</span>
                                    <span class="text-base font-bold text-zinc-900 dark:text-zinc-100 font-mono">24.6 KB</span>
                                    <span class="text-[11px] text-zinc-500 block mt-0.5">HTML, CSS, lightweight JS</span>
                                </div>
                                <div class="p-4 rounded-xl bg-zinc-100/70 dark:bg-zinc-900/60 border border-zinc-200/60 dark:border-zinc-800/80">
                                    <span class="text-zinc-400 font-mono text-[10px] uppercase block mb-1">Cloud Media (R2/S3)</span>
                                    <span class="text-base font-bold text-emerald-600 dark:text-emerald-400 font-mono">148.2 MB</span>
                                    <span class="text-[11px] text-zinc-500 block mt-0.5">4K Videos, WebP images, PDFs</span>
                                </div>
                                <div class="p-4 rounded-xl bg-zinc-100/70 dark:bg-zinc-900/60 border border-zinc-200/60 dark:border-zinc-800/80">
                                    <span class="text-zinc-400 font-mono text-[10px] uppercase block mb-1">Response Latency</span>
                                    <span class="text-base font-bold text-indigo-600 dark:text-indigo-400 font-mono">11 ms</span>
                                    <span class="text-[11px] text-zinc-500 block mt-0.5">Frankfurt, London, New York</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</section>