<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'

import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import CheckInTable from '@/components/checkin/CheckInTable.vue'
import CheckInDialog from '@/components/checkin/CheckinDialog.vue'

import { useCheckInStore } from '@/stores/checkInStore'
import { useReservationStore } from '@/stores/reservationStore'

import type { CheckIn } from '@/types/checkIn'

const router = useRouter()
const store = useCheckInStore()
const reservationStore = useReservationStore()

const showSuccessMessage = ref(false)
const successMessage = ref('')
const showCheckInDialog = ref(false)

const selectedCheckIn = ref<CheckIn | null>(null)

const filters = ref({
  search: '',
  guest_id: '',
  room_id: '',
})

// Get only confirmed reservations for check-in
const availableReservations = computed(() => {
  console.log('🎯 [CHECKIN VIEW COMPUTED] Computing availableReservations...')
  console.log('   Store has', reservationStore.reservations.length, 'total reservations')

  const filtered = reservationStore.reservations.filter((r: any) => {
    const isConfirmed = r.status === 'confirmed'
    if (!isConfirmed) {
      console.log(`   ❌ ${r.id}: status is "${r.status}", not "confirmed"`)
    }
    return isConfirmed
  })

  console.log(`✅ [CHECKIN VIEW COMPUTED] Found ${filtered.length} confirmed reservations`)

  if (filtered.length === 0 && reservationStore.reservations.length > 0) {
    console.warn('⚠️  [CHECKIN VIEW COMPUTED] NO CONFIRMED RESERVATIONS FOUND!')
    console.log('📊 [CHECKIN VIEW COMPUTED] Status breakdown:')
    reservationStore.reservations.forEach((r: any) => {
      console.log(`   - ${r.id}: status="${r.status}"`)
    })
  }

  return filtered
})

const totalCheckIns = computed(() => store.statistics.total_check_ins)
const activeGuestCount = computed(() => store.statistics.active_guests)
const checkedOutCount = computed(
  () => store.statistics.total_check_ins - store.statistics.active_guests,
)

const loadCheckIns = async (searchFilters = {}) => {
  try {
    console.log(' [CHECKIN VIEW] Starting to load check-ins with filters:', searchFilters)
    console.log(' [CHECKIN VIEW] Auth token:', !!localStorage.getItem('token'))
    console.log('👤 [CHECKIN VIEW] User:', localStorage.getItem('user'))

    await store.fetchCheckIns(searchFilters)

    console.log(' [CHECKIN VIEW] Check-ins loaded successfully!')
    console.log(' [CHECKIN VIEW] Total records:', store.checkIns.length)
    console.log('📄 [CHECKIN VIEW] Pagination:', store.pagination)
    console.log('📋 [CHECKIN VIEW] First check-in:', store.checkIns[0])
    console.log('⏳ [CHECKIN VIEW] Loading state:', store.loading)
    console.log(' [CHECKIN VIEW] Error state:', store.error)

    await store.fetchStatistics()

    console.log(' [CHECKIN VIEW] Statistics loaded:', store.statistics)
  } catch (error) {
    console.error(' [CHECKIN VIEW] Error loading check-ins:', error)
    console.error(' [CHECKIN VIEW] Store error:', store.error)
    showMessage('Failed to load check-ins', 'error')
  }
}

const loadReservations = async () => {
  try {
    console.log(' [CHECKIN VIEW] Loading all reservations for dialog...')
    // Load ALL reservations so dialog can filter them
    await reservationStore.fetchReservations()
    console.log(' [CHECKIN VIEW] Reservations loaded')
    console.log(' [CHECKIN VIEW] Total reservations:', reservationStore.reservations.length)

    if (reservationStore.reservations.length === 0) {
      console.warn('⚠️  [CHECKIN VIEW] NO RESERVATIONS LOADED FROM API!')
      console.log('📊 [CHECKIN VIEW] Store state:')
      console.log('   - reservations:', reservationStore.reservations)
      console.log('   - loading:', reservationStore.loading)
      return
    }

    // Log detailed structure of first reservation to understand data shape
    console.log(' [CHECKIN VIEW] DETAILED STRUCTURE OF FIRST RESERVATION:')
    const first = reservationStore.reservations[0]
    console.log(JSON.stringify(first, null, 2))

    // Log details of available ones
    const checkable = reservationStore.reservations.filter(
      (r: any) => r.status === 'confirmed' && r.room?.status === 'available',
    )
    console.log(
      ' [CHECKIN VIEW] Checkable reservations (confirmed + available room):',
      checkable.length,
    )

    // Log all confirmed reservations regardless of room status for debugging
    const allConfirmed = reservationStore.reservations.filter((r: any) => r.status === 'confirmed')
    console.log(' [CHECKIN VIEW] All confirmed reservations:', allConfirmed.length)
    allConfirmed.forEach((r: any) => {
      console.log(
        `  - ${r.id}: room.status=${r.room?.status}, room_id=${r.room?.id}, room_number=${r.room?.room_number}`,
      )
    })

    if (checkable.length === 0) {
      console.warn('  [CHECKIN VIEW] No checkable reservations found')
      console.log('   - Total reservations:', reservationStore.reservations.length)
      console.log('   - Confirmed reservations:', allConfirmed.length)

      if (allConfirmed.length > 0) {
        console.warn('   - ISSUE: Confirmed reservations exist but none have available rooms!')
        console.log('   - Room statuses for confirmed reservations:')
        allConfirmed.forEach((r: any) => {
          console.log(`     ${r.id}: room.status="${r.room?.status}"`)
        })
      }
    }
  } catch (error) {
    console.error(' Error loading reservations:', error)
  }
}

const openNewCheckInDialog = () => {
  showCheckInDialog.value = true
}

const handleCheckInSuccess = async () => {
  showMessage('Guest checked in successfully!')
  showCheckInDialog.value = false
  await refreshPage()
  await loadReservations() // Refresh reservations after check-in
}

const refreshPage = async () => {
  await loadCheckIns()
}

const search = async (newFilters: any) => {
  filters.value = newFilters
  await loadCheckIns(newFilters)
}

const view = (item: CheckIn) => {
  selectedCheckIn.value = item
  console.log('📋 Viewing check-in:', item)
}

const checkout = async (item: CheckIn) => {
  const confirmed = window.confirm(`Check out guest ${item.guest.full_name}?`)
  if (!confirmed) return

  try {
    console.log('✓ Checking out:', item.id)
    await store.checkOutGuest(item.id)
    showMessage(`${item.guest.full_name} checked out successfully`)
    await refreshPage()
  } catch (error: any) {
    console.error(' Checkout Error:', error)
    showMessage(error.message || 'Failed to check out guest', 'error')
  }
}

const remove = async (item: CheckIn) => {
  const confirmed = window.confirm('Are you sure you want to delete this check-in record?')
  if (!confirmed) return

  try {
    console.log('Deleting check-in:', item.id)
    await store.deleteCheckIn(item.id)
    await refreshPage()
    showMessage('Check-in record deleted successfully')
  } catch (error: any) {
    console.error(' Delete Error:', error)
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
  }
  await loadCheckIns()
}

onMounted(async () => {
  await refreshPage()
  // Force refresh of reservations to get latest confirmed status
  reservationStore.reservations = []
  await loadReservations()
})
</script>

<template>
  <DashboardLayout>
    <div class="space-y-6">
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
      <div class="bg-gradient-to-r from-blue-600 to-blue-700 rounded-2xl shadow-xl p-8 text-white">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
          <div>
            <h1 class="text-4xl font-bold mb-2">Guest Check-In Management</h1>
            <p class="text-blue-100 text-lg">
              Track guest arrivals, manage check-ins, and monitor occupancy in real-time
            </p>
          </div>
          <div class="flex gap-3">
            <button
              @click="openNewCheckInDialog"
              class="flex items-center gap-2 rounded-lg bg-white text-blue-600 px-6 py-3 hover:bg-blue-50 transition shadow-lg font-semibold"
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
            <div class="rounded-full bg-blue-100 p-3">
              <span class="material-symbols-rounded text-3xl text-blue-600">assignment_ind</span>
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
            <div class="rounded-full bg-green-100 p-3">
              <span class="material-symbols-rounded text-3xl text-green-600">person_check</span>
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
            <div class="rounded-full bg-purple-100 p-3">
              <span class="material-symbols-rounded text-3xl text-purple-600">logout</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Search & Filter Section -->
      <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex flex-col gap-4">
          <div class="flex items-center justify-between">
            <h3 class="text-lg font-semibold text-slate-800">Search & Filter</h3>
            <button
              @click="resetFilters"
              class="px-4 py-2 text-sm font-medium text-slate-600 hover:text-slate-900 border border-slate-200 rounded-lg hover:bg-slate-50 transition"
            >
              Reset Filters
            </button>
          </div>

          <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
            <!-- Search Input -->
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-2"
                >Search by guest or room</label
              >
              <input
                v-model="filters.search"
                type="text"
                placeholder="Enter guest name or room number..."
                class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              />
            </div>

            <!-- Guest Filter -->
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-2">Filter by guest</label>
              <input
                v-model="filters.guest_id"
                type="text"
                placeholder="Guest ID"
                class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              />
            </div>

            <!-- Room Filter -->
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-2">Filter by room</label>
              <input
                v-model="filters.room_id"
                type="text"
                placeholder="Room ID"
                class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              />
            </div>
          </div>

          <!-- Search Button -->
          <div class="flex justify-end">
            <button
              @click="() => search(filters)"
              class="flex items-center gap-2 px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-medium shadow-sm"
            >
              <span class="material-symbols-rounded">search</span>
              <span>Search</span>
            </button>
          </div>
        </div>
      </div>

      <!-- Check-In Records Table -->
      <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
        <div class="border-b border-slate-200 bg-slate-50 px-6 py-4">
          <h2 class="text-lg font-semibold text-slate-800">Check-In Records</h2>
          <p class="text-sm text-slate-500 mt-1">{{ store.checkIns.length }} record(s) found</p>
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
          <h3 class="text-xl font-semibold text-slate-700 mb-2">No Check-In Records</h3>
          <p class="text-slate-500 mb-6">Start checking in guests or adjust your filters</p>
          <button
            @click="refreshPage"
            class="flex items-center gap-2 px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-medium"
          >
            <span class="material-symbols-rounded">refresh</span>
            <span>Refresh</span>
          </button>
        </div>
      </div>

      <!-- Pagination Info -->
      <div
        v-if="store.checkIns.length > 0"
        class="flex justify-between items-center text-sm text-slate-600"
      >
        <div>
          Showing <span class="font-semibold">{{ store.checkIns.length }}</span> check-in record(s)
        </div>
        <button
          @click="refreshPage"
          class="text-blue-600 hover:text-blue-700 font-medium transition"
        >
          Refresh
        </button>
      </div>
    </div>
  </DashboardLayout>
</template>
