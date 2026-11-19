<?php

use App\Http\Controllers\Api\AuthApiController;
use App\Http\Controllers\Api\DashboardApiController;
use App\Http\Controllers\Api\OperationApiController;
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

// Authenticated Banking API Endpoints (Protected by Laravel Sanctum)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/auth/me', [AuthApiController::class, 'me']);
    Route::post('/auth/switch-role', [AuthApiController::class, 'switchRole']);
    Route::post('/auth/logout', [AuthApiController::class, 'logout']);

    // Operational Dashboard & Audit Export
    Route::get('/dashboard', [DashboardApiController::class, 'index']);
    Route::get('/audit/export', [DashboardApiController::class, 'exportAuditLogs']);

    // Mobile PWA Learner Portal Endpoint
    Route::get('/portal/learner', [DashboardApiController::class, 'learnerPortal']);

    // TNA Proposals & Multi-Approval Engine
    Route::get('/tna', [\App\Http\Controllers\Api\TnaApiController::class, 'index']);
    Route::get('/tna/{id}', [\App\Http\Controllers\Api\TnaApiController::class, 'show']);
    Route::get('/tna/{id}/attachment', [\App\Http\Controllers\Api\TnaApiController::class, 'downloadAttachment']);
    Route::post('/tna', [\App\Http\Controllers\Api\TnaApiController::class, 'store']);
    Route::match(['put', 'post'], '/tna/{id}', [\App\Http\Controllers\Api\TnaApiController::class, 'update']);
    Route::post('/tna/{id}/workflow-action', [\App\Http\Controllers\Api\TnaApiController::class, 'workflowAction']);
    Route::post('/tna/{id}/approve', [\App\Http\Controllers\Api\TnaApiController::class, 'workflowAction']);
    Route::delete('/tna/{id}', [\App\Http\Controllers\Api\TnaApiController::class, 'destroy']);

    // Master Categories & Sub-Categories (didalam sub ada categorynya)
    Route::get('/categories', [\App\Http\Controllers\Api\CategoryApiController::class, 'indexCategories']);
    Route::post('/categories', [\App\Http\Controllers\Api\CategoryApiController::class, 'storeCategory']);
    Route::get('/categories/{id}', [\App\Http\Controllers\Api\CategoryApiController::class, 'showCategory']);
    Route::put('/categories/{id}', [\App\Http\Controllers\Api\CategoryApiController::class, 'updateCategory']);
    Route::delete('/categories/{id}', [\App\Http\Controllers\Api\CategoryApiController::class, 'destroyCategory']);

    Route::get('/sub-categories', [\App\Http\Controllers\Api\CategoryApiController::class, 'indexSubCategories']);
    Route::post('/sub-categories', [\App\Http\Controllers\Api\CategoryApiController::class, 'storeSubCategory']);
    Route::get('/sub-categories/{id}', [\App\Http\Controllers\Api\CategoryApiController::class, 'showSubCategory']);
    Route::put('/sub-categories/{id}', [\App\Http\Controllers\Api\CategoryApiController::class, 'updateSubCategory']);
    Route::delete('/sub-categories/{id}', [\App\Http\Controllers\Api\CategoryApiController::class, 'destroySubCategory']);

    // Master Fiscal Years (Master Tahun Anggaran & Periode Diklat)
    Route::get('/fiscal-years', [\App\Http\Controllers\Api\FiscalYearApiController::class, 'index']);
    Route::post('/fiscal-years', [\App\Http\Controllers\Api\FiscalYearApiController::class, 'store']);
    Route::get('/fiscal-years/{id}', [\App\Http\Controllers\Api\FiscalYearApiController::class, 'show']);
    Route::put('/fiscal-years/{id}', [\App\Http\Controllers\Api\FiscalYearApiController::class, 'update']);
    Route::post('/fiscal-years/{id}/set-active', [\App\Http\Controllers\Api\FiscalYearApiController::class, 'setActive']);
    Route::delete('/fiscal-years/{id}', [\App\Http\Controllers\Api\FiscalYearApiController::class, 'destroy']);

    // Multi-Approval Module Settings
    Route::get('/approvals/settings', [\App\Http\Controllers\Api\ApprovalSettingApiController::class, 'index']);
    Route::post('/approvals/settings/{moduleCode}/steps', [\App\Http\Controllers\Api\ApprovalSettingApiController::class, 'storeStep']);
    Route::put('/approvals/settings/steps/{id}', [\App\Http\Controllers\Api\ApprovalSettingApiController::class, 'updateStep']);
    Route::delete('/approvals/settings/steps/{id}', [\App\Http\Controllers\Api\ApprovalSettingApiController::class, 'destroyStep']);

    // Training Programs & Work Plan (Terhubung TNA, RAB Komprehensif, Peserta, & Dual-Approval)
    Route::get('/programs', [\App\Http\Controllers\Api\ProgramApiController::class, 'index']);
    Route::get('/programs/{id}', [\App\Http\Controllers\Api\ProgramApiController::class, 'show']);
    Route::get('/programs/{id}/document', [\App\Http\Controllers\Api\ProgramApiController::class, 'downloadDocument']);
    Route::post('/programs', [\App\Http\Controllers\Api\ProgramApiController::class, 'store']);
    Route::match(['put', 'post'], '/programs/{id}', [\App\Http\Controllers\Api\ProgramApiController::class, 'update']);
    Route::post('/programs/{id}/workflow-action', [\App\Http\Controllers\Api\ProgramApiController::class, 'workflowAction']);
    Route::delete('/programs/{id}', [\App\Http\Controllers\Api\ProgramApiController::class, 'destroy']);

    // Class Batch Operations (1 Program to Many Classes), Attendance & Evaluation
    Route::get('/classes', [\App\Http\Controllers\Api\ClassApiController::class, 'index']);
    Route::get('/classes/{id}', [\App\Http\Controllers\Api\ClassApiController::class, 'show']);
    Route::post('/classes', [\App\Http\Controllers\Api\ClassApiController::class, 'store']);
    Route::put('/classes/{id}', [\App\Http\Controllers\Api\ClassApiController::class, 'update']);
    Route::post('/classes/{id}/approve', [\App\Http\Controllers\Api\ClassApiController::class, 'approve']);
    Route::post('/classes/{id}/enroll', [\App\Http\Controllers\Api\ClassApiController::class, 'enroll']);
    Route::delete('/classes/{id}/enroll/{enrollmentId}', [\App\Http\Controllers\Api\ClassApiController::class, 'removeEnrollment']);

    // Class Attendance Module
    Route::get('/classes/{id}/attendance', [\App\Http\Controllers\Api\ClassApiController::class, 'getAttendance']);
    Route::post('/classes/{id}/attendance', [\App\Http\Controllers\Api\ClassApiController::class, 'saveAttendance']);
    Route::post('/classes/{id}/attendance/quick-all', [\App\Http\Controllers\Api\ClassApiController::class, 'quickMarkAllPresent']);

    // Class Evaluation & Results Module
    Route::get('/classes/{id}/evaluation', [\App\Http\Controllers\Api\ClassApiController::class, 'getEvaluation']);
    Route::post('/classes/{id}/evaluation/scores', [\App\Http\Controllers\Api\ClassApiController::class, 'saveScores']);
    Route::post('/classes/{id}/certificates/generate', [\App\Http\Controllers\Api\ClassApiController::class, 'generateCertificates']);

    // Bank-Wide Learning Evaluations Overview
    Route::get('/evaluations/overview', [\App\Http\Controllers\Api\ClassApiController::class, 'evaluationsOverview']);

    // Financial Vouchers & Dual Control
    Route::post('/transactions', [OperationApiController::class, 'storeTransaction']);
    Route::post('/transactions/{id}/approve', [OperationApiController::class, 'approveTransaction']);

    // Participant Tasks & Facilitator Grading
    Route::post('/enrollments/{id}/submit-task', [OperationApiController::class, 'submitTask']);
    Route::post('/enrollments/{id}/grade', [OperationApiController::class, 'gradeParticipant']);

    // User Management & RBAC Matrix Endpoints (LDMS Architecture)
    Route::get('/users', [\App\Http\Controllers\Api\UserRbacApiController::class, 'indexUsers']);
    Route::post('/users', [\App\Http\Controllers\Api\UserRbacApiController::class, 'storeUser']);
    Route::put('/users/{id}', [\App\Http\Controllers\Api\UserRbacApiController::class, 'updateUser']);
    Route::delete('/users/{id}', [\App\Http\Controllers\Api\UserRbacApiController::class, 'destroyUser']);

    // Master Karyawan Endpoints (Bank Woori Saudara HR Core)
    Route::get('/karyawan', [\App\Http\Controllers\Api\UserRbacApiController::class, 'indexKaryawan']);
    Route::post('/karyawan', [\App\Http\Controllers\Api\UserRbacApiController::class, 'storeKaryawan']);
    Route::put('/karyawan/{id}', [\App\Http\Controllers\Api\UserRbacApiController::class, 'updateKaryawan']);
    Route::delete('/karyawan/{id}', [\App\Http\Controllers\Api\UserRbacApiController::class, 'destroyKaryawan']);

    // RBAC Roles & Matrix
    Route::get('/rbac/levels', [\App\Http\Controllers\Api\UserRbacApiController::class, 'indexLevels']);
    Route::get('/rbac/levels/{id}/access', [\App\Http\Controllers\Api\UserRbacApiController::class, 'getLevelAccess']);
    Route::post('/rbac/levels/{id}/access', [\App\Http\Controllers\Api\UserRbacApiController::class, 'updateLevelAccess']);
});
