<div id="upload" class="lp-upload" aria-labelledby="hero-title">
    <header class="lp-upload__heading">
        <h1 id="hero-title">Drop a site. Make it live.</h1>
        <p>Your next live link starts here.</p>
    </header>
    <div class="lp-upload__stage"><livewire:file-uploader /></div>
    <div class="lp-upload__formats"><span>HTML or ZIP archive</span><span>Up to 50 MB</span></div>
    @guest
        <p class="lp-upload__note">Want to keep it? <a href="{{ route('register') }}">Create a free account <svg class="lp-arrow" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14m-7-7 7 7-7 7" /></svg></a></p>
    @endguest
</div>
