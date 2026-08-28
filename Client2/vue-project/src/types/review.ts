/**
 * Review Types
 * Type definitions for menu item review and rating system
 */

export interface Review {
  id: string
  guest_id: string
  order_id: string
  menu_item_id: string
  rating: number
  review_text: string | null
  status: 'pending' | 'approved' | 'rejected'
  approved_by: string | null
  approved_at: string | null
  rejected_by: string | null
  rejected_at: string | null
  helpful_count: number
  not_helpful_count: number
  created_at: string
  updated_at: string
  guest?: {
    id: string
    first_name: string
    last_name: string
  }
  menu_item?: {
    id: string
    name: string
    image: string | null
  }
  response?: ReviewResponse | null
}

export interface ReviewResponse {
  id: string
  review_id: string
  responder_id: string
  response_text: string
  created_at: string
  updated_at: string
  responder?: {
    id: string
    name: string
    role: string
  }
}

export interface PublicReview {
  id: string
  guest_name: string
  rating: number
  review_text: string | null
  helpful_count: number
  not_helpful_count: number
  helpfulness_ratio: number
  created_at: string
  response: {
    text: string
    responder_role: string
    created_at: string
  } | null
}

export interface ReviewRating {
  average_rating: number | null
  review_count: number
}

export interface ReviewStats {
  menu_item_id: string
  total_reviews: number
  average_rating: number | null
  rating_distribution: {
    1: number
    2: number
    3: number
    4: number
    5: number
  }
  rating_percentages: {
    1: number
    2: number
    3: number
    4: number
    5: number
  }
}

export interface CreateReviewRequest {
  guest_id: string
  order_id: string
  menu_item_id: string
  rating: number
  review_text?: string
}

export interface UpdateReviewRequest {
  rating: number
  review_text?: string
}

export interface CreateResponseRequest {
  response_text: string
}

export interface VoteRequest {
  vote_type: 'helpful' | 'not_helpful'
  guest_id?: string
  ip_address?: string
}

export interface ReviewNotification {
  id: string
  user_id: string
  review_id: string
  notification_type: 'new_review' | 'review_approved' | 'review_rejected'
  message: string
  is_read: boolean
  created_at: string
  read_at: string | null
  review?: Review
}

export interface EligibleMenuItem {
  id: string
  name: string
  description: string
  image: string | null
  price: number
  order_id: string
  order_number: string
  rating?: number
  is_available?: boolean
}

export interface TopRatedItem {
  menu_item_id: string
  name: string
  image: string | null
  average_rating: number
  review_count: number
}

export interface ReviewTrend {
  date: string
  count: number
}

export interface PaginatedReviews {
  data: Review[] | PublicReview[]
  current_page: number
  last_page: number
  per_page: number
  total: number
}
