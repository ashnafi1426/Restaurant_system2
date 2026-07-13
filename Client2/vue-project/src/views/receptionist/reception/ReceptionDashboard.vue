<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import ReceptionStatCard from '@/components/reception/ReceptionStatCard.vue'
import TodaysArrivals from '@/components/reception/TodaysArrivals.vue'
import TodaysDepartures from '@/components/reception/TodaysDepartures.vue'
import RoomStatusMatrix from '@/components/reception/RoomStatusMatrix.vue'
import RecentReservations from '@/components/reception/RecentReservations.vue'
import NotificationCenter from '@/components/reception/NotificationCenter.vue'
import { getReceptionDashboard } from '@/services/receptionService'
import type { ReceptionDashboardData } from '@/types/reception'

const router = useRouter()
const dashboard = ref<ReceptionDashboardData | null>(null)
const loading = ref(false)
const errorOccurred = ref(false)

const loadDashboard = async () => {
  try {
    errorOccurred.value = false
    loading.value = true
    const data = await getReceptionDashboard()
    dashboard.value = data
  } catch (error) {
    console.error('Failed to load reception dashboard:', error)
    errorOccurred.value = true
  } finally {
    loading.value = false
  }
}

const quickCheckIn = () => {
  router.push('/check-in')
}

onMounted(loadDashboard)
</script>

<template>
  <DashboardLayout>
    <div class="w-full">
      <!-- Header Section -->
      <div class="bg-white border-b border-gray-200 px-8 py-6">
        <div class="flex items-start justify-between">
          <div class="flex-1">
            <h1 class="text-3xl font-bold text-gray-900">Receptionist Dashboard</h1>
            <p class="text-gray-500 text-sm mt-1">Manage your property efficiently today.</p>
          </div>
          <!-- Action Buttons (Right Aligned) -->
          <div class="flex gap-3 flex-shrink-0">
            <button
              @click="router.push('/guests/create')"
              class="px-5 py-2.5 bg-teal-600 hover:bg-teal-700 text-white text-sm font-semibold rounded-lg transition flex items-center gap-2 shadow-sm"
            >
              <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                <path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z" />
              </svg>
              Register Guest
            </button>
            <button
              @click="router.push('/reservations/create')"
              class="px-5 py-2.5 bg-teal-600 hover:bg-teal-700 text-white text-sm font-semibold rounded-lg transition flex items-center gap-2 shadow-sm"
            >
              <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                <path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z" />
              </svg>
              Create Reservation
            </button>
            <button
              @click="quickCheckIn"
              class="px-5 py-2.5 bg-white border-2 border-gray-300 text-gray-700 text-sm font-semibold rounded-lg hover:bg-gray-50 transition flex items-center gap-2"
            >
              <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                <path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z" />
              </svg>
              Quick Check In
            </button>
          </div>
        </div>
      </div>

      <!-- Main Content -->
      <div v-if="loading" class="p-8">
        <div class="grid grid-cols-6 gap-4 mb-8">
          <div v-for="i in 6" :key="i" class="h-32 bg-gray-200 rounded-lg animate-pulse"></div>
        </div>
      </div>
      <!-- Error State -->
      <div
        v-else-if="errorOccurred"
        class="flex flex-col items-center justify-center min-h-[60vh] text-center px-8"
      >
        <div class="p-4 bg-red-100 text-red-600 rounded-full mb-4">
          <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M12 9v2m0 4v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
            />
          </svg>
        </div>
        <h3 class="text-lg font-semibold text-gray-900">Unable to load dashboard</h3>
        <p class="text-gray-600 mt-2 mb-6">
          Make sure you are logged in with a valid receptionist or admin account.
        </p>
        <div class="flex gap-3">
          <button
            @click="loadDashboard"
            class="px-6 py-2 bg-teal-600 text-white rounded-lg hover:bg-teal-700 transition"
          >
            Try Again
          </button>
          <button
            @click="router.push('/login')"
            class="px-6 py-2 bg-gray-300 text-gray-800 rounded-lg hover:bg-gray-400 transition"
          >
            Back to Login
          </button>
        </div>
      </div>

      <!-- Main Grid Layout -->
      <div v-else-if="dashboard" class="p-8 space-y-8">
        <!-- Statistics Cards (6 columns in one row) -->
        <div class="grid grid-cols-6 gap-4">
          <ReceptionStatCard
            title="CHECK-INS"
            :value="dashboard.statistics.today_check_ins"
            icon="checkin"
            color="teal"
            subtext="Today"
          />
          <ReceptionStatCard
            title="CHECK-OUTS"
            :value="dashboard.statistics?.checkout_count || 0"
            icon="checkout"
            color="red"
            subtext="Today"
          />
          <ReceptionStatCard
            title="ACTIVE GUESTS"
            :value="dashboard.statistics.active_guests"
            icon="guests"
            color="blue"
            subtext="In property"
          />
          <ReceptionStatCard
            title="AVAILABLE ROOMS"
            :value="dashboard.statistics.available_rooms"
            icon="rooms"
            color="green"
            subtext="Ready"
          />
          <ReceptionStatCard
            title="PENDING"
            :value="dashboard.statistics.pending_reservations"
            icon="pending"
            color="orange"
            subtext="Reservations"
          />
          <ReceptionStatCard
            title="CONFIRMED"
            :value="dashboard.statistics.confirmed_reservations"
            icon="confirmed"
            color="purple"
            subtext="Reservations"
          />
        </div>

        <!-- Content Grid: 2 columns -->
        <div class="grid grid-cols-2 gap-8">
          <!-- Left Column -->
          <div class="space-y-8">
            <!-- Today's Arrivals -->
            <TodaysArrivals :arrivals="dashboard.today_arrivals" />

            <!-- Today's Departures -->
            <TodaysDepartures :departures="dashboard.today_departures" />
          </div>

          <!-- Right Column -->
          <div class="space-y-8">
            <!-- Room Status Matrix -->
            <RoomStatusMatrix :rooms="dashboard.room_matrix" />

            <!-- Recent Guest Requests -->
            <RecentReservations :reservations="dashboard.recent_reservations" />
          </div>
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>
