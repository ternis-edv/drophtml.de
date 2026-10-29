@props(['site' => null])
<nav class="site-navigation" aria-label="Site sections">
    @if($site)
        @foreach([
            ['dashboard.sites.edit', 'code-bracket', 'Code Studio'],
            ['dashboard.sites.media', 'photo', 'Media library'],
            ['dashboard.sites.stats', 'chart-bar', 'Analytics'],
            ['dashboard.sites.deployments', 'archive-box', 'Deployments'],
            ['dashboard.sites.domains', 'globe-alt', 'Domains'],
            ['dashboard.sites.redirects', 'arrows-right-left', 'Redirects'],
            ['dashboard.sites.errors', 'document-text', 'Error pages'],
            ['dashboard.sites.settings', 'cog-6-tooth', 'Settings'],
        ] as [$route, $icon, $label])
            <a @if($route !== 'dashboard.sites.edit') wire:navigate @endif href="{{ route($route, $site) }}" class="site-nav-item {{ request()->routeIs($route) ? 'is-active' : '' }}" @if(request()->routeIs($route)) aria-current="page" @endif><flux:icon :name="$icon" /><span>{{ $label }}</span>@if(request()->routeIs($route))<span class="site-nav-dot"></span>@endif</a>
        @endforeach
        <div class="site-sidebar-label">YOUR ASSETS</div>
    @endif
    <a wire:navigate href="{{ route('dashboard.media') }}" class="site-nav-item {{ request()->routeIs('dashboard.media') ? 'is-active' : '' }}" @if(request()->routeIs('dashboard.media')) aria-current="page" @endif><flux:icon.squares-2x2 />All media</a>
    <div class="site-sidebar-label">DASHBOARD</div>
    @foreach([
        ['dashboard', 'squares-2x2', 'All sites'],
        ['dashboard.sites.upload', 'arrow-up-tray', 'Deploy site'],
        ['settings.storage', 'circle-stack', 'Storage & usage'],
        ['dashboard.activity', 'clock', 'Activity log'],
        ['dashboard.help', 'chat-bubble-left-right', 'AI help'],
        ['profile.edit', 'user', 'Account settings'],
    ] as [$route, $icon, $label])
        @php($current = request()->routeIs($route) || ($route === 'profile.edit' && request()->routeIs('security.edit', 'api-tokens.edit', 'appearance.edit', 'settings.data-export')))
        <a href="{{ route($route) }}" wire:navigate class="site-nav-item {{ $current ? 'is-active' : '' }}" @if($current) aria-current="page" @endif><flux:icon :name="$icon" /><span>{{ $label }}</span></a>
    @endforeach
</nav>
