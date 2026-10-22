<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ filled($title ?? null) ? $title.' - '.config('app.name', 'DropHTML') : config('app.name', 'DropHTML').' - Instant Static Sites & Media Cloud' }}</title>

    <meta name="description" content="Instant static site hosting and high-speed cloud static media hosting. Drag & drop HTML or ZIP, GitHub CI/CD, password protection, and custom domains with free automatic SSL.">

    {{-- Mark JS availability so scroll-reveal content stays visible when JS is disabled --}}
    <script>document.documentElement.classList.add('js');</script>

    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">

    {{-- Self-hosted Instrument Sans (brand typeface) --}}
    @fonts

    {{-- app.css (Tailwind + Flux) is required by the Livewire file-uploader;
         landing.css contains the custom nested CSS for everything on this page. --}}
    @vite(['resources/css/app.css', 'resources/css/landing.css', 'resources/js/app.js'])
    @fluxAppearance
</head>
<body class="lp-body">
    <div class="lp-page">
        @include('partials.landing.navigation')

        <main class="lp-main">
            @yield('content')
        </main>

        @include('partials.landing.footer')
    </div>

    @fluxScripts

    {{-- Scroll reveal: fade sections in once on viewport entry --}}
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const targets = document.querySelectorAll('.lp-reveal');

            if (!('IntersectionObserver' in window) || window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                targets.forEach((el) => el.classList.add('is-shown'));
                return;
            }

            const observer = new IntersectionObserver((entries) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('is-shown');
                        observer.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.12 });

            targets.forEach((el) => observer.observe(el));
        });
    </script>
</body>
</html>
