<script setup lang="ts">
import { ref, onMounted, computed, watch } from 'vue'
import { useRouter } from 'vue-router'
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import CheckInDialog from '@/components/checkin/CheckinDialog.vue'
import {
  LogIn,
  LogOut,
  Users,
  Search,
  Filter,
  X,
  RefreshCw,
  Maximize2,
  Minimize2,
  RotateCcw,
  Plus,
  Eye,
  Trash2,
  CheckCircle2,
  BedDouble,
  Clock,
  Calendar,
  UserCheck,
  ChevronLeft,
  ChevronRight,
  AlertCircle,
  Building,
  Building2,
  Loader2,
} from 'lucide-vue-next'

import { useCheckInStore } from '@/stores/checkInStore'
import { useReservationStore } from '@/stores/reservationStore'
import { useAuthStore } from '@/stores/auth'
import { useHotelStore } from '@/stores/hotelStore'
import { useLanguageStore } from '@/stores/language'
import type { CheckIn, Guest } from '@/types/checkIn'

const router = useRouter()
const store = useCheckInStore()
const reservationStore = useReservationStore()
const authStore = useAuthStore()
const hotelStore = useHotelStore()
const languageStore = useLanguageStore()

const isFilterOpen = ref(false)
const isFullscreen = ref(false)
const showCheckInDialog = ref(false)
const selectedCheckIn = ref<CheckIn | null>(null)
const toastMessage = ref<string | null>(null)

const searchQuery = ref('')
const filterStatus = ref<'all' | 'active' | 'checked_out'>('all')
const filterRoom = ref('')

const currentPage = ref(1)
const perPage = ref(10)

const userRoleName = computed(() => {
  const role = String(authStore.user?.role || 'Staff').toLowerCase()
  return role.charAt(0).toUpperCase() + role.slice(1)
})

const availableReservations = computed(() => {
  return reservationStore.reservations.filter((r: any) => r.status === 'confirmed')
})

const totalCheckIns = computed(() => store.statistics.total_check_ins || store.checkIns.length || 0)
const activeGuestCount = computed(
  () =>
    store.statistics.active_guests || store.checkIns.filter((c: any) => !c.checked_out_at).length,
)
const checkedOutCount = computed(() => {
  const total = store.statistics.total_check_ins || store.checkIns.length
  const active = activeGuestCount.value
  return Math.max(0, total - active)
})

const showToast = (msg: string) => {
  toastMessage.value = msg
  setTimeout(() => {
    toastMessage.value = null
  }, 3000)
}

const loadCheckIns = async () => {
  try {
    await store.fetchCheckIns({
      page: currentPage.value,
      per_page: perPage.value,
      search: searchQuery.value.trim() || undefined,
    })
    await store.fetchStatistics()
  } catch (error) {
    console.error('Error loading check-ins:', error)
    showToast('Failed to load check-ins')
  }
}

const loadReservations = async () => {
  try {
    await reservationStore.fetchReservations()
  } catch (error) {
    console.error('Error loading reservations:', error)
  }
}

const filteredCheckIns = computed(() => {
  let list = store.checkIns || []

  if (searchQuery.value.trim()) {
    const q = searchQuery.value.toLowerCase().trim()
    list = list.filter((c: any) => {
      const guestName = `${c.guest?.first_name || ''} ${c.guest?.last_name || ''}`.toLowerCase()
      const roomNumber = String(c.room?.room_number || '').toLowerCase()
      const resNumber = (c.reservation?.reservation_number || '').toLowerCase()
      const email = (c.guest?.email || '').toLowerCase()
      const phone = String(c.guest?.phone || '').toLowerCase()
      return (
        guestName.includes(q) ||
        roomNumber.includes(q) ||
        resNumber.includes(q) ||
        email.includes(q) ||
        phone.includes(q)
      )
    })
  }

  if (filterStatus.value === 'active') {
    list = list.filter((c: any) => !c.checked_out_at)
  } else if (filterStatus.value === 'checked_out') {
    list = list.filter((c: any) => !!c.checked_out_at)
  }

  if (filterRoom.value.trim()) {
    const rQuery = filterRoom.value.toLowerCase().trim()
    list = list.filter((c: any) =>
      String(c.room?.room_number || '')
        .toLowerCase()
        .includes(rQuery),
    )
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

watch([searchQuery, filterStatus, filterRoom], () => {
  currentPage.value = 1
})

const handleRefresh = async () => {
  await loadCheckIns()
  showToast('Check-ins refreshed')
}

const handleResetFilters = () => {
  searchQuery.value = ''
  filterStatus.value = 'all'
  filterRoom.value = ''
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

const openNewCheckInDialog = () => {
  showCheckInDialog.value = true
}

const handleCheckInSuccess = async () => {
  showToast('Guest checked in successfully!')
  showCheckInDialog.value = false
  await loadCheckIns()
  await loadReservations()
}

const getGuestName = (guest?: Guest) => {
  if (!guest) return 'Guest'
  if (guest.full_name) return guest.full_name
  const name = `${guest.first_name || ''} ${guest.last_name || ''}`.trim()
  return name || 'Guest'
}

const getGuestInitials = (guest?: Guest) => {
  const name = getGuestName(guest)
  return (name.charAt(0) || 'G').toUpperCase()
}

const handleCheckout = async (item: CheckIn) => {
  const guestName = getGuestName(item.guest)
  const confirmed = window.confirm(`Check out ${guestName}?`)
  if (!confirmed) return

  try {
    await store.checkOutGuest(item.id)
    showToast('Guest checked out successfully!')
    await loadCheckIns()
  } catch (error: any) {
    console.error('[CheckIn] Error checking out guest:', error)
    showToast(error.message || 'Failed to check out guest')
  }
}

const handleDelete = async (item: CheckIn) => {
  const confirmed = window.confirm('Are you sure you want to delete this check-in record?')
  if (!confirmed) return

  try {
    await store.deleteCheckIn(item.id)
    showToast('Check-in record deleted successfully')
    await loadCheckIns()
  } catch (error: any) {
    console.error('[CheckIn] Error deleting check-in record:', error)
    showToast('Failed to delete check-in record')
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
    console.error('[CheckIn] Error formatting date:', error)
    return date
  }
}

onMounted(async () => {
  await Promise.all([loadCheckIns(), loadReservations()])
})

watch(
  () => hotelStore.hotelId,
  async () => {
    await Promise.all([loadCheckIns(), loadReservations()])
  },
)
</script>

<template>
  <DashboardLayout>
    <div
      class="space-y-6 bg-slate-50 dark:bg-slate-950 min-h-screen p-4 sm:p-6 max-w-full overflow-hidden font-sans"
      :class="{ 'fixed inset-0 z-50 p-6 overflow-y-auto bg-white dark:bg-slate-950': isFullscreen }"
    >
      <!-- Check-In Dialog -->
      <CheckInDialog
        v-model="showCheckInDialog"
        :reservations="availableReservations"
        @success="handleCheckInSuccess"
      />

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
            class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-600 to-indigo-600 flex items-center justify-center shadow-md flex-shrink-0 text-white"
          >
            <LogIn class="w-5 h-5 stroke-[2.2]" />
          </div>
          <div>
            <div class="flex items-center gap-2">
              <h1 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white">
                {{ languageStore.t('guest_checkin_mgmt', 'Guest Check-In Management') }}
              </h1>
              <span
                v-if="hotelStore.hotelName"
                class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300 border border-blue-200 dark:border-blue-700/50"
              >
                <Building2 class="w-3 h-3" />
                {{ hotelStore.hotelName }}
              </span>
              <span
                class="text-[10px] uppercase font-black bg-blue-500/10 text-blue-600 dark:text-blue-400 px-2 py-0.5 rounded-md border border-blue-500/20"
              >
                {{ userRoleName }}
              </span>
            </div>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
              {{
                languageStore.t(
                  'track_guest_arrivals',
                  'Track guest arrivals, manage check-ins, and monitor room occupancy.',
                )
              }}
            </p>
          </div>
        </div>
      </div>

      <!-- Statistics Cards -->
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <!-- Total Check-Ins -->
        <div
          class="bg-white dark:bg-slate-900 rounded-2xl p-5 shadow-xs border border-slate-200 dark:border-slate-800 flex items-center justify-between"
        >
          <div>
            <p
              class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider"
            >
              {{ languageStore.t('total_checkins', 'Total Check-Ins') }}
            </p>
            <h3 class="text-2xl font-black text-slate-900 dark:text-white mt-1">
              {{ totalCheckIns }}
            </h3>
          </div>
          <div class="p-3 rounded-2xl bg-blue-500/10 text-blue-600 dark:text-blue-400">
            <LogIn class="w-5 h-5" />
          </div>
        </div>

        <!-- Active In-House Guests -->
        <div
          class="bg-white dark:bg-slate-900 rounded-2xl p-5 shadow-xs border border-slate-200 dark:border-slate-800 flex items-center justify-between"
        >
          <div>
            <p
              class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider"
            >
              {{ languageStore.t('in_house_guests', 'In-House Guests') }}
            </p>
            <h3 class="text-2xl font-black text-emerald-600 dark:text-emerald-400 mt-1">
              {{ activeGuestCount }}
            </h3>
          </div>
          <div class="p-3 rounded-2xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400">
            <UserCheck class="w-5 h-5" />
          </div>
        </div>

        <!-- Checked Out -->
        <div
          class="bg-white dark:bg-slate-900 rounded-2xl p-5 shadow-xs border border-slate-200 dark:border-slate-800 flex items-center justify-between"
        >
          <div>
            <p
              class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider"
            >
              {{ languageStore.t('checked_out', 'Checked Out') }}
            </p>
            <h3 class="text-2xl font-black text-purple-600 dark:text-purple-400 mt-1">
              {{ checkedOutCount }}
            </h3>
          </div>
          <div class="p-3 rounded-2xl bg-purple-500/10 text-purple-600 dark:text-purple-400">
            <LogOut class="w-5 h-5" />
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
                  'search_checkin_ph',
                  'Search by guest name, room #, reservation, email, phone...',
                )
              "
              class="w-full rounded-xl border border-slate-200 dark:border-[#1e3455] bg-slate-50/80 dark:bg-[#13233c] text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 pl-10 pr-4 py-2.5 text-xs sm:text-sm focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 dark:focus:border-blue-400 transition outline-none"
            />
          </div>

          <!-- Filter Toggle Button -->
          <button
            type="button"
            @click="toggleFilter"
            class="inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-xs sm:text-sm font-semibold transition cursor-pointer flex-shrink-0"
            :class="[
              isFilterOpen
                ? 'bg-blue-600/10 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 border border-blue-500/40'
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
            :disabled="store.loading"
            :title="languageStore.t('refresh', 'Refresh')"
            class="inline-flex items-center justify-center h-9 w-9 sm:h-10 sm:w-10 rounded-xl border border-slate-200 dark:border-[#1e3455] bg-white dark:bg-[#13233c]/80 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-[#1c3356] hover:text-slate-900 dark:hover:text-white transition disabled:opacity-50 cursor-pointer"
          >
            <RefreshCw class="w-4 h-4" :class="{ 'animate-spin': store.loading }" />
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

          <!-- New Check-In Button -->
          <button
            type="button"
            @click="openNewCheckInDialog"
            class="inline-flex items-center gap-1.5 rounded-xl bg-blue-600 hover:bg-blue-700 dark:bg-[#0066FF] dark:hover:bg-[#0055DD] px-3.5 sm:px-4 py-2.5 text-xs sm:text-sm font-bold text-white shadow-sm shadow-blue-600/30 transition active:scale-98 cursor-pointer flex-shrink-0"
          >
            <Plus class="w-4 h-4" />
            <span>{{ languageStore.t('new_checkin', 'New Check-In') }}</span>
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
            <!-- Status Filter -->
            <div>
              <label class="mb-1.5 block text-xs font-semibold text-slate-600 dark:text-slate-300">
                {{ languageStore.t('occupancy_status', 'Occupancy Status') }}
              </label>
              <select
                v-model="filterStatus"
                class="w-full rounded-xl border border-slate-200 dark:border-[#1e3455] bg-slate-50/80 dark:bg-[#13233c] text-slate-900 dark:text-white px-3.5 py-2 text-xs sm:text-sm focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 dark:focus:border-blue-400 transition cursor-pointer font-medium outline-none"
              >
                <option value="all">{{ languageStore.t('all_records', 'All Records') }}</option>
                <option value="active">
                  {{ languageStore.t('active_in_house', 'Active In-House Only') }}
                </option>
                <option value="checked_out">
                  {{ languageStore.t('checked_out', 'Checked Out') }}
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
                class="w-full rounded-xl border border-slate-200 dark:border-[#1e3455] bg-slate-50/80 dark:bg-[#13233c] text-slate-900 dark:text-white px-3.5 py-2 text-xs sm:text-sm focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 dark:focus:border-blue-400 transition outline-none"
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

      <!-- Check-In Records Table Container -->
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
                  {{ languageStore.t('Reservation #', 'Reservation #') }}
                </th>
                <th class="py-3 px-4 whitespace-nowrap">
                  {{ languageStore.t('Checked In', 'Checked In') }}
                </th>
                <th class="py-3 px-4 whitespace-nowrap">
                  {{ languageStore.t('Expected Check Out', 'Expected Check Out') }}
                </th>
                <th class="py-3 px-4 text-center whitespace-nowrap">
                  {{ languageStore.t('Status', 'Status') }}
                </th>
                <th class="py-3 px-4 text-right pr-5 whitespace-nowrap">
                  {{ languageStore.t('Actions', 'Actions') }}
                </th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-[#1e3455]/60 text-xs">
              <!-- Loading Spinner State -->
              <tr v-if="store.loading">
                <td colspan="7" class="px-6 py-20 text-center">
                  <div class="flex flex-col items-center justify-center gap-3">
                    <Loader2 class="w-8 h-8 text-blue-600 dark:text-blue-400 animate-spin" />
                    <span class="text-xs font-bold text-slate-600 dark:text-slate-400">{{
                      languageStore.t('loading_checkin_records', 'Loading check-in records...')
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
                        class="w-8 h-8 rounded-xl bg-blue-500/10 text-blue-600 dark:text-blue-400 font-black flex items-center justify-center text-xs border border-blue-500/20 flex-shrink-0"
                      >
                        {{ getGuestInitials(checkIn.guest) }}
                      </div>
                      <div>
                        <div class="font-bold text-slate-900 dark:text-white">
                          {{ getGuestName(checkIn.guest) }}
                        </div>
                        <div class="text-[10px] text-slate-400 font-medium">
                          {{ checkIn.guest?.email || checkIn.guest?.phone || 'No contact' }}
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
                        Room {{ checkIn.room?.room_number || 'N/A' }}
                      </span>
                      <span
                        v-if="checkIn.room?.room_type?.name"
                        class="text-[10px] text-slate-400 font-medium"
                      >
                        ({{ checkIn.room.room_type.name }})
                      </span>
                    </div>
                  </td>

                  <!-- Reservation # -->
                  <td class="py-3 px-4 whitespace-nowrap">
                    <span
                      class="font-mono text-xs font-semibold text-slate-700 dark:text-slate-300"
                    >
                      {{ checkIn.reservation?.reservation_number || `#${checkIn.id.slice(-6)}` }}
                    </span>
                  </td>

                  <!-- Checked In -->
                  <td class="py-3 px-4 whitespace-nowrap">
                    <div class="font-medium text-slate-800 dark:text-slate-200">
                      {{ formatDate(checkIn.checked_in_at) }}
                    </div>
                  </td>

                  <!-- Expected Check Out -->
                  <td class="py-3 px-4 whitespace-nowrap">
                    <div class="font-medium text-slate-800 dark:text-slate-200">
                      {{ formatDate(checkIn.expected_check_out_at) }}
                    </div>
                  </td>

                  <!-- Status -->
                  <td class="py-3 px-4 text-center whitespace-nowrap">
                    <span
                      class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-extrabold border uppercase tracking-wider"
                      :class="
                        !checkIn.checked_out_at
                          ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/20'
                          : 'bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400 border-slate-200 dark:border-slate-700'
                      "
                    >
                      {{
                        !checkIn.checked_out_at
                          ? languageStore.t('In House', 'In House')
                          : languageStore.t('checked_out', 'Checked Out')
                      }}
                    </span>
                  </td>

                  <!-- Actions -->
                  <td class="py-3 px-4 text-right pr-5 whitespace-nowrap">
                    <div class="flex items-center justify-end gap-1">
                      <!-- Check-out button -->
                      <button
                        v-if="!checkIn.checked_out_at"
                        @click="handleCheckout(checkIn)"
                        class="p-1.5 text-purple-600 hover:bg-purple-50 dark:hover:bg-purple-950/40 rounded-lg transition cursor-pointer"
                        :title="languageStore.t('Check Out', 'Check Out Guest')"
                      >
                        <LogOut class="w-3.5 h-3.5" />
                      </button>

                      <!-- Delete button -->
                      <button
                        @click="handleDelete(checkIn)"
                        class="p-1.5 text-slate-500 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/40 rounded-lg transition cursor-pointer"
                        :title="languageStore.t('Delete', 'Delete Record')"
                      >
                        <Trash2 class="w-3.5 h-3.5" />
                      </button>
                    </div>
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
                        'no_checkin_records',
                        'No check-in records found matching your criteria.',
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
            v-if="store.loading"
            class="py-16 text-center flex flex-col items-center justify-center gap-3"
          >
            <Loader2 class="w-8 h-8 text-blue-600 dark:text-blue-400 animate-spin" />
            <span class="text-xs font-bold text-slate-600 dark:text-slate-400">{{
              languageStore.t('loading_checkin_records', 'Loading check-in records...')
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
                  {{ getGuestName(checkIn.guest) }}
                </span>
                <span
                  class="px-2 py-0.5 rounded-full text-[10px] font-bold border uppercase"
                  :class="
                    !checkIn.checked_out_at
                      ? 'bg-emerald-500/10 text-emerald-600'
                      : 'bg-slate-100 text-slate-500'
                  "
                >
                  {{
                    !checkIn.checked_out_at
                      ? languageStore.t('In House', 'In House')
                      : languageStore.t('checked_out', 'Checked Out')
                  }}
                </span>
              </div>
              <div
                class="flex items-center justify-between text-xs text-slate-600 dark:text-slate-400"
              >
                <span>Room {{ checkIn.room?.room_number || 'N/A' }}</span>
                <span>In: {{ formatDate(checkIn.checked_in_at) }}</span>
              </div>
              <div
                class="flex items-center justify-end gap-3 pt-1 border-t border-slate-100 dark:border-slate-800 text-xs font-bold"
              >
                <button
                  v-if="!checkIn.checked_out_at"
                  @click="handleCheckout(checkIn)"
                  class="text-purple-600"
                >
                  {{ languageStore.t('Check Out', 'Check Out') }}
                </button>
                <button @click="handleDelete(checkIn)" class="text-rose-600">
                  {{ languageStore.t('Delete', 'Delete') }}
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
            {{ languageStore.t('records', 'records') }}
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
                    ? 'bg-blue-600 text-white'
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
    </div>
  </DashboardLayout>
</template>
