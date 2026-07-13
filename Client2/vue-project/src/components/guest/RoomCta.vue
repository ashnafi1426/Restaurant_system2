<template>
  <div
    class="bg-gradient-to-r from-amber-500 via-amber-400 to-orange-500 rounded-xl shadow-2xl overflow-hidden p-8 sm:p-12 md:p-16"
  >
    <!-- Content Container -->
    <div class="max-w-4xl mx-auto text-center">
      <!-- Main Heading -->
      <h2 class="text-3xl sm:text-4xl md:text-5xl font-bold text-white mb-4 sm:mb-6 leading-tight">
        Ready to Book Your Perfect Room?
      </h2>

      <!-- Subtitle -->
      <p class="text-sm sm:text-base md:text-lg text-amber-50 mb-8 sm:mb-10 max-w-2xl mx-auto">
        Experience luxury and comfort at our premium hotel. Reserve your room today and enjoy
        special discounts for early bookings. Don't miss out on our exclusive offers!
      </p>

      <!-- CTA Buttons -->
      <div class="flex flex-col sm:flex-row gap-4 sm:gap-6 justify-center">
        <!-- Primary CTA -->
        <a
          href="/booking"
          class="px-8 sm:px-10 py-3 sm:py-4 bg-white text-amber-600 font-bold rounded-lg hover:bg-amber-50 transition-all duration-300 transform hover:scale-105 shadow-lg text-base sm:text-lg"
        >
          ✨ Book Now
        </a>

        <!-- Secondary CTA -->
        <a
          href="/contact"
          class="px-8 sm:px-10 py-3 sm:py-4 bg-amber-700 hover:bg-amber-800 text-white font-bold rounded-lg transition-all duration-300 transform hover:scale-105 shadow-lg text-base sm:text-lg"
        >
          📞 Contact Us
        </a>
      </div>

      <!-- Features Row -->
      <div class="mt-10 sm:mt-12 md:mt-16 grid grid-cols-1 sm:grid-cols-3 gap-4 sm:gap-6">
        <div class="text-white">
          <div class="text-2xl sm:text-3xl mb-2">🎁</div>
          <p class="font-semibold text-sm sm:text-base">Special Discounts</p>
          <p class="text-xs sm:text-sm text-amber-50">Up to 30% off for advance bookings</p>
        </div>

        <div class="text-white">
          <div class="text-2xl sm:text-3xl mb-2">✅</div>
          <p class="font-semibold text-sm sm:text-base">Free Cancellation</p>
          <p class="text-xs sm:text-sm text-amber-50">Cancel up to 48 hours before arrival</p>
        </div>

        <div class="text-white">
          <div class="text-2xl sm:text-3xl mb-2">🏆</div>
          <p class="font-semibold text-sm sm:text-base">Best Price Guaranteed</p>
          <p class="text-xs sm:text-sm text-amber-50">Price match guarantee on all bookings</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
// Room CTA component - call to action for booking
</script>

<style scoped>
/* Responsive CTA section */
</style>
