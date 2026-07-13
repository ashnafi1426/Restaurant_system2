<script setup lang="ts">
import { computed } from 'vue'

import type { OrderFilters, OrderStatus, PaymentType } from '@/types/order'

/*
|--------------------------------------------------------------------------
| Props
|--------------------------------------------------------------------------
*/

const props = defineProps<{
  filters: OrderFilters
  loading?: boolean
}>()

/*
|--------------------------------------------------------------------------
| Emits
|--------------------------------------------------------------------------
*/

const emit = defineEmits<{
  (e: 'update:filters', value: OrderFilters): void
  (e: 'search'): void
  (e: 'reset'): void
}>()

/*
|--------------------------------------------------------------------------
| Two-way Binding
|--------------------------------------------------------------------------
*/

const localFilters = computed({
  get: () => props.filters,

  set: (value: OrderFilters) => {
    emit('update:filters', value)
  },
})

/*
|--------------------------------------------------------------------------
| Select Options
|--------------------------------------------------------------------------
*/

const statusOptions: {
  label: string
  value: OrderStatus | ''
}[] = [
  {
    label: 'All Status',
    value: '',
  },
  {
    label: 'Pending',
    value: 'pending',
  },
  {
    label: 'Preparing',
    value: 'preparing',
  },
  {
    label: 'Ready',
    value: 'ready',
  },
  {
    label: 'Served',
    value: 'served',
  },
  {
    label: 'Cancelled',
    value: 'cancelled',
  },
]

const paymentOptions: {
  label: string
  value: PaymentType | ''
}[] = [
  {
    label: 'All Payments',
    value: '',
  },
  {
    label: 'Room Charge',
    value: 'room_charge',
  },
  {
    label: 'Cash',
    value: 'cash',
  },
  {
    label: 'Card',
    value: 'card',
  },
]

/*
|--------------------------------------------------------------------------
| Methods
|--------------------------------------------------------------------------
*/

function search(): void {
  emit('search')
}

function reset(): void {
  emit('reset')
}
</script>

<template>
  <div class="rounded-xl border border-gray-200 bg-white p-4 sm:p-5 md:p-6 lg:p-8 shadow-sm">
    <div
      class="grid grid-cols-1 gap-3 sm:gap-4 md:gap-5 lg:gap-6 sm:grid-cols-2 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6"
    >
      <!-- ===================================================== -->
      <!-- Search -->
      <!-- ===================================================== -->

      <div class="lg:col-span-2">
        <label
          class="mb-1.5 sm:mb-2 block text-xs sm:text-sm md:text-base font-medium text-gray-700"
        >
          Search
        </label>

        <input
          v-model="localFilters.search"
          type="text"
          placeholder="Order number, guest..."
          class="w-full rounded-lg border border-gray-300 px-3 sm:px-4 md:px-5 py-2 sm:py-2.5 md:py-3 text-xs sm:text-sm md:text-base focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 min-h-10"
        />
      </div>

      <!-- ===================================================== -->
      <!-- Status -->
      <!-- ===================================================== -->

      <div>
        <label
          class="mb-1.5 sm:mb-2 block text-xs sm:text-sm md:text-base font-medium text-gray-700"
        >
          Status
        </label>

        <select
          v-model="localFilters.status"
          class="w-full rounded-lg border border-gray-300 px-3 sm:px-4 md:px-5 py-2 sm:py-2.5 md:py-3 text-xs sm:text-sm md:text-base bg-white focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 min-h-10"
        >
          <option v-for="status in statusOptions" :key="status.value" :value="status.value">
            {{ status.label }}
          </option>
        </select>
      </div>

      <!-- ===================================================== -->
      <!-- Payment -->
      <!-- ===================================================== -->

      <div>
        <label
          class="mb-1.5 sm:mb-2 block text-xs sm:text-sm md:text-base font-medium text-gray-700"
        >
          Payment
        </label>

        <select
          v-model="localFilters.payment_type"
          class="w-full rounded-lg border border-gray-300 px-3 sm:px-4 md:px-5 py-2 sm:py-2.5 md:py-3 text-xs sm:text-sm md:text-base bg-white focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 min-h-10"
        >
          <option v-for="payment in paymentOptions" :key="payment.value" :value="payment.value">
            {{ payment.label }}
          </option>
        </select>
      </div>

      <!-- ===================================================== -->
      <!-- Date From -->
      <!-- ===================================================== -->

      <div>
        <label
          class="mb-1.5 sm:mb-2 block text-xs sm:text-sm md:text-base font-medium text-gray-700"
        >
          From
        </label>

        <input
          v-model="localFilters.date_from"
          type="date"
          class="w-full rounded-lg border border-gray-300 px-3 sm:px-4 md:px-5 py-2 sm:py-2.5 md:py-3 text-xs sm:text-sm md:text-base focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 min-h-10"
        />
      </div>

      <!-- ===================================================== -->
      <!-- Date To -->
      <!-- ===================================================== -->

      <div>
        <label
          class="mb-1.5 sm:mb-2 block text-xs sm:text-sm md:text-base font-medium text-gray-700"
        >
          To
        </label>

        <input
          v-model="localFilters.date_to"
          type="date"
          class="w-full rounded-lg border border-gray-300 px-3 sm:px-4 md:px-5 py-2 sm:py-2.5 md:py-3 text-xs sm:text-sm md:text-base focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 min-h-10"
        />
      </div>
    </div>

    <!-- ======================================================= -->
    <!-- Actions -->
    <!-- ======================================================= -->

    <div
      class="mt-4 sm:mt-5 md:mt-6 lg:mt-8 flex flex-col-reverse sm:flex-row gap-2 sm:gap-3 md:gap-4 justify-end"
    >
      <button
        type="button"
        :disabled="loading"
        class="rounded-lg border border-gray-300 px-4 sm:px-5 md:px-6 py-2 sm:py-2.5 md:py-3 text-xs sm:text-sm md:text-base font-medium text-gray-700 transition hover:bg-gray-100 disabled:cursor-not-allowed disabled:opacity-50 min-h-10"
        @click="reset"
      >
        Reset
      </button>

      <button
        type="button"
        :disabled="loading"
        class="rounded-lg bg-indigo-600 px-4 sm:px-5 md:px-6 py-2 sm:py-2.5 md:py-3 text-xs sm:text-sm md:text-base font-medium text-white transition hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-50 min-h-10"
        @click="search"
      >
        Search
      </button>
    </div>
  </div>
</template>
