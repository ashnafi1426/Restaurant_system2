<script setup lang="ts">
import { onMounted, ref, computed } from 'vue'
import DashboardLayout from '../../Layouts/DashboardLayout.vue'
import {
  Truck,
  Download,
  Clock,
  CheckCircle2,
  AlertCircle,
  RefreshCw,
  Search,
  Filter,
  X,
  Maximize2,
  Minimize2,
  RotateCcw,
  ChevronLeft,
  ChevronRight,
  Loader2,
  Eye,
  User,
  Hotel,
  Calendar,
  FileText,
  BedDouble,
  ShoppingBag,
} from 'lucide-vue-next'
import { useDeliveryManagementStore } from '@/stores/manager/deliveryManagementStore'
import { useLanguageStore } from '@/stores/language'

const store = useDeliveryManagementStore()
const languageStore = useLanguageStore()
const isLoading = ref(false)
const isFilterOpen = ref(false)
const isFullscreen = ref(false)

// Filter states
const searchQuery = ref('')
const selectedStatus = ref('')
const selectedFloor = ref('')

// Modal state
const showDetailsModal = ref(false)
const selectedDelivery = ref<any>(null)

const openDetailsModal = (delivery: any) => {
  selectedDelivery.value = delivery
  showDetailsModal.value = true
}

const closeDetailsModal = () => {
  showDetailsModal.value = false
  selectedDelivery.value = null
}

const deliveryData = computed(() => {
  if (!store.todaySummary) {
    return {
      total_deliveries: 0,
      completed: 0,
      in_progress: 0,
      failed: 0,
    }
  }

  return {
    total_deliveries: store.todaySummary.total_deliveries || 0,
    completed: store.todaySummary.completed || 0,
    in_progress: store.todaySummary.in_progress || 0,
    failed: store.todaySummary.failed || 0,
  }
})

const filteredDeliveries = computed(() => {
  let list = store.deliveries || []

  if (selectedStatus.value !== '') {
    list = list.filter((d) => (d.status || '').toLowerCase() === selectedStatus.value.toLowerCase())
  }

  if (selectedFloor.value !== '') {
    list = list.filter((d) => String(d.floor || d.room?.floor || '') === selectedFloor.value)
  }

  if (searchQuery.value.trim()) {
    const q = searchQuery.value.toLowerCase().trim()
    list = list.filter((d) => {
      const roomStr = getRoomNumber(d).toLowerCase()
      const orderId = String(d.order_id || d.order?.id || '').toLowerCase()
      const waiterName = (d.waiter?.name || d.waiter?.full_name || '').toLowerCase()
      return roomStr.includes(q) || orderId.includes(q) || waiterName.includes(q)
    })
  }

  return list
})

const total = computed(() => filteredDeliveries.value.length)
const totalPages = computed(() => Math.ceil(total.value / store.perPage) || 1)

const paginatedDeliveries = computed(() => {
  const start = (store.currentPage - 1) * store.perPage
  const end = start + store.perPage
  return filteredDeliveries.value.slice(start, end)
})

const showingFrom = computed(() => {
  if (total.value === 0) return 0
  return (store.currentPage - 1) * store.perPage + 1
})

const showingTo = computed(() => {
  return Math.min(store.currentPage * store.perPage, total.value)
})

const paginationPages = computed(() => {
  const pages: number[] = []
  const max = totalPages.value
  const cur = store.currentPage

  for (let i = Math.max(1, cur - 2); i <= Math.min(max, cur + 2); i++) {
    pages.push(i)
  }
  return pages
})

onMounted(async () => {
  isLoading.value = true
  try {
    await Promise.all([store.fetchTodaySummary(), store.fetchDeliveries()])
  } catch (error) {
    console.error('Failed to load delivery data:', error)
  } finally {
    isLoading.value = false
  }
})

const generateReport = async () => {
  isLoading.value = true
  try {
    await store.fetchDeliveryReport()
    alert('Report generated successfully!')
  } catch (error) {
    console.error('Failed to generate report:', error)
    alert('Failed to generate report')
  } finally {
    isLoading.value = false
  }
}

const changePageSize = (event: Event) => {
  const target = event.target as HTMLSelectElement
  store.perPage = Number(target.value)
  store.currentPage = 1
}

const goToPreviousPage = () => {
  if (store.currentPage > 1) {
    store.currentPage--
  }
}

const goToPage = (page: number) => {
  if (page >= 1 && page <= totalPages.value) {
    store.currentPage = page
  }
}

const goToNextPage = () => {
  if (store.currentPage < totalPages.value) {
    store.currentPage++
  }
}

const resetFilters = () => {
  searchQuery.value = ''
  selectedStatus.value = ''
  selectedFloor.value = ''
  store.currentPage = 1
}

const toggleFilter = () => {
  isFilterOpen.value = !isFilterOpen.value
}

const toggleFullscreen = () => {
  isFullscreen.value = !isFullscreen.value
}

const refresh = async () => {
  isLoading.value = true
  try {
    await Promise.all([store.fetchTodaySummary(), store.fetchDeliveries()])
  } finally {
    isLoading.value = false
  }
}

const getRoomNumber = (delivery: any): string => {
  if (!delivery) return 'Table / Walk-in'
  if (delivery.room?.room_number) return `Room ${delivery.room.room_number}`
  if (delivery.room_number) return `Room ${delivery.room_number}`
  if (delivery.order?.room_number) return `Room ${delivery.order.room_number}`
  if (delivery.order?.room?.room_number) return `Room ${delivery.order.room.room_number}`
  if (delivery.order_id) return `Order #${String(delivery.order_id).substring(0, 6)}`
  return 'Walk-in / Table'
}

const getStatusBadgeClass = (status: string) => {
  switch ((status || '').toLowerCase()) {
    case 'delivered':
      return 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/20 font-bold'
    case 'on_delivery':
    case 'in_transit':
      return 'bg-blue-500/10 text-blue-600 dark:text-blue-400 border-blue-500/20 font-bold'
    case 'assigned':
    case 'accepted':
    case 'picked_up':
      return 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border-amber-500/20 font-bold'
    case 'cancelled':
    case 'failed':
      return 'bg-rose-500/10 text-rose-600 dark:text-rose-400 border-rose-500/20 font-bold'
    default:
      return 'bg-slate-500/10 text-slate-600 dark:text-slate-400 border-slate-500/20 font-bold'
  }
}
</script>

<template>
  <DashboardLayout>
    <div
      class="space-y-6 bg-slate-50 dark:bg-slate-950 min-h-screen p-4 sm:p-6 max-w-full overflow-hidden font-sans"
      :class="{ 'fixed inset-0 z-50 p-6 overflow-y-auto bg-white dark:bg-slate-950': isFullscreen }"
    >
      <!-- Header -->
      <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-5 sm:p-6 rounded-2xl shadow-xs flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-600 to-blue-700 flex items-center justify-center shadow-md flex-shrink-0 text-white">
            <Truck class="w-5 h-5 stroke-[2.2]" />
          </div>
          <div>
            <h1 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white">
              {{ languageStore.t('room_service_deliveries', 'Room Service Deliveries') }}
            </h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
              {{ languageStore.t('room_service_deliveries_desc', 'Track and manage all active, in-transit, and completed room service deliveries.') }}
            </p>
          </div>
        </div>

        <button
          @click="generateReport"
          :disabled="isLoading"
          class="px-4 py-2.5 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 text-xs font-bold transition flex items-center justify-center gap-2 cursor-pointer border border-slate-200 dark:border-slate-700 disabled:opacity-50"
        >
          <Download class="w-4 h-4" />
          <span>{{ languageStore.t('generate_report', 'Generate Report') }}</span>
        </button>
      </div>

      <!-- Statistics Summary Cards -->
      <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <!-- Total Deliveries -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-xs flex items-center justify-between">
          <div>
            <p class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
              {{ languageStore.t('total_deliveries', 'Total Deliveries') }}
            </p>
            <h3 class="text-2xl font-black text-slate-900 dark:text-white mt-1">{{ deliveryData.total_deliveries }}</h3>
          </div>
          <div class="p-3 rounded-2xl bg-blue-500/10 text-blue-600 dark:text-blue-400">
            <Truck class="w-5 h-5" />
          </div>
        </div>

        <!-- Completed -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-xs flex items-center justify-between">
          <div>
            <p class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
              {{ languageStore.t('completed', 'Completed') }}
            </p>
            <h3 class="text-2xl font-black text-emerald-600 dark:text-emerald-400 mt-1">{{ deliveryData.completed }}</h3>
          </div>
          <div class="p-3 rounded-2xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400">
            <CheckCircle2 class="w-5 h-5" />
          </div>
        </div>

        <!-- In Progress -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-xs flex items-center justify-between">
          <div>
            <p class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
              {{ languageStore.t('in_progress', 'In Progress') }}
            </p>
            <h3 class="text-2xl font-black text-amber-600 dark:text-amber-400 mt-1">{{ deliveryData.in_progress }}</h3>
          </div>
          <div class="p-3 rounded-2xl bg-amber-500/10 text-amber-600 dark:text-amber-400">
            <Clock class="w-5 h-5" />
          </div>
        </div>

        <!-- Failed / Cancelled -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-xs flex items-center justify-between">
          <div>
            <p class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
              {{ languageStore.t('failed_issues', 'Failed / Issues') }}
            </p>
            <h3 class="text-2xl font-black text-rose-600 dark:text-rose-400 mt-1">{{ deliveryData.failed }}</h3>
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
            <Search class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 dark:text-slate-500" />
            <input
              v-model="searchQuery"
              type="text"
              :placeholder="languageStore.t('search_deliveries_placeholder', 'Search deliveries by room, order ID, or waiter...')"
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
            <span>{{ isFilterOpen ? languageStore.t('hide_filters', 'Hide Filter') : languageStore.t('filter', 'Filter') }}</span>
          </button>
        </div>

        <!-- Right: Action Buttons -->
        <div class="flex items-center gap-2 sm:gap-2.5">
          <!-- Refresh Button -->
          <button
            type="button"
            @click="refresh"
            :disabled="isLoading"
            :title="languageStore.t('refresh', 'Refresh')"
            class="inline-flex items-center justify-center h-9 w-9 sm:h-10 sm:w-10 rounded-xl border border-slate-200 dark:border-[#1e3455] bg-white dark:bg-[#13233c]/80 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-[#1c3356] hover:text-slate-900 dark:hover:text-white transition disabled:opacity-50 cursor-pointer"
          >
            <RefreshCw class="w-4 h-4" :class="{ 'animate-spin': isLoading }" />
          </button>

          <!-- Fullscreen Toggle -->
          <button
            type="button"
            @click="toggleFullscreen"
            :title="languageStore.t('toggle_fullscreen', 'Toggle Fullscreen')"
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
            <!-- Status Filter -->
            <div>
              <label class="mb-1.5 block text-xs font-semibold text-slate-600 dark:text-slate-300">
                {{ languageStore.t('delivery_status', 'Delivery Status') }}
              </label>
              <select
                v-model="selectedStatus"
                class="w-full rounded-xl border border-slate-200 dark:border-[#1e3455] bg-slate-50/80 dark:bg-[#13233c] text-slate-900 dark:text-white px-3.5 py-2 text-xs sm:text-sm focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 dark:focus:border-blue-400 transition cursor-pointer font-medium outline-none"
              >
                <option value="">{{ languageStore.t('all_statuses', 'All Statuses') }}</option>
                <option value="delivered">{{ languageStore.t('delivered', 'Delivered') }}</option>
                <option value="on_delivery">{{ languageStore.t('in_transit_on_delivery', 'In Transit / On Delivery') }}</option>
                <option value="assigned">{{ languageStore.t('assigned_picked_up', 'Assigned / Picked Up') }}</option>
                <option value="failed">{{ languageStore.t('failed_cancelled', 'Failed / Cancelled') }}</option>
              </select>
            </div>

            <!-- Floor Filter -->
            <div>
              <label class="mb-1.5 block text-xs font-semibold text-slate-600 dark:text-slate-300">
                {{ languageStore.t('floor', 'Floor') }}
              </label>
              <select
                v-model="selectedFloor"
                class="w-full rounded-xl border border-slate-200 dark:border-[#1e3455] bg-slate-50/80 dark:bg-[#13233c] text-slate-900 dark:text-white px-3.5 py-2 text-xs sm:text-sm focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 dark:focus:border-blue-400 transition cursor-pointer font-medium outline-none"
              >
                <option value="">{{ languageStore.t('all_floors', 'All Floors') }}</option>
                <option value="1">{{ languageStore.t('floor', 'Floor') }} 1</option>
                <option value="2">{{ languageStore.t('floor', 'Floor') }} 2</option>
                <option value="3">{{ languageStore.t('floor', 'Floor') }} 3</option>
                <option value="4">{{ languageStore.t('floor', 'Floor') }} 4</option>
                <option value="5">{{ languageStore.t('floor', 'Floor') }} 5</option>
              </select>
            </div>

            <!-- Reset Filters -->
            <div class="flex items-end">
              <button
                type="button"
                @click="resetFilters"
                class="w-full inline-flex items-center justify-center gap-1.5 rounded-xl border border-slate-200 dark:border-[#1e3455] bg-slate-100/70 dark:bg-[#13233c] px-3.5 py-2 text-xs font-semibold text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-[#1c3356] transition cursor-pointer"
              >
                <RotateCcw class="w-3.5 h-3.5" />
                <span>{{ languageStore.t('reset_filters', 'Reset Filters') }}</span>
              </button>
            </div>
          </div>
        </div>
      </Transition>

      <!-- Deliveries Data Table Container -->
      <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-xs overflow-hidden font-sans w-full">
        <!-- Desktop Table View -->
        <div class="hidden md:block overflow-x-auto w-full">
          <table class="w-full text-left border-collapse">
            <thead class="bg-slate-50/90 dark:bg-[#0c182c] border-b border-slate-200 dark:border-[#1e3455]">
              <tr class="text-[11px] font-bold text-slate-500 dark:text-slate-400 select-none">
                <th class="py-3 px-4 pl-5 whitespace-nowrap">{{ languageStore.t('room_location', 'Room / Location') }}</th>
                <th class="py-3 px-4 whitespace-nowrap">{{ languageStore.t('order_ref', 'Order Ref') }}</th>
                <th class="py-3 px-4 whitespace-nowrap">{{ languageStore.t('assigned_waiter', 'Assigned Waiter') }}</th>
                <th class="py-3 px-4 text-center whitespace-nowrap">{{ languageStore.t('floor', 'Floor') }}</th>
                <th class="py-3 px-4 text-center whitespace-nowrap">{{ languageStore.t('status', 'Status') }}</th>
                <th class="py-3 px-4 text-right pr-5 whitespace-nowrap">{{ languageStore.t('actions', 'Actions') }}</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-[#1e3455]/60 text-xs">
              <tr
                v-for="delivery in paginatedDeliveries"
                :key="delivery.id"
                class="hover:bg-slate-50/80 dark:hover:bg-[#13233c]/60 transition-colors duration-150 group"
              >
                <!-- Room -->
                <td class="py-3 px-4 pl-5 whitespace-nowrap">
                  <div class="font-extrabold text-slate-900 dark:text-white text-xs sm:text-sm">
                    {{ getRoomNumber(delivery) }}
                  </div>
                </td>

                <!-- Order ID -->
                <td class="py-3 px-4 whitespace-nowrap font-mono font-extrabold text-blue-600 dark:text-blue-400 text-xs">
                  #{{ delivery.order_id || delivery.order?.order_number || delivery.id }}
                </td>

                <!-- Waiter -->
                <td class="py-3 px-4 whitespace-nowrap font-medium text-slate-900 dark:text-white">
                  {{ delivery.waiter?.name || delivery.waiter?.full_name || languageStore.t('unassigned', 'Unassigned') }}
                </td>

                <!-- Floor -->
                <td class="py-3 px-4 text-center whitespace-nowrap">
                  <span class="font-semibold text-slate-600 dark:text-slate-400">
                    {{ languageStore.t('floor', 'Floor') }} {{ delivery.floor || delivery.room?.floor || 1 }}
                  </span>
                </td>

                <!-- Status -->
                <td class="py-3 px-4 text-center whitespace-nowrap">
                  <span
                    class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] border uppercase tracking-wider"
                    :class="getStatusBadgeClass(delivery.status)"
                  >
                    {{ (delivery.status || 'Pending').replace('_', ' ') }}
                  </span>
                </td>

                <!-- Actions -->
                <td class="py-3 px-4 text-right pr-5 whitespace-nowrap">
                  <button
                    @click="openDetailsModal(delivery)"
                    class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-bold text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg transition cursor-pointer"
                  >
                    <Eye class="w-3.5 h-3.5 text-blue-500" />
                    <span>{{ languageStore.t('view', 'View') }}</span>
                  </button>
                </td>
              </tr>

              <!-- Empty State -->
              <tr v-if="paginatedDeliveries.length === 0">
                <td colspan="6" class="px-6 py-12 text-center text-slate-500 dark:text-slate-400 text-xs font-bold">
                  {{ languageStore.t('no_deliveries_found', 'No deliveries found matching your search or filter.') }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Mobile View -->
        <div class="md:hidden divide-y divide-slate-100 dark:divide-slate-800">
          <div
            v-for="delivery in paginatedDeliveries"
            :key="delivery.id"
            class="p-4 space-y-3 hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition"
          >
            <div class="flex items-center justify-between">
              <span class="font-bold text-slate-900 dark:text-white text-sm">
                {{ getRoomNumber(delivery) }}
              </span>
              <span
                class="px-2 py-0.5 rounded-full text-[10px] font-bold border uppercase"
                :class="getStatusBadgeClass(delivery.status)"
              >
                {{ (delivery.status || 'Pending').replace('_', ' ') }}
              </span>
            </div>
            <div class="flex items-center justify-between text-xs text-slate-600 dark:text-slate-400">
              <span>{{ languageStore.t('waiter', 'Waiter') }}: {{ delivery.waiter?.name || languageStore.t('unassigned', 'Unassigned') }}</span>
              <button
                @click="openDetailsModal(delivery)"
                class="px-2 py-1 text-xs font-bold text-blue-600 bg-blue-50 dark:bg-blue-950/40 rounded-lg cursor-pointer"
              >
                {{ languageStore.t('details', 'Details') }}
              </button>
            </div>
          </div>
        </div>

        <!-- Pagination Footer -->
        <div
          v-if="total > 0"
          class="border-t border-slate-200 dark:border-slate-800/80 px-4 sm:px-6 py-3 sm:py-4 bg-slate-50/50 dark:bg-[#0c182c] flex flex-col sm:flex-row items-center justify-between gap-3 text-xs"
        >
          <div class="text-slate-500 dark:text-slate-400 font-medium">
            {{ languageStore.t('showing', 'Showing') }} <span class="font-bold text-slate-900 dark:text-white">{{ showingFrom }}</span> {{ languageStore.t('to', 'to') }}
            <span class="font-bold text-slate-900 dark:text-white">{{ showingTo }}</span> {{ languageStore.t('of', 'of') }}
            <span class="font-bold text-slate-900 dark:text-white">{{ total }}</span> {{ languageStore.t('tasks', 'tasks') }}
          </div>

          <div class="flex items-center gap-2 sm:gap-3">
            <div class="flex items-center gap-1.5">
              <span class="text-slate-500 dark:text-slate-400 font-medium">{{ languageStore.t('per_page', 'Per page') }}:</span>
              <select
                :value="store.perPage"
                @change="changePageSize"
                class="rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-[#13233c] text-slate-900 dark:text-white px-2 py-1 text-xs outline-none"
              >
                <option :value="10">10</option>
                <option :value="20">20</option>
                <option :value="50">50</option>
              </select>
            </div>

            <div class="flex items-center gap-1">
              <button
                @click="goToPreviousPage"
                :disabled="store.currentPage === 1"
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
                  store.currentPage === page
                    ? 'bg-blue-600 text-white'
                    : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800'
                ]"
              >
                {{ page }}
              </button>

              <button
                @click="goToNextPage"
                :disabled="store.currentPage === totalPages"
                class="p-1 rounded-lg border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 disabled:opacity-40 transition cursor-pointer"
              >
                <ChevronRight class="w-4 h-4" />
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Details Modal -->
      <div
        v-if="showDetailsModal"
        class="fixed inset-0 z-50 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4"
        @click.self="closeDetailsModal"
      >
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl w-full max-w-lg overflow-hidden shadow-2xl p-6 space-y-4">
          <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
            <h3 class="text-base font-extrabold text-slate-900 dark:text-white">
              {{ languageStore.t('delivery_task_details', 'Delivery Task Details') }}
            </h3>
            <button @click="closeDetailsModal" class="p-1 rounded-lg text-slate-400 hover:text-slate-600 cursor-pointer">
              <X class="w-4 h-4" />
            </button>
          </div>

          <div v-if="selectedDelivery" class="space-y-3 text-xs">
            <div class="flex justify-between py-1.5 border-b border-slate-100 dark:border-slate-800">
              <span class="text-slate-500">{{ languageStore.t('destination', 'Destination') }}:</span>
              <span class="font-bold text-slate-900 dark:text-white">{{ getRoomNumber(selectedDelivery) }}</span>
            </div>
            <div class="flex justify-between py-1.5 border-b border-slate-100 dark:border-slate-800">
              <span class="text-slate-500">{{ languageStore.t('order_reference', 'Order Reference') }}:</span>
              <span class="font-bold text-slate-900 dark:text-white">#{{ selectedDelivery.order_id || selectedDelivery.id }}</span>
            </div>
            <div class="flex justify-between py-1.5 border-b border-slate-100 dark:border-slate-800">
              <span class="text-slate-500">{{ languageStore.t('assigned_waiter', 'Assigned Waiter') }}:</span>
              <span class="font-bold text-slate-900 dark:text-white">{{ selectedDelivery.waiter?.name || languageStore.t('unassigned', 'Unassigned') }}</span>
            </div>
            <div class="flex justify-between py-1.5 border-b border-slate-100 dark:border-slate-800">
              <span class="text-slate-500">{{ languageStore.t('status', 'Status') }}:</span>
              <span class="font-bold uppercase" :class="getStatusBadgeClass(selectedDelivery.status)">{{ selectedDelivery.status }}</span>
            </div>
          </div>

          <button
            @click="closeDetailsModal"
            class="w-full py-2 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 font-bold rounded-xl text-xs transition cursor-pointer"
          >
            {{ languageStore.t('close', 'Close') }}
          </button>
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>
