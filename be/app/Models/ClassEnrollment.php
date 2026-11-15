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
    ];

    protected $casts = [
        'pre_test_score' => 'decimal:2',
        'post_test_score' => 'decimal:2',
        'task_score' => 'decimal:2',
        'final_score' => 'decimal:2',
        'elapse_progress_pct' => 'integer',
        'task_submitted_at' => 'datetime',
    ];

    public function trainingClass(): BelongsTo
    {
        return $this->belongsTo(TrainingClass::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
