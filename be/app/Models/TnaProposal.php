<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class TnaProposal extends Model
{
    use HasFactory, SoftDeletes;

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
        'strategic_objective',
        'target_quarter',
        'current_step_order',
        'approval_status_badge',
        'revision_count',
        'rejection_notes',
        'minimum_regulator_investment',
        'realization_amount',
        'attachment_path',
        'attachment_name',
        'attachment_size',
        'fiscal_year_id',
    ];

    protected $casts = [
        'total_budget_requested' => 'decimal:2',
        'total_budget_approved' => 'decimal:2',
        'minimum_regulator_investment' => 'decimal:2',
        'realization_amount' => 'decimal:2',
        'approved_at' => 'datetime',
        'current_step_order' => 'integer',
        'revision_count' => 'integer',
        'attachment_size' => 'integer',
    ];

    protected $appends = [
        'remaining_budget',
        'realization_percentage',
    ];

    public function getRemainingBudgetAttribute(): float
    {
        $proj = (float) ($this->total_budget_requested ?? 0);
        $real = (float) ($this->realization_amount ?? 0);
        return max(0, $proj - $real);
    }

    public function getRealizationPercentageAttribute(): float
    {
        $proj = (float) ($this->total_budget_requested ?? 0);
        if ($proj <= 0) return 0.0;
        $real = (float) ($this->realization_amount ?? 0);
        return round(($real / $proj) * 100, 1);
    }

    public function fiscalYear(): BelongsTo
    {
        return $this->belongsTo(FiscalYear::class, 'fiscal_year_id');
    }

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

    public function approvalHistories(): HasMany
    {
        return $this->hasMany(ApprovalHistory::class, 'record_id')
            ->where('module_code', 'tna')
            ->orderBy('id');
    }
}
