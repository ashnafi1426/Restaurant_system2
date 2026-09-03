<?php

namespace App\Services;

use App\Models\Hotel;
use App\Models\HotelUser;

class TenantContext
{
    protected ?Hotel $hotel = null;
    protected ?HotelUser $membership = null;

    /**
     * Set the current tenant hotel and membership.
     */
    public function setHotel(?Hotel $hotel, ?HotelUser $membership = null): void
    {
        $this->hotel = $hotel;
        $this->membership = $membership;
    }

    /**
     * Set the current tenant hotel by ID.
     */
    public function setHotelId(?string $hotelId): void
    {
        if ($hotelId) {
            $hotel = Hotel::find($hotelId);
            $this->setHotel($hotel);
        } else {
            $this->setHotel(null);
        }
    }

    /**
     * Get current active Hotel instance.
     */
    public function getHotel(): ?Hotel
    {
        return $this->hotel;
    }

    /**
     * Get current active Hotel UUID.
     */
    public function getHotelId(): ?string
    {
        return $this->hotel?->id;
    }

    /**
     * Quick static helper for getting current hotel ID.
     */
    public static function id(): ?string
    {
        return app(self::class)->getHotelId();
    }

    /**
     * Quick static helper for getting current hotel instance.
     */
    public static function hotel(): ?Hotel
    {
        return app(self::class)->getHotel();
    }

    /**
     * Get active membership in current hotel.
     */
    public function getMembership(): ?HotelUser
    {
        return $this->membership;
    }

    /**
     * Get current user's role in this hotel.
     */
    public function getCurrentRole(): ?string
    {
        return $this->membership?->role;
    }

    /**
     * Get current user's role model in this hotel.
     */
    public function getCurrentRoleModel(): ?\App\Models\Role
    {
        if ($this->membership?->role_id) {
            return $this->membership->roleModel;
        }

        if ($this->hotel && !empty($this->membership?->role)) {
            return \App\Models\Role::withoutTenant()
                ->where('hotel_id', $this->hotel->id)
                ->where('slug', strtolower($this->membership->role))
                ->first();
        }

        return null;
    }

    /**
     * Check if a tenant context is currently set.
     */
    public function hasTenant(): bool
    {
        return $this->hotel !== null;
    }

    /**
     * Clear the tenant context.
     */
    public function clear(): void
    {
        $this->hotel = null;
        $this->membership = null;
    }
}
