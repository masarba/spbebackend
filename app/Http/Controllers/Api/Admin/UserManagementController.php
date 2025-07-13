<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;

class UserManagementController extends Controller
{
    public function index()
    {
        $users = User::with('roles')->get();
        return response()->json([
            'status' => 'success',
            'data' => $users
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'username' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'role' => ['required', 'string', 'in:admin,auditor,auditee'],
            'status' => ['required', 'string', 'in:active,inactive'],
            'verifikasi' => ['nullable', 'string']
        ]);

        $user = User::create([
            'username' => $request->username,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'status' => $request->status ?? 'active',
            'verifikasi' => $request->verifikasi ?? 'verified',
            'google2fa_secret' => null,
            'is_2fa_enabled' => false,
            'auditor_id' => null,
            'auditee_id' => null,
            'is_new_user' => true
        ]);

        // Set auditee_id jika rolenya auditee
        if ($request->role === 'auditee') {
            $user->auditee_id = $user->id;
            $user->save();
        }

        // Set auditor_id jika rolenya auditor
        if ($request->role === 'auditor') {
            $user->auditor_id = $user->id;
            $user->save();
        }

        $user->assignRole($request->role);

        return response()->json([
            'status' => 'success',
            'message' => 'User berhasil dibuat',
            'data' => $user
        ], 201);
    }

    public function show($id)
    {
        $user = User::with('roles')->findOrFail($id);
        return response()->json([
            'status' => 'success',
            'data' => $user
        ]);
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'username' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $id],
            'role' => ['required', 'string', 'in:admin,auditor,auditee']
        ]);

        if ($request->filled('password')) {
            $request->validate([
                'password' => ['confirmed', Rules\Password::defaults()]
            ]);
            $user->password = Hash::make($request->password);
        }

        $user->username = $request->username;
        $user->email = $request->email;
        $user->save();

        // Update role
        $user->syncRoles([$request->role]);

        return response()->json([
            'status' => 'success',
            'message' => 'User berhasil diperbarui',
            'data' => $user
        ]);
    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);
        $user->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'User berhasil dihapus'
        ]);
    }

    public function getAuditors()
    {
        $auditors = User::role('auditor')->get();
        return response()->json([
            'status' => 'success',
            'data' => $auditors
        ]);
    }

    public function getAuditees()
    {
        $auditees = User::role('auditee')->get();
        return response()->json([
            'status' => 'success',
            'data' => $auditees
        ]);
    }
} 