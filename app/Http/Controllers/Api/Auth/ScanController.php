<?php

namespace App\Http\Controllers\Api\Auth;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class ScanController extends Controller
{
    // Method untuk scanning URL
    public function scanUrl(Request $request)
    {
        // Validasi input
        $request->validate([
            'url' => 'required|url',
        ]);

        $url = $request->input('url');

        // Kirim URL ke layanan ML (contoh: VirusTotal API)
        $apiKey = 'YOUR_VIRUSTOTAL_API_KEY'; // Ganti dengan API key Anda
        $response = Http::withHeaders([
            'x-apikey' => $apiKey,
        ])->post('https://www.virustotal.com/api/v3/urls', [
            'url' => $url,
        ]);

        // Proses hasil scanning
        if ($response->successful()) {
            $scanResults = $response->json();
            return response()->json([
                'status' => 'success',
                'data' => $scanResults,
            ]);
        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal melakukan scanning URL.',
            ], 500);
        }
    }

    // Method untuk scanning file
    public function scanFile(Request $request)
    {
        // Validasi input
        $request->validate([
            'file' => 'required|file|mimes:exe,apk|max:10240', // Maksimal 10MB
        ]);

        $file = $request->file('file');
        $fileName = $file->getClientOriginalName();

        // Simpan file sementara
        $filePath = $file->store('temp');

        // Kirim file ke layanan ML (contoh: VirusTotal API)
        $apiKey = 'YOUR_VIRUSTOTAL_API_KEY'; // Ganti dengan API key Anda
        $response = Http::withHeaders([
            'x-apikey' => $apiKey,
        ])->attach('file', file_get_contents(storage_path('app/' . $filePath)), $fileName)
          ->post('https://www.virustotal.com/api/v3/files');

        // Hapus file sementara
        Storage::delete($filePath);

        // Proses hasil scanning
        if ($response->successful()) {
            $scanResults = $response->json();
            return response()->json([
                'status' => 'success',
                'data' => $scanResults,
            ]);
        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal melakukan scanning file.',
            ], 500);
        }
    }
}
