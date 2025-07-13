<?php

namespace App\Http\Controllers\Api\Auth;

use App\Models\AdditionalQuestion;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class AdditionalQuestionController extends Controller
{
    // Menampilkan pertanyaan tambahan berdasarkan audit_id
    public function index($auditId)
    {
        $additionalQuestions = AdditionalQuestion::where('audit_id', $auditId)->get();
        return response()->json($additionalQuestions);
    }

    // Fungsi untuk menambahkan pertanyaan tambahan
    public function store(Request $request)
    {
        // Validasi input
        $request->validate([
            'question' => 'required|string|max:255', // Menambahkan batasan maksimum karakter
            'auditor_id' => 'required|integer|exists:users,id', // Memastikan auditor_id ada di tabel users
            'audit_id' => 'required|integer|exists:audits,id', // Memastikan audit_id ada di tabel audits
        ]);

        // Membuat pertanyaan tambahan
        $additionalQuestion = AdditionalQuestion::create([
            'question' => $request->input('question'),
            'auditor_id' => $request->input('auditor_id'),
            'audit_id' => $request->input('audit_id'),
        ]);

        return response()->json($additionalQuestion, 201);
    }
}
