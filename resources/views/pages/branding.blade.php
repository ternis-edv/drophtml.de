@extends('layouts.landing')

@section('content')
<div class="brand-page lp-container">
    <header class="brand-intro">
        <p class="brand-eyebrow">THE DROPHTML IDENTITY</p>
        <h1>A small mark.<br>A world of possibilities.</h1>
        <p>Our drop bracket brings a file and a downward point together. Here’s everything you need to put DropHTML in the picture.</p>
        <a class="brand-download" href="{{ asset('brand/brand-kit.zip') }}" download>Download the brand kit <span aria-hidden="true">↓</span></a>
        <span class="brand-note">SVG logos, icons, wordmarks &amp; font license · ZIP</span>
    </header>

    @foreach (['logo' => ['Logos', 'The complete lockup. Our first choice wherever there’s room.'], 'wordmark' => ['Wordmarks', 'Our name, set in outlined Instrument Sans. No font installation needed.'], 'icon' => ['Icons', 'The drop bracket, on its own. For compact spaces and familiar faces.']] as $type => [$heading, $description])
        <section class="brand-section" aria-labelledby="{{ $type }}-heading">
            <div class="brand-section-heading"><h2 id="{{ $type }}-heading">{{ $heading }}</h2><p>{{ $description }}</p></div>
            <div class="brand-grid">
                @foreach (['light' => 'Light background', 'dark' => 'Dark background', 'mono' => 'Monochrome', 'white' => 'White'] as $variant => $label)
                    <article class="brand-card">
                        <div class="brand-preview {{ in_array($variant, ['dark', 'white']) ? 'brand-preview-dark' : 'brand-preview-light' }}">
                            <img src="{{ asset("brand/{$type}-{$variant}.svg") }}" alt="DropHTML {{ $type }}, {{ strtolower($label) }}" class="{{ $type === 'icon' ? 'brand-symbol' : 'brand-lockup' }}" loading="lazy">
                        </div>
                        <div class="brand-card-caption"><h3>{{ $label }}</h3><a href="{{ asset("brand/{$type}-{$variant}.svg") }}" download aria-label="Download {{ $label }} {{ $type }} SVG">SVG ↓</a></div>
                    </article>
                @endforeach
            </div>
        </section>
    @endforeach

    <section class="brand-section" aria-labelledby="app-icon-heading">
        <div class="brand-section-heading"><h2 id="app-icon-heading">Made for the small stuff.</h2><p>A white symbol on an indigo tile, ready for app icons and bookmarks.</p></div>
        <div class="brand-app-tile"><img src="{{ asset('brand/app-icon.svg') }}" width="112" height="112" alt="DropHTML app icon"><div><h3>App icon</h3><p>Use the tile at 16px and above.</p><div class="brand-links"><a href="{{ asset('brand/app-icon.svg') }}" download>Download SVG ↓</a><a href="{{ asset('brand/app-icon-512.png') }}" download>512px PNG ↓</a></div></div></div>
    </section>

    <section class="brand-section" aria-labelledby="colors-heading">
        <div class="brand-section-heading"><h2 id="colors-heading">Our colors</h2><p>Indigo leads. Quiet neutrals give it room to breathe.</p></div>
        <div class="brand-colors">
            @foreach (['Drop indigo' => '#4F46E5', 'Ink' => '#171923', 'Periwinkle' => '#A5B4FC', 'Soft white' => '#F5F5FA', 'Mist' => '#F4F3FF', 'Slate' => '#687084', 'Pale slate' => '#A3A9BD'] as $name => $hex)
                <div><div class="brand-swatch" style="background: {{ $hex }}"></div><h3>{{ $name }}</h3><code>{{ $hex }}</code></div>
            @endforeach
        </div>
    </section>

    <section class="brand-section brand-guidelines" aria-labelledby="guidelines-heading">
        <div><p class="brand-eyebrow">A LITTLE ROOM GOES A LONG WAY</p><h2 id="guidelines-heading">Keep it unmistakable.</h2></div>
        <div><p>Leave at least one file-square width of clear space around the mark (12 units on its 64-unit grid). Keep the original proportions and colors.</p><p>Use the standalone symbol at 24px and above, and the horizontal logo at a minimum width of 160px. Choose a variant with clear contrast against its background.</p><p>Don’t stretch, rotate, add gradients, or change the spacing between the icon and lettering.</p><a href="{{ asset('brand/FONT-LICENSE.txt') }}">Instrument Sans font license ↗</a></div>
    </section>
</div>
@endsection
