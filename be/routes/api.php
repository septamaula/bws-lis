<?php

use App\Http\Controllers\Api\ApprovalSettingApiController;
use App\Http\Controllers\Api\AuthApiController;
use App\Http\Controllers\Api\BiCategoryApiController;
use App\Http\Controllers\Api\CategoryApiController;
use App\Http\Controllers\Api\ClassApiController;
use App\Http\Controllers\Api\DashboardApiController;
use App\Http\Controllers\Api\FacilitatorApiController;
use App\Http\Controllers\Api\FiscalYearApiController;
use App\Http\Controllers\Api\MemoApiController;
use App\Http\Controllers\Api\OperationApiController;
use App\Http\Controllers\Api\ProgramApiController;
use App\Http\Controllers\Api\TnaApiController;
use App\Http\Controllers\Api\TrainingLocationApiController;
use App\Http\Controllers\Api\UserRbacApiController;
use Illuminate\Support\Facades\Route;

// Public Health & Demo Auth Discovery Endpoints
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'system' => 'BWS Learning Integrated System API',
        'php_version' => PHP_VERSION,
        'timestamp' => now()->toIso8601String(),
    ]);
});

Route::get('/demo-users', [AuthApiController::class, 'demoUsers']);
Route::post('/auth/login', [AuthApiController::class, 'login']);

// Document & Attachment Stream Routes (viewable directly in browser tab or via API client)
Route::get('/tna/{id}/attachment', [TnaApiController::class, 'downloadAttachment']);
Route::get('/programs/{id}/document', [ProgramApiController::class, 'downloadDocument']);
Route::get('/memos/{id}/document', [MemoApiController::class, 'downloadDocument']);

// Authenticated Banking API Endpoints (Protected by Laravel Sanctum)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/auth/me', [AuthApiController::class, 'me']);
    Route::post('/auth/switch-role', [AuthApiController::class, 'switchRole']);
    Route::post('/auth/logout', [AuthApiController::class, 'logout']);

    // Operational Dashboard & Audit Export
    Route::get('/dashboard', [DashboardApiController::class, 'index']);
    Route::get('/audit/export', [DashboardApiController::class, 'exportAuditLogs']);

    // Mobile PWA Learner Portal Endpoints
    Route::get('/portal/learner', [DashboardApiController::class, 'learnerPortal']);
    Route::post('/portal/enrollments/{id}/leave-confirmation', [DashboardApiController::class, 'learnerSubmitLeave']);
    Route::post('/portal/enrollments/{id}/evaluation', [DashboardApiController::class, 'learnerSubmitEvaluation']);
    Route::post('/portal/enrollments/{id}/commitment', [DashboardApiController::class, 'learnerSignCommitment']);

    // TNA Proposals & Multi-Approval Engine
    Route::get('/tna', [TnaApiController::class, 'index']);
    Route::get('/tna/{id}', [TnaApiController::class, 'show']);
    Route::post('/tna', [TnaApiController::class, 'store']);
    Route::match(['put', 'post'], '/tna/{id}', [TnaApiController::class, 'update']);
    Route::post('/tna/{id}/status', [TnaApiController::class, 'updateStatus']);
    Route::post('/tna/{id}/workflow-action', [TnaApiController::class, 'workflowAction']);
    Route::post('/tna/{id}/approve', [TnaApiController::class, 'workflowAction']);
    Route::delete('/tna/{id}', [TnaApiController::class, 'destroy']);

    // Master Categories & Sub-Categories (didalam sub ada categorynya)
    Route::get('/categories', [CategoryApiController::class, 'indexCategories']);
    Route::post('/categories', [CategoryApiController::class, 'storeCategory']);
    Route::get('/categories/{id}', [CategoryApiController::class, 'showCategory']);
    Route::put('/categories/{id}', [CategoryApiController::class, 'updateCategory']);
    Route::delete('/categories/{id}', [CategoryApiController::class, 'destroyCategory']);

    Route::get('/sub-categories', [CategoryApiController::class, 'indexSubCategories']);
    Route::post('/sub-categories', [CategoryApiController::class, 'storeSubCategory']);
    Route::get('/sub-categories/{id}', [CategoryApiController::class, 'showSubCategory']);
    Route::put('/sub-categories/{id}', [CategoryApiController::class, 'updateSubCategory']);
    Route::delete('/sub-categories/{id}', [CategoryApiController::class, 'destroySubCategory']);

    // Master Fiscal Years (Master Tahun Anggaran & Periode Diklat)
    Route::get('/fiscal-years', [FiscalYearApiController::class, 'index']);
    Route::post('/fiscal-years', [FiscalYearApiController::class, 'store']);
    Route::get('/fiscal-years/{id}', [FiscalYearApiController::class, 'show']);
    Route::put('/fiscal-years/{id}', [FiscalYearApiController::class, 'update']);
    Route::post('/fiscal-years/{id}/set-active', [FiscalYearApiController::class, 'setActive']);
    Route::delete('/fiscal-years/{id}', [FiscalYearApiController::class, 'destroy']);

    // Multi-Approval Module Settings
    Route::get('/approvals/settings', [ApprovalSettingApiController::class, 'index']);
    Route::post('/approvals/settings/{moduleCode}/steps', [ApprovalSettingApiController::class, 'storeStep']);
    Route::put('/approvals/settings/steps/{id}', [ApprovalSettingApiController::class, 'updateStep']);
    Route::delete('/approvals/settings/steps/{id}', [ApprovalSettingApiController::class, 'destroyStep']);

    // Training Programs & Work Plan (Terhubung TNA, RAB Komprehensif, Peserta, & Dual-Approval)
    Route::get('/programs', [ProgramApiController::class, 'index']);
    Route::get('/programs/{id}', [ProgramApiController::class, 'show']);
    Route::post('/programs', [ProgramApiController::class, 'store']);
    Route::match(['put', 'post'], '/programs/{id}', [ProgramApiController::class, 'update']);
    Route::post('/programs/{id}/workflow-action', [ProgramApiController::class, 'workflowAction']);
    Route::delete('/programs/{id}', [ProgramApiController::class, 'destroy']);

    // Master Facilitators & Training Providers
    Route::get('/facilitators', [FacilitatorApiController::class, 'index']);
    Route::post('/facilitators', [FacilitatorApiController::class, 'store']);
    Route::get('/facilitators/{id}', [FacilitatorApiController::class, 'show']);
    Route::put('/facilitators/{id}', [FacilitatorApiController::class, 'update']);
    Route::delete('/facilitators/{id}', [FacilitatorApiController::class, 'destroy']);

    // Master BI Categories (Regulasi BI & OJK)
    Route::get('/bi-categories', [BiCategoryApiController::class, 'index']);
    Route::post('/bi-categories', [BiCategoryApiController::class, 'store']);
    Route::get('/bi-categories/{id}', [BiCategoryApiController::class, 'show']);
    Route::put('/bi-categories/{id}', [BiCategoryApiController::class, 'update']);
    Route::delete('/bi-categories/{id}', [BiCategoryApiController::class, 'destroy']);

    // Master Training Locations & Rooms (Master Ruang & Lokasi Diklat)
    Route::get('/training-locations', [TrainingLocationApiController::class, 'index']);
    Route::post('/training-locations', [TrainingLocationApiController::class, 'store']);
    Route::get('/training-locations/{id}', [TrainingLocationApiController::class, 'show']);
    Route::put('/training-locations/{id}', [TrainingLocationApiController::class, 'update']);
    Route::delete('/training-locations/{id}', [TrainingLocationApiController::class, 'destroy']);

    // Class Batch Operations (1 Program to Many Classes), Attendance & Evaluation
    Route::get('/classes', [ClassApiController::class, 'index']);
    Route::get('/classes/{id}', [ClassApiController::class, 'show']);
    Route::post('/classes', [ClassApiController::class, 'store']);
    Route::put('/classes/{id}', [ClassApiController::class, 'update']);
    Route::delete('/classes/{id}', [ClassApiController::class, 'destroy']);
    Route::post('/classes/upload-attachment', [ClassApiController::class, 'uploadAttachment']);
    Route::post('/classes/{id}/approve', [ClassApiController::class, 'approve']);
    Route::post('/classes/{id}/enroll', [ClassApiController::class, 'enroll']);
    Route::delete('/classes/{id}/enroll/{enrollmentId}', [ClassApiController::class, 'removeEnrollment']);

    // Class Attendance Module
    Route::get('/classes/{id}/attendance', [ClassApiController::class, 'getAttendance']);
    Route::post('/classes/{id}/attendance', [ClassApiController::class, 'saveAttendance']);
    Route::post('/classes/{id}/attendance/quick-all', [ClassApiController::class, 'quickMarkAllPresent']);

    // Class Evaluation & Results Module
    Route::get('/classes/{id}/evaluation', [ClassApiController::class, 'getEvaluation']);
    Route::post('/classes/{id}/evaluation/scores', [ClassApiController::class, 'saveScores']);
    Route::post('/classes/{id}/import-participants', [ClassApiController::class, 'importParticipants']);
    Route::post('/classes/{id}/certificates/generate', [ClassApiController::class, 'generateCertificates']);

    // Bank-Wide Learning Evaluations Overview
    Route::get('/evaluations/overview', [ClassApiController::class, 'evaluationsOverview']);

    // Financial Vouchers & Dual Control
    Route::post('/transactions', [OperationApiController::class, 'storeTransaction']);
    Route::post('/transactions/{id}/approve', [OperationApiController::class, 'approveTransaction']);

    // Participant Tasks & Facilitator Grading
    Route::post('/enrollments/{id}/submit-task', [OperationApiController::class, 'submitTask']);
    Route::post('/enrollments/{id}/grade', [OperationApiController::class, 'gradeParticipant']);

    // User Management & RBAC Matrix Endpoints (LDMS Architecture)
    Route::get('/users', [UserRbacApiController::class, 'indexUsers']);
    Route::post('/users', [UserRbacApiController::class, 'storeUser']);
    Route::put('/users/{id}', [UserRbacApiController::class, 'updateUser']);
    Route::delete('/users/{id}', [UserRbacApiController::class, 'destroyUser']);

    // Master Karyawan Endpoints (Bank Woori Saudara HR Core)
    Route::get('/karyawan', [UserRbacApiController::class, 'indexKaryawan']);
    Route::post('/karyawan', [UserRbacApiController::class, 'storeKaryawan']);
    Route::put('/karyawan/{id}', [UserRbacApiController::class, 'updateKaryawan']);
    Route::delete('/karyawan/{id}', [UserRbacApiController::class, 'destroyKaryawan']);

    // RBAC Roles & Matrix
    Route::get('/rbac/levels', [UserRbacApiController::class, 'indexLevels']);
    Route::get('/rbac/levels/{id}/access', [UserRbacApiController::class, 'getLevelAccess']);
    Route::post('/rbac/levels/{id}/access', [UserRbacApiController::class, 'updateLevelAccess']);

    // Memo Management (Internal & Eksternal) - SS2
    Route::get('/memos', [MemoApiController::class, 'index']);
    Route::get('/memos/{id}', [MemoApiController::class, 'show']);
    Route::post('/memos', [MemoApiController::class, 'store']);
    Route::match(['put', 'post'], '/memos/{id}', [MemoApiController::class, 'update']);
    Route::delete('/memos/{id}', [MemoApiController::class, 'destroy']);
});
