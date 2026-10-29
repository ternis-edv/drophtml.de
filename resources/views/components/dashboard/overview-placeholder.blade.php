<div class="space-y-6" x-data="dashboardPreview(@js((string) auth()->id()))" data-dashboard-preview>
    <div class="dashboard-page-heading flex flex-wrap items-start justify-between gap-4">
        <div>
            <div class="dashboard-eyebrow mb-3">YOUR WORKSPACE</div>
            <h1 class="dashboard-title">Your sites, at a glance.</h1>
            <p class="dashboard-lede mt-2" role="status" aria-live="polite" x-text="delayed ? 'The dashboard is taking longer than usual. Reload to try again.' : (preview ? 'Showing a saved preview. Updating your dashboard…' : 'Loading your dashboard…')">Loading your dashboard…</p>
        </div>
        <a href="{{ route('dashboard') }}" class="studio-button" x-cloak x-show="delayed">Reload dashboard</a>
    </div>
    <div x-cloak x-show="preview" class="space-y-6">
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <template x-for="stat in preview?.stats || []" :key="stat.label">
                <div class="dashboard-panel p-5"><span class="dashboard-stat-label" x-text="stat.label"></span><div class="dashboard-stat-value" x-text="stat.value"></div></div>
            </template>
        </div>
        <div class="flex items-center justify-between"><h2 class="dashboard-section-title dashboard-section-title">Recent sites</h2><span class="text-xs dashboard-muted">Saved preview · Read-only</span></div>
        <div class="grid sm:grid-cols-2 xl:grid-cols-3 gap-4">
            <template x-for="site in preview?.sites || []" :key="site.id">
                <article class="dashboard-panel p-5 min-w-0"><div class="mb-4 flex items-center gap-3"><flux:icon.globe-alt class="size-5 dashboard-accent" /><h3 class="dashboard-section-title truncate text-sm " x-text="site.name"></h3></div><p class="truncate text-xs dashboard-muted" x-text="site.slug"></p><p class="mt-4 text-xs dashboard-muted" x-text="site.status"></p></article>
            </template>
        </div>
    </div>
    <div x-show="!preview" aria-hidden="true" class="space-y-6">
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">@foreach(range(1, 4) as $item)<div class="dashboard-panel p-5 space-y-4"><div class="dashboard-skeleton h-3 w-20"></div><div class="dashboard-skeleton h-8 w-14"></div></div>@endforeach</div>
        <div class="grid sm:grid-cols-2 xl:grid-cols-3 gap-4">@foreach(range(1, 6) as $item)<div class="dashboard-panel p-5 space-y-5"><div class="dashboard-skeleton h-5 w-3/4"></div><div class="dashboard-skeleton h-3 w-1/2"></div><div class="dashboard-skeleton h-3 w-16"></div></div>@endforeach</div>
    </div>
    <noscript><p>Enable JavaScript to load your dashboard. You can still use the navigation to manage your account.</p></noscript>
</div>
