// Flux owns the app preference; standalone errors use the same storage key.
export function syncThemePreference(event, flux) {
    if (!flux || (event.key !== 'flux.appearance' && event.key !== null)) return;
    let saved;
    try { saved = window.localStorage.getItem('flux.appearance'); } catch { return; }
    const appearance = ['light', 'dark'].includes(saved) ? saved : 'system';
    flux.appearance = appearance;
    flux.applyAppearance?.(appearance);
}

if (typeof window !== 'undefined') {
    window.addEventListener('storage', event => syncThemePreference(event, window.Flux));
    window.addEventListener('pageshow', event => {
        if (event.persisted) syncThemePreference({ key: 'flux.appearance' }, window.Flux);
    });
}
