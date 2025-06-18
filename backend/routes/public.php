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
| Category Routes (New - Added for Customer Features)
|--------------------------------------------------------------------------
*/

// Lấy danh sách tất cả categories
Route::get('/categories', [CategoryController::class, 'index']);

// Lấy chi tiết category theo slug
Route::get('/categories/{slug}', [CategoryController::class, 'show'])
    ->where('slug', '[a-z0-9\-]+');

// Lấy danh sách categories có sản phẩm (for navigation)
Route::get('/categories/active', [CategoryController::class, 'getActiveCategories']);

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

    // Thêm route debug này vào cuối file public.php (TEMPORARY)

    Route::get('/debug-categories', function() {
        try {
            // 1. Check total categories
            $totalCategories = \App\Models\Category::count();
            
            // 2. Check total published products
            $totalPublishedProducts = \App\Models\Product::where('published', true)->where('stock_qty', '>', 0)->count();
            
            // 3. Check categories with products (manual query)
            $categoriesWithProducts = \App\Models\Category::whereHas('products', function($query) {
                $query->where('published', true)->where('stock_qty', '>', 0);
            })->with(['products' => function($query) {
                $query->where('published', true)->where('stock_qty', '>', 0);
            }])->get();
            
            // 4. Check each category's products count
            $categoryDetails = \App\Models\Category::all()->map(function($category) {
                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                    'total_products' => $category->products()->count(),
                    'published_products' => $category->products()->where('published', true)->where('stock_qty', '>', 0)->count(),
                ];
            });

            return response()->json([
                'debug_info' => [
                    'total_categories' => $totalCategories,
                    'total_published_products' => $totalPublishedProducts,
                    'categories_with_products_count' => $categoriesWithProducts->count(),
                    'categories_with_products' => $categoriesWithProducts,
                    'category_details' => $categoryDetails,
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    });
});