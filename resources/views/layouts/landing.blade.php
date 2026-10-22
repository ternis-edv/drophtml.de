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
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800,900&display=swap" rel="stylesheet" />

    <!-- Scripts / Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @fluxAppearance
    
    <style>
        @keyframes ambient-glow {
            0%, 100% { transform: translate(0, 0) scale(1); opacity: 0.4; }
            50% { transform: translate(25px, -25px) scale(1.1); opacity: 0.65; }
        }
        @keyframes float-gentle {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-8px); }
        }
        
        .animate-ambient { animation: ambient-glow 10s ease-in-out infinite alternate; }
        .animate-ambient-delayed { animation: ambient-glow 12s ease-in-out 4s infinite alternate; }
        .animate-float { animation: float-gentle 4s ease-in-out infinite; }
        
        .glass-panel {
            background: rgba(255, 255, 255, 0.78);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(0, 0, 0, 0.07);
        }
        .dark .glass-panel {
            background: rgba(12, 12, 16, 0.72);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.08);
        }

        .glass-card {
            background: rgba(255, 255, 255, 0.88);
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
            border: 1px solid rgba(0, 0, 0, 0.08);
        }
        .dark .glass-card {
            background: rgba(16, 16, 22, 0.8);
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
            border: 1px solid rgba(255, 255, 255, 0.08);
        }

        .bg-grid-mesh {
            background-image: radial-gradient(rgba(99, 102, 241, 0.08) 1px, transparent 1px);
            background-size: 32px 32px;
        }
        .dark .bg-grid-mesh {
            background-image: radial-gradient(rgba(255, 255, 255, 0.05) 1px, transparent 1px);
            background-size: 32px 32px;
        }
        
        html { scroll-behavior: smooth; }
        body { overflow-x: hidden; }

        /* Scroll reveal (Phase 5) — sections fade/slide in once on viewport entry */
        .js .reveal {
            opacity: 0;
            transform: translateY(28px);
            transition: opacity 700ms cubic-bezier(0.16, 1, 0.3, 1), transform 700ms cubic-bezier(0.16, 1, 0.3, 1);
            will-change: opacity, transform;
        }
        .js .reveal.is-shown {
            opacity: 1;
            transform: translateY(0);
            will-change: auto;
        }
        @media (prefers-reduced-motion: reduce) {
            .js .reveal {
                opacity: 1;
                transform: none;
                transition: none;
            }
        }
    </style>
</head>
<body class="antialiased selection:bg-indigo-500 selection:text-white bg-zinc-50 dark:bg-[#070709] text-zinc-900 dark:text-zinc-100 transition-colors duration-300">
    <div class="relative min-h-screen flex flex-col overflow-hidden bg-grid-mesh">
        <!-- Ambient Background Glow Mesh -->
        <div class="absolute top-[-12%] left-[-10%] w-[55%] h-[55%] rounded-full bg-gradient-to-tr from-indigo-500/20 via-purple-500/20 to-pink-500/10 dark:from-indigo-600/25 dark:via-purple-600/20 dark:to-cyan-500/15 blur-[140px] animate-ambient pointer-events-none"></div>
        <div class="absolute top-[35%] right-[-15%] w-[50%] h-[50%] rounded-full bg-gradient-to-br from-purple-500/15 via-indigo-500/15 to-emerald-500/10 dark:from-purple-600/20 dark:via-indigo-600/20 dark:to-emerald-600/15 blur-[140px] animate-ambient-delayed pointer-events-none"></div>
        <div class="absolute bottom-[-10%] left-[20%] w-[45%] h-[45%] rounded-full bg-indigo-500/10 dark:bg-indigo-700/15 blur-[130px] pointer-events-none"></div>

        @include('partials.landing.navigation')
        
        <main class="flex-grow relative z-10">
            @yield('content')
        </main>

        @include('partials.landing.footer')
    </div>
    @fluxScripts
</body>
</html>