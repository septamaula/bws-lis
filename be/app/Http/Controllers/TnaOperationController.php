<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\TnaItem;
use App\Models\TnaProposal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TnaOperationController extends Controller
{
    /**
     * Dept Head submits a new Training Needs Analysis (TNA) proposal.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'department' => 'required|string|max:100',
            'year' => 'required|integer|min:2025|max:2030',
            'priority' => 'required|in:Critical,High,Medium,Low',
            'items' => 'required|array|min:1',
            'items.*.competency_gap' => 'required|string',
            'items.*.recommended_program' => 'required|string',
            'items.*.target_audience' => 'required|string',
            'items.*.target_participants_count' => 'required|integer|min:1',
            'items.*.estimated_cost' => 'required|numeric|min:0',
            'items.*.urgency' => 'required|in:Critical,High,Medium,Low',
        ]);

        $code = 'TNA-'.$validated['year'].'-'.strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $validated['department']), 0, 3)).'-'.str_pad(TnaProposal::count() + 1, 3, '0', STR_PAD_LEFT);

        $totalRequested = collect($validated['items'])->sum('estimated_cost');

        $proposal = TnaProposal::create([
            'proposal_code' => $code,
            'department' => $validated['department'],
            'year' => $validated['year'],
            'proposed_by' => Auth::id(),
            'priority' => $validated['priority'],
            'total_budget_requested' => $totalRequested,
            'status' => 'SUBMITTED',
        ]);

        foreach ($validated['items'] as $item) {
            TnaItem::create([
                'tna_proposal_id' => $proposal->id,
                'competency_gap' => $item['competency_gap'],
                'recommended_program' => $item['recommended_program'],
                'target_audience' => $item['target_audience'],
                'target_participants_count' => $item['target_participants_count'],
                'estimated_cost' => $item['estimated_cost'],
                'urgency' => $item['urgency'],
            ]);
        }

        AuditLog::record('SUBMIT_TNA_PROPOSAL', $proposal, null, [
            'proposal_code' => $code,
            'department' => $proposal->department,
            'total_budget' => $totalRequested,
        ]);

        return back()->with('success', "Usulan TNA {$code} berhasil diajukan dan diteruskan ke HCBP untuk verifikasi.");
    }

    /**
     * HCBP reviews & approves/rejects the TNA proposal.
     */
    public function approve(Request $request, $id)
    {
        $request->validate([
            'decision' => 'required|in:APPROVED,REJECTED',
            'approved_budget' => 'nullable|numeric|min:0',
            'hcbp_notes' => 'nullable|string',
        ]);

        $proposal = TnaProposal::findOrFail($id);
        $user = Auth::user();

        $proposal->update([
            'status' => $request->decision,
            'total_budget_approved' => $request->decision === 'APPROVED' ? ($request->approved_budget ?? $proposal->total_budget_requested) : 0,
            'hcbp_notes' => $request->hcbp_notes,
            'approved_by' => $user->id,
            'approved_at' => now(),
        ]);

        AuditLog::record('HCBP_DECISION_TNA', $proposal, null, [
            'decision' => $request->decision,
            'proposal_code' => $proposal->proposal_code,
            'approved_budget' => $proposal->total_budget_approved,
            'hcbp' => $user->nik,
        ]);

        return back()->with('success', "Usulan TNA {$proposal->proposal_code} telah diperbarui: {$request->decision}");
    }
}
