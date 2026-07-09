<?php

namespace App\Models;

use Laravel\Sanctum\HasApiTokens;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasApiTokens, HasUuids, Notifiable;

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'phone',
        'password_hash',
        'role',
        'is_active'
    ];
    protected $hidden = [
        'password_hash',
        'remember_token'
    ];
    protected function casts(): array
    {
        return [
            'password_hash' => 'hashed',
            'is_active' => 'boolean'
        ];
    }

    /**
     * Get the password for authentication.
     * Since our column is password_hash, we need to tell Laravel to use it.
     */
    public function getAuthPassword()
    {
        return $this->password_hash;
    }
    public function isAdmin()
    {
        return $this->role === 'admin';
    }
    public function isReceptionist()
    {
        return $this->role === 'receptionist';
    }
    public function isCashier()
    {
        return $this->role === 'cashier';
    }
    public function isManager()
    {
        return $this->role === 'manager';
    }
    public function isChef()
    {
        return $this->role === 'chef';
    }
}
