<script setup lang="ts">
import { onMounted, ref, computed } from 'vue'
import { useRouter } from 'vue-router'

import DashboardLayout from '../../../layouts/DashboardLayout.vue'
import ReservationFilter from '../../../components/reservation/ReservationFilter.vue'
import ReservationTable from '../../../components/reservation/ReservationTable.vue'
import DeleteReservationDialog from '../../../components/reservation/DeleteReservationDialog.vue'

import { useReservationStore } from '@/stores/reservationStore'

import type { Reservation, ReservationFilter as Filter } from '@/types/reservation'

const router = useRouter()
const store = useReservationStore()

const deleteDialog = ref(false)
const selectedReservation = ref<Reservation | null>(null)
const showSuccessMessage = ref(false)
const successMessage = ref('')

const filters = ref<Filter>({
  search: '',
  status: '',
  guest_id: '',
  room_id: '',
  page: 1,
  per_page: 10,
})

const totalReservations = computed(() => {
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

const createReservation = () => {
  router.push('/reservations/create')
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

  try {
    await store.deleteReservation(selectedReservation.value.id)
    deleteDialog.value = false
    selectedReservation.value = null
    await loadReservations()
    showMessage('Reservation deleted successfully')
  } catch (error) {
    console.error(' Error deleting reservation:', error)
    showMessage('Failed to delete reservation', 'error')
  }
}

const checkIn = async (reservation: Reservation) => {
  try {
    console.log('🔓 [CHECK-IN] Starting check-in for:', reservation.booking_reference)
    console.log('🔓 [CHECK-IN] Current status:', reservation.status)

    await store.checkInReservation(reservation.id)
    await loadReservations()
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
    await loadReservations()
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
    await loadReservations()
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
    await loadReservations()
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
  loadReservations()
})
</script>

<template>
  <DashboardLayout>
    <div class="space-y-6">
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
        <span class="font-medium text-slate-800">Reservation Management</span>
      </nav>

      <!-- Header -->
      <div
        class="bg-gradient-to-r from-purple-600 to-purple-700 rounded-2xl shadow-xl p-8 text-white"
      >
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
          <div>
            <h1 class="text-4xl font-bold mb-2">Reservation Management</h1>
            <p class="text-purple-100 text-lg">
              Manage hotel reservations, check-ins and guest stays
            </p>
          </div>
          <div class="flex gap-3">
            <button
              @click="createReservation"
              class="flex items-center gap-2 rounded-lg bg-white text-purple-600 px-6 py-3 hover:bg-purple-50 transition shadow-lg"
            >
              <span class="material-symbols-rounded text-xl">add</span>
              <span class="font-semibold">New Reservation</span>
            </button>
          </div>
        </div>
      </div>

      <!-- Statistics Cards -->
      <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
        <!-- Total -->
        <div
          class="relative overflow-hidden rounded-xl border border-slate-200 bg-gradient-to-br from-blue-50 to-white p-6 shadow-sm hover:shadow-md transition"
        >
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-slate-600">Total</p>
              <h2 class="mt-2 text-3xl font-bold text-slate-900">{{ totalReservations }}</h2>
            </div>
            <div class="rounded-full bg-blue-100 p-3">
              <span class="material-symbols-rounded text-3xl text-blue-600">event</span>
            </div>
          </div>
        </div>

        <!-- Pending -->
        <div
          class="relative overflow-hidden rounded-xl border border-slate-200 bg-gradient-to-br from-amber-50 to-white p-6 shadow-sm hover:shadow-md transition"
        >
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-slate-600">Pending</p>
              <h2 class="mt-2 text-3xl font-bold text-slate-900">{{ pendingCount }}</h2>
            </div>
            <div class="rounded-full bg-amber-100 p-3">
              <span class="material-symbols-rounded text-3xl text-amber-600">schedule</span>
            </div>
          </div>
        </div>

        <!-- Confirmed -->
        <div
          class="relative overflow-hidden rounded-xl border border-slate-200 bg-gradient-to-br from-green-50 to-white p-6 shadow-sm hover:shadow-md transition"
        >
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-slate-600">Confirmed</p>
              <h2 class="mt-2 text-3xl font-bold text-slate-900">{{ confirmedCount }}</h2>
            </div>
            <div class="rounded-full bg-green-100 p-3">
              <span class="material-symbols-rounded text-3xl text-green-600">check_circle</span>
            </div>
          </div>
        </div>

        <!-- Checked In -->
        <div
          class="relative overflow-hidden rounded-xl border border-slate-200 bg-gradient-to-br from-purple-50 to-white p-6 shadow-sm hover:shadow-md transition"
        >
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-slate-600">Checked In</p>
              <h2 class="mt-2 text-3xl font-bold text-slate-900">{{ checkedInCount }}</h2>
            </div>
            <div class="rounded-full bg-purple-100 p-3">
              <span class="material-symbols-rounded text-3xl text-purple-600">login</span>
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
        class="rounded-2xl border-2 border-dashed border-slate-300 bg-white p-16 text-center"
      >
        <div
          class="mx-auto w-24 h-24 rounded-full bg-slate-100 flex items-center justify-center mb-6"
        >
          <span class="material-symbols-rounded text-5xl text-slate-400">event</span>
        </div>
        <h2 class="text-2xl font-bold text-slate-700 mb-2">No Reservations Found</h2>
        <p class="text-slate-500 mb-6 max-w-md mx-auto">
          No reservations match your current filters. Create your first reservation to get started.
        </p>
        <button
          @click="createReservation"
          class="inline-flex items-center gap-2 rounded-lg bg-purple-600 px-6 py-3 text-white hover:bg-purple-700 transition shadow-lg font-medium"
        >
          <span class="material-symbols-rounded">add</span>
          Create First Reservation
        </button>
      </div>

      <!-- Pagination -->
      <div
        v-if="hasReservations"
        class="flex flex-col gap-4 rounded-xl border bg-white p-6 shadow-sm sm:flex-row sm:items-center sm:justify-between"
      >
        <div class="text-slate-600">
          Showing
          <span class="font-semibold text-slate-900">{{ reservationsOnPage }}</span>
          of
          <span class="font-semibold text-slate-900">{{ totalReservations }}</span>
          reservations
        </div>
        <div class="flex items-center gap-2">
          <button
            @click="previousPage"
            :disabled="currentPage <= 1"
            class="flex items-center gap-1 rounded-lg border border-slate-300 px-4 py-2 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50 transition"
          >
            <span class="material-symbols-rounded text-sm">chevron_left</span>
            <span>Previous</span>
          </button>

          <div class="flex items-center gap-1 px-4">
            <span class="font-semibold text-slate-700">Page {{ currentPage }}</span>
            <span class="text-slate-500">of {{ lastPage }}</span>
          </div>

          <button
            @click="nextPage"
            :disabled="currentPage >= lastPage"
            class="flex items-center gap-1 rounded-lg border border-slate-300 px-4 py-2 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50 transition"
          >
            <span>Next</span>
            <span class="material-symbols-rounded text-sm">chevron_right</span>
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
