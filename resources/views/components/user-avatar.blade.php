@props(['user', 'size' => 'sm'])
@php($avatarUrl = $user->avatarUrl())
<span {{ $attributes->class('dashboard-user-avatar') }} data-size="{{ $size }}" aria-hidden="true" x-data="{ failed: false }" x-init="$nextTick(() => { const image = $el.querySelector('img'); if (image?.complete && !image.naturalWidth) failed = true })" wire:key="avatar-{{ $user->id }}-{{ $size }}-{{ md5($avatarUrl ?? '') }}">
    <span>{{ $user->initials() }}</span>
    @if($avatarUrl)
        <img src="{{ $avatarUrl }}" alt="" decoding="async" referrerpolicy="no-referrer" x-show="!failed" x-on:error="failed = true" />
    @endif
</span>
