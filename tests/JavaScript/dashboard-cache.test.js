import { test } from 'node:test';
import assert from 'node:assert/strict';
import { createDashboardCache, CACHE_PREFIX, CACHE_TTL } from '../../resources/js/dashboard-cache.js';

function memoryStorage() {
    const entries = new Map();
    return {
        get length() { return entries.size; },
        key: (i) => [...entries.keys()][i],
        getItem: (key) => entries.get(key) ?? null,
        setItem: (key, value) => entries.set(key, value),
        removeItem: (key) => entries.delete(key),
    };
}
const data = { stats: [{ label: 'Sites', value: '3' }], sites: [{ id: 1, name: 'My site' }] };

test('shows a saved preview only for its user, within the freshness window', () => {
    let time = 1000;
    const cache = createDashboardCache(memoryStorage(), () => time);
    cache.write('alice', data);
    assert.deepEqual(cache.read('alice'), data);
    assert.equal(cache.read('bob'), null);
    time += CACHE_TTL + 1;
    assert.equal(cache.read('alice'), null);
});

test('switching users and logout clear previews without deleting other preferences', () => {
    const storage = memoryStorage();
    const cache = createDashboardCache(storage);
    storage.setItem('drophtml_view_mode', 'grid');
    cache.write('alice', data);
    cache.write('bob', data);
    assert.equal(cache.read('alice'), null);
    assert.deepEqual(cache.read('bob'), data);
    cache.clear();
    assert.equal(cache.read('bob'), null);
    assert.equal(storage.getItem('drophtml_view_mode'), 'grid');
});

test('corrupt, malformed and future-dated entries are ignored', () => {
    const storage = memoryStorage();
    const cache = createDashboardCache(storage, () => 1000);
    for (const value of ['{', '{}', JSON.stringify({ userId: 'alice', savedAt: 2000, data }), JSON.stringify({ userId: 'alice', savedAt: 1000, data: {} })]) {
        storage.setItem(CACHE_PREFIX + 'alice', value);
        assert.equal(cache.read('alice'), null);
    }
});

test('storage restrictions never prevent loading fresh data', () => {
    const storage = { get length() { throw new Error('blocked'); }, getItem() { throw new Error('blocked'); }, setItem() { throw new Error('full'); } };
    const cache = createDashboardCache(storage);
    assert.equal(cache.read('alice'), null);
    assert.doesNotThrow(() => cache.write('alice', data));
    assert.doesNotThrow(() => cache.clear());
});
