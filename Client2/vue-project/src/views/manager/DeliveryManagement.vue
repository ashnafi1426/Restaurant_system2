<script setup lang="ts">
import { onMounted, ref, computed } from 'vue'
import DashboardLayout from '../../Layouts/DashboardLayout.vue'
import { Truck, Download } from 'lucide-vue-next'
import { useDeliveryManagementStore } from '@/stores/manager/deliveryManagementStore'

const store = useDeliveryManagementStore()
const isLoading = ref(false)
const jumpToPage = ref<number | null>(null)

// Computed properties for summary stats
const deliveryData = computed(() => {
  console.log('Computed deliveryData - store.todaySummary:', store.todaySummary)
  
  if (!store.todaySummary) {
    return {
      total_deliveries: 0,
      completed: 0,
      in_progress: 0,
      failed: 0
    }
  }

  return {
    total_deliveries: store.todaySummary.total_deliveries || 0,
    completed: store.todaySummary.completed || 0,
    in_progress: store.todaySummary.in_progress || 0,
    failed: store.todaySummary.failed || 0
  }
})

// Calculate total pages
const totalPages = computed(() => Math.ceil(store.totalDeliveries / store.perPage))

onMounted(async () => {
  console.log('DeliveryManagement component mounted')
  isLoading.value = true
  try {
    console.log('Fetching delivery data...')
    await Promise.all([
      store.fetchTodaySummary(),
      store.fetchDeliveries()
    ])
    console.log('Data fetched. Store state:', {
      todaySummary: store.todaySummary,
      deliveries: store.deliveries,
      totalDeliveries: store.totalDeliveries
    })
  } catch (error) {
    console.error('Failed to load delivery data:', error)
  } finally {
    isLoading.value = false
  }
})

const generateReport = async () => {
  isLoading.value = true
  try {
    await store.fetchDeliveryReport()
    alert('Report generated successfully!')
  } catch (error) {
    console.error('Failed to generate report:', error)
    alert('Failed to generate report')
  } finally {
    isLoading.value = false
  }
}

// Calculate page numbers to display (show up to 5 pages)
const getPageNumbers = () => {
  const totalPages = Math.ceil(store.totalDeliveries / store.perPage)
  const currentPage = store.currentPage
  const pages: number[] = []
  
  let startPage = Math.max(1, currentPage - 2)
  let endPage = Math.min(totalPages, currentPage + 2)
  
  // Adjust if we're near the beginning or end
  if (currentPage <= 3) {
    endPage = Math.min(totalPages, 5)
  } else if (currentPage >= totalPages - 2) {
    startPage = Math.max(1, totalPages - 4)
  }
  
  for (let i = startPage; i <= endPage; i++) {
    pages.push(i)
  }
  
  return pages
}

// Handle items per page change
const handlePerPageChange = async () => {
  // Reset to page 1 when changing items per page
  await store.fetchDeliveries(1)
  jumpToPage.value = null
}

// Handle jump to page
const handleJumpToPage = async () => {
  if (jumpToPage.value && jumpToPage.value >= 1 && jumpToPage.value <= totalPages.value) {
    await store.fetchDeliveries(jumpToPage.value)
    jumpToPage.value = null
  }
}

// Change items per page
const changePageSize = async (size: number) => {
  console.log('changePageSize called with size:', size)
  console.log('Current store.perPage before update:', store.perPage)
  store.perPage = size
  console.log('Updated store.perPage:', store.perPage)
  try {
    console.log('Fetching deliveries with page 1 and perPage:', store.perPage)
    await store.fetchDeliveries(1)
    console.log('Fetched successfully. Total deliveries:', store.totalDeliveries, 'Current perPage:', store.perPage)
    jumpToPage.value = null
  } catch (error) {
    console.error('Error fetching deliveries after page size change:', error)
  }
}

// Navigate to previous page
const goToPreviousPage = async () => {
  if (store.currentPage > 1) {
    await store.fetchDeliveries(store.currentPage - 1)
  }
}

// Navigate to specific page
const goToPage = async (page: number) => {
  await store.fetchDeliveries(page)
}

// Navigate to next page
const goToNextPage = async () => {
  if (store.currentPage < totalPages.value) {
    await store.fetchDeliveries(store.currentPage + 1)
  }
}
</script>

<template>
  <DashboardLayout>
    <div class="min-h-screen bg-white dark:bg-slate-950 transition-colors duration-300">
      <!-- PAGE HEADER -->
      <div class="mb-8 border-b border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 backdrop-blur-sm rounded-lg p-6 transition-colors duration-300">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-3xl font-bold text-slate-900 dark:text-slate-100 mb-2">Room Service</h1>
            <p class="text-slate-600">Track and manage all room service deliveries</p>
          </div>
          <div class="w-12 h-12 bg-gradient-to-br from-teal-100 to-teal-50 rounded-xl flex items-center justify-center">
            <Truck class="w-6 h-6 text-teal-600" />
          </div>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="isLoading" class="flex justify-center items-center py-32">
        <div class="text-center">
          <div class="relative w-12 h-12 mx-auto mb-4">
            <!-- Static background - BRIGHT CYAN -->
            <svg class="absolute inset-0 w-full h-full" viewBox="0 0 100 100">
              <circle cx="50" cy="50" r="45" fill="none" stroke="#0EA5E9" stroke-width="6" opacity="0.3" />
            </svg>
            
            <!-- Animated spinner - BRIGHT YELLOW -->
            <div class="absolute inset-0 animate-spin" style="animation: spin 1.5s linear infinite;">
              <svg viewBox="0 0 100 100" class="w-full h-full">
                <circle cx="50" cy="50" r="45" fill="none" stroke="#FBBF24" stroke-width="8" stroke-linecap="round" stroke-dasharray="70 280" />
              </svg>
            </div>
          </div>
          <p class="text-slate-700 dark:text-yellow-300 font-semibold text-sm">Loading delivery data...</p>
        </div>
      </div>

      <!-- Error State -->
      <div v-else-if="store.error" class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
        <p class="text-red-700 font-semibold">Error: {{ store.error }}</p>
      </div>

      <!-- Content -->
      <div v-else class="space-y-6">
        <!-- Delivery Summary -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
          <div class="bg-white dark:bg-slate-800 rounded-lg border border-slate-200/60 dark:border-slate-700 shadow-sm p-6 hover:shadow-md transition-shadow dark:transition-all">
            <p class="text-sm text-slate-600 dark:text-slate-400 font-medium">Total Deliveries</p>
            <h3 class="mt-3 text-3xl font-bold text-slate-900 dark:text-white">{{ deliveryData.total_deliveries }}</h3>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-2">Today</p>
          </div>
          <div class="bg-white dark:bg-slate-800 rounded-lg border border-slate-200/60 dark:border-slate-700 shadow-sm p-6 hover:shadow-md transition-shadow dark:transition-all">
            <p class="text-sm text-slate-600 dark:text-slate-400 font-medium">Completed</p>
            <h3 class="mt-3 text-3xl font-bold text-emerald-600 dark:text-emerald-400">{{ deliveryData.completed }}</h3>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-2">Successfully delivered</p>
          </div>
          <div class="bg-white dark:bg-slate-800 rounded-lg border border-slate-200/60 dark:border-slate-700 shadow-sm p-6 hover:shadow-md transition-shadow dark:transition-all">
            <p class="text-sm text-slate-600 dark:text-slate-400 font-medium">In Progress</p>
            <h3 class="mt-3 text-3xl font-bold text-blue-600 dark:text-blue-400">{{ deliveryData.in_progress }}</h3>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-2">Being delivered</p>
          </div>
          <div class="bg-white dark:bg-slate-800 rounded-lg border border-slate-200/60 dark:border-slate-700 shadow-sm p-6 hover:shadow-md transition-shadow dark:transition-all">
            <p class="text-sm text-slate-600 dark:text-slate-400 font-medium">Failed/Cancelled</p>
            <h3 class="mt-3 text-3xl font-bold text-red-600 dark:text-red-400">{{ deliveryData.failed }}</h3>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-2">Issues reported</p>
          </div>
        </div>

        <!-- Action Button -->
        <div class="flex justify-end">
          <button @click="generateReport" :disabled="isLoading" class="flex items-center gap-2 px-6 py-3 bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 disabled:opacity-50 text-slate-700 dark:text-slate-300 font-semibold rounded-lg transition-all duration-300">
            <Download class="w-4 h-4" />
            Generate Report
          </button>
        </div>

        <!-- Deliveries Table -->
        <div class="bg-white dark:bg-slate-800 rounded-lg border border-slate-200/60 dark:border-slate-700 shadow-sm overflow-hidden transition-colors duration-300">
          <div class="p-6 border-b border-slate-200 dark:border-slate-700">
            <h2 class="text-xl font-bold text-slate-900 dark:text-white">All Deliveries</h2>
          </div>
          
          <div v-if="store.deliveries.length === 0" class="text-center py-12">
            <p class="text-slate-500 dark:text-slate-400">No deliveries found</p>
          </div>
          
          <div v-else class="overflow-x-auto">
            <table class="w-full bg-white dark:bg-slate-800">
              <thead class="bg-slate-50 dark:bg-slate-900 border-b border-slate-200 dark:border-slate-700">
                <tr>
                  <th class="px-6 py-3 text-left text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase">Room #</th>
                  <th class="px-6 py-3 text-left text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase">Order ID</th>
                  <th class="px-6 py-3 text-left text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase">Waiter</th>
                  <th class="px-6 py-3 text-left text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase">Floor</th>
                  <th class="px-6 py-3 text-left text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase">Type</th>
                  <th class="px-6 py-3 text-left text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase">Status</th>
                  <th class="px-6 py-3 text-left text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase">Assigned</th>
                  <th class="px-6 py-3 text-left text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase">Actions</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                <tr v-for="delivery in store.deliveries" :key="delivery.id" class="hover:bg-slate-50 dark:hover:bg-slate-700/50 transition">
                  <!-- Room Number -->
                  <td class="px-6 py-4 whitespace-nowrap">
                    <span class="font-semibold text-slate-900 dark:text-white">{{ delivery.room?.room_number || 'N/A' }}</span>
                  </td>
                  
                  <!-- Order ID -->
                  <td class="px-6 py-4 whitespace-nowrap">
                    <span class="text-sm text-slate-600 dark:text-slate-400">{{ delivery.order_id?.substring(0, 8) || 'N/A' }}</span>
                  </td>
                  
                  <!-- Waiter Name -->
                  <td class="px-6 py-4 whitespace-nowrap">
                    <span class="text-sm text-slate-700 dark:text-slate-300">{{ delivery.waiter?.user?.name || 'Unassigned' }}</span>
                  </td>
                  
                  <!-- Floor -->
                  <td class="px-6 py-4 whitespace-nowrap">
                    <span class="text-sm text-slate-600 dark:text-slate-400">{{ delivery.floor?.name || delivery.floor?.floor_number || 'N/A' }}</span>
                  </td>
                  
                  <!-- Assignment Type -->
                  <td class="px-6 py-4 whitespace-nowrap">
                    <span 
                      :class="delivery.assignment_type === 'automatic' ? 'bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300' : 'bg-purple-50 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300'"
                      class="px-3 py-1 rounded-full text-xs font-medium"
                    >
                      {{ delivery.assignment_type || 'N/A' }}
                    </span>
                  </td>
                  
                  <!-- Status Badge -->
                  <td class="px-6 py-4 whitespace-nowrap">
                    <span 
                      :class="{
                        'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-800 dark:text-emerald-300': delivery.status === 'delivered',
                        'bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300': delivery.status === 'on_delivery',
                        'bg-amber-100 dark:bg-amber-900/30 text-amber-800 dark:text-amber-300': delivery.status === 'assigned' || delivery.status === 'accepted',
                        'bg-orange-100 dark:bg-orange-900/30 text-orange-800 dark:text-orange-300': delivery.status === 'picked_up',
                        'bg-gray-100 dark:bg-slate-700 text-gray-800 dark:text-slate-300': delivery.status === 'waiting_assignment',
                        'bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300': delivery.status === 'cancelled'
                      }"
                      class="px-3 py-1 rounded-full text-xs font-semibold"
                    >
                      {{ delivery.status?.replace(/_/g, ' ') || 'N/A' }}
                    </span>
                  </td>
                  
                  <!-- Assigned Time -->
                  <td class="px-6 py-4 whitespace-nowrap">
                    <span class="text-xs text-slate-500 dark:text-slate-400">
                      {{ delivery.assigned_at ? new Date(delivery.assigned_at).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' }) : '-' }}
                    </span>
                  </td>
                  
                  <!-- Actions -->
                  <td class="px-6 py-4 whitespace-nowrap">
                    <button 
                      class="text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300 text-sm font-medium transition"
                      title="View Details"
                    >
                      View
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Pagination -->
          <div v-if="store.totalDeliveries > 0" class="px-6 py-4 border-t border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 transition-colors duration-300">
            <!-- Pagination Controls in One Line -->
            <div class="flex items-center justify-between gap-4">
              <!-- Left: Info -->
              <div class="text-sm text-slate-600 dark:text-slate-400 font-medium whitespace-nowrap">
                Page {{ store.currentPage }} of {{ totalPages }} ({{ store.totalDeliveries }} total)
              </div>

              <!-- Middle: Items Per Page Dropdown -->
              <div class="relative">
                <select
                  :value="store.perPage.toString()"
                  @change="(e) => {
                    const value = Number((e.target as HTMLSelectElement).value)
                    console.log('Select changed to:', value)
                    changePageSize(value)
                  }"
                  class="px-3 py-1.5 border border-slate-300 dark:border-slate-600 rounded-lg text-sm font-medium text-slate-700 dark:text-slate-300 bg-white dark:bg-slate-900 hover:border-slate-400 dark:hover:border-slate-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200 cursor-pointer appearance-none pr-8"
                >
                  <option value="5">5 per page</option>
                  <option value="10">10 per page</option>
                  <option value="20">20 per page</option>
                  <option value="50">50 per page</option>
                </select>
                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-slate-700 dark:text-slate-300">
                  <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                    <path d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z" />
                  </svg>
                </div>
              </div>

              <!-- Right: Navigation Buttons -->
              <div class="flex items-center gap-2">
                <!-- Previous Button -->
                <button 
                  :disabled="store.currentPage === 1"
                  @click="goToPreviousPage"
                  class="px-3 py-1.5 border border-slate-300 dark:border-slate-600 rounded-lg text-slate-600 dark:text-slate-400 text-sm font-medium disabled:opacity-50 disabled:cursor-not-allowed hover:bg-slate-50 dark:hover:bg-slate-700 transition duration-200 whitespace-nowrap"
                >
                  ← Previous
                </button>

                <!-- Page Numbers -->
                <div class="flex items-center gap-1">
                  <button
                    v-for="page in getPageNumbers()"
                    :key="page"
                    @click="goToPage(page)"
                    :class="{
                      'bg-blue-600 text-white border-blue-600 dark:bg-blue-700 dark:border-blue-700': page === store.currentPage,
                      'bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-300 border border-slate-300 dark:border-slate-600 hover:bg-slate-50 dark:hover:bg-slate-700': page !== store.currentPage
                    }"
                    class="w-8 h-8 rounded-lg text-xs font-medium transition duration-200 cursor-pointer border"
                  >
                    {{ page }}
                  </button>
                </div>

                <!-- Next Button -->
                <button 
                  :disabled="store.currentPage >= totalPages"
                  @click="goToNextPage"
                  class="px-3 py-1.5 border border-slate-300 dark:border-slate-600 rounded-lg text-slate-600 dark:text-slate-400 text-sm font-medium disabled:opacity-50 disabled:cursor-not-allowed hover:bg-slate-50 dark:hover:bg-slate-700 transition duration-200 whitespace-nowrap"
                >
                  Next →
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>
