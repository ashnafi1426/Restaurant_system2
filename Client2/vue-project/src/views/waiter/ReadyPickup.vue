<template>
  <DashboardLayout>
    <div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-50 p-6">
      <div class="max-w-7xl mx-auto">
        <!-- Header -->
        <div class="mb-8">
          <h1 class="text-4xl font-bold text-slate-900">Ready for Pickup</h1>
          <p class="text-slate-600 mt-2">Orders ready to be picked up from the kitchen</p>
        </div>

        <!-- Error State -->
        <div v-if="error" class="bg-red-50 border-l-4 border-red-600 rounded-lg p-6 mb-6">
          <p class="text-red-700 font-semibold">Error</p>
          <p class="text-red-600 text-sm mt-2">{{ error }}</p>
          <button 
            @click="retryLoad"
            class="mt-4 px-3 py-1 bg-red-600 text-white rounded text-sm hover:bg-red-700"
          >
            Retry
          </button>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="flex items-center justify-center py-16">
          <div class="text-center">
            <div class="relative w-12 h-12 mx-auto mb-4">
              <svg class="absolute inset-0 w-full h-full" viewBox="0 0 100 100">
                <circle cx="50" cy="50" r="45" fill="none" stroke="#0EA5E9" stroke-width="6" opacity="0.3" />
              </svg>
              <div class="absolute inset-0 animate-spin" style="animation: spin 1.5s linear infinite;">
                <svg viewBox="0 0 100 100" class="w-full h-full">
                  <circle cx="50" cy="50" r="45" fill="none" stroke="#FBBF24" stroke-width="8" stroke-linecap="round" stroke-dasharray="70 280" />
                </svg>
              </div>
            </div>
            <p class="text-slate-700 dark:text-yellow-300 font-semibold text-sm">Loading orders...</p>
          </div>
        </div>

        <!-- Empty State -->
        <div v-else-if="orders.length === 0" class="bg-white rounded-lg shadow-sm p-12 text-center">
          <p class="text-slate-600 text-lg">No items ready for pickup</p>
          <p class="text-slate-500 mt-2">Check back soon for new orders</p>
        </div>

        <!-- Orders Table -->
        <div v-else class="bg-white rounded-lg shadow-sm overflow-hidden">
          <table class="w-full">
            <thead class="bg-slate-50 border-b border-slate-200">
              <tr>
                <th class="px-6 py-4 text-left text-xs font-semibold text-slate-700 uppercase">Order Number</th>
                <th class="px-6 py-4 text-left text-xs font-semibold text-slate-700 uppercase">Guest Name</th>
                <th class="px-6 py-4 text-left text-xs font-semibold text-slate-700 uppercase">Room</th>
                <th class="px-6 py-4 text-left text-xs font-semibold text-slate-700 uppercase">Items</th>
                <th class="px-6 py-4 text-left text-xs font-semibold text-slate-700 uppercase">Special Requests</th>
                <th class="px-6 py-4 text-left text-xs font-semibold text-slate-700 uppercase">Action</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="order in paginatedOrders" :key="order.id" class="border-b border-slate-100 hover:bg-slate-50">
                <td class="px-6 py-4 text-sm font-medium text-slate-900">#{{ order.order_number }}</td>
                <td class="px-6 py-4 text-sm text-slate-600">{{ order.guest_name }}</td>
                <td class="px-6 py-4 text-sm text-slate-600">{{ order.room_number }}</td>
                <td class="px-6 py-4 text-sm">
                  <span class="px-2 py-1 bg-blue-100 text-blue-800 rounded text-xs font-medium">
                    {{ order.items }} items
                  </span>
                </td>
                <td class="px-6 py-4 text-sm text-slate-600">{{ order.special_requests || 'None' }}</td>
                <td class="px-6 py-4 text-sm">
                  <button
                    @click="pickupOrder(order.id)"
                    :disabled="loadingOrderId === order.id"
                    class="px-3 py-2 bg-green-600 text-white rounded hover:bg-green-700 transition font-medium text-xs disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-1"
                  >
                    <span v-if="loadingOrderId === order.id" class="inline-block w-3 h-3 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                    {{ loadingOrderId === order.id ? 'Picking...' : 'Pickup' }}
                  </button>
                </td>
              </tr>
            </tbody>
          </table>

          <!-- Pagination -->
          <div class="bg-slate-50 px-6 py-4 border-t border-slate-200 flex items-center justify-between">
            <div class="text-sm text-slate-600">
              Showing {{ startIndex + 1 }} to {{ Math.min(endIndex, orders.length) }} of {{ orders.length }}
            </div>
            <div class="flex gap-2">
              <button
                @click="previousPage"
                :disabled="currentPage === 1"
                class="px-4 py-2 bg-slate-200 text-slate-700 rounded hover:bg-slate-300 disabled:opacity-50 disabled:cursor-not-allowed text-sm font-medium"
              >
                ← Previous
              </button>
              <div class="flex items-center gap-1">
                <button
                  v-for="page in totalPages"
                  :key="page"
                  @click="goToPage(page)"
                  :class="page === currentPage ? 'bg-blue-600 text-white' : 'bg-slate-200 text-slate-700 hover:bg-slate-300'"
                  class="px-3 py-2 rounded text-sm font-medium"
                >
                  {{ page }}
                </button>
              </div>
              <button
                @click="nextPage"
                :disabled="currentPage === totalPages"
                class="px-4 py-2 bg-slate-200 text-slate-700 rounded hover:bg-slate-300 disabled:opacity-50 disabled:cursor-not-allowed text-sm font-medium"
              >
                Next →
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import waiterService from '@/services/waiterService'

const loading = ref(true)
const error = ref<string | null>(null)
const loadingOrderId = ref<string | null>(null)
const orders = ref<any[]>([])
const currentPage = ref(1)
const itemsPerPage = ref(10)

const totalPages = computed(() => Math.ceil(orders.value.length / itemsPerPage.value))
const startIndex = computed(() => (currentPage.value - 1) * itemsPerPage.value)
const endIndex = computed(() => startIndex.value + itemsPerPage.value)
const paginatedOrders = computed(() => orders.value.slice(startIndex.value, endIndex.value))

const previousPage = () => {
  if (currentPage.value > 1) currentPage.value--
}

const nextPage = () => {
  if (currentPage.value < totalPages.value) currentPage.value++
}

const goToPage = (page: number) => {
  currentPage.value = page
}

const loadOrders = async () => {
  try {
    loading.value = true
    error.value = null
    console.log('[ReadyPickup] Loading ready orders...')
    
    const data = await waiterService.getReadyForPickup()
    console.log('[ReadyPickup] Orders loaded:', data)
    orders.value = data || []
    currentPage.value = 1
    
    if (orders.value.length === 0) {
      console.warn('[ReadyPickup] No ready orders found')
    }
  } catch (err: any) {
    console.error('[ReadyPickup] Error loading orders:', {
      status: err.response?.status,
      message: err.response?.data?.message || err.message,
      error: err.response?.data?.error,
      fullResponse: err.response?.data,
    })
    error.value = err.response?.data?.message || err.message || 'Failed to load orders'
    orders.value = []
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  loadOrders()
})

const retryLoad = () => {
  loadOrders()
}

const pickupOrder = async (orderId: string) => {
  if (!orderId) {
    console.error('[ReadyPickup] No order ID provided')
    error.value = 'Order ID not found'
    return
  }
  
  try {
    loadingOrderId.value = orderId
    console.log('[ReadyPickup] Picking up order:', orderId)
    
    const response = await waiterService.pickupOrder(orderId)
    console.log('[ReadyPickup] Order picked up:', response)
    
    await loadOrders()
    error.value = null
  } catch (err: any) {
    console.error('[ReadyPickup] Error picking up order:', {
      orderId,
      status: err.response?.status,
      message: err.response?.data?.message || err.message,
      error: err.response?.data?.error,
      fullResponse: err.response?.data,
    })
    error.value = `Failed to pickup order: ${err.response?.data?.message || err.message}`
  } finally {
    loadingOrderId.value = null
  }
}
</script>

<style scoped>
</style>
