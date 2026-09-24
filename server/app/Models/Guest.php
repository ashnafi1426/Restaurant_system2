<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\BelongsToTenant;

class Guest extends Model
{
    use HasFactory, HasUuids, SoftDeletes, BelongsToTenant;
    protected $table = 'guests';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'hotel_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'address',
        'nationality',
        'passport_number',
        'date_of_birth',
        'preferences',
    ];

    protected $casts = [
        'preferences' => 'array',
        'date_of_birth' => 'date:Y-m-d',
    ];

    public function getFullNameAttribute(): string
    {
        return trim($this->first_name . ' ' . $this->last_name);
    }
    public function reservations()
    {
        return $this->hasMany(Reservation::class);
    }

    public function checkIns()
    {
        return $this->hasMany(CheckIn::class);
    }

    public function reviews()
    {
        return $this->hasMany(MenuItemReview::class, 'guest_id');
    }

    public function getTotalReservationsAttribute(): int
    {
        return $this->reservations()->count();
    }

    public function getActiveReservationsAttribute()
    {
        return $this->reservations()
            ->whereIn('status', ['pending', 'confirmed', 'checked_in'])
            ->get();
    }

    public function getCompletedReservationsAttribute()
    {
        return $this->reservations()
            ->where('status', 'checked_out')
            ->get();
    }

    public function getCancelledReservationsAttribute()
    {
        return $this->reservations()
            ->where('status', 'cancelled')
            ->get();
    }

    public function cancel(Reservation $reservation)
    {
        if(!$reservation->canCancel()){
            return response()->json([
                'message'=>'Reservation cannot be cancelled.'
            ],422);
        }

        $reservation->update([
            'status'=>'cancelled',
            'cancelled_at'=>now()
        ]);

        return response()->json([
            'message'=>'Reservation cancelled.'
        ]);
    }

    public function getEligibleMenuItemsForReview()
    {
        return MenuItem::whereHas('orderItems.order', function ($query) {
            $query->where('guest_id', $this->id)
                  ->whereIn('status', [Order::STATUS_SERVED, 'completed']);
        })
        ->whereDoesntHave('reviews', function ($query) {
            $query->where('guest_id', $this->id);
        })
        ->with(['orderItems.order' => function ($query) {
            $query->where('guest_id', $this->id)
                  ->whereIn('status', [Order::STATUS_SERVED, 'completed']);
        }])
        ->get();
    }

    public function canReviewMenuItem(string $menuItemId, string $orderId): bool
    {
        $order = Order::where('id', $orderId)
            ->where('guest_id', $this->id)
            ->whereIn('status', [Order::STATUS_SERVED, 'completed'])
            ->first();
        
        if (!$order) {
            return false;
        }
        
        $hasMenuItem = $order->orderItems()->where('menu_item_id', $menuItemId)->exists();
        
        if (!$hasMenuItem) {
            return false;
        }
        
        $reviewExists = MenuItemReview::where('guest_id', $this->id)
            ->where('order_id', $orderId)
            ->where('menu_item_id', $menuItemId)
            ->exists();
        
        return !$reviewExists;
    }
}