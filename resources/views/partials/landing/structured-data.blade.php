@php
    // Structured data for the landing page (assembled in PHP to keep Blade
    // from interpreting "@context" and friends as directives).
    $appUrl = rtrim(config('app.url') ?: url('/'), '/');

    $structuredData = [
        '@context' => 'https://schema.org',
        '@graph' => [
            [
                '@type' => 'WebApplication',
                'name' => config('app.name', 'DropHTML'),
                'url' => $appUrl,
                'applicationCategory' => 'WebApplication',
                'operatingSystem' => 'Any',
                'description' => 'Instant static site hosting. Drag & drop HTML or ZIP files to publish a live website in seconds.',
                'image' => $appUrl.'/og.png',
                'offers' => [
                    '@type' => 'Offer',
                    'price' => '0',
                    'priceCurrency' => 'EUR',
                ],
            ],
            [
                '@type' => 'FAQPage',
                'mainEntity' => [
                    [
                        '@type' => 'Question',
                        'name' => 'Do I need an account to deploy?',
                        'acceptedAnswer' => [
                            '@type' => 'Answer',
                            'text' => 'No. Drop an HTML file or ZIP archive to publish as a guest. Guest sites expire after 24 hours. Create a free account to manage your sites and choose permanent hosting.',
                        ],
                    ],
                    [
                        '@type' => 'Question',
                        'name' => 'What can I upload?',
                        'acceptedAnswer' => [
                            '@type' => 'Answer',
                            'text' => 'Upload a single .html or .htm file, or a .zip archive up to 50 MB. For a complete website, include your HTML, CSS, JavaScript, and assets in the ZIP, with an index.html as your entry page. Server-side code such as PHP is not supported.',
                        ],
                    ],
                    [
                        '@type' => 'Question',
                        'name' => 'Can I use my own domain?',
                        'acceptedAnswer' => [
                            '@type' => 'Answer',
                            'text' => 'Yes. With an account, connect your custom domain from the dashboard. You can also protect client previews with a password and deploy updates from GitHub.',
                        ],
                    ],
                    [
                        '@type' => 'Question',
                        'name' => 'What about images, videos, and other media?',
                        'acceptedAnswer' => [
                            '@type' => 'Answer',
                            'text' => 'Bundle assets with your website, or use dedicated cloud media storage to host images, videos, and PDFs separately. Keep your site uploads small while managing your media from the dashboard.',
                        ],
                    ],
                ],
            ],
        ],
    ];
@endphp

<script type="application/ld+json">
    {!! json_encode($structuredData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) !!}
</script>
