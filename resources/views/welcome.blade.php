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
                        <flux:button href="https://github.com/ternis-edv/drophtml.de" target="_blank" variant="ghost" size="sm" icon="github" />
                    </div>
                </div>

                <div class="space-y-4">
                    <h4 class="font-bold uppercase text-xs tracking-widest text-zinc-400">Platform</h4>
                    <nav class="flex flex-col gap-2">
                        <a href="https://ternis-edv.de" target="_blank" class="text-sm text-zinc-500 hover:text-blue-500 transition-colors">ternis-edv.de</a>
                        <a href="https://xpsystems.de" target="_blank" class="text-sm text-zinc-500 hover:text-blue-500 transition-colors">xpsystems.de</a>
                        <a href="https://europehost.eu" target="_blank" class="text-sm text-zinc-500 hover:text-blue-500 transition-colors">europehost.eu</a>
                        <a href="https://dnbx.de" target="_blank" class="text-sm text-zinc-500 hover:text-blue-500 transition-colors">dnbx.de</a>
                    </nav>
                </div>

                <div class="space-y-4">
                    <h4 class="font-bold uppercase text-xs tracking-widest text-zinc-400">Legal</h4>
                    <nav class="flex flex-col gap-2">
                        <a href="#" class="text-sm text-zinc-500 hover:text-blue-500 transition-colors">Privacy Policy</a>
                        <a href="#" class="text-sm text-zinc-500 hover:text-blue-500 transition-colors">Terms of Service</a>
                        <div class="mt-4 text-zinc-400 text-xs">
                            &copy; {{ date('Y') }} DropHTML.de
                        </div>
                    </nav>
                </div>
            </div>
        </footer>

        @fluxScripts
    </body>
</html>