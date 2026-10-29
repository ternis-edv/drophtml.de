<script>
(() => {
    const root = document.documentElement;
    const key = 'drophtml:site-error-theme';
    const system = window.matchMedia('(prefers-color-scheme: dark)');
    let preference;
    try { preference = localStorage.getItem(key); } catch {}
    if (!['light', 'dark'].includes(preference)) preference = null;
    const apply = () => {
        const theme = preference || (system.matches ? 'dark' : 'light');
        root.dataset.theme = theme;
        root.classList?.toggle('dark', theme === 'dark');
        const button = document.getElementById('site-theme-toggle');
        if (button) {
            button.hidden = false;
            button.setAttribute('aria-label', `Switch to ${theme === 'dark' ? 'light' : 'dark'} theme`);
            button.title = button.getAttribute('aria-label');
        }
    };
    apply();
    system.addEventListener('change', () => { if (!preference) apply(); });
    document.addEventListener('DOMContentLoaded', () => {
        apply();
        document.getElementById('site-theme-toggle').addEventListener('click', () => {
            preference = root.dataset.theme === 'dark' ? 'light' : 'dark';
            try { localStorage.setItem(key, preference); } catch {}
            apply();
        });
    }, { once: true });
})();
</script>
