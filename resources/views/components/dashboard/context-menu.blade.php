@props(['label'])
<template x-teleport="body">
    <div class="dashboard-context-menu" x-ref="contextMenu" x-cloak x-show="contextOpen"
        role="menu" aria-label="{{ $label }}" :style="{ left: contextX + 'px', top: contextY + 'px' }"
        x-on:keydown="contextKeydown($event)"
        x-on:click.outside="closeContext(false)"
        x-on:click="if ($event.target.closest('[role=menuitem]')) closeContext(!!$event.target.closest('[data-context-restore]'))"
        x-on:contextmenu.stop="if (!$event.shiftKey) $event.preventDefault()"
        x-on:dashboard-context-open.window="if ($event.detail.id !== contextId) closeContext(false)"
        x-on:resize.window="closeContext(false)"
        x-on:scroll.window.capture="if (!$el.contains($event.target)) closeContext(false)"
        x-on:livewire:navigating.window="closeContext(false)">
        <div class="dashboard-context-label">{{ $label }}</div>
        {{ $slot }}
    </div>
</template>
