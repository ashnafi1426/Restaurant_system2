<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ManagerDashboardSetting extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'manager_dashboard_settings';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'manager_id',
        'show_revenue',
        'show_rooms',
        'show_orders',
        'show_housekeeping',
        'show_laundry',
        'show_notifications',
        'theme',
    ];

    protected $casts = [
        'show_revenue' => 'boolean',
        'show_rooms' => 'boolean',
        'show_orders' => 'boolean',
        'show_housekeeping' => 'boolean',
        'show_laundry' => 'boolean',
        'show_notifications' => 'boolean',
    ];

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }
}