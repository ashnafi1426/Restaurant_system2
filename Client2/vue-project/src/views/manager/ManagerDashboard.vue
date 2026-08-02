<script setup lang="ts">
import { onMounted, ref, computed } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useManagerStore } from '@/stores/managerStore'
import DashboardLayout from '../../Layouts/DashboardLayout.vue'
import { Calendar, Home, Users, AlertCircle, TrendingUp, Download } from 'lucide-vue-next'

const auth = useAuthStore()
const manager = useManagerStore()
const showLoginToast = ref(false)
const trendTab = ref('weekly')
const stats = computed(() => ({
  total_reservations: manager.dashboardStats.totalReservations,
  rooms_occupied: manager.dashboardStats.occupiedRooms,
  max_rooms: manager.dashboardStats.totalRooms,
  active_waiters: manager.dashboardStats.activeStaff,
  kitchen_ready: manager.dashboardStats.preparingOrders,
  today_revenue: manager.dashboardStats.todayRevenue,
}))
const activities = computed(() => manager.dashboardActivities || [])

const getActivityIcon = (activity: any): string => {
  if (activity.icon) {
    const iconMap: Record<string, string> = {
      'checkin': '🏠',
      'alert': '⚠️',
      'star': '⭐',
      'building': '🏢',
      'clock': '🕐',
    }
    return iconMap[activity.icon as string] || '📌'
  }
  return '📌'
}

const getActivityColor = (activity: any): string => {
  if (activity.color) {
    const colorMap: Record<string, string> = {
      'emerald': 'bg-emerald-50',
      'red': 'bg-red-50',
      'amber': 'bg-amber-50',
      'blue': 'bg-blue-50',
      'slate': 'bg-slate-50',
    }
    return colorMap[activity.color as string] || 'bg-slate-50'
  }
  return 'bg-slate-50'
}

onMounted(async () => {
  console.log('\n========== [ManagerDashboard.vue] MOUNT START ==========')
  console.log('[ManagerDashboard] 📍 Component mounted at:', new Date().toLocaleTimeString())
  
  // PART 1: Authentication Check
  console.log('\n>>> PART 1: Authentication Check')
  console.log('[ManagerDashboard] Auth token present:', !!auth.token)
  console.log('[ManagerDashboard] User role:', auth.user?.role)
  console.log('[ManagerDashboard] User ID:', auth.user?.id)

  if (!auth.token) {
    console.error('[ManagerDashboard] ❌ FAIL: No auth token - cannot proceed')
    return
  }
  console.log('[ManagerDashboard] ✅ PASS: Authentication verified')

  // PART 2: Store State Check
  console.log('\n>>> PART 2: Store Initial State')
  console.log('[ManagerDashboard] Store dashboardStats BEFORE fetch:', manager.dashboardStats)
  console.log('[ManagerDashboard] Store dashboardActivities BEFORE fetch:', manager.dashboardActivities)
  console.log('[ManagerDashboard] Store loading state:', manager.dashboardLoading)
  console.log('[ManagerDashboard] Store error state:', manager.dashboardError)

  // PART 3: Initialize Dashboard
  console.log('\n>>> PART 3: Initialize Dashboard')
  try {
    console.log('[ManagerDashboard] 🔄 Calling initializeManagerDashboard()...')
    const startTime = performance.now()
    
    await manager.initializeManagerDashboard()
    
    const duration = (performance.now() - startTime).toFixed(2)
    console.log(`[ManagerDashboard] ✅ Initialization complete in ${duration}ms`)
  } catch (err: any) {
    console.error('[ManagerDashboard] ❌ FAIL: Error during initialization')
    console.error('[ManagerDashboard] Error message:', err.message)
    console.error('[ManagerDashboard] Full error:', err)
  }

  // PART 4: Store State After Fetch
  console.log('\n>>> PART 4: Store State After Fetch')
  console.log('[ManagerDashboard] Store dashboardStats AFTER fetch:', manager.dashboardStats)
  console.log('[ManagerDashboard] Store dashboardActivities AFTER fetch:', manager.dashboardActivities)
  console.log('[ManagerDashboard] Computed stats from component:', {
    total_reservations: stats.value.total_reservations,
    rooms_occupied: stats.value.rooms_occupied,
    max_rooms: stats.value.max_rooms,
    active_waiters: stats.value.active_waiters,
    kitchen_ready: stats.value.kitchen_ready,
    today_revenue: stats.value.today_revenue,
  })
  console.log('[ManagerDashboard] Activities count:', activities.value.length)

  // PART 5: Login Toast
  console.log('\n>>> PART 5: Login Toast')
  const loginSuccess = sessionStorage.getItem('loginSuccess')
  if (loginSuccess) {
    try {
      showLoginToast.value = true
      console.log('[ManagerDashboard] ℹ️ Login success toast shown')
      setTimeout(() => {
        showLoginToast.value = false
        sessionStorage.removeItem('loginSuccess')
      }, 3000)
    } catch (e) {
      console.error('[ManagerDashboard] Error handling login toast:', e)
    }
  }

  console.log('\n========== [ManagerDashboard.vue] MOUNT COMPLETE ==========\n')
})
</script>

<template>
  <DashboardLayout>
    <div class="min-h-screen bg-gradient-to-br from-slate-50 via-white to-blue-50/30 dark:from-slate-950 dark:via-slate-900 dark:to-slate-900 p-6 transition-colors duration-300">
      <!-- Welcome Header -->
      <div class="mb-8">
        <h1 class="text-4xl font-bold text-slate-900 dark:text-slate-100">Welcome back, Manager</h1>
        <p class="text-slate-600 dark:text-slate-400 mt-2">Here is what's happening at Executive Horizon this morning.</p>
      </div>

      <!-- Stats Cards Grid -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 mb-8">
        <!-- Total Reservations -->
        <div class="bg-white dark:bg-slate-900/50 rounded-lg border border-slate-200 dark:border-slate-700 p-6 shadow-sm hover:shadow-md transition">
          <div class="flex items-center justify-between mb-4">
            <Calendar class="w-8 h-8 text-slate-400 dark:text-slate-500" />
          </div>
          <p class="text-xs text-slate-500 dark:text-slate-400 uppercase font-semibold tracking-wide mb-1">Total</p>
          <p class="text-xs text-slate-500 dark:text-slate-400 uppercase font-semibold tracking-wide">Reservations</p>
          <h3 class="text-4xl font-bold text-slate-900 dark:text-slate-100 mt-2">{{ stats.total_reservations }}</h3>
          <p class="text-xs text-emerald-600 dark:text-emerald-400 font-semibold mt-2">+12%</p>
        </div>

        <!-- Rooms Occupied -->
        <div class="bg-white dark:bg-slate-900/50 rounded-lg border border-slate-200 dark:border-slate-700 p-6 shadow-sm hover:shadow-md transition">
          <div class="flex items-center justify-between mb-4">
            <Home class="w-8 h-8 text-slate-400 dark:text-slate-500" />
          </div>
          <p class="text-xs text-slate-500 dark:text-slate-400 uppercase font-semibold tracking-wide mb-1">Rooms</p>
          <p class="text-xs text-slate-500 dark:text-slate-400 uppercase font-semibold tracking-wide">Occupied</p>
          <h3 class="text-4xl font-bold text-slate-900 dark:text-slate-100 mt-2">{{ stats.rooms_occupied }}<span class="text-lg text-slate-400 dark:text-slate-500">/{{ stats.max_rooms }}</span></h3>
          <p class="text-xs text-blue-600 dark:text-blue-400 font-semibold mt-2">● 88%</p>
        </div>

        <!-- Active Waiters -->
        <div class="bg-white dark:bg-slate-900/50 rounded-lg border border-slate-200 dark:border-slate-700 p-6 shadow-sm hover:shadow-md transition">
          <div class="flex items-center justify-between mb-4">
            <Users class="w-8 h-8 text-slate-400 dark:text-slate-500" />
          </div>
          <p class="text-xs text-slate-500 dark:text-slate-400 uppercase font-semibold tracking-wide">Active</p>
          <p class="text-xs text-slate-500 dark:text-slate-400 uppercase font-semibold tracking-wide">Waiters</p>
          <h3 class="text-4xl font-bold text-slate-900 dark:text-slate-100 mt-2">{{ stats.active_waiters }}</h3>
        </div>

        <!-- Kitchen Ready -->
        <div class="bg-white dark:bg-slate-900/50 rounded-lg border border-slate-200 dark:border-slate-700 p-6 shadow-sm hover:shadow-md transition">
          <div class="flex items-center justify-between mb-4">
            <AlertCircle class="w-8 h-8 text-red-500 dark:text-red-400" />
          </div>
          <p class="text-xs text-slate-500 dark:text-slate-400 uppercase font-semibold tracking-wide">Kitchen</p>
          <p class="text-xs text-red-600 dark:text-red-400 uppercase font-semibold tracking-wide">URGENT</p>
          <h3 class="text-4xl font-bold text-red-600 dark:text-red-400 mt-2">{{ stats.kitchen_ready }}</h3>
        </div>

        <!-- Today's Revenue -->
        <div class="bg-gradient-to-br from-blue-600 to-blue-700 rounded-lg p-6 shadow-lg text-white md:col-span-1 lg:col-span-1">
          <div class="flex items-center justify-between mb-4">
            <TrendingUp class="w-8 h-8 text-white opacity-20" />
          </div>
          <p class="text-xs uppercase font-semibold tracking-wide opacity-90 mb-1">Today's</p>
          <p class="text-xs uppercase font-semibold tracking-wide opacity-90">Revenue</p>
          <h3 class="text-3xl font-bold mt-3">${{ (stats.today_revenue / 1000).toFixed(0) }}<span class="text-lg">,{{ stats.today_revenue % 1000 }}</span></h3>
        </div>
      </div>

      <!-- Revenue Trend Section -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        <!-- Revenue Chart -->
        <div class="lg:col-span-2 bg-white dark:bg-slate-900/50 rounded-lg border border-slate-200 dark:border-slate-700 p-6 shadow-sm transition-colors duration-300">
          <div class="flex items-center justify-between mb-6">
            <div>
              <h2 class="text-xl font-bold text-slate-900 dark:text-slate-100">Revenue Trend</h2>
              <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Comparative analysis for the last 7 days</p>
            </div>
            <div class="flex gap-2">
              <button
                @click="trendTab = 'weekly'"
                :class="[
                  'px-4 py-2 rounded-lg text-sm font-medium transition',
                  trendTab === 'weekly'
                    ? 'bg-blue-100 dark:bg-blue-900/50 text-blue-700 dark:text-blue-300'
                    : 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700'
                ]"
              >
                Weekly
              </button>
              <button
                @click="trendTab = 'monthly'"
                :class="[
                  'px-4 py-2 rounded-lg text-sm font-medium transition',
                  trendTab === 'monthly'
                    ? 'bg-blue-100 dark:bg-blue-900/50 text-blue-700 dark:text-blue-300'
                    : 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700'
                ]"
              >
                Monthly
              </button>
            </div>
          </div>

          <!-- Chart Placeholder with Bar Chart -->
          <div class="h-64 flex items-end justify-center gap-3 bg-slate-50 dark:bg-slate-800/50 rounded-lg p-6 transition-colors duration-300">
            <div class="w-12 h-32 bg-blue-200 rounded-lg flex items-center justify-center text-xs text-center font-semibold">
              <div class="h-24 w-full bg-blue-300 rounded-lg"></div>
            </div>
            <div class="w-12 h-40 bg-blue-200 rounded-lg flex items-center justify-center text-xs text-center font-semibold">
              <div class="h-32 w-full bg-blue-400 rounded-lg"></div>
            </div>
            <div class="w-12 h-36 bg-blue-200 rounded-lg flex items-center justify-center text-xs text-center font-semibold">
              <div class="h-28 w-full bg-blue-300 rounded-lg"></div>
            </div>
            <div class="w-12 h-44 bg-blue-200 rounded-lg flex items-center justify-center text-xs text-center font-semibold">
              <div class="h-36 w-full bg-blue-400 rounded-lg"></div>
            </div>
            <div class="w-12 h-28 bg-blue-200 rounded-lg flex items-center justify-center text-xs text-center font-semibold">
              <div class="h-20 w-full bg-blue-300 rounded-lg"></div>
            </div>
            <div class="w-12 h-48 bg-blue-300 rounded-lg border-2 border-blue-600 flex items-center justify-center text-xs text-center font-semibold">
              <div class="h-40 w-full bg-blue-500 rounded-lg"></div>
            </div>
            <div class="w-12 h-20 bg-blue-200 rounded-lg flex items-center justify-center text-xs text-center font-semibold">
              <div class="h-12 w-full bg-blue-300 rounded-lg"></div>
            </div>
          </div>
          <div class="flex justify-center gap-8 mt-4 text-xs text-slate-600 dark:text-slate-400 font-medium">
            <span>Mon</span>
            <span>Tue</span>
            <span>Wed</span>
            <span>Thu</span>
            <span>Fri</span>
            <span class="font-bold text-blue-600 dark:text-blue-400">Sat</span>
            <span>Sun</span>
          </div>
        </div>

        <!-- Recent Activity -->
        <div class="bg-white dark:bg-slate-900/50 rounded-lg border border-slate-200 dark:border-slate-700 p-6 shadow-sm transition-colors duration-300">
          <h2 class="text-xl font-bold text-slate-900 dark:text-slate-100 mb-4">Recent Activity</h2>
          <p class="text-sm text-slate-500 dark:text-slate-400 mb-4">Live updates from floor managers</p>

          <div class="space-y-3 max-h-96 overflow-y-auto">
            <div
              v-for="(activity, idx) in activities.slice(0, 5)"
              :key="idx"
              :class="['p-3 rounded-lg border border-slate-200', getActivityColor(activity)]"
            >
              <div class="flex gap-3">
                <div class="text-2xl flex-shrink-0 mt-1">{{ getActivityIcon(activity) }}</div>
                <div class="flex-1 min-w-0">
                  <p class="font-medium text-slate-900 text-sm">{{ activity.title || 'Activity' }}</p>
                  <p class="text-xs text-slate-600">{{ activity.subtitle || '' }}</p>
                  <div class="flex items-center gap-2 mt-2 text-xs text-slate-500">
                    <span>{{ activity.time || 'Just now' }}</span>
                    <span>•</span>
                    <span>{{ activity.source || 'System' }}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <button class="w-full mt-4 text-blue-600 hover:text-blue-700 text-sm font-semibold py-2 transition">
            View All Logs →
          </button>
        </div>
      </div>

      <!-- Action Buttons -->
      <div class="flex flex-col sm:flex-row gap-4">
        <button class="flex-1 bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white font-semibold py-3 px-6 rounded-lg transition-all duration-300 flex items-center justify-center gap-2 shadow-lg shadow-blue-500/30">
          <Users class="w-5 h-5" />
          Assign Waiters
        </button>
        <button class="flex-1 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold py-3 px-6 rounded-lg transition-all duration-300 flex items-center justify-center gap-2">
          <Download class="w-5 h-5" />
          Export Reports
        </button>
      </div>
    </div>
  </DashboardLayout>
</template>
