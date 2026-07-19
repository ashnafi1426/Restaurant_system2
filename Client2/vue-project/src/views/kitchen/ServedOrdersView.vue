<script setup lang="ts">
import { onMounted, ref, computed } from 'vue'
import { useKitchenStore } from '@/stores/kitchenStore'
import { storeToRefs } from 'pinia'
import DashboardLayout from '@/layouts/DashboardLayout.vue'

const kitchenStore = useKitchenStore()
const { completedOrders, statistics } = storeToRefs(kitchenStore)

// Pagination state
const currentPage = ref(1)
const itemsPerPage = ref(10)
const searchQuery = ref('')

onMounted(async () => {
  await kitchenStore.fetchDashboard()
})

const formatTime = (dateTime: string) => {
  try {
    const date = new Date(dateTime)
    return date.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' })
  } catch {
    return '—'
  }
}

// Filter orders by search query
const filteredOrders = computed(() => {
  if (!searchQuery.value) return completedOrders.value || []
  
  const query = searchQuery.value.toLowerCase()
  return (completedOrders.value || []).filter(order =>
    order.order_number?.toString().includes(query) ||
    order.room?.room_number?.toString().includes(query) ||
    order.guest?.full_name?.toLowerCase().includes(query) ||
    order.items?.some(item => item.name?.toLowerCase().includes(query))
  )
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

// Pagination navigation functions
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
</script>

<template>
  <DashboardLayout>
    <div class="space-y-6">
      <!-- Header Section -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold text-slate-900">✓✓ Served Orders</h1>
          <p class="mt-2 text-slate-600">Completed and served orders</p>
        </div>
        <div class="bg-slate-100 text-slate-700 px-6 py-3 rounded-lg font-bold text-2xl">
          {{ statistics?.served_orders ?? 0 }}
        </div>
      </div>

      <!-- Search and Controls Section -->
      <div class="rounded-lg bg-white shadow-md p-4 sm:p-6">
        <div class="flex flex-col sm:flex-row gap-3 sm:gap-4 items-end">
          <!-- Search Input -->
          <div class="flex-1">
            <label for="search" class="block text-sm font-semibold text-slate-700 mb-2">
              Search Served Orders
            </label>
            <input
              id="search"
              v-model="searchQuery"
              type="text"
              placeholder="Search by order #, room, guest name, or item..."
              class="w-full px-4 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent"
            />
          </div>

          <!-- Clear Search Button -->
          <button
            v-if="searchQuery"
            @click="searchQuery = ''"
            class="px-4 py-2 bg-slate-200 hover:bg-slate-300 text-slate-700 font-semibold rounded-lg text-sm transition"
          >
            Clear
          </button>
        </div>

        <!-- Results Counter -->
        <div v-if="filteredOrders.length > 0" class="mt-3 text-sm text-slate-600">
          Found <span class="font-bold text-green-600">{{ filteredOrders.length }}</span>
          served order<span v-if="filteredOrders.length !== 1">s</span>
        </div>
        <div v-else-if="searchQuery" class="mt-3 text-sm text-slate-500">
          No orders match "{{ searchQuery }}"
        </div>
      </div>

      <!-- Table Container -->
      <div class="rounded-lg bg-white shadow-md overflow-hidden overflow-x-auto">
        <table v-if="paginatedOrders?.length" class="w-full text-sm">
          <!-- Table Header -->
          <thead>
            <tr class="bg-slate-100 border-b-2 border-slate-200">
              <th class="px-4 py-3 text-left font-bold text-slate-700">Order ID</th>
              <th class="px-4 py-3 text-left font-bold text-slate-700">Room</th>
              <th class="px-4 py-3 text-left font-bold text-slate-700">Guest</th>
              <th class="px-4 py-3 text-left font-bold text-slate-700">Items</th>
              <th class="px-4 py-3 text-left font-bold text-slate-700">Served Time</th>
              <th class="px-4 py-3 text-right font-bold text-slate-700">Total</th>
              <th class="px-4 py-3 text-center font-bold text-slate-700">Status</th>
            </tr>
          </thead>

          <!-- Table Body -->
          <tbody>
            <tr
              v-for="order in paginatedOrders"
              :key="order.id"
              class="border-b border-slate-200 transition hover:bg-slate-50 opacity-85"
            >
              <!-- Order ID -->
              <td class="px-4 py-3">
                <div class="font-mono text-xs font-semibold text-slate-900">
                  {{ order.order_number }}
                </div>
              </td>

              <!-- Room -->
              <td class="px-4 py-3">
                <div class="font-semibold text-slate-900">
                  {{ order.room?.room_number ? `ROOM ${order.room.room_number}` : '🍽️ TAKEOUT' }}
                </div>
              </td>

              <!-- Guest -->
              <td class="px-4 py-3">
                <div class="text-slate-700">
                  {{ order.guest?.full_name || 'Walk-in Guest' }}
                </div>
              </td>

              <!-- Items -->
              <td class="px-4 py-3">
                <div class="text-slate-700 space-y-1">
                  <div
                    v-for="(item, idx) in (order.items || []).slice(0, 2)"
                    :key="idx"
                    class="text-sm"
                  >
                    <span class="font-semibold">{{ item.quantity }}x</span> {{ item.name }}
                  </div>
                  <div v-if="(order.items || []).length > 2" class="text-xs text-slate-500 font-semibold">
                    +{{ (order.items || []).length - 2 }} more items
                  </div>
                </div>
              </td>

              <!-- Served Time -->
              <td class="px-4 py-3">
                <div class="font-semibold text-slate-900">
                  {{ formatTime(order.order_time) }}
                </div>
              </td>

              <!-- Total -->
              <td class="px-4 py-3 text-right">
                <div class="font-bold text-slate-900 text-lg">
                  ${{ parseFloat(order.total).toFixed(2) }}
                </div>
              </td>

              <!-- Status -->
              <td class="px-4 py-3 text-center">
                <span class="inline-block bg-green-100 text-green-700 px-3 py-1 rounded-full text-xs font-semibold">
                  ✓ Served
                </span>
              </td>
            </tr>
          </tbody>
        </table>

        <!-- Empty State -->
        <div v-else class="text-center py-16 bg-slate-50">
          <p class="text-6xl mb-4">📭</p>
          <p class="text-2xl font-bold text-slate-900">
            {{ searchQuery ? 'No Matching Orders' : 'No Served Orders' }}
          </p>
          <p class="text-slate-500 mt-2">
            {{ searchQuery ? 'Try adjusting your search' : 'No completed orders yet today' }}
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
              class="px-3 py-2 border border-slate-300 rounded-lg text-sm font-medium text-slate-900 hover:border-slate-400 focus:outline-none focus:ring-2 focus:ring-green-500"
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
                    ? 'bg-green-600 text-white'
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
                    ? 'bg-green-600 text-white'
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
                    ? 'bg-green-600 text-white'
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
            Showing <span class="text-green-600">{{ startItem }}-{{ endItem }}</span> of
            <span class="text-green-600">{{ filteredOrders.length }}</span>
          </div>
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>
