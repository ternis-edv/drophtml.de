// Read copy values from the DOM instead of interpolating filenames or code into JS.
// This keeps quotes, backticks, and multiline snippets intact.
function registerMediaLibrary() {
    window.Alpine.data('mediaLibrary', () => ({
        copied: null,
        copyError: '',
        copyTimer: null,
        async copy(value, key) {
            this.copyError = '';
            this.copied = null;
            clearTimeout(this.copyTimer);
            try {
                await navigator.clipboard.writeText(value);
                this.copied = key;
                this.copyTimer = setTimeout(() => { this.copied = null; }, 2200);
            } catch {
                this.copyError = 'Clipboard access is unavailable. Select the URL or snippet below and copy it manually.';
            }
        },
        destroy() { clearTimeout(this.copyTimer); },
    }));
}
if (window.Alpine) registerMediaLibrary();
else document.addEventListener('alpine:init', registerMediaLibrary, { once: true });
