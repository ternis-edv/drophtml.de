@php
    $locale = app()->getLocale() === 'de' ? 'de' : 'en';
    $imprintLinks = [
        $locale => 'https://ternis.dev/' . $locale . '/legal/imprint?domain=drophtml.de',
        $locale === 'de' ? 'en' : 'de' => 'https://ternis.dev/' . ($locale === 'de' ? 'en' : 'de') . '/legal/imprint?domain=drophtml.de',
    ];
@endphp

<footer class="lp-footer">
    <div class="lp-container">
        <div class="lp-footer__grid">
            <!-- Brand -->
            <div class="lp-footer__brand">
                <a href="{{ route('home') }}">
                    <x-brand-lockup />
                </a>
                <p>
                    Instant static site hosting for developers, creators, and teams. Drag &amp; drop HTML or ZIP,
                    GitHub CI/CD, password protection, and custom domains with automatic SSL.
                </p>
            </div>

            <!-- Product -->
            <div class="lp-footer__col">
                <h4>Product</h4>
                <ul>
                    <li><a href="#upload">Upload &amp; Deploy</a></li>
                    <li><a href="#features">Features</a></li>
                    <li><a href="{{ url('/docs/api') }}">API Documentation</a></li>
                    <li><a href="https://github.com/ternis-edv/drophtml.de" target="_blank" rel="noopener">GitHub Repository</a></li>
                </ul>
            </div>

            <!-- Legal -->
            <div class="lp-footer__col">
                <h4>Legal</h4>
                <ul>
                    <li><a href="{{ route('legal.show', 'privacy') }}">Privacy Policy</a></li>
                    <li><a href="{{ route('legal.show', 'terms') }}">Terms of Service</a></li>
                    <li><a href="{{ route('legal.show', 'imprint') }}">Imprint</a></li>
                </ul>
            </div>

            <!-- Developer -->
            <div class="lp-footer__col lp-footer__dev">
                <h4>Developer</h4>
                <a href="https://ternis.dev" target="_blank" rel="noopener" class="lp-footer__wordmark">
                    <img src="{{ asset('assets/branding/ternis-dev-wordmark-light.svg') }}" alt="ternis.dev" class="lp-wordmark lp-wordmark--light" width="115" height="38" />
                    <img src="{{ asset('assets/branding/ternis-dev-wordmark-dark.svg') }}" alt="" aria-hidden="true" class="lp-wordmark lp-wordmark--dark" width="115" height="38" />
                </a>
                <p>Developed &amp; operated by <a href="https://ternis.dev" target="_blank" rel="noopener">ternis.dev</a></p>
                <p class="lp-footer__imprint">
                    Imprint:
                    @foreach ($imprintLinks as $linkLocale => $link)
                        <a href="{{ $link }}" target="_blank" rel="noopener">{{ strtoupper($linkLocale) }}</a>@if (!$loop->last) &middot; @endif
                    @endforeach
                </p>
            </div>
        </div>

        <div class="lp-footer__bottom">
            <p>&copy; {{ date('Y') }} DropHTML.de — All rights reserved.</p>
            <p>
                <a href="https://ternis.dev" target="_blank" rel="noopener">ternis.dev</a> network
            </p>
        </div>
    </div>
</footer>
