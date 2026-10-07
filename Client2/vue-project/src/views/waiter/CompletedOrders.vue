<script setup lang="ts">
import { ref, onMounted, computed, watch } from 'vue'
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import waiterService from '@/services/waiterService'
import { useHotelStore } from '@/stores/hotelStore'
import { useLanguageStore } from '@/stores/language'
import { Building2, CheckCheck, Inbox, Timer } from 'lucide-vue-next'

const hotelStore = useHotelStore()
const languageStore = useLanguageStore()

const loading = ref(true)
const completed = ref<any[]>([])
const currentPage = ref(1)
const itemsPerPage = ref(10)

const totalPages = computed(() => Math.ceil(completed.value.length / itemsPerPage.value))
const startIndex = computed(() => (currentPage.value - 1) * itemsPerPage.value)
const endIndex = computed(() => startIndex.value + itemsPerPage.value)
const paginatedCompleted = computed(() => completed.value.slice(startIndex.value, endIndex.value))

const visiblePages = computed(() => {
  const pages: (number | string)[] = []
  const total = totalPages.value
  const current = currentPage.value

  if (total <= 7) {
    for (let i = 1; i <= total; i++) pages.push(i)
  } else {
    pages.push(1)
    if (current > 3) pages.push('...')
    
    const start = Math.max(2, current - 1)
    const end = Math.min(total - 1, current + 1)
    for (let i = start; i <= end; i++) {
      if (!pages.includes(i)) pages.push(i)
    }
    
    if (current < total - 2) pages.push('...')
    if (!pages.includes(total)) pages.push(total)
  }
  return pages
})

const formatDateTime = (date: string) => {
  if (!date) return '—'
  const dateObj = new Date(date)
  if (isNaN(dateObj.getTime())) return date
  return dateObj.toLocaleString('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
    hour12: true,
  })
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

const loadData = async () => {
  try {
    loading.value = true
    const data = await waiterService.getCompletedDeliveries(100)
    completed.value = data || []
    currentPage.value = 1
  } catch (err: any) {
    console.error('[CompletedOrders] Error:', err)
    completed.value = []
  } finally {
    loading.value = false
  }
}

watch(itemsPerPage, () => {
  currentPage.value = 1
})

onMounted(loadData)

watch(() => hotelStore.hotelId, loadData)
</script>

<template>
  <DashboardLayout>
    <div class="min-h-screen bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-slate-100 p-4 sm:p-6 lg:p-8 transition-colors duration-200 font-sans">
      <div class="max-w-7xl mx-auto space-y-6">
        <!-- Header -->
        <div class="bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 p-6 rounded-2xl shadow-sm dark:shadow-2xl">
          <div class="flex items-center gap-3">
            <div class="p-3 bg-emerald-50 dark:bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 rounded-xl border border-emerald-100 dark:border-emerald-500/30 shadow-sm">
              <CheckCheck class="w-6 h-6" />
            </div>
            <div>
              <div class="flex items-center gap-2">
                <h1 class="text-3xl font-extrabold text-slate-900 dark:text-white tracking-tight">{{ languageStore.t('completed_orders', 'Completed Orders') }}</h1>
                <span v-if="hotelStore.hotelName" class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300 border border-blue-200 dark:border-blue-700/50">
                  <Building2 class="w-3 h-3" />
                  {{ hotelStore.hotelName }}
                </span>
              </div>
              <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">{{ languageStore.t('completed_orders_desc', 'View your delivered order history, room numbers, and customer details') }}</p>
            </div>
          </div>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="flex items-center justify-center py-20 bg-white dark:bg-slate-900/40 rounded-2xl border border-slate-200 dark:border-slate-800">
          <div class="text-center">
            <div class="inline-block relative w-12 h-12 mb-3">
              <div class="absolute inset-0 rounded-full border-4 border-emerald-500/20 border-t-emerald-600 dark:border-t-emerald-400 animate-spin"></div>
            </div>
            <p class="text-slate-600 dark:text-slate-400 text-sm font-medium">{{ languageStore.t('loading_completed_orders', 'Loading completed orders...') }}</p>
          </div>
        </div>

        <!-- Empty State -->
        <div v-else-if="completed.length === 0" class="bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 rounded-2xl p-12 text-center">
          <Inbox class="w-12 h-12 text-slate-400 dark:text-slate-600 mx-auto mb-2" />
          <p class="text-slate-700 dark:text-slate-300 text-lg font-bold">{{ languageStore.t('no_completed_orders_yet', 'No completed orders yet') }}</p>
          <p class="text-slate-500 dark:text-slate-400 text-sm mt-1">{{ languageStore.t('completed_deliveries_appear_here', 'Your completed deliveries will appear here') }}</p>
        </div>

        <!-- Completed Orders Table -->
        <div v-else class="bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 rounded-2xl overflow-hidden shadow-sm dark:shadow-xl">
          <div class="overflow-x-auto">
            <table class="w-full">
              <thead class="bg-slate-50 dark:bg-slate-950/80 border-b border-slate-200 dark:border-slate-800">
                <tr>
                  <th class="px-6 py-4 text-left text-xs font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider">{{ languageStore.t('order_id', 'Order ID') }}</th>
                  <th class="px-6 py-4 text-left text-xs font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider">{{ languageStore.t('room', 'Room') }}</th>
                  <th class="px-6 py-4 text-left text-xs font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider">{{ languageStore.t('guest', 'Guest') }}</th>
                  <th class="px-6 py-4 text-left text-xs font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider">{{ languageStore.t('completed', 'Completed') }}</th>
                  <th class="px-6 py-4 text-left text-xs font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider">{{ languageStore.t('delivery_time', 'Delivery Time') }}</th>
                  <th class="px-6 py-4 text-left text-xs font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider">{{ languageStore.t('remarks', 'Remarks') }}</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                <tr v-for="order in paginatedCompleted" :key="order.id" class="hover:bg-slate-50 dark:hover:bg-slate-950/50 transition">
                  <td class="px-6 py-4 text-sm font-bold text-slate-900 dark:text-white">
                    #{{ order.order_number || order.order_id || String(order.id).substring(0,8) }}
                  </td>
                  <td class="px-6 py-4 text-sm">
                    <span class="px-2.5 py-1 bg-indigo-50 dark:bg-indigo-500/10 text-indigo-700 dark:text-indigo-400 border border-indigo-200 dark:border-indigo-500/20 rounded-md text-xs font-bold">
                      {{ order.room_number || 'N/A' }}
                    </span>
                  </td>
                  <td class="px-6 py-4 text-sm font-medium text-slate-700 dark:text-slate-300">
                    {{ order.guest_name || languageStore.t('guest', 'Guest') }}
                  </td>
                  <td class="px-6 py-4 text-sm text-slate-600 dark:text-slate-400">
                    {{ formatDateTime(order.delivered_at || order.created_at) }}
                  </td>
                  <td class="px-6 py-4 text-sm">
                    <span class="px-2.5 py-1 bg-emerald-50 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-500/20 rounded-lg text-xs font-bold inline-flex items-center gap-1">
                      <Timer class="w-3.5 h-3.5" />
                      {{ formatDuration(order.delivery_time_minutes || order.delivery_time) }} {{ languageStore.t('min', 'min') }}
                    </span>
                  </td>
                  <td class="px-6 py-4 text-sm text-slate-600 dark:text-slate-400">
                    {{ order.remarks || '—' }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Pagination -->
          <div class="bg-slate-50 dark:bg-slate-950/60 px-6 py-4 border-t border-slate-200 dark:border-slate-800 flex flex-col sm:flex-row items-center justify-between gap-4">
            <div class="flex items-center gap-3 text-xs text-slate-500 dark:text-slate-400">
              <div class="flex items-center gap-1.5">
                <label class="font-semibold text-slate-600 dark:text-slate-400 whitespace-nowrap">{{ languageStore.t('per_page', 'Per page:') }}</label>
                <select
                  v-model.number="itemsPerPage"
                  class="px-2.5 py-1 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg text-xs font-bold text-slate-700 dark:text-slate-200 focus:ring-2 focus:ring-indigo-500 cursor-pointer shadow-xs outline-none"
                >
                  <option :value="5">5</option>
                  <option :value="10">10</option>
                  <option :value="20">20</option>
                  <option :value="50">50</option>
                </select>
              </div>
              <span>
                {{ languageStore.t('showing', 'Showing') }} {{ completed.length > 0 ? startIndex + 1 : 0 }} {{ languageStore.t('to', 'to') }} {{ Math.min(endIndex, completed.length) }} {{ languageStore.t('of', 'of') }} {{ completed.length }}
              </span>
            </div>
            <div class="flex gap-2 items-center">
              <button
                @click="previousPage"
                :disabled="currentPage === 1"
                class="px-4 py-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-300 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 disabled:opacity-50 disabled:cursor-not-allowed text-xs font-semibold shadow-xs transition cursor-pointer"
              >
                ← {{ languageStore.t('previous', 'Previous') }}
              </button>
              <div class="flex items-center gap-1">
                <template v-for="(page, index) in visiblePages" :key="index">
                  <span v-if="page === '...'" class="px-2 py-1 text-xs text-slate-400 font-semibold">...</span>
                  <button
                    v-else
                    @click="goToPage(Number(page))"
                    :class="page === currentPage ? 'bg-indigo-600 text-white shadow-xs' : 'bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-800'"
                    class="px-3 py-1.5 rounded-lg text-xs font-semibold transition cursor-pointer"
                  >
                    {{ page }}
                  </button>
                </template>
              </div>
              <button
                @click="nextPage"
                :disabled="currentPage === totalPages || totalPages === 0"
                class="px-4 py-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-300 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 disabled:opacity-50 disabled:cursor-not-allowed text-xs font-semibold shadow-xs transition cursor-pointer"
              >
                {{ languageStore.t('next', 'Next') }} →
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>
