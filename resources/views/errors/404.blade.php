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
    @fluxAppearance
</head>
<body class="not-found-page">
    <header class="not-found-header">
        <a href="{{ route('home') }}" class="not-found-brand" aria-label="DropHTML home"><img src="/favicon.svg" alt="" width="28" height="28">drop<span>html</span></a>
        <a href="{{ route('dashboard') }}" class="not-found-header-link">Dashboard <span aria-hidden="true">↗</span></a>
    </header>
    <main id="main-content" class="not-found-main">
        <div class="not-found-copy">
            <p class="not-found-eyebrow"><span></span>404 / PAGE NOT FOUND</p>
            <h1>That page is<br>off the <em>map.</em></h1>
            <p class="not-found-description">We couldn’t find a DropHTML page at this address. It may have moved, or the link might need a small correction.</p>
            <div class="not-found-address"><span>YOU WERE LOOKING FOR</span><code>{{ request()->getPathInfo() }}</code><span class="not-found-address-status">404 · Not found</span></div>
            <div class="not-found-actions">
                @auth
                    <a href="{{ route('dashboard') }}" class="not-found-primary">Back to your sites <span aria-hidden="true">→</span></a>
                @else
                    <a href="{{ route('home') }}" class="not-found-primary">Take me home <span aria-hidden="true">→</span></a>
                @endauth
                <a href="{{ route('docs.agents') }}" class="not-found-secondary">Read the docs <span aria-hidden="true">↗</span></a>
            </div>
            <div class="not-found-hint"><span aria-hidden="true">↳</span><p>Followed a link? Check the spelling or ask the person who shared it for an updated address.</p></div>
        </div>
        <div class="not-found-art" aria-hidden="true">
            <div class="not-found-orbit"></div>
            <div class="not-found-window">
                <div class="not-found-window-bar"><div><i></i><i></i><i></i></div><span>route / not / found</span><span>↗</span></div>
                <div class="not-found-window-body"><span class="not-found-code-label">HTTP RESPONSE</span><div class="not-found-number">4<span>0</span>4</div><div class="not-found-code"><span>const</span> destination = <em>undefined</em>;<br><span>// Let’s find your way back.</span></div></div>
                <div class="not-found-window-footer"><span><i></i> No page at this address</span><span>↵ home</span></div>
            </div>
            <div class="not-found-floating">&lt;/&gt;<span>Even great sites have dead ends.</span></div>
        </div>
    </main>
    <nav class="not-found-recovery" aria-label="Find your next destination">
        <a href="{{ route('dashboard') }}"><span class="not-found-recovery-icon" aria-hidden="true">▦</span><span><strong>Your sites</strong><small>Pick up where you left off.</small></span><span aria-hidden="true">↗</span></a>
        <a href="{{ route('dashboard.sites.upload') }}"><span class="not-found-recovery-icon" aria-hidden="true">↑</span><span><strong>Publish something new</strong><small>An HTML file is all you need.</small></span><span aria-hidden="true">↗</span></a>
        <a href="{{ route('docs.agents') }}"><span class="not-found-recovery-icon" aria-hidden="true">&lt;/&gt;</span><span><strong>Find an answer</strong><small>Guides for your next step.</small></span><span aria-hidden="true">↗</span></a>
    </nav>
    <footer class="not-found-footer"><span>A wrong turn. A fresh start.</span><a href="mailto:support@drophtml.de">Need a hand? Contact support <span aria-hidden="true">↗</span></a></footer>
    @fluxScripts
</body>
</html>
