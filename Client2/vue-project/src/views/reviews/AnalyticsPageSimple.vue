<template>
  <DashboardLayout>
    <template #header>
      <div class="mb-6">
        <div class="flex items-center gap-2">
          <h1 class="text-3xl font-bold text-slate-900 dark:text-white mb-2">{{ languageStore.t('review_analytics', 'Review Analytics') }}</h1>
          <span v-if="hotelStore.hotelName" class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300 border border-blue-200 dark:border-blue-700/50">
            <Building2 class="w-3 h-3" />
            {{ hotelStore.hotelName }}
          </span>
        </div>
        <p class="text-slate-600 dark:text-slate-400">{{ languageStore.t('review_analytics_subtitle', 'View review metrics and statistics') }}</p>
      </div>
    </template>

    <div class="max-w-6xl">
      <!-- Key Metrics -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
        <div class="bg-white rounded-lg border border-slate-200 shadow-sm p-6 hover:shadow-md transition-shadow">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-slate-600 text-sm font-medium">{{ languageStore.t('total_reviews', 'Total Reviews') }}</p>
              <p class="text-3xl font-bold text-slate-900 mt-2">{{ totalReviews }}</p>
            </div>
            <MessageSquare class="w-10 h-10 text-blue-500 opacity-30" />
          </div>
        </div>
        <div class="bg-white rounded-lg border border-slate-200 shadow-sm p-6 hover:shadow-md transition-shadow">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-slate-600 text-sm font-medium">{{ languageStore.t('pending', 'Pending') }}</p>
              <p class="text-3xl font-bold text-yellow-600 mt-2">{{ pendingCount }}</p>
            </div>
            <Clock class="w-10 h-10 text-yellow-500 opacity-30" />
          </div>
        </div>
        <div class="bg-white rounded-lg border border-slate-200 shadow-sm p-6 hover:shadow-md transition-shadow">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-slate-600 text-sm font-medium">{{ languageStore.t('approved', 'Approved') }}</p>
              <p class="text-3xl font-bold text-green-600 mt-2">{{ approvedCount }}</p>
            </div>
            <CheckCircle class="w-10 h-10 text-green-500 opacity-30" />
          </div>
        </div>
        <div class="bg-white rounded-lg border border-slate-200 shadow-sm p-6 hover:shadow-md transition-shadow">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-slate-600 text-sm font-medium">{{ languageStore.t('avg_rating', 'Average Rating') }}</p>
              <p class="text-3xl font-bold text-amber-600 mt-2">{{ averageRating }}</p>
            </div>
            <Star class="w-10 h-10 text-amber-500 opacity-30" />
          </div>
        </div>
      </div>

      <!-- Status Breakdown -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Status Chart -->
        <div class="bg-white rounded-lg border border-slate-200 shadow-sm p-6">
          <div class="flex items-center gap-2 mb-4">
            <BarChart3 class="w-5 h-5 text-blue-600" />
            <h2 class="text-lg font-semibold text-slate-900">{{ languageStore.t('review_status', 'Review Status') }}</h2>
          </div>
          <div class="space-y-4">
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-3">
                <Clock class="w-4 h-4 text-yellow-500" />
                <span class="text-slate-700 font-medium">{{ languageStore.t('pending', 'Pending') }}</span>
              </div>
              <span class="px-3 py-1 bg-yellow-100 text-yellow-800 rounded-full text-sm font-bold">{{ pendingCount }}</span>
            </div>
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-3">
                <CheckCircle class="w-4 h-4 text-green-500" />
                <span class="text-slate-700 font-medium">{{ languageStore.t('approved', 'Approved') }}</span>
              </div>
              <span class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-sm font-bold">{{ approvedCount }}</span>
            </div>
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-3">
                <XCircle class="w-4 h-4 text-red-500" />
                <span class="text-slate-700 font-medium">{{ languageStore.t('rejected', 'Rejected') }}</span>
              </div>
              <span class="px-3 py-1 bg-red-100 text-red-800 rounded-full text-sm font-bold">{{ rejectedCount }}</span>
            </div>
          </div>
        </div>

        <!-- Rating Distribution -->
        <div class="bg-white rounded-lg border border-slate-200 shadow-sm p-6">
          <div class="flex items-center gap-2 mb-4">
            <TrendingUp class="w-5 h-5 text-blue-600" />
            <h2 class="text-lg font-semibold text-slate-900">{{ languageStore.t('rating_distribution', 'Rating Distribution') }}</h2>
          </div>
          <div class="space-y-3">
            <div v-for="rating in [5, 4, 3, 2, 1]" :key="rating" class="flex items-center gap-2">
              <div class="flex gap-0.5 w-12">
                <Star v-for="i in 5" :key="i" :size="14" :class="i <= rating ? 'fill-yellow-400 text-yellow-400' : 'text-gray-300'" />
              </div>
              <div class="flex-1 h-6 bg-slate-100 rounded-full overflow-hidden">
                <div 
                  class="h-full bg-gradient-to-r from-blue-500 to-blue-400 transition-all" 
                  :style="{ width: getPercentage(rating) + '%' }"
                ></div>
              </div>
              <span class="text-sm font-semibold text-slate-700 w-8 text-right">{{ getRatingCount(rating) }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Recent Reviews -->
      <div class="mt-6 bg-white rounded-lg border border-slate-200 shadow-sm p-6">
        <div class="flex items-center gap-2 mb-4">
          <MessageSquare class="w-5 h-5 text-blue-600" />
          <h2 class="text-lg font-semibold text-slate-900">{{ languageStore.t('recent_reviews', 'Recent Reviews') }}</h2>
        </div>
        <div v-if="recentReviews.length > 0" class="space-y-3">
          <div v-for="review in recentReviews.slice(0, 5)" :key="review.id" class="flex items-start justify-between p-4 bg-gradient-to-r from-slate-50 to-slate-100 rounded-lg border border-slate-200 hover:shadow-md transition-all">
            <div class="flex-1">
              <div class="flex items-center gap-2">
                <CheckCircle v-if="review.status === 'approved'" :size="16" class="text-green-600" />
                <Clock v-else-if="review.status === 'pending'" :size="16" class="text-yellow-600" />
                <XCircle v-else :size="16" class="text-red-600" />
                <p class="font-semibold text-slate-900">{{ review.menu_item?.name }}</p>
              </div>
              <p class="text-sm text-slate-600 mt-2 flex items-center gap-2">
                <User :size="14" class="text-slate-400" />
                {{ review.guest?.first_name }} {{ review.guest?.last_name }}
                <Calendar :size="14" class="text-slate-400 ml-2" />
                {{ formatDate(review.created_at) }}
              </p>
            </div>
            <div class="flex items-center gap-3 ml-4">
              <div class="flex gap-0.5">
                <Star v-for="i in 5" :key="i" :size="16" :class="i <= review.rating ? 'fill-yellow-400 text-yellow-400' : 'text-gray-300'" />
              </div>
              <span :class="[
                'px-3 py-1 rounded-full text-xs font-semibold whitespace-nowrap flex items-center gap-1',
                review.status === 'pending' ? 'bg-yellow-100 text-yellow-800' :
                review.status === 'approved' ? 'bg-green-100 text-green-800' :
                'bg-red-100 text-red-800'
              ]">
                <component :is="getStatusIcon(review.status)" :size="14" />
                {{ languageStore.t(review.status, review.status) }}
              </span>
            </div>
          </div>
        </div>
        <div v-else class="text-center py-12">
          <InboxIcon :size="48" class="mx-auto text-slate-300 mb-3" />
          <p class="text-slate-600 text-lg font-medium">{{ languageStore.t('no_reviews_yet', 'No reviews yet.') }}</p>
          <p class="text-slate-500 text-sm mt-1">{{ languageStore.t('guest_reviews_appear_here', 'Guest reviews will appear here as they are submitted.') }}</p>
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import { useReviewStore } from '@/stores/reviewStore'
import { useHotelStore } from '@/stores/hotelStore'
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import { useLanguageStore } from '@/stores/language'
import {
  MessageSquare,
  Clock,
  CheckCircle,
  Star,
  BarChart3,
  TrendingUp,
  XCircle,
  User,
  InboxIcon,
  Loader,
  Calendar,
  Building2,
} from 'lucide-vue-next'

const reviewStore = useReviewStore()
const hotelStore = useHotelStore()
const languageStore = useLanguageStore()

const pendingCount = computed(() => reviewStore.pendingReviews.length)
const approvedCount = computed(() => reviewStore.approvedReviews.length)
const rejectedCount = computed(() => reviewStore.rejectedReviews.length)
const totalReviews = computed(() => pendingCount.value + approvedCount.value + rejectedCount.value)

const allReviews = computed(() => [
  ...reviewStore.pendingReviews,
  ...reviewStore.approvedReviews,
  ...reviewStore.rejectedReviews
])

const averageRating = computed(() => {
  if (allReviews.value.length === 0) return '0.0'
  const sum = allReviews.value.reduce((acc, r) => acc + (r.rating || 0), 0)
  return (sum / allReviews.value.length).toFixed(1)
})

const recentReviews = computed(() => {
  return [...allReviews.value].sort((a, b) => {
    return new Date(b.created_at).getTime() - new Date(a.created_at).getTime()
  })
})

const getRatingCount = (rating: number) => {
  return allReviews.value.filter(r => r.rating === rating).length
}

const getPercentage = (rating: number) => {
  if (allReviews.value.length === 0) return 0
  const count = getRatingCount(rating)
  return (count / allReviews.value.length) * 100
}

const getStatusIcon = (status: string) => {
  switch (status) {
    case 'pending':
      return Clock
    case 'approved':
      return CheckCircle
    case 'rejected':
      return XCircle
    default:
      return Clock
  }
}

const formatDate = (dateString: string) => {
  const date = new Date(dateString)
  return date.toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

onMounted(() => {
  // Fetch all reviews for analytics
  reviewStore.fetchModeratorReviews(undefined, 1)
})

watch(() => hotelStore.hotelId, () => {
  reviewStore.fetchModeratorReviews(undefined, 1)
})
</script>

<style scoped>
</style>
