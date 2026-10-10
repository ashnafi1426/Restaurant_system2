<script setup lang="ts">
import { onMounted, computed, watch } from 'vue'
import { useManagerRevenueStore } from '@/stores/manager/revenueStore'
import { useHotelStore } from '@/stores/hotelStore'
import { useLanguageStore } from '@/stores/language'
import RevenueOverview from '@/components/manager/RevenueOverview.vue'
import DashboardLayout from '../../Layouts/DashboardLayout.vue'
import { Wallet, TrendingUp, TrendingDown, Calendar, BarChart3 } from 'lucide-vue-next'

const revenueStore = useManagerRevenueStore()
const hotelStore = useHotelStore()
const languageStore = useLanguageStore()

const loadData = async () => {
  await revenueStore.initialize()
}

onMounted(loadData)
watch(() => hotelStore.hotelId, loadData)

function formatCurrency(value: number) {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: hotelStore.currentHotel?.currency || 'ETB',
    maximumFractionDigits: 0,
  }).format(value)
}

const todayVsYesterdayDiff = computed(() => {
  const today = revenueStore.revenueSummary?.today ?? 0
  const yesterday = revenueStore.revenueSummary?.yesterday ?? 0
  if (yesterday === 0) return today > 0 ? 100 : 0
  return Math.round(((today - yesterday) / yesterday) * 100)
})

const monthVsYearPercent = computed(() => {
  const month = revenueStore.revenueSummary?.thisMonth ?? 0
  const year = revenueStore.revenueSummary?.thisYear ?? 0
  if (year === 0) return 0
  return Math.round((month / year) * 100)
})
</script>

<template>
  <DashboardLayout>
    <div
      class="min-h-screen bg-gradient-to-br from-slate-50 via-white to-blue-50/30 dark:from-slate-950 dark:via-slate-900 dark:to-slate-900 py-4 md:py-6 transition-colors duration-300"
    >
      <div
        class="mb-6 md:mb-8 border-b border-slate-200/60 dark:border-slate-700 bg-white/80 dark:bg-slate-900/80 backdrop-blur-sm rounded-lg p-4 md:p-6 transition-colors duration-300"
      >
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-2xl md:text-3xl font-bold text-slate-900 dark:text-slate-100 mb-2">
              {{ languageStore.t('finance_revenue', 'Finance & Revenue') }}
            </h1>
            <p class="text-sm md:text-base text-slate-600 dark:text-slate-400">
              {{
                languageStore.t(
                  'complete_financial_overview',
                  'Complete financial overview and analysis',
                )
              }}
            </p>
          </div>
          <div
            class="w-12 h-12 bg-gradient-to-br from-blue-100 to-blue-50 dark:from-blue-900 dark:to-blue-800 rounded-xl flex items-center justify-center"
          >
            <Wallet class="w-6 h-6 text-blue-600 dark:text-blue-400" />
          </div>
        </div>
      </div>

      <div v-if="revenueStore.loading" class="flex justify-center items-center py-32">
        <div class="text-center">
          <div class="relative w-12 h-12">
            <svg class="absolute inset-0 w-full h-full" viewBox="0 0 100 100">
              <circle
                cx="50"
                cy="50"
                r="45"
                fill="none"
                stroke="#0EA5E9"
                stroke-width="6"
                opacity="0.3"
              />
            </svg>
            <div class="absolute inset-0 animate-spin" style="animation: spin 1.5s linear infinite">
              <svg viewBox="0 0 100 100" class="w-full h-full">
                <circle
                  cx="50"
                  cy="50"
                  r="45"
                  fill="none"
                  stroke="#FBBF24"
                  stroke-width="8"
                  stroke-linecap="round"
                  stroke-dasharray="70 280"
                />
              </svg>
            </div>
          </div>
          <p class="text-slate-700 dark:text-yellow-300 font-semibold text-sm mt-4">
            {{ languageStore.t('loading_financial_data', 'Loading financial data...') }}
          </p>
        </div>
      </div>

      <div
        v-if="revenueStore.error && !revenueStore.loading"
        class="bg-red-50/80 dark:bg-red-900/20 backdrop-blur-sm border border-red-200/60 dark:border-red-800/60 text-red-700 dark:text-red-400 p-6 rounded-xl mb-6"
      >
        {{ revenueStore.error }}
      </div>

      <div v-if="!revenueStore.loading" class="space-y-6">
        <RevenueOverview />

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div
            class="bg-white dark:bg-slate-800 rounded-3xl border border-slate-200 dark:border-slate-700 shadow-sm p-6"
          >
            <h2
              class="text-xl font-bold text-slate-900 dark:text-slate-100 mb-6 flex items-center gap-2"
            >
              <Calendar class="w-5 h-5 text-blue-600 dark:text-blue-400" />
              {{ languageStore.t('period_breakdown', 'Period Performance') }}
            </h2>
            <div class="space-y-4">
              <div
                class="flex justify-between items-center pb-4 border-b border-slate-200 dark:border-slate-700"
              >
                <span class="text-slate-600 dark:text-slate-400">{{
                  languageStore.t('today', 'Today')
                }}</span>
                <span class="font-bold text-slate-900 dark:text-slate-100">{{
                  formatCurrency(revenueStore.revenueSummary?.today ?? 0)
                }}</span>
              </div>
              <div
                class="flex justify-between items-center pb-4 border-b border-slate-200 dark:border-slate-700"
              >
                <span class="text-slate-600 dark:text-slate-400">{{
                  languageStore.t('yesterday', 'Yesterday')
                }}</span>
                <span class="font-bold text-slate-700 dark:text-slate-300">{{
                  formatCurrency(revenueStore.revenueSummary?.yesterday ?? 0)
                }}</span>
              </div>
              <div
                class="flex justify-between items-center pb-4 border-b border-slate-200 dark:border-slate-700"
              >
                <span class="text-slate-600 dark:text-slate-400">{{
                  languageStore.t('this_week', 'This Week')
                }}</span>
                <span class="font-bold text-slate-900 dark:text-slate-100">{{
                  formatCurrency(revenueStore.revenueSummary?.thisWeek ?? 0)
                }}</span>
              </div>
              <div
                class="flex justify-between items-center pb-4 border-b border-slate-200 dark:border-slate-700"
              >
                <span class="text-slate-600 dark:text-slate-400">{{
                  languageStore.t('this_month', 'This Month')
                }}</span>
                <span class="font-bold text-blue-600 dark:text-blue-400">{{
                  formatCurrency(revenueStore.revenueSummary?.thisMonth ?? 0)
                }}</span>
              </div>
              <div class="flex justify-between items-center text-lg pt-1">
                <span class="font-bold text-slate-900 dark:text-slate-100">{{
                  languageStore.t('this_year', 'Year to Date')
                }}</span>
                <span class="font-bold text-emerald-600 dark:text-emerald-400">{{
                  formatCurrency(revenueStore.revenueSummary?.thisYear ?? 0)
                }}</span>
              </div>
            </div>
          </div>

          <div
            class="bg-white dark:bg-slate-800 rounded-3xl border border-slate-200 dark:border-slate-700 shadow-sm p-6"
          >
            <h2
              class="text-xl font-bold text-slate-900 dark:text-slate-100 mb-6 flex items-center gap-2"
            >
              <BarChart3 class="w-5 h-5 text-emerald-600 dark:text-emerald-400" />
              {{ languageStore.t('growth_indicators', 'Revenue Indicators') }}
            </h2>
            <div class="space-y-6">
              <div>
                <div class="flex justify-between mb-2 items-center">
                  <span class="text-slate-600 dark:text-slate-400">{{
                    languageStore.t('day_over_day_change', 'Day-over-Day Trend')
                  }}</span>
                  <span
                    :class="[
                      'font-bold flex items-center gap-1',
                      todayVsYesterdayDiff >= 0
                        ? 'text-emerald-600 dark:text-emerald-400'
                        : 'text-red-600 dark:text-red-400',
                    ]"
                  >
                    <TrendingUp v-if="todayVsYesterdayDiff >= 0" class="w-4 h-4" />
                    <TrendingDown v-else class="w-4 h-4" />
                    {{ todayVsYesterdayDiff >= 0 ? '+' : '' }}{{ todayVsYesterdayDiff }}%
                  </span>
                </div>
                <div class="h-2 bg-slate-100 dark:bg-slate-700 rounded-full overflow-hidden">
                  <div
                    class="h-full"
                    :class="
                      todayVsYesterdayDiff >= 0
                        ? 'bg-emerald-500 dark:bg-emerald-400'
                        : 'bg-red-500 dark:bg-red-400'
                    "
                    :style="{ width: Math.min(Math.abs(todayVsYesterdayDiff), 100) + '%' }"
                  ></div>
                </div>
              </div>

              <div>
                <div class="flex justify-between mb-2">
                  <span class="text-slate-600 dark:text-slate-400">{{
                    languageStore.t('month_of_year_share', 'Month Share of Annual Revenue')
                  }}</span>
                  <span class="font-bold text-slate-900 dark:text-slate-100"
                    >{{ monthVsYearPercent }}%</span
                  >
                </div>
                <div class="h-2 bg-slate-100 dark:bg-slate-700 rounded-full overflow-hidden">
                  <div
                    class="h-full bg-blue-500 dark:bg-blue-400"
                    :style="{ width: Math.min(monthVsYearPercent, 100) + '%' }"
                  ></div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div
          v-if="revenueStore.revenueChart.length > 0"
          class="bg-white dark:bg-slate-800 rounded-3xl border border-slate-200 dark:border-slate-700 shadow-sm p-6"
        >
          <h2 class="text-xl font-bold text-slate-900 dark:text-slate-100 mb-6">
            {{ languageStore.t('recent_periods', 'Period Revenue Breakdown') }}
          </h2>
          <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3">
            <div
              v-for="(point, idx) in revenueStore.revenueChart"
              :key="idx"
              class="p-4 bg-slate-50 dark:bg-slate-700/50 rounded-2xl text-center"
            >
              <p class="text-xs text-slate-500 dark:text-slate-400 font-medium">
                {{ (point as any).period || point.label || `P${idx + 1}` }}
              </p>
              <p class="font-bold text-slate-900 dark:text-slate-100 mt-1">
                {{ formatCurrency(point.revenue || 0) }}
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>

<style scoped>
@keyframes spin {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}

.animate-spin {
  animation: spin 1.5s linear infinite;
}
</style>
