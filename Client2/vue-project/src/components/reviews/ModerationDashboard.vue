<template>
  <div class="moderation-dashboard">
    <div class="header mb-6">
      <h2 class="text-2xl font-bold mb-2">Review Moderation Dashboard</h2>
      
      <!-- Status Tabs -->
      <div class="flex gap-2">
        <button
          v-for="status in ['pending', 'approved', 'rejected']"
          :key="status"
          @click="currentStatus = status as any"
          :class="[
            'px-4 py-2 rounded-lg font-semibold transition-colors',
            currentStatus === status
              ? 'bg-blue-600 text-white'
              : 'bg-gray-200 hover:bg-gray-300 text-gray-900'
          ]"
        >
          <span v-if="status === 'pending'">⏳ Pending ({{ pendingCount }})</span>
          <span v-else-if="status === 'approved'"> Approved</span>
          <span v-else> Rejected</span>
        </button>
      </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-3 gap-4 mb-6">
      <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
        <p class="text-gray-600 text-sm">Pending Reviews</p>
        <p class="text-3xl font-bold text-yellow-600">{{ pendingCount }}</p>
      </div>
      <div class="bg-green-50 border border-green-200 rounded-lg p-4">
        <p class="text-gray-600 text-sm">Approved</p>
        <p class="text-3xl font-bold text-green-600">{{ approvedCount }}</p>
      </div>
      <div class="bg-red-50 border border-red-200 rounded-lg p-4">
        <p class="text-gray-600 text-sm">Rejected</p>
        <p class="text-3xl font-bold text-red-600">{{ rejectedCount }}</p>
      </div>
    </div>

    <!-- Reviews List -->
    <div v-if="loading" class="space-y-4">
      <div v-for="i in 3" :key="i" class="animate-pulse">
        <div class="h-32 bg-gray-200 rounded-lg"></div>
      </div>
    </div>

    <div v-else-if="reviews.length === 0" class="text-center py-12 bg-gray-50 rounded-lg">
      <p class="text-gray-600">No {{ currentStatus }} reviews</p>
    </div>

    <div v-else class="space-y-4">
      <div v-for="review in reviews" :key="review.id" class="bg-white border border-gray-200 rounded-lg p-4">
        <!-- Review Header -->
        <div class="flex items-start justify-between mb-3">
          <div>
            <h4 class="font-semibold text-gray-900">{{ review.guest?.first_name }} {{ review.guest?.last_name }}</h4>
            <p class="text-sm text-gray-600">{{ review.menu_item?.name }}</p>
            <div class="flex gap-1 mt-1">
              <span v-for="i in 5" :key="i" class="text-lg" :class="i <= review.rating ? 'text-yellow-400' : 'text-gray-300'">★</span>
            </div>
          </div>
          <span :class="[
            'px-3 py-1 rounded-full text-sm font-semibold',
            review.status === 'pending' ? 'bg-yellow-100 text-yellow-800' : 
            review.status === 'approved' ? 'bg-green-100 text-green-800' :
            'bg-red-100 text-red-800'
          ]">
            {{ review.status }}
          </span>
        </div>

        <!-- Review Text -->
        <p v-if="review.review_text" class="text-gray-700 mb-3">{{ review.review_text }}</p>

        <!-- Review Metadata -->
        <div class="text-sm text-gray-600 mb-3 pb-3 border-b border-gray-200">
          <p>Submitted: {{ formatDate(review.created_at) }}</p>
          <p v-if="review.approved_at">Approved: {{ formatDate(review.approved_at) }}</p>
          <p v-if="review.rejected_at">Rejected: {{ formatDate(review.rejected_at) }}</p>
        </div>

        <!-- Actions -->
        <div class="flex gap-2">
          <button
            v-if="currentStatus === 'pending'"
            @click="approveReview(review.id)"
            :disabled="actionLoading === review.id"
            class="flex-1 bg-green-600 hover:bg-green-700 disabled:bg-gray-400 text-white font-semibold py-2 px-4 rounded-lg transition-colors"
          >
            {{ actionLoading === review.id ? 'Approving...' : 'Approve' }}
          </button>
          
          <button
            v-if="currentStatus === 'pending'"
            @click="rejectReview(review.id)"
            :disabled="actionLoading === review.id"
            class="flex-1 bg-red-600 hover:bg-red-700 disabled:bg-gray-400 text-white font-semibold py-2 px-4 rounded-lg transition-colors"
          >
            {{ actionLoading === review.id ? 'Rejecting...' : 'Reject' }}
          </button>

          <button
            v-if="currentStatus !== 'pending'"
            @click="deleteReview(review.id)"
            :disabled="actionLoading === review.id"
            class="flex-1 bg-gray-600 hover:bg-gray-700 disabled:bg-gray-400 text-white font-semibold py-2 px-4 rounded-lg transition-colors"
          >
            {{ actionLoading === review.id ? 'Deleting...' : 'Delete' }}
          </button>

          <button
            v-if="review.status === 'approved' && !review.response"
            @click="showResponseForm = review.id"
            class="px-4 py-2 border border-blue-600 text-blue-600 hover:bg-blue-50 font-semibold rounded-lg transition-colors"
          >
            Respond
          </button>
        </div>

        <!-- Response Section -->
        <div v-if="review.response" class="bg-blue-50 border-l-4 border-blue-500 p-3 mt-3">
          <p class="text-sm font-semibold text-blue-900 mb-1">Your Response</p>
          <p class="text-sm text-gray-700">{{ review.response.response_text }}</p>
        </div>

        <!-- Response Form -->
        <div v-if="showResponseForm === review.id" class="bg-gray-50 p-3 mt-3 rounded-lg">
          <textarea
            v-model="responseText"
            placeholder="Write your response... (max 500 characters)"
            maxlength="500"
            rows="3"
            class="w-full p-2 border border-gray-300 rounded mb-2"
          />
          <div class="flex gap-2">
            <button
              @click="submitResponse(review.id)"
              class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded"
            >
              Send Response
            </button>
            <button
              @click="showResponseForm = null"
              class="px-4 py-2 border border-gray-300 rounded hover:bg-gray-100"
            >
              Cancel
            </button>
          </div>
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
import { ref, watch, onMounted } from 'vue'
import reviewService from '@/services/reviewService'
import { Review } from '@/types/review'

const reviews = ref<Review[]>([])
const loading = ref(true)
const actionLoading = ref<string | null>(null)
const currentPage = ref(1)
const totalPages = ref(1)
const currentStatus = ref<'pending' | 'approved' | 'rejected'>('pending')
const pendingCount = ref(0)
const approvedCount = ref(0)
const rejectedCount = ref(0)
const showResponseForm = ref<string | null>(null)
const responseText = ref('')

const formatDate = (dateString: string | null) => {
  if (!dateString) return 'N/A'
  const date = new Date(dateString)
  return date.toLocaleDateString('en-US', { 
    month: 'short', 
    day: 'numeric', 
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const loadReviews = async () => {
  loading.value = true
  try {
    const data = await reviewService.listReviewsForModeration(currentStatus.value, currentPage.value, 15)
    reviews.value = (data.data as Review[]) || []
    totalPages.value = data.last_page || 1

    // Load counts
    const pending = await reviewService.getPendingReviewCount()
    pendingCount.value = pending
  } catch (error) {
    console.error('Failed to load reviews:', error)
  } finally {
    loading.value = false
  }
}

const approveReview = async (reviewId: string) => {
  actionLoading.value = reviewId
  try {
    await reviewService.approveReview(reviewId)
    await loadReviews()
  } catch (error) {
    console.error('Approval failed:', error)
  } finally {
    actionLoading.value = null
  }
}

const rejectReview = async (reviewId: string) => {
  actionLoading.value = reviewId
  try {
    await reviewService.rejectReview(reviewId)
    await loadReviews()
  } catch (error) {
    console.error('Rejection failed:', error)
  } finally {
    actionLoading.value = null
  }
}

const deleteReview = async (reviewId: string) => {
  if (!confirm('Are you sure you want to delete this review?')) return

  actionLoading.value = reviewId
  try {
    await reviewService.deleteReviewAsAdmin(reviewId)
    await loadReviews()
  } catch (error) {
    console.error('Deletion failed:', error)
  } finally {
    actionLoading.value = null
  }
}

const submitResponse = async (reviewId: string) => {
  if (!responseText.value.trim()) return

  try {
    await reviewService.createResponse(reviewId, { response_text: responseText.value })
    responseText.value = ''
    showResponseForm.value = null
    await loadReviews()
  } catch (error) {
    console.error('Response submission failed:', error)
  }
}

watch([currentPage, currentStatus], () => {
  loadReviews()
})

onMounted(() => {
  loadReviews()
})
</script>

<style scoped>
.moderation-dashboard {
  background: white;
  border-radius: 8px;
  padding: 20px;
}
</style>
