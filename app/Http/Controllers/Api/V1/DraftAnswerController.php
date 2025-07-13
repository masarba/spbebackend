<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Question;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DraftAnswerController extends Controller
{
    public function saveDraft(Request $request, $questionId)
    {
        $request->validate([
            'draft_answer' => 'required|string'
        ]);

        $question = Question::where('id', $questionId)
            ->where('auditee_id', Auth::id())
            ->firstOrFail();

        $question->update([
            'draft_answer' => $request->draft_answer,
            'is_draft' => true
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Jawaban sementara berhasil disimpan',
            'data' => $question
        ]);
    }

    public function submitAnswer(Request $request, $questionId)
    {
        $request->validate([
            'answer' => 'required|string'
        ]);

        $question = Question::where('id', $questionId)
            ->where('auditee_id', Auth::id())
            ->firstOrFail();

        $question->update([
            'answer' => $request->answer,
            'is_draft' => false,
            'draft_answer' => null
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Jawaban berhasil disubmit',
            'data' => $question
        ]);
    }

    public function getDraftAnswers()
    {
        $draftAnswers = Question::where('auditee_id', Auth::id())
            ->where('is_draft', true)
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $draftAnswers
        ]);
    }
} 