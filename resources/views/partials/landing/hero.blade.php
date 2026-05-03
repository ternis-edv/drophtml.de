<section class="w-full lg:max-w-6xl px-6 py-12 lg:py-24 flex flex-col items-center text-center gap-8">
    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-blue-50 dark:bg-blue-900/30 border border-blue-100 dark:border-blue-800 text-blue-600 dark:text-blue-400 text-xs font-bold tracking-wide uppercase animate-fade-in">
        <flux:icon.sparkles class="size-3" />
        <span>Instant Deployment is here</span>
    </div>

    <h1 class="text-5xl lg:text-7xl font-black tracking-tight leading-tight max-w-4xl">
        Deploy your <span class="text-transparent bg-clip-text bg-gradient-to-r from-blue-600 to-indigo-500">HTML</span> in seconds
    </h1>

    <p class="text-xl text-zinc-500 dark:text-zinc-400 max-w-2xl mx-auto leading-relaxed">
        The fastest way to get your static sites online. Drag, drop, and share. No complex configuration, no servers to manage.
    </p>

    <div class="flex flex-col sm:flex-row gap-4 mt-4">
        <flux:button size="lg" variant="primary" class="px-8 shadow-lg shadow-blue-500/20" @click="document.getElementById('upload-section').scrollIntoView({ behavior: 'smooth' })">
            Get Started for Free
        </flux:button>
        <flux:button size="lg" variant="ghost" icon="github" href="https://github.com/ternis-edv/drophtml.de" target="_blank">
            Star on GitHub
        </flux:button>
    </div>

    <div id="upload-section" class="w-full max-w-4xl mt-16 group transition-all duration-500 hover:scale-[1.01]">
        <div class="relative">
            <div class="absolute -inset-1 bg-gradient-to-r from-blue-600 to-indigo-500 rounded-3xl blur opacity-20 group-hover:opacity-30 transition duration-1000"></div>
            <div class="relative bg-white dark:bg-zinc-900 p-2 lg:p-4 rounded-2xl shadow-2xl border border-zinc-200 dark:border-zinc-800">
                <div class="bg-zinc-50 dark:bg-zinc-950 p-6 lg:p-10 rounded-xl border border-dashed border-zinc-300 dark:border-zinc-700">
                    <livewire:file-uploader />
                </div>
            </div>
        </div>
    </div>
</section>