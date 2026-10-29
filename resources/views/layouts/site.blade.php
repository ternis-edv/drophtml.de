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
    <body class="site-console-body antialiased">
        <a href="#site-content" class="studio-skip">Skip to workspace</a>
        <div class="site-console" x-data="{ navigationOpen: false }" @keydown.escape.window="if (navigationOpen) { navigationOpen = false; $refs.navToggle.focus() }" x-on:livewire:navigating.window="navigationOpen = false">
            <header class="studio-header">
                <a href="{{ route('dashboard') }}" class="studio-brand" aria-label="Back to dashboard"><img src="/favicon.svg" alt="" /><span>drop<span class="studio-muted">html</span></span></a>
                <span class="studio-divider"></span>
                <div class="studio-project"><span class="studio-project-name">{{ $workspaceSite?->original_name ?: ($workspaceSite?->slug ?? 'Dashboard') }}</span><span class="studio-label">{{ $workspaceSite ? 'Site workspace' : 'Your workspace' }}</span></div>
                <div class="studio-header-actions">
                    @if($workspaceSite)
                        <a class="studio-button" href="{{ route('dashboard.sites.edit', $workspaceSite) }}"><flux:icon.code-bracket /><span>Open Code Studio</span></a>
                        <a class="studio-button studio-button-primary" href="{{ url('/s/'.$workspaceSite->slug) }}" target="_blank" rel="noopener">Visit site <flux:icon.arrow-top-right-on-square /></a>
                    @else
                        <button type="button" class="studio-button" @click="$dispatch('open-command-palette')" aria-label="Search dashboard"><flux:icon.magnifying-glass />Quick search <kbd>⌘K</kbd></button>
                    @endif
                    <button type="button" x-ref="navToggle" class="studio-icon-button site-menu-toggle" @click="navigationOpen = !navigationOpen" :aria-expanded="navigationOpen" aria-controls="dashboard-navigation" aria-label="Toggle workspace navigation"><flux:icon.bars-3 /></button>
                </div>
            </header>
            <div class="site-console-grid">
                <button type="button" class="site-navigation-backdrop" x-cloak x-show="navigationOpen" @click="navigationOpen = false" aria-label="Close navigation"></button>
                <aside id="dashboard-navigation" class="site-sidebar" x-trap.inert.noscroll="navigationOpen && window.innerWidth <= 760" :class="{ 'is-open': navigationOpen }">
                    <div class="site-sidebar-label">{{ $workspaceSite ? 'MANAGE SITE' : 'WORKSPACE' }}</div>
                    <x-site-navigation :site="$workspaceSite" />
                    <div class="site-sidebar-bottom">
                        @if($workspaceSite)
                            <div class="site-sidebar-status"><i class="{{ $workspaceSite->status === 'active' ? 'is-active' : '' }}"></i>{{ ucfirst($workspaceSite->status) }}<span>{{ $workspaceSite->is_permanent ? 'Permanent' : 'Temporary' }}</span></div>
                            <a class="site-nav-item" href="{{ route('dashboard.sites.download', $workspaceSite) }}"><flux:icon.arrow-down-tray />Download site ZIP</a>
                        @endif
                        <a class="site-nav-item" href="{{ route('docs.agents') }}"><flux:icon.cpu-chip />Agent documentation</a>
                        <a class="site-nav-item" href="/docs/api"><flux:icon.document-text />API documentation</a>
                        <x-desktop-user-menu class="mt-4" :name="auth()->user()->name" />
                    </div>
                </aside>
                <main id="site-content" class="site-content" tabindex="-1">{{ $slot }}</main>
            </div>
            <livewire:dashboard.command-palette />
        </div>
        @persist('toast')
            <flux:toast.group><flux:toast /></flux:toast.group>
        @endpersist
        @fluxScripts
    </body>
</html>
