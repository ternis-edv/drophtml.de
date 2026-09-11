<section id="features" class="py-24 relative z-10">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Header -->
        <div class="text-center mb-16">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full glass-panel mb-4 border border-indigo-500/30 text-indigo-600 dark:text-indigo-400 text-xs font-semibold uppercase tracking-wider">
                Platform Architecture
            </div>
            <h2 class="text-3xl md:text-5xl font-extrabold mb-4 text-zinc-900 dark:text-white tracking-tight">
                Engineered for Modern Web Workflows
            </h2>
            <p class="text-zinc-600 dark:text-zinc-400 max-w-xl mx-auto text-base">
                Everything you need to ship, protect, scale, and automate static sites — without complex cloud server configuration.
            </p>
        </div>

        <!-- 6-Card Bento Grid -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            
            <!-- Card 1: GitHub CI/CD & Webhook Auto-Deploy (Large, 2 Cols) -->
            <div class="md:col-span-2 glass-panel rounded-3xl p-8 border border-zinc-200 dark:border-zinc-800 hover:border-indigo-400 dark:hover:border-indigo-500/50 hover:-translate-y-1 transition-all group overflow-hidden relative flex flex-col justify-between">
                <div class="absolute right-0 top-0 w-64 h-64 bg-indigo-500/10 rounded-full blur-[80px] group-hover:bg-indigo-500/20 transition-all duration-500"></div>
                <div class="relative z-10">
                    <div class="w-12 h-12 bg-indigo-100 dark:bg-indigo-500/20 text-indigo-600 dark:text-indigo-400 rounded-2xl flex items-center justify-center mb-6 ring-1 ring-indigo-500/30 group-hover:ring-indigo-500/50 transition-all">
                        <flux:icon.rocket-launch class="w-6 h-6" />
                    </div>
                    <h3 class="text-2xl font-bold mb-3 text-zinc-900 dark:text-white">GitHub CI/CD & Automatic Redeploy</h3>
                    <p class="text-zinc-600 dark:text-zinc-400 mb-6 max-w-md text-sm sm:text-base leading-relaxed">
                        Connect your GitHub repository with one click. Every push to your default branch triggers an automated redeploy through signed webhooks with zero-downtime cache invalidation.
                    </p>
                    <div class="bg-zinc-900 dark:bg-[#09090b] rounded-xl p-4 font-mono text-xs sm:text-sm border border-zinc-800 text-zinc-300 shadow-inner">
                        <div class="flex items-center justify-between pb-2 mb-2 border-b border-zinc-800 text-zinc-500 text-[11px]">
                            <span>Terminal (Push Event)</span>
                            <span class="text-emerald-400 flex items-center gap-1"><span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span> webhook 200 OK</span>
                        </div>
                        <span class="text-indigo-400 font-bold">$ git</span> push origin main<br>
                        <span class="text-zinc-500 mt-1 block text-xs">Resolving deltas: 100% (4/4), completed with 4 local objects.</span>
                        <span class="text-emerald-400 mt-1 block text-xs">&check; GitHub webhook verified via HMAC SHA256</span>
                        <span class="text-emerald-400 mt-0.5 block text-xs">&check; Site redeployed to https://my-landing.drophtml.de in 680ms</span>
                    </div>
                </div>
            </div>

            <!-- Card 2: Password Protection (1 Col, Interactive Toggle) -->
            <div 
                x-data="{ isProtected: true }"
                class="glass-panel rounded-3xl p-8 border border-zinc-200 dark:border-zinc-800 hover:border-purple-400 dark:hover:border-purple-500/50 hover:-translate-y-1 transition-all group relative overflow-hidden flex flex-col justify-between"
            >
                <div class="absolute right-[-20%] bottom-[-20%] w-48 h-48 bg-purple-500/10 rounded-full blur-[60px] group-hover:bg-purple-500/20 transition-all duration-500"></div>
                <div class="relative z-10">
                    <div class="w-12 h-12 bg-purple-100 dark:bg-purple-500/20 text-purple-600 dark:text-purple-400 rounded-2xl flex items-center justify-center mb-6 ring-1 ring-purple-500/30 group-hover:ring-purple-500/50 transition-all">
                        <flux:icon.lock-closed class="w-6 h-6" />
                    </div>
                    <h3 class="text-xl font-bold mb-2 text-zinc-900 dark:text-white">Client Staging & Password Lock</h3>
                    <p class="text-zinc-600 dark:text-zinc-400 text-sm leading-relaxed">
                        Sharing a client preview or confidential mockup? Guard any site behind password protection with secure session cookies and brute-force throttling.
                    </p>
                </div>
                
                <div class="mt-6 bg-zinc-100 dark:bg-[#09090b] border border-zinc-200 dark:border-zinc-800 rounded-2xl p-4 flex items-center justify-between relative z-10">
                    <div>
                        <div class="text-xs font-bold text-zinc-900 dark:text-white" x-text="isProtected ? 'Password Locked' : 'Public Access'"></div>
                        <div class="text-[11px] text-zinc-500" x-text="isProtected ? 'Requires passcode to unlock' : 'Accessible without passcode'"></div>
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

            <!-- Card 3: Custom Domains & Auto SSL (1 Col) -->
            <div 
                x-data="{ verified: true }"
                class="glass-panel rounded-3xl p-8 border border-zinc-200 dark:border-zinc-800 hover:border-pink-400 dark:hover:border-pink-500/50 hover:-translate-y-1 transition-all group relative overflow-hidden flex flex-col justify-between"
            >
                <div class="absolute left-[-20%] bottom-[-20%] w-48 h-48 bg-pink-500/10 rounded-full blur-[60px] group-hover:bg-pink-500/20 transition-all duration-500"></div>
                <div class="relative z-10">
                    <div class="w-12 h-12 bg-pink-100 dark:bg-pink-500/20 text-pink-600 dark:text-pink-400 rounded-2xl flex items-center justify-center mb-6 ring-1 ring-pink-500/30 group-hover:ring-pink-500/50 transition-all">
                        <flux:icon.globe-alt class="w-6 h-6" />
                    </div>
                    <h3 class="text-xl font-bold mb-2 text-zinc-900 dark:text-white">Custom Domains & Auto SSL</h3>
                    <p class="text-zinc-600 dark:text-zinc-400 text-sm leading-relaxed">
                        Attach your own domain or subdomains. Automatic DNS CNAME verification, apex IP routing, and auto-renewing TLS certificates.
                    </p>
                </div>
                
                <div class="mt-6 relative z-10 space-y-2">
                    <div class="bg-zinc-100 dark:bg-[#09090b] border border-zinc-200 dark:border-zinc-800 rounded-xl flex items-center justify-between p-3 text-xs">
                        <div class="flex items-center gap-2 truncate">
                            <flux:icon.check-circle class="w-4 h-4 text-emerald-500 shrink-0" />
                            <span class="font-mono font-medium text-zinc-800 dark:text-zinc-200 truncate">docs.mybrand.com</span>
                        </div>
                        <span class="px-2 py-0.5 rounded-full bg-emerald-100 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 text-[10px] font-bold">CNAME Active</span>
                    </div>
                </div>
            </div>

            <!-- Card 4: S3 / Cloudflare R2 Static Media (1 Col) -->
            <div class="glass-panel rounded-3xl p-8 border border-zinc-200 dark:border-zinc-800 hover:border-cyan-400 dark:hover:border-cyan-500/50 hover:-translate-y-1 transition-all group relative overflow-hidden flex flex-col justify-between">
                <div class="absolute right-[-20%] top-[-20%] w-48 h-48 bg-cyan-500/10 rounded-full blur-[60px] group-hover:bg-cyan-500/20 transition-all duration-500"></div>
                <div class="relative z-10">
                    <div class="w-12 h-12 bg-cyan-100 dark:bg-cyan-500/20 text-cyan-600 dark:text-cyan-400 rounded-2xl flex items-center justify-center mb-6 ring-1 ring-cyan-500/30 group-hover:ring-cyan-500/50 transition-all">
                        <flux:icon.cloud-arrow-up class="w-6 h-6" />
                    </div>
                    <div class="flex items-center gap-2 mb-2">
                        <h3 class="text-xl font-bold text-zinc-900 dark:text-white">Cloud Media Storage</h3>
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-cyan-100 dark:bg-cyan-950 text-cyan-700 dark:text-cyan-300">S3 / R2</span>
                    </div>
                    <p class="text-zinc-600 dark:text-zinc-400 text-sm leading-relaxed">
                        Host massive videos, raw images, and documents on AWS S3 or Cloudflare R2 without bloating your site's deploy zip. 1-click bi-directional file promotion.
                    </p>
                </div>
                
                <div class="mt-6 relative z-10">
                    <div class="bg-zinc-100 dark:bg-[#09090b] border border-zinc-200 dark:border-zinc-800 rounded-xl p-3 text-xs flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <flux:icon.film class="w-4 h-4 text-cyan-500" />
                            <span class="font-mono text-zinc-700 dark:text-zinc-300">hero.mp4 (42MB)</span>
                        </div>
                        <span class="text-[10px] font-bold text-cyan-600 dark:text-cyan-400">R2 CDN Edge</span>
                    </div>
                </div>
            </div>

            <!-- Card 5: Analytics & 1-Click Rollback (1 Col) -->
            <div class="glass-panel rounded-3xl p-8 border border-zinc-200 dark:border-zinc-800 hover:border-amber-400 dark:hover:border-amber-500/50 hover:-translate-y-1 transition-all group relative overflow-hidden flex flex-col justify-between">
                <div class="absolute left-[-20%] top-[-20%] w-48 h-48 bg-amber-500/10 rounded-full blur-[60px] group-hover:bg-amber-500/20 transition-all duration-500"></div>
                <div class="relative z-10">
                    <div class="w-12 h-12 bg-amber-100 dark:bg-amber-500/20 text-amber-600 dark:text-amber-400 rounded-2xl flex items-center justify-center mb-6 ring-1 ring-amber-500/30 group-hover:ring-amber-500/50 transition-all">
                        <flux:icon.chart-bar class="w-6 h-6" />
                    </div>
                    <h3 class="text-xl font-bold mb-2 text-zinc-900 dark:text-white">Analytics & Rollbacks</h3>
                    <p class="text-zinc-600 dark:text-zinc-400 text-sm leading-relaxed">
                        Privacy-friendly visitor analytics (views, unique referrers, devices) and full deployment history with instant 1-click rollbacks to any prior release.
                    </p>
                </div>

                <div class="mt-6 relative z-10 flex items-center justify-between bg-zinc-100 dark:bg-[#09090b] border border-zinc-200 dark:border-zinc-800 rounded-xl p-3 text-xs">
                    <div class="flex items-center gap-2 text-amber-600 dark:text-amber-400 font-mono">
                        <flux:icon.arrow-path class="w-4 h-4" />
                        <span>v1.0.4 &rarr; v1.0.3</span>
                    </div>
                    <span class="px-2 py-0.5 rounded bg-amber-100 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300 text-[10px] font-bold">Instant Switch</span>
                </div>
            </div>

            <!-- Card 6: REST API & Developer Tokens (Large, 3 Cols on desktop) -->
            <div class="md:col-span-3 glass-panel rounded-3xl p-8 border border-zinc-200 dark:border-zinc-800 hover:border-emerald-400 dark:hover:border-emerald-500/50 hover:-translate-y-1 transition-all group overflow-hidden relative">
                <div class="absolute right-0 top-0 w-64 h-64 bg-emerald-500/10 rounded-full blur-[80px] group-hover:bg-emerald-500/20 transition-all duration-500"></div>
                <div class="relative z-10 flex flex-col md:flex-row gap-8 items-center">
                    <div class="flex-1 space-y-4">
                        <div class="w-12 h-12 bg-emerald-100 dark:bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 rounded-2xl flex items-center justify-center ring-1 ring-emerald-500/30 group-hover:ring-emerald-500/50 transition-all">
                            <flux:icon.command-line class="w-6 h-6" />
                        </div>
                        <h3 class="text-2xl font-bold text-zinc-900 dark:text-white">Full REST API & Interactive OpenAPI Docs</h3>
                        <p class="text-zinc-600 dark:text-zinc-400 text-sm sm:text-base leading-relaxed">
                            Programmatically manage sites, upload archives, query visitor analytics, provision custom domains, and manage S3/R2 media assets. Tested and documented with OpenAPI 3.0 specification.
                        </p>
                        <div class="flex items-center gap-4 pt-2">
                            <a href="{{ url('/docs/api') }}" class="inline-flex items-center gap-2 text-sm font-bold text-emerald-600 dark:text-emerald-400 hover:underline">
                                <span>Explore Interactive API Documentation</span>
                                <flux:icon.arrow-right class="w-4 h-4" />
                            </a>
                        </div>
                    </div>
                    <div class="flex-1 w-full bg-zinc-900 dark:bg-[#09090b] rounded-2xl p-5 font-mono text-xs border border-zinc-800 text-zinc-300 shadow-inner">
                        <div class="text-zinc-500 mb-2">// Deploy static site or ZIP archive programmatically</div>
                        <span class="text-emerald-400">curl</span> -X POST https://drophtml.de/api/sites \<br>
                        &nbsp;&nbsp;-H <span class="text-yellow-300">"Authorization: Bearer $TOKEN"</span> \<br>
                        &nbsp;&nbsp;-F <span class="text-yellow-300">"original_name=landing.zip"</span> \<br>
                        &nbsp;&nbsp;-F <span class="text-yellow-300">"file=@build.zip"</span><br>
                        <span class="text-zinc-500 mt-3 block">// Status 201 Created:</span>
                        <div class="text-indigo-400 mt-1">{ "url": <span class="text-yellow-300">"https://drophtml.de/s/landing-9x2a"</span>, "permanent": <span class="text-emerald-400">true</span> }</div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</section>