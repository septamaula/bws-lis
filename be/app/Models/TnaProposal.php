<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TnaProposal extends Model
{
    protected $fillable = [
        'proposal_code',
        'department',
        'year',
        'proposed_by',
        'priority',
        'total_budget_requested',
        'total_budget_approved',
        'status',
        'rejection_reason',
        'hcbp_notes',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'total_budget_requested' => 'decimal:2',
        'total_budget_approved' => 'decimal:2',
        'approved_at' => 'datetime',
    ];

    public function proposer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'proposed_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(TnaItem::class);
    }
}
