<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Protected Site - {{ $site->original_name ?: 'DropHTML' }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet" />

    <!-- Scripts / Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @fluxStyles

    <style>
        .glass-panel {
            background: rgba(24, 24, 27, 0.75);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        @keyframes float {
            0%, 100% { transform: translateY(0) scale(1); }
            50% { transform: translateY(-15px) scale(1.03); }
        }
        .animate-float { animation: float 6s ease-in-out infinite; }
    </style>
</head>
<body class="min-h-screen bg-zinc-950 text-zinc-100 flex items-center justify-center p-4 selection:bg-indigo-500 selection:text-white relative overflow-hidden">
    <!-- Ambient Background Glow -->
    <div class="absolute top-1/4 left-1/4 w-96 h-96 rounded-full bg-indigo-600/20 blur-[120px] animate-float pointer-events-none"></div>
    <div class="absolute bottom-1/4 right-1/4 w-96 h-96 rounded-full bg-purple-600/20 blur-[120px] animate-float pointer-events-none" style="animation-delay: -3s;"></div>

    <div class="w-full max-w-md relative z-10">
        <div class="glass-panel rounded-2xl p-8 shadow-2xl">
            <div class="flex flex-col items-center text-center mb-6">
                <div class="w-14 h-14 rounded-2xl bg-indigo-500/10 text-indigo-400 border border-indigo-500/20 flex items-center justify-center mb-4">
                    <flux:icon.lock-closed class="w-7 h-7" />
                </div>
                <h1 class="text-2xl font-bold text-white tracking-tight">Protected Site</h1>
                <p class="text-sm text-zinc-400 mt-1">
                    This site is password-protected by its author. Please enter the password to view the content.
                </p>
            </div>

            @if(session('error') || $errors->has('password'))
                <div class="mb-6 p-3 bg-red-500/10 border border-red-500/20 text-red-400 text-sm rounded-lg flex items-center gap-2">
                    <flux:icon.exclamation-circle class="w-4 h-4 shrink-0" />
                    <span>{{ session('error') ?? $errors->first('password') }}</span>
                </div>
            @endif

            <form method="POST" action="{{ $unlockAction }}" class="space-y-5">
                @csrf
                <input type="hidden" name="redirect_to" value="{{ $redirectTo ?? request()->fullUrl() }}">

                <div>
                    <label for="password" class="block text-xs font-medium text-zinc-300 mb-1.5 uppercase tracking-wider">Site Password</label>
                    <div class="relative">
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            required 
                            autofocus 
                            placeholder="Enter password..."
                            class="w-full px-4 py-2.5 bg-zinc-900/90 border border-zinc-700/80 rounded-lg text-white placeholder-zinc-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all font-mono text-sm"
                        >
                    </div>
                </div>

                <button 
                    type="submit" 
                    class="w-full py-2.5 px-4 bg-indigo-600 hover:bg-indigo-500 active:bg-indigo-700 text-white font-medium text-sm rounded-lg shadow-lg shadow-indigo-600/20 transition-all flex items-center justify-center gap-2 cursor-pointer"
                >
                    <flux:icon.key class="w-4 h-4" />
                    <span>Unlock Site</span>
                </button>
            </form>

            <div class="mt-8 pt-6 border-t border-zinc-800/80 text-center">
                <a href="{{ config('app.url') }}" class="text-xs text-zinc-500 hover:text-zinc-300 transition-colors inline-flex items-center gap-1.5">
                    <flux:icon.rocket-launch class="w-3.5 h-3.5" />
                    <span>Hosted on DropHTML.de</span>
                </a>
            </div>
        </div>
    </div>
</body>
</html>
