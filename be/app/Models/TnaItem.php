<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class TnaItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tna_proposal_id',
        'category_id',
        'category_name',
        'sub_category_id',
        'sub_category_name',
        'budget',
        'competency_gap',
        'recommended_program',
        'target_audience',
        'target_participants_count',
        'estimated_cost',
        'urgency',
        'job_family',
        'training_type',
        'execution_quarter',
        'cost_per_participant',
        'impact_kpi',
    ];

    protected $casts = [
        'target_participants_count' => 'integer',
        'cost_per_participant' => 'decimal:2',
        'estimated_cost' => 'decimal:2',
        'budget' => 'decimal:2',
    ];

    public function proposal(): BelongsTo
    {
        return $this->belongsTo(TnaProposal::class, 'tna_proposal_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function subCategory(): BelongsTo
    {
        return $this->belongsTo(SubCategory::class, 'sub_category_id');
    }
}
