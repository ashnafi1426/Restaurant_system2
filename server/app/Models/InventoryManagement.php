<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryManagement extends Model
{
    use HasFactory;

    protected $table = 'inventory_management';

    protected $fillable = [
        'item_name',
        'category',
        'department',
        'unit',
        'current_quantity',
        'minimum_quantity',
        'maximum_quantity',
        'unit_cost',
        'supplier',
        'sku',
        'status',
        'is_alert_enabled',
        'last_restocked_at',
        'last_alerted_at',
        'notes',
    ];

    protected $casts = [
        'current_quantity' => 'integer',
        'minimum_quantity' => 'integer',
        'maximum_quantity' => 'integer',
        'unit_cost' => 'decimal:2',
        'is_alert_enabled' => 'boolean',
        'last_restocked_at' => 'datetime',
        'last_alerted_at' => 'datetime',
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
            $model->updateStatus();
        });

        static::updating(function ($model) {
            $model->updateStatus();
        });
    }

    /**
     * Update inventory status based on quantity
     */
    public function updateStatus(): void
    {
        if ($this->current_quantity <= 0) {
            $this->status = 'out_of_stock';
        } elseif ($this->current_quantity <= $this->minimum_quantity) {
            $this->status = 'critical';
        } elseif ($this->current_quantity < ($this->minimum_quantity * 1.5)) {
            $this->status = 'low_stock';
        } else {
            $this->status = 'in_stock';
        }
    }

    /**
     * Check if inventory is low
     */
    public function isLowStock(): bool
    {
        return in_array($this->status, ['low_stock', 'critical', 'out_of_stock']);
    }

    /**
     * Get total value of inventory
     */
    public function getTotalValue(): float
    {
        return $this->current_quantity * $this->unit_cost;
    }

    /**
     * Record a restock
     */
    public function restock($quantity): void
    {
        $this->current_quantity += $quantity;
        $this->last_restocked_at = now();
        $this->updateStatus();
        $this->save();
    }

    /**
     * Record consumption
     */
    public function consume($quantity): void
    {
        $this->current_quantity = max(0, $this->current_quantity - $quantity);
        $this->updateStatus();
        $this->save();
    }

    /**
     * Scope: Get low stock items
     */
    public function scopeLowStock($query)
    {
        return $query->whereIn('status', ['low_stock', 'critical', 'out_of_stock']);
    }

    /**
     * Scope: Get out of stock items
     */
    public function scopeOutOfStock($query)
    {
        return $query->where('status', 'out_of_stock');
    }

    /**
     * Scope: Get items by category
     */
    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope: Get items by department
     */
    public function scopeByDepartment($query, $department)
    {
        return $query->where('department', $department);
    }

    /**
     * Scope: Get items requiring alert
     */
    public function scopeNeedsAlert($query)
    {
        return $query->where('is_alert_enabled', true)
            ->lowStock();
    }

    /**
     * Scope: Get items not alerted recently (avoid duplicate alerts)
     */
    public function scopeNotRecentlyAlerted($query, $minutes = 60)
    {
        return $query->where(function ($q) {
            $q->whereNull('last_alerted_at')
              ->orWhere('last_alerted_at', '<', now()->subMinutes($minutes));
        });
    }

    /**
     * Mark as alerted
     */
    public function markAsAlerted(): void
    {
        $this->update(['last_alerted_at' => now()]);
    }
}
