<section id="benchmarks" class="py-20 relative z-10 border-y border-zinc-200 dark:border-zinc-800/60 bg-zinc-50/50 dark:bg-[#070709]/60">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-6 md:gap-8">
            
            <!-- Stat 1: Sites Deployed -->
            <div class="glass-panel p-6 rounded-2xl border border-zinc-200 dark:border-zinc-800 text-center relative overflow-hidden group hover:border-indigo-500/40 transition-colors">
                <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-indigo-500 to-purple-500"></div>
                <div class="text-3xl sm:text-4xl md:text-5xl font-black text-transparent bg-clip-text bg-gradient-to-r from-indigo-600 to-purple-600 dark:from-indigo-400 dark:to-purple-400 mb-2">
                    {{ number_format(max(12480, ($totalSites ?? 0) > 0 ? ($totalSites ?? 0) : 12480)) }}+
                </div>
                <div class="text-zinc-500 dark:text-zinc-400 text-xs font-semibold uppercase tracking-wider">
                    Sites Deployed
                </div>
            </div>

            <!-- Stat 2: Avg Deploy Time -->
            <div class="glass-panel p-6 rounded-2xl border border-zinc-200 dark:border-zinc-800 text-center relative overflow-hidden group hover:border-purple-500/40 transition-colors">
                <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-purple-500 to-pink-500"></div>
                <div class="text-3xl sm:text-4xl md:text-5xl font-black text-transparent bg-clip-text bg-gradient-to-r from-purple-600 to-pink-600 dark:from-purple-400 dark:to-pink-400 mb-2">
                    &lt; 840ms
                </div>
                <div class="text-zinc-500 dark:text-zinc-400 text-xs font-semibold uppercase tracking-wider">
                    Avg Deploy Speed
                </div>
            </div>

            <!-- Stat 3: Uptime SLA -->
            <div class="glass-panel p-6 rounded-2xl border border-zinc-200 dark:border-zinc-800 text-center relative overflow-hidden group hover:border-emerald-500/40 transition-colors">
                <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-emerald-500 to-teal-500"></div>
                <div class="text-3xl sm:text-4xl md:text-5xl font-black text-zinc-900 dark:text-white mb-2">
                    99.99%
                </div>
                <div class="text-zinc-500 dark:text-zinc-400 text-xs font-semibold uppercase tracking-wider">
                    Global Edge Uptime
                </div>
            </div>

            <!-- Stat 4: Global Edge PoPs -->
            <div class="glass-panel p-6 rounded-2xl border border-zinc-200 dark:border-zinc-800 text-center relative overflow-hidden group hover:border-cyan-500/40 transition-colors">
                <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-cyan-500 to-indigo-500"></div>
                <div class="text-3xl sm:text-4xl md:text-5xl font-black text-transparent bg-clip-text bg-gradient-to-r from-cyan-600 to-indigo-600 dark:from-cyan-400 dark:to-indigo-400 mb-2">
                    300+
                </div>
                <div class="text-zinc-500 dark:text-zinc-400 text-xs font-semibold uppercase tracking-wider">
                    Edge CDN PoPs Worldwide
                </div>
            </div>

        </div>

    </div>
</section>