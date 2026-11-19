<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\SubCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryApiController extends Controller
{
    /**
     * Get list of all Categories with their sub-categories count.
     */
    public function indexCategories(Request $request): JsonResponse
    {
        $query = Category::withCount('subCategories')->orderBy('sort_order')->orderBy('id');

        if ($request->boolean('with_subs')) {
            $query->with(['subCategories' => function ($q) {
                $q->orderBy('sort_order')->orderBy('id');
            }]);
        }

        if ($request->filled('q')) {
            $q = $request->input('q');
            $query->where(function ($sub) use ($q) {
                $sub->where('name', 'ilike', "%{$q}%")
                    ->orWhere('code', 'ilike', "%{$q}%")
                    ->orWhere('description', 'ilike', "%{$q}%");
            });
        }

        $categories = $query->get();

        return response()->json([
            'categories' => $categories,
            'total' => $categories->count(),
        ]);
    }

    /**
     * Store new Category.
     */
    public function storeCategory(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:categories,code'],
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            'icon' => ['nullable', 'string', 'max:50'],
            'color' => ['nullable', 'string', 'max:20'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer'],
        ]);

        $category = Category::create([
            'code' => strtoupper($validated['code']),
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'icon' => $validated['icon'] ?? 'Building2',
            'color' => $validated['color'] ?? '#0067AC',
            'is_active' => $validated['is_active'] ?? true,
            'sort_order' => $validated['sort_order'] ?? (Category::max('sort_order') + 1),
        ]);

        AuditLog::record('CATEGORY_CREATED', 'categories', $category->id, [
            'code' => $category->code,
            'name' => $category->name,
        ]);

        return response()->json([
            'message' => "Kategori Induk '{$category->name}' berhasil dibuat.",
            'category' => $category->loadCount('subCategories'),
        ], 201);
    }

    /**
     * Show Category with its Sub-Categories.
     */
    public function showCategory(int $id): JsonResponse
    {
        $category = Category::with(['subCategories' => function ($q) {
            $q->orderBy('sort_order');
        }])->findOrFail($id);

        return response()->json([
            'category' => $category,
        ]);
    }

    /**
     * Update Category.
     */
    public function updateCategory(Request $request, int $id): JsonResponse
    {
        $category = Category::findOrFail($id);

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:categories,code,'.$category->id],
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            'icon' => ['nullable', 'string', 'max:50'],
            'color' => ['nullable', 'string', 'max:20'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer'],
        ]);

        $category->update([
            'code' => strtoupper($validated['code']),
            'name' => $validated['name'],
            'description' => $validated['description'] ?? $category->description,
            'icon' => $validated['icon'] ?? $category->icon,
            'color' => $validated['color'] ?? $category->color,
            'is_active' => $validated['is_active'] ?? $category->is_active,
            'sort_order' => $validated['sort_order'] ?? $category->sort_order,
        ]);

        AuditLog::record('CATEGORY_UPDATED', 'categories', $category->id, [
            'code' => $category->code,
            'name' => $category->name,
        ]);

        return response()->json([
            'message' => "Kategori Induk '{$category->name}' berhasil diperbarui.",
            'category' => $category->loadCount('subCategories'),
        ]);
    }

    /**
     * Soft delete Category.
     */
    public function destroyCategory(int $id): JsonResponse
    {
        $category = Category::findOrFail($id);

        $subCount = $category->subCategories()->count();
        if ($subCount > 0) {
            $category->subCategories()->delete();
        }

        $category->delete();

        AuditLog::record('CATEGORY_DELETED', 'categories', $id, [
            'code' => $category->code,
            'name' => $category->name,
            'subs_deleted' => $subCount,
        ]);

        return response()->json([
            'message' => "Kategori '{$category->name}' dan seluruh sub-kategori terkait berhasil dihapus.",
        ]);
    }

    // =========================================================================
    // SUB-CATEGORIES CRUD (didalam sub ada categorynya)
    // =========================================================================

    /**
     * Get list of Sub-Categories with their parent Category.
     */
    public function indexSubCategories(Request $request): JsonResponse
    {
        $query = SubCategory::with('category')->orderBy('category_id')->orderBy('sort_order');

        if ($request->filled('category_id') && $request->input('category_id') !== 'ALL') {
            $query->where('category_id', $request->input('category_id'));
        }

        if ($request->filled('q')) {
            $q = $request->input('q');
            $query->where(function ($sub) use ($q) {
                $sub->where('name', 'ilike', "%{$q}%")
                    ->orWhere('code', 'ilike', "%{$q}%")
                    ->orWhere('target_competency', 'ilike', "%{$q}%")
                    ->orWhereHas('category', function ($catQ) use ($q) {
                        $catQ->where('name', 'ilike', "%{$q}%");
                    });
            });
        }

        $subCategories = $query->get();

        return response()->json([
            'subCategories' => $subCategories,
            'total' => $subCategories->count(),
        ]);
    }

    /**
     * Store new Sub-Category.
     */
    public function storeSubCategory(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
            'code' => ['required', 'string', 'max:50', 'unique:sub_categories,code'],
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            'target_competency' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer'],
        ]);

        $subCategory = SubCategory::create([
            'category_id' => $validated['category_id'],
            'code' => strtoupper($validated['code']),
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'target_competency' => $validated['target_competency'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
            'sort_order' => $validated['sort_order'] ?? (SubCategory::where('category_id', $validated['category_id'])->max('sort_order') + 1),
        ]);

        AuditLog::record('SUB_CATEGORY_CREATED', 'sub_categories', $subCategory->id, [
            'code' => $subCategory->code,
            'name' => $subCategory->name,
            'category_id' => $subCategory->category_id,
        ]);

        return response()->json([
            'message' => "Sub-Kategori '{$subCategory->name}' berhasil ditambahkan ke Kategori.",
            'subCategory' => $subCategory->load('category'),
        ], 201);
    }

    /**
     * Show Sub-Category with parent Category.
     */
    public function showSubCategory(int $id): JsonResponse
    {
        $subCategory = SubCategory::with('category')->findOrFail($id);

        return response()->json([
            'subCategory' => $subCategory,
        ]);
    }

    /**
     * Update Sub-Category.
     */
    public function updateSubCategory(Request $request, int $id): JsonResponse
    {
        $subCategory = SubCategory::findOrFail($id);

        $validated = $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
            'code' => ['required', 'string', 'max:50', 'unique:sub_categories,code,'.$subCategory->id],
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            'target_competency' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer'],
        ]);

        $subCategory->update([
            'category_id' => $validated['category_id'],
            'code' => strtoupper($validated['code']),
            'name' => $validated['name'],
            'description' => $validated['description'] ?? $subCategory->description,
            'target_competency' => $validated['target_competency'] ?? $subCategory->target_competency,
            'is_active' => $validated['is_active'] ?? $subCategory->is_active,
            'sort_order' => $validated['sort_order'] ?? $subCategory->sort_order,
        ]);

        AuditLog::record('SUB_CATEGORY_UPDATED', 'sub_categories', $subCategory->id, [
            'code' => $subCategory->code,
            'name' => $subCategory->name,
        ]);

        return response()->json([
            'message' => "Sub-Kategori '{$subCategory->name}' berhasil diperbarui.",
            'subCategory' => $subCategory->load('category'),
        ]);
    }

    /**
     * Soft delete Sub-Category.
     */
    public function destroySubCategory(int $id): JsonResponse
    {
        $subCategory = SubCategory::findOrFail($id);
        $name = $subCategory->name;
        $subCategory->delete();

        AuditLog::record('SUB_CATEGORY_DELETED', 'sub_categories', $id, [
            'name' => $name,
        ]);

        return response()->json([
            'message' => "Sub-Kategori '{$name}' berhasil dihapus.",
        ]);
    }
}
