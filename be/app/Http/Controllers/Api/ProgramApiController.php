<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ApprovalHistory;
use App\Models\ApprovalModule;
use App\Models\AuditLog;
use App\Models\ProgramBudgetItem;
use App\Models\ProgramParticipant;
use App\Models\TrainingProgram;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ProgramApiController extends Controller
{
    /**
     * GET /api/programs
     * Daftar program kerja diklat dengan data TNA terhubung, total RAB, dan status approval
     */
    public function index(Request $request): JsonResponse
    {
        $query = TrainingProgram::with(['tnaProposal', 'tnaItem', 'creator', 'approver'])
            ->withCount(['budgetItems', 'participants', 'classes'])
            ->orderByDesc('year')
            ->orderBy('program_code');

        if ($request->filled('year') && $request->year !== 'ALL') {
            $query->where('year', (int) $request->year);
        }

        if ($request->filled('process') && $request->process !== 'ALL') {
            $query->where('status', $request->process);
        } elseif ($request->filled('status') && $request->status !== 'ALL') {
            $query->where('status', $request->status);
        }

        if ($request->filled('department') && $request->department !== 'ALL') {
            $query->where('department', $request->department);
        }

        if ($request->filled('search')) {
            $s = '%'.trim($request->search).'%';
            $query->where(function ($q) use ($s) {
                $q->where('title', 'like', $s)
                    ->orWhere('program_code', 'like', $s)
                    ->orWhere('facilitator', 'like', $s)
                    ->orWhere('location', 'like', $s)
                    ->orWhere('category', 'like', $s)
                    ->orWhere('bi_category', 'like', $s);
            });
        }

        if ($request->filled('tna_proposal_id')) {
            $query->where('tna_proposal_id', $request->tna_proposal_id);
        }

        $programs = $query->get();

        $stats = [
            'total_programs' => $programs->count(),
            'total_budget' => (float) $programs->sum('total_budget'),
            'total_realization' => (float) $programs->sum('realization_amount'),
            'total_participants' => (int) $programs->sum('target_participants_count'),
            'mandatory_count' => $programs->where('is_mandatory', true)->count(),
            'approved_count' => $programs->where('status', 'APPROVED')->count(),
        ];

        return response()->json([
            'programs' => $programs,
            'stats' => $stats,
        ]);
    }

    /**
     * GET /api/programs/{id}
     * Rincian program lengkap beserta RAB, daftar peserta, dan alur approval
     */
    public function show(int $id): JsonResponse
    {
        $program = TrainingProgram::with([
            'tnaProposal.items',
            'tnaItem',
            'creator',
            'approver',
            'budgetItems',
            'participants',
            'approvalHistories.approver',
            'classes' => function ($q) {
                $q->withCount(['sessions', 'enrollments'])->orderByDesc('id');
            },
        ])->findOrFail($id);

        $approvalModule = ApprovalModule::where('module_code', 'program')->first();
        $flowSteps = $approvalModule
            ? $approvalModule->flowSteps()->where('is_active', true)->orderBy('step_order')->get()
            : collect();

        return response()->json([
            'program' => $program,
            'flow_steps' => $flowSteps,
        ]);
    }

    /**
     * POST /api/programs
     * Membuat program kerja baru terhubung dengan TNA
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'program_code' => 'nullable|string|max:50',
            'title' => 'required|string|max:255',
            'category' => 'required|string|max:100',
            'tna_proposal_id' => 'nullable|exists:tna_proposals,id',
            'tna_item_id' => 'nullable|exists:tna_items,id',
            'department' => 'nullable|string|max:100',
            'year' => 'nullable|integer|min:2020|max:2040',
            'execution_quarter' => 'nullable|string|max:50',
            'target_audience' => 'nullable|string|max:255',
            'target_participants_count' => 'nullable|integer|min:1',
            'participant_prerequisites' => 'nullable|string',
            'method' => 'nullable|string|max:50',
            'location' => 'nullable|string|max:255',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'facilitator' => 'nullable|string|max:150',
            'duration_hours' => 'nullable|integer|min:1',
            'duration_text' => 'nullable|string|max:100',
            'program_type' => 'nullable|string|max:100',
            'unit_initiator' => 'nullable|string|max:150',
            'activity_type' => 'nullable|string|max:100',
            'bi_category' => 'nullable|string|max:150',
            'passing_grade' => 'nullable|integer|min:0|max:100',
            'is_mandatory' => 'nullable|boolean',
            'description' => 'nullable|string',
            'notes' => 'nullable|string',
            'approval_scenario' => 'nullable|in:WITHOUT_BUDGET,WITH_BUDGET,WITH_APPROVAL_DOC',
            'approval_type' => 'nullable|in:DOCUMENT,WORKFLOW',
            'approval_doc_number' => 'nullable|string|max:100',
            'approval_doc_date' => 'nullable|date',
            'approval_doc_signer' => 'nullable|string|max:150',
            'document' => 'nullable|file|max:15360',
            'doc1' => 'nullable|file|max:15360',
            'doc1_name' => 'nullable|string|max:255',
            'doc1_remarks' => 'nullable|string|max:255',
            'doc2' => 'nullable|file|max:15360',
            'doc2_name' => 'nullable|string|max:255',
            'approval_doc_name' => 'nullable|string|max:255',
            'approval_doc_size' => 'nullable|integer',
            'is_submit' => 'nullable|boolean',
            // 10 Budget breakdown fields
            'budget_fee_facilitator' => 'nullable|numeric|min:0',
            'budget_meals' => 'nullable|numeric|min:0',
            'budget_hotel' => 'nullable|numeric|min:0',
            'budget_meeting_package' => 'nullable|numeric|min:0',
            'budget_transportation' => 'nullable|numeric|min:0',
            'budget_business_trip' => 'nullable|numeric|min:0',
            'budget_reward' => 'nullable|numeric|min:0',
            'budget_material' => 'nullable|numeric|min:0',
            'budget_internet' => 'nullable|numeric|min:0',
            'budget_miscellaneous' => 'nullable|numeric|min:0',
            'realization_amount' => 'nullable|numeric|min:0',
            'budget_items' => 'nullable|array',
            'participants' => 'nullable|array',
        ]);

        $user = $request->user();
        $year = $validated['year'] ?? 2026;
        $isSubmit = $request->boolean('is_submit', true);
        $approvalScenario = $validated['approval_scenario'] ?? 'WITH_BUDGET';
        $approvalType = $validated['approval_type'] ?? ($approvalScenario === 'WITH_APPROVAL_DOC' ? 'DOCUMENT' : 'WORKFLOW');

        // Handle Dokumen Persetujuan & Dokumen 1 / 2
        $docPath = null;
        $docName = $validated['approval_doc_name'] ?? null;
        $docSize = $validated['approval_doc_size'] ?? null;

        if ($request->hasFile('document')) {
            $file = $request->file('document');
            $docName = $file->getClientOriginalName();
            $docSize = $file->getSize();
            $docPath = $file->store('attachments/programs', 'public');
        } elseif (! empty($docName)) {
            $docPath = 'attachments/programs/'.uniqid().'_'.preg_replace('/[^A-Za-z0-9_\-\.]/', '', $docName);
        }

        $doc1Path = null;
        $doc1Name = $validated['doc1_name'] ?? null;
        if ($request->hasFile('doc1')) {
            $file = $request->file('doc1');
            $doc1Name = $file->getClientOriginalName();
            $doc1Path = $file->store('attachments/programs', 'public');
        } elseif ($doc1Name) {
            $doc1Path = 'attachments/programs/'.uniqid().'_'.preg_replace('/[^A-Za-z0-9_\-\.]/', '', $doc1Name);
        }

        $doc2Path = null;
        $doc2Name = $validated['doc2_name'] ?? null;
        if ($request->hasFile('doc2')) {
            $file = $request->file('doc2');
            $doc2Name = $file->getClientOriginalName();
            $doc2Path = $file->store('attachments/programs', 'public');
        } elseif ($doc2Name) {
            $doc2Path = 'attachments/programs/'.uniqid().'_'.preg_replace('/[^A-Za-z0-9_\-\.]/', '', $doc2Name);
        }

        // Hitung total 10 item RAB
        $bFee = (float) ($validated['budget_fee_facilitator'] ?? 0);
        $bMeals = (float) ($validated['budget_meals'] ?? 0);
        $bHotel = (float) ($validated['budget_hotel'] ?? 0);
        $bMeet = (float) ($validated['budget_meeting_package'] ?? 0);
        $bTrans = (float) ($validated['budget_transportation'] ?? 0);
        $bTrip = (float) ($validated['budget_business_trip'] ?? 0);
        $bReward = (float) ($validated['budget_reward'] ?? 0);
        $bMat = (float) ($validated['budget_material'] ?? 0);
        $bNet = (float) ($validated['budget_internet'] ?? 0);
        $bMisc = (float) ($validated['budget_miscellaneous'] ?? 0);

        $sum10 = $bFee + $bMeals + $bHotel + $bMeet + $bTrans + $bTrip + $bReward + $bMat + $bNet + $bMisc;

        $budgetItems = $validated['budget_items'] ?? [];
        $totalBudget = $sum10 > 0 ? $sum10 : collect($budgetItems)->sum(function ($i) {
            $sub = (float) ($i['subtotal'] ?? 0);
            if ($sub <= 0 && isset($i['quantity'], $i['unit_price'])) {
                $sub = (float) $i['quantity'] * (float) $i['unit_price'];
            }

            return $sub;
        });

        // Program Code
        if (! empty($validated['program_code'])) {
            $programCode = $validated['program_code'];
        } else {
            $count = TrainingProgram::where('year', $year)->count() + 1;
            $deptShort = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $validated['unit_initiator'] ?? $validated['department'] ?? 'GEN'), 0, 3));
            $programCode = 'PRG-'.$year.'-'.$deptShort.'-'.str_pad($count, 3, '0', STR_PAD_LEFT);
        }

        // Tentukan status dan badge
        if ($approvalScenario === 'WITHOUT_BUDGET') {
            $status = $isSubmit ? 'SUBMITTED' : 'DRAFT';
            $badge = $isSubmit ? 'Menunggu Disposisi LD Div Head' : 'Draft Program';
        } elseif ($approvalScenario === 'WITH_APPROVAL_DOC') {
            $status = $isSubmit ? 'APPROVED' : 'DRAFT';
            $badge = $isSubmit ? 'Disahkan via Dokumen ('.($docName ?? 'Memo').')' : 'Draft Program';
        } else {
            $status = $isSubmit ? 'SUBMITTED' : 'DRAFT';
            $badge = $isSubmit ? 'Menunggu Reviewer 1 (People Dev)' : 'Draft Program';
        }

        try {
            DB::beginTransaction();

            $program = TrainingProgram::create([
                'program_code' => $programCode,
                'title' => $validated['title'],
                'category' => $validated['category'],
                'tna_proposal_id' => $validated['tna_proposal_id'] ?? null,
                'tna_item_id' => $validated['tna_item_id'] ?? null,
                'department' => $validated['department'] ?? ($validated['unit_initiator'] ?? 'Human Capital'),
                'year' => $year,
                'execution_quarter' => $validated['execution_quarter'] ?? 'Q1',
                'target_audience' => $validated['target_audience'] ?? null,
                'target_participants_count' => $validated['target_participants_count'] ?? 20,
                'participant_prerequisites' => $validated['participant_prerequisites'] ?? null,
                'method' => $validated['method'] ?? 'Hybrid',
                'location' => $validated['location'] ?? 'BWS Learning Center',
                'start_date' => $validated['start_date'] ?? null,
                'end_date' => $validated['end_date'] ?? null,
                'facilitator' => $validated['facilitator'] ?? null,
                'duration_hours' => $validated['duration_hours'] ?? 8,
                'duration_text' => $validated['duration_text'] ?? null,
                'program_type' => $validated['program_type'] ?? 'In-House Training',
                'unit_initiator' => $validated['unit_initiator'] ?? null,
                'activity_type' => $validated['activity_type'] ?? 'Pelatihan',
                'bi_category' => $validated['bi_category'] ?? null,
                'passing_grade' => $validated['passing_grade'] ?? 75,
                'is_mandatory' => $request->boolean('is_mandatory', false),
                'description' => $validated['description'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'approval_scenario' => $approvalScenario,
                'approval_type' => $approvalType,
                'approval_doc_number' => $validated['approval_doc_number'] ?? null,
                'approval_doc_date' => $validated['approval_doc_date'] ?? null,
                'approval_doc_signer' => $validated['approval_doc_signer'] ?? null,
                'approval_doc_path' => $docPath,
                'approval_doc_name' => $docName,
                'approval_doc_size' => $docSize,
                'doc1_path' => $doc1Path,
                'doc1_name' => $doc1Name,
                'doc1_remarks' => $validated['doc1_remarks'] ?? null,
                'doc2_path' => $doc2Path,
                'doc2_name' => $doc2Name,
                'budget_fee_facilitator' => $bFee,
                'budget_meals' => $bMeals,
                'budget_hotel' => $bHotel,
                'budget_meeting_package' => $bMeet,
                'budget_transportation' => $bTrans,
                'budget_business_trip' => $bTrip,
                'budget_reward' => $bReward,
                'budget_material' => $bMat,
                'budget_internet' => $bNet,
                'budget_miscellaneous' => $bMisc,
                'total_budget' => $totalBudget,
                'realization_amount' => $validated['realization_amount'] ?? 0,
                'status' => $status,
                'current_step_order' => 1,
                'approval_status_badge' => $badge,
                'created_by' => $user?->id ?? 1,
                'approved_by' => ($approvalScenario === 'WITH_APPROVAL_DOC' && $isSubmit) ? ($user?->id ?? 1) : null,
                'approved_at' => ($approvalScenario === 'WITH_APPROVAL_DOC' && $isSubmit) ? now() : null,
            ]);

            // Simpan baris RAB
            foreach ($budgetItems as $item) {
                $qty = (float) ($item['quantity'] ?? 1);
                $unitPrice = (float) ($item['unit_price'] ?? 0);
                $subtotal = (float) ($item['subtotal'] ?? ($qty * $unitPrice));

                ProgramBudgetItem::create([
                    'training_program_id' => $program->id,
                    'item_type' => $item['item_type'] ?? 'OTHER',
                    'description' => $item['description'] ?? 'Biaya Pelatihan',
                    'quantity' => $qty,
                    'unit' => $item['unit'] ?? 'Paket',
                    'unit_price' => $unitPrice,
                    'subtotal' => $subtotal,
                    'notes' => $item['notes'] ?? null,
                ]);
            }

            // Simpan daftar nominasi peserta
            $participants = $validated['participants'] ?? [];
            foreach ($participants as $p) {
                ProgramParticipant::create([
                    'training_program_id' => $program->id,
                    'nik' => $p['nik'] ?? null,
                    'name' => $p['name'],
                    'department' => $p['department'] ?? $program->department,
                    'branch' => $p['branch'] ?? 'Kantor Pusat BWS',
                    'position' => $p['position'] ?? 'Staff / Officer',
                    'email' => $p['email'] ?? null,
                    'status' => 'NOMINATED',
                ]);
            }

            // Catat history
            if ($isSubmit) {
                if ($approvalType === 'DOCUMENT') {
                    ApprovalHistory::create([
                        'module_code' => 'program',
                        'record_id' => $program->id,
                        'step_order' => 1,
                        'step_name' => 'Pengesahan via Dokumen / SK Resmi',
                        'approver_id' => $user->id,
                        'approver_name' => $user->name,
                        'approver_nik' => $user->nik,
                        'role_name' => $user->role_name ?? 'Super Admin',
                        'action' => 'APPROVED',
                        'notes' => "Program disahkan via dokumen {$program->approval_doc_number} tgl {$program->approval_doc_date} oleh {$program->approval_doc_signer}.",
                        'approved_amount' => $totalBudget,
                        'action_at' => now(),
                    ]);
                } else {
                    ApprovalHistory::create([
                        'module_code' => 'program',
                        'record_id' => $program->id,
                        'step_order' => 1,
                        'step_name' => 'Pengajuan Program Kerja Diklat',
                        'approver_id' => $user->id,
                        'approver_name' => $user->name,
                        'approver_nik' => $user->nik,
                        'role_name' => $user->role_name ?? 'Dept Head',
                        'action' => 'SUBMITTED',
                        'notes' => "Program {$program->program_code} diajukan ke Alur Multi-Approval dengan total RAB Rp ".number_format($totalBudget, 0, ',', '.'),
                        'approved_amount' => $totalBudget,
                        'action_at' => now(),
                    ]);
                }
            }

            AuditLog::create([
                'user_id' => $user->id,
                'action' => 'CREATE_TRAINING_PROGRAM',
                'entity_type' => 'TrainingProgram',
                'entity_id' => $program->id,
                'ip_address' => $request->ip(),
                'details' => json_encode(['code' => $programCode, 'title' => $program->title, 'budget' => $totalBudget, 'approval_type' => $approvalType]),
            ]);

            DB::commit();

            return response()->json([
                'message' => $isSubmit
                    ? ($approvalType === 'DOCUMENT'
                        ? "Program {$programCode} berhasil disahkan via Dokumen Pendukung."
                        : "Program {$programCode} berhasil diajukan ke Alur Multi-Approval (Step 1 HCBP).")
                    : "Draft program {$programCode} berhasil disimpan.",
                'program' => $program->load(['tnaProposal', 'tnaItem', 'budgetItems', 'participants']),
            ], 201);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Gagal menyimpan program kerja pelatihan: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * PUT/POST /api/programs/{id}
     * Memperbarui program kerja (edit mode / revisi)
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $program = TrainingProgram::findOrFail($id);
        $user = $request->user();

        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'category' => 'sometimes|required|string|max:100',
            'tna_proposal_id' => 'nullable|exists:tna_proposals,id',
            'tna_item_id' => 'nullable|exists:tna_items,id',
            'department' => 'nullable|string|max:100',
            'year' => 'nullable|integer|min:2020|max:2040',
            'execution_quarter' => 'nullable|string|max:50',
            'target_audience' => 'nullable|string|max:255',
            'target_participants_count' => 'nullable|integer|min:1',
            'participant_prerequisites' => 'nullable|string',
            'method' => 'nullable|string|max:50',
            'location' => 'nullable|string|max:255',
            'duration_hours' => 'nullable|integer|min:1',
            'passing_grade' => 'nullable|integer|min:0|max:100',
            'is_mandatory' => 'nullable|boolean',
            'description' => 'nullable|string',
            'approval_type' => 'sometimes|required|in:DOCUMENT,WORKFLOW',
            'approval_doc_number' => 'nullable|string|max:100',
            'approval_doc_date' => 'nullable|date',
            'approval_doc_signer' => 'nullable|string|max:150',
            'document' => 'nullable|file|max:15360',
            'approval_doc_name' => 'nullable|string|max:255',
            'approval_doc_size' => 'nullable|integer',
            'is_submit' => 'nullable|boolean',
            'budget_items' => 'nullable|array',
            'budget_items.*.item_type' => 'nullable|string',
            'budget_items.*.description' => 'nullable|string',
            'budget_items.*.quantity' => 'nullable|numeric|min:0',
            'budget_items.*.unit' => 'nullable|string|max:50',
            'budget_items.*.unit_price' => 'nullable|numeric|min:0',
            'budget_items.*.subtotal' => 'nullable|numeric|min:0',
            'budget_items.*.notes' => 'nullable|string',
            'participants' => 'nullable|array',
            'participants.*.nik' => 'nullable|string|max:50',
            'participants.*.name' => 'nullable|string|max:150',
            'participants.*.department' => 'nullable|string|max:100',
            'participants.*.branch' => 'nullable|string|max:100',
            'participants.*.position' => 'nullable|string|max:100',
            'participants.*.email' => 'nullable|email|max:150',
        ]);

        try {
            DB::beginTransaction();

            if (isset($validated['title'])) {
                $program->title = $validated['title'];
            }
            if (isset($validated['category'])) {
                $program->category = $validated['category'];
            }
            if (array_key_exists('tna_proposal_id', $validated)) {
                $program->tna_proposal_id = $validated['tna_proposal_id'];
            }
            if (array_key_exists('tna_item_id', $validated)) {
                $program->tna_item_id = $validated['tna_item_id'];
            }
            if (isset($validated['department'])) {
                $program->department = $validated['department'];
            }
            if (isset($validated['year'])) {
                $program->year = $validated['year'];
            }
            if (isset($validated['execution_quarter'])) {
                $program->execution_quarter = $validated['execution_quarter'];
            }
            if (isset($validated['target_audience'])) {
                $program->target_audience = $validated['target_audience'];
            }
            if (isset($validated['target_participants_count'])) {
                $program->target_participants_count = $validated['target_participants_count'];
            }
            if (isset($validated['participant_prerequisites'])) {
                $program->participant_prerequisites = $validated['participant_prerequisites'];
            }
            if (isset($validated['method'])) {
                $program->method = $validated['method'];
            }
            if (isset($validated['location'])) {
                $program->location = $validated['location'];
            }
            if (isset($validated['duration_hours'])) {
                $program->duration_hours = $validated['duration_hours'];
            }
            if (isset($validated['passing_grade'])) {
                $program->passing_grade = $validated['passing_grade'];
            }
            if ($request->has('is_mandatory')) {
                $program->is_mandatory = $request->boolean('is_mandatory');
            }
            if (isset($validated['description'])) {
                $program->description = $validated['description'];
            }
            if (isset($validated['approval_type'])) {
                $program->approval_type = $validated['approval_type'];
            }
            if (isset($validated['approval_doc_number'])) {
                $program->approval_doc_number = $validated['approval_doc_number'];
            }
            if (isset($validated['approval_doc_date'])) {
                $program->approval_doc_date = $validated['approval_doc_date'];
            }
            if (isset($validated['approval_doc_signer'])) {
                $program->approval_doc_signer = $validated['approval_doc_signer'];
            }

            // Handle file upload
            if ($request->hasFile('document')) {
                $file = $request->file('document');
                $program->approval_doc_name = $file->getClientOriginalName();
                $program->approval_doc_size = $file->getSize();
                $program->approval_doc_path = $file->store('attachments/programs', 'public');
            }

            // Hitung ulang RAB jika diberikan
            if (isset($validated['budget_items'])) {
                $program->budgetItems()->delete();
                $totalBudget = 0;
                foreach ($validated['budget_items'] as $item) {
                    $qty = (float) ($item['quantity'] ?? 1);
                    $unitPrice = (float) ($item['unit_price'] ?? 0);
                    $subtotal = (float) ($item['subtotal'] ?? ($qty * $unitPrice));
                    $totalBudget += $subtotal;

                    ProgramBudgetItem::create([
                        'training_program_id' => $program->id,
                        'item_type' => $item['item_type'] ?? 'OTHER',
                        'description' => $item['description'] ?? 'Komponen Biaya',
                        'quantity' => $qty,
                        'unit' => $item['unit'] ?? 'Paket',
                        'unit_price' => $unitPrice,
                        'subtotal' => $subtotal,
                        'notes' => $item['notes'] ?? null,
                    ]);
                }
                $program->total_budget = $totalBudget;
            }

            // Perbarui peserta jika diberikan
            if (isset($validated['participants'])) {
                $program->participants()->delete();
                foreach ($validated['participants'] as $p) {
                    if (! empty($p['name'])) {
                        ProgramParticipant::create([
                            'training_program_id' => $program->id,
                            'nik' => $p['nik'] ?? null,
                            'name' => $p['name'],
                            'department' => $p['department'] ?? $program->department,
                            'branch' => $p['branch'] ?? 'Kantor Cabang',
                            'position' => $p['position'] ?? 'Staff',
                            'email' => $p['email'] ?? null,
                            'status' => 'NOMINATED',
                        ]);
                    }
                }
            }

            $isSubmit = $request->boolean('is_submit', false);
            if ($isSubmit) {
                if ($program->approval_type === 'DOCUMENT') {
                    $program->status = 'APPROVED';
                    $program->approval_status_badge = 'Disahkan via Dokumen ('.($program->approval_doc_number ?? 'MEMO').')';
                    $program->approved_by = $user->id;
                    $program->approved_at = now();
                    $program->rejection_notes = null;
                } else {
                    $program->status = 'SUBMITTED';
                    $program->current_step_order = 1;
                    $program->approval_status_badge = 'Menunggu Reviewer 1 (HCBP)';
                    $program->rejection_notes = null;

                    ApprovalHistory::create([
                        'module_code' => 'program',
                        'record_id' => $program->id,
                        'step_order' => 1,
                        'step_name' => 'Pengajuan Ulang Program Kerja Diklat',
                        'approver_id' => $user->id,
                        'approver_name' => $user->name,
                        'approver_nik' => $user->nik,
                        'role_name' => $user->role_name ?? 'Dept Head',
                        'action' => 'SUBMITTED',
                        'notes' => "Program {$program->program_code} diperbaiki dan diajukan ulang ke tahapan verifikasi berjenjang.",
                        'approved_amount' => $program->total_budget,
                        'action_at' => now(),
                    ]);
                }
            }

            $program->save();

            DB::commit();

            return response()->json([
                'message' => "Program {$program->program_code} berhasil diperbarui.",
                'program' => $program->fresh(['tnaProposal', 'tnaItem', 'budgetItems', 'participants']),
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Gagal memperbarui program kerja: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * POST /api/programs/{id}/workflow-action
     * Otorisasi berjenjang program pelatihan (APPROVE, REJECT, REQUEST_REVISION)
     */
    public function workflowAction(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'decision' => 'required|in:APPROVE,REJECT,REQUEST_REVISION',
            'notes' => 'required|string|min:5',
        ]);

        $program = TrainingProgram::findOrFail($id);
        $user = $request->user();
        $userRole = $user?->role_name ?? '';
        $isSuperAdmin = ($userRole === 'Super Admin' || ($user?->level && $user->level->is_admin));

        $mod = ApprovalModule::where('module_code', 'program')->first();
        $flowSteps = $mod ? $mod->flowSteps()->where('is_active', true)->orderBy('step_order')->get() : collect();
        $currentStep = $flowSteps->firstWhere('step_order', $program->current_step_order);

        if (! $isSuperAdmin && (! $currentStep || $currentStep->role_name !== $userRole)) {
            return response()->json([
                'message' => 'Anda tidak memiliki hak otorisasi untuk tahapan approval saat ini ('.($currentStep?->step_name ?? 'N/A').').',
            ], 403);
        }

        try {
            DB::beginTransaction();

            $decision = $validated['decision'];
            $notes = $validated['notes'];
            $stepOrder = $program->current_step_order;
            $stepName = $currentStep ? $currentStep->step_name : "Tahapan {$stepOrder}";

            if ($decision === 'APPROVE') {
                $nextStep = $flowSteps->firstWhere('step_order', $stepOrder + 1);
                if ($nextStep) {
                    $program->current_step_order = $nextStep->step_order;
                    $program->status = 'SUBMITTED';
                    $program->approval_status_badge = "Menunggu {$nextStep->step_name}";
                } else {
                    $program->status = 'APPROVED';
                    $program->approval_status_badge = 'Disetujui Final (Kadiv HC)';
                    $program->approved_by = $user->id;
                    $program->approved_at = now();
                }

                ApprovalHistory::create([
                    'module_code' => 'program',
                    'record_id' => $program->id,
                    'step_order' => $stepOrder,
                    'step_name' => $stepName,
                    'approver_id' => $user->id,
                    'approver_name' => $user->name,
                    'approver_nik' => $user->nik,
                    'role_name' => $userRole,
                    'action' => 'APPROVED',
                    'notes' => $notes,
                    'approved_amount' => $program->total_budget,
                    'action_at' => now(),
                ]);

                $msg = $nextStep
                    ? "Tahapan '{$stepName}' berhasil disetujui. Berkas diteruskan ke: {$nextStep->step_name}."
                    : "Program {$program->program_code} telah disetujui secara FINAL.";
            } elseif ($decision === 'REJECT') {
                $program->status = 'REJECTED';
                $program->approval_status_badge = 'Program Ditolak';
                $program->rejection_notes = $notes;

                ApprovalHistory::create([
                    'module_code' => 'program',
                    'record_id' => $program->id,
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

                $msg = "Program {$program->program_code} telah DITOLAK pada tahapan {$stepName}.";
            } else { // REQUEST_REVISION
                $program->status = 'REVISED';
                $program->approval_status_badge = 'Perlu Revisi Pengusul';
                $program->rejection_notes = $notes;

                ApprovalHistory::create([
                    'module_code' => 'program',
                    'record_id' => $program->id,
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

                $msg = 'Permintaan revisi program berhasil dikirimkan kembali kepada pengusul.';
            }

            $program->save();

            AuditLog::create([
                'user_id' => $user->id,
                'action' => 'WORKFLOW_'.$decision.'_PROGRAM',
                'entity_type' => 'TrainingProgram',
                'entity_id' => $program->id,
                'ip_address' => $request->ip(),
                'details' => json_encode(['code' => $program->program_code, 'decision' => $decision]),
            ]);

            DB::commit();

            return response()->json([
                'message' => $msg,
                'program' => $program->fresh(['tnaProposal', 'tnaItem', 'budgetItems', 'participants', 'approvalHistories.approver']),
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Gagal memproses aksi otorisasi: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/programs/{id}/document
     * Unduh dokumen pendukung pengesahan / memo SK program
     */
    public function downloadDocument(int $id): BinaryFileResponse|JsonResponse
    {
        $program = TrainingProgram::findOrFail($id);

        if (! $program->approval_doc_path) {
            return response()->json(['message' => 'Program ini tidak memiliki dokumen lampiran fisik.'], 404);
        }

        if (Storage::disk('public')->exists($program->approval_doc_path)) {
            $path = Storage::disk('public')->path($program->approval_doc_path);

            return response()->download($path, $program->approval_doc_name ?: 'Dokumen_Pengesahan_Program.pdf');
        }

        // Dummy generated stream jika berkas simulasi
        $safeName = preg_replace('/[^A-Za-z0-9_\-\.]/', '', $program->approval_doc_name ?: 'Dokumen_Pengesahan_Program.pdf');
        $dummyDir = storage_path('app/public/attachments/programs');
        if (! file_exists($dummyDir)) {
            mkdir($dummyDir, 0755, true);
        }
        $fallbackPath = $dummyDir.'/'.$safeName;
        if (! file_exists($fallbackPath)) {
            $content = "%PDF-1.4\n%BWS Learning Integrated System (LIS) - Official Approval Document\nProgram: {$program->program_code}\nTitle: {$program->title}\nBudget: Rp ".number_format($program->total_budget, 0, ',', '.')."\nSigner: {$program->approval_doc_signer}\nStatus: {$program->status}\n%%EOF";
            file_put_contents($fallbackPath, $content);
        }

        return response()->download($fallbackPath, $safeName);
    }

    /**
     * DELETE /api/programs/{id}
     * Menghapus program kerja jika masih berstatus DRAFT atau belum final
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $program = TrainingProgram::findOrFail($id);
        $user = $request->user();
        $isSuperAdmin = ($user?->role_name === 'Super Admin' || ($user?->level && $user->level->is_admin));

        if ($program->status === 'APPROVED' && ! $isSuperAdmin) {
            return response()->json([
                'message' => 'Program kerja yang telah Disetujui Final tidak dapat dihapus.',
            ], 422);
        }

        $code = $program->program_code;
        $program->budgetItems()->delete();
        $program->participants()->delete();
        $program->delete();

        return response()->json([
            'message' => "Program kerja {$code} berhasil dihapus.",
        ]);
    }
}
