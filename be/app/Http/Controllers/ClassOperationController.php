<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\TrainingClass;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ClassOperationController extends Controller
{
    /**
     * Maker submits a new class batch (Maker step in dual control).
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'program_id' => 'required|exists:training_programs,id',
            'batch_name' => 'required|string|max:255',
            'method' => 'required|string',
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
            'maker_id' => Auth::id(),
            'checker_id' => null,
            'approved_at' => null,
        ]);

        AuditLog::record('MAKER_CREATE_CLASS', $class, null, [
            'class_code' => $code,
            'batch_name' => $class->batch_name,
            'dual_control' => 'Submitted to HC Learning Head for review',
        ]);

        return back()->with('success', "Batch {$code} berhasil didaftarkan oleh Maker dan sedang menunggu Dual-Control Checker approval.");
    }

    /**
     * Checker reviews & approves/rejects class (Checker step in dual control).
     */
    public function approve(Request $request, $id)
    {
        $request->validate([
            'decision' => 'required|in:APPROVED,REJECTED',
            'checker_notes' => 'nullable|string',
        ]);

        $class = TrainingClass::findOrFail($id);
        $user = Auth::user();

        // Dual control integrity rule: Maker cannot be Checker on the same transaction!
        if ($class->maker_id === $user->id) {
            return back()->with('error', 'Pelanggaran Dual Control Perbankan: Maker dilarang menyetujui transaksi yang dibuat sendiri!');
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

        return back()->with('success', "Status Dual Control untuk {$class->class_code} berhasil diperbarui: {$request->decision}");
    }
}
