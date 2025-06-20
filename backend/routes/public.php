<?php
// File location: backend/routes/public.php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\CategoryController;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
|
| Các routes public không cần authentication
| Ví dụ: health check, database test, status, etc.
|
*/

/*
|--------------------------------------------------------------------------
| Database & System Routes (Existing)
|--------------------------------------------------------------------------
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

/*
|--------------------------------------------------------------------------
| Category Routes (Fixed Order - Specific routes BEFORE dynamic routes)
|--------------------------------------------------------------------------
*/

// Lấy danh sách tất cả categories
Route::get('/categories', [CategoryController::class, 'index']);

// ⭐ IMPORTANT: Specific routes MUST come BEFORE dynamic routes
// Lấy danh sách categories có sản phẩm (for navigation)
Route::get('/categories/active', [CategoryController::class, 'getActiveCategories']);

// Lấy chi tiết category theo slug (MUST be AFTER /categories/active)
Route::get('/categories/{slug}', [CategoryController::class, 'show'])
    ->where('slug', '[a-z0-9\-]+');

/*
|--------------------------------------------------------------------------
| Enhanced Status Check (Updated)
|--------------------------------------------------------------------------
*/

// API status with database connectivity check và endpoint listing
Route::get('/status', function () {
    try {
        // Test database connection
        \DB::connection()->getPdo();
        $dbStatus = 'connected';
    } catch (\Exception $e) {
        $dbStatus = 'disconnected';
    }

    return response()->json([
        'status' => 'ok',
        'service' => 'ShuttlePlay API',
        'version' => '1.0.0',
        'timestamp' => now()->toISOString(),
        'environment' => app()->environment(),
        'database' => $dbStatus,
        'endpoints' => [
            'auth' => '/api/login, /api/register',
            'products' => '/api/products',
            'categories' => '/api/categories',
            'search' => '/api/search/products',
            'system' => '/api/health, /api/db-test, /api/version'
        ]
    ]);
});