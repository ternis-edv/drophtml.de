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
        @keyframes ambient-glow {
            0%, 100% { transform: translate(0, 0) scale(1); opacity: 0.55; }
            50% { transform: translate(24px, -24px) scale(1.08); opacity: 0.8; }
        }
        @keyframes float-gentle {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-8px); }
        }

        .animate-ambient { animation: ambient-glow 12s ease-in-out infinite alternate; }
        .animate-ambient-delayed { animation: ambient-glow 15s ease-in-out 5s infinite alternate; }
        .animate-float { animation: float-gentle 5s ease-in-out infinite; }

        .bg-grid-mesh {
            background-image: radial-gradient(rgb(79 70 229 / 0.06) 1px, transparent 1px);
            background-size: 32px 32px;
        }
        .dark .bg-grid-mesh {
            background-image: radial-gradient(rgb(165 180 252 / 0.05) 1px, transparent 1px);
            background-size: 32px 32px;
        }

        html { scroll-behavior: smooth; }
        body { overflow-x: hidden; }
    </style>
</head>
<body class="antialiased selection:bg-accent selection:text-white bg-white text-zinc-900 dark:bg-zinc-950 dark:text-zinc-100 transition-colors duration-300">
    <div class="relative min-h-screen flex flex-col overflow-hidden bg-grid-mesh">
        {{-- Restrained ambient wash — brand indigo only, no rainbow mesh --}}
        <div class="absolute top-[-14%] left-[-8%] w-[50%] h-[50%] rounded-full bg-brand-600/12 dark:bg-brand-500/15 blur-[140px] animate-ambient pointer-events-none"></div>
        <div class="absolute top-[38%] right-[-12%] w-[42%] h-[42%] rounded-full bg-brand-400/10 dark:bg-brand-300/10 blur-[140px] animate-ambient-delayed pointer-events-none"></div>
        <div class="absolute bottom-[-10%] left-[22%] w-[40%] h-[40%] rounded-full bg-brand-600/8 dark:bg-brand-700/15 blur-[130px] pointer-events-none"></div>

        @include('partials.landing.navigation')

        <main class="flex-grow relative z-10">
            @yield('content')
        </main>

        @include('partials.landing.footer')
    </div>
    @fluxScripts
</body>
</html>
