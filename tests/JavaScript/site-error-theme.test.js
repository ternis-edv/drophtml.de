import { test } from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { runInNewContext } from 'node:vm';

const source = readFileSync(new URL('../../resources/views/errors/partials/theme-script.blade.php', import.meta.url), 'utf8').replace(/<\/?script>/g, '');
function page(saved, dark = false, blocked = false) {
    const root = { dataset: {} };
    const events = {};
    const attributes = {};
    const button = { hidden: true, setAttribute: (key, value) => attributes[key] = value, getAttribute: key => attributes[key], addEventListener: (key, callback) => events[key] = callback };
    const system = { matches: dark, addEventListener: (key, callback) => events.system = callback };
    const stored = [];
    runInNewContext(source, {
        document: { documentElement: root, getElementById: () => button, addEventListener: (key, callback) => events[key] = callback },
        window: { matchMedia: () => system, addEventListener: (key, callback) => events[key] = callback },
        localStorage: { getItem: key => { assert.equal(key, 'flux.appearance'); if (blocked) throw Error(); return saved; }, setItem: (...args) => { if (blocked) throw Error(); stored.push(args); } },
    });
    events.DOMContentLoaded();
    return { root, events, attributes, button, system, stored, setSaved(value) { saved = value; } };
}

test('site errors follow system theme until a manual choice is made', () => {
    const state = page(null);
    assert.equal(state.root.dataset.theme, 'light');
    assert.equal(state.button.hidden, false);
    state.system.matches = true;
    state.events.system();
    assert.equal(state.root.dataset.theme, 'dark');
    assert.equal(state.attributes['aria-label'], 'Switch to light theme');
    state.events.click();
    assert.equal(state.root.dataset.theme, 'light');
    assert.deepEqual(state.stored, [['flux.appearance', 'light']]);
    state.events.system();
    assert.equal(state.root.dataset.theme, 'light');
});

test('saved preferences win and blocked storage still allows toggling', () => {
    assert.equal(page('dark', false).root.dataset.theme, 'dark');
    assert.equal(page('invalid', true).root.dataset.theme, 'dark');
    const state = page(null, false, true);
    assert.doesNotThrow(() => state.events.click());
    assert.equal(state.root.dataset.theme, 'dark');
});


test('dashboard preference changes sync into open 404 pages and restored history', () => {
    const state = page('light');
    state.setSaved('dark');
    state.events.storage({ key: 'other-setting' });
    assert.equal(state.root.dataset.theme, 'light');
    state.events.storage({ key: 'flux.appearance' });
    assert.equal(state.root.dataset.theme, 'dark');
    state.setSaved(null);
    state.events.storage({ key: null });
    assert.equal(state.root.dataset.theme, 'light');
    state.setSaved('dark');
    state.events.pageshow();
    assert.equal(state.root.dataset.theme, 'dark');
});
