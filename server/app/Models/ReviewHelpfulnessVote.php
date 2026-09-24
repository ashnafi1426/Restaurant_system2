<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class ReviewHelpfulnessVote extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'review_helpfulness_votes';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'review_id',
        'guest_id',
        'ip_address',
        'vote_type',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public const VOTE_HELPFUL = 'helpful';
    public const VOTE_NOT_HELPFUL = 'not_helpful';

    public function review()
    {
        return $this->belongsTo(MenuItemReview::class, 'review_id');
    }

    public function guest()
    {
        return $this->belongsTo(Guest::class);
    }
}
