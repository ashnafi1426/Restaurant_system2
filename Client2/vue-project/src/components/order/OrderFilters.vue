<script setup lang="ts">
import { computed } from 'vue'

import type {
  OrderFilters,
  OrderStatus,
  PaymentType,
} from '@/types/order'

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
  <div
    class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm"
  >
    <div
      class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-6"
    >
      <!-- ===================================================== -->
      <!-- Search -->
      <!-- ===================================================== -->

      <div class="xl:col-span-2">
        <label
          class="mb-2 block text-sm font-medium text-gray-700"
        >
          Search
        </label>

        <input
          v-model="localFilters.search"
          type="text"
          placeholder="Order number, guest..."
          class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-indigo-500 focus:outline-none"
        >
      </div>

      <!-- ===================================================== -->
      <!-- Status -->
      <!-- ===================================================== -->

      <div>
        <label
          class="mb-2 block text-sm font-medium text-gray-700"
        >
          Status
        </label>

        <select
          v-model="localFilters.status"
          class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-indigo-500 focus:outline-none"
        >
          <option
            v-for="status in statusOptions"
            :key="status.value"
            :value="status.value"
          >
            {{ status.label }}
          </option>
        </select>
      </div>

      <!-- ===================================================== -->
      <!-- Payment -->
      <!-- ===================================================== -->

      <div>
        <label
          class="mb-2 block text-sm font-medium text-gray-700"
        >
          Payment
        </label>

        <select
          v-model="localFilters.payment_type"
          class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-indigo-500 focus:outline-none"
        >
          <option
            v-for="payment in paymentOptions"
            :key="payment.value"
            :value="payment.value"
          >
            {{ payment.label }}
          </option>
        </select>
      </div>

      <!-- ===================================================== -->
      <!-- Date From -->
      <!-- ===================================================== -->

      <div>
        <label
          class="mb-2 block text-sm font-medium text-gray-700"
        >
          From
        </label>

        <input
          v-model="localFilters.date_from"
          type="date"
          class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-indigo-500 focus:outline-none"
        >
      </div>

      <!-- ===================================================== -->
      <!-- Date To -->
      <!-- ===================================================== -->

      <div>
        <label
          class="mb-2 block text-sm font-medium text-gray-700"
        >
          To
        </label>

        <input
          v-model="localFilters.date_to"
          type="date"
          class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-indigo-500 focus:outline-none"
        >
      </div>
    </div>

    <!-- ======================================================= -->
    <!-- Actions -->
    <!-- ======================================================= -->

    <div
      class="mt-6 flex flex-wrap justify-end gap-3"
    >
      <button
        type="button"
        :disabled="loading"
        class="rounded-lg border border-gray-300 px-5 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-100 disabled:cursor-not-allowed disabled:opacity-50"
        @click="reset"
      >
        Reset
      </button>

      <button
        type="button"
        :disabled="loading"
        class="rounded-lg bg-indigo-600 px-5 py-2 text-sm font-medium text-white transition hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-50"
        @click="search"
      >
        Search
      </button>
    </div>
  </div>
</template>