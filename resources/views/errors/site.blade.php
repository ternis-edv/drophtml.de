@php
    [$eyebrow, $title, $description] = match($status) {
        403 => ['SITE UNAVAILABLE', 'This site is taking a break.', 'This website is not available right now. Please check back later or contact the site owner.'],
        500 => ['SOMETHING WENT WRONG', 'A small detour. Not the destination.', 'We could not load this page right now. Please try again in a moment.'],
        503 => ['TEMPORARILY UNAVAILABLE', 'Back in a little while.', 'This website is temporarily unavailable. Please try again later.'],
        default => $site
            ? ['PAGE NOT FOUND', 'This page wandered off.', 'The link may have changed, or this page may no longer be here. The rest of the website is a good place to start.']
            : ['SITE NOT FOUND', 'There is no site at this address.', 'This website may have expired, been removed, or moved to a different address. Check the link with the person who shared it.'],
    };
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $status }} · {{ $site?->original_name ?: ($site?->slug ?? 'Website unavailable') }}</title>
    <style>@include('errors.site-styles')</style>
</head>
<body>
    <div class="site-error-shell">
        <header><span class="site-identity"><i aria-hidden="true"></i><span>{{ $site?->original_name ?: ($site?->slug ?? 'Hosted website') }}</span></span><span class="error-label">{{ $status }} / {{ $eyebrow }}</span></header>
        <main>
            <div class="error-symbol" aria-hidden="true"><span>{{ $status }}</span><svg viewBox="0 0 100 100" fill="none"><path d="M20 70 42 48l16 16 22-34"/><circle cx="80" cy="30" r="9"/><path d="m74 24 12 12m0-12L74 36"/></svg></div>
            <p class="eyebrow">{{ $site ? 'YOU ARE STILL ON '.$site->slug : 'A LINK WITHOUT A DESTINATION' }}</p>
            <h1>{{ $title }}</h1>
            <p class="description">{{ $description }}</p>
            <div class="requested-path"><span>REQUESTED PATH</span><code>{{ $requestedPath }}</code></div>
            @if($site && $status === 404)
                <a class="primary" href="{{ $baseUrl }}">Go to site home <span aria-hidden="true">↗</span></a>
            @elseif(in_array($status, [500, 503], true))
                <a class="primary" href="{{ request()->url() }}">Try again <span aria-hidden="true">↻</span></a>
            @endif
            <p class="owner-note">{{ $status === 404 ? 'If you followed a link from another page, let the site owner know it needs an update.' : 'If this continues, please contact the site owner.' }}</p>
        </main>
        <footer><span>{{ $site ? 'A missing page. The same website.' : 'Check the address and try again.' }}</span><a href="{{ route('home') }}">Hosted on <strong>drophtml</strong> <span aria-hidden="true">↗</span></a></footer>
    </div>
</body>
</html>
