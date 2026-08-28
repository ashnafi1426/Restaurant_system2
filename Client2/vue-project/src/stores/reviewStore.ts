/**
 * Review Store (Pinia)
 * Manages review-related state and actions
 */

import { defineStore } from 'pinia'
import reviewService from '@/services/reviewService'
import type {
  Review,
  PublicReview,
  ReviewNotification,
  ReviewStats,
  TopRatedItem,
  ReviewTrend,
  EligibleMenuItem,
} from '@/types/review'

export interface ReviewState {
  // Guest Reviews
  guestReviews: Review[]
  selectedReview: Review | null
  guestReviewsLoading: boolean

  // Public Reviews
  publicReviews: PublicReview[]
  publicReviewsLoading: boolean
  currentPublicPage: number
  currentPublicSort: 'recent' | 'helpful'

  // Moderation
  pendingReviews: Review[]
  approvedReviews: Review[]
  rejectedReviews: Review[]
  moderationLoading: boolean
  pendingCount: number

  // Notifications
  notifications: ReviewNotification[]
  unreadNotificationCount: number
  notificationsLoading: boolean

  // Eligible Items
  eligibleItems: EligibleMenuItem[]
  eligibleItemsLoading: boolean

  // Analytics
  reviewStats: Record<string, ReviewStats>
  topRatedItems: TopRatedItem[]
  lowestRatedItems: TopRatedItem[]
  reviewTrends: ReviewTrend[]
  analyticsLoading: boolean
}

export const useReviewStore = defineStore('review', {
  state: (): ReviewState => ({
    // Guest Reviews
    guestReviews: [],
    selectedReview: null,
    guestReviewsLoading: false,

    // Public Reviews
    publicReviews: [],
    publicReviewsLoading: false,
    currentPublicPage: 1,
    currentPublicSort: 'recent',

    // Moderation
    pendingReviews: [],
    approvedReviews: [],
    rejectedReviews: [],
    moderationLoading: false,
    pendingCount: 0,

    // Notifications
    notifications: [],
    unreadNotificationCount: 0,
    notificationsLoading: false,

    // Eligible Items
    eligibleItems: [],
    eligibleItemsLoading: false,

    // Analytics
    reviewStats: {},
    topRatedItems: [],
    lowestRatedItems: [],
    reviewTrends: [],
    analyticsLoading: false,
  }),

  getters: {
    /**
     * Get average rating across all menu items
     */
    overallAverageRating(): number {
      if (Object.keys(this.reviewStats).length === 0) return 0
      const stats = Object.values(this.reviewStats)
      const sum = stats.reduce((acc, stat) => acc + (stat.average_rating || 0), 0)
      return sum / stats.length
    },

    /**
     * Get total review count across all items
     */
    totalReviewCount(): number {
      return Object.values(this.reviewStats).reduce((sum, stat) => sum + stat.total_reviews, 0)
    },

    /**
     * Get count of reviews by status
     */
    reviewCountByStatus(): Record<string, number> {
      return {
        pending: this.pendingReviews.length,
        approved: this.approvedReviews.length,
        rejected: this.rejectedReviews.length,
      }
    },

    /**
     * Get ratio of helpful votes
     */
    getHelpfulnessRatio: () => (reviewId: string) => {
      const review = [...this.guestReviews, ...this.publicReviews].find(r => r.id === reviewId)
      if (!review) return 0
      const total = review.helpful_count + review.not_helpful_count
      return total === 0 ? 0 : review.helpful_count / total
    },
  },

  actions: {
    /**
     * Guest Review Operations
     */

    async submitReview(guestId: string, orderId: string, menuItemId: string, rating: number, reviewText?: string) {
      this.guestReviewsLoading = true
      try {
        const review = await reviewService.createReview({
          guest_id: guestId,
          order_id: orderId,
          menu_item_id: menuItemId,
          rating,
          review_text: reviewText,
        })
        this.guestReviews.push(review)
        return review
      } finally {
        this.guestReviewsLoading = false
      }
    },

    async fetchGuestReview(reviewId: string) {
      this.guestReviewsLoading = true
      try {
        const review = await reviewService.getReview(reviewId)
        const index = this.guestReviews.findIndex(r => r.id === reviewId)
        if (index > -1) {
          this.guestReviews[index] = review
        } else {
          this.guestReviews.push(review)
        }
        this.selectedReview = review
        return review
      } finally {
        this.guestReviewsLoading = false
      }
    },

    async updateGuestReview(reviewId: string, rating: number, reviewText?: string) {
      this.guestReviewsLoading = true
      try {
        const review = await reviewService.updateReview(reviewId, { rating, review_text: reviewText })
        const index = this.guestReviews.findIndex(r => r.id === reviewId)
        if (index > -1) {
          this.guestReviews[index] = review
        }
        return review
      } finally {
        this.guestReviewsLoading = false
      }
    },

    async deleteGuestReview(reviewId: string) {
      this.guestReviewsLoading = true
      try {
        await reviewService.deleteReview(reviewId)
        this.guestReviews = this.guestReviews.filter(r => r.id !== reviewId)
      } finally {
        this.guestReviewsLoading = false
      }
    },

    /**
     * Public Reviews
     */

    async fetchPublicReviews(menuItemId: string, page: number = 1, sort: 'recent' | 'helpful' = 'recent') {
      this.publicReviewsLoading = true
      try {
        const data = await reviewService.getPublicReviews(menuItemId, page, 10, sort)
        this.publicReviews = (data.data as PublicReview[]) || []
        this.currentPublicPage = page
        this.currentPublicSort = sort
      } finally {
        this.publicReviewsLoading = false
      }
    },

    async voteHelpful(reviewId: string, guestId?: string, ipAddress?: string) {
      try {
        const result = await reviewService.voteHelpful(reviewId, { guest_id: guestId, ip_address: ipAddress })
        const review = this.publicReviews.find(r => r.id === reviewId)
        if (review) {
          review.helpful_count = result.helpful_count
          review.not_helpful_count = result.not_helpful_count
          review.helpfulness_ratio = review.helpful_count / (review.helpful_count + review.not_helpful_count)
        }
      } catch (error) {
        console.error('Vote helpful failed:', error)
        throw error
      }
    },

    async voteNotHelpful(reviewId: string, guestId?: string, ipAddress?: string) {
      try {
        const result = await reviewService.voteNotHelpful(reviewId, { guest_id: guestId, ip_address: ipAddress })
        const review = this.publicReviews.find(r => r.id === reviewId)
        if (review) {
          review.helpful_count = result.helpful_count
          review.not_helpful_count = result.not_helpful_count
          review.helpfulness_ratio = review.helpful_count / (review.helpful_count + review.not_helpful_count)
        }
      } catch (error) {
        console.error('Vote not helpful failed:', error)
        throw error
      }
    },

    /**
     * Moderation
     */

    async fetchModeratorReviews(status?: 'pending' | 'approved' | 'rejected', page: number = 1) {
      this.moderationLoading = true
      try {
        const data = await reviewService.listReviewsForModeration(status, page, 15)
        const reviews = (data.data as Review[]) || []

        // Separate by status
        this.pendingReviews = reviews.filter(r => r.status === 'pending')
        this.approvedReviews = reviews.filter(r => r.status === 'approved')
        this.rejectedReviews = reviews.filter(r => r.status === 'rejected')

        // Set pending count from the filtered list
        this.pendingCount = this.pendingReviews.length
      } finally {
        this.moderationLoading = false
      }
    },

    async approveReview(reviewId: string) {
      this.moderationLoading = true
      try {
        const review = await reviewService.approveReview(reviewId)
        const index = this.pendingReviews.findIndex(r => r.id === reviewId)
        if (index > -1) {
          this.pendingReviews.splice(index, 1)
          this.approvedReviews.push(review)
        }
        this.pendingCount = Math.max(0, this.pendingCount - 1)
        return review
      } finally {
        this.moderationLoading = false
      }
    },

    async rejectReview(reviewId: string) {
      this.moderationLoading = true
      try {
        const review = await reviewService.rejectReview(reviewId)
        const index = this.pendingReviews.findIndex(r => r.id === reviewId)
        if (index > -1) {
          this.pendingReviews.splice(index, 1)
          this.rejectedReviews.push(review)
        }
        this.pendingCount = Math.max(0, this.pendingCount - 1)
        return review
      } finally {
        this.moderationLoading = false
      }
    },

    async deleteReviewAsAdmin(reviewId: string) {
      this.moderationLoading = true
      try {
        await reviewService.deleteReviewAsAdmin(reviewId)
        this.approvedReviews = this.approvedReviews.filter(r => r.id !== reviewId)
        this.rejectedReviews = this.rejectedReviews.filter(r => r.id !== reviewId)
        this.pendingReviews = this.pendingReviews.filter(r => r.id !== reviewId)
      } finally {
        this.moderationLoading = false
      }
    },

    /**
     * Notifications
     */

    async fetchNotifications(page: number = 1) {
      this.notificationsLoading = true
      try {
        const data = await reviewService.getReviewNotifications(page, 10)
        this.notifications = (data.data as ReviewNotification[]) || []
        this.unreadNotificationCount = await reviewService.getUnreadNotificationCount()
      } finally {
        this.notificationsLoading = false
      }
    },

    async markNotificationAsRead(notificationId: string) {
      try {
        await reviewService.markNotificationAsRead(notificationId)
        const notification = this.notifications.find(n => n.id === notificationId)
        if (notification && !notification.is_read) {
          notification.is_read = true
          this.unreadNotificationCount = Math.max(0, this.unreadNotificationCount - 1)
        }
      } catch (error) {
        console.error('Mark as read failed:', error)
      }
    },

    /**
     * Eligible Items
     */

    async fetchEligibleItems(guestId: string) {
      this.eligibleItemsLoading = true
      try {
        this.eligibleItems = await reviewService.getEligibleItems(guestId)
      } finally {
        this.eligibleItemsLoading = false
      }
    },

    /**
     * Analytics
     */

    async fetchMenuItemStats(menuItemId: string) {
      this.analyticsLoading = true
      try {
        const stats = await reviewService.getMenuItemStats(menuItemId)
        this.reviewStats[menuItemId] = stats
        return stats
      } finally {
        this.analyticsLoading = false
      }
    },

    async fetchAnalyticsData(period: 'daily' | 'weekly' | 'monthly' = 'daily') {
      this.analyticsLoading = true
      try {
        const [topRated, lowestRated, trends] = await Promise.all([
          reviewService.getTopRatedItems(5, 10),
          reviewService.getLowestRatedItems(5, 10),
          reviewService.getReviewTrends(period),
        ])

        this.topRatedItems = topRated
        this.lowestRatedItems = lowestRated
        this.reviewTrends = trends
      } finally {
        this.analyticsLoading = false
      }
    },

    /**
     * Utility Methods
     */

    clearGuestReviews() {
      this.guestReviews = []
      this.selectedReview = null
    },

    clearPublicReviews() {
      this.publicReviews = []
      this.currentPublicPage = 1
    },

    clearNotifications() {
      this.notifications = []
      this.unreadNotificationCount = 0
    },
  },
})
