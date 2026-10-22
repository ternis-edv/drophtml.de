<section class="py-24 relative z-10 reveal" x-intersect.once="$el.classList.add('is-shown')">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <div class="relative rounded-2xl p-8 sm:p-12 lg:p-16 text-center overflow-hidden border border-zinc-800 bg-zinc-900 dark:bg-zinc-900/60">
            <div class="relative z-10 max-w-3xl mx-auto space-y-6">
                <!-- Badge -->
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 border border-white/15 text-brand-200 text-xs font-semibold uppercase tracking-wider">
                    <flux:icon.rocket-launch class="w-3.5 h-3.5 text-brand-300" />
                    <span>Deploy in seconds &bull; Free forever</span>
                </div>

                <!-- Headline -->
                <h2 class="text-3xl sm:text-4xl md:text-5xl font-bold text-white tracking-tight leading-tight">
                    Ready to Ship Your Next Static Site?
                </h2>

                <!-- Subtext -->
                <p class="text-zinc-300/90 text-base sm:text-lg leading-relaxed max-w-2xl mx-auto">
                    Join developers, designers, and agencies deploying lightning-fast websites with drag &amp; drop, GitHub CI/CD, and cloud static media storage.
                </p>

                <!-- Actions -->
                <div class="pt-4 flex flex-col sm:flex-row items-center justify-center gap-4">
                    @auth
                        <flux:button size="base" variant="primary" class="w-full sm:w-auto bg-white text-zinc-900 hover:bg-zinc-100 font-semibold px-8 py-3 rounded-xl transition-colors border-none cursor-pointer" :href="route('dashboard')">
                            Go to Your Dashboard
                            <flux:icon.arrow-right class="size-4 ml-1" />
                        </flux:button>
                    @else
                        <flux:button size="base" variant="primary" class="w-full sm:w-auto bg-white text-zinc-900 hover:bg-zinc-100 font-semibold px-8 py-3 rounded-xl transition-colors border-none cursor-pointer" :href="route('register')">
                            Create Free Account
                        </flux:button>
                        <flux:button size="base" variant="ghost" class="w-full sm:w-auto text-white hover:bg-white/10 border border-white/20 font-semibold px-6 py-3 rounded-xl cursor-pointer" :href="route('login')">
                            Existing User? Sign In
                        </flux:button>
                    @endauth
                </div>

                <!-- Micro trust indicators -->
                <div class="pt-6 flex flex-wrap items-center justify-center gap-6 text-xs text-zinc-400">
                    <div class="flex items-center gap-1.5">
                        <flux:icon.check class="w-4 h-4 text-emerald-400" />
                        <span>No credit card required</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <flux:icon.check class="w-4 h-4 text-emerald-400" />
                        <span>Instant deployment in &lt; 1s</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <flux:icon.check class="w-4 h-4 text-emerald-400" />
                        <span>99.99% Global Edge SLA</span>
                    </div>
                </div>

            </div>
        </div>

    </div>
</section>
