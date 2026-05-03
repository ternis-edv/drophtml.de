<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\SiteController;

$host = parse_url(config('app.url'), PHP_URL_HOST);

if ($host && $host !== 'localhost') {
    Route::domain('{slug}.' . $host)->group(function () {
        Route::get('/{path?}', [SiteController::class, 'show'])->where('path', '.*');
    });
}

Route::view('/', 'welcome')->name('home');

Route::get('/s/{slug}/{path?}', [SiteController::class, 'show'])->where('path', '.*');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
