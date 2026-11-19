<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClassEnrollment extends Model
{
    protected $fillable = [
        'training_class_id',
        'user_id',
        'attendance_status',
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
        'eval_instructor_rating',
        'eval_material_rating',
        'eval_facility_rating',
        'eval_notes',
        'grade',
        'certificate_number',
        'certificate_issued_at',
    ];

    protected $casts = [
        'pre_test_score' => 'decimal:2',
        'post_test_score' => 'decimal:2',
        'task_score' => 'decimal:2',
        'final_score' => 'decimal:2',
        'attendance_percentage' => 'decimal:2',
        'elapse_progress_pct' => 'integer',
        'eval_instructor_rating' => 'integer',
        'eval_material_rating' => 'integer',
        'eval_facility_rating' => 'integer',
        'task_submitted_at' => 'datetime',
        'certificate_issued_at' => 'datetime',
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
