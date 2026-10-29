// A read-only preview in this tab; live data and actions always come from Livewire.
export const CACHE_PREFIX = 'drophtml:overview:v1:';
export const CACHE_TTL = 5 * 60 * 1000;

export function createDashboardCache(storage, now = Date.now) {
    const clear = () => {
        try {
            for (let i = storage.length - 1; i >= 0; i--) {
                const key = storage.key(i);
                if (key?.startsWith(CACHE_PREFIX)) storage.removeItem(key);
            }
        } catch { /* Storage can be unavailable in private browsing. */ }
    };
    return {
        clear,
        read(userId) {
            try {
                const entry = JSON.parse(storage.getItem(CACHE_PREFIX + userId));
                if (!entry || entry.userId !== userId || !Number.isFinite(entry.savedAt)
                    || now() - entry.savedAt < 0 || now() - entry.savedAt > CACHE_TTL
                    || !Array.isArray(entry.data?.stats) || !Array.isArray(entry.data?.sites)) return null;
                return entry.data;
            } catch { return null; }
        },
        write(userId, data) {
            clear();
            try { storage.setItem(CACHE_PREFIX + userId, JSON.stringify({ userId, savedAt: now(), data })); }
            catch { /* A full/blocked cache must never block the dashboard. */ }
        },
    };
}

if (typeof window !== 'undefined') {
    let storage;
    try { storage = window.sessionStorage; } catch { storage = null; }
    const cache = window.drophtmlDashboardCache = createDashboardCache(storage);
    const register = () => window.Alpine.data('dashboardPreview', (userId) => ({
        preview: cache.read(userId),
        delayed: false,
        timer: null,
        init() { this.timer = setTimeout(() => { this.delayed = true; }, 12000); },
        destroy() { clearTimeout(this.timer); },
    }));
    if (window.Alpine) register();
    else document.addEventListener('alpine:init', register, { once: true });
    document.addEventListener('submit', (event) => {
        if (event.target instanceof HTMLFormElement && new URL(event.target.action).pathname.endsWith('/logout')) cache.clear();
    }, true);
}
