<script setup lang="ts">
import { onMounted, ref, computed, watch } from 'vue'
import { useRouter } from 'vue-router'
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import DeleteReservationDialog from '@/components/reservation/DeleteReservationDialog.vue'
import {
  Calendar,
  BedDouble,
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
  Edit,
  Trash2,
  CheckCircle2,
  LogIn,
  LogOut,
  XCircle,
  ChevronLeft,
  ChevronRight,
  Clock,
  DollarSign,
  UserCheck,
  Building2,
  Loader2,
} from 'lucide-vue-next'

import { useReservationStore } from '@/stores/reservationStore'
import { useGuestStore } from '@/stores/guestStore'
import { useHotelStore } from '@/stores/hotelStore'
import { useLanguageStore } from '@/stores/language'
import roomService from '@/services/roomService'
import { roomTypeService } from '@/services/roomtypeService'
import type { Reservation, ReservationFilter as FilterType } from '@/types/reservation'

const router = useRouter()
const store = useReservationStore()
const guestStore = useGuestStore()
const hotelStore = useHotelStore()
const languageStore = useLanguageStore()

const isFilterOpen = ref(false)
const isFullscreen = ref(false)
const deleteDialog = ref(false)
const selectedReservation = ref<Reservation | null>(null)
const toastMessage = ref<string | null>(null)
const roomTypes = ref<any[]>([])
const rooms = ref<any[]>([])

const searchQuery = ref('')
const filterStatus = ref('')
const filterRoomTypeId = ref('')
const filterCheckInDate = ref('')
const filterCheckOutDate = ref('')

const currentPage = ref(1)
const perPage = ref(10)
let searchDebounceTimeout: any = null

const totalReservations = computed(() => {
  const total = store.pagination.total ?? store.reservations.length ?? 0
  return typeof total === 'number' ? total : Array.isArray(total) ? total[0] : 0
})

const lastPage = computed(() => store.pagination.last_page || Math.ceil(totalReservations.value / perPage.value) || 1)

const pendingCount = computed(() => store.reservations.filter((r) => r.status === 'pending').length)
const confirmedCount = computed(() => store.reservations.filter((r) => r.status === 'confirmed').length)
const checkedInCount = computed(() => store.reservations.filter((r) => r.status === 'checked_in').length)
const checkedOutCount = computed(() => store.reservations.filter((r) => r.status === 'checked_out').length)

const showToast = (msg: string) => {
  toastMessage.value = msg
  setTimeout(() => {
    toastMessage.value = null
  }, 3000)
}

const loadReservations = async () => {
  try {
    const params: FilterType = {
      page: currentPage.value,
      per_page: perPage.value,
    }
    if (searchQuery.value.trim()) params.search = searchQuery.value.trim()
    if (filterStatus.value) params.status = filterStatus.value as any
    if (filterRoomTypeId.value) {
      params.room_type_id = filterRoomTypeId.value
    }
    if (filterCheckInDate.value) params.check_in_date = filterCheckInDate.value
    if (filterCheckOutDate.value) params.check_out_date = filterCheckOutDate.value

    await store.fetchReservations(params)
  } catch (error) {
    console.error('Error loading reservations:', error)
    showToast('Failed to load reservations')
  }
}

const loadRoomTypesAndRooms = async () => {
  try {
    // 1. Fetch from room types endpoint
    const typeRes = await roomTypeService.getRoomTypes({ per_page: 100 })
    const typeData = typeRes.data?.data || (Array.isArray(typeRes.data) ? typeRes.data : [])
    
    // 2. Fetch from rooms endpoint
    const roomRes = await roomService.getAllRooms()
    const rawRooms = roomRes.data?.data || (Array.isArray(roomRes.data) ? roomRes.data : [])
    rooms.value = Array.isArray(rawRooms) ? rawRooms : []

    const typesMap = new Map<string, any>()
    
    // Add from room types service
    if (Array.isArray(typeData)) {
      typeData.forEach((t: any) => {
        if (t && t.name) {
          const key = String(t.name).trim().toLowerCase()
          typesMap.set(key, {
            id: t.id || t.name,
            name: t.name,
          })
        }
      })
    }

    // Add any types embedded in rooms
    rooms.value.forEach((r: any) => {
      const rt = r.room_type || r.roomType
      if (rt && rt.name) {
        const key = String(rt.name).trim().toLowerCase()
        if (!typesMap.has(key)) {
          typesMap.set(key, {
            id: rt.id || rt.name,
            name: rt.name,
          })
        }
      } else if (typeof rt === 'string' && rt.trim()) {
        const key = rt.trim().toLowerCase()
        if (!typesMap.has(key)) {
          typesMap.set(key, { id: rt, name: rt })
        }
      }
    })

    // Fallback if none found
    if (typesMap.size === 0) {
      ;['Standard', 'Deluxe', 'Suite', 'Executive Suite'].forEach((name) => {
        typesMap.set(name.toLowerCase(), { id: name, name })
      })
    }

    roomTypes.value = Array.from(typesMap.values())
  } catch (error) {
    console.error('Error loading room types:', error)
    roomTypes.value = [
      { id: 'Standard', name: 'Standard' },
      { id: 'Deluxe', name: 'Deluxe' },
      { id: 'Suite', name: 'Suite' },
      { id: 'Executive Suite', name: 'Executive Suite' },
    ]
  }
}

// Reservations directly from store (backend handles query filtering & pagination)
const paginatedReservations = computed(() => {
  return store.reservations || []
})

const showingFrom = computed(() => {
  if (totalReservations.value === 0 || paginatedReservations.value.length === 0) return 0
  if (store.pagination.from) return store.pagination.from
  return (currentPage.value - 1) * perPage.value + 1
})

const showingTo = computed(() => {
  if (store.pagination.to) return store.pagination.to
  return Math.min(currentPage.value * perPage.value, totalReservations.value)
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

// Debounced search watcher
watch(searchQuery, () => {
  if (searchDebounceTimeout) clearTimeout(searchDebounceTimeout)
  searchDebounceTimeout = setTimeout(() => {
    currentPage.value = 1
    loadReservations()
  }, 300)
})

// Immediate filter watchers
watch([filterStatus, filterRoomTypeId, filterCheckInDate, filterCheckOutDate], () => {
  currentPage.value = 1
  loadReservations()
})

const handleRefresh = async () => {
  await loadReservations()
  showToast('Reservations refreshed')
}

const handleResetFilters = () => {
  searchQuery.value = ''
  filterStatus.value = ''
  filterRoomTypeId.value = ''
  filterCheckInDate.value = ''
  filterCheckOutDate.value = ''
  currentPage.value = 1
  loadReservations()
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
  loadReservations()
}

const goToPage = (page: number) => {
  if (page >= 1 && page <= lastPage.value) {
    currentPage.value = page
    loadReservations()
  }
}

const prevPage = () => {
  if (currentPage.value > 1) {
    currentPage.value--
    loadReservations()
  }
}

const nextPage = () => {
  if (currentPage.value < lastPage.value) {
    currentPage.value++
    loadReservations()
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
  const id = selectedReservation.value.id
  try {
    await store.deleteReservation(id)
    deleteDialog.value = false
    selectedReservation.value = null
    await loadReservations()
    showToast('Reservation deleted successfully')
  } catch (error) {
    console.error('[ReservationList] Error deleting reservation:', error)
    showToast('Failed to delete reservation')
  }
}

const confirmReservation = async (reservation: Reservation) => {
  try {
    await store.confirmReservation(reservation.id)
    await loadReservations()
    showToast('Reservation confirmed!')
  } catch (error) {
    console.error('[ReservationList] Error confirming reservation:', error)
    showToast('Failed to confirm reservation')
  }
}

const checkIn = async (reservation: Reservation) => {
  try {
    await store.checkInReservation(reservation.id)
    await loadReservations()
    showToast('Guest checked in successfully!')
  } catch (error) {
    console.error('[ReservationList] Error checking in guest:', error)
    showToast('Failed to check in guest')
  }
}

const checkOut = async (reservation: Reservation) => {
  try {
    await store.checkOutReservation(reservation.id)
    await loadReservations()
    showToast('Guest checked out successfully!')
  } catch (error) {
    console.error('[ReservationList] Error checking out guest:', error)
    showToast('Failed to check out guest')
  }
}

const cancelReservation = async (reservation: Reservation) => {
  try {
    await store.cancelReservation(reservation.id)
    await loadReservations()
    showToast('Reservation cancelled')
  } catch (error) {
    console.error('[ReservationList] Error cancelling reservation:', error)
    showToast('Failed to cancel reservation')
  }
}

const formatDate = (date: string) => {
  if (!date) return '—'
  try {
    const d = new Date(date)
    return d.toLocaleDateString('en-US', {
      month: 'short',
      day: 'numeric',
      year: 'numeric',
    })
  } catch (error) {
    console.error('[ReservationList] Error formatting date:', error)
    return date
  }
}

const currency = computed(() => hotelStore.currentHotel?.currency || 'ETB')

const formatCurrency = (amount: number | string) => {
  const num = typeof amount === 'string' ? parseFloat(amount) : amount
  if (isNaN(num)) return `0.00 ${currency.value}`
  return `${num.toFixed(2)} ${currency.value}`
}

const getStatusBadgeClass = (status: string) => {
  switch ((status || '').toLowerCase()) {
    case 'confirmed':
      return 'bg-blue-500/10 text-blue-600 dark:text-blue-400 border-blue-500/20'
    case 'checked_in':
      return 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/20'
    case 'checked_out':
      return 'bg-purple-500/10 text-purple-600 dark:text-purple-400 border-purple-500/20'
    case 'pending':
      return 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border-amber-500/20'
    case 'cancelled':
      return 'bg-rose-500/10 text-rose-600 dark:text-rose-400 border-rose-500/20'
    default:
      return 'bg-slate-500/10 text-slate-600 dark:text-slate-400 border-slate-500/20'
  }
}

const getRoomTypeName = (room?: any): string => {
  if (!room || !room.room_type) return ''
  if (typeof room.room_type === 'string') return room.room_type
  if (typeof room.room_type === 'object' && room.room_type.name) return room.room_type.name
  return ''
}

const reloadAll = async () => {
  await Promise.all([loadReservations(), loadRoomTypesAndRooms()])
}

onMounted(async () => {
  await reloadAll()
})

watch(() => hotelStore.hotelId, async () => {
  await reloadAll()
})
</script>

<template>
  <DashboardLayout>
    <div
      class="space-y-6 bg-slate-50 dark:bg-slate-950 min-h-screen p-4 sm:p-6 max-w-full overflow-hidden font-sans"
      :class="{ 'fixed inset-0 z-50 p-6 overflow-y-auto bg-white dark:bg-slate-950': isFullscreen }"
    >
      <!-- Toast Notification -->
      <div v-if="toastMessage" class="fixed bottom-6 right-6 z-50 bg-slate-900 dark:bg-white text-white dark:text-slate-900 px-5 py-3 rounded-2xl shadow-2xl flex items-center gap-3 border border-slate-800 text-xs font-bold">
        <CheckCircle2 class="w-4 h-4 text-emerald-500" />
        <span>{{ toastMessage }}</span>
      </div>

      <!-- Header Section -->
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-5 sm:p-6 rounded-2xl shadow-xs">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-600 to-indigo-600 flex items-center justify-center shadow-md flex-shrink-0 text-white">
            <Calendar class="w-5 h-5 stroke-[2.2]" />
          </div>
          <div>
            <div class="flex items-center gap-2">
              <h1 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white">{{ languageStore.t('Reservations', 'Reservations') }}</h1>
              <span v-if="hotelStore.hotelName" class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300 border border-blue-200 dark:border-blue-700/50">
                <Building2 class="w-3 h-3" />
                {{ hotelStore.hotelName }}
              </span>
            </div>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">{{ languageStore.t('manage_reservations_desc', 'Manage hotel reservations, check-ins, and guest stays.') }}</p>
          </div>
        </div>
      </div>

      <!-- Statistics Cards -->
      <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <!-- Total -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl p-5 shadow-xs border border-slate-200 dark:border-slate-800 flex items-center justify-between">
          <div>
            <p class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">{{ languageStore.t('Total Bookings', 'Total Bookings') }}</p>
            <h3 class="text-2xl font-black text-slate-900 dark:text-white mt-1">{{ totalReservations }}</h3>
          </div>
          <div class="p-3 rounded-2xl bg-blue-500/10 text-blue-600 dark:text-blue-400">
            <Calendar class="w-5 h-5" />
          </div>
        </div>

        <!-- Pending -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl p-5 shadow-xs border border-slate-200 dark:border-slate-800 flex items-center justify-between">
          <div>
            <p class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">{{ languageStore.t('pending', 'Pending') }}</p>
            <h3 class="text-2xl font-black text-amber-600 dark:text-amber-400 mt-1">{{ pendingCount }}</h3>
          </div>
          <div class="p-3 rounded-2xl bg-amber-500/10 text-amber-600 dark:text-amber-400">
            <Clock class="w-5 h-5" />
          </div>
        </div>

        <!-- Confirmed -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl p-5 shadow-xs border border-slate-200 dark:border-slate-800 flex items-center justify-between">
          <div>
            <p class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">{{ languageStore.t('confirmed', 'Confirmed') }}</p>
            <h3 class="text-2xl font-black text-blue-600 dark:text-blue-400 mt-1">{{ confirmedCount }}</h3>
          </div>
          <div class="p-3 rounded-2xl bg-blue-500/10 text-blue-600 dark:text-blue-400">
            <CheckCircle2 class="w-5 h-5" />
          </div>
        </div>

        <!-- Checked In -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl p-5 shadow-xs border border-slate-200 dark:border-slate-800 flex items-center justify-between">
          <div>
            <p class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">{{ languageStore.t('checked_in', 'Checked In') }}</p>
            <h3 class="text-2xl font-black text-emerald-600 dark:text-emerald-400 mt-1">{{ checkedInCount }}</h3>
          </div>
          <div class="p-3 rounded-2xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400">
            <UserCheck class="w-5 h-5" />
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
            <Search class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 dark:text-slate-500" />
            <input
              v-model="searchQuery"
              type="text"
              :placeholder="languageStore.t('search_reservations_ph', 'Search by guest name, reservation #, room, email...')"
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
                : 'border border-slate-200 dark:border-[#1e3455] bg-white dark:bg-[#13233c]/80 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-[#1c3356]'
            ]"
          >
            <component :is="isFilterOpen ? X : Filter" class="w-4 h-4" />
            <span>{{ isFilterOpen ? languageStore.t('hide_filter', 'Hide Filter') : languageStore.t('filter', 'Filter') }}</span>
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
          <div class="grid grid-cols-1 sm:grid-cols-4 gap-3.5 sm:gap-4">
            <!-- Status Filter -->
            <div>
              <label class="mb-1.5 block text-xs font-semibold text-slate-600 dark:text-slate-300">
                {{ languageStore.t('Reservation Status', 'Reservation Status') }}
              </label>
              <select
                v-model="filterStatus"
                class="w-full rounded-xl border border-slate-200 dark:border-[#1e3455] bg-slate-50/80 dark:bg-[#13233c] text-slate-900 dark:text-white px-3.5 py-2 text-xs sm:text-sm focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 dark:focus:border-blue-400 transition cursor-pointer font-medium outline-none"
              >
                <option value="">{{ languageStore.t('All Statuses', 'All Statuses') }}</option>
                <option value="pending">{{ languageStore.t('pending', 'Pending') }}</option>
                <option value="confirmed">{{ languageStore.t('confirmed', 'Confirmed') }}</option>
                <option value="checked_in">{{ languageStore.t('checked_in', 'Checked In') }}</option>
                <option value="checked_out">{{ languageStore.t('checked_out', 'Checked Out') }}</option>
                <option value="cancelled">{{ languageStore.t('cancelled', 'Cancelled') }}</option>
              </select>
            </div>

            <!-- Room Type Filter -->
            <div>
              <label class="mb-1.5 block text-xs font-semibold text-slate-600 dark:text-slate-300">
                {{ languageStore.t('Room Type', 'Room Type') }}
              </label>
              <select
                v-model="filterRoomTypeId"
                class="w-full rounded-xl border border-slate-200 dark:border-[#1e3455] bg-slate-50/80 dark:bg-[#13233c] text-slate-900 dark:text-white px-3.5 py-2 text-xs sm:text-sm focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 dark:focus:border-blue-400 transition cursor-pointer font-medium outline-none"
              >
                <option value="">{{ languageStore.t('All Room Types', 'All Room Types') }}</option>
                <option v-for="type in roomTypes" :key="type.id" :value="type.id">
                  {{ type.name }}
                </option>
              </select>
            </div>

            <!-- Check-in Date -->
            <div>
              <label class="mb-1.5 block text-xs font-semibold text-slate-600 dark:text-slate-300">
                {{ languageStore.t('Check-in Date', 'Check-in Date') }}
              </label>
              <input
                v-model="filterCheckInDate"
                type="date"
                class="w-full rounded-xl border border-slate-200 dark:border-[#1e3455] bg-slate-50/80 dark:bg-[#13233c] text-slate-900 dark:text-white px-3.5 py-2 text-xs sm:text-sm focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 dark:focus:border-blue-400 transition outline-none"
              />
            </div>

            <!-- Check-out Date / Reset -->
            <div class="flex items-end gap-2">
              <div class="flex-1">
                <label class="mb-1.5 block text-xs font-semibold text-slate-600 dark:text-slate-300">
                  {{ languageStore.t('Check-out Date', 'Check-out Date') }}
                </label>
                <input
                  v-model="filterCheckOutDate"
                  type="date"
                  class="w-full rounded-xl border border-slate-200 dark:border-[#1e3455] bg-slate-50/80 dark:bg-[#13233c] text-slate-900 dark:text-white px-3.5 py-2 text-xs sm:text-sm focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 dark:focus:border-blue-400 transition outline-none"
                />
              </div>

              <button
                type="button"
                @click="handleResetFilters"
                class="inline-flex items-center justify-center gap-1.5 rounded-xl border border-slate-200 dark:border-[#1e3455] bg-slate-100/70 dark:bg-[#13233c] px-3.5 py-2 text-xs font-semibold text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-[#1c3356] transition cursor-pointer flex-shrink-0 h-[38px]"
                :title="languageStore.t('reset_filters', 'Reset Filters')"
              >
                <RotateCcw class="w-3.5 h-3.5" />
                <span class="hidden sm:inline">{{ languageStore.t('Reset', 'Reset') }}</span>
              </button>
            </div>
          </div>
        </div>
      </Transition>

      <!-- Reservations Table Container -->
      <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-xs overflow-hidden font-sans w-full">
        <!-- Desktop Table View -->
        <div class="hidden md:block overflow-x-auto w-full">
          <table class="w-full text-left border-collapse">
            <thead class="bg-slate-50/90 dark:bg-[#0c182c] border-b border-slate-200 dark:border-[#1e3455]">
              <tr class="text-[11px] font-bold text-slate-500 dark:text-slate-400 select-none">
                <th class="py-3 px-4 pl-5 whitespace-nowrap">{{ languageStore.t('Guest & Booking #', 'Guest & Booking #') }}</th>
                <th class="py-3 px-4 whitespace-nowrap">{{ languageStore.t('room', 'Room') }}</th>
                <th class="py-3 px-4 whitespace-nowrap">{{ languageStore.t('Stay Dates', 'Stay Dates') }}</th>
                <th class="py-3 px-4 whitespace-nowrap">{{ languageStore.t('Total / Guests', 'Total / Guests') }}</th>
                <th class="py-3 px-4 text-center whitespace-nowrap">{{ languageStore.t('Status', 'Status') }}</th>
                <th class="py-3 px-4 text-right pr-5 whitespace-nowrap">{{ languageStore.t('Actions', 'Actions') }}</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-[#1e3455]/60 text-xs">
              <!-- Loading Spinner State -->
              <tr v-if="store.loading">
                <td colspan="6" class="px-6 py-20 text-center">
                  <div class="flex flex-col items-center justify-center gap-3">
                    <Loader2 class="w-8 h-8 text-blue-600 dark:text-blue-400 animate-spin" />
                    <span class="text-xs font-bold text-slate-600 dark:text-slate-400">{{ languageStore.t('loading_reservations_data', 'Loading reservations...') }}</span>
                  </div>
                </td>
              </tr>

              <!-- Data Rows -->
              <template v-else>
                <tr
                  v-for="reservation in paginatedReservations"
                  :key="reservation.id"
                  class="hover:bg-slate-50/80 dark:hover:bg-[#13233c]/60 transition-colors duration-150 group"
                >
                <!-- Guest & Booking # -->
                <td class="py-3 px-4 pl-5 whitespace-nowrap">
                  <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-xl bg-blue-500/10 text-blue-600 dark:text-blue-400 font-black flex items-center justify-center text-xs border border-blue-500/20 flex-shrink-0">
                      {{ (reservation.guest?.first_name || 'G').charAt(0).toUpperCase() }}
                    </div>
                    <div>
                      <div class="font-bold text-slate-900 dark:text-white">
                        {{ reservation.guest?.first_name }} {{ reservation.guest?.last_name }}
                      </div>
                      <div class="text-[10px] text-slate-400 font-mono">
                        {{ reservation.reservation_number || `#${reservation.id.slice(-6)}` }}
                      </div>
                    </div>
                  </div>
                </td>

                <!-- Room -->
                <td class="py-3 px-4 whitespace-nowrap">
                  <div class="flex items-center gap-2">
                    <span class="font-black text-slate-900 dark:text-white font-mono text-xs sm:text-sm">
                      Room {{ reservation.room?.room_number || 'TBD' }}
                    </span>
                    <span v-if="getRoomTypeName(reservation.room)" class="text-[10px] text-slate-400 font-medium">
                      ({{ getRoomTypeName(reservation.room) }})
                    </span>
                  </div>
                </td>

                <!-- Stay Dates -->
                <td class="py-3 px-4 whitespace-nowrap">
                  <div class="font-semibold text-slate-800 dark:text-slate-200">
                    {{ formatDate(reservation.check_in_date) }} → {{ formatDate(reservation.check_out_date) }}
                  </div>
                  <div class="text-[10px] text-slate-400 font-medium">
                    {{ reservation.adults_count || 1 }} {{ languageStore.t('Adults', 'Adults') }}, {{ reservation.children_count || 0 }} {{ languageStore.t('Kids', 'Kids') }}
                  </div>
                </td>

                <!-- Total / Guests -->
                <td class="py-3 px-4 whitespace-nowrap">
                  <div class="font-bold text-slate-900 dark:text-white">
                    {{ formatCurrency(reservation.total_price || reservation.total_amount || reservation.total || 0) }}
                  </div>
                  <div class="text-[10px] text-slate-400 font-medium capitalize">
                    {{ reservation.payment_status || 'Pending' }}
                  </div>
                </td>

                <!-- Status -->
                <td class="py-3 px-4 text-center whitespace-nowrap">
                  <span
                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-extrabold border uppercase tracking-wider"
                    :class="getStatusBadgeClass(reservation.status)"
                  >
                    {{ (reservation.status || 'pending').replace('_', ' ') }}
                  </span>
                </td>

                <!-- Actions -->
                <td class="py-3 px-4 text-right pr-5 whitespace-nowrap">
                  <div class="flex items-center justify-end gap-1">
                    <!-- Confirm -->
                    <button
                      v-if="reservation.status === 'pending'"
                      @click="confirmReservation(reservation)"
                      class="p-1.5 text-emerald-600 hover:bg-emerald-50 dark:hover:bg-emerald-950/40 rounded-lg transition cursor-pointer"
                      :title="languageStore.t('Confirm Reservation', 'Confirm Reservation')"
                    >
                      <CheckCircle2 class="w-3.5 h-3.5" />
                    </button>

                    <!-- Check-in -->
                    <button
                      v-if="reservation.status === 'confirmed'"
                      @click="checkIn(reservation)"
                      class="p-1.5 text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-950/40 rounded-lg transition cursor-pointer"
                      :title="languageStore.t('Check In Guest', 'Check In Guest')"
                    >
                      <LogIn class="w-3.5 h-3.5" />
                    </button>

                    <!-- Check-out -->
                    <button
                      v-if="reservation.status === 'checked_in'"
                      @click="checkOut(reservation)"
                      class="p-1.5 text-purple-600 hover:bg-purple-50 dark:hover:bg-purple-950/40 rounded-lg transition cursor-pointer"
                      :title="languageStore.t('Check Out Guest', 'Check Out Guest')"
                    >
                      <LogOut class="w-3.5 h-3.5" />
                    </button>

                    <!-- View Details -->
                    <button
                      @click="viewReservation(reservation)"
                      class="p-1.5 text-slate-500 hover:text-blue-600 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg transition cursor-pointer"
                      :title="languageStore.t('View Details', 'View Details')"
                    >
                      <Eye class="w-3.5 h-3.5" />
                    </button>

                    <!-- Edit -->
                    <button
                      @click="editReservation(reservation)"
                      class="p-1.5 text-slate-500 hover:text-amber-600 hover:bg-amber-50 dark:hover:bg-amber-950/40 rounded-lg transition cursor-pointer"
                      :title="languageStore.t('Edit Reservation', 'Edit Reservation')"
                    >
                      <Edit class="w-3.5 h-3.5" />
                    </button>

                    <!-- Delete -->
                    <button
                      @click="confirmDelete(reservation)"
                      class="p-1.5 text-slate-500 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/40 rounded-lg transition cursor-pointer"
                      :title="languageStore.t('Delete Reservation', 'Delete Reservation')"
                    >
                      <Trash2 class="w-3.5 h-3.5" />
                    </button>
                  </div>
                </td>
              </tr>

              <!-- Empty State -->
              <tr v-if="paginatedReservations.length === 0">
                <td colspan="6" class="px-6 py-12 text-center text-slate-500 dark:text-slate-400 text-xs font-bold">
                  {{ languageStore.t('no_reservations_found', 'No reservations found matching your criteria.') }}
                </td>
              </tr>
            </template>
          </tbody>
        </table>
      </div>

      <!-- Mobile View -->
      <div class="md:hidden divide-y divide-slate-100 dark:divide-slate-800">
        <div v-if="store.loading" class="py-16 text-center flex flex-col items-center justify-center gap-3">
          <Loader2 class="w-8 h-8 text-blue-600 dark:text-blue-400 animate-spin" />
          <span class="text-xs font-bold text-slate-600 dark:text-slate-400">{{ languageStore.t('loading_reservations_data', 'Loading reservations...') }}</span>
        </div>
        <template v-else>
          <div
            v-for="reservation in paginatedReservations"
            :key="reservation.id"
            class="p-4 space-y-3 hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition"
          >
            <div class="flex items-center justify-between">
              <span class="font-bold text-slate-900 dark:text-white text-sm">
                {{ reservation.guest?.first_name }} {{ reservation.guest?.last_name }}
              </span>
              <span
                class="px-2 py-0.5 rounded-full text-[10px] font-bold border uppercase"
                :class="getStatusBadgeClass(reservation.status)"
              >
                {{ reservation.status }}
              </span>
            </div>
            <div class="flex items-center justify-between text-xs text-slate-600 dark:text-slate-400">
              <span>Room {{ reservation.room?.room_number || 'TBD' }}</span>
              <span>{{ formatDate(reservation.check_in_date) }}</span>
            </div>
            <div class="flex items-center justify-end gap-3 pt-1 border-t border-slate-100 dark:border-slate-800 text-xs font-bold">
              <button @click="viewReservation(reservation)" class="text-blue-600">{{ languageStore.t('View', 'View') }}</button>
              <button @click="editReservation(reservation)" class="text-amber-600">{{ languageStore.t('Edit', 'Edit') }}</button>
              <button @click="confirmDelete(reservation)" class="text-rose-600">{{ languageStore.t('Delete', 'Delete') }}</button>
            </div>
          </div>
        </template>
      </div>

        <!-- Pagination Footer -->
        <div
          v-if="totalReservations > 0"
          class="border-t border-slate-200 dark:border-slate-800/80 px-4 sm:px-6 py-3 sm:py-4 bg-slate-50/50 dark:bg-[#0c182c] flex flex-col sm:flex-row items-center justify-between gap-3 text-xs"
        >
          <div class="text-slate-500 dark:text-slate-400 font-medium">
            {{ languageStore.t('Showing', 'Showing') }} <span class="font-bold text-slate-900 dark:text-white">{{ showingFrom }}</span> {{ languageStore.t('to', 'to') }}
            <span class="font-bold text-slate-900 dark:text-white">{{ showingTo }}</span> {{ languageStore.t('of', 'of') }}
            <span class="font-bold text-slate-900 dark:text-white">{{ totalReservations }}</span> {{ languageStore.t('reservations', 'reservations') }}
          </div>

          <div class="flex items-center gap-2 sm:gap-3">
            <div class="flex items-center gap-1.5">
              <span class="text-slate-500 dark:text-slate-400 font-medium">{{ languageStore.t('Per page:', 'Per page:') }}</span>
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
                    : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800'
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
