<?php

namespace App\Http\Controllers\Api\Auth;
use App\Models\AuditResult;
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class AuditResultController extends Controller
{
    public function getAuditResults($auditeeId) {
        $results = AuditResult::where('auditee_id', $auditeeId)->get();
        
        return response()->json($results);
    }
}
