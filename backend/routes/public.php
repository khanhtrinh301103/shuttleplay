<?php
// File location: backend/routes/public.php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
|
| Các routes public không cần authentication
| Ví dụ: health check, database test, status, etc.
|
*/

// Route test kết nối database
Route::get('/db-test', function() { 
    try {
        $version = DB::selectOne('select version() as v')->v;
        return response()->json([
            'connected'     => true,
            'pgsql_version' => $version,
            'status'        => 'Database connection successful'
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'connected' => false,
            'error' => $e->getMessage(),
            'status' => 'Database connection failed'
        ], 500);
    }
});

// Health check endpoint
Route::get('/health', function() {
    return response()->json([
        'status' => 'OK',
        'service' => 'ShuttlePlay API',
        'timestamp' => now()->toISOString()
    ]);
});

// API version info
Route::get('/version', function() {
    return response()->json([
        'api_version' => '1.0.0',
        'laravel_version' => app()->version(),
        'php_version' => PHP_VERSION
    ]);
});