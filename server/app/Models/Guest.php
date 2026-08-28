<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;

class Guest extends Model
{
    use HasFactory, HasUuids, SoftDeletes;
    protected $table = 'guests';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    /**
     * Mass assignable fields.
     */
    protected $fillable = [
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

    /**
     * Attribute casting.
     */
    protected $casts = [
        'preferences' => 'array',
        'date_of_birth' => 'date:Y-m-d',
    ];

    /**
     * Accessor for full name.
     */
    public function getFullNameAttribute(): string
    {
        return trim($this->first_name . ' ' . $this->last_name);
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /**
     * Guest has many reservations
     */
    public function reservations()
    {
        return $this->hasMany(Reservation::class);
    }

    /**
     * Guest has many check-ins
     */
    public function checkIns()
    {
        return $this->hasMany(CheckIn::class);
    }

    /**
     * Guest has many reviews
     */
    public function reviews()
    {
        return $this->hasMany(MenuItemReview::class, 'guest_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Get total number of reservations for guest
     */
    public function getTotalReservationsAttribute(): int
    {
        return $this->reservations()->count();
    }

    /**
     * Get active reservations (pending, confirmed, checked_in)
     */
    public function getActiveReservationsAttribute()
    {
        return $this->reservations()
            ->whereIn('status', ['pending', 'confirmed', 'checked_in'])
            ->get();
    }

    /**
     * Get completed reservations (checked_out)
     */
    public function getCompletedReservationsAttribute()
    {
        return $this->reservations()
            ->where('status', 'checked_out')
            ->get();
    }

    /**
     * Get cancelled reservations
     */
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

    /**
     * Get eligible menu items for review
     */
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

    /**
     * Check if guest can review a specific menu item from an order
     */
    public function canReviewMenuItem(string $menuItemId, string $orderId): bool
    {
        // Check if order exists and is completed
        $order = Order::where('id', $orderId)
            ->where('guest_id', $this->id)
            ->whereIn('status', [Order::STATUS_SERVED, 'completed'])
            ->first();
        
        if (!$order) {
            return false;
        }
        
        // Check if order contains the menu item
        $hasMenuItem = $order->orderItems()->where('menu_item_id', $menuItemId)->exists();
        
        if (!$hasMenuItem) {
            return false;
        }
        
        // Check if review already exists
        $reviewExists = MenuItemReview::where('guest_id', $this->id)
            ->where('order_id', $orderId)
            ->where('menu_item_id', $menuItemId)
            ->exists();
        
        return !$reviewExists;
    }
}