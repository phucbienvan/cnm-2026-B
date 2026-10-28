<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\AuthController;

// Auth: không cần đăng nhập
Route::controller(AuthController::class)->group(function () {
    Route::post('register', 'register');
    Route::post('verify-code', 'verifyCode');
    Route::post('resend-code', 'resendCode')->middleware('throttle:1,0.25');
    Route::post('login', 'login');
});

// Auth: cần đăng nhập
Route::middleware('auth:sanctum')->controller(AuthController::class)->group(function () {
    Route::post('logout', 'logout');
    Route::get('users', 'getUser');
});

$productNotFound = function () {
    return response()->json([
        'message' => 'product not found'
    ], 404);
};

Route::prefix('products')->controller(ProductController::class)->group(function () use ($productNotFound) {
    // Công khai
    Route::get('/', 'index');
    Route::get('/{product}', 'show')->missing($productNotFound);

    // Cần đăng nhập
    Route::middleware('auth:sanctum')->group(function () use ($productNotFound) {
        Route::post('/', 'store');
        Route::put('/{product}', 'update');
        Route::delete('/{product}', 'destroy')->missing($productNotFound);
    });
});