<nav 
    x-data="{ mobileMenuOpen: false }"
    class="fixed top-0 w-full z-50 glass-panel border-b border-zinc-200 dark:border-zinc-800/60 transition-all duration-300"
>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">
            <!-- Brand Logo -->
            <a href="{{ route('home') }}" class="flex items-center gap-2.5 hover:opacity-90 transition-opacity">
                <div class="w-8 h-8 rounded-lg bg-gradient-to-tr from-indigo-600 to-purple-500 flex items-center justify-center text-white shadow-md shadow-indigo-500/20">
                    <flux:icon.rocket-launch class="w-5 h-5 text-white" />
                </div>
                <span class="text-xl font-black tracking-tight bg-clip-text text-transparent bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-500 dark:from-indigo-400 dark:via-purple-400 dark:to-pink-400">
                    DropHTML<span class="text-xs font-mono font-medium text-zinc-400 dark:text-zinc-500 ml-0.5">.de</span>
                </span>
            </a>
            
            <!-- Desktop Nav Links -->
            <div class="hidden md:flex items-center space-x-8">
                <a href="#features" class="text-sm font-medium text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white transition-colors">Features</a>
                <a href="#demo" class="text-sm font-medium text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white transition-colors">Interactive Demo</a>
                <a href="#pricing" class="text-sm font-medium text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white transition-colors">Pricing</a>
                <a href="{{ url('/docs/api') }}" class="text-sm font-medium text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white transition-colors flex items-center gap-1">
                    API Docs <span class="px-1.5 py-0.5 rounded bg-zinc-200 dark:bg-zinc-800 text-[10px] font-mono">v1</span>
                </a>
            </div>

            <!-- Right Actions -->
            <div class="flex items-center gap-3">
                <!-- Theme Toggle Button -->
                <button 
                    @click="darkMode = !darkMode"
                    type="button"
                    class="p-2 rounded-full text-zinc-500 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white hover:bg-zinc-100 dark:hover:bg-zinc-800/60 transition-colors cursor-pointer"
                    aria-label="Toggle theme"
                >
                    <flux:icon.sun x-show="darkMode" class="w-4 h-4 text-amber-400" />
                    <flux:icon.moon x-show="!darkMode" class="w-4 h-4 text-indigo-600" />
                </button>

                @auth
                    <flux:button :href="route('dashboard')" variant="primary" size="sm" class="rounded-full px-5 shadow-sm">
                        Dashboard
                    </flux:button>
                @else
                    <flux:button :href="route('login')" variant="ghost" size="sm" class="text-zinc-600 dark:text-zinc-300 hover:text-zinc-900 dark:hover:text-white hidden sm:flex">
                        Log in
                    </flux:button>
                    <flux:button :href="route('register')" variant="primary" size="sm" class="rounded-full px-5 bg-indigo-600 hover:bg-indigo-500 text-white shadow-sm border-none">
                        Get Started
                    </flux:button>
                @endauth

                <!-- Mobile Hamburger Toggle -->
                <button 
                    @click="mobileMenuOpen = !mobileMenuOpen" 
                    type="button" 
                    class="md:hidden p-2 rounded-lg text-zinc-500 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors"
                >
                    <flux:icon.bars-3 x-show="!mobileMenuOpen" class="w-6 h-6" />
                    <flux:icon.x-mark x-show="mobileMenuOpen" class="w-6 h-6" />
                </button>
            </div>
        </div>
    </div>

    <!-- Mobile Drawer Menu -->
    <div 
        x-show="mobileMenuOpen" 
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 -translate-y-2"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 -translate-y-2"
        class="md:hidden glass-panel border-b border-zinc-200 dark:border-zinc-800 px-4 pt-2 pb-6 space-y-3"
        style="display: none;"
    >
        <a @click="mobileMenuOpen = false" href="#features" class="block py-2 text-sm font-medium text-zinc-700 dark:text-zinc-300 hover:text-indigo-600">Features</a>
        <a @click="mobileMenuOpen = false" href="#demo" class="block py-2 text-sm font-medium text-zinc-700 dark:text-zinc-300 hover:text-indigo-600">Interactive Demo</a>
        <a @click="mobileMenuOpen = false" href="#pricing" class="block py-2 text-sm font-medium text-zinc-700 dark:text-zinc-300 hover:text-indigo-600">Pricing</a>
        <a @click="mobileMenuOpen = false" href="{{ url('/docs/api') }}" class="block py-2 text-sm font-medium text-zinc-700 dark:text-zinc-300 hover:text-indigo-600">API Documentation</a>

        <div class="pt-4 border-t border-zinc-200 dark:border-zinc-800 flex flex-col gap-2">
            @guest
                <flux:button :href="route('login')" variant="ghost" class="w-full justify-center">Log in</flux:button>
                <flux:button :href="route('register')" variant="primary" class="w-full justify-center">Get Started Free</flux:button>
            @endguest
        </div>
    </div>
</nav>