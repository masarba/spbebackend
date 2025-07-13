<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenExpiredException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenInvalidException;

class JWTMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
        } catch (\Exception $e) {
            if ($e instanceof TokenExpiredException) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Token telah kadaluarsa'
                ], 401);
            } else if ($e instanceof TokenInvalidException) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Token tidak valid'
                ], 401);
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Token tidak ditemukan'
                ], 401);
            }
        }

        return $next($request);
    }
} 