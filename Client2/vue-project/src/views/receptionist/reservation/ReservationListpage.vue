<script setup lang="ts">
import { onMounted, onUnmounted, ref, computed, watch } from 'vue'
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
  MoreVertical,
  ExternalLink,
  Mail,
  Phone,
  Globe,
  FileText,
  User,
  AlertCircle,
} from 'lucide-vue-next'

import { useReservationStore } from '@/stores/reservationStore'
import { useGuestStore } from '@/stores/guestStore'
import { useHotelStore } from '@/stores/hotelStore'
import { useLanguageStore } from '@/stores/language'
import roomService from '@/services/roomService'
import { roomTypeService } from '@/services/roomTypeService'
import reservationService from '@/services/reservationService'
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

// Dropdown and Details Modal state
const openDropdownId = ref<string | null>(null)
const detailsModalOpen = ref(false)
const detailsReservation = ref<Reservation | null>(null)
const detailsLoading = ref(false)
const modalActionLoading = ref(false)

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

const toggleDropdown = (id: string, e: MouseEvent) => {
  e.stopPropagation()
  openDropdownId.value = openDropdownId.value === id ? null : id
}

const closeDropdown = () => {
  openDropdownId.value = null
}

const handleAction = (actionFn: () => void) => {
  closeDropdown()
  actionFn()
}

const handleClickOutside = (e: MouseEvent) => {
  const target = e.target as HTMLElement
  if (!target.closest('.action-dropdown-container')) {
    openDropdownId.value = null
  }
}

const openDetailsModal = async (reservation: Reservation) => {
  closeDropdown()
  if (!reservation) return
  detailsReservation.value = { ...reservation }
  detailsModalOpen.value = true
  detailsLoading.value = true
  try {
    const res = await reservationService.getReservation(reservation.id)
    const fresh = res?.data || res
    if (fresh) {
      detailsReservation.value = { ...reservation, ...fresh }
    }
  } catch (err) {
    console.warn('[ReservationList] Notice: using initial reservation data:', err)
  } finally {
    detailsLoading.value = false
  }
}

const viewReservation = (reservation: Reservation) => {
  openDetailsModal(reservation)
}

const navigateToViewPage = (id?: string) => {
  if (!id) return
  detailsModalOpen.value = false
  router.push(`/reservations/${id}`)
}

const editReservation = (reservation: Reservation) => {
  closeDropdown()
  router.push(`/reservations/${reservation.id}/edit`)
}

const confirmFromModal = async () => {
  if (!detailsReservation.value) return
  modalActionLoading.value = true
  try {
    await confirmReservation(detailsReservation.value)
    if (detailsReservation.value) {
      detailsReservation.value.status = 'confirmed'
    }
  } finally {
    modalActionLoading.value = false
  }
}

const checkInFromModal = async () => {
  if (!detailsReservation.value) return
  modalActionLoading.value = true
  try {
    await checkIn(detailsReservation.value)
    if (detailsReservation.value) {
      detailsReservation.value.status = 'checked_in'
    }
  } finally {
    modalActionLoading.value = false
  }
}

const checkOutFromModal = async () => {
  if (!detailsReservation.value) return
  modalActionLoading.value = true
  try {
    await checkOut(detailsReservation.value)
    if (detailsReservation.value) {
      detailsReservation.value.status = 'checked_out'
    }
  } finally {
    modalActionLoading.value = false
  }
}

const editFromModal = () => {
  if (!detailsReservation.value) return
  const id = detailsReservation.value.id
  detailsModalOpen.value = false
  router.push(`/reservations/${id}/edit`)
}

const deleteFromModal = () => {
  if (!detailsReservation.value) return
  const res = detailsReservation.value
  detailsModalOpen.value = false
  confirmDelete(res)
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
  window.addEventListener('click', handleClickOutside)
  await reloadAll()
})

onUnmounted(() => {
  window.removeEventListener('click', handleClickOutside)
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
      <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-xs font-sans w-full">
        <!-- Desktop Table View -->
        <div class="hidden md:block overflow-x-auto w-full min-h-[380px]">
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
                  v-for="(reservation, index) in paginatedReservations"
                  :key="reservation.id"
                  class="hover:bg-slate-50/80 dark:hover:bg-[#13233c]/60 transition-colors duration-150 group"
                  :class="{ 'relative z-30 bg-blue-50/30 dark:bg-[#13233c]/40': openDropdownId === reservation.id }"
                >
                <!-- Guest & Booking # -->
                <td class="py-3 px-4 pl-5 whitespace-nowrap">
                  <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-xl bg-blue-500/10 text-blue-600 dark:text-blue-400 font-black flex items-center justify-center text-xs border border-blue-500/20 flex-shrink-0">
                      {{ (reservation.guest?.first_name || 'G').charAt(0).toUpperCase() }}
                    </div>
                    <div @click="openDetailsModal(reservation)" class="cursor-pointer group/guest" title="Click to view details">
                      <div class="font-bold text-slate-900 dark:text-white group-hover/guest:text-blue-600 transition-colors">
                        {{ reservation.guest?.first_name }} {{ reservation.guest?.last_name }}
                      </div>
                      <div class="text-[10px] text-slate-400 font-mono group-hover/guest:text-blue-500 transition-colors">
                        {{ reservation.booking_reference || reservation.reservation_number || `#${String(reservation.id || '').slice(-6)}` }}
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

                <!-- Actions: Direct View Button + Three-dot dropdown menu -->
                <td class="py-3 px-4 text-right pr-5 whitespace-nowrap" :class="{ 'relative z-50': openDropdownId === reservation.id }">
                  <div class="flex items-center justify-end gap-1.5">
                    <!-- Direct Quick View Button -->
                    <button
                      type="button"
                      @click.stop="openDetailsModal(reservation)"
                      class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-xl border border-blue-200 dark:border-blue-900/60 bg-blue-50/70 dark:bg-blue-950/40 text-blue-600 dark:text-blue-400 hover:bg-blue-100 dark:hover:bg-blue-900/60 hover:border-blue-300 dark:hover:border-blue-600 shadow-2xs text-xs font-bold transition cursor-pointer"
                      :title="languageStore.t('view_details', 'View Details')"
                    >
                      <Eye class="w-3.5 h-3.5 stroke-[2.2]" />
                      <span class="hidden sm:inline">{{ languageStore.t('View', 'View') }}</span>
                    </button>

                    <!-- Three-dot secondary actions menu container -->
                    <div class="relative inline-block text-left action-dropdown-container">
                      <button
                        type="button"
                        @click.stop="toggleDropdown(reservation.id, $event)"
                        class="inline-flex items-center justify-center w-8 h-8 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-[#13233c] text-slate-600 dark:text-slate-300 hover:text-blue-600 dark:hover:text-blue-400 hover:border-blue-300 dark:hover:border-blue-500/50 hover:bg-blue-50/50 dark:hover:bg-blue-950/30 shadow-xs transition cursor-pointer"
                        :class="{ 'bg-blue-50 dark:bg-blue-950/40 text-blue-600 dark:text-blue-400 border-blue-400 dark:border-blue-500 shadow-sm ring-2 ring-blue-500/20': openDropdownId === reservation.id }"
                        :title="languageStore.t('Actions', 'Actions')"
                      >
                        <MoreVertical class="w-4 h-4 stroke-[2.5]" />
                      </button>

                      <!-- Dropdown Menu -->
                      <Transition
                        enter-active-class="transition duration-100 ease-out"
                        enter-from-class="transform scale-95 opacity-0"
                        enter-to-class="transform scale-100 opacity-100"
                        leave-active-class="transition duration-75 ease-in"
                        leave-from-class="transform scale-100 opacity-100"
                        leave-to-class="transform scale-95 opacity-0"
                      >
                        <div
                          v-if="openDropdownId === reservation.id"
                          class="absolute right-0 z-50 w-52 rounded-2xl bg-white dark:bg-[#0f1d32] border border-slate-200 dark:border-slate-800 shadow-2xl shadow-slate-900/20 dark:shadow-slate-950/70 py-1.5 focus:outline-none"
                          :class="[
                            paginatedReservations.length >= 4 && index >= paginatedReservations.length - 2
                              ? 'bottom-full mb-1.5'
                              : 'top-full mt-1.5'
                          ]"
                        >
                          <!-- View Details (Modal) -->
                          <button
                            type="button"
                            @click="openDetailsModal(reservation)"
                            class="w-full flex items-center gap-2.5 px-3.5 py-2 text-xs font-semibold text-slate-700 dark:text-slate-200 hover:bg-blue-50 dark:hover:bg-blue-950/40 hover:text-blue-600 dark:hover:text-blue-400 transition cursor-pointer text-left"
                          >
                            <Eye class="w-4 h-4 text-blue-500 flex-shrink-0" />
                            <span>{{ languageStore.t('view_details', 'View Details') }}</span>
                          </button>

                          <!-- Full Page View -->
                          <button
                            type="button"
                            @click="handleAction(() => navigateToViewPage(reservation.id))"
                            class="w-full flex items-center gap-2.5 px-3.5 py-2 text-xs font-semibold text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition cursor-pointer text-left"
                          >
                            <ExternalLink class="w-4 h-4 text-slate-400 flex-shrink-0" />
                            <span>{{ languageStore.t('Full Page View', 'Full Page View') }}</span>
                          </button>

                          <div class="my-1 border-t border-slate-100 dark:border-slate-800"></div>

                          <!-- Confirm (Pending) -->
                          <button
                            v-if="reservation.status === 'pending'"
                            type="button"
                            @click="handleAction(() => confirmReservation(reservation))"
                            class="w-full flex items-center gap-2.5 px-3.5 py-2 text-xs font-semibold text-slate-700 dark:text-slate-200 hover:bg-emerald-50 dark:hover:bg-emerald-950/40 hover:text-emerald-600 dark:hover:text-emerald-400 transition cursor-pointer text-left"
                          >
                            <CheckCircle2 class="w-4 h-4 text-emerald-500 flex-shrink-0" />
                            <span>{{ languageStore.t('confirm_booking', 'Confirm Booking') }}</span>
                          </button>

                          <!-- Check-in (Confirmed) -->
                          <button
                            v-if="reservation.status === 'confirmed'"
                            type="button"
                            @click="handleAction(() => checkIn(reservation))"
                            class="w-full flex items-center gap-2.5 px-3.5 py-2 text-xs font-semibold text-slate-700 dark:text-slate-200 hover:bg-blue-50 dark:hover:bg-blue-950/40 hover:text-blue-600 dark:hover:text-blue-400 transition cursor-pointer text-left"
                          >
                            <LogIn class="w-4 h-4 text-blue-500 flex-shrink-0" />
                            <span>{{ languageStore.t('check_in_guest', 'Check In Guest') }}</span>
                          </button>

                          <!-- Check-out (Checked In) -->
                          <button
                            v-if="reservation.status === 'checked_in'"
                            type="button"
                            @click="handleAction(() => checkOut(reservation))"
                            class="w-full flex items-center gap-2.5 px-3.5 py-2 text-xs font-semibold text-slate-700 dark:text-slate-200 hover:bg-purple-50 dark:hover:bg-purple-950/40 hover:text-purple-600 dark:hover:text-purple-400 transition cursor-pointer text-left"
                          >
                            <LogOut class="w-4 h-4 text-purple-500 flex-shrink-0" />
                            <span>{{ languageStore.t('check_out_guest', 'Check Out Guest') }}</span>
                          </button>

                          <!-- Edit -->
                          <button
                            type="button"
                            @click="handleAction(() => editReservation(reservation))"
                            class="w-full flex items-center gap-2.5 px-3.5 py-2 text-xs font-semibold text-slate-700 dark:text-slate-200 hover:bg-amber-50 dark:hover:bg-amber-950/40 hover:text-amber-600 dark:hover:text-amber-400 transition cursor-pointer text-left"
                          >
                            <Edit class="w-4 h-4 text-amber-500 flex-shrink-0" />
                            <span>{{ languageStore.t('edit_reservation', 'Edit') }}</span>
                          </button>

                          <div class="my-1 border-t border-slate-100 dark:border-slate-800"></div>

                          <!-- Delete -->
                          <button
                            type="button"
                            @click="handleAction(() => confirmDelete(reservation))"
                            class="w-full flex items-center gap-2.5 px-3.5 py-2 text-xs font-semibold text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition cursor-pointer text-left"
                          >
                            <Trash2 class="w-4 h-4 text-rose-500 flex-shrink-0" />
                            <span>{{ languageStore.t('delete_reservation', 'Delete') }}</span>
                          </button>
                        </div>
                      </Transition>
                    </div>
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
            <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100 dark:border-slate-800 text-xs font-bold">
              <button
                type="button"
                @click="openDetailsModal(reservation)"
                class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg bg-blue-50 dark:bg-blue-950/40 text-blue-600 dark:text-blue-400 hover:bg-blue-100 dark:hover:bg-blue-900/50 transition cursor-pointer"
              >
                <Eye class="w-3.5 h-3.5" />
                <span>{{ languageStore.t('View', 'View') }}</span>
              </button>
              <button
                type="button"
                @click="editReservation(reservation)"
                class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg bg-amber-50 dark:bg-amber-950/40 text-amber-600 dark:text-amber-400 hover:bg-amber-100 dark:hover:bg-amber-900/50 transition cursor-pointer"
              >
                <Edit class="w-3.5 h-3.5" />
                <span>{{ languageStore.t('Edit', 'Edit') }}</span>
              </button>
              <button
                type="button"
                @click="confirmDelete(reservation)"
                class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg bg-rose-50 dark:bg-rose-950/40 text-rose-600 dark:text-rose-400 hover:bg-rose-100 dark:hover:bg-rose-900/50 transition cursor-pointer"
              >
                <Trash2 class="w-3.5 h-3.5" />
                <span>{{ languageStore.t('Delete', 'Delete') }}</span>
              </button>
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

      <!-- Reservation Details Modal -->
      <Teleport to="body">
        <Transition
          enter-active-class="transition duration-200 ease-out"
          enter-from-class="opacity-0"
          enter-to-class="opacity-100"
          leave-active-class="transition duration-150 ease-in"
          leave-from-class="opacity-100"
          leave-to-class="opacity-0"
        >
          <div
            v-if="detailsModalOpen && detailsReservation"
            class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-xs overflow-y-auto"
            @click.self="detailsModalOpen = false"
          >
            <div
              class="w-full max-w-2xl bg-white dark:bg-[#0c182c] border border-slate-200 dark:border-slate-800 rounded-3xl shadow-2xl overflow-hidden font-sans my-8 transition-all"
            >
              <!-- Modal Header -->
              <div class="flex items-center justify-between px-6 py-5 border-b border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-[#0f1d32]/60">
                <div class="flex items-center gap-3">
                  <div class="w-10 h-10 rounded-xl bg-blue-500/10 text-blue-600 dark:text-blue-400 flex items-center justify-center font-bold flex-shrink-0">
                    <Calendar class="w-5 h-5" />
                  </div>
                  <div>
                    <div class="flex items-center gap-2">
                      <h3 class="text-base sm:text-lg font-black text-slate-900 dark:text-white">
                        {{ languageStore.t('Reservation Details', 'Reservation Details') }}
                      </h3>
                      <span
                        class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold border uppercase tracking-wider"
                        :class="getStatusBadgeClass(detailsReservation.status)"
                      >
                        {{ (detailsReservation.status || 'pending').replace('_', ' ') }}
                      </span>
                    </div>
                    <p class="text-xs text-slate-400 font-mono mt-0.5">
                      {{ detailsReservation.booking_reference || detailsReservation.reservation_number || `#${String(detailsReservation.id || '').slice(-8)}` }}
                    </p>
                  </div>
                </div>

                <button
                  type="button"
                  @click="detailsModalOpen = false"
                  class="p-2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-xl transition cursor-pointer"
                >
                  <X class="w-5 h-5" />
                </button>
              </div>

              <!-- Modal Body -->
              <div class="p-6 space-y-5 max-h-[72vh] overflow-y-auto">
                <!-- Loading indicator when fetching fresh data -->
                <div v-if="detailsLoading" class="flex items-center justify-center gap-2 py-2 text-xs font-semibold text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-950/40 rounded-xl p-2.5">
                  <Loader2 class="w-4 h-4 animate-spin" />
                  <span>{{ languageStore.t('Refreshing details...', 'Refreshing details...') }}</span>
                </div>

                <!-- Guest Profile Highlight -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between p-4 rounded-2xl bg-slate-50 dark:bg-[#13233c]/60 border border-slate-100 dark:border-slate-800 gap-3">
                  <div class="flex items-center gap-3.5">
                    <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-blue-600 to-indigo-600 text-white font-black text-lg flex items-center justify-center shadow-md flex-shrink-0">
                      {{ (detailsReservation.guest?.first_name || 'G').charAt(0).toUpperCase() }}
                    </div>
                    <div>
                      <h4 class="text-sm font-bold text-slate-900 dark:text-white">
                        {{ detailsReservation.guest?.first_name }} {{ detailsReservation.guest?.last_name }}
                      </h4>
                      <div class="flex flex-wrap items-center gap-3 text-xs text-slate-500 dark:text-slate-400 mt-1">
                        <span v-if="detailsReservation.guest?.email" class="flex items-center gap-1">
                          <Mail class="w-3.5 h-3.5 text-slate-400" />
                          {{ detailsReservation.guest?.email }}
                        </span>
                        <span v-if="detailsReservation.guest?.phone" class="flex items-center gap-1">
                          <Phone class="w-3.5 h-3.5 text-slate-400" />
                          {{ detailsReservation.guest?.phone }}
                        </span>
                        <span v-if="detailsReservation.guest?.nationality" class="flex items-center gap-1">
                          <Globe class="w-3.5 h-3.5 text-slate-400" />
                          {{ detailsReservation.guest?.nationality }}
                        </span>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Details Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                  <!-- Room & Stay -->
                  <div class="p-4 rounded-2xl border border-slate-200/80 dark:border-slate-800 space-y-3 bg-white dark:bg-[#0f1d32]/40">
                    <div class="flex items-center gap-2 text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider pb-2 border-b border-slate-100 dark:border-slate-800">
                      <BedDouble class="w-4 h-4 text-blue-500" />
                      <span>{{ languageStore.t('Room & Stay', 'Room & Stay') }}</span>
                    </div>
                    <div class="space-y-2 text-xs">
                      <div class="flex items-center justify-between">
                        <span class="text-slate-500">{{ languageStore.t('Room', 'Room') }}</span>
                        <span class="font-black text-slate-900 dark:text-white">
                          Room {{ detailsReservation.room?.room_number || 'TBD' }}
                          <span v-if="getRoomTypeName(detailsReservation.room)" class="text-slate-400 font-normal">
                            ({{ getRoomTypeName(detailsReservation.room) }})
                          </span>
                        </span>
                      </div>
                      <div class="flex items-center justify-between">
                        <span class="text-slate-500">{{ languageStore.t('Check-in Date', 'Check-in Date') }}</span>
                        <span class="font-bold text-slate-800 dark:text-slate-200">{{ formatDate(detailsReservation.check_in_date) }}</span>
                      </div>
                      <div class="flex items-center justify-between">
                        <span class="text-slate-500">{{ languageStore.t('Check-out Date', 'Check-out Date') }}</span>
                        <span class="font-bold text-slate-800 dark:text-slate-200">{{ formatDate(detailsReservation.check_out_date) }}</span>
                      </div>
                      <div class="flex items-center justify-between">
                        <span class="text-slate-500">{{ languageStore.t('Duration', 'Duration') }}</span>
                        <span class="font-semibold text-slate-700 dark:text-slate-300">
                          {{ detailsReservation.total_nights || detailsReservation.stay_duration || 1 }} night(s)
                        </span>
                      </div>
                    </div>
                  </div>

                  <!-- Guests & Billing -->
                  <div class="p-4 rounded-2xl border border-slate-200/80 dark:border-slate-800 space-y-3 bg-white dark:bg-[#0f1d32]/40">
                    <div class="flex items-center gap-2 text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider pb-2 border-b border-slate-100 dark:border-slate-800">
                      <DollarSign class="w-4 h-4 text-emerald-500" />
                      <span>{{ languageStore.t('Guests & Billing', 'Guests & Billing') }}</span>
                    </div>
                    <div class="space-y-2 text-xs">
                      <div class="flex items-center justify-between">
                        <span class="text-slate-500">{{ languageStore.t('Guests', 'Guests') }}</span>
                        <span class="font-bold text-slate-800 dark:text-slate-200">
                          {{ detailsReservation.adults_count || detailsReservation.number_of_guests || 1 }} Adults, {{ detailsReservation.children_count || 0 }} Kids
                        </span>
                      </div>
                      <div class="flex items-center justify-between">
                        <span class="text-slate-500">{{ languageStore.t('Payment Status', 'Payment Status') }}</span>
                        <span class="font-extrabold capitalize text-slate-800 dark:text-slate-200">
                          {{ detailsReservation.payment_status || 'Pending' }}
                        </span>
                      </div>
                      <div class="flex items-center justify-between pt-1 border-t border-slate-100 dark:border-slate-800">
                        <span class="text-slate-500 font-semibold">{{ languageStore.t('Total Price', 'Total Price') }}</span>
                        <span class="text-base font-black text-blue-600 dark:text-blue-400">
                          {{ formatCurrency(detailsReservation.total_price || detailsReservation.total_amount || 0) }}
                        </span>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Special Requests -->
                <div class="p-4 rounded-2xl border border-slate-200/80 dark:border-slate-800 bg-white dark:bg-[#0f1d32]/40 space-y-2">
                  <div class="flex items-center gap-2 text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider">
                    <FileText class="w-4 h-4 text-amber-500" />
                    <span>{{ languageStore.t('Special Requests & Notes', 'Special Requests & Notes') }}</span>
                  </div>
                  <p class="text-xs text-slate-600 dark:text-slate-300 leading-relaxed bg-slate-50 dark:bg-[#13233c]/60 p-3 rounded-xl border border-slate-100 dark:border-slate-800">
                    {{ detailsReservation.special_requests || 'No special requests provided.' }}
                  </p>
                </div>
              </div>

              <!-- Modal Footer Actions -->
              <div class="flex flex-wrap items-center justify-between gap-3 px-6 py-4 border-t border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-[#0f1d32]/60">
                <!-- Left: Open Full Page -->
                <button
                  type="button"
                  @click="navigateToViewPage(detailsReservation.id)"
                  class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 hover:bg-slate-100 dark:hover:bg-slate-700 text-xs font-bold text-slate-700 dark:text-slate-200 transition cursor-pointer"
                >
                  <ExternalLink class="w-3.5 h-3.5" />
                  <span>{{ languageStore.t('Full Page View', 'Full Page View') }}</span>
                </button>

                <!-- Right Action Buttons -->
                <div class="flex flex-wrap items-center gap-2">
                  <!-- Confirm (if pending) -->
                  <button
                    v-if="detailsReservation.status === 'pending'"
                    type="button"
                    :disabled="modalActionLoading"
                    @click="confirmFromModal"
                    class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold transition cursor-pointer disabled:opacity-50"
                  >
                    <CheckCircle2 class="w-3.5 h-3.5" />
                    <span>{{ languageStore.t('Confirm', 'Confirm') }}</span>
                  </button>

                  <!-- Check-in (if confirmed) -->
                  <button
                    v-if="detailsReservation.status === 'confirmed'"
                    type="button"
                    :disabled="modalActionLoading"
                    @click="checkInFromModal"
                    class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold transition cursor-pointer disabled:opacity-50"
                  >
                    <LogIn class="w-3.5 h-3.5" />
                    <span>{{ languageStore.t('Check In', 'Check In') }}</span>
                  </button>

                  <!-- Check-out (if checked_in) -->
                  <button
                    v-if="detailsReservation.status === 'checked_in'"
                    type="button"
                    :disabled="modalActionLoading"
                    @click="checkOutFromModal"
                    class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-purple-600 hover:bg-purple-700 text-white text-xs font-bold transition cursor-pointer disabled:opacity-50"
                  >
                    <LogOut class="w-3.5 h-3.5" />
                    <span>{{ languageStore.t('Check Out', 'Check Out') }}</span>
                  </button>

                  <!-- Edit -->
                  <button
                    type="button"
                    @click="editFromModal"
                    class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 hover:bg-slate-100 dark:hover:bg-slate-700 text-xs font-bold text-amber-600 dark:text-amber-400 transition cursor-pointer"
                  >
                    <Edit class="w-3.5 h-3.5" />
                    <span>{{ languageStore.t('Edit', 'Edit') }}</span>
                  </button>

                  <!-- Delete -->
                  <button
                    type="button"
                    @click="deleteFromModal"
                    class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl border border-rose-200 dark:border-rose-900/50 bg-rose-50/50 dark:bg-rose-950/30 hover:bg-rose-100 text-xs font-bold text-rose-600 dark:text-rose-400 transition cursor-pointer"
                  >
                    <Trash2 class="w-3.5 h-3.5" />
                    <span>{{ languageStore.t('Delete', 'Delete') }}</span>
                  </button>

                  <!-- Close -->
                  <button
                    type="button"
                    @click="detailsModalOpen = false"
                    class="px-4 py-2 rounded-xl bg-slate-200 dark:bg-slate-800 hover:bg-slate-300 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 text-xs font-bold transition cursor-pointer"
                  >
                    {{ languageStore.t('Close', 'Close') }}
                  </button>
                </div>
              </div>
            </div>
          </div>
        </Transition>
      </Teleport>

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
