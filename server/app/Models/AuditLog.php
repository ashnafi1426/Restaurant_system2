<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    use HasUuids;

    protected $table = 'audit_logs';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false; // table only has created_at

    protected $fillable = [
        'id',
        'hotel_id',
        'user_id',
        'action',
        'target_table',
        'target_id',
        'details',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    protected $casts = [
        'details' => 'array',
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function hotel(): BelongsTo
    {
        return $this->belongsTo(Hotel::class, 'hotel_id');
    }

    public static function record(
        string $action,
        ?string $hotelId = null,
        ?string $userId = null,
        ?string $targetTable = null,
        ?string $targetId = null,
        ?array $details = null,
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): self {
        return self::create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'hotel_id' => $hotelId,
            'user_id' => $userId ?: auth()->id(),
            'action' => $action,
            'target_table' => $targetTable,
            'target_id' => $targetId,
            'details' => $details,
            'ip_address' => $ipAddress ?: request()->ip(),
            'user_agent' => $userAgent ?: request()->userAgent(),
            'created_at' => now(),
        ]);
    }
}
