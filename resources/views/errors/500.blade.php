@extends('layouts.landing')

@section('content')
<div class="pt-40 pb-24 flex flex-col items-center justify-center min-h-[70vh] text-center px-4">
    <flux:icon.server-stack class="w-20 h-20 text-red-500 mb-8" />
    <h1 class="text-6xl font-extrabold text-zinc-900 dark:text-white mb-4">500</h1>
    <h2 class="text-2xl font-bold text-zinc-700 dark:text-zinc-300 mb-6">Server Error</h2>
    <p class="text-zinc-500 dark:text-zinc-400 max-w-md mb-10">Whoops, something went wrong on our servers. We've been notified and are looking into it.</p>
    <flux:button :href="route('home')" variant="primary" class="rounded-full px-8">Go back home</flux:button>
</div>
@endsection