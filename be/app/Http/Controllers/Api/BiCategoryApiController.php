<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\BiCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BiCategoryApiController extends Controller
{
    /**
     * GET /api/bi-categories
     */
    public function index(Request $request): JsonResponse
    {
        $query = BiCategory::orderBy('sort_order')->orderBy('code');

        if ($request->has('is_mandatory') && $request->is_mandatory !== '') {
            $query->where('is_mandatory', filter_var($request->is_mandatory, FILTER_VALIDATE_BOOLEAN));
        }

        if ($request->has('is_active') && $request->is_active !== '') {
            $query->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN));
        }

        if ($request->filled('search')) {
            $s = '%'.trim($request->search).'%';
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', $s)
                    ->orWhere('code', 'like', $s)
                    ->orWhere('regulation_reference', 'like', $s)
                    ->orWhere('description', 'like', $s);
            });
        }

        $categories = $query->get();

        return response()->json([
            'categories' => $categories,
            'stats' => [
                'total' => $categories->count(),
                'mandatory' => $categories->where('is_mandatory', true)->count(),
                'active' => $categories->where('is_active', true)->count(),
            ],
        ]);
    }

    /**
     * POST /api/bi-categories
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:bi_categories,code',
            'name' => 'required|string|max:150',
            'regulation_reference' => 'nullable|string|max:150',
            'description' => 'nullable|string',
            'is_mandatory' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'sort_order' => 'nullable|integer',
        ]);

        $validated['is_mandatory'] = $request->boolean('is_mandatory', true);
        $validated['is_active'] = $request->boolean('is_active', true);

        if (empty($validated['sort_order'])) {
            $validated['sort_order'] = BiCategory::max('sort_order') + 1;
        }

        $category = BiCategory::create($validated);

        AuditLog::create([
            'user_id' => $request->user()?->id ?? 1,
            'action' => 'CREATE_BI_CATEGORY',
            'entity_type' => 'BiCategory',
            'entity_id' => $category->id,
            'ip_address' => $request->ip(),
            'details' => json_encode(['name' => $category->name, 'code' => $category->code]),
        ]);

        return response()->json([
            'message' => "Kategori BI/OJK '{$category->name}' berhasil ditambahkan.",
            'category' => $category,
        ], 201);
    }

    /**
     * GET /api/bi-categories/{id}
     */
    public function show(int $id): JsonResponse
    {
        $category = BiCategory::findOrFail($id);

        return response()->json(['category' => $category]);
    }

    /**
     * PUT /api/bi-categories/{id}
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $category = BiCategory::findOrFail($id);

        $validated = $request->validate([
            'code' => 'sometimes|required|string|max:50|unique:bi_categories,code,'.$id,
            'name' => 'sometimes|required|string|max:150',
            'regulation_reference' => 'nullable|string|max:150',
            'description' => 'nullable|string',
            'is_mandatory' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'sort_order' => 'nullable|integer',
        ]);

        if ($request->has('is_mandatory')) {
            $validated['is_mandatory'] = $request->boolean('is_mandatory');
        }
        if ($request->has('is_active')) {
            $validated['is_active'] = $request->boolean('is_active');
        }

        $category->update($validated);

        AuditLog::create([
            'user_id' => $request->user()?->id ?? 1,
            'action' => 'UPDATE_BI_CATEGORY',
            'entity_type' => 'BiCategory',
            'entity_id' => $category->id,
            'ip_address' => $request->ip(),
            'details' => json_encode(['name' => $category->name, 'code' => $category->code]),
        ]);

        return response()->json([
            'message' => "Kategori BI/OJK '{$category->name}' berhasil diperbarui.",
            'category' => $category,
        ]);
    }

    /**
     * DELETE /api/bi-categories/{id}
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $category = BiCategory::findOrFail($id);
        $name = $category->name;
        $category->delete();

        AuditLog::create([
            'user_id' => $request->user()?->id ?? 1,
            'action' => 'DELETE_BI_CATEGORY',
            'entity_type' => 'BiCategory',
            'entity_id' => $id,
            'ip_address' => $request->ip(),
            'details' => json_encode(['name' => $name]),
        ]);

        return response()->json([
            'message' => "Kategori BI/OJK '{$name}' berhasil dihapus.",
        ]);
    }
}
