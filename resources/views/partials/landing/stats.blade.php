<section class="py-24 relative z-10 border-t border-zinc-800/50 bg-black/20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-8 md:gap-12" x-data="{ shown: false }" x-intersect.once="shown = true">
            
            <!-- Stat 1 -->
            <div :class="shown ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-10'" class="transition-all duration-700 ease-out text-center">
                <div class="text-4xl md:text-5xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-indigo-400 to-purple-400 mb-2"
                     x-data="{ count: 0, target: 12048 }"
                     x-intersect="
                        if(count === 0) {
                            let duration = 2000;
                            let start = performance.now();
                            let step = (timestamp) => {
                                let progress = Math.min((timestamp - start) / duration, 1);
                                count = Math.floor(progress * target);
                                if (progress < 1) window.requestAnimationFrame(step);
                            };
                            window.requestAnimationFrame(step);
                        }
                     "
                     x-text="count.toLocaleString()">
                    0
                </div>
                <div class="text-zinc-500 text-sm font-medium uppercase tracking-wider">Sites Deployed</div>
            </div>

            <!-- Stat 2 -->
            <div :class="shown ? 'opacity-100 translate-y-0 delay-100' : 'opacity-0 translate-y-10'" class="transition-all duration-700 ease-out text-center">
                <div class="text-4xl md:text-5xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-purple-400 to-pink-400 mb-2"
                     x-data="{ count: 0, target: 800 }"
                     x-intersect="
                        if(count === 0) {
                            let duration = 2000;
                            let start = performance.now();
                            let step = (timestamp) => {
                                let progress = Math.min((timestamp - start) / duration, 1);
                                count = Math.floor(progress * target);
                                if (progress < 1) window.requestAnimationFrame(step);
                            };
                            window.requestAnimationFrame(step);
                        }
                     "
                     x-text="count + 'ms'">
                    0ms
                </div>
                <div class="text-zinc-500 text-sm font-medium uppercase tracking-wider">Avg Deploy Time</div>
            </div>

            <!-- Stat 3 -->
            <div :class="shown ? 'opacity-100 translate-y-0 delay-200' : 'opacity-0 translate-y-10'" class="transition-all duration-700 ease-out text-center">
                <div class="text-4xl md:text-5xl font-extrabold text-white mb-2">
                    99.9%
                </div>
                <div class="text-zinc-500 text-sm font-medium uppercase tracking-wider">Uptime</div>
            </div>

            <!-- Stat 4 -->
            <div :class="shown ? 'opacity-100 translate-y-0 delay-300' : 'opacity-0 translate-y-10'" class="transition-all duration-700 ease-out text-center">
                <div class="text-4xl md:text-5xl font-extrabold text-white mb-2"
                     x-data="{ count: 0, target: 24 }"
                     x-intersect="
                        if(count === 0) {
                            let duration = 1500;
                            let start = performance.now();
                            let step = (timestamp) => {
                                let progress = Math.min((timestamp - start) / duration, 1);
                                count = Math.floor(progress * target);
                                if (progress < 1) window.requestAnimationFrame(step);
                            };
                            window.requestAnimationFrame(step);
                        }
                     "
                     x-text="count">
                    0
                </div>
                <div class="text-zinc-500 text-sm font-medium uppercase tracking-wider">Global Edge Nodes</div>
            </div>

        </div>
    </div>
</section>