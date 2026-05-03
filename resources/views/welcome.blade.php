<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ __('Welcome') }} - {{ config('app.name', 'Laravel') }}</title>

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        @fonts

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @fluxAppearance
    </head>
    <body class="min-h-screen bg-white antialiased dark:bg-linear-to-b dark:from-neutral-950 dark:to-neutral-900 text-zinc-900 dark:text-zinc-100 flex p-6 lg:p-8 items-center lg:justify-center flex-col transition-colors duration-300">
        <header class="w-full lg:max-w-4xl max-w-[335px] text-sm mb-12 flex justify-between items-center">
            <div class="flex items-center gap-2">
                <flux:icon.cloud-arrow-up class="w-8 h-8 text-blue-600" />
                <div class="font-bold text-2xl tracking-tight">DropHTML<span class="text-blue-500">.de</span></div>
            </div>
            
            <div class="flex items-center gap-4">
                @if (Route::has('login'))
                    <nav class="flex items-center gap-4">
                        @auth
                            <flux:button :href="route('dashboard')" variant="ghost" size="sm">Dashboard</flux:button>
                        @else
                            <flux:button :href="route('login')" variant="ghost" size="sm">Log in</flux:button>
                            <flux:button :href="route('register')" variant="primary" size="sm">Sign up</flux:button>
                        @endauth
                    </nav>
                @endif
                <flux:separator vertical class="h-4 mx-2" />
                <flux:button href="https://github.com/ternis-edv/drophtml.de" target="_blank" variant="ghost" size="sm" icon="github" />
            </div>
        </header>

        <main class="w-full lg:max-w-4xl max-w-[335px] flex flex-col gap-12">
            <div class="text-center space-y-4">
                <h1 class="text-5xl lg:text-6xl font-black tracking-tight leading-tight">
                    Instant <span class="text-blue-600 dark:text-blue-500">HTML</span> Hosting
                </h1>
                <p class="text-xl text-zinc-500 dark:text-zinc-400 max-w-2xl mx-auto">
                    Drag and drop your HTML files and get them published on a global subdomain instantly. 
                    No registration required for temporary drops.
                </p>
            </div>

            <div class="bg-white dark:bg-zinc-900/50 p-10 rounded-2xl shadow-xl border border-zinc-200 dark:border-zinc-800 backdrop-blur-sm">
                <livewire:file-uploader />
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mt-4">
                <flux:card class="p-6 space-y-3 hover:scale-[1.02] transition-transform duration-300">
                    <div class="p-3 bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 rounded-xl w-fit">
                        <flux:icon.bolt class="w-6 h-6" />
                    </div>
                    <h3 class="font-bold text-lg">Instant Publishing</h3>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">No registration required. Just drop your file and your website is live in seconds.</p>
                </flux:card>

                <flux:card class="p-6 space-y-3 hover:scale-[1.02] transition-transform duration-300">
                    <div class="p-3 bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400 rounded-xl w-fit">
                        <flux:icon.archive-box class="w-6 h-6" />
                    </div>
                    <h3 class="font-bold text-lg">ZIP & Folder Support</h3>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">Upload entire websites as a ZIP file. We'll extract and serve it for you automatically.</p>
                </flux:card>

                <flux:card class="p-6 space-y-3 hover:scale-[1.02] transition-transform duration-300">
                    <div class="p-3 bg-purple-100 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400 rounded-xl w-fit">
                        <flux:icon.globe-alt class="w-6 h-6" />
                    </div>
                    <h3 class="font-bold text-lg">Custom Domains</h3>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">Register to keep your sites longer, get a unique dashboard, and link your own custom domains.</p>
                </flux:card>
            </div>
        </main>

        <footer class="mt-24 pb-12 w-full max-w-4xl border-t border-zinc-200 dark:border-zinc-800 pt-8">
            <div class="flex flex-col md:flex-row justify-between items-center gap-6">
                <div class="flex flex-wrap justify-center md:justify-start gap-x-8 gap-y-4">
                    <a href="https://ternis-edv.de" target="_blank" class="text-zinc-400 hover:text-blue-500 transition-colors text-sm font-medium">ternis-edv.de</a>
                    <a href="https://xpsystems.de" target="_blank" class="text-zinc-400 hover:text-blue-500 transition-colors text-sm font-medium">xpsystems.de</a>
                    <a href="https://europehost.eu" target="_blank" class="text-zinc-400 hover:text-blue-500 transition-colors text-sm font-medium">europehost.eu</a>
                    <a href="https://dnbx.de" target="_blank" class="text-zinc-400 hover:text-blue-500 transition-colors text-sm font-medium">dnbx.de</a>
                </div>
                <div class="text-zinc-500 text-sm">
                    &copy; {{ date('Y') }} DropHTML.de
                </div>
            </div>
        </footer>
    </body>
</html>
