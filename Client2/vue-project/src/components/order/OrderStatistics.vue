<script setup lang="ts">
import type { OrderStatistics } from '@/types/order'

/*
|--------------------------------------------------------------------------
| Props
|--------------------------------------------------------------------------
*/

defineProps<{
  statistics: OrderStatistics

  loading: boolean
}>()

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

function formatCurrency(value: number): string {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
  }).format(value)
}
</script>

<template>
  <div class="grid grid-cols-1 gap-2 sm:gap-3 md:gap-4 lg:gap-6 sm:grid-cols-2 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-3 2xl:grid-cols-6 px-4 sm:px-6 md:px-8 lg:px-10">
    <!-- ======================================================= -->
    <!-- Total Orders -->
    <!-- ======================================================= -->

    <div class="rounded-xl border border-gray-200 bg-white p-4 sm:p-5 md:p-6 lg:p-8 shadow-sm">
      <p class="text-xs sm:text-sm md:text-base text-gray-500">Total Orders</p>

      <div v-if="loading" class="mt-4 h-8 sm:h-9 md:h-10 animate-pulse rounded bg-gray-200" />

      <h2 v-else class="mt-4 text-2xl sm:text-3xl md:text-4xl lg:text-5xl font-bold text-gray-900">
        {{ statistics.total_orders }}
      </h2>
    </div>

    <!-- ======================================================= -->
    <!-- Pending -->
    <!-- ======================================================= -->

    <div class="rounded-xl border border-yellow-200 bg-yellow-50 p-4 sm:p-5 md:p-6 lg:p-8">
      <p class="text-xs sm:text-sm md:text-base text-yellow-700">Pending</p>

      <div v-if="loading" class="mt-4 h-8 sm:h-9 md:h-10 animate-pulse rounded bg-yellow-200" />

      <h2 v-else class="mt-4 text-2xl sm:text-3xl md:text-4xl lg:text-5xl font-bold text-yellow-700">
        {{ statistics.pending_orders }}
      </h2>
    </div>

    <!-- ======================================================= -->
    <!-- Preparing -->
    <!-- ======================================================= -->

    <div class="rounded-xl border border-blue-200 bg-blue-50 p-4 sm:p-5 md:p-6 lg:p-8">
      <p class="text-xs sm:text-sm md:text-base text-blue-700">Preparing</p>

      <div v-if="loading" class="mt-4 h-8 sm:h-9 md:h-10 animate-pulse rounded bg-blue-200" />

      <h2 v-else class="mt-4 text-2xl sm:text-3xl md:text-4xl lg:text-5xl font-bold text-blue-700">
        {{ statistics.preparing_orders }}
      </h2>
    </div>

    <!-- ======================================================= -->
    <!-- Ready -->
    <!-- ======================================================= -->

    <div class="rounded-xl border border-green-200 bg-green-50 p-4 sm:p-5 md:p-6 lg:p-8">
      <p class="text-xs sm:text-sm md:text-base text-green-700">Ready</p>

      <div v-if="loading" class="mt-4 h-8 sm:h-9 md:h-10 animate-pulse rounded bg-green-200" />

      <h2 v-else class="mt-4 text-2xl sm:text-3xl md:text-4xl lg:text-5xl font-bold text-green-700">
        {{ statistics.ready_orders }}
      </h2>
    </div>

    <!-- ======================================================= -->
    <!-- Served -->
    <!-- ======================================================= -->

    <div class="rounded-xl border border-indigo-200 bg-indigo-50 p-4 sm:p-5 md:p-6 lg:p-8">
      <p class="text-xs sm:text-sm md:text-base text-indigo-700">Served</p>

      <div v-if="loading" class="mt-4 h-8 sm:h-9 md:h-10 animate-pulse rounded bg-indigo-200" />

      <h2 v-else class="mt-4 text-2xl sm:text-3xl md:text-4xl lg:text-5xl font-bold text-indigo-700">
        {{ statistics.served_orders }}
      </h2>
    </div>

    <!-- ======================================================= -->
    <!-- Revenue -->
    <!-- ======================================================= -->

    <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 sm:p-5 md:p-6 lg:p-8">
      <p class="text-xs sm:text-sm md:text-base text-emerald-700">Revenue</p>

      <div v-if="loading" class="mt-4 h-8 sm:h-9 md:h-10 animate-pulse rounded bg-emerald-200" />

      <h2 v-else class="mt-4 text-xl sm:text-2xl md:text-3xl lg:text-4xl font-bold text-emerald-700">
        {{ formatCurrency(statistics.total_revenue) }}
      </h2>
    </div>
  </div>
</template>
