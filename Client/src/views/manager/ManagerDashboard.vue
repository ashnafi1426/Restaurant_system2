<script setup lang="ts">
import { onMounted, ref, computed, watch } from 'vue'
import { useHotelStore } from '@/stores/hotelStore'
import { useManagerStore } from '@/stores/managerStore'
import { useLanguageStore } from '@/stores/language'
import DashboardLayout from '../../Layouts/DashboardLayout.vue'
import managerService from '@/services/managerService'
import {
  Calendar,
  Home,
  Users,
  AlertCircle,
  TrendingUp,
  RefreshCw,
  Building2,
} from 'lucide-vue-next'
import { Bar, Doughnut } from 'vue-chartjs'
import {
  Chart as ChartJS,
  Title,
  Tooltip,
  Legend,
  BarElement,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  ArcElement,
  Filler,
} from 'chart.js'

ChartJS.register(
  Title,
  Tooltip,
  Legend,
  BarElement,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  ArcElement,
  Filler,
)

const hotelStore = useHotelStore()
const manager = useManagerStore()
const languageStore = useLanguageStore()
const loading = ref(true)
const error = ref<string | null>(null)
const trendTab = ref<'weekly' | 'monthly'>('weekly')

const stats = computed(() => {
  const ds = manager.dashboardStats
  return {
    total_reservations: Number(ds.totalReservations ?? 0),
    rooms_occupied: Number(ds.occupiedRooms ?? 0),
    max_rooms: Number(ds.totalRooms ?? 0),
    active_waiters: Number(ds.activeStaff ?? 0),
    kitchen_ready: Number(ds.preparingOrders ?? 0),
    today_revenue: Number(ds.todayRevenue ?? 0),
  }
})

const activities = computed(() => manager.dashboardActivities || [])

const weeklyTrends = ref<{ label: string; revenue: number }[]>([])
const monthlyTrends = ref<{ label: string; revenue: number }[]>([])

const buildRevenueDataset = (
  trends: { label: string; revenue: number }[],
  fallbackLabels: string[],
  color: string,
  hoverColor: string,
  labelPrefix: string,
) => {
  const currency = hotelStore.currentHotel?.currency || 'ETB'
  const labels = trends.length ? trends.map((t) => t.label) : fallbackLabels
  const data = trends.length ? trends.map((t) => t.revenue) : fallbackLabels.map(() => 0)
  return {
    labels,
    datasets: [
      {
        label: `${labelPrefix} (${currency})`,
        data,
        backgroundColor: color,
        borderRadius: 8,
        borderSkipped: false,
        hoverBackgroundColor: hoverColor,
      },
    ],
  }
}

const weeklyRevenueChartData = computed(() =>
  buildRevenueDataset(
    weeklyTrends.value,
    ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
    '#3B82F6',
    '#2563EB',
    'Revenue',
  ),
)

const monthlyRevenueChartData = computed(() =>
  buildRevenueDataset(
    monthlyTrends.value,
    ['Day 1', 'Day 10', 'Day 20', 'Day 30'],
    '#6366F1',
    '#4F46E5',
    'Monthly Revenue',
  ),
)

const chartOptions = computed(() => ({
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: { display: false },
    tooltip: {
      backgroundColor: '#0F172A',
      titleFont: { size: 12, weight: 'bold' as const },
      bodyFont: { size: 12 },
      padding: 10,
      displayColors: false,
      callbacks: {
        label: (context: any) =>
          `Revenue: ${Number(context.raw || 0).toLocaleString()} ${hotelStore.currentHotel?.currency || 'ETB'}`,
      },
    },
  },
  scales: {
    x: {
      grid: { display: false },
      ticks: { font: { size: 11, weight: 'bold' as const } },
    },
    y: {
      grid: { color: 'rgba(226, 232, 240, 0.5)' },
      ticks: {
        font: { size: 11 },
        callback: (value: any) => `${value} ${hotelStore.currentHotel?.currency || 'ETB'}`,
      },
    },
  },
}))

// Occupancy Chart Data
const occupancyChartData = computed(() => ({
  labels: ['Occupied', 'Available'],
  datasets: [
    {
      data: [
        stats.value.rooms_occupied,
        Math.max(0, stats.value.max_rooms - stats.value.rooms_occupied),
      ],
      backgroundColor: ['#3B82F6', '#E2E8F0'],
      borderWidth: 0,
      hoverOffset: 4,
    },
  ],
}))

const doughnutOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      position: 'bottom' as const,
      labels: { font: { size: 11, weight: 'bold' as const } },
    },
  },
  cutout: '70%',
}

const loadData = async () => {
  try {
    loading.value = true
    error.value = null
    await manager.initializeManagerDashboard()
    try {
      weeklyTrends.value = await managerService.getRevenueChart('weekly')
      monthlyTrends.value = await managerService.getRevenueChart('monthly')
    } catch (chartErr) {
      console.warn('[ManagerDashboard] Chart load warning:', chartErr)
    }
  } catch (err: any) {
    console.error('[ManagerDashboard] Load error:', err)
    error.value = err.message || 'Failed to load manager dashboard'
  } finally {
    loading.value = false
  }
}

watch(
  () => hotelStore.hotelId,
  (newHotelId) => {
    if (newHotelId) {
      loadData()
    }
  },
)

onMounted(() => {
  loadData()
})
</script>

<template>
  <DashboardLayout>
    <div
      class="min-h-screen bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-slate-100 p-3 sm:p-6 lg:p-8 transition-colors duration-200"
    >
      <div class="max-w-7xl mx-auto space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
          <div>
            <div class="flex items-center gap-2.5">
              <h1
                class="text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-white tracking-tight"
              >
                {{ languageStore.t('manager_dashboard', 'Manager Executive Dashboard') }}
              </h1>
              <span
                class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-blue-100 dark:bg-blue-900/40 text-blue-800 dark:text-blue-300 border border-blue-200 dark:border-blue-700/50"
              >
                <Building2 class="w-3.5 h-3.5" />
                {{ hotelStore.hotelName }}
              </span>
            </div>
            <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1">
              {{
                languageStore.t(
                  'manager_dashboard_desc',
                  'Tenant-scoped operational analytics, live revenue, staff management & insights',
                )
              }}
            </p>
          </div>
          <button
            @click="loadData"
            :disabled="loading"
            class="px-4 py-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-200 rounded-xl text-xs font-semibold flex items-center gap-2 hover:bg-slate-100 dark:hover:bg-slate-800 transition shadow-xs self-start sm:self-auto disabled:opacity-50 cursor-pointer"
          >
            <RefreshCw :class="['w-4 h-4', loading ? 'animate-spin' : '']" />
            {{ languageStore.t('refresh', 'Refresh Overview') }}
          </button>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
          <div
            class="bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-sm dark:shadow-xl"
          >
            <div class="flex items-center justify-between">
              <span
                class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider"
                >{{ languageStore.t('Reservations', 'Reservations') }}</span
              >
              <div
                class="p-2.5 bg-blue-50 dark:bg-blue-500/10 text-blue-600 dark:text-blue-400 rounded-xl"
              >
                <Calendar class="w-5 h-5" />
              </div>
            </div>
            <h3 class="text-3xl font-extrabold text-slate-900 dark:text-white mt-3">
              {{ stats.total_reservations }}
            </h3>
            <p class="text-xs text-slate-500 dark:text-slate-400 font-medium mt-1">
              {{ languageStore.t('active_bookings', 'Active hotel bookings') }}
            </p>
          </div>

          <div
            class="bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-sm dark:shadow-xl"
          >
            <div class="flex items-center justify-between">
              <span
                class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider"
                >{{ languageStore.t('room_occupancy', 'Rooms Occupancy') }}</span
              >
              <div
                class="p-2.5 bg-indigo-50 dark:bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 rounded-xl"
              >
                <Home class="w-5 h-5" />
              </div>
            </div>
            <div class="mt-3 flex items-baseline gap-1">
              <h3 class="text-3xl font-extrabold text-slate-900 dark:text-white">
                {{ stats.rooms_occupied }}
              </h3>
              <span class="text-sm font-bold text-slate-400 dark:text-slate-500"
                >/ {{ stats.max_rooms }}</span
              >
            </div>
            <p class="text-xs text-blue-600 dark:text-blue-400 font-semibold mt-1">
              {{
                stats.max_rooms > 0
                  ? Math.round((stats.rooms_occupied / stats.max_rooms) * 100)
                  : 0
              }}% {{ languageStore.t('occupancy_rate', 'Occupancy Rate') }}
            </p>
          </div>

          <div
            class="bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-sm dark:shadow-xl"
          >
            <div class="flex items-center justify-between">
              <span
                class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider"
                >{{ languageStore.t('active_staff', 'Active Staff') }}</span
              >
              <div
                class="p-2.5 bg-emerald-50 dark:bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 rounded-xl"
              >
                <Users class="w-5 h-5" />
              </div>
            </div>
            <h3 class="text-3xl font-extrabold text-slate-900 dark:text-white mt-3">
              {{ stats.active_waiters }}
            </h3>
            <p class="text-xs text-emerald-600 dark:text-emerald-400 font-semibold mt-1">
              {{ languageStore.t('hotel_staff_members', 'Hotel staff members') }}
            </p>
          </div>

          <div
            class="bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-sm dark:shadow-xl"
          >
            <div class="flex items-center justify-between">
              <span
                class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider"
                >{{ languageStore.t('kitchen_alert', 'Kitchen Alert') }}</span
              >
              <div
                class="p-2.5 bg-rose-50 dark:bg-rose-500/10 text-rose-600 dark:text-rose-400 rounded-xl"
              >
                <AlertCircle class="w-5 h-5" />
              </div>
            </div>
            <h3 class="text-3xl font-extrabold text-slate-900 dark:text-white mt-3">
              {{ stats.kitchen_ready }}
            </h3>
            <p class="text-xs text-amber-600 dark:text-amber-400 font-semibold mt-1">
              {{ languageStore.t('preparing_ready_orders', 'Preparing / Ready orders') }}
            </p>
          </div>

          <div
            class="bg-gradient-to-br from-indigo-600 via-blue-600 to-blue-700 text-white rounded-2xl p-5 shadow-lg shadow-blue-500/20 sm:col-span-2 lg:col-span-1"
          >
            <div class="flex items-center justify-between">
              <span class="text-xs font-extrabold uppercase tracking-wider text-indigo-100">{{
                languageStore.t("Today's Revenue", "Today's Revenue")
              }}</span>
              <TrendingUp class="w-5 h-5 text-white/80" />
            </div>
            <h3 class="text-2xl sm:text-3xl font-black mt-3">
              {{ stats.today_revenue.toLocaleString('en-US', { minimumFractionDigits: 2 }) }}
              <span class="text-sm font-normal text-indigo-200">{{
                hotelStore.currentHotel?.currency || 'ETB'
              }}</span>
            </h3>
            <p class="text-xs text-indigo-100 mt-1 font-medium">
              {{ languageStore.t('hotel_dining_orders', 'Hotel Dining & Orders') }}
            </p>
          </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
          <div
            class="lg:col-span-2 bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-sm dark:shadow-xl"
          >
            <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-6 gap-3">
              <div>
                <h2 class="text-lg font-bold text-slate-900 dark:text-white">
                  {{ languageStore.t('revenue_analytics', 'Revenue Analytics') }}
                </h2>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                  {{
                    languageStore.t(
                      'revenue_analytics_desc',
                      'Live Chart.js breakdown of daily & weekly restaurant income',
                    )
                  }}
                </p>
              </div>

              <div class="flex gap-1.5 bg-slate-100 dark:bg-slate-800 p-1 rounded-xl">
                <button
                  @click="trendTab = 'weekly'"
                  :class="[
                    'px-3 py-1.5 rounded-lg text-xs font-bold transition cursor-pointer',
                    trendTab === 'weekly'
                      ? 'bg-indigo-600 text-white shadow-xs'
                      : 'text-slate-600 dark:text-slate-400 hover:text-slate-900',
                  ]"
                >
                  {{ languageStore.t('Week', 'Weekly') }}
                </button>
                <button
                  @click="trendTab = 'monthly'"
                  :class="[
                    'px-3 py-1.5 rounded-lg text-xs font-bold transition cursor-pointer',
                    trendTab === 'monthly'
                      ? 'bg-indigo-600 text-white shadow-xs'
                      : 'text-slate-600 dark:text-slate-400 hover:text-slate-900',
                  ]"
                >
                  {{ languageStore.t('Month', 'Monthly') }}
                </button>
              </div>
            </div>

            <div class="h-64 relative w-full">
              <Bar
                :data="trendTab === 'weekly' ? weeklyRevenueChartData : monthlyRevenueChartData"
                :options="chartOptions"
              />
            </div>
          </div>

          <div
            class="bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-sm dark:shadow-xl flex flex-col justify-between"
          >
            <div>
              <h2 class="text-lg font-bold text-slate-900 dark:text-white">
                {{ languageStore.t('room_occupancy', 'Room Occupancy') }}
              </h2>
              <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                {{
                  languageStore.t(
                    'room_occupancy_desc',
                    'Live ratio of occupied vs available rooms',
                  )
                }}
              </p>
            </div>

            <div class="h-56 relative w-full my-4 flex items-center justify-center">
              <Doughnut :data="occupancyChartData" :options="doughnutOptions" />
            </div>

            <div
              class="pt-4 border-t border-slate-200 dark:border-slate-800 flex justify-around text-center text-xs"
            >
              <div>
                <p class="text-slate-500 dark:text-slate-400 font-semibold">
                  {{ languageStore.t('Occupied', 'Occupied') }}
                </p>
                <p class="text-base font-extrabold text-blue-600 dark:text-blue-400 mt-0.5">
                  {{ stats.rooms_occupied }} {{ languageStore.t('rooms', 'Rooms') }}
                </p>
              </div>
              <div>
                <p class="text-slate-500 dark:text-slate-400 font-semibold">
                  {{ languageStore.t('Available', 'Available') }}
                </p>
                <p class="text-base font-extrabold text-slate-700 dark:text-slate-300 mt-0.5">
                  {{ Math.max(0, stats.max_rooms - stats.rooms_occupied) }}
                  {{ languageStore.t('rooms', 'Rooms') }}
                </p>
              </div>
            </div>
          </div>
        </div>

        <div
          class="bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-sm dark:shadow-xl"
        >
          <div class="flex items-center justify-between mb-4">
            <div>
              <h2 class="text-lg font-bold text-slate-900 dark:text-white">
                {{ languageStore.t('recent_operations_log', 'Recent Operations Log') }}
              </h2>
              <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                {{
                  languageStore.t(
                    'recent_operations_desc',
                    'Real-time system events, check-ins and order dispatches',
                  )
                }}
              </p>
            </div>
          </div>

          <div
            v-if="activities.length === 0"
            class="text-center py-8 text-slate-500 dark:text-slate-400 text-xs"
          >
            {{ languageStore.t('no_recent_activity_logs', 'No recent activity logs recorded') }}
          </div>

          <div v-else class="divide-y divide-slate-100 dark:divide-slate-800">
            <div
              v-for="act in activities.slice(0, 6)"
              :key="act.id"
              class="py-3 flex items-center justify-between text-xs hover:bg-slate-50 dark:hover:bg-slate-950/40 px-2 rounded-lg transition"
            >
              <div class="flex items-center gap-3">
                <span class="p-2 bg-slate-100 dark:bg-slate-800 rounded-lg text-sm">📌</span>
                <div>
                  <p class="font-bold text-slate-900 dark:text-white">
                    {{ act.title || act.description }}
                  </p>
                  <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">
                    {{ act.timestamp || 'Just now' }}
                  </p>
                </div>
              </div>
              <span
                class="px-2.5 py-1 bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 rounded-md font-semibold text-[10px]"
              >
                {{ act.type || 'SYSTEM' }}
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>
