<?php
// File location: backend/routes/customer-products.php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CustomerProductController;

/*
|--------------------------------------------------------------------------
| Customer Product Routes
|--------------------------------------------------------------------------
|
| Routes cho customer xem sản phẩm (buyers)
| Tất cả routes này đều public hoặc chỉ cần authentication cơ bản
|
*/

/*
|--------------------------------------------------------------------------
| Public Customer Product Routes (Không cần authentication)
|--------------------------------------------------------------------------
*/

// Lấy danh sách tất cả sản phẩm đã publish (có pagination, filter, search)
Route::get('/products', [CustomerProductController::class, 'index']);

// Xem chi tiết một sản phẩm cụ thể
Route::get('/products/{id}', [CustomerProductController::class, 'show']);

// Lấy sản phẩm theo category
Route::get('/categories/{categoryId}/products', [CustomerProductController::class, 'getByCategory']);

// Lấy sản phẩm từ một seller cụ thể
Route::get('/sellers/{sellerId}/products', [CustomerProductController::class, 'getBySeller']);

// Lấy danh sách categories (cho filter dropdown)
Route::get('/categories', [CustomerProductController::class, 'getCategories']);

// Tìm kiếm sản phẩm
Route::get('/products/search', [CustomerProductController::class, 'search']);

/*
|--------------------------------------------------------------------------
| Protected Customer Product Routes (Cần authentication)
|--------------------------------------------------------------------------
| 
| Một số routes có thể cần authentication trong tương lai như:
| - Thêm vào wishlist
| - Xem lịch sử xem sản phẩm
| - Đánh giá sản phẩm
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function () {
    
    // Chỉ customer và admin mới được truy cập những routes này
    // Sử dụng RoleMiddleware có sẵn với variadic parameters
    Route::middleware(['role:customer', 'admin'])->group(function () {
        
        // Có thể thêm các routes cần authentication ở đây trong tương lai
        // Ví dụ:
        // Route::post('/products/{id}/wishlist', [CustomerProductController::class, 'addToWishlist']);
        // Route::delete('/products/{id}/wishlist', [CustomerProductController::class, 'removeFromWishlist']);
        // Route::post('/products/{id}/review', [CustomerProductController::class, 'addReview']);
        
    });

});