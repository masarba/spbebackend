<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Auth\LoginController; 
use App\Http\Controllers\Api\Auth\LogoutController;
use App\Http\Controllers\Api\Auth\RegisterController;
use App\Http\Controllers\Api\Auth\RefreshTokenController; 
use App\Http\Controllers\Api\V1\KamusKerentananController;
use App\Http\Controllers\Api\Auth\AuditorLoginController;
use App\Http\Controllers\Api\Auth\TwoFactorController;
use App\Http\Controllers\Api\Auth\QuestionController;
use App\Http\Controllers\Api\Auth\AdditionalQuestionController;
use App\Http\Controllers\Api\Auth\ChangePasswordController; 
use App\Http\Controllers\Api\Auth\Setup2FAController; 
use App\Http\Controllers\Api\Auth\UserController;
use App\Http\Controllers\Api\Auth\Verify2FAController;
use App\Http\Controllers\Api\Auth\AuditController;
use App\Http\Controllers\Api\Auth\AuditRequestController;
use App\Http\Controllers\Api\Auth\ScanController;
use App\Http\Controllers\Api\Auth\HybridAnalysisController;
use App\Http\Controllers\Api\Admin\Auth\AdminLoginController;
use App\Http\Controllers\Api\Admin\Auth\AdminLogoutController;
use App\Http\Controllers\Api\Admin\UserManagementController;
use App\Http\Controllers\Api\Admin\AuditManagementController;
use App\Http\Controllers\Api\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Api\Admin\SettingsController;


// Route for public access (welcome page)
Route::get('/', function () {
    return view('welcome');
});

// Middleware for routes that require authentication
Route::middleware('jwt.auth')->prefix('v1')->group(function() {
    Route::apiResource('/kamus-kerentanan', KamusKerentananController::class);
});

// Authentication routes
Route::prefix('auth')->group(function() {
    Route::post('/login', [LoginController::class, '__invoke'])->middleware('guest');
    Route::post('/logout', [LogoutController::class, '__invoke'])->middleware('jwt.auth');    
    Route::post('/register', [RegisterController::class, '__invoke']);
    Route::post('/auditor-login', [AuditorLoginController::class, '__invoke'])->middleware('guest');
    
    // 2FA routes
    Route::post('/verify-2fa', [TwoFactorController::class, 'verify']); // Tidak perlu auth
    
    Route::middleware('jwt.auth')->group(function() {
        Route::get('/setup-2fa', [TwoFactorController::class, 'setup']);
        Route::post('/activate-2fa', [TwoFactorController::class, 'activate']);
        Route::post('/disable-2fa', [TwoFactorController::class, 'disable']);
    });
    
    // Rute yang memerlukan autentikasi
    Route::middleware('jwt.auth')->group(function() {
        Route::post('/audit-requests', [AuditRequestController::class, 'requestAudit']);
        Route::get('/audit-requests/pending', [AuditRequestController::class, 'getPendingRequests']);
        Route::patch('/audit-requests/{id}/approve', [AuditRequestController::class, 'approveRequest']);
        Route::patch('/audit-requests/{id}/reject', [AuditRequestController::class, 'rejectRequest']);
        Route::get('/audit-requests/approved/{id}', [AuditRequestController::class, 'getApprovedRequests']);
        Route::post('/audits/{auditee_id}/approve', [AuditController::class, 'approveAudit']);
        Route::get('/audit/signed-nda/{id}', [AuditRequestController::class, 'getSignedNDA']);
        Route::get('/questions/{id}', [AuditController::class, 'getQuestions']);
        Route::post('/additional-questions/{auditId}', [AdditionalQuestionController::class, 'index']);
        Route::get('/auditees', [AuditController::class, 'getAuditees']);
        Route::get('/audit-results/{auditeeId}', [AuditController::class, 'getAuditResults']);
        Route::post('/save-audit-results/{Id}', [AuditController::class, 'saveAuditResults']);
        Route::get('download-audit-results-pdf/{auditeeId}', [AuditController::class, 'downloadAuditResultsPdf']);
        Route::get('/audit/results/pdf/{auditeeId}', [AuditController::class, 'downloadAuditResultsPdf']);
        Route::get('/check-audit-approval/{auditId}', [AuditController::class, 'checkAuditApproval']);
        Route::get('audit-requests/{auditId}/download-nda', [AuditController::class, 'downloadSignedNDA']);
        Route::get('/audit-requests/{auditId}/download-nda-auditee', [AuditController::class, 'downloadDocumentNDA']);
        Route::post('/audit-requests/{id}/upload-signed-nda', [AuditController::class, 'uploadSignedNDA']);
        Route::get('/audit-requests/audit_results/{pdfPath}/download-audit-result', [AuditController::class, 'downloadAuditResult']);
        Route::post('audit-results/notify/{auditId}', [AuditController::class, 'notifyAuditor']);
        Route::get('/audit-requests/answered', [AuditController::class, 'getAnsweredRequests']);
        Route::post('/audit-requests/{id}/additional-questions', [AuditController::class, 'submitAdditionalQuestions']);
        Route::post('/audit-results/send-pdf', [AuditController::class, 'receivePdf']);
        Route::get('/audits/{id}/download-result', [AuditController::class, 'downloadAuditResult']);
        Route::post('/save-audit-progress/{id}', [AuditController::class, 'saveAuditProgress']);
        Route::get('/audit-requests/with-additional-questions', [AuditController::class, 'getAuditsWithAdditionalQuestions']);
        Route::post('/save-additional-answers/{id}', [AuditController::class, 'saveAdditionalAnswers']);
        Route::post('/scan-url', [ScanController::class, 'scanUrl']);
        Route::post('/scan-file', [ScanController::class, 'scanFile']);
        Route::post('/scan-file', [HybridAnalysisController::class, 'scanFile']);
        Route::post('/scan-url', [HybridAnalysisController::class, 'scanUrl']);
        Route::get('/audit-requests/{id}/additional-answers', [AuditController::class, 'getAdditionalAnswersByAuditee']);
        Route::get('/questions/load', [AuditController::class, 'loadQuestions']);

        // This route is commented out as it conflicts with the public verify-2fa route
        // Route::post('/verify-2fa', [Verify2FAController::class, 'verifyTwoFactorAuth']); 
        
 
        
        // Routes untuk mengganti password dan mengatur 2FA
        Route::post('/change-password', [ChangePasswordController::class, 'changePassword']); 
        Route::post('/setup-2fa', [Setup2FAController::class, 'setup']); 

        // Routes untuk pertanyaan dan jawaban
        Route::get('/questions/{auditeeId}', [QuestionController::class, 'index']);
        Route::post('/questions/{auditeeId}/submit', [QuestionController::class, 'submitAnswers']);
    });
});


// Route for refreshing tokens
Route::post('/auth/refresh', [RefreshTokenController::class, 'refresh'])->name('auth.refresh');

// Route to get authenticated user data
Route::middleware('jwt.auth')->get('/user', function (Request $request) {
    return $request->user();
});

// User-related routes that require authentication
Route::middleware(['jwt.auth', '2fa'])->group(function() {
    Route::post('/user/update-password', [UserController::class, 'updatePassword']);
    Route::post('/user/enable-2fa', [UserController::class, 'enableTwoFactorAuth']);
    Route::get('/user/first-login', [UserController::class, 'checkFirstLogin']);
});

// Admin routes
Route::prefix('admin')->group(function() {
    // Public admin routes (no auth required)
    Route::post('/login', [AdminLoginController::class, '__invoke']);
    Route::post('/verify-2fa', [TwoFactorController::class, 'verify']); // 2FA verification for admin (no auth required)
    
    // Protected admin routes (requires auth)
    Route::middleware(['jwt.auth'])->group(function() {
        // 2FA setup routes
        Route::get('/setup-2fa', [TwoFactorController::class, 'setup']);
        Route::post('/activate-2fa', [TwoFactorController::class, 'activate']);
        Route::post('/disable-2fa', [TwoFactorController::class, 'disable']);
        // Dashboard
        Route::get('/dashboard', [AdminDashboardController::class, 'index']);
        Route::get('/dashboard/statistics', [AdminDashboardController::class, 'getUserStatistics']);
        Route::get('/dashboard/active-users', [AdminDashboardController::class, 'getActiveUsers']);
        
        // Logout
        Route::post('/logout', [AdminLogoutController::class, '__invoke']);
        
        // User Management
        Route::prefix('users')->group(function() {
            Route::get('/', [UserManagementController::class, 'index']); // List all users
            Route::post('/', [UserManagementController::class, 'store']); // Create new user
            Route::get('/{id}', [UserManagementController::class, 'show']); // Get specific user
            Route::put('/{id}', [UserManagementController::class, 'update']); // Update user
            Route::delete('/{id}', [UserManagementController::class, 'destroy']); // Delete user
            Route::get('/auditors', [UserManagementController::class, 'getAuditors']); // List auditors
            Route::get('/auditees', [UserManagementController::class, 'getAuditees']); // List auditees
        });
        
        // Audit Management
        Route::prefix('audits')->group(function() {
            Route::get('/', [AuditManagementController::class, 'index']); // List all audits
            Route::get('/pending', [AuditManagementController::class, 'getPendingAudits']); // Get pending audits
            Route::get('/completed', [AuditManagementController::class, 'getCompletedAudits']); // Get completed audits
            Route::get('/{id}', [AuditManagementController::class, 'show']); // Get specific audit
            Route::put('/{id}', [AuditManagementController::class, 'update']); // Update audit
            Route::delete('/{id}', [AuditManagementController::class, 'destroy']); // Delete audit
            Route::post('/{id}/assign', [AuditManagementController::class, 'assignAuditor']); // Assign auditor to audit
            Route::get('/statistics', [AuditManagementController::class, 'getStatistics']); // Get audit statistics
        });
        
        // System Settings
        Route::prefix('settings')->group(function() {
            Route::get('/', [SettingsController::class, 'index']); // Get settings
            Route::put('/', [SettingsController::class, 'update']); // Update settings
        });
    });
});

// Routes for draft answers
Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('draft-answers')->group(function () {
        Route::post('/{questionId}/save', [App\Http\Controllers\Api\V1\DraftAnswerController::class, 'saveDraft']);
        Route::post('/{questionId}/submit', [App\Http\Controllers\Api\V1\DraftAnswerController::class, 'submitAnswer']);
        Route::get('/', [App\Http\Controllers\Api\V1\DraftAnswerController::class, 'getDraftAnswers']);
    });
});

Route::group(['middleware' => 'auth:api'], function () {
    // Questions routes
    Route::get('/auth/questions/{auditeeId}', [App\Http\Controllers\Api\Auth\QuestionController::class, 'index']);
    Route::post('/auth/questions/{auditeeId}/submit', [App\Http\Controllers\Api\Auth\QuestionController::class, 'submitAnswers']);
});