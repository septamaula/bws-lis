<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TnaItem extends Model
{
    protected $fillable = [
        'tna_proposal_id',
        'competency_gap',
        'recommended_program',
        'target_audience',
        'target_participants_count',
        'estimated_cost',
        'urgency',
    ];

    protected $casts = [
        'estimated_cost' => 'decimal:2',
    ];

    public function proposal(): BelongsTo
    {
        return $this->belongsTo(TnaProposal::class, 'tna_proposal_id');
    }
}
