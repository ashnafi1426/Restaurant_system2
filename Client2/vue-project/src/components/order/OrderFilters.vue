<script setup lang="ts">
import { computed } from 'vue'
import type { OrderFilters, OrderStatus, PaymentType } from '@/types/order'
import { Search, RotateCcw, Filter } from 'lucide-vue-next'

const props = defineProps<{
  filters: OrderFilters
  loading?: boolean
}>()

const emit = defineEmits<{
  (e: 'update:filters', value: OrderFilters): void
  (e: 'search'): void
  (e: 'reset'): void
}>()

const localFilters = computed({
  get: () => props.filters,
  set: (value: OrderFilters) => {
    emit('update:filters', value)
  },
})

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
  value: PaymentType | ''
}[] = [
  { label: 'All Payments', value: '' },
  { label: 'Room Charge', value: 'room_charge' },
  { label: 'Cash', value: 'cash' },
  { label: 'Card', value: 'card' },
]

function search(): void {
  emit('search')
}

function reset(): void {
  emit('reset')
}
</script>

<template>
  <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-4 sm:p-6 shadow-xs font-sans">
    <div class="flex items-center gap-2 mb-4">
      <div class="p-1.5 rounded-lg bg-blue-500/10 text-blue-600 dark:text-blue-400">
        <Filter class="w-4 h-4" />
      </div>
      <h3 class="text-sm font-black text-slate-900 dark:text-white">Filter & Search Orders</h3>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-6">
      <!-- Search Input -->
      <div class="lg:col-span-2">
        <label class="mb-1.5 block text-xs font-bold text-slate-700 dark:text-slate-300">
          Search
        </label>
        <div class="relative">
          <Search class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
          <input
            v-model="localFilters.search"
            @input="search"
            @keyup.enter="search"
            type="text"
            placeholder="Order number, guest name..."
            class="w-full rounded-xl border border-slate-300 dark:border-slate-800 bg-white dark:bg-slate-950 text-slate-900 dark:text-white pl-9 pr-3.5 py-2.5 text-xs focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
          />
        </div>
      </div>

      <!-- Status Dropdown -->
      <div>
        <label class="mb-1.5 block text-xs font-bold text-slate-700 dark:text-slate-300">
          Status
        </label>
        <select
          v-model="localFilters.status"
          @change="search"
          class="w-full rounded-xl border border-slate-300 dark:border-slate-800 bg-white dark:bg-slate-950 text-slate-900 dark:text-white px-3.5 py-2.5 text-xs focus:ring-2 focus:ring-blue-500 focus:border-transparent transition cursor-pointer font-semibold"
        >
          <option v-for="status in statusOptions" :key="status.value" :value="status.value">
            {{ status.label }}
          </option>
        </select>
      </div>

      <!-- Payment Dropdown -->
      <div>
        <label class="mb-1.5 block text-xs font-bold text-slate-700 dark:text-slate-300">
          Payment
        </label>
        <select
          v-model="localFilters.payment_type"
          @change="search"
          class="w-full rounded-xl border border-slate-300 dark:border-slate-800 bg-white dark:bg-slate-950 text-slate-900 dark:text-white px-3.5 py-2.5 text-xs focus:ring-2 focus:ring-blue-500 focus:border-transparent transition cursor-pointer font-semibold"
        >
          <option v-for="payment in paymentOptions" :key="payment.value" :value="payment.value">
            {{ payment.label }}
          </option>
        </select>
      </div>

      <!-- Date From -->
      <div>
        <label class="mb-1.5 block text-xs font-bold text-slate-700 dark:text-slate-300">
          From
        </label>
        <input
          v-model="localFilters.date_from"
          @change="search"
          type="date"
          class="w-full rounded-xl border border-slate-300 dark:border-slate-800 bg-white dark:bg-slate-950 text-slate-900 dark:text-white px-3 py-2 text-xs focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
        />
      </div>

      <!-- Date To -->
      <div>
        <label class="mb-1.5 block text-xs font-bold text-slate-700 dark:text-slate-300">
          To
        </label>
        <input
          v-model="localFilters.date_to"
          @change="search"
          type="date"
          class="w-full rounded-xl border border-slate-300 dark:border-slate-800 bg-white dark:bg-slate-950 text-slate-900 dark:text-white px-3 py-2 text-xs focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
        />
      </div>
    </div>

    <!-- Action Buttons -->
    <div class="mt-5 flex items-center justify-end gap-3">
      <button
        type="button"
        :disabled="loading"
        class="inline-flex items-center gap-1.5 rounded-xl border border-slate-300 dark:border-slate-800 px-4 py-2 text-xs font-bold text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition disabled:opacity-50 cursor-pointer"
        @click="reset"
      >
        <RotateCcw class="w-3.5 h-3.5" />
        <span>Reset</span>
      </button>

      <button
        type="button"
        :disabled="loading"
        class="inline-flex items-center gap-1.5 rounded-xl bg-blue-600 hover:bg-blue-700 px-5 py-2 text-xs font-bold text-white shadow-md shadow-blue-600/20 transition disabled:opacity-50 cursor-pointer"
        @click="search"
      >
        <Search class="w-3.5 h-3.5" />
        <span>Search</span>
      </button>
    </div>
  </div>
</template>
