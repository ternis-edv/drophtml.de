<section id="features" class="py-24 relative z-10">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <div class="text-center mb-16" x-data="{ shown: false }" x-intersect.once="shown = true">
            <h2 :class="shown ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-10'" class="transition-all duration-700 ease-out text-3xl md:text-4xl font-bold mb-4 text-zinc-900 dark:text-white">Everything you need. Nothing you don't.</h2>
            <p :class="shown ? 'opacity-100 translate-y-0 delay-100' : 'opacity-0 translate-y-10'" class="transition-all duration-700 ease-out text-zinc-600 dark:text-zinc-400">Built for speed, simplicity, and developer happiness.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6" x-data="{ shown: false }" x-intersect.once="shown = true">
            
            <!-- Card 1 (Large) -->
            <div :class="shown ? 'opacity-100 translate-y-0 delay-100' : 'opacity-0 translate-y-10'" class="transition-all duration-700 ease-out md:col-span-2 glass-panel rounded-3xl p-8 border border-zinc-200 dark:border-zinc-800 hover:border-indigo-400 dark:hover:border-indigo-500/50 hover:-translate-y-1 transition-all group overflow-hidden relative">
                <div class="absolute right-0 top-0 w-64 h-64 bg-indigo-500/10 rounded-full blur-[80px] group-hover:bg-indigo-500/20 transition-all duration-500"></div>
                <div class="relative z-10">
                    <div class="w-12 h-12 bg-indigo-100 dark:bg-indigo-500/20 text-indigo-600 dark:text-indigo-400 rounded-xl flex items-center justify-center mb-6 ring-1 ring-indigo-500/30 group-hover:ring-indigo-500/50 transition-all">
                        <flux:icon.rocket-launch class="w-6 h-6" />
                    </div>
                    <h3 class="text-2xl font-bold mb-3 text-zinc-900 dark:text-white">GitHub Auto-Deploy</h3>
                    <p class="text-zinc-600 dark:text-zinc-400 mb-6 max-w-md">Connect your repository. Every push to `main` instantly updates your live site. We handle the webhooks and builds automatically.</p>
                    <div class="bg-zinc-100 dark:bg-[#09090b] rounded-xl p-5 font-mono text-sm border border-zinc-200 dark:border-zinc-800 text-zinc-800 dark:text-zinc-300 shadow-inner">
                        <span class="text-indigo-600 dark:text-indigo-400">git</span> push origin main<br>
                        <span class="text-zinc-500 mt-2 block">Resolving deltas: 100% (3/3), completed with 3 local objects.</span>
                        <span class="text-green-600 dark:text-green-400 mt-2 block">✓ Build started...</span>
                        <span class="text-green-600 dark:text-green-400 mt-1 block">✓ Site deployed successfully to https://demo.drophtml.de</span>
                    </div>
                </div>
            </div>

            <!-- Card 2 -->
            <div :class="shown ? 'opacity-100 translate-y-0 delay-200' : 'opacity-0 translate-y-10'" class="transition-all duration-700 ease-out glass-panel rounded-3xl p-8 border border-zinc-200 dark:border-zinc-800 hover:border-purple-400 dark:hover:border-purple-500/50 hover:-translate-y-1 transition-all group relative overflow-hidden flex flex-col justify-between">
                <div class="absolute right-[-20%] bottom-[-20%] w-48 h-48 bg-purple-500/10 rounded-full blur-[60px] group-hover:bg-purple-500/20 transition-all duration-500"></div>
                <div class="relative z-10">
                    <div class="w-12 h-12 bg-purple-100 dark:bg-purple-500/20 text-purple-600 dark:text-purple-400 rounded-xl flex items-center justify-center mb-6 ring-1 ring-purple-500/30 group-hover:ring-purple-500/50 transition-all">
                        <flux:icon.lock-closed class="w-6 h-6" />
                    </div>
                    <h3 class="text-xl font-bold mb-3 text-zinc-900 dark:text-white">Password Protection</h3>
                    <p class="text-zinc-600 dark:text-zinc-400 text-sm">Sharing a client preview? Lock it behind a secure password with a single toggle.</p>
                </div>
                
                <div class="mt-6 bg-zinc-100 dark:bg-[#09090b] border border-zinc-200 dark:border-zinc-800 rounded-xl p-4 flex items-center justify-between relative z-10">
                    <span class="text-sm font-medium text-zinc-900 dark:text-white">Require Password</span>
                    <div class="w-10 h-6 bg-purple-500 rounded-full relative cursor-pointer">
                        <div class="absolute right-1 top-1 w-4 h-4 bg-white rounded-full shadow"></div>
                    </div>
                </div>
            </div>

            <!-- Card 3 -->
            <div :class="shown ? 'opacity-100 translate-y-0 delay-300' : 'opacity-0 translate-y-10'" class="transition-all duration-700 ease-out glass-panel rounded-3xl p-8 border border-zinc-200 dark:border-zinc-800 hover:border-pink-400 dark:hover:border-pink-500/50 hover:-translate-y-1 transition-all group relative overflow-hidden flex flex-col justify-between">
                <div class="absolute left-[-20%] bottom-[-20%] w-48 h-48 bg-pink-500/10 rounded-full blur-[60px] group-hover:bg-pink-500/20 transition-all duration-500"></div>
                <div class="relative z-10">
                    <div class="w-12 h-12 bg-pink-100 dark:bg-pink-500/20 text-pink-600 dark:text-pink-400 rounded-xl flex items-center justify-center mb-6 ring-1 ring-pink-500/30 group-hover:ring-pink-500/50 transition-all">
                        <flux:icon.globe-alt class="w-6 h-6" />
                    </div>
                    <h3 class="text-xl font-bold mb-3 text-zinc-900 dark:text-white">Custom Domains</h3>
                    <p class="text-zinc-600 dark:text-zinc-400 text-sm">Map your own domain names. Free SSL certificates are generated and renewed automatically.</p>
                </div>
                
                <div class="mt-6 relative z-10">
                    <div class="bg-zinc-100 dark:bg-[#09090b] border border-zinc-200 dark:border-zinc-800 rounded-lg flex items-center overflow-hidden">
                        <span class="bg-zinc-200 dark:bg-zinc-900 px-3 py-2 text-zinc-500 text-sm border-r border-zinc-300 dark:border-zinc-800">https://</span>
                        <span class="px-3 py-2 text-zinc-900 dark:text-white text-sm font-mono">my-startup.com</span>
                    </div>
                </div>
            </div>

            <!-- Card 4 (Large) -->
            <div :class="shown ? 'opacity-100 translate-y-0 delay-400' : 'opacity-0 translate-y-10'" class="transition-all duration-700 ease-out md:col-span-2 glass-panel rounded-3xl p-8 border border-zinc-200 dark:border-zinc-800 hover:border-emerald-400 dark:hover:border-emerald-500/50 hover:-translate-y-1 transition-all group overflow-hidden relative">
                <div class="absolute right-0 top-0 w-64 h-64 bg-emerald-500/10 rounded-full blur-[80px] group-hover:bg-emerald-500/20 transition-all duration-500"></div>
                <div class="relative z-10 flex flex-col md:flex-row gap-8 items-center">
                    <div class="flex-1">
                        <div class="w-12 h-12 bg-emerald-100 dark:bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 rounded-xl flex items-center justify-center mb-6 ring-1 ring-emerald-500/30 group-hover:ring-emerald-500/50 transition-all">
                            <flux:icon.command-line class="w-6 h-6" />
                        </div>
                        <h3 class="text-2xl font-bold mb-3 text-zinc-900 dark:text-white">Comprehensive API</h3>
                        <p class="text-zinc-600 dark:text-zinc-400 mb-6">Automate everything. Our REST API lets you manage sites, domains, and deployments programmatically from your CI/CD pipeline.</p>
                        <flux:button :href="url('/docs/api')" variant="ghost" class="text-emerald-600 dark:text-emerald-400 hover:text-emerald-700 dark:hover:text-emerald-300 px-0 transition-colors">Read API Docs &rarr;</flux:button>
                    </div>
                    <div class="flex-1 w-full bg-zinc-100 dark:bg-[#09090b] rounded-xl p-5 font-mono text-xs border border-zinc-200 dark:border-zinc-800 text-zinc-800 dark:text-zinc-300 shadow-inner">
<pre class="overflow-x-auto"><code><span class="text-emerald-600 dark:text-emerald-400">curl</span> -X POST https://api.drophtml.de/sites \
  -H <span class="text-yellow-600 dark:text-yellow-300">"Authorization: Bearer $TOKEN"</span> \
  -F <span class="text-yellow-600 dark:text-yellow-300">"original_name=landing.zip"</span> \
  -F <span class="text-yellow-600 dark:text-yellow-300">"file=@build.zip"</span>
  
<span class="text-zinc-500 mt-4 block"># Response:</span>
{
  <span class="text-indigo-600 dark:text-indigo-300">"id"</span>: <span class="text-yellow-600 dark:text-yellow-300">"98a7f..."</span>,
  <span class="text-indigo-600 dark:text-indigo-300">"url"</span>: <span class="text-yellow-600 dark:text-yellow-300">"https://demo.drophtml.de"</span>
}</code></pre>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>