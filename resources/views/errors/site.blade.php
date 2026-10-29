@php
    [$eyebrow, $title, $description] = match($status) {
        403 => ['SITE UNAVAILABLE', 'This door is closed for now.', 'This website is not available right now. Please check back later or contact the site owner.'],
        500 => ['SOMETHING WENT WRONG', 'An unexpected detour.', 'We could not load this page right now. Please try again in a moment.'],
        503 => ['TEMPORARILY UNAVAILABLE', 'A little pause. We’ll be back.', 'This website is temporarily unavailable. Please try again later.'],
        default => $site
            ? ['PAGE NOT FOUND', 'A wrong turn.\nA fresh start.', 'The page you’re looking for may have moved, or the link might be incomplete. Let’s get you back to familiar ground.']
            : ['SITE NOT FOUND', 'No destination\nat this address.', 'This website may have expired, been removed, or moved. Check the address with the person who shared it.'],
    };
    $siteName = $site?->original_name ?: ($site?->slug ?? 'Hosted website');
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta name="color-scheme" content="light dark">
    <title>{{ $status }} · {{ $siteName }}</title>
    <style>@include('errors.site-styles')</style>
</head>
<body>
    <div class="site-error-shell">
        <header>
            @if($site)
                <a class="site-identity" href="{{ $baseUrl }}" aria-label="{{ $siteName }} home"><span class="site-mark" aria-hidden="true">↗</span><span>{{ $siteName }}</span></a>
            @else
                <span class="site-identity"><span class="site-mark" aria-hidden="true">↗</span><span>{{ $siteName }}</span></span>
            @endif
            <span class="header-note">A small detour</span>
        </header>
        <main id="main">
            <section class="error-content" aria-labelledby="error-title">
                <p class="eyebrow"><span aria-hidden="true"></span>{{ $status }} / {{ $eyebrow }}</p>
                <h1 id="error-title">@foreach(explode('\\n', $title) as $line){{ $line }}@unless($loop->last)<br>@endunless @endforeach</h1>
                <p class="description">{{ $description }}</p>
                <div class="requested-path"><span>Requested address</span><code dir="ltr">{{ $requestedPath }}</code></div>
                @if($site && $status === 404)
                    <a class="primary" href="{{ $baseUrl }}">Back to site home <span aria-hidden="true">↗</span></a>
                    <p class="action-note">Your next stop: {{ $siteName }}</p>
                @elseif(in_array($status, [500, 503], true))
                    <a class="primary" href="{{ request()->url() }}">Try again <span aria-hidden="true">↻</span></a>
                @else
                    <p class="action-note">Double-check the spelling and try the link again.</p>
                @endif
            </section>
            <div class="wayfinder" aria-hidden="true">
                <div class="map-grid"></div>
                <span class="map-coordinate">YOU ARE HERE</span>
                <div class="map-number">{{ $status }}</div>
                <svg class="map-route" viewBox="0 0 460 440" fill="none">
                    <path class="route-shadow" d="M-20 345H100Q125 345 125 320V267Q125 242 150 242H295Q320 242 320 217V135Q320 110 345 110H480"/>
                    <path class="route-line" d="M-20 345H100Q125 345 125 320V267Q125 242 150 242H295Q320 242 320 217V135Q320 110 345 110H480"/>
                    <circle cx="125" cy="295" r="18" class="map-pin-halo"/><circle cx="125" cy="295" r="6" class="map-pin"/>
                    <rect x="300" y="90" width="40" height="40" rx="12" class="destination"/>
                    <path d="m313 110 5 5 9-10" class="destination-check"/>
                </svg>
                <span class="map-tag">{{ $status === 404 ? 'Destination not found' : 'Route interrupted' }}<span>There’s another way forward.</span></span>
                <span class="map-caption">RECALCULATING THE ROUTE <span>↗</span></span>
            </div>
        </main>
        <aside class="help-note"><span class="help-icon" aria-hidden="true">↳</span><p><strong>{{ $status === 404 ? 'Followed a broken link?' : 'Still having trouble?' }}</strong> {{ $status === 404 ? 'Let the site owner know so they can point it in the right direction.' : 'Please let the site owner know if this keeps happening.' }}</p></aside>
        <footer><span>{{ $site ? 'You’re still on '.$siteName : 'Every link needs a destination.' }}</span><a href="{{ route('home') }}">Hosted on <strong>drophtml</strong> <span aria-hidden="true">↗</span></a></footer>
    </div>
</body>
</html>
