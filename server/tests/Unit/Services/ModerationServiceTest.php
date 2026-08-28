<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\ModerationService;
use App\Services\RatingCalculationService;
use App\Services\NotificationService;
use App\Models\MenuItemReview;
use App\Models\MenuItem;
use App\Models\Guest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ModerationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ModerationService $service;
    protected RatingCalculationService $ratingCalculationService;
    protected NotificationService $notificationService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ratingCalculationService = new RatingCalculationService();
        $this->notificationService = new NotificationService();
        $this->service = new ModerationService(
            $this->ratingCalculationService,
            $this->notificationService
        );
    }

    /** @test */
    public function it_approves_review_and_sets_moderator_fields()
    {
        $moderator = User::factory()->create(['role' => 'manager']);
        $review = MenuItemReview::factory()->pending()->create();

        $approvedReview = $this->service->approveReview($review->id, $moderator->id);

        $this->assertEquals(MenuItemReview::STATUS_APPROVED, $approvedReview->status);
        $this->assertEquals($moderator->id, $approvedReview->approved_by);
        $this->assertNotNull($approvedReview->approved_at);
        $this->assertDatabaseHas('menu_item_reviews', [
            'id' => $review->id,
            'status' => MenuItemReview::STATUS_APPROVED,
            'approved_by' => $moderator->id,
        ]);
    }

    /** @test */
    public function it_rejects_review_and_sets_moderator_fields()
    {
        $moderator = User::factory()->create(['role' => 'manager']);
        $review = MenuItemReview::factory()->pending()->create();

        $rejectedReview = $this->service->rejectReview($review->id, $moderator->id);

        $this->assertEquals(MenuItemReview::STATUS_REJECTED, $rejectedReview->status);
        $this->assertEquals($moderator->id, $rejectedReview->rejected_by);
        $this->assertNotNull($rejectedReview->rejected_at);
        $this->assertDatabaseHas('menu_item_reviews', [
            'id' => $review->id,
            'status' => MenuItemReview::STATUS_REJECTED,
            'rejected_by' => $moderator->id,
        ]);
    }

    /** @test */
    public function it_deletes_review_and_returns_true()
    {
        $review = MenuItemReview::factory()->pending()->create();

        $result = $this->service->deleteReview($review->id);

        $this->assertTrue($result);
        $this->assertSoftDeleted('menu_item_reviews', ['id' => $review->id]);
    }

    /** @test */
    public function it_filters_reviews_by_status()
    {
        MenuItemReview::factory()->pending()->count(3)->create();
        MenuItemReview::factory()->approved()->count(2)->create();
        MenuItemReview::factory()->rejected()->count(1)->create();

        $pendingReviews = $this->service->getReviewsByStatus('pending');
        $approvedReviews = $this->service->getReviewsByStatus('approved');
        $rejectedReviews = $this->service->getReviewsByStatus('rejected');

        $this->assertEquals(3, $pendingReviews->total());
        $this->assertEquals(2, $approvedReviews->total());
        $this->assertEquals(1, $rejectedReviews->total());
    }

    /** @test */
    public function it_returns_moderation_statistics()
    {
        MenuItemReview::factory()->pending()->count(4)->create();
        MenuItemReview::factory()->approved()->count(2)->create();
        MenuItemReview::factory()->rejected()->count(1)->create();

        $stats = $this->service->getModerationStats();

        $this->assertEquals(4, $stats['pending']);
        $this->assertEquals(2, $stats['approved']);
        $this->assertEquals(1, $stats['rejected']);
        $this->assertEquals(7, $stats['total']);
    }
}
