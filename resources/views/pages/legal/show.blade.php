@extends('layouts.landing')

@section('content')
<div class="legal-page lp-container">
    <header class="legal-header">
        <p class="brand-eyebrow">THE DETAILS / DROPHTML</p>
        <h1>{{ __('legal.' . $page . '.title') }}</h1>
        <p>The policies and people behind your live links.</p>
    </header>
    <div class="legal-layout">
        <nav class="legal-nav" aria-label="Legal documents">
            <span>ON THE RECORD</span>
            @foreach (['privacy', 'terms', 'imprint'] as $document)
                <a href="{{ route('legal.show', $document) }}" @if ($page === $document) aria-current="page" @endif>{{ __('legal.' . $document . '.title') }} <span aria-hidden="true">↗</span></a>
            @endforeach
            <a href="{{ route('home') }}">Back to DropHTML <span aria-hidden="true">←</span></a>
        </nav>
        <article class="legal-content" aria-label="{{ __('legal.' . $page . '.title') }}">
            {!! __('legal.' . $page . '.content') !!}
        </article>
    </div>
</div>
@endsection
