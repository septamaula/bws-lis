<?php

namespace App\Http\Controllers;

use App\Models\ClassEnrollment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class LearnerPortalController extends Controller
{
    /**
     * Display the React + TypeScript Mobile PWA Learner Portal for Bank Woori Saudara Employees.
     */
    public function index(Request $request): Response
    {
        $user = Auth::user();

        // 1. Fetch user's enrollments with class & program details
        $enrollments = ClassEnrollment::with(['trainingClass.program'])
            ->where('user_id', $user->id)
            ->orderByDesc('id')
            ->get();

        // Transform into clean typed array for React
        $enrolledClasses = $enrollments->map(function ($e) {
            return [
                'id' => $e->id,
                'program_title' => $e->trainingClass?->program?->title ?? 'Program Pelatihan Bank',
                'program_code' => $e->trainingClass?->program?->code ?? 'TRN',
                'category' => $e->trainingClass?->program?->category ?? 'General Banking',
                'batch_number' => $e->trainingClass?->batch_number ?? 1,
                'start_date' => $e->trainingClass?->start_date ? substr($e->trainingClass->start_date, 0, 10) : '2026-06-01',
                'end_date' => $e->trainingClass?->end_date ? substr($e->trainingClass->end_date, 0, 10) : '2026-06-03',
                'location' => $e->trainingClass?->location ?? 'BWS Learning Center',
                'facilitator_name' => 'Dr. Hendra Gunawan',
                'attendance_percentage' => $e->attendance_percentage ?? 100,
                'completion_status' => $e->completion_status ?? 'ONGOING',
                'has_pre_test' => true,
                'has_post_test' => true,
                'certificate_number' => $e->certificate_number ?? "BWS-CERT-2026-{$e->id}",
            ];
        });

        // 2. Structured Task Data
        $tasks = [
            [
                'id' => 1,
                'title' => 'Action Learning Plan: Penerapan Analisis Risiko Kredit',
                'class_title' => 'Sertifikasi Manajemen Risiko SMR Level 2',
                'deadline' => '25 Mei 2026',
                'submitted_file' => 'Action_Plan_BWS_2026.pdf',
                'status' => 'GRADED',
                'grade' => 92,
            ],
            [
                'id' => 2,
                'title' => 'Studi Kasus: Kepatuhan Standar Anti-Money Laundering',
                'class_title' => 'Anti-Money Laundering (APU-PPT)',
                'deadline' => '30 Mei 2026',
                'submitted_file' => null,
                'status' => 'PENDING',
                'grade' => null,
            ],
        ];

        // 3. Learner KPIs
        $completedHours = 16;
        $targetHours = 20; // 20 Jam / Tahun Standar OJK

        // Set React PWA Root View
        Inertia::setRootView('portal');

        return Inertia::render('Portal/LearnerDashboard', [
            'kpis' => [
                'completed_hours' => $completedHours,
                'target_hours' => $targetHours,
                'enrolled_classes_count' => $enrolledClasses->count(),
                'active_certificates_count' => $enrolledClasses->where('completion_status', 'PASSED')->count(),
                'pending_tasks_count' => 1,
            ],
            'enrolledClasses' => $enrolledClasses->values()->all(),
            'tasks' => $tasks,
        ]);
    }
}
