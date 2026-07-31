<template>
  <DashboardLayout>
    <div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-50 p-6">
      <div class="max-w-7xl mx-auto">
        <!-- Header -->
        <div class="mb-8">
          <h1 class="text-4xl font-bold text-slate-900">Assigned Orders</h1>
          <p class="text-slate-600 mt-2">Manage your assigned orders and deliveries</p>
          <div v-if="!loading && assignments.length > 0" class="mt-4 text-sm text-slate-600">
            Showing {{ startIndex + 1 }} to {{ Math.min(startIndex + itemsPerPage, totalAssignments) }} of {{ totalAssignments }} orders
          </div>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="flex items-center justify-center py-16">
          <div class="text-center">
            <div class="animate-spin rounded-full h-12 w-12 border-4 border-slate-200 border-t-blue-600 mx-auto mb-4"></div>
            <p class="text-slate-600 font-medium">Loading orders...</p>
          </div>
        </div>

        <!-- Error State -->
        <div v-if="error && !loading" class="bg-red-50 border-l-4 border-red-600 rounded-lg p-6 mb-6">
          <p class="text-red-700 font-semibold">Error</p>
          <p class="text-red-600 text-sm mt-2">{{ error }}</p>
          <button 
            @click="retryLoad"
            class="mt-4 px-3 py-1 bg-red-600 text-white rounded text-sm hover:bg-red-700"
          >
            Retry
          </button>
        </div>

        <!-- Empty State -->
        <div v-else-if="assignments.length === 0 && !loading" class="bg-white rounded-lg shadow-sm p-12 text-center">
          <p class="text-slate-600 text-lg">No assigned orders yet</p>
          <p class="text-slate-500 mt-2">Check back later for new assignments</p>
        </div>

        <!-- Orders Table -->
        <div v-else class="bg-white rounded-lg shadow-sm overflow-hidden">
          <div class="overflow-x-auto">
            <table class="w-full">
              <thead class="bg-slate-100 border-b border-slate-200">
                <tr>
                  <th class="px-6 py-4 text-left text-sm font-semibold text-slate-900">Order #</th>
                  <th class="px-6 py-4 text-left text-sm font-semibold text-slate-900">Room</th>
                  <th class="px-6 py-4 text-left text-sm font-semibold text-slate-900">Guest Name</th>
                  <th class="px-6 py-4 text-left text-sm font-semibold text-slate-900">Items</th>
                  <th class="px-6 py-4 text-left text-sm font-semibold text-slate-900">Status</th>
                  <th class="px-6 py-4 text-left text-sm font-semibold text-slate-900">Assigned Time</th>
                  <th class="px-6 py-4 text-left text-sm font-semibold text-slate-900">Actions</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-200">
                <tr v-for="order in paginatedAssignments" :key="order.id" class="hover:bg-slate-50 transition">
                  <td class="px-6 py-4 text-sm text-slate-900 font-semibold">{{ order.order_number }}</td>
                  <td class="px-6 py-4 text-sm text-slate-600">{{ order.room_number || 'N/A' }}</td>
                  <td class="px-6 py-4 text-sm text-slate-600">{{ order.guest_name || 'N/A' }}</td>
                  <td class="px-6 py-4 text-sm text-slate-600">{{ order.items }} items</td>
                  <td class="px-6 py-4 text-sm">
                    <span :class="[
                      'px-3 py-1 rounded-full text-xs font-semibold',
                      order.order_status === 'assigned' ? 'bg-amber-100 text-amber-800' : 
                      order.order_status === 'accepted' ? 'bg-blue-100 text-blue-800' :
                      order.order_status === 'picked_up' ? 'bg-purple-100 text-purple-800' :
                      order.order_status === 'on_delivery' ? 'bg-green-100 text-green-800' :
                      'bg-slate-100 text-slate-800'
                    ]">
                      {{ order.order_status }}
                    </span>
                  </td>
                  <td class="px-6 py-4 text-sm text-slate-600">{{ formatDateTime(order.assigned_at) }}</td>
                  <td class="px-6 py-4 text-sm">
                    <div class="flex gap-2">
                      <button
                        @click="selectedOrder = order; showDetailModal = true"
                        class="px-3 py-1 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition text-xs font-medium whitespace-nowrap"
                      >
                        View Details
                      </button>
                      <button
                        v-if="order.order_status === 'assigned'"
                        @click="acceptOrder(order.id)"
                        :disabled="loadingOrderId === order.id"
                        class="px-3 py-1 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-xs font-medium disabled:opacity-50 disabled:cursor-not-allowed whitespace-nowrap"
                      >
                        {{ loadingOrderId === order.id ? 'Accepting...' : 'Accept' }}
                      </button>
                      <button
                        v-if="order.order_status === 'accepted'"
                        @click="pickupOrder(order.id)"
                        :disabled="loadingOrderId === order.id"
                        class="px-3 py-1 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition text-xs font-medium disabled:opacity-50 disabled:cursor-not-allowed whitespace-nowrap"
                      >
                        {{ loadingOrderId === order.id ? 'Picking up...' : 'Pickup Order' }}
                      </button>
                      <button
                        v-if="order.order_status === 'picked_up'"
                        @click="startDelivery(order.id)"
                        :disabled="loadingOrderId === order.id"
                        class="px-3 py-1 bg-green-600 text-white rounded-lg hover:bg-green-700 transition text-xs font-medium disabled:opacity-50 disabled:cursor-not-allowed whitespace-nowrap"
                      >
                        {{ loadingOrderId === order.id ? 'Starting...' : 'Start Delivery' }}
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Pagination Controls -->
          <div class="bg-slate-50 border-t border-slate-200 px-6 py-4 flex items-center justify-between">
            <div class="text-sm text-slate-600">
              Page {{ currentPage }} of {{ totalPages }} ({{ totalAssignments }} total)
            </div>
            <div class="flex gap-2 items-center">
              <!-- Items per page selector -->
              <select 
                v-model.number="itemsPerPage"
                @change="currentPage = 1"
                class="px-3 py-1 border border-slate-300 rounded-lg text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-600"
              >
                <option value="5">5 per page</option>
                <option value="10">10 per page</option>
                <option value="20">20 per page</option>
                <option value="50">50 per page</option>
              </select>

              <!-- Previous button -->
              <button
                @click="previousPage"
                :disabled="currentPage === 1"
                class="px-3 py-1 border border-slate-300 rounded-lg text-sm font-medium text-slate-700 hover:bg-slate-100 transition disabled:opacity-50 disabled:cursor-not-allowed"
              >
                ← Previous
              </button>

              <!-- Page numbers -->
              <div class="flex gap-1">
                <button
                  v-for="pageNum in visiblePages"
                  :key="pageNum"
                  @click="currentPage = pageNum"
                  :class="[
                    'px-3 py-1 rounded-lg text-sm font-medium transition',
                    currentPage === pageNum
                      ? 'bg-blue-600 text-white'
                      : 'border border-slate-300 text-slate-700 hover:bg-slate-100'
                  ]"
                >
                  {{ pageNum }}
                </button>
              </div>

              <!-- Next button -->
              <button
                @click="nextPage"
                :disabled="currentPage === totalPages"
                class="px-3 py-1 border border-slate-300 rounded-lg text-sm font-medium text-slate-700 hover:bg-slate-100 transition disabled:opacity-50 disabled:cursor-not-allowed"
              >
                Next →
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Order Detail Modal -->
    <div v-if="showDetailModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
        <!-- Modal Header -->
        <div class="sticky top-0 bg-gradient-to-r from-indigo-600 to-blue-600 text-white p-6 flex justify-between items-center">
          <div>
            <h2 class="text-2xl font-bold">Order Details</h2>
            <p class="text-indigo-100 mt-1">{{ selectedOrder?.order_number }}</p>
          </div>
          <button
            @click="showDetailModal = false"
            class="text-white hover:bg-white/20 p-2 rounded-lg transition"
          >
            ✕
          </button>
        </div>

        <!-- Modal Error Alert -->
        <div v-if="error" class="bg-red-50 border-b-2 border-red-600 p-4">
          <p class="text-red-700 font-semibold">⚠️ Error</p>
          <p class="text-red-600 text-sm mt-1">{{ error }}</p>
        </div>

        <!-- Modal Success Alert -->
        <div v-if="!error && loadingOrderId === null && selectedOrder?.id" class="bg-green-50 border-b-2 border-green-600 p-4">
          <p class="text-green-700 font-semibold">✓ Order Updated Successfully</p>
        </div>

        <!-- Modal Content -->
        <div class="p-6 space-y-6">
          <!-- Order Status Section -->
          <div class="bg-slate-50 rounded-lg p-4 border border-slate-200">
            <h3 class="font-semibold text-slate-900 mb-3">Order Status</h3>
            <div class="grid grid-cols-2 gap-4">
              <div>
                <p class="text-xs text-slate-600 uppercase font-semibold">Status</p>
                <span :class="[
                  'px-3 py-1 rounded-full text-sm font-semibold inline-block mt-1',
                  selectedOrder?.order_status === 'assigned' ? 'bg-amber-100 text-amber-800' : 
                  selectedOrder?.order_status === 'accepted' ? 'bg-blue-100 text-blue-800' :
                  selectedOrder?.order_status === 'picked_up' ? 'bg-purple-100 text-purple-800' :
                  selectedOrder?.order_status === 'on_delivery' ? 'bg-green-100 text-green-800' :
                  'bg-slate-100 text-slate-800'
                ]">
                  {{ selectedOrder?.order_status }}
                </span>
              </div>
              <div>
                <p class="text-xs text-slate-600 uppercase font-semibold">Assigned Time</p>
                <p class="text-slate-900 font-semibold mt-1">{{ selectedOrder?.assigned_at }}</p>
              </div>
            </div>
          </div>

          <!-- Guest & Room Information -->
          <div class="bg-slate-50 rounded-lg p-4 border border-slate-200">
            <h3 class="font-semibold text-slate-900 mb-3">Delivery Information</h3>
            <div class="grid grid-cols-2 gap-4">
              <div>
                <p class="text-xs text-slate-600 uppercase font-semibold">Guest Name</p>
                <p class="text-slate-900 font-semibold mt-1">{{ selectedOrder?.guest_name }}</p>
              </div>
              <div>
                <p class="text-xs text-slate-600 uppercase font-semibold">Room Number</p>
                <p class="text-slate-900 font-semibold mt-1">{{ selectedOrder?.room_number }}</p>
              </div>
            </div>
            <div v-if="selectedOrder?.special_requests && selectedOrder.special_requests !== 'None'" class="mt-4 pt-4 border-t border-slate-200">
              <p class="text-xs text-slate-600 uppercase font-semibold">Special Requests</p>
              <p class="text-slate-900 mt-1 italic">{{ selectedOrder?.special_requests }}</p>
            </div>
          </div>

          <!-- Items Section -->
          <div class="bg-slate-50 rounded-lg p-4 border border-slate-200">
            <h3 class="font-semibold text-slate-900 mb-3">Order Items ({{ selectedOrder?.items }})</h3>
            <div v-if="selectedOrder?.items_detail && selectedOrder.items_detail.length > 0" class="space-y-2">
              <div v-for="(item, index) in selectedOrder.items_detail" :key="index" class="bg-white rounded-lg p-3 border border-slate-200">
                <div class="flex justify-between items-start">
                  <div class="flex-1">
                    <p class="font-semibold text-slate-900">{{ item.name }}</p>
                    <p class="text-sm text-slate-600 mt-1">Quantity: <span class="font-semibold">{{ item.quantity }}</span></p>
                  </div>
                  <div class="text-right">
                    <p class="text-xs text-slate-600">Item</p>
                  </div>
                </div>
                <div v-if="item.notes && item.notes !== 'None'" class="mt-2 pt-2 border-t border-slate-200">
                  <p class="text-xs text-slate-600 font-semibold">Notes</p>
                  <p class="text-sm text-slate-700 italic">{{ item.notes }}</p>
                </div>
              </div>
            </div>
            <div v-else class="text-center py-4 text-slate-500">
              No items found
            </div>
          </div>

          <!-- Wait Time -->
          <div class="bg-slate-50 rounded-lg p-4 border border-slate-200">
            <p class="text-xs text-slate-600 uppercase font-semibold">Wait Time</p>
            <p class="text-2xl font-bold text-indigo-600 mt-1">{{ selectedOrder?.wait_time_minutes }} minutes</p>
          </div>

          <!-- Action Buttons -->
          <div class="bg-slate-50 rounded-lg p-4 border border-slate-200">
            <h3 class="font-semibold text-slate-900 mb-3">Actions</h3>
            <div class="flex gap-3 flex-wrap">
              <button
                v-if="selectedOrder?.order_status === 'assigned'"
                @click="handleAcceptFromModal"
                :disabled="loadingOrderId === selectedOrder?.id"
                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-medium disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
              >
                <span v-if="loadingOrderId === selectedOrder?.id" class="inline-block w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                {{ loadingOrderId === selectedOrder?.id ? 'Accepting...' : 'Accept Order' }}
              </button>
              <button
                v-if="selectedOrder?.order_status === 'accepted'"
                @click="handlePickupFromModal"
                :disabled="loadingOrderId === selectedOrder?.id"
                class="px-4 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition font-medium disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
              >
                <span v-if="loadingOrderId === selectedOrder?.id" class="inline-block w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                {{ loadingOrderId === selectedOrder?.id ? 'Picking up...' : 'Pickup Order' }}
              </button>
              <button
                v-if="selectedOrder?.order_status === 'picked_up'"
                @click="handleDeliveryFromModal"
                :disabled="loadingOrderId === selectedOrder?.id"
                class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition font-medium disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
              >
                <span v-if="loadingOrderId === selectedOrder?.id" class="inline-block w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                {{ loadingOrderId === selectedOrder?.id ? 'Starting...' : 'Start Delivery' }}
              </button>
              <button
                @click="showDetailModal = false"
                :disabled="loadingOrderId !== null"
                class="px-4 py-2 bg-slate-300 text-slate-900 rounded-lg hover:bg-slate-400 transition font-medium disabled:opacity-50"
              >
                Close
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
const assignments = ref<any[]>([])
const showDetailModal = ref(false)
const selectedOrder = ref<any>(null)

// Pagination state
const currentPage = ref(1)
const itemsPerPage = ref(10)

// Computed properties for pagination
const totalAssignments = computed(() => assignments.value.length)

const totalPages = computed(() => {
  return Math.ceil(totalAssignments.value / itemsPerPage.value)
})

const startIndex = computed(() => {
  return (currentPage.value - 1) * itemsPerPage.value
})

const paginatedAssignments = computed(() => {
  const end = startIndex.value + itemsPerPage.value
  return assignments.value.slice(startIndex.value, end)
})

const visiblePages = computed(() => {
  const pages = []
  const maxVisible = 5
  let start = Math.max(1, currentPage.value - Math.floor(maxVisible / 2))
  let end = Math.min(totalPages.value, start + maxVisible - 1)
  
  // Adjust if we're near the end
  if (end - start + 1 < maxVisible) {
    start = Math.max(1, end - maxVisible + 1)
  }
  
  for (let i = start; i <= end; i++) {
    pages.push(i)
  }
  return pages
})

const loadAssignments = async () => {
  try {
    loading.value = true
    error.value = null
    console.log('[AssignedOrders] Loading assignments...')
    
    const data = await waiterService.getRecentAssignments(100)
    console.log('[AssignedOrders] ✅ Assignments loaded:', data)
    assignments.value = data || []
    currentPage.value = 1 // Reset to first page
    
    if (assignments.value.length === 0) {
      console.warn('[AssignedOrders] ⚠️  No assignments found')
    }
  } catch (err: any) {
    console.error('[AssignedOrders] ❌ Error loading assignments:', {
      status: err.response?.status,
      message: err.response?.data?.message || err.message,
      error: err.response?.data?.error,
    })
    error.value = err.message || 'Failed to load orders'
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  loadAssignments()
})

const formatDateTime = (dateTime: string) => {
  if (!dateTime) return 'N/A'
  const date = new Date(dateTime)
  return date.toLocaleString('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit',
    hour12: true,
  })
}

const retryLoad = () => {
  loadAssignments()
}

const nextPage = () => {
  if (currentPage.value < totalPages.value) {
    currentPage.value++
  }
}

const previousPage = () => {
  if (currentPage.value > 1) {
    currentPage.value--
  }
}

const acceptOrder = async (orderId: string) => {
  if (!orderId) {
    console.error('[AssignedOrders] ❌ No order ID provided')
    error.value = 'No order ID provided'
    return
  }
  
  try {
    loadingOrderId.value = orderId
    console.log('[AssignedOrders] Accepting order:', orderId)
    console.log('[AssignedOrders] Order data:', assignments.value.find(o => o.id === orderId))
    
    // Use the API endpoint directly with proper error handling
    const response = await waiterService.acceptAssignment(orderId)
    console.log('[AssignedOrders] ✅ Order accepted:', response)
    
    // Show success message
    error.value = null
    
    // Reload assignments
    await loadAssignments()
  } catch (err: any) {
    console.error('[AssignedOrders] ❌ Error accepting order:', {
      orderId,
      status: err.response?.status,
      message: err.response?.data?.message || err.message,
      error: err.response?.data?.error,
      fullResponse: err.response?.data,
    })
    error.value = `Error: ${err.response?.data?.message || err.message}`
  } finally {
    loadingOrderId.value = null
  }
}

const startDelivery = async (orderId: string) => {
  if (!orderId) {
    console.error('[AssignedOrders] ❌ No order ID provided for delivery')
    error.value = 'No order ID provided for delivery'
    return
  }
  
  try {
    loadingOrderId.value = orderId
    console.log('[AssignedOrders] Starting delivery for order:', orderId)
    console.log('[AssignedOrders] Full order data:', assignments.value.find(o => o.id === orderId))
    
    const response = await waiterService.startDelivery(orderId)
    console.log('[AssignedOrders] ✅ Delivery started successfully:', response)
    
    // Show success message
    error.value = null
    
    // Reload assignments to update status
    await loadAssignments()
    console.log('[AssignedOrders] ✅ Assignments reloaded after delivery start')
  } catch (err: any) {
    console.error('[AssignedOrders] ❌ Error starting delivery:', {
      orderId,
      status: err.response?.status,
      statusText: err.response?.statusText,
      message: err.response?.data?.message || err.message,
      error: err.response?.data?.error,
      fullResponse: err.response?.data,
    })
    error.value = `Error: ${err.response?.data?.message || err.message}`
  } finally {
    loadingOrderId.value = null
  }
}

const pickupOrder = async (orderId: string) => {
  if (!orderId) {
    console.error('[AssignedOrders] ❌ No order ID provided for pickup')
    error.value = 'No order ID provided for pickup'
    return
  }
  
  try {
    loadingOrderId.value = orderId
    console.log('[AssignedOrders] Picking up order:', orderId)
    console.log('[AssignedOrders] Full order data:', assignments.value.find(o => o.id === orderId))
    
    const response = await waiterService.pickupOrder(orderId)
    console.log('[AssignedOrders] ✅ Order picked up successfully:', response)
    
    // Show success message
    error.value = null
    
    // Reload assignments to update status
    await loadAssignments()
    console.log('[AssignedOrders] ✅ Assignments reloaded after pickup')
  } catch (err: any) {
    console.error('[AssignedOrders] ❌ Error picking up order:', {
      orderId,
      status: err.response?.status,
      statusText: err.response?.statusText,
      message: err.response?.data?.message || err.message,
      error: err.response?.data?.error,
      fullResponse: err.response?.data,
    })
    error.value = `Error: ${err.response?.data?.message || err.message}`
  } finally {
    loadingOrderId.value = null
  }
}

const handleAcceptFromModal = async () => {
  if (selectedOrder.value?.id) {
    await acceptOrder(selectedOrder.value.id)
    // Close modal after successful action
    if (!error.value) {
      showDetailModal.value = false
    }
  }
}

const handlePickupFromModal = async () => {
  if (selectedOrder.value?.id) {
    await pickupOrder(selectedOrder.value.id)
    // Close modal after successful action
    if (!error.value) {
      showDetailModal.value = false
    }
  }
}

const handleDeliveryFromModal = async () => {
  if (selectedOrder.value?.id) {
    await startDelivery(selectedOrder.value.id)
    // Close modal after successful action
    if (!error.value) {
      showDetailModal.value = false
    }
  }
}
</script>

<style scoped>
</style>
