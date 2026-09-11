<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ filled($title ?? null) ? $title.' - '.config('app.name', 'DropHTML') : config('app.name', 'DropHTML').' - Instant Static Sites & Media Cloud' }}</title>

    <meta name="description" content="Instant static site hosting and S3/R2-powered static media hosting for developers. Drag & drop HTML or ZIP, GitHub CI/CD, password protection, and custom domains with free automatic SSL.">

    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800,900&display=swap" rel="stylesheet" />

    <!-- Scripts / Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @fluxAppearance
    
    @fluxStyles
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
            background: rgba(255, 255, 255, 0.75);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(0, 0, 0, 0.06);
        }
        .dark .glass-panel {
            background: rgba(14, 14, 18, 0.7);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.08);
        }

        .glass-card {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(0, 0, 0, 0.08);
        }
        .dark .glass-card {
            background: rgba(18, 18, 23, 0.75);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.08);
        }
        
        html { scroll-behavior: smooth; }
        body { overflow-x: hidden; }
    </style>
</head>
<body class="antialiased selection:bg-indigo-500 selection:text-white bg-zinc-50 dark:bg-[#070709] text-zinc-900 dark:text-zinc-100 transition-colors duration-300">
    <div class="relative min-h-screen flex flex-col overflow-hidden">
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