<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import checkInService from '@/services/checkInService'

interface Guest {
  id: string
  first_name: string
  last_name: string
  email: string
  phone: string
}

interface Room {
  id: string
  room_number: string
  room_type: {
    name: string
  }
}

interface Reservation {
  id: string
  reservation_number: string
  check_in_date: string
  check_out_date: string
}

interface CheckIn {
  id: string
  guest: Guest
  room: Room
  reservation: Reservation
  checked_in_at: string
  expected_check_out_at: string
  checked_out_at: string | null
}

interface PaginationMeta {
  current_page: number
  last_page: number
  per_page: number
  total: number
}

const checkIns = ref<CheckIn[]>([])
const loading = ref(false)
const searchQuery = ref('')
const currentPage = ref(1)
const perPage = ref(10)
const paginationMeta = ref<PaginationMeta | null>(null)
const showCheckOutModal = ref(false)
const selectedCheckIn = ref<CheckIn | null>(null)
const processingCheckOut = ref(false)

// Computed: Filter only active check-ins (not checked out yet)
const activeCheckIns = computed(() => {
  return checkIns.value.filter((checkIn) => !checkIn.checked_out_at)
})

// Computed: Search filtered check-ins
const filteredCheckIns = computed(() => {
  if (!searchQuery.value) {
    return activeCheckIns.value
  }

  const query = searchQuery.value.toLowerCase()
  return activeCheckIns.value.filter((checkIn) => {
    const guestName = `${checkIn.guest.first_name} ${checkIn.guest.last_name}`.toLowerCase()
    const roomNumber = checkIn.room.room_number.toLowerCase()
    const reservationNumber = checkIn.reservation.reservation_number.toLowerCase()

    return (
      guestName.includes(query) ||
      roomNumber.includes(query) ||
      reservationNumber.includes(query) ||
      checkIn.guest.email.toLowerCase().includes(query) ||
      checkIn.guest.phone.includes(query)
    )
  })
})

const loadCheckIns = async () => {
  loading.value = true
  try {
    const response = await checkInService.getAll({
      page: currentPage.value,
      per_page: perPage.value
    })

    if (response.data.data) {
      checkIns.value = response.data.data
      paginationMeta.value = response.data.meta
    }
  } catch (error: any) {
    console.error('Failed to load check-ins:', error)
    alert('Failed to load check-ins. Please try again.')
  } finally {
    loading.value = false
  }
}

const openCheckOutModal = (checkIn: CheckIn) => {
  selectedCheckIn.value = checkIn
  showCheckOutModal.value = true
}

const closeCheckOutModal = () => {
  showCheckOutModal.value = false
  selectedCheckIn.value = null
}

const confirmCheckOut = async () => {
  if (!selectedCheckIn.value) return

  processingCheckOut.value = true
  try {
    await checkInService.checkOut(selectedCheckIn.value.id)

    // Success - reload data
    await loadCheckIns()
    closeCheckOutModal()
    alert('Guest checked out successfully!')
  } catch (error: any) {
    console.error('Check-out failed:', error)
    const errorMessage = error.response?.data?.message || error.message || 'Check-out failed'
    alert(`Check-out failed: ${errorMessage}`)
  } finally {
    processingCheckOut.value = false
  }
}

const calculateStayDuration = (checkedInAt: string): string => {
  const checkInDate = new Date(checkedInAt)
  const now = new Date()
  const diffMs = now.getTime() - checkInDate.getTime()
  const diffDays = Math.floor(diffMs / (1000 * 60 * 60 * 24))
  const diffHours = Math.floor((diffMs % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60))

  if (diffDays === 0) {
    return `${diffHours} hour${diffHours !== 1 ? 's' : ''}`
  } else if (diffDays === 1) {
    return `1 day, ${diffHours} hour${diffHours !== 1 ? 's' : ''}`
  } else {
    return `${diffDays} days`
  }
}

const formatDate = (dateString: string): string => {
  return new Date(dateString).toLocaleString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const goToPage = (page: number) => {
  currentPage.value = page
  loadCheckIns()
}

onMounted(() => {
  loadCheckIns()
})
</script>

<template>
  <DashboardLayout>
    <div class="w-full min-h-screen bg-gray-50 dark:bg-slate-900">
      <!-- Header -->
      <div class="bg-white dark:bg-slate-800 border-b border-gray-200 dark:border-slate-700 px-6 py-4">
        <div class="flex items-start justify-between">
          <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">🚪 Guest Check-Out</h1>
            <p class="text-gray-500 dark:text-slate-400 text-sm mt-1">
              Manage guest departures and room availability
            </p>
          </div>

          <!-- Stats Card -->
          <div class="bg-gradient-to-br from-red-500 to-red-600 text-white px-6 py-3 rounded-lg shadow-lg">
            <p class="text-xs opacity-90 uppercase">Active Guests</p>
            <p class="text-3xl font-bold mt-1">{{ activeCheckIns.length }}</p>
          </div>
        </div>

        <!-- Search Bar -->
        <div class="mt-4">
          <div class="relative">
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Search by guest name, room number, email, or phone..."
              class="w-full px-4 py-3 pl-12 border border-gray-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent dark:bg-slate-700 dark:text-white"
            />
            <svg
              class="absolute left-4 top-1/2 transform -translate-y-1/2 w-5 h-5 text-gray-400"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
              />
            </svg>
          </div>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="flex items-center justify-center min-h-[400px]">
        <div class="text-center">
          <div
            class="w-16 h-16 border-4 border-red-200 border-t-red-600 rounded-full animate-spin mx-auto mb-4"
          ></div>
          <p class="text-gray-600 dark:text-slate-400">Loading active guests...</p>
        </div>
      </div>

      <!-- Empty State -->
      <div
        v-else-if="filteredCheckIns.length === 0"
        class="flex flex-col items-center justify-center min-h-[400px] text-center px-8"
      >
        <div class="p-6 bg-gray-100 dark:bg-slate-800 rounded-full mb-4">
          <svg class="w-16 h-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
            />
          </svg>
        </div>
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">No Active Guests</h3>
        <p class="text-gray-600 dark:text-slate-400">
          {{ searchQuery ? 'No guests found matching your search.' : 'All guests have been checked out.' }}
        </p>
      </div>

      <!-- Check-Ins Table -->
      <div v-else class="p-6">
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-gray-200 dark:border-slate-700">
          <div class="w-full">
            <table class="w-full table-fixed">
              <thead class="bg-gray-50 dark:bg-slate-700">
                <tr>
                  <th class="w-[20%] px-2 py-2 text-left text-[10px] font-semibold text-gray-600 dark:text-slate-300 uppercase">
                    Guest
                  </th>
                  <th class="w-[12%] px-2 py-2 text-left text-[10px] font-semibold text-gray-600 dark:text-slate-300 uppercase">
                    Room
                  </th>
                  <th class="w-[15%] px-2 py-2 text-left text-[10px] font-semibold text-gray-600 dark:text-slate-300 uppercase">
                    Ref#
                  </th>
                  <th class="w-[13%] px-2 py-2 text-left text-[10px] font-semibold text-gray-600 dark:text-slate-300 uppercase">
                    In
                  </th>
                  <th class="w-[12%] px-2 py-2 text-left text-[10px] font-semibold text-gray-600 dark:text-slate-300 uppercase">
                    Stay
                  </th>
                  <th class="w-[13%] px-2 py-2 text-left text-[10px] font-semibold text-gray-600 dark:text-slate-300 uppercase">
                    Out
                  </th>
                  <th class="w-[15%] px-2 py-2 text-center text-[10px] font-semibold text-gray-600 dark:text-slate-300 uppercase">
                    Action
                  </th>
                </tr>
              </thead>
              <tbody class="bg-white dark:bg-slate-800 divide-y divide-gray-200 dark:divide-slate-700">
                <tr
                  v-for="checkIn in filteredCheckIns"
                  :key="checkIn.id"
                  class="hover:bg-gray-50 dark:hover:bg-slate-700 transition"
                >
                  <td class="px-2 py-2">
                    <div class="text-xs font-medium text-gray-900 dark:text-white truncate">
                      {{ checkIn.guest.first_name }} {{ checkIn.guest.last_name }}
                    </div>
                    <div class="text-[10px] text-gray-500 dark:text-slate-400 truncate">{{ checkIn.guest.phone }}</div>
                  </td>
                  <td class="px-2 py-2">
                    <div class="text-xs font-bold text-gray-900 dark:text-white">
                      {{ checkIn.room.room_number }}
                    </div>
                    <div class="text-[10px] text-gray-500 dark:text-slate-400 truncate">
                      {{ checkIn.room.room_type?.name || 'N/A' }}
                    </div>
                  </td>
                  <td class="px-2 py-2">
                    <span class="text-xs text-gray-900 dark:text-white font-mono block truncate">
                      {{ checkIn.reservation.reservation_number }}
                    </span>
                  </td>
                  <td class="px-2 py-2">
                    <div class="text-xs text-gray-900 dark:text-white">
                      {{ new Date(checkIn.checked_in_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric' }) }}
                    </div>
                    <div class="text-[10px] text-gray-500 dark:text-slate-400">
                      {{ new Date(checkIn.checked_in_at).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: false }) }}
                    </div>
                  </td>
                  <td class="px-2 py-2">
                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                      {{ calculateStayDuration(checkIn.checked_in_at) }}
                    </span>
                  </td>
                  <td class="px-2 py-2">
                    <div class="text-xs text-gray-900 dark:text-white">
                      {{ new Date(checkIn.expected_check_out_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric' }) }}
                    </div>
                    <div class="text-[10px] text-gray-500 dark:text-slate-400">
                      {{ new Date(checkIn.expected_check_out_at).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: false }) }}
                    </div>
                  </td>
                  <td class="px-2 py-2 text-center">
                    <button
                      @click="openCheckOutModal(checkIn)"
                      class="inline-flex items-center justify-center gap-1 px-2 py-1 bg-red-600 hover:bg-red-700 text-white rounded text-[10px] font-medium transition shadow-sm w-full"
                    >
                      <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                          stroke-linecap="round"
                          stroke-linejoin="round"
                          stroke-width="2"
                          d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"
                        />
                      </svg>
                      Out
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Pagination (if needed) -->
        <div v-if="paginationMeta && paginationMeta.last_page > 1" class="mt-4 flex justify-center gap-2">
          <button
            v-for="page in paginationMeta.last_page"
            :key="page"
            @click="goToPage(page)"
            :class="[
              'px-4 py-2 rounded-lg text-sm font-medium transition',
              currentPage === page
                ? 'bg-red-600 text-white'
                : 'bg-white dark:bg-slate-800 text-gray-700 dark:text-slate-300 hover:bg-gray-100 dark:hover:bg-slate-700 border border-gray-300 dark:border-slate-600'
            ]"
          >
            {{ page }}
          </button>
        </div>
      </div>

      <!-- Check-Out Confirmation Modal -->
      <div
        v-if="showCheckOutModal && selectedCheckIn"
        class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4"
        @click.self="closeCheckOutModal"
      >
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-2xl max-w-md w-full">
          <!-- Modal Header -->
          <div class="px-6 py-4 border-b border-gray-200 dark:border-slate-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Confirm Check-Out</h3>
          </div>

          <!-- Modal Body -->
          <div class="px-6 py-4">
            <p class="text-gray-600 dark:text-slate-400 mb-4">
              Are you sure you want to check out this guest?
            </p>

            <div class="bg-gray-50 dark:bg-slate-700 rounded-lg p-4 space-y-2">
              <div class="flex justify-between">
                <span class="text-sm text-gray-600 dark:text-slate-400">Guest:</span>
                <span class="text-sm font-medium text-gray-900 dark:text-white">
                  {{ selectedCheckIn.guest.first_name }} {{ selectedCheckIn.guest.last_name }}
                </span>
              </div>
              <div class="flex justify-between">
                <span class="text-sm text-gray-600 dark:text-slate-400">Room:</span>
                <span class="text-sm font-medium text-gray-900 dark:text-white">
                  {{ selectedCheckIn.room.room_number }}
                </span>
              </div>
              <div class="flex justify-between">
                <span class="text-sm text-gray-600 dark:text-slate-400">Stay Duration:</span>
                <span class="text-sm font-medium text-gray-900 dark:text-white">
                  {{ calculateStayDuration(selectedCheckIn.checked_in_at) }}
                </span>
              </div>
              <div class="flex justify-between">
                <span class="text-sm text-gray-600 dark:text-slate-400">Reservation:</span>
                <span class="text-sm font-medium text-gray-900 dark:text-white font-mono">
                  {{ selectedCheckIn.reservation.reservation_number }}
                </span>
              </div>
            </div>

            <div class="mt-4 p-3 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg">
              <p class="text-sm text-yellow-800 dark:text-yellow-200">
                ⚠️ This will mark the room as available and update the reservation status.
              </p>
            </div>
          </div>

          <!-- Modal Footer -->
          <div class="px-6 py-4 border-t border-gray-200 dark:border-slate-700 flex gap-3 justify-end">
            <button
              @click="closeCheckOutModal"
              :disabled="processingCheckOut"
              class="px-4 py-2 bg-gray-200 dark:bg-slate-700 text-gray-800 dark:text-slate-200 rounded-lg hover:bg-gray-300 dark:hover:bg-slate-600 transition disabled:opacity-50"
            >
              Cancel
            </button>
            <button
              @click="confirmCheckOut"
              :disabled="processingCheckOut"
              class="px-4 py-2 bg-red-600 hover:bg-red-700 disabled:bg-red-400 text-white rounded-lg transition flex items-center gap-2"
            >
              <svg
                v-if="processingCheckOut"
                class="w-4 h-4 animate-spin"
                fill="none"
                viewBox="0 0 24 24"
              >
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              <span>{{ processingCheckOut ? 'Processing...' : 'Confirm Check-Out' }}</span>
            </button>
          </div>
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>
