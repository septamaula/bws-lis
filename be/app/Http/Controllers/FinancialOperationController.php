<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\FinancialTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FinancialOperationController extends Controller
{
    /**
     * Maker submits budget disbursement voucher.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'training_class_id' => 'nullable|exists:training_classes,id',
            'category' => 'required|string',
            'description' => 'required|string',
            'amount' => 'required|numeric|min:10000',
        ]);

        $trxNumber = 'TRX-'.date('Y').'-'.str_pad(FinancialTransaction::count() + 1, 4, '0', STR_PAD_LEFT);

        $trx = FinancialTransaction::create([
            ...$validated,
            'trx_number' => $trxNumber,
            'maker_id' => Auth::id(),
            'checker_id' => null,
            'status' => 'PENDING_APPROVAL',
        ]);

        AuditLog::record('MAKER_SUBMIT_FINANCIAL', $trx, null, [
            'trx_number' => $trxNumber,
            'amount' => $trx->amount,
            'category' => $trx->category,
        ]);

        return back()->with('success', "Pengajuan pencairan {$trxNumber} telah diteruskan ke Dual-Control Checker.");
    }

    /**
     * Checker approves financial disbursement voucher.
     */
    public function approve(Request $request, $id)
    {
        $request->validate([
            'decision' => 'required|in:APPROVED,REJECTED',
            'checker_notes' => 'nullable|string',
        ]);

        $trx = FinancialTransaction::findOrFail($id);
        $user = Auth::user();

        // Strict Banking Rule: Maker cannot approve their own financial transaction
        if ($trx->maker_id === $user->id) {
            return back()->with('error', 'Pelanggaran Standar Keuangan Perbankan: Maker dilarang menyetujui voucher dana miliknya!');
        }

        $trx->update([
            'status' => $request->decision,
            'checker_id' => $user->id,
            'checker_notes' => $request->checker_notes,
            'approved_at' => now(),
            'disbursed_at' => $request->decision === 'APPROVED' ? now() : null,
        ]);

        AuditLog::record('CHECKER_DECISION_FINANCIAL', $trx, null, [
            'decision' => $request->decision,
            'trx_number' => $trx->trx_number,
            'amount' => $trx->amount,
            'checker' => $user->nik,
        ]);

        return back()->with('success', "Transaksi Keuangan {$trx->trx_number} telah disetujui untuk pencairan.");
    }
}
