<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-zinc-50 text-zinc-900 antialiased dark:bg-zinc-950 dark:text-zinc-100 selection:bg-accent selection:text-white">
        <div class="relative min-h-screen grid lg:grid-cols-12 overflow-hidden">
            <!-- Left Branding & Showcase Panel (Desktop lg:col-span-5 xl:col-span-6) -->
            <div class="relative hidden lg:flex lg:col-span-5 xl:col-span-6 flex-col justify-between p-12 bg-zinc-950 text-white border-e border-zinc-800/80 overflow-hidden">
                <!-- Ambient Glow Effects -->
                <div class="absolute -top-24 -left-24 w-96 h-96 rounded-full bg-accent/25 blur-[120px] pointer-events-none"></div>
                <div class="absolute -bottom-24 -right-24 w-96 h-96 rounded-full bg-brand-400/15 blur-[120px] pointer-events-none"></div>
                <div class="absolute inset-0 bg-[radial-gradient(#312e81_1px,transparent_1px)] [background-size:24px_24px] opacity-20 pointer-events-none"></div>

                <!-- Top Brand -->
                <div class="relative z-10">
                    <a href="{{ route('home') }}" class="inline-flex items-center gap-3 group" wire:navigate>
                        <x-brand-lockup inverse />
                    </a>
                </div>

                <!-- Center Content: Value Propositions -->
                <div class="relative z-10 my-auto py-12 space-y-8">
                    <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-accent/15 border border-accent/25 text-brand-200 text-xs font-medium">
                        <span class="size-2 rounded-full bg-brand-300 animate-ping"></span>
                        <span>Instant Static Cloud Hosting</span>
                    </div>

                    <h2 class="text-3xl xl:text-4xl font-bold tracking-tight leading-snug">
                        Deploy your HTML in seconds, not hours.
                    </h2>

                    <div class="space-y-4 text-sm text-zinc-400">
                        <div class="flex items-start gap-3">
                            <div class="size-6 rounded-lg bg-accent/15 text-brand-300 flex items-center justify-center shrink-0 mt-0.5">
                                <flux:icon.check class="size-3.5" />
                            </div>
                            <p><strong class="text-zinc-200 font-semibold">Zero-Config Publishing:</strong> Drag & drop single HTML files or full ZIP archives for immediate deployment.</p>
                        </div>
                        <div class="flex items-start gap-3">
                            <div class="size-6 rounded-lg bg-accent/15 text-brand-300 flex items-center justify-center shrink-0 mt-0.5">
                                <flux:icon.check class="size-3.5" />
                            </div>
                            <p><strong class="text-zinc-200 font-semibold">Custom Domains & Auto-SSL:</strong> Connect your domains with zero manual cert renewal.</p>
                        </div>
                        <div class="flex items-start gap-3">
                            <div class="size-6 rounded-lg bg-emerald-500/10 text-emerald-400 flex items-center justify-center shrink-0 mt-0.5">
                                <flux:icon.check class="size-3.5" />
                            </div>
                            <p><strong class="text-zinc-200 font-semibold">In-Browser Code Studio:</strong> Edit HTML, CSS, and JS live with responsive preview and instant rollbacks.</p>
                        </div>
                    </div>
                </div>

                <!-- Bottom Quote / Stats Badge -->
                <div class="relative z-10 pt-6 border-t border-zinc-800/80 flex items-center justify-between text-xs text-zinc-400">
                    <div class="flex items-center gap-2">
                        <span class="size-2 rounded-full bg-emerald-400"></span>
                        <span>99.99% Edge Uptime</span>
                    </div>
                    <span class="font-mono text-zinc-400">v2.0 Stable</span>
                </div>
            </div>

            <!-- Right Form Panel (lg:col-span-7 xl:col-span-6) -->
            <div class="flex flex-col justify-between p-6 sm:p-10 lg:p-12 lg:col-span-7 xl:col-span-6 relative">
                <!-- Mobile Top Bar with Logo & Theme Switcher -->
                <div class="flex items-center justify-between mb-8">
                    <a href="{{ route('home') }}" class="lg:hidden inline-flex items-center gap-2.5 group" wire:navigate>
                        <x-brand-lockup />
                    </a>

                    <div class="hidden lg:block"></div>

                    <!-- Theme Toggle -->
                    <div class="flex items-center gap-1 text-xs" x-data>
                        <button type="button" @click="$flux.appearance = document.documentElement.classList.contains('dark') ? 'light' : 'dark'" class="p-2 rounded-xl text-zinc-500 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white hover:bg-zinc-100 dark:hover:bg-zinc-800/80 transition-colors cursor-pointer" title="Toggle Theme">
                            <flux:icon.sun class="size-4 hidden dark:block text-amber-500" />
                            <flux:icon.moon class="size-4 block dark:hidden text-accent" />
                        </button>
                    </div>
                </div>

                <!-- Main Auth Card Content Container -->
                <div class="my-auto mx-auto w-full max-w-md">
                    <div class="glass-card rounded-2xl p-6 sm:p-8 shadow-xl shadow-zinc-900/5 dark:shadow-none border border-zinc-200/80 dark:border-zinc-800">
                        {{ $slot }}
                    </div>
                </div>

                <!-- Footer Links -->
                <div class="mt-8 pt-4 border-t border-zinc-200/60 dark:border-zinc-800/60 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-zinc-600 dark:text-zinc-400">
                    <div>
                        &copy; {{ date('Y') }} DropHTML.de &bull; All rights reserved.
                    </div>
                    <div class="flex items-center gap-4">
                        <a href="{{ route('legal.show', 'privacy') }}" class="hover:text-zinc-900 dark:hover:text-zinc-200 transition-colors">Privacy</a>
                        <a href="{{ route('legal.show', 'terms') }}" class="hover:text-zinc-900 dark:hover:text-zinc-200 transition-colors">Terms</a>
                        <a href="{{ route('legal.show', 'imprint') }}" class="hover:text-zinc-900 dark:hover:text-zinc-200 transition-colors">Imprint</a>
                    </div>
                </div>
            </div>
        </div>

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
