<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\StoreManagementController;
use App\Http\Controllers\Admin\ProductManagementController;
use App\Http\Controllers\Admin\ListingManagementController;

Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
    // Dashboard routes
    Route::get('/stats', [DashboardController::class, 'stats']);
    Route::get('/pending-approvals', [DashboardController::class, 'pendingApprovals']);

    // Store management
    Route::prefix('stores')->group(function () {
        Route::get('/', [StoreManagementController::class, 'index']);
        Route::get('/{store}', [StoreManagementController::class, 'show']);
        Route::post('/{store}/approve', [StoreManagementController::class, 'approve']);
        Route::post('/{store}/reject', [StoreManagementController::class, 'reject']);
        Route::put('/{store}', [StoreManagementController::class, 'update']);
        Route::delete('/{store}', [StoreManagementController::class, 'destroy']);
    });

    // Product management
    Route::prefix('products')->group(function () {
        Route::get('/', [ProductManagementController::class, 'index']);
        Route::get('/{product}', [ProductManagementController::class, 'show']);
        Route::post('/{product}/approve', [ProductManagementController::class, 'approve']);
        Route::post('/{product}/reject', [ProductManagementController::class, 'reject']);
        Route::put('/{product}', [ProductManagementController::class, 'update']);
        Route::delete('/{product}', [ProductManagementController::class, 'destroy']);
    });

    // Listing management
    Route::prefix('listings')->group(function () {
        Route::get('/', [ListingManagementController::class, 'index']);
        Route::get('/statistics', [ListingManagementController::class, 'statistics']);
        Route::get('/{listing}', [ListingManagementController::class, 'show']);
        Route::post('/{listing}/approve', [ListingManagementController::class, 'approve']);
        Route::post('/{listing}/reject', [ListingManagementController::class, 'reject']);
        Route::put('/{listing}', [ListingManagementController::class, 'update']);
        Route::delete('/{listing}', [ListingManagementController::class, 'destroy']);
        Route::post('/bulk-approve', [ListingManagementController::class, 'bulkApprove']);
        Route::post('/bulk-reject', [ListingManagementController::class, 'bulkReject']);
    });
});
