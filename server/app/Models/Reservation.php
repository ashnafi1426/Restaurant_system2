<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use App\Models\Traits\BelongsToTenant;

class Reservation extends Model
{
    use HasFactory, HasUuids, BelongsToTenant;
    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'hotel_id',
        'booking_reference',
        'guest_id',
        'room_id',
        'check_in_date',
        'check_out_date',
        'number_of_guests',
        'total_amount',
        'status',
        'special_requests',
        'cancelled_at',
        'created_by',
    ];

    protected $casts = [
        'check_in_date'  => 'date',
        'check_out_date' => 'date',
        'total_amount'   => 'decimal:2',
        'cancelled_at'   => 'datetime',
        'created_at'     => 'datetime',
        'updated_at'     => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Reservation $reservation) {
            if (empty($reservation->booking_reference)) {
                $reservation->booking_reference = self::generateBookingReference();
            }

            if (auth()->check() && empty($reservation->created_by)) {
                $reservation->created_by = auth()->id();
            }
        });
    }

    public static function generateBookingReference(): string
    {
        $prefix = 'BK-' . now()->format('Ymd');
        
        $counter = 1;
        $maxAttempts = 9999;
        
        do {
            $bookingReference = sprintf('%s-%04d', $prefix, $counter);
            
            $exists = static::where('booking_reference', $bookingReference)->exists();
            
            if (!$exists) {
                return $bookingReference;
            }
            
            $counter++;
        } while ($counter <= $maxAttempts);
        
        return $prefix . '-' . strtoupper(substr(uniqid(), -4));
    }

    public function guest()
    {
        return $this->belongsTo(Guest::class);
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function checkIn()
    {
        return $this->hasOne(CheckIn::class);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeConfirmed($query)
    {
        return $query->where('status', 'confirmed');
    }

    public function scopeCheckedIn($query)
    {
        return $query->where('status', 'checked_in');
    }

    public function scopeCheckedOut($query)
    {
        return $query->where('status', 'checked_out');
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

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

    public function getTotalNightsAttribute(): int
    {
        if ($this->check_in_date && $this->check_out_date) {
            return $this->check_out_date->diffInDays($this->check_in_date);
        }
        return 0;
    }

    public function getGuestNameAttribute(): string
    {
        return $this->guest
            ? trim($this->guest->first_name . ' ' . $this->guest->last_name)
            : '';
    }

    public function getIsActiveAttribute(): bool
    {
        return in_array($this->status, [
            'pending',
            'confirmed',
            'checked_in',
        ]);
    }

    public function canCheckIn(): bool
    {
        return $this->status === 'confirmed';
    }

    public function canCheckOut(): bool
    {
        return $this->status === 'checked_in';
    }

    public function canCancel(): bool
    {
        return in_array($this->status, [
            'pending',
            'confirmed',
        ]);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isConfirmed(): bool
    {
        return $this->status === 'confirmed';
    }

    public function isCheckedIn(): bool
    {
        return $this->status === 'checked_in';
    }

    public function isCheckedOut(): bool
    {
        return $this->status === 'checked_out';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }
}