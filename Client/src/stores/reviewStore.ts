import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
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

export const useReviewStore = defineStore('review', () => {
  const guestReviews = ref<Review[]>([])
  const selectedReview = ref<Review | null>(null)
  const guestReviewsLoading = ref(false)

  const publicReviews = ref<PublicReview[]>([])
  const publicReviewsLoading = ref(false)
  const currentPublicPage = ref(1)
  const currentPublicSort = ref<'recent' | 'helpful'>('recent')

  const pendingReviews = ref<Review[]>([])
  const approvedReviews = ref<Review[]>([])
  const rejectedReviews = ref<Review[]>([])
  const moderationLoading = ref(false)
  const pendingCount = ref(0)

  const notifications = ref<ReviewNotification[]>([])
  const unreadNotificationCount = ref(0)
  const notificationsLoading = ref(false)

  const eligibleItems = ref<EligibleMenuItem[]>([])
  const eligibleItemsLoading = ref(false)

  const reviewStats = ref<Record<string, ReviewStats>>({})
  const topRatedItems = ref<TopRatedItem[]>([])
  const lowestRatedItems = ref<TopRatedItem[]>([])
  const reviewTrends = ref<ReviewTrend[]>([])
  const analyticsLoading = ref(false)

  const overallAverageRating = computed(() => {
    const stats = Object.values(reviewStats.value)
    if (stats.length === 0) return 0
    const sum = stats.reduce((acc, stat) => acc + (stat.average_rating || 0), 0)
    return sum / stats.length
  })

  const totalReviewCount = computed(() => {
    return Object.values(reviewStats.value).reduce((sum, stat) => sum + stat.total_reviews, 0)
  })

  const reviewCountByStatus = computed<Record<string, number>>(() => ({
    pending: pendingReviews.value.length,
    approved: approvedReviews.value.length,
    rejected: rejectedReviews.value.length,
  }))

  const getHelpfulnessRatio = computed(() => (reviewId: string) => {
    const review = [...guestReviews.value, ...publicReviews.value].find((r) => r.id === reviewId)
    if (!review) return 0
    const total = (review.helpful_count || 0) + (review.not_helpful_count || 0)
    return total === 0 ? 0 : (review.helpful_count || 0) / total
  })

  async function submitReview(
    guestId: string,
    orderId: string,
    menuItemId: string,
    rating: number,
    reviewText?: string,
  ) {
    guestReviewsLoading.value = true
    try {
      const review = await reviewService.createReview({
        guest_id: guestId,
        order_id: orderId,
        menu_item_id: menuItemId,
        rating,
        review_text: reviewText,
      })
      guestReviews.value.push(review)
      return review
    } finally {
      guestReviewsLoading.value = false
    }
  }

  async function fetchGuestReview(reviewId: string) {
    guestReviewsLoading.value = true
    try {
      const review = await reviewService.getReview(reviewId)
      const index = guestReviews.value.findIndex((r) => r.id === reviewId)
      if (index > -1) {
        guestReviews.value[index] = review
      } else {
        guestReviews.value.push(review)
      }
      selectedReview.value = review
      return review
    } finally {
      guestReviewsLoading.value = false
    }
  }

  async function updateGuestReview(reviewId: string, rating: number, reviewText?: string) {
    guestReviewsLoading.value = true
    try {
      const review = await reviewService.updateReview(reviewId, { rating, review_text: reviewText })
      const index = guestReviews.value.findIndex((r) => r.id === reviewId)
      if (index > -1) {
        guestReviews.value[index] = review
      }
      return review
    } finally {
      guestReviewsLoading.value = false
    }
  }

  async function deleteGuestReview(reviewId: string) {
    guestReviewsLoading.value = true
    try {
      await reviewService.deleteReview(reviewId)
      guestReviews.value = guestReviews.value.filter((r) => r.id !== reviewId)
    } finally {
      guestReviewsLoading.value = false
    }
  }

  async function fetchPublicReviews(
    menuItemId: string,
    page: number = 1,
    sort: 'recent' | 'helpful' = 'recent',
  ) {
    publicReviewsLoading.value = true
    try {
      const data = await reviewService.getPublicReviews(menuItemId, page, 10, sort)
      publicReviews.value = (data.data as PublicReview[]) || []
      currentPublicPage.value = page
      currentPublicSort.value = sort
    } finally {
      publicReviewsLoading.value = false
    }
  }

  async function voteReview(
    reviewId: string,
    action: (id: string, payload: any) => Promise<any>,
    guestId?: string,
    ipAddress?: string,
  ) {
    try {
      const result = await action(reviewId, { guest_id: guestId, ip_address: ipAddress })
      const review = publicReviews.value.find((r) => r.id === reviewId)
      if (review) {
        review.helpful_count = result.helpful_count
        review.not_helpful_count = result.not_helpful_count
        review.helpfulness_ratio =
          result.helpful_count / (result.helpful_count + result.not_helpful_count || 1)
      }
      return result
    } catch (error) {
      console.error('[ReviewStore] Error voting on review:', error)
      throw error
    }
  }

  function voteHelpful(reviewId: string, guestId?: string, ipAddress?: string) {
    return voteReview(reviewId, reviewService.voteHelpful, guestId, ipAddress)
  }

  function voteNotHelpful(reviewId: string, guestId?: string, ipAddress?: string) {
    return voteReview(reviewId, reviewService.voteNotHelpful, guestId, ipAddress)
  }

  async function fetchModeratorReviews(
    status?: 'pending' | 'approved' | 'rejected',
    page: number = 1,
  ) {
    moderationLoading.value = true
    try {
      const data = await reviewService.listReviewsForModeration(status, page, 15)
      const reviews = (data.data as Review[]) || []

      pendingReviews.value = reviews.filter((r) => r.status === 'pending')
      approvedReviews.value = reviews.filter((r) => r.status === 'approved')
      rejectedReviews.value = reviews.filter((r) => r.status === 'rejected')
      pendingCount.value = pendingReviews.value.length
    } finally {
      moderationLoading.value = false
    }
  }

  async function moderateReview(
    reviewId: string,
    action: (id: string) => Promise<Review>,
    targetList: typeof approvedReviews,
  ) {
    moderationLoading.value = true
    try {
      const review = await action(reviewId)
      const index = pendingReviews.value.findIndex((r) => r.id === reviewId)
      if (index > -1) {
        pendingReviews.value.splice(index, 1)
        targetList.value.push(review)
      }
      pendingCount.value = Math.max(0, pendingCount.value - 1)
      return review
    } finally {
      moderationLoading.value = false
    }
  }

  function approveReview(reviewId: string) {
    return moderateReview(reviewId, reviewService.approveReview, approvedReviews)
  }

  function rejectReview(reviewId: string) {
    return moderateReview(reviewId, reviewService.rejectReview, rejectedReviews)
  }

  async function deleteReviewAsAdmin(reviewId: string) {
    moderationLoading.value = true
    try {
      await reviewService.deleteReviewAsAdmin(reviewId)
      approvedReviews.value = approvedReviews.value.filter((r) => r.id !== reviewId)
      rejectedReviews.value = rejectedReviews.value.filter((r) => r.id !== reviewId)
      pendingReviews.value = pendingReviews.value.filter((r) => r.id !== reviewId)
    } finally {
      moderationLoading.value = false
    }
  }

  async function fetchNotifications(page: number = 1) {
    notificationsLoading.value = true
    try {
      const data = await reviewService.getReviewNotifications(page, 10)
      notifications.value = data.data || []
      unreadNotificationCount.value = await reviewService.getUnreadNotificationCount()
    } finally {
      notificationsLoading.value = false
    }
  }

  async function markNotificationAsRead(notificationId: string) {
    try {
      await reviewService.markNotificationAsRead(notificationId)
      const notification = notifications.value.find((n) => n.id === notificationId)
      if (notification && !notification.is_read) {
        notification.is_read = true
        unreadNotificationCount.value = Math.max(0, unreadNotificationCount.value - 1)
      }
    } catch (err: any) {
      console.error('[ReviewStore] Error marking notification as read:', err)
    }
  }

  async function fetchEligibleItems(guestId: string) {
    eligibleItemsLoading.value = true
    try {
      eligibleItems.value = await reviewService.getEligibleItems(guestId)
    } finally {
      eligibleItemsLoading.value = false
    }
  }

  async function fetchMenuItemStats(menuItemId: string) {
    analyticsLoading.value = true
    try {
      const stats = await reviewService.getMenuItemStats(menuItemId)
      reviewStats.value[menuItemId] = stats
      return stats
    } finally {
      analyticsLoading.value = false
    }
  }

  async function fetchAnalyticsData(period: 'daily' | 'weekly' | 'monthly' = 'daily') {
    analyticsLoading.value = true
    try {
      const [topRated, lowestRated, trends] = await Promise.all([
        reviewService.getTopRatedItems(5, 10),
        reviewService.getLowestRatedItems(5, 10),
        reviewService.getReviewTrends(period),
      ])

      topRatedItems.value = topRated
      lowestRatedItems.value = lowestRated
      reviewTrends.value = trends
    } finally {
      analyticsLoading.value = false
    }
  }

  function clearGuestReviews() {
    guestReviews.value = []
    selectedReview.value = null
  }

  function clearPublicReviews() {
    publicReviews.value = []
    currentPublicPage.value = 1
  }

  function clearNotifications() {
    notifications.value = []
    unreadNotificationCount.value = 0
  }

  return {
    guestReviews,
    selectedReview,
    guestReviewsLoading,

    publicReviews,
    publicReviewsLoading,
    currentPublicPage,
    currentPublicSort,

    pendingReviews,
    approvedReviews,
    rejectedReviews,
    moderationLoading,
    pendingCount,

    notifications,
    unreadNotificationCount,
    notificationsLoading,

    eligibleItems,
    eligibleItemsLoading,

    reviewStats,
    topRatedItems,
    lowestRatedItems,
    reviewTrends,
    analyticsLoading,

    overallAverageRating,
    totalReviewCount,
    reviewCountByStatus,
    getHelpfulnessRatio,

    submitReview,
    fetchGuestReview,
    updateGuestReview,
    deleteGuestReview,

    fetchPublicReviews,
    voteHelpful,
    voteNotHelpful,

    fetchModeratorReviews,
    approveReview,
    rejectReview,
    deleteReviewAsAdmin,

    fetchNotifications,
    markNotificationAsRead,

    fetchEligibleItems,
    fetchMenuItemStats,
    fetchAnalyticsData,

    clearGuestReviews,
    clearPublicReviews,
    clearNotifications,
  }
})
