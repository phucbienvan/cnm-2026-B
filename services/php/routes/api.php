<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\AuthController;

/*
|--------------------------------------------------------------------------
| Auth Routes (Public) — không cần đăng nhập
|--------------------------------------------------------------------------
*/
Route::prefix('auth')->group(function () {
    Route::post('register',     [AuthController::class, 'register']);
    Route::post('verify-code',  [AuthController::class, 'verifyCode']);
    Route::post('resend-code',  [AuthController::class, 'resendCode']);
    Route::post('login',        [AuthController::class, 'login']);
});

/*
|--------------------------------------------------------------------------
| Auth Routes (Protected) — phải đăng nhập (auth:sanctum)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->prefix('auth')->group(function () {
    Route::post('logout',       [AuthController::class, 'logout']);
    Route::get('users',         [AuthController::class, 'getUser']);
});

/*
|--------------------------------------------------------------------------
| Product Routes (Public) — ai cũng có thể xem
|--------------------------------------------------------------------------
*/
Route::prefix('products')->group(function () {
    Route::get('/',             [ProductController::class, 'index']);
    Route::get('/{product}',    [ProductController::class, 'show'])->missing(function () {
        return response()->json(['message' => 'product not found'], 404);
    });
});

/*
|--------------------------------------------------------------------------
| Product Routes (Protected) — phải đăng nhập mới được tạo/sửa/xóa
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->prefix('products')->group(function () {
    Route::post('/',            [ProductController::class, 'store']);
    Route::put('/{product}',    [ProductController::class, 'update']);

    // Xóa sản phẩm: middleware auth:sanctum đảm bảo đã đăng nhập,
    // logic kiểm tra chính chủ nằm trong ProductController@destroy
    Route::delete('/{product}', [ProductController::class, 'destroy'])->missing(function () {
        return response()->json(['message' => 'product not found'], 404);
    });
});
