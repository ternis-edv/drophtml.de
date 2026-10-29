export function menuPosition(x, y, width, height, viewportWidth, viewportHeight) {
    return {
        x: Math.max(8, Math.min(x, viewportWidth - width - 8)),
        y: Math.max(8, Math.min(y, viewportHeight - height - 8)),
    };
}

export function menuIndex(key, current, count) {
    if (!count) return -1;
    if (key === 'Home') return 0;
    if (key === 'End') return count - 1;
    if (current < 0 && key === 'ArrowUp') return count - 1;
    if (current < 0 && key === 'ArrowDown') return 0;
    if (key === 'ArrowDown') return (current + 1 + count) % count;
    if (key === 'ArrowUp') return (current - 1 + count) % count;
    return current;
}

export function menuMatch(labels, query, current = -1) {
    const search = query.trim().toLocaleLowerCase();
    if (!search || !labels.length) return -1;
    for (let offset = 1; offset <= labels.length; offset++) {
        const index = (Math.max(-1, current) + offset) % labels.length;
        if (labels[index].trim().toLocaleLowerCase().startsWith(search)) return index;
    }
    return -1;
}

const enabledItems = menu => [...menu.querySelectorAll('[role="menuitem"]')]
    .filter(item => !item.disabled && item.getAttribute('aria-disabled') !== 'true' && item.getClientRects().length);

let sequence = 0;
function registerContextMenu() {
    window.Alpine.data('dashboardContextMenu', () => ({
        contextId: ++sequence,
        contextOpen: false,
        contextX: 8,
        contextY: 8,
        contextOrigin: null,
        contextSearch: '',
        contextSearchAt: 0,
        showContext(event) {
            if (event.shiftKey && event.type === 'contextmenu') return;
            if (event.target.closest('input, textarea, select, [contenteditable]:not([contenteditable="false"])')) return;
            event.preventDefault();
            event.stopPropagation();
            this.contextSearch = '';
            this.contextSearchAt = 0;
            this.contextOrigin = this.$el;
            const rect = this.$el.getBoundingClientRect();
            const keyboard = event.type === 'keydown';
            const x = keyboard ? rect.left + 12 : event.clientX;
            const y = keyboard ? rect.top + Math.min(rect.height, 36) : event.clientY;
            window.dispatchEvent(new CustomEvent('dashboard-context-open', { detail: { id: this.contextId } }));
            this.contextX = x;
            this.contextY = y;
            this.contextOpen = true;
            this.$nextTick(() => {
                if (!this.contextOpen) return;
                const menu = this.$refs.contextMenu;
                if (!menu) return;
                const box = menu.getBoundingClientRect();
                const position = menuPosition(x, y, box.width, box.height, window.innerWidth, window.innerHeight);
                this.contextX = position.x;
                this.contextY = position.y;
                enabledItems(menu)[0]?.focus({ preventScroll: true });
            });
        },
        closeContext(restore = false) {
            if (!this.contextOpen) return;
            this.contextOpen = false;
            if (restore) this.contextOrigin?.focus({ preventScroll: true });
        },
        contextKeydown(event) {
            if (event.key === 'Escape') {
                event.preventDefault();
                event.stopPropagation();
                this.closeContext(true);
            } else if (event.key === 'Tab') {
                this.closeContext(true);
            } else if (['ArrowDown', 'ArrowUp', 'Home', 'End'].includes(event.key)) {
                event.preventDefault();
                const items = enabledItems(this.$refs.contextMenu);
                const next = menuIndex(event.key, items.indexOf(document.activeElement), items.length);
                items[next]?.focus({ preventScroll: true });
                this.contextSearch = '';
            } else if (event.key.length === 1 && event.key !== ' ' && !event.ctrlKey && !event.metaKey && !event.altKey && !event.isComposing) {
                event.preventDefault();
                const time = Date.now();
                this.contextSearch = time - this.contextSearchAt > 700 ? event.key : this.contextSearch + event.key;
                this.contextSearchAt = time;
                // Repeating a letter cycles matching actions instead of searching for "ccc".
                const query = [...this.contextSearch].every(letter => letter.toLocaleLowerCase() === event.key.toLocaleLowerCase())
                    ? event.key : this.contextSearch;
                const items = enabledItems(this.$refs.contextMenu);
                const current = items.indexOf(document.activeElement);
                const start = query.length === 1 ? current : current - 1;
                const next = menuMatch(items.map(item => item.textContent), query, start);
                items[next]?.focus({ preventScroll: true });
            }
        },
    }));
}
if (typeof window !== 'undefined') {
    if (window.Alpine) registerContextMenu();
    else document.addEventListener('alpine:init', registerContextMenu, { once: true });
}
