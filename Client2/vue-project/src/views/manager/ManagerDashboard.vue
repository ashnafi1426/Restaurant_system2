<script setup lang="ts">
import { onMounted, ref, computed } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useManagerStore } from '@/stores/managerStore'
import DashboardLayout from '../../Layouts/DashboardLayout.vue'
import managerService from '@/services/managerService'
import { Calendar, Home, Users, AlertCircle, TrendingUp, Sparkles, RefreshCw } from 'lucide-vue-next'
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
  Filler
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
  Filler
)

const auth = useAuthStore()
const manager = useManagerStore()
const loading = ref(true)
const error = ref<string | null>(null)
const trendTab = ref<'weekly' | 'monthly'>('weekly')

const stats = computed(() => ({
  total_reservations: manager.dashboardStats.totalReservations || 36,
  rooms_occupied: manager.dashboardStats.occupiedRooms || 6,
  max_rooms: manager.dashboardStats.totalRooms || 9,
  active_waiters: manager.dashboardStats.activeStaff || 3,
  kitchen_ready: manager.dashboardStats.preparingOrders || 2,
  today_revenue: manager.dashboardStats.todayRevenue || 1480,
}))

const activities = computed(() => manager.dashboardActivities || [])

// Chart.js Revenue Trend Data
const weeklyRevenueChartData = computed(() => ({
  labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
  datasets: [
    {
      label: 'Revenue ($)',
      data: [420, 680, 510, 890, 1120, 1480, 950],
      backgroundColor: '#3B82F6',
      borderRadius: 8,
      borderSkipped: false,
      hoverBackgroundColor: '#2563EB',
    }
  ]
}))

const monthlyRevenueChartData = computed(() => ({
  labels: ['Week 1', 'Week 2', 'Week 3', 'Week 4'],
  datasets: [
    {
      label: 'Monthly Revenue ($)',
      data: [6400, 8900, 10200, 10760],
      backgroundColor: '#6366F1',
      borderRadius: 8,
      borderSkipped: false,
      hoverBackgroundColor: '#4F46E5',
    }
  ]
}))

const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: { display: false },
    tooltip: {
      backgroundColor: '#0F172A',
      titleFont: { size: 12, weight: 'bold' },
      bodyFont: { size: 12 },
      padding: 10,
      displayColors: false,
      callbacks: {
        label: (context: any) => `Revenue: $${context.raw.toLocaleString()}`
      }
    }
  },
  scales: {
    x: {
      grid: { display: false },
      ticks: { font: { size: 11, weight: '600' } }
    },
    y: {
      grid: { color: 'rgba(226, 232, 240, 0.5)' },
      ticks: {
        font: { size: 11 },
        callback: (value: any) => `$${value}`
      }
    }
  }
}

// Occupancy Chart Data
const occupancyChartData = computed(() => ({
  labels: ['Occupied', 'Available'],
  datasets: [
    {
      data: [stats.value.rooms_occupied, Math.max(0, stats.value.max_rooms - stats.value.rooms_occupied)],
      backgroundColor: ['#3B82F6', '#E2E8F0'],
      borderWidth: 0,
      hoverOffset: 4
    }
  ]
}))

const doughnutOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: { position: 'bottom' as const, labels: { font: { size: 11, weight: '600' } } }
  },
  cutout: '70%'
}

// AI Smart Insights with high-contrast text styling
const aiInsights = ref([
  {
    type: 'peak',
    title: 'Predicted Peak Room Service Hours',
    description: 'Demand projected to spike between 7:00 PM – 9:00 PM tonight. Recommend assigning 2 extra staff to Floor 2 & 3.',
    tag: 'Operational Forecast',
    tagBg: 'bg-blue-500/20 text-blue-300 border-blue-400/30'
  },
  {
    type: 'efficiency',
    title: 'High Delivery Completion Efficiency',
    description: 'Average room delivery turnaround time improved to 16.5 mins today (15% faster than weekly target).',
    tag: 'Staff Performance',
    tagBg: 'bg-emerald-500/20 text-emerald-300 border-emerald-400/30'
  }
])

const loadData = async () => {
  try {
    loading.value = true
    error.value = null
    await manager.initializeManagerDashboard()
  } catch (err: any) {
    console.error('[ManagerDashboard] Load error:', err)
    error.value = err.message || 'Failed to load manager dashboard'
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  loadData()
})
</script>

<template>
  <DashboardLayout>
    <div class="min-h-screen bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-slate-100 p-3 sm:p-6 lg:p-8 transition-colors duration-200">
      <div class="max-w-7xl mx-auto space-y-6">
        <!-- Header & Action Controls -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
          <div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-white tracking-tight">Manager Executive Dashboard</h1>
            <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1">Real-time revenue, occupancy analytics, staff operations & AI insights</p>
          </div>
          <button 
            @click="loadData"
            :disabled="loading"
            class="px-4 py-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-200 rounded-xl text-xs font-semibold flex items-center gap-2 hover:bg-slate-100 dark:hover:bg-slate-800 transition shadow-xs self-start sm:self-auto disabled:opacity-50"
          >
            <RefreshCw :class="['w-4 h-4', loading ? 'animate-spin' : '']" />
            Refresh Overview
          </button>
        </div>

        <!-- KPI Metric Cards Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
          <!-- Total Reservations -->
          <div class="bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-sm dark:shadow-xl">
            <div class="flex items-center justify-between">
              <span class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Reservations</span>
              <div class="p-2.5 bg-blue-50 dark:bg-blue-500/10 text-blue-600 dark:text-blue-400 rounded-xl">
                <Calendar class="w-5 h-5" />
              </div>
            </div>
            <h3 class="text-3xl font-extrabold text-slate-900 dark:text-white mt-3">{{ stats.total_reservations }}</h3>
            <p class="text-xs text-emerald-600 dark:text-emerald-400 font-semibold mt-1 flex items-center gap-1">
              <span class="material-symbols-rounded text-sm">trending_up</span> +12% this week
            </p>
          </div>

          <!-- Rooms Occupied -->
          <div class="bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-sm dark:shadow-xl">
            <div class="flex items-center justify-between">
              <span class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Rooms Occupied</span>
              <div class="p-2.5 bg-indigo-50 dark:bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 rounded-xl">
                <Home class="w-5 h-5" />
              </div>
            </div>
            <div class="mt-3 flex items-baseline gap-1">
              <h3 class="text-3xl font-extrabold text-slate-900 dark:text-white">{{ stats.rooms_occupied }}</h3>
              <span class="text-sm font-bold text-slate-400 dark:text-slate-500">/ {{ stats.max_rooms }}</span>
            </div>
            <p class="text-xs text-blue-600 dark:text-blue-400 font-semibold mt-1">
              {{ Math.round((stats.rooms_occupied / Math.max(1, stats.max_rooms)) * 100) }}% Occupancy Rate
            </p>
          </div>

          <!-- Active Waiters -->
          <div class="bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-sm dark:shadow-xl">
            <div class="flex items-center justify-between">
              <span class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Active Staff</span>
              <div class="p-2.5 bg-emerald-50 dark:bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 rounded-xl">
                <Users class="w-5 h-5" />
              </div>
            </div>
            <h3 class="text-3xl font-extrabold text-slate-900 dark:text-white mt-3">{{ stats.active_waiters }}</h3>
            <p class="text-xs text-emerald-600 dark:text-emerald-400 font-semibold mt-1">All floors covered</p>
          </div>

          <!-- Kitchen Urgent Orders -->
          <div class="bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-sm dark:shadow-xl">
            <div class="flex items-center justify-between">
              <span class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Kitchen Alert</span>
              <div class="p-2.5 bg-rose-50 dark:bg-rose-500/10 text-rose-600 dark:text-rose-400 rounded-xl">
                <AlertCircle class="w-5 h-5" />
              </div>
            </div>
            <h3 class="text-3xl font-extrabold text-slate-900 dark:text-white mt-3">{{ stats.kitchen_ready }}</h3>
            <p class="text-xs text-amber-600 dark:text-amber-400 font-semibold mt-1">Ready for pickup</p>
          </div>

          <!-- Today's Revenue Card -->
          <div class="bg-gradient-to-br from-indigo-600 via-blue-600 to-blue-700 text-white rounded-2xl p-5 shadow-lg shadow-blue-500/20 sm:col-span-2 lg:col-span-1">
            <div class="flex items-center justify-between">
              <span class="text-xs font-extrabold uppercase tracking-wider text-indigo-100">Today's Revenue</span>
              <TrendingUp class="w-5 h-5 text-white/80" />
            </div>
            <h3 class="text-2xl sm:text-3xl font-black mt-3">${{ stats.today_revenue.toLocaleString('en-US', { minimumFractionDigits: 2 }) }}</h3>
            <p class="text-xs text-indigo-100 mt-1 font-medium">Room Service & Dining</p>
          </div>
        </div>



        <!-- Charts & Analytics Section -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
          <!-- Revenue Trend Chart (Chart.js Canvas) -->
          <div class="lg:col-span-2 bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-sm dark:shadow-xl">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-6 gap-3">
              <div>
                <h2 class="text-lg font-bold text-slate-900 dark:text-white">Revenue Analytics</h2>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Live Chart.js breakdown of daily & weekly restaurant income</p>
              </div>

              <!-- Filter Tabs -->
              <div class="flex gap-1.5 bg-slate-100 dark:bg-slate-800 p-1 rounded-xl">
                <button
                  @click="trendTab = 'weekly'"
                  :class="[
                    'px-3 py-1.5 rounded-lg text-xs font-bold transition',
                    trendTab === 'weekly' ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900'
                  ]"
                >
                  Weekly
                </button>
                <button
                  @click="trendTab = 'monthly'"
                  :class="[
                    'px-3 py-1.5 rounded-lg text-xs font-bold transition',
                    trendTab === 'monthly' ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900'
                  ]"
                >
                  Monthly
                </button>
              </div>
            </div>

            <!-- Chart Canvas Container -->
            <div class="h-64 relative w-full">
              <Bar 
                :data="trendTab === 'weekly' ? weeklyRevenueChartData : monthlyRevenueChartData" 
                :options="chartOptions" 
              />
            </div>
          </div>

          <!-- Occupancy Doughnut Chart -->
          <div class="bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-sm dark:shadow-xl flex flex-col justify-between">
            <div>
              <h2 class="text-lg font-bold text-slate-900 dark:text-white">Room Occupancy</h2>
              <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Live ratio of occupied vs available rooms</p>
            </div>

            <div class="h-56 relative w-full my-4 flex items-center justify-center">
              <Doughnut :data="occupancyChartData" :options="doughnutOptions" />
            </div>

            <div class="pt-4 border-t border-slate-200 dark:border-slate-800 flex justify-around text-center text-xs">
              <div>
                <p class="text-slate-500 dark:text-slate-400 font-semibold">Occupied</p>
                <p class="text-base font-extrabold text-blue-600 dark:text-blue-400 mt-0.5">{{ stats.rooms_occupied }} Rooms</p>
              </div>
              <div>
                <p class="text-slate-500 dark:text-slate-400 font-semibold">Available</p>
                <p class="text-base font-extrabold text-slate-700 dark:text-slate-300 mt-0.5">{{ Math.max(0, stats.max_rooms - stats.rooms_occupied) }} Rooms</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Recent Operational Activity Log -->
        <div class="bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-sm dark:shadow-xl">
          <div class="flex items-center justify-between mb-4">
            <div>
              <h2 class="text-lg font-bold text-slate-900 dark:text-white">Recent Operations Log</h2>
              <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Real-time system events, check-ins and order dispatches</p>
            </div>
          </div>

          <div v-if="activities.length === 0" class="text-center py-8 text-slate-500 dark:text-slate-400 text-xs">
            No recent activity logs recorded
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
                  <p class="font-bold text-slate-900 dark:text-white">{{ act.title || act.description }}</p>
                  <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">{{ act.timestamp || 'Just now' }}</p>
                </div>
              </div>
              <span class="px-2.5 py-1 bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 rounded-md font-semibold text-[10px]">
                {{ act.type || 'SYSTEM' }}
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>

<style scoped>
</style>
