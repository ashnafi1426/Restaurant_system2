# Requirements Document

## Introduction

This document specifies the requirements for a verified-purchase menu item review and rating system for the Hotel Management System. The system ensures authenticity by allowing only guests who have completed orders containing specific menu items to review those items. It includes a moderation workflow, public rating display, and privacy protection for guest information.

## Glossary

- **Guest**: A hotel guest who places orders for menu items via the QR ordering system
- **Review_System**: The complete menu item review and rating system
- **Menu_Item_Review**: A single review record containing rating, review text, and verification data
- **Verified_Purchase**: An order that has been completed/delivered and contains the menu item being reviewed
- **Review_Status**: The moderation state of a review (pending, approved, rejected)
- **Rating**: A numeric score from 1 to 5 stars assigned to a menu item
- **Average_Rating**: The calculated mean of all approved ratings for a menu item
- **Review_Moderator**: An admin or manager who can approve, reject, or delete reviews
- **Order_Completion**: The state where an order has status 'served' or 'completed'
- **Duplicate_Review**: A review from the same guest for the same menu item within the same order

- **Public_Review**: An approved review displayed on public-facing menu pages

- **Review_Count**: The total number of approved reviews for a menu item
## Requirements
### Requirement 1: Review Submission with Purchase Verification

**User Story:** As a guest, I want to review menu items I have ordered and received, so that I can share my experience with other guests.

#### Acceptance Criteria

1. WHEN a Guest submits a review, THE Review_System SHALL verify that the Guest has a completed Order containing the Menu_Item
2. WHEN a Guest submits a review, THE Review_System SHALL verify that no Duplicate_Review exists for the same Guest, Order, and Menu_Item combination
3. WHEN verification passes, THE Review_System SHALL create a Menu_Item_Review with status 'pending'
4. WHEN a Guest submits a review, THE Review_System SHALL require a Rating between 1 and 5
5. WHEN a Guest submits a review, THE Review_System SHALL accept optional review text with maximum length of 1000 characters
6. IF a Guest attempts to review a Menu_Item they have not ordered, THEN THE Review_System SHALL return an error message stating 'You must order this item before reviewing it'
7. IF a Duplicate_Review exists, THEN THE Review_System SHALL return an error message stating 'You have already reviewed this item for this order'
8. IF the Order is not completed, THEN THE Review_System SHALL return an error message stating 'You can only review items from completed orders'
9. WHEN a review is successfully created, THE Review_System SHALL return the review ID and confirmation message

### Requirement 2: Review Moderation Workflow

**User Story:** As a Review_Moderator, I want to review and moderate guest reviews before they appear publicly, so that I can maintain quality and prevent inappropriate content.

#### Acceptance Criteria

1. WHEN a Menu_Item_Review is created, T HE Review_System SHALL set its status to 'pending'
2. WHEN a Review_Moderator approves a pending review, THE Review_System SHALL update the review status to 'approved' and record the approval timestamp
3. WHEN a Review_Moderator rejects a pending review, THE Review_System SHALL update the review status to 'rejected' and record the rejection timestamp
4. WHEN a Review_Moderator deletes a review, THE Review_System SHALL permanently remove the review record
5. THE Review_System SHALL provide a list of all reviews filtered by status (pending, approved, rejected)
6. THE Review_System SHALL display review details including Guest name, Menu_Item name, Rating, review text, and Order reference
7. WHEN a review status changes, THE Review_System SHALL recalculate the Average_Rating for the Menu_Item
8. THE Review_System SHALL restrict moderation operations to users with admin or manager roles

### Requirement 3: Public Rating Display and Aggregation

**User Story:** As a guest browsing the menu, I want to see ratings and reviews for menu items, so that I can make informed ordering decisions.

#### Acceptance Criteria

1. WHEN a guest views a menu item, THE Review_System SHALL display the Average_Rating calculated from all approved reviews
2. WHEN a guest views a menu item, THE Review_System SHALL display the Review_Count (total number of approved reviews)
3. WHEN no approved reviews exist, THE Review_System SHALL display 'No reviews yet' instead of a rating
4. THE Review_System SHALL calculate Average_Rating as the arithmetic mean of all approved ratings, rounded to one decimal place
5. WHEN displaying public reviews, THE Review_System SHALL show only approved reviews
6. WHEN displaying public reviews, THE Review_System SHALL show Guest first name and last initial only (e.g., 'John D.')
7. WHEN displaying public reviews, THE Review_System SHALL show the Rating, review text, and creation date
8. THE Review_System SHALL sort public reviews by creation date in descending order (newest first)
9. WHEN a guest requests reviews for a menu item, THE Review_System SHALL paginate results with 10 reviews per page

### Requirement 4: Review Update and Deletion by Guest

**User Story:** As a guest, I want to update or delete my review before it is moderated, so that I can correct mistakes or change my opinion.

#### Acceptance Criteria

1. WHEN a Guest updates their pending review, THE Review_System SHALL allow modification of Rating and review text
2. WHEN a Guest deletes their pending review, THE Review_System SHALL permanently remove the review
3. IF a review status is 'approved' or 'rejected', THEN THE Review_System SHALL prevent Guest modifications and return an error 'Cannot modify a moderated review'
4. THE Review_System SHALL verify that the Guest owns the review before allowing update or deletion
5. IF a Guest attempts to modify another Guest's review, THEN THE Review_System SHALL return an error 'Unauthorized access'
6. WHEN a Guest updates a review, THE Review_System SHALL validate the new Rating is between 1 and 5
7. WHEN a Guest updates a review, THE Review_System SHALL validate review text length does not exceed 1000 characters

### Requirement 5: Review Eligibility Checking

**User Story:** As a guest, I want to know which menu items I can review, so that I don't waste time attempting to review items I haven't ordered.

#### Acceptance Criteria

1. WHEN a Guest requests eligible reviews, THE Review_System SHALL return all Menu_Items from the Guest's completed orders that have not been reviewed
2. THE Review_System SHALL include the Order reference for each eligible Menu_Item
3. THE Review_System SHALL exclude Menu_Items that the Guest has already reviewed for each specific order
4. THE Review_System SHALL only include orders with status 'served' or 'completed'
5. WHEN a Guest has no eligible items, THE Review_System SHALL return an empty list with message 'No items available to review'

### Requirement 6: Rating Statistics and Analytics

**User Story:** As a manager, I want to view rating statistics and trends, so that I can identify popular items and areas for improvement.

#### Acceptance Criteria

1. THE Review_System SHALL provide statistics showing total approved reviews, average rating, and rating distribution (count per star level) for each Menu_Item
2. THE Review_System SHALL calculate the percentage distribution of ratings (1-star %, 2-star %, 3-star %, 4-star %, 5-star %)
3. THE Review_System SHALL provide a list of top-rated Menu_Items (minimum 5 reviews, sorted by Average_Rating descending)
4. THE Review_System SHALL provide a list of lowest-rated Menu_Items (minimum 5 reviews, sorted by Average_Rating ascending)
5. THE Review_System SHALL calculate the total number of pending reviews awaiting moderation
6. THE Review_System SHALL provide a trend report showing review submission rate over time (daily, weekly, monthly)
7. THE Review_System SHALL restrict access to detailed analytics to users with manager or admin roles

### Requirement 7: Data Integrity and Constraints

**User Story:** As a system administrator, I want the review system to maintain data integrity, so that the rating system remains reliable and trustworthy.

#### Acceptance Criteria

1. THE Review_System SHALL enforce a unique constraint on the combination of Guest ID, Order ID, and Menu_Item ID
2. THE Review_System SHALL enforce foreign key constraints to ensure Guest, Order, and Menu_Item records exist
3. WHEN an Order is deleted, THE Review_System SHALL prevent deletion if approved reviews exist and return an error
4. WHEN a Menu_Item is deleted, THE Review_System SHALL cascade delete all associated reviews
5. WHEN a Guest record is deleted, THE Review_System SHALL anonymize approved reviews by replacing Guest name with 'Anonymous Guest' instead of deleting them
6. THE Review_System SHALL store all timestamps in UTC format
7. THE Review_System SHALL validate that Rating values are integers between 1 and 5 inclusive
8. THE Review_System SHALL validate that review text does not contain HTML tags or script elements

### Requirement 8: Review Notification System

**User Story:** As a manager, I want to be notified when new reviews are submitted, so that I can moderate them promptly.

#### Acceptance Criteria

1. WHEN a Guest submits a new review, THE Review_System SHALL create a notification for all users with manager or admin roles
2. THE notification SHALL include the Menu_Item name, Guest name, Rating, and a link to the review moderation page
3. WHEN a Review_Moderator approves or rejects a review, THE Review_System SHALL create a notification for the Guest who submitted it
4. THE Guest notification SHALL include the moderation decision and Menu_Item name
5. THE Review_System SHALL mark notifications as unread by default
6. THE Review_System SHALL provide an endpoint to retrieve unread review notification count
7. WHEN a notification is viewed, THE Review_System SHALL mark it as read

### Requirement 9: Review Response by Management

**User Story:** As a manager, I want to respond to guest reviews, so that I can address concerns and show appreciation for feedback.

#### Acceptance Criteria

1. WHEN a Review_Moderator responds to an approved review, THE Review_System SHALL store the response text with maximum length of 500 characters
2. THE Review_System SHALL associate the response with the Review_Moderator's user ID and timestamp
3. WHEN displaying public reviews, THE Review_System SHALL show management responses below the original review
4. THE Review_System SHALL allow only one response per review
5. WHEN a Review_Moderator updates a response, THE Review_System SHALL replace the previous response and update the timestamp
6. WHEN a Review_Moderator deletes a response, THE Review_System SHALL remove the response while keeping the original review
7. THE Review_System SHALL display the responder's role (Manager/Admin) but not their full name

### Requirement 10: Review Helpfulness Voting

**User Story:** As a guest, I want to vote on whether reviews are helpful, so that the most useful reviews are highlighted for other guests.

#### Acceptance Criteria

1. WHEN a Guest votes a review as helpful, THE Review_System SHALL increment the helpful_count for that review
2. WHEN a Guest votes a review as not helpful, THE Review_System SHALL increment the not_helpful_count for that review
3. THE Review_System SHALL allow each Guest to vote once per review (one helpful OR one not helpful vote)
4. IF a Guest attempts to vote twice on the same review, THEN THE Review_System SHALL return an error 'You have already voted on this review'
5. THE Review_System SHALL calculate a helpfulness_ratio as helpful_count / (helpful_count + not_helpful_count)
6. WHEN sorting reviews, THE Review_System SHALL provide an option to sort by helpfulness_ratio in descending order
7. THE Review_System SHALL display vote counts publicly as 'X guests found this helpful'
8. THE Review_System SHALL allow anonymous voting (no authentication required for public menu browsing)
9. THE Review_System SHALL track votes by IP address for anonymous users to prevent duplicate voting

