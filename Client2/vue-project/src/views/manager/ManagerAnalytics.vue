<script setup lang="ts">
import { onMounted, ref, computed, watch } from 'vue'
import DashboardLayout from '../../Layouts/DashboardLayout.vue'
import { useHotelStore } from '@/stores/hotelStore'
import { useLanguageStore } from '@/stores/language'
import axios from '@/services/axios'
import { BarChart3, TrendingUp, Building2 } from 'lucide-vue-next'

const hotelStore = useHotelStore()
const languageStore = useLanguageStore()
const isLoading = ref(false)
const currency = computed(() => hotelStore.currentHotel?.currency || 'ETB')

const analyticsData = ref({
  revenue_today: 0,
  orders_completed: 0,
  customer_satisfaction: 96,
  avg_delivery_time: 15
})

const loadData = async () => {
  isLoading.value = true
  try {
    const res = await axios.get('/manager/analytics').catch((err) => {
      console.error('[ManagerAnalytics] Error fetching /manager/analytics:', err)
      return null
    })
    if (res?.data?.data) {
      const data = res.data.data
      const rev = data.revenue?.today_revenue ?? data.statistics?.today_revenue ?? 0
      const ords = data.statistics?.total_orders ?? 0
      analyticsData.value.revenue_today = rev
      analyticsData.value.orders_completed = ords
      analyticsData.value.avg_delivery_time = data.statistics?.avg_delivery_time || 15
    }
  } catch (e) {
    console.error('Failed to load analytics:', e)
  } finally {
    isLoading.value = false
  }
}

onMounted(loadData)

watch(() => hotelStore.hotelId, loadData)
</script>

<template>
  <DashboardLayout>
    <div class="min-h-screen bg-slate-50 dark:bg-slate-950 p-4 sm:p-6 space-y-6">
      <!-- PAGE HEADER -->
      <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-xs">
        <div class="flex items-center justify-between">
          <div>
            <div class="flex items-center gap-2">
              <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-white tracking-tight">
                {{ languageStore.t('analytics_reports', 'Analytics & Reports') }}
              </h1>
              <span v-if="hotelStore.hotelName" class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300 border border-blue-200 dark:border-blue-700/50">
                <Building2 class="w-3 h-3" />
                {{ hotelStore.hotelName }}
              </span>
            </div>
            <p class="text-slate-500 dark:text-slate-400 text-xs sm:text-sm mt-1">
              {{ languageStore.t('dashboard_overview_desc', 'Live hotel metrics, operations analytics, and department reports.') }}
            </p>
          </div>
          <div class="w-12 h-12 bg-gradient-to-br from-indigo-500/10 to-indigo-500/20 rounded-xl flex items-center justify-center text-indigo-600 dark:text-indigo-400">
            <BarChart3 class="w-6 h-6" />
          </div>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="isLoading" class="flex justify-center items-center py-32">
        <div class="text-center">
          <div class="relative w-12 h-12">
            <svg class="absolute inset-0 w-full h-full" viewBox="0 0 100 100">
              <circle cx="50" cy="50" r="45" fill="none" stroke="#0EA5E9" stroke-width="6" opacity="0.3" />
            </svg>
            <div class="absolute inset-0 animate-spin" style="animation: spin 1.5s linear infinite;">
              <svg viewBox="0 0 100 100" class="w-full h-full">
                <circle cx="50" cy="50" r="45" fill="none" stroke="#FBBF24" stroke-width="8" stroke-linecap="round" stroke-dasharray="70 280" />
              </svg>
            </div>
          </div>
          <p class="text-slate-700 dark:text-yellow-300 font-semibold text-sm mt-4">{{ languageStore.t('Loading...', 'Loading analytics data...') }}</p>
        </div>
      </div>

      <!-- Content -->
      <div v-if="!isLoading" class="space-y-6">
        <!-- Key Metrics -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
          <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/60 dark:border-slate-800 shadow-sm p-6 hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm text-slate-600 dark:text-slate-400 font-medium">
                  {{ languageStore.t("todays_revenue", "Today's Revenue") }}
                </p>
                <h3 class="mt-3 text-3xl font-bold text-emerald-600 dark:text-emerald-400">{{ analyticsData.revenue_today.toLocaleString() }} {{ currency }}</h3>
              </div>
              <TrendingUp class="w-8 h-8 text-emerald-500 opacity-20" />
            </div>
          </div>
          <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/60 dark:border-slate-800 shadow-sm p-6 hover:shadow-md transition-shadow">
            <p class="text-sm text-slate-600 dark:text-slate-400 font-medium">
              {{ languageStore.t('orders_completed', 'Orders Completed') }}
            </p>
            <h3 class="mt-3 text-3xl font-bold text-blue-600 dark:text-blue-400">{{ analyticsData.orders_completed }}</h3>
            <p class="text-xs text-slate-500 mt-2">{{ languageStore.t('Food Orders', 'Total orders') }}</p>
          </div>
          <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/60 dark:border-slate-800 shadow-sm p-6 hover:shadow-md transition-shadow">
            <p class="text-sm text-slate-600 dark:text-slate-400 font-medium">
              {{ languageStore.t('customer_satisfaction', 'Satisfaction Rate') }}
            </p>
            <h3 class="mt-3 text-3xl font-bold text-purple-600 dark:text-purple-400">{{ analyticsData.customer_satisfaction }}%</h3>
            <p class="text-xs text-slate-500 mt-2">{{ languageStore.t('guest_reviews', 'Customer feedback') }}</p>
          </div>
          <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/60 dark:border-slate-800 shadow-sm p-6 hover:shadow-md transition-shadow">
            <p class="text-sm text-slate-600 dark:text-slate-400 font-medium">
              {{ languageStore.t('avg_delivery_time', 'Avg Delivery Time') }}
            </p>
            <h3 class="mt-3 text-3xl font-bold text-orange-600 dark:text-orange-400">{{ analyticsData.avg_delivery_time }} {{ languageStore.t('minutes', 'min') }}</h3>
            <p class="text-xs text-slate-500 mt-2">{{ languageStore.t('average', 'Average') }}</p>
          </div>
        </div>

        <!-- Performance Overview -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/60 dark:border-slate-800 shadow-sm p-6">
          <h2 class="text-xl font-bold text-slate-900 dark:text-white mb-6">
            {{ languageStore.t('revenue_performance', 'Performance Metrics') }}
          </h2>
          <div class="space-y-4">
            <div>
              <div class="flex justify-between mb-2">
                <span class="text-sm font-medium text-slate-700 dark:text-slate-300">{{ languageStore.t('staff_activity', 'Staff Efficiency') }}</span>
                <span class="text-sm font-semibold text-slate-900 dark:text-white">88%</span>
              </div>
              <div class="h-2 bg-slate-200 dark:bg-slate-800 rounded-full overflow-hidden">
                <div class="h-full bg-gradient-to-r from-blue-500 to-blue-600" style="width: 88%"></div>
              </div>
            </div>
            <div>
              <div class="flex justify-between mb-2">
                <span class="text-sm font-medium text-slate-700 dark:text-slate-300">{{ languageStore.t('order_accuracy', 'Order Accuracy') }}</span>
                <span class="text-sm font-semibold text-slate-900 dark:text-white">95%</span>
              </div>
              <div class="h-2 bg-slate-200 dark:bg-slate-800 rounded-full overflow-hidden">
                <div class="h-full bg-gradient-to-r from-emerald-500 to-emerald-600" style="width: 95%"></div>
              </div>
            </div>
            <div>
              <div class="flex justify-between mb-2">
                <span class="text-sm font-medium text-slate-700 dark:text-slate-300">{{ languageStore.t('delivery_on_time', 'Delivery On-Time') }}</span>
                <span class="text-sm font-semibold text-slate-900 dark:text-white">92%</span>
              </div>
              <div class="h-2 bg-slate-200 dark:bg-slate-800 rounded-full overflow-hidden">
                <div class="h-full bg-gradient-to-r from-purple-500 to-purple-600" style="width: 92%"></div>
              </div>
            </div>
          </div>
        </div>

        <!-- Recent Activity -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/60 dark:border-slate-800 shadow-sm p-6">
          <h2 class="text-xl font-bold text-slate-900 dark:text-white mb-4">
            {{ languageStore.t('recent_operations_log', 'Recent Activity') }}
          </h2>
          <div class="space-y-3">
            <div class="flex items-center justify-between p-3 bg-slate-50 dark:bg-slate-800/60 rounded-xl">
              <span class="text-sm text-slate-700 dark:text-slate-300">{{ languageStore.t('peak_hours', 'Peak hours: 12:00 PM - 1:30 PM') }}</span>
              <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">2h</span>
            </div>
            <div class="flex items-center justify-between p-3 bg-slate-50 dark:bg-slate-800/60 rounded-xl">
              <span class="text-sm text-slate-700 dark:text-slate-300">{{ languageStore.t('new_waiter_assigned', 'New waiter assigned to Floor 2') }}</span>
              <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">30m</span>
            </div>
            <div class="flex items-center justify-between p-3 bg-slate-50 dark:bg-slate-800/60 rounded-xl">
              <span class="text-sm text-slate-700 dark:text-slate-300">{{ languageStore.t('revenue_target_exceeded', 'Daily revenue target exceeded') }}</span>
              <span class="text-xs font-semibold text-emerald-600 dark:text-emerald-400">15m</span>
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
