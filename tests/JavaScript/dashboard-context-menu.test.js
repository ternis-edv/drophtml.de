import { test } from 'node:test';
import assert from 'node:assert/strict';
import { menuIndex, menuPosition } from '../../resources/js/dashboard-context-menu.js';

test('context menus stay inside the viewport near every edge', () => {
    assert.deepEqual(menuPosition(995, 745, 232, 320, 1000, 750), { x: 760, y: 422 });
    assert.deepEqual(menuPosition(-5, -2, 232, 320, 1000, 750), { x: 8, y: 8 });
    assert.deepEqual(menuPosition(100, 120, 232, 320, 1000, 750), { x: 100, y: 120 });
});

test('menu keyboard navigation wraps, supports home/end and handles empty menus', () => {
    assert.equal(menuIndex('ArrowDown', 3, 4), 0);
    assert.equal(menuIndex('ArrowUp', 0, 4), 3);
    assert.equal(menuIndex('Home', 2, 4), 0);
    assert.equal(menuIndex('End', 0, 4), 3);
    assert.equal(menuIndex('ArrowUp', -1, 4), 3);
    assert.equal(menuIndex('ArrowDown', -1, 4), 0);
    assert.equal(menuIndex('ArrowDown', 0, 0), -1);
});
