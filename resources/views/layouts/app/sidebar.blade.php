<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800">
        <flux:sidebar sticky collapsible="mobile" class="border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:sidebar.header>
                <x-app-logo :sidebar="true" href="{{ route('dashboard') }}" wire:navigate />
                <flux:sidebar.collapse class="lg:hidden" />
            </flux:sidebar.header>

            <div class="px-2 pt-2 pb-1" x-data>
                <button 
                    type="button" 
                    @click="$dispatch('open-command-palette')" 
                    class="w-full flex items-center justify-between px-3 py-2 text-xs text-zinc-500 hover:text-zinc-800 dark:text-zinc-400 dark:hover:text-zinc-200 bg-white dark:bg-zinc-800/90 hover:bg-zinc-100 dark:hover:bg-zinc-700/60 border border-zinc-200 dark:border-zinc-700/80 rounded-xl shadow-xs transition-colors group cursor-pointer"
                >
                    <span class="flex items-center gap-2">
                        <flux:icon.magnifying-glass class="size-3.5 text-zinc-400 group-hover:text-zinc-600 dark:group-hover:text-zinc-300" />
                        <span class="font-medium">Quick Search...</span>
                    </span>
                    <kbd class="px-1.5 py-0.5 text-[10px] font-mono font-medium text-zinc-400 dark:text-zinc-500 bg-zinc-100 dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded">⌘K</kbd>
                </button>
            </div>

            <flux:sidebar.nav>
                <flux:sidebar.group :heading="__('Platform')" class="grid">
                    <flux:sidebar.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>
                        {{ __('Dashboard') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="arrow-up-tray" :href="route('dashboard.sites.upload')" :current="request()->routeIs('dashboard.sites.upload')" wire:navigate>
                        {{ __('Upload Site') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="clipboard-document-list" :href="route('dashboard.activity')" :current="request()->routeIs('dashboard.activity')" wire:navigate>
                        {{ __('Activity Log') }}
                    </flux:sidebar.item>
                </flux:sidebar.group>
            </flux:sidebar.nav>

            <flux:spacer />

            <flux:sidebar.nav>
                <flux:sidebar.item icon="document-text" href="/docs/api">
                    {{ __('API Docs') }}
                </flux:sidebar.item>
                <flux:sidebar.item icon="folder-git-2" href="https://github.com/ternis-edv/drophtml.de" target="_blank">
                    {{ __('Repository') }}
                </flux:sidebar.item>
            </flux:sidebar.nav>

            <div class="px-2 py-1.5 mb-1 flex items-center justify-between text-xs text-zinc-500 rounded-lg bg-zinc-100/80 dark:bg-zinc-800/80 border border-zinc-200/60 dark:border-zinc-700/60" x-data>
                <span class="font-medium text-[11px] uppercase tracking-wider text-zinc-400">Theme</span>
                <div class="flex items-center gap-0.5">
                    <button type="button" @click="$flux.appearance = 'light'" :class="$flux.appearance === 'light' ? 'bg-white text-indigo-600 shadow-xs' : 'text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-200'" class="p-1 rounded-md transition-colors" title="Light Mode">
                        <flux:icon.sun class="size-3.5" />
                    </button>
                    <button type="button" @click="$flux.appearance = 'dark'" :class="$flux.appearance === 'dark' ? 'bg-zinc-900 text-indigo-400 shadow-xs' : 'text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-200'" class="p-1 rounded-md transition-colors" title="Dark Mode">
                        <flux:icon.moon class="size-3.5" />
                    </button>
                    <button type="button" @click="$flux.appearance = 'system'" :class="$flux.appearance === 'system' ? 'bg-white dark:bg-zinc-700 text-indigo-600 dark:text-indigo-400 shadow-xs' : 'text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-200'" class="p-1 rounded-md transition-colors" title="System Preference">
                        <flux:icon.computer-desktop class="size-3.5" />
                    </button>
                </div>
            </div>

            <x-desktop-user-menu class="hidden lg:block" :name="auth()->user()->name" />
        </flux:sidebar>

        <!-- Mobile User Menu -->
        <flux:header class="lg:hidden">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

            <div class="flex items-center ml-2" x-data>
                <button 
                    type="button" 
                    @click="$dispatch('open-command-palette')" 
                    class="p-1.5 text-zinc-500 hover:text-zinc-800 dark:text-zinc-400 dark:hover:text-zinc-200 rounded-lg hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors"
                    title="Search (⌘K)"
                >
                    <flux:icon.magnifying-glass class="size-5" />
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
