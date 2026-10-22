@props([
    'sidebar' => false,
])

@if($sidebar)
    <flux:sidebar.brand aria-label="drophtml.de" {{ $attributes }}>
        <x-slot name="logo" class="h-8">
            <x-brand-lockup />
        </x-slot>
    </flux:sidebar.brand>
@else
    <flux:brand aria-label="drophtml.de" {{ $attributes }}>
        <x-slot name="logo" class="h-8">
            <x-brand-lockup />
        </x-slot>
    </flux:brand>
@endif
