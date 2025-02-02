<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\FileController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Auth routes
Route::prefix('auth')->group(function () {
    Route::get('google', [GoogleAuthController::class, 'redirectToGoogle']);
    Route::get('google/callback', [GoogleAuthController::class, 'handleGoogleCallback']);
    
    // Protected routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', [GoogleAuthController::class, 'logout']);
    });
});

// File upload routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('upload/image', [FileController::class, 'uploadImage']);
    Route::post('upload/images', [FileController::class, 'uploadMultipleImages']);
    Route::delete('upload/image', [FileController::class, 'deleteImage']);
});

// Protected API routes
Route::middleware('auth:sanctum')->group(function () {
    // User routes
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // Admin routes
    Route::middleware(['role:admin'])->prefix('admin')->group(function () {
        // Admin route'ları buraya eklenecek
    });

    // Store routes
    Route::middleware(['role:store'])->prefix('store')->group(function () {
        // Mağaza route'ları buraya eklenecek
    });
});
