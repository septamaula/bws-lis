<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApprovalHistory extends Model
{
    use HasFactory;

    protected $table = 'approval_histories';

    protected $fillable = [
        'module_code',
        'record_id',
        'step_order',
        'step_name',
        'approver_id',
        'approver_name',
        'approver_nik',
        'role_name',
        'action',
        'notes',
        'approved_amount',
        'action_at',
    ];

    protected $casts = [
        'step_order' => 'integer',
        'approved_amount' => 'decimal:2',
        'action_at' => 'datetime',
    ];

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }
}
