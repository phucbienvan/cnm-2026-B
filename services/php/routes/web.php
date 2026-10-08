<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/pasts', function () {
    return response()->json([
        'message' => 'Hello, World!',
    ]);
});

Route::get('/products', function () {
    return response()->json([
        'message' => 'Danh sách sản phẩm',
    ]);
});
