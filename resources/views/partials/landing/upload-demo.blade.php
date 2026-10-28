<div id="upload" class="lp-upload" aria-labelledby="upload-title">
    <div class="lp-upload__panel">
        <div class="lp-upload__toolbar">
            <span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="m8 8-4 4 4 4m8-8 4 4-4 4m-3-11-2 22" /></svg> NEW DEPLOYMENT</span>
            <span class="lp-upload__ready"><span class="lp-status-dot" aria-hidden="true"></span> Ready to publish</span>
        </div>
        <div class="lp-upload__body">
            <h2 id="upload-title">Drop a site. Make it live.</h2>
            <p>Your next live link starts here.</p>
            <div class="lp-upload__stage"><livewire:file-uploader /></div>
            <div class="lp-upload__formats"><span>HTML or ZIP archive</span><span>Up to 50 MB</span></div>
        </div>
        <div class="lp-upload__footnote">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><rect x="5" y="10" width="14" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>
            @guest
                <span>Just trying things out? Guest sites stay live for 24 hours.</span>
            @else
                <span>Manage your published sites from your dashboard.</span>
            @endguest
        </div>
    </div>
    @guest
        <p class="lp-upload__note">Want to keep it? <a href="{{ route('register') }}">Create a free account <svg class="lp-arrow" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14m-7-7 7 7-7 7" /></svg></a></p>
    @endguest
</div>
