<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ChangePasswordController extends Controller
{
    public function changePassword(Request $request)
    {
        // Validate the request
        $request->validate([
            'password' => 'required|string|min:8|confirmed', // Ensure the password has at least 8 characters
        ]);

        // Get the authenticated user
        $user = $request->user();

        // Check if the user is authenticated
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // Update the user's password
        $user->password = Hash::make($request->password);
        $user->is_new_user = false; // Set the user as no longer new
        $user->save();

        return response()->json(['message' => 'Password updated successfully.']);
    }
}



