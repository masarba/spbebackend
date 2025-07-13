<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Auth\LoginController; 
use App\Http\Controllers\Api\Auth\LogoutController;
use App\Http\Controllers\Api\Auth\RegisterController;
use App\Http\Controllers\Api\Auth\ChangePasswordController; 
use App\Http\Controllers\Api\Auth\Setup2FAController; 
use App\Http\Controllers\Api\Auth\UserController; 
use App\Http\Controllers\Api\Auth\Verify2FAController;

Route::get('/', function () {
    return view('welcome');
});

// Rute untuk otentikasi
Route::prefix('auth')->group(function() {
    Route::post('/login', [LoginController::class, '__invoke'])->middleware('guest');
    Route::post('/logout', [LogoutController::class, '__invoke'])->middleware('auth:sanctum'); 
    Route::post('/register', [RegisterController::class, '__invoke']);
});

// Rute untuk mengganti password (untuk pengguna)
Route::middleware('auth:sanctum')->post('/change-password', [ChangePasswordController::class, 'changePassword']); 

// Rute untuk pengaturan 2FA (untuk pengguna)
Route::middleware('auth:sanctum')->post('/setup-2fa', [Setup2FAController::class, 'setup']); 

Route::middleware('auth:sanctum')->post('/verify-2fa', [Verify2FAController::class, 'setup']); 
// Rute untuk memeriksa apakah ini adalah login pertama
Route::middleware('auth:sanctum')->get('/user/first-login', [UserController::class, 'checkFirstLogin']); 
