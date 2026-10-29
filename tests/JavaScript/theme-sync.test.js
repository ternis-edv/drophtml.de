import { test } from 'node:test';
import assert from 'node:assert/strict';
import { syncThemePreference } from '../../resources/js/theme-sync.js';

test('404 preference changes update Flux in another app tab, including system reset', () => {
    let saved = 'dark';
    const applied = [];
    const flux = { appearance: 'light', applyAppearance: value => applied.push(value) };
    globalThis.window = { localStorage: { getItem: () => saved } };
    try {
        syncThemePreference({ key: 'other-setting' }, flux);
        assert.equal(flux.appearance, 'light');
        syncThemePreference({ key: 'flux.appearance' }, flux);
        assert.equal(flux.appearance, 'dark');
        saved = null;
        syncThemePreference({ key: null }, flux);
        assert.equal(flux.appearance, 'system');
        assert.deepEqual(applied, ['dark', 'system']);
        window.localStorage.getItem = () => { throw Error('blocked'); };
        assert.doesNotThrow(() => syncThemePreference({ key: 'flux.appearance' }, flux));
        assert.doesNotThrow(() => syncThemePreference({ key: null }, null));
    } finally { delete globalThis.window; }
});
