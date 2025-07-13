<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenExpiredException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenInvalidException;

class RefreshTokenController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function refresh()
    {
        try {
            $token = JWTAuth::parseToken()->refresh();
            $user = auth()->guard('jwt')->user();
            $refreshToken = JWTAuth::fromUser($user);

            return response()->json([
                'access_token' => $token,
                'token_type' => 'Bearer',
                'refresh_token' => $refreshToken,
                'expires_in' => auth()->factory()->getTTL() * 60
            ]);
        } catch (TokenExpiredException $e) {
            return response()->json(['error' => 'Token telah kadaluarsa'], 401);
        } catch (TokenInvalidException $e) {
            return response()->json(['error' => 'Token tidak valid'], 401);
        } catch (JWTException $e) {
            return response()->json(['error' => 'Token tidak dapat di-refresh'], 401);
        }
    }
}

