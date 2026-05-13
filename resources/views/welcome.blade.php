<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ config('app.name', 'DropHTML') }} - Instant HTML Hosting</title>

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @fluxAppearance

        <style>
            @keyframes fade-in {
                from { opacity: 0; transform: translateY(10px); }
                to { opacity: 1; transform: translateY(0); }
            }
            .animate-fade-in {
                animation: fade-in 0.6s ease-out forwards;
            }
            .animate-pulse-slow {
                animation: pulse 4s cubic-bezier(0.4, 0, 0.6, 1) infinite;
            }
            @keyframes pulse {
                0%, 100% { opacity: 1; }
                50% { opacity: 0.8; }
            }
        </style>
    </head>
    <body class="min-h-screen bg-white antialiased dark:bg-zinc-950 text-zinc-900 dark:text-zinc-100 flex flex-col items-center transition-colors duration-300 overflow-x-hidden">
        
        <!-- Header -->
        <header class="w-full lg:max-w-6xl px-6 py-8 flex justify-between items-center sticky top-0 z-50 bg-white/80 dark:bg-zinc-950/80 backdrop-blur-md">
            <a href="/" class="flex items-center gap-2 group" wire:navigate>
                <div class="p-2 rounded-xl bg-blue-600 group-hover:scale-110 transition-transform duration-300 shadow-lg shadow-blue-500/20">
                    <flux:icon.cloud-arrow-up class="size-6 text-white" />
                </div>
                <div class="font-black text-2xl tracking-tighter">DropHTML<span class="text-blue-500">.de</span></div>
            </a>
            
            <div class="flex items-center gap-4">
                @if (Route::has('login'))
                    <nav class="hidden md:flex items-center gap-8 mr-4">
                        <a href="#features" class="text-sm font-bold text-zinc-500 hover:text-zinc-900 dark:hover:text-white transition-colors">Features</a>
                        <a href="#analytics" class="text-sm font-bold text-zinc-500 hover:text-zinc-900 dark:hover:text-white transition-colors">Analytics</a>
                    </nav>
                    <div class="flex items-center gap-2">
                        @auth
                            <flux:button :href="route('dashboard')" variant="primary" size="sm">Go to Dashboard</flux:button>
                        @else
                            <flux:button :href="route('login')" variant="ghost" size="sm">Log in</flux:button>
                            <flux:button :href="route('register')" variant="primary" size="sm" class="px-6">Sign up</flux:button>
                        @endauth
                    </div>
                @endif
            </div>
        </header>

        <main class="w-full flex flex-col items-center">
            @include('partials.landing.hero')

            <div id="features" class="w-full bg-zinc-50 dark:bg-zinc-900/30 flex justify-center border-y border-zinc-100 dark:border-zinc-800/50">
                @include('partials.landing.features')
            </div>

            <div id="analytics" class="w-full flex justify-center">
                @include('partials.landing.stats-preview')
            </div>

            <div class="w-full bg-zinc-50 dark:bg-zinc-900/30 flex justify-center border-t border-zinc-100 dark:border-zinc-800/50">
                @include('partials.landing.cta')
            </div>
        </main>

        <!-- Footer -->
        <footer class="w-full flex justify-center bg-white dark:bg-zinc-950 border-t border-zinc-100 dark:border-zinc-900 py-16">
            <div class="w-full lg:max-w-6xl px-6 grid grid-cols-1 md:grid-cols-4 gap-12">
                <div class="md:col-span-2 space-y-6">
                    <div class="flex items-center gap-2">
                        <flux:icon.cloud-arrow-up class="size-6 text-blue-600" />
                        <div class="font-black text-2xl tracking-tighter">DropHTML<span class="text-blue-500">.de</span></div>
                    </div>
                    <p class="text-zinc-500 dark:text-zinc-400 max-w-sm leading-relaxed">
                        Instant hosting for static sites. Deploy from anywhere, scale to everywhere. Proudly built for developers.
                    </p>
                    <div class="flex gap-4">
                        <flux:button href="https://github.com/ternis-edv/drophtml.de" target="_blank" variant="ghost" size="sm">
                            <svg class="size-4" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path fill-rule="evenodd" d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.531 1.032 1.531 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0022 12.017C22 6.484 17.522 2 12 2z" clip-rule="evenodd" />
                            </svg>
                        </flux:button>
                    </div>
                </div>

                <div class="space-y-4">
                    <h4 class="font-bold uppercase text-xs tracking-widest text-zinc-400">Platform</h4>
                    <nav class="flex flex-col gap-2">
                        <a href="#features" class="text-sm text-zinc-500 hover:text-blue-500 transition-colors">Features</a>
                        <a href="#analytics" class="text-sm text-zinc-500 hover:text-blue-500 transition-colors">Analytics</a>
                        <a href="#features" class="text-sm text-zinc-500 hover:text-blue-500 transition-colors">100% Free</a>
                        <a href="https://github.com/ternis-edv/drophtml.de" target="_blank" class="text-sm text-zinc-500 hover:text-blue-500 transition-colors">Open Source</a>
                    </nav>
                </div>

                <div class="space-y-4">
                    <h4 class="font-bold uppercase text-xs tracking-widest text-zinc-400">Legal</h4>
                    <nav class="flex flex-col gap-2">
                        <a href="https://ternis-edv.de/impressum" target="_blank" class="text-sm text-zinc-500 hover:text-blue-500 transition-colors">Impressum</a>
                        <a href="https://ternis-edv.de/datenschutz" target="_blank" class="text-sm text-zinc-500 hover:text-blue-500 transition-colors">Privacy Policy</a>
                        <div class="mt-4 text-zinc-400 text-xs">
                            &copy; {{ date('Y') }} DropHTML.de - A <a href="https://ternis-edv.de" target="_blank" class="hover:text-blue-500">ternis-edv.de</a> project
                        </div>
                    </nav>
                </div>
            </div>
        </footer>

        @fluxScripts
    </body>
</html>