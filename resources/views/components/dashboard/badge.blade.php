@props(['color' => 'zinc', 'size' => 'sm', 'icon' => null])
@php
    $tone = match($color) {
        'green', 'emerald' => 'success',
        'red', 'rose' => 'danger',
        'amber', 'yellow', 'orange' => 'warning',
        'zinc', 'gray', null => 'neutral',
        default => 'accent',
    };
@endphp
<span {{ $attributes->class('dashboard-badge') }} data-tone="{{ $tone }}" data-size="{{ $size }}">
    @if($icon)<flux:icon :name="$icon" class="size-3" />@endif
    {{ $slot }}
</span>
