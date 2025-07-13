<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SettingsController extends Controller
{
    public function index()
    {
        $settings = Cache::get('system_settings', [
            'enable_2fa' => true,
            'audit_approval_required' => true,
            'max_file_size' => 10, // MB
            'allowed_file_types' => ['pdf', 'doc', 'docx'],
            'notification_settings' => [
                'email_notifications' => true,
                'audit_reminders' => true,
                'completion_alerts' => true
            ],
            'audit_settings' => [
                'auto_assign_auditors' => false,
                'require_nda' => true,
                'allow_additional_questions' => true
            ]
        ]);

        return response()->json([
            'status' => 'success',
            'data' => $settings
        ]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'enable_2fa' => 'boolean',
            'audit_approval_required' => 'boolean',
            'max_file_size' => 'integer|min:1|max:100',
            'allowed_file_types' => 'array',
            'allowed_file_types.*' => 'string',
            'notification_settings' => 'array',
            'notification_settings.email_notifications' => 'boolean',
            'notification_settings.audit_reminders' => 'boolean',
            'notification_settings.completion_alerts' => 'boolean',
            'audit_settings' => 'array',
            'audit_settings.auto_assign_auditors' => 'boolean',
            'audit_settings.require_nda' => 'boolean',
            'audit_settings.allow_additional_questions' => 'boolean'
        ]);

        $settings = $request->all();
        Cache::put('system_settings', $settings, now()->addYear());

        return response()->json([
            'status' => 'success',
            'message' => 'Pengaturan berhasil diperbarui',
            'data' => $settings
        ]);
    }
} 