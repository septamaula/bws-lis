<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\ClassAttendance;
use App\Models\ClassEnrollment;
use App\Models\ClassSession;
use App\Models\ProgramParticipant;
use App\Models\TrainingClass;
use App\Models\TrainingProgram;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClassApiController extends Controller
{
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

        // Instructor Rating Stats
        $avgInstructorRating = $class->evaluations()->avg('instructor_rating')
            ?: $class->enrollments->whereNotNull('eval_instructor_rating')->avg('eval_instructor_rating')
            ?: 4.8;

        return response()->json([
            'class' => $class,
            'stats' => [
                'total_sessions' => $totalSessions,
                'total_enrolled' => $totalEnrollments,
                'avg_attendance_pct' => $avgAttendancePct,
                'eligible_for_cert_count' => $eligibleForCertCount,
                'avg_pre_test' => round($avgPre, 1),
                'avg_post_test' => round($avgPost, 1),
                'competency_gain_pct' => $gainPct,
                'passed_count' => $passedCount,
                'failed_count' => $failedCount,
                'pass_rate_pct' => $totalEnrollments > 0 ? round(($passedCount / $totalEnrollments) * 100, 1) : 0,
                'avg_instructor_rating' => round($avgInstructorRating, 2),
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
            'passing_grade' => $validated['passing_grade'] ?? ($program->passing_grade ?? 75),
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
            'enrollments' => function ($q) {
                $q->with('evaluation')->orderBy('participant_name');
            },
        ])->findOrFail($id);

        $passingGrade = $class->passing_grade ?? 75;

        $participants = $class->enrollments->map(function ($e) {
            $pre = $e->pre_test_score !== null ? (float) $e->pre_test_score : null;
            $post = $e->post_test_score !== null ? (float) $e->post_test_score : null;
            $task = $e->task_score !== null ? (float) $e->task_score : null;
            $final = $e->final_score !== null ? (float) $e->final_score : null;

            $delta = null;
            if ($pre !== null && $post !== null && $pre > 0) {
                $delta = round((($post - $pre) / $pre) * 100, 1);
            }

            return [
                'id' => $e->id,
                'nik' => $e->nik,
                'name' => $e->participant_name ?: ($e->user->name ?? 'Peserta'),
                'department' => $e->department,
                'branch' => $e->branch,
                'position' => $e->position,
                'attendance_percentage' => (float) $e->attendance_percentage,
                'pre_test_score' => $pre,
                'post_test_score' => $post,
                'task_score' => $task,
                'final_score' => $final,
                'delta_gain_pct' => $delta,
                'grade' => $e->grade,
                'completion_status' => $e->completion_status,
                'eval_instructor_rating' => $e->eval_instructor_rating,
                'eval_material_rating' => $e->eval_material_rating,
                'eval_facility_rating' => $e->eval_facility_rating,
                'eval_notes' => $e->eval_notes,
                'certificate_number' => $e->certificate_number,
                'certificate_issued_at' => $e->certificate_issued_at ? Carbon::parse($e->certificate_issued_at)->format('d M Y') : null,
                'is_eligible_for_cert' => ($e->completion_status === 'PASSED' && $e->attendance_percentage >= 80),
            ];
        });

        // Summary Statistics
        $totalEnrolled = $class->enrollments->count();
        $scoredCount = $class->enrollments->whereNotNull('post_test_score')->count();
        $avgPre = $class->enrollments->whereNotNull('pre_test_score')->avg('pre_test_score') ?? 0;
        $avgPost = $class->enrollments->whereNotNull('post_test_score')->avg('post_test_score') ?? 0;
        $avgFinal = $class->enrollments->whereNotNull('final_score')->avg('final_score') ?? 0;
        $gainPct = $avgPre > 0 ? round((($avgPost - $avgPre) / $avgPre) * 100, 1) : 0;

        $passedCount = $class->enrollments->where('completion_status', 'PASSED')->count();
        $failedCount = $class->enrollments->where('completion_status', 'FAILED')->count();
        $certifiedCount = $class->enrollments->whereNotNull('certificate_number')->count();

        $avgInstRating = $class->enrollments->whereNotNull('eval_instructor_rating')->avg('eval_instructor_rating') ?: 4.8;

        return response()->json([
            'class_id' => $class->id,
            'class_code' => $class->class_code,
            'batch_name' => $class->batch_name,
            'program_title' => $class->program->title,
            'passing_grade' => $passingGrade,
            'participants' => $participants,
            'stats' => [
                'total_enrolled' => $totalEnrolled,
                'scored_count' => $scoredCount,
                'avg_pre_test' => round($avgPre, 1),
                'avg_post_test' => round($avgPost, 1),
                'avg_final_score' => round($avgFinal, 1),
                'competency_gain_pct' => $gainPct,
                'passed_count' => $passedCount,
                'failed_count' => $failedCount,
                'pass_rate_pct' => $totalEnrolled > 0 ? round(($passedCount / $totalEnrolled) * 100, 1) : 0,
                'certified_count' => $certifiedCount,
                'avg_instructor_rating' => round($avgInstRating, 2),
            ],
        ]);
    }

    /**
     * Batch update scores for participants.
     */
    public function saveScores(Request $request, int $id): JsonResponse
    {
        $class = TrainingClass::findOrFail($id);
        $passingGrade = $class->passing_grade ?? 75;

        $request->validate([
            'scores' => 'required|array|min:1',
            'scores.*.enrollment_id' => 'required|exists:class_enrollments,id',
            'scores.*.pre_test_score' => 'nullable|numeric|min:0|max:100',
            'scores.*.post_test_score' => 'nullable|numeric|min:0|max:100',
            'scores.*.task_score' => 'nullable|numeric|min:0|max:100',
            'scores.*.eval_instructor_rating' => 'nullable|integer|min:1|max:5',
            'scores.*.eval_material_rating' => 'nullable|integer|min:1|max:5',
            'scores.*.eval_facility_rating' => 'nullable|integer|min:1|max:5',
            'scores.*.eval_notes' => 'nullable|string',
        ]);

        $updated = 0;
        foreach ($request->scores as $s) {
            $enrollment = ClassEnrollment::findOrFail($s['enrollment_id']);

            $pre = isset($s['pre_test_score']) && $s['pre_test_score'] !== '' ? (float) $s['pre_test_score'] : $enrollment->pre_test_score;
            $post = isset($s['post_test_score']) && $s['post_test_score'] !== '' ? (float) $s['post_test_score'] : $enrollment->post_test_score;
            $task = isset($s['task_score']) && $s['task_score'] !== '' ? (float) $s['task_score'] : $enrollment->task_score;

            // Calculate final score
            $final = null;
            if ($post !== null) {
                if ($task !== null) {
                    $final = round(($pre * 0.2) + ($post * 0.5) + ($task * 0.3), 2);
                } elseif ($pre !== null) {
                    $final = round(($pre * 0.3) + ($post * 0.7), 2);
                } else {
                    $final = $post;
                }
            }

            // Grade and completion
            $status = $enrollment->completion_status;
            $grade = null;
            if ($final !== null) {
                $status = ($final >= $passingGrade) ? 'PASSED' : 'FAILED';
                if ($final >= 90) {
                    $grade = 'A';
                } elseif ($final >= 80) {
                    $grade = 'B';
                } elseif ($final >= 70) {
                    $grade = 'C';
                } elseif ($final >= 60) {
                    $grade = 'D';
                } else {
                    $grade = 'E';
                }
            }

            $updateData = [
                'pre_test_score' => $pre,
                'post_test_score' => $post,
                'task_score' => $task,
                'final_score' => $final,
                'grade' => $grade,
                'completion_status' => $status,
            ];

            if (isset($s['eval_instructor_rating'])) {
                $updateData['eval_instructor_rating'] = $s['eval_instructor_rating'];
            }
            if (isset($s['eval_material_rating'])) {
                $updateData['eval_material_rating'] = $s['eval_material_rating'];
            }
            if (isset($s['eval_facility_rating'])) {
                $updateData['eval_facility_rating'] = $s['eval_facility_rating'];
            }
            if (isset($s['eval_notes'])) {
                $updateData['eval_notes'] = $s['eval_notes'];
            }

            $enrollment->update($updateData);
            $updated++;
        }

        return response()->json([
            'message' => "Nilai evaluasi {$updated} peserta berhasil disimpan dan kelulusan diperbarui.",
        ]);
    }

    /**
     * Issue E-Certificates for eligible participants (PASSED + attendance >= 80%).
     */
    public function generateCertificates(Request $request, int $id): JsonResponse
    {
        $class = TrainingClass::with('program')->findOrFail($id);

        $eligible = ClassEnrollment::where('training_class_id', $class->id)
            ->where('completion_status', 'PASSED')
            ->where('attendance_percentage', '>=', 80)
            ->whereNull('certificate_number')
            ->get();

        $issued = 0;
        $year = date('Y');
        $codeClean = preg_replace('/[^A-Za-z0-9]/', '', $class->program->program_code ?? 'PRG');

        foreach ($eligible as $e) {
            $seq = str_pad(ClassEnrollment::whereNotNull('certificate_number')->count() + 1, 4, '0', STR_PAD_LEFT);
            $certNumber = "CERT/BWS-LIS/{$year}/{$codeClean}/{$seq}";

            $e->update([
                'certificate_id' => $certNumber,
                'certificate_number' => $certNumber,
                'certificate_url' => "/certificates/{$certNumber}.pdf",
                'certificate_issued_at' => now(),
            ]);
            $issued++;
        }

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
        $query = ClassEnrollment::with(['trainingClass.program', 'user']);

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

        // Calculate global statistics
        $allScored = ClassEnrollment::whereNotNull('post_test_score')->get();
        $avgPre = ClassEnrollment::whereNotNull('pre_test_score')->avg('pre_test_score') ?? 62;
        $avgPost = $allScored->avg('post_test_score') ?? 86;
        $gainPct = $avgPre > 0 ? round((($avgPost - $avgPre) / $avgPre) * 100, 1) : 38.7;

        $totalEnrollments = ClassEnrollment::count();
        $passedCount = ClassEnrollment::where('completion_status', 'PASSED')->count();
        $passRate = $totalEnrollments > 0 ? round(($passedCount / $totalEnrollments) * 100, 1) : 92.5;

        $avgInstructorRating = ClassEnrollment::whereNotNull('eval_instructor_rating')->avg('eval_instructor_rating') ?: 4.85;

        // Distinct programs and classes for dropdown filters
        $programs = TrainingProgram::select('id', 'program_code', 'title')->orderBy('title')->get();
        $classes = TrainingClass::select('id', 'program_id', 'class_code', 'batch_name')->orderByDesc('id')->get();

        $rows = $enrollments->map(function ($e) {
            $pre = (float) $e->pre_test_score;
            $post = (float) $e->post_test_score;
            $delta = ($pre > 0 && $post > 0) ? round((($post - $pre) / $pre) * 100, 1) : 0;

            return [
                'id' => $e->id,
                'class_id' => $e->training_class_id,
                'class_code' => $e->trainingClass->class_code ?? 'CLS',
                'batch_name' => $e->trainingClass->batch_name ?? 'Batch',
                'program_title' => $e->trainingClass->program->title ?? 'Program Diklat',
                'nik' => $e->nik ?: ($e->user->nik ?? '-'),
                'name' => $e->participant_name ?: ($e->user->name ?? 'Peserta'),
                'department' => $e->department ?: ($e->user->department ?? '-'),
                'pre_score' => $pre ?: null,
                'post_score' => $post ?: null,
                'delta_pct' => $delta,
                'attendance_pct' => (float) $e->attendance_percentage,
                'final_score' => (float) $e->final_score,
                'grade' => $e->grade,
                'status' => $e->completion_status ?: 'IN_PROGRESS',
                'instructor_rating' => $e->eval_instructor_rating ?: 5,
                'eval_notes' => $e->eval_notes ?: ($post >= 80 ? 'Sangat Baik & Kompeten' : 'Cukup'),
                'certificate_number' => $e->certificate_number,
                'certificate_issued_at' => $e->certificate_issued_at,
            ];
        });

        return response()->json([
            'stats' => [
                'avg_pre_test' => round($avgPre, 1),
                'avg_post_test' => round($avgPost, 1),
                'competency_gain_pct' => $gainPct,
                'pass_rate_pct' => $passRate,
                'avg_instructor_rating' => round($avgInstructorRating, 2),
                'total_evaluated' => $totalEnrollments,
            ],
            'programs' => $programs,
            'classes' => $classes,
            'evaluation_records' => $rows,
        ]);
    }
}
