<footer class="border-t border-zinc-200 dark:border-zinc-800/50 bg-zinc-50 dark:bg-[#050505] pt-16 pb-8 relative z-10">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-12">
            <!-- Brand & Partners -->
            <div class="flex flex-col gap-3">
                <a href="{{ route('home') }}" class="flex items-center gap-2 hover:opacity-80 transition-opacity">
                    <flux:icon.rocket-launch class="w-6 h-6 text-indigo-500 dark:text-indigo-400" />
                    <span class="text-xl font-bold tracking-tight bg-clip-text text-transparent bg-gradient-to-r from-indigo-500 to-purple-500 dark:from-indigo-400 dark:to-purple-400">
                        DropHTML
                    </span>
                </a>
                <p class="text-xs text-zinc-500 dark:text-zinc-400 leading-relaxed max-w-sm">
                    Instant static site hosting for developers, creators, and teams. Ship HTML in seconds with drag & drop, GitHub CI/CD, and custom domains.
                </p>
                <div class="text-xs text-zinc-500 dark:text-zinc-500 space-y-1 pt-2">
                    <div>
                        <span class="font-medium text-zinc-600 dark:text-zinc-400">A Project by:</span>
                        <a href="https://ternis-edv.de" target="_blank" rel="noopener" class="text-indigo-600 dark:text-indigo-400 hover:underline">ternis-edv.de</a>
                        (<a href="https://ternis.dev" target="_blank" rel="noopener" class="text-indigo-600 dark:text-indigo-400 hover:underline">ternis.dev</a>),
                        <a href="https://xpsystems.eu" target="_blank" rel="noopener" class="text-indigo-600 dark:text-indigo-400 hover:underline">xpsystems.eu</a> /
                        <a href="https://xpsystems.de" target="_blank" rel="noopener" class="text-indigo-600 dark:text-indigo-400 hover:underline">.de</a>,
                        <a href="https://mtex.dev" target="_blank" rel="noopener" class="text-indigo-600 dark:text-indigo-400 hover:underline">mtex.dev</a>
                    </div>
                    <div>
                        <span class="font-medium text-zinc-600 dark:text-zinc-400">Domain by:</span>
                        <a href="https://dnbx.de" target="_blank" rel="noopener" class="text-purple-600 dark:text-purple-400 hover:underline">dnbx.de</a>
                    </div>
                </div>
            </div>

            <!-- Quick Links -->
            <div>
                <h4 class="text-xs font-semibold text-zinc-900 dark:text-white uppercase tracking-wider mb-4">Resources</h4>
                <ul class="space-y-2 text-sm text-zinc-600 dark:text-zinc-400">
                    <li><a href="#features" class="hover:text-zinc-900 dark:hover:text-white transition-colors">Features</a></li>
                    <li><a href="#demo" class="hover:text-zinc-900 dark:hover:text-white transition-colors">Live Demo</a></li>
                    <li><a href="#pricing" class="hover:text-zinc-900 dark:hover:text-white transition-colors">Pricing</a></li>
                    <li><a href="{{ url('/docs/api') }}" class="hover:text-zinc-900 dark:hover:text-white transition-colors">API Documentation</a></li>
                    <li><a href="https://github.com/ternis-edv/drophtml.de" target="_blank" rel="noopener" class="hover:text-zinc-900 dark:hover:text-white transition-colors">GitHub Repository</a></li>
                </ul>
            </div>

            <!-- Legal & Related -->
            <div>
                <h4 class="text-xs font-semibold text-zinc-900 dark:text-white uppercase tracking-wider mb-4">Legal & Network</h4>
                <ul class="space-y-2 text-sm text-zinc-600 dark:text-zinc-400">
                    <li><a href="{{ route('legal.show', 'privacy') }}" class="hover:text-zinc-900 dark:hover:text-white transition-colors">Privacy Policy</a></li>
                    <li><a href="{{ route('legal.show', 'terms') }}" class="hover:text-zinc-900 dark:hover:text-white transition-colors">Terms of Service</a></li>
                    <li><a href="{{ route('legal.show', 'imprint') }}" class="hover:text-zinc-900 dark:hover:text-white transition-colors">Imprint & Legal Notice</a></li>
                    <li class="pt-2 text-xs text-zinc-500 dark:text-zinc-500">
                        Network: <a href="https://httpclient.de" target="_blank" rel="noopener" class="hover:text-zinc-800 dark:hover:text-white">httpclient.de</a>, <a href="https://api-sandbox.de" target="_blank" rel="noopener" class="hover:text-zinc-800 dark:hover:text-white">api-sandbox.de</a>
                    </li>
                </ul>
            </div>
        </div>

        <div class="mt-8 flex flex-col md:flex-row justify-between items-center border-t border-zinc-200 dark:border-zinc-800/50 pt-8 text-xs text-zinc-500 dark:text-zinc-600">
            <p>&copy; {{ date('Y') }} DropHTML.de. All rights reserved.</p>
            <p class="mt-2 md:mt-0 flex items-center gap-1">Made with <flux:icon.heart class="w-3 h-3 text-red-500/70" /> by <a href="https://ternis-edv.de" target="_blank" rel="noopener" class="hover:underline">ternis-edv.de</a>, <a href="https://xpsystems.eu" target="_blank" rel="noopener" class="hover:underline">xpsystems.eu</a>, & <a href="https://mtex.dev" target="_blank" rel="noopener" class="hover:underline">mtex.dev</a>.</p>
        </div>
    </div>
</footer>