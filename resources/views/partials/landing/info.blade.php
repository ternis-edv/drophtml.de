<section id="features" class="lp-section lp-reveal">
    <div class="lp-container">
        <div class="lp-section-head">
            <span class="lp-eyebrow">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="m16 18 6-6-6-6" /><path d="m8 6-6 6 6 6" />
                </svg>
                <span>Built for shipping</span>
            </span>
            <h2>Everything you need to ship static</h2>
            <p>From a first drag &amp; drop to production domains — fast, secure, and without server overhead.</p>
        </div>

        <div class="lp-features__grid">
            <div class="lp-features__card">
                <div class="lp-features__head">
                    <span class="lp-features__icon">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M12 13v8" /><path d="m8 17 4-4 4 4" />
                            <path d="M20 16.58A5 5 0 0 0 18 7h-1.26A8 8 0 1 0 4 15.25" />
                        </svg>
                    </span>
                    <h3>Instant deploys</h3>
                </div>
                <p>Upload HTML or ZIP archives up to 50MB and get a live URL right away — no build step, no CLI.</p>
            </div>

            <div class="lp-features__card">
                <div class="lp-features__head">
                    <span class="lp-features__icon">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <line x1="6" x2="6" y1="3" y2="15" />
                            <circle cx="18" cy="6" r="3" />
                            <circle cx="6" cy="18" r="3" />
                            <path d="M18 9a9 9 0 0 1-9 9" />
                        </svg>
                    </span>
                    <h3>GitHub CI/CD</h3>
                </div>
                <p>Connect a repository with zero configuration and deploy automatically via webhooks on every push.</p>
            </div>

            <div class="lp-features__card">
                <div class="lp-features__head">
                    <span class="lp-features__icon">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <circle cx="12" cy="12" r="10" />
                            <path d="M12 2a14.5 14.5 0 0 0 0 20 14.5 14.5 0 0 0 0-20" />
                            <path d="M2 12h20" />
                        </svg>
                    </span>
                    <h3>Custom domains &amp; SSL</h3>
                </div>
                <p>Map your own domains with automated Let's Encrypt certificates and password-protected client previews.</p>
            </div>

            <div class="lp-features__card">
                <div class="lp-features__head">
                    <span class="lp-features__icon">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <rect width="18" height="18" x="3" y="3" rx="2" />
                            <path d="M7 3v18" /><path d="M3 7.5h4" /><path d="M3 12h18" />
                            <path d="M3 16.5h4" /><path d="M17 3v18" /><path d="M17 7.5h4" /><path d="M17 16.5h4" />
                        </svg>
                    </span>
                    <h3>Global media cloud</h3>
                </div>
                <p>Host 4K videos, WebP images, and PDFs on dedicated high-speed cloud storage served from the edge.</p>
            </div>
        </div>
    </div>
</section>

<section id="faq" class="lp-section lp-faq" aria-labelledby="faq-title">
    <div class="lp-container lp-faq__layout">
        <div class="lp-section-head">
            <span class="lp-eyebrow">Good to know</span>
            <h2 id="faq-title">A few things before<br />you drop.</h2>
            <p>Less setup, fewer questions. Here are the essentials.</p>
        </div>
        <div class="lp-faq__list">
            <details>
                <summary>Do I need an account to deploy?</summary>
                <p>No. Drop an HTML file or ZIP archive to publish as a guest. Guest sites expire after 24 hours. <a href="{{ route('register') }}">Create a free account</a> to manage your sites and choose permanent hosting.</p>
            </details>
            <details>
                <summary>What can I upload?</summary>
                <p>Upload a single .html or .htm file, or a .zip archive up to 50 MB. For a complete website, include your HTML, CSS, JavaScript, and assets in the ZIP, with an index.html as your entry page. Server-side code such as PHP is not supported.</p>
            </details>
            <details>
                <summary>Can I use my own domain?</summary>
                <p>Yes. With an account, connect your custom domain from the dashboard. You can also protect client previews with a password and deploy updates from GitHub.</p>
            </details>
            <details>
                <summary>What about images, videos, and other media?</summary>
                <p>Bundle assets with your website, or use dedicated cloud media storage to host images, videos, and PDFs separately. Keep your site uploads small while managing your media from the dashboard.</p>
            </details>
        </div>
    </div>
</section>

<section class="lp-section" aria-labelledby="cta-title">
    <div class="lp-container">
        <div class="lp-cta">
            <div><h2 id="cta-title">Made something? Make it live.</h2><p>Your prototype, portfolio, or next side project deserves a link.</p></div>
            <a href="#upload" class="lp-btn lp-btn--primary">Drop your first site <svg class="lp-arrow lp-arrow--diagonal" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M7 17 17 7M7 7h10v10" /></svg></a>
        </div>
    </div>
</section>
