<script setup lang="ts">
import { onMounted, ref, computed } from 'vue'
import { useKitchenStore } from '@/stores/kitchenStore'
import { storeToRefs } from 'pinia'
import DashboardLayout from '@/layouts/DashboardLayout.vue'
import { CheckCheck, ShoppingBag, Inbox } from 'lucide-vue-next'

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
  return (completedOrders.value || []).filter(
    (order) =>
      order.order_number?.toString().includes(query) ||
      order.room?.room_number?.toString().includes(query) ||
      order.guest?.full_name?.toLowerCase().includes(query) ||
      order.items?.some((item) => item.name?.toLowerCase().includes(query)),
  )
})

// Pagination computed properties
const totalPages = computed(() => {
  return Math.ceil((filteredOrders.value?.length || 0) / itemsPerPage.value) || 1
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
          <h1 class="text-3xl font-bold text-slate-900 dark:text-white flex items-center gap-3">
            <CheckCheck class="w-8 h-8 text-emerald-500" />
            <span>Served Orders</span>
          </h1>
          <p class="mt-2 text-slate-600 dark:text-slate-400">Completed and served orders</p>
        </div>
        <div class="bg-emerald-500/10 border border-emerald-500/20 text-emerald-600 dark:text-emerald-400 px-6 py-3 rounded-2xl font-black text-2xl">
          {{ statistics?.served_orders ?? 0 }}
        </div>
      </div>

      <!-- Search and Controls Section -->
      <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-xs p-4 sm:p-6">
        <div class="flex flex-col sm:flex-row gap-3 sm:gap-4 items-end">
          <!-- Search Input -->
          <div class="flex-1">
            <label for="search" class="block text-xs font-black text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-2">
              Search Served Orders
            </label>
            <input
              id="search"
              v-model="searchQuery"
              type="text"
              placeholder="Search by order #, room, guest name, or item..."
              class="w-full px-4 py-2.5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-slate-900 dark:text-white rounded-xl text-xs sm:text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent font-medium"
            />
          </div>

          <!-- Clear Search Button -->
          <button
            v-if="searchQuery"
            @click="searchQuery = ''"
            class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-bold rounded-xl text-xs transition cursor-pointer"
          >
            Clear
          </button>
        </div>

        <!-- Results Counter -->
        <div v-if="filteredOrders.length > 0" class="mt-3 text-xs text-slate-600 dark:text-slate-400 font-medium">
          Found <span class="font-bold text-emerald-600 dark:text-emerald-400">{{ filteredOrders.length }}</span> served
          order<span v-if="filteredOrders.length !== 1">s</span>
        </div>
        <div v-else-if="searchQuery" class="mt-3 text-xs text-slate-500">
          No orders match "{{ searchQuery }}"
        </div>
      </div>

      <!-- Table Container -->
      <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-xs overflow-hidden overflow-x-auto">
        <table v-if="filteredOrders.length > 0" class="w-full text-sm">
          <!-- Table Header -->
          <thead>
            <tr class="bg-slate-50 dark:bg-slate-950 border-b border-slate-200 dark:border-slate-800 text-xs font-black text-slate-400 dark:text-slate-500 uppercase tracking-wider select-none">
              <th class="px-4 py-3.5 text-left">Order ID</th>
              <th class="px-4 py-3.5 text-left">Room</th>
              <th class="px-4 py-3.5 text-left">Guest</th>
              <th class="px-4 py-3.5 text-left">Items</th>
              <th class="px-4 py-3.5 text-left">Served Time</th>
              <th class="px-4 py-3.5 text-right">Total</th>
              <th class="px-4 py-3.5 text-center">Status</th>
            </tr>
          </thead>

          <!-- Table Body -->
          <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
            <tr
              v-for="order in paginatedOrders"
              :key="order.id"
              class="transition hover:bg-slate-50/50 dark:hover:bg-slate-800/30"
            >
              <!-- Order ID -->
              <td class="px-4 py-3">
                <div class="font-mono text-xs font-bold text-slate-900 dark:text-white">
                  {{ order.order_number }}
                </div>
              </td>

              <!-- Room -->
              <td class="px-4 py-3">
                <div class="font-bold text-slate-900 dark:text-slate-100 text-xs flex items-center gap-1.5">
                  <span v-if="order.room?.room_number">ROOM {{ order.room.room_number }}</span>
                  <span v-else class="inline-flex items-center gap-1 text-amber-600 dark:text-amber-400 font-bold">
                    <ShoppingBag class="w-3.5 h-3.5" />
                    <span>TAKEOUT</span>
                  </span>
                </div>
              </td>

              <!-- Guest -->
              <td class="px-4 py-3">
                <div class="text-slate-700 dark:text-slate-300 text-xs font-medium">
                  {{ order.guest?.full_name || 'Walk-in Guest' }}
                </div>
              </td>

              <!-- Items -->
              <td class="px-4 py-3">
                <div class="text-slate-700 dark:text-slate-300 space-y-1">
                  <div
                    v-for="(item, idx) in (order.items || []).slice(0, 2)"
                    :key="idx"
                    class="text-xs font-medium"
                  >
                    <span class="font-bold text-amber-600 dark:text-amber-400">{{ item.quantity }}x</span> {{ item.name }}
                  </div>
                  <div
                    v-if="(order.items || []).length > 2"
                    class="text-[10px] text-slate-400 font-bold"
                  >
                    +{{ (order.items || []).length - 2 }} more items
                  </div>
                </div>
              </td>

              <!-- Served Time -->
              <td class="px-4 py-3">
                <div class="font-bold text-slate-900 dark:text-slate-100 text-xs">
                  {{ formatTime(order.order_time) }}
                </div>
              </td>

              <!-- Total -->
              <td class="px-4 py-3 text-right">
                <div class="font-black text-slate-900 dark:text-white text-xs font-mono">
                  ${{ parseFloat(order.total).toFixed(2) }}
                </div>
              </td>

              <!-- Status -->
              <td class="px-4 py-3 text-center">
                <span
                  class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-black bg-emerald-100 text-emerald-800 dark:bg-emerald-500/10 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-500/20 uppercase tracking-wider"
                >
                  <CheckCheck class="w-3.5 h-3.5" />
                  <span>Served</span>
                </span>
              </td>
            </tr>
          </tbody>
        </table>

        <!-- Empty State -->
        <div v-else class="text-center py-16 bg-slate-50 dark:bg-slate-950">
          <Inbox class="w-12 h-12 text-slate-400 dark:text-slate-600 mx-auto mb-3" />
          <p class="text-2xl font-bold text-slate-900 dark:text-white">
            {{ searchQuery ? 'No Matching Orders' : 'No Served Orders' }}
          </p>
          <p class="text-slate-500 dark:text-slate-400 mt-1 text-xs">
            {{ searchQuery ? 'Try adjusting your search' : 'No completed orders yet today' }}
          </p>
        </div>
      </div>

      <!-- Pagination Section -->
      <div v-if="filteredOrders.length > 0" class="rounded-lg bg-white shadow-md p-4 sm:p-6">
        <div class="flex flex-col lg:flex-row items-center justify-between gap-4 sm:gap-6">
          <!-- Left: Items per page selector -->
          <div class="flex items-center gap-3">
            <label for="itemsPerPage" class="text-sm font-semibold text-slate-700"
              >Items per page:</label
            >
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
