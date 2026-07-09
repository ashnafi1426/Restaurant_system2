<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Reservation extends Model
{
    use HasFactory, HasUuids;
    protected $keyType = 'string';

    /**
     * Indicates if the IDs are auto-incrementing.
     */
    public $incrementing = false;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'booking_reference',
        'guest_id',
        'room_id',
        'check_in_date',
        'check_out_date',
        'number_of_guests',
        'status',
        'special_requests',
        'cancelled_at',
        'created_by',
    ];

    /**
     * Attribute Casting.
     */
    protected $casts = [
        'check_in_date'  => 'date',
        'check_out_date' => 'date',
        'cancelled_at'   => 'datetime',
        'created_at'     => 'datetime',
        'updated_at'     => 'datetime',
    ];

    /**
     * Boot the model.
     */
    protected static function booted(): void
    {
        static::creating(function (Reservation $reservation) {

            // Generate Booking Reference
            if (empty($reservation->booking_reference)) {
                $reservation->booking_reference = self::generateBookingReference();
            }

            // Save Logged-in User
            if (auth()->check() && empty($reservation->created_by)) {
                $reservation->created_by = auth()->id();
            }
        });
    }

    /**
     * Generate Booking Reference.
     *
     * Example:
     * BK-20260702-0001
     */
    protected static function generateBookingReference(): string
    {
        $prefix = 'BK-' . now()->format('Ymd');

        $count = static::whereDate('created_at', today())->count() + 1;

        return sprintf('%s-%04d', $prefix, $count);
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /**
     * Guest Relationship
     */
    public function guest()
    {
        return $this->belongsTo(Guest::class);
    }

    /**
     * Room Relationship
     */
    public function room()
    {
        return $this->belongsTo(Room::class);
    }
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * CheckIn Relationship - One reservation has one check-in
     */
    public function checkIn()
    {
        return $this->hasOne(CheckIn::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Query Scopes
    |--------------------------------------------------------------------------
    */

    /**
     * Pending Reservations
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Confirmed Reservations
     */
    public function scopeConfirmed($query)
    {
        return $query->where('status', 'confirmed');
    }

    /**
     * Checked In Reservations
     */
    public function scopeCheckedIn($query)
    {
        return $query->where('status', 'checked_in');
    }

    /**
     * Checked Out Reservations
     */
    public function scopeCheckedOut($query)
    {
        return $query->where('status', 'checked_out');
    }

    /**
     * Cancelled Reservations
     */
    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    /**
     * Search Reservation
     */
    public function scopeSearch($query, ?string $search)
    {
        if (!$search) {
            return $query;
        }

        return $query->where(function ($query) use ($search) {

            $query->where('booking_reference', 'LIKE', "%{$search}%")
                  ->orWhereHas('guest', function ($guest) use ($search) {

                      $guest->where('first_name', 'LIKE', "%{$search}%")
                            ->orWhere('last_name', 'LIKE', "%{$search}%")
                            ->orWhere('email', 'LIKE', "%{$search}%");

                  });

        });
    }
    public function getStayDurationAttribute(): string
    {
        return "{$this->total_nights} Night(s)";
    }
    public function getGuestNameAttribute(): string
    {
        return $this->guest
            ? trim($this->guest->first_name . ' ' . $this->guest->last_name)
            : '';
    }

    /**
     * Is Reservation Active
     */
    public function getIsActiveAttribute(): bool
    {
        return in_array($this->status, [
            'pending',
            'confirmed',
            'checked_in',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Can Check In
     */
    public function canCheckIn(): bool
    {
        return $this->status === 'confirmed';
    }

    /**
     * Can Check Out
     */
    public function canCheckOut(): bool
    {
        return $this->status === 'checked_in';
    }

    /**
     * Can Cancel
     */
    public function canCancel(): bool
    {
        return in_array($this->status, [
            'pending',
            'confirmed',
        ]);
    }

    /**
     * Is Pending
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Is Confirmed
     */
    public function isConfirmed(): bool
    {
        return $this->status === 'confirmed';
    }

    /**
     * Is Checked In
     */
    public function isCheckedIn(): bool
    {
        return $this->status === 'checked_in';
    }

    /**
     * Is Checked Out
     */
    public function isCheckedOut(): bool
    {
        return $this->status === 'checked_out';
    }

    /**
     * Is Cancelled
     */
    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }
}