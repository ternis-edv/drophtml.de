@props(['site', 'current' => 'editor'])
@php
    [$heading, $description] = match($current) {
        'media' => ['Media library', 'Everything your site needs. Upload, preview, and embed your assets.'],
        'stats' => ['Site Analytics', 'A closer look at the people visiting your site.'],
        'deployments' => ['Deployments & rollbacks', 'Your publishing history, with a way back when you need it.'],
        'domains' => ['Custom domains', 'Give your site a home of its own.'],
        'redirects' => ['Redirects', 'Keep every link pointing in the right direction.'],
        'settings' => ['Site settings', 'Fine-tune how your site is published, accessed, and maintained.'],
        default => ['Code Studio', 'A workspace for your next idea.'],
    };
@endphp
<x-dashboard.page-heading :title="$heading" :description="$description" :eyebrow="'SITE WORKSPACE / '.$site->slug" />
