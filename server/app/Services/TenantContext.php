<?php

namespace App\Services;

use App\Models\Hotel;
use App\Models\HotelUser;

class TenantContext
{
    protected ?Hotel $hotel = null;
    protected ?HotelUser $membership = null;

    public function setHotel(?Hotel $hotel, ?HotelUser $membership = null): void
    {
        $this->hotel = $hotel;
        $this->membership = $membership;
    }

    public function setHotelId(?string $hotelId): void
    {
        if ($hotelId) {
            $hotel = Hotel::find($hotelId);
            $this->setHotel($hotel);
        } else {
            $this->setHotel(null);
        }
    }

    public function getHotel(): ?Hotel
    {
        return $this->hotel;
    }

    public function getHotelId(): ?string
    {
        return $this->hotel?->id;
    }

    public static function id(): ?string
    {
        return app(self::class)->getHotelId();
    }

    public static function hotel(): ?Hotel
    {
        return app(self::class)->getHotel();
    }

    public function getMembership(): ?HotelUser
    {
        return $this->membership;
    }

    public function getCurrentRole(): ?string
    {
        return $this->membership?->role;
    }

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

    public function hasTenant(): bool
    {
        return $this->hotel !== null;
    }

    public function clear(): void
    {
        $this->hotel = null;
        $this->membership = null;
    }
}

