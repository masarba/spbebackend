<?php

namespace App\Http\Controllers\Api\Auth;

use App\Models\AuditRequest;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AuditRequestController extends Controller
{
    // Fungsi untuk mengajukan permintaan audit
    public function requestAudit(Request $request)
    {
        // Validasi input auditor_id dan file NDA
        $request->validate([
            'auditor_id' => 'required|exists:users,id',
            'nda_document' => 'required|file|mimes:pdf|max:2048',
        ]);

        // Ambil ID auditee yang sedang login
        $auditee_id = Auth::id();

        if (!$auditee_id) {
            return response()->json(['message' => 'Auditee tidak terautentikasi'], 401);
        }

        // Cek apakah sudah ada audit request pending dengan auditor yang sama
        $existingRequest = AuditRequest::where('auditee_id', $auditee_id)
            ->where('auditor_id', $request->auditor_id)
            ->where('status', 'pending')
            ->first();

        if ($existingRequest) {
            return response()->json(['message' => 'Permintaan audit dengan auditor ini sudah diajukan dan masih pending.'], 400);
        }

        // Simpan NDA ke storage dan ambil path-nya
        $ndaPath = $request->file('nda_document')->store('nda_documents', 'public');

        // Buat permintaan audit baru
        $auditRequest = AuditRequest::create([
            'auditee_id' => $auditee_id,
            'auditor_id' => $request->auditor_id,
            'nda_document' => $ndaPath,
            'status' => 'pending',
        ]);

        return response()->json([
            'message' => 'Permintaan audit berhasil diajukan.',
            'data' => $auditRequest,
        ], 201);
    }

    // Fungsi untuk mendapatkan permintaan audit yang pending
    public function getPendingRequests()
    {
        // Ambil semua audit request yang pending
        $requests = AuditRequest::where('status', 'pending')
            ->with(['auditee:id,username', 'auditor:id,username']) // Ambil data relasi auditee dan auditor
            ->get(['id', 'auditee_id', 'auditor_id', 'status', 'nda_document', 'signed_nda', 'pdf_path']); // Kolom yang diambil

        return response()->json($requests, 200);
    }

    // Fungsi untuk mendapatkan permintaan audit yang telah dijawab
    public function getAnsweredRequests()
    {
        $requests = AuditRequest::where('status', 'answered')
            ->with('auditee:id,username') // Relasi ke auditee untuk mendapatkan username
            ->get(['id', 'auditee_id', 'auditor_id', 'status', 'nda_document', 'signed_nda', 'pdf_path']);

        return response()->json($requests, 200);
    }

    // Fungsi untuk menyetujui permintaan audit
    public function approveRequest(Request $request, $id)
    {
        $auditRequest = AuditRequest::findOrFail($id);

        // Cek apakah auditor yang login adalah pemilik audit request
        if ($auditRequest->auditor_id !== Auth::id()) {
            return response()->json(['message' => 'Akses ditolak. Anda bukan auditor untuk permintaan ini.'], 403);
        }

        // Cek apakah signed_nda sudah ada
        if (empty($auditRequest->signed_nda)) {
            return response()->json([
                'message' => 'NDA yang ditandatangani belum diunggah.',
                'errors' => ['signed_nda' => ['Harap unggah NDA yang telah ditandatangani terlebih dahulu.']]
            ], 400);
        }

        // Update status menjadi approved
        $auditRequest->update([
            'status' => 'approved'
        ]);

        // Logging perubahan status
        Log::info("Audit ID {$id} disetujui.");

        return response()->json([
            'message' => 'Permintaan audit disetujui.',
            'data' => $auditRequest
        ]);
    }

    // Fungsi untuk menolak permintaan audit
    public function rejectRequest($id)
    {
        $auditRequest = AuditRequest::findOrFail($id);

        // Cek apakah auditor yang login adalah pemilik audit request
        if ($auditRequest->auditor_id !== Auth::id()) {
            return response()->json(['message' => 'Akses ditolak. Anda bukan auditor untuk permintaan ini.'], 403);
        }

        // Update status menjadi rejected
        $auditRequest->update(['status' => 'rejected']);

        // Logging perubahan status
        Log::info("Audit ID {$id} ditolak.");

        return response()->json(['message' => 'Permintaan audit ditolak.']);
    }

    // Fungsi untuk mendapatkan permintaan audit yang telah disetujui
    public function getApprovedRequests()
    {
        $approvedRequests = AuditRequest::where('auditee_id', Auth::id())
            ->where('status', 'approved')
            ->get(['id', 'auditor_id', 'status', 'signed_nda']);

        return response()->json($approvedRequests, 200);
    }

    // Fungsi untuk mendapatkan dokumen NDA yang telah ditandatangani
    public function getSignedNDA($id)
    {
        $auditRequest = AuditRequest::where('auditee_id', Auth::id())
            ->where('id', $id)
            ->where('status', 'approved')
            ->firstOrFail();

        // Menambahkan pengecekan jika tidak ada file NDA
        if (!$auditRequest->signed_nda) {
            return response()->json(['message' => 'NDA belum ditandatangani'], 404);
        }

        return response()->json(['signed_nda' => asset('storage/' . $auditRequest->signed_nda)], 200);
    }
}
