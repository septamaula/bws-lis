<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UserLevel extends Model
{
    use HasFactory;

    protected $table = 'user_level';

    protected $primaryKey = 'level_id';

    protected $fillable = [
        'level',
        'role',
        'keterangan',
        'is_admin',
        'is_active',
    ];

    protected $casts = [
        'is_admin' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'level_id', 'level_id');
    }

    public function accesses(): HasMany
    {
        return $this->hasMany(UserAccess::class, 'level_id', 'level_id');
    }

    public function getAccessKeys(): array
    {
        return $this->accesses()->pluck('controller_key')->toArray();
    }

    public function hasAccess(string $controllerKey): bool
    {
        if ($this->is_admin) {
            return true;
        }

        return $this->accesses()->where('controller_key', $controllerKey)->exists();
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
