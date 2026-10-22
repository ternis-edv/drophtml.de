<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ filled($title ?? null) ? $title.' - '.config('app.name', 'DropHTML') : config('app.name', 'DropHTML').' - Instant Static Sites & Media Cloud' }}</title>

    <meta name="description" content="Instant static site hosting and high-speed cloud static media hosting for developers. Drag & drop HTML or ZIP, GitHub CI/CD, password protection, and custom domains with free automatic SSL.">

    {{-- Mark JS availability so scroll-reveal content stays visible when JS is disabled --}}
    <script>document.documentElement.classList.add('js');</script>

    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">

    {{-- Self-hosted Instrument Sans (brand typeface) --}}
    @fonts

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @fluxAppearance

    <style>
        html { scroll-behavior: smooth; }
        body { overflow-x: hidden; }
    </style>
</head>
<body class="antialiased selection:bg-accent selection:text-white bg-white text-zinc-900 dark:bg-zinc-950 dark:text-zinc-100 transition-colors duration-300">
    <div class="relative min-h-screen flex flex-col">
        @include('partials.landing.navigation')

        <main class="flex-grow relative z-10">
            @yield('content')
        </main>

        @include('partials.landing.footer')
    </div>
    @fluxScripts
</body>
</html>
