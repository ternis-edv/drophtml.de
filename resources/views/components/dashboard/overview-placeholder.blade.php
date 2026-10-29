<div class="space-y-6" x-data="dashboardPreview(@js((string) auth()->id()))" data-dashboard-preview>
    <div class="site-page-heading flex flex-wrap items-start justify-between gap-4">
        <div>
            <div class="site-page-eyebrow">YOUR WORKSPACE</div>
            <h1>Your sites, at a glance.</h1>
            <p role="status" aria-live="polite" x-text="delayed ? 'The dashboard is taking longer than usual. Reload to try again.' : (preview ? 'Showing a saved preview. Updating your dashboard…' : 'Loading your dashboard…')">Loading your dashboard…</p>
        </div>
        <a href="{{ route('dashboard') }}" class="studio-button" x-cloak x-show="delayed">Reload dashboard</a>
    </div>
    <div x-cloak x-show="preview" class="space-y-6">
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <template x-for="stat in preview?.stats || []" :key="stat.label">
                <div class="panel p-5"><span class="stat-label" x-text="stat.label"></span><div class="stat-value" x-text="stat.value"></div></div>
            </template>
        </div>
        <div class="flex items-center justify-between"><h2 class="section-title">Recent sites</h2><span class="text-xs text-zinc-500">Saved preview · Read-only</span></div>
        <div class="grid sm:grid-cols-2 xl:grid-cols-3 gap-4">
            <template x-for="site in preview?.sites || []" :key="site.id">
                <article class="panel p-5 min-w-0"><div class="mb-4 flex items-center gap-3"><flux:icon.globe-alt class="size-5 text-accent" /><h3 class="truncate text-sm font-medium" x-text="site.name"></h3></div><p class="truncate text-xs text-zinc-500" x-text="site.slug"></p><p class="mt-4 text-xs text-zinc-500" x-text="site.status"></p></article>
            </template>
        </div>
    </div>
    <div x-show="!preview" aria-hidden="true" class="space-y-6">
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">@foreach(range(1, 4) as $item)<div class="panel p-5 space-y-4"><div class="skeleton h-3 w-20"></div><div class="skeleton h-8 w-14"></div></div>@endforeach</div>
        <div class="grid sm:grid-cols-2 xl:grid-cols-3 gap-4">@foreach(range(1, 6) as $item)<div class="panel p-5 space-y-5"><div class="skeleton h-5 w-3/4"></div><div class="skeleton h-3 w-1/2"></div><div class="skeleton h-3 w-16"></div></div>@endforeach</div>
    </div>
    <noscript><p>Enable JavaScript to load your dashboard. You can still use the navigation to manage your account.</p></noscript>
</div>
