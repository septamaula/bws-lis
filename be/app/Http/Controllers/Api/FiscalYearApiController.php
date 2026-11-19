<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\FiscalYear;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FiscalYearApiController extends Controller
{
    /**
     * Get list of all Master Fiscal Years.
     */
    public function index(): JsonResponse
    {
        $years = FiscalYear::withCount('tnaProposals')
            ->orderByDesc('year')
            ->get();

        $activeYear = FiscalYear::where('is_active', true)->first();

        return response()->json([
            'years' => $years,
            'active_year' => $activeYear ? $activeYear->year : 2026,
            'active_year_data' => $activeYear,
            'total' => $years->count(),
        ]);
    }

    /**
     * Store new Fiscal Year.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'year' => ['required', 'integer', 'min:2020', 'max:2040', 'unique:fiscal_years,year'],
            'name' => ['required', 'string', 'max:100'],
            'theme' => ['nullable', 'string', 'max:255'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'total_budget_cap' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', 'in:AKTIF,PERENCANAAN,TUTUP_BUKU'],
            'is_active' => ['nullable', 'boolean'],
            'description' => ['nullable', 'string'],
        ]);

        return DB::transaction(function () use ($validated) {
            $makeActive = $validated['is_active'] ?? ($validated['status'] === 'AKTIF');

            if ($makeActive) {
                FiscalYear::query()->update(['is_active' => false]);
            }

            $fiscalYear = FiscalYear::create([
                'year' => $validated['year'],
                'name' => $validated['name'],
                'theme' => $validated['theme'] ?? null,
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
                'total_budget_cap' => $validated['total_budget_cap'] ?? 0,
                'status' => $validated['status'],
                'is_active' => $makeActive,
                'description' => $validated['description'] ?? null,
            ]);

            AuditLog::record('FISCAL_YEAR_CREATED', 'fiscal_years', $fiscalYear->id, [
                'year' => $fiscalYear->year,
                'name' => $fiscalYear->name,
                'status' => $fiscalYear->status,
            ]);

            return response()->json([
                'message' => "Master Tahun '{$fiscalYear->name}' berhasil ditambahkan.",
                'fiscalYear' => $fiscalYear->loadCount('tnaProposals'),
            ], 201);
        });
    }

    /**
     * Show Fiscal Year.
     */
    public function show(int $id): JsonResponse
    {
        $fiscalYear = FiscalYear::withCount('tnaProposals')->findOrFail($id);

        return response()->json([
            'fiscalYear' => $fiscalYear,
        ]);
    }

    /**
     * Update Fiscal Year.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $fiscalYear = FiscalYear::findOrFail($id);

        $validated = $request->validate([
            'year' => ['required', 'integer', 'min:2020', 'max:2040', 'unique:fiscal_years,year,'.$fiscalYear->id],
            'name' => ['required', 'string', 'max:100'],
            'theme' => ['nullable', 'string', 'max:255'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'total_budget_cap' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', 'in:AKTIF,PERENCANAAN,TUTUP_BUKU'],
            'is_active' => ['nullable', 'boolean'],
            'description' => ['nullable', 'string'],
        ]);

        return DB::transaction(function () use ($validated, $fiscalYear) {
            $makeActive = $validated['is_active'] ?? $fiscalYear->is_active;

            if ($makeActive) {
                FiscalYear::where('id', '!=', $fiscalYear->id)->update(['is_active' => false]);
            }

            $fiscalYear->update([
                'year' => $validated['year'],
                'name' => $validated['name'],
                'theme' => $validated['theme'] ?? $fiscalYear->theme,
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
                'total_budget_cap' => $validated['total_budget_cap'] ?? $fiscalYear->total_budget_cap,
                'status' => $validated['status'],
                'is_active' => $makeActive,
                'description' => $validated['description'] ?? $fiscalYear->description,
            ]);

            AuditLog::record('FISCAL_YEAR_UPDATED', 'fiscal_years', $fiscalYear->id, [
                'year' => $fiscalYear->year,
                'name' => $fiscalYear->name,
                'status' => $fiscalYear->status,
            ]);

            return response()->json([
                'message' => "Master Tahun '{$fiscalYear->name}' berhasil diperbarui.",
                'fiscalYear' => $fiscalYear->loadCount('tnaProposals'),
            ]);
        });
    }

    /**
     * Set a specific Fiscal Year as the Active Current Year.
     */
    public function setActive(int $id): JsonResponse
    {
        return DB::transaction(function () use ($id) {
            FiscalYear::query()->update(['is_active' => false]);

            $fiscalYear = FiscalYear::findOrFail($id);
            $fiscalYear->update([
                'is_active' => true,
                'status' => 'AKTIF',
            ]);

            AuditLog::record('FISCAL_YEAR_SET_ACTIVE', 'fiscal_years', $fiscalYear->id, [
                'year' => $fiscalYear->year,
            ]);

            return response()->json([
                'message' => "Tahun {$fiscalYear->year} ({$fiscalYear->name}) kini aktif sebagai Tahun Buku berjalan BWS.",
                'active_year' => $fiscalYear->year,
            ]);
        });
    }

    /**
     * Soft delete Fiscal Year.
     */
    public function destroy(int $id): JsonResponse
    {
        $fiscalYear = FiscalYear::findOrFail($id);

        if ($fiscalYear->is_active) {
            return response()->json([
                'message' => 'Tahun yang sedang aktif tidak dapat dihapus. Silakan aktifkan tahun lain terlebih dahulu.',
            ], 422);
        }

        $year = $fiscalYear->year;
        $fiscalYear->delete();

        AuditLog::record('FISCAL_YEAR_DELETED', 'fiscal_years', $id, [
            'year' => $year,
        ]);

        return response()->json([
            'message' => "Master Tahun {$year} berhasil dihapus.",
        ]);
    }
}
