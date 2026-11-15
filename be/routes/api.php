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

    // TNA Proposals
    Route::post('/tna', [OperationApiController::class, 'storeTna']);
    Route::post('/tna/{id}/approve', [OperationApiController::class, 'approveTna']);

    // Class Operations & Dual Control
    Route::post('/classes', [OperationApiController::class, 'storeClass']);
    Route::post('/classes/{id}/approve', [OperationApiController::class, 'approveClass']);

    // Financial Vouchers & Dual Control
    Route::post('/transactions', [OperationApiController::class, 'storeTransaction']);
    Route::post('/transactions/{id}/approve', [OperationApiController::class, 'approveTransaction']);

    // Participant Tasks & Facilitator Grading
    Route::post('/enrollments/{id}/submit-task', [OperationApiController::class, 'submitTask']);
    Route::post('/enrollments/{id}/grade', [OperationApiController::class, 'gradeParticipant']);
});
