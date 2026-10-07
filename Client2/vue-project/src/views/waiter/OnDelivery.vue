<script setup lang="ts">
import { ref, onMounted, computed, watch } from 'vue'
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import waiterService from '@/services/waiterService'
import { useHotelStore } from '@/stores/hotelStore'
import { useLanguageStore } from '@/stores/language'
import {
  Search,
  Filter,
  X,
  RefreshCw,
  Maximize2,
  Minimize2,
  RotateCcw,
  Truck,
  CheckCircle2,
  ChevronLeft,
  ChevronRight,
  Loader2,
  BedDouble,
  ShoppingBag,
  Building2,
} from 'lucide-vue-next'

const hotelStore = useHotelStore()
const languageStore = useLanguageStore()

const loading = ref(true)
const deliveries = ref<any[]>([])
const currentPage = ref(1)
const itemsPerPage = ref(10)
const completingId = ref<string | null>(null)

const isFilterOpen = ref(false)
const isFullscreen = ref(false)
const searchQuery = ref('')
const selectedPriority = ref('all')
const selectedType = ref('all')

const filteredDeliveries = computed(() => {
  const list = deliveries.value || []
  const priorityFilter = selectedPriority.value
  const typeFilter = selectedType.value
  const q = searchQuery.value.trim().toLowerCase()

  if (priorityFilter === 'all' && typeFilter === 'all' && !q) {
    return list
  }

  return list.filter((d) => {
    if (priorityFilter !== 'all' && (d.priority || 'normal').toLowerCase() !== priorityFilter.toLowerCase()) {
      return false
    }

    const hasRoom = Boolean(d.room_number || d.room?.room_number)
    if (typeFilter === 'room' && !hasRoom) return false
    if (typeFilter === 'walk_in' && hasRoom) return false

    if (q) {
      const ordNum = String(d.order_number || d.order_id || d.id || '').toLowerCase()
      const roomNum = String(d.room_number || d.room?.room_number || '').toLowerCase()
      const guest = String(d.guest_name || d.guest?.full_name || '').toLowerCase()
      if (!ordNum.includes(q) && !roomNum.includes(q) && !guest.includes(q)) {
        return false
      }
    }

    return true
  })
})

const total = computed(() => filteredDeliveries.value.length)
const totalPages = computed(() => Math.ceil(total.value / itemsPerPage.value) || 1)

const paginatedDeliveries = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage.value
  return filteredDeliveries.value.slice(start, start + itemsPerPage.value)
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

watch(itemsPerPage, () => {
  currentPage.value = 1
})

const resetFilters = () => {
  searchQuery.value = ''
  selectedPriority.value = 'all'
  selectedType.value = 'all'
  currentPage.value = 1
}

const toggleFilter = () => {
  isFilterOpen.value = !isFilterOpen.value
}

const toggleFullscreen = () => {
  isFullscreen.value = !isFullscreen.value
}

const loadDeliveries = async () => {
  try {
    loading.value = true
    const data = await waiterService.getActiveDeliveries()
    if (Array.isArray(data)) {
      deliveries.value = data
    } else if (data && Array.isArray((data as any).data)) {
      deliveries.value = (data as any).data
    } else {
      deliveries.value = []
    }
    currentPage.value = 1
  } catch (err: any) {
    console.error('[OnDelivery] Error loading deliveries:', err)
  } finally {
    loading.value = false
  }
}

const completeDelivery = async (deliveryId: string) => {
  try {
    completingId.value = deliveryId
    await waiterService.completeDelivery(deliveryId)
    deliveries.value = deliveries.value.filter((d: any) => d.id !== deliveryId && d.order_id !== deliveryId)
    await loadDeliveries()
  } catch (err: any) {
    console.error('[OnDelivery] Error completing delivery:', err)
    alert(err.message || 'Failed to complete delivery')
  } finally {
    completingId.value = null
  }
}

const formatDateTime = (dateStr: string) => {
  if (!dateStr) return '-'
  const d = new Date(dateStr)
  return isNaN(d.getTime()) ? dateStr : d.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' })
}

onMounted(() => {
  loadDeliveries()
})

watch(() => hotelStore.hotelId, () => {
  loadDeliveries()
})
</script>

<template>
  <DashboardLayout>
    <div
      class="space-y-6 bg-slate-50 dark:bg-slate-950 min-h-screen p-4 sm:p-6 max-w-full overflow-hidden font-sans"
      :class="{ 'fixed inset-0 z-50 p-6 overflow-y-auto bg-white dark:bg-slate-950': isFullscreen }"
    >
      <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-5 sm:p-6 rounded-2xl shadow-xs flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-purple-600 to-purple-700 flex items-center justify-center shadow-md flex-shrink-0 text-white">
            <Truck class="w-5 h-5 stroke-[2.2]" />
          </div>
          <div>
            <div class="flex items-center gap-2">
              <h1 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white">{{ languageStore.t('on_delivery', 'On Delivery') }}</h1>
              <span v-if="hotelStore.hotelName" class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300 border border-blue-200 dark:border-blue-700/50">
                <Building2 class="w-3 h-3" />
                {{ hotelStore.hotelName }}
              </span>
            </div>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">{{ languageStore.t('on_delivery_sub', 'Track your active room deliveries and mark completed once delivered.') }}</p>
          </div>
        </div>

        <div class="flex items-center gap-2">
          <div class="px-4 py-2 rounded-xl bg-purple-500/10 border border-purple-500/20 text-purple-600 dark:text-purple-400 font-extrabold text-xs sm:text-sm">
            {{ languageStore.t('in_transit', 'In Transit') }}: {{ deliveries.length }} {{ languageStore.t('deliveries', 'Deliveries') }}
          </div>
        </div>
      </div>

      <div
        class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-slate-200 dark:border-slate-800/80 bg-white dark:bg-[#0b1527] p-3 sm:p-4 shadow-xs transition-all"
      >
        <div class="flex flex-1 items-center gap-2.5 min-w-[280px] max-w-2xl">
          <div class="relative flex-1">
            <Search class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 dark:text-slate-500" />
            <input
              v-model="searchQuery"
              type="text"
              :placeholder="languageStore.t('search_active_deliveries_placeholder', 'Search active deliveries by #, room, or guest...')"
              class="w-full rounded-xl border border-slate-200 dark:border-[#1e3455] bg-slate-50/80 dark:bg-[#13233c] text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 pl-10 pr-4 py-2.5 text-xs sm:text-sm focus:ring-2 focus:ring-purple-500/40 focus:border-purple-500 dark:focus:border-purple-400 transition outline-none"
            />
          </div>

          <button
            type="button"
            @click="toggleFilter"
            class="inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-xs sm:text-sm font-semibold transition cursor-pointer flex-shrink-0"
            :class="[
              isFilterOpen
                ? 'bg-purple-600/10 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400 border border-purple-500/40'
                : 'border border-slate-200 dark:border-[#1e3455] bg-white dark:bg-[#13233c]/80 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-[#1c3356]'
            ]"
          >
            <component :is="isFilterOpen ? X : Filter" class="w-4 h-4" />
            <span>{{ isFilterOpen ? languageStore.t('hide_filter', 'Hide Filter') : languageStore.t('filter', 'Filter') }}</span>
          </button>
        </div>

        <div class="flex items-center gap-2 sm:gap-2.5">
          <button
            type="button"
            @click="loadDeliveries"
            :disabled="loading"
            :title="languageStore.t('refresh', 'Refresh')"
            class="inline-flex items-center justify-center h-9 w-9 sm:h-10 sm:w-10 rounded-xl border border-slate-200 dark:border-[#1e3455] bg-white dark:bg-[#13233c]/80 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-[#1c3356] hover:text-slate-900 dark:hover:text-white transition disabled:opacity-50 cursor-pointer"
          >
            <RefreshCw class="w-4 h-4" :class="{ 'animate-spin': loading }" />
          </button>

          <button
            type="button"
            @click="toggleFullscreen"
            :title="languageStore.t('toggle_fullscreen', 'Toggle Fullscreen')"
            class="inline-flex items-center justify-center h-9 w-9 sm:h-10 sm:w-10 rounded-xl border border-slate-200 dark:border-[#1e3455] bg-white dark:bg-[#13233c]/80 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-[#1c3356] hover:text-slate-900 dark:hover:text-white transition cursor-pointer"
          >
            <component :is="isFullscreen ? Minimize2 : Maximize2" class="w-4 h-4" />
          </button>
        </div>
      </div>

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
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-3.5 sm:gap-4">
            <div>
              <label class="mb-1.5 block text-xs font-semibold text-slate-600 dark:text-slate-300">
                {{ languageStore.t('priority', 'Priority') }}
              </label>
              <select
                v-model="selectedPriority"
                class="w-full rounded-xl border border-slate-200 dark:border-[#1e3455] bg-slate-50/80 dark:bg-[#13233c] text-slate-900 dark:text-white px-3.5 py-2 text-xs sm:text-sm focus:ring-2 focus:ring-purple-500/40 focus:border-purple-500 dark:focus:border-purple-400 transition cursor-pointer font-medium outline-none"
              >
                <option value="all">{{ languageStore.t('all_priorities', 'All Priorities') }}</option>
                <option value="normal">{{ languageStore.t('normal_priority', 'Normal Priority') }}</option>
                <option value="high">{{ languageStore.t('high_priority', 'High Priority') }}</option>
              </select>
            </div>

            <div>
              <label class="mb-1.5 block text-xs font-semibold text-slate-600 dark:text-slate-300">
                {{ languageStore.t('destination', 'Destination') }}
              </label>
              <select
                v-model="selectedType"
                class="w-full rounded-xl border border-slate-200 dark:border-[#1e3455] bg-slate-50/80 dark:bg-[#13233c] text-slate-900 dark:text-white px-3.5 py-2 text-xs sm:text-sm focus:ring-2 focus:ring-purple-500/40 focus:border-purple-500 dark:focus:border-purple-400 transition cursor-pointer font-medium outline-none"
              >
                <option value="all">{{ languageStore.t('all_deliveries', 'All Deliveries') }}</option>
                <option value="room">{{ languageStore.t('room_service', 'Room Service') }}</option>
                <option value="walk_in">{{ languageStore.t('takeout_table', 'Takeout / Table') }}</option>
              </select>
            </div>

            <div class="flex items-end">
              <button
                type="button"
                @click="resetFilters"
                class="w-full inline-flex items-center justify-center gap-1.5 rounded-xl border border-slate-200 dark:border-[#1e3455] bg-slate-100/70 dark:bg-[#13233c] px-3.5 py-2 text-xs font-semibold text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-[#1c3356] transition cursor-pointer"
              >
                <RotateCcw class="w-3.5 h-3.5" />
                <span>{{ languageStore.t('reset_filters', 'Reset Filters') }}</span>
              </button>
            </div>
          </div>
        </div>
      </Transition>

      <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-xs overflow-hidden font-sans w-full">
        <div class="hidden md:block overflow-x-auto w-full">
          <table class="w-full text-left border-collapse">
            <thead class="bg-slate-50/90 dark:bg-[#0c182c] border-b border-slate-200 dark:border-[#1e3455]">
              <tr class="text-[11px] font-bold text-slate-500 dark:text-slate-400 select-none">
                <th class="py-3 px-4 pl-5 whitespace-nowrap">{{ languageStore.t('order_ref', 'Order Ref') }}</th>
                <th class="py-3 px-4 whitespace-nowrap">{{ languageStore.t('destination', 'Room / Destination') }}</th>
                <th class="py-3 px-4 whitespace-nowrap">{{ languageStore.t('guest_name', 'Guest Name') }}</th>
                <th class="py-3 px-4 whitespace-nowrap">{{ languageStore.t('started_at', 'Started At') }}</th>
                <th class="py-3 px-4 text-center whitespace-nowrap">{{ languageStore.t('priority', 'Priority') }}</th>
                <th class="py-3 px-4 text-right pr-5 whitespace-nowrap">{{ languageStore.t('actions', 'Action') }}</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-[#1e3455]/60 text-xs">
              <tr v-if="loading">
                <td colspan="6" class="px-6 py-20 text-center">
                  <div class="flex flex-col items-center justify-center gap-3">
                    <Loader2 class="w-8 h-8 text-purple-600 dark:text-purple-400 animate-spin" />
                    <div class="space-y-0.5">
                      <p class="text-xs sm:text-sm font-extrabold text-slate-800 dark:text-slate-200">{{ languageStore.t('loading_deliveries', 'Loading active deliveries...') }}</p>
                      <p class="text-[11px] text-slate-500 dark:text-slate-400">{{ languageStore.t('fetching_live_orders', 'Fetching live orders currently in transit') }}</p>
                    </div>
                  </div>
                </td>
              </tr>

              <template v-else>
                <tr
                  v-for="delivery in paginatedDeliveries"
                  :key="delivery.id"
                  class="hover:bg-slate-50/80 dark:hover:bg-[#13233c]/60 transition-colors duration-150 group"
                >
                  <td class="py-3 px-4 pl-5 whitespace-nowrap font-mono font-extrabold text-blue-600 dark:text-blue-400 text-xs">
                    #{{ delivery.order_number || delivery.order_id || String(delivery.id).substring(0, 8) }}
                  </td>

                  <td class="py-3 px-4 whitespace-nowrap">
                    <span
                      v-if="delivery.room_number || delivery.room?.room_number"
                      class="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-800 dark:text-slate-200 font-bold text-xs border border-slate-200 dark:border-slate-700"
                    >
                      <BedDouble class="w-3 h-3 text-slate-400" />
                      {{ languageStore.t('room', 'Room') }} {{ delivery.room_number || delivery.room?.room_number }}
                    </span>
                    <span
                      v-else
                      class="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-300 font-bold text-xs border border-amber-200 dark:border-amber-800"
                    >
                      <ShoppingBag class="w-3 h-3" />
                      {{ languageStore.t('takeout', 'Takeout') }}
                    </span>
                  </td>

                  <td class="py-3 px-4 whitespace-nowrap font-medium text-slate-900 dark:text-white">
                    {{ delivery.guest_name || delivery.guest?.full_name || languageStore.t('guest', 'Guest') }}
                  </td>

                  <td class="py-3 px-4 whitespace-nowrap text-slate-600 dark:text-slate-400">
                    {{ formatDateTime(delivery.on_delivery_at || delivery.picked_up_at || delivery.created_at) }}
                  </td>

                  <td class="py-3 px-4 text-center whitespace-nowrap">
                    <span
                      class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-extrabold border uppercase tracking-wider"
                      :class="[
                        (delivery.priority || '').toLowerCase() === 'high'
                          ? 'bg-rose-500/10 text-rose-600 dark:text-rose-400 border-rose-500/20'
                          : 'bg-blue-500/10 text-blue-600 dark:text-blue-400 border-blue-500/20'
                      ]"
                    >
                      {{ languageStore.t((delivery.priority || 'Normal').toLowerCase(), delivery.priority || 'Normal') }}
                    </span>
                  </td>

                  <td class="py-3 px-4 text-right pr-5 whitespace-nowrap">
                    <button
                      @click="completeDelivery(delivery.id)"
                      :disabled="completingId === delivery.id"
                      class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow-xs transition cursor-pointer disabled:opacity-50"
                    >
                      <Loader2 v-if="completingId === delivery.id" class="w-3.5 h-3.5 animate-spin" />
                      <CheckCircle2 v-else class="w-3.5 h-3.5" />
                      <span>{{ completingId === delivery.id ? languageStore.t('processing', 'Completing...') : languageStore.t('delivered', 'Delivered') }}</span>
                    </button>
                  </td>
                </tr>

                <tr v-if="paginatedDeliveries.length === 0">
                  <td colspan="6" class="px-6 py-12 text-center text-slate-500 dark:text-slate-400 text-xs font-bold">
                    {{ languageStore.t('no_active_deliveries', 'No active deliveries in transit right now.') }}
                  </td>
                </tr>
              </template>
            </tbody>
          </table>
        </div>

        <div class="md:hidden divide-y divide-slate-100 dark:divide-slate-800">
          <div v-if="loading" class="py-16 text-center flex flex-col items-center justify-center gap-3">
            <Loader2 class="w-8 h-8 text-purple-600 dark:text-purple-400 animate-spin" />
            <span class="text-xs font-extrabold text-slate-700 dark:text-slate-300">{{ languageStore.t('loading_deliveries', 'Loading active deliveries...') }}</span>
          </div>
          <template v-else>
            <div
              v-for="delivery in paginatedDeliveries"
              :key="delivery.id"
              class="p-4 space-y-3 hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition"
            >
              <div class="flex items-center justify-between">
                <span class="font-mono font-bold text-blue-600 dark:text-blue-400 text-xs">
                  #{{ delivery.order_number || delivery.order_id }}
                </span>
                <button
                  @click="completeDelivery(delivery.id)"
                  :disabled="completingId === delivery.id"
                  class="px-3 py-1 bg-emerald-600 text-white font-bold text-xs rounded-lg inline-flex items-center gap-1 disabled:opacity-50"
                >
                  <Loader2 v-if="completingId === delivery.id" class="w-3 h-3 animate-spin" />
                  <span>{{ completingId === delivery.id ? languageStore.t('processing', 'Completing...') : languageStore.t('delivered', 'Delivered') }}</span>
                </button>
              </div>
              <div class="flex items-center justify-between text-xs text-slate-600 dark:text-slate-400">
                <span>{{ delivery.guest_name || languageStore.t('guest', 'Guest') }}</span>
                <span>{{ languageStore.t('room', 'Room') }} {{ delivery.room_number || 'N/A' }}</span>
              </div>
            </div>
            <div v-if="paginatedDeliveries.length === 0" class="p-8 text-center text-slate-500 text-xs font-bold">
              {{ languageStore.t('no_active_deliveries', 'No active deliveries in transit right now.') }}
            </div>
          </template>
        </div>

        <div
          v-if="total > 0"
          class="border-t border-slate-200 dark:border-slate-800/80 px-4 sm:px-6 py-3 sm:py-4 bg-slate-50/50 dark:bg-[#0c182c] flex flex-col sm:flex-row items-center justify-between gap-3 text-xs"
        >
          <div class="text-slate-500 dark:text-slate-400 font-medium">
            {{ languageStore.t('showing', 'Showing') }} <span class="font-bold text-slate-900 dark:text-white">{{ showingFrom }}</span> {{ languageStore.t('to', 'to') }}
            <span class="font-bold text-slate-900 dark:text-white">{{ showingTo }}</span> {{ languageStore.t('of', 'of') }}
            <span class="font-bold text-slate-900 dark:text-white">{{ total }}</span> {{ languageStore.t('deliveries', 'deliveries') }}
          </div>

          <div class="flex items-center gap-2 sm:gap-3">
            <div class="flex items-center gap-1.5">
              <span class="text-slate-500 dark:text-slate-400 font-medium">{{ languageStore.t('per_page', 'Per page:') }}</span>
              <select
                v-model.number="itemsPerPage"
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
                    ? 'bg-purple-600 text-white'
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
