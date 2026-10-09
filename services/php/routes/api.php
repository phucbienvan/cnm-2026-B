<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductController;

Route::get('/products' , [ProductController::class , 'index']);
Route::post('/products' , [ProductController::class , 'store']);

Route::get('/products/{product}' , [ProductController::class , 'show'])->missing(function () {
    return response()->json([
        'message' => 'product not found'
    ], 404);
});
Route::delete('/products/{product}' , [ProductController::class , 'destroy'])->missing(function () {
    return response()->json([
        'message' => 'product not found'
    ], 404);
});
Route::put('/products/{product}', [ProductController::class, 'update']);