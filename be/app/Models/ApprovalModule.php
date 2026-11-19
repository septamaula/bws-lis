<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ApprovalModule extends Model
{
    use HasFactory;

    protected $table = 'approval_modules';

    protected $fillable = [
        'module_code',
        'module_name',
        'description',
        'icon_name',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function flowSteps(): HasMany
    {
        return $this->hasMany(ApprovalFlowStep::class, 'module_id')->orderBy('step_order');
    }
}
