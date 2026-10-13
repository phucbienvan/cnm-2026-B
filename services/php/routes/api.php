<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\AuthController;

Route::post('register' , [AuthController::class , 'register']);
Route::get('/email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])
    ->middleware(['signed'])
    ->name('verification.verify');

Route::post('login' , [AuthController::class , 'login']);
Route::post('forgot-password' , [AuthController::class , 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.reset');
Route::post('logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');

Route::get('users' , [AuthController::class , 'getUser'])->middleware('auth:sanctum');

Route::get('/products' , [ProductController::class , 'index']);
Route::post('/products' , [ProductController::class , 'store']);

Route::get('/products/{product}' , [ProductController::class , 'show'])->missing(function () {
    return response()->json([
        'message' => 'product not found'
    ], 404);
});
Route::match(['put', 'patch'], '/products/{product}', [ProductController::class, 'update'])->missing(function () {
    return response()->json([
        'message' => 'product not found'
    ], 404);
});
Route::delete('/products/{product}' , [ProductController::class , 'destroy'])->missing(function () {
    return response()->json([
        'message' => 'product not found'
    ], 404);
});
