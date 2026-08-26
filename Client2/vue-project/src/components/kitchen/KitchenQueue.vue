<script setup lang="ts">
import { ref, computed } from 'vue'
import { CookingPot, Search, Clock, ChefHat, Check, CheckCheck, Loader, Eye, UtensilsCrossed, Inbox } from 'lucide-vue-next'
import type { KitchenOrder } from '@/types/kitchen'

const props = defineProps<{
  pendingOrders?: KitchenOrder[]
  preparingOrders?: KitchenOrder[]
  readyOrders?: KitchenOrder[]
  completedOrders?: KitchenOrder[]
  processing?: boolean
  loading?: boolean
}>()

const emit = defineEmits<{
  (e: 'start', order: KitchenOrder): void
  (e: 'ready', order: KitchenOrder): void
  (e: 'served', order: KitchenOrder): void
  (e: 'view', order: KitchenOrder): void
}>()

const selectedFilter = ref('all')
const searchQuery = ref('')

// Pagination state
const currentPage = ref(1)
const itemsPerPage = ref(10)

// Track which order is being processed for individual button states
const processingOrderId = ref<string | null>(null)

const isProcessing = computed(() => (orderId: string) => {
  return processingOrderId.value === orderId
})

// Combine all orders with their status
const allOrdersWithStatus = computed(() => {
  const orders: Array<KitchenOrder & { status: string }> = []

  props.pendingOrders?.forEach((order) => orders.push({ ...order, status: 'pending' }))
  props.preparingOrders?.forEach((order) => orders.push({ ...order, status: 'preparing' }))
  props.readyOrders?.forEach((order) => orders.push({ ...order, status: 'ready' }))
  props.completedOrders?.forEach((order) => orders.push({ ...order, status: 'served' }))

  return orders
})

// Filter and search orders
const filteredOrders = computed(() => {
  let filtered = allOrdersWithStatus.value

  // Filter by status
  if (selectedFilter.value !== 'all') {
    filtered = filtered.filter((order) => order.status === selectedFilter.value)
  }

  // Filter by search query (room number or items)
  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    filtered = filtered.filter(
      (order) =>
        order.room?.room_number?.toString().includes(query) ||
        order.items?.some((item) => item.name?.toLowerCase().includes(query)),
    )
  }

  return filtered
})

// Pagination computed properties
const totalPages = computed(() => {
  return Math.ceil((filteredOrders.value?.length || 0) / itemsPerPage.value)
})

const paginatedOrders = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage.value
  const end = start + itemsPerPage.value
  return (filteredOrders.value || []).slice(start, end)
})

const startItem = computed(() => {
  return (currentPage.value - 1) * itemsPerPage.value + 1
})

const endItem = computed(() => {
  return Math.min(currentPage.value * itemsPerPage.value, filteredOrders.value?.length || 0)
})

const hasNextPage = computed(() => currentPage.value < totalPages.value)
const hasPrevPage = computed(() => currentPage.value > 1)

const goToNextPage = () => {
  if (hasNextPage.value) {
    currentPage.value++
  }
}

const goToPrevPage = () => {
  if (hasPrevPage.value) {
    currentPage.value--
  }
}

const goToPage = (page: number) => {
  if (page >= 1 && page <= totalPages.value) {
    currentPage.value = page
  }
}

const changeItemsPerPage = (newAmount: number) => {
  itemsPerPage.value = newAmount
  currentPage.value = 1 // Reset to first page
}

// Helper function to get page numbers to display
const getPaginationRange = (): number[] => {
  const range: number[] = []
  const start = Math.max(2, currentPage.value - 1)
  const end = Math.min(totalPages.value - 1, currentPage.value + 1)

  for (let i = start; i <= end; i++) {
    range.push(i)
  }

  return range
}

async function handleStartPreparing(order: KitchenOrder) {
  processingOrderId.value = order.id
  try {
    emit('start', order)
  } finally {
    setTimeout(() => {
      processingOrderId.value = null
    }, 500)
  }
}

async function handleMarkReady(order: KitchenOrder) {
  processingOrderId.value = order.id
  try {
    emit('ready', order)
  } finally {
    setTimeout(() => {
      processingOrderId.value = null
    }, 500)
  }
}

async function handleMarkServed(order: KitchenOrder) {
  processingOrderId.value = order.id
  try {
    emit('served', order)
  } finally {
    setTimeout(() => {
      processingOrderId.value = null
    }, 500)
  }
}

function getTimeRemaining(orderTime: string): string {
  try {
    const orderDate = new Date(orderTime)
    const now = new Date()
    const diff = Math.floor((now.getTime() - orderDate.getTime()) / 1000 / 60)
    return `${String(diff).padStart(2, '0')}:${String(Math.floor(now.getSeconds() % 60)).padStart(2, '0')} mins`
  } catch {
    return '-- mins'
  }
}

function getItemsPreview(items: any[]): string {
  if (!items?.length) return 'No items'
  return items.map((i) => `${i.quantity}x ${i.name}`).join(', ')
}

function getStatusIcon(status: string) {
  switch (status) {
    case 'pending':
      return Clock
    case 'preparing':
      return ChefHat
    case 'ready':
      return Check
    case 'served':
      return CheckCheck
    default:
      return UtensilsCrossed
  }
}

function getStatusColor(status: string): string {
  const colors: Record<string, string> = {
    pending: 'bg-amber-500/10 text-amber-600 dark:bg-amber-500/20 dark:text-amber-400 border border-amber-500/20',
    preparing: 'bg-blue-500/10 text-blue-600 dark:bg-blue-500/20 dark:text-blue-400 border border-blue-500/20',
    ready: 'bg-emerald-500/10 text-emerald-600 dark:bg-emerald-500/20 dark:text-emerald-400 border border-emerald-500/20',
    served: 'bg-slate-500/10 text-slate-600 dark:bg-slate-800 dark:text-slate-400 border border-slate-500/20',
  }
  return colors[status] || 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300'
}

function getStatusBgRow(status: string): string {
  const colors: Record<string, string> = {
    pending: 'hover:bg-amber-50/50 dark:hover:bg-amber-950/20',
    preparing: 'hover:bg-blue-50/50 dark:hover:bg-blue-950/20',
    ready: 'hover:bg-emerald-50/50 dark:hover:bg-emerald-950/20',
    served: 'hover:bg-slate-50 dark:hover:bg-slate-800/50',
  }
  return colors[status] || 'hover:bg-slate-50 dark:hover:bg-slate-800/50'
}
</script>

<template>
  <div class="space-y-4 sm:space-y-5 md:space-y-6">
    <!-- Header Section -->
    <div class="space-y-4">
      <!-- Title -->
      <div class="min-w-0">
        <h2 class="text-lg sm:text-xl md:text-2xl font-black text-slate-900 dark:text-white flex items-center gap-2">
          <CookingPot class="w-6 h-6 text-amber-500" />
          <span>Your Kitchen Orders</span>
          <span class="text-xs sm:text-sm text-amber-500 font-bold">●</span>
        </h2>
        <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1 font-medium">Only showing orders assigned to you</p>
      </div>

      <!-- Controls: Search and Filter -->
      <div class="flex flex-col sm:flex-row gap-3 sm:gap-4">
        <!-- Search Input -->
        <div class="flex-1">
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Search by room number or dish name..."
            class="w-full px-3 sm:px-4 py-2 border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-900 dark:text-white rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-amber-500"
          />
        </div>

        <!-- Status Filter -->
        <div class="flex gap-1.5 flex-wrap">
          <button
            v-for="status in ['all', 'pending', 'preparing', 'ready', 'served']"
            :key="status"
            @click="selectedFilter = status"
            :class="[
              'px-3.5 sm:px-4 py-2 rounded-xl font-bold text-xs sm:text-sm transition cursor-pointer border',
              selectedFilter === status
                ? 'bg-slate-900 dark:bg-slate-800 text-white border-slate-900 dark:border-slate-700 shadow-md'
                : 'bg-white dark:bg-slate-900/80 text-slate-600 dark:text-slate-400 border-slate-200 dark:border-slate-800 hover:bg-slate-100 dark:hover:bg-slate-800',
            ]"
          >
            {{ status.toUpperCase() }}
            <span v-if="status === 'all'" class="ml-1 text-xs opacity-75">
              ({{ filteredOrders.length }})
            </span>
            <span v-else class="ml-1 text-xs opacity-75">
              ({{ filteredOrders.filter((o) => o.status === status).length }})
            </span>
          </button>
        </div>
      </div>
    </div>

    <!-- Table Container -->
    <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-md overflow-hidden overflow-x-auto">
      <table class="w-full text-sm">
        <!-- Table Header -->
        <thead>
          <tr class="bg-slate-50 dark:bg-slate-800/80 border-b border-slate-200 dark:border-slate-800">
            <th class="px-3 sm:px-4 py-3 text-left font-black text-slate-700 dark:text-slate-200 uppercase text-xs tracking-wider">Room</th>
            <th class="px-3 sm:px-4 py-3 text-left font-black text-slate-700 dark:text-slate-200 uppercase text-xs tracking-wider">Guest</th>
            <th class="px-3 sm:px-4 py-3 text-left font-black text-slate-700 dark:text-slate-200 uppercase text-xs tracking-wider">Items</th>
            <th class="px-3 sm:px-4 py-3 text-left font-black text-slate-700 dark:text-slate-200 uppercase text-xs tracking-wider">Time</th>
            <th class="px-3 sm:px-4 py-3 text-center font-black text-slate-700 dark:text-slate-200 uppercase text-xs tracking-wider">Action</th>
            <th class="px-3 sm:px-4 py-3 text-center font-black text-slate-700 dark:text-slate-200 uppercase text-xs tracking-wider">Status</th>
          </tr>
        </thead>

        <!-- Table Body -->
        <tbody>
          <tr
            v-for="order in paginatedOrders"
            :key="order.id"
            :class="[
              'border-b border-slate-200 transition cursor-pointer',
              getStatusBgRow(order.status),
            ]"
            @click="emit('view', order)"
          >
            <!-- Room Number -->
            <td class="px-3 sm:px-4 py-3">
              <div class="font-bold text-slate-900 dark:text-white">
                {{ order.room?.room_number || '—' }}
              </div>
              <div v-if="order.notes" class="text-xs text-rose-500 font-black">PRIORITY</div>
            </td>

            <!-- Guest Name -->
            <td class="px-3 sm:px-4 py-3">
              <div class="text-slate-700 dark:text-slate-300 font-medium">
                {{ order.room?.guest || 'QR Guest' }}
              </div>
            </td>

            <!-- Items Column -->
            <td class="px-3 sm:px-4 py-3">
              <div class="text-slate-700 dark:text-slate-300 max-w-xs">
                <div
                  v-for="(item, idx) in (order.items || []).slice(0, 2)"
                  :key="idx"
                  class="truncate text-sm font-medium"
                >
                  <span class="font-bold text-amber-500">{{ item.quantity }}x</span> {{ item.name }}
                  <span v-if="item.notes" class="text-xs text-slate-400 ml-1"
                    >({{ item.notes }})</span
                  >
                </div>
                <div
                  v-if="(order.items || []).length > 2"
                  class="text-xs text-slate-500 dark:text-slate-400 font-bold"
                >
                  +{{ (order.items || []).length - 2 }} more items
                </div>
              </div>
            </td>

            <!-- Time Column -->
            <td class="px-3 sm:px-4 py-3">
              <div class="font-black text-slate-900 dark:text-white">
                {{ getTimeRemaining(order.order_time) }}
              </div>
            </td>

            <!-- Action Column -->
            <td class="px-3 sm:px-4 py-3">
              <div class="flex justify-center gap-2">
                <!-- Start Preparing Button -->
                <button
                  v-if="order.status === 'pending'"
                  @click.stop="handleStartPreparing(order)"
                  :disabled="isProcessing(order.id) || processing"
                  class="px-3 py-1.5 bg-amber-500 hover:bg-amber-600 disabled:opacity-50 text-slate-950 font-black rounded-xl text-xs transition cursor-pointer flex items-center gap-1.5 shadow-md shadow-amber-500/20"
                >
                  <Loader v-if="isProcessing(order.id)" class="w-3.5 h-3.5 animate-spin" />
                  <span v-else>START</span>
                </button>

                <!-- Mark Ready Button -->
                <button
                  v-else-if="order.status === 'preparing'"
                  @click.stop="handleMarkReady(order)"
                  :disabled="isProcessing(order.id) || processing"
                  class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white font-bold rounded-xl text-xs transition cursor-pointer flex items-center gap-1.5 shadow-md shadow-blue-600/20"
                >
                  <Loader v-if="isProcessing(order.id)" class="w-3.5 h-3.5 animate-spin" />
                  <span v-else>READY</span>
                </button>

                <!-- Mark Served Button -->
                <button
                  v-else-if="order.status === 'ready'"
                  @click.stop="handleMarkServed(order)"
                  :disabled="isProcessing(order.id) || processing"
                  class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 text-white font-bold rounded-xl text-xs transition cursor-pointer flex items-center gap-1.5 shadow-md shadow-emerald-600/20"
                >
                  <Loader v-if="isProcessing(order.id)" class="w-3.5 h-3.5 animate-spin" />
                  <span v-else>SERVE</span>
                </button>

                <!-- View Details Button (for served) -->
                <button
                  v-else
                  @click.stop="emit('view', order)"
                  class="px-3 py-1.5 bg-slate-200 dark:bg-slate-800 hover:bg-slate-300 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-bold rounded-xl text-xs transition cursor-pointer flex items-center gap-1"
                >
                  <Eye class="w-3.5 h-3.5" />
                  <span>VIEW</span>
                </button>
              </div>
            </td>

            <!-- Status Column -->
            <td class="px-3 sm:px-4 py-3 text-center">
              <span
                :class="[
                  'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-black uppercase tracking-wider',
                  getStatusColor(order.status),
                ]"
              >
                <component :is="getStatusIcon(order.status)" class="w-3.5 h-3.5" />
                <span>{{ order.status }}</span>
              </span>
            </td>
          </tr>
        </tbody>
      </table>

      <!-- Loading State -->
      <div v-if="props.loading" class="text-center py-16 bg-white dark:bg-slate-900">
        <Loader class="w-8 h-8 mx-auto text-amber-500 animate-spin mb-3" />
        <p class="text-sm font-bold text-slate-700 dark:text-slate-300">Loading kitchen orders...</p>
      </div>

      <!-- Empty State -->
      <div v-else-if="filteredOrders.length === 0" class="text-center py-16 px-4 bg-white dark:bg-slate-900">
        <div class="w-14 h-14 bg-amber-500/10 border border-amber-500/20 text-amber-500 rounded-2xl flex items-center justify-center mx-auto mb-4">
          <Inbox class="w-7 h-7" />
        </div>
        <h3 class="text-base font-black text-slate-900 dark:text-white">No Kitchen Orders Found</h3>
        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 max-w-sm mx-auto font-medium">
          {{
            searchQuery
              ? 'No orders match your search term.'
              : `No ${selectedFilter === 'all' ? 'active' : selectedFilter} kitchen orders at the moment.`
          }}
        </p>
      </div>
    </div>

    <!-- Pagination Section -->
    <div v-if="filteredOrders.length > 0" class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-md p-4 sm:p-5">
      <div class="flex flex-col lg:flex-row items-center justify-between gap-4">
        <!-- Left: Items per page selector -->
        <div class="flex items-center gap-3 text-slate-600 dark:text-slate-400 text-xs">
          <label for="itemsPerPage" class="text-sm font-semibold text-slate-700"
            >Items per page:</label
          >
          <select
            id="itemsPerPage"
            :value="itemsPerPage"
            @change="changeItemsPerPage(Number(($event.target as HTMLSelectElement).value))"
            class="px-3 py-2 border border-slate-300 rounded-lg text-sm font-medium text-slate-900 hover:border-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500"
          >
            <option value="5">5</option>
            <option value="10">10</option>
            <option value="15">15</option>
            <option value="20">20</option>
            <option value="25">25</option>
          </select>
        </div>

        <!-- Center: Page info and pagination buttons -->
        <div class="flex flex-col sm:flex-row items-center gap-2 sm:gap-4">
          <!-- Previous Button -->
          <button
            @click="goToPrevPage"
            :disabled="!hasPrevPage"
            class="px-3 py-1.5 border border-slate-200 dark:border-slate-800 rounded-xl text-xs font-bold text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 disabled:opacity-40 disabled:cursor-not-allowed transition cursor-pointer"
          >
            ← Previous
          </button>

          <!-- Page Numbers -->
          <div class="flex items-center gap-1">
            <!-- First page -->
            <button
              @click="goToPage(1)"
              :class="[
                'px-3 py-1.5 rounded-xl text-xs font-bold transition cursor-pointer border',
                currentPage === 1
                  ? 'bg-slate-900 dark:bg-slate-800 border-slate-900 dark:border-slate-700 text-white shadow-sm'
                  : 'border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800',
              ]"
            >
              1
            </button>

            <!-- Ellipsis if needed -->
            <span v-if="currentPage > 3" class="px-1 text-slate-400 font-bold">...</span>

            <!-- Pages around current -->
            <button
              v-for="page in getPaginationRange()"
              :key="page"
              @click="goToPage(page)"
              :class="[
                'px-3 py-1.5 rounded-xl text-xs font-bold transition cursor-pointer border',
                currentPage === page
                  ? 'bg-slate-900 dark:bg-slate-800 border-slate-900 dark:border-slate-700 text-white shadow-sm'
                  : 'border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800',
              ]"
            >
              {{ page }}
            </button>

            <!-- Ellipsis if needed -->
            <span v-if="currentPage < totalPages - 2" class="px-1 text-slate-400 font-bold">...</span>

            <!-- Last page -->
            <button
              v-if="totalPages > 1"
              @click="goToPage(totalPages)"
              :class="[
                'px-3 py-1.5 rounded-xl text-xs font-bold transition cursor-pointer border',
                currentPage === totalPages
                  ? 'bg-slate-900 dark:bg-slate-800 border-slate-900 dark:border-slate-700 text-white shadow-sm'
                  : 'border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800',
              ]"
            >
              {{ totalPages }}
            </button>
          </div>

          <!-- Next Button -->
          <button
            @click="goToNextPage"
            :disabled="!hasNextPage"
            class="px-3 py-1.5 border border-slate-200 dark:border-slate-800 rounded-xl text-xs font-bold text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 disabled:opacity-40 disabled:cursor-not-allowed transition cursor-pointer"
          >
            Next →
          </button>
        </div>

        <!-- Right: Results info -->
        <div class="text-xs font-bold text-slate-600 dark:text-slate-400 whitespace-nowrap">
          Showing <span class="text-amber-500 font-black">{{ startItem }}-{{ endItem }}</span> of
          <span class="text-amber-500 font-black">{{ filteredOrders.length }}</span>
        </div>
      </div>
    </div>
  </div>
</template>
