<template>
  <DashboardLayout>
    <div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-50 p-6">
      <div class="max-w-7xl mx-auto">
        <!-- Header -->
        <div class="mb-8">
          <h1 class="text-4xl font-bold text-slate-900">On Delivery</h1>
          <p class="text-slate-600 mt-2">Track your active deliveries</p>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="flex items-center justify-center py-16">
          <div class="text-center">
            <div class="relative w-12 h-12">
              <svg class="absolute inset-0 w-full h-full" viewBox="0 0 100 100">
                <circle cx="50" cy="50" r="45" fill="none" stroke="#0EA5E9" stroke-width="6" opacity="0.3" />
              </svg>
              <div class="absolute inset-0 animate-spin" style="animation: spin 1.5s linear infinite;">
                <svg viewBox="0 0 100 100" class="w-full h-full">
                  <circle cx="50" cy="50" r="45" fill="none" stroke="#FBBF24" stroke-width="8" stroke-linecap="round" stroke-dasharray="70 280" />
                </svg>
              </div>
            </div>
            <p class="text-slate-700 dark:text-yellow-300 font-semibold text-sm mt-4">Loading deliveries...</p>
          </div>
        </div>

        <!-- Empty State -->
        <div v-else-if="paginatedDeliveries.length === 0" class="bg-white rounded-lg shadow-sm p-12 text-center">
          <p class="text-slate-600 text-lg">No active deliveries</p>
          <p class="text-slate-500 mt-2">You're all caught up!</p>
        </div>

        <!-- Deliveries Table -->
        <div v-else class="bg-white rounded-lg shadow-sm overflow-hidden">
          <table class="w-full">
            <thead class="bg-slate-50 border-b border-slate-200">
              <tr>
                <th class="px-6 py-4 text-left text-xs font-semibold text-slate-700 uppercase">Order ID</th>
                <th class="px-6 py-4 text-left text-xs font-semibold text-slate-700 uppercase">Room</th>
                <th class="px-6 py-4 text-left text-xs font-semibold text-slate-700 uppercase">Guest Name</th>
                <th class="px-6 py-4 text-left text-xs font-semibold text-slate-700 uppercase">Started At</th>
                <th class="px-6 py-4 text-left text-xs font-semibold text-slate-700 uppercase">Priority</th>
                <th class="px-6 py-4 text-left text-xs font-semibold text-slate-700 uppercase">Action</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="delivery in paginatedDeliveries" :key="delivery.id" class="border-b border-slate-100 hover:bg-slate-50">
                <td class="px-6 py-4 text-sm font-medium text-slate-900">#{{ delivery.order_number }}</td>
                <td class="px-6 py-4 text-sm text-slate-600">{{ delivery.room_number }}</td>
                <td class="px-6 py-4 text-sm text-slate-600">{{ delivery.guest_name }}</td>
                <td class="px-6 py-4 text-sm text-slate-600">{{ formatDateTime(delivery.on_delivery_at) }}</td>
                <td class="px-6 py-4 text-sm">
                  <span :class="getPriorityClass(delivery.priority)" class="px-3 py-1 rounded-full text-xs font-semibold">
                    {{ delivery.priority?.toUpperCase() || 'NORMAL' }}
                  </span>
                </td>
                <td class="px-6 py-4 text-sm">
                  <button
                    @click="completeDelivery(delivery.id)"
                    class="px-3 py-2 bg-green-600 text-white rounded hover:bg-green-700 transition font-medium text-xs"
                  >
                    Complete
                  </button>
                </td>
              </tr>
            </tbody>
          </table>

          <!-- Pagination -->
          <div class="bg-slate-50 px-6 py-4 border-t border-slate-200 flex items-center justify-between">
            <div class="text-sm text-slate-600">
              Showing {{ startIndex + 1 }} to {{ Math.min(endIndex, deliveries.length) }} of {{ deliveries.length }}
            </div>
            <div class="flex gap-2">
              <button
                @click="previousPage"
                :disabled="currentPage === 1"
                class="px-4 py-2 bg-slate-200 text-slate-700 rounded hover:bg-slate-300 disabled:opacity-50 disabled:cursor-not-allowed text-sm font-medium"
              >
                ← Previous
              </button>
              <div class="flex items-center gap-2">
                <span v-for="page in totalPages" :key="page" class="flex items-center gap-1">
                  <button
                    @click="goToPage(page)"
                    :class="page === currentPage ? 'bg-blue-600 text-white' : 'bg-slate-200 text-slate-700 hover:bg-slate-300'"
                    class="px-3 py-2 rounded text-sm font-medium"
                  >
                    {{ page }}
                  </button>
                </span>
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
const deliveries = ref<any[]>([])
const currentPage = ref(1)
const itemsPerPage = ref(10)

const totalPages = computed(() => Math.ceil(deliveries.value.length / itemsPerPage.value))
const startIndex = computed(() => (currentPage.value - 1) * itemsPerPage.value)
const endIndex = computed(() => startIndex.value + itemsPerPage.value)
const paginatedDeliveries = computed(() => deliveries.value.slice(startIndex.value, endIndex.value))

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

const getPriorityClass = (priority: string) => {
  switch (priority?.toLowerCase()) {
    case 'high':
      return 'bg-red-100 text-red-800'
    case 'medium':
      return 'bg-yellow-100 text-yellow-800'
    case 'low':
      return 'bg-green-100 text-green-800'
    default:
      return 'bg-blue-100 text-blue-800'
  }
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
    console.log('[OnDelivery] Loading active deliveries...')
    const data = await waiterService.getOnDelivery()
    console.log('[OnDelivery] Deliveries:', data)
    deliveries.value = data || []
    currentPage.value = 1
  } catch (err: any) {
    console.error('[OnDelivery] Error:', err)
    deliveries.value = []
  } finally {
    loading.value = false
  }
})

const completeDelivery = async (deliveryId: string) => {
  try {
    await waiterService.deliverOrder(deliveryId)
    console.log('[OnDelivery] Delivery completed:', deliveryId)
    const data = await waiterService.getOnDelivery()
    deliveries.value = data || []
    if (currentPage.value > totalPages.value) {
      currentPage.value = Math.max(1, totalPages.value)
    }
  } catch (err: any) {
    console.error('[OnDelivery] Error completing delivery:', err)
  }
}
</script>

<style scoped>
@keyframes spin {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}

.animate-spin {
  animation: spin 1.5s linear infinite;
}
</style>
