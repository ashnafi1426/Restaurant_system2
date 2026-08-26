<template>
  <DashboardLayout>
    <div class="min-h-screen bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-slate-100 p-3 sm:p-6 lg:p-8 transition-colors duration-200">
      <div class="max-w-7xl mx-auto space-y-6">
        <!-- Header -->
        <div class="bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 p-5 sm:p-6 rounded-2xl shadow-sm dark:shadow-2xl">
          <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-center gap-3">
              <div>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-white tracking-tight">Assigned Orders</h1>
                <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1">Manage your assigned orders and delivery progress</p>
              </div>
            </div>
            <div v-if="!loading && assignments.length > 0" class="text-xs font-semibold text-slate-500 dark:text-slate-400 bg-slate-100 dark:bg-slate-800 px-3 py-1.5 rounded-lg border border-slate-200 dark:border-slate-700 self-start sm:self-auto">
              Showing {{ startIndex + 1 }} to {{ Math.min(startIndex + itemsPerPage, totalAssignments) }} of {{ totalAssignments }} orders
            </div>
          </div>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="flex items-center justify-center py-20 bg-white dark:bg-slate-900/40 rounded-2xl border border-slate-200 dark:border-slate-800">
          <div class="text-center">
            <div class="inline-block relative w-12 h-12 mb-3">
              <div class="absolute inset-0 rounded-full border-4 border-indigo-500/20 border-t-indigo-600 dark:border-t-indigo-400 animate-spin"></div>
            </div>
            <p class="text-slate-600 dark:text-slate-400 text-sm font-medium">Loading assigned orders...</p>
          </div>
        </div>

        <!-- Error State -->
        <div v-else-if="error && !loading" class="bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800 rounded-2xl p-6">
          <p class="text-rose-700 dark:text-rose-400 font-semibold text-sm">Error loading orders</p>
          <p class="text-rose-600 dark:text-rose-300 text-xs mt-1">{{ error }}</p>
          <button 
            @click="retryLoad"
            class="mt-4 px-4 py-2 bg-rose-600 text-white rounded-xl text-xs font-semibold hover:bg-rose-700 transition shadow-sm"
          >
            Retry
          </button>
        </div>

        <!-- Empty State -->
        <div v-else-if="assignments.length === 0 && !loading" class="bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 rounded-2xl p-12 text-center">
          <span class="material-symbols-rounded text-5xl block mb-2 text-slate-400 dark:text-slate-600">inbox</span>
          <p class="text-slate-700 dark:text-slate-300 text-lg font-bold">No assigned orders yet</p>
          <p class="text-slate-500 dark:text-slate-400 text-sm mt-1">Check back later for new assignments</p>
        </div>

        <!-- Orders Table Container -->
        <div v-else class="bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 rounded-2xl overflow-hidden shadow-sm dark:shadow-xl">
          <div class="w-full overflow-x-auto">
            <table class="w-full text-left border-collapse">
              <thead class="bg-slate-50 dark:bg-slate-950/80 border-b border-slate-200 dark:border-slate-800 text-[11px] sm:text-xs font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider">
                <tr>
                  <th class="px-3 sm:px-4 py-3.5 whitespace-nowrap">Order #</th>
                  <th class="px-3 sm:px-4 py-3.5 whitespace-nowrap">Room</th>
                  <th class="px-3 sm:px-4 py-3.5 whitespace-nowrap">Guest</th>
                  <th class="px-3 sm:px-4 py-3.5 whitespace-nowrap">Items</th>
                  <th class="px-3 sm:px-4 py-3.5 whitespace-nowrap">Status</th>
                  <th class="px-3 sm:px-4 py-3.5 whitespace-nowrap">Assigned Time</th>
                  <th class="px-3 sm:px-4 py-3.5 text-right whitespace-nowrap">Actions</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-200 dark:divide-slate-800 text-xs sm:text-sm">
                <tr v-for="order in paginatedAssignments" :key="order.id" class="hover:bg-slate-50/80 dark:hover:bg-slate-950/50 transition duration-150">
                  <td class="px-3 sm:px-4 py-3 font-bold text-slate-900 dark:text-white max-w-[130px] sm:max-w-[160px]">
                    <div class="truncate" :title="order.order_number || order.order_id">
                      #{{ order.order_number || order.order_id || order.id.substring(0,8) }}
                    </div>
                  </td>
                  <td class="px-3 sm:px-4 py-3 whitespace-nowrap">
                    <span class="px-2 py-0.5 bg-indigo-50 dark:bg-indigo-500/10 text-indigo-700 dark:text-indigo-400 border border-indigo-200 dark:border-indigo-500/20 rounded text-xs font-bold">
                      {{ order.room_number || 'N/A' }}
                    </span>
                  </td>
                  <td class="px-3 sm:px-4 py-3 font-medium text-slate-700 dark:text-slate-300 max-w-[120px] sm:max-w-[150px] truncate" :title="order.guest_name">
                    {{ order.guest_name || 'Guest' }}
                  </td>
                  <td class="px-3 sm:px-4 py-3 whitespace-nowrap">
                    <span class="px-2 py-0.5 bg-blue-50 dark:bg-blue-500/10 text-blue-700 dark:text-blue-400 border border-blue-200 dark:border-blue-500/20 rounded text-xs font-bold">
                      {{ typeof order.items === 'number' ? order.items : (Array.isArray(order.items) ? order.items.length : 1) }} items
                    </span>
                  </td>
                  <td class="px-3 sm:px-4 py-3 whitespace-nowrap">
                    <span :class="[
                      'px-2.5 py-0.5 rounded-full text-[10px] sm:text-xs font-bold uppercase tracking-wider border',
                      order.order_status === 'assigned' ? 'bg-amber-50 dark:bg-amber-500/10 text-amber-700 dark:text-amber-400 border-amber-200 dark:border-amber-500/20' : 
                      order.order_status === 'accepted' ? 'bg-blue-50 dark:bg-blue-500/10 text-blue-700 dark:text-blue-400 border-blue-200 dark:border-blue-500/20' :
                      order.order_status === 'picked_up' ? 'bg-purple-50 dark:bg-purple-500/10 text-purple-700 dark:text-purple-400 border-purple-200 dark:border-purple-500/20' :
                      order.order_status === 'on_delivery' ? 'bg-emerald-50 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border-emerald-200 dark:border-emerald-500/20' :
                      'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border-slate-200 dark:border-slate-700'
                    ]">
                      {{ order.order_status || 'assigned' }}
                    </span>
                  </td>
                  <td class="px-3 sm:px-4 py-3 text-slate-600 dark:text-slate-400 whitespace-nowrap text-xs">
                    {{ formatDateTime(order.assigned_at || order.created_at) }}
                  </td>
                  <td class="px-3 sm:px-4 py-3 text-right whitespace-nowrap">
                    <div class="relative inline-block text-left">
                      <button
                        @click.stop="toggleMenu(order.id)"
                        class="w-8 h-8 rounded-lg bg-slate-100 dark:bg-slate-800 hover:bg-indigo-50 dark:hover:bg-indigo-500/20 text-slate-700 dark:text-slate-300 hover:text-indigo-600 dark:hover:text-indigo-400 flex items-center justify-center transition shadow-xs border border-slate-200 dark:border-slate-700"
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
                          @click="selectedOrder = order; showDetailModal = true; activeMenuId = null"
                          class="w-full px-3.5 py-2 text-xs font-semibold text-slate-700 dark:text-slate-200 hover:bg-indigo-50 dark:hover:bg-indigo-500/10 hover:text-indigo-600 dark:hover:text-indigo-400 flex items-center gap-2 transition"
                        >
                          <span class="material-symbols-rounded text-sm">visibility</span>
                          View Details
                        </button>

                        <button
                          v-if="order.order_status === 'assigned'"
                          @click="acceptOrder(order.id); activeMenuId = null"
                          class="w-full px-3.5 py-2 text-xs font-semibold text-slate-700 dark:text-slate-200 hover:bg-blue-50 dark:hover:bg-blue-500/10 hover:text-blue-600 dark:hover:text-blue-400 flex items-center gap-2 transition"
                        >
                          <span class="material-symbols-rounded text-sm">check_circle</span>
                          Accept Order
                        </button>

                        <button
                          v-if="order.order_status === 'accepted'"
                          @click="pickupOrder(order.id); activeMenuId = null"
                          class="w-full px-3.5 py-2 text-xs font-semibold text-slate-700 dark:text-slate-200 hover:bg-amber-50 dark:hover:bg-amber-500/10 hover:text-amber-600 dark:hover:text-amber-400 flex items-center gap-2 transition"
                        >
                          <span class="material-symbols-rounded text-sm">shopping_bag</span>
                          Pickup Order
                        </button>

                        <button
                          v-if="order.order_status === 'picked_up'"
                          @click="startDelivery(order.id); activeMenuId = null"
                          class="w-full px-3.5 py-2 text-xs font-semibold text-slate-700 dark:text-slate-200 hover:bg-emerald-50 dark:hover:bg-emerald-500/10 hover:text-emerald-600 dark:hover:text-emerald-400 flex items-center gap-2 transition"
                        >
                          <span class="material-symbols-rounded text-sm">local_shipping</span>
                          Start Delivery
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
                Showing {{ startIndex + 1 }} to {{ Math.min(startIndex + itemsPerPage, totalAssignments) }} of {{ totalAssignments }} entries
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
                  v-for="pageNum in visiblePages"
                  :key="pageNum"
                  @click="currentPage = pageNum"
                  :class="pageNum === currentPage ? 'bg-indigo-600 text-white' : 'bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-800'"
                  class="px-2.5 py-1 rounded-lg text-xs font-semibold transition"
                >
                  {{ pageNum }}
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

    <!-- Order Detail Modal -->
    <div v-if="showDetailModal" class="fixed inset-0 bg-slate-950/60 backdrop-blur-xs flex items-center justify-center z-50 p-4">
      <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-800 max-w-2xl w-full max-h-[90vh] overflow-y-auto">
        <div class="sticky top-0 bg-indigo-600 text-white p-5 flex justify-between items-center rounded-t-2xl">
          <div>
            <h2 class="text-xl font-bold">Order Details</h2>
            <p class="text-indigo-100 text-xs mt-1">#{{ selectedOrder?.order_number || selectedOrder?.id }}</p>
          </div>
          <button
            @click="showDetailModal = false"
            class="text-white hover:bg-white/20 p-2 rounded-lg transition"
          >
            ✕
          </button>
        </div>

        <div class="p-6 space-y-6">
          <div class="bg-slate-50 dark:bg-slate-950 rounded-xl p-4 border border-slate-200 dark:border-slate-800">
            <h3 class="font-bold text-slate-900 dark:text-white mb-3 text-sm">Order Summary</h3>
            <div class="grid grid-cols-2 gap-4 text-xs">
              <div>
                <p class="text-slate-500 uppercase font-semibold">Guest</p>
                <p class="font-bold text-slate-900 dark:text-white mt-1">{{ selectedOrder?.guest_name || 'Guest' }}</p>
              </div>
              <div>
                <p class="text-slate-500 uppercase font-semibold">Room</p>
                <p class="font-bold text-slate-900 dark:text-white mt-1">{{ selectedOrder?.room_number || 'N/A' }}</p>
              </div>
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
import { 
  Beef, Coffee, Cake, Fish, Pizza, Soup, 
  Sandwich, Apple, Wine, IceCream, Cookie, 
  Egg, Salad, UtensilsCrossed, ChefHat 
} from 'lucide-vue-next'

const loading = ref(true)
const error = ref<string | null>(null)
const assignments = ref<any[]>([])
const currentPage = ref(1)
const itemsPerPage = ref(10)
const selectedOrder = ref<any>(null)
const showDetailModal = ref(false)
const loadingOrderId = ref<string | null>(null)
const activeMenuId = ref<string | null>(null)

// Map food items to appropriate Lucide icons
const getFoodIcon = (itemName: string, category?: string): Component => {
  const name = itemName?.toLowerCase() || ''
  const cat = category?.toLowerCase() || ''

  // Breakfast items
  if (name.includes('egg') || name.includes('omelet')) return Egg
  if (name.includes('bacon') || name.includes('pancake') || name.includes('waffle')) return ChefHat
  
  // Main dishes
  if (name.includes('burger') || name.includes('sandwich')) return Sandwich
  if (name.includes('pizza')) return Pizza
  if (name.includes('steak') || name.includes('beef') || name.includes('mignon')) return Beef
  if (name.includes('chicken') || name.includes('poultry')) return ChefHat
  if (name.includes('fish') || name.includes('salmon') || name.includes('tuna')) return Fish
  
  // Soups & bowls
  if (name.includes('soup') || name.includes('ramen') || name.includes('noodle') || name.includes('bowl')) return Soup
  
  // Salads
  if (cat.includes('salad') || name.includes('salad')) return Salad
  
  // Desserts
  if (cat.includes('dessert') || name.includes('cake') || name.includes('lava')) return Cake
  if (name.includes('ice cream') || name.includes('gelato')) return IceCream
  if (name.includes('cookie') || name.includes('chocolate')) return Cookie
  
  // Drinks
  if (cat.includes('drink') || cat.includes('beverage')) return Coffee
  if (name.includes('coffee') || name.includes('espresso') || name.includes('tea')) return Coffee
  if (name.includes('wine') || name.includes('beer') || name.includes('cocktail')) return Wine
  
  // Fruits
  if (name.includes('fruit') || name.includes('apple') || name.includes('banana') || name.includes('orange')) return Apple
  
  // Default
  return UtensilsCrossed
}

const totalAssignments = computed(() => assignments.value.length)
const totalPages = computed(() => Math.ceil(totalAssignments.value / itemsPerPage.value))
const startIndex = computed(() => (currentPage.value - 1) * itemsPerPage.value)
const paginatedAssignments = computed(() => assignments.value.slice(startIndex.value, startIndex.value + itemsPerPage.value))

const visiblePages = computed(() => {
  const pages = []
  const maxVisible = 5
  let start = Math.max(1, currentPage.value - 2)
  let end = Math.min(totalPages.value, start + maxVisible - 1)
  if (end - start < maxVisible - 1) {
    start = Math.max(1, end - maxVisible + 1)
  }
  for (let i = start; i <= end; i++) {
    pages.push(i)
  }
  return pages
})

const toggleMenu = (id: string) => {
  activeMenuId.value = activeMenuId.value === id ? null : id
}

const handleOutsideClick = () => {
  activeMenuId.value = null
}

const loadAssignments = async () => {
  try {
    loading.value = true
    error.value = null
    const data = await waiterService.getRecentAssignments(100)
    assignments.value = data || []
    currentPage.value = 1
  } catch (err: any) {
    console.error('[AssignedOrders] Error:', err)
    error.value = err.message || 'Failed to load orders'
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  loadAssignments()
  window.addEventListener('click', handleOutsideClick)
})

onUnmounted(() => {
  window.removeEventListener('click', handleOutsideClick)
})

const formatDateTime = (dateTime: string) => {
  if (!dateTime) return 'N/A'
  try {
    const date = new Date(dateTime)
    return date.toLocaleString('en-US', {
      month: 'short',
      day: 'numeric',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
      hour12: true,
    })
  } catch (e) {
    return dateTime
  }
}

const retryLoad = () => loadAssignments()
const nextPage = () => { if (currentPage.value < totalPages.value) currentPage.value++ }
const previousPage = () => { if (currentPage.value > 1) currentPage.value-- }

const acceptOrder = async (orderId: string) => {
  try {
    loadingOrderId.value = orderId
    await waiterService.acceptAssignment(orderId)
    await loadAssignments()
  } catch (err: any) {
    error.value = err.response?.data?.message || err.message
  } finally {
    loadingOrderId.value = null
  }
}

const pickupOrder = async (orderId: string) => {
  try {
    loadingOrderId.value = orderId
    await waiterService.pickupOrder(orderId)
    await loadAssignments()
  } catch (err: any) {
    error.value = err.response?.data?.message || err.message
  } finally {
    loadingOrderId.value = null
  }
}

const startDelivery = async (orderId: string) => {
  try {
    loadingOrderId.value = orderId
    await waiterService.startDelivery(orderId)
    await loadAssignments()
  } catch (err: any) {
    error.value = err.response?.data?.message || err.message
  } finally {
    loadingOrderId.value = null
  }
}
</script>

<style scoped>
</style>
