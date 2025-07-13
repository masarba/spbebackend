<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;

class AuditorLoginController extends Controller
{
    /**
     * Handle the admin (auditor) login request and generate JWT token.
     */
    public function __invoke(Request $request)
    {
        try {
            // Validate request input
            $request->validate([
                'email' => 'required|email',
                'password' => 'required|string',
            ]);

            // Find user by email
            $user = User::where('email', $request->email)->first();

            // Check if the user exists and has the auditor role
            if (!$user || $user->role !== 'auditor') {
                throw ValidationException::withMessages([
                    'email' => ['Anda tidak memiliki akses sebagai auditor.']
                ]);
            }

            // Validate the password
            if (!Hash::check($request->password, $user->password)) {
                throw ValidationException::withMessages([
                    'email' => ['Email atau password salah.']
                ]);
            }

            // Generate JWT token
            try {
                if (!$token = auth()->guard('jwt')->attempt($request->only('email', 'password'))) {
                    throw ValidationException::withMessages([
                        'email' => ['Email atau password salah.']
                    ]);
                }

                $refreshToken = JWTAuth::fromUser($user);

            } catch (JWTException $e) {
                return response()->json(['error' => 'Token tidak dapat dibuat'], 500);
            }

            // Prepare base response data
            $userData = [
                'access_token' => $token,
                'token_type' => 'Bearer',
                'refresh_token' => $refreshToken,
                'expires_in' => auth()->factory()->getTTL() * 60,
                'role' => $user->role,
                'auditor_id' => $user->id,
            ];

            // For users with 2FA already set up
            if (!empty($user->google2fa_secret)) {
                $userData['google2fa_secret'] = $user->google2fa_secret;
                $userData['redirect_to'] = '/verify-2fa';
                $userData['email'] = $user->email; // Kirim email untuk digunakan di verifikasi 2FA
                
                \Log::info('2FA auditor login:', [
                    'user_id' => $user->id,
                    'redirect_to' => '/verify-2fa'
                ]);
                
                return response()->json($userData);
            }

            // Default response for setting up 2FA
            $userData['redirect_to'] = '/setup-2fa';
            
            \Log::info('Auditor login successful:', [
                'user_id' => $user->id,
                'role' => $user->role,
                'redirect_to' => '/setup-2fa'
            ]);

            return response()->json($userData);
            
        } catch (\Exception $e) {
            \Log::error('Auditor login error:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat login'
            ], 500);
        }
    }
}
