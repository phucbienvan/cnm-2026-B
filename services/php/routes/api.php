<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\AuthController;

Route::post('register' , [AuthController::class , 'register']);
Route::post('verify-code' , [AuthController::class , 'verifyCode']);
Route::post('resend-code', [AuthController::class, 'resendCode'])->middleware('throttle:1,0.25');
Route::post('login' , [AuthController::class , 'login']);
Route::post('logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
Route::get('users' , [AuthController::class , 'getUser'])->middleware('auth:sanctum');


Route::prefix('products')->controller(ProductController::class)->group(function () {
    Route::get('/', 'index');
    Route::post('/', 'store')->middleware('auth:sanctum');
    Route::get('/{product}', 'show')->missing(function () {
        return response()->json([
            'message' => 'product not found',
        ], 404);
    });
    Route::put('/{product}', 'update')->middleware('auth:sanctum');
    Route::delete('/{product}', 'destroy')->missing(function () {
        return response()->json([
            'message' => 'product not found',
        ], 404);
    });
});
