<script>
(() => {
    const root = document.documentElement;
    const key = 'flux.appearance';
    const system = window.matchMedia('(prefers-color-scheme: dark)');
    let preference;
    const readPreference = () => {
        try {
            const saved = localStorage.getItem(key);
            preference = ['light', 'dark'].includes(saved) ? saved : null;
        } catch { /* Keep the in-memory choice when storage is blocked. */ }
    };
    readPreference();
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
    window.addEventListener('storage', (event) => {
        if (event.key !== key && event.key !== null) return;
        readPreference();
        apply();
    });
    window.addEventListener('pageshow', () => { readPreference(); apply(); });
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
