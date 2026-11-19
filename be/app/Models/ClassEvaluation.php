<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClassEvaluation extends Model
{
    use HasFactory;

    protected $fillable = [
        'training_class_id',
        'class_enrollment_id',
        'user_id',
        'instructor_rating',
        'material_rating',
        'facility_rating',
        'recommendation_rating',
        'feedback_notes',
    ];

    protected $casts = [
        'instructor_rating' => 'integer',
        'material_rating' => 'integer',
        'facility_rating' => 'integer',
        'recommendation_rating' => 'integer',
    ];

    public function trainingClass(): BelongsTo
    {
        return $this->belongsTo(TrainingClass::class, 'training_class_id');
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(ClassEnrollment::class, 'class_enrollment_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
