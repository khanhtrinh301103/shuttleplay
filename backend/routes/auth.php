<?php
// File location: backend/routes/auth.php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
|
| Tất cả routes liên quan đến authentication: register, login, logout, profile
|
*/

/*
|--------------------------------------------------------------------------
| Public Authentication Routes (Không cần token)
|--------------------------------------------------------------------------
*/

// Đăng ký tài khoản mới
Route::post('/register', [AuthController::class, 'register']);

// Đăng nhập
Route::post('/login', [AuthController::class, 'login']);

/*
|--------------------------------------------------------------------------
| Protected Authentication Routes (Cần token)
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function () {
    
    // Đăng xuất
    Route::post('/logout', [AuthController::class, 'logout']);
    
    // Lấy thông tin user hiện tại
    Route::get('/me', [AuthController::class, 'me']);
    
    // Refresh token
    Route::post('/refresh-token', [AuthController::class, 'refresh']);
    
    // Legacy route - Lấy thông tin user (giữ lại để tương thích)
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    
});