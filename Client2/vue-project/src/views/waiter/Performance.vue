<template>
  <DashboardLayout>
    <div class="min-h-screen bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-slate-100 p-4 sm:p-6 lg:p-8 transition-colors duration-200 font-sans">
      <div class="max-w-7xl mx-auto space-y-6">
        
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white dark:bg-slate-900/90 backdrop-blur-xl border border-slate-200 dark:border-slate-800 p-6 rounded-2xl shadow-sm dark:shadow-2xl transition-colors duration-200">
          <div>
            <div class="flex items-center gap-3">
              <div class="p-3 bg-indigo-50 dark:bg-indigo-500/20 text-indigo-600 dark:text-indigo-400 rounded-xl border border-indigo-100 dark:border-indigo-500/30 shadow-sm">
                <TrendingUp class="w-6 h-6" />
              </div>
              <div>
                <div class="flex items-center gap-2">
                  <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-white tracking-tight">{{ languageStore.t('performance_analytics', 'Performance Analytics') }}</h1>
                  <span v-if="hotelStore.hotelName" class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300 border border-blue-200 dark:border-blue-700/50">
                    <Building2 class="w-3 h-3" />
                    {{ hotelStore.hotelName }}
                  </span>
                </div>
                <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1">{{ languageStore.t('performance_analytics_desc', 'Real-time metrics, delivery efficiency, and service ratings') }}</p>
              </div>
            </div>
          </div>

          <div class="flex items-center bg-slate-100 dark:bg-slate-950 p-1.5 rounded-xl border border-slate-200 dark:border-slate-800 shadow-inner">
            <button
              v-for="period in periodOptions"
              :key="period.key"
              @click="selectedPeriod = period.key"
              :class="[
                'px-4 py-2 text-xs sm:text-sm font-semibold rounded-lg transition-all duration-200 cursor-pointer',
                selectedPeriod === period.key
                  ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/30'
                  : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200 hover:bg-white dark:hover:bg-slate-900'
              ]"
            >
              {{ languageStore.t(period.key, period.label) }}
            </button>
          </div>
        </div>

        <div v-if="loading" class="flex items-center justify-center py-24 bg-white dark:bg-slate-900/40 rounded-2xl border border-slate-200 dark:border-slate-800/60 shadow-sm">
          <div class="text-center">
            <div class="inline-block relative w-12 h-12 mb-3">
              <div class="absolute inset-0 rounded-full border-4 border-indigo-500/20 border-t-indigo-600 dark:border-t-indigo-400 animate-spin"></div>
            </div>
            <p class="text-slate-600 dark:text-slate-400 text-sm font-medium">{{ languageStore.t('fetching_performance_data', 'Fetching performance data...') }}</p>
          </div>
        </div>

        <div v-else class="space-y-6">
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
            <div class="bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 p-6 rounded-2xl shadow-sm dark:shadow-xl hover:border-indigo-300 dark:hover:border-slate-700 transition duration-300">
              <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">{{ languageStore.t('total_deliveries', 'Total Deliveries') }}</span>
                <div class="w-10 h-10 rounded-xl bg-blue-50 dark:bg-blue-500/10 border border-blue-100 dark:border-blue-500/20 flex items-center justify-center text-blue-600 dark:text-blue-400">
                  <Truck class="w-5 h-5" />
                </div>
              </div>
              <div class="mt-4 flex items-baseline justify-between">
                <span class="text-3xl font-black text-slate-900 dark:text-white">{{ currentStats.deliveries || 0 }}</span>
                <span class="text-xs font-medium text-emerald-700 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-500/10 px-2.5 py-1 rounded-full border border-emerald-200 dark:border-emerald-500/20 flex items-center gap-1">
                  <CheckCircle2 class="w-3.5 h-3.5" />
                  {{ currentStats.deliveries || 0 }} {{ languageStore.t('completed', 'completed') }}
                </span>
              </div>
              <p class="text-xs text-slate-500 dark:text-slate-400 mt-3">
                <span class="text-rose-600 dark:text-rose-400 font-semibold">{{ currentStats.failed || 0 }}</span> {{ languageStore.t('failed_deliveries', 'failed deliveries in period') }}
              </p>
            </div>

            <div class="bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 p-6 rounded-2xl shadow-sm dark:shadow-xl hover:border-emerald-300 dark:hover:border-slate-700 transition duration-300">
              <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">{{ languageStore.t('on_time_rate', 'Success Rate') }}</span>
                <div class="w-10 h-10 rounded-xl bg-emerald-50 dark:bg-emerald-500/10 border border-emerald-100 dark:border-emerald-500/20 flex items-center justify-center text-emerald-600 dark:text-emerald-400">
                  <ShieldCheck class="w-5 h-5" />
                </div>
              </div>
              <div class="mt-4 flex items-baseline justify-between">
                <span class="text-3xl font-black text-slate-900 dark:text-white">{{ currentStats.successRate || 100 }}%</span>
                <span :class="[
                  'text-xs font-medium px-2.5 py-1 rounded-full border flex items-center gap-1',
                  currentStats.successRate >= 90 ? 'text-emerald-700 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-500/10 border-emerald-200 dark:border-emerald-500/20' :
                  currentStats.successRate >= 75 ? 'text-amber-700 dark:text-amber-400 bg-amber-50 dark:bg-amber-500/10 border-amber-200 dark:border-amber-500/20' :
                  'text-rose-700 dark:text-rose-400 bg-rose-50 dark:bg-rose-500/10 border-rose-200 dark:border-rose-500/20'
                ]">
                  {{ currentStats.successRate >= 90 ? languageStore.t('excellent', 'Excellent') : currentStats.successRate >= 75 ? languageStore.t('good', 'Good') : languageStore.t('needs_work', 'Needs Work') }}
                </span>
              </div>
              <div class="w-full bg-slate-100 dark:bg-slate-800 rounded-full h-2 mt-3 overflow-hidden">
                <div class="bg-gradient-to-r from-emerald-500 to-teal-400 h-full rounded-full transition-all duration-500" :style="{ width: `${currentStats.successRate || 100}%` }"></div>
              </div>
            </div>

            <div class="bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 p-6 rounded-2xl shadow-sm dark:shadow-xl hover:border-amber-300 dark:hover:border-slate-700 transition duration-300">
              <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">{{ languageStore.t('avg_delivery_time', 'Avg Delivery Time') }}</span>
                <div class="w-10 h-10 rounded-xl bg-amber-50 dark:bg-amber-500/10 border border-amber-100 dark:border-amber-500/20 flex items-center justify-center text-amber-600 dark:text-amber-400">
                  <Timer class="w-5 h-5" />
                </div>
              </div>
              <div class="mt-4 flex items-baseline justify-between">
                <span class="text-3xl font-black text-slate-900 dark:text-white">{{ currentStats.averageDeliveryTime || 0 }} <span class="text-sm font-normal text-slate-500 dark:text-slate-400">{{ languageStore.t('min', 'min') }}</span></span>
                <span class="text-xs font-medium text-amber-700 dark:text-amber-400 bg-amber-50 dark:bg-amber-500/10 px-2.5 py-1 rounded-full border border-amber-200 dark:border-amber-500/20 flex items-center gap-1">
                  <Zap class="w-3.5 h-3.5" />
                  {{ currentStats.averageDeliveryTime <= 20 ? languageStore.t('fast', 'Fast') : languageStore.t('standard', 'Standard') }}
                </span>
              </div>
              <p class="text-xs text-slate-500 dark:text-slate-400 mt-3">{{ languageStore.t('target_delivery_time', 'Target delivery time is < 25 minutes') }}</p>
            </div>

            <div class="bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 p-6 rounded-2xl shadow-sm dark:shadow-xl hover:border-yellow-300 dark:hover:border-slate-700 transition duration-300">
              <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">{{ languageStore.t('customer_rating', 'Avg Rating') }}</span>
                <div class="w-10 h-10 rounded-xl bg-yellow-50 dark:bg-yellow-500/10 border border-yellow-100 dark:border-yellow-500/20 flex items-center justify-center text-yellow-600 dark:text-yellow-400">
                  <Star class="w-5 h-5" />
                </div>
              </div>
              <div class="mt-4 flex items-baseline justify-between">
                <span class="text-3xl font-black text-slate-900 dark:text-white">{{ currentStats.rating || 4.8 }}<span class="text-sm font-normal text-slate-500 dark:text-slate-400">/5</span></span>
                <div class="flex items-center gap-0.5 text-yellow-500 dark:text-yellow-400">
                  <Star v-for="i in 5" :key="i" class="w-3.5 h-3.5 fill-current" :class="{ 'opacity-30': i > Math.round(currentStats.rating || 4.8) }" />
                </div>
              </div>
              <p class="text-xs text-slate-500 dark:text-slate-400 mt-3">{{ languageStore.t('based_on_guest_feedback', 'Based on guest feedback ratings') }}</p>
            </div>

          </div>

          <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-6">
              <div class="bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-sm dark:shadow-xl">
                <h3 class="text-base font-bold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
                  <Award class="w-4 h-4 text-amber-500 dark:text-amber-400" />
                  {{ languageStore.t('service_badges_milestones', 'Service Badges & Milestones') }}
                </h3>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                  
                  <div class="bg-slate-50 dark:bg-slate-950 border border-amber-200 dark:border-amber-500/20 p-4 rounded-xl text-center flex flex-col items-center shadow-xs">
                    <div class="w-12 h-12 rounded-full bg-amber-100 dark:bg-amber-500/10 text-amber-600 dark:text-amber-400 flex items-center justify-center mb-2 text-2xl border border-amber-200 dark:border-amber-500/30">
                      🏆
                    </div>
                    <span class="text-xs font-bold text-slate-800 dark:text-slate-200">{{ languageStore.t('top_courier', 'Top Courier') }}</span>
                    <span class="text-[10px] text-slate-500 dark:text-slate-400 mt-1">{{ languageStore.t('high_completion_rate', 'High Completion Rate') }}</span>
                  </div>

                  <div class="bg-slate-50 dark:bg-slate-950 border border-blue-200 dark:border-blue-500/20 p-4 rounded-xl text-center flex flex-col items-center shadow-xs">
                    <div class="w-12 h-12 rounded-full bg-blue-100 dark:bg-blue-500/10 text-blue-600 dark:text-blue-400 flex items-center justify-center mb-2 text-2xl border border-blue-200 dark:border-blue-500/30">
                      ⚡
                    </div>
                    <span class="text-xs font-bold text-slate-800 dark:text-slate-200">{{ languageStore.t('speed_demon', 'Speed Demon') }}</span>
                    <span class="text-[10px] text-slate-500 dark:text-slate-400 mt-1">{{ languageStore.t('under_20m_avg_time', 'Under 20m Avg Time') }}</span>
                  </div>

                  <div class="bg-slate-50 dark:bg-slate-950 border border-emerald-200 dark:border-emerald-500/20 p-4 rounded-xl text-center flex flex-col items-center shadow-xs">
                    <div class="w-12 h-12 rounded-full bg-emerald-100 dark:bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center mb-2 text-2xl border border-emerald-200 dark:border-emerald-500/30">
                      ⭐
                    </div>
                    <span class="text-xs font-bold text-slate-800 dark:text-slate-200">{{ languageStore.t('guest_favorite', 'Guest Favorite') }}</span>
                    <span class="text-[10px] text-slate-500 dark:text-slate-400 mt-1">{{ languageStore.t('rating_score_45', '4.5+ Rating Score') }}</span>
                  </div>

                  <div class="bg-slate-50 dark:bg-slate-950 border border-purple-200 dark:border-purple-500/20 p-4 rounded-xl text-center flex flex-col items-center shadow-xs">
                    <div class="w-12 h-12 rounded-full bg-purple-100 dark:bg-purple-500/10 text-purple-600 dark:text-purple-400 flex items-center justify-center mb-2 text-2xl border border-purple-200 dark:border-purple-500/30">
                      🎯
                    </div>
                    <span class="text-xs font-bold text-slate-800 dark:text-slate-200">{{ languageStore.t('reliable', 'Reliable') }}</span>
                    <span class="text-[10px] text-slate-500 dark:text-slate-400 mt-1">{{ languageStore.t('zero_delays', 'Zero Delays') }}</span>
                  </div>

                </div>
              </div>

              <div class="bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-sm dark:shadow-xl">
                <h3 class="text-base font-bold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
                  <Gauge class="w-4 h-4 text-blue-500 dark:text-blue-400" />
                  {{ languageStore.t('delivery_speed_distribution', 'Delivery Speed Distribution') }}
                </h3>
                <div class="space-y-4">
                  <div>
                    <div class="flex justify-between text-xs mb-1">
                      <span class="text-slate-700 dark:text-slate-300 font-medium">{{ languageStore.t('under_15_mins', 'Under 15 minutes (Express)') }}</span>
                      <span class="text-emerald-600 dark:text-emerald-400 font-bold">65% {{ languageStore.t('of_deliveries', 'of deliveries') }}</span>
                    </div>
                    <div class="w-full bg-slate-100 dark:bg-slate-950 rounded-full h-2.5 overflow-hidden border border-slate-200 dark:border-slate-800">
                      <div class="bg-emerald-500 h-full rounded-full" style="width: 65%"></div>
                    </div>
                  </div>

                  <div>
                    <div class="flex justify-between text-xs mb-1">
                      <span class="text-slate-700 dark:text-slate-300 font-medium">{{ languageStore.t('15_30_mins', '15 - 30 minutes (Standard)') }}</span>
                      <span class="text-blue-600 dark:text-blue-400 font-bold">28% {{ languageStore.t('of_deliveries', 'of deliveries') }}</span>
                    </div>
                    <div class="w-full bg-slate-100 dark:bg-slate-950 rounded-full h-2.5 overflow-hidden border border-slate-200 dark:border-slate-800">
                      <div class="bg-blue-500 h-full rounded-full" style="width: 28%"></div>
                    </div>
                  </div>

                  <div>
                    <div class="flex justify-between text-xs mb-1">
                      <span class="text-slate-700 dark:text-slate-300 font-medium">{{ languageStore.t('over_30_mins', '30+ minutes (Extended)') }}</span>
                      <span class="text-amber-600 dark:text-amber-400 font-bold">7% {{ languageStore.t('of_deliveries', 'of deliveries') }}</span>
                    </div>
                    <div class="w-full bg-slate-100 dark:bg-slate-950 rounded-full h-2.5 overflow-hidden border border-slate-200 dark:border-slate-800">
                      <div class="bg-amber-500 h-full rounded-full" style="width: 7%"></div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div class="bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-sm dark:shadow-xl flex flex-col justify-between">
              <div>
                <h3 class="text-base font-bold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
                  <History class="w-4 h-4 text-indigo-500 dark:text-indigo-400" />
                  {{ languageStore.t('recent_completed_deliveries', 'Recent Completed Deliveries') }}
                </h3>
                
                <div v-if="recentDeliveries.length === 0" class="text-center py-10 text-slate-500 dark:text-slate-400 text-sm">
                  <Inbox class="w-8 h-8 text-slate-400 dark:text-slate-600 mx-auto mb-2" />
                  {{ languageStore.t('no_completed_deliveries_period', 'No completed deliveries recorded for this period yet.') }}
                </div>

                <div v-else class="space-y-3">
                  <div
                    v-for="item in recentDeliveries.slice(0, 5)"
                    :key="item.id"
                    class="bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 p-3.5 rounded-xl flex items-center justify-between hover:border-slate-300 dark:hover:border-slate-700 transition duration-200"
                  >
                    <div class="flex items-center gap-3">
                      <div class="w-9 h-9 rounded-lg bg-indigo-50 dark:bg-indigo-500/10 border border-indigo-100 dark:border-indigo-500/20 text-indigo-600 dark:text-indigo-400 flex items-center justify-center font-bold text-xs">
                        {{ languageStore.t('room', 'RM') }} {{ item.room?.room_number || item.room_id || 'N/A' }}
                      </div>
                      <div>
                        <p class="text-xs font-semibold text-slate-900 dark:text-white">{{ languageStore.t('order_ref', 'Order #') }}{{ item.order?.order_number || String(item.id).substring(0,8) }}</p>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400">
                          {{ item.delivered_at ? formatDate(item.delivered_at) : languageStore.t('completed', 'Completed') }}
                        </p>
                      </div>
                    </div>
                    <span class="px-2.5 py-1 text-[10px] font-bold rounded-full bg-emerald-50 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-500/20">
                      {{ languageStore.t('delivered', 'Delivered') }}
                    </span>
                  </div>
                </div>
              </div>

              <div class="mt-6 pt-4 border-t border-slate-200 dark:border-slate-800 text-center">
                <router-link to="/waiter/delivery-history" class="text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 dark:hover:text-indigo-300 transition duration-150 inline-flex items-center gap-1">
                  {{ languageStore.t('view_full_history', 'View Full History') }}
                  <ArrowRight class="w-3.5 h-3.5" />
                </router-link>
              </div>
            </div>

          </div>
        </div>

      </div>
    </div>
  </DashboardLayout>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import waiterService from '@/services/waiterService'
import { useHotelStore } from '@/stores/hotelStore'
import { useLanguageStore } from '@/stores/language'
import {
  Building2,
  TrendingUp,
  Truck,
  CheckCircle2,
  ShieldCheck,
  Timer,
  Zap,
  Star,
  Award,
  Gauge,
  History,
  Inbox,
  ArrowRight
} from 'lucide-vue-next'

const hotelStore = useHotelStore()
const languageStore = useLanguageStore()

const loading = ref(true)
const selectedPeriod = ref<'today' | 'week' | 'month'>('today')

const periodOptions = [
  { key: 'today', label: 'Today' },
  { key: 'week', label: 'This Week' },
  { key: 'month', label: 'This Month' },
] as const

const rawPerformanceData = ref<any>(null)
const recentDeliveries = ref<any[]>([])

const currentStats = computed(() => {
  if (!rawPerformanceData.value) {
    return {
      deliveries: 0,
      failed: 0,
      averageDeliveryTime: 0,
      rating: 4.8,
      successRate: 100,
    }
  }

  const periodData = rawPerformanceData.value[selectedPeriod.value] || {}

  const deliveries = periodData.deliveries ?? 0
  const failed = periodData.failed ?? 0
  const averageDeliveryTime = periodData.average_delivery_time ?? periodData.average_time ?? 0
  const rating = periodData.rating ?? periodData.guest_rating ?? 4.8
  const successRate = periodData.success_rate ?? (deliveries + failed > 0 ? Math.round((deliveries / (deliveries + failed)) * 100) : 100)

  return {
    deliveries,
    failed,
    averageDeliveryTime,
    rating,
    successRate,
  }
})

const formatDate = (dateString: string) => {
  if (!dateString) return ''
  try {
    const d = new Date(dateString)
    return d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
  } catch (err) {
    console.error('[Performance] Error formatting date:', err)
    return dateString
  }
}

const loadData = async () => {
  try {
    loading.value = true
    const [perfData, completedData] = await Promise.all([
      waiterService.getPerformance().catch((err: any) => {
        console.error('[Performance] Error fetching performance:', err)
        return null
      }),
      waiterService.getCompletedDeliveries(10).catch((err: any) => {
        console.error('[Performance] Error fetching completed deliveries:', err)
        return []
      }),
    ])

    if (perfData) {
      rawPerformanceData.value = perfData
    }

    if (Array.isArray(completedData)) {
      recentDeliveries.value = completedData
    }
  } catch (err: any) {
    console.error('[Performance] Error loading data:', err)
  } finally {
    loading.value = false
  }
}

onMounted(loadData)

watch(() => hotelStore.hotelId, loadData)
</script>

<style scoped>
</style>
