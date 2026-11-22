<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class LdmsUser extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $table = 'user';

    protected $primaryKey = 'user_id';

    protected $fillable = [
        'username',
        'password',
        'nama',
        'telpon',
        'email',
        'level_id',
        'project_id',
        'karyawan_id',
        'foto',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function karyawan(): BelongsTo
    {
        return $this->belongsTo(Karyawan::class, 'karyawan_id', 'karyawan_id');
    }

    public function level(): BelongsTo
    {
        return $this->belongsTo(UserLevel::class, 'level_id', 'level_id');
    }

    public function hasAccess(string $controllerKey): bool
    {
        if ($this->level && $this->level->is_admin) {
            return true;
        }

        return $this->level ? $this->level->hasAccess($controllerKey) : false;
    }
}
