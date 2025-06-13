<?php
// File location: backend/routes/products.php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductController;

/*
|--------------------------------------------------------------------------
| Product Routes
|--------------------------------------------------------------------------
|
| Routes cho quản lý sản phẩm của seller
|
*/

/*
|--------------------------------------------------------------------------
| Public Product Routes (Không cần authentication)
|--------------------------------------------------------------------------
*/

// Lấy danh sách categories (dùng cho dropdown khi tạo sản phẩm)
Route::get('/categories', [ProductController::class, 'getCategories']);

/*
|--------------------------------------------------------------------------
| Protected Product Routes (Cần authentication)
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function () {
    
    /*
    |--------------------------------------------------------------------------
    | Seller Product Management Routes
    |--------------------------------------------------------------------------
    */
    
    // Chỉ seller mới được truy cập những routes này
    Route::middleware(['role:seller'])->group(function () {
        
        // CRUD sản phẩm của seller
        Route::get('/seller/products', [ProductController::class, 'index']); // Lấy danh sách sản phẩm của seller
        Route::post('/seller/products', [ProductController::class, 'store']); // Tạo sản phẩm mới
        Route::get('/seller/products/{id}', [ProductController::class, 'show']); // Xem chi tiết sản phẩm
        Route::put('/seller/products/{id}', [ProductController::class, 'update']); // Cập nhật sản phẩm
        Route::delete('/seller/products/{id}', [ProductController::class, 'destroy']); // Xóa sản phẩm
        
        // Toggle publish/unpublish sản phẩm
        Route::patch('/seller/products/{id}/toggle-publish', [ProductController::class, 'togglePublish']);
        
    });

});