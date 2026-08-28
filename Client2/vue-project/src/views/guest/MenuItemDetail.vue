<template>
  <div class="menu-item-detail-page min-h-screen bg-gray-50">
    <div class="max-w-4xl mx-auto px-4 py-8">
      <!-- Header with Back Button -->
      <div class="flex items-center justify-between mb-6">
        <button
          @click="goBack"
          class="flex items-center gap-2 text-blue-600 hover:text-blue-700 font-semibold"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
          </svg>
          Back to Menu
        </button>
      </div>

      <!-- Main Content Grid -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
        <!-- Left: Image and Basic Info -->
        <div>
          <!-- Item Image -->
          <div class="rounded-lg overflow-hidden mb-6 h-80 bg-gray-200">
            <img
              v-if="item?.image"
              :src="item.image"
              :alt="item?.name"
              class="w-full h-full object-cover"
            />
            <div v-else class="w-full h-full flex items-center justify-center">
              <span class="text-6xl">🍽️</span>
            </div>
          </div>

          <!-- Basic Info Card -->
          <div class="bg-white rounded-lg p-6 shadow">
            <!-- Name and Category -->
            <h1 class="text-3xl font-bold text-gray-900 mb-2">{{ item?.name }}</h1>
            <span class="inline-block bg-purple-100 text-purple-700 px-3 py-1 rounded-full text-sm font-semibold mb-4">
              {{ item?.category }}
            </span>

            <!-- Rating and Reviews Count -->
            <div v-if="stats" class="mb-4 pb-4 border-b border-gray-200">
              <div class="flex items-center gap-4">
                <div>
                  <div class="flex items-center gap-2 mb-1">
                    <span class="text-2xl font-bold text-yellow-600">{{ stats.average_rating?.toFixed(1) || 'N/A' }}</span>
                    <div class="flex gap-1">
                      <span v-for="i in 5" :key="i" class="text-lg"
                        :class="i <= Math.round(stats.average_rating || 0) ? 'text-yellow-400' : 'text-gray-300'"
                      >★</span>
                    </div>
                  </div>
                  <p class="text-sm text-gray-600">{{ stats.total_reviews }} review{{ stats.total_reviews !== 1 ? 's' : '' }}</p>
                </div>
              </div>

              <!-- Rating Distribution -->
              <div class="mt-4 space-y-2">
                <div v-for="rating in 5" :key="rating" class="flex items-center gap-2">
                  <span class="text-sm w-4">{{ rating }}★</span>
                  <div class="flex-1 h-2 bg-gray-200 rounded-full overflow-hidden">
                    <div
                      class="h-full bg-yellow-400 transition-all"
                      :style="{ width: `${(stats.rating_percentages?.[rating] || 0)}%` }"
                    />
                  </div>
                  <span class="text-sm w-8 text-right text-gray-600">{{ stats.rating_percentages?.[rating] || 0 }}%</span>
                </div>
              </div>
            </div>

            <!-- Price -->
            <div class="mb-6">
              <span class="text-3xl font-bold text-green-600">${{ item?.price?.toFixed(2) }}</span>
            </div>

            <!-- Availability -->
            <div class="mb-6">
              <span :class="[
                'inline-block px-4 py-2 rounded-full font-semibold',
                item?.is_available
                  ? 'bg-green-100 text-green-700'
                  : 'bg-red-100 text-red-700'
              ]">
                {{ item?.is_available ? '✓ Available' : '✗ Not Available' }}
              </span>
            </div>

            <!-- Add to Cart Button -->
            <button
              @click="addToCart"
              :disabled="!item?.is_available"
              class="w-full bg-blue-600 hover:bg-blue-700 disabled:bg-gray-400 text-white font-bold py-3 px-4 rounded-lg transition-colors"
            >
              Add to Cart
            </button>
          </div>
        </div>

        <!-- Right: Description and Reviews -->
        <div class="space-y-6">
          <!-- Description Card -->
          <div class="bg-white rounded-lg p-6 shadow">
            <h2 class="text-xl font-bold text-gray-900 mb-4">Description</h2>
            <p class="text-gray-700 leading-relaxed">
              {{ item?.description || 'No description available' }}
            </p>
          </div>

          <!-- Reviews Tabs -->
          <div class="bg-white rounded-lg shadow overflow-hidden">
            <!-- Tabs -->
            <div class="border-b border-gray-200 flex">
              <button
                @click="reviewTab = 'reviews'"
                :class="[
                  'flex-1 px-4 py-3 font-semibold text-center transition-colors',
                  reviewTab === 'reviews'
                    ? 'border-b-2 border-blue-600 text-blue-600'
                    : 'text-gray-600 hover:text-gray-900'
                ]"
              >
                Reviews ({{ stats?.total_reviews || 0 }})
              </button>
              <button
                v-if="canReview"
                @click="reviewTab = 'submit'"
                :class="[
                  'flex-1 px-4 py-3 font-semibold text-center transition-colors',
                  reviewTab === 'submit'
                    ? 'border-b-2 border-blue-600 text-blue-600'
                    : 'text-gray-600 hover:text-gray-900'
                ]"
              >
                Write Review
              </button>
            </div>

            <!-- Tab Content -->
            <div class="p-6">
              <!-- Reviews List Tab -->
              <div v-show="reviewTab === 'reviews'">
                <PublicReviewsList :menu-item-id="menuItemId" />
              </div>

              <!-- Submit Review Tab -->
              <div v-show="reviewTab === 'submit'" v-if="canReview">
                <ReviewSubmissionForm
                  :menu-item-id="menuItemId"
                  :guest-id="currentUser?.id || ''"
                  :order-id="selectedOrderId || ''"
                  :menu-item="item"
                  @success="onReviewSuccess"
                  @cancel="reviewTab = 'reviews'"
                />
              </div>

              <!-- Not Eligible Message -->
              <div v-if="!canReview && !item?.is_available" class="text-center py-8">
                <p class="text-gray-600">Item not available for review</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import reviewService from '@/services/reviewService'
import { ReviewStats } from '@/types/review'
import PublicReviewsList from '@/components/reviews/PublicReviewsList.vue'
import ReviewSubmissionForm from '@/components/reviews/ReviewSubmissionForm.vue'

const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()

const menuItemId = computed(() => route.params.id as string)
const item = ref<any>(null)
const stats = ref<ReviewStats | null>(null)
const currentUser = authStore.user
const selectedOrderId = ref<string | null>(null)
const reviewTab = ref<'reviews' | 'submit'>('reviews')

// Check if user can review
const canReview = computed(() => {
  return currentUser?.id && item.value?.is_available
})

const goBack = () => {
  router.back()
}

const addToCart = () => {
  // Emit to parent or use store
  console.log('Added to cart:', item.value)
}

const onReviewSuccess = (review: any) => {
  reviewTab.value = 'reviews'
  // Reload stats
  loadStats()
}

const loadItem = async () => {
  try {
    // This would come from your menu store
    // For now, using placeholder
    item.value = {
      id: menuItemId.value,
      name: 'Menu Item',
      description: 'Item description',
      price: 12.99,
      image: null,
      category: 'Main Course',
      is_available: true
    }
  } catch (error) {
    console.error('Failed to load item:', error)
  }
}

const loadStats = async () => {
  try {
    stats.value = await reviewService.getMenuItemStats(menuItemId.value)
  } catch (error) {
    console.error('Failed to load stats:', error)
  }
}

onMounted(() => {
  loadItem()
  loadStats()
})
</script>

<style scoped>
.menu-item-detail-page {
  background-color: #f3f4f6;
}
</style>
