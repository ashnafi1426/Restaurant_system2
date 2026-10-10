<template>
  <div class="guest-review-page">
    <div class="max-w-6xl mx-auto">
      <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">
          {{ languageStore.t('my_reviews', 'My Reviews') }}
        </h1>
        <p class="text-gray-600">
          {{
            languageStore.t(
              'manage_reviews_and_account',
              'Manage your menu item reviews and see how your feedback helps',
            )
          }}
        </p>
      </div>

      <div class="flex gap-2 mb-6 border-b border-gray-200">
        <button
          @click="activeTab = 'write'"
          :class="[
            'px-4 py-2 font-semibold border-b-2 transition-colors cursor-pointer',
            activeTab === 'write'
              ? 'border-blue-600 text-blue-600'
              : 'border-transparent text-gray-600 hover:text-gray-900',
          ]"
        >
          {{ languageStore.t('write_review', 'Write a Review') }}
        </button>
        <button
          @click="activeTab = 'my-reviews'"
          :class="[
            'px-4 py-2 font-semibold border-b-2 transition-colors cursor-pointer',
            activeTab === 'my-reviews'
              ? 'border-blue-600 text-blue-600'
              : 'border-transparent text-gray-600 hover:text-gray-900',
          ]"
        >
          {{ languageStore.t('my_reviews', 'My Reviews') }} ({{ guestReviews.length }})
        </button>
      </div>

      <div v-show="activeTab === 'write'" class="bg-white rounded-lg border border-gray-200 p-6">
        <div v-if="!selectedItem">
          <EligibleItemsList :guest-id="currentUser?.id || ''" @select="selectedItem = $event" />
        </div>
        <div v-else class="space-y-4">
          <button
            @click="selectedItem = null"
            class="text-blue-600 hover:text-blue-700 font-semibold text-sm cursor-pointer"
          >
            ← {{ languageStore.t('back_to_items', 'Back to Items') }}
          </button>
          <ReviewSubmissionForm
            :menu-item="selectedItem"
            :guest-id="currentUser?.id || ''"
            :order-id="selectedItem.order_id"
            :menu-item-id="selectedItem.id"
            @success="onReviewSuccess"
            @cancel="selectedItem = null"
          />
        </div>
      </div>

      <div v-show="activeTab === 'my-reviews'" class="space-y-4">
        <div v-if="guestReviewsLoading" class="space-y-4">
          <div v-for="i in 3" :key="i" class="animate-pulse h-32 bg-gray-200 rounded"></div>
        </div>
        <div
          v-else-if="guestReviews.length === 0"
          class="text-center py-12 bg-white rounded-lg border border-gray-200"
        >
          <p class="text-gray-600 text-lg">
            {{ languageStore.t('no_reviews_written_yet', "You haven't written any reviews yet.") }}
          </p>
        </div>
        <div v-else class="space-y-4">
          <div
            v-for="review in guestReviews"
            :key="review.id"
            class="bg-white rounded-lg border border-gray-200 p-6"
          >
            <div class="flex items-start justify-between mb-4">
              <div>
                <h3 class="text-lg font-semibold text-gray-900">{{ review.menu_item?.name }}</h3>
                <p class="text-sm text-gray-600 mt-1">{{ formatDate(review.created_at) }}</p>
              </div>
              <span
                :class="[
                  'px-3 py-1 rounded-full text-sm font-semibold',
                  review.status === 'pending'
                    ? 'bg-yellow-100 text-yellow-800'
                    : review.status === 'approved'
                      ? 'bg-green-100 text-green-800'
                      : 'bg-red-100 text-red-800',
                ]"
              >
                {{ languageStore.t(review.status, review.status) }}
              </span>
            </div>

            <div class="flex gap-1 mb-3">
              <span
                v-for="i in 5"
                :key="i"
                class="text-lg"
                :class="i <= review.rating ? 'text-yellow-400' : 'text-gray-300'"
                >★</span
              >
            </div>

            <p v-if="review.review_text" class="text-gray-700 mb-4">{{ review.review_text }}</p>

            <div v-if="review.response" class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-4">
              <p class="text-sm font-semibold text-blue-900 mb-2">
                {{ languageStore.t('reply_from_management', 'Response from Management') }}
              </p>
              <p class="text-sm text-gray-700">{{ review.response.response_text }}</p>
            </div>

            <div class="flex gap-2">
              <button
                @click="editingReviewId = review.id"
                :disabled="review.status !== 'pending'"
                class="px-4 py-2 bg-blue-600 hover:bg-blue-700 disabled:bg-gray-400 text-white font-semibold rounded transition-colors cursor-pointer"
              >
                {{ languageStore.t('edit', 'Edit') }}
              </button>
              <button
                @click="deleteReview(review.id)"
                class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-semibold rounded transition-colors cursor-pointer"
              >
                {{ languageStore.t('delete', 'Delete') }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <Transition name="fade">
      <div
        v-if="showSuccess"
        class="fixed bottom-4 right-4 bg-green-500 text-white px-4 py-3 rounded-lg shadow-lg"
      >
        {{ successMessage }}
      </div>
    </Transition>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useReviewStore } from '@/stores/reviewStore'
import { useAuthStore } from '@/stores/auth'
import ReviewSubmissionForm from '@/components/reviews/ReviewSubmissionForm.vue'
import EligibleItemsList from '@/components/reviews/EligibleItemsList.vue'
import type { EligibleMenuItem } from '@/types/review'
import { useLanguageStore } from '@/stores/language'

const languageStore = useLanguageStore()

const reviewStore = useReviewStore()
const authStore = useAuthStore()

const activeTab = ref<'write' | 'my-reviews'>('my-reviews')
const selectedItem = ref<EligibleMenuItem | null>(null)
const editingReviewId = ref<string | null>(null)
const showSuccess = ref(false)
const successMessage = ref('')

const currentUser = authStore.user
const guestReviews = ref(reviewStore.guestReviews)
const guestReviewsLoading = ref(false)

const formatDate = (dateString: string) => {
  const date = new Date(dateString)
  return date.toLocaleDateString('en-US', {
    month: 'long',
    day: 'numeric',
    year: 'numeric',
  })
}

const onReviewSuccess = (review: any) => {
  guestReviews.value.unshift(review)
  selectedItem.value = null
  successMessage.value = 'Your review has been submitted!'
  showSuccess.value = true
  setTimeout(() => {
    showSuccess.value = false
  }, 3000)
}

const deleteReview = async (reviewId: string) => {
  if (!confirm('Are you sure you want to delete this review?')) return
  try {
    guestReviewsLoading.value = true
    await reviewStore.deleteGuestReview(reviewId)
    guestReviews.value = guestReviews.value.filter((r) => r.id !== reviewId)
    successMessage.value = 'Review deleted successfully'
    showSuccess.value = true
    setTimeout(() => {
      showSuccess.value = false
    }, 3000)
  } catch (error) {
    console.error('Failed to delete review:', error)
  } finally {
    guestReviewsLoading.value = false
  }
}

onMounted(() => {
  if (guestReviews.value.length === 0 && currentUser?.id) {
  }
})
</script>

<style scoped>
.guest-review-page {
  min-height: 100vh;
  background: #f9fafb;
  padding: 24px 0;
}

.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
