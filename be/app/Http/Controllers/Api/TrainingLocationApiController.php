<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\TrainingLocation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TrainingLocationApiController extends Controller
{
    /**
     * GET /api/training-locations
     */
    public function index(Request $request): JsonResponse
    {
        $query = TrainingLocation::orderBy('code');

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
                    ->orWhere('building', 'like', $s)
                    ->orWhere('facilities', 'like', $s);
            });
        }

        $locations = $query->get();

        return response()->json([
            'locations' => $locations,
            'stats' => [
                'total' => $locations->count(),
                'active' => $locations->where('is_active', true)->count(),
                'physical' => $locations->where('type', 'Physical Room')->count(),
                'virtual' => $locations->where('type', 'Virtual Platform')->count(),
                'auditorium' => $locations->where('type', 'Auditorium')->count(),
            ],
        ]);
    }

    /**
     * POST /api/training-locations
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => 'nullable|string|max:50|unique:training_locations,code',
            'name' => 'required|string|max:150',
            'type' => 'required|string|max:50',
            'building' => 'nullable|string|max:150',
            'floor' => 'nullable|string|max:50',
            'capacity' => 'nullable|integer|min:1',
            'facilities' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        if (empty($validated['code'])) {
            $count = TrainingLocation::withTrashed()->count() + 1;
            $validated['code'] = 'RNG-'.str_pad($count, 2, '0', STR_PAD_LEFT);
        }

        $location = TrainingLocation::create($validated);

        AuditLog::record(
            'CREATE_TRAINING_LOCATION',
            'Master Data Ruangan Baru: '.$location->name.' ('.$location->code.')',
            ['location_id' => $location->id, 'data' => $location->toArray()]
        );

        return response()->json([
            'message' => 'Ruangan diklat berhasil ditambahkan ke Master Data.',
            'location' => $location,
        ], 201);
    }

    /**
     * GET /api/training-locations/{id}
     */
    public function show(int $id): JsonResponse
    {
        $location = TrainingLocation::findOrFail($id);

        return response()->json(['location' => $location]);
    }

    /**
     * PUT /api/training-locations/{id}
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $location = TrainingLocation::findOrFail($id);

        $validated = $request->validate([
            'code' => 'sometimes|string|max:50|unique:training_locations,code,'.$location->id,
            'name' => 'sometimes|string|max:150',
            'type' => 'sometimes|string|max:50',
            'building' => 'nullable|string|max:150',
            'floor' => 'nullable|string|max:50',
            'capacity' => 'nullable|integer|min:1',
            'facilities' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        $location->update($validated);

        AuditLog::record(
            'UPDATE_TRAINING_LOCATION',
            'Update Master Data Ruangan: '.$location->name.' ('.$location->code.')',
            ['location_id' => $location->id, 'changes' => $validated]
        );

        return response()->json([
            'message' => 'Data ruangan diklat berhasil diperbarui.',
            'location' => $location,
        ]);
    }

    /**
     * DELETE /api/training-locations/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        $location = TrainingLocation::findOrFail($id);
        $name = $location->name;
        $location->delete();

        AuditLog::record(
            'DELETE_TRAINING_LOCATION',
            'Hapus Master Data Ruangan: '.$name,
            ['location_id' => $id]
        );

        return response()->json([
            'message' => 'Data ruangan berhasil dihapus dari Master Data.',
        ]);
    }
}
