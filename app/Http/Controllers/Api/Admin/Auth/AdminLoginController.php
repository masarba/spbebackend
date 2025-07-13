<?php

namespace App\Http\Controllers\Api\Admin\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class AdminLoginController extends Controller
{
    public function __invoke(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $credentials = $request->only('email', 'password');
        
        if (!$token = auth()->guard('jwt')->attempt($credentials)) {
            throw ValidationException::withMessages([
                'email' => ['Kredensial yang diberikan tidak sesuai dengan data kami.'],
            ]);
        }

        $user = auth()->guard('jwt')->user();

        if (!$user->hasRole('admin')) {
            auth()->guard('jwt')->logout();
            throw ValidationException::withMessages([
                'email' => ['Akun ini tidak memiliki akses admin.'],
            ]);
        }

        // Tambahkan logika 2FA
        if (!empty($user->google2fa_secret)) {
            \Log::info('Admin login with 2FA:', [
                'user_id' => $user->id,
                'email' => $user->email,
                'redirect_to' => '/admin/verify-2fa', // Perbarui path redirect
                'has_2fa' => true, // Tambahkan flag untuk debugging
                'time' => now()->toDateTimeString()
            ]);
            
            return response()->json([
                'status' => 'success',
                'message' => 'Login berhasil, 2FA diperlukan',
                'user' => $user,
                'google2fa_secret' => $user->google2fa_secret,
                'email' => $user->email, // Tambahkan email untuk verifikasi 2FA
                'redirect_to' => '/admin/verify-2fa', // Perbarui path redirect sesuai dengan rute di frontend
                'requires_2fa' => true, // Flag eksplisit untuk frontend
                'authorization' => [
                    'token' => $token,
                    'type' => 'bearer',
                ]
            ]);
        }

        // Jika tidak ada 2FA, arahkan untuk setup 2FA
        \Log::info('Admin login without 2FA:', [
            'user_id' => $user->id,
            'email' => $user->email,
            'redirect_to' => '/admin/setup-2fa', // Perbarui path redirect
            'has_2fa' => false, // Flag untuk debugging
            'time' => now()->toDateTimeString()
        ]);
        return response()->json([
            'status' => 'success',
            'message' => 'Login berhasil, perlu setup 2FA',
            'user' => $user,
            'redirect_to' => '/admin/setup-2fa', // Perbarui path redirect sesuai dengan rute di frontend
            'requires_setup_2fa' => true, // Flag eksplisit untuk frontend
            'authorization' => [
                'token' => $token,
                'type' => 'bearer',
            ]
        ]);
    }
} 