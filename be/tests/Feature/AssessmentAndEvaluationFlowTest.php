<?php

namespace Tests\Feature;

use App\Models\ClassEnrollment;
use App\Models\ClassEvaluation;
use App\Models\ClassSession;
use App\Models\TrainingClass;
use App\Models\TrainingProgram;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AssessmentAndEvaluationFlowTest extends TestCase
{
    use DatabaseTransactions;

    private function createProgramAndClass(?int $passingGrade = null): array
    {
        $admin = User::where('role_name', 'Super Admin')->firstOrFail();

        $program = TrainingProgram::create([
            'program_code' => 'PRG-TEST-'.uniqid(),
            'title' => 'Treasury & Risk Alignment',
            'category' => 'Risk Management',
            'passing_grade' => $passingGrade ?? 70,
            'duration_hours' => 8,
            'created_by' => $admin->id,
        ]);

        $class = TrainingClass::create([
            'program_id' => $program->id,
            'class_code' => 'CLS-TST-'.uniqid(),
            'batch_name' => 'Batch Test I',
            'method' => 'In-Classroom',
            'start_date' => '2026-07-01',
            'end_date' => '2026-07-02',
            'location_or_url' => 'BWS Learning Center',
            'max_quota' => 30,
            'trainer_name' => 'Dr. Hendra Gunawan',
            'instructors' => ['Dr. Hendra Gunawan', 'Siti Rahma'],
            'passing_grade' => $passingGrade ?? 70,
            'dual_control_status' => 'APPROVED',
            'maker_id' => $admin->id,
        ]);

        ClassSession::create([
            'training_class_id' => $class->id,
            'session_number' => 1,
            'session_name' => 'Day 1 Session',
            'session_date' => '2026-07-01',
            'start_time' => '08:30',
            'end_time' => '16:30',
            'topic' => 'Core Treasury Controls',
        ]);

        return [$admin, $program, $class];
    }

    public function test_excel_ld_part_import_dry_run_and_commit_maps_attendance_points_and_passing_grade_70(): void
    {
        [$admin, $program, $class] = $this->createProgramAndClass(null);
        $learner = User::where('role_name', 'Employee / Participant')->firstOrFail();

        Sanctum::actingAs($admin);

        $rows = [
            [
                'nik' => $learner->nik,
                'name' => $learner->name,
                'position' => 'Relationship Manager',
                'working_unit' => 'Treasury',
                'attendance_remark' => 'Present',
                'pre_test_score' => 40,
                'post_test_score' => 72,
            ],
            [
                'nik' => 'EXT-SICK-002',
                'name' => 'Dian Purnama',
                'position' => 'Teller',
                'working_unit' => 'Branch Bandung',
                'attendance_remark' => 'Absent - Sick',
                'pre_test_score' => 50,
                'post_test_score' => 65,
            ],
            [
                'nik' => 'EXT-BUSY-003',
                'name' => 'Agus Salim',
                'position' => 'Credit Analyst',
                'working_unit' => 'Head Office',
                'attendance_remark' => 'Absent - Busy',
                'pre_test_score' => 60,
                'post_test_score' => 80,
            ],
        ];

        $previewRes = $this->postJson("/api/classes/{$class->id}/import-participants", [
            'dry_run' => true,
            'rows' => $rows,
        ]);

        $previewRes->assertStatus(200)
            ->assertJsonPath('dry_run', true)
            ->assertJsonPath('passing_grade', 70)
            ->assertJsonPath('summary.created', 3)
            ->assertJsonPath('summary.passed', 2)
            ->assertJsonPath('summary.failed', 1)
            ->assertJsonPath('preview_rows.0.completion_status', 'PASSED')
            ->assertJsonPath('preview_rows.0.invitation_point', 1)
            ->assertJsonPath('preview_rows.0.point_add', 1)
            ->assertJsonPath('preview_rows.1.completion_status', 'FAILED')
            ->assertJsonPath('preview_rows.1.invitation_point', 0)
            ->assertJsonPath('preview_rows.1.point_add', 0)
            ->assertJsonPath('preview_rows.2.invitation_point', 1)
            ->assertJsonPath('preview_rows.2.point_add', 0);

        $this->assertEquals(0, ClassEnrollment::where('training_class_id', $class->id)->count());

        $commitRes = $this->postJson("/api/classes/{$class->id}/import-participants", [
            'dry_run' => false,
            'rows' => $rows,
        ]);

        $commitRes->assertStatus(200)
            ->assertJsonPath('dry_run', false)
            ->assertJsonPath('summary.created', 3);

        $this->assertEquals(3, ClassEnrollment::where('training_class_id', $class->id)->count());

        $enrolledLearner = ClassEnrollment::where('training_class_id', $class->id)
            ->where('nik', $learner->nik)
            ->firstOrFail();

        $this->assertNotNull($enrolledLearner->participant_code);
        $this->assertStringStartsWith('P', $enrolledLearner->participant_code);
        $this->assertEquals('PASSED', $enrolledLearner->completion_status);
        $this->assertEquals(1, $enrolledLearner->invitation_point);
        $this->assertEquals(1, $enrolledLearner->point_add);
        $this->assertEquals(1, $enrolledLearner->attendances()->where('status', 'HADIR')->count());
    }

    public function test_save_scores_scopes_to_class_allows_null_clear_and_enforces_scale_1_to_4(): void
    {
        [$admin, $program, $classA] = $this->createProgramAndClass(70);
        [, , $classB] = $this->createProgramAndClass(70);

        $enrollmentA = ClassEnrollment::create([
            'training_class_id' => $classA->id,
            'nik' => 'NIK-A-01',
            'participant_name' => 'Peserta Kelas A',
            'completion_status' => 'ONGOING',
        ]);

        $enrollmentB = ClassEnrollment::create([
            'training_class_id' => $classB->id,
            'nik' => 'NIK-B-01',
            'participant_name' => 'Peserta Kelas B',
            'completion_status' => 'ONGOING',
        ]);

        Sanctum::actingAs($admin);

        // Reject scale > 4
        $this->postJson("/api/classes/{$classA->id}/evaluation/scores", [
            'scores' => [[
                'enrollment_id' => $enrollmentA->id,
                'eval_instructor_rating' => 5,
            ]],
        ])->assertStatus(422);

        // Cross-class IDOR protection
        $this->postJson("/api/classes/{$classA->id}/evaluation/scores", [
            'scores' => [[
                'enrollment_id' => $enrollmentB->id,
                'post_test_score' => 90,
            ]],
        ])->assertStatus(404);

        // Save scores: low Pre-Test (45) + passing Post-Test (70) must PASS
        $this->postJson("/api/classes/{$classA->id}/evaluation/scores", [
            'scores' => [[
                'enrollment_id' => $enrollmentA->id,
                'pre_test_score' => 45,
                'post_test_score' => 70,
                'eval_instructor_rating' => 4,
                'eval_material_rating' => 3,
                'eval_committee_rating' => 4,
            ]],
        ])->assertStatus(200);

        $enrollmentA->refresh();
        $this->assertEquals('PASSED', $enrollmentA->completion_status);
        $this->assertEquals(70.0, (float) $enrollmentA->final_score);
        $this->assertEquals(4.0, (float) $enrollmentA->eval_committee_rating);

        // Explicitly clearing scores to null must reset final_score and status
        $this->postJson("/api/classes/{$classA->id}/evaluation/scores", [
            'scores' => [[
                'enrollment_id' => $enrollmentA->id,
                'pre_test_score' => null,
                'post_test_score' => null,
                'task_score' => null,
            ]],
        ])->assertStatus(200);

        $enrollmentA->refresh();
        $this->assertNull($enrollmentA->pre_test_score);
        $this->assertNull($enrollmentA->post_test_score);
        $this->assertNull($enrollmentA->final_score);
        $this->assertEquals('ONGOING', $enrollmentA->completion_status);
    }

    public function test_evaluations_overview_returns_real_zero_and_null_stats_without_fake_fallbacks(): void
    {
        [$admin, , $emptyClass] = $this->createProgramAndClass(70);
        Sanctum::actingAs($admin);

        $response = $this->getJson("/api/evaluations/overview?class_id={$emptyClass->id}");

        $response->assertStatus(200)
            ->assertJsonPath('stats.total_evaluated', 0)
            ->assertJsonPath('stats.avg_pre_test', 0)
            ->assertJsonPath('stats.avg_post_test', 0)
            ->assertJsonPath('stats.pass_rate_pct', 0)
            ->assertJsonPath('stats.avg_instructor_rating', null)
            ->assertJsonPath('stats.avg_material_rating', null)
            ->assertJsonPath('stats.avg_committee_rating', null);
    }

    public function test_certificate_generation_requires_passed_and_80_pct_attendance(): void
    {
        [$admin, , $class] = $this->createProgramAndClass(70);

        $eligiblePassed = ClassEnrollment::create([
            'training_class_id' => $class->id,
            'nik' => 'CERT-01',
            'participant_name' => 'Eligible Participant',
            'completion_status' => 'PASSED',
            'post_test_score' => 88,
            'final_score' => 88,
            'attendance_percentage' => 90,
        ]);

        $lowAttendancePassed = ClassEnrollment::create([
            'training_class_id' => $class->id,
            'nik' => 'CERT-02',
            'participant_name' => 'Low Attendance Passed',
            'completion_status' => 'PASSED',
            'post_test_score' => 85,
            'final_score' => 85,
            'attendance_percentage' => 50,
        ]);

        $failedHighAttendance = ClassEnrollment::create([
            'training_class_id' => $class->id,
            'nik' => 'CERT-03',
            'participant_name' => 'Failed High Attendance',
            'completion_status' => 'FAILED',
            'post_test_score' => 55,
            'final_score' => 55,
            'attendance_percentage' => 100,
        ]);

        Sanctum::actingAs($admin);

        $res = $this->postJson("/api/classes/{$class->id}/certificates/generate");
        $res->assertStatus(200)
            ->assertJsonPath('issued_count', 1);

        $this->assertNotNull($eligiblePassed->fresh()->certificate_number);
        $this->assertStringStartsWith('CERT/BWS-LIS/', $eligiblePassed->fresh()->certificate_number);
        $this->assertNull($lowAttendancePassed->fresh()->certificate_number);
        $this->assertNull($failedHighAttendance->fresh()->certificate_number);

        // Facilitator grading must also respect >=80% attendance before issuing certificate
        $gradeRes = $this->postJson("/api/enrollments/{$lowAttendancePassed->id}/grade", [
            'post_test_score' => 90,
            'task_score' => 85,
            'task_feedback' => 'Strong project plan, pending attendance make-up.',
        ]);
        $gradeRes->assertStatus(200);
        $this->assertNull($lowAttendancePassed->fresh()->certificate_number);
    }

    public function test_learner_pwa_multipart_task_leave_evaluation_1_to_4_and_commitment(): void
    {
        Storage::fake('public');

        [, , $class] = $this->createProgramAndClass(70);
        $learner = User::where('role_name', 'Employee / Participant')->firstOrFail();
        $otherLearner = User::create([
            'nik' => 'OTH-'.uniqid(),
            'name' => 'Other Learner',
            'email' => 'other_'.uniqid().'@bws.co.id',
            'password' => 'password123',
            'role_name' => 'Employee / Participant',
        ]);

        $enrollment = ClassEnrollment::create([
            'participant_code' => 'P26_990001',
            'training_class_id' => $class->id,
            'user_id' => $learner->id,
            'nik' => $learner->nik,
            'participant_name' => $learner->name,
            'attendance_status' => 'PRESENT',
            'attendance_percentage' => 100,
            'pre_test_score' => 55,
            'post_test_score' => 80,
            'final_score' => 80,
            'completion_status' => 'PASSED',
            'grade' => 'B',
        ]);

        // Another participant cannot spoof task submission
        Sanctum::actingAs($otherLearner);
        $this->postJson("/api/enrollments/{$enrollment->id}/submit-task", [
            'notes' => 'Spoofed attempt',
        ])->assertStatus(403);

        // Owner learner uploads multipart PDF Action Plan
        Sanctum::actingAs($learner);
        $taskFile = UploadedFile::fake()->create('action_plan.pdf', 120, 'application/pdf');
        $taskRes = $this->post("/api/enrollments/{$enrollment->id}/submit-task", [
            'notes' => 'Branch rollout Q3',
            'file' => $taskFile,
        ]);
        $taskRes->assertStatus(200);
        $this->assertStringStartsWith('/storage/attachments/tasks/', $enrollment->fresh()->task_submission_url);

        // Leave confirmation + attachment
        $sickNote = UploadedFile::fake()->create('medical_cert.pdf', 80, 'application/pdf');
        $leaveRes = $this->post("/api/portal/enrollments/{$enrollment->id}/leave-confirmation", [
            'leave_status' => 'Absent - Sick',
            'leave_notes' => 'Demam tinggi disertai surat dokter',
            'attachment' => $sickNote,
        ]);
        $leaveRes->assertStatus(200);
        $enrollment->refresh();
        $this->assertEquals('Absent - Sick', $enrollment->leave_status);
        $this->assertEquals(0, $enrollment->invitation_point);
        $this->assertEquals(0, $enrollment->point_add);
        $this->assertStringStartsWith('/storage/attachments/leave/', $enrollment->leave_attachment_url);

        // Evaluation 1-4 rejects score > 4
        $this->postJson("/api/portal/enrollments/{$enrollment->id}/evaluation", [
            'material_scores' => [5, 4, 4],
            'instructor_scores' => [
                ['slot' => 'P1', 'name' => 'Dr. Hendra Gunawan', 'scores' => [4, 4, 4]],
            ],
            'committee_scores' => [4, 4, 4],
        ])->assertStatus(422);

        // Valid List Evl multi-instructor 1-4 submission
        $evalRes = $this->postJson("/api/portal/enrollments/{$enrollment->id}/evaluation", [
            'material_scores' => [4, 3, 4], // avg 3.67
            'instructor_scores' => [
                ['slot' => 'P1', 'name' => 'Dr. Hendra Gunawan', 'scores' => [4, 4, 4]], // avg 4.00
                ['slot' => 'P2', 'name' => 'Siti Rahma', 'scores' => [3, 4, 3]], // avg 3.33
            ],
            'committee_scores' => [4, 4, 3], // avg 3.67
            'feedback_notes' => 'Materi aplikatif untuk cabang.',
        ]);
        $evalRes->assertStatus(200);

        $evalRecord = ClassEvaluation::where('class_enrollment_id', $enrollment->id)->firstOrFail();
        $this->assertStringStartsWith('E', $evalRecord->evaluation_code);
        $this->assertEquals(3.67, $evalRecord->material_avg);
        $this->assertEquals(3.67, $evalRecord->instructor_avg);
        $this->assertEquals(3.67, $evalRecord->committee_avg);

        // Digital E-Commitment Letter sign-off
        $this->postJson("/api/portal/enrollments/{$enrollment->id}/commitment", [
            'agreed' => true,
        ])->assertStatus(200);
        $this->assertNotNull($enrollment->fresh()->commitment_signed_at);

        // Learner portal endpoint reflects all updated fields
        $portalRes = $this->getJson('/api/portal/learner');
        $portalRes->assertStatus(200)
            ->assertJsonStructure(['evaluation_catalog' => ['material', 'instructor', 'committee']]);
    }
}
