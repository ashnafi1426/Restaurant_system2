<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ManagerActivityLog extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'manager_activity_logs';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'manager_id',
        'module',
        'action',
        'reference_type',
        'reference_id',
        'description',
        'ip_address',
        'device',
    ];

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }
}