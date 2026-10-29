@props(['title', 'description' => null, 'eyebrow' => 'YOUR WORKSPACE'])
<header {{ $attributes->class('dashboard-page-heading') }}>
    <div class="min-w-0">
        @if($eyebrow)<div class="dashboard-eyebrow mb-3">{{ $eyebrow }}</div>@endif
        <h1 class="dashboard-title">{{ $title }}</h1>
        @if($description)<p class="dashboard-lede mt-2 max-w-2xl">{{ $description }}</p>@endif
    </div>
    @if($slot->isNotEmpty())<div class="dashboard-page-actions">{{ $slot }}</div>@endif
</header>
