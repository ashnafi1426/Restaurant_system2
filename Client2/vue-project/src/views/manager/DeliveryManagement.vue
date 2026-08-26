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
  ChevronLeft,
  ChevronRight,
  Loader2,
  X,
  Eye,
  User,
  Hotel,
  Calendar,
  FileText
} from 'lucide-vue-next'
import { useDeliveryManagementStore } from '@/stores/manager/deliveryManagementStore'

const store = useDeliveryManagementStore()
const isLoading = ref(false)

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

// Computed properties for summary stats
const deliveryData = computed(() => {
  if (!store.todaySummary) {
    return {
      total_deliveries: 0,
      completed: 0,
      in_progress: 0,
      failed: 0
    }
  }

  return {
    total_deliveries: store.todaySummary.total_deliveries || 0,
    completed: store.todaySummary.completed || 0,
    in_progress: store.todaySummary.in_progress || 0,
    failed: store.todaySummary.failed || 0
  }
})

// Calculate total pages
const totalPages = computed(() => Math.ceil(store.totalDeliveries / store.perPage) || 1)

const paginatedDeliveries = computed(() => {
  const all = store.deliveries || []
  if (all.length > store.perPage) {
    const start = (store.currentPage - 1) * store.perPage
    return all.slice(start, start + store.perPage)
  }
  return all
})

const showingFrom = computed(() => {
  if (store.totalDeliveries === 0) return 0
  return (store.currentPage - 1) * store.perPage + 1
})

const showingTo = computed(() => {
  return Math.min(store.currentPage * store.perPage, store.totalDeliveries)
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
    await Promise.all([
      store.fetchTodaySummary(),
      store.fetchDeliveries()
    ])
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

const changePageSize = async (event: Event) => {
  const target = event.target as HTMLSelectElement
  const size = Number(target.value)
  store.perPage = size
  await store.fetchDeliveries(1)
}

const goToPreviousPage = async () => {
  if (store.currentPage > 1) {
    await store.fetchDeliveries(store.currentPage - 1)
  }
}

const goToPage = async (page: number) => {
  if (page >= 1 && page <= totalPages.value) {
    await store.fetchDeliveries(page)
  }
}

const goToNextPage = async () => {
  if (store.currentPage < totalPages.value) {
    await store.fetchDeliveries(store.currentPage + 1)
  }
}

const getRoomNumber = (delivery: any): string => {
  if (!delivery) return 'Table / Walk-in'
  if (delivery.room?.room_number) return `Room ${delivery.room.room_number}`
  if (delivery.room_number) return `Room ${delivery.room_number}`
  if (delivery.order?.room_number) return `Room ${delivery.order.room_number}`
  if (delivery.order?.room?.room_number) return `Room ${delivery.order.room.room_number}`
  if (delivery.order_id) return `Order #${delivery.order_id.substring(0, 6)}`
  return 'Walk-in / Table'
}

const getStatusBadgeClass = (status: string) => {
  switch ((status || '').toLowerCase()) {
    case 'delivered':
      return 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/20 font-black'
    case 'on_delivery':
    case 'in_transit':
      return 'bg-blue-500/10 text-blue-600 dark:text-blue-400 border-blue-500/20 font-black'
    case 'assigned':
    case 'accepted':
    case 'picked_up':
      return 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border-amber-500/20 font-black'
    case 'cancelled':
    case 'failed':
      return 'bg-rose-500/10 text-rose-600 dark:text-rose-400 border-rose-500/20 font-black'
    default:
      return 'bg-slate-500/10 text-slate-600 dark:text-slate-400 border-slate-500/20 font-black'
  }
}
</script>

<template>
  <DashboardLayout>
    <div class="space-y-6 bg-slate-50 dark:bg-slate-950 min-h-screen p-4 sm:p-6 max-w-full overflow-hidden font-sans">
      <!-- Header Banner -->
      <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-6 rounded-3xl shadow-xs flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">Room Service Deliveries</h1>
          <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Track and manage all active, in-transit, and completed room service deliveries.</p>
        </div>

        <button
          @click="generateReport"
          :disabled="isLoading"
          class="px-4 py-2.5 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 text-xs font-extrabold transition flex items-center justify-center gap-2 cursor-pointer border border-slate-200 dark:border-slate-700 disabled:opacity-50"
        >
          <Download class="w-4 h-4" />
          <span>Generate Report</span>
        </button>
      </div>

      <!-- Statistics Summary Cards -->
      <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <!-- Total Deliveries -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-5 shadow-xs flex items-center justify-between">
          <div>
            <p class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Total Deliveries</p>
            <h3 class="text-2xl font-black text-slate-900 dark:text-white mt-1">{{ deliveryData.total_deliveries }}</h3>
            <p class="text-[10px] text-slate-400 font-bold mt-1">Today</p>
          </div>
          <div class="p-3 rounded-2xl bg-blue-500/10 text-blue-600 dark:text-blue-400">
            <Truck class="w-5 h-5" />
          </div>
        </div>

        <!-- Completed -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-5 shadow-xs flex items-center justify-between">
          <div>
            <p class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Completed</p>
            <h3 class="text-2xl font-black text-emerald-600 dark:text-emerald-400 mt-1">{{ deliveryData.completed }}</h3>
            <p class="text-[10px] text-emerald-600 dark:text-emerald-400 font-bold mt-1">Delivered</p>
          </div>
          <div class="p-3 rounded-2xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400">
            <CheckCircle2 class="w-5 h-5" />
          </div>
        </div>

        <!-- In Progress -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-5 shadow-xs flex items-center justify-between">
          <div>
            <p class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">In Progress</p>
            <h3 class="text-2xl font-black text-amber-600 dark:text-amber-400 mt-1">{{ deliveryData.in_progress }}</h3>
            <p class="text-[10px] text-amber-600 dark:text-amber-400 font-bold mt-1">Active</p>
          </div>
          <div class="p-3 rounded-2xl bg-amber-500/10 text-amber-600 dark:text-amber-400">
            <Clock class="w-5 h-5" />
          </div>
        </div>

        <!-- Failed / Cancelled -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-5 shadow-xs flex items-center justify-between">
          <div>
            <p class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Failed / Cancelled</p>
            <h3 class="text-2xl font-black text-rose-600 dark:text-rose-400 mt-1">{{ deliveryData.failed }}</h3>
            <p class="text-[10px] text-rose-600 dark:text-rose-400 font-bold mt-1">Issues</p>
          </div>
          <div class="p-3 rounded-2xl bg-rose-500/10 text-rose-600 dark:text-rose-400">
            <AlertCircle class="w-5 h-5" />
          </div>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="isLoading" class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 p-16 text-center space-y-3">
        <Loader2 class="w-8 h-8 text-amber-500 animate-spin mx-auto" />
        <p class="text-xs font-bold text-slate-500 dark:text-slate-400">Loading delivery data...</p>
      </div>

      <!-- Error State -->
      <div v-else-if="store.error" class="p-4 rounded-2xl bg-rose-500/10 border border-rose-500/30 text-rose-700 dark:text-rose-300 text-xs font-bold flex items-center gap-2.5">
        <AlertCircle class="w-4 h-4 text-rose-500 flex-shrink-0" />
        <span>Error: {{ store.error }}</span>
      </div>

      <!-- Deliveries Data Table Container -->
      <div v-else class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl overflow-hidden shadow-xs">
        <!-- Table Header Title Bar -->
        <div class="p-5 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between bg-slate-50/50 dark:bg-slate-950/50">
          <div class="flex items-center gap-2.5">
            <Truck class="w-5 h-5 text-blue-500" />
            <h2 class="text-base font-extrabold text-slate-900 dark:text-white">All Deliveries</h2>
          </div>
          <span class="px-3 py-1 bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-xs font-black rounded-full border border-slate-300/60 dark:border-slate-700">
            {{ store.totalDeliveries }} Total Tasks
          </span>
        </div>
        
        <div v-if="store.deliveries.length === 0" class="p-12 text-center text-slate-500 dark:text-slate-400 text-xs font-bold">
          No deliveries found for today.
        </div>
        
        <div v-else class="overflow-x-auto w-full">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="bg-slate-50 dark:bg-slate-950/80 border-b border-slate-200 dark:border-slate-800 text-[10px] font-black uppercase text-slate-500 dark:text-slate-400 tracking-wider">
                <th class="px-3.5 py-3 whitespace-nowrap">Room #</th>
                <th class="px-3.5 py-3 whitespace-nowrap">Order ID</th>
                <th class="px-3.5 py-3 whitespace-nowrap">Waiter</th>
                <th class="px-3.5 py-3 whitespace-nowrap">Floor</th>
                <th class="px-3.5 py-3 text-center whitespace-nowrap">Type</th>
                <th class="px-3.5 py-3 text-center whitespace-nowrap">Status</th>
                <th class="px-3.5 py-3 whitespace-nowrap">Assigned</th>
                <th class="px-3.5 py-3 text-right whitespace-nowrap pr-6">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 text-xs">
              <tr
                v-for="delivery in paginatedDeliveries"
                :key="delivery.id"
                class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition"
              >
                <!-- Room Number -->
                <td class="px-3.5 py-3 whitespace-nowrap">
                  <div class="font-extrabold text-slate-900 dark:text-white text-xs sm:text-sm">
                    {{ getRoomNumber(delivery) }}
                  </div>
                </td>
                
                <!-- Order ID -->
                <td class="px-3.5 py-3 whitespace-nowrap font-mono text-[11px] font-bold text-slate-600 dark:text-slate-400">
                  #{{ (delivery.order_id || '').substring(0, 8) || 'N/A' }}
                </td>
                
                <!-- Waiter Name -->
                <td class="px-3.5 py-3 whitespace-nowrap">
                  <div class="flex items-center gap-2">
                    <div class="w-6 h-6 rounded-full bg-blue-500/10 text-blue-600 dark:text-blue-400 border border-blue-500/20 font-black text-[10px] flex items-center justify-center flex-shrink-0">
                      {{ ((delivery.waiter?.user?.name || delivery.waiter?.name || 'W')[0] || 'W').toUpperCase() }}
                    </div>
                    <span class="font-bold text-slate-900 dark:text-white text-xs">
                      {{ delivery.waiter?.user?.name || delivery.waiter?.name || 'Unassigned' }}
                    </span>
                  </div>
                </td>
                
                <!-- Floor -->
                <td class="px-3.5 py-3 whitespace-nowrap text-slate-700 dark:text-slate-300 font-semibold text-xs">
                  {{ delivery.floor?.name || (delivery.floor?.floor_number !== undefined ? 'Floor #' + delivery.floor.floor_number : 'N/A') }}
                </td>
                
                <!-- Assignment Type -->
                <td class="px-3.5 py-3 text-center whitespace-nowrap">
                  <span 
                    :class="[
                      'px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider border',
                      delivery.assignment_type === 'automatic'
                        ? 'bg-blue-500/10 text-blue-600 dark:text-blue-400 border-blue-500/20'
                        : 'bg-purple-500/10 text-purple-600 dark:text-purple-400 border-purple-500/20'
                    ]"
                  >
                    {{ delivery.assignment_type || 'N/A' }}
                  </span>
                </td>
                
                <!-- Status Badge -->
                <td class="px-3.5 py-3 text-center whitespace-nowrap">
                  <span 
                    :class="[
                      'inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider border',
                      getStatusBadgeClass(delivery.status)
                    ]"
                  >
                    <span class="w-1.5 h-1.5 rounded-full bg-current"></span>
                    {{ (delivery.status || 'N/A').replace(/_/g, ' ') }}
                  </span>
                </td>
                
                <!-- Assigned Time -->
                <td class="px-3.5 py-3 whitespace-nowrap text-[11px] font-bold text-slate-500 dark:text-slate-400">
                  {{ delivery.assigned_at ? new Date(delivery.assigned_at).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' }) : '-' }}
                </td>
                
                <!-- Actions -->
                <td class="px-3.5 py-3 text-right whitespace-nowrap pr-6">
                  <button
                    @click="openDetailsModal(delivery)"
                    class="px-3 py-1.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs shadow-md shadow-blue-600/20 transition cursor-pointer flex items-center gap-1 ml-auto"
                    title="View Details"
                  >
                    <Eye class="w-3.5 h-3.5" />
                    <span>View</span>
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination Bar with 5, 10, 20, 50 Options -->
        <div
          v-if="store.totalDeliveries > 0"
          class="flex flex-col sm:flex-row items-center justify-between gap-4 border-t border-slate-200 dark:border-slate-800 bg-slate-50/80 dark:bg-slate-950 p-4 text-xs font-sans"
        >
          <!-- Left Side: Per Page Selector & Showing Count -->
          <div class="flex flex-wrap items-center gap-4 text-slate-600 dark:text-slate-400">
            <div class="flex items-center gap-2">
              <span class="font-bold text-slate-700 dark:text-slate-300">Items per page:</span>
              <select
                :value="store.perPage"
                @change="changePageSize"
                class="px-2.5 py-1.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-900 dark:text-white font-black focus:outline-none focus:border-amber-500 cursor-pointer shadow-2xs"
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
              <span class="font-extrabold text-slate-900 dark:text-white">{{ store.totalDeliveries }}</span> deliveries
            </div>
          </div>

          <!-- Right Side: Page Controls -->
          <div class="flex items-center gap-1.5">
            <button
              @click="goToPreviousPage"
              :disabled="store.currentPage <= 1"
              class="p-2 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 disabled:opacity-40 disabled:cursor-not-allowed transition cursor-pointer flex items-center gap-1 font-bold"
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
                  store.currentPage === p
                    ? 'bg-amber-500 border-amber-500 text-slate-950 font-black shadow-md shadow-amber-500/20'
                    : 'bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800'
                ]"
              >
                {{ p }}
              </button>
            </div>

            <button
              @click="goToNextPage"
              :disabled="store.currentPage >= totalPages"
              class="p-2 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 disabled:opacity-40 disabled:cursor-not-allowed transition cursor-pointer flex items-center gap-1 font-bold"
              title="Next Page"
            >
              <span class="hidden sm:inline">Next</span>
              <ChevronRight class="w-4 h-4" />
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Delivery Task Details Modal -->
    <div
      v-if="showDetailsModal && selectedDelivery"
      class="fixed inset-0 bg-slate-950/70 backdrop-blur-xs flex items-center justify-center p-4 z-50 font-sans"
    >
      <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 max-w-lg w-full shadow-2xl space-y-6 relative animate-in fade-in zoom-in duration-150">
        <!-- Modal Header -->
        <div class="flex items-center justify-between pb-4 border-b border-slate-100 dark:border-slate-800">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-2xl bg-blue-500/10 text-blue-600 dark:text-blue-400 border border-blue-500/20 flex items-center justify-center font-black">
              <Truck class="w-5 h-5" />
            </div>
            <div>
              <h3 class="font-extrabold text-base text-slate-900 dark:text-white">Delivery Task Details</h3>
              <p class="text-[11px] font-mono text-slate-400">Task #{{ (selectedDelivery.id || '').substring(0, 8) }}</p>
            </div>
          </div>

          <button
            @click="closeDetailsModal"
            class="p-2 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-500 dark:text-slate-400 transition cursor-pointer"
          >
            <X class="w-4 h-4" />
          </button>
        </div>

        <!-- Details Grid -->
        <div class="grid grid-cols-2 gap-3 text-xs">
          <!-- Room -->
          <div class="p-3 rounded-2xl bg-slate-50 dark:bg-slate-950 border border-slate-200/60 dark:border-slate-800">
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1 flex items-center gap-1">
              <Hotel class="w-3 h-3 text-blue-500" />
              Location / Room
            </p>
            <p class="font-black text-slate-900 dark:text-white text-sm">
              {{ getRoomNumber(selectedDelivery) }}
            </p>
          </div>

          <!-- Status -->
          <div class="p-3 rounded-2xl bg-slate-50 dark:bg-slate-950 border border-slate-200/60 dark:border-slate-800">
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1 flex items-center gap-1">
              <CheckCircle2 class="w-3 h-3 text-emerald-500" />
              Delivery Status
            </p>
            <span
              :class="[
                'inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase border mt-0.5',
                getStatusBadgeClass(selectedDelivery.status)
              ]"
            >
              {{ (selectedDelivery.status || 'N/A').replace(/_/g, ' ') }}
            </span>
          </div>

          <!-- Waiter -->
          <div class="p-3 rounded-2xl bg-slate-50 dark:bg-slate-950 border border-slate-200/60 dark:border-slate-800">
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1 flex items-center gap-1">
              <User class="w-3 h-3 text-amber-500" />
              Assigned Waiter
            </p>
            <p class="font-extrabold text-slate-900 dark:text-white">
              {{ selectedDelivery.waiter?.user?.name || selectedDelivery.waiter?.name || 'Unassigned' }}
            </p>
          </div>

          <!-- Order ID -->
          <div class="p-3 rounded-2xl bg-slate-50 dark:bg-slate-950 border border-slate-200/60 dark:border-slate-800">
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1 flex items-center gap-1">
              <FileText class="w-3 h-3 text-purple-500" />
              Order Reference
            </p>
            <p class="font-mono font-bold text-slate-900 dark:text-white">
              #{{ (selectedDelivery.order_id || '').substring(0, 8) || 'N/A' }}
            </p>
          </div>
        </div>

        <!-- Timestamps List -->
        <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-950 border border-slate-200/60 dark:border-slate-800 space-y-2 text-xs">
          <p class="text-[10px] font-black text-slate-400 uppercase tracking-wider mb-2 flex items-center gap-1">
            <Calendar class="w-3 h-3 text-blue-500" />
            Task Timeline
          </p>

          <div class="flex justify-between py-1 border-b border-slate-200/40 dark:border-slate-800/60">
            <span class="text-slate-500">Assigned At:</span>
            <span class="font-bold text-slate-900 dark:text-white">
              {{ selectedDelivery.assigned_at ? new Date(selectedDelivery.assigned_at).toLocaleString() : 'N/A' }}
            </span>
          </div>

          <div class="flex justify-between py-1 border-b border-slate-200/40 dark:border-slate-800/60">
            <span class="text-slate-500">Assignment Method:</span>
            <span class="font-extrabold uppercase text-blue-600 dark:text-blue-400">
              {{ selectedDelivery.assignment_type || 'N/A' }}
            </span>
          </div>

          <div v-if="selectedDelivery.remarks" class="pt-1">
            <span class="text-slate-500 block mb-0.5">Remarks:</span>
            <span class="font-medium text-slate-800 dark:text-slate-200 bg-white dark:bg-slate-900 p-2 rounded-xl block border border-slate-200 dark:border-slate-800">
              {{ selectedDelivery.remarks }}
            </span>
          </div>
        </div>

        <!-- Modal Footer -->
        <div class="pt-2 flex justify-end">
          <button
            @click="closeDetailsModal"
            class="px-5 py-2.5 rounded-xl bg-slate-900 dark:bg-slate-800 hover:bg-slate-800 dark:hover:bg-slate-700 text-white font-extrabold text-xs transition cursor-pointer"
          >
            Close Window
          </button>
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>
