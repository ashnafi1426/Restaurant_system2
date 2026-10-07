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
  Clock,
  ChevronLeft,
  ChevronRight,
  Loader2,
  BedDouble,
  ShoppingBag,
  PackageCheck,
  Building2,
} from 'lucide-vue-next'

interface ReadyOrder {
  id: string | number
  order_number?: string
  order_id?: string
  guest_name?: string
  guest?: { full_name?: string }
  room_number?: string | number
  room?: { room_number?: string | number }
  items?: number | any[]
  special_requests?: string
}

const hotelStore = useHotelStore()
const languageStore = useLanguageStore()

const loading = ref(true)
const orders = ref<ReadyOrder[]>([])
const currentPage = ref(1)
const itemsPerPage = ref(10)
const loadingOrderId = ref<string | null>(null)

const isFilterOpen = ref(false)
const isFullscreen = ref(false)
const searchQuery = ref('')
const selectedType = ref('all')

const getOrderRoomNumber = (order: ReadyOrder) => order.room_number || order.room?.room_number
const getOrderGuestName = (order: ReadyOrder) => order.guest_name || order.guest?.full_name || languageStore.t('guest', 'Guest')
const getOrderReference = (order: ReadyOrder) => order.order_number || order.order_id || String(order.id).substring(0, 8)
const getOrderItemsCount = (order: ReadyOrder) => {
  if (typeof order.items === 'number') return order.items
  if (Array.isArray(order.items)) return order.items.length
  return 1
}

const filteredOrders = computed(() => {
  const list = orders.value || []
  const typeFilter = selectedType.value
  const query = searchQuery.value.trim().toLowerCase()

  if (typeFilter === 'all' && !query) return list

  return list.filter((order) => {
    const hasRoom = Boolean(getOrderRoomNumber(order))
    if (typeFilter === 'room' && !hasRoom) return false
    if (typeFilter === 'walk_in' && hasRoom) return false

    if (query) {
      const orderRef = String(getOrderReference(order)).toLowerCase()
      const roomNum = String(getOrderRoomNumber(order) || '').toLowerCase()
      const guestName = String(getOrderGuestName(order)).toLowerCase()

      if (!orderRef.includes(query) && !roomNum.includes(query) && !guestName.includes(query)) {
        return false
      }
    }

    return true
  })
})

const total = computed(() => filteredOrders.value.length)
const totalPages = computed(() => Math.ceil(total.value / itemsPerPage.value) || 1)

const paginatedOrders = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage.value
  return filteredOrders.value.slice(start, start + itemsPerPage.value)
})

const showingFrom = computed(() => (total.value === 0 ? 0 : (currentPage.value - 1) * itemsPerPage.value + 1))
const showingTo = computed(() => Math.min(currentPage.value * itemsPerPage.value, total.value))

const paginationPages = computed(() => {
  const pages: number[] = []
  const max = totalPages.value
  const cur = currentPage.value

  for (let i = Math.max(1, cur - 2); i <= Math.min(max, cur + 2); i++) {
    pages.push(i)
  }
  return pages
})

const goToPage = (page: number) => {
  if (page >= 1 && page <= totalPages.value) {
    currentPage.value = page
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
  selectedType.value = 'all'
  currentPage.value = 1
}

const toggleFilter = () => {
  isFilterOpen.value = !isFilterOpen.value
}

const toggleFullscreen = () => {
  isFullscreen.value = !isFullscreen.value
}

const loadOrders = async () => {
  try {
    loading.value = true
    const data = await waiterService.getReadyForPickupOrders()
    orders.value = Array.isArray(data) ? data : (data as any)?.data || []
    currentPage.value = 1
  } catch (err: any) {
    console.error('[ReadyPickup] Error loading ready orders:', err)
  } finally {
    loading.value = false
  }
}

const pickupOrder = async (orderId: string | number) => {
  try {
    loadingOrderId.value = String(orderId)
    await waiterService.pickupOrder(String(orderId))
    orders.value = orders.value.filter((o) => o.id !== orderId && o.order_id !== String(orderId))
    await loadOrders()
  } catch (err: any) {
    console.error('[ReadyPickup] Error picking up order:', err)
    alert(err.message || 'Failed to pickup order')
  } finally {
    loadingOrderId.value = null
  }
}

onMounted(loadOrders)
watch(() => hotelStore.hotelId, loadOrders)
</script>

<template>
  <DashboardLayout>
    <div
      class="space-y-6 bg-slate-50 dark:bg-slate-950 min-h-screen p-4 sm:p-6 max-w-full overflow-hidden font-sans"
      :class="{ 'fixed inset-0 z-50 p-6 overflow-y-auto bg-white dark:bg-slate-950': isFullscreen }"
    >
      <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-5 sm:p-6 rounded-2xl shadow-xs flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-amber-500 to-amber-600 flex items-center justify-center shadow-md flex-shrink-0 text-white">
            <Clock class="w-5 h-5 stroke-[2.2]" />
          </div>
          <div>
            <div class="flex items-center gap-2">
              <h1 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white">{{ languageStore.t('ready_for_pickup', 'Ready for Pickup') }}</h1>
              <span v-if="hotelStore.hotelName" class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300 border border-blue-200 dark:border-blue-700/50">
                <Building2 class="w-3 h-3" />
                {{ hotelStore.hotelName }}
              </span>
            </div>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">{{ languageStore.t('ready_pickup_sub', 'Orders cooked and prepared by kitchen, ready to be collected.') }}</p>
          </div>
        </div>

        <div class="flex items-center gap-2">
          <div class="px-4 py-2 rounded-xl bg-amber-500/10 border border-amber-500/20 text-amber-600 dark:text-amber-400 font-extrabold text-xs sm:text-sm">
            {{ languageStore.t('ready_for_pickup', 'Ready') }}: {{ orders.length }} {{ languageStore.t('orders', 'Orders') }}
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
              :placeholder="languageStore.t('search_ready_orders_placeholder', 'Search ready orders by #, room, or guest...')"
              class="w-full rounded-xl border border-slate-200 dark:border-[#1e3455] bg-slate-50/80 dark:bg-[#13233c] text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 pl-10 pr-4 py-2.5 text-xs sm:text-sm focus:ring-2 focus:ring-amber-500/40 focus:border-amber-500 dark:focus:border-amber-400 transition outline-none"
            />
          </div>

          <button
            type="button"
            @click="toggleFilter"
            class="inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-xs sm:text-sm font-semibold transition cursor-pointer flex-shrink-0"
            :class="[
              isFilterOpen
                ? 'bg-amber-600/10 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400 border border-amber-500/40'
                : 'border border-slate-200 dark:border-[#1e3455] bg-white dark:bg-[#13233c]/80 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-[#1c3356]'
            ]"
          >
            <X v-if="isFilterOpen" class="w-4 h-4" />
            <Filter v-else class="w-4 h-4" />
            <span>{{ isFilterOpen ? languageStore.t('hide_filter', 'Hide Filter') : languageStore.t('filter', 'Filter') }}</span>
          </button>
        </div>

        <div class="flex items-center gap-2 sm:gap-2.5">
          <button
            type="button"
            @click="loadOrders"
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
            <Minimize2 v-if="isFullscreen" class="w-4 h-4" />
            <Maximize2 v-else class="w-4 h-4" />
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
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5 sm:gap-4">
            <div>
              <label class="mb-1.5 block text-xs font-semibold text-slate-600 dark:text-slate-300">
                {{ languageStore.t('service_type', 'Service Type') }}
              </label>
              <select
                v-model="selectedType"
                class="w-full rounded-xl border border-slate-200 dark:border-[#1e3455] bg-slate-50/80 dark:bg-[#13233c] text-slate-900 dark:text-white px-3.5 py-2 text-xs sm:text-sm focus:ring-2 focus:ring-amber-500/40 focus:border-amber-500 dark:focus:border-amber-400 transition cursor-pointer font-medium outline-none"
              >
                <option value="all">{{ languageStore.t('all_pickup_orders', 'All Pickup Orders') }}</option>
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
                <th class="py-3 px-4 whitespace-nowrap">{{ languageStore.t('guest_name', 'Guest Name') }}</th>
                <th class="py-3 px-4 whitespace-nowrap">{{ languageStore.t('destination', 'Room / Destination') }}</th>
                <th class="py-3 px-4 whitespace-nowrap">{{ languageStore.t('items', 'Items') }}</th>
                <th class="py-3 px-4 whitespace-nowrap">{{ languageStore.t('special_requests', 'Special Requests') }}</th>
                <th class="py-3 px-4 text-right pr-5 whitespace-nowrap">{{ languageStore.t('actions', 'Action') }}</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-[#1e3455]/60 text-xs">
              <tr v-if="loading">
                <td colspan="6" class="px-6 py-16 text-center">
                  <div class="flex flex-col items-center justify-center gap-3">
                    <Loader2 class="w-8 h-8 text-emerald-600 dark:text-emerald-400 animate-spin" />
                    <span class="text-xs font-bold text-slate-600 dark:text-slate-400">{{ languageStore.t('loading_pickup_orders', 'Loading orders ready for pickup...') }}</span>
                  </div>
                </td>
              </tr>

              <template v-else>
                <tr
                  v-for="order in paginatedOrders"
                  :key="order.id"
                  class="hover:bg-slate-50/80 dark:hover:bg-[#13233c]/60 transition-colors duration-150 group"
                >
                  <td class="py-3 px-4 pl-5 whitespace-nowrap font-mono font-extrabold text-blue-600 dark:text-blue-400 text-xs">
                    #{{ getOrderReference(order) }}
                  </td>

                  <td class="py-3 px-4 whitespace-nowrap font-medium text-slate-900 dark:text-white">
                    {{ getOrderGuestName(order) }}
                  </td>

                  <td class="py-3 px-4 whitespace-nowrap">
                    <span
                      v-if="getOrderRoomNumber(order)"
                      class="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-800 dark:text-slate-200 font-bold text-xs border border-slate-200 dark:border-slate-700"
                    >
                      <BedDouble class="w-3 h-3 text-slate-400" />
                      {{ languageStore.t('room', 'Room') }} {{ getOrderRoomNumber(order) }}
                    </span>
                    <span
                      v-else
                      class="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-300 font-bold text-xs border border-amber-200 dark:border-amber-800"
                    >
                      <ShoppingBag class="w-3 h-3" />
                      {{ languageStore.t('takeout', 'Takeout') }}
                    </span>
                  </td>

                  <td class="py-3 px-4 whitespace-nowrap">
                    <span class="font-bold text-slate-700 dark:text-slate-300">
                      {{ getOrderItemsCount(order) }} {{ languageStore.t('items', 'items') }}
                    </span>
                  </td>

                  <td class="py-3 px-4 text-slate-500 dark:text-slate-400 max-w-xs truncate">
                    {{ order.special_requests || languageStore.t('none', 'None') }}
                  </td>

                  <td class="py-3 px-4 text-right pr-5 whitespace-nowrap">
                    <button
                      @click="pickupOrder(order.id)"
                      :disabled="loadingOrderId === String(order.id)"
                      class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow-xs transition cursor-pointer disabled:opacity-50"
                    >
                      <PackageCheck class="w-3.5 h-3.5" />
                      <span>{{ languageStore.t('pickup', 'Pickup') }}</span>
                    </button>
                  </td>
                </tr>

                <tr v-if="paginatedOrders.length === 0">
                  <td colspan="6" class="px-6 py-12 text-center text-slate-500 dark:text-slate-400 text-xs font-bold">
                    {{ languageStore.t('no_pickup_orders', 'No orders ready for pickup.') }}
                  </td>
                </tr>
              </template>
            </tbody>
          </table>
        </div>

        <div class="md:hidden divide-y divide-slate-100 dark:divide-slate-800">
          <div v-if="loading" class="py-12 text-center flex flex-col items-center justify-center gap-3">
            <Loader2 class="w-8 h-8 text-emerald-600 dark:text-emerald-400 animate-spin" />
            <span class="text-xs font-bold text-slate-600 dark:text-slate-400">{{ languageStore.t('loading_pickup_orders', 'Loading orders ready for pickup...') }}</span>
          </div>
          <template v-else>
            <div
              v-for="order in paginatedOrders"
              :key="order.id"
              class="p-4 space-y-3 hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition"
            >
              <div class="flex items-center justify-between">
                <span class="font-mono font-bold text-blue-600 dark:text-blue-400 text-xs">
                  #{{ getOrderReference(order) }}
                </span>
                <button
                  @click="pickupOrder(order.id)"
                  :disabled="loadingOrderId === String(order.id)"
                  class="px-3 py-1 bg-emerald-600 text-white font-bold text-xs rounded-lg transition disabled:opacity-50"
                >
                  {{ languageStore.t('pickup', 'Pickup') }}
                </button>
              </div>
              <div class="flex items-center justify-between text-xs text-slate-600 dark:text-slate-400">
                <span>{{ getOrderGuestName(order) }}</span>
                <span>{{ languageStore.t('room', 'Room') }} {{ getOrderRoomNumber(order) || 'N/A' }}</span>
              </div>
            </div>
            <div v-if="paginatedOrders.length === 0" class="p-8 text-center text-slate-500 text-xs font-bold">
              {{ languageStore.t('no_pickup_orders', 'No orders ready for pickup.') }}
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
            <span class="font-bold text-slate-900 dark:text-white">{{ total }}</span> {{ languageStore.t('orders', 'orders') }}
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
                    ? 'bg-amber-600 text-white'
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
