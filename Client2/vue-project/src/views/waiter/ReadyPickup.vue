<template>
  <DashboardLayout>
    <div class="min-h-screen bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-slate-100 p-3 sm:p-6 lg:p-8 transition-colors duration-200">
      <div class="max-w-7xl mx-auto space-y-6">
        <!-- Header -->
        <div class="bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 p-5 sm:p-6 rounded-2xl shadow-sm dark:shadow-2xl">
          <div class="flex items-center gap-3">
            <div>
              <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-white tracking-tight">Ready for Pickup</h1>
              <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1">Orders ready to be picked up from the kitchen</p>
            </div>
          </div>
        </div>

        <!-- Error State -->
        <div v-if="error" class="bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800 rounded-2xl p-6">
          <p class="text-rose-700 dark:text-rose-400 font-semibold text-sm">Error loading orders</p>
          <p class="text-rose-600 dark:text-rose-300 text-xs mt-1">{{ error }}</p>
          <button 
            @click="retryLoad"
            class="mt-4 px-4 py-2 bg-rose-600 text-white rounded-xl text-xs font-semibold hover:bg-rose-700 transition shadow-sm"
          >
            Retry
          </button>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="flex items-center justify-center py-20 bg-white dark:bg-slate-900/40 rounded-2xl border border-slate-200 dark:border-slate-800">
          <div class="text-center">
            <div class="inline-block relative w-12 h-12 mb-3">
              <div class="absolute inset-0 rounded-full border-4 border-amber-500/20 border-t-amber-600 dark:border-t-amber-400 animate-spin"></div>
            </div>
            <p class="text-slate-600 dark:text-slate-400 text-sm font-medium">Loading kitchen ready orders...</p>
          </div>
        </div>

        <!-- Empty State -->
        <div v-else-if="orders.length === 0" class="bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 rounded-2xl p-12 text-center">
          <span class="material-symbols-rounded text-5xl block mb-2 text-slate-400 dark:text-slate-600">outdoor_grill</span>
          <p class="text-slate-700 dark:text-slate-300 text-lg font-bold">No items ready for pickup</p>
          <p class="text-slate-500 dark:text-slate-400 text-sm mt-1">Check back soon for new orders</p>
        </div>

        <!-- Orders Table Container -->
        <div v-else class="bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 rounded-2xl overflow-hidden shadow-sm dark:shadow-xl">
          <div class="w-full overflow-x-auto">
            <table class="w-full text-left border-collapse">
              <thead class="bg-slate-50 dark:bg-slate-950/80 border-b border-slate-200 dark:border-slate-800 text-[11px] sm:text-xs font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider">
                <tr>
                  <th class="px-3 sm:px-4 py-3.5 whitespace-nowrap">Order Number</th>
                  <th class="px-3 sm:px-4 py-3.5 whitespace-nowrap">Guest Name</th>
                  <th class="px-3 sm:px-4 py-3.5 whitespace-nowrap">Room</th>
                  <th class="px-3 sm:px-4 py-3.5 whitespace-nowrap">Items</th>
                  <th class="px-3 sm:px-4 py-3.5 whitespace-nowrap">Special Requests</th>
                  <th class="px-3 sm:px-4 py-3.5 text-right whitespace-nowrap">Action</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-200 dark:divide-slate-800 text-xs sm:text-sm">
                <tr v-for="order in paginatedOrders" :key="order.id" class="hover:bg-slate-50/80 dark:hover:bg-slate-950/50 transition">
                  <td class="px-3 sm:px-4 py-3 font-bold text-slate-900 dark:text-white max-w-[130px] sm:max-w-[160px]">
                    <div class="truncate" :title="order.order_number || order.order_id">
                      #{{ order.order_number || order.order_id || order.id.substring(0,8) }}
                    </div>
                  </td>
                  <td class="px-3 sm:px-4 py-3 font-medium text-slate-700 dark:text-slate-300 max-w-[120px] sm:max-w-[150px] truncate" :title="order.guest_name">
                    {{ order.guest_name || 'Guest' }}
                  </td>
                  <td class="px-3 sm:px-4 py-3 whitespace-nowrap">
                    <span class="px-2.5 py-0.5 bg-indigo-50 dark:bg-indigo-500/10 text-indigo-700 dark:text-indigo-400 border border-indigo-200 dark:border-indigo-500/20 rounded text-xs font-bold">
                      {{ order.room_number || 'N/A' }}
                    </span>
                  </td>
                  <td class="px-3 sm:px-4 py-3 whitespace-nowrap">
                    <span class="px-2 py-0.5 bg-blue-50 dark:bg-blue-500/10 text-blue-700 dark:text-blue-400 border border-blue-200 dark:border-blue-500/20 rounded text-xs font-bold">
                      {{ typeof order.items === 'number' ? order.items : (Array.isArray(order.items) ? order.items.length : 1) }} items
                    </span>
                  </td>
                  <td class="px-3 sm:px-4 py-3 text-slate-600 dark:text-slate-400 max-w-[140px] truncate" :title="order.special_requests">
                    {{ order.special_requests || 'None' }}
                  </td>
                  <td class="px-3 sm:px-4 py-3 text-right whitespace-nowrap">
                    <div class="relative inline-block text-left">
                      <button
                        @click.stop="toggleMenu(order.id)"
                        class="w-8 h-8 rounded-lg bg-slate-100 dark:bg-slate-800 hover:bg-emerald-50 dark:hover:bg-emerald-500/20 text-slate-700 dark:text-slate-300 hover:text-emerald-600 dark:hover:text-emerald-400 flex items-center justify-center transition shadow-xs border border-slate-200 dark:border-slate-700"
                        title="Actions"
                      >
                        <span class="material-symbols-rounded text-lg">more_vert</span>
                      </button>

                      <div
                        v-if="activeMenuId === order.id"
                        @click.stop
                        class="absolute right-0 mt-1 w-44 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl shadow-xl z-50 py-1 text-left overflow-hidden transition-all duration-150"
                      >
                        <button
                          @click="pickupOrder(order.id); activeMenuId = null"
                          :disabled="loadingOrderId === order.id"
                          class="w-full px-3.5 py-2 text-xs font-semibold text-slate-700 dark:text-slate-200 hover:bg-emerald-50 dark:hover:bg-emerald-500/10 hover:text-emerald-600 dark:hover:text-emerald-400 flex items-center gap-2 transition"
                        >
                          <span class="material-symbols-rounded text-sm">shopping_bag</span>
                          {{ loadingOrderId === order.id ? 'Picking up...' : 'Pickup Order' }}
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
                Showing {{ startIndex + 1 }} to {{ Math.min(startIndex + itemsPerPage, orders.length) }} of {{ orders.length }} entries
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
const error = ref<string | null>(null)
const orders = ref<any[]>([])
const currentPage = ref(1)
const itemsPerPage = ref(10)
const loadingOrderId = ref<string | null>(null)
const activeMenuId = ref<string | null>(null)

const totalPages = computed(() => Math.ceil(orders.value.length / itemsPerPage.value))
const startIndex = computed(() => (currentPage.value - 1) * itemsPerPage.value)
const endIndex = computed(() => startIndex.value + itemsPerPage.value)
const paginatedOrders = computed(() => orders.value.slice(startIndex.value, endIndex.value))

const toggleMenu = (id: string) => {
  activeMenuId.value = activeMenuId.value === id ? null : id
}

const handleOutsideClick = () => {
  activeMenuId.value = null
}

const previousPage = () => { if (currentPage.value > 1) currentPage.value-- }
const nextPage = () => { if (currentPage.value < totalPages.value) currentPage.value++ }
const goToPage = (page: number) => { currentPage.value = page }

const loadOrders = async () => {
  try {
    loading.value = true
    error.value = null
    const data = await waiterService.getReadyForPickup()
    orders.value = data || []
    currentPage.value = 1
  } catch (err: any) {
    console.error('[ReadyPickup] Error:', err)
    error.value = err.message || 'Failed to load ready orders'
    orders.value = []
  } finally {
    loading.value = false
  }
}

const retryLoad = () => loadOrders()

const pickupOrder = async (orderId: string) => {
  try {
    loadingOrderId.value = orderId
    await waiterService.pickupOrder(orderId)
    await loadOrders()
  } catch (err: any) {
    error.value = err.response?.data?.message || err.message
  } finally {
    loadingOrderId.value = null
  }
}

onMounted(() => {
  loadOrders()
  window.addEventListener('click', handleOutsideClick)
})

onUnmounted(() => {
  window.removeEventListener('click', handleOutsideClick)
})
</script>

<style scoped>
</style>
