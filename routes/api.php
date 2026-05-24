<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/sites', function (Request $request) {
    return \App\Models\Site::where('user_id', $request->user()->id)->get();
})->middleware('auth:sanctum');
