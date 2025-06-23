<?php
// File location: backend/routes/cart.php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CartController;

/*
|--------------------------------------------------------------------------
| Shopping Cart Routes
|--------------------------------------------------------------------------
|
| Routes cho quản lý giỏ hàng của customer
| Tất cả routes đều yêu cầu authentication và role customer
|
*/

Route::middleware('auth:sanctum')->group(function () {
    
    /*
    |--------------------------------------------------------------------------
    | Customer Cart Routes
    |--------------------------------------------------------------------------
    */
    
    // Chỉ customer mới được truy cập cart
    Route::middleware(['role:customer'])->group(function () {
        
        /*
        |--------------------------------------------------------------------------
        | Basic Cart Operations
        |--------------------------------------------------------------------------
        */
        
        // Lấy giỏ hàng hiện tại với tất cả items
        Route::get('/cart', [CartController::class, 'index']);
        
        // Thêm sản phẩm vào giỏ hàng
        Route::post('/cart/add', [CartController::class, 'addToCart']);
        
        // Cập nhật số lượng item trong giỏ hàng
        Route::put('/cart/items/{cartItemId}', [CartController::class, 'updateCartItem'])
            ->where('cartItemId', '[0-9]+');
        
        // Xóa item khỏi giỏ hàng
        Route::delete('/cart/items/{cartItemId}', [CartController::class, 'removeFromCart'])
            ->where('cartItemId', '[0-9]+');
        
        // Xóa tất cả items trong giỏ hàng
        Route::delete('/cart/clear', [CartController::class, 'clearCart']);
        
        /*
        |--------------------------------------------------------------------------
        | Cart Information & Utilities
        |--------------------------------------------------------------------------
        */
        
        // Lấy số lượng items trong giỏ hàng (quick check cho header/badge)
        Route::get('/cart/count', [CartController::class, 'getCartCount']);
        
        // Validate và clean up giỏ hàng (xóa items không available)
        Route::post('/cart/validate', [CartController::class, 'validateCart']);
        
        // Lấy thống kê giỏ hàng
        Route::get('/cart/stats', [CartController::class, 'getCartStats']);
        
        /*
        |--------------------------------------------------------------------------
        | Guest Cart Integration
        |--------------------------------------------------------------------------
        */
        
        // Merge guest cart với user cart (dùng khi user login)
        Route::post('/cart/merge-guest', [CartController::class, 'mergeGuestCart']);
        
    });

});

/*
|--------------------------------------------------------------------------
| API Documentation
|--------------------------------------------------------------------------
*/

/*
🛒 SHOPPING CART API ENDPOINTS:

1. GET /api/cart
   - Lấy giỏ hàng hiện tại với tất cả items và product details
   - Response: CartResource với items, summary, status

2. POST /api/cart/add
   - Thêm sản phẩm vào giỏ hàng
   - Body: {product_id: int, quantity: int}
   - Logic: Nếu product đã có thì tăng quantity, nếu chưa thì tạo mới

3. PUT /api/cart/items/{cartItemId}
   - Cập nhật số lượng item
   - Body: {quantity: int}
   - Logic: quantity = 0 sẽ xóa item

4. DELETE /api/cart/items/{cartItemId}
   - Xóa item khỏi giỏ hàng

5. DELETE /api/cart/clear
   - Xóa tất cả items trong giỏ hàng

6. GET /api/cart/count
   - Lấy tổng số items trong cart (cho header badge)
   - Response: {total_items: int, cart_id: int}

7. POST /api/cart/validate
   - Validate cart và clean up items không available
   - Response: cart + danh sách items bị xóa/cập nhật

8. GET /api/cart/stats
   - Lấy thống kê chi tiết về cart
   - Response: total amount, average price, sellers count, etc.

9. POST /api/cart/merge-guest
   - Merge guest cart với user cart (khi user login)
   - Body: {guest_cart_items: [{product_id, quantity}]}

EXAMPLE USAGE:

// 1. Thêm sản phẩm vào cart
POST /api/cart/add
{
    "product_id": 123,
    "quantity": 2
}

// 2. Cập nhật số lượng
PUT /api/cart/items/456
{
    "quantity": 3
}

// 3. Lấy cart với đầy đủ thông tin
GET /api/cart

// 4. Lấy số lượng items cho header
GET /api/cart/count

// 5. Validate cart trước khi checkout
POST /api/cart/validate

FEATURES:
✅ Auto-create cart khi user add item đầu tiên
✅ Stock validation khi add/update
✅ Prevent buying own products
✅ Clean up unavailable items
✅ Support merge guest cart
✅ Comprehensive error handling
✅ Rich product information in responses
✅ Price calculation with quantity
✅ Image optimization with multiple sizes
✅ Cart statistics and summary

*/