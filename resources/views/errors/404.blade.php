<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Page not found · DropHTML</title>
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    @fonts
    @vite(['resources/css/app.css'])
    @include('errors.partials.theme-script')
    <style>@include('errors.partials.wayfinder-styles')</style>
</head>
<body class="not-found-page">
    <header class="not-found-header">
        <a href="{{ route('home') }}" class="not-found-brand" aria-label="DropHTML home"><img src="/favicon.svg" alt="" width="28" height="28">drop<span>html</span></a>
        <div class="not-found-header-actions">
            <a href="{{ route('dashboard') }}" class="not-found-header-link">Dashboard @include('errors.partials.icon', ['name' => 'arrow'])</a>
            <button type="button" class="not-found-theme" id="site-theme-toggle" aria-label="Switch theme" hidden>
                <span class="not-found-sun">@include('errors.partials.icon', ['name' => 'sun'])</span>
                <span class="not-found-moon">@include('errors.partials.icon', ['name' => 'moon'])</span>
            </button>
        </div>
    </header>
    <main id="main-content" class="not-found-main">
        <div class="not-found-copy">
            <p class="not-found-eyebrow"><span></span>404 / PAGE NOT FOUND</p>
            <h1>That page is<br>off the <em>map.</em></h1>
            <p class="not-found-description">We couldn’t find a DropHTML page at this address. It may have moved, or the link might need a small correction.</p>
            <div class="not-found-address"><span>YOU WERE LOOKING FOR</span><code>{{ request()->getPathInfo() }}</code><span class="not-found-address-status">404 · Not found</span></div>
            <div class="not-found-actions">
                @auth
                    <a href="{{ route('dashboard') }}" class="not-found-primary">Back to your sites @include('errors.partials.icon', ['name' => 'arrow'])</a>
                @else
                    <a href="{{ route('home') }}" class="not-found-primary">Take me home @include('errors.partials.icon', ['name' => 'arrow'])</a>
                @endauth
                <a href="{{ route('docs.agents') }}" class="not-found-secondary">Read the docs @include('errors.partials.icon', ['name' => 'arrow'])</a>
            </div>
            <div class="not-found-hint">@include('errors.partials.icon', ['name' => 'turn'])<p>Followed a link? Check the spelling or ask the person who shared it for an updated address.</p></div>
        </div>
        <div class="not-found-art">
            @include('errors.partials.wayfinder', ['status' => 404])
        </div>
    </main>
    <nav class="not-found-recovery" aria-label="Find your next destination">
        <a href="{{ route('dashboard') }}"><span class="not-found-recovery-icon" aria-hidden="true">@include('errors.partials.icon', ['name' => 'grid'])</span><span><strong>Your sites</strong><small>Pick up where you left off.</small></span>@include('errors.partials.icon', ['name' => 'arrow'])</a>
        <a href="{{ route('dashboard.sites.upload') }}"><span class="not-found-recovery-icon" aria-hidden="true">@include('errors.partials.icon', ['name' => 'upload'])</span><span><strong>Publish something new</strong><small>An HTML file is all you need.</small></span>@include('errors.partials.icon', ['name' => 'arrow'])</a>
        <a href="{{ route('docs.agents') }}"><span class="not-found-recovery-icon" aria-hidden="true">@include('errors.partials.icon', ['name' => 'code'])</span><span><strong>Find an answer</strong><small>Guides for your next step.</small></span>@include('errors.partials.icon', ['name' => 'arrow'])</a>
    </nav>
    <footer class="not-found-footer"><span>A wrong turn. A fresh start.</span><a href="mailto:support@drophtml.de">Need a hand? Contact support @include('errors.partials.icon', ['name' => 'arrow'])</a></footer>
</body>
</html>
