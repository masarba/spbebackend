<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use PragmaRX\Google2FA\Google2FA;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class TwoFactorController extends Controller
{
    /**
     * Setup 2FA for the user
     */
    public function setup(Request $request)
    {
        try {
            // Get authenticated user
            $user = Auth::guard('jwt')->user();
            
            if (!$user) {
                return response()->json(['error' => 'Unauthenticated'], 401);
            }

            // Create new Google2FA instance
            $google2fa = new Google2FA();
            
            // Generate a new secret key
            $secretKey = $google2fa->generateSecretKey();
            
            // Generate QR Code URL
            $qrCodeUrl = $google2fa->getQRCodeUrl(
                'SPBE-SCAN', // Application name
                $user->email, // User email/username
                $secretKey   // Secret key
            );
            
            // Generate a full Google Chart API URL for QR code
            $qrCodeUrl = 'https://chart.googleapis.com/chart?cht=qr&chs=300x300&chl=' . urlencode($qrCodeUrl);
            
            // Store the secret key temporarily (not saved to database yet)
            // This will only be saved once the user verifies the code
            $request->session()->put('2fa_secret', $secretKey);
            
            return response()->json([
                'secret' => $secretKey,
                'qr_url' => $qrCodeUrl
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Setup 2FA error:', [
                'user_id' => Auth::id(),
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json(['error' => 'Gagal setup 2FA'], 500);
        }
    }
    
    /**
     * Activate 2FA after setup
     */
    public function activate(Request $request)
    {
        try {
            // Validate request
            $request->validate([
                'otp' => 'required|string|size:6',
                'secret' => 'required|string'
            ]);
            
            // Get authenticated user
            $user = Auth::guard('jwt')->user();
            
            if (!$user) {
                return response()->json(['error' => 'Unauthenticated'], 401);
            }
            
            // Get the secret key
            $secretKey = $request->secret;
            
            // Verify the OTP
            $google2fa = new Google2FA();
            $valid = $google2fa->verifyKey($secretKey, $request->otp);
            
            if (!$valid) {
                return response()->json(['error' => 'Kode OTP tidak valid'], 422);
            }
            
            // Save the secret key to user
            $user->google2fa_secret = $secretKey;
            $user->google2fa_enabled = true;
            $user->save();
            
            return response()->json([
                'success' => true,
                'message' => '2FA berhasil diaktifkan'
            ]);
            
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            \Log::error('Activate 2FA error:', [
                'user_id' => Auth::id(),
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json(['error' => 'Gagal mengaktifkan 2FA'], 500);
        }
    }
    
    /**
     * Verify 2FA code during login
     */
    public function verify(Request $request)
    {
        try {
            // Log the incoming request for debugging
            \Log::info('2FA Verification Request:', [
                'data' => $request->all()
            ]);
            
            // Validate request
            $request->validate([
                'otp' => 'required|string|size:6',
                'email' => 'required|email',
                'google2fa_secret' => 'required|string'
            ]);
            
            // Get user by email
            $user = User::where('email', $request->email)->first();
            
            if (!$user) {
                return response()->json(['error' => 'User tidak ditemukan'], 404);
            }
            
            // Verify the OTP using the passed secret
            $google2fa = new Google2FA();
            $valid = $google2fa->verifyKey($request->google2fa_secret, $request->otp);
            
            if (!$valid) {
                return response()->json(['error' => 'Kode OTP tidak valid'], 422);
            }
            
            // Generate new token after successful verification
            $token = auth()->guard('jwt')->fromUser($user);
            $refreshToken = JWTAuth::fromUser($user);
            
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
            
        } catch (ValidationException $e) {
            \Log::error('2FA Validation Error:', [
                'errors' => $e->errors()
            ]);
            
            return response()->json([
                'errors' => $e->errors(),
                'message' => 'Validasi gagal, pastikan semua data telah diisi.'
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Verify 2FA error:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json(['error' => 'Gagal verifikasi 2FA'], 500);
        }
    }
    
    /**
     * Disable 2FA for user
     */
    public function disable(Request $request)
    {
        try {
            // Validate request
            $request->validate([
                'otp' => 'required|string|size:6',
            ]);
            
            // Get authenticated user
            $user = Auth::guard('jwt')->user();
            
            if (!$user) {
                return response()->json(['error' => 'Unauthenticated'], 401);
            }
            
            // Verify the OTP
            $google2fa = new Google2FA();
            $valid = $google2fa->verifyKey($user->google2fa_secret, $request->otp);
            
            if (!$valid) {
                return response()->json(['error' => 'Kode OTP tidak valid'], 422);
            }
            
            // Disable 2FA
            $user->google2fa_secret = null;
            $user->google2fa_enabled = false;
            $user->save();
            
            return response()->json([
                'success' => true,
                'message' => '2FA berhasil dinonaktifkan'
            ]);
            
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            \Log::error('Disable 2FA error:', [
                'user_id' => Auth::id(),
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json(['error' => 'Gagal menonaktifkan 2FA'], 500);
        }
    }
}
