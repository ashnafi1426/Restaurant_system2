<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class OrderItem extends Model
{
    use HasFactory, HasUuids;
    protected $table = 'order_items';
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'order_id',
        'menu_item_id',
        'item_name',
        'quantity',
        'item_price_at_order',
        'tax_rate_id',
        'tax_rate',
        'tax_amount',
        'subtotal',
        'total',
        'line_total',
        'notes',
    ];

    protected static function booted()
    {
        static::creating(function ($item) {
            if (empty($item->item_name) && !empty($item->menu_item_id)) {
                $menuItem = MenuItem::withoutGlobalScopes()->find($item->menu_item_id);
                if ($menuItem) {
                    $item->item_name = $menuItem->name;
                }
            }
        });
    }

    public function getItemNameAttribute($value): ?string
    {
        return $value ?: ($this->relationLoaded('menuItem') ? $this->menuItem?->name : $value);
    }

    protected $casts = [
        'quantity' => 'integer',
        'item_price_at_order' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'total' => 'decimal:2',
        'line_total' => 'decimal:2',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function menuItem(): BelongsTo
    {
        return $this->belongsTo(MenuItem::class);
    }

    public function taxRate(): BelongsTo
    {
        return $this->belongsTo(TaxRate::class, 'tax_rate_id');
    }
}

