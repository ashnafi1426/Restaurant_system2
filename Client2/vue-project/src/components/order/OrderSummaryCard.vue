<script setup lang="ts">
/*
|--------------------------------------------------------------------------
| Props
|--------------------------------------------------------------------------
*/

const props = defineProps<{
  subtotal: number
  tax: number
  discount: number
  total: number
  loading?: boolean
}>()
function formatCurrency(value: number): string {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
  }).format(value)
}
</script>

<template>
  <div class="rounded-xl border border-gray-200 bg-white shadow-sm">
    <!-- ===================================================== -->
    <!-- Header -->
    <!-- ===================================================== -->

    <div class="border-b border-gray-200 px-4 sm:px-5 md:px-6 lg:px-8 py-3 sm:py-4 md:py-5 lg:py-6">
      <h3 class="text-base sm:text-lg md:text-xl lg:text-2xl font-semibold text-gray-900">Order Summary</h3>

      <p class="mt-1 text-xs sm:text-sm md:text-base text-gray-500">Financial summary of the current order.</p>
    </div>

    <!-- ===================================================== -->
    <!-- Loading -->
    <!-- ===================================================== -->

    <div v-if="loading" class="flex items-center justify-center py-8 sm:py-10 md:py-12 lg:py-16">
      <div
        class="h-10 w-10 sm:h-12 sm:w-12 animate-spin rounded-full border-4 border-indigo-600 border-t-transparent"
      />

      <span class="ml-3 sm:ml-4 text-xs sm:text-sm md:text-base text-gray-600">Calculating...</span>
    </div>

    <!-- ===================================================== -->
    <!-- Summary -->
    <!-- ===================================================== -->

    <div v-else class="space-y-3 sm:space-y-4 md:space-y-5 lg:space-y-6 p-4 sm:p-5 md:p-6 lg:p-8">
      <!-- Subtotal -->

      <div class="flex items-center justify-between text-xs sm:text-sm md:text-base">
        <span class="text-gray-600">Subtotal</span>

        <span class="font-semibold text-gray-900">
          {{ formatCurrency(subtotal) }}
        </span>
      </div>

      <!-- Tax -->

      <div class="flex items-center justify-between text-xs sm:text-sm md:text-base">
        <span class="text-gray-600">Tax</span>

        <span class="font-semibold text-gray-900">
          {{ formatCurrency(tax) }}
        </span>
      </div>

      <!-- Discount -->

      <div class="flex items-center justify-between text-xs sm:text-sm md:text-base">
        <span class="text-gray-600">Discount</span>

        <span class="font-semibold text-red-600">- {{ formatCurrency(discount) }}</span>
      </div>

      <hr class="border-gray-200 my-2 sm:my-3 md:my-4 lg:my-5" />

      <!-- Grand Total -->

      <div class="flex items-center justify-between">
        <span class="text-base sm:text-lg md:text-xl lg:text-2xl font-bold text-gray-900">Grand Total</span>

        <span class="text-xl sm:text-2xl md:text-3xl lg:text-4xl font-bold text-indigo-600">
          {{ formatCurrency(total) }}
        </span>
      </div>
    </div>
  </div>
</template>
