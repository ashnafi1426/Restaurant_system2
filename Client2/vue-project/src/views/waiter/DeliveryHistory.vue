<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import waiterService from '@/services/waiterService'
import {
  Search,
  Filter,
  X,
  RefreshCw,
  Maximize2,
  Minimize2,
  RotateCcw,
  FileText,
  CheckCircle2,
  ChevronLeft,
  ChevronRight,
  Loader2,
  BedDouble,
  ShoppingBag,
  Timer,
} from 'lucide-vue-next'

const loading = ref(true)
const error = ref<string | null>(null)
const history = ref<any[]>([])
const currentPage = ref(1)
const itemsPerPage = ref(10)

const isFilterOpen = ref(false)
const isFullscreen = ref(false)
const searchQuery = ref('')
const filters = ref({
  start_date: '',
  end_date: '',
  type: 'all',
})

const filteredHistory = computed(() => {
  let list = history.value || []

  if (filters.value.type !== 'all') {
    if (filters.value.type === 'room') {
      list = list.filter((item) => Boolean(item.room_number || item.room?.room_number))
    } else if (filters.value.type === 'walk_in') {
      list = list.filter((item) => !item.room_number && !item.room?.room_number)
    }
  }

  if (searchQuery.value.trim()) {
    const q = searchQuery.value.toLowerCase().trim()
    list = list.filter((item) => {
      const ordNum = String(item.order_number || item.order_id || item.id || '').toLowerCase()
      const roomNum = String(item.room_number || item.room?.room_number || '').toLowerCase()
      return ordNum.includes(q) || roomNum.includes(q)
    })
  }

  return list
})

const total = computed(() => filteredHistory.value.length)
const totalPages = computed(() => Math.ceil(total.value / itemsPerPage.value) || 1)

const paginatedHistory = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage.value
  const end = start + itemsPerPage.value
  return filteredHistory.value.slice(start, end)
})

const showingFrom = computed(() => {
  if (total.value === 0) return 0
  return (currentPage.value - 1) * itemsPerPage.value + 1
})

const showingTo = computed(() => {
  return Math.min(currentPage.value * itemsPerPage.value, total.value)
})

const paginationPages = computed(() => {
  const pages: number[] = []
  const max = totalPages.value
  const cur = currentPage.value

  for (let i = Math.max(1, cur - 2); i <= Math.min(max, cur + 2); i++) {
    pages.push(i)
  }
  return pages
})

const changeItemsPerPage = (event: Event) => {
  const target = event.target as HTMLSelectElement
  itemsPerPage.value = Number(target.value)
  currentPage.value = 1
}

const goToPage = (p: number) => {
  if (p >= 1 && p <= totalPages.value) {
    currentPage.value = p
  }
}

const prevPage = () => {
  if (currentPage.value > 1) {
    currentPage.value--
  }
}

const nextPage = () => {
  if (currentPage.value < totalPages.value) {
    currentPage.value++
  }
}

const resetFilters = () => {
  searchQuery.value = ''
  filters.value.start_date = ''
  filters.value.end_date = ''
  filters.value.type = 'all'
  currentPage.value = 1
  fetchHistory()
}

const toggleFilter = () => {
  isFilterOpen.value = !isFilterOpen.value
}

const toggleFullscreen = () => {
  isFullscreen.value = !isFullscreen.value
}

const fetchHistory = async () => {
  try {
    loading.value = true
    error.value = null
    const data = await waiterService.getDeliveryHistory(filters.value)
    if (Array.isArray(data)) {
      history.value = data
    } else if (data && Array.isArray(data.data)) {
      history.value = data.data
    } else {
      history.value = []
    }
    currentPage.value = 1
  } catch (err: any) {
    console.error('[DeliveryHistory] Error:', err)
    error.value = err.message || 'Failed to load delivery history'
  } finally {
    loading.value = false
  }
}

const formatDateTime = (dateStr: string) => {
  if (!dateStr) return '-'
  try {
    const d = new Date(dateStr)
    return d.toLocaleString('en-US', {
      month: 'short',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
    })
  } catch {
    return dateStr
  }
}

const formatDuration = (val: any) => {
  if (val === null || val === undefined) return 0
  const num = Number(val)
  return isNaN(num) ? 0 : Math.round(num)
}

onMounted(() => {
  fetchHistory()
})
</script>

<template>
  <DashboardLayout>
    <div
      class="space-y-6 bg-slate-50 dark:bg-slate-950 min-h-screen p-4 sm:p-6 max-w-full overflow-hidden font-sans"
      :class="{ 'fixed inset-0 z-50 p-6 overflow-y-auto bg-white dark:bg-slate-950': isFullscreen }"
    >
      <!-- Header -->
      <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-5 sm:p-6 rounded-2xl shadow-xs flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-600 to-indigo-700 flex items-center justify-center shadow-md flex-shrink-0 text-white">
            <FileText class="w-5 h-5 stroke-[2.2]" />
          </div>
          <div>
            <h1 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white">Delivery History</h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">View your past completed deliveries, durations, and timestamps.</p>
          </div>
        </div>

        <div class="flex items-center gap-2">
          <div class="px-4 py-2 rounded-xl bg-indigo-500/10 border border-indigo-500/20 text-indigo-600 dark:text-indigo-400 font-extrabold text-xs sm:text-sm">
            Total: {{ history.length }} Deliveries
          </div>
        </div>
      </div>

      <!-- Top Bar Toolbar -->
      <div
        class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-slate-200 dark:border-slate-800/80 bg-white dark:bg-[#0b1527] p-3 sm:p-4 shadow-xs transition-all"
      >
        <!-- Left: Search & Filter Toggle -->
        <div class="flex flex-1 items-center gap-2.5 min-w-[280px] max-w-2xl">
          <!-- Search Input -->
          <div class="relative flex-1">
            <Search class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 dark:text-slate-500" />
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Search history by order # or room..."
              class="w-full rounded-xl border border-slate-200 dark:border-[#1e3455] bg-slate-50/80 dark:bg-[#13233c] text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 pl-10 pr-4 py-2.5 text-xs sm:text-sm focus:ring-2 focus:ring-indigo-500/40 focus:border-indigo-500 dark:focus:border-indigo-400 transition outline-none"
            />
          </div>

          <!-- Filter Toggle Button -->
          <button
            type="button"
            @click="toggleFilter"
            class="inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-xs sm:text-sm font-semibold transition cursor-pointer flex-shrink-0"
            :class="[
              isFilterOpen
                ? 'bg-indigo-600/10 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 border border-indigo-500/40'
                : 'border border-slate-200 dark:border-[#1e3455] bg-white dark:bg-[#13233c]/80 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-[#1c3356]'
            ]"
          >
            <component :is="isFilterOpen ? X : Filter" class="w-4 h-4" />
            <span>{{ isFilterOpen ? 'Hide Filter' : 'Filter' }}</span>
          </button>
        </div>

        <!-- Right: Action Buttons -->
        <div class="flex items-center gap-2 sm:gap-2.5">
          <!-- Refresh Button -->
          <button
            type="button"
            @click="fetchHistory"
            :disabled="loading"
            title="Refresh"
            class="inline-flex items-center justify-center h-9 w-9 sm:h-10 sm:w-10 rounded-xl border border-slate-200 dark:border-[#1e3455] bg-white dark:bg-[#13233c]/80 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-[#1c3356] hover:text-slate-900 dark:hover:text-white transition disabled:opacity-50 cursor-pointer"
          >
            <RefreshCw class="w-4 h-4" :class="{ 'animate-spin': loading }" />
          </button>

          <!-- Fullscreen Toggle -->
          <button
            type="button"
            @click="toggleFullscreen"
            title="Toggle Fullscreen"
            class="inline-flex items-center justify-center h-9 w-9 sm:h-10 sm:w-10 rounded-xl border border-slate-200 dark:border-[#1e3455] bg-white dark:bg-[#13233c]/80 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-[#1c3356] hover:text-slate-900 dark:hover:text-white transition cursor-pointer"
          >
            <component :is="isFullscreen ? Minimize2 : Maximize2" class="w-4 h-4" />
          </button>
        </div>
      </div>

      <!-- Expandable Filter Panel -->
      <Transition
        enter-active-class="transition duration-200 ease-out"
        enter-from-class="transform -translate-y-2 opacity-0 scale-98"
        enter-to-class="transform translate-y-0 opacity-100 scale-100"
        leave-active-class="transition duration-150 ease-in"
        leave-from-class="transform translate-y-0 opacity-100 scale-100"
        leave-to-class="transform -translate-y-2 opacity-0 scale-98"
      >
        <div
          v-if="isFilterOpen"
          class="rounded-2xl border border-slate-200 dark:border-slate-800/80 bg-white dark:bg-[#0b1527] p-4 sm:p-5 shadow-sm space-y-4"
        >
          <div class="grid grid-cols-1 sm:grid-cols-4 gap-3.5 sm:gap-4">
            <!-- Start Date -->
            <div>
              <label class="mb-1.5 block text-xs font-semibold text-slate-600 dark:text-slate-300">
                Start Date
              </label>
              <input
                v-model="filters.start_date"
                type="date"
                class="w-full rounded-xl border border-slate-200 dark:border-[#1e3455] bg-slate-50/80 dark:bg-[#13233c] text-slate-900 dark:text-white px-3.5 py-2 text-xs sm:text-sm focus:ring-2 focus:ring-indigo-500/40 focus:border-indigo-500 dark:focus:border-indigo-400 transition outline-none"
              />
            </div>

            <!-- End Date -->
            <div>
              <label class="mb-1.5 block text-xs font-semibold text-slate-600 dark:text-slate-300">
                End Date
              </label>
              <input
                v-model="filters.end_date"
                type="date"
                class="w-full rounded-xl border border-slate-200 dark:border-[#1e3455] bg-slate-50/80 dark:bg-[#13233c] text-slate-900 dark:text-white px-3.5 py-2 text-xs sm:text-sm focus:ring-2 focus:ring-indigo-500/40 focus:border-indigo-500 dark:focus:border-indigo-400 transition outline-none"
              />
            </div>

            <!-- Type -->
            <div>
              <label class="mb-1.5 block text-xs font-semibold text-slate-600 dark:text-slate-300">
                Service Type
              </label>
              <select
                v-model="filters.type"
                class="w-full rounded-xl border border-slate-200 dark:border-[#1e3455] bg-slate-50/80 dark:bg-[#13233c] text-slate-900 dark:text-white px-3.5 py-2 text-xs sm:text-sm focus:ring-2 focus:ring-indigo-500/40 focus:border-indigo-500 dark:focus:border-indigo-400 transition cursor-pointer font-medium outline-none"
              >
                <option value="all">All Types</option>
                <option value="room">Room Service</option>
                <option value="walk_in">Takeout / Table</option>
              </select>
            </div>

            <!-- Apply & Reset -->
            <div class="flex items-end gap-2">
              <button
                type="button"
                @click="fetchHistory"
                class="flex-1 inline-flex items-center justify-center gap-1.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white px-3.5 py-2 text-xs font-bold transition cursor-pointer"
              >
                <span>Apply</span>
              </button>
              <button
                type="button"
                @click="resetFilters"
                class="inline-flex items-center justify-center p-2 rounded-xl border border-slate-200 dark:border-[#1e3455] bg-slate-100/70 dark:bg-[#13233c] text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-[#1c3356] transition cursor-pointer"
                title="Reset"
              >
                <RotateCcw class="w-4 h-4" />
              </button>
            </div>
          </div>
        </div>
      </Transition>

      <!-- History Table -->
      <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-xs overflow-hidden font-sans w-full">
        <!-- Desktop Table View -->
        <div class="hidden md:block overflow-x-auto w-full">
          <table class="w-full text-left border-collapse">
            <thead class="bg-slate-50/90 dark:bg-[#0c182c] border-b border-slate-200 dark:border-[#1e3455]">
              <tr class="text-[11px] font-bold text-slate-500 dark:text-slate-400 select-none">
                <th class="py-3 px-4 pl-5 whitespace-nowrap">Order Ref</th>
                <th class="py-3 px-4 whitespace-nowrap">Room / Destination</th>
                <th class="py-3 px-4 text-center whitespace-nowrap">Status</th>
                <th class="py-3 px-4 whitespace-nowrap">Delivered At</th>
                <th class="py-3 px-4 text-right pr-5 whitespace-nowrap">Duration</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-[#1e3455]/60 text-xs">
              <tr
                v-for="item in paginatedHistory"
                :key="item.id"
                class="hover:bg-slate-50/80 dark:hover:bg-[#13233c]/60 transition-colors duration-150 group"
              >
                <!-- Order Ref -->
                <td class="py-3 px-4 pl-5 whitespace-nowrap font-mono font-extrabold text-blue-600 dark:text-blue-400 text-xs">
                  #{{ item.order_number || item.order_id || String(item.id).substring(0, 8) }}
                </td>

                <!-- Room / Destination -->
                <td class="py-3 px-4 whitespace-nowrap">
                  <span
                    v-if="item.room_number || item.room?.room_number"
                    class="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-800 dark:text-slate-200 font-bold text-xs border border-slate-200 dark:border-slate-700"
                  >
                    <BedDouble class="w-3 h-3 text-slate-400" />
                    Room {{ item.room_number || item.room?.room_number }}
                  </span>
                  <span
                    v-else
                    class="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-300 font-bold text-xs border border-amber-200 dark:border-amber-800"
                  >
                    <ShoppingBag class="w-3 h-3" />
                    Takeout
                  </span>
                </td>

                <!-- Status -->
                <td class="py-3 px-4 text-center whitespace-nowrap">
                  <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20 uppercase tracking-wider">
                    <CheckCircle2 class="w-3.5 h-3.5" />
                    <span>Delivered</span>
                  </span>
                </td>

                <!-- Delivered At -->
                <td class="py-3 px-4 whitespace-nowrap text-slate-600 dark:text-slate-400">
                  {{ formatDateTime(item.delivered_at || item.created_at || item.assigned_at) }}
                </td>

                <!-- Duration -->
                <td class="py-3 px-4 text-right pr-5 whitespace-nowrap">
                  <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-lg bg-blue-50 dark:bg-blue-950/40 text-blue-700 dark:text-blue-300 font-bold font-mono text-xs border border-blue-200 dark:border-blue-800">
                    <Timer class="w-3.5 h-3.5" />
                    {{ formatDuration(item.delivery_time_minutes || item.delivery_time || item.delivery_duration) }} min
                  </span>
                </td>
              </tr>

              <!-- Empty State -->
              <tr v-if="paginatedHistory.length === 0">
                <td colspan="5" class="px-6 py-12 text-center text-slate-500 dark:text-slate-400 text-xs font-bold">
                  No delivery history records found.
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Mobile View -->
        <div class="md:hidden divide-y divide-slate-100 dark:divide-slate-800">
          <div
            v-for="item in paginatedHistory"
            :key="item.id"
            class="p-4 space-y-3 hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition"
          >
            <div class="flex items-center justify-between">
              <span class="font-mono font-bold text-blue-600 dark:text-blue-400 text-xs">
                #{{ item.order_number || item.order_id }}
              </span>
              <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/10 text-emerald-600 border border-emerald-500/20 uppercase">
                Delivered
              </span>
            </div>
            <div class="flex items-center justify-between text-xs text-slate-600 dark:text-slate-400">
              <span>{{ formatDateTime(item.delivered_at || item.created_at) }}</span>
              <span class="font-bold">{{ formatDuration(item.delivery_time_minutes || item.delivery_time) }} min</span>
            </div>
          </div>
        </div>

        <!-- Pagination Footer -->
        <div
          v-if="total > 0"
          class="border-t border-slate-200 dark:border-slate-800/80 px-4 sm:px-6 py-3 sm:py-4 bg-slate-50/50 dark:bg-[#0c182c] flex flex-col sm:flex-row items-center justify-between gap-3 text-xs"
        >
          <div class="text-slate-500 dark:text-slate-400 font-medium">
            Showing <span class="font-bold text-slate-900 dark:text-white">{{ showingFrom }}</span> to
            <span class="font-bold text-slate-900 dark:text-white">{{ showingTo }}</span> of
            <span class="font-bold text-slate-900 dark:text-white">{{ total }}</span> deliveries
          </div>

          <div class="flex items-center gap-2 sm:gap-3">
            <div class="flex items-center gap-1.5">
              <span class="text-slate-500 dark:text-slate-400 font-medium">Per page:</span>
              <select
                :value="itemsPerPage"
                @change="changeItemsPerPage"
                class="rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-[#13233c] text-slate-900 dark:text-white px-2 py-1 text-xs outline-none"
              >
                <option :value="10">10</option>
                <option :value="20">20</option>
                <option :value="50">50</option>
              </select>
            </div>

            <div class="flex items-center gap-1">
              <button
                @click="prevPage"
                :disabled="currentPage === 1"
                class="p-1 rounded-lg border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 disabled:opacity-40 transition cursor-pointer"
              >
                <ChevronLeft class="w-4 h-4" />
              </button>

              <button
                v-for="page in paginationPages"
                :key="page"
                @click="goToPage(page)"
                class="px-2.5 py-1 rounded-lg text-xs font-bold transition cursor-pointer"
                :class="[
                  currentPage === page
                    ? 'bg-indigo-600 text-white'
                    : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800'
                ]"
              >
                {{ page }}
              </button>

              <button
                @click="nextPage"
                :disabled="currentPage === totalPages"
                class="p-1 rounded-lg border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 disabled:opacity-40 transition cursor-pointer"
              >
                <ChevronRight class="w-4 h-4" />
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>
