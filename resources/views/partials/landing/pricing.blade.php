<section id="pricing" class="py-24 relative z-10">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <div class="text-center mb-16" x-data="{ shown: false }" x-intersect.once="shown = true">
            <h2 :class="shown ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-10'" class="transition-all duration-700 ease-out text-3xl md:text-4xl font-bold mb-4 text-white">Simple, Transparent Pricing</h2>
            <p :class="shown ? 'opacity-100 translate-y-0 delay-100' : 'opacity-0 translate-y-10'" class="transition-all duration-700 ease-out text-zinc-400">Everything you need to host static files, absolutely free for individuals.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 max-w-4xl mx-auto" x-data="{ shown: false }" x-intersect.once="shown = true">
            
            <!-- Personal Free Plan -->
            <div :class="shown ? 'opacity-100 translate-y-0 delay-100' : 'opacity-0 translate-y-10'" class="transition-all duration-700 ease-out glass-panel rounded-3xl p-8 border border-zinc-800 relative overflow-hidden group">
                <div class="absolute inset-0 bg-gradient-to-b from-indigo-500/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
                <div class="relative z-10">
                    <h3 class="text-2xl font-bold text-white mb-2">Personal</h3>
                    <div class="flex items-baseline gap-2 mb-6">
                        <span class="text-5xl font-extrabold text-white">$0</span>
                        <span class="text-zinc-500">/forever</span>
                    </div>
                    <p class="text-zinc-400 mb-8 h-12">Perfect for side projects, portfolios, and rapid prototyping.</p>
                    
                    <ul class="space-y-4 mb-8">
                        <li class="flex items-center gap-3 text-zinc-300">
                            <flux:icon.check-circle class="w-5 h-5 text-indigo-400" /> Unlimited temporary sites
                        </li>
                        <li class="flex items-center gap-3 text-zinc-300">
                            <flux:icon.check-circle class="w-5 h-5 text-indigo-400" /> Up to 50MB per upload
                        </li>
                        <li class="flex items-center gap-3 text-zinc-300">
                            <flux:icon.check-circle class="w-5 h-5 text-indigo-400" /> Global Edge Network
                        </li>
                        <li class="flex items-center gap-3 text-zinc-300">
                            <flux:icon.check-circle class="w-5 h-5 text-indigo-400" /> Free drophtml.de subdomains
                        </li>
                    </ul>
                    
                    <flux:button :href="route('register')" variant="primary" class="w-full justify-center">Start for free</flux:button>
                </div>
            </div>

            <!-- Commercial Plan -->
            <div :class="shown ? 'opacity-100 translate-y-0 delay-200' : 'opacity-0 translate-y-10'" class="transition-all duration-700 ease-out glass-panel rounded-3xl p-8 border border-zinc-800 relative overflow-hidden group">
                <div class="absolute inset-0 bg-gradient-to-b from-purple-500/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
                <div class="absolute top-0 right-8 transform -translate-y-1/2">
                    <span class="bg-purple-500 text-white text-xs font-bold px-3 py-1 rounded-full uppercase tracking-wide">Business</span>
                </div>
                
                <div class="relative z-10">
                    <h3 class="text-2xl font-bold text-white mb-2">Commercial</h3>
                    <div class="flex items-baseline gap-2 mb-6">
                        <span class="text-5xl font-extrabold text-white">Custom</span>
                    </div>
                    <p class="text-zinc-400 mb-8 h-12">For agencies and companies needing permanent hosting and custom domains.</p>
                    
                    <ul class="space-y-4 mb-8">
                        <li class="flex items-center gap-3 text-zinc-300">
                            <flux:icon.check-circle class="w-5 h-5 text-purple-400" /> Everything in Personal
                        </li>
                        <li class="flex items-center gap-3 text-zinc-300">
                            <flux:icon.check-circle class="w-5 h-5 text-purple-400" /> Permanent sites
                        </li>
                        <li class="flex items-center gap-3 text-zinc-300">
                            <flux:icon.check-circle class="w-5 h-5 text-purple-400" /> Custom domains & auto SSL
                        </li>
                        <li class="flex items-center gap-3 text-zinc-300">
                            <flux:icon.check-circle class="w-5 h-5 text-purple-400" /> Password protection
                        </li>
                    </ul>
                    
                    <flux:button href="mailto:business@drophtml.de" variant="ghost" class="w-full justify-center border border-zinc-700">Contact Us</flux:button>
                </div>
            </div>

        </div>
    </div>
</section>