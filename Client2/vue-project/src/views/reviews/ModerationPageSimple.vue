<template>
  <DashboardLayout>
    <template #header>
      <div class="mb-6">
        <h1 class="text-3xl font-bold text-slate-900 mb-2">
          {{ languageStore.t('review_moderation_dashboard', 'Review Moderation') }}
        </h1>
        <p class="text-slate-600">
          {{
            languageStore.t(
              'review_moderation_subtitle',
              'Review and approve pending guest reviews',
            )
          }}
          ({{ pendingReviews.length }} {{ languageStore.t('pending', 'pending') }})
        </p>
      </div>
    </template>

    <div class="max-w-6xl">
      <!-- Loading State -->
      <div v-if="loading" class="space-y-4">
        <div
          v-for="i in 3"
          :key="i"
          class="bg-white rounded-lg border border-slate-200 p-6 animate-pulse"
        >
          <div class="h-4 bg-slate-200 rounded w-1/3 mb-4"></div>
          <div class="h-3 bg-slate-200 rounded w-2/3 mb-2"></div>
          <div class="h-3 bg-slate-200 rounded w-1/2"></div>
        </div>
      </div>

      <!-- Reviews List -->
      <div v-else-if="pendingReviews.length > 0" class="space-y-4">
        <div
          v-for="review in pendingReviews"
          :key="review.id"
          class="bg-white rounded-lg border border-slate-200 shadow-sm p-6 hover:shadow-md transition-shadow"
        >
          <!-- Header -->
          <div class="flex items-start justify-between mb-4">
            <div class="flex-1">
              <h3 class="text-lg font-semibold text-slate-900 flex items-center gap-2">
                <Utensils :size="20" class="text-blue-600" />
                {{ review.menu_item?.name || 'Unknown Item' }}
              </h3>
              <p class="text-sm text-slate-600 mt-2 flex items-center gap-2">
                <User :size="16" class="text-slate-400" />
                <strong
                  >{{ review.guest?.first_name }}
                  {{ review.guest?.last_name || 'Anonymous Guest' }}</strong
                >
                <span class="text-slate-500">({{ review.guest?.email || 'N/A' }})</span>
              </p>
              <p class="text-xs text-slate-500 mt-1 flex items-center gap-2">
                <Calendar :size="14" class="text-slate-400" />
                {{ formatDate(review.created_at) }}
              </p>
            </div>
            <span
              class="px-3 py-2 bg-yellow-100 text-yellow-800 rounded-full text-sm font-semibold whitespace-nowrap ml-4 flex items-center gap-2"
            >
              <Clock :size="16" />
              {{ languageStore.t(review.status || 'pending', review.status || 'pending') }}
            </span>
          </div>

          <!-- Rating -->
          <div
            class="flex items-center gap-3 mb-4 p-3 bg-amber-50 rounded-lg border border-amber-100"
          >
            <div class="flex gap-0.5">
              <Star
                v-for="i in 5"
                :key="i"
                :size="20"
                :class="i <= review.rating ? 'fill-yellow-400 text-yellow-400' : 'text-gray-300'"
              />
            </div>
            <span class="text-sm font-semibold text-slate-700"
              >{{ review.rating }}/5 {{ languageStore.t('rating', 'Rating') }}</span
            >
          </div>

          <!-- Review Text -->
          <div
            v-if="review.review_text"
            class="mb-4 p-4 bg-slate-50 rounded-lg border border-slate-200"
          >
            <div class="flex items-start gap-2 mb-2">
              <MessageCircle :size="16" class="text-blue-600 mt-0.5 flex-shrink-0" />
              <p class="text-slate-700">{{ review.review_text }}</p>
            </div>
          </div>

          <!-- Actions -->
          <div class="flex flex-wrap gap-3 pt-4 border-t border-slate-200">
            <button
              @click="handleApproveReview(review.id)"
              :disabled="approving === review.id"
              class="flex items-center gap-2 px-4 py-2 bg-green-600 hover:bg-green-700 disabled:bg-gray-400 text-white font-semibold rounded-lg transition-colors cursor-pointer"
            >
              <CheckCircle v-if="approving !== review.id" :size="18" />
              <Loader v-else :size="18" class="animate-spin" />
              {{
                approving === review.id
                  ? languageStore.t('approving', 'Approving...')
                  : languageStore.t('approve', 'Approve')
              }}
            </button>
            <button
              @click="handleRejectReview(review.id)"
              :disabled="rejecting === review.id"
              class="flex items-center gap-2 px-4 py-2 bg-red-600 hover:bg-red-700 disabled:bg-gray-400 text-white font-semibold rounded-lg transition-colors cursor-pointer"
            >
              <XCircle v-if="rejecting !== review.id" :size="18" />
              <Loader v-else :size="18" class="animate-spin" />
              {{
                rejecting === review.id
                  ? languageStore.t('rejecting', 'Rejecting...')
                  : languageStore.t('reject', 'Reject')
              }}
            </button>
          </div>
        </div>
      </div>

      <!-- Empty State -->
      <div v-else class="bg-white rounded-lg border border-slate-200 shadow-sm p-6">
        <div class="text-center py-16">
          <CheckCircle :size="64" class="mx-auto text-green-500 mb-4 opacity-50" />
          <p class="text-slate-900 mb-2 text-2xl font-bold">
            {{ languageStore.t('no_pending_reviews', 'No pending reviews to moderate!') }}
          </p>
          <p class="text-slate-600 mb-8 text-lg">
            {{
              languageStore.t(
                'all_reviews_processed',
                'All reviews have been processed successfully.',
              )
            }}
          </p>
          <router-link
            to="/reviews/analytics"
            class="inline-flex items-center gap-2 px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-semibold"
          >
            <BarChart3 :size="20" />
            {{ languageStore.t('view_analytics', 'View Analytics') }}
          </router-link>
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useReviewStore } from '@/stores/reviewStore'
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import { useLanguageStore } from '@/stores/language'
import {
  Star,
  Clock,
  User,
  Calendar,
  Utensils,
  MessageCircle,
  CheckCircle,
  XCircle,
  Loader,
  BarChart3,
} from 'lucide-vue-next'

const reviewStore = useReviewStore()
const languageStore = useLanguageStore()
const loading = ref(false)
const approving = ref<string | null>(null)
const rejecting = ref<string | null>(null)

const pendingReviews = ref<any[]>([])

const formatDate = (dateString: string) => {
  const date = new Date(dateString)
  return date.toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

const fetchPendingReviews = async () => {
  loading.value = true
  try {
    // Fetch moderator reviews (pending status)
    await reviewStore.fetchModeratorReviews('pending', 1)
    pendingReviews.value = reviewStore.pendingReviews
  } catch (error) {
    console.error('Failed to fetch pending reviews:', error)
  } finally {
    loading.value = false
  }
}

const handleApproveReview = async (reviewId: string) => {
  if (confirm('Approve this review?')) {
    approving.value = reviewId
    try {
      await reviewStore.approveReview(reviewId)
      // Remove from pending list
      pendingReviews.value = pendingReviews.value.filter((r) => r.id !== reviewId)
    } catch (error) {
      console.error('Failed to approve review:', error)
      alert('Failed to approve review: ' + (error as any).message)
    } finally {
      approving.value = null
    }
  }
}

const handleRejectReview = async (reviewId: string) => {
  if (confirm('Reject this review?')) {
    rejecting.value = reviewId
    try {
      await reviewStore.rejectReview(reviewId)
      // Remove from pending list
      pendingReviews.value = pendingReviews.value.filter((r) => r.id !== reviewId)
    } catch (error) {
      console.error('Failed to reject review:', error)
      alert('Failed to reject review: ' + (error as any).message)
    } finally {
      rejecting.value = null
    }
  }
}

onMounted(() => {
  fetchPendingReviews()
})
</script>

<style scoped></style>
