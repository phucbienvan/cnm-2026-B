<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

Route::post('register', [AuthController::class, 'register'])->middleware('throttle:5,1');
Route::post('verify-email', [AuthController::class, 'verifyEmail'])->middleware('throttle:5,1');
Route::post('verify-code', [AuthController::class, 'verifyEmail'])->middleware('throttle:5,1');
Route::post('resend-verification-code', [AuthController::class, 'resendVerificationCode'])->middleware('throttle:5,1');
Route::post('resend-code', [AuthController::class, 'resendVerificationCode'])->middleware('throttle:5,1');
Route::post('forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:5,1');
Route::post('reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:5,1');
Route::post('login', [AuthController::class, 'login'])->middleware('throttle:5,1');
Route::get('users', [AuthController::class, 'getUser'])->middleware('auth:sanctum');
Route::post('logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');

Route::get('/products', [ProductController::class, 'index']);
Route::post('/products', [ProductController::class, 'store']);

Route::get('/products/{product}', [ProductController::class, 'show'])->missing(function () {
    return response()->json([
        'message' => 'product not found',
    ], 404);
});
Route::delete('/products/{product}', [ProductController::class, 'destroy'])->missing(function () {
    return response()->json([
        'message' => 'product not found',
    ], 404);
});
Route::put('/products/{product}', [ProductController::class, 'update']);
