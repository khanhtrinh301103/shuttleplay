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
| Thiết kế mới: Tách riêng Main Image và Secondary Images
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
        
        /*
        |--------------------------------------------------------------------------
        | 🆕 MAIN IMAGE ROUTES (1 ảnh chính duy nhất)
        |--------------------------------------------------------------------------
        */
        
        // Upload/Replace ảnh chính cho sản phẩm (1 ảnh duy nhất)
        Route::post('/seller/products/{productId}/main-image', [ProductImageController::class, 'uploadMainImage']);
        
        /*
        |--------------------------------------------------------------------------
        | 🆕 SECONDARY IMAGES ROUTES (nhiều ảnh phụ)
        |--------------------------------------------------------------------------
        */
        
        // Upload ảnh phụ cho sản phẩm (nhiều ảnh)
        Route::post('/seller/products/{productId}/secondary-images', [ProductImageController::class, 'uploadSecondaryImages']);
        
        /*
        |--------------------------------------------------------------------------
        | GENERAL IMAGE MANAGEMENT ROUTES
        |--------------------------------------------------------------------------
        */
        
        // Lấy tất cả ảnh của sản phẩm (main + secondary)
        Route::get('/seller/products/{productId}/images', [ProductImageController::class, 'getProductImages']);
        
        // Xóa một image cụ thể (main hoặc secondary)
        Route::delete('/seller/products/{productId}/images/{imageId}', [ProductImageController::class, 'deleteImage']);
        
        // Đặt ảnh phụ thành ảnh chính (promote secondary to main)
        Route::patch('/seller/products/{productId}/images/{imageId}/set-main', [ProductImageController::class, 'setMainImage']);
        
        // Lấy các URL biến đổi của ảnh (thumbnail, medium, large)
        Route::get('/seller/products/{productId}/images/{imageId}/transformations', [ProductImageController::class, 'getTransformedUrls']);
        
    });

});

/*
|--------------------------------------------------------------------------
| API Documentation
|--------------------------------------------------------------------------
*/

/*
🆕 NEW IMAGE API ARCHITECTURE:

1. MAIN IMAGE API:
   POST /api/seller/products/{productId}/main-image
   - Body: form-data với field "main_image" (single file)
   - Purpose: Upload/Replace ảnh chính duy nhất
   - Logic: Nếu đã có main image → replace, nếu chưa → create new

2. SECONDARY IMAGES API:
   POST /api/seller/products/{productId}/secondary-images  
   - Body: form-data với field "secondary_images[]" (multiple files)
   - Purpose: Upload nhiều ảnh phụ
   - Logic: Tất cả đều is_main = false

3. GET IMAGES:
   GET /api/seller/products/{productId}/images
   - Response: {main_image: object, secondary_images: array}

4. DELETE IMAGE:
   DELETE /api/seller/products/{productId}/images/{imageId}
   - Logic: Nếu xóa main image → promote secondary image đầu tiên thành main

5. PROMOTE TO MAIN:
   PATCH /api/seller/products/{productId}/images/{imageId}/set-main
   - Logic: Chuyển secondary image thành main image

EXAMPLE USAGE:

// 1. Upload main image
POST /api/seller/products/123/main-image
Content-Type: multipart/form-data
Authorization: Bearer TOKEN

Form data:
- main_image: [file]

// 2. Upload secondary images  
POST /api/seller/products/123/secondary-images
Content-Type: multipart/form-data
Authorization: Bearer TOKEN

Form data:
- secondary_images[]: [file1]
- secondary_images[]: [file2] 
- secondary_images[]: [file3]

// 3. Get all images
GET /api/seller/products/123/images

// 4. Delete an image
DELETE /api/seller/products/123/images/456

// 5. Promote secondary to main
PATCH /api/seller/products/123/images/456/set-main

*/