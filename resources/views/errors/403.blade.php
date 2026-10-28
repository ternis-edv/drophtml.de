@extends('layouts.landing')

@section('content')
<div class="pt-40 pb-24 flex flex-col items-center justify-center min-h-[70vh] px-4 relative z-10">
    <div class="panel rise-in p-10 md:p-16 max-w-2xl w-full mx-auto shadow-xl text-center relative overflow-hidden backdrop-blur-sm">
        <div class="absolute top-[-50%] left-[-10%] w-[60%] h-[60%] rounded-full bg-accent/15 dark:bg-accent/10 blur-[80px] pointer-events-none" aria-hidden="true"></div>
        
        <div class="relative z-10 flex flex-col items-center">
            <div class="w-20 h-20 bg-accent/10 text-accent rounded-2xl flex items-center justify-center mb-8 ring-1 ring-accent/25">
                <flux:icon.shield-exclamation class="w-10 h-10" />
            </div>
            <h1 class="text-6xl font-bold tracking-tight text-transparent bg-clip-text bg-gradient-to-r from-zinc-800 to-zinc-500 dark:from-white dark:to-zinc-500 mb-4">{{ $exception->getStatusCode() }}</h1>
            <h2 class="text-2xl font-bold text-zinc-900 dark:text-white mb-6">Access Denied</h2>
            <p class="text-zinc-600 dark:text-zinc-400 max-w-md mx-auto mb-10 leading-relaxed">{{ $exception->getMessage() ?: 'You do not have permission to view this page or perform this action.' }}</p>
            
            <div class="flex flex-col sm:flex-row items-center gap-4 justify-center">
                <flux:button :href="route('home')" variant="primary" class="rounded-full px-8 shadow-lg shadow-accent/25">Go back home</flux:button>
                <flux:button href="mailto:support@drophtml.de" variant="ghost" class="rounded-full px-8 text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white">Contact Support</flux:button>
            </div>
        </div>
    </div>
</div>
@endsection
