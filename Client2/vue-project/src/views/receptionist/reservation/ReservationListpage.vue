<script setup lang="ts">
import { onMounted, ref, computed } from 'vue'
import { useRouter } from 'vue-router'

import DashboardLayout from '../../../layouts/DashboardLayout.vue'
import ReservationFilter from '../../../components/reservation/ReservationFilter.vue'
import ReservationTable from '../../../components/reservation/ReservationTable.vue'
import DeleteReservationDialog from '../../../components/reservation/DeleteReservationDialog.vue'
import { ChevronLeft, ChevronRight } from 'lucide-vue-next'

import { useReservationStore } from '@/stores/reservationStore'
import { useGuestStore } from '@/stores/guestStore'
import roomService from '@/services/roomService'

import type { Reservation, ReservationFilter as Filter } from '@/types/reservation'

const router = useRouter()
const store = useReservationStore()
const guestStore = useGuestStore()

const deleteDialog = ref(false)
const selectedReservation = ref<Reservation | null>(null)
const showSuccessMessage = ref(false)
const successMessage = ref('')
const rooms = ref<any[]>([])

const filters = ref<Filter>({
  search: '',
  status: '',
  guest_id: '',
  room_id: '',
  check_in_date: '',
  check_out_date: '',
  page: 1,
  per_page: 10,
})

const totalReservations = computed(() =>  {
  const total = store.pagination.total || 0
  // Ensure it's a number, not an array
  return typeof total === 'number' ? total : Array.isArray(total) ? total[0] : 0
})
const currentPage = computed(() => store.pagination.current_page || 1)
const lastPage = computed(() => store.pagination.last_page || 1)
const reservationsOnPage = computed(() => store.reservations?.length || 0)
const hasReservations = computed(() => reservationsOnPage.value > 0)

const pendingCount = computed(() => store.reservations.filter((r) => r.status === 'pending').length)
const confirmedCount = computed(
  () => store.reservations.filter((r) => r.status === 'confirmed').length,
)
const checkedInCount = computed(
  () => store.reservations.filter((r) => r.status === 'checked_in').length,
)
const cancelledCount = computed(
  () => store.reservations.filter((r) => r.status === 'cancelled').length,
)

const loadReservations = async () => {
  try {
    await store.fetchReservations(filters.value)
  } catch (error) {
    console.error('Error loading reservations:', error)
    showMessage('Failed to load reservations', 'error')
  }
}

const loadGuestsAndRooms = async () => {
  try {
    // Load guests
    await guestStore.fetchGuests()
    
    // Load rooms
    const roomResponse = await roomService.getAllRooms()
    rooms.value = roomResponse.data || roomResponse || []
  } catch (error) {
    console.error('Error loading guests/rooms:', error)
  }
}

const viewReservation = (reservation: Reservation) => {
  router.push(`/reservations/${reservation.id}`)
}

const editReservation = (reservation: Reservation) => {
  router.push(`/reservations/${reservation.id}/edit`)
}

const confirmDelete = (reservation: Reservation) => {
  selectedReservation.value = reservation
  deleteDialog.value = true
}

const deleteReservation = async () => {
  if (!selectedReservation.value) return
  const reservationId = selectedReservation.value.id

  try {
    await store.deleteReservation(reservationId)
    deleteDialog.value = false
    selectedReservation.value = null

    // Remove from list instead of full refetch
    const index = store.reservations.findIndex((r) => r.id === reservationId)
    if (index !== -1) {
      store.reservations.splice(index, 1)
    }

    showMessage('Reservation deleted successfully')
  } catch (error: any) {
    console.error(' Error deleting reservation:', error)
    const errorMsg = error.response?.data?.message || error.message || 'Failed to delete reservation'
    showMessage(errorMsg, 'error')
  }
}

const checkIn = async (reservation: Reservation) => {
  try {
    console.log('🔓 [CHECK-IN] Starting check-in for:', reservation.booking_reference)
    console.log('🔓 [CHECK-IN] Current status:', reservation.status)

    await store.checkInReservation(reservation.id)
    showMessage(`${reservation.booking_reference} checked in successfully`)
  } catch (error: any) {
    console.error(' [CHECK-IN] Error:', error)
    console.error(' [CHECK-IN] Error response:', error.response?.data)

    const errorMsg =
      error.response?.data?.message || error.message || 'Failed to check in reservation'
    showMessage(errorMsg, 'error')
  }
}

const checkOut = async (reservation: Reservation) => {
  try {
    console.log(' [CHECK-OUT] Starting check-out for:', reservation.booking_reference)
    console.log('🚪 [CHECK-OUT] Current status:', reservation.status)

    await store.checkOutReservation(reservation.id)
    showMessage(`${reservation.booking_reference} checked out successfully`)
  } catch (error: any) {
    console.error(' [CHECK-OUT] Error:', error)
    console.error(' [CHECK-OUT] Error response:', error.response?.data)

    const errorMsg =
      error.response?.data?.message || error.message || 'Failed to check out reservation'
    showMessage(errorMsg, 'error')
  }
}

const cancelReservation = async (reservation: Reservation) => {
  try {
    console.log(' [CANCEL] Starting cancellation for:', reservation.booking_reference)
    console.log(' [CANCEL] Current status:', reservation.status)

    await store.cancelReservationAction(reservation.id)
    showMessage(`${reservation.booking_reference} cancelled successfully`)
  } catch (error: any) {
    console.error(' [CANCEL] Error:', error)
    console.error(' [CANCEL] Error response:', error.response?.data)

    const errorMsg =
      error.response?.data?.message || error.message || 'Failed to cancel reservation'
    showMessage(errorMsg, 'error')
  }
}

const confirmReservation = async (reservation: Reservation) => {
  try {
    console.log(' [CONFIRM] Starting confirmation for:', reservation.booking_reference)
    console.log(' [CONFIRM] Reservation ID:', reservation.id)
    console.log(' [CONFIRM] Current status:', reservation.status)

    await store.confirmReservation(reservation.id)
    showMessage(`${reservation.booking_reference} confirmed successfully`)
  } catch (error: any) {
    console.error(' [CONFIRM] Error confirming:', error)
    console.error(' [CONFIRM] Error response:', error.response?.data)
    console.error(' [CONFIRM] Error message:', error.response?.data?.message || error.message)
    console.error(' [CONFIRM] Error status:', error.response?.status)

    const errorMsg =
      error.response?.data?.message || error.message || 'Failed to confirm reservation'
    showMessage(errorMsg, 'error')
  }
}

const changePerPage = async (event: Event) => {
  const target = event.target as HTMLSelectElement
  filters.value.per_page = Number(target.value)
  filters.value.page = 1
  await loadReservations()
}

const goToPage = async (page: number) => {
  if (page < 1 || page > lastPage.value) return
  filters.value.page = page
  await loadReservations()
}

const previousPage = async () => {
  if (filters.value.page <= 1) return
  filters.value.page--
  await loadReservations()
}

const nextPage = async () => {
  if (filters.value.page >= lastPage.value) return
  filters.value.page++
  await loadReservations()
}

const showingFrom = computed(() => {
  if (totalReservations.value === 0) return 0
  return (currentPage.value - 1) * (filters.value.per_page || 10) + 1
})

const showingTo = computed(() => {
  return Math.min(currentPage.value * (filters.value.per_page || 10), totalReservations.value)
})

const paginationPages = computed(() => {
  const pages: number[] = []
  const total = lastPage.value
  const current = currentPage.value

  for (let i = Math.max(1, current - 2); i <= Math.min(total, current + 2); i++) {
    pages.push(i)
  }
  return pages
})

const refreshReservations = async () => {
  await loadReservations()
  showMessage('Reservations refreshed')
}

const resetFilters = async () => {
  filters.value = {
    search: '',
    status: '',
    guest_id: '',
    room_id: '',
    check_in_date: '',
    check_out_date: '',
    page: 1,
    per_page: 10,
  }
  await loadReservations()
}

const showMessage = (message: string, type: 'success' | 'error' = 'success') => {
  successMessage.value = message
  showSuccessMessage.value = true
  setTimeout(() => {
    showSuccessMessage.value = false
  }, 3000)
}

onMounted(() => {
  loadGuestsAndRooms()
  loadReservations()
})
</script>

<template>
  <DashboardLayout>
    <div class="space-y-6 bg-slate-50 dark:bg-slate-950 min-h-screen p-4 sm:p-6 max-w-full overflow-hidden font-sans">
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
          class="fixed top-4 right-4 z-50 bg-green-500 dark:bg-green-700 text-white px-6 py-3 rounded-lg shadow-lg flex items-center gap-3"
        >
          <span>{{ successMessage }}</span>
        </div>
      </transition>

      <!-- Breadcrumb -->
      <nav class="flex items-center text-sm text-slate-500 dark:text-slate-400">
        <a href="/dashboard" class="hover:text-slate-700 dark:hover:text-slate-300 transition">Dashboard</a>
        <span class="mx-2">/</span>
        <span class="font-medium text-slate-800 dark:text-slate-200">Reservation Management</span>
      </nav>

      <!-- Header -->
      <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-gray-100 dark:border-slate-700 p-6 sm:p-8">
        <div>
          <h1 class="text-3xl sm:text-4xl font-bold text-slate-900 dark:text-white">Reservations</h1>
          <p class="text-slate-600 dark:text-slate-400 text-base sm:text-lg mt-2">
            Manage hotel reservations, check-ins and guest stays
          </p>
        </div>
      </div>

      <!-- Statistics Cards -->
      <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
        <!-- Total -->
        <div
          class="relative overflow-hidden rounded-xl border border-slate-200 dark:border-slate-700 bg-gradient-to-br from-blue-50 dark:from-blue-900/20 to-white dark:to-slate-800 p-6 shadow-sm hover:shadow-md dark:hover:shadow-slate-900/50 transition"
        >
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-slate-600 dark:text-slate-400">Total</p>
              <h2 class="mt-2 text-3xl font-bold text-slate-900 dark:text-white">{{ totalReservations }}</h2>
            </div>
            <!-- <div class="rounded-full bg-blue-100 dark:bg-blue-900/40 p-3">
              <span class="material-symbols-rounded text-3xl text-blue-600 dark:text-blue-400">event</span>
            </div> -->
          </div>
        </div>

        <!-- Pending -->
        <div
          class="relative overflow-hidden rounded-xl border border-slate-200 dark:border-slate-700 bg-gradient-to-br from-amber-50 dark:from-amber-900/20 to-white dark:to-slate-800 p-6 shadow-sm hover:shadow-md dark:hover:shadow-slate-900/50 transition"
        >
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-slate-600 dark:text-slate-400">Pending</p>
              <h2 class="mt-2 text-3xl font-bold text-slate-900 dark:text-white">{{ pendingCount }}</h2>
            </div>
          </div>
        </div>

        <!-- Confirmed -->
        <div
          class="relative overflow-hidden rounded-xl border border-slate-200 dark:border-slate-700 bg-gradient-to-br from-green-50 dark:from-green-900/20 to-white dark:to-slate-800 p-6 shadow-sm hover:shadow-md dark:hover:shadow-slate-900/50 transition"
        >
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-slate-600 dark:text-slate-400">Confirmed</p>
              <h2 class="mt-2 text-3xl font-bold text-slate-900 dark:text-white">{{ confirmedCount }}</h2>
            </div>
            <!-- <div class="rounded-full bg-green-100 dark:bg-green-900/40 p-3">
              <span class="material-symbols-rounded text-3xl text-green-600 dark:text-green-400">check_circle</span>
            </div> -->
          </div>
        </div>

        <!-- Checked In -->
        <div
          class="relative overflow-hidden rounded-xl border border-slate-200 dark:border-slate-700 bg-gradient-to-br from-purple-50 dark:from-purple-900/20 to-white dark:to-slate-800 p-6 shadow-sm hover:shadow-md dark:hover:shadow-slate-900/50 transition"
        >
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-slate-600 dark:text-slate-400">Checked In</p>
              <h2 class="mt-2 text-3xl font-bold text-slate-900 dark:text-white">{{ checkedInCount }}</h2>
            </div>
          </div>
        </div>
      </div>

      <!-- Filter -->
      <ReservationFilter
        :filters="filters"
        @update:filters="filters = $event"
        @search="loadReservations"
        @reset="resetFilters"
      />

      <!-- Table -->
      <ReservationTable
        :reservations="store.reservations"
        :loading="store.loading"
        @view="viewReservation"
        @edit="editReservation"
        @delete="confirmDelete"
        @confirm="confirmReservation"
        @check-in="checkIn"
        @check-out="checkOut"
        @cancel="cancelReservation"
      />

      <!-- Empty State -->
      <div
        v-if="!store.loading && !hasReservations"
        class="rounded-2xl border-2 border-dashed border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 p-16 text-center"
      >
        <div
          class="mx-auto w-24 h-24 rounded-full bg-slate-100 dark:bg-slate-700 flex items-center justify-center mb-6"
        >
          <span class="material-symbols-rounded text-5xl text-slate-400 dark:text-slate-500">event</span>
        </div>
        <h2 class="text-2xl font-bold text-slate-700 dark:text-slate-300 mb-2">No Reservations Found</h2>
        <p class="text-slate-500 dark:text-slate-400 mb-6 max-w-md mx-auto">
          No reservations match your current filters. Guests can make reservations online.
        </p>
      </div>

      <!-- Pagination Bar with Per-Page options 5, 10, 20, 50 -->
      <div
        v-if="hasReservations"
        class="flex flex-col sm:flex-row items-center justify-between gap-4 rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-4 shadow-xs text-xs font-sans"
      >
        <!-- Left Side: Per Page Selector & Showing Count Info -->
        <div class="flex flex-wrap items-center gap-4 text-slate-600 dark:text-slate-400">
          <div class="flex items-center gap-2">
            <span class="font-bold text-slate-700 dark:text-slate-300">Items per page:</span>
            <select
              :value="filters.per_page"
              @change="changePerPage"
              class="px-2.5 py-1.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white font-black focus:outline-none focus:border-blue-500 cursor-pointer shadow-2xs"
            >
              <option :value="5">5</option>
              <option :value="10">10</option>
              <option :value="20">20</option>
              <option :value="50">50</option>
            </select>
          </div>

          <div class="text-xs font-medium">
            Showing <span class="font-extrabold text-slate-900 dark:text-white">{{ showingFrom }}</span> to
            <span class="font-extrabold text-slate-900 dark:text-white">{{ showingTo }}</span> of
            <span class="font-extrabold text-slate-900 dark:text-white">{{ totalReservations }}</span> reservations
          </div>
        </div>

        <!-- Right Side: Page Navigation Buttons -->
        <div class="flex items-center gap-1.5">
          <button
            @click="previousPage"
            :disabled="currentPage <= 1"
            class="p-2 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 disabled:opacity-40 disabled:cursor-not-allowed transition cursor-pointer flex items-center gap-1 font-bold"
            title="Previous Page"
          >
            <ChevronLeft class="w-4 h-4" />
            <span class="hidden sm:inline">Prev</span>
          </button>

          <div class="flex items-center gap-1">
            <button
              v-for="p in paginationPages"
              :key="p"
              @click="goToPage(p)"
              :class="[
                'w-8 h-8 rounded-xl font-black text-xs transition cursor-pointer flex items-center justify-center border',
                currentPage === p
                  ? 'bg-blue-600 border-blue-600 text-white shadow-md shadow-blue-600/20'
                  : 'bg-slate-50 dark:bg-slate-950 border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800'
              ]"
            >
              {{ p }}
            </button>
          </div>

          <button
            @click="nextPage"
            :disabled="currentPage >= lastPage"
            class="p-2 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 disabled:opacity-40 disabled:cursor-not-allowed transition cursor-pointer flex items-center gap-1 font-bold"
            title="Next Page"
          >
            <span class="hidden sm:inline">Next</span>
            <ChevronRight class="w-4 h-4" />
          </button>
        </div>
      </div>

      <!-- Delete Dialog -->
      <DeleteReservationDialog
        v-model="deleteDialog"
        :reservation="selectedReservation"
        :loading="store.loading"
        @confirm="deleteReservation"
      />
    </div>
  </DashboardLayout>
</template>
