<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AuthController;

Route::get('/', function () {
    return view('welcome');
});

Route::get("/users/{user}", [UserController::class, "index"])->middleware('check.cus');

Route::post('/resend-code', [AuthController::class, 'resendCode']);