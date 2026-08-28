<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\ReviewNotification;
use App\Models\User;
use App\Models\MenuItemReview;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ReviewNotificationTest extends TestCase
{
    use RefreshDatabase;
    public function it_has_correct_notification_type_constants()
    {
        $this->assertEquals('new_review', ReviewNotification::TYPE_NEW_REVIEW);
        $this->assertEquals('review_approved', ReviewNotification::TYPE_REVIEW_APPROVED);
        $this->assertEquals('review_rejected', ReviewNotification::TYPE_REVIEW_REJECTED);
    }

    /** @test */
    public function it_uses_uuid_as_primary_key()
    {
        $notification = new ReviewNotification();
        $this->assertFalse($notification->incrementing);
        $this->assertEquals('string', $notification->getKeyType());
    }

    /** @test */
    public function it_has_timestamps_disabled()
    {
        $notification = new ReviewNotification();
        $this->assertFalse($notification->timestamps);
    }

    /** @test */
    public function it_casts_attributes_correctly()
    {
        $notification = new ReviewNotification();
        $casts = $notification->getCasts();
        
        $this->assertEquals('boolean', $casts['is_read']);
        $this->assertEquals('datetime', $casts['created_at']);
        $this->assertEquals('datetime', $casts['read_at']);
    }

    /** @test */
    public function it_has_fillable_attributes()
    {
        $notification = new ReviewNotification();
        $fillable = $notification->getFillable();
        
        $this->assertContains('user_id', $fillable);
        $this->assertContains('review_id', $fillable);
        $this->assertContains('notification_type', $fillable);
        $this->assertContains('message', $fillable);
        $this->assertContains('is_read', $fillable);
        $this->assertContains('read_at', $fillable);
    }

    /** @test */
    public function it_can_scope_unread_notifications()
    {
        // This test verifies the scope method exists
        $notification = new ReviewNotification();
        $query = $notification->newQuery()->unread();
        
        // Verify the query has the where clause for is_read = false
        $sql = $query->toSql();
        $this->assertStringContainsString('is_read', $sql);
    }

    /** @test */
    public function it_can_scope_notifications_for_user()
    {
        // This test verifies the scope method exists
        $notification = new ReviewNotification();
        $testUserId = 'test-user-id';
        $query = $notification->newQuery()->forUser($testUserId);
        
        // Verify the query has the where clause for user_id
        $sql = $query->toSql();
        $bindings = $query->getBindings();
        
        $this->assertStringContainsString('user_id', $sql);
        $this->assertContains($testUserId, $bindings);
    }

    /** @test */
    public function it_defines_user_relationship()
    {
        $notification = new ReviewNotification();
        $relation = $notification->user();
        
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $relation);
        $this->assertEquals('user_id', $relation->getForeignKeyName());
    }

    /** @test */
    public function it_defines_review_relationship()
    {
        $notification = new ReviewNotification();
        $relation = $notification->review();
        
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $relation);
        $this->assertEquals('review_id', $relation->getForeignKeyName());
    }
}
