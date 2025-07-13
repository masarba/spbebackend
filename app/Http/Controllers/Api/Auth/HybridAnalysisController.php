<?php

namespace App\Http\Controllers\Api\Auth;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class HybridAnalysisController extends Controller
{
    // Fungsi untuk scan file
    public function scanFile(Request $request)
    {
        // Validasi input
        $request->validate([
            'file' => 'required|file|mimes:exe,apk,pdf|max:10240', // Maksimal 10MB
        ]);

        // Ambil API Key dari .env
        $apiKey = env('co6ecoum51290b804m8z0nxcf58366b9i8rzvmqpb7aebd6bue7tx1qrc035d382');

        // Kirim file ke Hybrid Analysis API
        $response = Http::withHeaders([
            'api-key' => $apiKey,
        ])->attach('file', file_get_contents($request->file('file')->path()), $request->file('file')->getClientOriginalName())
          ->post('https://www.hybrid-analysis.com/api/v2/submit/file');

        // Proses response
        if ($response->successful()) {
            return response()->json($response->json());
        } else {
            return response()->json(['error' => 'Gagal melakukan scanning file.'], $response->status());
        }
    }

    // Fungsi untuk scan URL
    public function scanUrl(Request $request)
    {
        // Validasi input
        $request->validate([
            'url' => 'required|url',
        ]);

        // Ambil API Key dari .env
        $apiKey = env('HYBRID_ANALYSIS_API_KEY');

        // Kirim URL ke Hybrid Analysis API
        $response = Http::withHeaders([
            'api-key' => $apiKey,
        ])->post('https://www.hybrid-analysis.com/api/v2/submit/url', [
            'url' => $request->input('url'),
        ]);

        // Proses response
        if ($response->successful()) {
            return response()->json($response->json());
        } else {
            return response()->json(['error' => 'Gagal melakukan scanning URL.'], $response->status());
        }
    }
}
