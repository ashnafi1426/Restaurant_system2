<script setup lang="ts">
import { ref, onMounted, computed, watch } from 'vue'
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import checkInService from '@/services/checkInService'
import { useAuthStore } from '@/stores/auth'
import {
  LogOut,
  Users,
  Search,
  Filter,
  X,
  RefreshCw,
  Maximize2,
  Minimize2,
  RotateCcw,
  BedDouble,
  Clock,
  Calendar,
  UserCheck,
  CheckCircle2,
  ChevronLeft,
  ChevronRight,
  AlertCircle,
  Building,
  Building2,
  Loader2,
} from 'lucide-vue-next'

import { useHotelStore } from '@/stores/hotelStore'
import { useLanguageStore } from '@/stores/language'

const authStore = useAuthStore()
const hotelStore = useHotelStore()
const languageStore = useLanguageStore()

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

const checkIns = ref<CheckIn[]>([])
const loading = ref(false)
const searchQuery = ref('')
const filterRoom = ref('')
const filterDeparture = ref<'all' | 'due_today' | 'overdue'>('all')

const isFilterOpen = ref(false)
const isFullscreen = ref(false)

const currentPage = ref(1)
const perPage = ref(10)
const totalCheckInsCount = ref(0)

const showCheckOutModal = ref(false)
const selectedCheckIn = ref<CheckIn | null>(null)
const processingCheckOut = ref(false)
const toastMessage = ref<string | null>(null)

const userRoleName = computed(() => {
  const role = String(authStore.user?.role || 'Staff').toLowerCase()
  return role.charAt(0).toUpperCase() + role.slice(1)
})

const showToast = (msg: string) => {
  toastMessage.value = msg
  setTimeout(() => {
    toastMessage.value = null
  }, 3000)
}

// Active in-house guests only for checkout view
const activeCheckIns = computed(() => {
  return checkIns.value.filter((checkIn) => !checkIn.checked_out_at)
})

const dueTodayCount = computed(() => {
  const todayStr = new Date().toISOString().slice(0, 10)
  return activeCheckIns.value.filter((c) => (c.expected_check_out_at || '').startsWith(todayStr))
    .length
})

const overdueCount = computed(() => {
  const now = new Date()
  return activeCheckIns.value.filter((c) => {
    if (!c.expected_check_out_at) return false
    return new Date(c.expected_check_out_at) < now
  }).length
})

const filteredCheckIns = computed(() => {
  let list = activeCheckIns.value

  if (searchQuery.value.trim()) {
    const query = searchQuery.value.toLowerCase().trim()
    list = list.filter((checkIn) => {
      const guestName =
        `${checkIn.guest?.first_name || ''} ${checkIn.guest?.last_name || ''}`.toLowerCase()
      const roomNumber = String(checkIn.room?.room_number || '').toLowerCase()
      const reservationNumber = (checkIn.reservation?.reservation_number || '').toLowerCase()
      const email = (checkIn.guest?.email || '').toLowerCase()
      const phone = String(checkIn.guest?.phone || '').toLowerCase()

      return (
        guestName.includes(query) ||
        roomNumber.includes(query) ||
        reservationNumber.includes(query) ||
        email.includes(query) ||
        phone.includes(query)
      )
    })
  }

  if (filterRoom.value.trim()) {
    const rQuery = filterRoom.value.toLowerCase().trim()
    list = list.filter((c) =>
      String(c.room?.room_number || '')
        .toLowerCase()
        .includes(rQuery),
    )
  }

  if (filterDeparture.value === 'due_today') {
    const todayStr = new Date().toISOString().slice(0, 10)
    list = list.filter((c) => (c.expected_check_out_at || '').startsWith(todayStr))
  } else if (filterDeparture.value === 'overdue') {
    const now = new Date()
    list = list.filter((c) => c.expected_check_out_at && new Date(c.expected_check_out_at) < now)
  }

  return list
})

const totalRecords = computed(() => filteredCheckIns.value.length)
const lastPage = computed(() => Math.ceil(totalRecords.value / perPage.value) || 1)

const paginatedCheckIns = computed(() => {
  const start = (currentPage.value - 1) * perPage.value
  const end = start + perPage.value
  return filteredCheckIns.value.slice(start, end)
})

const showingFrom = computed(() => {
  if (totalRecords.value === 0) return 0
  return (currentPage.value - 1) * perPage.value + 1
})

const showingTo = computed(() => {
  return Math.min(currentPage.value * perPage.value, totalRecords.value)
})

const paginationPages = computed(() => {
  const pages: number[] = []
  const max = lastPage.value
  const cur = currentPage.value

  for (let i = Math.max(1, cur - 2); i <= Math.min(max, cur + 2); i++) {
    pages.push(i)
  }
  return pages
})

watch([searchQuery, filterRoom, filterDeparture], () => {
  currentPage.value = 1
})

const loadCheckIns = async () => {
  loading.value = true
  try {
    const response = await checkInService.getAll({
      page: 1,
      per_page: 100,
      status: 'active',
    })

    if (response.data) {
      if (response.data.data) {
        checkIns.value = response.data.data
        totalCheckInsCount.value = response.data.total || response.data.data.length
      } else if (Array.isArray(response.data)) {
        checkIns.value = response.data
        totalCheckInsCount.value = response.data.length
      }
    }
  } catch (error: any) {
    console.error('Failed to load check-ins:', error)
    showToast('Failed to load check-ins')
  } finally {
    loading.value = false
  }
}

const handleRefresh = async () => {
  await loadCheckIns()
  showToast('Guest departures refreshed')
}

const handleResetFilters = () => {
  searchQuery.value = ''
  filterRoom.value = ''
  filterDeparture.value = 'all'
  currentPage.value = 1
  loadCheckIns()
}

const toggleFilter = () => {
  isFilterOpen.value = !isFilterOpen.value
}

const toggleFullscreen = () => {
  isFullscreen.value = !isFullscreen.value
}

const changePerPage = (event: Event) => {
  const target = event.target as HTMLSelectElement
  perPage.value = Number(target.value)
  currentPage.value = 1
}

const goToPage = (page: number) => {
  if (page >= 1 && page <= lastPage.value) {
    currentPage.value = page
  }
}

const prevPage = () => {
  if (currentPage.value > 1) {
    currentPage.value--
  }
}

const nextPage = () => {
  if (currentPage.value < lastPage.value) {
    currentPage.value++
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
    await loadCheckIns()
    closeCheckOutModal()
    showToast('Guest checked out successfully!')
  } catch (error: any) {
    console.error('Check-out failed:', error)
    const msg = error.response?.data?.message || error.message || 'Check-out failed'
    showToast(`Check-out failed: ${msg}`)
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
    return `${diffHours} hr${diffHours !== 1 ? 's' : ''}`
  } else if (diffDays === 1) {
    return `1 day, ${diffHours} hr`
  } else {
    return `${diffDays} days`
  }
}

const formatDate = (date?: string) => {
  if (!date) return '—'
  try {
    const d = new Date(date)
    return d.toLocaleString('en-US', {
      month: 'short',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
    })
  } catch (error) {
    console.error('[CheckOut] Error formatting date:', error)
    return date
  }
}

onMounted(() => {
  loadCheckIns()
})

watch(
  () => hotelStore.hotelId,
  () => {
    loadCheckIns()
  },
)
</script>

<template>
  <DashboardLayout>
    <div
      class="space-y-6 bg-slate-50 dark:bg-slate-950 min-h-screen p-4 sm:p-6 max-w-full overflow-hidden font-sans"
      :class="{ 'fixed inset-0 z-50 p-6 overflow-y-auto bg-white dark:bg-slate-950': isFullscreen }"
    >
      <!-- Toast Notification -->
      <div
        v-if="toastMessage"
        class="fixed bottom-6 right-6 z-50 bg-slate-900 dark:bg-white text-white dark:text-slate-900 px-5 py-3 rounded-2xl shadow-2xl flex items-center gap-3 border border-slate-800 text-xs font-bold"
      >
        <CheckCircle2 class="w-4 h-4 text-emerald-500" />
        <span>{{ toastMessage }}</span>
      </div>

      <!-- Header Section -->
      <div
        class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-5 sm:p-6 rounded-2xl shadow-xs"
      >
        <div class="flex items-center gap-3">
          <div
            class="w-10 h-10 rounded-xl bg-gradient-to-br from-purple-600 to-rose-600 flex items-center justify-center shadow-md flex-shrink-0 text-white"
          >
            <LogOut class="w-5 h-5 stroke-[2.2]" />
          </div>
          <div>
            <div class="flex items-center gap-2">
              <h1 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white">
                {{ languageStore.t('guest_checkout_mgmt', 'Guest Check-Out Management') }}
              </h1>
              <span
                v-if="hotelStore.hotelName"
                class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300 border border-blue-200 dark:border-blue-700/50"
              >
                <Building2 class="w-3 h-3" />
                {{ hotelStore.hotelName }}
              </span>
              <span
                class="text-[10px] uppercase font-black bg-purple-500/10 text-purple-600 dark:text-purple-400 px-2 py-0.5 rounded-md border border-purple-500/20"
              >
                {{ userRoleName }}
              </span>
            </div>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
              {{
                languageStore.t(
                  'manage_guest_departures',
                  'Manage guest departures, room release, and real-time occupancy records.',
                )
              }}
            </p>
          </div>
        </div>
      </div>

      <!-- Statistics Cards -->
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <!-- Active Guests In-House -->
        <div
          class="bg-white dark:bg-slate-900 rounded-2xl p-5 shadow-xs border border-slate-200 dark:border-slate-800 flex items-center justify-between"
        >
          <div>
            <p
              class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider"
            >
              {{ languageStore.t('active_in_house_guests', 'Active In-House Guests') }}
            </p>
            <h3 class="text-2xl font-black text-slate-900 dark:text-white mt-1">
              {{ activeCheckIns.length }}
            </h3>
          </div>
          <div class="p-3 rounded-2xl bg-blue-500/10 text-blue-600 dark:text-blue-400">
            <UserCheck class="w-5 h-5" />
          </div>
        </div>

        <!-- Departures Due Today -->
        <div
          class="bg-white dark:bg-slate-900 rounded-2xl p-5 shadow-xs border border-slate-200 dark:border-slate-800 flex items-center justify-between"
        >
          <div>
            <p
              class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider"
            >
              {{ languageStore.t('due_today', 'Due Today') }}
            </p>
            <h3 class="text-2xl font-black text-amber-600 dark:text-amber-400 mt-1">
              {{ dueTodayCount }}
            </h3>
          </div>
          <div class="p-3 rounded-2xl bg-amber-500/10 text-amber-600 dark:text-amber-400">
            <Clock class="w-5 h-5" />
          </div>
        </div>

        <!-- Overdue Departures -->
        <div
          class="bg-white dark:bg-slate-900 rounded-2xl p-5 shadow-xs border border-slate-200 dark:border-slate-800 flex items-center justify-between"
        >
          <div>
            <p
              class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider"
            >
              {{ languageStore.t('overdue_checkouts', 'Overdue Checkouts') }}
            </p>
            <h3 class="text-2xl font-black text-rose-600 dark:text-rose-400 mt-1">
              {{ overdueCount }}
            </h3>
          </div>
          <div class="p-3 rounded-2xl bg-rose-500/10 text-rose-600 dark:text-rose-400">
            <AlertCircle class="w-5 h-5" />
          </div>
        </div>
      </div>

      <!-- Top Bar Toolbar -->
      <div
        class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-slate-200 dark:border-slate-800/80 bg-white dark:bg-[#0b1527] p-3 sm:p-4 shadow-xs transition-all"
      >
        <!-- Left: Search & Filter Toggle -->
        <div class="flex flex-1 items-center gap-2.5 min-w-[280px] max-w-2xl">
          <!-- Search Input -->
          <div class="relative flex-1">
            <Search
              class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 dark:text-slate-500"
            />
            <input
              v-model="searchQuery"
              type="text"
              :placeholder="
                languageStore.t(
                  'search_checkout_ph',
                  'Search active guests by name, room #, booking reference, phone...',
                )
              "
              class="w-full rounded-xl border border-slate-200 dark:border-[#1e3455] bg-slate-50/80 dark:bg-[#13233c] text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 pl-10 pr-4 py-2.5 text-xs sm:text-sm focus:ring-2 focus:ring-purple-500/40 focus:border-purple-500 dark:focus:border-purple-400 transition outline-none"
            />
          </div>

          <!-- Filter Toggle Button -->
          <button
            type="button"
            @click="toggleFilter"
            class="inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-xs sm:text-sm font-semibold transition cursor-pointer flex-shrink-0"
            :class="[
              isFilterOpen
                ? 'bg-purple-600/10 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400 border border-purple-500/40'
                : 'border border-slate-200 dark:border-[#1e3455] bg-white dark:bg-[#13233c]/80 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-[#1c3356]',
            ]"
          >
            <component :is="isFilterOpen ? X : Filter" class="w-4 h-4" />
            <span>{{
              isFilterOpen
                ? languageStore.t('hide_filter', 'Hide Filter')
                : languageStore.t('filter', 'Filter')
            }}</span>
          </button>
        </div>

        <!-- Right: Action Buttons -->
        <div class="flex items-center gap-2 sm:gap-2.5">
          <!-- Refresh Button -->
          <button
            type="button"
            @click="handleRefresh"
            :disabled="loading"
            :title="languageStore.t('refresh', 'Refresh')"
            class="inline-flex items-center justify-center h-9 w-9 sm:h-10 sm:w-10 rounded-xl border border-slate-200 dark:border-[#1e3455] bg-white dark:bg-[#13233c]/80 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-[#1c3356] hover:text-slate-900 dark:hover:text-white transition disabled:opacity-50 cursor-pointer"
          >
            <RefreshCw class="w-4 h-4" :class="{ 'animate-spin': loading }" />
          </button>

          <!-- Fullscreen Toggle -->
          <button
            type="button"
            @click="toggleFullscreen"
            :title="languageStore.t('fullscreen', 'Toggle Fullscreen')"
            class="inline-flex items-center justify-center h-9 w-9 sm:h-10 sm:w-10 rounded-xl border border-slate-200 dark:border-[#1e3455] bg-white dark:bg-[#13233c]/80 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-[#1c3356] hover:text-slate-900 dark:hover:text-white transition cursor-pointer"
          >
            <component :is="isFullscreen ? Minimize2 : Maximize2" class="w-4 h-4" />
          </button>
        </div>
      </div>

      <!-- Expandable Filter Panel -->
      <Transition
        enter-active-class="transition duration-200 ease-out"
        enter-from-class="transform -translate-y-2 opacity-0 scale-98"
        enter-to-class="transform translate-y-0 opacity-100 scale-100"
        leave-active-class="transition duration-150 ease-in"
        leave-from-class="transform translate-y-0 opacity-100 scale-100"
        leave-to-class="transform -translate-y-2 opacity-0 scale-98"
      >
        <div
          v-if="isFilterOpen"
          class="rounded-2xl border border-slate-200 dark:border-slate-800/80 bg-white dark:bg-[#0b1527] p-4 sm:p-5 shadow-sm space-y-4"
        >
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-3.5 sm:gap-4">
            <!-- Departure Schedule -->
            <div>
              <label class="mb-1.5 block text-xs font-semibold text-slate-600 dark:text-slate-300">
                {{ languageStore.t('occupancy_status', 'Departure Schedule') }}
              </label>
              <select
                v-model="filterDeparture"
                class="w-full rounded-xl border border-slate-200 dark:border-[#1e3455] bg-slate-50/80 dark:bg-[#13233c] text-slate-900 dark:text-white px-3.5 py-2 text-xs sm:text-sm focus:ring-2 focus:ring-purple-500/40 focus:border-purple-500 dark:focus:border-purple-400 transition cursor-pointer font-medium outline-none"
              >
                <option value="all">
                  {{ languageStore.t('all_records', 'All In-House Guests') }}
                </option>
                <option value="due_today">{{ languageStore.t('due_today', 'Due Today') }}</option>
                <option value="overdue">
                  {{ languageStore.t('overdue_checkouts', 'Overdue Departures') }}
                </option>
              </select>
            </div>

            <!-- Room Filter -->
            <div>
              <label class="mb-1.5 block text-xs font-semibold text-slate-600 dark:text-slate-300">
                {{ languageStore.t('filter_by_room', 'Filter by Room #') }}
              </label>
              <input
                v-model="filterRoom"
                type="text"
                placeholder="e.g. 101, 204..."
                class="w-full rounded-xl border border-slate-200 dark:border-[#1e3455] bg-slate-50/80 dark:bg-[#13233c] text-slate-900 dark:text-white px-3.5 py-2 text-xs sm:text-sm focus:ring-2 focus:ring-purple-500/40 focus:border-purple-500 dark:focus:border-purple-400 transition outline-none"
              />
            </div>

            <!-- Reset Button -->
            <div class="flex items-end">
              <button
                type="button"
                @click="handleResetFilters"
                class="w-full inline-flex items-center justify-center gap-1.5 rounded-xl border border-slate-200 dark:border-[#1e3455] bg-slate-100/70 dark:bg-[#13233c] px-3.5 py-2 text-xs font-semibold text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-[#1c3356] transition cursor-pointer h-[38px]"
              >
                <RotateCcw class="w-3.5 h-3.5" />
                <span>{{ languageStore.t('reset_filters', 'Reset Filters') }}</span>
              </button>
            </div>
          </div>
        </div>
      </Transition>

      <!-- Check-Out Table Container -->
      <div
        class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-xs overflow-hidden font-sans w-full"
      >
        <!-- Desktop Table View -->
        <div class="hidden md:block overflow-x-auto w-full">
          <table class="w-full text-left border-collapse">
            <thead
              class="bg-slate-50/90 dark:bg-[#0c182c] border-b border-slate-200 dark:border-[#1e3455]"
            >
              <tr class="text-[11px] font-bold text-slate-500 dark:text-slate-400 select-none">
                <th class="py-3 px-4 pl-5 whitespace-nowrap">
                  {{ languageStore.t('Guest', 'Guest') }}
                </th>
                <th class="py-3 px-4 whitespace-nowrap">
                  {{ languageStore.t('Room & Type', 'Room & Type') }}
                </th>
                <th class="py-3 px-4 whitespace-nowrap">
                  {{ languageStore.t('Reservation #', 'Booking Ref') }}
                </th>
                <th class="py-3 px-4 whitespace-nowrap">
                  {{ languageStore.t('Checked In', 'Check-In Time') }}
                </th>
                <th class="py-3 px-4 whitespace-nowrap">
                  {{ languageStore.t('Stay Duration', 'Stay Duration') }}
                </th>
                <th class="py-3 px-4 whitespace-nowrap">
                  {{ languageStore.t('Expected Departure', 'Expected Departure') }}
                </th>
                <th class="py-3 px-4 text-right pr-5 whitespace-nowrap">
                  {{ languageStore.t('Actions', 'Action') }}
                </th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-[#1e3455]/60 text-xs">
              <!-- Loading Spinner State -->
              <tr v-if="loading">
                <td colspan="7" class="px-6 py-20 text-center">
                  <div class="flex flex-col items-center justify-center gap-3">
                    <Loader2 class="w-8 h-8 text-rose-600 dark:text-rose-400 animate-spin" />
                    <span class="text-xs font-bold text-slate-600 dark:text-slate-400">{{
                      languageStore.t('loading_checkout_records', 'Loading checkout records...')
                    }}</span>
                  </div>
                </td>
              </tr>

              <!-- Data Rows -->
              <template v-else>
                <tr
                  v-for="checkIn in paginatedCheckIns"
                  :key="checkIn.id"
                  class="hover:bg-slate-50/80 dark:hover:bg-[#13233c]/60 transition-colors duration-150 group"
                >
                  <!-- Guest -->
                  <td class="py-3 px-4 pl-5 whitespace-nowrap">
                    <div class="flex items-center gap-2.5">
                      <div
                        class="w-8 h-8 rounded-xl bg-purple-500/10 text-purple-600 dark:text-purple-400 font-black flex items-center justify-center text-xs border border-purple-500/20 flex-shrink-0"
                      >
                        {{ (checkIn.guest?.first_name || 'G').charAt(0).toUpperCase() }}
                      </div>
                      <div>
                        <div class="font-bold text-slate-900 dark:text-white">
                          {{ checkIn.guest?.first_name }} {{ checkIn.guest?.last_name }}
                        </div>
                        <div class="text-[10px] text-slate-400 font-medium">
                          {{ checkIn.guest?.phone || checkIn.guest?.email || 'No contact' }}
                        </div>
                      </div>
                    </div>
                  </td>

                  <!-- Room & Type -->
                  <td class="py-3 px-4 whitespace-nowrap">
                    <div class="flex items-center gap-2">
                      <span
                        class="font-black text-slate-900 dark:text-white font-mono text-xs sm:text-sm"
                      >
                        Room {{ checkIn.room?.room_number }}
                      </span>
                      <span
                        v-if="checkIn.room?.room_type?.name"
                        class="text-[10px] text-slate-400 font-medium"
                      >
                        ({{ checkIn.room.room_type.name }})
                      </span>
                    </div>
                  </td>

                  <!-- Booking Ref -->
                  <td class="py-3 px-4 whitespace-nowrap">
                    <span
                      class="font-mono text-xs font-semibold text-purple-600 dark:text-purple-400"
                    >
                      {{ checkIn.reservation?.reservation_number || `#${checkIn.id.slice(-6)}` }}
                    </span>
                  </td>

                  <!-- Check-In Time -->
                  <td class="py-3 px-4 whitespace-nowrap">
                    <div class="font-medium text-slate-800 dark:text-slate-200">
                      {{ formatDate(checkIn.checked_in_at) }}
                    </div>
                  </td>

                  <!-- Stay Duration -->
                  <td class="py-3 px-4 whitespace-nowrap">
                    <span
                      class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-500/10 text-blue-600 dark:text-blue-400 border border-blue-500/20"
                    >
                      {{ calculateStayDuration(checkIn.checked_in_at) }}
                    </span>
                  </td>

                  <!-- Expected Departure -->
                  <td class="py-3 px-4 whitespace-nowrap">
                    <div class="font-medium text-slate-800 dark:text-slate-200">
                      {{ formatDate(checkIn.expected_check_out_at) }}
                    </div>
                  </td>

                  <!-- Action -->
                  <td class="py-3 px-4 text-right pr-5 whitespace-nowrap">
                    <button
                      @click="openCheckOutModal(checkIn)"
                      class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-xs font-bold transition shadow-xs cursor-pointer"
                    >
                      <LogOut class="w-3.5 h-3.5" />
                      <span>{{ languageStore.t('Check Out', 'Check Out') }}</span>
                    </button>
                  </td>
                </tr>

                <!-- Empty State -->
                <tr v-if="paginatedCheckIns.length === 0">
                  <td
                    colspan="7"
                    class="px-6 py-12 text-center text-slate-500 dark:text-slate-400 text-xs font-bold"
                  >
                    {{
                      languageStore.t(
                        'no_active_guests',
                        'No active in-house guests found matching your criteria.',
                      )
                    }}
                  </td>
                </tr>
              </template>
            </tbody>
          </table>
        </div>

        <!-- Mobile View -->
        <div class="md:hidden divide-y divide-slate-100 dark:divide-slate-800">
          <div
            v-if="loading"
            class="py-16 text-center flex flex-col items-center justify-center gap-3"
          >
            <Loader2 class="w-8 h-8 text-rose-600 dark:text-rose-400 animate-spin" />
            <span class="text-xs font-bold text-slate-600 dark:text-slate-400">{{
              languageStore.t('loading_checkout_records', 'Loading checkout records...')
            }}</span>
          </div>
          <template v-else>
            <div
              v-for="checkIn in paginatedCheckIns"
              :key="checkIn.id"
              class="p-4 space-y-3 hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition"
            >
              <div class="flex items-center justify-between">
                <span class="font-bold text-slate-900 dark:text-white text-sm">
                  {{ checkIn.guest?.first_name }} {{ checkIn.guest?.last_name }}
                </span>
                <span
                  class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-500/10 text-blue-600 border border-blue-500/20"
                >
                  {{ calculateStayDuration(checkIn.checked_in_at) }}
                </span>
              </div>
              <div
                class="flex items-center justify-between text-xs text-slate-600 dark:text-slate-400"
              >
                <span>Room {{ checkIn.room?.room_number }}</span>
                <span>Exp: {{ formatDate(checkIn.expected_check_out_at) }}</span>
              </div>
              <div
                class="flex items-center justify-end pt-1 border-t border-slate-100 dark:border-slate-800 text-xs font-bold"
              >
                <button
                  @click="openCheckOutModal(checkIn)"
                  class="px-3 py-1.5 bg-rose-600 text-white rounded-xl font-bold text-xs"
                >
                  {{ languageStore.t('Check Out', 'Check Out') }}
                </button>
              </div>
            </div>
          </template>
        </div>

        <!-- Pagination Footer -->
        <div
          v-if="filteredCheckIns.length > 0"
          class="border-t border-slate-200 dark:border-slate-800/80 px-4 sm:px-6 py-3 sm:py-4 bg-slate-50/50 dark:bg-[#0c182c] flex flex-col sm:flex-row items-center justify-between gap-3 text-xs"
        >
          <div class="text-slate-500 dark:text-slate-400 font-medium">
            {{ languageStore.t('Showing', 'Showing') }}
            <span class="font-bold text-slate-900 dark:text-white">{{ showingFrom }}</span>
            {{ languageStore.t('to', 'to') }}
            <span class="font-bold text-slate-900 dark:text-white">{{ showingTo }}</span>
            {{ languageStore.t('of', 'of') }}
            <span class="font-bold text-slate-900 dark:text-white">{{ totalRecords }}</span>
            {{ languageStore.t('active_in_house_guests', 'active guests') }}
          </div>

          <div class="flex items-center gap-2 sm:gap-3">
            <div class="flex items-center gap-1.5">
              <span class="text-slate-500 dark:text-slate-400 font-medium">{{
                languageStore.t('Per page:', 'Per page:')
              }}</span>
              <select
                :value="perPage"
                @change="changePerPage"
                class="rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-[#13233c] text-slate-900 dark:text-white px-2 py-1 text-xs outline-none"
              >
                <option :value="5">5</option>
                <option :value="10">10</option>
                <option :value="20">20</option>
                <option :value="50">50</option>
              </select>
            </div>

            <div class="flex items-center gap-1">
              <button
                @click="prevPage"
                :disabled="currentPage === 1"
                class="p-1 rounded-lg border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 disabled:opacity-40 transition cursor-pointer"
              >
                <ChevronLeft class="w-4 h-4" />
              </button>

              <button
                v-for="page in paginationPages"
                :key="page"
                @click="goToPage(page)"
                class="px-2.5 py-1 rounded-lg text-xs font-bold transition cursor-pointer"
                :class="[
                  currentPage === page
                    ? 'bg-purple-600 text-white'
                    : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800',
                ]"
              >
                {{ page }}
              </button>

              <button
                @click="nextPage"
                :disabled="currentPage === lastPage"
                class="p-1 rounded-lg border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 disabled:opacity-40 transition cursor-pointer"
              >
                <ChevronRight class="w-4 h-4" />
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Check Out Modal -->
      <div
        v-if="showCheckOutModal && selectedCheckIn"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-xs p-4"
      >
        <div
          class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl max-w-md w-full p-6 shadow-2xl space-y-4"
        >
          <div class="flex items-center gap-3">
            <div
              class="w-10 h-10 rounded-xl bg-rose-500/10 text-rose-600 flex items-center justify-center flex-shrink-0"
            >
              <LogOut class="w-5 h-5" />
            </div>
            <div>
              <h3 class="text-base font-bold text-slate-900 dark:text-white">
                {{ languageStore.t('confirm_guest_departure', 'Confirm Guest Departure') }}
              </h3>
              <p class="text-xs text-slate-500">
                {{ languageStore.t('room_release_desc', 'Room release & checkout completion') }}
              </p>
            </div>
          </div>

          <p
            class="text-xs text-slate-600 dark:text-slate-300 leading-relaxed bg-slate-50 dark:bg-slate-950 p-3.5 rounded-xl border border-slate-200 dark:border-slate-800"
          >
            Check out
            <strong
              >{{ selectedCheckIn.guest?.first_name }}
              {{ selectedCheckIn.guest?.last_name }}</strong
            >
            from <strong>Room {{ selectedCheckIn.room?.room_number }}</strong
            >? This will release the room for cleaning and update status.
          </p>

          <div class="flex justify-end gap-2.5 pt-2">
            <button
              @click="closeCheckOutModal"
              class="px-4 py-2 text-xs font-bold text-slate-700 dark:text-slate-300 bg-slate-100 dark:bg-slate-800 rounded-xl hover:bg-slate-200 dark:hover:bg-slate-700 transition cursor-pointer"
            >
              {{ languageStore.t('Cancel', 'Cancel') }}
            </button>
            <button
              @click="confirmCheckOut"
              :disabled="processingCheckOut"
              class="px-5 py-2 text-xs font-bold text-white bg-rose-600 hover:bg-rose-700 rounded-xl transition shadow-sm shadow-rose-600/20 disabled:opacity-50 cursor-pointer flex items-center gap-1.5"
            >
              <LogOut class="w-3.5 h-3.5" />
              <span>{{
                processingCheckOut
                  ? languageStore.t('checking_out', 'Checking out...')
                  : languageStore.t('confirm_check_out', 'Confirm Check-Out')
              }}</span>
            </button>
          </div>
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>
