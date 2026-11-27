<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClassEnrollment extends Model
{
    protected $fillable = [
        'participant_code',
        'training_class_id',
        'user_id',
        'attendance_status',
        'attendance_remark',
        'invitation_point',
        'point_add',
        'pre_test_score',
        'post_test_score',
        'task_score',
        'final_score',
        'elapse_progress_pct',
        'completion_status',
        'task_submission_url',
        'task_submitted_at',
        'task_feedback',
        'certificate_id',
        'certificate_url',
        'nik',
        'participant_name',
        'department',
        'branch',
        'position',
        'attendance_percentage',
        'leave_status',
        'leave_notes',
        'leave_attachment_url',
        'eval_instructor_rating',
        'eval_material_rating',
        'eval_facility_rating',
        'eval_committee_rating',
        'eval_notes',
        'grade',
        'certificate_number',
        'certificate_issued_at',
        'commitment_signed_at',
        'imported_at',
    ];

    protected $casts = [
        'pre_test_score' => 'decimal:2',
        'post_test_score' => 'decimal:2',
        'task_score' => 'decimal:2',
        'final_score' => 'decimal:2',
        'attendance_percentage' => 'decimal:2',
        'invitation_point' => 'integer',
        'point_add' => 'integer',
        'elapse_progress_pct' => 'integer',
        'eval_instructor_rating' => 'float',
        'eval_material_rating' => 'float',
        'eval_facility_rating' => 'float',
        'eval_committee_rating' => 'float',
        'task_submitted_at' => 'datetime',
        'certificate_issued_at' => 'datetime',
        'commitment_signed_at' => 'datetime',
        'imported_at' => 'datetime',
    ];

    public function trainingClass(): BelongsTo
    {
        return $this->belongsTo(TrainingClass::class, 'training_class_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function attendances()
    {
        return $this->hasMany(ClassAttendance::class, 'class_enrollment_id');
    }

    public function evaluation()
    {
        return $this->hasOne(ClassEvaluation::class, 'class_enrollment_id');
    }
}
