<?php

use App\Http\Controllers\Api\ActivityLogController;
use App\Http\Controllers\Api\DeploymentController;
use App\Http\Controllers\Api\DomainController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\SiteController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    // Profile
    Route::get('/user', [ProfileController::class, 'show']);
    Route::put('/user', [ProfileController::class, 'update']);
    Route::put('/user/password', [ProfileController::class, 'updatePassword']);
    Route::delete('/user', [ProfileController::class, 'destroy']);

    // Sites
    Route::apiResource('sites', SiteController::class);

    // Domains (Nested under sites)
    Route::apiResource('sites.domains', DomainController::class)->except(['update']);

    // Deployments (Nested under sites, Read-only)
    Route::apiResource('sites.deployments', DeploymentController::class)->only(['index', 'show']);

    // Activity Logs (Read-only)
    Route::apiResource('activity-logs', ActivityLogController::class)->only(['index', 'show']);
});
