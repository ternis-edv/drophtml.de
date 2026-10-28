<?php

return [
    'legacy_root' => env('MEDIA_LEGACY_ROOT', storage_path('app/public')),

    /*
    |--------------------------------------------------------------------------
    | Public Media Base URL (Custom Domain)
    |--------------------------------------------------------------------------
    | Large media assets are delivered from object storage (Cloudflare R2 /
    | S3). When a custom domain is connected to the bucket (for example
    | assets.drophtml.de as a Cloudflare R2 custom domain), set its base URL
    | here — every cloud-hosted media URL will be built from it. Leave empty
    | to fall back to the storage driver's default URL (e.g. r2.dev).
    */

    'public_url' => env('MEDIA_PUBLIC_URL'),

    /*
    |--------------------------------------------------------------------------
    | Automatic Large-Asset Offloading
    |--------------------------------------------------------------------------
    | Media files (images / videos) that live inside a site's code bundle
    | and are at least `threshold_kb` big are automatically moved to cloud
    | asset storage on upload/deploy. References inside the site's HTML/CSS
    | are rewritten to the cloud delivery URL. Set MEDIA_OFFLOAD_ENABLED=false
    | to keep everything in the code bundle.
    */

    'offload' => [
        'enabled' => env('MEDIA_OFFLOAD_ENABLED', true),

        'threshold_kb' => (int) env('MEDIA_OFFLOAD_THRESHOLD_KB', 512),

        // Media categories eligible for automatic offloading.
        'types' => ['image', 'video'],
    ],

];
