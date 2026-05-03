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
    <body class="bg-[#FDFDFC] dark:bg-[#0a0a0a] text-[#1b1b18] flex p-6 lg:p-8 items-center lg:justify-center min-h-screen flex-col">
        <header class="w-full lg:max-w-4xl max-w-[335px] text-sm mb-6 flex justify-between items-center">
            <div class="font-bold text-xl">DropHTML<span class="text-blue-500">.de</span></div>
            @if (Route::has('login'))
                <nav class="flex items-center justify-end gap-4">
                    @auth
                        <a href="{{ route('dashboard') }}" class="text-sm">Dashboard</a>
                    @else
                        <a href="{{ route('login') }}" class="text-sm">Log in</a>
                    @endauth
                </nav>
            @endif
        </header>

        <main class="w-full lg:max-w-4xl max-w-[335px] flex flex-col gap-8">
            <div class="text-center">
                <h1 class="text-4xl font-bold mb-4">Instant HTML Hosting</h1>
                <p class="text-gray-600 dark:text-gray-400">Drag and drop your HTML files and get them published on a subdomain instantly.</p>
            </div>

            <div class="bg-white dark:bg-[#161615] p-8 rounded-xl shadow-sm border dark:border-gray-800">
                <livewire:file-uploader />
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-8">
                <div class="p-4 border dark:border-gray-800 rounded-lg">
                    <h3 class="font-bold mb-2">🚀 Instant</h3>
                    <p class="text-sm text-gray-500">No registration required. Just drop and go.</p>
                </div>
                <div class="p-4 border dark:border-gray-800 rounded-lg">
                    <h3 class="font-bold mb-2">📁 Zip Support</h3>
                    <p class="text-sm text-gray-500">Upload entire websites as a ZIP file.</p>
                </div>
                <div class="p-4 border dark:border-gray-800 rounded-lg">
                    <h3 class="font-bold mb-2">🔗 Custom URLs</h3>
                    <p class="text-sm text-gray-500">Get a unique subdomain for every drop.</p>
                </div>
            </div>
        </main>

        <footer class="mt-auto pt-12 pb-8 text-gray-400 text-xs text-center">
            <div class="flex flex-wrap justify-center gap-4 mb-4">
                <a href="https://ternis-edv.de" target="_blank" class="hover:text-gray-600">ternis-edv.de</a>
                <a href="https://xpsystems.eu" target="_blank" class="hover:text-gray-600">xpsystems.eu</a>
                <a href="https://xpsystems.de" target="_blank" class="hover:text-gray-600">xpsystems.de</a>
                <a href="https://europehost.eu" target="_blank" class="hover:text-gray-600">europehost.eu</a>
                <a href="https://dnbx.de" target="_blank" class="hover:text-gray-600">dnbx.de</a>
                <a href="https://github.com/ternis-edv/drophtml.de" target="_blank" class="hover:text-gray-600">GitHub</a>
            </div>
            &copy; {{ date('Y') }} DropHTML.de - Drag, Drop, Done.
        </footer>
    </body>
</html>
