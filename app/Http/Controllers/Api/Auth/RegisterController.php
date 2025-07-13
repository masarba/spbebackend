<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class RegisterController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(RegisterRequest $request)
    {
        \Log::info('Received request:', $request->all()); // Log request

        // Create a new user
        $user = User::create([
            'username' => $request->username,
            'email' => $request->email,
            'password' => bcrypt($request->password),
            'role' => $request->role, // Save the selected role
        ]);

        // Set auditee_id if the role is 'auditee'
        if ($request->role === 'auditee') {
            $user->auditee_id = $user->id; // Set the auditee_id to the user's own ID
            $user->save(); // Save the updated user
        }

        // Set auditor_id if the role is 'auditor'
        if ($request->role === 'auditor') {
            $user->auditor_id = $user->id;
            $user->save();
        }

        // Generate access token
        if (!$token = auth()->guard('jwt')->attempt($request->only('email', 'password'))) {
            return response()->json(['error' => 'Token tidak dapat dibuat'], 500);
        }

        $refreshToken = JWTAuth::fromUser($user);

        return response()->json([
            'user' => $user,
            'access_token' => $token,
            'token_type' => 'Bearer',
            'refresh_token' => $refreshToken,
            'expires_in' => auth()->factory()->getTTL() * 60
        ]);
    }
}



