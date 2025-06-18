<?php
// File location: backend/routes/customer-products.php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CustomerProductController;

/*
|--------------------------------------------------------------------------
| Customer Product Routes
|--------------------------------------------------------------------------
|
| Routes cho customer xem sản phẩm (public API - không cần authentication)
|
*/

/*
|--------------------------------------------------------------------------
| Public Product Routes (Không cần authentication)
|--------------------------------------------------------------------------
*/

// Lấy danh sách tất cả sản phẩm với filtering và pagination
Route::get('/products', [CustomerProductController::class, 'index']);

// Lấy chi tiết một sản phẩm
Route::get('/products/{id}', [CustomerProductController::class, 'show'])
    ->where('id', '[0-9]+');

// Lấy sản phẩm theo category slug
Route::get('/categories/{categorySlug}/products', [CustomerProductController::class, 'getByCategory'])
    ->where('categorySlug', '[a-z0-9\-]+');

// Lấy sản phẩm liên quan
Route::get('/products/{productId}/related', [CustomerProductController::class, 'getRelatedProducts'])
    ->where('productId', '[0-9]+');

// Lấy sản phẩm mới nhất
Route::get('/products/latest', [CustomerProductController::class, 'getLatestProducts']);

// Lấy sản phẩm theo seller
Route::get('/sellers/{sellerId}/products', [CustomerProductController::class, 'getBySeller'])
    ->where('sellerId', '[0-9]+');

// Search sản phẩm nâng cao
Route::get('/search/products', [CustomerProductController::class, 'searchProducts']);

/*
|--------------------------------------------------------------------------
| API Documentation Comments
|--------------------------------------------------------------------------
*/

/*
API ENDPOINTS SUMMARY:

1. GET /api/products
   - Lấy danh sách sản phẩm với pagination
   - Query params: search, category_id, min_price, max_price, sort_by, sort_order, per_page
   - Response: products array + pagination info

2. GET /api/products/{id}
   - Lấy chi tiết sản phẩm by ID
   - Response: single product với đầy đủ thông tin

3. GET /api/categories/{categorySlug}/products
   - Lấy sản phẩm theo category slug
   - Query params: search, min_price, max_price, sort_by, sort_order, per_page
   - Response: category info + products array + pagination

4. GET /api/products/{productId}/related
   - Lấy sản phẩm liên quan (cùng category)
   - Query params: limit (default: 8, max: 20)
   - Response: related_products array

5. GET /api/products/latest
   - Lấy sản phẩm mới nhất
   - Query params: limit (default: 10, max: 20)
   - Response: latest_products array

6. GET /api/sellers/{sellerId}/products
   - Lấy sản phẩm theo seller ID
   - Query params: sort_by, sort_order, per_page
   - Response: seller info + products array + pagination

7. GET /api/search/products
   - Search sản phẩm nâng cao
   - Query params: q (required), category_id, min_price, max_price, sort_by, sort_order, per_page
   - Response: search results + applied filters + pagination

EXAMPLE USAGE:

Frontend có thể gọi các API này để:
- Hiển thị trang chủ với sản phẩm mới nhất
- Hiển thị danh sách sản phẩm với filter và search
- Hiển thị chi tiết sản phẩm với hình ảnh và reviews
- Hiển thị sản phẩm theo danh mục
- Hiển thị sản phẩm của một seller cụ thể
- Hiển thị sản phẩm liên quan trên trang chi tiết

*/