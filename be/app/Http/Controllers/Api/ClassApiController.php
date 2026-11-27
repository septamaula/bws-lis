<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\ClassAttendance;
use App\Models\ClassEnrollment;
use App\Models\ClassSession;
use App\Models\Karyawan;
use App\Models\ProgramParticipant;
use App\Models\TrainingClass;
use App\Models\TrainingProgram;
use App\Models\User;
use App\Services\ParticipantScoringService;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ClassApiController extends Controller
{
    public function __construct(private readonly ParticipantScoringService $scoring) {}

    /**
     * List all classes with filtering & summary statistics.
     */
    public function index(Request $request): JsonResponse
    {
        $query = TrainingClass::with(['program.tnaProposal', 'tnaProposal', 'facilitator', 'maker', 'checker'])
            ->withCount(['enrollments', 'sessions']);

        if ($request->filled('tna_id') && $request->tna_id !== 'ALL') {
            $tnaId = (int) $request->tna_id;
            $query->where(function ($q) use ($tnaId) {
                $q->where('tna_proposal_id', $tnaId)
                    ->orWhereHas('program', fn ($pq) => $pq->where('tna_proposal_id', $tnaId));
            });
        }

        if ($request->filled('program_id')) {
            $query->where('program_id', $request->program_id);
        }

        if ($request->filled('dual_control_status')) {
            $query->where('dual_control_status', $request->dual_control_status);
        }

        if ($request->filled('method')) {
            $query->where('method', 'like', "%{$request->method}%");
        }

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('class_code', 'like', "%{$s}%")
                    ->orWhere('batch_name', 'like', "%{$s}%")
                    ->orWhere('location_or_url', 'like', "%{$s}%")
                    ->orWhere('trainer_name', 'like', "%{$s}%")
                    ->orWhereHas('program', function ($pq) use ($s) {
                        $pq->where('title', 'like', "%{$s}%")
                            ->orWhere('program_code', 'like', "%{$s}%");
                    });
            });
        }

        $classes = $query->orderByDesc('id')->get();

        // Calculate summary statistics
        $allClasses = TrainingClass::all();
        $totalClasses = $allClasses->count();
        $activeClasses = $allClasses->whereIn('dual_control_status', ['APPROVED', 'ONGOING'])->count();
        $pendingChecker = $allClasses->where('dual_control_status', 'PENDING_CHECKER')->count();
        $completedClasses = $allClasses->where('dual_control_status', 'COMPLETED')->count();
        $totalEnrolled = ClassEnrollment::count();

        return response()->json([
            'classes' => $classes,
            'stats' => [
                'total_classes' => $totalClasses,
                'active_classes' => $activeClasses,
                'pending_checker' => $pendingChecker,
                'completed_classes' => $completedClasses,
                'total_enrolled' => $totalEnrolled,
            ],
        ]);
    }

    /**
     * Get class detail with sessions, enrollments, attendance, and evaluation data.
     */
    public function show(int $id): JsonResponse
    {
        $class = TrainingClass::with([
            'program.tnaProposal',
            'program.participants',
            'facilitator',
            'maker',
            'checker',
            'sessions' => function ($q) {
                $q->orderBy('session_number');
            },
            'enrollments.user',
            'enrollments.attendances',
            'enrollments.evaluation',
        ])->findOrFail($id);

        // Fetch program nominated participants that are not yet enrolled in this class
        $enrolledNiks = $class->enrollments->pluck('nik')->filter()->toArray();
        $enrolledUserIds = $class->enrollments->pluck('user_id')->filter()->toArray();

        $availableProgramParticipants = ProgramParticipant::where('training_program_id', $class->program_id)
            ->whereNotIn('nik', $enrolledNiks)
            ->get();

        // Attendance Stats
        $totalSessions = $class->sessions->count();
        $totalEnrollments = $class->enrollments->count();
        $avgAttendancePct = $totalEnrollments > 0 ? round($class->enrollments->avg('attendance_percentage'), 1) : 0;
        $eligibleForCertCount = $class->enrollments->where('attendance_percentage', '>=', 80)->count();

        // Evaluation Stats
        $scoredEnrollments = $class->enrollments->whereNotNull('post_test_score');
        $avgPre = $class->enrollments->whereNotNull('pre_test_score')->avg('pre_test_score') ?? 0;
        $avgPost = $scoredEnrollments->avg('post_test_score') ?? 0;
        $gainPct = $avgPre > 0 ? round((($avgPost - $avgPre) / $avgPre) * 100, 1) : 0;
        $passedCount = $class->enrollments->where('completion_status', 'PASSED')->count();
        $failedCount = $class->enrollments->where('completion_status', 'FAILED')->count();

        // Kirkpatrick Evaluation Stats (Scale 1-4; null when unrated instead of fabricated defaults)
        $avgMaterialRating = $class->evaluations()->avg('material_avg')
            ?? $class->evaluations()->avg('material_rating')
            ?? $class->enrollments->whereNotNull('eval_material_rating')->avg('eval_material_rating');
        $avgInstructorRating = $class->evaluations()->avg('instructor_avg')
            ?? $class->evaluations()->avg('instructor_rating')
            ?? $class->enrollments->whereNotNull('eval_instructor_rating')->avg('eval_instructor_rating');
        $avgCommitteeRating = $class->evaluations()->avg('committee_avg')
            ?? $class->evaluations()->avg('committee_rating')
            ?? $class->enrollments->whereNotNull('eval_committee_rating')->avg('eval_committee_rating');
        $avgOverallRating = $class->evaluations()->avg('overall_avg');

        $totalInvitationPoints = (int) $class->enrollments->sum(fn ($e) => (int) ($e->invitation_point ?? 1));
        $totalPointAdd = (int) $class->enrollments->sum(fn ($e) => (int) ($e->point_add ?? 0));

        return response()->json([
            'class' => $class,
            'stats' => [
                'total_sessions' => $totalSessions,
                'total_enrolled' => $totalEnrollments,
                'avg_attendance_pct' => $avgAttendancePct,
                'eligible_for_cert_count' => $eligibleForCertCount,
                'total_invitation_points' => $totalInvitationPoints,
                'total_point_add' => $totalPointAdd,
                'avg_pre_test' => round($avgPre, 1),
                'avg_post_test' => round($avgPost, 1),
                'competency_gain_pct' => $gainPct,
                'passed_count' => $passedCount,
                'failed_count' => $failedCount,
                'pass_rate_pct' => $totalEnrollments > 0 ? round(($passedCount / $totalEnrollments) * 100, 1) : 0,
                'avg_material_rating' => $avgMaterialRating !== null ? round((float) $avgMaterialRating, 2) : null,
                'avg_instructor_rating' => $avgInstructorRating !== null ? round((float) $avgInstructorRating, 2) : null,
                'avg_committee_rating' => $avgCommitteeRating !== null ? round((float) $avgCommitteeRating, 2) : null,
                'avg_overall_rating' => $avgOverallRating !== null ? round((float) $avgOverallRating, 2) : null,
            ],
            'available_program_participants' => $availableProgramParticipants,
        ]);
    }

    /**
     * Store a new Training Class Batch linked to a Program.
     * Supports: "1 program bisa beberapa class".
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'program_id' => 'required|exists:training_programs,id',
            'tna_proposal_id' => 'nullable|exists:tna_proposals,id',
            'class_code' => 'nullable|string|max:50',
            'pic_name' => 'nullable|string|max:150',
            'batch_name' => 'required|string|max:255',
            'method' => 'required|string|max:100',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'daily_schedule' => 'nullable|string|max:100',
            'location_or_url' => 'required|string',
            'max_quota' => 'required|integer|min:1|max:200',
            'facilitator_id' => 'nullable|exists:users,id',
            'trainer_name' => 'nullable|string|max:150',
            'instructors' => 'nullable|array|max:15',
            'instructors.*' => 'nullable|string|max:150',
            'passing_grade' => 'nullable|integer|min:0|max:100',
            'description' => 'nullable|string',
            'notes' => 'nullable|string',
            'sessions' => 'nullable|array',
            'documents' => 'nullable|array',
            'documentation_photos' => 'nullable|array',
            'evaluation_config' => 'nullable|array',
            'enrolled_participants' => 'nullable|array',
            'auto_approve' => 'nullable|boolean',
        ]);

        $program = TrainingProgram::findOrFail($validated['program_id']);

        // Class Code: use provided or auto generate
        if (! empty($validated['class_code'])) {
            $classCode = $validated['class_code'];
        } else {
            $existingClassesCount = TrainingClass::where('program_id', $program->id)->count();
            $batchNumber = $existingClassesCount + 1;
            $progCodeClean = preg_replace('/[^A-Za-z0-9]/', '', $program->program_code);
            $classCode = 'CLS-'.date('Y').'-'.substr($progCodeClean, -6).'-B'.$batchNumber;
            while (TrainingClass::where('class_code', $classCode)->exists()) {
                $classCode = 'CLS-'.date('Y').'-'.substr($progCodeClean, -6).'-B'.(++$batchNumber);
            }
        }

        $user = $request->user();
        $isApproverRole = in_array($user->role_name ?? '', ['Super Admin', 'HC Learning Head (Checker)', 'Executive', 'Dept Head']);
        $autoApprove = $request->boolean('auto_approve', false) && $isApproverRole;

        $dualControlStatus = $autoApprove ? 'APPROVED' : 'PENDING_CHECKER';

        $class = TrainingClass::create([
            'program_id' => $program->id,
            'tna_proposal_id' => $validated['tna_proposal_id'] ?? $program->tna_proposal_id,
            'class_code' => $classCode,
            'pic_name' => $validated['pic_name'] ?? ($user->name ?? 'Staff HC Training'),
            'batch_name' => $validated['batch_name'],
            'method' => $validated['method'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'daily_schedule' => $validated['daily_schedule'] ?? '08:30 - 16:30 WIB',
            'location_or_url' => $validated['location_or_url'],
            'max_quota' => $validated['max_quota'],
            'enrolled_count' => 0,
            'facilitator_id' => $validated['facilitator_id'] ?? null,
            'trainer_name' => $validated['trainer_name'] ?? ($program->trainer_name ?? ($program->facilitator ?? 'Tim Fasilitator BWS')),
            'instructors' => ! empty($validated['instructors']) ? array_values(array_filter($validated['instructors'])) : null,
            'passing_grade' => $validated['passing_grade'] ?? ($program->passing_grade ?? ParticipantScoringService::DEFAULT_PASSING_GRADE),
            'description' => $validated['description'] ?? "Batch pelaksanaan untuk program {$program->title}",
            'notes' => $validated['notes'] ?? null,
            'documents' => $validated['documents'] ?? [],
            'documentation_photos' => $validated['documentation_photos'] ?? [],
            'evaluation_config' => $validated['evaluation_config'] ?? [],
            'dual_control_status' => $dualControlStatus,
            'maker_id' => $user->id,
            'checker_id' => $autoApprove ? $user->id : null,
            'checker_notes' => $autoApprove ? 'Disetujui otomatis saat pembuatan oleh otoritas pembina.' : null,
            'approved_at' => $autoApprove ? now() : null,
            'is_attendance_open' => true,
        ]);

        // Create Sessions
        if (! empty($validated['sessions']) && is_array($validated['sessions'])) {
            foreach ($validated['sessions'] as $idx => $s) {
                ClassSession::create([
                    'training_class_id' => $class->id,
                    'session_number' => $idx + 1,
                    'session_name' => $s['session_name'] ?? ($s['topic'] ?? 'Sesi '.($idx + 1)),
                    'session_date' => $s['session_date'] ?? $class->start_date,
                    'start_time' => $s['start_time'] ?? '08:30',
                    'end_time' => $s['end_time'] ?? '16:30',
                    'topic' => $s['topic'] ?? null,
                    'trainer_name' => $s['trainer_name'] ?? $class->trainer_name,
                    'location_or_url' => $s['location_or_url'] ?? $class->location_or_url,
                ]);
            }
        } else {
            // Auto-generate sessions based on date range
            $startDate = Carbon::parse($class->start_date);
            $endDate = Carbon::parse($class->end_date);
            $period = CarbonPeriod::create($startDate, $endDate);

            $sessNum = 1;
            foreach ($period as $date) {
                ClassSession::create([
                    'training_class_id' => $class->id,
                    'session_number' => $sessNum,
                    'session_name' => "Hari ke-{$sessNum}: ".$date->locale('id')->isoFormat('dddd, D MMMM Y'),
                    'session_date' => $date->format('Y-m-d'),
                    'start_time' => '08:30',
                    'end_time' => '16:30',
                    'topic' => "Materi Sesi {$sessNum} - {$program->title}",
                    'trainer_name' => $class->trainer_name,
                    'location_or_url' => $class->location_or_url,
                ]);
                $sessNum++;
                if ($sessNum > 10) {
                    break;
                }
            }
        }

        // Enroll Initial Participants
        $enrolledCount = 0;
        if (! empty($validated['enrolled_participants']) && is_array($validated['enrolled_participants'])) {
            foreach ($validated['enrolled_participants'] as $p) {
                ClassEnrollment::create([
                    'training_class_id' => $class->id,
                    'user_id' => $p['user_id'] ?? null,
                    'nik' => $p['nik'] ?? null,
                    'participant_name' => $p['name'] ?? $p['participant_name'] ?? 'Peserta BWS',
                    'department' => $p['department'] ?? 'Operations',
                    'branch' => $p['branch'] ?? 'Kantor Pusat',
                    'position' => $p['position'] ?? 'Staff',
                    'attendance_status' => 'REGISTERED',
                    'completion_status' => 'NOT_STARTED',
                    'attendance_percentage' => 0,
                ]);
                $enrolledCount++;
            }
        }

        $class->update(['enrolled_count' => $enrolledCount]);

        AuditLog::record('CREATE_TRAINING_CLASS', $class, null, [
            'class_code' => $classCode,
            'batch_name' => $class->batch_name,
            'program' => $program->title,
            'enrolled_count' => $enrolledCount,
        ]);

        return response()->json([
            'message' => "Batch Kelas {$classCode} ({$class->batch_name}) berhasil dibuat untuk program {$program->title}.",
            'class' => $class->load(['program.tnaProposal', 'tnaProposal', 'sessions', 'enrollments']),
        ], 201);
    }

    /**
     * Update class details.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $class = TrainingClass::findOrFail($id);

        $validated = $request->validate([
            'program_id' => 'sometimes|required|exists:training_programs,id',
            'tna_proposal_id' => 'nullable|exists:tna_proposals,id',
            'class_code' => 'nullable|string|max:50',
            'pic_name' => 'nullable|string|max:150',
            'batch_name' => 'sometimes|required|string|max:255',
            'method' => 'sometimes|required|string|max:100',
            'start_date' => 'sometimes|required|date',
            'end_date' => 'sometimes|required|date|after_or_equal:start_date',
            'daily_schedule' => 'nullable|string|max:100',
            'location_or_url' => 'sometimes|required|string',
            'max_quota' => 'sometimes|required|integer|min:1',
            'facilitator_id' => 'nullable|exists:users,id',
            'trainer_name' => 'nullable|string|max:150',
            'instructors' => 'nullable|array|max:15',
            'instructors.*' => 'nullable|string|max:150',
            'passing_grade' => 'nullable|integer|min:0|max:100',
            'description' => 'nullable|string',
            'notes' => 'nullable|string',
            'is_attendance_open' => 'nullable|boolean',
            'dual_control_status' => 'nullable|string',
            'sessions' => 'nullable|array',
            'documents' => 'nullable|array',
            'documentation_photos' => 'nullable|array',
            'evaluation_config' => 'nullable|array',
            'enrolled_participants' => 'nullable|array',
        ]);

        $class->update($validated);

        // Update sessions if explicitly provided
        if (isset($validated['sessions']) && is_array($validated['sessions'])) {
            $class->sessions()->delete();
            foreach ($validated['sessions'] as $idx => $s) {
                ClassSession::create([
                    'training_class_id' => $class->id,
                    'session_number' => $idx + 1,
                    'session_name' => $s['session_name'] ?? ($s['topic'] ?? 'Sesi '.($idx + 1)),
                    'session_date' => $s['session_date'] ?? $class->start_date,
                    'start_time' => $s['start_time'] ?? '08:30',
                    'end_time' => $s['end_time'] ?? '16:30',
                    'topic' => $s['topic'] ?? null,
                    'trainer_name' => $s['trainer_name'] ?? $class->trainer_name,
                    'location_or_url' => $s['location_or_url'] ?? $class->location_or_url,
                ]);
            }
        }

        // Update enrolled participants if provided
        if (isset($validated['enrolled_participants']) && is_array($validated['enrolled_participants'])) {
            $class->enrollments()->delete();
            $enrolledCount = 0;
            foreach ($validated['enrolled_participants'] as $p) {
                ClassEnrollment::create([
                    'participant_code' => $p['participant_code'] ?? $this->scoring->generateParticipantCode(),
                    'training_class_id' => $class->id,
                    'user_id' => $p['user_id'] ?? null,
                    'nik' => $p['nik'] ?? null,
                    'participant_name' => $p['name'] ?? $p['participant_name'] ?? 'Peserta BWS',
                    'department' => $p['department'] ?? 'Operations',
                    'branch' => $p['branch'] ?? 'Kantor Pusat',
                    'position' => $p['position'] ?? 'Staff',
                    'attendance_status' => 'REGISTERED',
                    'completion_status' => 'NOT_STARTED',
                    'attendance_percentage' => 0,
                    'invitation_point' => 1,
                    'point_add' => 0,
                ]);
                $enrolledCount++;
            }
            $class->update(['enrolled_count' => $enrolledCount]);
        }

        return response()->json([
            'message' => "Data Batch Kelas {$class->class_code} berhasil diperbarui.",
            'class' => $class->fresh(['program.tnaProposal', 'tnaProposal', 'sessions', 'enrollments']),
        ]);
    }

    /**
     * Delete a training class batch.
     */
    public function destroy(int $id): JsonResponse
    {
        $class = TrainingClass::findOrFail($id);
        $classCode = $class->class_code;
        $class->sessions()->delete();
        $class->enrollments()->delete();
        $class->attendances()->delete();
        $class->evaluations()->delete();
        $class->delete();

        return response()->json([
            'message' => "Kelas {$classCode} berhasil dihapus.",
        ]);
    }

    /**
     * Upload an attachment file for class (materials, documents, or photos).
     */
    public function uploadAttachment(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|max:20480',
            'type' => 'nullable|string',
        ]);

        $file = $request->file('file');
        $fileName = $file->getClientOriginalName();
        $subfolder = $request->input('type') === 'photo' ? 'attachments/classes/photos' : 'attachments/classes/docs';
        $path = $file->store($subfolder, 'public');

        return response()->json([
            'message' => 'File berhasil diunggah.',
            'file_name' => $fileName,
            'file_path' => $path,
            'file_size' => $file->getSize(),
        ]);
    }

    /**
     * Dual-Control Maker-Checker Approval for Class Batch.
     */
    public function approve(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'decision' => 'required|in:APPROVED,REJECTED',
            'checker_notes' => 'nullable|string',
        ]);

        $class = TrainingClass::findOrFail($id);
        $user = $request->user();

        if ($class->maker_id === $user->id && ! in_array($user->role_name ?? '', ['Super Admin', 'Executive'])) {
            return response()->json([
                'message' => 'Pelanggaran Dual Control Perbankan: Maker dilarang menyetujui transaksi yang dibuat sendiri!',
            ], 403);
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

        return response()->json([
            'message' => "Status Dual-Control Batch {$class->class_code} telah diperbarui: {$request->decision}",
            'class' => $class->fresh(['program', 'maker', 'checker']),
        ]);
    }

    /**
     * Enroll participant(s) into the class.
     */
    public function enroll(Request $request, int $id): JsonResponse
    {
        $class = TrainingClass::findOrFail($id);

        $request->validate([
            'participants' => 'required|array|min:1',
            'participants.*.nik' => 'nullable|string',
            'participants.*.name' => 'nullable|string',
            'participants.*.department' => 'nullable|string',
            'participants.*.branch' => 'nullable|string',
            'participants.*.position' => 'nullable|string',
            'participants.*.user_id' => 'nullable|exists:users,id',
        ]);

        $added = 0;
        foreach ($request->participants as $p) {
            // Check if already enrolled
            $exists = ClassEnrollment::where('training_class_id', $class->id)
                ->where(function ($q) use ($p) {
                    if (! empty($p['user_id'])) {
                        $q->where('user_id', $p['user_id']);
                    }
                    if (! empty($p['nik'])) {
                        $q->orWhere('nik', $p['nik']);
                    }
                })->exists();

            if (! $exists) {
                ClassEnrollment::create([
                    'participant_code' => $p['participant_code'] ?? $this->scoring->generateParticipantCode(),
                    'training_class_id' => $class->id,
                    'user_id' => $p['user_id'] ?? null,
                    'nik' => $p['nik'] ?? null,
                    'participant_name' => $p['name'] ?? $p['participant_name'] ?? 'Peserta BWS',
                    'department' => $p['department'] ?? 'Operations',
                    'branch' => $p['branch'] ?? 'Kantor Pusat',
                    'position' => $p['position'] ?? 'Staff',
                    'attendance_status' => 'REGISTERED',
                    'completion_status' => 'NOT_STARTED',
                    'attendance_percentage' => 0,
                    'invitation_point' => 1,
                    'point_add' => 0,
                ]);
                $added++;
            }
        }

        $class->update(['enrolled_count' => $class->enrollments()->count()]);

        return response()->json([
            'message' => "Berhasil mendaftarkan {$added} peserta ke dalam batch {$class->class_code}.",
            'enrolled_count' => $class->enrolled_count,
        ]);
    }

    /**
     * Remove enrollment.
     */
    public function removeEnrollment(int $id, int $enrollmentId): JsonResponse
    {
        $class = TrainingClass::findOrFail($id);
        $enrollment = ClassEnrollment::where('training_class_id', $class->id)->findOrFail($enrollmentId);

        $enrollment->attendances()->delete();
        $enrollment->evaluation()->delete();
        $enrollment->delete();

        $class->update(['enrolled_count' => $class->enrollments()->count()]);

        return response()->json([
            'message' => 'Peserta berhasil dihapus dari batch kelas.',
            'enrolled_count' => $class->enrolled_count,
        ]);
    }

    // ==========================================
    // ATTENDANCE MODULE ENDPOINTS
    // ==========================================

    /**
     * Get attendance matrix for class.
     */
    public function getAttendance(int $id): JsonResponse
    {
        $class = TrainingClass::with([
            'sessions' => function ($q) {
                $q->orderBy('session_number');
            },
            'enrollments' => function ($q) {
                $q->with('attendances')->orderBy('participant_name');
            },
        ])->findOrFail($id);

        $totalSessions = $class->sessions->count();
        $enrollments = $class->enrollments->map(function ($e) use ($totalSessions) {
            $hadirCount = $e->attendances->where('status', 'HADIR')->count();
            $izinCount = $e->attendances->where('status', 'IZIN')->count();
            $sakitCount = $e->attendances->where('status', 'SAKIT')->count();
            $alpaCount = $e->attendances->where('status', 'ALPA')->count();

            $pct = $totalSessions > 0 ? round(($hadirCount / $totalSessions) * 100, 1) : 0;

            return [
                'id' => $e->id,
                'nik' => $e->nik,
                'name' => $e->participant_name ?: ($e->user->name ?? 'Peserta'),
                'department' => $e->department,
                'branch' => $e->branch,
                'position' => $e->position,
                'hadir_count' => $hadirCount,
                'izin_count' => $izinCount,
                'sakit_count' => $sakitCount,
                'alpa_count' => $alpaCount,
                'attendance_percentage' => $pct,
                'is_eligible_for_cert' => $pct >= 80,
                'attendances' => $e->attendances->keyBy('class_session_id'),
            ];
        });

        return response()->json([
            'class_id' => $class->id,
            'class_code' => $class->class_code,
            'batch_name' => $class->batch_name,
            'sessions' => $class->sessions,
            'participants' => $enrollments,
            'total_sessions' => $totalSessions,
            'is_attendance_open' => $class->is_attendance_open,
        ]);
    }

    /**
     * Record / Update attendance per session.
     */
    public function saveAttendance(Request $request, int $id): JsonResponse
    {
        $class = TrainingClass::findOrFail($id);

        $validated = $request->validate([
            'session_id' => 'required|exists:class_sessions,id',
            'attendances' => 'required|array|min:1',
            'attendances.*.enrollment_id' => 'required|exists:class_enrollments,id',
            'attendances.*.status' => 'required|in:HADIR,IZIN,SAKIT,ALPA',
            'attendances.*.check_in_time' => 'nullable|string',
            'attendances.*.notes' => 'nullable|string',
        ]);

        $session = ClassSession::where('training_class_id', $class->id)->findOrFail($validated['session_id']);
        $user = $request->user();

        $savedCount = 0;
        foreach ($validated['attendances'] as $att) {
            $enrollment = ClassEnrollment::findOrFail($att['enrollment_id']);

            ClassAttendance::updateOrCreate(
                [
                    'training_class_id' => $class->id,
                    'class_session_id' => $session->id,
                    'class_enrollment_id' => $enrollment->id,
                ],
                [
                    'user_id' => $enrollment->user_id,
                    'attendance_date' => $session->session_date,
                    'status' => $att['status'],
                    'check_in_time' => $att['check_in_time'] ?? ($att['status'] === 'HADIR' ? date('H:i').' WIB' : null),
                    'notes' => $att['notes'] ?? null,
                    'recorded_by' => $user->id,
                ]
            );
            $savedCount++;
        }

        // Recalculate attendance_percentage for all enrollments in this class
        $totalSessions = $class->sessions()->count();
        if ($totalSessions > 0) {
            foreach ($class->enrollments as $enrollment) {
                $hadir = ClassAttendance::where('class_enrollment_id', $enrollment->id)
                    ->where('status', 'HADIR')
                    ->count();
                $pct = round(($hadir / $totalSessions) * 100, 1);
                $enrollment->update([
                    'attendance_percentage' => $pct,
                    'attendance_status' => $pct >= 80 ? 'PRESENT' : ($hadir > 0 ? 'PERMISSION' : 'ABSENT'),
                    'attendance_remark' => $pct >= 80 ? 'Present' : ($hadir > 0 ? 'Absent - Busy' : 'Absent'),
                    'invitation_point' => 1,
                    'point_add' => $pct >= 80 ? 1 : 0,
                ]);
            }
        }

        return response()->json([
            'message' => "Presensi sesi {$session->session_name} berhasil disimpan ({$savedCount} peserta diperbarui).",
        ]);
    }

    /**
     * Mark all enrolled participants as HADIR for a session in 1 click.
     */
    public function quickMarkAllPresent(Request $request, int $id): JsonResponse
    {
        $class = TrainingClass::findOrFail($id);
        $request->validate([
            'session_id' => 'required|exists:class_sessions,id',
        ]);

        $session = ClassSession::where('training_class_id', $class->id)->findOrFail($request->session_id);
        $user = $request->user();
        $nowTime = date('H:i').' WIB';

        $enrollments = $class->enrollments;
        foreach ($enrollments as $e) {
            ClassAttendance::updateOrCreate(
                [
                    'training_class_id' => $class->id,
                    'class_session_id' => $session->id,
                    'class_enrollment_id' => $e->id,
                ],
                [
                    'user_id' => $e->user_id,
                    'attendance_date' => $session->session_date,
                    'status' => 'HADIR',
                    'check_in_time' => $nowTime,
                    'notes' => 'Presensi serentak via sistem',
                    'recorded_by' => $user->id,
                ]
            );
        }

        // Recalculate percentages
        $totalSessions = $class->sessions()->count();
        foreach ($enrollments as $enrollment) {
            $hadir = ClassAttendance::where('class_enrollment_id', $enrollment->id)
                ->where('status', 'HADIR')
                ->count();
            $pct = $totalSessions > 0 ? round(($hadir / $totalSessions) * 100, 1) : 0;
            $enrollment->update([
                'attendance_percentage' => $pct,
                'attendance_status' => 'PRESENT',
                'attendance_remark' => 'Present',
                'invitation_point' => 1,
                'point_add' => 1,
            ]);
        }

        return response()->json([
            'message' => "Seluruh {$enrollments->count()} peserta berhasil ditandai HADIR untuk sesi {$session->session_name}.",
        ]);
    }

    // ==========================================
    // EVALUATION MODULE ENDPOINTS
    // ==========================================

    /**
     * Get evaluation & learning assessment for class.
     */
    public function getEvaluation(int $id): JsonResponse
    {
        $class = TrainingClass::with([
            'program',
            'facilitator',
            'evaluations',
            'enrollments' => function ($q) {
                $q->with('evaluation')->orderBy('participant_name');
            },
        ])->findOrFail($id);

        $passingGrade = $this->scoring->resolvePassingGrade($class);

        $rawInstructors = is_array($class->instructors) && $class->instructors !== []
            ? $class->instructors
            : array_values(array_filter([$class->trainer_name ?? $class->facilitator?->name]));
        $instructors = [];
        foreach ($rawInstructors as $idx => $inst) {
            $instructors[] = [
                'slot' => 'P'.($idx + 1),
                'name' => is_array($inst) ? (string) ($inst['name'] ?? 'Instructor') : (string) $inst,
            ];
        }

        $participants = $class->enrollments->map(function (ClassEnrollment $e) {
            $pre = $e->pre_test_score !== null ? (float) $e->pre_test_score : null;
            $post = $e->post_test_score !== null ? (float) $e->post_test_score : null;
            $task = $e->task_score !== null ? (float) $e->task_score : null;
            $final = $e->final_score !== null ? (float) $e->final_score : null;

            $delta = null;
            if ($pre !== null && $post !== null && $pre > 0) {
                $delta = round((($post - $pre) / $pre) * 100, 1);
            }

            $eval = $e->evaluation;

            return [
                'id' => $e->id,
                'participant_code' => $e->participant_code,
                'nik' => $e->nik,
                'name' => $e->participant_name ?: ($e->user->name ?? 'Peserta'),
                'department' => $e->department,
                'branch' => $e->branch,
                'position' => $e->position,
                'attendance_percentage' => $e->attendance_percentage !== null ? (float) $e->attendance_percentage : 0.0,
                'attendance_remark' => $e->attendance_remark,
                'invitation_point' => (int) ($e->invitation_point ?? 1),
                'point_add' => (int) ($e->point_add ?? 0),
                'leave_status' => $e->leave_status,
                'leave_notes' => $e->leave_notes,
                'leave_attachment_url' => $e->leave_attachment_url,
                'commitment_signed_at' => $e->commitment_signed_at?->toIso8601String(),
                'pre_test_score' => $pre,
                'post_test_score' => $post,
                'task_score' => $task,
                'final_score' => $final,
                'delta_gain_pct' => $delta,
                'grade' => $e->grade,
                'completion_status' => $e->completion_status,
                'eval_instructor_rating' => $e->eval_instructor_rating !== null ? (float) $e->eval_instructor_rating : $eval?->instructor_avg,
                'eval_material_rating' => $e->eval_material_rating !== null ? (float) $e->eval_material_rating : $eval?->material_avg,
                'eval_facility_rating' => $e->eval_facility_rating !== null ? (float) $e->eval_facility_rating : $eval?->facility_rating,
                'eval_committee_rating' => $e->eval_committee_rating !== null ? (float) $e->eval_committee_rating : $eval?->committee_avg,
                'eval_notes' => $e->eval_notes ?? $eval?->feedback_notes,
                'evaluation_detail' => $eval ? [
                    'evaluation_code' => $eval->evaluation_code,
                    'material_scores' => $eval->material_scores,
                    'instructor_scores' => $eval->instructor_scores,
                    'committee_scores' => $eval->committee_scores,
                    'material_avg' => $eval->material_avg,
                    'instructor_avg' => $eval->instructor_avg,
                    'committee_avg' => $eval->committee_avg,
                    'overall_avg' => $eval->overall_avg,
                    'feedback_notes' => $eval->feedback_notes,
                ] : null,
                'certificate_number' => $e->certificate_number,
                'certificate_issued_at' => $e->certificate_issued_at ? Carbon::parse($e->certificate_issued_at)->format('d M Y') : null,
                'is_eligible_for_cert' => $this->scoring->isEligibleForCertificate($e),
            ];
        });

        // Summary Statistics (no fabricated fallback ratings)
        $totalEnrolled = $class->enrollments->count();
        $scoredCount = $class->enrollments->whereNotNull('post_test_score')->count();
        $avgPre = $class->enrollments->whereNotNull('pre_test_score')->avg('pre_test_score') ?? 0;
        $avgPost = $class->enrollments->whereNotNull('post_test_score')->avg('post_test_score') ?? 0;
        $avgFinal = $class->enrollments->whereNotNull('final_score')->avg('final_score') ?? 0;
        $gainPct = $avgPre > 0 ? round((($avgPost - $avgPre) / $avgPre) * 100, 1) : 0;

        $passedCount = $class->enrollments->where('completion_status', 'PASSED')->count();
        $failedCount = $class->enrollments->where('completion_status', 'FAILED')->count();
        $certifiedCount = $class->enrollments->whereNotNull('certificate_number')->count();

        $avgMatRating = $class->evaluations->whereNotNull('material_avg')->avg('material_avg')
            ?? $class->enrollments->whereNotNull('eval_material_rating')->avg('eval_material_rating');
        $avgInstRating = $class->evaluations->whereNotNull('instructor_avg')->avg('instructor_avg')
            ?? $class->enrollments->whereNotNull('eval_instructor_rating')->avg('eval_instructor_rating');
        $avgCommRating = $class->evaluations->whereNotNull('committee_avg')->avg('committee_avg')
            ?? $class->enrollments->whereNotNull('eval_committee_rating')->avg('eval_committee_rating');
        $avgOverallRating = $class->evaluations->whereNotNull('overall_avg')->avg('overall_avg');

        return response()->json([
            'class_id' => $class->id,
            'class_code' => $class->class_code,
            'batch_name' => $class->batch_name,
            'program_title' => $class->program->title,
            'passing_grade' => $passingGrade,
            'instructors' => $instructors,
            'evaluation_catalog' => $this->scoring->evaluationQuestionCatalog(),
            'participants' => $participants,
            'stats' => [
                'total_enrolled' => $totalEnrolled,
                'scored_count' => $scoredCount,
                'avg_pre_test' => round((float) $avgPre, 1),
                'avg_post_test' => round((float) $avgPost, 1),
                'avg_final_score' => round((float) $avgFinal, 1),
                'competency_gain_pct' => $gainPct,
                'passed_count' => $passedCount,
                'failed_count' => $failedCount,
                'pass_rate_pct' => $totalEnrolled > 0 ? round(($passedCount / $totalEnrolled) * 100, 1) : 0,
                'certified_count' => $certifiedCount,
                'avg_material_rating' => $avgMatRating !== null ? round((float) $avgMatRating, 2) : null,
                'avg_instructor_rating' => $avgInstRating !== null ? round((float) $avgInstRating, 2) : null,
                'avg_committee_rating' => $avgCommRating !== null ? round((float) $avgCommRating, 2) : null,
                'avg_overall_rating' => $avgOverallRating !== null ? round((float) $avgOverallRating, 2) : null,
            ],
        ]);
    }

    /**
     * Batch update scores and Kirkpatrick 1-4 evaluations for participants in this class.
     */
    public function saveScores(Request $request, int $id): JsonResponse
    {
        $class = TrainingClass::with('program')->findOrFail($id);
        $passingGrade = $this->scoring->resolvePassingGrade($class);

        $request->validate([
            'scores' => 'required|array|min:1',
            'scores.*.enrollment_id' => 'required|exists:class_enrollments,id',
            'scores.*.pre_test_score' => 'nullable|numeric|min:0|max:100',
            'scores.*.post_test_score' => 'nullable|numeric|min:0|max:100',
            'scores.*.task_score' => 'nullable|numeric|min:0|max:100',
            'scores.*.eval_instructor_rating' => 'nullable|numeric|min:1|max:4',
            'scores.*.eval_material_rating' => 'nullable|numeric|min:1|max:4',
            'scores.*.eval_facility_rating' => 'nullable|numeric|min:1|max:4',
            'scores.*.eval_committee_rating' => 'nullable|numeric|min:1|max:4',
            'scores.*.eval_notes' => 'nullable|string',
        ]);

        $updated = 0;
        foreach ($request->scores as $s) {
            $enrollment = ClassEnrollment::where('training_class_id', $class->id)
                ->findOrFail($s['enrollment_id']);

            $pre = array_key_exists('pre_test_score', $s)
                ? ($s['pre_test_score'] === null || $s['pre_test_score'] === '' ? null : (float) $s['pre_test_score'])
                : ($enrollment->pre_test_score !== null ? (float) $enrollment->pre_test_score : null);
            $post = array_key_exists('post_test_score', $s)
                ? ($s['post_test_score'] === null || $s['post_test_score'] === '' ? null : (float) $s['post_test_score'])
                : ($enrollment->post_test_score !== null ? (float) $enrollment->post_test_score : null);
            $task = array_key_exists('task_score', $s)
                ? ($s['task_score'] === null || $s['task_score'] === '' ? null : (float) $s['task_score'])
                : ($enrollment->task_score !== null ? (float) $enrollment->task_score : null);

            $evaluated = $this->scoring->evaluateScores(
                $pre,
                $post,
                $task,
                $passingGrade,
                $enrollment->completion_status ?? 'ONGOING'
            );

            $updateData = [
                'pre_test_score' => $pre,
                'post_test_score' => $post,
                'task_score' => $task,
                'final_score' => $evaluated['final_score'],
                'grade' => $evaluated['grade'],
                'completion_status' => $evaluated['completion_status'],
            ];

            if ($evaluated['elapse_progress_pct'] !== null) {
                $updateData['elapse_progress_pct'] = $evaluated['elapse_progress_pct'];
            }

            foreach (['eval_instructor_rating', 'eval_material_rating', 'eval_facility_rating', 'eval_committee_rating'] as $ratingKey) {
                if (array_key_exists($ratingKey, $s)) {
                    $updateData[$ratingKey] = $s[$ratingKey] === null || $s[$ratingKey] === ''
                        ? null
                        : (float) $s[$ratingKey];
                }
            }

            if (array_key_exists('eval_notes', $s)) {
                $updateData['eval_notes'] = $s['eval_notes'];
            }

            $enrollment->update($updateData);
            $updated++;
        }

        return response()->json([
            'message' => "Nilai evaluasi {$updated} peserta berhasil disimpan dan kelulusan diperbarui.",
            'updated_count' => $updated,
        ]);
    }

    /**
     * Import or dry-run preview participants & Pre/Post-Test scores from Excel (LD Part template).
     */
    public function importParticipants(Request $request, int $id): JsonResponse
    {
        $class = TrainingClass::with(['program', 'sessions'])->findOrFail($id);
        $passingGrade = $this->scoring->resolvePassingGrade($class);

        $validated = $request->validate([
            'dry_run' => 'nullable|boolean',
            'rows' => 'required|array|min:1|max:500',
            'rows.*.nik' => 'required|string|max:50',
            'rows.*.name' => 'nullable|string|max:255',
            'rows.*.position' => 'nullable|string|max:150',
            'rows.*.working_unit' => 'nullable|string|max:150',
            'rows.*.attendance_remark' => 'nullable|string|max:60',
            'rows.*.pre_test_score' => 'nullable|numeric|min:0|max:100',
            'rows.*.post_test_score' => 'nullable|numeric|min:0|max:100',
        ]);

        $isDryRun = $request->boolean('dry_run', false);

        $nikList = collect($validated['rows'])
            ->map(fn (array $r): string => trim((string) $r['nik']))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $usersByNik = User::whereIn('nik', $nikList)->get()->keyBy('nik');
        $karyawanByNik = Karyawan::whereIn('nik', $nikList)->get()->keyBy('nik');
        $karyawanIds = $karyawanByNik->pluck('karyawan_id')->filter()->all();
        $usersByKaryawanId = $karyawanIds !== []
            ? User::whereIn('karyawan_id', $karyawanIds)->get()->keyBy('karyawan_id')
            : collect();

        $existingByNik = ClassEnrollment::where('training_class_id', $class->id)
            ->whereIn('nik', $nikList)
            ->get()
            ->keyBy('nik');

        $previewRows = [];
        $created = 0;
        $updated = 0;
        $passed = 0;
        $failed = 0;
        $unmatchedNiks = [];

        $process = function () use (
            $validated,
            $class,
            $passingGrade,
            $isDryRun,
            $usersByNik,
            $karyawanByNik,
            $usersByKaryawanId,
            $existingByNik,
            &$previewRows,
            &$created,
            &$updated,
            &$passed,
            &$failed,
            &$unmatchedNiks,
            $request
        ): void {
            $nextCodeSeed = null;
            $yy = date('y');
            $prefix = "P{$yy}_";
            $latestCode = ClassEnrollment::where('participant_code', 'like', $prefix.'%')
                ->orderByDesc('participant_code')
                ->value('participant_code');
            if (is_string($latestCode) && preg_match('/^P\d+_(\d+)$/', $latestCode, $matches) === 1) {
                $nextCodeSeed = ((int) $matches[1]) + 1;
            } else {
                $nextCodeSeed = ClassEnrollment::count() + 1;
            }

            foreach ($validated['rows'] as $row) {
                $nik = trim((string) $row['nik']);
                $user = $usersByNik->get($nik);
                $karyawan = $karyawanByNik->get($nik);
                if (! $user && $karyawan) {
                    $user = $usersByKaryawanId->get($karyawan->karyawan_id);
                }

                if (! $user && ! $karyawan) {
                    $unmatchedNiks[] = $nik;
                }

                /** @var ClassEnrollment|null $existing */
                $existing = $existingByNik->get($nik);

                $att = $this->scoring->mapAttendanceRemark($row['attendance_remark'] ?? 'Present');
                $pre = array_key_exists('pre_test_score', $row) && $row['pre_test_score'] !== null && $row['pre_test_score'] !== ''
                    ? (float) $row['pre_test_score']
                    : null;
                $post = array_key_exists('post_test_score', $row) && $row['post_test_score'] !== null && $row['post_test_score'] !== ''
                    ? (float) $row['post_test_score']
                    : null;

                $existingTask = $existing?->task_score !== null ? (float) $existing->task_score : null;
                $evaluated = $this->scoring->evaluateScores(
                    $pre,
                    $post,
                    $existingTask,
                    $passingGrade,
                    $existing?->completion_status ?? 'ONGOING'
                );

                $action = $existing ? 'UPDATE' : 'CREATE';
                if ($action === 'CREATE') {
                    $created++;
                } else {
                    $updated++;
                }

                if ($evaluated['completion_status'] === 'PASSED') {
                    $passed++;
                } elseif ($evaluated['completion_status'] === 'FAILED') {
                    $failed++;
                }

                $resolvedName = trim((string) ($row['name'] ?? ''))
                    ?: ($karyawan?->nama ?? $user?->name ?? $existing?->participant_name ?? $nik);
                $resolvedPos = trim((string) ($row['position'] ?? ''))
                    ?: ($karyawan?->jabatan ?? $existing?->position ?? 'Staff');
                $resolvedUnit = trim((string) ($row['working_unit'] ?? ''))
                    ?: ($karyawan?->departemen ?? $user?->department ?? $existing?->department ?? 'Operations');

                $participantCode = $existing?->participant_code
                    ?: $prefix.str_pad((string) ($nextCodeSeed++), 6, '0', STR_PAD_LEFT);

                $previewRows[] = [
                    'nik' => $nik,
                    'participant_code' => $participantCode,
                    'name' => $resolvedName,
                    'position' => $resolvedPos,
                    'working_unit' => $resolvedUnit,
                    'matched_system_user' => $user !== null || $karyawan !== null,
                    'action' => $action,
                    'attendance_remark' => $att['remark'],
                    'invitation_point' => $att['invitation_point'],
                    'point_add' => $att['point_add'],
                    'pre_test_score' => $pre,
                    'post_test_score' => $post,
                    'final_score' => $evaluated['final_score'],
                    'grade' => $evaluated['grade'],
                    'completion_status' => $evaluated['completion_status'],
                ];

                if (! $isDryRun) {
                    $payload = [
                        'participant_code' => $participantCode,
                        'training_class_id' => $class->id,
                        'user_id' => $user?->id ?? $existing?->user_id,
                        'nik' => $nik,
                        'participant_name' => $resolvedName,
                        'position' => $resolvedPos,
                        'department' => $resolvedUnit,
                        'branch' => $karyawan?->kantor_cabang ?? $user?->branch ?? $existing?->branch ?? 'Kantor Pusat',
                        'attendance_remark' => $att['remark'],
                        'attendance_status' => $att['enrollment_status'],
                        'invitation_point' => $att['invitation_point'],
                        'point_add' => $att['point_add'],
                        'attendance_percentage' => $att['attendance_percentage'],
                        'pre_test_score' => $pre,
                        'post_test_score' => $post,
                        'final_score' => $evaluated['final_score'],
                        'grade' => $evaluated['grade'],
                        'completion_status' => $evaluated['completion_status'],
                        'elapse_progress_pct' => $evaluated['elapse_progress_pct'] ?? ($existing?->elapse_progress_pct ?? 0),
                        'imported_at' => now(),
                    ];

                    if ($existing) {
                        $existing->update($payload);
                        $savedEnrollment = $existing;
                    } else {
                        $savedEnrollment = ClassEnrollment::create($payload);
                    }

                    // Sync attendance sessions when no manual attendance records exist yet
                    if ($class->sessions->isNotEmpty() && ! $savedEnrollment->attendances()->exists()) {
                        foreach ($class->sessions as $session) {
                            ClassAttendance::create([
                                'training_class_id' => $class->id,
                                'class_session_id' => $session->id,
                                'class_enrollment_id' => $savedEnrollment->id,
                                'user_id' => $savedEnrollment->user_id,
                                'attendance_date' => $session->session_date,
                                'status' => $att['attendance_status'],
                                'check_in_time' => $att['attendance_status'] === 'HADIR' ? '08:30 WIB' : null,
                                'notes' => 'Excel LD Part Import: '.$att['remark'],
                                'recorded_by' => $request->user()?->id,
                            ]);
                        }
                    }
                }
            }

            if (! $isDryRun) {
                $class->update(['enrolled_count' => $class->enrollments()->count()]);

                AuditLog::record('IMPORT_CLASS_PARTICIPANTS_EXCEL', $class, null, [
                    'class_code' => $class->class_code,
                    'total_rows' => count($validated['rows']),
                    'created' => $created,
                    'updated' => $updated,
                    'passed' => $passed,
                    'failed' => $failed,
                ]);
            }
        };

        if ($isDryRun) {
            $process();
        } else {
            DB::transaction($process);
        }

        return response()->json([
            'message' => $isDryRun
                ? 'Pratinjau impor Excel peserta berhasil divalidasi.'
                : "Impor Excel selesai: {$created} peserta baru ditambahkan, {$updated} peserta diperbarui.",
            'dry_run' => $isDryRun,
            'passing_grade' => $passingGrade,
            'summary' => [
                'total_rows' => count($previewRows),
                'created' => $created,
                'updated' => $updated,
                'passed' => $passed,
                'failed' => $failed,
                'unmatched_niks' => array_values(array_unique($unmatchedNiks)),
            ],
            'preview_rows' => $previewRows,
        ]);
    }

    /**
     * Issue E-Certificates for eligible participants (PASSED + attendance >= 80%).
     */
    public function generateCertificates(Request $request, int $id): JsonResponse
    {
        $class = TrainingClass::with('program')->findOrFail($id);

        $eligible = ClassEnrollment::with('trainingClass.program')
            ->where('training_class_id', $class->id)
            ->whereNull('certificate_number')
            ->get()
            ->filter(fn (ClassEnrollment $e): bool => $this->scoring->isEligibleForCertificate($e));

        $issued = 0;
        foreach ($eligible as $e) {
            if ($this->scoring->issueCertificateIfEligible($e) !== null) {
                $issued++;
            }
        }

        AuditLog::record('GENERATE_CLASS_CERTIFICATES', $class, null, [
            'class_code' => $class->class_code,
            'issued_count' => $issued,
        ]);

        return response()->json([
            'message' => "Berhasil menerbitkan {$issued} E-Sertifikat resmi Bank Woori Saudara bagi peserta yang lulus.",
            'issued_count' => $issued,
        ]);
    }

    /**
     * Enterprise Evaluations Overview for activeTab === 'eval'.
     */
    public function evaluationsOverview(Request $request): JsonResponse
    {
        $query = ClassEnrollment::with(['trainingClass.program', 'user', 'evaluation']);

        if ($request->filled('program_id')) {
            $query->whereHas('trainingClass', function ($q) use ($request) {
                $q->where('program_id', $request->program_id);
            });
        }

        if ($request->filled('class_id')) {
            $query->where('training_class_id', $request->class_id);
        }

        if ($request->filled('completion_status')) {
            $query->where('completion_status', $request->completion_status);
        }

        $enrollments = $query->orderByDesc('id')->get();

        // Calculate real statistics over filtered set (no fabricated defaults)
        $avgPre = $enrollments->whereNotNull('pre_test_score')->avg('pre_test_score') ?? 0;
        $avgPost = $enrollments->whereNotNull('post_test_score')->avg('post_test_score') ?? 0;
        $gainPct = $avgPre > 0 ? round((($avgPost - $avgPre) / $avgPre) * 100, 1) : 0;

        $totalEnrollments = $enrollments->count();
        $passedCount = $enrollments->where('completion_status', 'PASSED')->count();
        $passRate = $totalEnrollments > 0 ? round(($passedCount / $totalEnrollments) * 100, 1) : 0;

        $avgMaterialRating = $enrollments
            ->map(fn (ClassEnrollment $e) => $e->eval_material_rating ?? $e->evaluation?->material_avg)
            ->filter(fn ($v) => $v !== null)
            ->avg();
        $avgInstructorRating = $enrollments
            ->map(fn (ClassEnrollment $e) => $e->eval_instructor_rating ?? $e->evaluation?->instructor_avg)
            ->filter(fn ($v) => $v !== null)
            ->avg();
        $avgCommitteeRating = $enrollments
            ->map(fn (ClassEnrollment $e) => $e->eval_committee_rating ?? $e->evaluation?->committee_avg)
            ->filter(fn ($v) => $v !== null)
            ->avg();
        $avgOverallRating = $enrollments
            ->map(fn (ClassEnrollment $e) => $e->evaluation?->overall_avg)
            ->filter(fn ($v) => $v !== null)
            ->avg();

        // Distinct programs and classes for dropdown filters
        $programs = TrainingProgram::select('id', 'program_code', 'title')->orderBy('title')->get();
        $classes = TrainingClass::select('id', 'program_id', 'class_code', 'batch_name')->orderByDesc('id')->get();

        $rows = $enrollments->map(function (ClassEnrollment $e) {
            $pre = $e->pre_test_score !== null ? (float) $e->pre_test_score : null;
            $post = $e->post_test_score !== null ? (float) $e->post_test_score : null;
            $delta = ($pre !== null && $post !== null && $pre > 0) ? round((($post - $pre) / $pre) * 100, 1) : null;
            $eval = $e->evaluation;

            return [
                'id' => $e->id,
                'participant_code' => $e->participant_code,
                'class_id' => $e->training_class_id,
                'class_code' => $e->trainingClass->class_code ?? 'CLS',
                'batch_name' => $e->trainingClass->batch_name ?? 'Batch',
                'program_title' => $e->trainingClass->program->title ?? 'Program Diklat',
                'nik' => $e->nik ?: ($e->user->nik ?? '-'),
                'name' => $e->participant_name ?: ($e->user->name ?? 'Peserta'),
                'department' => $e->department ?: ($e->user->department ?? '-'),
                'pre_score' => $pre,
                'post_score' => $post,
                'delta_pct' => $delta,
                'attendance_pct' => (float) ($e->attendance_percentage ?? 0),
                'invitation_point' => (int) ($e->invitation_point ?? 1),
                'point_add' => (int) ($e->point_add ?? 0),
                'final_score' => $e->final_score !== null ? (float) $e->final_score : null,
                'grade' => $e->grade,
                'status' => $e->completion_status ?: 'ONGOING',
                'material_rating' => $e->eval_material_rating !== null ? (float) $e->eval_material_rating : $eval?->material_avg,
                'instructor_rating' => $e->eval_instructor_rating !== null ? (float) $e->eval_instructor_rating : $eval?->instructor_avg,
                'committee_rating' => $e->eval_committee_rating !== null ? (float) $e->eval_committee_rating : $eval?->committee_avg,
                'overall_rating' => $eval?->overall_avg,
                'evaluation_detail' => $eval ? [
                    'evaluation_code' => $eval->evaluation_code,
                    'material_scores' => $eval->material_scores,
                    'instructor_scores' => $eval->instructor_scores,
                    'committee_scores' => $eval->committee_scores,
                    'material_avg' => $eval->material_avg,
                    'instructor_avg' => $eval->instructor_avg,
                    'committee_avg' => $eval->committee_avg,
                    'overall_avg' => $eval->overall_avg,
                    'feedback_notes' => $eval->feedback_notes,
                ] : null,
                'eval_notes' => $e->eval_notes ?? $eval?->feedback_notes,
                'certificate_number' => $e->certificate_number,
                'certificate_issued_at' => $e->certificate_issued_at,
            ];
        });

        return response()->json([
            'stats' => [
                'avg_pre_test' => round((float) $avgPre, 1),
                'avg_post_test' => round((float) $avgPost, 1),
                'competency_gain_pct' => $gainPct,
                'pass_rate_pct' => $passRate,
                'avg_material_rating' => $avgMaterialRating !== null ? round((float) $avgMaterialRating, 2) : null,
                'avg_instructor_rating' => $avgInstructorRating !== null ? round((float) $avgInstructorRating, 2) : null,
                'avg_committee_rating' => $avgCommitteeRating !== null ? round((float) $avgCommitteeRating, 2) : null,
                'avg_overall_rating' => $avgOverallRating !== null ? round((float) $avgOverallRating, 2) : null,
                'total_evaluated' => $totalEnrollments,
            ],
            'programs' => $programs,
            'classes' => $classes,
            'evaluation_records' => $rows,
        ]);
    }
}
