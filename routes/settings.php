<?php

use App\Http\Middleware\ConfirmPasswordIfSet;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Route::livewire('settings/profile', 'pages::settings.profile')->name('profile.edit');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::livewire('settings/appearance', 'pages::settings.appearance')->name('appearance.edit');
    Route::livewire('settings/storage', 'pages::settings.storage')->name('settings.storage');
    Route::livewire('settings/api-tokens', 'pages::settings.api-tokens')->name('api-tokens.edit');

    Route::livewire('settings/security', 'pages::settings.security')
        ->middleware([ConfirmPasswordIfSet::class])
        ->name('security.edit');
});
