<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'service' => 'BWS Learning Integrated System (LIS) — Backend REST API',
        'framework' => 'Laravel '.app()->version(),
        'php' => PHP_VERSION,
        'status' => 'running',
        'endpoints' => [
            'api_health' => url('/api/health'),
            'fe_ops_portal' => 'http://localhost:5173',
            'pwa_learner_portal' => 'http://localhost:5174',
        ],
    ]);
});

Route::get('/login', function () {
    return response()->json(['message' => 'Unauthenticated.'], 401);
})->name('login');
