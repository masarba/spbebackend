<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Audit;
use App\Models\User;
use Carbon\Carbon;

class AuditManagementController extends Controller
{
    public function index()
    {
        $audits = Audit::with(['auditor', 'auditee'])->get();
        return response()->json([
            'status' => 'success',
            'data' => $audits
        ]);
    }

    public function getPendingAudits()
    {
        $pendingAudits = Audit::where('status', 'pending')
            ->with(['auditor', 'auditee'])
            ->get();
            
        return response()->json([
            'status' => 'success',
            'data' => $pendingAudits
        ]);
    }

    public function getCompletedAudits()
    {
        $completedAudits = Audit::where('status', 'completed')
            ->with(['auditor', 'auditee'])
            ->get();
            
        return response()->json([
            'status' => 'success',
            'data' => $completedAudits
        ]);
    }

    public function show($id)
    {
        $audit = Audit::with(['auditor', 'auditee', 'questions', 'additionalQuestions'])
            ->findOrFail($id);
            
        return response()->json([
            'status' => 'success',
            'data' => $audit
        ]);
    }

    public function update(Request $request, $id)
    {
        $audit = Audit::findOrFail($id);

        $request->validate([
            'status' => 'required|in:pending,in_progress,completed,cancelled',
            'notes' => 'nullable|string'
        ]);

        $audit->update($request->all());

        return response()->json([
            'status' => 'success',
            'message' => 'Audit berhasil diperbarui',
            'data' => $audit
        ]);
    }

    public function destroy($id)
    {
        $audit = Audit::findOrFail($id);
        $audit->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Audit berhasil dihapus'
        ]);
    }

    public function assignAuditor(Request $request, $id)
    {
        $request->validate([
            'auditor_id' => 'required|exists:users,id'
        ]);

        $audit = Audit::findOrFail($id);
        $auditor = User::role('auditor')->findOrFail($request->auditor_id);

        $audit->auditor_id = $auditor->id;
        $audit->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Auditor berhasil ditugaskan',
            'data' => $audit
        ]);
    }

    public function getStatistics()
    {
        $totalAudits = Audit::count();
        $pendingAudits = Audit::where('status', 'pending')->count();
        $inProgressAudits = Audit::where('status', 'in_progress')->count();
        $completedAudits = Audit::where('status', 'completed')->count();
        $cancelledAudits = Audit::where('status', 'cancelled')->count();

        // Statistik bulanan
        $monthlyStats = Audit::selectRaw('MONTH(created_at) as month, COUNT(*) as total')
            ->whereYear('created_at', Carbon::now()->year)
            ->groupBy('month')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => [
                'total_audits' => $totalAudits,
                'pending_audits' => $pendingAudits,
                'in_progress_audits' => $inProgressAudits,
                'completed_audits' => $completedAudits,
                'cancelled_audits' => $cancelledAudits,
                'monthly_statistics' => $monthlyStats
            ]
        ]);
    }
} 