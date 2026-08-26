<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'

import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import CheckInTable from '@/components/checkin/CheckInTable.vue'
import CheckInDialog from '@/components/checkin/CheckinDialog.vue'
import PaginationBar from '@/components/common/PaginationBar.vue'

import { useCheckInStore } from '@/stores/checkInStore'
import { useReservationStore } from '@/stores/reservationStore'
import { useAuthStore } from '@/stores/auth'

import type { CheckIn } from '@/types/checkIn'

const router = useRouter()
const store = useCheckInStore()
const reservationStore = useReservationStore()
const authStore = useAuthStore()
const showSuccessMessage = ref(false)
const successMessage = ref('')
const showCheckInDialog = ref(false)
const selectedCheckIn = ref<CheckIn | null>(null)
const filters = ref({
  search: '',
  guest_id: '',
  room_id: '',
  page: 1,
  per_page: 10,
})
const userRoleName = computed(() => {
  const role = String(authStore.user?.role || 'Staff').toLowerCase()
  return role.charAt(0).toUpperCase() + role.slice(1)
})
const availableReservations = computed(() => {
  const filtered = reservationStore.reservations.filter((r: any) => r.status === 'confirmed')
  return filtered
})

const totalCheckIns = computed(() => store.statistics.total_check_ins || store.pagination.total || 0)
const activeGuestCount = computed(() => store.statistics.active_guests || 0)
const checkedOutCount = computed(
  () => (store.statistics.total_check_ins || 0) - (store.statistics.active_guests || 0),
)

const loadCheckIns = async (newFilters = {}) => {
  try {
    const combinedFilters = {
      ...filters.value,
      ...newFilters,
    }
    await store.fetchCheckIns(combinedFilters)
    await store.fetchStatistics()
  } catch (error) {
    console.error('Error loading check-ins:', error)
    showMessage('Failed to load check-ins', 'error')
  }
}

const handlePageChange = async (newPage: number) => {
  filters.value.page = newPage
  await loadCheckIns()
}

const handlePerPageChange = async (newPerPage: number) => {
  filters.value.per_page = newPerPage
  filters.value.page = 1
  await loadCheckIns()
}
const loadReservations = async () => {
  try {
    await reservationStore.fetchReservations()
  } catch (error) {
    console.error('Error loading reservations:', error)
  }
}

const openNewCheckInDialog = () => {
  showCheckInDialog.value = true
}

const handleCheckInSuccess = async () => {
  showMessage('Guest checked in successfully!')
  showCheckInDialog.value = false
  await refreshPage()
  await loadReservations()
}

const refreshPage = async () => {
  await loadCheckIns()
}

const search = async (newFilters: any) => {
  filters.value = {
    ...filters.value,
    ...newFilters,
    page: 1,
  }
  await loadCheckIns()
}

const view = (item: CheckIn) => {
  selectedCheckIn.value = item
}

const checkout = async (item: CheckIn) => {
  const confirmed = window.confirm(`Check out guest ${item.guest?.first_name || ''} ${item.guest?.last_name || ''}?`)
  if (!confirmed) return

  try {
    await store.checkOutGuest(item.id)
    showMessage('Guest checked out successfully')
    await refreshPage()
  } catch (error: any) {
    showMessage(error.message || 'Failed to check out guest', 'error')
  }
}

const remove = async (item: CheckIn) => {
  const confirmed = window.confirm('Are you sure you want to delete this check-in record?')
  if (!confirmed) return

  try {
    await store.deleteCheckIn(item.id)
    await refreshPage()
    showMessage('Check-in record deleted successfully')
  } catch (error: any) {
    showMessage('Failed to delete check-in record', 'error')
  }
}

const showMessage = (message: string, type: 'success' | 'error' = 'success') => {
  successMessage.value = message
  showSuccessMessage.value = true
  setTimeout(() => {
    showSuccessMessage.value = false
  }, 3000)
}

const resetFilters = async () => {
  filters.value = {
    search: '',
    guest_id: '',
    room_id: '',
    page: 1,
    per_page: 10,
  }
  await loadCheckIns()
}

onMounted(async () => {
  await refreshPage()
  reservationStore.reservations = []
  await loadReservations()
})
</script>

<template>
  <DashboardLayout>
    <div class="space-y-6 bg-slate-50 dark:bg-slate-950 min-h-screen p-4 sm:p-6 max-w-full overflow-hidden font-sans">
      <!-- Check-In Dialog -->
      <CheckInDialog
        v-model="showCheckInDialog"
        :reservations="availableReservations"
        @success="handleCheckInSuccess"
      />

      <!-- Success/Error Toast -->
      <transition
        enter-active-class="transition ease-out duration-300"
        enter-from-class="opacity-0 translate-y-2"
        enter-to-class="opacity-100 translate-y-0"
        leave-active-class="transition ease-in duration-200"
        leave-from-class="opacity-100 translate-y-0"
        leave-to-class="opacity-0 translate-y-2"
      >
        <div
          v-if="showSuccessMessage"
          class="fixed top-4 right-4 z-50 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg flex items-center gap-3"
        >
          <span class="material-symbols-rounded">check_circle</span>
          <span>{{ successMessage }}</span>
        </div>
      </transition>

      <!-- Breadcrumb -->
      <nav class="flex items-center text-sm text-slate-500">
        <a href="/dashboard" class="hover:text-slate-700 transition">Dashboard</a>
        <span class="mx-2">/</span>
        <span class="font-medium text-slate-800">Check In Management</span>
      </nav>

      <!-- Header -->
      <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-gray-100 dark:border-slate-700 p-6 sm:p-8">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
          <div>
            <div class="flex items-center gap-2 mb-2">
              <h1 class="text-3xl sm:text-4xl font-bold text-slate-900 dark:text-white">Guest Check-In Management</h1>
              <span class="text-xs uppercase font-extrabold bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300 px-2.5 py-1 rounded-full">
                {{ userRoleName }}
              </span>
            </div>
            <p class="text-slate-600 dark:text-slate-400 text-base sm:text-lg">
              Track guest arrivals, manage check-ins, and monitor occupancy in real-time
            </p>
          </div>
          <div class="flex gap-3">
            <button
              @click="openNewCheckInDialog"
              class="flex items-center gap-2 rounded-xl bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 dark:from-blue-700 dark:to-blue-800 dark:hover:from-blue-600 dark:hover:to-blue-700 text-white px-6 py-3 transition shadow-sm hover:shadow-md font-semibold cursor-pointer"
            >
              <span class="material-symbols-rounded text-xl">login</span>
              <span>New Check-In</span>
            </button>
          </div>
        </div>
      </div>

      <!-- Statistics Cards -->
      <div class="grid grid-cols-1 gap-6 sm:grid-cols-3">
        <!-- Total Check-Ins -->
        <div
          class="relative overflow-hidden rounded-xl border border-slate-200 bg-gradient-to-br from-blue-50 to-white p-6 shadow-sm hover:shadow-md transition"
        >
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-slate-600">Total Check-Ins</p>
              <h2 class="mt-2 text-3xl font-bold text-slate-900">{{ totalCheckIns }}</h2>
            </div>
          </div>
        </div>

        <!-- Active Guests -->
        <div
          class="relative overflow-hidden rounded-xl border border-slate-200 bg-gradient-to-br from-green-50 to-white p-6 shadow-sm hover:shadow-md transition"
        >
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-slate-600">Active Guests</p>
              <h2 class="mt-2 text-3xl font-bold text-slate-900">{{ activeGuestCount }}</h2>
            </div>
          </div>
        </div>

        <!-- Checked Out -->
        <div
          class="relative overflow-hidden rounded-xl border border-slate-200 bg-gradient-to-br from-purple-50 to-white p-6 shadow-sm hover:shadow-md transition"
        >
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-slate-600">Checked Out</p>
              <h2 class="mt-2 text-3xl font-bold text-slate-900">{{ checkedOutCount }}</h2>
            </div>
          </div>
        </div>
      </div>

      <!-- Search & Filter Section -->
      <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-6 shadow-xs">
        <div class="flex flex-col gap-4">
          <div class="flex items-center justify-between">
            <h3 class="text-base font-extrabold text-slate-900 dark:text-white">Search & Filter</h3>
            <button
              @click="resetFilters"
              class="px-4 py-2 text-xs font-bold text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white border border-slate-200 dark:border-slate-800 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-800 transition cursor-pointer"
            >
              Reset Filters
            </button>
          </div>

          <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
            <div>
              <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                Search guest name, room, or booking reference
              </label>
              <input
                v-model="filters.search"
                @keyup.enter="() => search(filters)"
                type="text"
                placeholder="Enter search term..."
                class="w-full px-3.5 py-2 border border-slate-300 dark:border-slate-800 bg-white dark:bg-slate-950 text-slate-900 dark:text-white text-xs rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
              />
            </div>

            <div>
              <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">Guest ID</label>
              <input
                v-model="filters.guest_id"
                @keyup.enter="() => search(filters)"
                type="text"
                placeholder="Filter by Guest ID..."
                class="w-full px-3.5 py-2 border border-slate-300 dark:border-slate-800 bg-white dark:bg-slate-950 text-slate-900 dark:text-white text-xs rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
              />
            </div>

            <div>
              <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">Room ID</label>
              <input
                v-model="filters.room_id"
                @keyup.enter="() => search(filters)"
                type="text"
                placeholder="Filter by Room ID..."
                class="w-full px-3.5 py-2 border border-slate-300 dark:border-slate-800 bg-white dark:bg-slate-950 text-slate-900 dark:text-white text-xs rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
              />
            </div>
          </div>

          <div class="flex justify-end">
            <button
              @click="() => search(filters)"
              class="flex items-center gap-2 px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-medium shadow-sm cursor-pointer"
            >
              <span class="material-symbols-rounded">search</span>
              <span>Search</span>
            </button>
          </div>
        </div>
      </div>

      <!-- Check-In Records Table -->
      <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-xs overflow-hidden space-y-0">
        <div class="border-b border-slate-200 dark:border-slate-800 bg-slate-50/80 dark:bg-slate-900/80 px-4 sm:px-6 py-4 flex items-center justify-between">
          <div>
            <h2 class="text-base font-extrabold text-slate-900 dark:text-white tracking-tight">Check-In Records</h2>
            <p class="text-xs text-slate-500 dark:text-slate-400 font-medium mt-0.5">
              Showing page {{ store.pagination.current_page }} of {{ store.pagination.last_page }} ({{ store.pagination.total }} total records)
            </p>
          </div>
        </div>

        <CheckInTable
          :loading="store.loading"
          :check-ins="store.checkIns"
          @view="view"
          @checkout="checkout"
          @delete="remove"
        />

        <!-- Empty State -->
        <div
          v-if="!store.loading && store.checkIns.length === 0"
          class="flex flex-col items-center justify-center p-16 text-center"
        >
          <div class="rounded-full bg-slate-100 p-4 mb-4">
            <span class="material-symbols-rounded text-4xl text-slate-400">event_note</span>
          </div>
          <h3 class="text-xl font-semibold text-slate-700 mb-2">No Check-In Records Found</h3>
          <p class="text-slate-500 mb-6">Start checking in guests or adjust your search filters</p>
          <button
            @click="refreshPage"
            class="flex items-center gap-2 px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-medium cursor-pointer"
          >
            <span class="material-symbols-rounded">refresh</span>
            <span>Refresh</span>
          </button>
        </div>
      </div>

      <!-- Interactive Pagination Bar -->
      <PaginationBar
        :pagination="store.pagination"
        :role-name="userRoleName"
        @page-change="handlePageChange"
        @per-page-change="handlePerPageChange"
      />
    </div>
  </DashboardLayout>
</template>
