<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TwoFactorMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if the user is authenticated
        $user = auth()->user();
        
        // If user is authenticated, 2FA is enabled, but hasn't passed 2FA
        if ($user && $user->google2fa_secret && !session('2fa_passed')) {
            // Redirect to the 2FA verification or setup page
            return redirect('/setup-2fa'); // Adjust this to your route for 2FA
        }

        // Continue processing the request
        return $next($request);
    }
}
