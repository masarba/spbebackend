<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\Question;
use App\Models\AuditRequest; // Model untuk audit_requests
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class QuestionController extends Controller
{
    public function index($auditeeId)
    {
        try {
            // Cari audit request yang aktif untuk auditee ini
            $auditRequest = AuditRequest::where('auditee_id', $auditeeId)
                ->where('status', 'approved')
                ->latest()
                ->first();

            if (!$auditRequest) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Tidak ada audit request yang aktif untuk auditee ini.'
                ], 404);
            }

            // Ambil pertanyaan yang terkait dengan audit request ini
            $questions = Question::where('audit_request_id', $auditRequest->id)
                ->select('id', 'question', 'answer', 'category', 'is_draft', 'draft_answer')
                ->get();

            if ($questions->isEmpty()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Tidak ada pertanyaan untuk ditampilkan.'
                ], 404);
            }

            // Kelompokkan pertanyaan berdasarkan kategori
            $groupedQuestions = $questions->groupBy('category')->map(function ($group, $category) {
                return [
                    'category' => $category,
                    'questions' => $group->map(function ($question) {
                        return [
                            'id' => $question->id,
                            'question' => $question->question,
                            'answer' => $question->answer,
                            'is_draft' => $question->is_draft,
                            'draft_answer' => $question->draft_answer
                        ];
                    }),
                ];
            })->values();

            return response()->json([
                'status' => 'success',
                'data' => $groupedQuestions
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error in QuestionController@index: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengambil data pertanyaan. Silakan coba lagi.'
            ], 500);
        }
    }

    public function submitAnswers(Request $request, $auditeeId)
    {
        try {
            // Validasi input
            $request->validate([
                'answers' => 'required|array',
                'answers.*.id' => 'required|exists:questions,id',
                'answers.*.answer' => 'required|in:0,1'
            ]);

            $answers = $request->input('answers');

            // Update jawaban untuk setiap pertanyaan
            foreach ($answers as $answer) {
                $question = Question::where('id', $answer['id'])
                    ->where('audit_request_id', function($query) use ($auditeeId) {
                        $query->select('id')
                            ->from('audit_requests')
                            ->where('auditee_id', $auditeeId)
                            ->latest();
                    })
                    ->firstOrFail();

                $question->update([
                    'answer' => $answer['answer'],
                    'is_draft' => false,
                    'draft_answer' => null
                ]);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Jawaban berhasil disimpan'
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error('Question not found in submitAnswers: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Pertanyaan tidak ditemukan atau tidak sesuai dengan auditee.'
            ], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation error in submitAnswers: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Format jawaban tidak valid.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error in submitAnswers: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menyimpan jawaban. Silakan coba lagi.'
            ], 500);
        }
    }
}
