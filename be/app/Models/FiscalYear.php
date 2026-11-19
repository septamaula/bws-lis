<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class FiscalYear extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'year',
        'name',
        'theme',
        'start_date',
        'end_date',
        'total_budget_cap',
        'status',
        'is_active',
        'description',
    ];

    protected $casts = [
        'year' => 'integer',
        'total_budget_cap' => 'decimal:2',
        'is_active' => 'boolean',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function tnaProposals(): HasMany
    {
        return $this->hasMany(TnaProposal::class, 'fiscal_year_id');
    }
}
