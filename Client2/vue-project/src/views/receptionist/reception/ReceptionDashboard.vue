<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import ReceptionStatCard from '@/components/reception/ReceptionStatCard.vue'
import TodaysArrivals from '@/components/reception/TodaysArrivals.vue'
import TodaysDepartures from '@/components/reception/TodaysDepartures.vue'
import RoomStatusMatrix from '@/components/reception/RoomStatusMatrix.vue'
import RecentReservations from '@/components/reception/RecentReservations.vue'
import { getReceptionDashboard } from '@/services/receptionService'
import type { ReceptionDashboardData } from '@/types/reception'
import { LogIn, CalendarPlus, RefreshCw, AlertCircle } from 'lucide-vue-next'
import { useLanguageStore } from '@/stores/language'

const router = useRouter()
const languageStore = useLanguageStore()
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

const newBooking = () => {
  router.push('/reservations/create')
}

onMounted(loadDashboard)
</script>

<template>
  <DashboardLayout>
    <div class="space-y-6 w-full">
      <!-- Header Banner -->
      <div
        class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white dark:bg-slate-900 p-6 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs w-full"
      >
        <div>
          <div class="flex items-center gap-2">
            <h1
              class="text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-white tracking-tight"
            >
              {{ languageStore.t('receptionist_front_desk', 'Receptionist Front Desk') }}
            </h1>
            <span
              class="px-2.5 py-0.5 text-xs font-black rounded-full bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20"
            >
              {{ languageStore.t('live_system', 'LIVE SYSTEM') }}
            </span>
          </div>
          <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1">
            {{
              languageStore.t(
                'receptionist_desc',
                'Real-time property occupancy, guest arrivals, and check-in management.',
              )
            }}
          </p>
        </div>

        <!-- Action Buttons -->
        <div class="flex items-center gap-2.5 flex-wrap">
          <button
            @click="loadDashboard"
            :disabled="loading"
            class="px-3.5 py-2.5 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 text-xs font-bold rounded-xl transition flex items-center gap-1.5 cursor-pointer"
            title="Refresh Data"
          >
            <RefreshCw class="w-4 h-4" :class="{ 'animate-spin': loading }" />
            <span>{{ languageStore.t('refresh', 'Refresh') }}</span>
          </button>

          <button
            @click="newBooking"
            class="px-4 py-2.5 bg-slate-900 dark:bg-white text-white dark:text-slate-900 hover:bg-slate-800 dark:hover:bg-slate-100 text-xs font-bold rounded-xl shadow-xs transition flex items-center gap-2 cursor-pointer"
          >
            <CalendarPlus class="w-4 h-4" />
            <span>{{ languageStore.t('new_booking', '+ New Booking') }}</span>
          </button>

          <button
            @click="quickCheckIn"
            class="px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl shadow-xs transition flex items-center gap-2 cursor-pointer"
          >
            <LogIn class="w-4 h-4" />
            <span>{{ languageStore.t('quick_check_in', 'Quick Check-In') }}</span>
          </button>
        </div>
      </div>

      <!-- Loading State -->
      <div
        v-if="loading && !dashboard"
        class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-4"
      >
        <div
          v-for="i in 6"
          :key="i"
          class="h-24 bg-slate-200 dark:bg-slate-800 rounded-2xl animate-pulse"
        />
      </div>

      <!-- Error State -->
      <div
        v-else-if="errorOccurred"
        class="flex flex-col items-center justify-center p-12 text-center bg-white dark:bg-slate-900 rounded-2xl border border-rose-200 dark:border-rose-900/50"
      >
        <div class="p-3 bg-rose-500/10 text-rose-600 rounded-2xl mb-3">
          <AlertCircle class="w-8 h-8" />
        </div>
        <h3 class="text-base font-bold text-slate-900 dark:text-white">Unable to load dashboard</h3>
        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 mb-4">
          Failed to communicate with hotel management API.
        </p>
        <button
          @click="loadDashboard"
          class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl transition"
        >
          Try Again
        </button>
      </div>

      <!-- Main Dashboard View -->
      <div v-else-if="dashboard" class="space-y-6 w-full">
        <!-- Stat Cards Grid (6 Columns) -->
        <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-3 sm:gap-4 w-full">
          <ReceptionStatCard
            title="Check-Ins"
            :value="dashboard.statistics.today_check_ins"
            icon="checkin"
            color="green"
            subtext="Today"
          />
          <ReceptionStatCard
            title="Check-Outs"
            :value="dashboard.statistics?.checkout_count || 0"
            icon="checkout"
            color="red"
            subtext="Today"
          />
          <ReceptionStatCard
            title="Active Guests"
            :value="dashboard.statistics.active_guests"
            icon="guests"
            color="blue"
            subtext="In property"
          />
          <ReceptionStatCard
            title="Available Rooms"
            :value="dashboard.statistics.available_rooms"
            icon="rooms"
            color="teal"
            subtext="Ready"
          />
          <ReceptionStatCard
            title="Pending"
            :value="dashboard.statistics.pending_reservations"
            icon="pending"
            color="orange"
            subtext="Reservations"
          />
          <ReceptionStatCard
            title="Confirmed"
            :value="dashboard.statistics.confirmed_reservations"
            icon="confirmed"
            color="purple"
            subtext="Reservations"
          />
        </div>

        <!-- Middle Section: Today's Arrivals & Departures (2 Columns) -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 w-full">
          <TodaysArrivals :arrivals="dashboard.today_arrivals" />
          <TodaysDepartures :departures="dashboard.today_departures" />
        </div>

        <!-- Room Status Matrix (Full Width / Section) -->
        <div class="w-full">
          <RoomStatusMatrix :rooms="dashboard.room_matrix" />
        </div>

        <!-- Recent Reservations Table (Full Width Across Bottom) -->
        <div class="w-full">
          <RecentReservations :reservations="dashboard.recent_reservations" />
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>
