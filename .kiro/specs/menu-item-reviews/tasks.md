# Implementation Plan: Menu Item Review System

## Overview

This implementation plan breaks down the menu item review and rating system into discrete backend-focused tasks. The system enables verified-purchase reviews with moderation workflow, rating aggregation, helpfulness voting, and notification system. The implementation follows a layered approach: database → models → services → controllers → validation → testing.

## Tasks

- [~] 1. Set up database migrations and schema
  - [x] 1.1 Create menu_item_reviews table migration
    - Create migration file with UUID primary ke y
    - Add columns: guest_id, order_id, menu_item_id, rating, review_text, status, approved_by, approved_at, rejected_by, rejected_at, helpful_count, not_helpful_count, timestamps, soft deletes
    - Define foreign keys with appropriate cascade/restrict rules
    - Add unique constraint on (guest_id, order_id, menu_item_id)
    - Add indexes for performance: idx_menu_item_status, idx_status_created, idx_guest_pending
    - Add check constraint for rating (1-5)
    - _Requirements: 1.1, 1.2, 1.4, 7.1, 7.2, 7.7_

  - [x] 1.2 Create review_responses table migration
    - Create migration with UUID primary key
    - Add columns: review_id, responder_id, response_text (max 500 chars), timestamps
    - Define foreign keys to menu_item_reviews and users
    - Add unique constraint on review_id (one response per review)
    - _Requirements: 9.1, 9.2, 9.4_

  - [x] 1.3 Create review_helpfulness_votes table migration
    - Create migration with UUID primary key
    - Add columns: review_id, guest_id (nullable), ip_address (nullable), vote_type (enum: helpful/not_helpful), created_at
    - Define foreign keys with ON DELETE SET NULL for guest_id
    - Add unique constraints for (review_id, guest_id) and (review_id, ip_address)
    - Add index on (review_id, vote_type)
    - _Requirements: 10.1, 10.2, 10.3, 10.8, 10.9_

  - [x] 1.4 Create review_notifications table migration
    - Create migration with UUID primary key
    - Add columns: user_id, review_id, notification_type (enum), message, is_read, created_at, read_at
    - Define foreign keys to users and menu_item_reviews with cascade delete
    - Add index on (user_id, is_read, created_at)
    - _Requirements: 8.1, 8.2, 8.5, 8.6_

  - [-] 1.5 Run migrations and verify schema
    - Execute all migrations
    - Verify tables created with correct structure
    - Test foreign key constraints
    - _Requirements: 7.1, 7.2, 7.3, 7.4_

- [x] 2. Create Eloquent models and relationships
  - [x] 2.1 Create MenuItemReview model
    - Set up model with HasUuids, HasFactory, SoftDeletes traits
    - Define fillable attributes
    - Add status constants (PENDING, APPROVED, REJECTED)
    - Define relationships: guest(), order(), menuItem(), approver(), rejecter(), response(), votes(), notifications()
    - Add query scopes: pending(), approved(), rejected(), forMenuItem(), byGuest(), recentFirst(), byHelpfulness()
    - Add status helper methods: isPending(), isApproved(), isRejected(), canBeModifiedByGuest()
    - Add accessors: getAnonymizedGuestNameAttribute(), getHelpfulnessRatioAttribute(), getPublicDisplayDataAttribute()
    - _Requirements: 1.1, 1.3, 2.1, 2.2, 2.3, 3.6, 3.7, 4.1, 4.3, 7.5_

  - [x] 2.2 Create ReviewResponse model
    - Set up model with HasUuids, HasFactory traits
    - Define fillable attributes
    - Define relationships: review(), responder()
    - _Requirements: 9.1, 9.2, 9.7_

  - [x] 2.3 Create ReviewHelpfulnessVote model
    - Set up model with HasUuids, HasFactory traits
    - Disable automatic timestamps (use created_at only)
    - Add vote type constants (VOTE_HELPFUL, VOTE_NOT_HELPFUL)
    - Define relationships: review(), guest()
    - _Requirements: 10.1, 10.2, 10.3_

  - [x] 2.4 Create ReviewNotification model
    - Set up model with HasUuids, HasFactory traits
    - Disable automatic updated_at
    - Add notification type constants
    - Define relationships: user(), review()
    - Add query scopes: unread(), forUser()
    - Add helper method: markAsRead()
    - _Requirements: 8.1, 8.2, 8.3, 8.7_

  - [x] 2.5 Extend MenuItem model with review relationships
    - Add reviews() relationship
    - Add approvedReviews() relationship
    - Add computed attributes: getAverageRatingAttribute(), getReviewCountAttribute(), getRatingDistributionAttribute()
    - _Requirements: 3.1, 3.2, 3.3, 3.4, 6.1, 6.2_

  - [x] 2.6 Extend Guest model with review functionality
    - Add reviews() relationship
    - Add method: getEligibleMenuItemsForReview()
    - Add method: canReviewMenuItem($menuItemId, $orderId)
    - _Requirements: 1.1, 1.2, 5.1, 5.2, 5.3, 5.4, 5.5_

  - [x] 2.7 Extend Order model with review relationships
    - Add reviews() relationship
    - Add method: isCompleted()
    - Add computed attribute: getReviewableItemsAttribute()
    - _Requirements: 1.1, 1.8, 5.4_

- [ ] 3. Implement service layer (business logic)
  - [x] 3.1 Create PurchaseVerificationService
    - Implement verifyPurchase($guestId, $menuItemId, $orderId): verify order is completed and contains menu item
    - Implement checkDuplicateReview($guestId, $menuItemId, $orderId): check for existing review
    - Throw PurchaseNotVerifiedException with appropriate messages
    - Throw DuplicateReviewException when duplicate found
    - _Requirements: 1.1, 1.2, 1.6, 1.7, 1.8_

  - [x] 3.2 Create RatingCalculationService
    - Implement calculateRatingStats($menuItemId): return average_rating, review_count, rating_distribution
    - Calculate average as arithmetic mean of approved reviews, rounded to 1 decimal
    - Implement recalculateForMenuItem($menuItemId): trigger recalculation and cache update
    - Implement caching strategy with Laravel Cache
    - _Requirements: 3.1, 3.2, 3.4, 6.1, 6.2_

  - [x] 3.3 Create NotificationService
    - Implement notifyModeratorsOfNewReview($review): create notifications for all manager/admin users
    - Implement notifyGuestOfModeration($review, $decision): notify guest of approval/rejection
    - Format notification messages according to requirements
    - _Requirements: 8.1, 8.2, 8.3, 8.4_

  - [x] 3.4 Create ReviewService
    - Implement createReview($data): validate, verify purchase, create review, trigger notifications
    - Implement updateReview($reviewId, $guestId, $data): validate ownership and status, update review
    - Implement deleteReview($reviewId, $guestId): validate ownership and status, delete review
    - Implement getEligibleItems($guestId): return reviewable menu items for guest
    - Throw ReviewNotModifiableException for approved/rejected reviews
    - Throw UnauthorizedReviewAccessException for ownership violations
    - _Requirements: 1.1, 1.2, 1.3, 1.4, 1.5, 1.9, 4.1, 4.2, 4.3, 4.4, 4.5, 5.1, 5.2, 5.3, 5.4_

  - [x] 3.5 Create ModerationService
    - Implement approveReview($reviewId, $moderatorId): update status, set approval fields, trigger rating recalculation, notify guest
    - Implement rejectReview($reviewId, $moderatorId): update status, set rejection fields, trigger rating recalculation, notify guest
    - Implement deleteReview($reviewId): permanently delete review, trigger rating recalculation
    - Implement getReviewsByStatus($status, $pagination): return filtered and paginated reviews
    - _Requirements: 2.1, 2.2, 2.3, 2.4, 2.5, 2.6, 2.7, 2.8_

  - [x] 3.6 Create ResponseService
    - Implement createResponse($reviewId, $responderId, $text): create management response, validate review is approved
    - Implement updateResponse($responseId, $text): update existing response
    - Implement deleteResponse($responseId): remove response
    - Enforce one response per review constraint
    - _Requirements: 9.1, 9.2, 9.4, 9.5, 9.6, 9.7_

  - [x] 3.7 Create VotingService
    - Implement voteHelpful($reviewId, $guestId, $ipAddress): record helpful vote, increment count
    - Implement voteNotHelpful($reviewId, $guestId, $ipAddress): record not helpful vote, increment count
    - Check for duplicate votes by guest_id or ip_address
    - Throw DuplicateVoteException on duplicate attempts
    - _Requirements: 10.1, 10.2, 10.3, 10.4, 10.5, 10.7, 10.8, 10.9_

  - [x] 3.8 Create AnalyticsService
    - Implement getMenuItemStatistics($menuItemId): return total reviews, average, distribution, percentages
    - Implement getTopRatedItems($minReviews, $limit): return items sorted by rating descending
    - Implement getLowestRatedItems($minReviews, $limit): return items sorted by rating ascending
    - Implement getPendingReviewCount(): return count of pending reviews
    - Implement getReviewTrends($period): return submission rates (daily/weekly/monthly)
    - _Requirements: 6.1, 6.2, 6.3, 6.4, 6.5, 6.6_

- [x] 4. Create custom exception classes
  - [x] 4.1 Create review-specific exceptions
    - Create PurchaseNotVerifiedException
    - Create DuplicateReviewException
    - Create ReviewNotModifiableException
    - Create UnauthorizedReviewAccessException
    - Create DuplicateVoteException
    - All exceptions should extend base Exception with clear messages
    - _Requirements: 1.6, 1.7, 1.8, 4.3, 4.5, 10.4_

- [x] 5. Create API request validation classes
  - [x] 5.1 Create CreateReviewRequest
    - Validate required: guest_id, order_id, menu_item_id, rating
    - Validate rating: integer, between 1 and 5
    - Validate review_text: nullable, string, max 1000 characters
    - Strip HTML tags and scripts from review_text
    - _Requirements: 1.4, 1.5, 4.6, 4.7, 7.7, 7.8_

  - [x] 5.2 Create UpdateReviewRequest
    - Validate rating: integer, between 1 and 5
    - Validate review_text: nullable, string, max 1000 characters
    - Strip HTML tags and scripts from review_text
    - _Requirements: 4.1, 4.6, 4.7, 7.8_

  - [x] 5.3 Create CreateResponseRequest
    - Validate response_text: required, string, max 500 characters
    - Strip HTML tags
    - _Requirements: 9.1_

  - [x] 5.4 Create VoteRequest
    - Validate vote_type: required, in:helpful,not_helpful
    - Capture ip_address from request
    - _Requirements: 10.1, 10.2_

- [x] 6. Implement API controllers
  - [x] 6.1 Create ReviewController
    - POST /api/reviews - store(): create review using ReviewService
    - GET /api/reviews/{id} - show(): return review details
    - PUT /api/reviews/{id} - update(): update review (guest owner only, pending only)
    - DELETE /api/reviews/{id} - destroy(): delete review (guest owner only, pending only)
    - GET /api/guests/{guestId}/eligible-items - eligibleItems(): return reviewable items
    - Handle service exceptions and return appropriate HTTP responses
    - _Requirements: 1.1-1.9, 4.1-4.7, 5.1-5.5_

  - [x] 6.2 Create ModerationController
    - GET /api/admin/reviews - index(): list reviews filtered by status with pagination
    - POST /api/admin/reviews/{id}/approve - approve(): approve review
    - POST /api/admin/reviews/{id}/reject - reject(): reject review
    - DELETE /api/admin/reviews/{id} - destroy(): permanently delete review
    - Add middleware: auth:sanctum, role:manager,admin
    - _Requirements: 2.1-2.8_

  - [x] 6.3 Create PublicReviewController
    - GET /api/menu-items/{menuItemId}/reviews - index(): return approved reviews with pagination (10 per page), sorted by newest first
    - Support sorting by helpfulness (query param: sort=helpful)
    - Return public display data (anonymized guest names)
    - No authentication required
    - _Requirements: 3.1, 3.2, 3.3, 3.5, 3.6, 3.7, 3.8, 3.9_

  - [x] 6.4 Create ResponseController
    - POST /api/admin/reviews/{reviewId}/response - store(): create management response
    - PUT /api/admin/responses/{id} - update(): update response
    - DELETE /api/admin/responses/{id} - destroy(): delete response
    - Add middleware: auth:sanctum, role:manager,admin
    - _Requirements: 9.1-9.7_

  - [x] 6.5 Create VotingController
    - POST /api/reviews/{reviewId}/vote - vote(): record helpfulness vote
    - Support anonymous voting with IP tracking
    - Return updated vote counts
    - _Requirements: 10.1-10.9_

  - [x] 6.6 Create NotificationController
    - GET /api/notifications/reviews - index(): return review notifications for authenticated user
    - GET /api/notifications/reviews/unread-count - unreadCount(): return count of unread notifications
    - POST /api/notifications/{id}/read - markAsRead(): mark notification as read
    - Add middleware: auth:sanctum
    - _Requirements: 8.5, 8.6, 8.7_

  - [x] 6.7 Create AnalyticsController
    - GET /api/admin/analytics/menu-items/{id}/stats - itemStats(): return detailed statistics
    - GET /api/admin/analytics/top-rated - topRated(): return top-rated items
    - GET /api/admin/analytics/lowest-rated - lowestRated(): return lowest-rated items
    - GET /api/admin/analytics/pending-count - pendingCount(): return pending review count
    - GET /api/admin/analytics/review-trends - trends(): return trend data
    - Add middleware: auth:sanctum, role:manager,admin
    - _Requirements: 6.1-6.7_

- [x] 7. Register API routes
  - [x] 7.1 Add routes in routes/api.php
    - Register guest review routes (auth:sanctum for guests)
    - Register public review routes (no auth)
    - Register moderation routes (auth + role middleware)
    - Register response routes (auth + role middleware)
    - Register voting routes (no auth)
    - Register notification routes (auth)
    - Register analytics routes (auth + role middleware)
    - Group routes logically with prefixes
    - _Requirements: All endpoints from Requirements 1-10_

- [x] 8. Checkpoint - Core functionality complete
  - Ensure all migrations run successfully
  - Ensure all models load without errors
  - Ensure all services can be instantiated
  - Ensure all routes are registered correctly
  - Test basic API calls with Postman or similar tool
  - Ask the user if questions arise

- [x] 9. Implement data integrity features
  - [x] 9.1 Add Guest model observer for anonymization
    - Create GuestObserver
    - On deleting() event: anonymize approved reviews (set guest name to 'Anonymous Guest')
    - Register observer in EventServiceProvider
    - _Requirements: 7.5_

  - [x] 9.2 Add Order model observer for deletion protection
    - Create OrderObserver
    - On deleting() event: check if approved reviews exist, prevent deletion if true
    - Throw exception with appropriate message
    - Register observer in EventServiceProvider
    - _Requirements: 7.3_

  - [x] 9.3 Add review status change observers
    - Create MenuItemReviewObserver
    - On updated() event: if status changed to/from approved, trigger rating recalculation
    - Register observer in EventServiceProvider
    - _Requirements: 2.7_

- [~] 10. Create factory classes for testing
  - [ ] 10.1 Create MenuItemReviewFactory
    - Define default state with faker data
    - Add state methods: pending(), approved(), rejected()
    - _Testing support_

  - [ ] 10.2 Create ReviewResponseFactory
    - Define default state
    - _Testing support_

  - [ ] 10.3 Create ReviewHelpfulnessVoteFactory
    - Define default state
    - Add state methods: helpful(), notHelpful()
    - _Testing support_

  - [ ] 10.4 Create ReviewNotificationFactory
    - Define default state
    - Add state methods for each notification type
    - _Testing support_

- [ ] 11. Write unit tests for services
  - [ ] 11.1 Create ReviewServiceTest
    - Test successful review creation with valid purchase
    - Test rejection when guest has not ordered item
    - Test rejection when order is not completed
    - Test rejection for duplicate review
    - Test rating validation (1-5)
    - Test prevention of modification for approved reviews
    - Test successful update for pending reviews
    - Test successful deletion for pending reviews
    - Test unauthorized access prevention
    - _Requirements: 1.1, 1.2, 1.4, 1.6, 1.7, 1.8, 4.1, 4.2, 4.3, 4.4, 4.5, 4.6, 4.7_

  - [ ] 11.2 Create RatingCalculationServiceTest
    - Test average rating calculation with multiple approved reviews
    - Test exclusion of pending reviews from calculation
    - Test null return when no approved reviews exist
    - Test rounding to one decimal place
    - Test rating distribution calculation
    - _Requirements: 3.1, 3.2, 3.4, 6.1, 6.2_

  - [ ] 11.3 Create PurchaseVerificationServiceTest
    - Test verification success for completed order with menu item
    - Test verification failure for non-existent order
    - Test verification failure for incomplete order
    - Test verification failure for order without menu item
    - Test duplicate detection
    - _Requirements: 1.1, 1.2, 1.6, 1.7, 1.8_

  - [ ] 11.4 Create ModerationServiceTest
    - Test approval updates status and records moderator
    - Test rejection updates status and records moderator
    - Test rating recalculation triggered on status change
    - Test notification creation on moderation actions
    - _Requirements: 2.1, 2.2, 2.3, 2.4, 2.7_

  - [ ] 11.5 Create VotingServiceTest
    - Test helpful vote increments count
    - Test not_helpful vote increments count
    - Test duplicate vote prevention by guest_id
    - Test duplicate vote prevention by ip_address
    - Test helpfulness ratio calculation
    - _Requirements: 10.1, 10.2, 10.3, 10.4, 10.5_

  - [ ] 11.6 Create ResponseServiceTest
    - Test response creation for approved review
    - Test response update replaces existing text
    - Test response deletion preserves review
    - Test one response per review constraint
    - _Requirements: 9.1, 9.4, 9.5, 9.6_

  - [ ] 11.7 Create NotificationServiceTest
    - Test moderator notifications created on new review
    - Test guest notification on approval
    - Test guest notification on rejection
    - Test notification includes correct data
    - _Requirements: 8.1, 8.2, 8.3, 8.4_

  - [ ] 11.8 Create AnalyticsServiceTest
    - Test statistics calculation with rating distribution
    - Test top-rated items retrieval with minimum review threshold
    - Test lowest-rated items retrieval
    - Test pending count calculation
    - _Requirements: 6.1, 6.2, 6.3, 6.4, 6.5_

- [ ] 12. Write integration tests for API endpoints
  - [ ] 12.1 Create ReviewApiTest
    - Test POST /api/reviews with valid data returns 201
    - Test POST /api/reviews without purchase returns 422
    - Test PUT /api/reviews/{id} for pending review returns 200
    - Test PUT /api/reviews/{id} for approved review returns 422
    - Test DELETE /api/reviews/{id} for own pending review returns 204
    - Test DELETE /api/reviews/{id} for approved review returns 422
    - Test GET /api/guests/{guestId}/eligible-items returns correct items
    - _Requirements: 1.1-1.9, 4.1-4.7, 5.1-5.5_

  - [ ] 12.2 Create ModerationApiTest
    - Test GET /api/admin/reviews with status filter
    - Test POST /api/admin/reviews/{id}/approve updates status
    - Test POST /api/admin/reviews/{id}/reject updates status
    - Test DELETE /api/admin/reviews/{id} removes review
    - Test moderation endpoints require manager/admin role
    - _Requirements: 2.1-2.8_

  - [ ] 12.3 Create PublicReviewApiTest
    - Test GET /api/menu-items/{id}/reviews returns only approved reviews
    - Test pagination returns 10 reviews per page
    - Test sorting by newest first (default)
    - Test sorting by helpfulness
    - Test guest names are anonymized
    - Test returns null rating when no reviews exist
    - _Requirements: 3.1-3.9_

  - [ ] 12.4 Create ResponseApiTest
    - Test POST /api/admin/reviews/{id}/response creates response
    - Test PUT /api/admin/responses/{id} updates response
    - Test DELETE /api/admin/responses/{id} removes response
    - Test response endpoints require manager/admin role
    - _Requirements: 9.1-9.7_

  - [ ] 12.5 Create VotingApiTest
    - Test POST /api/reviews/{id}/vote with helpful type
    - Test POST /api/reviews/{id}/vote with not_helpful type
    - Test duplicate vote returns 422
    - Test anonymous voting works with IP tracking
    - _Requirements: 10.1-10.9_

  - [ ] 12.6 Create NotificationApiTest
    - Test GET /api/notifications/reviews returns user's notifications
    - Test GET /api/notifications/reviews/unread-count returns correct count
    - Test POST /api/notifications/{id}/read marks as read
    - _Requirements: 8.1-8.7_

  - [ ] 12.7 Create AnalyticsApiTest
    - Test GET /api/admin/analytics/menu-items/{id}/stats returns full statistics
    - Test GET /api/admin/analytics/top-rated filters by minimum reviews
    - Test GET /api/admin/analytics/lowest-rated filters by minimum reviews
    - Test analytics endpoints require manager/admin role
    - _Requirements: 6.1-6.7_

- [ ] 13. Write database constraint tests
  - [ ] 13.1 Create ReviewConstraintTest
    - Test unique constraint on (guest_id, order_id, menu_item_id) prevents duplicates
    - Test foreign key constraint prevents invalid guest_id
    - Test foreign key constraint prevents invalid order_id
    - Test cascade delete when menu_item deleted
    - Test restrict delete when order has approved reviews
    - Test check constraint enforces rating 1-5
    - _Requirements: 7.1, 7.2, 7.3, 7.4, 7.7_

  - [ ] 13.2 Create ResponseConstraintTest
    - Test unique constraint on review_id prevents multiple responses
    - Test cascade delete when review deleted
    - _Requirements: 9.4_

  - [ ] 13.3 Create VoteConstraintTest
    - Test unique constraint on (review_id, guest_id)
    - Test unique constraint on (review_id, ip_address)
    - _Requirements: 10.3, 10.9_

- [ ] 14. Write complete workflow integration tests
  - [ ] 14.1 Create CompleteReviewWorkflowTest
    - Test complete lifecycle: guest submits → moderator approves → guest votes → management responds
    - Verify rating calculation at each stage
    - Verify notifications created at appropriate points
    - Verify all data persisted correctly
    - _Requirements: Integration of 1.1-10.9_

  - [ ] 14.2 Create GuestDeletionWorkflowTest
    - Create approved review
    - Delete guest
    - Verify review still exists with "Anonymous Guest"
    - Verify guest_id set to null or maintained with anonymization
    - _Requirements: 7.5_

  - [ ] 14.3 Create OrderDeletionWorkflowTest
    - Create approved review
    - Attempt to delete order
    - Verify deletion prevented
    - Verify error message returned
    - _Requirements: 7.3_

- [~] 15. Final checkpoint and handoff
  - Run all tests and verify they pass
  - Test all API endpoints manually with Postman
  - Verify database constraints are enforced
  - Review code for security vulnerabilities
  - Ensure error messages match requirements exactly
  - Ask the user if questions arise

## Notes

- Tasks marked with `*` are optional testing tasks that can be skipped for faster MVP
- All backend implementation tasks (1-9) must be completed for core functionality
- Testing tasks (10-14) are highly recommended but can be prioritized based on timeline
- Frontend implementation is not included in this task list and will be a separate phase
- Each task references specific requirements for traceability
- Checkpoints (tasks 8 and 15) ensure incremental validation
- The dependency graph ensures proper task sequencing for parallel execution where possible

## Task Dependency Graph

```json
{
  "waves": [
    { "id": 0, "tasks": ["1.1", "1.2", "1.3", "1.4"] },
    { "id": 1, "tasks": ["1.5"] },
    { "id": 2, "tasks": ["2.1", "2.2", "2.3", "2.4"] },
    { "id": 3, "tasks": ["2.5", "2.6", "2.7"] },
    { "id": 4, "tasks": ["3.1", "4.1"] },
    { "id": 5, "tasks": ["3.2", "3.3", "3.4"] },
    { "id": 6, "tasks": ["3.5", "3.6", "3.7", "3.8"] },
    { "id": 7, "tasks": ["5.1", "5.2", "5.3", "5.4"] },
    { "id": 8, "tasks": ["6.1", "6.2", "6.3", "6.4", "6.5", "6.6", "6.7"] },
    { "id": 9, "tasks": ["7.1"] },
    { "id": 10, "tasks": ["9.1", "9.2", "9.3"] },
    { "id": 11, "tasks": ["10.1", "10.2", "10.3", "10.4"] },
    { "id": 12, "tasks": ["11.1", "11.2", "11.3", "11.4", "11.5", "11.6", "11.7", "11.8"] },
    { "id": 13, "tasks": ["12.1", "12.2", "12.3", "12.4", "12.5", "12.6", "12.7"] },
    { "id": 14, "tasks": ["13.1", "13.2", "13.3"] },
    { "id": 15, "tasks": ["14.1", "14.2", "14.3"] }
  ]
}
```
