<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use PragmaRX\Google2FA\Google2FA;
use App\Models\User;

class Setup2FAController extends Controller
{
    public function setup(Request $request)
    {
        // Get the authenticated user
        $user = $request->user();

        // Initialize the Google2FA instance
        $google2fa = new Google2FA();

        // Generate a new secret key for the user
        $secretKey = $google2fa->generateSecretKey();

        // Save the secret key to the user's record
        $user->google2fa_secret = $secretKey; // Update to use the correct column name
        $user->is_2fa_enabled = true; // Enable 2FA for the user
        $user->save();

        // Mark the session as 2FA passed, so user won't be redirected to /2fa again
       

        // Generate the QR code URL for the user to scan with Google Authenticator
        $qrCodeUrl = $google2fa->getQRCodeUrl(
            'YourAppName',          // The name of your application
            $user->email,           // The user's email
            $secretKey              // The secret key generated for the user
        );

        // Return the QR code URL and secret key (optional, depending on your frontend needs)
        return response()->json([
            'qr_code_url' => $qrCodeUrl,
            'secret' => $secretKey,  // Optional: some apps may not show this
            'redirect_to' => '/dashboard', // Redirect to dashboard after successful setup
        ]);
    }
}
