<div class="dashboard-settings-grid">
    <nav class="dashboard-settings-nav" aria-label="{{ __('Account settings sections') }}">
        @foreach([
            ['profile.edit', 'user', 'Profile'],
            ['security.edit', 'shield-check', 'Security'],
            ['settings.storage', 'circle-stack', 'Storage & usage'],
            ['api-tokens.edit', 'key', 'API tokens'],
            ['settings.data-export', 'document-arrow-down', 'Data export'],
            ['appearance.edit', 'paint-brush', 'Appearance'],
        ] as [$route, $icon, $label])
            <a href="{{ route($route) }}" wire:navigate @if(request()->routeIs($route)) aria-current="page" @endif><flux:icon :name="$icon" /><span>{{ __($label) }}</span></a>
        @endforeach
    </nav>
    <div class="dashboard-settings-content {{ $maxWidth ?? '' }}">
        <div class="dashboard-panel p-5 sm:p-6">
            <h2 class="dashboard-section-title dashboard-section-title">{{ $heading ?? '' }}</h2>
            <p class="dashboard-lede mt-1">{{ $subheading ?? '' }}</p>
            <div class="mt-6 w-full">{{ $slot }}</div>
        </div>
    </div>
</div>
