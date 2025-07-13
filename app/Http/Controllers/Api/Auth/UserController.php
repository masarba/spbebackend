<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Illuminate\Validation\ValidationException;
use PragmaRX\Google2FA\Google2FA;

class UserController extends Controller
{
    /**
     * Update user password.
     */
    public function updatePassword(Request $request)
    {
        $user = Auth::user();

        // Validasi input password
        $request->validate([
            'password' => 'required|string|min:8|confirmed',
        ]);

        // Update password pada user
        $user->password = Hash::make($request->password);
        $user->save();

        return response()->json(['message' => 'Password updated successfully.']);
    }

    /**
     * Enable two-factor authentication (2FA) for the user.
     */
    public function enableTwoFactorAuth(Request $request)
    {
        $user = Auth::user();

        // Generate the secret key using Google2FA
        $google2fa = new Google2FA();
        $secret = $google2fa->generateSecretKey();

        // Simpan secret ke user dan tandai bahwa 2FA diaktifkan
        $user->google2fa_secret = $secret;
        $user->is_2fa_enabled = true;
        $user->save();

        // Kembalikan secret yang digunakan untuk membuat QR code
        return response()->json([
            'secret' => $secret,
            'message' => 'Two-factor authentication enabled successfully.'
        ]);
    }

    /**
     * Check if this is the user's first login.
     */
    public function checkFirstLogin()
    {
        $user = Auth::user();

        // Cek apakah ini adalah login pertama kali pengguna
        if ($user->is_first_login) {
            return response()->json(['message' => 'First login'], 200);
        }

        return response()->json(['message' => 'Not first login'], 200);
    }
}
