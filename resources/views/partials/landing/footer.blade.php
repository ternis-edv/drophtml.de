<footer class="border-t border-zinc-200 dark:border-zinc-800/50 bg-zinc-50 dark:bg-[#050505] pt-16 pb-8 relative z-10">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col md:flex-row justify-between items-center gap-6">
            <div class="flex flex-col gap-2">
                <a href="{{ route('home') }}" class="flex items-center gap-2 hover:opacity-80 transition-opacity">
                    <flux:icon.rocket-launch class="w-6 h-6 text-zinc-400 dark:text-zinc-500" />
                    <span class="text-lg font-bold text-zinc-600 dark:text-zinc-400 tracking-tight">DropHTML</span>
                </a>
                <div class="text-xs text-zinc-500 dark:text-zinc-600 mt-2">
                    Similar Projects: <a href="https://httpclient.de" target="_blank" class="hover:text-zinc-800 dark:hover:text-white transition-colors">httpclient.de</a>, <a href="https://api-sandbox.de" target="_blank" class="hover:text-zinc-800 dark:hover:text-white transition-colors">api-sandbox.de</a>
                </div>
            </div>
            <div class="flex flex-wrap items-center justify-center gap-6 text-sm font-medium text-zinc-500">
                <a href="{{ route('legal.show', 'privacy') }}" class="hover:text-zinc-900 dark:hover:text-white transition-colors">Privacy Policy</a>
                <a href="{{ route('legal.show', 'terms') }}" class="hover:text-zinc-900 dark:hover:text-white transition-colors">Terms of Service</a>
                <a href="{{ route('legal.show', 'imprint') }}" class="hover:text-zinc-900 dark:hover:text-white transition-colors">Imprint</a>
                <a href="{{ url('/docs/api') }}" class="hover:text-zinc-900 dark:hover:text-white transition-colors">API Docs</a>
            </div>
        </div>
        <div class="mt-8 flex flex-col md:flex-row justify-between items-center border-t border-zinc-200 dark:border-zinc-800/50 pt-8 text-xs text-zinc-500 dark:text-zinc-600">
            <p>&copy; {{ date('Y') }} DropHTML.de. All rights reserved.</p>
            <p class="mt-2 md:mt-0 flex items-center gap-1">Made with <flux:icon.heart class="w-3 h-3 text-red-500/70" /> for developers.</p>
        </div>
    </div>
</footer>