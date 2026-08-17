<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import checkInService from '@/services/checkInService'
import PaginationBar from '@/components/common/PaginationBar.vue'
import { useAuthStore } from '@/stores/auth'

const authStore = useAuthStore()

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
const paginationMeta = ref<PaginationMeta>({
  current_page: 1,
  last_page: 1,
  per_page: 10,
  total: 0,
})

const showCheckOutModal = ref(false)
const selectedCheckIn = ref<CheckIn | null>(null)
const processingCheckOut = ref(false)

const userRoleName = computed(() => {
  const role = String(authStore.user?.role || 'Staff').toLowerCase()
  return role.charAt(0).toUpperCase() + role.slice(1)
})

// Filter active check-ins
const activeCheckIns = computed(() => {
  return checkIns.value.filter((checkIn) => !checkIn.checked_out_at)
})

const filteredCheckIns = computed(() => {
  if (!searchQuery.value) {
    return activeCheckIns.value
  }

  const query = searchQuery.value.toLowerCase().trim()
  return activeCheckIns.value.filter((checkIn) => {
    const guestName = `${checkIn.guest?.first_name || ''} ${checkIn.guest?.last_name || ''}`.toLowerCase()
    const roomNumber = (checkIn.room?.room_number || '').toLowerCase()
    const reservationNumber = (checkIn.reservation?.reservation_number || '').toLowerCase()
    const email = (checkIn.guest?.email || '').toLowerCase()
    const phone = checkIn.guest?.phone || ''

    return (
      guestName.includes(query) ||
      roomNumber.includes(query) ||
      reservationNumber.includes(query) ||
      email.includes(query) ||
      phone.includes(query)
    )
  })
})

const loadCheckIns = async () => {
  loading.value = true
  try {
    const response = await checkInService.getAll({
      page: currentPage.value,
      per_page: perPage.value,
      search: searchQuery.value,
      status: 'active',
    })

    if (response.data) {
      if (response.data.data) {
        checkIns.value = response.data.data
        paginationMeta.value = {
          current_page: response.data.current_page || currentPage.value,
          last_page: response.data.last_page || 1,
          per_page: response.data.per_page || perPage.value,
          total: response.data.total || response.data.data.length,
        }
      } else if (Array.isArray(response.data)) {
        checkIns.value = response.data
        paginationMeta.value = {
          current_page: 1,
          last_page: 1,
          per_page: response.data.length || 10,
          total: response.data.length,
        }
      }
    }
  } catch (error: any) {
    console.error('Failed to load check-ins:', error)
  } finally {
    loading.value = false
  }
}

const handlePageChange = (newPage: number) => {
  currentPage.value = newPage
  loadCheckIns()
}

const handlePerPageChange = (newPerPage: number) => {
  perPage.value = newPerPage
  currentPage.value = 1
  loadCheckIns()
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
  if (!checkedInAt) return '0 days'
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

onMounted(() => {
  loadCheckIns()
})
</script>

<template>
  <DashboardLayout>
    <div class="w-full min-h-screen bg-gray-50 dark:bg-slate-900 p-6 space-y-6">
      <!-- Header -->
      <div class="bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 rounded-2xl p-6 shadow-xs">
        <div class="flex items-start justify-between flex-wrap gap-4">
          <div>
            <div class="flex items-center gap-2 mb-1">
              <h1 class="text-2xl font-bold text-gray-900 dark:text-white">🚪 Guest Check-Out</h1>
              <span class="text-xs font-bold uppercase bg-purple-500/10 text-purple-600 dark:text-purple-400 px-2.5 py-0.5 rounded-full border border-purple-500/20">
                {{ userRoleName }}
              </span>
            </div>
            <p class="text-gray-500 dark:text-slate-400 text-sm">
              Manage guest departures, room release, and occupancy records
            </p>
          </div>

          <!-- Stats Card -->
          <div class="bg-gradient-to-br from-rose-500 to-rose-600 text-white px-6 py-3 rounded-2xl shadow-md">
            <p class="text-xs font-bold opacity-90 uppercase tracking-wider">Active Guests</p>
            <p class="text-3xl font-extrabold mt-0.5">{{ paginationMeta.total || activeCheckIns.length }}</p>
          </div>
        </div>

        <!-- Search Bar -->
        <div class="mt-5">
          <div class="relative">
            <input
              v-model="searchQuery"
              @keyup.enter="loadCheckIns"
              type="text"
              placeholder="Search by guest name, room number, email, phone, or reservation ref..."
              class="w-full px-4 py-3 pl-12 border border-gray-300 dark:border-slate-700 rounded-xl focus:ring-2 focus:ring-rose-500/50 focus:border-transparent dark:bg-slate-950 dark:text-white text-sm font-semibold"
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
      <div v-if="loading" class="flex items-center justify-center min-h-[300px]">
        <div class="text-center">
          <div class="w-12 h-12 border-4 border-rose-200 border-t-rose-600 rounded-full animate-spin mx-auto mb-3"></div>
          <p class="text-xs font-semibold text-gray-600 dark:text-slate-400">Loading active guests...</p>
        </div>
      </div>

      <!-- Empty State -->
      <div
        v-else-if="filteredCheckIns.length === 0"
        class="flex flex-col items-center justify-center min-h-[300px] text-center px-8 bg-white dark:bg-slate-800 rounded-2xl p-8 border border-slate-200 dark:border-slate-700"
      >
        <div class="p-4 bg-gray-100 dark:bg-slate-700 rounded-full mb-3">
          <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
        </div>
        <h3 class="text-base font-bold text-gray-900 dark:text-white mb-1">No Active Guests Found</h3>
        <p class="text-xs text-gray-500 dark:text-slate-400">
          {{ searchQuery ? 'No guests found matching your search term.' : 'All registered guests have been checked out.' }}
        </p>
      </div>

      <!-- Check-Ins Table + Pagination -->
      <div v-else class="space-y-4">
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xs border border-gray-200 dark:border-slate-700 overflow-hidden">
          <table class="w-full text-left border-collapse">
            <thead class="bg-gray-50 dark:bg-slate-900 border-b border-gray-200 dark:border-slate-700">
              <tr>
                <th class="px-4 py-3 text-xs font-bold text-gray-600 dark:text-slate-300 uppercase">Guest</th>
                <th class="px-4 py-3 text-xs font-bold text-gray-600 dark:text-slate-300 uppercase">Room</th>
                <th class="px-4 py-3 text-xs font-bold text-gray-600 dark:text-slate-300 uppercase">Booking Ref</th>
                <th class="px-4 py-3 text-xs font-bold text-gray-600 dark:text-slate-300 uppercase">Check-In Time</th>
                <th class="px-4 py-3 text-xs font-bold text-gray-600 dark:text-slate-300 uppercase">Stay Duration</th>
                <th class="px-4 py-3 text-xs font-bold text-gray-600 dark:text-slate-300 uppercase">Expected Out</th>
                <th class="px-4 py-3 text-center text-xs font-bold text-gray-600 dark:text-slate-300 uppercase">Action</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-slate-700/60 bg-white dark:bg-slate-800">
              <tr
                v-for="checkIn in filteredCheckIns"
                :key="checkIn.id"
                class="hover:bg-gray-50/80 dark:hover:bg-slate-700/50 transition"
              >
                <td class="px-4 py-3">
                  <div class="text-xs font-bold text-gray-900 dark:text-white">
                    {{ checkIn.guest?.first_name }} {{ checkIn.guest?.last_name }}
                  </div>
                  <div class="text-[11px] text-gray-500 dark:text-slate-400 font-medium">{{ checkIn.guest?.phone }}</div>
                </td>
                <td class="px-4 py-3">
                  <div class="text-xs font-black text-slate-900 dark:text-white">
                    Room {{ checkIn.room?.room_number }}
                  </div>
                  <div class="text-[11px] text-gray-500 dark:text-slate-400 font-medium">
                    {{ checkIn.room?.room_type?.name || 'Standard' }}
                  </div>
                </td>
                <td class="px-4 py-3">
                  <span class="text-xs font-mono font-bold text-purple-600 dark:text-purple-400">
                    {{ checkIn.reservation?.reservation_number || 'N/A' }}
                  </span>
                </td>
                <td class="px-4 py-3">
                  <div class="text-xs font-semibold text-gray-900 dark:text-white">
                    {{ checkIn.checked_in_at ? new Date(checkIn.checked_in_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) : 'N/A' }}
                  </div>
                  <div class="text-[11px] text-gray-500 dark:text-slate-400 font-mono">
                    {{ checkIn.checked_in_at ? new Date(checkIn.checked_in_at).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: false }) : '' }}
                  </div>
                </td>
                <td class="px-4 py-3">
                  <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-100 text-blue-800 dark:bg-blue-900/60 dark:text-blue-200">
                    {{ calculateStayDuration(checkIn.checked_in_at) }}
                  </span>
                </td>
                <td class="px-4 py-3">
                  <div class="text-xs font-semibold text-gray-900 dark:text-white">
                    {{ checkIn.expected_check_out_at ? new Date(checkIn.expected_check_out_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) : 'N/A' }}
                  </div>
                </td>
                <td class="px-4 py-3 text-center">
                  <button
                    @click="openCheckOutModal(checkIn)"
                    class="inline-flex items-center justify-center gap-1.5 px-3 py-1.5 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-xs font-bold transition shadow-xs cursor-pointer"
                  >
                    <span>Check Out</span>
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination Bar -->
        <PaginationBar
          :pagination="paginationMeta"
          :role-name="userRoleName"
          @page-change="handlePageChange"
          @per-page-change="handlePerPageChange"
        />
      </div>

      <!-- Check Out Modal -->
      <div v-if="showCheckOutModal && selectedCheckIn" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-xs p-4">
        <div class="bg-white dark:bg-slate-800 rounded-2xl max-w-md w-full p-6 shadow-2xl space-y-4">
          <h3 class="text-lg font-bold text-gray-900 dark:text-white">Confirm Guest Check-Out</h3>
          <p class="text-xs text-gray-600 dark:text-slate-300 leading-relaxed">
            Are you sure you want to check out <strong>{{ selectedCheckIn.guest?.first_name }} {{ selectedCheckIn.guest?.last_name }}</strong> from Room <strong>{{ selectedCheckIn.room?.room_number }}</strong>? This will set room status to Available/Cleaning.
          </p>

          <div class="flex justify-end gap-3 pt-2">
            <button
              @click="closeCheckOutModal"
              class="px-4 py-2 text-xs font-semibold text-gray-600 dark:text-slate-400 bg-gray-100 dark:bg-slate-700 rounded-xl hover:bg-gray-200 transition cursor-pointer"
            >
              Cancel
            </button>
            <button
              @click="confirmCheckOut"
              :disabled="processingCheckOut"
              class="px-5 py-2 text-xs font-bold text-white bg-rose-600 hover:bg-rose-700 rounded-xl transition shadow-xs disabled:opacity-50 cursor-pointer"
            >
              {{ processingCheckOut ? 'Processing...' : 'Confirm Check-Out' }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>
