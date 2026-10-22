<nav 
    x-data="{ mobileMenuOpen: false, scrolled: false }"
    @scroll.window="scrolled = (window.pageYOffset > 20)"
    :class="scrolled ? 'glass-panel shadow-md border-b border-zinc-200/60 dark:border-zinc-800/60' : 'bg-transparent border-b border-transparent'"
    class="fixed top-0 w-full z-50 transition-all duration-300"
>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16 sm:h-20">
            <!-- Brand Logo -->
            <a href="{{ route('home') }}" class="flex items-center gap-3 group">
                <x-brand-lockup />
            </a>
            
            <!-- Desktop Nav Links -->
            <div class="hidden lg:flex items-center gap-7">
                @foreach ([
                    '#features' => 'Features',
                    '#demo' => 'Live Studio',
                    '#media-cloud' => 'Media Cloud',
                    '#benchmarks' => 'Benchmarks',
                    '#pricing' => 'Pricing',
                    '#faq' => 'FAQ',
                ] as $href => $label)
                    <a href="{{ $href }}" class="text-sm font-medium text-zinc-600 dark:text-zinc-400 hover:text-accent dark:hover:text-brand-300 transition-colors">{{ $label }}</a>
                @endforeach
                <a href="{{ url('/docs/api') }}" class="text-sm font-medium text-zinc-600 dark:text-zinc-400 hover:text-accent dark:hover:text-brand-300 transition-colors inline-flex items-center gap-1">
                    <span>API</span>
                    <flux:icon.arrow-top-right-on-square class="size-3 text-zinc-400" />
                </a>
            </div>

            <!-- Right Actions -->
            <div class="flex items-center gap-1 sm:gap-2">
                <!-- Theme Switcher -->
                <button 
                    @click="$flux.appearance = document.documentElement.classList.contains('dark') ? 'light' : 'dark'"
                    type="button"
                    class="p-2 rounded-xl text-zinc-500 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white hover:bg-zinc-100 dark:hover:bg-zinc-800/80 transition-colors cursor-pointer"
                    aria-label="Toggle Theme"
                >
                    <flux:icon.sun class="size-4 text-amber-500 hidden dark:block" />
                    <flux:icon.moon class="size-4 text-accent block dark:hidden" />
                </button>

                @auth
                    <flux:button :href="route('dashboard')" variant="primary" size="sm" class="rounded-xl px-3 sm:px-4 font-semibold">
                        <flux:icon.squares-2x2 class="size-3.5 mr-1" />
                        Dashboard
                    </flux:button>
                @else
                    <a href="{{ route('login') }}" class="text-sm font-semibold text-zinc-600 dark:text-zinc-300 hover:text-zinc-900 dark:hover:text-white px-3 py-2 transition-colors hidden sm:inline-block">
                        Log in
                    </a>
                    <flux:button :href="route('register')" variant="primary" size="sm" class="rounded-xl px-3 sm:px-4 font-semibold">
                        Deploy Free
                        <flux:icon.arrow-right class="size-3.5 ml-0.5" />
                    </flux:button>
                @endauth

                <!-- Mobile Hamburger Toggle -->
                <button 
                    @click="mobileMenuOpen = !mobileMenuOpen" 
                    type="button" 
                    class="lg:hidden p-2 rounded-xl text-zinc-500 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors cursor-pointer"
                    :aria-expanded="mobileMenuOpen"
                    aria-label="Toggle Navigation"
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
        class="lg:hidden glass-panel border-b border-zinc-200 dark:border-zinc-800 px-5 pt-3 pb-6 space-y-1 shadow-xl"
        style="display: none;"
    >
        @foreach ([
            '#features' => 'Features',
            '#demo' => 'Live Studio',
            '#media-cloud' => 'Media Cloud',
            '#benchmarks' => 'Benchmarks',
            '#pricing' => 'Pricing',
            '#faq' => 'FAQ',
            url('/docs/api') => 'API Documentation',
        ] as $href => $label)
            <a @click="mobileMenuOpen = false" href="{{ $href }}" class="block py-2.5 text-sm font-medium text-zinc-700 dark:text-zinc-300 hover:text-accent dark:hover:text-brand-300 transition-colors">{{ $label }}</a>
        @endforeach

        <div class="pt-4 mt-2 border-t border-zinc-200 dark:border-zinc-800 flex flex-col gap-2">
            @guest
                <flux:button :href="route('login')" variant="secondary" class="w-full justify-center">Log in</flux:button>
                <flux:button :href="route('register')" variant="primary" class="w-full justify-center">Deploy Free</flux:button>
            @endguest
        </div>
    </div>
</nav>
