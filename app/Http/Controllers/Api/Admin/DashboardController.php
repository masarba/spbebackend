<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Audit;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        try {
            $totalUsers = User::count();
            $totalAudits = Audit::count();
            $pendingAudits = Audit::where('status', 'pending')->count();
            $completedAudits = Audit::where('status', 'completed')->count();

            return response()->json([
                'status' => 'success',
                'data' => [
                    'total_users' => $totalUsers,
                    'total_audits' => $totalAudits,
                    'pending_audits' => $pendingAudits,
                    'completed_audits' => $completedAudits
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch dashboard data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getUserStatistics()
    {
        try {
            $userStats = [
                'total' => User::count(),
                'active' => User::where('status', 'active')->count(),
                'inactive' => User::where('status', 'inactive')->count(),
                'new_this_month' => User::whereMonth('created_at', now()->month)->count()
            ];

            return response()->json([
                'status' => 'success',
                'data' => $userStats
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch user statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getActiveUsers()
    {
        try {
            $activeUsers = User::where('status', 'active')
                ->select('id', 'username', 'email', 'role', 'last_login')
                ->orderBy('last_login', 'desc')
                ->take(10)
                ->get();

            return response()->json([
                'status' => 'success',
                'data' => $activeUsers
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch active users',
                'error' => $e->getMessage()
            ], 500);
        }
    }
} 