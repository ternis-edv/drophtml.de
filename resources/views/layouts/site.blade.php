@php
    $workspaceSite = request()->route('site')
        ? auth()->user()->sites()->findOrFail(request()->route('site'))
        : null;
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
        @vite(['resources/css/editor.css', 'resources/css/site-workspace.css'])
    </head>
    <body class="site-console-body dark antialiased">
        <a href="#site-content" class="studio-skip">Skip to workspace</a>
        <div class="site-console" x-data="{ navigationOpen: false }">
            <header class="studio-header">
                <a href="{{ route('dashboard') }}" class="studio-brand" aria-label="Back to dashboard"><img src="/favicon.svg" alt="" /><span>drop<span class="studio-muted">html</span></span></a>
                <span class="studio-divider"></span>
                <div class="studio-project"><span class="studio-project-name">{{ $workspaceSite?->original_name ?: ($workspaceSite?->slug ?? 'Your workspace') }}</span><span class="studio-label">{{ $workspaceSite ? 'Site workspace' : 'Media workspace' }}</span></div>
                <div class="studio-header-actions">
                    @if($workspaceSite)
                        <a class="studio-button" href="{{ route('dashboard.sites.edit', $workspaceSite) }}"><flux:icon.code-bracket /><span>Open Code Studio</span></a>
                        <a class="studio-button studio-button-primary" href="{{ url('/s/'.$workspaceSite->slug) }}" target="_blank" rel="noopener">Visit site <flux:icon.arrow-top-right-on-square /></a>
                    @else
                        <a class="studio-button" href="{{ route('dashboard') }}"><flux:icon.arrow-left />All sites</a>
                    @endif
                    <button class="studio-icon-button site-menu-toggle" @click="navigationOpen = !navigationOpen" :aria-expanded="navigationOpen" aria-label="Toggle workspace navigation"><flux:icon.bars-3 /></button>
                </div>
            </header>
            <div class="site-console-grid">
                <aside class="site-sidebar" :class="{ 'is-open': navigationOpen }">
                    <div class="site-sidebar-label">{{ $workspaceSite ? 'MANAGE SITE' : 'WORKSPACE' }}</div>
                    <x-site-navigation :site="$workspaceSite" />
                    <div class="site-sidebar-bottom">
                        @if($workspaceSite)
                            <div class="site-sidebar-status"><i class="{{ $workspaceSite->status === 'active' ? 'is-active' : '' }}"></i>{{ ucfirst($workspaceSite->status) }}<span>{{ $workspaceSite->is_permanent ? 'Permanent' : 'Temporary' }}</span></div>
                            <a class="site-nav-item" href="{{ route('dashboard.sites.download', $workspaceSite) }}"><flux:icon.arrow-down-tray />Download site ZIP</a>
                        @endif
                        <a class="site-nav-item" href="{{ route('dashboard') }}"><flux:icon.arrow-left />Back to all sites</a>
                    </div>
                </aside>
                <main id="site-content" class="site-content" tabindex="-1">{{ $slot }}</main>
            </div>
        </div>
        <flux:toast />
        @fluxScripts
    </body>
</html>
