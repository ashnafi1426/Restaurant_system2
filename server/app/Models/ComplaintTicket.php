<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComplaintTicket extends Model
{
    use HasFactory;

    protected $table = 'complaint_tickets';

    protected $fillable = [
        'ticket_number',
        'guest_id',
        'manager_id',
        'type',
        'department',
        'severity',
        'status',
        'description',
        'resolution_notes',
        'assigned_to',
        'assigned_at',
        'escalated_at',
        'resolved_at',
        'satisfaction_rating',
        'feedback',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'escalated_at' => 'datetime',
        'resolved_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->ticket_number)) {
                $model->ticket_number = 'TKT-' . date('YmdHis') . '-' . rand(1000, 9999);
            }
        });
    }

    public function guest(): BelongsTo
    {
        return $this->belongsTo(User::class, 'guest_id');
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function assignedStaff(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function assignTo($userId): void
    {
        $this->update([
            'assigned_to' => $userId,
            'assigned_at' => now(),
            'status' => 'assigned',
        ]);
    }

    public function escalate(): void
    {
        $this->update([
            'status' => 'escalated',
            'escalated_at' => now(),
            'severity' => match ($this->severity) {
                'low' => 'normal',
                'normal' => 'high',
                'high' => 'critical',
                default => $this->severity,
            },
        ]);
    }

    public function resolve($notes): void
    {
        $this->update([
            'status' => 'resolved',
            'resolution_notes' => $notes,
            'resolved_at' => now(),
        ]);
    }

    public function scopeOpen($query)
    {
        return $query->whereIn('status', ['open', 'assigned', 'in_progress']);
    }

    public function scopeUrgent($query)
    {
        return $query->whereIn('severity', ['high', 'critical']);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByDepartment($query, $department)
    {
        return $query->where('department', $department);
    }

    public function scopeUnresolved($query)
    {
        return $query->whereNotIn('status', ['resolved', 'closed']);
    }
}
