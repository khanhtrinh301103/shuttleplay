<?php
// File location: backend/routes/api.php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| File này chỉ là entry point chính, include các route files khác
| Các route sẽ tự động có prefix /api và middleware "api"
|
*/

// Include Authentication routes
require __DIR__.'/auth.php';

// Include Public routes  
require __DIR__.'/public.php';

// Include Customer Product routes (Public API cho customer xem sản phẩm)
require __DIR__.'/customer-products.php';

// Include Product routes (Protected API cho seller quản lý sản phẩm)
require __DIR__.'/products.php';

// Include Image routes (Protected API cho seller quản lý hình ảnh)
require __DIR__.'/images.php';

// 🆕 Include Cart routes (Protected API cho customer quản lý giỏ hàng)
require __DIR__.'/cart.php';

/*
|--------------------------------------------------------------------------
| Fallback Route
|--------------------------------------------------------------------------
*/

Route::fallback(function(){
    return response()->json([
        'message' => 'Route not found. Please check the URL and try again.'
    ], 404);
});