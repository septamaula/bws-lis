<?php

namespace App\Services;

use App\Models\ClassEnrollment;
use App\Models\ClassEvaluation;
use App\Models\TrainingClass;

class ParticipantScoringService
{
    public const DEFAULT_PASSING_GRADE = 70;

    public const MIN_CERTIFICATE_ATTENDANCE = 80.0;

    /**
     * Resolve effective passing grade for a class (default 70 per Template.xlsx).
     */
    public function resolvePassingGrade(TrainingClass $class): int
    {
        return (int) ($class->passing_grade ?? $class->program?->passing_grade ?? self::DEFAULT_PASSING_GRADE);
    }

    /**
     * Map Template.xlsx Parameter sheet attendance remark or internal status
     * into standardized status values, Invitation Point, and Point Add.
     *
     * @return array{
     *     remark: string,
     *     attendance_status: string,
     *     enrollment_status: string,
     *     invitation_point: int,
     *     point_add: int,
     *     attendance_percentage: float
     * }
     */
    public function mapAttendanceRemark(?string $input): array
    {
        $normalized = strtolower(trim((string) $input));

        return match (true) {
            in_array($normalized, ['present', 'hadir', 'h', '1'], true) => [
                'remark' => 'Present',
                'attendance_status' => 'HADIR',
                'enrollment_status' => 'PRESENT',
                'invitation_point' => 1,
                'point_add' => 1,
                'attendance_percentage' => 100.0,
            ],
            in_array($normalized, ['absent - sick', 'absent-sick', 'sick', 'sakit', 's'], true) => [
                'remark' => 'Absent - Sick',
                'attendance_status' => 'SAKIT',
                'enrollment_status' => 'PERMISSION',
                'invitation_point' => 0,
                'point_add' => 0,
                'attendance_percentage' => 0.0,
            ],
            in_array($normalized, ['absent - maternity', 'absent-maternity', 'maternity', 'cuti melahirkan'], true) => [
                'remark' => 'Absent - Maternity',
                'attendance_status' => 'IZIN',
                'enrollment_status' => 'PERMISSION',
                'invitation_point' => 0,
                'point_add' => 0,
                'attendance_percentage' => 0.0,
            ],
            in_array($normalized, ['absent - business', 'absent-business', 'business', 'dinas', 'tugas dinas'], true) => [
                'remark' => 'Absent - Business',
                'attendance_status' => 'IZIN',
                'enrollment_status' => 'PERMISSION',
                'invitation_point' => 0,
                'point_add' => 0,
                'attendance_percentage' => 0.0,
            ],
            in_array($normalized, ['absent - busy', 'absent-busy', 'busy', 'izin', 'i'], true) => [
                'remark' => 'Absent - Busy',
                'attendance_status' => 'IZIN',
                'enrollment_status' => 'PERMISSION',
                'invitation_point' => 1,
                'point_add' => 0,
                'attendance_percentage' => 0.0,
            ],
            default => [
                'remark' => 'Absent',
                'attendance_status' => 'ALPA',
                'enrollment_status' => 'ABSENT',
                'invitation_point' => 1,
                'point_add' => 0,
                'attendance_percentage' => 0.0,
            ],
        };
    }

    /**
     * Evaluate Pre-Test, Post-Test, and Task scores according to unified rules:
     * - Pass/Fail is determined on Post-Test >= passingGrade (default 70),
     *   never penalizing a learner for a low baseline Pre-Test score.
     * - When both Post-Test and Task are present (e.g. Action Plan grading),
     *   final_score blends 60% Post-Test + 40% Task, while still requiring
     *   Post-Test >= passingGrade when Post-Test exists.
     * - When Post-Test and Task are both null, resets to ONGOING with null final_score/grade.
     *
     * @return array{
     *     final_score: float|null,
     *     completion_status: string,
     *     grade: string|null,
     *     elapse_progress_pct: int|null
     * }
     */
    public function evaluateScores(?float $preTest, ?float $postTest, ?float $taskScore, int $passingGrade, string $currentStatus = 'ONGOING'): array
    {
        if ($postTest === null && $taskScore === null) {
            return [
                'final_score' => null,
                'completion_status' => $currentStatus === 'CANCELLED' ? 'CANCELLED' : 'ONGOING',
                'grade' => null,
                'elapse_progress_pct' => null,
            ];
        }

        if ($postTest !== null && $taskScore !== null) {
            $finalScore = round(($postTest * 0.6) + ($taskScore * 0.4), 2);
            $passed = $postTest >= $passingGrade && $finalScore >= $passingGrade;
        } elseif ($postTest !== null) {
            $finalScore = round($postTest, 2);
            $passed = $postTest >= $passingGrade;
        } else {
            $finalScore = round((float) $taskScore, 2);
            $passed = $finalScore >= $passingGrade;
        }

        return [
            'final_score' => $finalScore,
            'completion_status' => $passed ? 'PASSED' : 'FAILED',
            'grade' => $this->calculateGrade($finalScore),
            'elapse_progress_pct' => 100,
        ];
    }

    /**
     * Map numeric score (0-100) to letter grade.
     */
    public function calculateGrade(?float $score): ?string
    {
        if ($score === null) {
            return null;
        }

        if ($score >= 90) {
            return 'A';
        }
        if ($score >= 80) {
            return 'B';
        }
        if ($score >= 70) {
            return 'C';
        }
        if ($score >= 60) {
            return 'D';
        }

        return 'E';
    }

    /**
     * Generate next sequential participant record code (e.g. P26_000001).
     */
    public function generateParticipantCode(?string $yearSuffix = null): string
    {
        $yy = $yearSuffix ?: date('y');
        $prefix = "P{$yy}_";

        $latest = ClassEnrollment::where('participant_code', 'like', $prefix.'%')
            ->orderByDesc('participant_code')
            ->value('participant_code');

        $nextSeq = 1;
        if (is_string($latest) && preg_match('/^P\d+_(\d+)$/', $latest, $matches) === 1) {
            $nextSeq = ((int) $matches[1]) + 1;
        } else {
            $nextSeq = ClassEnrollment::count() + 1;
        }

        return $prefix.str_pad((string) $nextSeq, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Generate next sequential evaluation record code (e.g. E26_000001).
     */
    public function generateEvaluationCode(?string $yearSuffix = null): string
    {
        $yy = $yearSuffix ?: date('y');
        $prefix = "E{$yy}_";

        $latest = ClassEvaluation::where('evaluation_code', 'like', $prefix.'%')
            ->orderByDesc('evaluation_code')
            ->value('evaluation_code');

        $nextSeq = 1;
        if (is_string($latest) && preg_match('/^E\d+_(\d+)$/', $latest, $matches) === 1) {
            $nextSeq = ((int) $matches[1]) + 1;
        } else {
            $nextSeq = ClassEvaluation::count() + 1;
        }

        return $prefix.str_pad((string) $nextSeq, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Check certificate eligibility (PASSED AND attendance >= 80%) and issue
     * unified certificate number format CERT/BWS-LIS/{YEAR}/{CLASS_CODE}/{SEQ}.
     */
    public function isEligibleForCertificate(ClassEnrollment $enrollment): bool
    {
        if ($enrollment->completion_status !== 'PASSED') {
            return false;
        }

        $attendancePct = $enrollment->attendance_percentage;
        if ($attendancePct === null) {
            $attendancePct = ($enrollment->attendance_status === 'PRESENT' || (int) $enrollment->point_add >= 1)
                ? 100.0
                : 0.0;
        }

        return (float) $attendancePct >= self::MIN_CERTIFICATE_ATTENDANCE;
    }

    /**
     * Issue certificate fields on eligible enrollment and return the certificate number, or null if ineligible.
     */
    public function issueCertificateIfEligible(ClassEnrollment $enrollment, ?int $sequence = null): ?string
    {
        if (! $this->isEligibleForCertificate($enrollment)) {
            return null;
        }

        if (! empty($enrollment->certificate_number)) {
            return $enrollment->certificate_number;
        }

        $enrollment->loadMissing('trainingClass.program');
        $rawCode = $enrollment->trainingClass?->program?->program_code
            ?? ($enrollment->trainingClass?->class_code ?? 'PRG');
        $codeClean = preg_replace('/[^A-Za-z0-9]/', '', $rawCode) ?: 'PRG';
        $seq = $sequence ?? (
            ClassEnrollment::whereNotNull('certificate_number')->count() + 1
        );

        $certNumber = 'CERT/BWS-LIS/'.date('Y').'/'.$codeClean.'/'.str_pad((string) $seq, 4, '0', STR_PAD_LEFT);

        $enrollment->update([
            'certificate_number' => $certNumber,
            'certificate_id' => $certNumber,
            'certificate_url' => "/certificates/{$certNumber}.pdf",
            'certificate_issued_at' => now(),
        ]);

        return $certNumber;
    }

    /**
     * Return structured List Evl question catalog (1-4 rating scale).
     *
     * @return array{
     *     scale_min: int,
     *     scale_max: int,
     *     material: array<int, array{code: string, label_en: string, label_id: string}>,
     *     instructor: array<int, array{code: string, label_en: string, label_id: string}>,
     *     committee: array<int, array{code: string, label_en: string, label_id: string}>
     * }
     */
    public function evaluationQuestionCatalog(): array
    {
        return [
            'scale_min' => 1,
            'scale_max' => 4,
            'material' => [
                [
                    'code' => 'M1',
                    'label_en' => 'Provides benefits in expanding knowledge and insight',
                    'label_id' => 'Memberikan manfaat dalam meningkatkan wawasan pengetahuan',
                ],
                [
                    'code' => 'M2',
                    'label_en' => 'Provides practical value in supporting daily work performance',
                    'label_id' => 'Memberikan manfaat dalam menunjang kinerja pekerjaan',
                ],
                [
                    'code' => 'M3',
                    'label_en' => 'Module presentation and layout are clear and easy to understand',
                    'label_id' => 'Tampilan presentasi modul dapat dimengerti dengan jelas',
                ],
            ],
            'instructor' => [
                [
                    'code' => 'P1',
                    'label_en' => 'Ability to explain training material in clear and understandable language',
                    'label_id' => 'Kemampuan menjelaskan materi dengan bahasa yang mudah dipahami',
                ],
                [
                    'code' => 'P2',
                    'label_en' => 'Mastery of the subject matter delivered during the session',
                    'label_id' => 'Menguasai isi materi yang dibawakan selama sesi pelatihan',
                ],
                [
                    'code' => 'P3',
                    'label_en' => 'Ability to create an effective learning environment in terms of method and pacing',
                    'label_id' => 'Kemampuan menciptakan suasana belajar yang efektif dari sisi metode dan waktu',
                ],
            ],
            'committee' => [
                [
                    'code' => 'KP1',
                    'label_en' => 'Organizing committee support for smooth training delivery',
                    'label_id' => 'Dukungan panitia terhadap kelancaran proses pelatihan',
                ],
                [
                    'code' => 'KP2',
                    'label_en' => 'Organizing committee responsiveness in fulfilling participant needs',
                    'label_id' => 'Dukungan panitia terhadap pemenuhan kebutuhan peserta pelatihan',
                ],
                [
                    'code' => 'KP3',
                    'label_en' => 'Organizing committee professionalism and courtesy when interacting with participants',
                    'label_id' => 'Etika dan keramahan panitia dalam berinteraksi dengan peserta pelatihan',
                ],
            ],
        ];
    }

    /**
     * Compute category and overall averages on 1.00-4.00 scale from List Evl ratings.
     *
     * @param  array<int, int|float>  $materialScores
     * @param  array<int, array{slot?: string, name?: string, scores: array<int, int|float>}>  $instructorEntries
     * @param  array<int, int|float>  $committeeScores
     * @return array{
     *     material_avg: float|null,
     *     instructor_avg: float|null,
     *     committee_avg: float|null,
     *     overall_avg: float|null,
     *     instructor_scores: array<int, array{slot: string, name: string, scores: array<int, float>, avg: float}>
     * }
     */
    public function computeEvaluationAverages(array $materialScores, array $instructorEntries, array $committeeScores): array
    {
        $mVals = array_values(array_filter(array_map('floatval', $materialScores), fn (float $v): bool => $v >= 1 && $v <= 4));
        $cVals = array_values(array_filter(array_map('floatval', $committeeScores), fn (float $v): bool => $v >= 1 && $v <= 4));

        $normalizedInstructors = [];
        $instructorAvgs = [];
        foreach ($instructorEntries as $idx => $entry) {
            $rawScores = $entry['scores'] ?? [];
            $vals = array_values(array_filter(array_map('floatval', $rawScores), fn (float $v): bool => $v >= 1 && $v <= 4));
            if ($vals === []) {
                continue;
            }
            $avg = round(array_sum($vals) / count($vals), 2);
            $instructorAvgs[] = $avg;
            $normalizedInstructors[] = [
                'slot' => (string) ($entry['slot'] ?? ('P'.($idx + 1))),
                'name' => (string) ($entry['name'] ?? ('Instructor '.($idx + 1))),
                'scores' => $vals,
                'avg' => $avg,
            ];
        }

        $materialAvg = $mVals !== [] ? round(array_sum($mVals) / count($mVals), 2) : null;
        $instructorAvg = $instructorAvgs !== [] ? round(array_sum($instructorAvgs) / count($instructorAvgs), 2) : null;
        $committeeAvg = $cVals !== [] ? round(array_sum($cVals) / count($cVals), 2) : null;

        $parts = array_values(array_filter([$materialAvg, $instructorAvg, $committeeAvg], fn (?float $v): bool => $v !== null));
        $overallAvg = $parts !== [] ? round(array_sum($parts) / count($parts), 2) : null;

        return [
            'material_avg' => $materialAvg,
            'instructor_avg' => $instructorAvg,
            'committee_avg' => $committeeAvg,
            'overall_avg' => $overallAvg,
            'instructor_scores' => $normalizedInstructors,
        ];
    }
}
