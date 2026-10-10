<script setup lang="ts">
import { ref, computed } from 'vue'
import {
  CookingPot,
  Search,
  Clock,
  ChefHat,
  Check,
  CheckCheck,
  Loader,
  Eye,
  UtensilsCrossed,
  Inbox,
  BedDouble,
} from 'lucide-vue-next'
import { useLanguageStore } from '@/stores/language'
import type { KitchenOrder } from '@/types/kitchen'

const languageStore = useLanguageStore()

const props = defineProps<{
  pendingOrders?: KitchenOrder[]
  preparingOrders?: KitchenOrder[]
  readyOrders?: KitchenOrder[]
  completedOrders?: KitchenOrder[]
  processing?: boolean
  loading?: boolean
}>()

const emit = defineEmits<{
  (e: 'start', order: KitchenOrder): void
  (e: 'ready', order: KitchenOrder): void
  (e: 'served', order: KitchenOrder): void
  (e: 'view', order: KitchenOrder): void
}>()

const selectedFilter = ref('all')
const searchQuery = ref('')

const currentPage = ref(1)
const itemsPerPage = ref(10)

const processingOrderId = ref<string | null>(null)

const isProcessing = computed(() => (orderId: string) => {
  return processingOrderId.value === orderId
})

const allOrdersWithStatus = computed(() => {
  const orders: Array<KitchenOrder & { status: string }> = []

  props.pendingOrders?.forEach((order) => orders.push({ ...order, status: 'pending' }))
  props.preparingOrders?.forEach((order) => orders.push({ ...order, status: 'preparing' }))
  props.readyOrders?.forEach((order) => orders.push({ ...order, status: 'ready' }))
  props.completedOrders?.forEach((order) => orders.push({ ...order, status: 'served' }))

  return orders
})

const filteredOrders = computed(() => {
  let filtered = allOrdersWithStatus.value

  if (selectedFilter.value !== 'all') {
    filtered = filtered.filter((order) => order.status === selectedFilter.value)
  }

  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase().trim()
    filtered = filtered.filter(
      (order) =>
        order.room?.room_number?.toString().toLowerCase().includes(query) ||
        order.table?.table_number?.toString().toLowerCase().includes(query) ||
        order.table?.table_name?.toLowerCase().includes(query) ||
        order.order_number?.toLowerCase().includes(query) ||
        order.guest?.full_name?.toLowerCase().includes(query) ||
        order.items?.some((item) => item.name?.toLowerCase().includes(query)),
    )
  }

  return filtered
})

const totalPages = computed(() => {
  return Math.ceil(filteredOrders.value.length / itemsPerPage.value) || 1
})

const paginatedOrders = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage.value
  const end = start + itemsPerPage.value
  return filteredOrders.value.slice(start, end)
})

const startItem = computed(() => {
  return filteredOrders.value.length === 0 ? 0 : (currentPage.value - 1) * itemsPerPage.value + 1
})

const endItem = computed(() => {
  return Math.min(currentPage.value * itemsPerPage.value, filteredOrders.value.length)
})

const hasPrevPage = computed(() => currentPage.value > 1)
const hasNextPage = computed(() => currentPage.value < totalPages.value)

const goToPage = (page: number) => {
  if (page >= 1 && page <= totalPages.value) {
    currentPage.value = page
  }
}

const goToPrevPage = () => {
  if (hasPrevPage.value) {
    currentPage.value--
  }
}

const goToNextPage = () => {
  if (hasNextPage.value) {
    currentPage.value++
  }
}

const changeItemsPerPage = (newAmount: number) => {
  itemsPerPage.value = newAmount
  currentPage.value = 1
}

const getPaginationRange = (): number[] => {
  const range: number[] = []
  const start = Math.max(2, currentPage.value - 1)
  const end = Math.min(totalPages.value - 1, currentPage.value + 1)

  for (let i = start; i <= end; i++) {
    range.push(i)
  }

  return range
}

async function handleStartPreparing(order: KitchenOrder) {
  processingOrderId.value = order.id
  try {
    emit('start', order)
  } finally {
    setTimeout(() => {
      processingOrderId.value = null
    }, 500)
  }
}

async function handleMarkReady(order: KitchenOrder) {
  processingOrderId.value = order.id
  try {
    emit('ready', order)
  } finally {
    setTimeout(() => {
      processingOrderId.value = null
    }, 500)
  }
}

async function handleMarkServed(order: KitchenOrder) {
  processingOrderId.value = order.id
  try {
    emit('served', order)
  } finally {
    setTimeout(() => {
      processingOrderId.value = null
    }, 500)
  }
}

function getTimeRemaining(orderTime: string): string {
  try {
    const orderDate = new Date(orderTime)
    const now = new Date()
    const diff = Math.floor((now.getTime() - orderDate.getTime()) / 1000 / 60)
    return `${String(diff).padStart(2, '0')}:${String(Math.floor(now.getSeconds() % 60)).padStart(2, '0')} ${languageStore.t('min', 'mins')}`
  } catch (error) {
    console.error('[KitchenQueue] Error calculating time remaining:', error)
    return `-- ${languageStore.t('min', 'mins')}`
  }
}

function getItemsPreview(items: any[]): string {
  if (!items?.length) return 'No items'
  return items.map((i) => `${i.quantity}x ${i.name}`).join(', ')
}

function getStatusIcon(status: string) {
  switch (status) {
    case 'pending':
      return Clock
    case 'preparing':
      return ChefHat
    case 'ready':
      return Check
    case 'served':
      return CheckCheck
    default:
      return UtensilsCrossed
  }
}

function getStatusColor(status: string): string {
  const colors: Record<string, string> = {
    pending:
      'bg-amber-500/10 text-amber-600 dark:bg-amber-500/20 dark:text-amber-400 border border-amber-500/20',
    preparing:
      'bg-blue-500/10 text-blue-600 dark:bg-blue-500/20 dark:text-blue-400 border border-blue-500/20',
    ready:
      'bg-emerald-500/10 text-emerald-600 dark:bg-emerald-500/20 dark:text-emerald-400 border border-emerald-500/20',
    served:
      'bg-slate-500/10 text-slate-600 dark:bg-slate-800 dark:text-slate-400 border border-slate-500/20',
  }
  return colors[status] || 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300'
}

function getStatusBgRow(status: string): string {
  const colors: Record<string, string> = {
    pending: 'hover:bg-amber-50/50 dark:hover:bg-amber-950/20',
    preparing: 'hover:bg-blue-50/50 dark:hover:bg-blue-950/20',
    ready: 'hover:bg-emerald-50/50 dark:hover:bg-emerald-950/20',
    served: 'hover:bg-slate-50 dark:hover:bg-slate-800/50',
  }
  return colors[status] || 'hover:bg-slate-50 dark:hover:bg-slate-800/50'
}
</script>

<template>
  <div class="space-y-4 sm:space-y-5 md:space-y-6">
    <div class="space-y-4">
      <div class="min-w-0">
        <h2
          class="text-lg sm:text-xl md:text-2xl font-black text-slate-900 dark:text-white flex items-center gap-2"
        >
          <CookingPot class="w-6 h-6 text-amber-500" />
          <span>{{ languageStore.t('your_kitchen_orders', 'Your Kitchen Orders') }}</span>
          <span class="text-xs sm:text-sm text-amber-500 font-bold">●</span>
        </h2>
        <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1 font-medium">
          {{ languageStore.t('only_assigned_to_you', 'Only showing orders assigned to you') }}
        </p>
      </div>

      <div class="flex flex-col sm:flex-row gap-3 sm:gap-4">
        <div class="flex-1">
          <input
            v-model="searchQuery"
            type="text"
            :placeholder="
              languageStore.t('search_room_dish', 'Search by room number or dish name...')
            "
            class="w-full px-3 sm:px-4 py-2 border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-900 dark:text-white rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-amber-500"
          />
        </div>

        <div class="flex gap-1.5 flex-wrap">
          <button
            v-for="status in ['all', 'pending', 'preparing', 'ready', 'served']"
            :key="status"
            @click="selectedFilter = status"
            :class="[
              'px-3.5 sm:px-4 py-2 rounded-xl font-bold text-xs sm:text-sm transition cursor-pointer border',
              selectedFilter === status
                ? 'bg-slate-900 dark:bg-slate-800 text-white border-slate-900 dark:border-slate-700 shadow-md'
                : 'bg-white dark:bg-slate-900/80 text-slate-600 dark:text-slate-400 border-slate-200 dark:border-slate-800 hover:bg-slate-100 dark:hover:bg-slate-800',
            ]"
          >
            {{ languageStore.t(status, status).toUpperCase() }}
            <span v-if="status === 'all'" class="ml-1 text-xs opacity-75">
              ({{ filteredOrders.length }})
            </span>
            <span v-else class="ml-1 text-xs opacity-75">
              ({{ filteredOrders.filter((o) => o.status === status).length }})
            </span>
          </button>
        </div>
      </div>
    </div>

    <div
      class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-md overflow-hidden overflow-x-auto"
    >
      <table class="w-full text-sm">
        <thead>
          <tr
            class="bg-slate-50 dark:bg-slate-800/80 border-b border-slate-200 dark:border-slate-800"
          >
            <th
              class="px-3 sm:px-4 py-3 text-left font-black text-slate-700 dark:text-slate-200 uppercase text-xs tracking-wider"
            >
              {{ languageStore.t('location', 'Location') }}
            </th>
            <th
              class="px-3 sm:px-4 py-3 text-left font-black text-slate-700 dark:text-slate-200 uppercase text-xs tracking-wider"
            >
              {{ languageStore.t('guest', 'Guest') }}
            </th>
            <th
              class="px-3 sm:px-4 py-3 text-left font-black text-slate-700 dark:text-slate-200 uppercase text-xs tracking-wider"
            >
              {{ languageStore.t('items', 'Items') }}
            </th>
            <th
              class="px-3 sm:px-4 py-3 text-left font-black text-slate-700 dark:text-slate-200 uppercase text-xs tracking-wider"
            >
              {{ languageStore.t('time', 'Time') }}
            </th>
            <th
              class="px-3 sm:px-4 py-3 text-center font-black text-slate-700 dark:text-slate-200 uppercase text-xs tracking-wider"
            >
              {{ languageStore.t('action', 'Action') }}
            </th>
            <th
              class="px-3 sm:px-4 py-3 text-center font-black text-slate-700 dark:text-slate-200 uppercase text-xs tracking-wider"
            >
              {{ languageStore.t('status', 'Status') }}
            </th>
          </tr>
        </thead>

        <tbody>
          <tr
            v-for="order in paginatedOrders"
            :key="order.id"
            :class="[
              'border-b border-slate-200 dark:border-slate-800/60 transition cursor-pointer',
              getStatusBgRow(order.status),
            ]"
            @click="emit('view', order)"
          >
            <td class="px-3 sm:px-4 py-3">
              <div class="flex items-center gap-1.5 font-bold text-slate-900 dark:text-white">
                <span
                  v-if="order.table?.table_number || order.order_type === 'walk_in'"
                  class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 font-bold text-xs border border-emerald-200 dark:border-emerald-800"
                >
                  <UtensilsCrossed class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400" />
                  {{
                    order.table?.table_name ||
                    `${languageStore.t('table', 'Table')} ${order.table?.table_number || ''}`
                  }}
                </span>
                <span
                  v-else-if="order.room?.room_number"
                  class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-blue-50 dark:bg-blue-950/40 text-blue-700 dark:text-blue-300 font-bold text-xs border border-blue-200 dark:border-blue-800"
                >
                  <BedDouble class="w-3.5 h-3.5 text-blue-500" />
                  {{ languageStore.t('room', 'Room') }} {{ order.room.room_number }}
                </span>
                <span v-else class="text-slate-400">—</span>
              </div>
              <div v-if="order.notes" class="text-xs text-rose-500 font-black mt-0.5">
                {{ languageStore.t('priority', 'PRIORITY') }}
              </div>
            </td>

            <td class="px-3 sm:px-4 py-3">
              <div class="text-slate-700 dark:text-slate-300 font-medium">
                {{
                  order.guest?.full_name ||
                  (order.table
                    ? languageStore.t('walk_in_guest', 'Walk-in Guest')
                    : languageStore.t('qr_guest', 'QR Guest'))
                }}
              </div>
            </td>

            <td class="px-3 sm:px-4 py-3">
              <div class="text-slate-700 dark:text-slate-300 max-w-xs">
                <div
                  v-for="(item, idx) in (order.items || []).slice(0, 2)"
                  :key="idx"
                  class="truncate text-sm font-medium"
                >
                  <span class="font-bold text-amber-500">{{ item.quantity }}x</span> {{ item.name }}
                  <span v-if="item.notes" class="text-xs text-slate-400 ml-1"
                    >({{ item.notes }})</span
                  >
                </div>
                <div
                  v-if="(order.items || []).length > 2"
                  class="text-xs text-slate-500 dark:text-slate-400 font-bold"
                >
                  +{{ (order.items || []).length - 2 }}
                  {{ languageStore.t('more_items', 'more items') }}
                </div>
              </div>
            </td>

            <td class="px-3 sm:px-4 py-3">
              <div class="font-black text-slate-900 dark:text-white">
                {{ getTimeRemaining(order.order_time) }}
              </div>
            </td>

            <td class="px-3 sm:px-4 py-3">
              <div class="flex justify-center gap-2">
                <button
                  v-if="order.status === 'pending'"
                  @click.stop="handleStartPreparing(order)"
                  :disabled="isProcessing(order.id) || processing"
                  class="px-3 py-1.5 bg-amber-500 hover:bg-amber-600 disabled:opacity-50 text-slate-950 font-black rounded-xl text-xs transition cursor-pointer flex items-center gap-1.5 shadow-md shadow-amber-500/20"
                >
                  <Loader v-if="isProcessing(order.id)" class="w-3.5 h-3.5 animate-spin" />
                  <span v-else>{{ languageStore.t('start', 'START') }}</span>
                </button>

                <button
                  v-else-if="order.status === 'preparing'"
                  @click.stop="handleMarkReady(order)"
                  :disabled="isProcessing(order.id) || processing"
                  class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white font-bold rounded-xl text-xs transition cursor-pointer flex items-center gap-1.5 shadow-md shadow-blue-600/20"
                >
                  <Loader v-if="isProcessing(order.id)" class="w-3.5 h-3.5 animate-spin" />
                  <span v-else>{{ languageStore.t('ready', 'READY') }}</span>
                </button>

                <button
                  v-else-if="order.status === 'ready'"
                  @click.stop="handleMarkServed(order)"
                  :disabled="isProcessing(order.id) || processing"
                  class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 text-white font-bold rounded-xl text-xs transition cursor-pointer flex items-center gap-1.5 shadow-md shadow-emerald-600/20"
                >
                  <Loader v-if="isProcessing(order.id)" class="w-3.5 h-3.5 animate-spin" />
                  <span v-else>{{ languageStore.t('serve', 'SERVE') }}</span>
                </button>

                <button
                  v-else
                  @click.stop="emit('view', order)"
                  class="px-3 py-1.5 bg-slate-200 dark:bg-slate-800 hover:bg-slate-300 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-bold rounded-xl text-xs transition cursor-pointer flex items-center gap-1"
                >
                  <Eye class="w-3.5 h-3.5" />
                  <span>{{ languageStore.t('view', 'VIEW') }}</span>
                </button>
              </div>
            </td>

            <td class="px-3 sm:px-4 py-3 text-center">
              <span
                :class="[
                  'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-black uppercase tracking-wider',
                  getStatusColor(order.status),
                ]"
              >
                <component :is="getStatusIcon(order.status)" class="w-3.5 h-3.5" />
                <span>{{ languageStore.t(order.status, order.status) }}</span>
              </span>
            </td>
          </tr>
        </tbody>
      </table>

      <div v-if="props.loading" class="text-center py-16 bg-white dark:bg-slate-900">
        <Loader class="w-8 h-8 mx-auto text-amber-500 animate-spin mb-3" />
        <p class="text-sm font-bold text-slate-700 dark:text-slate-300">
          {{ languageStore.t('loading_food_orders', 'Loading kitchen orders...') }}
        </p>
      </div>

      <div
        v-else-if="filteredOrders.length === 0"
        class="text-center py-16 px-4 bg-white dark:bg-slate-900"
      >
        <div
          class="w-14 h-14 bg-amber-500/10 border border-amber-500/20 text-amber-500 rounded-2xl flex items-center justify-center mx-auto mb-4"
        >
          <Inbox class="w-7 h-7" />
        </div>
        <h3 class="text-base font-black text-slate-900 dark:text-white">
          {{ languageStore.t('no_food_orders_found', 'No Kitchen Orders Found') }}
        </h3>
        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 max-w-sm mx-auto font-medium">
          {{
            searchQuery
              ? languageStore.t('no_food_orders_found', 'No orders match your search term.')
              : languageStore.t('no_food_orders_found', `No orders at the moment.`)
          }}
        </p>
      </div>
    </div>

    <div
      v-if="filteredOrders.length > 0"
      class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-md p-4 sm:p-5"
    >
      <div class="flex flex-col lg:flex-row items-center justify-between gap-4">
        <div class="flex items-center gap-3 text-slate-600 dark:text-slate-400 text-xs">
          <label
            for="itemsPerPage"
            class="text-sm font-semibold text-slate-700 dark:text-slate-300"
            >{{ languageStore.t('per_page', 'Items per page:') }}</label
          >
          <select
            id="itemsPerPage"
            :value="itemsPerPage"
            @change="changeItemsPerPage(Number(($event.target as HTMLSelectElement).value))"
            class="px-3 py-2 border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 rounded-lg text-sm font-medium text-slate-900 dark:text-white hover:border-slate-400 focus:outline-none focus:ring-2 focus:ring-amber-500"
          >
            <option :value="5">5</option>
            <option :value="10">10</option>
            <option :value="15">15</option>
            <option :value="20">20</option>
            <option :value="25">25</option>
          </select>
        </div>

        <div class="flex flex-col sm:flex-row items-center gap-2 sm:gap-4">
          <button
            @click="goToPrevPage"
            :disabled="!hasPrevPage"
            class="px-3 py-1.5 border border-slate-200 dark:border-slate-800 rounded-xl text-xs font-bold text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 disabled:opacity-40 disabled:cursor-not-allowed transition cursor-pointer"
          >
            ← {{ languageStore.t('previous', 'Previous') }}
          </button>

          <div class="flex items-center gap-1">
            <button
              @click="goToPage(1)"
              :class="[
                'px-3 py-1.5 rounded-xl text-xs font-bold transition cursor-pointer border',
                currentPage === 1
                  ? 'bg-slate-900 dark:bg-slate-800 border-slate-900 dark:border-slate-700 text-white shadow-sm'
                  : 'border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800',
              ]"
            >
              1
            </button>

            <span v-if="currentPage > 3" class="px-1 text-slate-400 font-bold">...</span>

            <button
              v-for="page in getPaginationRange()"
              :key="page"
              @click="goToPage(page)"
              :class="[
                'px-3 py-1.5 rounded-xl text-xs font-bold transition cursor-pointer border',
                currentPage === page
                  ? 'bg-slate-900 dark:bg-slate-800 border-slate-900 dark:border-slate-700 text-white shadow-sm'
                  : 'border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800',
              ]"
            >
              {{ page }}
            </button>

            <span v-if="currentPage < totalPages - 2" class="px-1 text-slate-400 font-bold"
              >...</span
            >

            <button
              v-if="totalPages > 1"
              @click="goToPage(totalPages)"
              :class="[
                'px-3 py-1.5 rounded-xl text-xs font-bold transition cursor-pointer border',
                currentPage === totalPages
                  ? 'bg-slate-900 dark:bg-slate-800 border-slate-900 dark:border-slate-700 text-white shadow-sm'
                  : 'border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800',
              ]"
            >
              {{ totalPages }}
            </button>
          </div>

          <button
            @click="goToNextPage"
            :disabled="!hasNextPage"
            class="px-3 py-1.5 border border-slate-200 dark:border-slate-800 rounded-xl text-xs font-bold text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 disabled:opacity-40 disabled:cursor-not-allowed transition cursor-pointer"
          >
            {{ languageStore.t('next', 'Next') }} →
          </button>
        </div>

        <div class="text-xs font-bold text-slate-600 dark:text-slate-400 whitespace-nowrap">
          {{ languageStore.t('showing', 'Showing') }}
          <span class="text-amber-500 font-black">{{ startItem }}-{{ endItem }}</span>
          {{ languageStore.t('of', 'of') }}
          <span class="text-amber-500 font-black">{{ filteredOrders.length }}</span>
        </div>
      </div>
    </div>
  </div>
</template>
