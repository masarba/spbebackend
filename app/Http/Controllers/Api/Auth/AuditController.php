<?php

namespace App\Http\Controllers\Api\Auth;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\AuditRequest;
use App\Models\Question;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use App\Models\Audit;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class AuditController extends Controller
{
    // Fungsi untuk mendapatkan daftar auditee
    public function getAuditees()
    {
        $auditees = User::where('role', 'auditee')->get(['id', 'username']);
        
        return response()->json($auditees, 200);
    }

    // Fungsi untuk menyetujui audit
    public function approveAudit($auditRequestId)
    {
        $auditRequest = AuditRequest::find($auditRequestId);

        if (!$auditRequest) {
            return response()->json(['message' => 'Audit request not found'], 404);
        }

        if (empty($auditRequest->signed_nda)) {
            return response()->json([
                'message' => 'The signed NDA is required to approve this audit.',
                'errors' => ['signed_nda' => ['The signed NDA field is required.']]
            ], 400);
        }

        if (!Storage::disk('public')->exists($auditRequest->signed_nda)) {
            return response()->json([
                'message' => 'Signed NDA file not found in the storage.',
                'errors' => ['signed_nda' => ['The signed NDA file does not exist.']]
            ], 404);
        }

        $auditRequest->status = 'approved';
        $auditRequest->save();

        return response()->json(['message' => 'Audit request approved successfully'], 200);
    }

    // Fungsi untuk mendapatkan hasil audit dari auditee yang dipilih
    public function getAuditResults($auditeeId)
    {
        // Ambil data hasil audit berdasarkan auditee_id
        $auditResults = AuditRequest::where('auditee_id', $auditeeId)
            ->with(['questions' => function ($query) {
                $query->select('id', 'question_text'); // Ambil hanya kolom yang dibutuhkan
            }])
            ->get();

        // Jika tidak ada hasil audit, kembalikan pesan error
        if ($auditResults->isEmpty()) {
            return response()->json(['message' => 'No audit results found for the selected auditee.'], 404);
        }

        // Inisialisasi area yang perlu diperbaiki
        $improvementAreas = [];

        foreach ($auditResults as $audit) {
            foreach ($audit->questions as $question) {
                if ($question->pivot->answer === 0) { // Cek jawaban 0 di pivot table
                    $improvementAreas[] = $question->question_text;
                }
            }
        }

        return response()->json([
            'audit_results' => $auditResults,
            'improvement_areas' => $improvementAreas,
        ], 200);
    }

    // Fungsi untuk menyimpan hasil audit
    public function saveAuditResults(Request $request)
    {
        try {
            // Validasi input
            $validated = $request->validate([
                'id' => 'required|exists:audit_requests,id',
                'question_groups' => 'required|array',
                'additional_questions' => 'nullable|array',
            ]);

            $totalScore = 0;
            $totalQuestions = 0;
            $categoryScores = [];
            $savedAnswers = [];

            foreach ($validated['question_groups'] as $group) {
                if (empty($group['category'])) {
                    return response()->json(['message' => 'Category is required.'], 400);
                }

                $categoryTotalScore = 0;
                $categoryTotalQuestions = count($group['questions']);

                foreach ($group['questions'] as $question) {
                    if (!isset($question['id'])) {
                        return response()->json([
                            'message' => 'Invalid question structure.',
                            'details' => 'Each question must have an id field.'
                        ], 400);
                    }

                    $answer = isset($question['answer']) ? (int) $question['answer'] : 0;

                    $categoryTotalScore += $answer;
                    $totalScore += $answer;
                    $totalQuestions++;

                    // Cari pertanyaan yang ada
                    $existingQuestion = Question::find($question['id']);
                    if (!$existingQuestion) {
                        return response()->json([
                            'message' => 'Question not found',
                            'question_id' => $question['id']
                        ], 404);
                    }

                    // Update pertanyaan yang ada
                    $existingQuestion->update([
                        'answer' => $answer,
                        'audit_request_id' => $validated['id']
                    ]);

                    $savedAnswers[] = [
                        'id' => $existingQuestion->id,
                        'question' => $existingQuestion->question,
                        'answer' => $answer,
                        'category' => $group['category'],
                        'audit_request_id' => $validated['id']
                    ];
                }

                $categoryScorePercentage = $categoryTotalQuestions > 0
                    ? round(($categoryTotalScore / ($categoryTotalQuestions * 1)) * 100, 2)
                    : 0;

                    $kesimpulan = $this->getQualitativeLabel($categoryTotalScore);

                $categoryScores[] = [
                    'category' => $group['category'],
                    'score' => $categoryScorePercentage,
                    'raw_score' => $categoryTotalScore,
                    'total_questions' => $categoryTotalQuestions,
                    'kesimpulan' => $kesimpulan
                ];
            }

            $totalScorePercentage = $totalQuestions > 0 ? round(($totalScore / $totalQuestions) * 100, 2) : 0;
            $totalKesimpulan = $this->getTotalKesimpulan($totalScorePercentage);


            // Perbarui status audit dan skor total
            $auditRequest = AuditRequest::find($validated['id']);
            if ($auditRequest) {
                $auditRequest->update([
                    'status' => 'approved',
                    'progress' => $totalScorePercentage
                ]);
            }

            $improvementAreas = $this->generateImprovementAreas($totalScorePercentage);

            \Log::info('Saved answers:', $savedAnswers);
            \Log::info('Category Scores:', $categoryScores);

            return response()->json([
                'message' => 'Audit results saved successfully.',
                'totalScore' => $totalScorePercentage,
                'kesimpulanTotal' => $totalKesimpulan,
                'categoryScores' => $categoryScores,
                'recommendations' => $this->generateRecommendations($validated),
                'improvementAreas' => $improvementAreas,
                'savedAnswers' => $savedAnswers,
                'redirect' => '/dashboard-default'
            ], 200);

        } catch (\Exception $e) {
            \Log::error('Error saving audit results: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error saving audit results.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function getQualitativeLabel($rawScore)
    {
        if ($rawScore >= 3) {
           return 'Memadai';
        } elseif ($rawScore >= 1) {
            return 'Perlu Peningkatan';
        } else {
            return 'Tidak Memadai';
        }
    }

    private function getTotalKesimpulan($totalScore)
    {
        if ($totalScore >= 75) {
            return 'Memadai';
        } elseif ($totalScore >= 30) {
            return 'Perlu Peningkatan';
        } else {
            return 'Tidak Memadai';
        }
    }



    private function generateImprovementAreas($totalScore)
    {
        // Misalnya, jika skor kurang dari 60, tambahkan area perbaikan
        if ($totalScore < 60) {
            return [
                'area1' => 'Security policy updates are needed.',
                'area2' => 'Compliance with ISO 27001 standards is lacking.',
                'area3' => 'Additional training for staff on data protection is recommended.',
            ];
        }
        return [];
    }

    // Fungsi untuk menghasilkan rekomendasi
    private function generateRecommendations($validated)
    {
        $recommendations = [];

        foreach ($validated['question_groups'] as $group) {
            foreach ($group['questions'] as $question) {
                // Cari pertanyaan dari database
                $questionData = Question::find($question['id']);
                if ($questionData && isset($question['answer']) && $question['answer'] === 0) {
                    $recommendations[] = "Improve the area: " . $questionData->question;
                }
            }
        }

        return $recommendations;
    }

    // Fungsi untuk memeriksa status persetujuan audit
    public function checkAuditApproval($id)
    {
        $auditRequest = AuditRequest::find($id);

        if (!$auditRequest) {
            return response()->json(['message' => 'Audit request not found'], 404);
        }

        return response()->json([
            'approved' => $auditRequest->status === 'approved',
            'message' => 'Audit approval status retrieved successfully.',
        ], 200);
    }

    // Fungsi untuk mengunduh dokumen NDA
    public function downloadDocumentNDA($id)
    {
        $auditRequest = AuditRequest::find($id);

        if (!$auditRequest || !$auditRequest->nda_document) {
            return response()->json(['message' => 'Document NDA not found for this audit.'], 404);
        }

        if (!Storage::disk('public')->exists($auditRequest->nda_document)) {
            return response()->json(['message' => 'File not found in storage.'], 404);
        }

        return response()->download(storage_path("app/public/" . $auditRequest->nda_document), 'Document_NDA_' . $id . '.pdf');
    }

    // Fungsi untuk mengunduh dokumen NDA yang sudah ditandatangani
    public function downloadSignedNDA($auditRequestId)
    {
        $auditRequest = AuditRequest::find($auditRequestId);

        if (!$auditRequest || !$auditRequest->signed_nda) {
            return response()->json(['message' => 'Signed NDA not found for this audit.'], 404);
        }

        if (!Storage::disk('public')->exists($auditRequest->signed_nda)) {
            return response()->json(['message' => 'File not found in storage.'], 404);
        }

        return response()->download(storage_path("app/public/" . $auditRequest->signed_nda), 'Signed_NDA_' . $auditRequestId . '.pdf');
    }

    // Fungsi untuk menghasilkan audit_id otomatis
    private function generateAuditId()
    {
        $today = Carbon::now()->format('dmy');
        $lastAuditRequest = AuditRequest::where('id', 'like', $today . '%')
            ->orderBy('id', 'desc')
            ->first();

        $newNumber = $lastAuditRequest ? str_pad(intval(substr($lastAuditRequest->id, 6)) + 1, 2, '0', STR_PAD_LEFT) : '01';

        return $today . $newNumber;
    }

    // Fungsi untuk mengunggah dokumen NDA yang sudah ditandatangani
    public function uploadSignedNDA(Request $request, $auditRequestId)
    {
        $validated = $request->validate([
            'signed_nda' => 'required|file|mimes:pdf|max:5048',
        ]);

        $auditRequest = AuditRequest::find($auditRequestId);

        if (!$auditRequest) {
            return response()->json(['message' => 'Audit request not found'], 404);
        }

        $filePath = $request->file('signed_nda')->store('nda_documents/signed', 'public');
        $auditRequest->signed_nda = $filePath;
        $auditRequest->save();

        return response()->json(['message' => 'Signed NDA successfully uploaded.', 'file_path' => $filePath], 200);
    }

    public function downloadAuditResult($id)
    {
        try {
            \Log::info('Attempting to download audit result for ID: ' . $id);
            
            // Cari audit request berdasarkan ID
            $auditRequest = AuditRequest::find($id);
            
            if (!$auditRequest) {
                \Log::warning('Audit request not found for ID: ' . $id);
                return response()->json([
                    'status' => 'error',
                    'message' => 'Data audit tidak ditemukan'
                ], 404);
            }

            if (!$auditRequest->pdf_path) {
                \Log::warning('No PDF file associated with audit request ID: ' . $id);
                return response()->json([
                    'status' => 'error',
                    'message' => 'File hasil audit belum diunggah'
                ], 404);
            }

            // Tentukan path file di storage
            $filePath = storage_path('app/public/' . $auditRequest->pdf_path);

            // Cek apakah file ada
            if (!file_exists($filePath)) {
                \Log::warning('File not found in storage: ' . $filePath);
                return response()->json([
                    'status' => 'error',
                    'message' => 'File tidak ditemukan di storage',
                    'file_path' => $filePath
                ], 404);
            }

            \Log::info('Successfully found file for download', [
                'file_path' => $filePath,
                'audit_request_id' => $id
            ]);

            // Kembalikan respons unduhan PDF dengan nama file yang sesuai
            $fileName = 'Hasil_Audit_' . $id . '.pdf';
            return response()->download($filePath, $fileName);

        } catch (\Exception $e) {
            \Log::error('Error downloading audit result: ' . $e->getMessage(), [
                'audit_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengunduh file hasil audit',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getPendingRequests()
    {
        $requests = AuditRequest::where('status', 'pending')
            ->with('auditee:id,username') // Relasi auditee untuk username
            ->get(['id', 'auditee_id', 'auditor_id', 'status', 'nda_document', 'signed_nda', 'pdf_path']);

        return response()->json($requests);
    }

    public function getAnsweredRequests()
    {
        // Ambil dari kedua tabel: audit_requests dan audits
        $requests = AuditRequest::where('status', 'answered')
            ->with('auditee:id,username')
            ->get(['id', 'auditee_id', 'auditor_id', 'status', 'nda_document', 'signed_nda', 'pdf_path']);

        $audits = Audit::where('status', 'answered')
            ->with('auditee:id,username')
            ->get();

        if ($requests->isEmpty() && $audits->isEmpty()) {
            return response()->json(['message' => 'Tidak ada audit dengan status answered.'], 404);
        }

        // Gabungkan hasil dari kedua tabel
        $combinedResults = [
            'audit_requests' => $requests,
            'audits' => $audits
        ];

        return response()->json($combinedResults, 200);
    }

    public function receivePdf(Request $request)
    {
        $validated = $request->validate([
            'pdf' => 'required|file|mimes:pdf|max:15000', // Maksimal 15MB
            'id' => 'required|exists:audit_requests,id', // Validasi menggunakan id
        ]);

        // Simpan file PDF
        $path = $request->file('pdf')->store('audit_results', 'public');

        // Simpan informasi file ke database dan update status
        $auditRequest = AuditRequest::find($validated['id']); // Cari berdasarkan id
        if ($auditRequest) {
            $auditRequest->pdf_path = $path;
            $auditRequest->status = 'answered'; // Update status menjadi answered
            $auditRequest->save();

            // Simpan ke tabel audits
            Audit::create([
                'auditee_id' => $auditRequest->auditee_id,
                'group_id' => null, // Sesuaikan jika ada group_id
                'score' => 0, // Sesuaikan dengan skor audit jika ada
                'status' => 'answered', // Ubah status menjadi answered
                'file' => $path,
                'audit_id' => $this->generateAuditId(), // Menggunakan fungsi yang sudah ada
                'question' => $auditRequest->additional_questions ?? '', // Menggunakan pertanyaan tambahan jika ada
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }

        return response()->json(['message' => 'PDF berhasil diterima dan disimpan.', 'path' => $path], 200);
    }

    public function submitAdditionalQuestions(Request $request, $id)
    {
        $request->validate([
            'questions' => 'required', // Bisa string atau array
        ]);

        $auditRequest = AuditRequest::find($id);

        if (!$auditRequest) {
            return response()->json(['message' => 'Audit request not found'], 404);
        }

        try {
            // Cek apakah input adalah array atau string
            if (is_array($request->questions)) {
                // Simpan sebagai JSON jika input adalah array
                $auditRequest->additional_questions = json_encode($request->questions);
            } else {
                // Jika string, periksa apakah string tersebut valid JSON
                json_decode($request->questions);
                if (json_last_error() === JSON_ERROR_NONE) {
                    // Valid JSON string, simpan langsung
                    $auditRequest->additional_questions = $request->questions;
                } else {
                    // Bukan JSON valid, simpan sebagai string biasa
                    $auditRequest->additional_questions = $request->questions;
                }
            }

            $auditRequest->save();

            return response()->json(['message' => 'Additional questions submitted successfully']);
        } catch (\Exception $e) {
            \Log::error('Error menyimpan pertanyaan tambahan: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to save questions: ' . $e->getMessage()], 500);
        }
    }

    public function getAuditsWithAdditionalQuestions()
    {
        // Query untuk mendapatkan audit yang memiliki pertanyaan tambahan
        $audits = AuditRequest::whereNotNull('additional_questions')
            ->where('additional_questions', '!=', '')
            ->with('auditee:id,username') // Relasi auditee untuk username
            ->get(['id', 'auditee_id', 'auditor_id', 'status', 'additional_questions']);

        // Periksa apakah ada hasil
        if ($audits->isEmpty()) {
            return response()->json(['message' => 'Tidak ada audit dengan pertanyaan tambahan.'], 404);
        }

        return response()->json($audits, 200);
    }

    public function saveAdditionalAnswers(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'answers' => 'required', // Bisa berupa string atau array
            ]);

            // Temukan AuditRequest berdasarkan ID
            $auditRequest = AuditRequest::findOrFail($id);

            // Cek apakah input adalah array atau string
            if (is_array($validated['answers'])) {
                // Simpan sebagai JSON jika input adalah array
                $auditRequest->answer = json_encode($validated['answers']);
            } else {
                // Jika string, periksa apakah string tersebut valid JSON
                json_decode($validated['answers']);
                if (json_last_error() === JSON_ERROR_NONE) {
                    // Valid JSON string, simpan langsung
                    $auditRequest->answer = $validated['answers'];
                } else {
                    // Bukan JSON valid, simpan sebagai string biasa
                    $auditRequest->answer = $validated['answers'];
                }
            }

            $auditRequest->save();

            return response()->json(['message' => 'Answers saved successfully.'], 200);
        } catch (\Exception $e) {
            \Log::error('Error menyimpan jawaban tambahan: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to save answers: ' . $e->getMessage()], 500);
        }
    }

    public function getQuestionsByCategory(Request $request)
    {
        // Pastikan audit_request_id diberikan
        $auditRequestId = $request->query('audit_request_id');
        if (!$auditRequestId) {
            return response()->json(['message' => 'audit_request_id is required'], 400);
        }

        // Ambil semua pertanyaan dengan kategori dan jawaban yang sudah tersimpan
        $questions = Question::select('id', 'question', 'answer', 'category', 'audit_request_id')
            ->where('audit_request_id', $auditRequestId)
            ->whereNotNull('category')
            ->orderBy('category')
            ->orderBy('id')
            ->get();

        // Jika belum ada pertanyaan untuk audit ini, buat pertanyaan baru
        if ($questions->isEmpty()) {
            $defaultQuestions = [
                'Keamanan Jaringan' => [
                    'Apakah firewall telah dikonfigurasi dengan benar?',
                    'Apakah terdapat mekanisme deteksi intrusi pada jaringan?',
                    'Apakah enkripsi digunakan dalam komunikasi internal?'
                ],
                'Manajemen Akses' => [
                    'Apakah terdapat kebijakan manajemen akses yang jelas?',
                    'Apakah autentikasi dua faktor diterapkan pada sistem kritikal?',
                    'Apakah hak akses pengguna ditinjau secara berkala?'
                ],
                'Keamanan Data' => [
                    'Apakah data sensitif dienkripsi saat disimpan?',
                    'Apakah terdapat mekanisme pencadangan data secara berkala?',
                    'Apakah terdapat kebijakan pemulihan bencana untuk data?'
                ],
                'Kepatuhan dan Regulasi' => [
                    'Apakah organisasi mematuhi standar keamanan informasi yang berlaku?',
                    'Apakah terdapat kebijakan pengelolaan risiko keamanan informasi?',
                    'Apakah dilakukan audit keamanan secara berkala?'
                ]
            ];

            foreach ($defaultQuestions as $category => $questionList) {
                foreach ($questionList as $questionText) {
                    Question::create([
                        'category' => $category,
                        'question' => $questionText,
                        'answer' => null,
                        'audit_request_id' => $auditRequestId
                    ]);
                }
            }

            // Ambil pertanyaan yang baru dibuat
            $questions = Question::select('id', 'question', 'answer', 'category', 'audit_request_id')
                ->where('audit_request_id', $auditRequestId)
                ->whereNotNull('category')
                ->orderBy('category')
                ->orderBy('id')
                ->get();
        }

        // Kelompokkan pertanyaan berdasarkan kategori
        $groupedQuestions = $questions->groupBy('category');

        // Format response
        $response = $groupedQuestions->map(function ($group, $category) {
            return [
                'category' => $category,
                'questions' => $group->map(function ($question) {
                    return [
                        'id' => $question->id,
                        'question' => $question->question,
                        'answer' => $question->answer !== null ? (int) $question->answer : null,
                        'audit_request_id' => $question->audit_request_id
                    ];
                })->values()->all(),
            ];
        })->values();

        // Log untuk debugging
        \Log::info('Questions with answers:', ['response' => $response]);

        return response()->json($response, 200);
    }

    // Di dalam AuditController
    public function getAdditionalAnswersByAuditee($id)
    {
        // Cari permintaan audit berdasarkan ID
        $auditRequest = AuditRequest::where('id', $id)
            ->with('auditee:id,username')
            ->first();

        if (!$auditRequest) {
            return response()->json(['message' => 'Audit request not found.'], 404);
        }

        try {
            // Coba parse sebagai JSON
            $questionsData = json_decode($auditRequest->additional_questions, true);
            
            // Jika format JSON valid dan berbentuk array
            if (json_last_error() === JSON_ERROR_NONE && is_array($questionsData)) {
                // Jika jawaban ada, tambahkan ke hasil
                if ($auditRequest->answer) {
                    $answersData = json_decode($auditRequest->answer, true);
                    
                    // Jika jawaban dalam format JSON valid
                    if (json_last_error() === JSON_ERROR_NONE && is_array($answersData)) {
                        // Jika format pertanyaan sudah dalam bentuk [{question, answer}]
                        if (isset($questionsData[0]['question'])) {
                            return response()->json($questionsData, 200);
                        }
                        
                        // Jika format pertanyaan adalah array biasa, gabungkan dengan jawaban
                        $responses = collect($questionsData)->map(function ($question, $index) use ($answersData) {
                            return [
                                'question' => $question,
                                'answer' => $answersData[$index] ?? null,
                            ];
                        });
                        
                        return response()->json($responses, 200);
                    }
                } else {
                    // Jika jawaban tidak ada, kembalikan format question-answer dari data pertanyaan
                    if (isset($questionsData[0]['question'])) {
                        return response()->json($questionsData, 200);
                    }
                    
                    // Jika pertanyaan dalam format array biasa
                    $responses = collect($questionsData)->map(function ($question) {
                        return [
                            'question' => $question,
                            'answer' => null,
                        ];
                    });
                    
                    return response()->json($responses, 200);
                }
            }
            
            // Jika pertanyaan bukan JSON valid, tapi jawaban mungkin JSON
            if ($auditRequest->answer) {
                $answersData = json_decode($auditRequest->answer, true);
                
                // Jika jawaban dalam format JSON valid
                if (json_last_error() === JSON_ERROR_NONE && is_array($answersData)) {
                    // Buat array dari string pertanyaan (tanpa pemisahan koma)
                    return response()->json([
                        [
                            'question' => $auditRequest->additional_questions,
                            'answer' => $answersData[0] ?? null
                        ]
                    ], 200);
                }
            }
            
            // Jika bukan JSON, kembalikan sebagai satu pertanyaan-jawaban
            return response()->json([
                [
                    'question' => $auditRequest->additional_questions,
                    'answer' => $auditRequest->answer
                ]
            ], 200);
            
        } catch (\Exception $e) {
            \Log::error('Error saat memproses pertanyaan dan jawaban tambahan: ' . $e->getMessage());
            return response()->json(['message' => 'Gagal memproses data pertanyaan dan jawaban.'], 500);
        }
    }

    public function getAudits()
    {
        $audits = Audit::with('auditee:id,username')
            ->orderBy('created_at', 'desc')
            ->get();

        if ($audits->isEmpty()) {
            return response()->json(['message' => 'Tidak ada data audit.'], 404);
        }

        return response()->json($audits, 200);
    }

    public function saveAuditProgress($id)
    {
        try {
            // Validasi input dengan nilai default
            $validator = Validator::make(request()->all(), [
                'progress' => 'nullable|integer|min:0|max:100',
                'notes' => 'nullable|string',
                'status' => 'nullable|in:in_progress,completed,pending,approved,rejected,answered'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Cari audit request berdasarkan ID
            $auditRequest = AuditRequest::find($id);
            if (!$auditRequest) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Permintaan audit tidak ditemukan'
                ], 404);
            }

            // Update progress audit dengan nilai default jika tidak ada
            $updateData = [];
            
            if (request()->has('progress')) {
                $updateData['progress'] = request('progress');
            }
            
            if (request()->has('notes')) {
                $updateData['notes'] = request('notes');
            }
            
            if (request()->has('status')) {
                $updateData['status'] = request('status');
            }

            $auditRequest->update($updateData);

            // Log aktivitas
            Log::info('Progress audit diperbarui', [
                'audit_id' => $id,
                'progress' => request('progress', $auditRequest->progress),
                'status' => request('status', $auditRequest->status),
                'updated_by' => auth()->user()->id
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Progress audit berhasil disimpan',
                'data' => $auditRequest,
                'redirect' => '/dashboard-default' // Mengubah endpoint ke dashboard-default
            ]);

        } catch (\Exception $e) {
            Log::error('Error saat menyimpan progress audit: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat menyimpan progress audit'
            ], 500);
        }
    }

    public function getQuestions($id)
    {
        try {
            \Log::info('Attempting to get questions for audit request ID: ' . $id);
            
            // Cek apakah audit request ada
            $auditRequest = AuditRequest::find($id);
            if (!$auditRequest) {
                \Log::warning('Audit request not found for ID: ' . $id);
                return response()->json([
                    'message' => 'Audit request tidak ditemukan'
                ], 404);
            }

            // Ambil pertanyaan berdasarkan audit_request_id
            $questions = Question::where('audit_request_id', $id)
                ->whereNotNull('category')
                ->orderBy('category')
                ->orderBy('id')
                ->get();

            \Log::info('Found ' . $questions->count() . ' existing questions');

            // Jika belum ada pertanyaan, buat pertanyaan default
            if ($questions->isEmpty()) {
                \Log::info('No questions found, creating default questions');
                
                $defaultQuestions = [
                    'Keamanan Jaringan' => [
                        'Apakah firewall telah dikonfigurasi dengan benar?',
                        'Apakah terdapat mekanisme deteksi intrusi pada jaringan?',
                        'Apakah enkripsi digunakan dalam komunikasi internal?'
                    ],
                    'Manajemen Akses' => [
                        'Apakah terdapat kebijakan manajemen akses yang jelas?',
                        'Apakah autentikasi dua faktor diterapkan pada sistem kritikal?',
                        'Apakah hak akses pengguna ditinjau secara berkala?'
                    ],
                    'Keamanan Data' => [
                        'Apakah data sensitif dienkripsi saat disimpan?',
                        'Apakah terdapat mekanisme pencadangan data secara berkala?',
                        'Apakah terdapat kebijakan pemulihan bencana untuk data?'
                    ],
                    'Kepatuhan dan Regulasi' => [
                        'Apakah organisasi mematuhi standar keamanan informasi yang berlaku?',
                        'Apakah terdapat kebijakan pengelolaan risiko keamanan informasi?',
                        'Apakah dilakukan audit keamanan secara berkala?'
                    ]
                ];

                try {
                    \DB::beginTransaction();
                    
                    foreach ($defaultQuestions as $category => $questionList) {
                        foreach ($questionList as $questionText) {
                            Question::create([
                                'category' => $category,
                                'question' => $questionText,
                                'answer' => null,
                                'audit_request_id' => $id
                            ]);
                        }
                    }

                    \DB::commit();
                    \Log::info('Successfully created default questions');

                } catch (\Exception $e) {
                    \DB::rollBack();
                    \Log::error('Error creating default questions: ' . $e->getMessage());
                    throw $e;
                }

                // Ambil pertanyaan yang baru dibuat
                $questions = Question::where('audit_request_id', $id)
                    ->whereNotNull('category')
                    ->orderBy('category')
                    ->orderBy('id')
                    ->get();
            }

            // Kelompokkan pertanyaan berdasarkan kategori
            $groupedQuestions = $questions->groupBy('category')->map(function ($group, $category) {
                return [
                    'category' => $category,
                    'questions' => $group->map(function ($question) {
                        return [
                            'id' => $question->id,
                            'question' => $question->question,
                            'answer' => $question->answer !== null ? (int) $question->answer : null
                        ];
                    })->values()
                ];
            })->values();

            \Log::info('Successfully retrieved and formatted questions', [
                'total_categories' => $groupedQuestions->count(),
                'audit_request_id' => $id
            ]);

            return response()->json([
                'status' => 'success',
                'data' => $groupedQuestions
            ]);

        } catch (\Exception $e) {
            \Log::error('Error getting questions: ' . $e->getMessage(), [
                'audit_request_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengambil data pertanyaan',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
