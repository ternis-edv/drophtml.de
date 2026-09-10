<section id="features" class="py-24 relative z-10">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <div class="text-center mb-16" x-data="{ shown: false }" x-intersect.once="shown = true">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full glass-panel mb-4 border border-indigo-500/30 text-indigo-600 dark:text-indigo-400 text-xs font-semibold uppercase tracking-wider">
                Feature Highlights
            </div>
            <h2 :class="shown ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-10'" class="transition-all duration-700 ease-out text-3xl md:text-5xl font-extrabold mb-4 text-zinc-900 dark:text-white tracking-tight">
                Everything You Need. Zero Fluff.
            </h2>
            <p :class="shown ? 'opacity-100 translate-y-0 delay-100' : 'opacity-0 translate-y-10'" class="transition-all duration-700 ease-out text-zinc-600 dark:text-zinc-400 max-w-xl mx-auto text-base">
                Engineered for speed, developer simplicity, and peace of mind.
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6" x-data="{ shown: false }" x-intersect.once="shown = true">
            
            <!-- Card 1: GitHub Auto-Deploy (Large) -->
            <div :class="shown ? 'opacity-100 translate-y-0 delay-100' : 'opacity-0 translate-y-10'" class="transition-all duration-700 ease-out md:col-span-2 glass-panel rounded-3xl p-8 border border-zinc-200 dark:border-zinc-800 hover:border-indigo-400 dark:hover:border-indigo-500/50 hover:-translate-y-1 transition-all group overflow-hidden relative">
                <div class="absolute right-0 top-0 w-64 h-64 bg-indigo-500/10 rounded-full blur-[80px] group-hover:bg-indigo-500/20 transition-all duration-500"></div>
                <div class="relative z-10">
                    <div class="w-12 h-12 bg-indigo-100 dark:bg-indigo-500/20 text-indigo-600 dark:text-indigo-400 rounded-2xl flex items-center justify-center mb-6 ring-1 ring-indigo-500/30 group-hover:ring-indigo-500/50 transition-all">
                        <flux:icon.rocket-launch class="w-6 h-6" />
                    </div>
                    <h3 class="text-2xl font-bold mb-3 text-zinc-900 dark:text-white">GitHub CI/CD & Auto-Deploy</h3>
                    <p class="text-zinc-600 dark:text-zinc-400 mb-6 max-w-md text-sm sm:text-base">
                        Connect your GitHub account with one click. Every push to your default branch triggers an automatic redeploy through secure webhooks with instant deployment logs.
                    </p>
                    <div class="bg-zinc-900 dark:bg-[#09090b] rounded-xl p-5 font-mono text-xs sm:text-sm border border-zinc-800 text-zinc-300 shadow-inner">
                        <div class="flex items-center justify-between pb-2 mb-2 border-b border-zinc-800 text-zinc-500 text-[11px]">
                            <span>Terminal (Push Event)</span>
                            <span class="text-emerald-400 flex items-center gap-1"><span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span> webhook 200 OK</span>
                        </div>
                        <span class="text-indigo-400 font-bold">$ git</span> push origin main<br>
                        <span class="text-zinc-500 mt-1.5 block">Resolving deltas: 100% (4/4), completed with 4 local objects.</span>
                        <span class="text-emerald-400 mt-1.5 block">&check; GitHub webhook received payload</span>
                        <span class="text-emerald-400 mt-0.5 block">&check; Site redeployed successfully to https://my-landing.drophtml.de</span>
                    </div>
                </div>
            </div>

            <!-- Card 2: Password Protection (Interactive) -->
            <div 
                x-data="{ isProtected: true }"
                :class="shown ? 'opacity-100 translate-y-0 delay-200' : 'opacity-0 translate-y-10'" 
                class="transition-all duration-700 ease-out glass-panel rounded-3xl p-8 border border-zinc-200 dark:border-zinc-800 hover:border-purple-400 dark:hover:border-purple-500/50 hover:-translate-y-1 transition-all group relative overflow-hidden flex flex-col justify-between"
            >
                <div class="absolute right-[-20%] bottom-[-20%] w-48 h-48 bg-purple-500/10 rounded-full blur-[60px] group-hover:bg-purple-500/20 transition-all duration-500"></div>
                <div class="relative z-10">
                    <div class="w-12 h-12 bg-purple-100 dark:bg-purple-500/20 text-purple-600 dark:text-purple-400 rounded-2xl flex items-center justify-center mb-6 ring-1 ring-purple-500/30 group-hover:ring-purple-500/50 transition-all">
                        <flux:icon.lock-closed class="w-6 h-6" />
                    </div>
                    <h3 class="text-xl font-bold mb-2 text-zinc-900 dark:text-white">Password Protection</h3>
                    <p class="text-zinc-600 dark:text-zinc-400 text-sm">
                        Sharing a private client staging preview or confidential portfolio? Lock any site behind a password with a single toggle and session authentication.
                    </p>
                </div>
                
                <div class="mt-6 bg-zinc-100 dark:bg-[#09090b] border border-zinc-200 dark:border-zinc-800 rounded-2xl p-4 flex items-center justify-between relative z-10">
                    <div>
                        <div class="text-xs font-bold text-zinc-900 dark:text-white" x-text="isProtected ? 'Password Locked' : 'Public Access'"></div>
                        <div class="text-[11px] text-zinc-500" x-text="isProtected ? 'Requires PIN to access' : 'Accessible by anyone'"></div>
                    </div>
                    <button 
                        @click="isProtected = !isProtected" 
                        type="button"
                        :class="isProtected ? 'bg-purple-600' : 'bg-zinc-400 dark:bg-zinc-700'"
                        class="w-12 h-6.5 rounded-full relative transition-colors cursor-pointer p-0.5"
                    >
                        <div 
                            :class="isProtected ? 'translate-x-5.5' : 'translate-x-0'"
                            class="w-5.5 h-5.5 bg-white rounded-full shadow-md transition-transform"
                        ></div>
                    </button>
                </div>
            </div>

            <!-- Card 3: Custom Domains & Auto SSL -->
            <div 
                x-data="{ verified: true }"
                :class="shown ? 'opacity-100 translate-y-0 delay-300' : 'opacity-0 translate-y-10'" 
                class="transition-all duration-700 ease-out glass-panel rounded-3xl p-8 border border-zinc-200 dark:border-zinc-800 hover:border-pink-400 dark:hover:border-pink-500/50 hover:-translate-y-1 transition-all group relative overflow-hidden flex flex-col justify-between"
            >
                <div class="absolute left-[-20%] bottom-[-20%] w-48 h-48 bg-pink-500/10 rounded-full blur-[60px] group-hover:bg-pink-500/20 transition-all duration-500"></div>
                <div class="relative z-10">
                    <div class="w-12 h-12 bg-pink-100 dark:bg-pink-500/20 text-pink-600 dark:text-pink-400 rounded-2xl flex items-center justify-center mb-6 ring-1 ring-pink-500/30 group-hover:ring-pink-500/50 transition-all">
                        <flux:icon.globe-alt class="w-6 h-6" />
                    </div>
                    <h3 class="text-xl font-bold mb-2 text-zinc-900 dark:text-white">Custom Domains & SSL</h3>
                    <p class="text-zinc-600 dark:text-zinc-400 text-sm">
                        Map your own apex domain or subdomains. Automatic DNS CNAME verification with free, auto-renewed Let's Encrypt SSL certificates.
                    </p>
                </div>
                
                <div class="mt-6 relative z-10 space-y-2">
                    <div class="bg-zinc-100 dark:bg-[#09090b] border border-zinc-200 dark:border-zinc-800 rounded-xl flex items-center justify-between p-2.5 text-xs">
                        <div class="flex items-center gap-2 truncate">
                            <flux:icon.check-circle class="w-4 h-4 text-emerald-500 shrink-0" />
                            <span class="font-mono font-medium text-zinc-800 dark:text-zinc-200 truncate">docs.mysaas.com</span>
                        </div>
                        <span class="px-2 py-0.5 rounded-full bg-emerald-100 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 text-[10px] font-bold">CNAME Verified</span>
                    </div>
                </div>
            </div>

            <!-- Card 4: Comprehensive API (Large) -->
            <div :class="shown ? 'opacity-100 translate-y-0 delay-400' : 'opacity-0 translate-y-10'" class="transition-all duration-700 ease-out md:col-span-2 glass-panel rounded-3xl p-8 border border-zinc-200 dark:border-zinc-800 hover:border-emerald-400 dark:hover:border-emerald-500/50 hover:-translate-y-1 transition-all group overflow-hidden relative">
                <div class="absolute right-0 top-0 w-64 h-64 bg-emerald-500/10 rounded-full blur-[80px] group-hover:bg-emerald-500/20 transition-all duration-500"></div>
                <div class="relative z-10 flex flex-col md:flex-row gap-8 items-center">
                    <div class="flex-1">
                        <div class="w-12 h-12 bg-emerald-100 dark:bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 rounded-2xl flex items-center justify-center mb-6 ring-1 ring-emerald-500/30 group-hover:ring-emerald-500/50 transition-all">
                            <flux:icon.command-line class="w-6 h-6" />
                        </div>
                        <h3 class="text-2xl font-bold mb-3 text-zinc-900 dark:text-white">Full REST API & OpenAPI Docs</h3>
                        <p class="text-zinc-600 dark:text-zinc-400 mb-6 text-sm sm:text-base">
                            Automate your deployments from any language or framework. Create sites, manage custom domains, inspect activity logs, and stream build statistics.
                        </p>
                        <flux:button :href="url('/docs/api')" variant="ghost" class="text-emerald-600 dark:text-emerald-400 hover:text-emerald-700 dark:hover:text-emerald-300 px-0 transition-colors font-semibold">
                            Explore Interactive API Docs &rarr;
                        </flux:button>
                    </div>
                    <div class="flex-1 w-full bg-zinc-900 dark:bg-[#09090b] rounded-2xl p-5 font-mono text-xs border border-zinc-800 text-zinc-300 shadow-inner">
                        <div class="text-zinc-500 mb-2">// Create site via API with Personal Access Token</div>
                        <span class="text-emerald-400">curl</span> -X POST https://drophtml.de/api/sites \<br>
                        &nbsp;&nbsp;-H <span class="text-yellow-300">"Authorization: Bearer $TOKEN"</span> \<br>
                        &nbsp;&nbsp;-F <span class="text-yellow-300">"original_name=landing.zip"</span> \<br>
                        &nbsp;&nbsp;-F <span class="text-yellow-300">"file=@build.zip"</span><br>
                        <span class="text-zinc-500 mt-3 block">// Status 201 Created:</span>
                        <div class="text-indigo-400 mt-1">{ "url": <span class="text-yellow-300">"https://drophtml.de/s/landing-9x2a"</span> }</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>