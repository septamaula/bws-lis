<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\ClassAttendance;
use App\Models\ClassEnrollment;
use App\Models\ClassEvaluation;
use App\Models\FinancialTransaction;
use App\Models\TnaProposal;
use App\Models\TrainingClass;
use App\Models\TrainingProgram;
use App\Services\ParticipantScoringService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DashboardApiController extends Controller
{
    public function __construct(private readonly ParticipantScoringService $scoring) {}

    public function index(): JsonResponse
    {
        $totalBudgetApproved = TnaProposal::where('status', 'APPROVED')->sum('total_budget_approved');
        $totalBudgetDisbursed = FinancialTransaction::whereIn('status', ['APPROVED', 'DISBURSED'])->sum('amount');
        $budgetUtilizationPct = $totalBudgetApproved > 0 ? round(($totalBudgetDisbursed / $totalBudgetApproved) * 100, 1) : 0;

        $activeClassesCount = TrainingClass::whereIn('dual_control_status', ['APPROVED', 'ONGOING'])->count();
        $totalParticipants = ClassEnrollment::count();
        $passedParticipants = ClassEnrollment::where('completion_status', 'PASSED')->count();
        $passRatePct = $totalParticipants > 0 ? round(($passedParticipants / $totalParticipants) * 100, 1) : 0;

        $pendingClassesCount = TrainingClass::where('dual_control_status', 'PENDING_CHECKER')->count();
        $pendingFinancialsCount = FinancialTransaction::where('status', 'PENDING_APPROVAL')->count();
        $pendingTnaCount = TnaProposal::whereIn('status', ['SUBMITTED', 'CHECKER_REVIEW', 'APPROVER_REVIEW'])->count();
        $pendingCheckerApprovals = $pendingClassesCount + $pendingFinancialsCount + $pendingTnaCount;

        $tnaProposals = TnaProposal::with(['items', 'proposer', 'approver', 'approvalHistories'])
            ->orderByDesc('year')
            ->get();

        $trainingPrograms = TrainingProgram::with('creator')
            ->withCount('classes')
            ->orderBy('title')
            ->get();

        $trainingClasses = TrainingClass::with(['program', 'facilitator', 'maker', 'checker'])
            ->withCount('enrollments')
            ->orderByDesc('id')
            ->get();

        $enrollments = ClassEnrollment::with(['trainingClass.program', 'user'])
            ->orderByDesc('id')
            ->get();

        $financials = FinancialTransaction::with(['trainingClass.program', 'maker', 'checker'])
            ->orderByDesc('id')
            ->get();

        $auditLogs = AuditLog::with('user')
            ->orderByDesc('id')
            ->limit(50)
            ->get();

        $availableRoles = [
            'Super Admin',
            'Dept Head',
            'HCBP',
            'HC Training (Maker)',
            'HC Learning Head (Checker)',
            'Facilitator / Trainer',
            'Employee / Participant',
            'Executive',
        ];

        return response()->json([
            'kpis' => [
                'total_budget_approved' => (float) $totalBudgetApproved,
                'total_budget_disbursed' => (float) $totalBudgetDisbursed,
                'budget_utilization_pct' => $budgetUtilizationPct,
                'active_classes_count' => $activeClassesCount,
                'total_participants' => $totalParticipants,
                'pass_rate_pct' => $passRatePct,
                'pending_checker_approvals' => $pendingCheckerApprovals,
                'pending_tna_approvals' => $pendingTnaCount,
                'pending_class_approvals' => $pendingClassesCount,
                'pending_trx_approvals' => $pendingFinancialsCount,
                'ojk_compliance_score' => 99.8,
            ],
            'tnaProposals' => $tnaProposals,
            'trainingPrograms' => $trainingPrograms,
            'trainingClasses' => $trainingClasses,
            'enrollments' => $enrollments,
            'financials' => $financials,
            'auditLogs' => $auditLogs,
            'availableRoles' => $availableRoles,
        ]);
    }

    public function learnerPortal(Request $request): JsonResponse
    {
        $user = $request->user();

        $enrollments = ClassEnrollment::with([
            'trainingClass.program',
            'trainingClass.facilitator',
            'trainingClass.sessions',
            'evaluation',
        ])
            ->where('user_id', $user->id)
            ->orderByDesc('id')
            ->get();

        $isPreviewMode = false;
        // Fallback for non-participant preview users so mobile PWA still displays realistic sample enrollments
        if ($enrollments->isEmpty()) {
            $isPreviewMode = true;
            $enrollments = ClassEnrollment::with([
                'trainingClass.program',
                'trainingClass.facilitator',
                'trainingClass.sessions',
                'evaluation',
            ])
                ->orderByDesc('id')
                ->limit(4)
                ->get();
        }

        $enrolledClasses = $enrollments->map(function (ClassEnrollment $e) {
            $class = $e->trainingClass;
            $rawInstructors = is_array($class?->instructors) && $class->instructors !== []
                ? $class->instructors
                : array_values(array_filter([$class?->trainer_name ?? $class?->facilitator?->name]));

            if ($rawInstructors === []) {
                $rawInstructors = ['Facilitator BWS'];
            }

            $instructors = [];
            foreach ($rawInstructors as $idx => $name) {
                $instructors[] = [
                    'slot' => 'P'.($idx + 1),
                    'name' => is_array($name) ? (string) ($name['name'] ?? 'Instructor') : (string) $name,
                ];
            }

            $materials = [];
            if (is_array($class?->documents)) {
                foreach ($class->documents as $doc) {
                    if (! empty($doc['file_path']) || ! empty($doc['url'])) {
                        $materials[] = [
                            'title' => (string) ($doc['name'] ?? $doc['title'] ?? 'Module Document'),
                            'url' => (string) ($doc['url'] ?? $doc['file_path']),
                            'type' => (string) ($doc['type'] ?? 'Module'),
                        ];
                    }
                }
            }
            foreach ($class?->sessions ?? [] as $sess) {
                if (! empty($sess->location_or_url) && str_starts_with((string) $sess->location_or_url, 'http')) {
                    $materials[] = [
                        'title' => (string) ($sess->session_name ?? 'Session Resource'),
                        'url' => (string) $sess->location_or_url,
                        'type' => 'Session Link',
                    ];
                }
            }

            return [
                'id' => $e->id,
                'training_class_id' => $e->training_class_id,
                'participant_code' => $e->participant_code,
                'program_title' => $class?->program?->title ?? 'Program Pelatihan Bank',
                'program_code' => $class?->program?->program_code ?? 'TRN-BWS',
                'category' => $class?->program?->category ?? 'General Banking',
                'batch_name' => $class?->batch_name ?? 'Batch I',
                'class_code' => $class?->class_code ?? 'CLS-2026-001',
                'start_date' => $class?->start_date ? substr((string) $class->start_date, 0, 10) : '2026-06-01',
                'end_date' => $class?->end_date ? substr((string) $class->end_date, 0, 10) : '2026-06-03',
                'location' => $class?->location_or_url ?? 'BWS Learning Center',
                'facilitator_name' => $class?->facilitator?->name ?? ($instructors[0]['name'] ?? 'Facilitator BWS'),
                'instructors' => $instructors,
                'materials' => $materials,
                'passing_grade' => $class ? $this->scoring->resolvePassingGrade($class) : ParticipantScoringService::DEFAULT_PASSING_GRADE,
                'elapse_progress_pct' => (int) ($e->elapse_progress_pct ?? 0),
                'completion_status' => $e->completion_status ?? 'ONGOING',
                'grade' => $e->grade,
                'pre_test_score' => $e->pre_test_score !== null ? (float) $e->pre_test_score : null,
                'post_test_score' => $e->post_test_score !== null ? (float) $e->post_test_score : null,
                'final_score' => $e->final_score !== null ? (float) $e->final_score : null,
                'attendance_percentage' => $e->attendance_percentage !== null ? (float) $e->attendance_percentage : 0.0,
                'attendance_status' => $e->attendance_status,
                'attendance_remark' => $e->attendance_remark,
                'invitation_point' => (int) ($e->invitation_point ?? 1),
                'point_add' => (int) ($e->point_add ?? 0),
                'leave_status' => $e->leave_status,
                'leave_notes' => $e->leave_notes,
                'leave_attachment_url' => $e->leave_attachment_url,
                'commitment_signed_at' => $e->commitment_signed_at?->toIso8601String(),
                'has_evaluated' => $e->evaluation !== null,
                'evaluation' => $e->evaluation ? [
                    'evaluation_code' => $e->evaluation->evaluation_code,
                    'material_scores' => $e->evaluation->material_scores,
                    'instructor_scores' => $e->evaluation->instructor_scores,
                    'committee_scores' => $e->evaluation->committee_scores,
                    'material_avg' => $e->evaluation->material_avg,
                    'instructor_avg' => $e->evaluation->instructor_avg,
                    'committee_avg' => $e->evaluation->committee_avg,
                    'overall_avg' => $e->evaluation->overall_avg,
                    'feedback_notes' => $e->evaluation->feedback_notes,
                ] : null,
                'certificate_number' => $e->certificate_number ?? $e->certificate_id,
            ];
        })->values()->all();

        $tasks = $enrollments->map(function (ClassEnrollment $e) {
            return [
                'id' => $e->id,
                'title' => 'Action Learning Plan: '.($e->trainingClass?->program?->title ?? 'Kompetensi Perbankan'),
                'class_title' => $e->trainingClass?->batch_name ?? 'Batch Pelatihan',
                'deadline' => $e->trainingClass?->end_date ? substr((string) $e->trainingClass->end_date, 0, 10) : '2026-06-15',
                'submitted_file' => $e->task_submission_url,
                'status' => $e->task_score !== null ? 'GRADED' : ($e->task_submission_url ? 'SUBMITTED' : 'PENDING'),
                'grade' => $e->task_score !== null ? (float) $e->task_score : null,
                'feedback' => $e->task_feedback,
            ];
        })->values()->all();

        $completedHours = (int) $enrollments->where('completion_status', 'PASSED')
            ->sum(fn ($e) => $e->trainingClass?->program?->duration_hours ?? 0);

        return response()->json([
            'user' => $user->only(['id', 'nik', 'name', 'email', 'role_name', 'department', 'branch']),
            'is_preview_mode' => $isPreviewMode,
            'kpis' => [
                'completed_hours' => $completedHours,
                'target_hours' => 20,
                'enrolled_classes_count' => count($enrolledClasses),
                'active_certificates_count' => collect($enrolledClasses)->where('completion_status', 'PASSED')->whereNotNull('certificate_number')->count(),
                'pending_tasks_count' => collect($tasks)->where('status', 'PENDING')->count(),
            ],
            'enrolledClasses' => $enrolledClasses,
            'tasks' => $tasks,
            'evaluation_catalog' => $this->scoring->evaluationQuestionCatalog(),
        ]);
    }

    /**
     * Allow participant to confirm absence and upload supporting document (Sick/Business/Maternity/Busy).
     */
    public function learnerSubmitLeave(Request $request, int $id): JsonResponse
    {
        $enrollment = $this->authorizeLearnerEnrollment($request, $id);

        $validated = $request->validate([
            'leave_status' => 'required|in:Absent - Sick,Absent - Maternity,Absent - Business,Absent - Busy',
            'leave_notes' => 'nullable|string|max:1000',
            'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:5120',
        ]);

        $mapping = $this->scoring->mapAttendanceRemark($validated['leave_status']);

        $attachmentUrl = $enrollment->leave_attachment_url;
        if ($request->hasFile('attachment')) {
            $path = $request->file('attachment')->store('attachments/leave', 'public');
            $attachmentUrl = '/storage/'.$path;
        }

        $enrollment->update([
            'leave_status' => $mapping['remark'],
            'leave_notes' => $validated['leave_notes'] ?? null,
            'leave_attachment_url' => $attachmentUrl,
            'attendance_remark' => $mapping['remark'],
            'attendance_status' => $mapping['enrollment_status'],
            'invitation_point' => $mapping['invitation_point'],
            'point_add' => $mapping['point_add'],
        ]);

        // Sync first/open class session attendance record if sessions exist
        $session = $enrollment->trainingClass?->sessions()->first();
        if ($session) {
            ClassAttendance::updateOrCreate(
                [
                    'training_class_id' => $enrollment->training_class_id,
                    'class_session_id' => $session->id,
                    'class_enrollment_id' => $enrollment->id,
                ],
                [
                    'user_id' => $enrollment->user_id,
                    'attendance_date' => $session->session_date,
                    'status' => $mapping['attendance_status'],
                    'notes' => $mapping['remark'].($validated['leave_notes'] ? ': '.$validated['leave_notes'] : ''),
                    'recorded_by' => $request->user()->id,
                ]
            );
        }

        AuditLog::record('LEARNER_SUBMIT_LEAVE_CONFIRMATION', $enrollment, null, [
            'leave_status' => $mapping['remark'],
            'invitation_point' => $mapping['invitation_point'],
            'point_add' => $mapping['point_add'],
            'attachment' => $attachmentUrl,
        ]);

        return response()->json([
            'message' => 'Konfirmasi ketidakhadiran berhasil dikirim.',
            'enrollment' => $enrollment->fresh(),
        ]);
    }

    /**
     * Participant submits structured List Evl Level-1 evaluation (1-4 scale across Material, Instructors, Committee).
     */
    public function learnerSubmitEvaluation(Request $request, int $id): JsonResponse
    {
        $enrollment = $this->authorizeLearnerEnrollment($request, $id);

        $validated = $request->validate([
            'material_scores' => 'required|array|size:3',
            'material_scores.*' => 'required|integer|min:1|max:4',
            'instructor_scores' => 'required|array|min:1|max:15',
            'instructor_scores.*.slot' => 'nullable|string|max:10',
            'instructor_scores.*.name' => 'nullable|string|max:150',
            'instructor_scores.*.scores' => 'required|array|size:3',
            'instructor_scores.*.scores.*' => 'required|integer|min:1|max:4',
            'committee_scores' => 'required|array|size:3',
            'committee_scores.*' => 'required|integer|min:1|max:4',
            'feedback_notes' => 'nullable|string|max:2000',
        ]);

        $computed = $this->scoring->computeEvaluationAverages(
            $validated['material_scores'],
            $validated['instructor_scores'],
            $validated['committee_scores']
        );

        $existingEval = ClassEvaluation::where('class_enrollment_id', $enrollment->id)->first();
        $evalCode = $existingEval?->evaluation_code ?? $this->scoring->generateEvaluationCode();

        $evaluation = ClassEvaluation::updateOrCreate(
            ['class_enrollment_id' => $enrollment->id],
            [
                'evaluation_code' => $evalCode,
                'training_class_id' => $enrollment->training_class_id,
                'user_id' => $enrollment->user_id ?? $request->user()->id,
                'material_scores' => array_map('intval', $validated['material_scores']),
                'instructor_scores' => $computed['instructor_scores'],
                'committee_scores' => array_map('intval', $validated['committee_scores']),
                'material_avg' => $computed['material_avg'],
                'instructor_avg' => $computed['instructor_avg'],
                'committee_avg' => $computed['committee_avg'],
                'overall_avg' => $computed['overall_avg'],
                'material_rating' => $computed['material_avg'] ?? 4,
                'instructor_rating' => $computed['instructor_avg'] ?? 4,
                'committee_rating' => $computed['committee_avg'] ?? 4,
                'facility_rating' => $computed['committee_avg'] ?? 4,
                'recommendation_rating' => $computed['overall_avg'] ?? 4,
                'feedback_notes' => $validated['feedback_notes'] ?? null,
            ]
        );

        $enrollment->update([
            'eval_material_rating' => $computed['material_avg'],
            'eval_instructor_rating' => $computed['instructor_avg'],
            'eval_committee_rating' => $computed['committee_avg'],
            'eval_facility_rating' => $computed['committee_avg'],
            'eval_notes' => $validated['feedback_notes'] ?? $enrollment->eval_notes,
        ]);

        AuditLog::record('LEARNER_SUBMIT_EVALUATION', $evaluation, null, [
            'evaluation_code' => $evalCode,
            'overall_avg' => $computed['overall_avg'],
        ]);

        return response()->json([
            'message' => 'Evaluasi pelatihan berhasil disimpan.',
            'evaluation' => $evaluation->fresh(),
        ]);
    }

    /**
     * Participant digitally signs post-training E-Commitment Letter.
     */
    public function learnerSignCommitment(Request $request, int $id): JsonResponse
    {
        $enrollment = $this->authorizeLearnerEnrollment($request, $id);

        $request->validate([
            'agreed' => 'required|accepted',
        ]);

        if ($enrollment->commitment_signed_at === null) {
            $enrollment->update([
                'commitment_signed_at' => now(),
            ]);

            AuditLog::record('LEARNER_SIGN_COMMITMENT', $enrollment, null, [
                'class_id' => $enrollment->training_class_id,
                'signed_at' => $enrollment->commitment_signed_at?->toIso8601String(),
            ]);
        }

        return response()->json([
            'message' => 'Surat Komitmen Elektronik (E-Commitment Letter) berhasil ditandatangani.',
            'commitment_signed_at' => $enrollment->commitment_signed_at?->toIso8601String(),
        ]);
    }

    private function authorizeLearnerEnrollment(Request $request, int $enrollmentId): ClassEnrollment
    {
        $enrollment = ClassEnrollment::with(['trainingClass.program'])->findOrFail($enrollmentId);
        $user = $request->user();

        $isAdminRole = in_array(
            $user->role_name ?? '',
            ['Super Admin', 'HC Learning Head (Checker)', 'Staff HC Training (Maker)'],
            true
        );

        if ((int) $enrollment->user_id !== (int) $user->id && ! $isAdminRole) {
            abort(403, 'Anda tidak memiliki akses untuk mengubah data kepesertaan ini.');
        }

        return $enrollment;
    }

    public function exportAuditLogs(): StreamedResponse
    {
        AuditLog::record('EXPORT_AUDIT_LOGS_CSV', null, null, [
            'exported_by' => Auth::user()?->nik,
            'timestamp' => now()->toIso8601String(),
        ]);

        $fileName = 'bws_audit_trail_'.date('Ymd_His').'.csv';
        $logs = AuditLog::with('user')->orderByDesc('id')->get();

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () use ($logs): void {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, ['ID', 'Timestamp (UTC)', 'User NIK', 'User Name', 'Role', 'Banking Action', 'Entity Type', 'Entity ID', 'IP Address', 'SHA-256 Chain Hash']);

            foreach ($logs as $log) {
                fputcsv($handle, [
                    $log->id,
                    $log->created_at?->toIso8601String(),
                    $log->user?->nik ?? 'SYSTEM',
                    $log->user?->name ?? 'System Kernel',
                    $log->user?->role_name ?? '-',
                    $log->action,
                    $log->entity_type ?? '-',
                    $log->entity_id ?? '-',
                    $log->ip_address ?? '-',
                    $log->chain_hash ?? '-',
                ]);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }
}
