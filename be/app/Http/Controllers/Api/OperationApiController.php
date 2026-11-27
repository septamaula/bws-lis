<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\ClassEnrollment;
use App\Models\FinancialTransaction;
use App\Models\TnaItem;
use App\Models\TnaProposal;
use App\Models\TrainingClass;
use App\Services\ParticipantScoringService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class OperationApiController extends Controller
{
    public function __construct(private readonly ParticipantScoringService $scoring) {}

    public function storeTna(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'department' => 'required|string|max:100',
            'year' => 'required|integer|min:2025|max:2030',
            'priority' => 'required|in:Critical,High,Medium,Low',
            'items' => 'required|array|min:1',
            'items.*.competency_gap' => 'required|string',
            'items.*.recommended_program' => 'required|string',
            'items.*.target_audience' => 'required|string',
            'items.*.target_participants_count' => 'required|integer|min:1',
            'items.*.estimated_cost' => 'required|numeric|min:0',
            'items.*.urgency' => 'required|in:Critical,High,Medium,Low',
        ]);

        $code = 'TNA-'.$validated['year'].'-'.strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $validated['department']), 0, 3)).'-'.str_pad(TnaProposal::count() + 1, 3, '0', STR_PAD_LEFT);
        $totalRequested = collect($validated['items'])->sum('estimated_cost');

        $proposal = TnaProposal::create([
            'proposal_code' => $code,
            'department' => $validated['department'],
            'year' => $validated['year'],
            'proposed_by' => $request->user()->id,
            'priority' => $validated['priority'],
            'total_budget_requested' => $totalRequested,
            'status' => 'SUBMITTED',
        ]);

        foreach ($validated['items'] as $item) {
            TnaItem::create([
                'tna_proposal_id' => $proposal->id,
                'competency_gap' => $item['competency_gap'],
                'recommended_program' => $item['recommended_program'],
                'target_audience' => $item['target_audience'],
                'target_participants_count' => $item['target_participants_count'],
                'estimated_cost' => $item['estimated_cost'],
                'urgency' => $item['urgency'],
            ]);
        }

        AuditLog::record('SUBMIT_TNA_PROPOSAL', $proposal, null, [
            'proposal_code' => $code,
            'department' => $proposal->department,
            'total_budget' => $totalRequested,
        ]);

        return response()->json([
            'message' => "Usulan TNA {$code} berhasil diajukan dan diteruskan ke HCBP untuk verifikasi.",
            'proposal' => $proposal->load(['items', 'proposer']),
        ], 201);
    }

    public function approveTna(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'decision' => 'required|in:APPROVED,REJECTED',
            'approved_budget' => 'nullable|numeric|min:0',
            'hcbp_notes' => 'nullable|string',
        ]);

        $proposal = TnaProposal::findOrFail($id);
        $user = $request->user();

        $proposal->update([
            'status' => $request->decision,
            'total_budget_approved' => $request->decision === 'APPROVED' ? ($request->approved_budget ?? $proposal->total_budget_requested) : 0,
            'hcbp_notes' => $request->hcbp_notes,
            'approved_by' => $user->id,
            'approved_at' => now(),
        ]);

        AuditLog::record('HCBP_DECISION_TNA', $proposal, null, [
            'decision' => $request->decision,
            'proposal_code' => $proposal->proposal_code,
            'approved_budget' => $proposal->total_budget_approved,
            'hcbp' => $user->nik,
        ]);

        return response()->json([
            'message' => "Usulan TNA {$proposal->proposal_code} telah diperbarui: {$request->decision}",
            'proposal' => $proposal->fresh(['items', 'proposer', 'approver']),
        ]);
    }

    public function storeClass(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'program_id' => 'required|exists:training_programs,id',
            'batch_name' => 'required|string|max:255',
            'method' => 'required|string|max:50',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'max_quota' => 'required|integer|min:5|max:100',
            'location_or_url' => 'required|string',
            'facilitator_id' => 'nullable|exists:users,id',
        ]);

        $code = 'CLS-'.date('Y').'-'.str_pad(TrainingClass::count() + 1, 3, '0', STR_PAD_LEFT);

        $class = TrainingClass::create([
            ...$validated,
            'class_code' => $code,
            'enrolled_count' => 0,
            'dual_control_status' => 'PENDING_CHECKER',
            'maker_id' => $request->user()->id,
            'checker_id' => null,
            'approved_at' => null,
        ]);

        AuditLog::record('MAKER_CREATE_CLASS', $class, null, [
            'class_code' => $code,
            'batch_name' => $class->batch_name,
            'dual_control' => 'Submitted to HC Learning Head for review',
        ]);

        return response()->json([
            'message' => "Batch {$code} berhasil didaftarkan oleh Maker dan menunggu Dual-Control Checker approval.",
            'trainingClass' => $class->load(['program', 'maker']),
        ], 201);
    }

    public function approveClass(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'decision' => 'required|in:APPROVED,REJECTED',
            'checker_notes' => 'nullable|string',
        ]);

        $class = TrainingClass::findOrFail($id);
        $user = $request->user();

        if ($class->maker_id === $user->id) {
            return response()->json([
                'message' => 'Pelanggaran Dual Control Perbankan: Maker dilarang menyetujui transaksi yang dibuat sendiri!',
            ], 403);
        }

        $oldStatus = $class->dual_control_status;
        $class->update([
            'dual_control_status' => $request->decision,
            'checker_id' => $user->id,
            'checker_notes' => $request->checker_notes,
            'approved_at' => now(),
        ]);

        AuditLog::record('CHECKER_DECISION_CLASS', $class, ['status' => $oldStatus], [
            'decision' => $request->decision,
            'checker' => $user->nik,
            'notes' => $request->checker_notes,
        ]);

        return response()->json([
            'message' => "Status Dual Control untuk {$class->class_code} berhasil diperbarui: {$request->decision}",
            'trainingClass' => $class->fresh(['program', 'maker', 'checker']),
        ]);
    }

    public function storeTransaction(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'training_class_id' => 'nullable|exists:training_classes,id',
            'category' => 'required|string|max:100',
            'description' => 'required|string',
            'amount' => 'required|numeric|min:10000',
            'receipt_attachment_url' => 'nullable|string|max:255',
        ]);

        $trxNumber = 'TRX-'.date('Y').'-'.str_pad(FinancialTransaction::count() + 1, 4, '0', STR_PAD_LEFT);

        $trx = FinancialTransaction::create([
            ...$validated,
            'trx_number' => $trxNumber,
            'maker_id' => $request->user()->id,
            'checker_id' => null,
            'status' => 'PENDING_APPROVAL',
        ]);

        AuditLog::record('MAKER_SUBMIT_FINANCIAL', $trx, null, [
            'trx_number' => $trxNumber,
            'amount' => $trx->amount,
            'category' => $trx->category,
        ]);

        return response()->json([
            'message' => "Pengajuan pencairan {$trxNumber} telah diteruskan ke Dual-Control Checker.",
            'transaction' => $trx->load(['trainingClass.program', 'maker']),
        ], 201);
    }

    public function approveTransaction(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'decision' => 'required|in:APPROVED,REJECTED',
            'checker_notes' => 'nullable|string',
        ]);

        $trx = FinancialTransaction::findOrFail($id);
        $user = $request->user();

        if ($trx->maker_id === $user->id) {
            return response()->json([
                'message' => 'Pelanggaran Standar Keuangan Perbankan: Maker dilarang menyetujui voucher dana miliknya!',
            ], 403);
        }

        $oldStatus = $trx->status;
        $trx->update([
            'status' => $request->decision,
            'checker_id' => $user->id,
            'checker_notes' => $request->checker_notes,
            'approved_at' => now(),
            'disbursed_at' => $request->decision === 'APPROVED' ? now() : null,
        ]);

        AuditLog::record('CHECKER_DECISION_FINANCIAL', $trx, ['status' => $oldStatus], [
            'decision' => $request->decision,
            'trx_number' => $trx->trx_number,
            'amount' => $trx->amount,
            'checker' => $user->nik,
        ]);

        return response()->json([
            'message' => "Transaksi {$trx->trx_number} berhasil diputuskan: {$request->decision}",
            'transaction' => $trx->fresh(['trainingClass.program', 'maker', 'checker']),
        ]);
    }

    public function submitTask(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'notes' => 'nullable|string|max:2000',
            'file' => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png|max:10240',
        ]);

        $enrollment = ClassEnrollment::findOrFail($id);
        $user = $request->user();

        $isAdminOrFacilitator = in_array(
            $user->role_name ?? '',
            ['Super Admin', 'HC Learning Head (Checker)', 'Staff HC Training (Maker)', 'Facilitator'],
            true
        );

        if ((int) $enrollment->user_id !== (int) $user->id && ! $isAdminOrFacilitator) {
            return response()->json([
                'message' => 'Anda tidak memiliki otorisasi untuk mengumpulkan tugas peserta lain.',
            ], 403);
        }

        $submissionUrl = $enrollment->task_submission_url;
        $storedFileName = null;

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $storedPath = $file->store('attachments/tasks', 'public');
            $submissionUrl = '/storage/'.$storedPath;
            $storedFileName = $file->getClientOriginalName();
        } elseif (! empty($validated['notes'])) {
            $nikClean = preg_replace('/[^a-zA-Z0-9_-]/', '_', strtolower($user->nik ?? 'bws'));
            $notePath = "attachments/tasks/action_plan_{$nikClean}_{$enrollment->id}_".time().'.txt';
            Storage::disk('public')->put($notePath, $validated['notes']);
            $submissionUrl = '/storage/'.$notePath;
            $storedFileName = basename($notePath);
        } else {
            return response()->json([
                'message' => 'Silakan unggah berkas Action Plan atau isi catatan implementasi terlebih dahulu.',
            ], 422);
        }

        $enrollment->update([
            'task_submission_url' => $submissionUrl,
            'task_submitted_at' => now(),
            'elapse_progress_pct' => max((int) ($enrollment->elapse_progress_pct ?? 0), 90),
        ]);

        AuditLog::record('PARTICIPANT_SUBMIT_TASK', $enrollment, null, [
            'class_id' => $enrollment->training_class_id,
            'file' => $storedFileName,
            'url' => $submissionUrl,
        ]);

        return response()->json([
            'message' => 'Tugas Paska Pelatihan / Action Plan berhasil disubmit ke Facilitator.',
            'enrollment' => $enrollment->fresh(['trainingClass.program', 'user']),
        ]);
    }

    public function gradeParticipant(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'post_test_score' => 'required|numeric|min:0|max:100',
            'task_score' => 'required|numeric|min:0|max:100',
            'task_feedback' => 'nullable|string',
        ]);

        $enrollment = ClassEnrollment::with(['trainingClass.program'])->findOrFail($id);
        $passingGrade = $this->scoring->resolvePassingGrade($enrollment->trainingClass);

        $preScore = $enrollment->pre_test_score !== null ? (float) $enrollment->pre_test_score : null;
        $postScore = (float) $request->post_test_score;
        $taskScore = (float) $request->task_score;

        $eval = $this->scoring->evaluateScores($preScore, $postScore, $taskScore, $passingGrade, $enrollment->completion_status ?? 'ONGOING');

        $enrollment->update([
            'post_test_score' => $postScore,
            'task_score' => $taskScore,
            'final_score' => $eval['final_score'],
            'grade' => $eval['grade'],
            'task_feedback' => $request->task_feedback,
            'completion_status' => $eval['completion_status'],
            'elapse_progress_pct' => 100,
        ]);

        $certId = $this->scoring->issueCertificateIfEligible($enrollment->fresh(['trainingClass.program']));

        AuditLog::record('FACILITATOR_GRADE_PARTICIPANT', $enrollment, null, [
            'final_score' => $eval['final_score'],
            'passed' => $eval['completion_status'] === 'PASSED',
            'certificate_id' => $certId,
        ]);

        return response()->json([
            'message' => "Penilaian peserta berhasil disimpan. Nilai akhir: {$eval['final_score']} (".($eval['completion_status'] === 'PASSED' ? 'LULUS' : 'REMEDIAL').')',
            'enrollment' => $enrollment->fresh(['trainingClass.program', 'user']),
        ]);
    }
}
