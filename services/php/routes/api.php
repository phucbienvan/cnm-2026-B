<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\AuthController;

/*-----------------Auth Routes-----------------*/
Route::post('register', [AuthController::class, 'register']);
Route::post('verify-code', [AuthController::class, 'verifyCode']);
Route::post('resend-code', [AuthController::class, 'resendCode'])->middleware('throttle:1,0.25');
Route::post('login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('logout', [AuthController::class, 'logout']);
    Route::get('users', [AuthController::class, 'getUser']);
});

/*-----------------Product Routes-----------------*/
Route::get('/products', [ProductController::class, 'index']);

Route::get('/products/{product}', [ProductController::class, 'show'])->missing(function () {
    return response()->json(['message' => 'product not found'], 404);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/products', [ProductController::class, 'store']);
    Route::put('/products/{product}', [ProductController::class, 'update']);
    Route::delete('/products/{product}', [ProductController::class, 'destroy'])->missing(function () {
        return response()->json(['message' => 'product not found'], 404);
    });
});
