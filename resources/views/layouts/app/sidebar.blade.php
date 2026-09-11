<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-zinc-50 text-zinc-900 dark:bg-zinc-950 dark:text-zinc-100 antialiased selection:bg-indigo-500 selection:text-white">
        <flux:sidebar sticky collapsible="mobile" class="border-e border-zinc-200/80 bg-white dark:border-zinc-800 dark:bg-zinc-900/90 backdrop-blur-md">
            <flux:sidebar.header class="pt-3 pb-2">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5 group" wire:navigate>
                    <div class="size-8.5 rounded-xl bg-gradient-to-tr from-indigo-600 via-indigo-500 to-purple-500 flex items-center justify-center text-white shadow-md shadow-indigo-500/20 group-hover:scale-105 transition-transform">
                        <flux:icon.rocket-launch class="size-4.5" />
                    </div>
                    <div class="flex flex-col">
                        <span class="text-base font-black tracking-tight leading-tight bg-clip-text text-transparent bg-gradient-to-r from-zinc-900 via-zinc-800 to-zinc-600 dark:from-white dark:via-zinc-200 dark:to-zinc-400">
                            DropHTML<span class="text-[11px] font-mono font-semibold text-indigo-600 dark:text-indigo-400 ml-0.5">.de</span>
                        </span>
                        <span class="text-[10px] text-zinc-600 dark:text-zinc-400 font-medium">Static Edge Cloud</span>
                    </div>
                </a>
                <flux:sidebar.collapse class="lg:hidden" />
            </flux:sidebar.header>

            <div class="px-2 pt-2 pb-1" x-data>
                <button 
                    type="button" 
                    @click="$dispatch('open-command-palette')" 
                    class="w-full flex items-center justify-between px-3 py-2 text-xs text-zinc-500 hover:text-zinc-800 dark:text-zinc-400 dark:hover:text-zinc-200 bg-zinc-100/70 dark:bg-zinc-800/80 hover:bg-zinc-100 dark:hover:bg-zinc-700/60 border border-zinc-200/80 dark:border-zinc-700/80 rounded-xl shadow-2xs transition-all group cursor-pointer"
                >
                    <span class="flex items-center gap-2">
                        <flux:icon.magnifying-glass class="size-3.5 text-zinc-400 group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors" />
                        <span class="font-medium">Command Palette...</span>
                    </span>
                    <kbd class="px-1.5 py-0.5 text-[10px] font-mono font-semibold text-zinc-400 dark:text-zinc-500 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-md shadow-2xs">⌘K</kbd>
                </button>
            </div>

            <flux:sidebar.nav class="mt-2">
                <flux:sidebar.group :heading="__('Deployments')" class="grid gap-1">
                    <flux:sidebar.item icon="squares-2x2" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>
                        {{ __('Sites Overview') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="arrow-up-tray" :href="route('dashboard.sites.upload')" :current="request()->routeIs('dashboard.sites.upload')" wire:navigate>
                        {{ __('Deploy Site') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="clock" :href="route('dashboard.activity')" :current="request()->routeIs('dashboard.activity')" wire:navigate>
                        {{ __('Activity Log') }}
                    </flux:sidebar.item>
                </flux:sidebar.group>
            </flux:sidebar.nav>

            <flux:spacer />

            <flux:sidebar.nav>
                <flux:sidebar.group :heading="__('Resources')">
                    <flux:sidebar.item icon="document-text" href="/docs/api">
                        {{ __('API Documentation') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="folder-git-2" href="https://github.com/ternis-edv/drophtml.de" target="_blank">
                        {{ __('GitHub Repo') }}
                    </flux:sidebar.item>
                </flux:sidebar.group>
            </flux:sidebar.nav>

            <!-- Theme Switcher Pill -->
            <div class="px-2 py-1.5 mb-1 flex items-center justify-between text-xs text-zinc-500 rounded-xl bg-zinc-100/80 dark:bg-zinc-800/60 border border-zinc-200/60 dark:border-zinc-700/60" x-data>
                <span class="font-medium text-[10px] uppercase tracking-wider text-zinc-600 dark:text-zinc-400">Theme</span>
                <div class="flex items-center gap-0.5">
                    <button type="button" @click="$flux.appearance = 'light'" :class="$flux.appearance === 'light' ? 'bg-white text-indigo-600 shadow-2xs' : 'text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-200'" class="p-1.5 rounded-lg transition-all cursor-pointer" title="Light Mode">
                        <flux:icon.sun class="size-3.5" />
                    </button>
                    <button type="button" @click="$flux.appearance = 'dark'" :class="$flux.appearance === 'dark' ? 'bg-zinc-900 text-indigo-400 shadow-2xs' : 'text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-200'" class="p-1.5 rounded-lg transition-all cursor-pointer" title="Dark Mode">
                        <flux:icon.moon class="size-3.5" />
                    </button>
                    <button type="button" @click="$flux.appearance = 'system'" :class="$flux.appearance === 'system' ? 'bg-white dark:bg-zinc-700 text-indigo-600 dark:text-indigo-400 shadow-2xs' : 'text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-200'" class="p-1.5 rounded-lg transition-all cursor-pointer" title="System Preference">
                        <flux:icon.computer-desktop class="size-3.5" />
                    </button>
                </div>
            </div>

            <x-desktop-user-menu class="hidden lg:block" :name="auth()->user()->name" />
        </flux:sidebar>

        <!-- Mobile User Menu -->
        <flux:header class="lg:hidden border-b border-zinc-200/80 dark:border-zinc-800 bg-white/90 dark:bg-zinc-900/90 backdrop-blur-md">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

            <div class="flex items-center gap-1 ml-2" x-data>
                <button 
                    type="button" 
                    @click="$dispatch('open-command-palette')" 
                    class="p-1.5 text-zinc-500 hover:text-zinc-800 dark:text-zinc-400 dark:hover:text-zinc-200 rounded-lg hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors cursor-pointer"
                    title="Search (⌘K)"
                >
                    <flux:icon.magnifying-glass class="size-5" />
                </button>
                <button 
                    type="button" 
                    @click="$flux.appearance = document.documentElement.classList.contains('dark') ? 'light' : 'dark'" 
                    class="p-1.5 text-zinc-500 hover:text-zinc-800 dark:text-zinc-400 dark:hover:text-zinc-200 rounded-lg hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors cursor-pointer"
                    title="Toggle Theme"
                >
                    <flux:icon.sun class="size-5 hidden dark:block text-amber-400" />
                    <flux:icon.moon class="size-5 block dark:hidden text-indigo-600" />
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
                            class="w-full cursor-pointer"
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

        @auth
            <livewire:dashboard.command-palette />
        @endauth

        @fluxScripts
    </body>
</html>
