<script setup lang="ts">
import { ref, onMounted, onUnmounted, computed } from 'vue'
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import waiterService from '@/services/waiterService'
import {
  Search,
  Filter,
  X,
  RefreshCw,
  Maximize2,
  Minimize2,
  RotateCcw,
  Truck,
  Eye,
  ChevronLeft,
  ChevronRight,
  Loader2,
  BedDouble,
  ShoppingBag,
  Clock,
  CheckCircle2,
} from 'lucide-vue-next'

const loading = ref(true)
const error = ref<string | null>(null)
const assignments = ref<any[]>([])
const currentPage = ref(1)
const itemsPerPage = ref(10)
const selectedOrder = ref<any>(null)
const showDetailModal = ref(false)

const isFilterOpen = ref(false)
const isFullscreen = ref(false)
const searchQuery = ref('')
const selectedStatus = ref('')
const selectedType = ref('all')

const filteredAssignments = computed(() => {
  let list = assignments.value || []

  if (selectedStatus.value !== '') {
    list = list.filter((o) => (o.order_status || o.status || '').toLowerCase() === selectedStatus.value.toLowerCase())
  }

  if (selectedType.value !== 'all') {
    if (selectedType.value === 'room') {
      list = list.filter((o) => Boolean(o.room_number || o.room?.room_number))
    } else if (selectedType.value === 'walk_in') {
      list = list.filter((o) => !o.room_number && !o.room?.room_number)
    }
  }

  if (searchQuery.value.trim()) {
    const q = searchQuery.value.toLowerCase().trim()
    list = list.filter((o) => {
      const ordNum = String(o.order_number || o.order_id || o.id || '').toLowerCase()
      const roomNum = String(o.room_number || o.room?.room_number || '').toLowerCase()
      const guest = String(o.guest_name || o.guest?.full_name || '').toLowerCase()
      return ordNum.includes(q) || roomNum.includes(q) || guest.includes(q)
    })
  }

  return list
})

const total = computed(() => filteredAssignments.value.length)
const totalPages = computed(() => Math.ceil(total.value / itemsPerPage.value) || 1)

const paginatedAssignments = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage.value
  const end = start + itemsPerPage.value
  return filteredAssignments.value.slice(start, end)
})

const showingFrom = computed(() => {
  if (total.value === 0) return 0
  return (currentPage.value - 1) * itemsPerPage.value + 1
})

const showingTo = computed(() => {
  return Math.min(currentPage.value * itemsPerPage.value, total.value)
})

const paginationPages = computed(() => {
  const pages: number[] = []
  const max = totalPages.value
  const cur = currentPage.value

  for (let i = Math.max(1, cur - 2); i <= Math.min(max, cur + 2); i++) {
    pages.push(i)
  }
  return pages
})

const changeItemsPerPage = (event: Event) => {
  const target = event.target as HTMLSelectElement
  itemsPerPage.value = Number(target.value)
  currentPage.value = 1
}

const goToPage = (p: number) => {
  if (p >= 1 && p <= totalPages.value) {
    currentPage.value = p
  }
}

const prevPage = () => {
  if (currentPage.value > 1) {
    currentPage.value--
  }
}

const nextPage = () => {
  if (currentPage.value < totalPages.value) {
    currentPage.value++
  }
}

const resetFilters = () => {
  searchQuery.value = ''
  selectedStatus.value = ''
  selectedType.value = 'all'
  currentPage.value = 1
}

const toggleFilter = () => {
  isFilterOpen.value = !isFilterOpen.value
}

const toggleFullscreen = () => {
  isFullscreen.value = !isFullscreen.value
}

const loadAssignments = async () => {
  try {
    loading.value = true
    error.value = null
    const data = await waiterService.getRecentAssignments(100)
    assignments.value = data || []
    currentPage.value = 1
  } catch (err: any) {
    console.error('[AssignedOrders] Error:', err)
    error.value = err.message || 'Failed to load orders'
  } finally {
    loading.value = false
  }
}

const viewDetails = (order: any) => {
  selectedOrder.value = order
  showDetailModal.value = true
}

const formatDateTime = (dateStr: string) => {
  if (!dateStr) return '-'
  try {
    const d = new Date(dateStr)
    return d.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' })
  } catch {
    return dateStr
  }
}

const getStatusBadge = (status: string) => {
  switch ((status || '').toLowerCase()) {
    case 'assigned':
      return 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border-amber-500/20'
    case 'accepted':
    case 'picked_up':
      return 'bg-blue-500/10 text-blue-600 dark:text-blue-400 border-blue-500/20'
    case 'on_delivery':
      return 'bg-purple-500/10 text-purple-600 dark:text-purple-400 border-purple-500/20'
    case 'delivered':
      return 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/20'
    default:
      return 'bg-slate-500/10 text-slate-600 dark:text-slate-400 border-slate-500/20'
  }
}

onMounted(() => {
  loadAssignments()
})
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
            <h1 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white">Assigned Orders</h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Manage your assigned orders and live delivery progress.</p>
          </div>
        </div>

        <div class="flex items-center gap-2">
          <div class="px-4 py-2 rounded-xl bg-blue-500/10 border border-blue-500/20 text-blue-600 dark:text-blue-400 font-extrabold text-xs sm:text-sm">
            Assigned: {{ assignments.length }} Orders
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
              placeholder="Search by order #, room, or guest name..."
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
            <span>{{ isFilterOpen ? 'Hide Filter' : 'Filter' }}</span>
          </button>
        </div>

        <!-- Right: Action Buttons -->
        <div class="flex items-center gap-2 sm:gap-2.5">
          <!-- Refresh Button -->
          <button
            type="button"
            @click="loadAssignments"
            :disabled="loading"
            title="Refresh"
            class="inline-flex items-center justify-center h-9 w-9 sm:h-10 sm:w-10 rounded-xl border border-slate-200 dark:border-[#1e3455] bg-white dark:bg-[#13233c]/80 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-[#1c3356] hover:text-slate-900 dark:hover:text-white transition disabled:opacity-50 cursor-pointer"
          >
            <RefreshCw class="w-4 h-4" :class="{ 'animate-spin': loading }" />
          </button>

          <!-- Fullscreen Toggle -->
          <button
            type="button"
            @click="toggleFullscreen"
            title="Toggle Fullscreen"
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
                Order Status
              </label>
              <select
                v-model="selectedStatus"
                class="w-full rounded-xl border border-slate-200 dark:border-[#1e3455] bg-slate-50/80 dark:bg-[#13233c] text-slate-900 dark:text-white px-3.5 py-2 text-xs sm:text-sm focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 dark:focus:border-blue-400 transition cursor-pointer font-medium outline-none"
              >
                <option value="">All Statuses</option>
                <option value="assigned">Assigned</option>
                <option value="accepted">Accepted</option>
                <option value="picked_up">Picked Up</option>
                <option value="on_delivery">On Delivery</option>
              </select>
            </div>

            <!-- Type Filter -->
            <div>
              <label class="mb-1.5 block text-xs font-semibold text-slate-600 dark:text-slate-300">
                Service Type
              </label>
              <select
                v-model="selectedType"
                class="w-full rounded-xl border border-slate-200 dark:border-[#1e3455] bg-slate-50/80 dark:bg-[#13233c] text-slate-900 dark:text-white px-3.5 py-2 text-xs sm:text-sm focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 dark:focus:border-blue-400 transition cursor-pointer font-medium outline-none"
              >
                <option value="all">All Types</option>
                <option value="room">Room Service</option>
                <option value="walk_in">Takeout / Walk-in</option>
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
                <span>Reset Filters</span>
              </button>
            </div>
          </div>
        </div>
      </Transition>

      <!-- Table Container -->
      <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-xs overflow-hidden font-sans w-full">
        <!-- Desktop Table View -->
        <div class="hidden md:block overflow-x-auto w-full">
          <table class="w-full text-left border-collapse">
            <thead class="bg-slate-50/90 dark:bg-[#0c182c] border-b border-slate-200 dark:border-[#1e3455]">
              <tr class="text-[11px] font-bold text-slate-500 dark:text-slate-400 select-none">
                <th class="py-3 px-4 pl-5 whitespace-nowrap">Order Ref</th>
                <th class="py-3 px-4 whitespace-nowrap">Room / Service</th>
                <th class="py-3 px-4 whitespace-nowrap">Guest</th>
                <th class="py-3 px-4 whitespace-nowrap">Items</th>
                <th class="py-3 px-4 text-center whitespace-nowrap">Status</th>
                <th class="py-3 px-4 whitespace-nowrap">Assigned Time</th>
                <th class="py-3 px-4 text-right pr-5 whitespace-nowrap">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-[#1e3455]/60 text-xs">
              <tr
                v-for="order in paginatedAssignments"
                :key="order.id"
                class="hover:bg-slate-50/80 dark:hover:bg-[#13233c]/60 transition-colors duration-150 group"
              >
                <!-- Order Ref -->
                <td class="py-3 px-4 pl-5 whitespace-nowrap font-mono font-extrabold text-blue-600 dark:text-blue-400 text-xs">
                  #{{ order.order_number || order.order_id || String(order.id).substring(0, 8) }}
                </td>

                <!-- Room / Service -->
                <td class="py-3 px-4 whitespace-nowrap">
                  <span
                    v-if="order.room_number || order.room?.room_number"
                    class="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-800 dark:text-slate-200 font-bold text-xs border border-slate-200 dark:border-slate-700"
                  >
                    <BedDouble class="w-3 h-3 text-slate-400" />
                    Room {{ order.room_number || order.room?.room_number }}
                  </span>
                  <span
                    v-else
                    class="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-300 font-bold text-xs border border-amber-200 dark:border-amber-800"
                  >
                    <ShoppingBag class="w-3 h-3" />
                    Takeout
                  </span>
                </td>

                <!-- Guest -->
                <td class="py-3 px-4 whitespace-nowrap font-medium text-slate-900 dark:text-white">
                  {{ order.guest_name || order.guest?.full_name || 'Guest' }}
                </td>

                <!-- Items -->
                <td class="py-3 px-4 whitespace-nowrap">
                  <span class="font-bold text-slate-700 dark:text-slate-300">
                    {{ typeof order.items === 'number' ? order.items : (Array.isArray(order.items) ? order.items.length : 1) }} items
                  </span>
                </td>

                <!-- Status -->
                <td class="py-3 px-4 text-center whitespace-nowrap">
                  <span
                    class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-extrabold border uppercase tracking-wider"
                    :class="getStatusBadge(order.order_status || order.status)"
                  >
                    {{ (order.order_status || order.status || 'assigned').replace('_', ' ') }}
                  </span>
                </td>

                <!-- Assigned Time -->
                <td class="py-3 px-4 whitespace-nowrap text-slate-600 dark:text-slate-400">
                  {{ formatDateTime(order.assigned_at || order.created_at) }}
                </td>

                <!-- Actions -->
                <td class="py-3 px-4 text-right pr-5 whitespace-nowrap">
                  <button
                    @click="viewDetails(order)"
                    class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-bold text-blue-600 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-950/40 rounded-lg transition"
                  >
                    <Eye class="w-3.5 h-3.5" />
                    <span>Details</span>
                  </button>
                </td>
              </tr>

              <!-- Empty State -->
              <tr v-if="paginatedAssignments.length === 0">
                <td colspan="7" class="px-6 py-12 text-center text-slate-500 dark:text-slate-400 text-xs font-bold">
                  No assigned orders match your criteria.
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Mobile Card View -->
        <div class="md:hidden divide-y divide-slate-100 dark:divide-slate-800">
          <div
            v-for="order in paginatedAssignments"
            :key="order.id"
            class="p-4 space-y-3 hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition"
          >
            <div class="flex items-center justify-between">
              <span class="font-mono font-bold text-blue-600 dark:text-blue-400 text-xs">
                #{{ order.order_number || order.order_id }}
              </span>
              <span
                class="px-2 py-0.5 rounded-full text-[10px] font-bold border uppercase"
                :class="getStatusBadge(order.order_status || order.status)"
              >
                {{ (order.order_status || order.status || 'assigned').replace('_', ' ') }}
              </span>
            </div>
            <div class="flex items-center justify-between text-xs text-slate-600 dark:text-slate-400">
              <span>{{ order.guest_name || 'Guest' }}</span>
              <button
                @click="viewDetails(order)"
                class="px-2 py-1 text-xs font-bold text-blue-600 bg-blue-50 dark:bg-blue-950/40 rounded-lg"
              >
                Details
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
            Showing <span class="font-bold text-slate-900 dark:text-white">{{ showingFrom }}</span> to
            <span class="font-bold text-slate-900 dark:text-white">{{ showingTo }}</span> of
            <span class="font-bold text-slate-900 dark:text-white">{{ total }}</span> orders
          </div>

          <div class="flex items-center gap-2 sm:gap-3">
            <div class="flex items-center gap-1.5">
              <span class="text-slate-500 dark:text-slate-400 font-medium">Per page:</span>
              <select
                :value="itemsPerPage"
                @change="changeItemsPerPage"
                class="rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-[#13233c] text-slate-900 dark:text-white px-2 py-1 text-xs outline-none"
              >
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
                :disabled="currentPage === totalPages"
                class="p-1 rounded-lg border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 disabled:opacity-40 transition cursor-pointer"
              >
                <ChevronRight class="w-4 h-4" />
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Order Detail Modal -->
      <div
        v-if="showDetailModal"
        class="fixed inset-0 z-50 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4"
        @click.self="showDetailModal = false"
      >
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl max-w-md w-full p-6 space-y-4 shadow-2xl">
          <div class="flex justify-between items-center border-b border-slate-100 dark:border-slate-800 pb-3">
            <h3 class="text-base font-extrabold text-slate-900 dark:text-white">Order Details</h3>
            <button @click="showDetailModal = false" class="text-slate-400 hover:text-slate-600 p-1">
              <X class="w-4 h-4" />
            </button>
          </div>

          <div v-if="selectedOrder" class="space-y-3 text-xs">
            <div class="flex justify-between py-1 border-b border-slate-100 dark:border-slate-800">
              <span class="text-slate-500">Order Ref:</span>
              <span class="font-bold text-slate-900 dark:text-white">#{{ selectedOrder.order_number || selectedOrder.id }}</span>
            </div>
            <div class="flex justify-between py-1 border-b border-slate-100 dark:border-slate-800">
              <span class="text-slate-500">Guest:</span>
              <span class="font-bold text-slate-900 dark:text-white">{{ selectedOrder.guest_name || 'Guest' }}</span>
            </div>
            <div class="flex justify-between py-1 border-b border-slate-100 dark:border-slate-800">
              <span class="text-slate-500">Destination:</span>
              <span class="font-bold text-slate-900 dark:text-white">Room {{ selectedOrder.room_number || 'N/A' }}</span>
            </div>
            <div class="flex justify-between py-1 border-b border-slate-100 dark:border-slate-800">
              <span class="text-slate-500">Status:</span>
              <span class="font-bold uppercase" :class="getStatusBadge(selectedOrder.order_status || selectedOrder.status)">{{ selectedOrder.order_status || selectedOrder.status }}</span>
            </div>
          </div>

          <button
            @click="showDetailModal = false"
            class="w-full py-2 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 font-bold rounded-xl text-xs transition"
          >
            Close
          </button>
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>
