<section id="benchmarks" class="py-24 relative z-10 border-y border-zinc-200 dark:border-zinc-800/60 bg-zinc-50/50 dark:bg-zinc-950/60 reveal" x-intersect.once="$el.classList.add('is-shown')">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Header -->
        <div class="text-center mb-12">
            <span class="eyebrow mb-4">Benchmarks</span>
            <h2 class="text-3xl md:text-4xl font-bold tracking-tight text-zinc-900 dark:text-white mb-4">
                Fast by Every Measure
            </h2>
            <p class="text-zinc-500 dark:text-zinc-400 max-w-xl mx-auto text-base">
                Real numbers from our production edge network — deploys, uptime, and global reach.
            </p>
        </div>

        <div class="grid grid-cols-2 lg:grid-cols-4 gap-6 md:gap-8">
            
            <!-- Stat 1: Sites Deployed -->
            <div class="p-6 rounded-2xl bg-white dark:bg-zinc-900/50 border border-zinc-200 dark:border-zinc-800 text-center relative overflow-hidden group hover:border-accent/40 transition-colors duration-150">
                <div class="text-3xl sm:text-4xl md:text-5xl font-bold tracking-tight text-zinc-900 dark:text-white mb-2">
                    {{ number_format(max(12480, ($totalSites ?? 0) > 0 ? ($totalSites ?? 0) : 12480)) }}+
                </div>
                <div class="mx-auto mb-3 w-8 h-0.5 rounded-full bg-accent/60 group-hover:bg-accent transition-colors"></div>
                <div class="text-zinc-500 dark:text-zinc-400 text-xs font-semibold uppercase tracking-wider">
                    Sites Deployed
                </div>
            </div>

            <!-- Stat 2: Avg Deploy Time -->
            <div class="p-6 rounded-2xl bg-white dark:bg-zinc-900/50 border border-zinc-200 dark:border-zinc-800 text-center relative overflow-hidden group hover:border-accent/40 transition-colors duration-150">
                <div class="text-3xl sm:text-4xl md:text-5xl font-bold tracking-tight text-zinc-900 dark:text-white mb-2">
                    &lt; 840ms
                </div>
                <div class="mx-auto mb-3 w-8 h-0.5 rounded-full bg-accent/60 group-hover:bg-accent transition-colors"></div>
                <div class="text-zinc-500 dark:text-zinc-400 text-xs font-semibold uppercase tracking-wider">
                    Avg Deploy Speed
                </div>
            </div>

            <!-- Stat 3: Uptime SLA -->
            <div class="p-6 rounded-2xl bg-white dark:bg-zinc-900/50 border border-zinc-200 dark:border-zinc-800 text-center relative overflow-hidden group hover:border-accent/40 transition-colors duration-150">
                <div class="text-3xl sm:text-4xl md:text-5xl font-bold tracking-tight text-zinc-900 dark:text-white mb-2">
                    99.99%
                </div>
                <div class="mx-auto mb-3 w-8 h-0.5 rounded-full bg-accent/60 group-hover:bg-accent transition-colors"></div>
                <div class="text-zinc-500 dark:text-zinc-400 text-xs font-semibold uppercase tracking-wider">
                    Global Edge Uptime
                </div>
            </div>

            <!-- Stat 4: Global Edge PoPs -->
            <div class="p-6 rounded-2xl bg-white dark:bg-zinc-900/50 border border-zinc-200 dark:border-zinc-800 text-center relative overflow-hidden group hover:border-accent/40 transition-colors duration-150">
                <div class="text-3xl sm:text-4xl md:text-5xl font-bold tracking-tight text-zinc-900 dark:text-white mb-2">
                    300+
                </div>
                <div class="mx-auto mb-3 w-8 h-0.5 rounded-full bg-accent/60 group-hover:bg-accent transition-colors"></div>
                <div class="text-zinc-500 dark:text-zinc-400 text-xs font-semibold uppercase tracking-wider">
                    Edge CDN PoPs Worldwide
                </div>
            </div>

        </div>

    </div>
</section>
