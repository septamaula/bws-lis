<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ApprovalFlowStep;
use App\Models\ApprovalModule;
use App\Models\AuditLog;
use App\Models\UserLevel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApprovalSettingApiController extends Controller
{
    /**
     * GET /api/approvals/settings
     * Mengambil daftar seluruh modul beserta tahapan multi-approval-nya
     */
    public function index(): JsonResponse
    {
        $modules = ApprovalModule::with(['flowSteps' => function ($q) {
            $q->orderBy('step_order');
        }])->orderBy('id')->get();

        $availableRoles = UserLevel::where('is_active', true)->orderBy('level_id')->pluck('level');

        return response()->json([
            'modules' => $modules,
            'availableRoles' => $availableRoles,
        ]);
    }

    /**
     * POST /api/approvals/settings/{moduleCode}/steps
     * Menambahkan tahapan approval baru pada suatu modul
     */
    public function storeStep(Request $request, string $moduleCode): JsonResponse
    {
        $module = ApprovalModule::where('module_code', $moduleCode)->firstOrFail();

        $validated = $request->validate([
            'step_name' => 'required|string|max:150',
            'role_name' => 'required|string|max:100',
            'min_amount' => 'nullable|numeric|min:0',
            'max_amount' => 'nullable|numeric|min:0',
            'sla_days' => 'required|integer|min:1|max:30',
        ]);

        $maxOrder = $module->flowSteps()->max('step_order') ?? 0;
        $newOrder = $maxOrder + 1;

        $step = ApprovalFlowStep::create([
            'module_id' => $module->id,
            'step_order' => $newOrder,
            'step_name' => $validated['step_name'],
            'role_name' => $validated['role_name'],
            'min_amount' => $validated['min_amount'] ?? 0,
            'max_amount' => $validated['max_amount'] ?? null,
            'sla_days' => $validated['sla_days'],
            'is_active' => true,
        ]);

        AuditLog::create([
            'user_id' => $request->user()?->id ?? 1,
            'action' => 'CREATE_APPROVAL_STEP',
            'entity_type' => 'ApprovalFlowStep',
            'entity_id' => $step->id,
            'ip_address' => $request->ip(),
            'details' => json_encode(['module' => $moduleCode, 'step_name' => $step->step_name, 'role' => $step->role_name]),
        ]);

        return response()->json([
            'message' => "Tahapan approval '{$step->step_name}' berhasil ditambahkan ke modul {$module->module_name}.",
            'step' => $step,
        ], 201);
    }

    /**
     * PUT /api/approvals/settings/steps/{id}
     * Memperbarui konfigurasi tahapan approval
     */
    public function updateStep(Request $request, int $id): JsonResponse
    {
        $step = ApprovalFlowStep::with('module')->findOrFail($id);

        $validated = $request->validate([
            'step_name' => 'required|string|max:150',
            'role_name' => 'required|string|max:100',
            'min_amount' => 'nullable|numeric|min:0',
            'max_amount' => 'nullable|numeric|min:0',
            'sla_days' => 'required|integer|min:1|max:30',
            'is_active' => 'required|boolean',
        ]);

        $step->update($validated);

        AuditLog::create([
            'user_id' => $request->user()?->id ?? 1,
            'action' => 'UPDATE_APPROVAL_STEP',
            'entity_type' => 'ApprovalFlowStep',
            'entity_id' => $step->id,
            'ip_address' => $request->ip(),
            'details' => json_encode(['module' => $step->module?->module_code, 'step_name' => $step->step_name]),
        ]);

        return response()->json([
            'message' => "Konfigurasi tahapan '{$step->step_name}' berhasil diperbarui.",
            'step' => $step,
        ]);
    }

    /**
     * DELETE /api/approvals/settings/steps/{id}
     * Menghapus tahapan approval dan menata ulang urutan (re-order)
     */
    public function destroyStep(Request $request, int $id): JsonResponse
    {
        $step = ApprovalFlowStep::findOrFail($id);
        $moduleId = $step->module_id;
        $stepName = $step->step_name;

        $step->delete();

        // Re-index urutan order
        $remainingSteps = ApprovalFlowStep::where('module_id', $moduleId)
            ->orderBy('step_order')
            ->get();

        foreach ($remainingSteps as $index => $s) {
            $s->update(['step_order' => $index + 1]);
        }

        AuditLog::create([
            'user_id' => $request->user()?->id ?? 1,
            'action' => 'DELETE_APPROVAL_STEP',
            'entity_type' => 'ApprovalFlowStep',
            'entity_id' => $id,
            'ip_address' => $request->ip(),
            'details' => json_encode(['deleted_step' => $stepName]),
        ]);

        return response()->json([
            'message' => "Tahapan approval '{$stepName}' berhasil dihapus dan urutan diperbarui.",
        ]);
    }
}
