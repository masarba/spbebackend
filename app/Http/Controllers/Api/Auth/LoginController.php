<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Http\Requests\LoginRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;
use App\Models\AuditRequest;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function __invoke(LoginRequest $request)
    {
        try {
            // Validasi kredensial
            $credentials = $request->only(['email', 'password']);
            
            if (!$token = auth()->attempt($credentials)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Email atau password salah'
                ], 401);
            }

            // Dapatkan user yang login
            $user = auth()->user();

            // Siapkan data response dasar
            $userData = [
                'access_token' => $token,
                'token_type' => 'Bearer',
                'expires_in' => auth()->factory()->getTTL() * 60,
                'role' => $user->role,
            ];

            // Jika user adalah auditee, tambahkan auditee_id
            if ($user->role === 'auditee') {
                // Ambil auditee_id dari tabel audit_requests
                $auditRequest = AuditRequest::where('auditee_id', $user->id)
                    ->first();
                
                if ($auditRequest) {
                    $userData['auditee_id'] = $auditRequest->auditee_id;
                }

                // Log untuk debugging
                \Log::info('Auditee login data:', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'audit_request' => $auditRequest
                ]);
            }

            // Jika user adalah auditor, tambahkan auditor_id
            if ($user->role === 'auditor') {
                $userData['auditor_id'] = $user->id;
            }

            // Untuk user baru yang belum ganti password
            if ($user->is_new_user) {
                $userData['redirect_to'] = '/change-password';
                
                \Log::info('New user login:', [
                    'user_id' => $user->id,
                    'redirect_to' => '/change-password'
                ]);
                
                return response()->json($userData);
            }

            // Untuk user dengan 2FA
            if (!empty($user->google2fa_secret)) {
                $userData['google2fa_secret'] = $user->google2fa_secret;
                $userData['redirect_to'] = '/verify-2fa';
                
                \Log::info('2FA user login:', [
                    'user_id' => $user->id,
                    'redirect_to' => '/verify-2fa'
                ]);
                
                return response()->json($userData);
            }

            // Default response untuk setup 2FA
            $userData['redirect_to'] = '/setup-2fa';
            
            \Log::info('User login successful:', [
                'user_id' => $user->id,
                'role' => $user->role,
                'redirect_to' => '/setup-2fa'
            ]);

            return response()->json($userData);

        } catch (\Exception $e) {
            \Log::error('Login error:', [
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
