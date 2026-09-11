<?php

use App\Http\Controllers\Api\ActivityLogController;
use App\Http\Controllers\Api\DeploymentController;
use App\Http\Controllers\Api\DomainController;
use App\Http\Controllers\Api\MediaAssetController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\SiteController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {
    // Profile
    Route::get('/user', [ProfileController::class, 'show']);
    Route::get('/user/storage', [ProfileController::class, 'storage']);
    Route::put('/user', [ProfileController::class, 'update']);
    Route::put('/user/password', [ProfileController::class, 'updatePassword']);
    Route::delete('/user', [ProfileController::class, 'destroy']);

    // Sites
    Route::post('sites/{site}/deploy', [SiteController::class, 'deploy']);
    Route::post('sites/{site}/duplicate', [SiteController::class, 'duplicate']);
    Route::get('sites/{site}/download', [SiteController::class, 'download']);
    Route::apiResource('sites', SiteController::class);

    // Domains (Nested under sites)
    Route::apiResource('sites.domains', DomainController::class)->except(['update']);

    // Deployments (Nested under sites)
    Route::post('sites/{site}/deployments/{deployment}/rollback', [DeploymentController::class, 'rollback']);
    Route::get('sites/{site}/deployments/{deployment}/download', [DeploymentController::class, 'download']);
    Route::apiResource('sites.deployments', DeploymentController::class)->only(['index', 'show']);

    // Activity Logs (Read-only)
    Route::apiResource('activity-logs', ActivityLogController::class)->only(['index', 'show']);

    // Media Assets (S3/R2 Asset Storage & Site Code Storage)
    Route::post('media/{media}/migrate', [MediaAssetController::class, 'migrate']);
    Route::apiResource('media', MediaAssetController::class)->parameters(['media' => 'media']);
});
