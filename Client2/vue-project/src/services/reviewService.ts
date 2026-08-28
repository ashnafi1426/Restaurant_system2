/**
 * Review Service
 * Handles all review-related API calls
 */

import axios from './axios'
import type {
  Review,
  CreateReviewRequest,
  UpdateReviewRequest,
  CreateResponseRequest,
  VoteRequest,
  ReviewNotification,
  EligibleMenuItem,
  PublicReview,
  ReviewStats,
  TopRatedItem,
  ReviewTrend,
  PaginatedReviews,
} from '@/types/review'

/**
 * Review Submission
 */
export const createReview = async (data: CreateReviewRequest): Promise<Review> => {
  // Try guest endpoint first (for QR menu)
  try {
    const response = await axios.post(`/guest-reviews`, data)
    return response.data.data || response.data
  } catch (guestError: any) {
    // If guest endpoint fails, try authenticated endpoint
    if (guestError.response?.status === 422 || guestError.response?.status === 500) {
      // If validation or server error, don't retry
      throw guestError
    }
    // Otherwise try authenticated endpoint
    const response = await axios.post(`/reviews`, data)
    return response.data.data || response.data
  }
}

export const getReview = async (id: string): Promise<Review> => {
  const response = await axios.get(`/reviews/${id}`)
  return response.data.data
}

export const updateReview = async (id: string, data: UpdateReviewRequest): Promise<Review> => {
  const response = await axios.put(`/reviews/${id}`, data)
  return response.data.data
}

export const deleteReview = async (id: string): Promise<void> => {
  await axios.delete(`/reviews/${id}`)
}

/**
 * Eligible Items
 */
export const getEligibleItems = async (guestId: string): Promise<EligibleMenuItem[]> => {
  const response = await axios.get(`/guests/${guestId}/eligible-items`)
  return response.data.data
}

/**
 * Public Reviews
 */
export const getPublicReviews = async (
  menuItemId: string,
  page: number = 1,
  perPage: number = 10,
  sort: 'recent' | 'helpful' = 'recent'
): Promise<PaginatedReviews> => {
  const response = await axios.get(`/menu-items/${menuItemId}/reviews`, {
    params: {
      page,
      per_page: perPage,
      sort,
    },
  })
  return response.data
}

/**
 * Moderation
 */
export const listReviewsForModeration = async (
  status?: 'pending' | 'approved' | 'rejected',
  page: number = 1,
  perPage: number = 15
): Promise<PaginatedReviews> => {
  const response = await axios.get(`/admin/reviews`, {
    params: {
      status,
      page,
      per_page: perPage,
    },
  })
  return response.data
}

export const approveReview = async (reviewId: string): Promise<Review> => {
  const response = await axios.post(`/admin/reviews/${reviewId}/approve`)
  return response.data.data
}

export const rejectReview = async (reviewId: string): Promise<Review> => {
  const response = await axios.post(`/admin/reviews/${reviewId}/reject`)
  return response.data.data
}

export const deleteReviewAsAdmin = async (reviewId: string): Promise<void> => {
  await axios.delete(`/admin/reviews/${reviewId}`)
}

/**
 * Management Responses
 */
export const createResponse = async (
  reviewId: string,
  data: CreateResponseRequest
): Promise<{ id: string; response_text: string; responder_id: string }> => {
  const response = await axios.post(`/admin/reviews/${reviewId}/response`, data)
  return response.data.data
}

export const updateResponse = async (
  responseId: string,
  data: CreateResponseRequest
): Promise<{ id: string; response_text: string }> => {
  const response = await axios.put(`/admin/responses/${responseId}`, data)
  return response.data.data
}

export const deleteResponse = async (responseId: string): Promise<void> => {
  await axios.delete(`/admin/responses/${responseId}`)
}

/**
 * Helpfulness Voting
 */
export const voteHelpful = async (
  reviewId: string,
  data: Partial<VoteRequest>
): Promise<{ helpful_count: number; not_helpful_count: number }> => {
  const response = await axios.post(`/reviews/${reviewId}/vote`, {
    vote_type: 'helpful',
    ...data,
  })
  return response.data.data
}

export const voteNotHelpful = async (
  reviewId: string,
  data: Partial<VoteRequest>
): Promise<{ helpful_count: number; not_helpful_count: number }> => {
  const response = await axios.post(`/reviews/${reviewId}/vote`, {
    vote_type: 'not_helpful',
    ...data,
  })
  return response.data.data
}

/**
 * Notifications
 */
export const getReviewNotifications = async (
  page: number = 1,
  perPage: number = 10
): Promise<PaginatedReviews> => {
  const response = await axios.get(`/notifications/reviews`, {
    params: {
      page,
      per_page: perPage,
    },
  })
  return response.data
}

export const getUnreadNotificationCount = async (): Promise<number> => {
  const response = await axios.get(`/notifications/reviews/unread-count`)
  return response.data.unread_count
}

export const markNotificationAsRead = async (notificationId: string): Promise<void> => {
  await axios.post(`/notifications/${notificationId}/read`)
}

/**
 * Analytics
 */
export const getMenuItemStats = async (menuItemId: string): Promise<ReviewStats> => {
  try {
    // Try public endpoint first (no auth needed)
    const response = await axios.get(`/menu-items/${menuItemId}/review-stats`)
    return response.data
  } catch (error) {
    // Fallback to admin endpoint if public endpoint doesn't exist
    try {
      const response = await axios.get(`/admin/analytics/menu-items/${menuItemId}/review-stats`)
      return response.data
    } catch (fallbackError) {
      console.error('Failed to load review stats:', fallbackError)
      // Return default stats if both fail
      return {
        menu_item_id: menuItemId,
        total_reviews: 0,
        average_rating: 0,
        rating_distribution: { 1: 0, 2: 0, 3: 0, 4: 0, 5: 0 }
      }
    }
  }
}

export const getTopRatedItems = async (
  minReviews: number = 5,
  limit: number = 10
): Promise<TopRatedItem[]> => {
  const response = await axios.get(`/admin/analytics/top-rated`, {
    params: {
      min_reviews: minReviews,
      limit,
    },
  })
  return response.data.data
}

export const getLowestRatedItems = async (
  minReviews: number = 5,
  limit: number = 10
): Promise<TopRatedItem[]> => {
  const response = await axios.get(`/admin/analytics/lowest-rated`, {
    params: {
      min_reviews: minReviews,
      limit,
    },
  })
  return response.data.data
}

export const getPendingReviewCount = async (): Promise<number> => {
  const response = await axios.get(`/admin/reviews-stats/pending-count`)
  return response.data.pending_count ?? 0
}

export const getReviewTrends = async (period: 'daily' | 'weekly' | 'monthly' = 'daily'): Promise<ReviewTrend[]> => {
  const response = await axios.get(`/admin/analytics/review-trends`, {
    params: { period },
  })
  return response.data.data
}

export default {
  // Guest Review Operations
  createReview,
  getReview,
  updateReview,
  deleteReview,
  getEligibleItems,

  // Public Reviews
  getPublicReviews,

  // Moderation
  listReviewsForModeration,
  approveReview,
  rejectReview,
  deleteReviewAsAdmin,

  // Management Responses
  createResponse,
  updateResponse,
  deleteResponse,

  // Voting
  voteHelpful,
  voteNotHelpful,

  // Notifications
  getReviewNotifications,
  getUnreadNotificationCount,
  markNotificationAsRead,

  // Analytics
  getMenuItemStats,
  getTopRatedItems,
  getLowestRatedItems,
  getPendingReviewCount,
  getReviewTrends,
}
