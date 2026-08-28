<template>
  <div class="public-reviews-list">
    <!-- Header with Rating and Sort -->
    <div class="mb-6">
      <div class="flex items-center justify-between mb-4">
        <div v-if="stats">
          <div class="flex items-center gap-2">
            <span class="text-3xl font-bold text-gray-900">{{ stats.average_rating || 'N/A' }}</span>
            <div>
              <div class="flex gap-1">
                <span v-for="i in 5" :key="i" class="text-lg" :class="i <= Math.round(stats.average_rating || 0) ? 'text-yellow-400' : 'text-gray-300'">★</span>
              </div>
              <p class="text-sm text-gray-600">{{ stats.total_reviews }} review{{ stats.total_reviews !== 1 ? 's' : '' }}</p>
            </div>
          </div>
        </div>

        <div class="flex gap-2">
          <button
            @click="currentSort = 'recent'"
            :class="[
              'px-4 py-2 rounded-lg font-semibold transition-colors',
              currentSort === 'recent'
                ? 'bg-blue-600 text-white'
                : 'bg-gray-200 hover:bg-gray-300 text-gray-900'
            ]"
          >
            Recent
          </button>
          <button
            @click="currentSort = 'helpful'"
            :class="[
              'px-4 py-2 rounded-lg font-semibold transition-colors',
              currentSort === 'helpful'
                ? 'bg-blue-600 text-white'
                : 'bg-gray-200 hover:bg-gray-300 text-gray-900'
            ]"
          >
            Helpful
          </button>
        </div>
      </div>

      <!-- Rating Distribution -->
      <div v-if="stats?.rating_distribution" class="bg-gray-50 p-4 rounded-lg">
        <div v-for="rating in 5" :key="rating" class="flex items-center gap-3 mb-2">
          <span class="text-sm font-medium w-8">{{ rating }}★</span>
          <div class="flex-1 h-2 bg-gray-200 rounded-full overflow-hidden">
            <div
              class="h-full bg-yellow-400 transition-all"
              :style="{ width: `${(stats.rating_percentages?.[rating] || 0) / 100 * 100}%` }"
            />
          </div>
          <span class="text-sm text-gray-600 w-12 text-right">{{ stats.rating_percentages?.[rating] || 0 }}%</span>
        </div>
      </div>
    </div>

    <!-- No Reviews Message -->
    <div v-if="loading" class="space-y-4">
      <div v-for="i in 3" :key="i" class="animate-pulse">
        <div class="h-24 bg-gray-200 rounded-lg"></div>
      </div>
    </div>

    <div v-else-if="reviews.length === 0" class="text-center py-12">
      <p class="text-gray-600 text-lg">No reviews yet. Be the first to review this item!</p>
    </div>

    <!-- Reviews List -->
    <div v-else class="space-y-4">
      <div v-for="review in reviews" :key="review.id" class="bg-white border border-gray-200 rounded-lg p-4">
        <!-- Review Header -->
        <div class="flex items-center justify-between mb-3">
          <div>
            <h4 class="font-semibold text-gray-900">{{ review.guest_name }}</h4>
            <p class="text-sm text-gray-500">{{ formatDate(review.created_at) }}</p>
          </div>
          <div class="flex gap-1">
            <span v-for="i in 5" :key="i" class="text-lg" :class="i <= review.rating ? 'text-yellow-400' : 'text-gray-300'">★</span>
          </div>
        </div>

        <!-- Review Text -->
        <p v-if="review.review_text" class="text-gray-700 mb-3">{{ review.review_text }}</p>

        <!-- Helpful Votes -->
        <div class="flex items-center gap-4 mb-3 pt-3 border-t border-gray-200">
          <button
            @click="voteHelpful(review.id)"
            class="flex items-center gap-1 text-sm hover:text-blue-600 transition-colors"
          >
            <span>👍</span>
            <span>{{ review.helpful_count }} helpful</span>
          </button>
          <button
            @click="voteNotHelpful(review.id)"
            class="flex items-center gap-1 text-sm hover:text-red-600 transition-colors"
          >
            <span>👎</span>
            <span>{{ review.not_helpful_count }} not helpful</span>
          </button>
        </div>

        <!-- Management Response -->
        <div v-if="review.response" class="bg-blue-50 border-l-4 border-blue-500 p-3 mt-3">
          <p class="text-sm font-semibold text-blue-900 mb-1">Response from Management</p>
          <p class="text-sm text-gray-700">{{ review.response.text }}</p>
          <p class="text-xs text-gray-600 mt-2">{{ formatDate(review.response.created_at) }}</p>
        </div>
      </div>
    </div>

    <!-- Pagination -->
    <div v-if="totalPages > 1" class="flex items-center justify-center gap-2 mt-6">
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
import { ref, computed, watch, onMounted } from 'vue'
import reviewService from '@/services/reviewService'
import { PublicReview, ReviewStats, PaginatedReviews } from '@/types/review'

interface Props {
  menuItemId: string
}

const props = defineProps<Props>()

const reviews = ref<PublicReview[]>([])
const stats = ref<ReviewStats | null>(null)
const loading = ref(true)
const currentPage = ref(1)
const totalPages = ref(1)
const currentSort = ref<'recent' | 'helpful'>('recent')

const formatDate = (dateString: string) => {
  const date = new Date(dateString)
  return date.toLocaleDateString('en-US', { 
    month: 'short', 
    day: 'numeric', 
    year: 'numeric' 
  })
}

const loadReviews = async () => {
  loading.value = true
  try {
    const data = await reviewService.getPublicReviews(
      props.menuItemId,
      currentPage.value,
      10,
      currentSort.value
    )
    
    reviews.value = (data.data as PublicReview[]) || []
    totalPages.value = data.last_page || 1
    
    // Load stats
    stats.value = await reviewService.getMenuItemStats(props.menuItemId)
  } catch (error) {
    console.error('Failed to load reviews:', error)
  } finally {
    loading.value = false
  }
}

const voteHelpful = async (reviewId: string) => {
  try {
    const ipAddress = await getClientIP()
    const result = await reviewService.voteHelpful(reviewId, { ip_address: ipAddress })
    
    // Update local review
    const review = reviews.value.find(r => r.id === reviewId)
    if (review) {
      review.helpful_count = result.helpful_count
      review.not_helpful_count = result.not_helpful_count
      review.helpfulness_ratio = review.helpful_count / (review.helpful_count + review.not_helpful_count)
    }
  } catch (error) {
    console.error('Vote failed:', error)
  }
}

const voteNotHelpful = async (reviewId: string) => {
  try {
    const ipAddress = await getClientIP()
    const result = await reviewService.voteNotHelpful(reviewId, { ip_address: ipAddress })
    
    // Update local review
    const review = reviews.value.find(r => r.id === reviewId)
    if (review) {
      review.helpful_count = result.helpful_count
      review.not_helpful_count = result.not_helpful_count
      review.helpfulness_ratio = review.helpful_count / (review.helpful_count + review.not_helpful_count)
    }
  } catch (error) {
    console.error('Vote failed:', error)
  }
}

const getClientIP = async () => {
  try {
    const response = await fetch('https://api.ipify.org?format=json')
    const data = await response.json()
    return data.ip
  } catch {
    return '127.0.0.1'
  }
}

watch([currentPage, currentSort], () => {
  loadReviews()
})

onMounted(() => {
  loadReviews()
})
</script>

<style scoped>
.public-reviews-list {
  background: white;
  border-radius: 8px;
  padding: 20px;
}
</style>
