@extends('layouts.landing')

@section('content')
<div class="pt-40 pb-24 flex flex-col items-center justify-center min-h-[70vh] px-4 relative z-10">
    <div class="glass-panel rounded-3xl p-10 md:p-16 max-w-2xl w-full mx-auto shadow-2xl border border-zinc-200 dark:border-zinc-800 text-center relative overflow-hidden">
        <div class="absolute top-[-50%] left-[-10%] w-[60%] h-[60%] rounded-full bg-yellow-400/20 dark:bg-yellow-500/10 blur-[80px] pointer-events-none"></div>
        
        <div class="relative z-10 flex flex-col items-center">
            <div class="w-20 h-20 bg-yellow-100 dark:bg-yellow-500/20 text-yellow-600 dark:text-yellow-500 rounded-2xl flex items-center justify-center mb-8 ring-1 ring-yellow-500/30">
                <flux:icon.exclamation-triangle class="w-10 h-10" />
            </div>
            <h1 class="text-6xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-zinc-800 to-zinc-500 dark:from-white dark:to-zinc-500 mb-4">404</h1>
            <h2 class="text-2xl font-bold text-zinc-900 dark:text-white mb-6">Page Not Found</h2>
            <p class="text-zinc-600 dark:text-zinc-400 max-w-md mx-auto mb-10 leading-relaxed">We couldn't find the page or site you were looking for. It might have been deleted, expired, or the URL might be incorrect.</p>
            
            <div class="flex flex-col sm:flex-row items-center gap-4 justify-center">
                <flux:button :href="route('home')" variant="primary" class="rounded-full px-8 shadow-lg shadow-indigo-500/25">Go back home</flux:button>
                <flux:button href="mailto:support@drophtml.de" variant="ghost" class="rounded-full px-8 text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white">Contact Support</flux:button>
            </div>
        </div>
    </div>
</div>
@endsection