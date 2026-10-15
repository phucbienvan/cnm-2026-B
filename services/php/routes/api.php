<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\AuthController;

Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);
Route::post('forgot-password', [AuthController::class, 'forgotPassword']);

Route::get('email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])
    ->middleware(['signed'])
    ->name('verification.verify');

Route::get('reset-password/{token}', function (string $token) {
    return response()->json([
        'message' => 'Reset password token',
        'token' => $token
    ]);
})->name('password.reset');

Route::middleware('auth:sanctum')->group(function () {
    Route::get('users', [AuthController::class, 'getUser']);
    Route::post('logout', [AuthController::class, 'logout']);
});

Route::get('/products', [ProductController::class, 'index']);
Route::post('/products', [ProductController::class, 'store']);

Route::get('/products/{product}', [ProductController::class, 'show'])->missing(function () {
    return response()->json([
        'message' => 'product not found'
    ], 404);
});
Route::delete('/products/{product}', [ProductController::class, 'destroy'])->missing(function () {
    return response()->json([
        'message' => 'product not found'
    ], 404);
});
Route::put('/products/{product}', [ProductController::class, 'update']);