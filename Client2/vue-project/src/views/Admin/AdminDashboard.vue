<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import DashboardLayout from '../../layouts/DashboardLayout.vue'
import AdminStatCard from '../../components/dashboard/AdminStatCard.vue'
import MonthlyRevenueChart from '../../components/dashboard/MonthlyRevenueChart.vue'
import RoomStatusChart from '../../components/dashboard/RoomStatusChart.vue'
import RecentReservationsTable from '../../components/dashboard/RecentReservationsTable.vue'
import StaffActivityWidget from '../../components/dashboard/StaffActivityWidget.vue'
import MaintenanceAlerts from '../../components/dashboard/MaintenanceAlerts.vue'

import {
  mdiAccountMultiple,
  mdiAccount,
  mdiBed,
  mdiCashMultiple,
  mdiRefresh,
  mdiPlus,
} from '@mdi/js'

import { getDashboard } from '../../services/dashboardService'
import type { DashboardData } from '../../types/dashboard'
import SvgIcon from '@jamescoyle/vue-icon'

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

const createNewBooking = () => {
  router.push('/reservations/create')
}

onMounted(loadDashboard)
</script>

<template>
  <DashboardLayout>
    <div class="w-full bg-gray-50 -mx-4 sm:-mx-6 -my-4 sm:-my-6 px-4 sm:px-6 py-4 sm:py-6">
      <!-- Loading State -->
      <div v-if="loading" class="animate-pulse space-y-4 sm:space-y-6">
        <div class="h-8 bg-gray-200 rounded w-1/3"></div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 lg:gap-6">
          <div v-for="i in 4" :key="i" class="h-32 bg-gray-200 rounded-lg"></div>
        </div>
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-3 sm:gap-4 lg:gap-6">
          <div class="h-80 bg-gray-200 rounded-lg"></div>
          <div class="h-80 bg-gray-200 rounded-lg"></div>
        </div>
      </div>

      <!-- Error State -->
      <div
        v-else-if="errorOccurred"
        class="flex flex-col items-center justify-center min-h-[50vh] text-center px-4"
      >
        <div class="p-4 bg-red-100 text-red-600 rounded-full mb-4">
          <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
            />
          </svg>
        </div>
        <h3 class="text-lg font-semibold text-gray-900">Failed to load dashboard</h3>
        <p class="text-gray-600 mt-2 mb-6 text-sm">
          Unable to fetch your dashboard data. Please try again.
        </p>
      </div>

      <!-- Main Content -->
      <div v-else-if="dashboard" class="space-y-4 sm:space-y-6">
        <!-- Header Section -->
        <div class="mb-4 sm:mb-6">
          <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 mb-1">Administrator Dashboard</h1>
          <p class="text-gray-600 text-xs sm:text-sm mb-4">
            Real-time property performance and operational overview.
          </p>

          <!-- Action Buttons Row - Responsive Grid -->
          <div
            class="flex flex-col sm:flex-row gap-2 sm:gap-3 flex-wrap items-stretch sm:items-center"
          >
            <!-- Refresh Button -->
            <button
              @click="refreshDashboard"
              :disabled="refreshing"
              class="p-2 hover:bg-gray-100 rounded-lg transition-colors disabled:opacity-50 flex items-center justify-center"
              title="Refresh dashboard"
            >
              <SvgIcon
                type="mdi"
                :path="mdiRefresh"
                :size="20"
                :class="refreshing ? 'animate-spin' : ''"
                class="text-gray-600"
              />
            </button>

            <!-- New Booking Button (Primary) -->
            <button
              @click="createNewBooking"
              class="px-3 sm:px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs sm:text-sm font-medium rounded-lg transition-colors flex items-center gap-2 justify-center sm:justify-start"
              title="Create a new reservation"
            >
              <span class="text-base">+</span>
              <span>New Booking</span>
            </button>

            <!-- Add User Button -->
            <router-link
              to="/users/create"
              class="px-3 sm:px-4 py-2 bg-white border border-gray-300 text-gray-700 text-xs sm:text-sm font-medium rounded-lg hover:bg-gray-50 transition-colors flex items-center gap-1 justify-center sm:justify-start whitespace-nowrap"
            >
              <span>+</span>
              <span>Add User</span>
            </router-link>

            <!-- Add Room Button -->
            <router-link
              to="/rooms/create"
              class="px-3 sm:px-4 py-2 bg-white border border-gray-300 text-gray-700 text-xs sm:text-sm font-medium rounded-lg hover:bg-gray-50 transition-colors flex items-center gap-1 justify-center sm:justify-start whitespace-nowrap"
            >
              <span>+</span>
              <span>Add Room</span>
            </router-link>

            <!-- Add Room Type Button -->
            <router-link
              to="/room-types/create"
              class="px-3 sm:px-4 py-2 bg-teal-600 hover:bg-teal-700 text-white text-xs sm:text-sm font-medium rounded-lg transition-colors flex items-center gap-1 justify-center sm:justify-start whitespace-nowrap"
            >
              <span>+</span>
              <span>Add Room Type</span>
            </router-link>
          </div>
        </div>

        <!-- 4 Stat Cards - Responsive Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 lg:gap-6">
          <AdminStatCard
            title="Total Users"
            :value="dashboard.overview.totalUsers"
            :icon="mdiAccountMultiple"
            :trend="12"
            color="teal"
          />
          <AdminStatCard
            title="Active Staff"
            :value="dashboard.overview.activeStaff"
            :icon="mdiAccount"
            :trend="8"
            color="blue"
          />
          <AdminStatCard
            title="Occupancy Rate"
            :value="`${dashboard.overview.occupancyRate}%`"
            :icon="mdiBed"
            :trend="5"
            color="green"
          />
          <AdminStatCard
            title="Today's Revenue"
            :value="`$${dashboard.overview.todayRevenue.toLocaleString()}`"
            :icon="mdiCashMultiple"
            :trend="15"
            color="purple"
          />
        </div>

        <!-- Charts Row - Stack on Mobile -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-3 sm:gap-4 lg:gap-6">
          <MonthlyRevenueChart :data="dashboard.monthlyRevenue" />
          <RoomStatusChart
            :occupied="dashboard.roomStatistics.occupied"
            :available="dashboard.roomStatistics.available"
            :reserved="dashboard.roomStatistics.reserved"
            :maintenance="dashboard.roomStatistics.maintenance"
          />
        </div>

        <!-- Bottom Row: Reservations and Sidebar - Stack on Mobile -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-3 sm:gap-4 lg:gap-6">
          <div class="lg:col-span-2">
            <RecentReservationsTable :reservations="dashboard.recentReservations" />
          </div>
          <div class="space-y-3 sm:space-y-4 lg:space-y-6">
            <StaffActivityWidget :activities="dashboard.staffActivity" />
            <MaintenanceAlerts :alerts="dashboard.maintenanceAlerts" />
          </div>
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>
