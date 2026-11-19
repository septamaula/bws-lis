<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClassSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'training_class_id',
        'session_number',
        'session_name',
        'session_date',
        'start_time',
        'end_time',
        'topic',
        'trainer_name',
        'location_or_url',
    ];

    protected $casts = [
        'session_number' => 'integer',
        'session_date' => 'date',
    ];

    public function trainingClass(): BelongsTo
    {
        return $this->belongsTo(TrainingClass::class, 'training_class_id');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(ClassAttendance::class, 'class_session_id');
    }
}
