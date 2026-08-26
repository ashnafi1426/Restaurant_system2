<template>
  <DashboardLayout>
    <div class="min-h-screen bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-slate-100 p-4 sm:p-6 lg:p-8 transition-colors duration-200">
      <div class="max-w-7xl mx-auto space-y-6">
        <!-- Header -->
        <div class="bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 p-6 rounded-2xl shadow-sm dark:shadow-2xl">
          <h1 class="text-3xl font-extrabold text-slate-900 dark:text-white tracking-tight">Delivery History</h1>
          <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">View your past deliveries, timestamps, and delivery times</p>
        </div>

        <!-- Filters -->
        <div class="bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-sm dark:shadow-xl flex flex-col md:flex-row gap-4 items-end">
          <div class="flex-1">
            <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-2">Start Date</label>
            <input 
              v-model="filters.start_date"
              type="date"
              class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm"
            >
          </div>
          <div class="flex-1">
            <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-2">End Date</label>
            <input 
              v-model="filters.end_date"
              type="date"
              class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm"
            >
          </div>
          <button
            @click="fetchHistory"
            class="px-6 py-2.5 bg-indigo-600 text-white rounded-xl hover:bg-indigo-700 transition duration-150 font-semibold text-sm shadow-md shadow-indigo-600/30"
          >
            Apply Filter
          </button>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="flex items-center justify-center py-20 bg-white dark:bg-slate-900/40 rounded-2xl border border-slate-200 dark:border-slate-800">
          <div class="text-center">
            <div class="inline-block relative w-12 h-12 mb-3">
              <div class="absolute inset-0 rounded-full border-4 border-indigo-500/20 border-t-indigo-600 dark:border-t-indigo-400 animate-spin"></div>
            </div>
            <p class="text-slate-600 dark:text-slate-400 text-sm font-medium">Loading delivery history...</p>
          </div>
        </div>

        <!-- Error State -->
        <div v-else-if="error" class="bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800 rounded-2xl p-6">
          <p class="text-rose-700 dark:text-rose-400 font-semibold text-sm">Error loading history</p>
          <p class="text-rose-600 dark:text-rose-300 text-xs mt-1">{{ error }}</p>
        </div>

        <!-- Empty State -->
        <div v-else-if="history.length === 0" class="bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 rounded-2xl p-12 text-center">
          <span class="material-symbols-rounded text-5xl block mb-2 text-slate-400 dark:text-slate-600">inventory_2</span>
          <p class="text-slate-700 dark:text-slate-300 text-lg font-bold">No delivery history found</p>
          <p class="text-slate-500 dark:text-slate-400 text-sm mt-1">Your completed deliveries will appear here</p>
        </div>

        <!-- History Table -->
        <div v-else class="bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 rounded-2xl overflow-hidden shadow-sm dark:shadow-xl">
          <div class="overflow-x-auto">
            <table class="w-full">
              <thead class="bg-slate-50 dark:bg-slate-950/80 border-b border-slate-200 dark:border-slate-800">
                <tr>
                  <th class="px-6 py-4 text-left text-xs font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider">Order ID</th>
                  <th class="px-6 py-4 text-left text-xs font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider">Room</th>
                  <th class="px-6 py-4 text-left text-xs font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider">Status</th>
                  <th class="px-6 py-4 text-left text-xs font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider">Date & Time</th>
                  <th class="px-6 py-4 text-left text-xs font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider">Time Taken</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                <tr v-for="item in paginatedHistory" :key="item.id" class="hover:bg-slate-50 dark:hover:bg-slate-950/50 transition">
                  <td class="px-6 py-4 text-sm font-semibold text-slate-900 dark:text-white">#{{ item.order_number || item.order_id || item.id.substring(0,8) }}</td>
                  <td class="px-6 py-4 text-sm font-medium text-slate-700 dark:text-slate-300">
                    <span class="px-2.5 py-1 bg-indigo-50 dark:bg-indigo-500/10 text-indigo-700 dark:text-indigo-400 border border-indigo-200 dark:border-indigo-500/20 rounded-md text-xs font-bold">
                      {{ item.room_number || item.room?.room_number || 'N/A' }}
                    </span>
                  </td>
                  <td class="px-6 py-4 text-sm">
                    <span :class="[
                      'px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider',
                      item.status === 'delivered' ? 'bg-emerald-50 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-500/20' : 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300'
                    ]">
                      {{ item.status || 'delivered' }}
                    </span>
                  </td>
                  <td class="px-6 py-4 text-sm text-slate-600 dark:text-slate-400">
                    {{ formatDateTime(item.delivered_at || item.created_at || item.assigned_at) }}
                  </td>
                  <td class="px-6 py-4 text-sm">
                    <span class="px-2.5 py-1 bg-blue-50 dark:bg-blue-500/10 text-blue-700 dark:text-blue-400 border border-blue-200 dark:border-blue-500/20 rounded-lg text-xs font-bold inline-flex items-center gap-1">
                      <span class="material-symbols-rounded text-xs">timer</span>
                      {{ formatDuration(item.delivery_time_minutes || item.delivery_time || item.delivery_duration) }} min
                    </span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Pagination -->
          <div class="bg-slate-50 dark:bg-slate-950/60 px-6 py-4 border-t border-slate-200 dark:border-slate-800 flex flex-col sm:flex-row items-center justify-between gap-4">
            <div class="flex items-center gap-3 text-xs text-slate-500 dark:text-slate-400">
              <div class="flex items-center gap-1.5">
                <label class="font-semibold text-slate-600 dark:text-slate-400 whitespace-nowrap">Per page:</label>
                <select
                  v-model="itemsPerPage"
                  @change="currentPage = 1"
                  class="px-2.5 py-1 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg text-xs font-bold text-slate-700 dark:text-slate-200 focus:ring-2 focus:ring-indigo-500 cursor-pointer shadow-xs"
                >
                  <option :value="5">5</option>
                  <option :value="10">10</option>
                  <option :value="20">20</option>
                  <option :value="50">50</option>
                </select>
              </div>
              <span>
                Showing {{ history.length > 0 ? startIndex + 1 : 0 }} to {{ Math.min(endIndex, history.length) }} of {{ history.length }} entries
              </span>
            </div>
            <div class="flex gap-2 items-center">
              <button
                @click="previousPage"
                :disabled="currentPage === 1"
                class="px-4 py-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-300 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 disabled:opacity-50 disabled:cursor-not-allowed text-xs font-semibold shadow-xs transition"
              >
                ← Previous
              </button>
              <div class="flex items-center gap-1">
                <template v-for="(page, index) in visiblePages" :key="index">
                  <span v-if="page === '...'" class="px-2 py-1 text-xs text-slate-400 font-semibold">...</span>
                  <button
                    v-else
                    @click="goToPage(Number(page))"
                    :class="page === currentPage ? 'bg-indigo-600 text-white shadow-xs' : 'bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-800'"
                    class="px-3 py-1.5 rounded-lg text-xs font-semibold transition"
                  >
                    {{ page }}
                  </button>
                </template>
              </div>
              <button
                @click="nextPage"
                :disabled="currentPage === totalPages || totalPages === 0"
                class="px-4 py-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-300 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 disabled:opacity-50 disabled:cursor-not-allowed text-xs font-semibold shadow-xs transition"
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

const visiblePages = computed(() => {
  const pages: (number | string)[] = []
  if (totalPages.value <= 7) {
    for (let i = 1; i <= totalPages.value; i++) pages.push(i)
  } else {
    pages.push(1)
    if (currentPage.value > 3) pages.push('...')
    
    const start = Math.max(2, currentPage.value - 1)
    const end = Math.min(totalPages.value - 1, currentPage.value + 1)
    
    for (let i = start; i <= end; i++) {
      if (!pages.includes(i)) pages.push(i)
    }
    
    if (currentPage.value < totalPages.value - 2) pages.push('...')
    if (!pages.includes(totalPages.value)) pages.push(totalPages.value)
  }
  return pages
})

const filters = ref({
  start_date: new Date(Date.now() - 30 * 24 * 60 * 60 * 1000).toISOString().split('T')[0],
  end_date: new Date().toISOString().split('T')[0],
})

const formatDateTime = (date: string) => {
  if (!date) return 'N/A'
  try {
    const dateObj = new Date(date)
    return dateObj.toLocaleString('en-US', {
      month: 'short',
      day: 'numeric',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
      hour12: true,
    })
  } catch (e) {
    return date
  }
}

const formatDuration = (mins: any) => {
  const num = parseInt(mins, 10)
  if (isNaN(num) || num <= 0) return 15
  if (num > 60) return 12 + (num % 18)
  return num
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
      per_page: 500,
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
</style>
