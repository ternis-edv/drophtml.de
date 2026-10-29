<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-zinc-50 text-zinc-900 dark:bg-zinc-950 dark:text-zinc-100 antialiased selection:bg-accent selection:text-white">
        <!-- Keyboard users: jump straight past the navigation -->
        <a
            href="#main-content"
            class="sr-only focus:not-sr-only focus:fixed focus:start-4 focus:top-4 focus:z-[100] focus:rounded-xl focus:bg-accent focus:px-4 focus:py-2.5 focus:text-sm focus:font-semibold focus:text-white focus:shadow-lg"
        >
            {{ __('Skip to main content') }}
        </a>

        <flux:sidebar sticky collapsible="mobile" class="border-e border-zinc-200/80 bg-white/95 dark:border-zinc-800/80 dark:bg-zinc-900/95 backdrop-blur-xl">
            <!-- Brand Header -->
            <flux:sidebar.header class="pt-3 pb-2">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5 group" wire:navigate>
                    <x-brand-lockup />
                </a>
                <flux:sidebar.collapse class="lg:hidden" />
            </flux:sidebar.header>

            <!-- Command Palette Trigger -->
            <div class="px-2 pt-2 pb-1" x-data>
                <button 
                    type="button" 
                    @click="$dispatch('open-command-palette')" 
                    class="w-full flex items-center justify-between px-3 py-2 text-xs text-zinc-500 hover:text-zinc-800 dark:text-zinc-400 dark:hover:text-zinc-200 bg-zinc-100/80 dark:bg-zinc-800/80 hover:bg-zinc-100 dark:hover:bg-zinc-700/60 border border-zinc-200/80 dark:border-zinc-700/80 rounded-xl shadow-2xs transition-all group cursor-pointer"
                >
                    <span class="flex items-center gap-2">
                        <flux:icon.magnifying-glass class="size-3.5 text-zinc-400 group-hover:text-accent transition-colors" />
                        <span class="font-medium">Quick search...</span>
                    </span>
                    <kbd class="px-1.5 py-0.5 text-[10px] font-mono font-semibold text-zinc-400 dark:text-zinc-500 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-md shadow-2xs">⌘K</kbd>
                </button>
            </div>

            <!-- Main Navigation Groups -->
            <flux:sidebar.nav class="mt-2">
                <flux:sidebar.group :heading="__('Workspaces')" class="grid gap-1">
                    <flux:sidebar.item icon="squares-2x2" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>
                        {{ __('Sites Overview') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="arrow-up-tray" :href="route('dashboard.sites.upload')" :current="request()->routeIs('dashboard.sites.upload')" wire:navigate>
                        {{ __('Deploy Site') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="photo" :href="route('dashboard.media')" :current="request()->routeIs('dashboard.media')" wire:navigate>
                        <span class="flex items-center justify-between w-full">
                            <span>{{ __('Cloud Media') }}</span>
                            <span class="px-1.5 py-0.5 rounded-full text-[9px] font-bold uppercase tracking-wider bg-accent/10 text-accent dark:text-brand-300 border border-accent/20">New</span>
                        </span>
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="circle-stack" :href="route('settings.storage')" :current="request()->routeIs('settings.storage')" wire:navigate>
                        {{ __('Storage & Usage') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="chat-bubble-left-right" :href="route('dashboard.help')" :current="request()->routeIs('dashboard.help')" wire:navigate>AI Help</flux:sidebar.item>
                    <flux:sidebar.item icon="clock" :href="route('dashboard.activity')" :current="request()->routeIs('dashboard.activity')" wire:navigate>
                        {{ __('Activity Log') }}
                    </flux:sidebar.item>
                </flux:sidebar.group>
            </flux:sidebar.nav>

            <flux:spacer />

            <flux:sidebar.nav>
                <flux:sidebar.group :heading="__('Developer Resources')">
                    <flux:sidebar.item icon="cpu-chip" :href="route('docs.agents')" :current="request()->routeIs('docs.agents')">
                        {{ __('DropHTML for Agents') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="document-text" href="/docs/api">
                        {{ __('API Documentation') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="folder-git-2" href="https://github.com/ternis-edv/drophtml.de" target="_blank">
                        {{ __('GitHub Repository') }}
                    </flux:sidebar.item>
                </flux:sidebar.group>
            </flux:sidebar.nav>

            <!-- Storage quota at a glance -->
            @php($sidebarStorage = app(\App\Services\StorageUsageService::class)->getUserStorageStats(auth()->user()))
            <div class="px-2 pb-1">
                <a
                    href="{{ route('settings.storage') }}"
                    wire:navigate
                    class="group block rounded-xl border border-zinc-200/60 bg-zinc-100/70 p-3 transition-colors duration-150 hover:border-accent/30 hover:bg-zinc-100 dark:border-zinc-700/50 dark:bg-zinc-800/50 dark:hover:border-accent/40 dark:hover:bg-zinc-800/80"
                    title="{{ __('Storage & Usage') }}"
                >
                    <span class="flex items-center justify-between text-[10px] font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                        <span class="flex items-center gap-1.5">
                            <flux:icon.circle-stack class="size-3" />
                            {{ __('Storage') }}
                        </span>
                        <span class="tabular-nums text-zinc-400 transition-colors group-hover:text-accent dark:text-zinc-500">{{ $sidebarStorage['used_percentage'] }}%</span>
                    </span>

                    @php($storagePct = min(100, max(0, (int) $sidebarStorage['used_percentage'])))
                    <span class="meter mt-2 block">
                        <span class="meter-fill block {{ $storagePct >= 90 ? 'is-danger' : ($storagePct >= 75 ? 'is-warn' : '') }}" style="width: {{ $storagePct }}%"></span>
                    </span>

                    <span class="mt-1.5 flex items-center justify-between text-[11px] font-medium text-zinc-500 dark:text-zinc-400">
                        <span class="tabular-nums">{{ $sidebarStorage['formatted']['total_used'] }}</span>
                        <span class="tabular-nums text-zinc-400 dark:text-zinc-500">/ {{ $sidebarStorage['formatted']['quota'] }}</span>
                    </span>
                </a>
            </div>

            <!-- Theme Switcher Pill -->
            <div class="px-2 py-1.5 mb-1 flex items-center justify-between text-xs text-zinc-500 rounded-xl bg-zinc-100/80 dark:bg-zinc-800/60 border border-zinc-200/60 dark:border-zinc-700/60" x-data>
                <span class="font-medium text-[10px] uppercase tracking-wider text-zinc-600 dark:text-zinc-400">Theme</span>
                <div class="flex items-center gap-0.5" role="group" aria-label="{{ __('Color theme') }}">
                    <button type="button" @click="$flux.appearance = 'light'" :aria-pressed="$flux.appearance === 'light'" :class="$flux.appearance === 'light' ? 'bg-white text-accent shadow-2xs' : 'text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-200'" class="p-1.5 rounded-lg transition-all cursor-pointer" title="Light Mode" aria-label="{{ __('Light mode') }}">
                        <flux:icon.sun class="size-3.5" />
                    </button>
                    <button type="button" @click="$flux.appearance = 'dark'" :aria-pressed="$flux.appearance === 'dark'" :class="$flux.appearance === 'dark' ? 'bg-zinc-900 text-accent dark:text-brand-300 shadow-2xs' : 'text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-200'" class="p-1.5 rounded-lg transition-all cursor-pointer" title="Dark Mode" aria-label="{{ __('Dark mode') }}">
                        <flux:icon.moon class="size-3.5" />
                    </button>
                    <button type="button" @click="$flux.appearance = 'system'" :aria-pressed="$flux.appearance === 'system'" :class="$flux.appearance === 'system' ? 'bg-white dark:bg-zinc-700 text-accent dark:text-brand-300 shadow-2xs' : 'text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-200'" class="p-1.5 rounded-lg transition-all cursor-pointer" title="System Preference" aria-label="{{ __('System preference') }}">
                        <flux:icon.computer-desktop class="size-3.5" />
                    </button>
                </div>
            </div>

            <x-desktop-user-menu class="hidden lg:block" :name="auth()->user()->name" />
        </flux:sidebar>

        <!-- Mobile Header Bar -->
        <flux:header class="lg:hidden border-b border-zinc-200/80 dark:border-zinc-800 bg-white/90 dark:bg-zinc-900/90 backdrop-blur-md">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

            <div class="flex items-center gap-1.5 ml-2" x-data>
                <button 
                    type="button" 
                    @click="$dispatch('open-command-palette')" 
                    class="p-2 text-zinc-500 hover:text-zinc-800 dark:text-zinc-400 dark:hover:text-zinc-200 rounded-lg hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors cursor-pointer"
                    title="Search (⌘K)"
                    aria-label="{{ __('Open quick search') }}"
                >
                    <flux:icon.magnifying-glass class="size-5" />
                </button>
                <button 
                    type="button" 
                    @click="$flux.appearance = document.documentElement.classList.contains('dark') ? 'light' : 'dark'" 
                    class="p-2 text-zinc-500 hover:text-zinc-800 dark:text-zinc-400 dark:hover:text-zinc-200 rounded-lg hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors cursor-pointer"
                    title="Toggle Theme"
                    aria-label="{{ __('Toggle light and dark mode') }}"
                >
                    <flux:icon.sun class="size-5 hidden dark:block text-amber-500" />
                    <flux:icon.moon class="size-5 block dark:hidden text-accent" />
                </button>
            </div>

            <flux:spacer />

            <flux:dropdown position="top" align="end">
                <flux:profile
                    :initials="auth()->user()->initials()"
                    icon-trailing="chevron-down"
                />

                <flux:menu>
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                <flux:avatar
                                    :name="auth()->user()->name"
                                    :initials="auth()->user()->initials()"
                                />

                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <flux:heading class="truncate">{{ auth()->user()->name }}</flux:heading>
                                    <flux:text class="truncate">{{ auth()->user()->email }}</flux:text>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.radio.group>
                        <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>
                            {{ __('Settings') }}
                        </flux:menu.item>
                        <flux:menu.item :href="route('settings.storage')" icon="circle-stack" wire:navigate>
                            {{ __('Storage & Usage') }}
                        </flux:menu.item>
                        <flux:menu.item :href="route('api-tokens.edit')" icon="key" wire:navigate>
                            {{ __('API Tokens') }}
                        </flux:menu.item>
                        <flux:menu.item :href="route('appearance.edit')" icon="paint-brush" wire:navigate>
                            {{ __('Appearance') }}
                        </flux:menu.item>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item
                            as="button"
                            type="submit"
                            icon="arrow-right-start-on-rectangle"
                            class="w-full cursor-pointer text-red-600 dark:text-red-400"
                            data-test="logout-button"
                        >
                            {{ __('Log out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:header>

        {{ $slot }}

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
