<template>
  <div class="guest-review-history bg-white rounded-lg shadow p-6">
    <div class="mb-6">
      <h2 class="text-2xl font-bold text-gray-900 mb-2">My Review History</h2>
      <p class="text-gray-600">Track your reviews and feedback</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
      <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
        <p class="text-gray-600 text-sm">Total Reviews</p>
        <p class="text-3xl font-bold text-blue-600">{{ totalReviews }}</p>
      </div>

      <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
        <p class="text-gray-600 text-sm">Average Rating</p>
        <div class="flex items-center gap-2">
          <span class="text-3xl font-bold text-yellow-600">{{ averageRating }}</span>
          <span class="text-2xl text-yellow-400">★</span>
        </div>
      </div>

      <div class="bg-green-50 border border-green-200 rounded-lg p-4">
        <p class="text-gray-600 text-sm">Approved Reviews</p>
        <p class="text-3xl font-bold text-green-600">{{ approvedCount }}</p>
      </div>
    </div>

    <div class="flex gap-2 mb-6 border-b border-gray-200">
      <button
        v-for="status in ['all', 'pending', 'approved', 'rejected']"
        :key="status"
        @click="selectedStatus = status as any"
        :class="[
          'px-4 py-2 font-semibold border-b-2 transition-colors',
          selectedStatus === status
            ? 'border-blue-600 text-blue-600'
            : 'border-transparent text-gray-600 hover:text-gray-900'
        ]"
      >
        <span class="capitalize">{{ status }}</span>
        <span class="text-xs ml-1" v-if="getCountByStatus(status) > 0">
          ({{ getCountByStatus(status) }})
        </span>
      </button>
    </div>

    <div v-if="loading" class="space-y-4">
      <div v-for="i in 3" :key="i" class="animate-pulse h-24 bg-gray-200 rounded"></div>
    </div>

    <div v-else-if="filteredReviews.length === 0" class="text-center py-12 bg-gray-50 rounded-lg">
      <p class="text-gray-600">No reviews found</p>
    </div>

    <div v-else class="space-y-4">
      <div
        v-for="review in filteredReviews"
        :key="review.id"
        class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow"
      >
        <div class="flex items-start justify-between mb-3">
          <div>
            <h3 class="font-semibold text-gray-900">{{ review.menu_item?.name }}</h3>
            <p class="text-sm text-gray-600">{{ formatDate(review.created_at) }}</p>
          </div>
          <span :class="[
            'px-3 py-1 rounded-full text-xs font-semibold',
            review.status === 'pending' ? 'bg-yellow-100 text-yellow-800' :
            review.status === 'approved' ? 'bg-green-100 text-green-800' :
            'bg-red-100 text-red-800'
          ]">
            {{ review.status }}
          </span>
        </div>

        <div class="flex gap-1 mb-3">
          <span v-for="i in 5" :key="i" class="text-lg"
            :class="i <= review.rating ? 'text-yellow-400' : 'text-gray-300'"
          >★</span>
        </div>

        <p v-if="review.review_text" class="text-gray-700 mb-3">{{ review.review_text }}</p>

        <div class="flex gap-4 text-sm text-gray-600 mb-3 pb-3 border-t border-gray-200 pt-3">
          <span>👍 {{ review.helpful_count }} helpful</span>
          <span>👎 {{ review.not_helpful_count }} not helpful</span>
        </div>

        <div v-if="review.response" class="bg-blue-50 border-l-4 border-blue-500 p-3 mb-3">
          <p class="text-xs font-semibold text-blue-900 mb-1">Management Response</p>
          <p class="text-sm text-gray-700">{{ review.response.response_text }}</p>
          <p class="text-xs text-gray-600 mt-1">{{ formatDate(review.response?.created_at) }}</p>
        </div>

        <div class="flex gap-2">
          <button
            v-if="review.status === 'pending'"
            @click="editReview(review)"
            class="px-3 py-1 text-sm bg-blue-100 hover:bg-blue-200 text-blue-700 font-semibold rounded transition-colors"
          >
            Edit
          </button>
          <button
            @click="deleteReview(review.id)"
            class="px-3 py-1 text-sm bg-red-100 hover:bg-red-200 text-red-700 font-semibold rounded transition-colors"
          >
            Delete
          </button>
          <router-link
            :to="`/menu-items/${review.menu_item_id}`"
            class="px-3 py-1 text-sm bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold rounded transition-colors text-center"
          >
            View Item
          </router-link>
        </div>
      </div>
    </div>

    <div v-if="totalPages > 1" class="flex items-center justify-center gap-2 mt-6 pt-6 border-t border-gray-200">
      <button
        @click="currentPage = Math.max(1, currentPage - 1)"
        :disabled="currentPage === 1"
        class="px-3 py-2 border border-gray-300 rounded disabled:opacity-50"
      >
        Previous
      </button>
      
      <span class="text-sm text-gray-600">
        Page {{ currentPage }} of {{ totalPages }}
      </span>

      <button
        @click="currentPage = Math.min(totalPages, currentPage + 1)"
        :disabled="currentPage === totalPages"
        class="px-3 py-2 border border-gray-300 rounded disabled:opacity-50"
      >
        Next
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import { useReviewStore } from '@/stores/reviewStore'
import { useAuthStore } from '@/stores/auth'
import reviewService from '@/services/reviewService'
import type { Review } from '@/types/review'

const reviewStore = useReviewStore()
const authStore = useAuthStore()

const reviews = ref<Review[]>([])
const selectedStatus = ref<'all' | 'pending' | 'approved' | 'rejected'>('all')
const loading = ref(false)
const currentPage = ref(1)
const totalPages = ref(1)

const totalReviews = computed(() => reviews.value.length)
const approvedCount = computed(() => reviews.value.filter(r => r.status === 'approved').length)
const pendingCount = computed(() => reviews.value.filter(r => r.status === 'pending').length)
const rejectedCount = computed(() => reviews.value.filter(r => r.status === 'rejected').length)

const averageRating = computed(() => {
  if (reviews.value.length === 0) return '0.0'
  const total = reviews.value.reduce((sum, r) => sum + r.rating, 0)
  return (total / reviews.value.length).toFixed(1)
})

const filteredReviews = computed(() => {
  if (selectedStatus.value === 'all') return reviews.value
  return reviews.value.filter(r => r.status === selectedStatus.value)
})

const formatDate = (dateString: string) => {
  const date = new Date(dateString)
  return date.toLocaleDateString('en-US', {
    month: 'long',
    day: 'numeric',
    year: 'numeric'
  })
}

const getCountByStatus = (status: string) => {
  if (status === 'all') return reviews.value.length
  if (status === 'pending') return pendingCount.value
  if (status === 'approved') return approvedCount.value
  if (status === 'rejected') return rejectedCount.value
  return 0
}

const editReview = (_review: Review) => {}

const deleteReview = async (reviewId: string) => {
  if (!confirm('Are you sure you want to delete this review?')) return

  loading.value = true
  try {
    await reviewService.deleteReview(reviewId)
    reviews.value = reviews.value.filter(r => r.id !== reviewId)
  } catch (error) {
    console.error('[GuestReviewHistory] Failed to delete review:', error)
  } finally {
    loading.value = false
  }
}

const loadReviews = async () => {
  if (!authStore.user?.id) return

  loading.value = true
  try {
    reviews.value = reviewStore.guestReviews
  } catch (error) {
    console.error('[GuestReviewHistory] Failed to load reviews:', error)
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  loadReviews()
})

watch(currentPage, () => {
  loadReviews()
})
</script>

<style scoped>
</style>
