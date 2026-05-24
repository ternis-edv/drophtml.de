@extends('layouts.landing')

@section('content')
<div class="pt-40 pb-24 flex flex-col items-center justify-center min-h-[70vh] text-center px-4">
    <flux:icon.exclamation-triangle class="w-20 h-20 text-yellow-500 mb-8" />
    <h1 class="text-6xl font-extrabold text-zinc-900 dark:text-white mb-4">404</h1>
    <h2 class="text-2xl font-bold text-zinc-700 dark:text-zinc-300 mb-6">Page Not Found</h2>
    <p class="text-zinc-500 dark:text-zinc-400 max-w-md mb-10">We couldn't find the page or site you were looking for. It might have been deleted, expired, or the URL might be incorrect.</p>
    <flux:button :href="route('home')" variant="primary" class="rounded-full px-8">Go back home</flux:button>
</div>
@endsection