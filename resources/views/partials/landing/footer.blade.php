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
            {{-- Brand --}}
            <div class="lp-footer__brand">
                <a href="{{ route('home') }}">
                    <x-brand-lockup />
                </a>
                <p>
                    From a local file to a live link in seconds.
                    Static site hosting for developers, creators, and teams.
                </p>
            </div>

            {{-- Product navigation --}}
            <nav class="lp-footer__col" aria-labelledby="footer-product-heading">
                <h2 id="footer-product-heading">Product</h2>
                <ul>
                    <li><a href="#upload">Upload &amp; Deploy</a></li>
                    <li><a href="#features">Features</a></li>
                    <li><a href="{{ url('/docs/agents') }}">DropHTML for Agents</a></li>
                    <li><a href="{{ url('/docs/api') }}">API Documentation</a></li>
                    <li><a href="{{ route('branding') }}">Branding</a></li>
                    <li><a href="https://github.com/ternis-edv/drophtml.de" target="_blank" rel="noopener">GitHub Repository</a></li>
                </ul>
            </nav>

            {{-- Legal navigation --}}
            <nav class="lp-footer__col" aria-labelledby="footer-legal-heading">
                <h2 id="footer-legal-heading">Legal</h2>
                <ul>
                    <li><a href="{{ route('legal.show', 'privacy') }}">Privacy Policy</a></li>
                    <li><a href="{{ route('legal.show', 'terms') }}">Terms of Service</a></li>
                    <li><a href="{{ route('legal.show', 'imprint') }}">Imprint</a></li>
                </ul>
            </nav>

            {{-- Developer --}}
            <div class="lp-footer__col lp-footer__dev">
                <h2>Behind DropHTML</h2>
                <p>Developed &amp; operated by</p>
                <a href="https://ternis.dev" target="_blank" rel="noopener" class="lp-footer__wordmark" aria-label="ternis.dev">
                    <img src="{{ asset('assets/branding/ternis-dev-wordmark-light.svg') }}" alt="" aria-hidden="true" class="lp-wordmark lp-wordmark--light" width="115" height="38" />
                    <img src="{{ asset('assets/branding/ternis-dev-wordmark-dark.svg') }}" alt="" aria-hidden="true" class="lp-wordmark lp-wordmark--dark" width="115" height="38" />
                </a>
                <p class="lp-footer__imprint">
                    Developer imprint:
                    @foreach ($imprintLinks as $linkLocale => $link)
                        <a href="{{ $link }}" target="_blank" rel="noopener">{{ strtoupper($linkLocale) }}</a>@if (!$loop->last) &middot; @endif
                    @endforeach
                </p>
            </div>
        </div>

        <div class="lp-footer__bottom">
            <p>&copy; {{ date('Y') }} DropHTML.de — All rights reserved.</p>
            <a href="https://ternis.net" target="_blank" rel="noopener" class="lp-footer__network">
                <span>Part of</span> ternis.net <span aria-hidden="true">↗</span>
            </a>
        </div>
    </div>
</footer>
