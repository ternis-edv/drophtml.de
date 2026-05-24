@extends('layouts.landing')

@section('content')
<div class="pt-32 pb-24 max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="glass-panel rounded-3xl p-8 md:p-12 border border-zinc-200 dark:border-zinc-800">
        <h1 class="text-3xl md:text-4xl font-bold mb-8 text-zinc-900 dark:text-white capitalize">{{ __('legal.' . $page . '.title') }}</h1>
        
        <div class="prose dark:prose-invert max-w-none prose-zinc">
            {!! __('legal.' . $page . '.content') !!}
        </div>
    </div>
</div>
@endsection