<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TrainingProgram extends Model
{
    protected $fillable = [
        'program_code',
        'title',
        'category',
        'description',
        'passing_grade',
        'duration_hours',
        'is_mandatory',
        'created_by',
    ];

    protected $casts = [
        'is_mandatory' => 'boolean',
        'passing_grade' => 'integer',
        'duration_hours' => 'integer',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function classes(): HasMany
    {
        return $this->hasMany(TrainingClass::class, 'program_id');
    }
}
