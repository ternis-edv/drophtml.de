<section id="upload" class="lp-section lp-reveal">
    <div class="lp-container">
        <div class="lp-section-head">
            <span class="lp-eyebrow">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M12 3v12" /><path d="m17 8-5-5-5 5" />
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                </svg>
                <span>Drag &amp; Drop Publisher</span>
            </span>
            <h2>Deploy instantly, right from your browser</h2>
            <p>
                No command-line setup or account required to test. Drop a single
                <code>.html</code> file or a full <code>.zip</code> archive below to publish immediately.
            </p>
        </div>

        <div class="lp-upload">
            <div class="lp-upload__panel">
                <div class="lp-upload__stage">
                    <livewire:file-uploader />
                </div>

                <!-- 3-Step Pipeline -->
                <ol class="lp-upload__steps">
                    <li class="lp-upload__step">
                        <span class="lp-upload__step-num">1</span>
                        <div>
                            <strong>Drop your file</strong>
                            <span>Accepts single HTML, CSS, JS, or zipped project folders.</span>
                        </div>
                    </li>
                    <li class="lp-upload__step">
                        <span class="lp-upload__step-num">2</span>
                        <div>
                            <strong>Automatic Edge Caching</strong>
                            <span>Zip Slip protection, sanitized file extraction &amp; SSL generation.</span>
                        </div>
                    </li>
                    <li class="lp-upload__step">
                        <span class="lp-upload__step-num">3</span>
                        <div>
                            <strong>Live URL in &lt; 2s</strong>
                            <span>Share with clients, lock with password, or connect your domain.</span>
                        </div>
                    </li>
                </ol>
            </div>

            @guest
                <p class="lp-upload__note">
                    Guest uploads expire in 24 hours.
                    <a href="{{ route('register') }}">Create a free account</a>
                    to retain them permanently and connect custom domains.
                </p>
            @endguest
        </div>
    </div>
</section>
