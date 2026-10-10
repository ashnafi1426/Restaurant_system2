<template>
  <div class="manager-analytics-widget bg-white rounded-lg shadow p-6">
    <div class="flex items-center justify-between mb-6">
      <div>
        <h3 class="text-lg font-bold text-gray-900">Review Analytics</h3>
        <p class="text-xs text-gray-500 mt-1">Quick performance overview</p>
      </div>
      <router-link
        to="/reviews/analytics"
        class="inline-block px-3 py-1 text-sm bg-blue-100 text-blue-700 hover:bg-blue-200 font-semibold rounded transition-colors"
      >
        Full Report
      </router-link>
    </div>

    <div class="grid grid-cols-2 gap-4 mb-6">
      <div
        class="bg-gradient-to-br from-yellow-50 to-yellow-100 p-4 rounded-lg border border-yellow-200"
      >
        <p class="text-xs text-gray-600 mb-2">Overall Rating</p>
        <div class="flex items-end gap-2">
          <span class="text-3xl font-bold text-yellow-600">{{ overallRating }}</span>
          <span class="text-2xl mb-1">★</span>
        </div>
        <p class="text-xs text-gray-600 mt-2">{{ totalReviews }} reviews</p>
      </div>

      <div
        class="bg-gradient-to-br from-green-50 to-green-100 p-4 rounded-lg border border-green-200"
      >
        <p class="text-xs text-gray-600 mb-2">Top Rated Item</p>
        <p class="text-sm font-semibold text-gray-900 truncate">{{ topItem?.name || 'N/A' }}</p>
        <div class="flex items-center gap-1 mt-2">
          <span class="text-lg font-bold text-green-600">{{
            topItem?.average_rating?.toFixed(1) || '0'
          }}</span>
          <span class="text-xs text-yellow-500">★ ({{ topItem?.review_count || 0 }})</span>
        </div>
      </div>

      <div class="bg-gradient-to-br from-blue-50 to-blue-100 p-4 rounded-lg border border-blue-200">
        <p class="text-xs text-gray-600 mb-2">Response Rate</p>
        <p class="text-3xl font-bold text-blue-600">{{ responseRate }}%</p>
        <p class="text-xs text-gray-600 mt-2">Responded reviews</p>
      </div>

      <div class="bg-gradient-to-br from-red-50 to-red-100 p-4 rounded-lg border border-red-200">
        <p class="text-xs text-gray-600 mb-2">Needs Attention</p>
        <p class="text-sm font-semibold text-gray-900 truncate">{{ lowestItem?.name || 'N/A' }}</p>
        <div class="flex items-center gap-1 mt-2">
          <span class="text-lg font-bold text-red-600">{{
            lowestItem?.average_rating?.toFixed(1) || '0'
          }}</span>
          <span class="text-xs text-yellow-500">★ ({{ lowestItem?.review_count || 0 }})</span>
        </div>
      </div>
    </div>

    <div class="border-t pt-4">
      <div class="flex items-center justify-between mb-3">
        <p class="text-sm font-semibold text-gray-900">Review Trends</p>
        <div class="flex gap-2">
          <button
            v-for="period in ['daily', 'weekly', 'monthly']"
            :key="period"
            @click="selectedPeriod = period as any"
            :class="[
              'px-2 py-1 text-xs font-semibold rounded-transition-colors',
              selectedPeriod === period
                ? 'bg-blue-600 text-white'
                : 'bg-gray-100 text-gray-700 hover:bg-gray-200',
            ]"
          >
            {{ period.charAt(0).toUpperCase() + period.slice(1) }}
          </button>
        </div>
      </div>

      <div v-if="trends.length > 0" class="space-y-2">
        <div
          v-for="(trend, index) in trends.slice(0, 5)"
          :key="index"
          class="flex items-center gap-2"
        >
          <span class="text-xs w-12 text-gray-600">{{ trend.date }}</span>
          <div class="flex-1 h-6 bg-gray-100 rounded relative overflow-hidden">
            <div
              class="h-full bg-gradient-to-r from-blue-400 to-blue-600 transition-all"
              :style="{ width: `${(trend.count / maxTrendCount) * 100}%` }"
            />
          </div>
          <span class="text-xs font-semibold text-gray-700 w-6 text-right">{{ trend.count }}</span>
        </div>
      </div>

      <div v-else class="text-center py-6 text-gray-500">
        <p class="text-sm">No trend data available</p>
      </div>
    </div>

    <div class="flex gap-2 mt-6 pt-6 border-t">
      <router-link
        to="/reviews/analytics"
        class="flex-1 text-center px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded transition-colors text-sm"
      >
        View Full Analytics
      </router-link>
      <router-link
        to="/reviews/moderation"
        class="flex-1 text-center px-3 py-2 border border-gray-300 hover:bg-gray-50 text-gray-700 font-semibold rounded transition-colors text-sm"
      >
        Moderate Reviews
      </router-link>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import reviewService from '@/services/reviewService'
import type { TopRatedItem, ReviewTrend } from '@/types/review'

const selectedPeriod = ref<'daily' | 'weekly' | 'monthly'>('daily')
const topItems = ref<TopRatedItem[]>([])
const lowestItems = ref<TopRatedItem[]>([])
const trends = ref<ReviewTrend[]>([])
const totalReviews = ref(0)
const overallRating = ref('0.0')
const responseRate = ref(0)

const topItem = computed(() => topItems.value[0] || null)
const lowestItem = computed(() => lowestItems.value[0] || null)
const maxTrendCount = computed(() => Math.max(...trends.value.map((t) => t.count), 1))

const loadData = async () => {
  try {
    const [top, lowest, trendData, overallStats] = await Promise.all([
      reviewService.getTopRatedItems(5, 5),
      reviewService.getLowestRatedItems(5, 5),
      reviewService.getReviewTrends(selectedPeriod.value),
      reviewService.getOverallStatistics().catch(() => null),
    ])

    topItems.value = top
    lowestItems.value = lowest
    trends.value = trendData

    if (overallStats) {
      totalReviews.value = overallStats.total_reviews || 0
      overallRating.value = (overallStats.average_rating || 0).toFixed(1)
      const total =
        (overallStats.approved_reviews || 0) +
        (overallStats.rejected_reviews || 0) +
        (overallStats.pending_reviews || 0)
      responseRate.value =
        total > 0
          ? Math.round(
              ((overallStats.approved_reviews + overallStats.rejected_reviews) / total) * 100,
            )
          : 0
    } else {
      totalReviews.value = top.reduce((sum, item) => sum + item.review_count, 0)
      if (top.length > 0) {
        const avgSum = top.reduce((sum, item) => sum + item.average_rating, 0)
        overallRating.value = (avgSum / top.length).toFixed(1)
      }
      responseRate.value = 0
    }
  } catch (error) {
    console.error('[ManagerAnalyticsWidget] Error loading analytics data:', error)
  }
}

onMounted(() => {
  loadData()
})
</script>

<style scoped></style>
