<section id="upload" class="py-24 relative z-10 reveal" x-intersect.once="$el.classList.add('is-shown')">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <span class="eyebrow mb-4">
                <flux:icon.arrow-up-tray class="size-3.5" />
                <span>Drag &amp; Drop Publisher</span>
            </span>
            <h2 class="text-3xl sm:text-4xl font-bold tracking-tight text-zinc-900 dark:text-white mb-4">
                Deploy Instantly in Your Browser
            </h2>
            <p class="text-sm sm:text-base text-zinc-500 dark:text-zinc-400 max-w-xl mx-auto">
                No command-line setup or account required to test. Drop single <code class="px-1.5 py-0.5 rounded bg-zinc-100 dark:bg-zinc-800 text-zinc-800 dark:text-zinc-200 text-[0.85em] font-mono">.html</code> files or full <code class="px-1.5 py-0.5 rounded bg-zinc-100 dark:bg-zinc-800 text-zinc-800 dark:text-zinc-200 text-[0.85em] font-mono">.zip</code> archives below to publish immediately.
            </p>
        </div>

        <div class="rounded-2xl p-3 sm:p-5 bg-white dark:bg-zinc-900/50 border border-zinc-200 dark:border-zinc-800 relative overflow-hidden">
            <div class="bg-zinc-50 dark:bg-zinc-950 rounded-xl p-6 sm:p-8 border border-zinc-200 dark:border-zinc-800">
                <livewire:file-uploader />
            </div>

            <!-- 3-Step Pipeline Visual Footer -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 p-4 mt-3 pt-4 border-t border-zinc-200/60 dark:border-zinc-800/60 text-xs">
                <div class="flex items-start gap-3">
                    <span class="size-6 rounded-full bg-accent/10 dark:bg-accent/15 text-accent dark:text-accent-content font-bold flex items-center justify-center shrink-0 border border-accent/20">1</span>
                    <div>
                        <p class="font-semibold text-zinc-900 dark:text-zinc-100">Drop your file</p>
                        <p class="text-[11px] text-zinc-500 dark:text-zinc-400 mt-0.5">Accepts single HTML, CSS, JS, or zipped project folders.</p>
                    </div>
                </div>

                <div class="flex items-start gap-3">
                    <span class="size-6 rounded-full bg-accent/10 dark:bg-accent/15 text-accent dark:text-accent-content font-bold flex items-center justify-center shrink-0 border border-accent/20">2</span>
                    <div>
                        <p class="font-semibold text-zinc-900 dark:text-zinc-100">Automatic Edge Caching</p>
                        <p class="text-[11px] text-zinc-500 dark:text-zinc-400 mt-0.5">Zip Slip protection, sanitized file extraction &amp; SSL generation.</p>
                    </div>
                </div>

                <div class="flex items-start gap-3">
                    <span class="size-6 rounded-full bg-accent/10 dark:bg-accent/15 text-accent dark:text-accent-content font-bold flex items-center justify-center shrink-0 border border-accent/20">3</span>
                    <div>
                        <p class="font-semibold text-zinc-900 dark:text-zinc-100">Live URL in &lt; 2s</p>
                        <p class="text-[11px] text-zinc-500 dark:text-zinc-400 mt-0.5">Share with clients, lock with password, or connect your domain.</p>
                    </div>
                </div>
            </div>

            @guest
                <div class="mt-4 text-center text-xs text-zinc-500 dark:text-zinc-400">
                    Guest uploads expire in 24 hours. <a href="{{ route('register') }}" class="text-accent dark:text-accent-content font-semibold hover:underline">Create a free account</a> to retain them permanently and connect custom domains.
                </div>
            @endguest
        </div>
    </div>
</section>
