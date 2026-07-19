<script setup lang="ts">
import { onMounted, computed, ref } from 'vue'
import { useKitchenStore } from '@/stores/kitchenStore'
import { storeToRefs } from 'pinia'
import DashboardLayout from '@/layouts/DashboardLayout.vue'

const kitchenStore = useKitchenStore()
const { pendingOrders, statistics, actionLoading, error } = storeToRefs(kitchenStore)

// Notification state
const notification = ref<{ show: boolean; type: string; message: string }>({
  show: false,
  type: 'error',
  message: '',
})

// Pagination state
const currentPage = ref(1)
const itemsPerPage = ref(10)

// Computed properties for pagination
const totalPages = computed(() => {
  return Math.ceil((pendingOrders.value?.length || 0) / itemsPerPage.value)
})

const paginatedOrders = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage.value
  const end = start + itemsPerPage.value
  return (pendingOrders.value || []).slice(start, end)
})

const startItem = computed(() => {
  return (currentPage.value - 1) * itemsPerPage.value + 1
})

const endItem = computed(() => {
  return Math.min(currentPage.value * itemsPerPage.value, pendingOrders.value?.length || 0)
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

onMounted(async () => {
  await kitchenStore.fetchDashboard()
})

async function startPreparing(orderId: string) {
  // Clear previous notification
  notification.value.show = false
  
  try {
    console.log(`🔵 [UI] START button clicked for order: ${orderId}`)
    await kitchenStore.startPreparing(orderId)
    
    // Check if error occurred during the action
    if (error.value) {
      console.error(`❌ [UI] Error from store:`, error.value)
      notification.value = {
        show: true,
        type: 'error',
        message: error.value,
      }
    } else {
      console.log(`✅ [UI] Order started successfully`)
      notification.value = {
        show: true,
        type: 'success',
        message: `Order #${orderId} started preparing`,
      }
    }
  } catch (err: any) {
    console.error(`❌ [UI] Exception caught:`, err)
    const errorMessage = err?.response?.data?.message ?? err.message ?? 'Failed to start order'
    notification.value = {
      show: true,
      type: 'error',
      message: errorMessage,
    }
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
</script>

<template>
  <DashboardLayout>
    <!-- ✅ Error Notification Toast -->
    <Teleport to="body" v-if="notification.show">
      <div class="fixed top-4 right-4 z-50 animate-fade-in">
        <div
          :class="[
            'px-6 py-4 rounded-lg shadow-xl font-semibold flex items-center gap-3 max-w-md border-l-4',
            notification.type === 'error'
              ? 'bg-red-50 text-red-900 border-red-500'
              : 'bg-green-50 text-green-900 border-green-500',
          ]"
        >
          <span class="text-xl flex-shrink-0">{{ notification.type === 'error' ? '❌' : '✅' }}</span>
          <span class="flex-1">{{ notification.message }}</span>
          <button
            @click="notification.show = false"
            class="text-xl leading-none hover:opacity-70 flex-shrink-0"
          >
            ×
          </button>
        </div>
      </div>
    </Teleport>

    <div class="space-y-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold text-slate-900">⏳ Pending Orders</h1>
          <p class="mt-2 text-slate-600">Orders waiting to be prepared</p>
        </div>
        <div class="bg-amber-100 text-amber-700 px-6 py-3 rounded-lg font-bold text-2xl">
          {{ statistics?.pending_orders ?? 0 }}
        </div>
      </div>

      <!-- Table Container -->
      <div class="rounded-lg bg-white shadow-md overflow-hidden overflow-x-auto">
        <table v-if="pendingOrders?.length" class="w-full text-sm">
          <!-- Table Header -->
          <thead>
            <tr class="bg-slate-100 border-b-2 border-slate-200">
              <th class="px-4 py-3 text-left font-bold text-slate-700">Order ID</th>
              <th class="px-4 py-3 text-left font-bold text-slate-700">Room</th>
              <th class="px-4 py-3 text-left font-bold text-slate-700">Guest</th>
              <th class="px-4 py-3 text-left font-bold text-slate-700">Items</th>
              <th class="px-4 py-3 text-left font-bold text-slate-700">Notes</th>
              <th class="px-4 py-3 text-left font-bold text-slate-700">Time</th>
              <th class="px-4 py-3 text-right font-bold text-slate-700">Total</th>
              <th class="px-4 py-3 text-center font-bold text-slate-700">Action</th>
            </tr>
          </thead>

          <!-- Table Body -->
          <tbody>
            <tr
              v-for="order in paginatedOrders"
              :key="order.id"
              class="border-b border-slate-200 transition hover:bg-amber-50"
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
                    <span v-if="item.notes" class="block text-xs text-amber-600 mt-0.5">
                      📝 {{ item.notes }}
                    </span>
                  </div>
                  <div v-if="(order.items || []).length > 2" class="text-xs text-slate-500 font-semibold">
                    +{{ (order.items || []).length - 2 }} more items
                  </div>
                </div>
              </td>

              <!-- Notes -->
              <td class="px-4 py-3">
                <div v-if="order.notes" class="text-xs text-red-600 font-bold">
                  ⚠️ {{ order.notes }}
                </div>
                <div v-else class="text-xs text-slate-400">—</div>
              </td>

              <!-- Time -->
              <td class="px-4 py-3">
                <div class="font-semibold text-slate-900">
                  {{ formatTime(order.order_time) }}
                </div>
              </td>

              <!-- Total -->
              <td class="px-4 py-3 text-right">
                <div class="font-bold text-slate-900">
                  ${{ parseFloat(order.total).toFixed(2) }}
                </div>
              </td>

              <!-- Action -->
              <td class="px-4 py-3 text-center">
                <button
                  @click="startPreparing(order.id)"
                  :disabled="actionLoading !== null"
                  class="px-3 py-1.5 bg-teal-600 hover:bg-teal-700 disabled:bg-teal-400 disabled:cursor-not-allowed text-white font-bold rounded text-xs transition flex items-center justify-center gap-1"
                >
                  <span v-if="actionLoading === order.id" class="inline-block animate-spin">⟳</span>
                  <span v-else>👨‍🍳</span>
                  <span class="hidden sm:inline">{{ actionLoading === order.id ? 'STARTING...' : 'START' }}</span>
                </button>
              </td>
            </tr>
          </tbody>
        </table>

        <!-- Empty State -->
        <div v-else class="text-center py-16 bg-slate-50">
          <p class="text-6xl mb-4">✅</p>
          <p class="text-2xl font-bold text-slate-900">No Pending Orders</p>
          <p class="text-slate-500 mt-2">All orders have been started!</p>
        </div>
      </div>

      <!-- Pagination Section -->
      <div v-if="pendingOrders?.length" class="flex flex-col sm:flex-row items-center justify-between gap-4 px-4 py-4 bg-white rounded-lg shadow-md">
        <!-- Left: Items per page selector -->
        <div class="flex items-center gap-3">
          <label for="itemsPerPage" class="text-sm font-semibold text-slate-700">Items per page:</label>
          <select
            id="itemsPerPage"
            :value="itemsPerPage"
            @change="changeItemsPerPage(Number(($event.target as HTMLSelectElement).value))"
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
        <div class="flex items-center gap-2">
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
                'px-3 py-2 rounded-lg text-sm font-semibold transition',
                currentPage === 1
                  ? 'bg-teal-600 text-white'
                  : 'border border-slate-300 text-slate-700 hover:bg-slate-50',
              ]"
            >
              1
            </button>

            <!-- Ellipsis if needed -->
            <span v-if="currentPage > 3" class="px-2 text-slate-500">...</span>

            <!-- Pages around current -->
            <button
              v-for="page in getPaginationRange()"
              :key="page"
              @click="goToPage(page)"
              :class="[
                'px-3 py-2 rounded-lg text-sm font-semibold transition',
                currentPage === page
                  ? 'bg-teal-600 text-white'
                  : 'border border-slate-300 text-slate-700 hover:bg-slate-50',
              ]"
            >
              {{ page }}
            </button>

            <!-- Ellipsis if needed -->
            <span v-if="currentPage < totalPages - 2" class="px-2 text-slate-500">...</span>

            <!-- Last page -->
            <button
              v-if="totalPages > 1"
              @click="goToPage(totalPages)"
              :class="[
                'px-3 py-2 rounded-lg text-sm font-semibold transition',
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
        <div class="text-sm font-semibold text-slate-600">
          Showing <span class="text-teal-600">{{ startItem }}-{{ endItem }}</span> of
          <span class="text-teal-600">{{ pendingOrders?.length }}</span>
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>

<style scoped>
@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(-10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.animate-fade-in {
  animation: fadeIn 0.3s ease-out;
}
</style>
