<?php
namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use PragmaRX\Google2FALaravel\Facade as Google2FA;

class Verify2FAController extends Controller
{
    public function verifyTwoFactorAuth(Request $request)
    {
        // Log the incoming request for debugging
        \Log::info('Verify2FAController - 2FA Verification Request:', [
            'data' => $request->all()
        ]);
        
        // Validate incoming request
        $validator = Validator::make($request->all(), [
            'otp' => 'required|string|size:6',
            'email' => 'required|email',
            'google2fa_secret' => 'required|string',
        ]);

        if ($validator->fails()) {
            \Log::error('Verify2FAController - Validation Error:', [
                'errors' => $validator->errors()
            ]);
            
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $otp = $request->otp;
        $email = $request->email;
        $google2fa_secret = $request->google2fa_secret;

        // Get user by email
        $user = \App\Models\User::where('email', $email)->first();
        
        if (!$user) {
            return response()->json(['error' => 'User tidak ditemukan'], 404);
        }

        // Verify the OTP
        $isValid = Google2FA::verifyKey($google2fa_secret, $otp);

        if ($isValid) {
            // Generate new token after successful verification
            $token = auth()->guard('jwt')->fromUser($user);
            $refreshToken = \PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth::fromUser($user);
            
            return response()->json([
                'success' => true,
                'message' => 'Verifikasi 2FA berhasil',
                'access_token' => $token,
                'token_type' => 'Bearer',
                'refresh_token' => $refreshToken,
                'expires_in' => auth()->guard('jwt')->factory()->getTTL() * 60,
                'user' => [
                    'id' => $user->id,
                    'email' => $user->email,
                    'role' => $user->role
                ]
            ]);
        } else {
            return response()->json(['error' => 'Kode OTP tidak valid'], 422);
        }
    }
}
