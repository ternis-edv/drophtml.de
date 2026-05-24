<nav class="fixed top-0 w-full z-50 glass-panel border-b border-zinc-800/50 transition-all duration-300">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">
            <div class="flex items-center gap-2">
                <flux:icon.rocket class="w-8 h-8 text-indigo-400" />
                <span class="text-xl font-bold tracking-tight bg-clip-text text-transparent bg-gradient-to-r from-indigo-400 to-purple-400">
                    DropHTML
                </span>
            </div>
            
            <div class="hidden md:flex items-center space-x-8">
                <a href="#features" class="text-sm font-medium text-zinc-400 hover:text-white transition-colors">Features</a>
                <a href="#demo" class="text-sm font-medium text-zinc-400 hover:text-white transition-colors">Interactive Demo</a>
                <a href="{{ url('/docs/api') }}" class="text-sm font-medium text-zinc-400 hover:text-white transition-colors">API</a>
            </div>

            <div class="flex items-center gap-4">
                @auth
                    <flux:button :href="route('dashboard')" variant="primary" size="sm" class="rounded-full px-6">
                        Dashboard
                    </flux:button>
                @else
                    <flux:button :href="route('login')" variant="ghost" size="sm" class="text-zinc-300 hover:text-white hidden sm:flex">
                        Log in
                    </flux:button>
                    <flux:button :href="route('register')" variant="primary" size="sm" class="rounded-full px-6 bg-indigo-500 hover:bg-indigo-600 border-none">
                        Get Started
                    </flux:button>
                @endauth
            </div>
        </div>
    </div>
</nav>