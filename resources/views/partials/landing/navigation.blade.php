<nav 
    x-data="{ mobileMenuOpen: false, scrolled: false }"
    @scroll.window="scrolled = (window.pageYOffset > 20)"
    :class="scrolled ? 'glass-panel shadow-md border-b border-zinc-200/80 dark:border-zinc-800/80' : 'bg-transparent border-b border-transparent'"
    class="fixed top-0 w-full z-50 transition-all duration-300"
>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16 sm:h-20">
            <!-- Brand Logo -->
            <a href="{{ route('home') }}" class="flex items-center gap-3 group">
                <x-brand-lockup />
            </a>
            
            <!-- Desktop Nav Links -->
            <div class="hidden lg:flex items-center space-x-7">
                <a href="#features" class="text-xs font-semibold text-zinc-600 dark:text-zinc-400 hover:text-indigo-600 dark:hover:text-white transition-colors">Features</a>
                <a href="#demo" class="text-xs font-semibold text-zinc-600 dark:text-zinc-400 hover:text-indigo-600 dark:hover:text-white transition-colors">Live Studio</a>
                <a href="#media-cloud" class="text-xs font-semibold text-zinc-600 dark:text-zinc-400 hover:text-indigo-600 dark:hover:text-white transition-colors flex items-center gap-1.5">
                    <span>Media Cloud</span>
                    <span class="px-1.5 py-0.5 rounded-full bg-cyan-500/10 text-cyan-600 dark:text-cyan-400 text-[9px] font-bold border border-cyan-500/20">Cloud CDN</span>
                </a>
                <a href="#benchmarks" class="text-xs font-semibold text-zinc-600 dark:text-zinc-400 hover:text-indigo-600 dark:hover:text-white transition-colors">Benchmarks</a>
                <a href="#pricing" class="text-xs font-semibold text-zinc-600 dark:text-zinc-400 hover:text-indigo-600 dark:hover:text-white transition-colors">Pricing</a>
                <a href="#faq" class="text-xs font-semibold text-zinc-600 dark:text-zinc-400 hover:text-indigo-600 dark:hover:text-white transition-colors">FAQ</a>
                <a href="{{ url('/docs/api') }}" class="text-xs font-semibold text-zinc-600 dark:text-zinc-400 hover:text-indigo-600 dark:hover:text-white transition-colors flex items-center gap-1">
                    <span>API</span>
                    <flux:icon.arrow-top-right-on-square class="size-3 text-zinc-400" />
                </a>
            </div>

            <!-- Right Actions -->
            <div class="flex items-center gap-1 sm:gap-2.5">
                <!-- Theme Switcher -->
                <button 
                    @click="$flux.appearance = document.documentElement.classList.contains('dark') ? 'light' : 'dark'"
                    type="button"
                    class="p-2 rounded-xl text-zinc-500 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white hover:bg-zinc-100 dark:hover:bg-zinc-800/80 transition-colors cursor-pointer"
                    aria-label="Toggle Theme"
                >
                    <flux:icon.sun class="size-4 text-amber-400 hidden dark:block" />
                    <flux:icon.moon class="size-4 text-indigo-600 block dark:hidden" />
                </button>

                @auth
                    <flux:button :href="route('dashboard')" variant="primary" size="sm" class="rounded-xl px-3 sm:px-4 py-2 font-bold shadow-xs bg-indigo-600 hover:bg-indigo-500 text-white cursor-pointer">
                        <flux:icon.squares-2x2 class="size-3.5 mr-1" />
                        Dashboard
                    </flux:button>
                @else
                    <a href="{{ route('login') }}" class="text-xs font-bold text-zinc-600 dark:text-zinc-300 hover:text-zinc-900 dark:hover:text-white px-3 py-2 transition-colors hidden sm:inline-block">
                        Log in
                    </a>
                    <flux:button :href="route('register')" variant="primary" size="sm" class="rounded-xl px-3 sm:px-4 py-2 font-bold shadow-xs bg-indigo-600 hover:bg-indigo-500 text-white cursor-pointer">
                        Deploy Free &rarr;
                    </flux:button>
                @endauth

                <!-- Mobile Hamburger Toggle -->
                <button 
                    @click="mobileMenuOpen = !mobileMenuOpen" 
                    type="button" 
                    class="lg:hidden p-2 rounded-xl text-zinc-500 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors cursor-pointer"
                >
                    <flux:icon.bars-3 x-show="!mobileMenuOpen" class="size-5" />
                    <flux:icon.x-mark x-show="mobileMenuOpen" class="size-5" style="display: none;" />
                </button>
            </div>
        </div>
    </div>

    <!-- Mobile Navigation Drawer -->
    <div 
        x-show="mobileMenuOpen" 
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 -translate-y-2"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 -translate-y-2"
        class="lg:hidden glass-panel border-b border-zinc-200 dark:border-zinc-800 px-5 pt-3 pb-6 space-y-2.5 shadow-xl"
        style="display: none;"
    >
        <a @click="mobileMenuOpen = false" href="#features" class="block py-2 text-xs font-semibold text-zinc-700 dark:text-zinc-300 hover:text-indigo-600">Features</a>
        <a @click="mobileMenuOpen = false" href="#demo" class="block py-2 text-xs font-semibold text-zinc-700 dark:text-zinc-300 hover:text-indigo-600">Live Studio</a>
        <a @click="mobileMenuOpen = false" href="#media-cloud" class="block py-2 text-xs font-semibold text-zinc-700 dark:text-zinc-300 hover:text-indigo-600">Cloud Media Storage</a>
        <a @click="mobileMenuOpen = false" href="#benchmarks" class="block py-2 text-xs font-semibold text-zinc-700 dark:text-zinc-300 hover:text-indigo-600">Benchmarks</a>
        <a @click="mobileMenuOpen = false" href="#pricing" class="block py-2 text-xs font-semibold text-zinc-700 dark:text-zinc-300 hover:text-indigo-600">Pricing</a>
        <a @click="mobileMenuOpen = false" href="#faq" class="block py-2 text-xs font-semibold text-zinc-700 dark:text-zinc-300 hover:text-indigo-600">FAQ</a>
        <a @click="mobileMenuOpen = false" href="{{ url('/docs/api') }}" class="block py-2 text-xs font-semibold text-zinc-700 dark:text-zinc-300 hover:text-indigo-600">API Documentation</a>

        <div class="pt-4 border-t border-zinc-200 dark:border-zinc-800 flex flex-col gap-2">
            @guest
                <flux:button :href="route('login')" variant="ghost" class="w-full justify-center">Log in</flux:button>
                <flux:button :href="route('register')" variant="primary" class="w-full justify-center">Deploy Free</flux:button>
            @endguest
        </div>
    </div>
</nav>