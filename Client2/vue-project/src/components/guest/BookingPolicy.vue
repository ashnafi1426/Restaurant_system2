<template>
  <div class="rounded-lg sm:rounded-xl border border-slate-200 bg-white p-4 sm:p-6 md:p-8">
    <!-- Title -->
    <h3 class="text-lg sm:text-xl font-bold text-slate-900 mb-4 sm:mb-6">Booking Policy</h3>

    <!-- Policies List -->
    <div class="space-y-3 sm:space-y-4">
      <div class="flex items-start gap-3 sm:gap-4">
        <div class="flex-shrink-0 mt-0.5 sm:mt-1">
          <svg class="h-5 w-5 sm:h-6 sm:w-6 text-green-500" fill="currentColor" viewBox="0 0 20 20">
            <path
              fill-rule="evenodd"
              d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
              clip-rule="evenodd"
            />
          </svg>
        </div>
        <div>
          <p class="font-semibold text-sm sm:text-base text-slate-900">Free Cancellation</p>
          <p class="text-xs sm:text-sm text-slate-600 mt-1">
            Cancel for free up to 48 hours before check-in
          </p>
        </div>
      </div>

      <div class="flex items-start gap-3 sm:gap-4">
        <div class="flex-shrink-0 mt-0.5 sm:mt-1">
          <svg class="h-5 w-5 sm:h-6 sm:w-6 text-green-500" fill="currentColor" viewBox="0 0 20 20">
            <path
              fill-rule="evenodd"
              d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
              clip-rule="evenodd"
            />
          </svg>
        </div>
        <div>
          <p class="font-semibold text-sm sm:text-base text-slate-900">Best Price Guarantee</p>
          <p class="text-xs sm:text-sm text-slate-600 mt-1">
            Found a better price? We'll match it!
          </p>
        </div>
      </div>

      <div class="flex items-start gap-3 sm:gap-4">
        <div class="flex-shrink-0 mt-0.5 sm:mt-1">
          <svg class="h-5 w-5 sm:h-6 sm:w-6 text-green-500" fill="currentColor" viewBox="0 0 20 20">
            <path
              fill-rule="evenodd"
              d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
              clip-rule="evenodd"
            />
          </svg>
        </div>
        <div>
          <p class="font-semibold text-sm sm:text-base text-slate-900">Secure Payment</p>
          <p class="text-xs sm:text-sm text-slate-600 mt-1">
            Your payment is protected and encrypted
          </p>
        </div>
      </div>

      <div class="flex items-start gap-3 sm:gap-4">
        <div class="flex-shrink-0 mt-0.5 sm:mt-1">
          <svg class="h-5 w-5 sm:h-6 sm:w-6 text-green-500" fill="currentColor" viewBox="0 0 20 20">
            <path
              fill-rule="evenodd"
              d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
              clip-rule="evenodd"
            />
          </svg>
        </div>
        <div>
          <p class="font-semibold text-sm sm:text-base text-slate-900">24/7 Support</p>
          <p class="text-xs sm:text-sm text-slate-600 mt-1">
            Our support team is always here to help
          </p>
        </div>
      </div>
    </div>

    <!-- Terms Checkbox -->
    <div class="mt-6 sm:mt-8 flex items-start gap-3 sm:gap-4">
      <input type="checkbox" class="mt-1 rounded w-4 h-4 sm:w-5 sm:h-5" />
      <label class="text-xs sm:text-sm text-slate-600">
        I agree to the
        <a href="#" class="font-semibold text-amber-600 hover:text-amber-700"
          >terms and conditions</a
        >
        and
        <a href="#" class="font-semibold text-amber-600 hover:text-amber-700">privacy policy</a>
      </label>
    </div>
  </div>
</template>

<script setup lang="ts">
// Booking policy component
</script>

<style scoped>
/* Responsive booking policy */
</style>
