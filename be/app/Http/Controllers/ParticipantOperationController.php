<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\ClassEnrollment;
use App\Services\ParticipantScoringService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ParticipantOperationController extends Controller
{
    public function __construct(private readonly ParticipantScoringService $scoring) {}

    /**
     * Participant uploads/submits their post-training task or action plan.
     */
    public function submitTask(Request $request, $id)
    {
        $request->validate([
            'notes' => 'nullable|string',
        ]);

        $enrollment = ClassEnrollment::where('id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $filename = 'action_plan_'.strtolower(str_replace('-', '_', Auth::user()->nik)).'.pdf';

        $enrollment->update([
            'task_submission_url' => '/secure_storage/submissions/'.$filename,
            'task_submitted_at' => now(),
            'elapse_progress_pct' => max($enrollment->elapse_progress_pct, 90),
        ]);

        AuditLog::record('PARTICIPANT_SUBMIT_TASK', $enrollment, null, [
            'class_id' => $enrollment->training_class_id,
            'file' => $filename,
        ]);

        return back()->with('success', 'Tugas Paska Pelatihan / Action Plan berhasil disubmit ke Facilitator.');
    }

    /**
     * Facilitator grades pre-test, post-test, or task.
     */
    public function grade(Request $request, $id)
    {
        $request->validate([
            'post_test_score' => 'required|numeric|min:0|max:100',
            'task_score' => 'required|numeric|min:0|max:100',
            'task_feedback' => 'nullable|string',
        ]);

        $enrollment = ClassEnrollment::with(['trainingClass.program'])->findOrFail($id);
        $passingGrade = $this->scoring->resolvePassingGrade($enrollment->trainingClass);
        $eval = $this->scoring->evaluateScores(
            $enrollment->pre_test_score !== null ? (float) $enrollment->pre_test_score : null,
            (float) $request->post_test_score,
            (float) $request->task_score,
            $passingGrade,
            $enrollment->completion_status ?? 'ONGOING'
        );

        $enrollment->update([
            'post_test_score' => (float) $request->post_test_score,
            'task_score' => (float) $request->task_score,
            'final_score' => $eval['final_score'],
            'grade' => $eval['grade'],
            'task_feedback' => $request->task_feedback,
            'completion_status' => $eval['completion_status'],
            'elapse_progress_pct' => 100,
        ]);

        $certId = $this->scoring->issueCertificateIfEligible($enrollment->fresh(['trainingClass.program']));

        AuditLog::record('FACILITATOR_GRADE_PARTICIPANT', $enrollment, null, [
            'final_score' => $eval['final_score'],
            'passed' => $eval['completion_status'] === 'PASSED',
            'certificate_id' => $certId,
        ]);

        return back()->with('success', "Penilaian peserta berhasil disimpan. Nilai akhir: {$eval['final_score']} (".($eval['completion_status'] === 'PASSED' ? 'LULUS' : 'REMEDIAL').')');
    }
}
