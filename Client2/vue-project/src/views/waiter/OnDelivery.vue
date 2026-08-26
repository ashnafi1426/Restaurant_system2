<template>
  <DashboardLayout>
    <div class="min-h-screen bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-slate-100 p-3 sm:p-6 lg:p-8 transition-colors duration-200">
      <div class="max-w-7xl mx-auto space-y-6">
        <!-- Header -->
        <div class="bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 p-5 sm:p-6 rounded-2xl shadow-sm dark:shadow-2xl">
          <div class="flex items-center gap-3">
            <div>
              <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-white tracking-tight">On Delivery</h1>
              <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1">Track your active room deliveries and mark completed</p>
            </div>
          </div>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="flex items-center justify-center py-20 bg-white dark:bg-slate-900/40 rounded-2xl border border-slate-200 dark:border-slate-800">
          <div class="text-center">
            <div class="inline-block relative w-12 h-12 mb-3">
              <div class="absolute inset-0 rounded-full border-4 border-emerald-500/20 border-t-emerald-600 dark:border-t-emerald-400 animate-spin"></div>
            </div>
            <p class="text-slate-600 dark:text-slate-400 text-sm font-medium">Loading active deliveries...</p>
          </div>
        </div>

        <!-- Empty State -->
        <div v-else-if="paginatedDeliveries.length === 0" class="bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 rounded-2xl p-12 text-center">
          <span class="material-symbols-rounded text-5xl block mb-2 text-slate-400 dark:text-slate-600">done_all</span>
          <p class="text-slate-700 dark:text-slate-300 text-lg font-bold">No active deliveries right now</p>
          <p class="text-slate-500 dark:text-slate-400 text-sm mt-1">You're all caught up!</p>
        </div>

        <!-- Deliveries Table -->
        <div v-else class="bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 rounded-2xl overflow-hidden shadow-sm dark:shadow-xl">
          <div class="w-full overflow-x-auto">
            <table class="w-full text-left border-collapse">
              <thead class="bg-slate-50 dark:bg-slate-950/80 border-b border-slate-200 dark:border-slate-800 text-[11px] sm:text-xs font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider">
                <tr>
                  <th class="px-3 sm:px-4 py-3.5 whitespace-nowrap">Order ID</th>
                  <th class="px-3 sm:px-4 py-3.5 whitespace-nowrap">Room</th>
                  <th class="px-3 sm:px-4 py-3.5 whitespace-nowrap">Guest Name</th>
                  <th class="px-3 sm:px-4 py-3.5 whitespace-nowrap">Started At</th>
                  <th class="px-3 sm:px-4 py-3.5 whitespace-nowrap">Priority</th>
                  <th class="px-3 sm:px-4 py-3.5 text-right whitespace-nowrap">Action</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-200 dark:divide-slate-800 text-xs sm:text-sm">
                <tr v-for="delivery in paginatedDeliveries" :key="delivery.id" class="hover:bg-slate-50/80 dark:hover:bg-slate-950/50 transition">
                  <td class="px-3 sm:px-4 py-3 font-bold text-slate-900 dark:text-white max-w-[130px] sm:max-w-[160px]">
                    <div class="truncate" :title="delivery.order_number || delivery.order_id">
                      #{{ delivery.order_number || delivery.order_id || delivery.id.substring(0,8) }}
                    </div>
                  </td>
                  <td class="px-3 sm:px-4 py-3 whitespace-nowrap">
                    <span class="px-2.5 py-0.5 bg-indigo-50 dark:bg-indigo-500/10 text-indigo-700 dark:text-indigo-400 border border-indigo-200 dark:border-indigo-500/20 rounded text-xs font-bold">
                      {{ delivery.room_number || 'N/A' }}
                    </span>
                  </td>
                  <td class="px-3 sm:px-4 py-3 font-medium text-slate-700 dark:text-slate-300 max-w-[120px] sm:max-w-[150px] truncate" :title="delivery.guest_name">
                    {{ delivery.guest_name || 'Guest' }}
                  </td>
                  <td class="px-3 sm:px-4 py-3 text-slate-600 dark:text-slate-400 whitespace-nowrap text-xs">
                    {{ formatDateTime(delivery.on_delivery_at || delivery.picked_up_at || delivery.created_at) }}
                  </td>
                  <td class="px-3 sm:px-4 py-3 whitespace-nowrap">
                    <span :class="[
                      'px-2.5 py-0.5 rounded-full text-[10px] sm:text-xs font-bold uppercase tracking-wider border',
                      delivery.priority?.toLowerCase() === 'high' ? 'bg-rose-50 dark:bg-rose-500/10 text-rose-700 dark:text-rose-400 border-rose-200 dark:border-rose-500/20' : 'bg-blue-50 dark:bg-blue-500/10 text-blue-700 dark:text-blue-400 border-blue-200 dark:border-blue-500/20'
                    ]">
                      {{ delivery.priority?.toUpperCase() || 'NORMAL' }}
                    </span>
                  </td>
                  <td class="px-3 sm:px-4 py-3 text-right whitespace-nowrap">
                    <div class="relative inline-block text-left">
                      <button
                        @click.stop="toggleMenu(delivery.id)"
                        class="w-8 h-8 rounded-lg bg-slate-100 dark:bg-slate-800 hover:bg-emerald-50 dark:hover:bg-emerald-500/20 text-slate-700 dark:text-slate-300 hover:text-emerald-600 dark:hover:text-emerald-400 flex items-center justify-center transition shadow-xs border border-slate-200 dark:border-slate-700"
                        title="Actions"
                      >
                        <span class="material-symbols-rounded text-lg">more_vert</span>
                      </button>

                      <div
                        v-if="activeMenuId === delivery.id"
                        @click.stop
                        class="absolute right-0 mt-1 w-44 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl shadow-xl z-50 py-1 text-left overflow-hidden transition-all duration-150"
                      >
                        <button
                          @click="completeDelivery(delivery.id); activeMenuId = null"
                          :disabled="completingId === delivery.id"
                          class="w-full px-3.5 py-2 text-xs font-semibold text-slate-700 dark:text-slate-200 hover:bg-emerald-50 dark:hover:bg-emerald-500/10 hover:text-emerald-600 dark:hover:text-emerald-400 flex items-center gap-2 transition"
                        >
                          <span class="material-symbols-rounded text-sm">check_circle</span>
                          {{ completingId === delivery.id ? 'Completing...' : 'Complete Delivery' }}
                        </button>
                      </div>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Pagination Bar with Per-Page Dropdown Selector -->
          <div class="bg-slate-50 dark:bg-slate-950/60 px-4 py-3 border-t border-slate-200 dark:border-slate-800 flex flex-col sm:flex-row items-center justify-between gap-3">
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
                  <option :value="100">100</option>
                </select>
              </div>
              <span>
                Showing {{ startIndex + 1 }} to {{ Math.min(endIndex, deliveries.length) }} of {{ deliveries.length }} entries
              </span>
            </div>

            <div class="flex gap-1.5">
              <button
                @click="previousPage"
                :disabled="currentPage === 1"
                class="px-3 py-1.5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-300 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 disabled:opacity-50 disabled:cursor-not-allowed text-xs font-semibold shadow-xs"
              >
                ← Prev
              </button>
              <div class="flex items-center gap-1">
                <button
                  v-for="page in totalPages"
                  :key="page"
                  @click="goToPage(page)"
                  :class="page === currentPage ? 'bg-indigo-600 text-white' : 'bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-800'"
                  class="px-2.5 py-1 rounded-lg text-xs font-semibold transition"
                >
                  {{ page }}
                </button>
              </div>
              <button
                @click="nextPage"
                :disabled="currentPage === totalPages || totalPages === 0"
                class="px-3 py-1.5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-300 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 disabled:opacity-50 disabled:cursor-not-allowed text-xs font-semibold shadow-xs"
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
import { ref, onMounted, onUnmounted, computed } from 'vue'
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import waiterService from '@/services/waiterService'

const loading = ref(true)
const deliveries = ref<any[]>([])
const currentPage = ref(1)
const itemsPerPage = ref(10)
const completingId = ref<string | null>(null)
const activeMenuId = ref<string | null>(null)

const totalPages = computed(() => Math.ceil(deliveries.value.length / itemsPerPage.value))
const startIndex = computed(() => (currentPage.value - 1) * itemsPerPage.value)
const endIndex = computed(() => startIndex.value + itemsPerPage.value)
const paginatedDeliveries = computed(() => deliveries.value.slice(startIndex.value, endIndex.value))

const toggleMenu = (id: string) => {
  activeMenuId.value = activeMenuId.value === id ? null : id
}

const handleOutsideClick = () => {
  activeMenuId.value = null
}

const formatDateTime = (date: string) => {
  if (!date) return 'N/A'
  try {
    const d = new Date(date)
    return d.toLocaleString('en-US', {
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

const previousPage = () => { if (currentPage.value > 1) currentPage.value-- }
const nextPage = () => { if (currentPage.value < totalPages.value) currentPage.value++ }
const goToPage = (page: number) => { currentPage.value = page }

const loadDeliveries = async () => {
  try {
    loading.value = true
    const data = await waiterService.getOnDelivery()
    deliveries.value = data || []
    currentPage.value = 1
  } catch (err) {
    console.error('[OnDelivery] Load error:', err)
    deliveries.value = []
  } finally {
    loading.value = false
  }
}

const completeDelivery = async (id: string) => {
  try {
    completingId.value = id
    await waiterService.deliverOrder(id)
    await loadDeliveries()
  } catch (err) {
    console.error('[OnDelivery] Complete error:', err)
  } finally {
    completingId.value = null
  }
}

onMounted(() => {
  loadDeliveries()
  window.addEventListener('click', handleOutsideClick)
})

onUnmounted(() => {
  window.removeEventListener('click', handleOutsideClick)
})
</script>

<style scoped>
</style>
