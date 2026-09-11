<section id="upload" class="py-20 relative z-10">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-10">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full glass-panel mb-3 border border-indigo-500/30 text-indigo-600 dark:text-indigo-400 text-xs font-semibold uppercase tracking-wider">
                <flux:icon.arrow-up-tray class="size-3.5 text-indigo-500" />
                <span>Drag &amp; Drop Publisher</span>
            </div>
            <h2 class="text-3xl sm:text-4xl font-extrabold text-zinc-900 dark:text-white tracking-tight mb-3">
                Deploy Instantly in Your Browser
            </h2>
            <p class="text-sm sm:text-base text-zinc-600 dark:text-zinc-400 max-w-xl mx-auto">
                No command-line setup or account required to test. Drop single <code>.html</code> files or full <code>.zip</code> archives below to publish immediately.
            </p>
        </div>

        <div class="glass-panel rounded-3xl p-3 sm:p-5 border border-zinc-200/80 dark:border-zinc-800 shadow-2xl relative">
            <div class="bg-white dark:bg-[#09090c] rounded-2xl p-6 sm:p-8 border border-zinc-200/80 dark:border-zinc-800">
                <livewire:file-uploader />
            </div>

            <!-- 3-Step Pipeline Visual Footer -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 p-4 mt-3 pt-4 border-t border-zinc-200/60 dark:border-zinc-800/60 text-xs">
                <div class="flex items-start gap-3">
                    <span class="size-6 rounded-full bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 font-bold flex items-center justify-center shrink-0 border border-indigo-500/20">1</span>
                    <div>
                        <p class="font-bold text-zinc-900 dark:text-zinc-100">Drop your file</p>
                        <p class="text-[11px] text-zinc-500 mt-0.5">Accepts single HTML, CSS, JS, or zipped project folders.</p>
                    </div>
                </div>

                <div class="flex items-start gap-3">
                    <span class="size-6 rounded-full bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 font-bold flex items-center justify-center shrink-0 border border-indigo-500/20">2</span>
                    <div>
                        <p class="font-bold text-zinc-900 dark:text-zinc-100">Automatic Edge Caching</p>
                        <p class="text-[11px] text-zinc-500 mt-0.5">Zip Slip protection, sanitized file extraction &amp; SSL generation.</p>
                    </div>
                </div>

                <div class="flex items-start gap-3">
                    <span class="size-6 rounded-full bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 font-bold flex items-center justify-center shrink-0 border border-indigo-500/20">3</span>
                    <div>
                        <p class="font-bold text-zinc-900 dark:text-zinc-100">Live URL in &lt; 2s</p>
                        <p class="text-[11px] text-zinc-500 mt-0.5">Share with clients, lock with password, or connect your domain.</p>
                    </div>
                </div>
            </div>

            @guest
                <div class="mt-4 text-center text-xs text-zinc-500">
                    Guest uploads expire in 24 hours. <a href="{{ route('register') }}" class="text-indigo-600 dark:text-indigo-400 font-semibold hover:underline">Create a free account</a> to retain them permanently and connect custom domains.
                </div>
            @endguest
        </div>
    </div>
</section>