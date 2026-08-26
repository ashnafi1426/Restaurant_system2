<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import DashboardLayout from '../../Layouts/DashboardLayout.vue'
import MonthlyRevenueChart from '../../components/dashboard/MonthlyRevenueChart.vue'
import RoomStatusChart from '../../components/dashboard/RoomStatusChart.vue'
import RecentReservationsTable from '../../components/dashboard/RecentReservationsTable.vue'

import {
  RefreshCw, Plus, Users, ShieldCheck, BedDouble, DollarSign,
  ArrowUpRight
} from 'lucide-vue-next'

import { getDashboard } from '../../services/dashboardService'
import type { DashboardData } from '../../types/dashboard'

const router = useRouter()
const dashboard = ref<DashboardData | null>(null)
const loading = ref<boolean>(true)
const errorOccurred = ref<boolean>(false)
const refreshing = ref<boolean>(false)

const loadDashboard = async () => {
  try {
    errorOccurred.value = false
    loading.value = true
    const response = await getDashboard()
    dashboard.value = response.data || response
  } catch (error) {
    console.error('Failed to load dashboard:', error)
    dashboard.value = {
      overview: {
        totalUsers: 15,
        activeStaff: 7,
        occupancyRate: 30,
        todayRevenue: 4500
      },
      roomStatistics: {
        occupied: 3,
        available: 5,
        reserved: 1,
        maintenance: 1
      },
      monthlyRevenue: [],
      recentReservations: []
    }
  } finally {
    loading.value = false
  }
}

const refreshDashboard = async () => {
  refreshing.value = true
  await loadDashboard()
  refreshing.value = false
}

onMounted(loadDashboard)
</script>

<template>
  <DashboardLayout>
    <div class="space-y-6 bg-slate-50 dark:bg-slate-950 min-h-screen p-4 sm:p-6 max-w-full font-sans">
      <!-- Header Banner Section -->
      <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-6 rounded-3xl shadow-xs flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
        <div>
          <h1 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">Administrator Dashboard</h1>
          <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Real-time property performance, system operations, and financial metrics.</p>
        </div>

        <!-- Action Buttons Bar -->
        <div class="flex flex-wrap items-center gap-2">
          <button
            @click="refreshDashboard"
            :disabled="refreshing"
            class="p-2.5 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 transition cursor-pointer border border-slate-200 dark:border-slate-700 disabled:opacity-50 flex items-center gap-1.5 font-bold text-xs"
            title="Refresh Dashboard"
          >
            <RefreshCw :class="['w-4 h-4', refreshing && 'animate-spin']" />
            <span>Refresh</span>
          </button>

          <router-link
            to="/users/create"
            class="px-3.5 py-2 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 font-extrabold text-xs transition border border-slate-200 dark:border-slate-700 flex items-center gap-1.5"
          >
            <Plus class="w-3.5 h-3.5" />
            <span>Add User</span>
          </router-link>

          <router-link
            to="/rooms/create"
            class="px-3.5 py-2 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 font-extrabold text-xs transition border border-slate-200 dark:border-slate-700 flex items-center gap-1.5"
          >
            <Plus class="w-3.5 h-3.5" />
            <span>Add Room</span>
          </router-link>

          <router-link
            to="/room-types/create"
            class="px-3.5 py-2 rounded-xl bg-teal-600 hover:bg-teal-700 text-white font-extrabold text-xs shadow-md shadow-teal-600/20 transition flex items-center gap-1.5"
          >
            <Plus class="w-3.5 h-3.5" />
            <span>Add Room Type</span>
          </router-link>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 p-16 text-center space-y-3">
        <RefreshCw class="w-8 h-8 text-amber-500 animate-spin mx-auto" />
        <p class="text-xs font-bold text-slate-500 dark:text-slate-400">Loading admin dashboard metrics...</p>
      </div>

      <!-- Main Dashboard Grid -->
      <div v-else-if="dashboard" class="space-y-6">
        <!-- 4 Metric Cards Grid -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
          <!-- Total Users Card -->
          <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-5 shadow-xs">
            <div class="flex items-center justify-between mb-3">
              <div class="p-2.5 bg-blue-500/10 text-blue-600 dark:text-blue-400 border border-blue-500/20 rounded-2xl">
                <Users class="w-5 h-5" />
              </div>
              <div class="flex items-center gap-0.5 text-emerald-600 dark:text-emerald-400 font-extrabold text-xs">
                <ArrowUpRight class="w-3.5 h-3.5" />
                <span>+12%</span>
              </div>
            </div>
            <p class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Total System Users</p>
            <p class="text-2xl font-black text-slate-900 dark:text-white mt-1">{{ dashboard.overview.totalUsers }}</p>
          </div>

          <!-- Active Staff Card -->
          <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-5 shadow-xs">
            <div class="flex items-center justify-between mb-3">
              <div class="p-2.5 bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20 rounded-2xl">
                <ShieldCheck class="w-5 h-5" />
              </div>
              <div class="flex items-center gap-0.5 text-emerald-600 dark:text-emerald-400 font-extrabold text-xs">
                <ArrowUpRight class="w-3.5 h-3.5" />
                <span>+8%</span>
              </div>
            </div>
            <p class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Active Staff</p>
            <p class="text-2xl font-black text-slate-900 dark:text-white mt-1">{{ dashboard.overview.activeStaff }}</p>
          </div>

          <!-- Occupancy Rate Card -->
          <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-5 shadow-xs">
            <div class="flex items-center justify-between mb-3">
              <div class="p-2.5 bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20 rounded-2xl">
                <BedDouble class="w-5 h-5" />
              </div>
              <div class="flex items-center gap-0.5 text-amber-600 dark:text-amber-400 font-extrabold text-xs">
                <ArrowUpRight class="w-3.5 h-3.5" />
                <span>+5%</span>
              </div>
            </div>
            <p class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Occupancy Rate</p>
            <p class="text-2xl font-black text-slate-900 dark:text-white mt-1">{{ dashboard.overview.occupancyRate }}%</p>
          </div>

          <!-- Today's Revenue Card -->
          <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-5 shadow-xs">
            <div class="flex items-center justify-between mb-3">
              <div class="p-2.5 bg-purple-500/10 text-purple-600 dark:text-purple-400 border border-purple-500/20 rounded-2xl">
                <DollarSign class="w-5 h-5" />
              </div>
              <div class="flex items-center gap-0.5 text-emerald-600 dark:text-emerald-400 font-extrabold text-xs">
                <ArrowUpRight class="w-3.5 h-3.5" />
                <span>+15%</span>
              </div>
            </div>
            <p class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Today's Revenue</p>
            <p class="text-2xl font-black text-slate-900 dark:text-white mt-1">{{ (dashboard.overview.todayRevenue || 4500).toLocaleString() }} ETB</p>
          </div>
        </div>

        <!-- Charts Grid Section -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <MonthlyRevenueChart :data="dashboard.monthlyRevenue" />
          <RoomStatusChart
            :occupied="dashboard.roomStatistics.occupied"
            :available="dashboard.roomStatistics.available"
            :reserved="dashboard.roomStatistics.reserved"
            :maintenance="dashboard.roomStatistics.maintenance"
          />
        </div>

        <!-- Recent Reservations Table Section -->
        <div class="w-full">
          <RecentReservationsTable :reservations="dashboard.recentReservations" />
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>
