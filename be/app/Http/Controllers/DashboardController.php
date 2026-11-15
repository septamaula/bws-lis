<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\ClassEnrollment;
use App\Models\FinancialTransaction;
use App\Models\TnaProposal;
use App\Models\TrainingClass;
use App\Models\TrainingProgram;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $user = Auth::user();

        // 1. KPI Calculations
        $totalBudgetApproved = TnaProposal::where('status', 'APPROVED')->sum('total_budget_approved');
        $totalBudgetDisbursed = FinancialTransaction::where('status', 'APPROVED')->sum('amount');
        $budgetUtilizationPct = $totalBudgetApproved > 0 ? round(($totalBudgetDisbursed / $totalBudgetApproved) * 100, 1) : 0;

        $activeClassesCount = TrainingClass::whereIn('dual_control_status', ['APPROVED', 'ONGOING'])->count();
        $totalParticipants = ClassEnrollment::count();
        $passedParticipants = ClassEnrollment::where('completion_status', 'PASSED')->count();
        $passRatePct = $totalParticipants > 0 ? round(($passedParticipants / $totalParticipants) * 100, 1) : 0;

        $pendingCheckerApprovals = TrainingClass::where('dual_control_status', 'PENDING_CHECKER')->count()
            + FinancialTransaction::where('status', 'PENDING_APPROVAL')->count()
            + TnaProposal::where('status', 'SUBMITTED')->count();

        // 2. Fetch Module Data
        $tnaProposals = TnaProposal::with(['items', 'proposer', 'approver'])
            ->orderByDesc('id')
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

        $financials = FinancialTransaction::with(['trainingClass', 'maker', 'checker'])
            ->orderByDesc('id')
            ->get();

        $auditLogs = AuditLog::orderByDesc('id')
            ->limit(20)
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

        return Inertia::render('Dashboard', [
            'kpis' => [
                'total_budget_approved' => (float) $totalBudgetApproved,
                'total_budget_disbursed' => (float) $totalBudgetDisbursed,
                'budget_utilization_pct' => $budgetUtilizationPct,
                'active_classes_count' => $activeClassesCount,
                'total_participants' => $totalParticipants,
                'pass_rate_pct' => $passRatePct,
                'pending_checker_approvals' => $pendingCheckerApprovals,
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

    /**
     * Export Immutable Audit Logs as CSV for OJK / Bank Indonesia compliance inspection.
     */
    public function exportAuditLogs()
    {
        AuditLog::record('EXPORT_AUDIT_LOGS_CSV', null, null, [
            'exported_by' => Auth::user()?->nik,
            'timestamp' => now()->toIso8601String(),
        ]);

        $fileName = 'bws_audit_trail_'.date('Ymd_His').'.csv';
        $logs = AuditLog::orderByDesc('id')->get();

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () use ($logs) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['ID', 'Timestamp (UTC)', 'User NIK', 'User Name', 'Role', 'Banking Action', 'Entity Type', 'Entity ID', 'IP Address', 'SHA-256 Chain Hash']);

            foreach ($logs as $log) {
                fputcsv($handle, [
                    $log->id,
                    $log->created_at?->toIso8601String(),
                    $log->user_nik,
                    $log->user_name,
                    $log->role,
                    $log->action,
                    $log->entity_type,
                    $log->entity_id,
                    $log->ip_address,
                    $log->hash,
                ]);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }
}
