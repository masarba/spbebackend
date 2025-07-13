<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Auth\TwoFactorController;

// 2FA routes
Route::prefix('auth')->group(function () {
    // Route that doesn't require auth
    Route::post('verify-2fa', [TwoFactorController::class, 'verify']);
    
    Route::middleware(['jwt.auth'])->group(function () {
        Route::get('setup-2fa', [TwoFactorController::class, 'setup']);
        Route::post('activate-2fa', [TwoFactorController::class, 'activate']);
        Route::post('disable-2fa', [TwoFactorController::class, 'disable']);
    });
});
