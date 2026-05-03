<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\SiteController;

Route::domain('{slug}.' . config('app.url_base', 'drophtml.test'))->group(function () {
    Route::get('/{path?}', [SiteController::class, 'show'])->where('path', '.*');
});

Route::view('/', 'welcome')->name('home');

Route::get('/s/{slug}/{path?}', [SiteController::class, 'show'])->where('path', '.*');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
