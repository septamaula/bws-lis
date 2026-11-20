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
        'tna_proposal_id',
        'tna_item_id',
        'department',
        'year',
        'execution_quarter',
        'target_audience',
        'target_participants_count',
        'participant_prerequisites',
        'method',
        'location',
        'start_date',
        'end_date',
        'facilitator',
        'duration_text',
        'program_type',
        'unit_initiator',
        'activity_type',
        'bi_category',
        'budget_fee_facilitator',
        'budget_meals',
        'budget_hotel',
        'budget_meeting_package',
        'budget_transportation',
        'budget_business_trip',
        'budget_reward',
        'budget_material',
        'budget_internet',
        'budget_miscellaneous',
        'realization_amount',
        'doc1_path',
        'doc1_name',
        'doc1_remarks',
        'doc2_path',
        'doc2_name',
        'notes',
        'approval_scenario',
        'approval_doc_number',
        'approval_doc_date',
        'approval_doc_signer',
        'approval_doc_path',
        'approval_doc_name',
        'approval_doc_size',
        'total_budget',
        'status',
        'current_step_order',
        'approval_status_badge',
        'rejection_notes',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'is_mandatory' => 'boolean',
        'passing_grade' => 'integer',
        'duration_hours' => 'integer',
        'target_participants_count' => 'integer',
        'total_budget' => 'decimal:2',
        'budget_fee_facilitator' => 'decimal:2',
        'budget_meals' => 'decimal:2',
        'budget_hotel' => 'decimal:2',
        'budget_meeting_package' => 'decimal:2',
        'budget_transportation' => 'decimal:2',
        'budget_business_trip' => 'decimal:2',
        'budget_reward' => 'decimal:2',
        'budget_material' => 'decimal:2',
        'budget_internet' => 'decimal:2',
        'budget_miscellaneous' => 'decimal:2',
        'realization_amount' => 'decimal:2',
        'current_step_order' => 'integer',
        'start_date' => 'date',
        'end_date' => 'date',
        'approval_doc_date' => 'date',
        'approved_at' => 'datetime',
    ];

    protected $appends = [
        'remaining_budget',
    ];

    public function getRemainingBudgetAttribute(): float
    {
        $budget = (float) ($this->total_budget ?? 0);
        $real = (float) ($this->realization_amount ?? 0);
        return max(0, $budget - $real);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function tnaProposal(): BelongsTo
    {
        return $this->belongsTo(TnaProposal::class, 'tna_proposal_id');
    }

    public function tnaItem(): BelongsTo
    {
        return $this->belongsTo(TnaItem::class, 'tna_item_id');
    }

    public function budgetItems(): HasMany
    {
        return $this->hasMany(ProgramBudgetItem::class, 'training_program_id');
    }

    public function participants(): HasMany
    {
        return $this->hasMany(ProgramParticipant::class, 'training_program_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function approvalHistories(): HasMany
    {
        return $this->hasMany(ApprovalHistory::class, 'record_id')
            ->where('module_code', 'program')
            ->orderBy('id', 'asc');
    }

    public function classes(): HasMany
    {
        return $this->hasMany(TrainingClass::class, 'program_id');
    }
}
