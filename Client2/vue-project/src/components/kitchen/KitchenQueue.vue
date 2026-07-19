<script setup lang="ts">
import { ref, computed } from 'vue'
import type { KitchenOrder } from '@/types/kitchen'

const props = defineProps<{
  pendingOrders?: KitchenOrder[]
  preparingOrders?: KitchenOrder[]
  readyOrders?: KitchenOrder[]
  completedOrders?: KitchenOrder[]
  processing?: boolean
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
  
  props.pendingOrders?.forEach(order => orders.push({ ...order, status: 'pending' }))
  props.preparingOrders?.forEach(order => orders.push({ ...order, status: 'preparing' }))
  props.readyOrders?.forEach(order => orders.push({ ...order, status: 'ready' }))
  props.completedOrders?.forEach(order => orders.push({ ...order, status: 'served' }))
  
  return orders
})

// Filter and search orders
const filteredOrders = computed(() => {
  let filtered = allOrdersWithStatus.value
  
  // Filter by status
  if (selectedFilter.value !== 'all') {
    filtered = filtered.filter(order => order.status === selectedFilter.value)
  }
  
  // Filter by search query (room number or items)
  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    filtered = filtered.filter(order => 
      order.room?.room_number?.toString().includes(query) ||
      order.items?.some(item => item.name?.toLowerCase().includes(query))
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

function getStatusColor(status: string): string {
  const colors: Record<string, string> = {
    pending: 'bg-amber-100 text-amber-800',
    preparing: 'bg-blue-100 text-blue-800',
    ready: 'bg-green-100 text-green-800',
    served: 'bg-slate-100 text-slate-800'
  }
  return colors[status] || 'bg-gray-100 text-gray-800'
}

function getStatusBgRow(status: string): string {
  const colors: Record<string, string> = {
    pending: 'hover:bg-amber-50',
    preparing: 'hover:bg-blue-50',
    ready: 'hover:bg-green-50',
    served: 'hover:bg-slate-50'
  }
  return colors[status] || 'hover:bg-gray-50'
}
</script>

<template>
  <div class="space-y-4 sm:space-y-5 md:space-y-6">
    <!-- Header Section -->
    <div class="space-y-4">
      <!-- Title -->
      <div class="min-w-0">
        <h2 class="text-lg sm:text-xl md:text-2xl font-bold text-slate-900 flex items-center gap-2">
          🍳 Your Kitchen Orders
          <span class="text-xs sm:text-sm text-red-500 font-semibold">●</span>
        </h2>
        <p class="text-xs sm:text-sm text-slate-500 mt-1">Only showing orders assigned to you</p>
      </div>

      <!-- Controls: Search and Filter -->
      <div class="flex flex-col sm:flex-row gap-3 sm:gap-4">
        <!-- Search Input -->
        <div class="flex-1">
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Search by room number or dish name..."
            class="w-full px-3 sm:px-4 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          />
        </div>

        <!-- Status Filter -->
        <div class="flex gap-2">
          <button
            v-for="status in ['all', 'pending', 'preparing', 'ready', 'served']"
            :key="status"
            @click="selectedFilter = status"
            :class="[
              'px-3 sm:px-4 py-2 rounded-lg font-semibold text-xs sm:text-sm transition',
              selectedFilter === status
                ? 'bg-blue-600 text-white'
                : 'bg-slate-100 text-slate-700 hover:bg-slate-200'
            ]"
          >
            {{ status.toUpperCase() }}
            <span v-if="status === 'all'" class="ml-1 text-xs">
              ({{ filteredOrders.length }})
            </span>
            <span v-else class="ml-1 text-xs">
              ({{ filteredOrders.filter(o => o.status === status).length }})
            </span>
          </button>
        </div>
      </div>
    </div>

    <!-- Table Container -->
    <div class="rounded-lg bg-white shadow-md overflow-hidden overflow-x-auto">
      <table class="w-full text-sm">
        <!-- Table Header -->
        <thead>
          <tr class="bg-slate-100 border-b-2 border-slate-200">
            <th class="px-3 sm:px-4 py-3 text-left font-bold text-slate-700">Room</th>
            <th class="px-3 sm:px-4 py-3 text-left font-bold text-slate-700">Guest</th>
            <th class="px-3 sm:px-4 py-3 text-left font-bold text-slate-700">Items</th>
            <th class="px-3 sm:px-4 py-3 text-left font-bold text-slate-700">Time</th>
            <th class="px-3 sm:px-4 py-3 text-center font-bold text-slate-700">Action</th>
            <th class="px-3 sm:px-4 py-3 text-center font-bold text-slate-700">Status</th>
          </tr>
        </thead>

        <!-- Table Body -->
        <tbody>
          <tr
            v-for="order in paginatedOrders"
            :key="order.id"
            :class="[
              'border-b border-slate-200 transition cursor-pointer',
              getStatusBgRow(order.status)
            ]"
            @click="emit('view', order)"
          >
            <!-- Room Number -->
            <td class="px-3 sm:px-4 py-3">
              <div class="font-semibold text-slate-900">
                {{ order.room?.room_number || '—' }}
              </div>
              <div v-if="order.notes" class="text-xs text-red-600 font-bold">⚠️ PRIORITY</div>
            </td>

            <!-- Guest Name -->
            <td class="px-3 sm:px-4 py-3">
              <div class="text-slate-700">
                {{ order.room?.guest || 'QR Guest' }}
              </div>
            </td>

            <!-- Items Column -->
            <td class="px-3 sm:px-4 py-3">
              <div class="text-slate-700 max-w-xs">
                <div
                  v-for="(item, idx) in (order.items || []).slice(0, 2)"
                  :key="idx"
                  class="truncate text-sm"
                >
                  {{ item.quantity }}x {{ item.name }}
                  <span v-if="item.notes" class="text-xs text-slate-500 ml-1"
                    >({{ item.notes }})</span
                  >
                </div>
                <div v-if="(order.items || []).length > 2" class="text-xs text-slate-500 font-semibold">
                  +{{ (order.items || []).length - 2 }} more items
                </div>
              </div>
            </td>

            <!-- Time Column -->
            <td class="px-3 sm:px-4 py-3">
              <div class="font-bold text-slate-900">
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
                  class="px-2 sm:px-3 py-1.5 bg-teal-600 hover:bg-teal-700 disabled:bg-teal-400 disabled:cursor-not-allowed text-white font-bold rounded text-xs transition flex items-center gap-1"
                >
                  <span v-if="isProcessing(order.id)" class="inline-block animate-spin">⟳</span>
                  <span v-else>START</span>
                </button>

                <!-- Mark Ready Button -->
                <button
                  v-else-if="order.status === 'preparing'"
                  @click.stop="handleMarkReady(order)"
                  :disabled="isProcessing(order.id) || processing"
                  class="px-2 sm:px-3 py-1.5 bg-blue-600 hover:bg-blue-700 disabled:bg-blue-400 disabled:cursor-not-allowed text-white font-bold rounded text-xs transition flex items-center gap-1"
                >
                  <span v-if="isProcessing(order.id)" class="inline-block animate-spin">⟳</span>
                  <span v-else>READY</span>
                </button>

                <!-- Mark Served Button -->
                <button
                  v-else-if="order.status === 'ready'"
                  @click.stop="handleMarkServed(order)"
                  :disabled="isProcessing(order.id) || processing"
                  class="px-2 sm:px-3 py-1.5 bg-green-600 hover:bg-green-700 disabled:bg-green-400 disabled:cursor-not-allowed text-white font-bold rounded text-xs transition flex items-center gap-1"
                >
                  <span v-if="isProcessing(order.id)" class="inline-block animate-spin">⟳</span>
                  <span v-else>SERVE</span>
                </button>

                <!-- View Details Button (for served) -->
                <button
                  v-else
                  @click.stop="emit('view', order)"
                  class="px-2 sm:px-3 py-1.5 bg-slate-400 hover:bg-slate-500 text-white font-bold rounded text-xs transition"
                >
                  VIEW
                </button>
              </div>
            </td>

            <!-- Status Column -->
            <td class="px-3 sm:px-4 py-3 text-center">
              <span
                :class="[
                  'inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-bold',
                  getStatusColor(order.status)
                ]"
              >
                <span v-if="order.status === 'pending'" class="text-lg">⏳</span>
                <span v-else-if="order.status === 'preparing'" class="text-lg">👨‍🍳</span>
                <span v-else-if="order.status === 'ready'" class="text-lg">✓</span>
                <span v-else class="text-lg">✓✓</span>
                {{ order.status.toUpperCase() }}
              </span>
            </td>
          </tr>
        </tbody>
      </table>

      <!-- Empty State -->
      <div v-if="filteredOrders.length === 0" class="text-center py-12 bg-slate-50">
        <p class="text-slate-500 text-sm">
          {{
            searchQuery
              ? 'No orders match your search'
              : `No ${selectedFilter === 'all' ? 'orders' : selectedFilter + ' orders'} found`
          }}
        </p>
      </div>
    </div>

    <!-- Pagination Section -->
    <div v-if="filteredOrders.length > 0" class="rounded-lg bg-white shadow-md p-4 sm:p-6">
      <div class="flex flex-col lg:flex-row items-center justify-between gap-4 sm:gap-6">
        <!-- Left: Items per page selector -->
        <div class="flex items-center gap-3">
          <label for="itemsPerPage" class="text-sm font-semibold text-slate-700">Items per page:</label>
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
            class="px-3 py-2 border border-slate-300 rounded-lg text-sm font-semibold text-slate-700 hover:bg-slate-50 disabled:opacity-50 disabled:cursor-not-allowed transition"
          >
            ← Previous
          </button>

          <!-- Page Numbers -->
          <div class="flex items-center gap-1">
            <!-- First page -->
            <button
              @click="goToPage(1)"
              :class="[
                'px-2.5 py-2 rounded-lg text-sm font-semibold transition',
                currentPage === 1
                  ? 'bg-blue-600 text-white'
                  : 'border border-slate-300 text-slate-700 hover:bg-slate-50',
              ]"
            >
              1
            </button>

            <!-- Ellipsis if needed -->
            <span v-if="currentPage > 3" class="px-1 text-slate-500">...</span>

            <!-- Pages around current -->
            <button
              v-for="page in getPaginationRange()"
              :key="page"
              @click="goToPage(page)"
              :class="[
                'px-2.5 py-2 rounded-lg text-sm font-semibold transition',
                currentPage === page
                  ? 'bg-blue-600 text-white'
                  : 'border border-slate-300 text-slate-700 hover:bg-slate-50',
              ]"
            >
              {{ page }}
            </button>

            <!-- Ellipsis if needed -->
            <span v-if="currentPage < totalPages - 2" class="px-1 text-slate-500">...</span>

            <!-- Last page -->
            <button
              v-if="totalPages > 1"
              @click="goToPage(totalPages)"
              :class="[
                'px-2.5 py-2 rounded-lg text-sm font-semibold transition',
                currentPage === totalPages
                  ? 'bg-blue-600 text-white'
                  : 'border border-slate-300 text-slate-700 hover:bg-slate-50',
              ]"
            >
              {{ totalPages }}
            </button>
          </div>

          <!-- Next Button -->
          <button
            @click="goToNextPage"
            :disabled="!hasNextPage"
            class="px-3 py-2 border border-slate-300 rounded-lg text-sm font-semibold text-slate-700 hover:bg-slate-50 disabled:opacity-50 disabled:cursor-not-allowed transition"
          >
            Next →
          </button>
        </div>

        <!-- Right: Results info -->
        <div class="text-sm font-semibold text-slate-600 whitespace-nowrap">
          Showing <span class="text-blue-600">{{ startItem }}-{{ endItem }}</span> of
          <span class="text-blue-600">{{ filteredOrders.length }}</span>
        </div>
      </div>
    </div>

    <!-- Summary Stats -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 sm:gap-4">
      <div class="bg-amber-50 rounded-lg p-3 sm:p-4 text-center">
        <div class="text-xs sm:text-sm text-amber-700 font-semibold">Pending</div>
        <div class="text-lg sm:text-xl font-bold text-amber-900">
          {{ pendingOrders?.length || 0 }}
        </div>
      </div>
      <div class="bg-blue-50 rounded-lg p-3 sm:p-4 text-center">
        <div class="text-xs sm:text-sm text-blue-700 font-semibold">Preparing</div>
        <div class="text-lg sm:text-xl font-bold text-blue-900">
          {{ preparingOrders?.length || 0 }}
        </div>
      </div>
      <div class="bg-green-50 rounded-lg p-3 sm:p-4 text-center">
        <div class="text-xs sm:text-sm text-green-700 font-semibold">Ready</div>
        <div class="text-lg sm:text-xl font-bold text-green-900">
          {{ readyOrders?.length || 0 }}
        </div>
      </div>
      <div class="bg-slate-50 rounded-lg p-3 sm:p-4 text-center">
        <div class="text-xs sm:text-sm text-slate-700 font-semibold">Served</div>
        <div class="text-lg sm:text-xl font-bold text-slate-900">
          {{ completedOrders?.length || 0 }}
        </div>
      </div>
    </div>
  </div>
</template>
