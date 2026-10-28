<section class="lp-hero" aria-labelledby="hero-title">
    <div class="lp-container">
        <div class="lp-hero__grid">
            <div class="lp-hero__copy">
                <span class="lp-eyebrow"><span class="lp-status-dot" aria-hidden="true"></span> A simpler way to go live</span>
                <h1 id="hero-title">Your next idea.<br />One drop away.<br /><span class="lp-hero__accent">Ship HTML in seconds.</span></h1>
                <p class="lp-hero__sub">Turn your HTML or ZIP into a live website. Drop your files, get a link, and share what you made. No build setup. No server to manage.</p>
                <div class="lp-hero__actions">
                    <a href="#upload" class="lp-btn lp-btn--primary">Deploy your site <svg class="lp-arrow lp-arrow--diagonal" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M7 17 17 7M7 7h10v10" /></svg></a>
                    @auth
                        <a href="{{ route('dashboard') }}" class="lp-btn lp-btn--ghost">Go to Your Dashboard</a>
                    @else
                        <a href="{{ route('register') }}" class="lp-hero__secondary">Create a free account <svg class="lp-arrow" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14m-7-7 7 7-7 7" /></svg></a>
                    @endauth
                </div>
                <p class="lp-hero__reassurance">No account needed to try. Just bring your files.</p>
                <ul class="lp-trust" aria-label="Hosting features">
                    <li><span aria-hidden="true">✓</span> Automatic SSL</li>
                    <li><span aria-hidden="true">✓</span> Custom domains</li>
                    <li><span aria-hidden="true">✓</span> GitHub deploys</li>
                </ul>
            </div>
            @include('partials.landing.upload-demo')
        </div>
        <div class="lp-workflow" aria-label="How it works">
            <p>FROM LOCAL TO LIVE<br /><strong>Three steps. Zero setup.</strong></p>
            <ol>
                <li><span>01</span><div><strong>Drop your project</strong><p>A single HTML file or a ZIP with your assets.</p></div></li>
                <li><span>02</span><div><strong>We take it from here</strong><p>Your files are unpacked and published.</p></div></li>
                <li><span>03</span><div><strong>Share your link</strong><p>Send your live site to anyone, anywhere.</p></div></li>
            </ol>
        </div>
    </div>
</section>
