<template>
  <DashboardLayout>
    <div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-50 p-6">
      <div class="max-w-7xl mx-auto">
        <!-- Header -->
        <div class="mb-8">
          <h1 class="text-4xl font-bold text-slate-900">Delivery History</h1>
          <p class="text-slate-600 mt-2">View your past deliveries and performance</p>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-lg shadow-sm p-4 mb-6 flex flex-col md:flex-row gap-4 items-end">
          <div class="flex-1">
            <label class="block text-sm font-medium text-slate-700 mb-1">Start Date</label>
            <input 
              v-model="filters.start_date"
              type="date"
              class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            >
          </div>
          <div class="flex-1">
            <label class="block text-sm font-medium text-slate-700 mb-1">End Date</label>
            <input 
              v-model="filters.end_date"
              type="date"
              class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            >
          </div>
          <button
            @click="fetchHistory"
            class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-medium"
          >
            Filter
          </button>
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
            <p class="text-slate-700 dark:text-yellow-300 font-semibold text-sm mt-4">Loading history...</p>
          </div>
        </div>

        <!-- Error State -->
        <div v-else-if="error" class="bg-red-50 border-l-4 border-red-600 rounded-lg p-6 mb-6">
          <p class="text-red-700 font-semibold">Error loading history</p>
          <p class="text-red-600 text-sm mt-2">{{ error }}</p>
        </div>

        <!-- Empty State -->
        <div v-else-if="history.length === 0" class="bg-white rounded-lg shadow-sm p-12 text-center">
          <p class="text-slate-600 text-lg">No delivery history found</p>
          <p class="text-slate-500 mt-2">Your completed deliveries will appear here</p>
        </div>

        <!-- History Table -->
        <div v-else class="bg-white rounded-lg shadow-sm overflow-hidden">
          <div class="overflow-x-auto">
            <table class="w-full">
              <thead class="bg-slate-50 border-b border-slate-200">
                <tr>
                  <th class="px-6 py-4 text-left text-xs font-semibold text-slate-700 uppercase">Order ID</th>
                  <th class="px-6 py-4 text-left text-xs font-semibold text-slate-700 uppercase">Room</th>
                  <th class="px-6 py-4 text-left text-xs font-semibold text-slate-700 uppercase">Status</th>
                  <th class="px-6 py-4 text-left text-xs font-semibold text-slate-700 uppercase">Date & Time</th>
                  <th class="px-6 py-4 text-left text-xs font-semibold text-slate-700 uppercase">Time Taken</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-200">
                <tr v-for="item in paginatedHistory" :key="item.id" class="hover:bg-slate-50 transition">
                  <td class="px-6 py-4 text-sm font-medium text-slate-900">#{{ item.order_id }}</td>
                  <td class="px-6 py-4 text-sm text-slate-600">{{ item.room_number || 'N/A' }}</td>
                  <td class="px-6 py-4 text-sm">
                    <span :class="[
                      'px-3 py-1 rounded-full text-xs font-semibold',
                      item.status === 'delivered' ? 'bg-green-100 text-green-800' : 'bg-slate-100 text-slate-800'
                    ]">
                      {{ item.status?.toUpperCase() }}
                    </span>
                  </td>
                  <td class="px-6 py-4 text-sm text-slate-600">{{ formatDateTime(item.created_at) }}</td>
                  <td class="px-6 py-4 text-sm">
                    <span class="px-2 py-1 bg-blue-100 text-blue-800 rounded text-xs font-medium">
                      {{ item.delivery_time || '-' }} min
                    </span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Pagination -->
          <div class="bg-slate-50 px-6 py-4 border-t border-slate-200 flex items-center justify-between">
            <div class="text-sm text-slate-600">
              Showing {{ startIndex + 1 }} to {{ Math.min(endIndex, history.length) }} of {{ history.length }}
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
const history = ref<any[]>([])
const currentPage = ref(1)
const itemsPerPage = ref(10)

const totalPages = computed(() => Math.ceil(history.value.length / itemsPerPage.value))
const startIndex = computed(() => (currentPage.value - 1) * itemsPerPage.value)
const endIndex = computed(() => startIndex.value + itemsPerPage.value)
const paginatedHistory = computed(() => history.value.slice(startIndex.value, endIndex.value))

const filters = ref({
  start_date: new Date(Date.now() - 30 * 24 * 60 * 60 * 1000).toISOString().split('T')[0],
  end_date: new Date().toISOString().split('T')[0],
})

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

const fetchHistory = async () => {
  try {
    loading.value = true
    error.value = null
    
    console.log('[DeliveryHistory] Loading history with filters:', filters.value)
    const result = await waiterService.getHistory({
      start_date: filters.value.start_date,
      end_date: filters.value.end_date,
    })
    
    console.log('[DeliveryHistory] History data:', result.data)
    history.value = result.data || []
    currentPage.value = 1
  } catch (err: any) {
    console.error('[DeliveryHistory] Error:', err)
    error.value = err.message || 'Failed to load history'
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchHistory()
})
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
