<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Facilitator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FacilitatorApiController extends Controller
{
    /**
     * GET /api/facilitators
     */
    public function index(Request $request): JsonResponse
    {
        $query = Facilitator::orderBy('name');

        if ($request->filled('type') && $request->type !== 'ALL') {
            $query->where('type', $request->type);
        }

        if ($request->has('is_active') && $request->is_active !== '') {
            $query->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN));
        }

        if ($request->filled('search')) {
            $s = '%'.trim($request->search).'%';
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', $s)
                    ->orWhere('code', 'like', $s)
                    ->orWhere('specialization', 'like', $s)
                    ->orWhere('contact_person', 'like', $s);
            });
        }

        $facilitators = $query->get();

        return response()->json([
            'facilitators' => $facilitators,
            'stats' => [
                'total' => $facilitators->count(),
                'active' => $facilitators->where('is_active', true)->count(),
                'external' => $facilitators->where('type', 'External Vendor')->count(),
                'certification' => $facilitators->where('type', 'Lembaga Sertifikasi')->count(),
                'internal' => $facilitators->where('type', 'Internal Trainer')->count(),
            ],
        ]);
    }

    /**
     * POST /api/facilitators
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => 'nullable|string|max:50|unique:facilitators,code',
            'name' => 'required|string|max:150',
            'type' => 'required|string|max:50',
            'contact_person' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:100',
            'specialization' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        if (empty($validated['code'])) {
            $count = Facilitator::withTrashed()->count() + 1;
            $validated['code'] = 'FAC-'.str_pad($count, 3, '0', STR_PAD_LEFT);
        }

        $validated['is_active'] = $request->boolean('is_active', true);

        $facilitator = Facilitator::create($validated);

        AuditLog::create([
            'user_id' => $request->user()?->id ?? 1,
            'action' => 'CREATE_FACILITATOR',
            'entity_type' => 'Facilitator',
            'entity_id' => $facilitator->id,
            'ip_address' => $request->ip(),
            'details' => json_encode(['name' => $facilitator->name, 'code' => $facilitator->code]),
        ]);

        return response()->json([
            'message' => "Facilitator '{$facilitator->name}' berhasil ditambahkan.",
            'facilitator' => $facilitator,
        ], 201);
    }

    /**
     * GET /api/facilitators/{id}
     */
    public function show(int $id): JsonResponse
    {
        $facilitator = Facilitator::findOrFail($id);

        return response()->json(['facilitator' => $facilitator]);
    }

    /**
     * PUT /api/facilitators/{id}
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $facilitator = Facilitator::findOrFail($id);

        $validated = $request->validate([
            'code' => 'nullable|string|max:50|unique:facilitators,code,'.$id,
            'name' => 'sometimes|required|string|max:150',
            'type' => 'sometimes|required|string|max:50',
            'contact_person' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:100',
            'specialization' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        if ($request->has('is_active')) {
            $validated['is_active'] = $request->boolean('is_active');
        }

        $facilitator->update($validated);

        AuditLog::create([
            'user_id' => $request->user()?->id ?? 1,
            'action' => 'UPDATE_FACILITATOR',
            'entity_type' => 'Facilitator',
            'entity_id' => $facilitator->id,
            'ip_address' => $request->ip(),
            'details' => json_encode(['name' => $facilitator->name, 'code' => $facilitator->code]),
        ]);

        return response()->json([
            'message' => "Facilitator '{$facilitator->name}' berhasil diperbarui.",
            'facilitator' => $facilitator,
        ]);
    }

    /**
     * DELETE /api/facilitators/{id}
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $facilitator = Facilitator::findOrFail($id);
        $name = $facilitator->name;
        $facilitator->delete();

        AuditLog::create([
            'user_id' => $request->user()?->id ?? 1,
            'action' => 'DELETE_FACILITATOR',
            'entity_type' => 'Facilitator',
            'entity_id' => $id,
            'ip_address' => $request->ip(),
            'details' => json_encode(['name' => $name]),
        ]);

        return response()->json([
            'message' => "Facilitator '{$name}' berhasil dihapus.",
        ]);
    }
}
