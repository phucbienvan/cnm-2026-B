<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\AuthController;
use Illuminate\Http\JsonResponse;

Route::controller(AuthController::class)->group(function (): void {
    Route::post('register', 'register');
    Route::post('verify-code', 'verifyCode');
    Route::post('resend-code', 'resendCode')->middleware('throttle:1,0.25');
    Route::post('login', 'login');

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::post('logout', 'logout');
        Route::get('users', 'getUser');
    });
});

Route::prefix('products')->controller(ProductController::class)->group(function (): void {
    $productNotFound = function (): JsonResponse {
        return response()->json([
            'message' => 'product not found',
        ], 404);
    };

    Route::get('/', 'index');
    Route::get('{product}', 'show')->missing($productNotFound);

    Route::middleware('auth:sanctum')->group(function () use ($productNotFound): void {
        Route::post('/', 'store');
        Route::put('{product}', 'update');
        Route::delete('{product}', 'destroy')->missing($productNotFound);
    });
});