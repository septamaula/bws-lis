<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ApprovalFlowStep;
use App\Models\ApprovalHistory;
use App\Models\ApprovalModule;
use App\Models\AuditLog;
use App\Models\FiscalYear;
use App\Models\Category;
use App\Models\SubCategory;
use App\Models\TnaItem;
use App\Models\TnaProposal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TnaApiController extends Controller
{
    /**
     * GET /api/tna
     * Mengambil daftar usulan TNA beserta ringkasan status multi-approval
     */
    public function index(Request $request): JsonResponse
    {
        $year = $request->query('year', 2026);

        $query = TnaProposal::with(['items.category', 'items.subCategory', 'proposer', 'fiscalYear'])
            ->where('year', $year)
            ->orderByDesc('id');

        if ($request->filled('department') && $request->department !== 'ALL') {
            $query->where('department', $request->department);
        }

        if ($request->filled('status') && $request->status !== 'ALL') {
            $query->where('status', $request->status);
        }

        $proposals = $query->get();

        $stats = [
            'total_proposals' => $proposals->count(),
            'total_requested' => (float) $proposals->sum('total_budget_requested'),
            'total_approved' => (float) $proposals->where('status', 'APPROVED')->sum('total_budget_approved'),
            'total_items_count' => (int) $proposals->sum(fn($p) => $p->items->count()),
        ];

        return response()->json([
            'proposals' => $proposals,
            'stats' => $stats,
        ]);
    }

    /**
     * GET /api/tna/{id}
     * Mengambil detail lengkap dokumen TNA, rincian kompetensi, dan alur multi-approval
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $proposal = TnaProposal::with(['items.category', 'items.subCategory', 'proposer', 'approver', 'approvalHistories.approver', 'fiscalYear'])
            ->findOrFail($id);

        $tnaModule = ApprovalModule::where('module_code', 'tna')->first();
        $flowSteps = $tnaModule ? $tnaModule->flowSteps()->where('is_active', true)->orderBy('step_order')->get() : collect();

        $user = $request->user();
        $userRole = $user?->role_name ?? '';
        $isSuperAdmin = ($userRole === 'Super Admin' || ($user?->level && $user->level->is_admin));

        // Cari step aktif saat ini
        $currentStep = $flowSteps->firstWhere('step_order', $proposal->current_step_order);

        // Apakah user saat ini berhak melakukan approval pada step ini?
        $canApprove = false;
        if (in_array($proposal->status, ['SUBMITTED', 'CHECKER_REVIEW', 'APPROVER_REVIEW'])) {
            if ($isSuperAdmin) {
                $canApprove = true;
            } elseif ($currentStep && $currentStep->role_name === $userRole) {
                $canApprove = true;
            }
        }

        $isProposer = ($user && $user->id === $proposal->proposed_by);

        return response()->json([
            'proposal' => $proposal,
            'flowSteps' => $flowSteps,
            'currentStep' => $currentStep,
            'canApprove' => $canApprove,
            'isProposer' => $isProposer,
        ]);
    }

    /**
     * POST /api/tna
     * Menyimpan usulan TNA baru (DRAFT atau diajukan langsung ke Multi-Approval)
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'department' => 'required|string|max:100',
            'year' => 'required|integer|min:2020|max:2040',
            'priority' => 'nullable|in:Critical,High,Medium,Low',
            'target_quarter' => 'nullable|string|max:50',
            'strategic_objective' => 'required|string',
            'is_submit' => 'required|boolean',
            'attachment' => 'nullable|file|max:15360',
            'attachment_name' => 'nullable|string|max:255',
            'attachment_size' => 'nullable|integer',
            'items' => 'required|array|min:1',
            'items.*.category_id' => 'nullable|exists:categories,id',
            'items.*.category_name' => 'nullable|string',
            'items.*.sub_category_id' => 'nullable|exists:sub_categories,id',
            'items.*.sub_category_name' => 'nullable|string',
            'items.*.budget' => 'nullable|numeric|min:0',
            'items.*.execution_quarter' => 'nullable|string|max:50',
            'items.*.target_participants_count' => 'nullable|integer|min:1',
            'items.*.job_family' => 'nullable|string|max:100',
            'items.*.competency_gap' => 'nullable|string',
            'items.*.recommended_program' => 'nullable|string',
            'items.*.training_type' => 'nullable|string|max:100',
            'items.*.target_audience' => 'nullable|string',
            'items.*.cost_per_participant' => 'nullable|numeric|min:0',
            'items.*.urgency' => 'nullable|in:Critical,High,Medium,Low',
            'items.*.impact_kpi' => 'nullable|string',
        ]);

        $user = $request->user();

        // Handle File Attachment
        $attachmentPath = null;
        $attachmentName = $validated['attachment_name'] ?? null;
        $attachmentSize = $validated['attachment_size'] ?? null;

        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $attachmentName = $file->getClientOriginalName();
            $attachmentSize = $file->getSize();
            $attachmentPath = $file->store('attachments/tna', 'public');
        } elseif ($attachmentName && !$attachmentPath) {
            $attachmentPath = 'attachments/tna/' . uniqid() . '_' . preg_replace('/[^A-Za-z0-9_\-\.]/', '', $attachmentName);
        }

        // Find fiscal year
        $fiscalYear = \App\Models\FiscalYear::where('year', $validated['year'])->first();

        // Hitung total anggaran
        $totalRequested = 0;
        foreach ($validated['items'] as &$item) {
            $budget = isset($item['budget']) ? (float)$item['budget'] : (float)($item['estimated_cost'] ?? (($item['target_participants_count'] ?? 1) * ($item['cost_per_participant'] ?? 0)));
            $item['budget'] = $budget;
            $item['estimated_cost'] = $budget;
            $totalRequested += $budget;
        }

        // Generate nomor kode proposal unik perbankan
        $deptShort = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $validated['department']), 0, 3));
        $count = TnaProposal::where('year', $validated['year'])->count() + 1;
        $proposalCode = 'TNA-' . $validated['year'] . '-' . $deptShort . '-' . str_pad($count, 3, '0', STR_PAD_LEFT);

        $isSubmit = $validated['is_submit'];
        $status = $isSubmit ? 'SUBMITTED' : 'DRAFT';
        $badge = $isSubmit ? 'Menunggu Review HCBP' : 'Draft Usulan';
        $priority = $validated['priority'] ?? 'High';
        $targetQuarter = $validated['target_quarter'] ?? 'Q1 - Q2 ' . $validated['year'];

        try {
            DB::beginTransaction();

            $proposal = TnaProposal::create([
                'proposal_code' => $proposalCode,
                'department' => $validated['department'],
                'year' => $validated['year'],
                'fiscal_year_id' => $fiscalYear?->id,
                'proposed_by' => $user->id,
                'priority' => $priority,
                'total_budget_requested' => $totalRequested,
                'total_budget_approved' => null,
                'status' => $status,
                'strategic_objective' => $validated['strategic_objective'],
                'target_quarter' => $targetQuarter,
                'current_step_order' => 1,
                'approval_status_badge' => $badge,
                'revision_count' => 0,
                'attachment_path' => $attachmentPath,
                'attachment_name' => $attachmentName,
                'attachment_size' => $attachmentSize,
            ]);

            // Cache Category & SubCategory lookups
            $allCats = \App\Models\Category::all()->keyBy('id');
            $allSubs = \App\Models\SubCategory::all()->keyBy('id');

            foreach ($validated['items'] as $item) {
                $catId = $item['category_id'] ?? null;
                $subId = $item['sub_category_id'] ?? null;

                $catObj = $catId ? $allCats->get($catId) : null;
                $subObj = $subId ? $allSubs->get($subId) : null;

                $catName = $item['category_name'] ?? ($catObj?->name ?? 'Operasional & Perbankan');
                $subName = $item['sub_category_name'] ?? ($subObj?->name ?? ($item['recommended_program'] ?? 'Program Diklat'));
                $compGap = $item['competency_gap'] ?? ($subObj?->target_competency ?? $subObj?->description ?? $subName);
                $execQ = $item['execution_quarter'] ?? 'Q1';
                $partCount = $item['target_participants_count'] ?? 10;
                $budget = $item['budget'] ?? 0;
                $costPerPart = $partCount > 0 ? round($budget / $partCount, 2) : $budget;

                TnaItem::create([
                    'tna_proposal_id' => $proposal->id,
                    'category_id' => $catId,
                    'category_name' => $catName,
                    'sub_category_id' => $subId,
                    'sub_category_name' => $subName,
                    'budget' => $budget,
                    'job_family' => $item['job_family'] ?? $catName,
                    'competency_gap' => $compGap,
                    'recommended_program' => $subName,
                    'training_type' => $item['training_type'] ?? 'In-House Training (Internal Academy)',
                    'target_audience' => $item['target_audience'] ?? 'Staff & Officer Divisi ' . $proposal->department,
                    'target_participants_count' => $partCount,
                    'cost_per_participant' => $costPerPart,
                    'estimated_cost' => $budget,
                    'execution_quarter' => $execQ,
                    'urgency' => $item['urgency'] ?? $priority,
                    'impact_kpi' => $item['impact_kpi'] ?? 'Mendukung target kinerja divisi & audit regulasi OJK',
                ]);
            }

            // Catat history jika langsung diajukan ke Multi-Approval
            if ($isSubmit) {
                ApprovalHistory::create([
                    'module_code' => 'tna',
                    'record_id' => $proposal->id,
                    'step_order' => 1,
                    'step_name' => "Pengajuan Dokumen TNA Divisi {$proposal->department}",
                    'approver_id' => $user->id,
                    'approver_name' => $user->name,
                    'approver_nik' => $user->nik,
                    'role_name' => $user->role_name ?? 'Dept Head',
                    'action' => 'SUBMITTED',
                    'notes' => "Usulan TNA resmi diajukan dengan {$proposal->items()->count()} kebutuhan diklat. Sasaran: {$proposal->strategic_objective}",
                    'approved_amount' => $totalRequested,
                    'action_at' => now(),
                ]);
            }

            AuditLog::create([
                'user_id' => $user->id,
                'action' => $isSubmit ? 'SUBMIT_TNA_PROPOSAL' : 'DRAFT_TNA_PROPOSAL',
                'entity_type' => 'TnaProposal',
                'entity_id' => $proposal->id,
                'ip_address' => $request->ip(),
                'details' => json_encode(['code' => $proposalCode, 'department' => $proposal->department, 'total' => $totalRequested]),
            ]);

            DB::commit();

            return response()->json([
                'message' => $isSubmit
                    ? "Usulan TNA {$proposalCode} berhasil diajukan ke Alur Multi-Approval (Step 1 HCBP)."
                    : "Draft usulan TNA {$proposalCode} berhasil disimpan.",
                'proposal' => $proposal->load('items'),
            ], 201);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Gagal memproses usulan TNA: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * POST /api/tna/{id}/workflow-action
     * Otorisasi berjenjang TNA (APPROVE, REJECT, REQUEST_REVISION)
     */
    public function workflowAction(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'decision' => 'required|in:APPROVE,REJECT,REQUEST_REVISION',
            'notes' => 'required|string|min:5',
            'approved_budget' => 'nullable|numeric|min:0',
        ]);

        $proposal = TnaProposal::with('items')->findOrFail($id);
        $user = $request->user();
        $userRole = $user?->role_name ?? '';
        $isSuperAdmin = ($userRole === 'Super Admin' || ($user?->level && $user->level->is_admin));

        $tnaModule = ApprovalModule::where('module_code', 'tna')->first();
        $flowSteps = $tnaModule ? $tnaModule->flowSteps()->where('is_active', true)->orderBy('step_order')->get() : collect();
        $currentStep = $flowSteps->firstWhere('step_order', $proposal->current_step_order);

        // Validasi otorisasi peran
        if (!$isSuperAdmin && (!$currentStep || $currentStep->role_name !== $userRole)) {
            return response()->json([
                'message' => 'Anda tidak memiliki hak akses otorisasi untuk tahapan approval saat ini (' . ($currentStep?->step_name ?? 'N/A') . ').',
            ], 403);
        }

        try {
            DB::beginTransaction();

            $decision = $validated['decision'];
            $notes = $validated['notes'];
            $stepOrder = $proposal->current_step_order;
            $stepName = $currentStep ? $currentStep->step_name : "Tahapan {$stepOrder}";

            if ($decision === 'APPROVE') {
                $approvedBudget = $validated['approved_budget'] ?? $proposal->total_budget_approved ?? $proposal->total_budget_requested;
                $proposal->total_budget_approved = $approvedBudget;

                // Cek apakah ada tahapan berikutnya
                $nextStep = $flowSteps->firstWhere('step_order', $stepOrder + 1);

                if ($nextStep) {
                    // Maju ke tahapan berikutnya
                    $proposal->current_step_order = $nextStep->step_order;
                    $proposal->status = 'CHECKER_REVIEW';
                    $proposal->approval_status_badge = "Menunggu {$nextStep->step_name}";
                } else {
                    // Tahapan Terakhir: FINAL APPROVED
                    $proposal->status = 'APPROVED';
                    $proposal->approval_status_badge = 'Disetujui Final (Kadiv HC)';
                    $proposal->approved_by = $user->id;
                    $proposal->approved_at = now();
                }

                ApprovalHistory::create([
                    'module_code' => 'tna',
                    'record_id' => $proposal->id,
                    'step_order' => $stepOrder,
                    'step_name' => $stepName,
                    'approver_id' => $user->id,
                    'approver_name' => $user->name,
                    'approver_nik' => $user->nik,
                    'role_name' => $userRole,
                    'action' => 'APPROVED',
                    'notes' => $notes,
                    'approved_amount' => $approvedBudget,
                    'action_at' => now(),
                ]);

                $msg = $nextStep
                    ? "Tahapan '{$stepName}' berhasil disetujui. Berkas diteruskan ke: {$nextStep->step_name}."
                    : "Usulan TNA {$proposal->proposal_code} telah disetujui secara FINAL dan resmi disahkan ke Annual Training Plan.";

            } elseif ($decision === 'REJECT') {
                $proposal->status = 'REJECTED';
                $proposal->approval_status_badge = 'Usulan Ditolak';
                $proposal->rejection_notes = $notes;

                ApprovalHistory::create([
                    'module_code' => 'tna',
                    'record_id' => $proposal->id,
                    'step_order' => $stepOrder,
                    'step_name' => $stepName,
                    'approver_id' => $user->id,
                    'approver_name' => $user->name,
                    'approver_nik' => $user->nik,
                    'role_name' => $userRole,
                    'action' => 'REJECTED',
                    'notes' => $notes,
                    'approved_amount' => 0,
                    'action_at' => now(),
                ]);

                $msg = "Usulan TNA {$proposal->proposal_code} telah DITOLAK pada tahapan {$stepName}.";

            } else { // REQUEST_REVISION
                $proposal->status = 'REVISED';
                $proposal->approval_status_badge = 'Perlu Revisi Pengusul';
                $proposal->revision_count += 1;
                $proposal->rejection_notes = $notes;

                ApprovalHistory::create([
                    'module_code' => 'tna',
                    'record_id' => $proposal->id,
                    'step_order' => $stepOrder,
                    'step_name' => $stepName,
                    'approver_id' => $user->id,
                    'approver_name' => $user->name,
                    'approver_nik' => $user->nik,
                    'role_name' => $userRole,
                    'action' => 'REVISED',
                    'notes' => $notes,
                    'approved_amount' => null,
                    'action_at' => now(),
                ]);

                $msg = "Permintaan revisi berhasil dikirimkan kembali kepada pengusul ({$proposal->department}).";
            }

            $proposal->save();

            AuditLog::create([
                'user_id' => $user->id,
                'action' => 'WORKFLOW_' . $decision . '_TNA',
                'entity_type' => 'TnaProposal',
                'entity_id' => $proposal->id,
                'ip_address' => $request->ip(),
                'details' => json_encode(['code' => $proposal->proposal_code, 'step' => $stepName, 'decision' => $decision]),
            ]);

            DB::commit();

            return response()->json([
                'message' => $msg,
                'proposal' => $proposal->fresh(['items', 'proposer', 'approver', 'approvalHistories.approver']),
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Gagal memproses aksi otorisasi: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * PUT/POST /api/tna/{id}
     * Memperbarui / merevisi dokumen usulan TNA dan rincian tabel kebutuhannya
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $proposal = TnaProposal::findOrFail($id);
        $user = $request->user();
        $userRole = $user?->role_name ?? '';
        $isSuperAdmin = ($userRole === 'Super Admin' || ($user?->level && $user->level->is_admin));

        // Hanya boleh diedit jika belum APPROVED final, atau oleh Super Admin
        if ($proposal->status === 'APPROVED' && !$isSuperAdmin) {
            return response()->json([
                'message' => 'Dokumen TNA yang telah berstatus Disetujui Final (APPROVED) tidak dapat diubah.',
            ], 422);
        }

        $validated = $request->validate([
            'department' => 'sometimes|required|string|max:100',
            'year' => 'sometimes|required|integer|min:2020|max:2040',
            'priority' => 'nullable|in:Critical,High,Medium,Low',
            'target_quarter' => 'nullable|string|max:50',
            'strategic_objective' => 'sometimes|required|string',
            'is_submit' => 'nullable|boolean',
            'attachment' => 'nullable|file|max:15360',
            'attachment_name' => 'nullable|string|max:255',
            'attachment_size' => 'nullable|integer',
            'items' => 'required|array|min:1',
            'items.*.category_id' => 'nullable|exists:categories,id',
            'items.*.category_name' => 'nullable|string',
            'items.*.sub_category_id' => 'nullable|exists:sub_categories,id',
            'items.*.sub_category_name' => 'nullable|string',
            'items.*.budget' => 'nullable|numeric|min:0',
            'items.*.execution_quarter' => 'nullable|string|max:50',
            'items.*.target_participants_count' => 'nullable|integer|min:1',
        ]);

        try {
            DB::beginTransaction();

            $isSubmit = $request->boolean('is_submit', false);

            if (isset($validated['department'])) $proposal->department = $validated['department'];
            if (isset($validated['year'])) $proposal->year = $validated['year'];
            if (isset($validated['priority'])) $proposal->priority = $validated['priority'];
            if (isset($validated['target_quarter'])) $proposal->target_quarter = $validated['target_quarter'];
            if (isset($validated['strategic_objective'])) $proposal->strategic_objective = $validated['strategic_objective'];

            // Handle Fiscal Year
            if (isset($validated['year'])) {
                $fy = FiscalYear::where('year', $validated['year'])->first();
                if ($fy) $proposal->fiscal_year_id = $fy->id;
            }

            // Handle File Attachment
            if ($request->hasFile('attachment')) {
                $file = $request->file('attachment');
                $proposal->attachment_name = $file->getClientOriginalName();
                $proposal->attachment_size = $file->getSize();
                $proposal->attachment_path = $file->store('attachments/tna', 'public');
            } elseif (!empty($validated['attachment_name']) && !$proposal->attachment_path) {
                $proposal->attachment_name = $validated['attachment_name'];
                $proposal->attachment_size = $validated['attachment_size'] ?? 102400;
                $proposal->attachment_path = 'attachments/tna/' . uniqid() . '_' . preg_replace('/[^A-Za-z0-9_\-\.]/', '', $validated['attachment_name']);
            }

            // Hitung ulang total budget requested dari item baru
            $totalRequested = collect($validated['items'])->sum(function ($i) {
                return (float) ($i['budget'] ?? 0);
            });
            $proposal->total_budget_requested = $totalRequested;

            // Jika diajukan ulang (re-submit setelah revisi)
            if ($isSubmit) {
                $proposal->status = 'SUBMITTED';
                $proposal->current_step_order = 1;
                $proposal->approval_status_badge = 'Diajukan Ulang (Menunggu Reviewer 1 HCBP)';
                $proposal->rejection_notes = null; // Clear catatan penolakan lama setelah diperbaiki
            }

            $proposal->save();

            // Re-populate Items: Delete old items and insert updated items
            $proposal->items()->delete();

            $allCats = \App\Models\Category::all()->keyBy('id');
            $allSubs = \App\Models\SubCategory::all()->keyBy('id');

            foreach ($validated['items'] as $item) {
                $catId = $item['category_id'] ?? null;
                $subId = $item['sub_category_id'] ?? null;

                $catObj = $catId ? $allCats->get($catId) : null;
                $subObj = $subId ? $allSubs->get($subId) : null;

                $catName = $item['category_name'] ?? ($catObj?->name ?? 'Operasional & Perbankan');
                $subName = $item['sub_category_name'] ?? ($subObj?->name ?? 'Program Diklat');
                $compGap = $item['competency_gap'] ?? ($subObj?->target_competency ?? $subObj?->description ?? $subName);
                $execQ = $item['execution_quarter'] ?? 'Q1';
                $partCount = $item['target_participants_count'] ?? 10;
                $budget = $item['budget'] ?? 0;
                $costPerPart = $partCount > 0 ? round($budget / $partCount, 2) : $budget;

                TnaItem::create([
                    'tna_proposal_id' => $proposal->id,
                    'category_id' => $catId,
                    'category_name' => $catName,
                    'sub_category_id' => $subId,
                    'sub_category_name' => $subName,
                    'budget' => $budget,
                    'job_family' => $item['job_family'] ?? $catName,
                    'competency_gap' => $compGap,
                    'recommended_program' => $subName,
                    'training_type' => $item['training_type'] ?? 'In-House Training (Internal Academy)',
                    'target_audience' => $item['target_audience'] ?? 'Staff & Officer Divisi ' . $proposal->department,
                    'target_participants_count' => $partCount,
                    'cost_per_participant' => $costPerPart,
                    'estimated_cost' => $budget,
                    'execution_quarter' => $execQ,
                    'urgency' => $item['urgency'] ?? $proposal->priority,
                    'impact_kpi' => $item['impact_kpi'] ?? 'Mendukung target kinerja divisi & audit regulasi OJK',
                ]);
            }

            // Catat history jika diajukan ulang
            if ($isSubmit) {
                ApprovalHistory::create([
                    'module_code' => 'tna',
                    'record_id' => $proposal->id,
                    'step_order' => 1,
                    'step_name' => "Pengajuan Ulang Dokumen TNA Divisi {$proposal->department}",
                    'approver_id' => $user->id,
                    'approver_name' => $user->name,
                    'approver_nik' => $user->nik,
                    'role_name' => $userRole ?: 'Dept Head',
                    'action' => 'RESUBMITTED',
                    'notes' => "Revisi usulan telah diperbaiki dengan {$proposal->items()->count()} rincian program diklat. Diajukan kembali ke alur Multi-Approval.",
                    'approved_amount' => $totalRequested,
                    'action_at' => now(),
                ]);
            }

            AuditLog::create([
                'user_id' => $user->id,
                'action' => $isSubmit ? 'RESUBMIT_TNA_PROPOSAL' : 'UPDATE_TNA_PROPOSAL',
                'entity_type' => 'TnaProposal',
                'entity_id' => $proposal->id,
                'ip_address' => $request->ip(),
                'details' => json_encode(['code' => $proposal->proposal_code, 'department' => $proposal->department, 'total' => $totalRequested, 'is_submit' => $isSubmit]),
            ]);

            DB::commit();

            return response()->json([
                'message' => $isSubmit
                    ? "Usulan TNA {$proposal->proposal_code} berhasil diperbaiki dan diajukan ulang ke alur Multi-Approval."
                    : "Perubahan rincian usulan TNA {$proposal->proposal_code} berhasil disimpan.",
                'proposal' => $proposal->fresh(['items.category', 'items.subCategory', 'approvalHistories']),
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Gagal memperbarui usulan TNA: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * DELETE /api/tna/{id}
     * Menghapus (soft delete) dokumen usulan TNA
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $proposal = TnaProposal::findOrFail($id);

        if ($proposal->status === 'APPROVED') {
            return response()->json([
                'message' => 'Usulan TNA yang telah berstatus Disetujui Final tidak dapat dihapus.',
            ], 422);
        }

        try {
            DB::beginTransaction();

            $code = $proposal->proposal_code;

            // Soft delete items dan proposal
            $proposal->items()->delete();
            $proposal->delete();

            AuditLog::create([
                'user_id' => $request->user()?->id ?? 1,
                'action' => 'DELETE_TNA_PROPOSAL',
                'entity_type' => 'TnaProposal',
                'entity_id' => $id,
                'ip_address' => $request->ip(),
                'details' => json_encode(['proposal_code' => $code]),
            ]);

            DB::commit();

            return response()->json([
                'message' => "Usulan TNA {$code} berhasil dihapus dari sistem.",
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Gagal menghapus usulan TNA: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/tna/{id}/attachment
     * Mengunduh dokumen lampiran pendukung usulan TNA
     */
    public function downloadAttachment(int $id)
    {
        $proposal = TnaProposal::findOrFail($id);

        if (!$proposal->attachment_path || !\Illuminate\Support\Facades\Storage::disk('public')->exists($proposal->attachment_path)) {
            return response()->json([
                'message' => 'Dokumen lampiran pengajuan TNA: ' . ($proposal->attachment_name ?? 'Dokumen_Pendukung_TNA.pdf'),
                'attachment_name' => $proposal->attachment_name ?? 'Dokumen_Pendukung_TNA.pdf',
                'status' => 'ATTACHED_ONLINE',
            ]);
        }

        return \Illuminate\Support\Facades\Storage::disk('public')->download(
            $proposal->attachment_path,
            $proposal->attachment_name ?? 'Dokumen_Pendukung_TNA.pdf'
        );
    }
}
