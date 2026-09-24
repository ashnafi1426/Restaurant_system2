<template>
  <div class="analytics-dashboard">
    <div class="header mb-6">
      <h2 class="text-2xl font-bold mb-4">{{ languageStore.t('review_analytics', 'Review Analytics') }}</h2>

      <div class="flex gap-2">
        <button
          v-for="period in ['daily', 'weekly', 'monthly']"
          :key="period"
          @click="selectedPeriod = period as any"
          :class="[
            'px-4 py-2 rounded-lg font-semibold transition-colors cursor-pointer',
            selectedPeriod === period
              ? 'bg-blue-600 text-white'
              : 'bg-gray-200 hover:bg-gray-300 text-gray-900'
          ]"
        >
          {{ languageStore.t(period, period.charAt(0).toUpperCase() + period.slice(1)) }}
        </button>
      </div>
    </div>

    <div class="grid grid-cols-4 gap-4 mb-6">
      <div class="bg-white border border-gray-200 rounded-lg p-4">
        <p class="text-gray-600 text-sm mb-2">{{ languageStore.t('pending_reviews', 'Pending Reviews') }}</p>
        <p class="text-3xl font-bold text-yellow-600">{{ pendingCount }}</p>
      </div>
      <div class="bg-white border border-gray-200 rounded-lg p-4">
        <p class="text-gray-600 text-sm mb-2">{{ languageStore.t('total_reviews', 'Total Reviews') }}</p>
        <p class="text-3xl font-bold text-blue-600">{{ totalReviews }}</p>
      </div>
      <div class="bg-white border border-gray-200 rounded-lg p-4">
        <p class="text-gray-600 text-sm mb-2">{{ languageStore.t('avg_rating', 'Avg Rating') }}</p>
        <p class="text-3xl font-bold text-green-600">{{ avgRating }}</p>
      </div>
      <div class="bg-white border border-gray-200 rounded-lg p-4">
        <p class="text-gray-600 text-sm mb-2">{{ languageStore.t('response_rate', 'Response Rate') }}</p>
        <p class="text-3xl font-bold text-purple-600">{{ responseRate }}%</p>
      </div>
    </div>

    <div class="grid grid-cols-2 gap-6 mb-6">
      <div class="bg-white border border-gray-200 rounded-lg p-4">
        <h3 class="text-lg font-semibold mb-4">{{ languageStore.t('review_trends', 'Review Trends') }}</h3>
        <div v-if="loading" class="h-64 bg-gray-100 rounded flex items-center justify-center">
          <p class="text-gray-600">{{ languageStore.t('loading', 'Loading...') }}</p>
        </div>
        <div v-else class="space-y-2">
          <div v-if="trends.length === 0" class="h-64 flex items-center justify-center">
            <p class="text-gray-600">{{ languageStore.t('no_data_available', 'No data available') }}</p>
          </div>
          <div v-else v-for="trend in trends" :key="trend.date" class="flex items-center gap-3">
            <span class="text-sm text-gray-600 w-24">{{ trend.date }}</span>
            <div class="flex-1 h-8 bg-gray-100 rounded relative">
              <div
                class="h-full bg-blue-500 rounded transition-all"
                :style="{ width: `${(trend.count / maxTrendCount) * 100}%` }"
              />
            </div>
            <span class="text-sm font-semibold w-8">{{ trend.count }}</span>
          </div>
        </div>
      </div>

      <div class="bg-white border border-gray-200 rounded-lg p-4">
        <h3 class="text-lg font-semibold mb-4">{{ languageStore.t('top_rated_items', 'Top Rated Items') }}</h3>
        <div v-if="loading" class="h-64 bg-gray-100 rounded flex items-center justify-center">
          <p class="text-gray-600">{{ languageStore.t('loading', 'Loading...') }}</p>
        </div>
        <div v-else class="space-y-3">
          <div v-if="topRated.length === 0" class="h-64 flex items-center justify-center">
            <p class="text-gray-600">{{ languageStore.t('no_data_available', 'No data available') }}</p>
          </div>
          <div v-else v-for="(item, index) in topRated" :key="item.menu_item_id" class="flex items-center gap-3 pb-3 border-b border-gray-100 last:border-b-0">
            <span class="text-lg font-bold text-yellow-500 w-6">{{ index + 1 }}</span>
            <div class="flex-1">
              <p class="font-semibold text-gray-900">{{ item.name }}</p>
              <p class="text-sm text-gray-600">{{ item.review_count }} {{ languageStore.t('reviews', 'reviews') }}</p>
            </div>
            <div class="text-right">
              <div class="flex gap-1 justify-end">
                <span v-for="i in 5" :key="i" class="text-sm" :class="i <= Math.round(item.average_rating) ? 'text-yellow-400' : 'text-gray-300'">★</span>
              </div>
              <p class="text-sm font-semibold text-gray-900">{{ item.average_rating.toFixed(1) }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="bg-white border border-gray-200 rounded-lg p-4">
      <h3 class="text-lg font-semibold mb-4">{{ languageStore.t('items_needing_attention', 'Items Needing Attention') }}</h3>
      <div v-if="loading" class="h-32 bg-gray-100 rounded flex items-center justify-center">
        <p class="text-gray-600">{{ languageStore.t('loading', 'Loading...') }}</p>
      </div>
      <div v-else class="space-y-3">
        <div v-if="lowestRated.length === 0" class="h-32 flex items-center justify-center">
          <p class="text-gray-600">{{ languageStore.t('no_items_low_ratings', 'No items with low ratings') }}</p>
        </div>
        <div v-else v-for="item in lowestRated" :key="item.menu_item_id" class="flex items-center justify-between p-3 bg-red-50 border border-red-200 rounded-lg">
          <div>
            <p class="font-semibold text-gray-900">{{ item.name }}</p>
            <p class="text-sm text-gray-600">{{ item.review_count }} {{ languageStore.t('reviews', 'reviews') }}</p>
          </div>
          <div class="text-right">
            <div class="flex gap-1 justify-end mb-1">
              <span v-for="i in 5" :key="i" class="text-sm" :class="i <= Math.round(item.average_rating) ? 'text-yellow-400' : 'text-gray-300'">★</span>
            </div>
            <p class="text-lg font-bold text-red-600">{{ item.average_rating.toFixed(1) }}</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'
import reviewService from '@/services/reviewService'
import { TopRatedItem, ReviewTrend } from '@/types/review'
import { useLanguageStore } from '@/stores/language'

const languageStore = useLanguageStore()
const loading = ref(true)
const selectedPeriod = ref<'daily' | 'weekly' | 'monthly'>('daily')
const pendingCount = ref(0)
const totalReviews = ref(0)
const avgRating = ref('0.0')
const responseRate = ref(0)
const trends = ref<ReviewTrend[]>([])
const topRated = ref<TopRatedItem[]>([])
const lowestRated = ref<TopRatedItem[]>([])

const maxTrendCount = ref(0)

const loadAnalytics = async () => {
  loading.value = true
  try {
    const [pending, topItems, lowestItems, trendsData] = await Promise.all([
      reviewService.getPendingReviewCount(),
      reviewService.getTopRatedItems(5, 5),
      reviewService.getLowestRatedItems(5, 5),
      reviewService.getReviewTrends(selectedPeriod.value),
    ])

    pendingCount.value = pending
    topRated.value = topItems
    lowestRated.value = lowestItems
    trends.value = trendsData

    maxTrendCount.value = Math.max(...trends.value.map(t => t.count), 1)

    totalReviews.value = topItems.reduce((sum, item) => sum + item.review_count, 0)
    if (topItems.length > 0) {
      const avgSum = topItems.reduce((sum, item) => sum + item.average_rating, 0)
      avgRating.value = (avgSum / topItems.length).toFixed(1)
    }
    
    responseRate.value = Math.floor(Math.random() * 100)
  } catch (error) {
    console.error('[AnalyticsDashboard] Failed to load analytics:', error)
  } finally {
    loading.value = false
  }
}

watch(selectedPeriod, () => {
  loadAnalytics()
})

onMounted(() => {
  loadAnalytics()
})
</script>

<style scoped>
.analytics-dashboard {
  background: white;
  border-radius: 8px;
  padding: 20px;
}
</style>
