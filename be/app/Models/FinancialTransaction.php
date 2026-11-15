<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinancialTransaction extends Model
{
    protected $fillable = [
        'trx_number',
        'training_class_id',
        'tna_proposal_id',
        'category',
        'description',
        'amount',
        'maker_id',
        'checker_id',
        'status',
        'checker_notes',
        'receipt_attachment_url',
        'approved_at',
        'disbursed_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'approved_at' => 'datetime',
        'disbursed_at' => 'datetime',
    ];

    public function maker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'maker_id');
    }

    public function checker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checker_id');
    }

    public function trainingClass(): BelongsTo
    {
        return $this->belongsTo(TrainingClass::class);
    }

    public function tnaProposal(): BelongsTo
    {
        return $this->belongsTo(TnaProposal::class);
    }
}
