<footer class="border-t border-zinc-800/50 bg-[#050505] pt-16 pb-8 relative z-10">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col md:flex-row justify-between items-center gap-6">
            <div class="flex items-center gap-2">
                <flux:icon.rocket-launch class="w-6 h-6 text-zinc-500" />
                <span class="text-lg font-bold text-zinc-400 tracking-tight">DropHTML</span>
            </div>
            <div class="flex items-center space-x-6 text-sm font-medium text-zinc-500">
                <a href="#" class="hover:text-white transition-colors">Privacy Policy</a>
                <a href="#" class="hover:text-white transition-colors">Terms of Service</a>
                <a href="{{ url('/docs/api') }}" class="hover:text-white transition-colors">API Docs</a>
            </div>
        </div>
        <div class="mt-8 flex flex-col md:flex-row justify-between items-center border-t border-zinc-800/50 pt-8 text-xs text-zinc-600">
            <p>&copy; {{ date('Y') }} DropHTML.de. All rights reserved.</p>
            <p class="mt-2 md:mt-0 flex items-center gap-1">Made with <flux:icon.heart class="w-3 h-3 text-red-500/70" /> for developers.</p>
        </div>
    </div>
</footer>