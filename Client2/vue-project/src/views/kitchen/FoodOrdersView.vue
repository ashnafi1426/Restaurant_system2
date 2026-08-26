<script setup lang="ts">
import { onMounted, ref, computed } from 'vue'
import { useKitchenStore } from '@/stores/kitchenStore'
import { storeToRefs } from 'pinia'
import DashboardLayout from '@/layouts/DashboardLayout.vue'
import { 
  Beef, Coffee, Cake, Fish, Pizza, Soup, 
  Sandwich, Apple, Wine, IceCream, Cookie, 
  Egg, Salad, UtensilsCrossed, ChefHat,
  Clock, Check, CheckCheck, Inbox, ShoppingBag, Utensils, FileText, CookingPot
} from 'lucide-vue-next'

const kitchenStore = useKitchenStore()
const { orders, statistics, loading } = storeToRefs(kitchenStore)

const selectedStatus = ref<string>('all')
const searchQuery = ref<string>('')

// Pagination state
const currentPage = ref(1)
const itemsPerPage = ref(10)

onMounted(async () => {
  await kitchenStore.fetchDashboard()
})

const filteredOrders = computed(() => {
  let filtered = orders.value || []

  // Filter by status
  if (selectedStatus.value !== 'all') {
    filtered = filtered.filter((order) => order.status === selectedStatus.value)
  }

  // Filter by search
  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    filtered = filtered.filter(
      (order) =>
        order.room?.room_number?.toString().includes(query) ||
        order.order_number?.toLowerCase().includes(query) ||
        order.guest?.full_name?.toLowerCase().includes(query) ||
        order.items?.some((item) => item.name?.toLowerCase().includes(query)),
    )
  }

  return filtered
})

// Pagination computed properties
const totalPages = computed(() => {
  return Math.ceil(filteredOrders.value.length / itemsPerPage.value) || 1
})

const paginatedOrders = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage.value
  const end = start + itemsPerPage.value
  return filteredOrders.value.slice(start, end)
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

// Handle select change event
const handleItemsPerPageChange = (event: Event) => {
  const value = (event.target as HTMLSelectElement).value
  changeItemsPerPage(Number(value))
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

const getStatusBadgeColor = (status: string) => {
  switch (status) {
    case 'pending':
      return 'bg-amber-100 text-amber-800'
    case 'preparing':
      return 'bg-blue-100 text-blue-800'
    case 'ready':
      return 'bg-green-100 text-green-800'
    case 'served':
      return 'bg-slate-100 text-slate-800'
    default:
      return 'bg-gray-100 text-gray-800'
  }
}

const getStatusRowBg = (status: string) => {
  switch (status) {
    case 'pending':
      return 'hover:bg-amber-50'
    case 'preparing':
      return 'hover:bg-blue-50'
    case 'ready':
      return 'hover:bg-green-50'
    case 'served':
      return 'hover:bg-slate-50'
    default:
      return 'hover:bg-gray-50'
  }
}

const getStatusIconComponent = (status: string) => {
  switch (status) {
    case 'pending':
      return Clock
    case 'preparing':
      return CookingPot
    case 'ready':
      return Check
    case 'served':
      return CheckCheck
    default:
      return UtensilsCrossed
  }
}

const formatTime = (dateTime: string) => {
  try {
    const date = new Date(dateTime)
    return date.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' })
  } catch {
    return '—'
  }
}

const getItemsPreview = (items: any[]) => {
  if (!items?.length) return 'No items'
  return items.map((i) => `${i.quantity}x ${i.name}`).join(', ')
}

// Map food items to appropriate Lucide icons
const getFoodIcon = (itemName: string, category?: string) => {
  const name = itemName?.toLowerCase() || ''
  const cat = category?.toLowerCase() || ''

  // Breakfast items
  if (name.includes('egg') || name.includes('omelet')) return Egg
  if (name.includes('bacon') || name.includes('pancake') || name.includes('waffle')) return ChefHat
  
  // Main dishes
  if (name.includes('burger') || name.includes('sandwich')) return Sandwich
  if (name.includes('pizza')) return Pizza
  if (name.includes('steak') || name.includes('beef') || name.includes('mignon')) return Beef
  if (name.includes('chicken') || name.includes('poultry')) return ChefHat
  if (name.includes('fish') || name.includes('salmon') || name.includes('tuna')) return Fish
  
  // Soups & bowls
  if (name.includes('soup') || name.includes('ramen') || name.includes('noodle') || name.includes('bowl')) return Soup
  
  // Salads
  if (cat.includes('salad') || name.includes('salad')) return Salad
  
  // Desserts
  if (cat.includes('dessert') || name.includes('cake') || name.includes('lava')) return Cake
  if (name.includes('ice cream') || name.includes('gelato')) return IceCream
  if (name.includes('cookie') || name.includes('chocolate')) return Cookie
  
  // Drinks
  if (cat.includes('drink') || cat.includes('beverage')) return Coffee
  if (name.includes('coffee') || name.includes('espresso') || name.includes('tea')) return Coffee
  if (name.includes('wine') || name.includes('beer') || name.includes('cocktail')) return Wine
  
  // Fruits
  if (name.includes('fruit') || name.includes('apple') || name.includes('banana') || name.includes('orange')) return Apple
  
  // Default
  return UtensilsCrossed
}

const statusCounts = computed(() => {
  return {
    all: orders.value?.length || 0,
    pending: orders.value?.filter((o) => o.status === 'pending').length || 0,
    preparing: orders.value?.filter((o) => o.status === 'preparing').length || 0,
    ready: orders.value?.filter((o) => o.status === 'ready').length || 0,
    served: orders.value?.filter((o) => o.status === 'served').length || 0,
  }
})

// Helper to get status count safely
const getStatusCount = (status: string): number => {
  const counts = statusCounts.value as Record<string, number>
  return counts[status] || 0
}
</script>

<template>
  <DashboardLayout>
    <div class="space-y-6">
      <!-- Header Section -->
      <div class="space-y-4">
        <!-- Title -->
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-3xl font-bold text-slate-900 dark:text-white flex items-center gap-3">
              <UtensilsCrossed class="w-8 h-8 text-amber-500" />
              <span>All Food Orders</span>
            </h1>
            <p class="mt-2 text-slate-600 dark:text-slate-400">Complete kitchen order history</p>
          </div>
          <div class="bg-amber-500/10 border border-amber-500/20 text-amber-600 dark:text-amber-400 px-6 py-3 rounded-2xl font-black text-2xl">
            {{ filteredOrders.length }}
          </div>
        </div>

        <!-- Search and Filter Section -->
        <div class="flex flex-col sm:flex-row gap-4">
          <!-- Search Input -->
          <div class="flex-1">
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Search by room, order #, guest name, or item..."
              class="w-full px-4 py-2.5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-slate-900 dark:text-white rounded-xl text-xs sm:text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent font-medium"
            />
          </div>

          <!-- Status Filter Buttons -->
          <div class="flex gap-2 flex-wrap">
            <button
              v-for="status in ['all', 'pending', 'preparing', 'ready', 'served']"
              :key="status"
              @click="selectedStatus = status"
              :class="[
                'px-3 py-2 rounded-xl font-bold text-xs sm:text-sm transition whitespace-nowrap cursor-pointer',
                selectedStatus === status
                  ? 'bg-amber-600 text-white shadow-md shadow-amber-600/20'
                  : 'bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-300 hover:border-amber-500',
              ]"
            >
              {{ status.toUpperCase() }}
              <span class="ml-1 text-xs">({{ getStatusCount(status) }})</span>
            </button>
          </div>
        </div>
      </div>

      <!-- Table Container -->
      <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-xs overflow-hidden overflow-x-auto">
        <table class="w-full text-sm">
          <!-- Table Header -->
          <thead>
            <tr class="bg-slate-50 dark:bg-slate-950 border-b border-slate-200 dark:border-slate-800 text-xs font-black text-slate-400 dark:text-slate-500 uppercase tracking-wider select-none">
              <th class="px-4 py-3.5 text-left">Order ID</th>
              <th class="px-4 py-3.5 text-left">Room</th>
              <th class="px-4 py-3.5 text-left">Guest</th>
              <th class="px-4 py-3.5 text-left">Items</th>
              <th class="px-4 py-3.5 text-left">Time</th>
              <th class="px-4 py-3.5 text-right">Total</th>
              <th class="px-4 py-3.5 text-center">Status</th>
            </tr>
          </thead>

          <!-- Table Body -->
          <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
            <tr
              v-for="order in paginatedOrders"
              :key="order.id"
              :class="[
                'transition cursor-pointer',
                getStatusRowBg(order.status),
              ]"
            >
              <!-- Order ID Column -->
              <td class="px-4 py-3">
                <div class="font-mono text-xs font-bold text-slate-900 dark:text-white">
                  {{ order.order_number }}
                </div>
              </td>

              <!-- Room Column -->
              <td class="px-4 py-3">
                <div class="font-bold text-slate-900 dark:text-slate-100 flex items-center gap-1.5 text-xs">
                  <span v-if="order.room?.room_number" class="font-bold">ROOM {{ order.room.room_number }}</span>
                  <span v-else class="inline-flex items-center gap-1 text-amber-600 dark:text-amber-400 font-bold">
                    <ShoppingBag class="w-3.5 h-3.5" />
                    <span>TAKEOUT</span>
                  </span>
                </div>
              </td>

              <!-- Guest Column -->
              <td class="px-4 py-3">
                <div class="text-slate-700 dark:text-slate-300 text-xs font-medium">
                  {{ order.guest?.full_name || 'Walk-in Guest' }}
                </div>
              </td>

              <!-- Items Column -->
              <td class="px-4 py-3">
                <div class="text-slate-700 dark:text-slate-300 max-w-sm">
                  <div
                    v-for="(item, idx) in (order.items || []).slice(0, 2)"
                    :key="idx"
                    class="text-xs font-medium flex items-center gap-2 mb-1"
                  >
                    <component 
                      :is="getFoodIcon(item.name, item.category)" 
                      :size="14" 
                      class="text-amber-500 flex-shrink-0"
                    />
                    <span>{{ item.quantity }}x {{ item.name }}</span>
                  </div>
                  <div
                    v-if="(order.items || []).length > 2"
                    class="text-[10px] text-slate-400 font-bold mt-1 ml-5"
                  >
                    +{{ (order.items || []).length - 2 }} more items
                  </div>
                </div>
              </td>

              <!-- Time Column -->
              <td class="px-4 py-3">
                <div class="font-bold text-slate-900 dark:text-slate-100 text-xs">
                  {{ formatTime(order.order_time) }}
                </div>
              </td>

              <!-- Total Column -->
              <td class="px-4 py-3 text-right">
                <div class="font-black text-slate-900 dark:text-white text-xs font-mono">
                  ${{ parseFloat(order.total).toFixed(2) }}
                </div>
              </td>

              <!-- Status Column -->
              <td class="px-4 py-3 text-center">
                <span
                  :class="[
                    'inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-black uppercase tracking-wider',
                    getStatusBadgeColor(order.status),
                  ]"
                >
                  <component :is="getStatusIconComponent(order.status)" class="w-3.5 h-3.5" />
                  <span>{{ order.status }}</span>
                </span>
              </td>
            </tr>
          </tbody>
        </table>

        <!-- Empty State -->
        <div v-if="filteredOrders.length === 0" class="text-center py-12 bg-slate-50 dark:bg-slate-950">
          <Inbox class="w-12 h-12 text-slate-400 dark:text-slate-600 mx-auto mb-3" />
          <p class="text-xl font-bold text-slate-900">
            No {{ selectedStatus === 'all' ? 'Orders' : selectedStatus + ' Orders' }}
          </p>
          <p class="text-slate-500 mt-2">
            {{ searchQuery ? 'No orders match your search' : 'No orders found in this status' }}
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
              @change="handleItemsPerPageChange"
              class="px-3 py-2 border border-slate-300 rounded-lg text-sm font-medium text-slate-900 hover:border-slate-400 focus:outline-none focus:ring-2 focus:ring-teal-500"
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
                    ? 'bg-teal-600 text-white'
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
                    ? 'bg-teal-600 text-white'
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
                    ? 'bg-teal-600 text-white'
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
            Showing <span class="text-teal-600">{{ startItem }}-{{ endItem }}</span> of
            <span class="text-teal-600">{{ filteredOrders.length }}</span>
          </div>
        </div>
      </div>

      <!-- Summary Stats -->
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div class="bg-amber-50 rounded-lg p-4 text-center border-l-4 border-amber-400">
          <div class="text-sm text-amber-700 font-semibold">Pending</div>
          <div class="text-2xl font-bold text-amber-900">{{ statusCounts.pending }}</div>
        </div>
        <div class="bg-blue-50 rounded-lg p-4 text-center border-l-4 border-blue-400">
          <div class="text-sm text-blue-700 font-semibold">Preparing</div>
          <div class="text-2xl font-bold text-blue-900">{{ statusCounts.preparing }}</div>
        </div>
        <div class="bg-green-50 rounded-lg p-4 text-center border-l-4 border-green-400">
          <div class="text-sm text-green-700 font-semibold">Ready</div>
          <div class="text-2xl font-bold text-green-900">{{ statusCounts.ready }}</div>
        </div>
        <div class="bg-slate-50 rounded-lg p-4 text-center border-l-4 border-slate-400">
          <div class="text-sm text-slate-700 font-semibold">Served</div>
          <div class="text-2xl font-bold text-slate-900">{{ statusCounts.served }}</div>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="text-center py-8">
        <p class="text-slate-600 text-lg">⏳ Loading orders...</p>
      </div>
    </div>
  </DashboardLayout>
</template>
