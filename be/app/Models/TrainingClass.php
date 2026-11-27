<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TrainingClass extends Model
{
    protected $fillable = [
        'program_id',
        'tna_proposal_id',
        'class_code',
        'pic_name',
        'batch_name',
        'method',
        'start_date',
        'end_date',
        'max_quota',
        'enrolled_count',
        'location_or_url',
        'facilitator_id',
        'dual_control_status',
        'maker_id',
        'checker_id',
        'checker_notes',
        'approved_at',
        'daily_schedule',
        'trainer_name',
        'instructors',
        'passing_grade',
        'description',
        'notes',
        'documents',
        'documentation_photos',
        'evaluation_config',
        'is_attendance_open',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'approved_at' => 'datetime',
        'max_quota' => 'integer',
        'enrolled_count' => 'integer',
        'passing_grade' => 'integer',
        'is_attendance_open' => 'boolean',
        'documents' => 'array',
        'documentation_photos' => 'array',
        'evaluation_config' => 'array',
        'instructors' => 'array',
    ];

    public function tnaProposal(): BelongsTo
    {
        return $this->belongsTo(TnaProposal::class, 'tna_proposal_id');
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(TrainingProgram::class, 'program_id');
    }

    public function facilitator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'facilitator_id');
    }

    public function maker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'maker_id');
    }

    public function checker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checker_id');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(ClassEnrollment::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(ClassSession::class)->orderBy('session_number');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(ClassAttendance::class);
    }

    public function evaluations(): HasMany
    {
        return $this->hasMany(ClassEvaluation::class);
    }
}
