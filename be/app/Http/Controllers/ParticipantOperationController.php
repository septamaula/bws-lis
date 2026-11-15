<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\ClassEnrollment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ParticipantOperationController extends Controller
{
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

        $enrollment = ClassEnrollment::findOrFail($id);
        $finalScore = round(($enrollment->pre_test_score * 0.2) + ($request->post_test_score * 0.4) + ($request->task_score * 0.4), 2);
        $passed = $finalScore >= ($enrollment->trainingClass->program->passing_grade ?? 75);

        $certId = $passed ? 'BWS-CERT-'.date('Y').'-'.strtoupper(substr(md5(uniqid()), 0, 8)) : null;

        $enrollment->update([
            'post_test_score' => $request->post_test_score,
            'task_score' => $request->task_score,
            'final_score' => $finalScore,
            'task_feedback' => $request->task_feedback,
            'completion_status' => $passed ? 'PASSED' : 'FAILED',
            'elapse_progress_pct' => 100,
            'certificate_id' => $certId,
            'certificate_url' => $passed ? "/secure_storage/certificates/{$certId}.pdf" : null,
        ]);

        AuditLog::record('FACILITATOR_GRADE_PARTICIPANT', $enrollment, null, [
            'final_score' => $finalScore,
            'passed' => $passed,
            'certificate_id' => $certId,
        ]);

        return back()->with('success', "Penilaian peserta berhasil disimpan. Nilai akhir: {$finalScore} (".($passed ? 'LULUS' : 'REMEDIAL').')');
    }
}
