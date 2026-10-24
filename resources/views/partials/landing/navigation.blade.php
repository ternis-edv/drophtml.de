<nav
    class="lp-nav"
    x-data="{ mobileMenuOpen: false, scrolled: window.pageYOffset > 20 }"
    aria-label="Main navigation"
    @keydown.escape.window="if (mobileMenuOpen) { mobileMenuOpen = false; $refs.menuToggle.focus() }"
    @click.outside="mobileMenuOpen = false"
    @resize.window="if (window.innerWidth >= 1024) mobileMenuOpen = false"
    @scroll.window="scrolled = (window.pageYOffset > 20)"
    :class="{ 'is-scrolled': scrolled }"
>
    <div class="lp-container">
        <div class="lp-nav__inner">
            <!-- Brand Logo -->
            <a href="{{ route('home') }}" class="lp-nav__brand">
                <x-brand-lockup />
            </a>

            <!-- Desktop Nav Links -->
            <div class="lp-nav__links">
                <a href="#upload">Upload</a>
                <a href="#features">Features</a>
                <a href="#faq">FAQs</a>
                <a href="{{ url('/docs/api') }}">API Docs</a>
            </div>

            <!-- Right Actions -->
            <div class="lp-nav__actions">
                <!-- Theme Switcher -->
                <button
                    type="button"
                    class="lp-icon-btn"
                    @click="$flux.appearance = document.documentElement.classList.contains('dark') ? 'light' : 'dark'"
                    aria-label="Toggle theme"
                >
                    <svg class="lp-theme-sun" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="12" cy="12" r="4" />
                        <path d="M12 2v2" /><path d="M12 20v2" />
                        <path d="m4.93 4.93 1.41 1.41" /><path d="m17.66 17.66 1.41 1.41" />
                        <path d="M2 12h2" /><path d="M20 12h2" />
                        <path d="m6.34 17.66-1.41 1.41" /><path d="m19.07 4.93-1.41 1.41" />
                    </svg>
                    <svg class="lp-theme-moon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z" />
                    </svg>
                </button>

                @auth
                    <a href="{{ route('dashboard') }}" class="lp-btn lp-btn--primary lp-btn--sm">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <rect width="7" height="9" x="3" y="3" rx="1" />
                            <rect width="7" height="5" x="14" y="3" rx="1" />
                            <rect width="7" height="9" x="14" y="12" rx="1" />
                            <rect width="7" height="5" x="3" y="16" rx="1" />
                        </svg>
                        Dashboard
                    </a>
                @else
                    <a href="{{ route('login') }}" class="lp-login">Log in</a>
                    <a href="{{ route('register') }}" class="lp-btn lp-btn--primary lp-btn--sm">
                        Deploy Free
                        <svg class="lp-arrow" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M5 12h14" /><path d="m12 5 7 7-7 7" />
                        </svg>
                    </a>
                @endauth

                <!-- Mobile Hamburger Toggle -->
                <button
                    type="button"
                    class="lp-icon-btn lp-nav__burger"
                    x-ref="menuToggle"
                    aria-controls="mobile-navigation"
                    @click="mobileMenuOpen = !mobileMenuOpen"
                    :aria-expanded="mobileMenuOpen"
                    aria-label="Toggle navigation"
                >
                    <svg x-show="!mobileMenuOpen" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width: 1.25rem; height: 1.25rem;" aria-hidden="true">
                        <path d="M3 6h18" /><path d="M3 12h18" /><path d="M3 18h18" />
                    </svg>
                    <svg x-show="mobileMenuOpen" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width: 1.25rem; height: 1.25rem; display: none;" aria-hidden="true">
                        <path d="M18 6 6 18" /><path d="m6 6 12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Mobile Navigation Drawer -->
    <div
        class="lp-nav__drawer"
        id="mobile-navigation"
        x-show="mobileMenuOpen"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 -translate-y-2"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 -translate-y-2"
        style="display: none;"
    >
        <a href="#upload" @click="mobileMenuOpen = false">Upload</a>
        <a href="#features" @click="mobileMenuOpen = false">Features</a>
        <a href="#faq" @click="mobileMenuOpen = false">FAQs</a>
        <a href="{{ url('/docs/api') }}" @click="mobileMenuOpen = false">API Documentation</a>

        <div class="lp-drawer-actions">
            @guest
                <a href="{{ route('login') }}" class="lp-btn lp-btn--ghost">Log in</a>
                <a href="{{ route('register') }}" class="lp-btn lp-btn--primary">Deploy Free</a>
            @endguest
        </div>
    </div>
</nav>
