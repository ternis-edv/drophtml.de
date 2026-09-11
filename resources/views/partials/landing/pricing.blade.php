<section id="pricing" class="py-24 relative z-10" x-data="{ annual: true }">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Header -->
        <div class="text-center mb-16">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full glass-panel mb-4 border border-indigo-500/30 text-indigo-600 dark:text-indigo-400 text-xs font-semibold uppercase tracking-wider">
                Transparent Plans
            </div>
            <h2 class="text-3xl md:text-5xl font-extrabold mb-4 text-zinc-900 dark:text-white tracking-tight">
                Simple, Honest Pricing
            </h2>
            <p class="text-zinc-600 dark:text-zinc-400 max-w-xl mx-auto text-base">
                Get started completely free. Upgrade when you need permanent retention, custom domains, and production automation.
            </p>

            <!-- Billing Toggle -->
            <div class="mt-8 inline-flex items-center gap-3 p-1.5 rounded-full bg-zinc-200/80 dark:bg-zinc-800/80 border border-zinc-300 dark:border-zinc-700">
                <button 
                    @click="annual = false"
                    :class="!annual ? 'bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white shadow-xs font-bold' : 'text-zinc-600 dark:text-zinc-400 font-medium'"
                    class="px-4 py-1.5 rounded-full text-xs transition-all cursor-pointer"
                >
                    Monthly
                </button>
                <button 
                    @click="annual = true"
                    :class="annual ? 'bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white shadow-xs font-bold' : 'text-zinc-600 dark:text-zinc-400 font-medium'"
                    class="px-4 py-1.5 rounded-full text-xs transition-all flex items-center gap-1.5 cursor-pointer"
                >
                    <span>Yearly</span>
                    <span class="px-1.5 py-0.5 rounded-full bg-emerald-500 text-white text-[10px] font-bold">Save 20%</span>
                </button>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 max-w-4xl mx-auto">
            
            <!-- Personal Free Plan -->
            <div class="glass-panel rounded-3xl p-8 border border-zinc-200 dark:border-zinc-800 relative overflow-hidden flex flex-col justify-between group hover:border-indigo-400 dark:hover:border-indigo-500/50 hover:shadow-xl transition-all">
                <div>
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-2xl font-bold text-zinc-900 dark:text-white">Community</h3>
                        <span class="px-2.5 py-0.5 rounded-full bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400 text-xs font-medium">Free Forever</span>
                    </div>
                    <div class="flex items-baseline gap-2 mb-4">
                        <span class="text-5xl font-black text-zinc-900 dark:text-white">&euro;0</span>
                        <span class="text-zinc-500 text-sm">/month</span>
                    </div>
                    <p class="text-zinc-600 dark:text-zinc-400 text-sm mb-6">Ideal for quick prototypes, developer portfolios, student projects, and staging tests.</p>
                    
                    <ul class="space-y-3.5 mb-8 text-sm">
                        <li class="flex items-center gap-3 text-zinc-700 dark:text-zinc-300">
                            <flux:icon.check-circle class="w-5 h-5 text-indigo-500 shrink-0" /> Unlimited temporary sites (30-day auto cleanup)
                        </li>
                        <li class="flex items-center gap-3 text-zinc-700 dark:text-zinc-300">
                            <flux:icon.check-circle class="w-5 h-5 text-indigo-500 shrink-0" /> Up to 50MB per single site upload
                        </li>
                        <li class="flex items-center gap-3 text-zinc-700 dark:text-zinc-300">
                            <flux:icon.check-circle class="w-5 h-5 text-indigo-500 shrink-0" /> 100MB Cloud Asset Storage
                        </li>
                        <li class="flex items-center gap-3 text-zinc-700 dark:text-zinc-300">
                            <flux:icon.check-circle class="w-5 h-5 text-indigo-500 shrink-0" /> Global Edge CDN distribution
                        </li>
                        <li class="flex items-center gap-3 text-zinc-700 dark:text-zinc-300">
                            <flux:icon.check-circle class="w-5 h-5 text-indigo-500 shrink-0" /> Free <code>{slug}.drophtml.de</code> subdomains
                        </li>
                        <li class="flex items-center gap-3 text-zinc-700 dark:text-zinc-300">
                            <flux:icon.check-circle class="w-5 h-5 text-indigo-500 shrink-0" /> GitHub OAuth & Webhook auto-deploys
                        </li>
                        <li class="flex items-center gap-3 text-zinc-700 dark:text-zinc-300">
                            <flux:icon.check-circle class="w-5 h-5 text-indigo-500 shrink-0" /> In-browser Code Studio & File Manager
                        </li>
                    </ul>
                </div>
                
                <flux:button :href="route('register')" variant="ghost" class="w-full justify-center border border-zinc-300 dark:border-zinc-700 hover:bg-zinc-100 dark:hover:bg-zinc-800 font-bold">
                    Start for Free
                </flux:button>
            </div>

            <!-- Pro / Commercial Plan -->
            <div class="glass-panel rounded-3xl p-8 border-2 border-indigo-500/80 relative overflow-hidden flex flex-col justify-between shadow-2xl shadow-indigo-500/10">
                <div class="absolute top-0 right-8 transform -translate-y-1/2">
                    <span class="bg-gradient-to-r from-indigo-600 to-purple-600 text-white text-[11px] font-extrabold px-3 py-1 rounded-full uppercase tracking-wider shadow-md">
                        Most Popular
                    </span>
                </div>
                
                <div>
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-2xl font-bold text-zinc-900 dark:text-white">Pro & Commercial</h3>
                        <span class="px-2.5 py-0.5 rounded-full bg-indigo-50 dark:bg-indigo-950/50 text-indigo-600 dark:text-indigo-400 text-xs font-bold">Production Ready</span>
                    </div>
                    <div class="flex items-baseline gap-2 mb-4">
                        <span class="text-5xl font-black text-zinc-900 dark:text-white" x-text="annual ? '&euro;4.80' : '&euro;6'"></span>
                        <span class="text-zinc-500 text-sm">/month <span x-show="annual" class="text-xs text-indigo-500 font-medium">(billed annually)</span></span>
                    </div>
                    <p class="text-zinc-600 dark:text-zinc-400 text-sm mb-6">For freelancers, agencies, and teams requiring permanent domains, custom SSL, and SLA.</p>
                    
                    <ul class="space-y-3.5 mb-8 text-sm">
                        <li class="flex items-center gap-3 text-zinc-700 dark:text-zinc-300">
                            <flux:icon.check-circle class="w-5 h-5 text-emerald-500 shrink-0" /> <strong>Permanent site retention</strong> (never expires)
                        </li>
                        <li class="flex items-center gap-3 text-zinc-700 dark:text-zinc-300">
                            <flux:icon.check-circle class="w-5 h-5 text-emerald-500 shrink-0" /> <strong>Unlimited custom domains</strong> with auto SSL
                        </li>
                        <li class="flex items-center gap-3 text-zinc-700 dark:text-zinc-300">
                            <flux:icon.check-circle class="w-5 h-5 text-emerald-500 shrink-0" /> <strong>Unlimited Cloud Asset Storage</strong> & 4K streaming
                        </li>
                        <li class="flex items-center gap-3 text-zinc-700 dark:text-zinc-300">
                            <flux:icon.check-circle class="w-5 h-5 text-emerald-500 shrink-0" /> <strong>Password protection</strong> for client previews
                        </li>
                        <li class="flex items-center gap-3 text-zinc-700 dark:text-zinc-300">
                            <flux:icon.check-circle class="w-5 h-5 text-emerald-500 shrink-0" /> Full REST API & Personal Access Tokens
                        </li>
                        <li class="flex items-center gap-3 text-zinc-700 dark:text-zinc-300">
                            <flux:icon.check-circle class="w-5 h-5 text-emerald-500 shrink-0" /> Real-time traffic analytics & 1-click rollbacks
                        </li>
                        <li class="flex items-center gap-3 text-zinc-700 dark:text-zinc-300">
                            <flux:icon.check-circle class="w-5 h-5 text-emerald-500 shrink-0" /> Priority infrastructure support & 99.99% SLA
                        </li>
                    </ul>
                </div>
                
                <flux:button :href="route('register')" variant="primary" class="w-full justify-center bg-indigo-600 hover:bg-indigo-500 text-white font-bold shadow-md border-none">
                    Get Started with Pro
                </flux:button>
            </div>

        </div>
    </div>
</section>