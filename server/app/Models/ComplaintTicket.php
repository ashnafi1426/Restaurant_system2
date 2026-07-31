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

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->ticket_number)) {
                $model->ticket_number = 'TKT-' . date('YmdHis') . '-' . rand(1000, 9999);
            }
        });
    }

    /**
     * Get the guest who filed the complaint
     */
    public function guest(): BelongsTo
    {
        return $this->belongsTo(User::class, 'guest_id');
    }

    /**
     * Get the manager handling the complaint
     */
    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    /**
     * Get the staff member assigned to resolve the complaint
     */
    public function assignedStaff(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Assign complaint to staff
     */
    public function assignTo($userId): void
    {
        $this->update([
            'assigned_to' => $userId,
            'assigned_at' => now(),
            'status' => 'assigned',
        ]);
    }

    /**
     * Escalate complaint
     */
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

    /**
     * Resolve complaint
     */
    public function resolve($notes): void
    {
        $this->update([
            'status' => 'resolved',
            'resolution_notes' => $notes,
            'resolved_at' => now(),
        ]);
    }

    /**
     * Scope: Get open complaints
     */
    public function scopeOpen($query)
    {
        return $query->whereIn('status', ['open', 'assigned', 'in_progress']);
    }

    /**
     * Scope: Get urgent complaints
     */
    public function scopeUrgent($query)
    {
        return $query->whereIn('severity', ['high', 'critical']);
    }

    /**
     * Scope: Get complaints by type
     */
    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope: Get complaints by department
     */
    public function scopeByDepartment($query, $department)
    {
        return $query->where('department', $department);
    }

    /**
     * Scope: Get unresolved complaints
     */
    public function scopeUnresolved($query)
    {
        return $query->whereNotIn('status', ['resolved', 'closed']);
    }
}
