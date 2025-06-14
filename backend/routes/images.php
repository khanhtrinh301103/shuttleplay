<?php
// File location: backend/routes/images.php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductImageController;

/*
|--------------------------------------------------------------------------
| Product Image Routes
|--------------------------------------------------------------------------
|
| Routes cho quản lý hình ảnh sản phẩm với Cloudinary
|
*/

Route::middleware('auth:sanctum')->group(function () {
    
    /*
    |--------------------------------------------------------------------------
    | Seller Image Management Routes
    |--------------------------------------------------------------------------
    */
    
    // Chỉ seller mới được truy cập những routes này
    Route::middleware(['role:seller'])->group(function () {
        
        // Upload images cho sản phẩm
        Route::post('/seller/products/{productId}/images', [ProductImageController::class, 'uploadImages']);
        
        // Xóa một image của sản phẩm
        Route::delete('/seller/products/{productId}/images/{imageId}', [ProductImageController::class, 'deleteImage']);
        
        // Đặt ảnh chính cho sản phẩm
        Route::patch('/seller/products/{productId}/images/{imageId}/set-main', [ProductImageController::class, 'setMainImage']);
        
        // Sắp xếp lại thứ tự ảnh
        Route::patch('/seller/products/{productId}/images/reorder', [ProductImageController::class, 'reorderImages']);
        
        // Lấy các URL biến đổi của ảnh (thumbnail, medium, large)
        Route::get('/seller/products/{productId}/images/{imageId}/transformations', [ProductImageController::class, 'getTransformedUrls']);
        
    });

});