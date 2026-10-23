<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

Route::controller(AuthController::class)->group(function () {
    Route::post('register', 'register');
    Route::post('verify-code', 'verifyCode');
    Route::post('resend-code', 'resendCode')->middleware('throttle:1,0.25');
    Route::post('login', 'login');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', 'logout');
        Route::get('users', 'getUser');
    });
});

Route::prefix('products')->controller(ProductController::class)->group(function () {
    Route::get('/', 'index');
    Route::get('/{product}', 'show')->missing(function () {
        return response()->json([
            'message' => 'product not found'
        ], 404);
    });

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/', 'store');
        Route::put('/{product}', 'update')->missing(function () {
            return response()->json([
                'message' => 'product not found'
            ], 404);
        });
        Route::delete('/{product}', 'destroy')->missing(function () {
            return response()->json([
                'message' => 'product not found'
            ], 404);
        });
    });
});
