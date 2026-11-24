<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Memo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class MemoApiController extends Controller
{
    /**
     * Display a listing of memos (Internal & Eksternal).
     */
    public function index(Request $request): JsonResponse
    {
        $query = Memo::query();

        if ($request->filled('category') && in_array(strtoupper($request->category), ['INTERNAL', 'EKSTERNAL'])) {
            $query->where('category', strtoupper($request->category));
        }

        if ($request->filled('search')) {
            $s = trim($request->search);
            $query->where(function ($q) use ($s) {
                $q->where('memo_number', 'LIKE', "%{$s}%")
                    ->orWhere('pic', 'LIKE', "%{$s}%")
                    ->orWhere('receiver', 'LIKE', "%{$s}%")
                    ->orWhere('subject', 'LIKE', "%{$s}%");
            });
        }

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('memo_date', [$request->start_date, $request->end_date]);
        }

        $allMemos = $query->orderBy('memo_date', 'desc')->orderBy('id', 'desc')->get();

        $internal = $allMemos->where('category', 'INTERNAL')->values();
        $eksternal = $allMemos->where('category', 'EKSTERNAL')->values();

        return response()->json([
            'success' => true,
            'total' => $allMemos->count(),
            'total_internal' => $internal->count(),
            'total_eksternal' => $eksternal->count(),
            'data' => $allMemos,
            'internal' => $internal,
            'eksternal' => $eksternal,
        ]);
    }

    /**
     * Store a newly created memo.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'category' => 'required|in:INTERNAL,EKSTERNAL,internal,eksternal',
            'memo_date' => 'required|date',
            'memo_number' => 'required|string|max:120',
            'pic' => 'required|string|max:120',
            'receiver' => 'nullable|string|max:150',
            'subject' => 'required|string|max:255',
            'description' => 'nullable|string',
            'document' => 'nullable|file|max:20480',
        ]);

        $data = [
            'category' => strtoupper($validated['category']),
            'memo_date' => $validated['memo_date'],
            'memo_number' => $validated['memo_number'],
            'pic' => $validated['pic'],
            'receiver' => $validated['receiver'] ?? null,
            'subject' => $validated['subject'],
            'description' => $validated['description'] ?? null,
            'created_by' => $request->user()?->id,
            'updated_by' => $request->user()?->id,
        ];

        if ($request->hasFile('document') && $request->file('document')->isValid()) {
            $file = $request->file('document');
            $data['document_name'] = $file->getClientOriginalName();
            $path = $file->store('memos', 'public');
            $data['document_path'] = $path;
        }

        $memo = Memo::create($data);

        AuditLog::create([
            'user_id' => $request->user()?->id,
            'action' => 'CREATE_MEMO',
            'module' => 'MEMO',
            'details' => "Membuat Memo {$memo->category}: {$memo->memo_number} - {$memo->subject}",
            'ip_address' => $request->ip(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Dokumen Memo berhasil dibuat.',
            'data' => $memo,
        ], 201);
    }

    /**
     * Display the specified memo.
     */
    public function show(int $id): JsonResponse
    {
        $memo = Memo::findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $memo,
        ]);
    }

    /**
     * Update the specified memo.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $memo = Memo::findOrFail($id);

        $validated = $request->validate([
            'category' => 'sometimes|required|in:INTERNAL,EKSTERNAL,internal,eksternal',
            'memo_date' => 'sometimes|required|date',
            'memo_number' => 'sometimes|required|string|max:120',
            'pic' => 'sometimes|required|string|max:120',
            'receiver' => 'nullable|string|max:150',
            'subject' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'document' => 'nullable|file|max:20480',
        ]);

        if (isset($validated['category'])) {
            $memo->category = strtoupper($validated['category']);
        }
        if (isset($validated['memo_date'])) {
            $memo->memo_date = $validated['memo_date'];
        }
        if (isset($validated['memo_number'])) {
            $memo->memo_number = $validated['memo_number'];
        }
        if (isset($validated['pic'])) {
            $memo->pic = $validated['pic'];
        }
        if (array_key_exists('receiver', $validated)) {
            $memo->receiver = $validated['receiver'];
        }
        if (isset($validated['subject'])) {
            $memo->subject = $validated['subject'];
        }
        if (array_key_exists('description', $validated)) {
            $memo->description = $validated['description'];
        }

        if ($request->hasFile('document') && $request->file('document')->isValid()) {
            $file = $request->file('document');
            $memo->document_name = $file->getClientOriginalName();
            $path = $file->store('memos', 'public');
            $memo->document_path = $path;
        }

        $memo->updated_by = $request->user()?->id;
        $memo->save();

        AuditLog::create([
            'user_id' => $request->user()?->id,
            'action' => 'UPDATE_MEMO',
            'module' => 'MEMO',
            'details' => "Memperbarui Memo {$memo->category}: {$memo->memo_number}",
            'ip_address' => $request->ip(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Dokumen Memo berhasil diperbarui.',
            'data' => $memo,
        ]);
    }

    /**
     * Remove the specified memo.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $memo = Memo::findOrFail($id);
        $memoNumber = $memo->memo_number;
        $category = $memo->category;

        $memo->delete();

        AuditLog::create([
            'user_id' => $request->user()?->id,
            'action' => 'DELETE_MEMO',
            'module' => 'MEMO',
            'details' => "Menghapus Memo {$category}: {$memoNumber}",
            'ip_address' => $request->ip(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Memo berhasil dihapus.',
        ]);
    }

    /**
     * Download or view memo attachment document.
     */
    public function downloadDocument(int $id): BinaryFileResponse|JsonResponse
    {
        $memo = Memo::findOrFail($id);

        if (!$memo->document_path || !Storage::disk('public')->exists($memo->document_path)) {
            return response()->json(['message' => 'Berkas dokumen memo tidak ditemukan.'], 404);
        }

        $fullPath = Storage::disk('public')->path($memo->document_path);

        return response()->download($fullPath, $memo->document_name ?: "Memo_{$memo->memo_number}.pdf");
    }
}
