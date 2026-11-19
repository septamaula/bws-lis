<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApprovalFlowStep extends Model
{
    use HasFactory;

    protected $table = 'approval_flow_steps';

    protected $fillable = [
        'module_id',
        'step_order',
        'step_name',
        'role_name',
        'min_amount',
        'max_amount',
        'sla_days',
        'is_active',
    ];

    protected $casts = [
        'step_order' => 'integer',
        'min_amount' => 'decimal:2',
        'max_amount' => 'decimal:2',
        'sla_days' => 'integer',
        'is_active' => 'boolean',
    ];

    public function module(): BelongsTo
    {
        return $this->belongsTo(ApprovalModule::class, 'module_id');
    }
}
