<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Request;

class AuditLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'user_nik',
        'user_name',
        'role',
        'action',
        'entity_type',
        'entity_id',
        'ip_address',
        'user_agent',
        'old_values',
        'new_values',
        'hash',
        'created_at',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Record an immutable audit log entry with SHA-256 tamper-evident integrity hash.
     */
    public static function record(string $action, ?Model $entity = null, ?array $oldValues = null, ?array $newValues = null): self
    {
        $user = auth()->user();
        $ip = Request::ip() ?? '127.0.0.1';
        $ua = Request::userAgent() ?? 'CLI / Local System';
        $now = now();

        // Get latest hash in chain
        $latest = self::orderByDesc('id')->first();
        $prevHash = $latest ? $latest->hash : '0000000000000000000000000000000000000000000000000000000000000000';

        $payload = json_encode([
            'prev' => $prevHash,
            'user' => $user?->nik ?? 'ANONYMOUS',
            'action' => $action,
            'entity_type' => $entity ? get_class($entity) : null,
            'entity_id' => $entity?->getKey(),
            'ip' => $ip,
            'time' => $now->toIso8601String(),
            'new' => $newValues,
        ]);

        $hash = hash('sha256', $payload);

        return self::create([
            'user_id' => $user?->id,
            'user_nik' => $user?->nik,
            'user_name' => $user?->name,
            'role' => $user?->role_name,
            'action' => $action,
            'entity_type' => $entity ? get_class($entity) : null,
            'entity_id' => $entity?->getKey(),
            'ip_address' => $ip,
            'user_agent' => substr($ua, 0, 500),
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'hash' => $hash,
            'created_at' => $now,
        ]);
    }
}
