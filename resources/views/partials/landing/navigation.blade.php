<nav class="fixed top-0 w-full z-50 glass-panel border-b border-zinc-200 dark:border-zinc-800/50 transition-all duration-300">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">
            <div class="flex items-center gap-2">
                <flux:icon.rocket-launch class="w-8 h-8 text-indigo-500 dark:text-indigo-400" />
                <span class="text-xl font-bold tracking-tight bg-clip-text text-transparent bg-gradient-to-r from-indigo-500 to-purple-500 dark:from-indigo-400 dark:to-purple-400">
                    DropHTML
                </span>
            </div>
            
            <div class="hidden md:flex items-center space-x-8">
                <a href="#features" class="text-sm font-medium text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white transition-colors">Features</a>
                <a href="#demo" class="text-sm font-medium text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white transition-colors">Interactive Demo</a>
                <a href="{{ url('/docs/api') }}" class="text-sm font-medium text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white transition-colors">API</a>
            </div>

            <div class="flex items-center gap-4">
                <button @click="darkMode = !darkMode" class="p-2 text-zinc-500 hover:text-zinc-800 dark:text-zinc-400 dark:hover:text-white focus:outline-none transition-colors" aria-label="Toggle dark mode">
                    <flux:icon.sun x-show="!darkMode" class="w-5 h-5" style="display: none;" />
                    <flux:icon.moon x-show="darkMode" class="w-5 h-5" style="display: none;" />
                </button>
                @auth
                    <flux:button :href="route('dashboard')" variant="primary" size="sm" class="rounded-full px-6">
                        Dashboard
                    </flux:button>
                @else
                    <flux:button :href="route('login')" variant="ghost" size="sm" class="text-zinc-600 dark:text-zinc-300 hover:text-zinc-900 dark:hover:text-white hidden sm:flex">
                        Log in
                    </flux:button>
                    <flux:button :href="route('register')" variant="primary" size="sm" class="rounded-full px-6 bg-indigo-500 hover:bg-indigo-600 text-white border-none">
                        Get Started
                    </flux:button>
                @endauth
            </div>
        </div>
    </div>
</nav>