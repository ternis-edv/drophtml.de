<section class="relative pt-32 pb-20 lg:pt-48 lg:pb-32 overflow-hidden">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10 text-center">
        <div x-data="{ shown: false }" x-intersect.once="shown = true">
            <div :class="shown ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-10'" class="transition-all duration-1000 ease-out flex flex-col items-center">
                
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full glass-panel mb-8 border border-indigo-500/30 text-indigo-600 dark:text-indigo-300 text-xs font-medium cursor-pointer hover:bg-indigo-500/10 transition-colors">
                    <span class="flex h-2 w-2 relative">
                      <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-indigo-400 opacity-75"></span>
                      <span class="relative inline-flex rounded-full h-2 w-2 bg-indigo-500"></span>
                    </span>
                    DropHTML API v1 is now live
                </div>

                <h1 class="text-5xl md:text-7xl font-extrabold tracking-tight mb-6 text-zinc-900 dark:text-white leading-tight">
                    Ship HTML in <span class="text-transparent bg-clip-text bg-gradient-to-r from-indigo-500 via-purple-500 to-pink-500 dark:from-indigo-400 dark:via-purple-400 dark:to-pink-400">seconds.</span><br/>
                    Not hours.
                </h1>
                
                <p class="text-lg md:text-xl text-zinc-600 dark:text-zinc-400 max-w-2xl mx-auto mb-10 leading-relaxed">
                    The fastest way to deploy static sites, landing pages, and prototypes. Drag, drop, and share your URL instantly. No config required.
                </p>

                <div class="flex flex-col sm:flex-row items-center gap-4">
                    <flux:button :href="route('register')" size="base" variant="primary" class="rounded-full px-8 py-3 text-lg bg-indigo-600 text-white hover:bg-indigo-700 dark:bg-white dark:text-black dark:hover:bg-zinc-200 border-none shadow-[0_0_40px_-10px_rgba(79,70,229,0.3)] dark:shadow-[0_0_40px_-10px_rgba(255,255,255,0.3)] transition-all hover:scale-105">
                        Start Deploying for Free
                    </flux:button>
                    <flux:button href="#demo" size="base" variant="ghost" class="rounded-full px-8 py-3 text-lg text-zinc-900 border border-zinc-300 hover:bg-zinc-100 dark:text-white dark:border-zinc-700 dark:hover:bg-zinc-800 transition-all">
                        Try Interactive Demo
                    </flux:button>
                </div>
            </div>
        </div>
    </div>
</section>