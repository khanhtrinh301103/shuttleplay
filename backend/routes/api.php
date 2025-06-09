<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;      // ← Phải có dòng này!
use App\Http\Controllers\Api\UserController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Các route trong file này sẽ tự động có prefix /api
| và middleware "api" do Laravel cấu hình sẵn.
|
*/

// Ví dụ lấy thông tin user đã auth
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Route test kết nối database
Route::get('/db-test', function() {
    $version = DB::selectOne('select version() as v')->v;
    return response()->json([
        'connected'     => true,
        'pgsql_version' => $version,
    ]);
});

// Một số BFF endpoints ví dụ
Route::get('/bff/seller-products', function() {
    return response()->json(DB::table('vw_seller_products')->get());
});
Route::get('/bff/customer-orders/{userId}', function($userId) {
    return response()->json(
        DB::table('vw_customer_orders')->where('customer_id', $userId)->get()
    );
});

// Ví dụ resource route cho UserController
Route::apiResource('users', UserController::class);
