<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;

// Ana sayfa
Route::get('/', function () {
    Log::info('Ana sayfa endpoint\'i çağrıldı');
    return response()->json([
        'message' => 'Gocamania API',
        'status' => 'running',
        'timestamp' => now()->toDateTimeString()
    ]);
});

// Test endpoint'i
Route::get('/test', function () {
    Log::info('Test endpoint\'i çağrıldı');
    return response()->json([
        'message' => 'Test endpoint çalışıyor',
        'php_version' => PHP_VERSION,
        'laravel_version' => app()->version(),
        'environment' => app()->environment(),
        'debug' => config('app.debug'),
        'timestamp' => now()->toDateTimeString()
    ]);
});

// Health check endpoint'i
Route::get('/health', function () {
    Log::info('Health check endpoint\'i çağrıldı');
    return response()->json([
        'status' => 'healthy',
        'timestamp' => now()->toDateTimeString()
    ]);
});
