<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ filled($title ?? null) ? $title.' - '.config('app.name', 'DropHTML') : config('app.name', 'DropHTML').' - Ship HTML in seconds' }}</title>

    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet" />

    <!-- Scripts / Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @fluxAppearance
    
    <!-- Alpine Intersect -->
    <script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/intersect@3.x.x/dist/cdn.min.js"></script>
    
    @fluxStyles
    <style>
        /* Custom Animations */
        @keyframes float {
            0%, 100% { transform: translateY(0) scale(1); }
            50% { transform: translateY(-20px) scale(1.05); }
        }
        .animate-float { animation: float 6s ease-in-out infinite; }
        .animate-float-delayed { animation: float 6s ease-in-out 3s infinite; }
        
        .glass-panel {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(0, 0, 0, 0.05);
        }
        .dark .glass-panel {
            background: rgba(24, 24, 27, 0.65);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        body {
            overflow-x: hidden;
        }
        
        /* Smooth scrolling */
        html { scroll-behavior: smooth; }
    </style>
</head>
<body class="antialiased selection:bg-indigo-500 selection:text-white bg-zinc-50 dark:bg-[#09090b] text-zinc-900 dark:text-zinc-100 transition-colors duration-300">
    <div class="relative min-h-screen flex flex-col overflow-hidden">
        <!-- Ambient Background Glows -->
        <div class="absolute top-[-10%] left-[-10%] w-[40%] h-[40%] rounded-full bg-indigo-400/30 dark:bg-indigo-600/20 blur-[120px] animate-float pointer-events-none"></div>
        <div class="absolute bottom-[-10%] right-[-10%] w-[40%] h-[40%] rounded-full bg-purple-400/30 dark:bg-purple-600/20 blur-[120px] animate-float-delayed pointer-events-none"></div>

        @include('partials.landing.navigation')
        
        <main class="flex-grow relative z-10">
            @yield('content')
        </main>

        @include('partials.landing.footer')
    </div>
    @fluxScripts
</body>
</html>