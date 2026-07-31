<template>
  <DashboardLayout>
    <div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-50 p-6">
      <div class="max-w-7xl mx-auto">
        <!-- Header -->
        <div class="mb-8">
          <h1 class="text-4xl font-bold text-slate-900">Completed Orders</h1>
          <p class="text-slate-600 mt-2">View your delivery history</p>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="flex items-center justify-center py-16">
          <div class="text-center">
            <div class="animate-spin rounded-full h-12 w-12 border-4 border-slate-200 border-t-blue-600 mx-auto mb-4"></div>
            <p class="text-slate-600 font-medium">Loading completed orders...</p>
          </div>
        </div>

        <!-- Empty State -->
        <div v-else-if="completed.length === 0" class="bg-white rounded-lg shadow-sm p-12 text-center">
          <p class="text-slate-600 text-lg">No completed orders yet</p>
          <p class="text-slate-500 mt-2">Your completed deliveries will appear here</p>
        </div>

        <!-- Completed Orders Table -->
        <div v-else class="bg-white rounded-lg shadow-sm overflow-hidden">
          <table class="w-full">
            <thead class="bg-slate-50 border-b border-slate-200">
              <tr>
                <th class="px-6 py-4 text-left text-xs font-semibold text-slate-700 uppercase">Order ID</th>
                <th class="px-6 py-4 text-left text-xs font-semibold text-slate-700 uppercase">Room</th>
                <th class="px-6 py-4 text-left text-xs font-semibold text-slate-700 uppercase">Guest</th>
                <th class="px-6 py-4 text-left text-xs font-semibold text-slate-700 uppercase">Completed</th>
                <th class="px-6 py-4 text-left text-xs font-semibold text-slate-700 uppercase">Delivery Time</th>
                <th class="px-6 py-4 text-left text-xs font-semibold text-slate-700 uppercase">Remarks</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="order in paginatedCompleted" :key="order.id" class="border-b border-slate-100 hover:bg-slate-50">
                <td class="px-6 py-4 text-sm font-medium text-slate-900">#{{ order.order_number }}</td>
                <td class="px-6 py-4 text-sm text-slate-600">{{ order.room_number }}</td>
                <td class="px-6 py-4 text-sm text-slate-600">{{ order.guest_name }}</td>
                <td class="px-6 py-4 text-sm text-slate-600">{{ formatDateTime(order.delivered_at) }}</td>
                <td class="px-6 py-4 text-sm">
                  <span class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-xs font-semibold">
                    {{ order.delivery_time_minutes || 'N/A' }} min
                  </span>
                </td>
                <td class="px-6 py-4 text-sm text-slate-600">{{ order.remarks || 'None' }}</td>
              </tr>
            </tbody>
          </table>

          <!-- Pagination -->
          <div class="bg-slate-50 px-6 py-4 border-t border-slate-200 flex items-center justify-between">
            <div class="text-sm text-slate-600">
              Showing {{ startIndex + 1 }} to {{ Math.min(endIndex, completed.length) }} of {{ completed.length }}
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
const completed = ref<any[]>([])
const currentPage = ref(1)
const itemsPerPage = ref(10)

const totalPages = computed(() => Math.ceil(completed.value.length / itemsPerPage.value))
const startIndex = computed(() => (currentPage.value - 1) * itemsPerPage.value)
const endIndex = computed(() => startIndex.value + itemsPerPage.value)
const paginatedCompleted = computed(() => completed.value.slice(startIndex.value, endIndex.value))

const formatDateTime = (date: string) => {
  if (!date) return 'N/A'
  const dateObj = new Date(date)
  return dateObj.toLocaleString('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit',
    hour12: true,
  })
}

const previousPage = () => {
  if (currentPage.value > 1) currentPage.value--
}

const nextPage = () => {
  if (currentPage.value < totalPages.value) currentPage.value++
}

const goToPage = (page: number) => {
  currentPage.value = page
}

onMounted(async () => {
  try {
    loading.value = true
    console.log('[CompletedOrders] Loading completed orders...')
    
    const data = await waiterService.getCompletedDeliveries(100)
    console.log('[CompletedOrders] Completed:', data)
    completed.value = data || []
    currentPage.value = 1
  } catch (err: any) {
    console.error('[CompletedOrders] Error:', err)
    completed.value = []
  } finally {
    loading.value = false
  }
})
</script>

<style scoped>
</style>
