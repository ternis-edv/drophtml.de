<section class="relative pt-32 pb-16 lg:pt-44 lg:pb-24 overflow-hidden">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10 text-center">
        <div x-data="{ 
            shown: false,
            headlines: [
                'Ship HTML in seconds.',
                'Deploy client previews.',
                'Automate staging sites.',
                'Share prototypes instantly.'
            ],
            currentHeadlineIndex: 0,
            init() {
                this.shown = true;
                setInterval(() => {
                    this.currentHeadlineIndex = (this.currentHeadlineIndex + 1) % this.headlines.length;
                }, 3200);
            }
        }">
            <div :class="shown ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-10'" class="transition-all duration-1000 ease-out flex flex-col items-center">
                
                <!-- Announcement Pill -->
                <a href="#features" class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full glass-panel mb-8 border border-indigo-500/30 text-indigo-600 dark:text-indigo-300 text-xs font-medium hover:bg-indigo-500/10 transition-all shadow-sm">
                    <span class="flex h-2 w-2 relative">
                      <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-indigo-400 opacity-75"></span>
                      <span class="relative inline-flex rounded-full h-2 w-2 bg-indigo-500"></span>
                    </span>
                    <span class="font-semibold">DropHTML Engine 2.0</span> &bull; Automatic Let's Encrypt SSL &bull; Global Edge CDN
                </a>

                <!-- Main Heading with Rotating Accent -->
                <h1 class="text-4xl sm:text-6xl lg:text-7xl font-black tracking-tight mb-6 text-zinc-900 dark:text-white leading-[1.15] max-w-4xl">
                    Instant static site hosting.<br/>
                    <span 
                        class="text-transparent bg-clip-text bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-500 dark:from-indigo-400 dark:via-purple-400 dark:to-pink-400 transition-all duration-500 inline-block"
                        x-text="headlines[currentHeadlineIndex]"
                    ></span>
                </h1>
                
                <!-- Subtitle -->
                <p class="text-base sm:text-lg md:text-xl text-zinc-600 dark:text-zinc-400 max-w-2xl mx-auto mb-10 leading-relaxed">
                    Drag, drop, and publish HTML or ZIP files in seconds. Connect GitHub repositories with zero configuration, protect client staging behind passwords, and link custom domains with automatic SSL.
                </p>

                <!-- Primary CTAs -->
                <div class="flex flex-col sm:flex-row items-center gap-4 mb-12">
                    <flux:button 
                        :href="route('register')" 
                        variant="primary" 
                        class="rounded-full px-8 py-3.5 text-base font-bold bg-indigo-600 hover:bg-indigo-500 text-white shadow-lg shadow-indigo-500/25 dark:shadow-indigo-600/30 transition-all hover:scale-105 border-none"
                    >
                        <flux:icon.rocket-launch class="w-4 h-4 mr-2" /> Start Deploying for Free
                    </flux:button>
                    
                    <flux:button 
                        href="#demo" 
                        variant="ghost" 
                        class="rounded-full px-8 py-3.5 text-base font-semibold border border-zinc-300 hover:bg-zinc-100 dark:text-white dark:border-zinc-700 dark:hover:bg-zinc-800 transition-all"
                    >
                        Try Interactive Demo &darr;
                    </flux:button>
                </div>

                <!-- Feature Badges Row -->
                <div class="flex flex-wrap items-center justify-center gap-3 text-xs font-semibold text-zinc-600 dark:text-zinc-400">
                    <div class="flex items-center gap-1.5 px-3 py-1 rounded-full bg-zinc-100 dark:bg-zinc-900/60 border border-zinc-200 dark:border-zinc-800">
                        <flux:icon.check class="w-3.5 h-3.5 text-emerald-500" /> Single HTML or ZIP
                    </div>
                    <div class="flex items-center gap-1.5 px-3 py-1 rounded-full bg-zinc-100 dark:bg-zinc-900/60 border border-zinc-200 dark:border-zinc-800">
                        <flux:icon.check class="w-3.5 h-3.5 text-emerald-500" /> Automatic Webhooks
                    </div>
                    <div class="flex items-center gap-1.5 px-3 py-1 rounded-full bg-zinc-100 dark:bg-zinc-900/60 border border-zinc-200 dark:border-zinc-800">
                        <flux:icon.check class="w-3.5 h-3.5 text-emerald-500" /> Password Locked Previews
                    </div>
                    <div class="flex items-center gap-1.5 px-3 py-1 rounded-full bg-zinc-100 dark:bg-zinc-900/60 border border-zinc-200 dark:border-zinc-800">
                        <flux:icon.check class="w-3.5 h-3.5 text-emerald-500" /> REST API & Tokens
                    </div>
                </div>

            </div>
        </div>
    </div>
</section>