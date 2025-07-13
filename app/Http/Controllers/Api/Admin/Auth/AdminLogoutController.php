<?php

namespace App\Http\Controllers\Api\Admin\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AdminLogoutController extends Controller
{
    public function __invoke(Request $request)
    {
        auth()->guard('jwt')->logout();

        return response()->json([
            'status' => 'success',
            'message' => 'Berhasil logout'
        ]);
    }
} 