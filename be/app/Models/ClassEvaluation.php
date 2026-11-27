<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClassEvaluation extends Model
{
    use HasFactory;

    protected $fillable = [
        'evaluation_code',
        'training_class_id',
        'class_enrollment_id',
        'user_id',
        'instructor_rating',
        'material_rating',
        'facility_rating',
        'committee_rating',
        'recommendation_rating',
        'material_scores',
        'instructor_scores',
        'committee_scores',
        'material_avg',
        'instructor_avg',
        'committee_avg',
        'overall_avg',
        'feedback_notes',
    ];

    protected $casts = [
        'instructor_rating' => 'float',
        'material_rating' => 'float',
        'facility_rating' => 'float',
        'committee_rating' => 'float',
        'recommendation_rating' => 'float',
        'material_scores' => 'array',
        'instructor_scores' => 'array',
        'committee_scores' => 'array',
        'material_avg' => 'float',
        'instructor_avg' => 'float',
        'committee_avg' => 'float',
        'overall_avg' => 'float',
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
