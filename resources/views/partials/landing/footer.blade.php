<footer class="border-t border-zinc-200 dark:border-zinc-800/60 bg-zinc-50 dark:bg-[#050505] pt-16 pb-8 relative z-10">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-8 mb-12">
            <!-- Brand & Partners -->
            <div class="md:col-span-2 flex flex-col gap-3">
                <a href="{{ route('home') }}" class="flex items-center gap-2 hover:opacity-85 transition-opacity">
                    <div class="w-7 h-7 rounded-lg bg-gradient-to-tr from-indigo-600 to-purple-500 flex items-center justify-center text-white shadow-sm">
                        <flux:icon.rocket-launch class="w-4 h-4 text-white" />
                    </div>
                    <span class="text-xl font-black tracking-tight bg-clip-text text-transparent bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-500 dark:from-indigo-400 dark:via-purple-400 dark:to-pink-400">
                        DropHTML<span class="text-xs font-mono text-zinc-400 dark:text-zinc-500">.de</span>
                    </span>
                </a>
                <p class="text-xs text-zinc-500 dark:text-zinc-400 leading-relaxed max-w-sm">
                    Instant static site hosting for developers, creators, and teams. Ship HTML in seconds with drag & drop, GitHub CI/CD, password protection, and custom domains with automatic SSL.
                </p>

                <!-- Status Badge -->
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-800/60 text-emerald-700 dark:text-emerald-300 text-xs font-medium w-fit mt-2">
                    <span class="relative flex h-2 w-2">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                    </span>
                    <span>All Systems Operational &bull; Global Edge CDN</span>
                </div>

                <div class="text-xs text-zinc-500 dark:text-zinc-500 space-y-1 pt-3">
                    <div>
                        <span class="font-semibold text-zinc-700 dark:text-zinc-300">A Project by:</span>
                        <a href="https://ternis-edv.de" target="_blank" rel="noopener" class="text-indigo-600 dark:text-indigo-400 hover:underline">ternis-edv.de</a>
                        (<a href="https://ternis.dev" target="_blank" rel="noopener" class="text-indigo-600 dark:text-indigo-400 hover:underline">ternis.dev</a>),
                        <a href="https://xpsystems.eu" target="_blank" rel="noopener" class="text-indigo-600 dark:text-indigo-400 hover:underline">xpsystems.eu</a> /
                        <a href="https://xpsystems.de" target="_blank" rel="noopener" class="text-indigo-600 dark:text-indigo-400 hover:underline">.de</a>,
                        <a href="https://mtex.dev" target="_blank" rel="noopener" class="text-indigo-600 dark:text-indigo-400 hover:underline">mtex.dev</a>
                    </div>
                    <div>
                        <span class="font-semibold text-zinc-700 dark:text-zinc-300">Domains & DNS by:</span>
                        <a href="https://dnbx.de" target="_blank" rel="noopener" class="text-purple-600 dark:text-purple-400 hover:underline">dnbx.de</a>
                    </div>
                </div>
            </div>

            <!-- Quick Links -->
            <div>
                <h4 class="text-xs font-bold text-zinc-900 dark:text-white uppercase tracking-wider mb-4">Platform</h4>
                <ul class="space-y-2.5 text-xs text-zinc-600 dark:text-zinc-400">
                    <li><a href="#features" class="hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">Features & Tech</a></li>
                    <li><a href="#demo" class="hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">Live Demos & Simulator</a></li>
                    <li><a href="#pricing" class="hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">Pricing & Plans</a></li>
                    <li><a href="{{ url('/docs/api') }}" class="hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">API Documentation</a></li>
                    <li><a href="https://github.com/ternis-edv/drophtml.de" target="_blank" rel="noopener" class="hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">GitHub Repository</a></li>
                </ul>
            </div>

            <!-- Legal & Related -->
            <div>
                <h4 class="text-xs font-bold text-zinc-900 dark:text-white uppercase tracking-wider mb-4">Legal & Network</h4>
                <ul class="space-y-2.5 text-xs text-zinc-600 dark:text-zinc-400">
                    <li><a href="{{ route('legal.show', 'privacy') }}" class="hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">Privacy Policy</a></li>
                    <li><a href="{{ route('legal.show', 'terms') }}" class="hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">Terms of Service</a></li>
                    <li><a href="{{ route('legal.show', 'imprint') }}" class="hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">Imprint & Legal Notice</a></li>
                    <li class="pt-2 text-[11px] text-zinc-400 dark:text-zinc-500">
                        Network: <a href="https://httpclient.de" target="_blank" rel="noopener" class="hover:text-zinc-800 dark:hover:text-white underline">httpclient.de</a>, <a href="https://api-sandbox.de" target="_blank" rel="noopener" class="hover:text-zinc-800 dark:hover:text-white underline">api-sandbox.de</a>
                    </li>
                </ul>
            </div>
        </div>

        <div class="mt-8 flex flex-col md:flex-row justify-between items-center border-t border-zinc-200 dark:border-zinc-800/60 pt-8 text-xs text-zinc-500 dark:text-zinc-600">
            <p>&copy; {{ date('Y') }} DropHTML.de. All rights reserved.</p>
            <p class="mt-2 md:mt-0 flex items-center gap-1">
                Engineered with <flux:icon.heart class="w-3.5 h-3.5 text-red-500" /> by 
                <a href="https://ternis-edv.de" target="_blank" rel="noopener" class="hover:underline text-zinc-700 dark:text-zinc-300">ternis-edv.de</a>, 
                <a href="https://xpsystems.eu" target="_blank" rel="noopener" class="hover:underline text-zinc-700 dark:text-zinc-300">xpsystems.eu</a>, & 
                <a href="https://mtex.dev" target="_blank" rel="noopener" class="hover:underline text-zinc-700 dark:text-zinc-300">mtex.dev</a>.
            </p>
        </div>
    </div>
</footer>