<template>
  <div class="manager-review-widget bg-white rounded-lg shadow p-6">
    <div class="flex items-center justify-between mb-4">
      <div>
        <h3 class="text-lg font-bold text-gray-900"> Review Moderation</h3>
        <p class="text-xs text-gray-500 mt-1">{{ statusMessage }}</p>
      </div>
      <router-link
        to="/reviews/moderation"
        class="inline-block px-3 py-1 text-sm bg-blue-100 text-blue-700 hover:bg-blue-200 font-semibold rounded transition-colors"
      >
        Full Dashboard
      </router-link>
    </div>

    <div class="grid grid-cols-3 gap-3 mb-4">
      <div class="text-center p-2 bg-yellow-50 rounded">
        <p class="text-2xl font-bold text-yellow-600">{{ pendingCount }}</p>
        <p class="text-xs text-gray-600">Pending</p>
      </div>
      <div class="text-center p-2 bg-green-50 rounded">
        <p class="text-2xl font-bold text-green-600">{{ approvedCount }}</p>
        <p class="text-xs text-gray-600">Approved</p>
      </div>
      <div class="text-center p-2 bg-red-50 rounded">
        <p class="text-2xl font-bold text-red-600">{{ rejectedCount }}</p>
        <p class="text-xs text-gray-600">Rejected</p>
      </div>
    </div>

    <div v-if="latestPending" class="border-t pt-4">
      <p class="text-sm font-semibold text-gray-900 mb-2">Latest Pending:</p>
      <div class="bg-yellow-50 p-3 rounded-lg">
        <p class="text-sm font-semibold text-gray-900">
          {{ latestPending.guest?.first_name }} rated {{ latestPending.menu_item?.name }}
        </p>
        <div class="flex gap-0.5 mt-1">
          <span
            v-for="i in 5"
            :key="i"
            class="text-xs"
            :class="i <= latestPending.rating ? 'text-yellow-400' : 'text-gray-300'"
          >
            ★
          </span>
        </div>
        <p v-if="latestPending.review_text" class="text-xs text-gray-700 mt-2 line-clamp-2">
          {{ latestPending.review_text }}
        </p>
        <div class="flex gap-2 mt-3">
          <button
            @click="approve"
            class="flex-1 px-2 py-1 text-xs bg-green-600 hover:bg-green-700 text-white font-semibold rounded transition-colors"
          >
            Approve
          </button>
          <button
            @click="reject"
            class="flex-1 px-2 py-1 text-xs bg-red-600 hover:bg-red-700 text-white font-semibold rounded transition-colors"
          >
            Reject
          </button>
        </div>
      </div>
    </div>

    <div v-else class="text-center py-4">
      <p class="text-gray-600 text-sm">✓ All reviews moderated!</p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import reviewService from '@/services/reviewService'
import type { Review } from '@/types/review'

const pendingReviews = ref<Review[]>([])
const pendingCount = ref(0)
const approvedCount = ref(0)
const rejectedCount = ref(0)

const latestPending = computed(() => pendingReviews.value[0] || null)

const statusMessage = computed(() => {
  if (pendingCount.value === 0) return 'All reviews moderated'
  if (pendingCount.value === 1) return '1 review pending approval'
  return `${pendingCount.value} reviews pending approval`
})

const loadData = async () => {
  try {
    const data = await reviewService.listReviewsForModeration('pending', 1, 5)
    pendingReviews.value = (data.data as Review[]) || []
    pendingCount.value = await reviewService.getPendingReviewCount()

    try {
      const approved = await reviewService.listReviewsForModeration('approved', 1, 1)
      const rejected = await reviewService.listReviewsForModeration('rejected', 1, 1)
      approvedCount.value = approved.total || 0
      rejectedCount.value = rejected.total || 0
    } catch (error) {
      console.error('[ManagerReviewWidget] Error loading approved/rejected review counts:', error)
      approvedCount.value = 0
      rejectedCount.value = 0
    }
  } catch (error) {
    console.error('[ManagerReviewWidget] Error loading review data:', error)
  }
}

const approve = async () => {
  if (!latestPending.value) return
  try {
    await reviewService.approveReview(latestPending.value.id)
    pendingReviews.value.shift()
    pendingCount.value = Math.max(0, pendingCount.value - 1)
    approvedCount.value++
  } catch (error) {
    console.error('[ManagerReviewWidget] Error approving review:', error)
  }
}

const reject = async () => {
  if (!latestPending.value) return
  try {
    await reviewService.rejectReview(latestPending.value.id)
    pendingReviews.value.shift()
    pendingCount.value = Math.max(0, pendingCount.value - 1)
    rejectedCount.value++
  } catch (error) {
    console.error('[ManagerReviewWidget] Error rejecting review:', error)
  }
}

onMounted(() => {
  loadData()
})
</script>

<style scoped>
</style>
