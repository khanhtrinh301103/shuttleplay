<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Route bảo mật (chỉ ví dụ)
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Route BFF thử nghiệm
Route::get('/bff', function () {
    return response()->json([
        'message' => 'BFF is alive!',
    ]);
});

