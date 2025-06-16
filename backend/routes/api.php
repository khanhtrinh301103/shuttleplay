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

// Include Product routes (SELLER)
require __DIR__.'/products.php';

// Include Customer Product routes (CUSTOMER) - 🆕 NEW
require __DIR__.'/customer-products.php';

// Include Image routes
require __DIR__.'/images.php';

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