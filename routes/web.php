<?php

use App\Http\Controllers\Auth\SocialiteController;
use App\Http\Controllers\SiteController;
use App\Http\Controllers\Webhooks\GithubWebhookController;
use App\Models\Deployment;
use App\Models\Site;
use App\Services\AnalyticsService;
use App\Services\SiteDeploymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

$host = parse_url(config('app.url'), PHP_URL_HOST);
$escapedHost = $host ? preg_quote($host, '/') : 'drophtml\.de';
$appDomain = $host ?: 'localhost';

// 1. Subdomain routing for {slug}.drophtml.de
if ($host && $host !== 'localhost') {
    Route::domain('{slug}.'.$host)->group(function () {
        Route::get('/{path?}', [SiteController::class, 'showSubdomain'])->where('path', '.*');
        Route::post('/_unlock', [SiteController::class, 'unlockSubdomain'])->middleware('throttle:site-unlock')->name('site.subdomain.unlock');
    });
}

// 2. Custom domains routing (matches any non-app host domain)
Route::domain('{custom_domain}')->where(['custom_domain' => '^(?!localhost$|127\.0\.0\.1$|'.$escapedHost.'$|.*\.'.$escapedHost.'$).*'])->group(function () {
    Route::get('/{path?}', [SiteController::class, 'showCustomDomain'])->where('path', '.*');
    Route::post('/_unlock', [SiteController::class, 'unlockCustomDomain'])->middleware('throttle:site-unlock')->name('site.custom_domain.unlock');
});

// 3. Main App routes (scoped to app domain or localhost)
Route::domain($appDomain)->group(function () {
    Route::get('/', function () {
        return view('welcome', [
            'totalSites' => Site::count(),
        ]);
    })->name('home');

    Route::get('/legal/{page}', function ($page) {
        if (! in_array($page, ['privacy', 'terms', 'imprint'])) {
            abort(404);
        }

        return view('pages.legal.show', ['page' => $page]);
    })->name('legal.show');

    Route::get('/s/{slug}/{path?}', [SiteController::class, 'show'])->where('path', '.*');
    Route::post('/s/{slug}/_unlock', [SiteController::class, 'unlock'])->middleware('throttle:site-unlock')->name('site.unlock');

    Route::get('/auth/{provider}/redirect', [SocialiteController::class, 'redirect'])->name('social.redirect');
    Route::get('/auth/{provider}/callback', [SocialiteController::class, 'callback'])->name('social.callback');

    Route::post('/webhooks/github', [GithubWebhookController::class, 'handle'])->middleware('throttle:webhooks')->name('webhooks.github');

    Route::middleware(['auth', 'verified'])->group(function () {
        Route::view('dashboard', 'dashboard')->name('dashboard');
        Route::view('dashboard/sites/upload', 'dashboard.sites-upload')->name('dashboard.sites.upload');
        Route::view('dashboard/sites/{site}/edit', 'dashboard.site-editor')->name('dashboard.sites.edit');
        Route::view('dashboard/sites/{site}/domains', 'dashboard.site-domains')->name('dashboard.sites.domains');
        Route::view('dashboard/sites/{site}/deployments', 'dashboard.site-deployments')->name('dashboard.sites.deployments');
        Route::get('dashboard/sites/{site}/deployments/{deployment}/download', function (Site $site, Deployment $deployment, SiteDeploymentService $deploymentService) {
            if ($site->user_id !== auth()->id() || $deployment->site_id !== $site->id) {
                abort(403);
            }

            return $deploymentService->downloadDeploymentZip($site, $deployment);
        })->name('dashboard.sites.deployments.download');
        Route::view('dashboard/sites/{site}/stats', 'dashboard.site-stats')->name('dashboard.sites.stats');
        Route::get('dashboard/sites/{site}/stats/export', function (Site $site, Request $request, AnalyticsService $analytics) {
            if ($site->user_id !== auth()->id()) {
                abort(403);
            }
            $days = (int) $request->query('days', 30);

            return $analytics->exportCsv($site, $days);
        })->name('dashboard.sites.stats.export');
        Route::get('dashboard/sites/{site}/download', function (Site $site, SiteDeploymentService $deploymentService) {
            if ($site->user_id !== auth()->id()) {
                abort(403);
            }

            return $deploymentService->downloadZip($site);
        })->name('dashboard.sites.download');
        Route::view('dashboard/media', 'dashboard.media')->name('dashboard.media');
        Route::view('dashboard/sites/{site}/media', 'dashboard.site-media')->name('dashboard.sites.media');
        Route::view('dashboard/sites/{site}/settings', 'dashboard.site-settings')->name('dashboard.sites.settings');
        Route::view('dashboard/activity', 'dashboard.activity')->name('dashboard.activity');
    });

    require __DIR__.'/settings.php';
});
