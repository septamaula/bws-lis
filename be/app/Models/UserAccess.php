<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserAccess extends Model
{
    use HasFactory;

    protected $table = 'user_access';

    protected $fillable = [
        'level_id',
        'controller_key',
    ];

    public function level(): BelongsTo
    {
        return $this->belongsTo(UserLevel::class, 'level_id', 'level_id');
    }
}
