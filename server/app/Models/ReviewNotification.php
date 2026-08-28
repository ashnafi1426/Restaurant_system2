<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class ReviewNotification extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'review_notifications';
    public $incrementing = false;
    protected $keyType = 'string';
    
    // Disable automatic updated_at
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'review_id',
        'notification_type',
        'message',
        'is_read',
        'read_at',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'created_at' => 'datetime',
        'read_at' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | Notification Type Constants
    |--------------------------------------------------------------------------
    */

    public const TYPE_NEW_REVIEW = 'new_review';
    public const TYPE_REVIEW_APPROVED = 'review_approved';
    public const TYPE_REVIEW_REJECTED = 'review_rejected';

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function review()
    {
        return $this->belongsTo(MenuItemReview::class, 'review_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Query Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    public function scopeForUser($query, string $userId)
    {
        return $query->where('user_id', $userId);
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */

    public function markAsRead(): void
    {
        $this->update([
            'is_read' => true,
            'read_at' => now(),
        ]);
    }
}
