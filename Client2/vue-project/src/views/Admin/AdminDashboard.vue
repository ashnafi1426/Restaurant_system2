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
  RefreshCw, Plus, Users, ShieldCheck, BedDouble, DollarSign,
  ArrowUpRight, Building2, Percent, AlertTriangle, Layers, TrendingUp
} from 'lucide-vue-next'

import { getDashboard } from '../../services/dashboardService'
import type { DashboardData } from '../../types/dashboard'
import { useAuthStore } from '../../stores/auth'
import { useHotelStore } from '../../stores/hotelStore'
import { useLanguageStore } from '../../stores/language'
import axios from '../../services/axios'

const router = useRouter()
const auth = useAuthStore()
const hotelStore = useHotelStore()
const languageStore = useLanguageStore()

const dashboard = ref<DashboardData | null>(null)
const loading = ref<boolean>(true)
const errorOccurred = ref<boolean>(false)
const refreshing = ref<boolean>(false)

// Platform Super Admin States
const platformStats = ref<any>({})
const platformHotels = ref<any[]>([])
const showAddHotelModal = ref(false)
const selectedHotelDetails = ref<any>(null)
const newHotelForm = ref({
  name: '',
  slug: '',
  city: '',
  country: 'Ethiopia',
  admin_email: '',
  admin_first_name: '',
  admin_last_name: '',
})

const loadPlatformData = async () => {
  if (!auth.isPlatformAdmin) return
  try {
    const [statsRes, hotelsRes] = await Promise.all([
      axios.get('/platform/statistics'),
      axios.get('/platform/hotels'),
    ])
    platformStats.value = statsRes.data.data || {}
    platformHotels.value = hotelsRes.data.data?.data || []
  } catch (err) {
    console.error('Failed to load platform data:', err)
  }
}

const submitNewHotel = async () => {
  try {
    await axios.post('/platform/hotels', newHotelForm.value)
    showAddHotelModal.value = false
    await loadPlatformData()
    await hotelStore.loadHotels()
  } catch (err) {
    console.error('Failed to create hotel:', err)
  }
}

const toggleHotelStatus = async (hotelId: string, newStatus: string) => {
  try {
    await axios.patch(`/platform/hotels/${hotelId}/status`, { status: newStatus })
    await loadPlatformData()
  } catch (err) {
    console.error('Failed to update hotel status:', err)
  }
}

const archiveHotel = async (hotelId: string, name: string) => {
  if (!confirm(`Are you sure you want to archive "${name}"? All data remains preserved.`)) return
  try {
    await axios.post(`/platform/hotels/${hotelId}/archive`)
    await loadPlatformData()
  } catch (err) {
    console.error('Failed to archive hotel:', err)
  }
}

const loadDashboard = async () => {
  try {
    errorOccurred.value = false
    loading.value = true
    const response = await getDashboard()
    dashboard.value = response.data || response
    if (auth.isPlatformAdmin) {
      await loadPlatformData()
    }
  } catch (error) {
    console.error('Failed to load dashboard:', error)
    errorOccurred.value = true
  } finally {
    loading.value = false
  }
}

const refreshDashboard = async () => {
  refreshing.value = true
  await loadDashboard()
  refreshing.value = false
}

onMounted(() => {
  loadDashboard()
})

watch(() => hotelStore.hotelId, () => {
  loadDashboard()
})
</script>

<template>
  <DashboardLayout>
    <div class="space-y-6 bg-slate-50 dark:bg-slate-950 min-h-screen p-4 sm:p-6 max-w-full font-sans transition-colors">
      <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-6 rounded-3xl shadow-xs flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
        <div>
          <div class="flex flex-wrap items-center gap-2.5">
            <h1 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">
              {{ languageStore.t('administrator_dashboard', 'Administrator Dashboard') }}
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
             {{ languageStore.t('super_admin', 'Super Admin') }}
            </span>
            <span
              v-else
              class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20"
            >
              {{ languageStore.t('hotel_admin', 'Hotel Admin') }}
            </span>
          </div>
          <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1 font-medium">
            {{ languageStore.t('dashboard_overview_desc', 'Real-time overview of hotel rooms, occupancy, revenue, staff operations, and guests.') }}
          </p>
        </div>

        <div class="flex items-center gap-2.5 flex-wrap">
          <button
            @click="refreshDashboard"
            :disabled="refreshing"
            class="px-4 py-2.5 rounded-2xl border border-slate-200 dark:border-slate-800 hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 font-bold text-xs flex items-center gap-2 transition cursor-pointer"
          >
            <RefreshCw class="w-4 h-4" :class="{ 'animate-spin': refreshing || loading }" />
            <span>{{ refreshing ? languageStore.t('refreshing', 'Refreshing...') : languageStore.t('refresh', 'Refresh') }}</span>
          </button>

          <button
            v-if="auth.isPlatformAdmin"
            @click="showAddHotelModal = true"
            class="px-4 py-2.5 rounded-2xl bg-blue-600 hover:bg-blue-500 text-white font-bold text-xs flex items-center gap-2 shadow-md shadow-blue-600/20 transition cursor-pointer"
          >
            <Plus class="w-4 h-4" />
            <span>{{ languageStore.t('onboard_hotel', 'Onboard Hotel') }}</span>
          </button>
        </div>
      </div>

      <!-- LOADING SKELETON -->
      <div v-if="loading && !dashboard" class="py-24 text-center bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-xs">
        <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-primary-500 mx-auto mb-3"></div>
        <p class="text-xs font-semibold text-slate-500 dark:text-slate-400">{{ languageStore.t('loading_dashboard_metrics', 'Loading hotel dashboard metrics...') }}</p>
      </div>

      <!-- MAIN DASHBOARD CONTENT -->
      <div v-else class="space-y-6">

        <!-- 4 OVERVIEW STAT CARDS -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
          <!-- Total Rooms -->
          <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-5 shadow-xs flex items-center justify-between">
            <div class="space-y-1">
              <p class="text-xs font-extrabold uppercase tracking-wider text-slate-400 dark:text-slate-500">{{ languageStore.t('total_rooms', 'Total Rooms') }}</p>
              <h3 class="text-2xl font-black text-slate-900 dark:text-white">
                {{ dashboard?.overview?.totalRooms ?? 0 }}
              </h3>
              <p class="text-[11px] text-slate-500 font-medium">
                {{ dashboard?.roomStatistics?.available ?? 0 }} {{ languageStore.t('available_right_now', 'available right now') }}
              </p>
            </div>
            <div class="p-3.5 rounded-2xl bg-blue-500/10 text-blue-500 border border-blue-500/20">
              <BedDouble class="w-6 h-6" />
            </div>
          </div>

          <!-- Occupancy Rate -->
          <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-5 shadow-xs flex items-center justify-between">
            <div class="space-y-1">
              <p class="text-xs font-extrabold uppercase tracking-wider text-slate-400 dark:text-slate-500">{{ languageStore.t('occupancy_rate', 'Occupancy Rate') }}</p>
              <h3 class="text-2xl font-black text-emerald-600 dark:text-emerald-400">
                {{ dashboard?.overview?.occupancyRate ?? 0 }}%
              </h3>
              <p class="text-[11px] text-slate-500 font-medium">
                {{ dashboard?.roomStatistics?.occupied ?? 0 }} {{ languageStore.t('rooms_currently_occupied', 'rooms currently occupied') }}
              </p>
            </div>
            <div class="p-3.5 rounded-2xl bg-emerald-500/10 text-emerald-500 border border-emerald-500/20">
              <Percent class="w-6 h-6" />
            </div>
          </div>

          <!-- Active Staff -->
          <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-5 shadow-xs flex items-center justify-between">
            <div class="space-y-1">
              <p class="text-xs font-extrabold uppercase tracking-wider text-slate-400 dark:text-slate-500">{{ languageStore.t('active_staff', 'Active Staff') }}</p>
              <h3 class="text-2xl font-black text-indigo-600 dark:text-indigo-400">
                {{ dashboard?.overview?.activeStaff ?? 0 }}
              </h3>
              <p class="text-[11px] text-slate-500 font-medium">
                {{ dashboard?.overview?.totalUsers ?? 0 }} {{ languageStore.t('registered_hotel_users', 'registered hotel users') }}
              </p>
            </div>
            <div class="p-3.5 rounded-2xl bg-indigo-500/10 text-indigo-500 border border-indigo-500/20">
              <Users class="w-6 h-6" />
            </div>
          </div>

          <!-- Today's Revenue -->
          <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-5 shadow-xs flex items-center justify-between">
            <div class="space-y-1">
              <p class="text-xs font-extrabold uppercase tracking-wider text-slate-400 dark:text-slate-500">{{ languageStore.t('todays_revenue', "Today's Revenue") }}</p>
              <h3 class="text-2xl font-black text-amber-500">
                {{ (dashboard?.overview?.todayRevenue ?? 0).toLocaleString() }} ETB
              </h3>
              <p class="text-[11px] text-slate-500 font-medium">{{ languageStore.t('from_reservations_orders', 'From room reservations & orders') }}</p>
            </div>
            <div class="p-3.5 rounded-2xl bg-amber-500/10 text-amber-500 border border-amber-500/20">
              <DollarSign class="w-6 h-6" />
            </div>
          </div>
        </div>

        <!-- CHARTS SECTION: MONTHLY REVENUE & ROOM STATUS -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
          <!-- Monthly Revenue Chart (2 cols) -->
          <div class="lg:col-span-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 shadow-xs">
            <div class="flex items-center justify-between mb-4">
              <div>
                <h3 class="text-base font-black text-slate-900 dark:text-white">{{ languageStore.t('revenue_performance', 'Revenue Performance') }}</h3>
                <p class="text-xs text-slate-500">{{ languageStore.t('revenue_performance_desc', 'Last 6 months revenue trajectory for this hotel') }}</p>
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
                <h3 class="text-base font-black text-slate-900 dark:text-white">{{ languageStore.t('room_inventory', 'Room Inventory') }}</h3>
                <p class="text-xs text-slate-500">{{ languageStore.t('room_inventory_desc', 'Occupancy & availability status') }}</p>
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
                <h3 class="text-base font-black text-slate-900 dark:text-white">{{ languageStore.t('recent_reservations', 'Recent Reservations') }}</h3>
                <p class="text-xs text-slate-500">{{ languageStore.t('recent_reservations_desc', 'Latest guest bookings & check-ins') }}</p>
              </div>
              <button
                @click="router.push('/reservations')"
                class="text-xs font-bold text-primary-600 hover:text-primary-700 dark:text-primary-400 flex items-center gap-1 cursor-pointer"
              >
                <span>{{ languageStore.t('view_all', 'View All') }}</span>
                <ArrowUpRight class="w-3.5 h-3.5" />
              </button>
            </div>
            <RecentReservationsTable :reservations="dashboard?.recentReservations" />
          </div>

          <!-- Staff Activity & Maintenance Alerts (1 col) -->
          <div class="space-y-6">
            <!-- Staff Activity Widget -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 shadow-xs">
              <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-black text-slate-900 dark:text-white">{{ languageStore.t('staff_activity', 'Staff Activity') }}</h3>
                <div class="p-1.5 rounded-xl bg-indigo-500/10 text-indigo-500">
                  <Users class="w-4 h-4" />
                </div>
              </div>
              <StaffActivityWidget :activities="dashboard?.staffActivity" />
            </div>

            <!-- Maintenance Alerts Widget -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 shadow-xs">
              <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-black text-slate-900 dark:text-white">{{ languageStore.t('maintenance_alerts', 'Maintenance Alerts') }}</h3>
                <div class="p-1.5 rounded-xl bg-amber-500/10 text-amber-500">
                  <AlertTriangle class="w-4 h-4" />
                </div>
              </div>
              <MaintenanceAlerts :alerts="dashboard?.maintenanceAlerts" />
            </div>
          </div>
        </div>

        <!-- PLATFORM SUPER ADMIN SECTION (SHOWN ONLY TO SUPER ADMIN) -->
        <div v-if="auth.isPlatformAdmin" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 shadow-xs space-y-4">
          <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-4">
            <div>
              <h3 class="text-lg font-black text-slate-900 dark:text-white">{{ languageStore.t('platform_multi_hotel_overview', 'Platform Multi-Hotel Overview') }}</h3>
              <p class="text-xs text-slate-500">{{ languageStore.t('platform_multi_hotel_desc', 'Total hotels enrolled and system-wide stats') }}</p>
            </div>
            <button
              @click="router.push('/admin/hotels')"
              class="px-4 py-2 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-xs font-bold text-slate-700 dark:text-slate-200 transition cursor-pointer"
            >
              {{ languageStore.t('manage_hotels', 'Manage Hotels') }}
            </button>
          </div>

          <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 pt-2">
            <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-800">
              <p class="text-[11px] font-extrabold uppercase text-slate-400">{{ languageStore.t('total_hotels', 'Total Hotels') }}</p>
              <h4 class="text-xl font-black text-slate-900 dark:text-white mt-1">{{ platformStats?.total_hotels ?? 0 }}</h4>
            </div>
            <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-800">
              <p class="text-[11px] font-extrabold uppercase text-slate-400">{{ languageStore.t('active_hotels', 'Active Hotels') }}</p>
              <h4 class="text-xl font-black text-emerald-500 mt-1">{{ platformStats?.active_hotels ?? 0 }}</h4>
            </div>
            <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-800">
              <p class="text-[11px] font-extrabold uppercase text-slate-400">{{ languageStore.t('total_platform_users', 'Total Platform Users') }}</p>
              <h4 class="text-xl font-black text-indigo-500 mt-1">{{ platformStats?.total_users ?? 0 }}</h4>
            </div>
            <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-800">
              <p class="text-[11px] font-extrabold uppercase text-slate-400">{{ languageStore.t('total_rooms_enrolled', 'Total Rooms Enrolled') }}</p>
              <h4 class="text-xl font-black text-amber-500 mt-1">{{ platformStats?.total_rooms ?? 0 }}</h4>
            </div>
          </div>
        </div>

      </div>
    </div>
  </DashboardLayout>
</template>
