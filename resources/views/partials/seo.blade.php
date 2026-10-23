@php
    // Shared SEO & social-sharing meta.
    // Override per page with $title / $description / $ogImage / $canonical.
    $seoTitle = filled($title ?? null)
        ? $title.' - '.config('app.name', 'DropHTML')
        : config('app.name', 'DropHTML').' - Instant Static Sites & Media Cloud';
    $seoDescription = filled($description ?? null)
        ? $description
        : 'Instant static site hosting and high-speed cloud static media hosting. Drag & drop HTML or ZIP, GitHub CI/CD, password protection, and custom domains with free automatic SSL.';
    $seoImage = $ogImage ?? '/og.png';
    $seoCanonical = filled($canonical ?? null) ? $canonical : url()->current();
@endphp

<link rel="canonical" href="{{ $seoCanonical }}">

<meta property="og:type" content="website">
<meta property="og:site_name" content="{{ config('app.name', 'DropHTML') }}">
<meta property="og:title" content="{{ $seoTitle }}">
<meta property="og:description" content="{{ $seoDescription }}">
<meta property="og:url" content="{{ $seoCanonical }}">
<meta property="og:image" content="{{ preg_match('#^https?://#', $seoImage) ? $seoImage : url($seoImage) }}">
@if ($seoImage === '/og.png')
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
@endif
<meta property="og:image:alt" content="{{ config('app.name', 'DropHTML') }} - instant static site hosting">

<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $seoTitle }}">
<meta name="twitter:description" content="{{ $seoDescription }}">
<meta name="twitter:image" content="{{ preg_match('#^https?://#', $seoImage) ? $seoImage : url($seoImage) }}">
