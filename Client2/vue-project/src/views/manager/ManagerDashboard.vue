<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useManagerStore } from '@/stores/managerStore'
import DashboardLayout from '../../Layouts/DashboardLayout.vue'
import { Calendar, Home, Users, AlertCircle, TrendingUp, Download } from 'lucide-vue-next'

const auth = useAuthStore()
const manager = useManagerStore()

const showLoginToast = ref(false)
const trendTab = ref('weekly')

const stats = ref({
  total_reservations: 124,
  rooms_occupied: 176,
  max_rooms: 200,
  active_waiters: 42,
  kitchen_ready: 8,
  today_revenue: 42850
})

const activities = [
  {
    icon: 'checkin',
    title: 'Room 402 check-in',
    subtitle: 'completed',
    time: '2 mins ago',
    source: 'Front Desk',
    color: 'emerald'
  },
  {
    icon: 'alert',
    title: 'Urgent: Kitchen supply low',
    subtitle: 'on Wagyu',
    time: '15 mins ago',
    source: 'Chef Ramsay',
    color: 'red'
  },
  {
    icon: 'star',
    title: 'VIP Guest Mr. Sterling',
    subtitle: 'arrived at Lounge',
    time: '42 mins ago',
    source: 'Floor 2',
    color: 'amber'
  },
  {
    icon: 'building',
    title: 'Penthouse B marked as',
    subtitle: 'Ready',
    time: '1 hour ago',
    source: 'Housekeeping',
    color: 'blue'
  },
  {
    icon: 'clock',
    title: 'Shift handover: Evening',
    subtitle: 'team notified',
    time: '2 hours ago',
    source: 'System',
    color: 'slate'
  }
]

const getActivityIcon = (iconType: string) => {
  switch (iconType) {
    case 'checkin':
      return '🏠'
    case 'alert':
      return '⚠️'
    case 'star':
      return '⭐'
    case 'building':
      return '🏢'
    case 'clock':
      return '🕐'
    default:
      return '📌'
  }
}

const getActivityColor = (color: string) => {
  switch (color) {
    case 'emerald':
      return 'bg-emerald-50'
    case 'red':
      return 'bg-red-50'
    case 'amber':
      return 'bg-amber-50'
    case 'blue':
      return 'bg-blue-50'
    case 'slate':
      return 'bg-slate-50'
    default:
      return 'bg-slate-50'
  }
}

onMounted(async () => {
  console.log('[ManagerDashboard] Checking authentication...')
  console.log('[ManagerDashboard] Token present:', !!auth.token)
  console.log('[ManagerDashboard] User role:', auth.user?.role)
  
  if (!auth.token) {
    console.error('[ManagerDashboard] No auth token - manager not logged in')
    return
  }
  
  console.log('[ManagerDashboard]  Authenticated - dashboard ready')

  const loginSuccess = sessionStorage.getItem('loginSuccess')
  if (loginSuccess) {
    try {
      showLoginToast.value = true
      setTimeout(() => {
        showLoginToast.value = false
        sessionStorage.removeItem('loginSuccess')
      }, 3000)
    } catch (e) {
      console.error('Error parsing login success data:', e)
    }
  }
})
</script>

<template>
  <DashboardLayout>
    <div class="min-h-screen bg-gradient-to-br from-slate-50 via-white to-blue-50/30 p-6">
      <!-- Welcome Header -->
      <div class="mb-8">
        <h1 class="text-4xl font-bold text-slate-900">Welcome back, Manager</h1>
        <p class="text-slate-600 mt-2">Here is what's happening at Executive Horizon this morning.</p>
      </div>

      <!-- Stats Cards Grid -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 mb-8">
        <!-- Total Reservations -->
        <div class="bg-white rounded-lg border border-slate-200 p-6 shadow-sm hover:shadow-md transition">
          <div class="flex items-center justify-between mb-4">
            <Calendar class="w-8 h-8 text-slate-400" />
          </div>
          <p class="text-xs text-slate-500 uppercase font-semibold tracking-wide mb-1">Total</p>
          <p class="text-xs text-slate-500 uppercase font-semibold tracking-wide">Reservations</p>
          <h3 class="text-4xl font-bold text-slate-900 mt-2">{{ stats.total_reservations }}</h3>
          <p class="text-xs text-emerald-600 font-semibold mt-2">+12%</p>
        </div>

        <!-- Rooms Occupied -->
        <div class="bg-white rounded-lg border border-slate-200 p-6 shadow-sm hover:shadow-md transition">
          <div class="flex items-center justify-between mb-4">
            <Home class="w-8 h-8 text-slate-400" />
          </div>
          <p class="text-xs text-slate-500 uppercase font-semibold tracking-wide mb-1">Rooms</p>
          <p class="text-xs text-slate-500 uppercase font-semibold tracking-wide">Occupied</p>
          <h3 class="text-4xl font-bold text-slate-900 mt-2">{{ stats.rooms_occupied }}<span class="text-lg text-slate-400">/{{ stats.max_rooms }}</span></h3>
          <p class="text-xs text-blue-600 font-semibold mt-2">● 88%</p>
        </div>

        <!-- Active Waiters -->
        <div class="bg-white rounded-lg border border-slate-200 p-6 shadow-sm hover:shadow-md transition">
          <div class="flex items-center justify-between mb-4">
            <Users class="w-8 h-8 text-slate-400" />
          </div>
          <p class="text-xs text-slate-500 uppercase font-semibold tracking-wide">Active</p>
          <p class="text-xs text-slate-500 uppercase font-semibold tracking-wide">Waiters</p>
          <h3 class="text-4xl font-bold text-slate-900 mt-2">{{ stats.active_waiters }}</h3>
        </div>

        <!-- Kitchen Ready -->
        <div class="bg-white rounded-lg border border-slate-200 p-6 shadow-sm hover:shadow-md transition">
          <div class="flex items-center justify-between mb-4">
            <AlertCircle class="w-8 h-8 text-red-500" />
          </div>
          <p class="text-xs text-slate-500 uppercase font-semibold tracking-wide">Kitchen</p>
          <p class="text-xs text-red-600 uppercase font-semibold tracking-wide">URGENT</p>
          <h3 class="text-4xl font-bold text-red-600 mt-2">{{ stats.kitchen_ready }}</h3>
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
        <div class="lg:col-span-2 bg-white rounded-lg border border-slate-200 p-6 shadow-sm">
          <div class="flex items-center justify-between mb-6">
            <div>
              <h2 class="text-xl font-bold text-slate-900">Revenue Trend</h2>
              <p class="text-sm text-slate-500 mt-1">Comparative analysis for the last 7 days</p>
            </div>
            <div class="flex gap-2">
              <button
                @click="trendTab = 'weekly'"
                :class="[
                  'px-4 py-2 rounded-lg text-sm font-medium transition',
                  trendTab === 'weekly'
                    ? 'bg-blue-100 text-blue-700'
                    : 'bg-slate-100 text-slate-700 hover:bg-slate-200'
                ]"
              >
                Weekly
              </button>
              <button
                @click="trendTab = 'monthly'"
                :class="[
                  'px-4 py-2 rounded-lg text-sm font-medium transition',
                  trendTab === 'monthly'
                    ? 'bg-blue-100 text-blue-700'
                    : 'bg-slate-100 text-slate-700 hover:bg-slate-200'
                ]"
              >
                Monthly
              </button>
            </div>
          </div>

          <!-- Chart Placeholder with Bar Chart -->
          <div class="h-64 flex items-end justify-center gap-3 bg-slate-50 rounded-lg p-6">
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
          <div class="flex justify-center gap-8 mt-4 text-xs text-slate-600 font-medium">
            <span>Mon</span>
            <span>Tue</span>
            <span>Wed</span>
            <span>Thu</span>
            <span>Fri</span>
            <span class="font-bold text-blue-600">Sat</span>
            <span>Sun</span>
          </div>
        </div>

        <!-- Recent Activity -->
        <div class="bg-white rounded-lg border border-slate-200 p-6 shadow-sm">
          <h2 class="text-xl font-bold text-slate-900 mb-4">Recent Activity</h2>
          <p class="text-sm text-slate-500 mb-4">Live updates from floor managers</p>

          <div class="space-y-3 max-h-96 overflow-y-auto">
            <div
              v-for="(activity, idx) in activities.slice(0, 5)"
              :key="idx"
              :class="['p-3 rounded-lg border border-slate-200', getActivityColor(activity.color)]"
            >
              <div class="flex gap-3">
                <div class="text-2xl flex-shrink-0 mt-1">{{ getActivityIcon(activity.icon) }}</div>
                <div class="flex-1 min-w-0">
                  <p class="font-medium text-slate-900 text-sm">{{ activity.title }}</p>
                  <p class="text-xs text-slate-600">{{ activity.subtitle }}</p>
                  <div class="flex items-center gap-2 mt-2 text-xs text-slate-500">
                    <span>{{ activity.time }}</span>
                    <span>•</span>
                    <span>{{ activity.source }}</span>
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
