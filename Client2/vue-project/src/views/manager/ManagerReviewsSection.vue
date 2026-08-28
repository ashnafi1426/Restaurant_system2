<template>
  <div class="manager-reviews-section">
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
      <div>
        <h2 class="text-2xl font-bold text-gray-900">Review Management</h2>
        <p class="text-gray-600 text-sm mt-1">Moderate and respond to guest reviews</p>
      </div>
      <router-link
        to="/reviews/moderation"
        class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition-colors"
      >
        View Full Dashboard
      </router-link>
    </div>

    <!-- Stats Row -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
      <!-- Pending Reviews -->
      <div class="bg-white rounded-lg shadow p-4 border-l-4 border-yellow-400">
        <p class="text-gray-600 text-sm mb-1">Pending Reviews</p>
        <p class="text-3xl font-bold text-yellow-600">{{ pendingCount }}</p>
        <p class="text-xs text-gray-500 mt-2">Awaiting approval</p>
      </div>

      <!-- Approved Reviews -->
      <div class="bg-white rounded-lg shadow p-4 border-l-4 border-green-400">
        <p class="text-gray-600 text-sm mb-1">Approved Reviews</p>
        <p class="text-3xl font-bold text-green-600">{{ approvedCount }}</p>
        <p class="text-xs text-gray-500 mt-2">Published</p>
      </div>

      <!-- Rejected Reviews -->
      <div class="bg-white rounded-lg shadow p-4 border-l-4 border-red-400">
        <p class="text-gray-600 text-sm mb-1">Rejected Reviews</p>
        <p class="text-3xl font-bold text-red-600">{{ rejectedCount }}</p>
        <p class="text-xs text-gray-500 mt-2">Archived</p>
      </div>

      <!-- Avg Rating -->
      <div class="bg-white rounded-lg shadow p-4 border-l-4 border-blue-400">
        <p class="text-gray-600 text-sm mb-1">Average Rating</p>
        <div class="flex items-center gap-2">
          <span class="text-3xl font-bold text-blue-600">{{ avgRating }}</span>
          <span class="text-xl text-yellow-400">★</span>
        </div>
        <p class="text-xs text-gray-500 mt-2">Across all items</p>
      </div>
    </div>

    <!-- Pending Reviews Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
      <div class="border-b border-gray-200 p-6">
        <h3 class="text-lg font-bold text-gray-900">Pending Reviews</h3>
      </div>

      <div v-if="loading" class="p-6">
        <div class="space-y-4">
          <div v-for="i in 3" :key="i" class="animate-pulse h-20 bg-gray-200 rounded"></div>
        </div>
      </div>

      <div v-else-if="pendingReviews.length === 0" class="p-6 text-center">
        <p class="text-gray-600">No pending reviews</p>
      </div>

      <div v-else class="divide-y divide-gray-200">
        <div
          v-for="review in pendingReviews.slice(0, 5)"
          :key="review.id"
          class="p-4 hover:bg-gray-50 transition-colors"
        >
          <!-- Review Header -->
          <div class="flex items-start justify-between mb-2">
            <div>
              <h4 class="font-semibold text-gray-900">
                {{ review.guest?.first_name }} {{ review.guest?.last_name }}
              </h4>
              <p class="text-sm text-gray-600">{{ review.menu_item?.name }}</p>
            </div>
            <div class="flex gap-1">
              <span
                v-for="i in 5"
                :key="i"
                class="text-lg"
                :class="i <= review.rating ? 'text-yellow-400' : 'text-gray-300'"
              >
                ★
              </span>
            </div>
          </div>

          <!-- Review Text -->
          <p v-if="review.review_text" class="text-sm text-gray-700 mb-3">{{ review.review_text }}</p>

          <!-- Actions -->
          <div class="flex gap-2">
            <button
              @click="approveReview(review.id)"
              :disabled="actionLoading === review.id"
              class="px-3 py-1 text-sm bg-green-100 hover:bg-green-200 text-green-700 font-semibold rounded transition-colors disabled:opacity-50"
            >
              {{ actionLoading === review.id ? 'Approving...' : 'Approve' }}
            </button>
            <button
              @click="rejectReview(review.id)"
              :disabled="actionLoading === review.id"
              class="px-3 py-1 text-sm bg-red-100 hover:bg-red-200 text-red-700 font-semibold rounded transition-colors disabled:opacity-50"
            >
              {{ actionLoading === review.id ? 'Rejecting...' : 'Reject' }}
            </button>
            <button
              @click="toggleResponse(review.id)"
              class="px-3 py-1 text-sm bg-blue-100 hover:bg-blue-200 text-blue-700 font-semibold rounded transition-colors"
            >
              Respond
            </button>
          </div>

          <!-- Response Form -->
          <div v-if="responseFormId === review.id" class="mt-3 pt-3 border-t border-gray-200">
            <textarea
              v-model="responseText"
              placeholder="Write your response..."
              maxlength="500"
              rows="3"
              class="w-full p-2 border border-gray-300 rounded mb-2 text-sm"
            />
            <div class="flex gap-2">
              <button
                @click="submitResponse(review.id)"
                class="flex-1 px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded text-sm transition-colors"
              >
                Send Response
              </button>
              <button
                @click="toggleResponse(null)"
                class="px-3 py-2 border border-gray-300 rounded text-sm font-semibold hover:bg-gray-100 transition-colors"
              >
                Cancel
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- View All Link -->
      <div v-if="pendingReviews.length > 5" class="p-4 border-t border-gray-200 text-center">
        <router-link
          to="/reviews/moderation"
          class="text-blue-600 hover:text-blue-700 font-semibold text-sm"
        >
          View All Pending Reviews ({{ pendingReviews.length }})
        </router-link>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import reviewService from '@/services/reviewService'
import type { Review } from '@/types/review'

const pendingReviews = ref<Review[]>([])
const approvedCount = ref(0)
const rejectedCount = ref(0)
const pendingCount = ref(0)
const avgRating = ref('0.0')
const loading = ref(true)
const actionLoading = ref<string | null>(null)
const responseFormId = ref<string | null>(null)
const responseText = ref('')

const loadData = async () => {
  loading.value = true
  try {
    // Load pending reviews
    const data = await reviewService.listReviewsForModeration('pending', 1, 5)
    pendingReviews.value = (data.data as Review[]) || []

    // Load pending count
    pendingCount.value = await reviewService.getPendingReviewCount()

    // Load stats for approved/rejected counts
    const approved = await reviewService.listReviewsForModeration('approved', 1, 1)
    const rejected = await reviewService.listReviewsForModeration('rejected', 1, 1)

    approvedCount.value = approved.total || 0
    rejectedCount.value = rejected.total || 0

    // Calculate average rating from pending reviews
    if (pendingReviews.value.length > 0) {
      const total = pendingReviews.value.reduce((sum, r) => sum + r.rating, 0)
      avgRating.value = (total / pendingReviews.value.length).toFixed(1)
    }
  } catch (error) {
    console.error('Failed to load review data:', error)
  } finally {
    loading.value = false
  }
}

const approveReview = async (reviewId: string) => {
  actionLoading.value = reviewId
  try {
    await reviewService.approveReview(reviewId)
    pendingReviews.value = pendingReviews.value.filter(r => r.id !== reviewId)
    pendingCount.value = Math.max(0, pendingCount.value - 1)
    approvedCount.value++
  } catch (error) {
    console.error('Failed to approve review:', error)
  } finally {
    actionLoading.value = null
  }
}

const rejectReview = async (reviewId: string) => {
  actionLoading.value = reviewId
  try {
    await reviewService.rejectReview(reviewId)
    pendingReviews.value = pendingReviews.value.filter(r => r.id !== reviewId)
    pendingCount.value = Math.max(0, pendingCount.value - 1)
    rejectedCount.value++
  } catch (error) {
    console.error('Failed to reject review:', error)
  } finally {
    actionLoading.value = null
  }
}

const toggleResponse = (reviewId: string | null) => {
  responseFormId.value = reviewId
  responseText.value = ''
}

const submitResponse = async (reviewId: string) => {
  if (!responseText.value.trim()) return

  try {
    await reviewService.createResponse(reviewId, { response_text: responseText.value })
    responseFormId.value = null
    responseText.value = ''
    // Optionally reload data
  } catch (error) {
    console.error('Failed to submit response:', error)
  }
}

onMounted(() => {
  loadData()
})
</script>

<style scoped>
.manager-reviews-section {
  /* Component styles */
}
</style>
