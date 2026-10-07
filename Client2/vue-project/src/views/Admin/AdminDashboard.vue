<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import DashboardLayout from '../../Layouts/DashboardLayout.vue'
import MonthlyRevenueChart from '../../components/dashboard/MonthlyRevenueChart.vue'
import RoomStatusChart from '../../components/dashboard/RoomStatusChart.vue'
import RecentReservationsTable from '../../components/dashboard/RecentReservationsTable.vue'
import StaffActivityWidget from '../../components/dashboard/StaffActivityWidget.vue'
import MaintenanceAlerts from '../../components/dashboard/MaintenanceAlerts.vue'

import {
  RefreshCw,
  Plus,
  Users,
  BedDouble,
  DollarSign,
  ArrowUpRight,
  Building2,
  Percent,
  AlertTriangle,
  TrendingUp
} from 'lucide-vue-next'

import { getDashboard } from '../../services/dashboardService'
import type { DashboardData } from '../../types/dashboard'
import { useAuthStore } from '../../stores/auth'
import { useHotelStore } from '../../stores/hotelStore'
import axios from '../../services/axios'

const router = useRouter()
const auth = useAuthStore()
const hotelStore = useHotelStore()

// State
const dashboard = ref<DashboardData | null>(null)
const loading = ref<boolean>(true)
const refreshing = ref<boolean>(false)
const platformStats = ref<Record<string, any>>({})

const getCacheKey = () => `admin_dashboard_cache_${hotelStore.hotelId || 'platform'}`

const ensureArray = <T = any>(val: any): T[] => {
  if (Array.isArray(val)) return val
  if (val && typeof val === 'object') return Object.values(val)
  return []
}

const normalizeDashboardData = (data: any): DashboardData => {
  if (!data || typeof data !== 'object') return data
  return {
    ...data,
    recentReservations: ensureArray(data.recentReservations),
    staffActivity: ensureArray(data.staffActivity),
    maintenanceAlerts: ensureArray(data.maintenanceAlerts),
  }
}

const restoreCachedData = () => {
  try {
    const cached = localStorage.getItem(getCacheKey())
    if (cached) {
      const parsed = JSON.parse(cached)
      if (parsed && typeof parsed === 'object') {
        dashboard.value = normalizeDashboardData(parsed)
        loading.value = false
      }
    }
  } catch (e) {
    // Ignore cache parse error
  }
}

const loadPlatformStats = async () => {
  if (!auth.isPlatformAdmin) return
  try {
    const statsRes = await axios.get('/platform/statistics')
    platformStats.value = statsRes.data.data || {}
  } catch (err) {
    console.error('[AdminDashboard] Failed to load platform stats:', err)
  }
}

const loadDashboard = async (isManualRefresh = false) => {
  try {
    if (!dashboard.value) {
      loading.value = true
    }

    const response = await getDashboard(isManualRefresh ? { refresh: 'true' } : {})
    const freshData = normalizeDashboardData(response.data || response)
    dashboard.value = freshData

    try {
      localStorage.setItem(getCacheKey(), JSON.stringify(freshData))
    } catch (e) {}

    if (auth.isPlatformAdmin) {
      await loadPlatformStats()
    }
  } catch (error) {
    console.error('[AdminDashboard] Failed to load dashboard:', error)
  } finally {
    loading.value = false
  }
}

const refreshDashboard = async () => {
  refreshing.value = true
  await loadDashboard(true)
  refreshing.value = false
}

const formatRevenue = (value?: number): string => {
  return (value ?? 0).toLocaleString()
}

onMounted(() => {
  restoreCachedData()
  loadDashboard()
})

watch(() => hotelStore.hotelId, () => {
  restoreCachedData()
  loadDashboard()
})
</script>

<template>
  <DashboardLayout>
    <div class="space-y-6 bg-slate-50 dark:bg-slate-950 min-h-screen p-4 sm:p-6 max-w-full font-sans transition-colors">
      <!-- HEADER CARD -->
      <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-6 rounded-3xl shadow-xs flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
        <div>
          <div class="flex flex-wrap items-center gap-2.5">
            <h1 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">
              Administrator Dashboard
            </h1>
            <span
              v-if="hotelStore.hotelName"
              class="px-3 py-1 rounded-xl text-xs font-bold bg-primary-500/10 text-primary-600 dark:text-primary-400 border border-primary-500/20 flex items-center gap-1.5"
            >
              <Building2 class="w-3.5 h-3.5" />
              {{ hotelStore.hotelName }}
            </span>

            <span
              v-if="auth.isPlatformAdmin"
              class="px-2.5 py-0.5 rounded-full text-xs font-black bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 border border-indigo-500/20"
            >
              Super Admin
            </span>
            <span
              v-else
              class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20"
            >
              Hotel Admin
            </span>
          </div>
          <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1 font-medium">
            Real-time overview of hotel rooms, occupancy, revenue, staff operations, and guests.
          </p>
        </div>

        <div class="flex items-center gap-2.5 flex-wrap">
          <button
            @click="refreshDashboard"
            :disabled="refreshing"
            class="px-4 py-2.5 rounded-2xl border border-slate-200 dark:border-slate-800 hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 font-bold text-xs flex items-center gap-2 transition cursor-pointer"
          >
            <RefreshCw class="w-4 h-4" :class="{ 'animate-spin': refreshing || loading }" />
            <span>{{ refreshing ? 'Refreshing...' : 'Refresh' }}</span>
          </button>

          <button
            v-if="auth.isPlatformAdmin"
            @click="router.push('/admin/hotels')"
            class="px-4 py-2.5 rounded-2xl bg-blue-600 hover:bg-blue-500 text-white font-bold text-xs flex items-center gap-2 shadow-md shadow-blue-600/20 transition cursor-pointer"
          >
            <Plus class="w-4 h-4" />
            <span>Manage Hotels</span>
          </button>
        </div>
      </div>

      <!-- LOADING SKELETON -->
      <div v-if="loading && !dashboard" class="py-24 text-center bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-xs">
        <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-primary-500 mx-auto mb-3"></div>
        <p class="text-xs font-semibold text-slate-500 dark:text-slate-400">Loading hotel dashboard metrics...</p>
      </div>

      <!-- MAIN DASHBOARD CONTENT -->
      <div v-else class="space-y-6">
        <!-- 4 OVERVIEW STAT CARDS -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
          <!-- Total Rooms -->
          <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-5 shadow-xs flex items-center justify-between">
            <div class="space-y-1">
              <p class="text-xs font-extrabold uppercase tracking-wider text-slate-400 dark:text-slate-500">Total Rooms</p>
              <h3 class="text-2xl font-black text-slate-900 dark:text-white">
                {{ dashboard?.overview?.totalRooms ?? 0 }}
              </h3>
              <p class="text-[11px] text-slate-500 font-medium">
                {{ dashboard?.roomStatistics?.available ?? 0 }} available right now
              </p>
            </div>
            <div class="p-3.5 rounded-2xl bg-blue-500/10 text-blue-500 border border-blue-500/20">
              <BedDouble class="w-6 h-6" />
            </div>
          </div>

          <!-- Occupancy Rate -->
          <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-5 shadow-xs flex items-center justify-between">
            <div class="space-y-1">
              <p class="text-xs font-extrabold uppercase tracking-wider text-slate-400 dark:text-slate-500">Occupancy Rate</p>
              <h3 class="text-2xl font-black text-emerald-600 dark:text-emerald-400">
                {{ dashboard?.overview?.occupancyRate ?? 0 }}%
              </h3>
              <p class="text-[11px] text-slate-500 font-medium">
                {{ dashboard?.roomStatistics?.occupied ?? 0 }} rooms currently occupied
              </p>
            </div>
            <div class="p-3.5 rounded-2xl bg-emerald-500/10 text-emerald-500 border border-emerald-500/20">
              <Percent class="w-6 h-6" />
            </div>
          </div>

          <!-- Active Staff -->
          <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-5 shadow-xs flex items-center justify-between">
            <div class="space-y-1">
              <p class="text-xs font-extrabold uppercase tracking-wider text-slate-400 dark:text-slate-500">Active Staff</p>
              <h3 class="text-2xl font-black text-indigo-600 dark:text-indigo-400">
                {{ dashboard?.overview?.activeStaff ?? 0 }}
              </h3>
              <p class="text-[11px] text-slate-500 font-medium">
                {{ dashboard?.overview?.totalUsers ?? 0 }} registered hotel users
              </p>
            </div>
            <div class="p-3.5 rounded-2xl bg-indigo-500/10 text-indigo-500 border border-indigo-500/20">
              <Users class="w-6 h-6" />
            </div>
          </div>

          <!-- Today's Revenue -->
          <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-5 shadow-xs flex items-center justify-between">
            <div class="space-y-1">
              <p class="text-xs font-extrabold uppercase tracking-wider text-slate-400 dark:text-slate-500">Today's Revenue</p>
              <h3 class="text-2xl font-black text-amber-500">
                {{ formatRevenue(dashboard?.overview?.todayRevenue) }} ETB
              </h3>
              <p class="text-[11px] text-slate-500 font-medium">From room reservations & orders</p>
            </div>
            <div class="p-3.5 rounded-2xl bg-amber-500/10 text-amber-500 border border-amber-500/20">
              <DollarSign class="w-6 h-6" />
            </div>
          </div>
        </div>

        <!-- CHARTS SECTION: MONTHLY REVENUE & ROOM STATUS -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
          <div class="lg:col-span-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 shadow-xs">
            <div class="flex items-center justify-between mb-4">
              <div>
                <h3 class="text-base font-black text-slate-900 dark:text-white">Revenue Performance</h3>
                <p class="text-xs text-slate-500">Last 6 months revenue trajectory for this hotel</p>
              </div>
              <div class="p-2 rounded-xl bg-primary-500/10 text-primary-500">
                <TrendingUp class="w-4 h-4" />
              </div>
            </div>
            <MonthlyRevenueChart :revenue-data="dashboard?.monthlyRevenue" />
          </div>

          <!-- Room Status Chart (1 col) -->
          <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 shadow-xs">
            <div class="flex items-center justify-between mb-4">
              <div>
                <h3 class="text-base font-black text-slate-900 dark:text-white">Room Inventory</h3>
                <p class="text-xs text-slate-500">Occupancy & availability status</p>
              </div>
              <div class="p-2 rounded-xl bg-blue-500/10 text-blue-500">
                <BedDouble class="w-4 h-4" />
              </div>
            </div>
            <RoomStatusChart :room-stats="dashboard?.roomStatistics" />
          </div>
        </div>

        <!-- RECENT RESERVATIONS & OPERATIONAL ACTIVITY -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
          <!-- Recent Reservations Table (2 cols) -->
          <div class="lg:col-span-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 shadow-xs">
            <div class="flex items-center justify-between mb-4">
              <div>
                <h3 class="text-base font-black text-slate-900 dark:text-white">Recent Reservations</h3>
                <p class="text-xs text-slate-500">Latest guest bookings & check-ins</p>
              </div>
              <button
                @click="router.push('/reservations')"
                class="text-xs font-bold text-primary-600 hover:text-primary-700 dark:text-primary-400 flex items-center gap-1 cursor-pointer"
              >
                <span>View All</span>
                <ArrowUpRight class="w-3.5 h-3.5" />
              </button>
            </div>
            <RecentReservationsTable :reservations="dashboard?.recentReservations || []" />
          </div>

          <!-- Staff Activity & Maintenance Alerts (1 col) -->
          <div class="space-y-6">
            <!-- Staff Activity Widget -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 shadow-xs">
              <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-black text-slate-900 dark:text-white">Staff Activity</h3>
                <div class="p-1.5 rounded-xl bg-indigo-500/10 text-indigo-500">
                  <Users class="w-4 h-4" />
                </div>
              </div>
              <StaffActivityWidget :activities="dashboard?.staffActivity || []" />
            </div>

            <!-- Maintenance Alerts Widget -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 shadow-xs">
              <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-black text-slate-900 dark:text-white">Maintenance Alerts</h3>
                <div class="p-1.5 rounded-xl bg-amber-500/10 text-amber-500">
                  <AlertTriangle class="w-4 h-4" />
                </div>
              </div>
              <MaintenanceAlerts :alerts="dashboard?.maintenanceAlerts || []" />
            </div>
          </div>
        </div>

        <!-- PLATFORM SUPER ADMIN SECTION (SHOWN ONLY TO SUPER ADMIN) -->
        <div v-if="auth.isPlatformAdmin" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 shadow-xs space-y-4">
          <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-4">
            <div>
              <h3 class="text-lg font-black text-slate-900 dark:text-white">Platform Multi-Hotel Overview</h3>
              <p class="text-xs text-slate-500">Total hotels enrolled and system-wide stats</p>
            </div>
            <button
              @click="router.push('/admin/hotels')"
              class="px-4 py-2 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-xs font-bold text-slate-700 dark:text-slate-200 transition cursor-pointer"
            >
              Manage Hotels
            </button>
          </div>

          <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 pt-2">
            <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-800">
              <p class="text-[11px] font-extrabold uppercase text-slate-400">Total Hotels</p>
              <h4 class="text-xl font-black text-slate-900 dark:text-white mt-1">{{ platformStats?.total_hotels ?? 0 }}</h4>
            </div>
            <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-800">
              <p class="text-[11px] font-extrabold uppercase text-slate-400">Active Hotels</p>
              <h4 class="text-xl font-black text-emerald-500 mt-1">{{ platformStats?.active_hotels ?? 0 }}</h4>
            </div>
            <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-800">
              <p class="text-[11px] font-extrabold uppercase text-slate-400">Total Platform Users</p>
              <h4 class="text-xl font-black text-indigo-500 mt-1">{{ platformStats?.total_users ?? 0 }}</h4>
            </div>
            <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-800">
              <p class="text-[11px] font-extrabold uppercase text-slate-400">Total Rooms Enrolled</p>
              <h4 class="text-xl font-black text-amber-500 mt-1">{{ platformStats?.total_rooms ?? 0 }}</h4>
            </div>
          </div>
        </div>

      </div>
    </div>
  </DashboardLayout>
</template>
