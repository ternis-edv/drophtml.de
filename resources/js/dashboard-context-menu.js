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

let sequence = 0;
function registerContextMenu() {
    window.Alpine.data('dashboardContextMenu', () => ({
        contextId: ++sequence,
        contextOpen: false,
        contextX: 8,
        contextY: 8,
        contextOrigin: null,
        showContext(event) {
            if (event.shiftKey && event.type === 'contextmenu') return;
            if (event.target.closest('input, textarea, select, [contenteditable]:not([contenteditable="false"])')) return;
            event.preventDefault();
            event.stopPropagation();
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
                menu.querySelector('[role="menuitem"]')?.focus({ preventScroll: true });
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
                const items = [...this.$refs.contextMenu.querySelectorAll('[role="menuitem"]')].filter(item => !item.disabled);
                const next = menuIndex(event.key, items.indexOf(document.activeElement), items.length);
                items[next]?.focus();
            }
        },
    }));
}
if (typeof window !== 'undefined') {
    if (window.Alpine) registerContextMenu();
    else document.addEventListener('alpine:init', registerContextMenu, { once: true });
}
