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
        <flux:button size="base" variant="primary" class="px-8 shadow-lg shadow-blue-500/20" @click="document.getElementById('upload-section').scrollIntoView({ behavior: 'smooth' })">
            Get Started for Free
        </flux:button>
        <flux:button size="base" variant="ghost" href="https://github.com/ternis-edv/drophtml.de" target="_blank">
            <svg class="size-4 mr-2" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path fill-rule="evenodd" d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.531 1.032 1.531 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0022 12.017C22 6.484 17.522 2 12 2z" clip-rule="evenodd" />
            </svg>
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