<script setup lang="ts">
import { ref, watch } from 'vue'
import type { OrderFilters, OrderStatus } from '@/types/order'
import {
  Search,
  Filter,
  X,
  RefreshCw,
  Maximize2,
  Minimize2,
  Plus,
  RotateCcw,
} from 'lucide-vue-next'

const props = defineProps<{
  filters: OrderFilters
  loading?: boolean
  isFullscreen?: boolean
}>()

const emit = defineEmits<{
  (e: 'update:filters', value: OrderFilters): void
  (e: 'search'): void
  (e: 'reset'): void
  (e: 'refresh'): void
  (e: 'create'): void
  (e: 'toggle-fullscreen'): void
  (e: 'toggle-columns'): void
}>()

// Filter visibility state
const isFilterOpen = ref(false)

// Reactive local filters initialized from props
const localFilters = ref<OrderFilters>({
  search: props.filters.search || '',
  status: props.filters.status || '',
  payment_type: props.filters.payment_type || '',
  order_type: props.filters.order_type || '',
  date_from: props.filters.date_from || '',
  date_to: props.filters.date_to || '',
  page: props.filters.page || 1,
  per_page: props.filters.per_page || 15,
})

// Keep local state in sync when parent filters change
watch(
  () => props.filters,
  (newVal) => {
    localFilters.value = {
      search: newVal.search || '',
      status: newVal.status || '',
      payment_type: newVal.payment_type || '',
      order_type: newVal.order_type || '',
      date_from: newVal.date_from || '',
      date_to: newVal.date_to || '',
      page: newVal.page || 1,
      per_page: newVal.per_page || 15,
    }
  },
  { deep: true },
)

const statusOptions: {
  label: string
  value: OrderStatus | ''
}[] = [
  { label: 'All Statuses', value: '' },
  { label: 'Pending', value: 'pending' },
  { label: 'Preparing', value: 'preparing' },
  { label: 'Ready', value: 'ready' },
  { label: 'Served', value: 'served' },
  { label: 'Cancelled', value: 'cancelled' },
]

const paymentOptions: {
  label: string
  value: string
}[] = [
  { label: 'All Payments', value: '' },
  { label: 'Room Charge', value: 'room_charge' },
  { label: 'Cash', value: 'cash' },
  { label: 'Card / Online', value: 'card' },
  { label: 'Chapa', value: 'chapa' },
]

const orderTypeOptions: {
  label: string
  value: string
}[] = [
  { label: 'All Order Types', value: '' },
  { label: 'Room Service', value: 'room_service' },
  { label: 'Walk-in / Table Dining', value: 'walk_in' },
]

let debounceTimer: ReturnType<typeof setTimeout> | null = null

function onSearchInput(): void {
  if (debounceTimer) clearTimeout(debounceTimer)
  debounceTimer = setTimeout(() => {
    emit('update:filters', { ...localFilters.value, page: 1 })
    emit('search')
  }, 350)
}

function onFilterChange(): void {
  if (debounceTimer) clearTimeout(debounceTimer)
  emit('update:filters', { ...localFilters.value, page: 1 })
  emit('search')
}

function reset(): void {
  if (debounceTimer) clearTimeout(debounceTimer)
  localFilters.value = {
    search: '',
    status: '',
    payment_type: '',
    order_type: '',
    date_from: '',
    date_to: '',
    page: 1,
    per_page: props.filters.per_page || 15,
  }
  emit('update:filters', { ...localFilters.value })
  emit('reset')
}

function toggleFilter(): void {
  isFilterOpen.value = !isFilterOpen.value
}
</script>

<template>
  <div class="space-y-3 font-sans w-full">
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
            v-model="localFilters.search"
            @input="onSearchInput"
            @keyup.enter="onFilterChange"
            type="text"
            placeholder="Search orders, guest, room/table..."
            class="w-full rounded-xl border border-slate-200 dark:border-[#1e3455] bg-slate-50/80 dark:bg-[#13233c] text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 pl-10 pr-4 py-2.5 text-xs sm:text-sm focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 dark:focus:border-blue-400 transition outline-none"
          />
        </div>

        <!-- Filter Toggle Button -->
        <button
          type="button"
          @click="toggleFilter"
          class="inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-xs sm:text-sm font-semibold transition cursor-pointer flex-shrink-0"
          :class="[
            isFilterOpen
              ? 'bg-blue-600/10 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 border border-blue-500/40'
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
          @click="emit('refresh')"
          :disabled="loading"
          title="Refresh orders"
          class="inline-flex items-center justify-center h-9 w-9 sm:h-10 sm:w-10 rounded-xl border border-slate-200 dark:border-[#1e3455] bg-white dark:bg-[#13233c]/80 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-[#1c3356] hover:text-slate-900 dark:hover:text-white transition disabled:opacity-50 cursor-pointer"
        >
          <RefreshCw class="w-4 h-4" :class="{ 'animate-spin': loading }" />
        </button>

        <!-- Fullscreen Toggle -->
        <button
          type="button"
          @click="emit('toggle-fullscreen')"
          title="Toggle fullscreen table"
          class="inline-flex items-center justify-center h-9 w-9 sm:h-10 sm:w-10 rounded-xl border border-slate-200 dark:border-[#1e3455] bg-white dark:bg-[#13233c]/80 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-[#1c3356] hover:text-slate-900 dark:hover:text-white transition cursor-pointer"
        >
          <component :is="isFullscreen ? Minimize2 : Maximize2" class="w-4 h-4" />
        </button>

        <!-- Create New Primary Button -->
        <button
          type="button"
          @click="emit('create')"
          class="inline-flex items-center gap-1.5 rounded-xl bg-blue-600 hover:bg-blue-700 dark:bg-[#0066FF] dark:hover:bg-[#0055DD] px-3.5 sm:px-4 py-2.5 text-xs sm:text-sm font-bold text-white shadow-sm shadow-blue-600/30 transition active:scale-98 cursor-pointer flex-shrink-0"
        >
          <Plus class="w-4 h-4" />
          <span>Create New</span>
        </button>
      </div>
    </div>

    <!-- Expandable Filter Panel (Slide down when Filter is active) -->
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
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3.5 sm:gap-4">
          <!-- Status -->
          <div>
            <label class="mb-1.5 block text-xs font-semibold text-slate-600 dark:text-slate-300">
              Status
            </label>
            <select
              v-model="localFilters.status"
              @change="onFilterChange"
              class="w-full rounded-xl border border-slate-200 dark:border-[#1e3455] bg-slate-50/80 dark:bg-[#13233c] text-slate-900 dark:text-white px-3.5 py-2 text-xs sm:text-sm focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 dark:focus:border-blue-400 transition cursor-pointer font-medium outline-none"
            >
              <option v-for="status in statusOptions" :key="status.value" :value="status.value">
                {{ status.label }}
              </option>
            </select>
          </div>

          <!-- Payment -->
          <div>
            <label class="mb-1.5 block text-xs font-semibold text-slate-600 dark:text-slate-300">
              Payment
            </label>
            <select
              v-model="localFilters.payment_type"
              @change="onFilterChange"
              class="w-full rounded-xl border border-slate-200 dark:border-[#1e3455] bg-slate-50/80 dark:bg-[#13233c] text-slate-900 dark:text-white px-3.5 py-2 text-xs sm:text-sm focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 dark:focus:border-blue-400 transition cursor-pointer font-medium outline-none"
            >
              <option v-for="payment in paymentOptions" :key="payment.value" :value="payment.value">
                {{ payment.label }}
              </option>
            </select>
          </div>

          <!-- Order Type -->
          <div>
            <label class="mb-1.5 block text-xs font-semibold text-slate-600 dark:text-slate-300">
              Order Type
            </label>
            <select
              v-model="localFilters.order_type"
              @change="onFilterChange"
              class="w-full rounded-xl border border-slate-200 dark:border-[#1e3455] bg-slate-50/80 dark:bg-[#13233c] text-slate-900 dark:text-white px-3.5 py-2 text-xs sm:text-sm focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 dark:focus:border-blue-400 transition cursor-pointer font-medium outline-none"
            >
              <option v-for="ot in orderTypeOptions" :key="ot.value" :value="ot.value">
                {{ ot.label }}
              </option>
            </select>
          </div>

          <!-- From Date -->
          <div>
            <label class="mb-1.5 block text-xs font-semibold text-slate-600 dark:text-slate-300">
              From Date
            </label>
            <input
              v-model="localFilters.date_from"
              @change="onFilterChange"
              type="date"
              class="w-full rounded-xl border border-slate-200 dark:border-[#1e3455] bg-slate-50/80 dark:bg-[#13233c] text-slate-900 dark:text-white px-3 py-1.5 text-xs sm:text-sm focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 dark:focus:border-blue-400 transition outline-none"
            />
          </div>

          <!-- To Date -->
          <div>
            <label class="mb-1.5 block text-xs font-semibold text-slate-600 dark:text-slate-300">
              To Date
            </label>
            <input
              v-model="localFilters.date_to"
              @change="onFilterChange"
              type="date"
              class="w-full rounded-xl border border-slate-200 dark:border-[#1e3455] bg-slate-50/80 dark:bg-[#13233c] text-slate-900 dark:text-white px-3 py-1.5 text-xs sm:text-sm focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 dark:focus:border-blue-400 transition outline-none"
            />
          </div>
        </div>

        <!-- Filter Actions / Reset -->
        <div class="flex items-center justify-between pt-1 border-t border-slate-100 dark:border-slate-800/60">
          <span class="text-[11px] text-slate-500 dark:text-slate-400 font-medium">
            Filters update list results automatically
          </span>
          <button
            type="button"
            @click="reset"
            class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 dark:border-[#1e3455] bg-slate-100/70 dark:bg-[#13233c] px-3.5 py-1.5 text-xs font-semibold text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-[#1c3356] transition cursor-pointer"
          >
            <RotateCcw class="w-3.5 h-3.5" />
            <span>Reset Filters</span>
          </button>
        </div>
      </div>
    </Transition>
  </div>
</template>

