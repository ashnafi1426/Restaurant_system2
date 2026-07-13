<template>
  <div
    class="relative w-full bg-gradient-to-b from-slate-900 via-slate-800 to-slate-700 overflow-hidden"
  >
    <!-- Background Image with Overlay -->
    <div class="absolute inset-0 w-full h-full">
      <img
        src="/images/hero/hero.jpg"
        alt="Hotel Rooms"
        class="w-full h-full object-cover opacity-40"
      />
      <!-- Multi-layer Overlay for Better Text Readability -->
      <div class="absolute inset-0 bg-gradient-to-r from-black/70 via-black/50 to-black/70"></div>
      <div
        class="absolute inset-0 bg-gradient-to-b from-black/40 via-transparent to-black/60"
      ></div>
    </div>

    <!-- Content Container -->
    <div class="relative w-full">
      <!-- Main Hero Section -->
      <div
        class="min-h-screen sm:min-h-[500px] md:min-h-[600px] lg:min-h-[700px] flex items-center justify-center px-4 sm:px-6 md:px-8 lg:px-10 py-16 sm:py-24 md:py-32"
      >
        <div class="text-center max-w-5xl mx-auto w-full">
          <!-- Decorative Badge -->
          <div class="mb-4 sm:mb-6 md:mb-8 inline-block">
            <span
              class="inline-block px-3 sm:px-4 md:px-6 py-1.5 sm:py-2 md:py-2.5 bg-amber-500/20 border border-amber-400/40 text-amber-300 text-xs sm:text-sm md:text-base font-semibold rounded-full backdrop-blur-sm"
            >
              ⭐ Premium Accommodations
            </span>
          </div>

          <!-- Main Heading with Animation -->
          <h1
            class="text-3xl sm:text-4xl md:text-5xl lg:text-6xl xl:text-7xl font-bold text-white mb-4 sm:mb-6 md:mb-8 leading-tight drop-shadow-2xl"
          >
            Discover Our
            <span class="block text-amber-400 mt-2 sm:mt-3 md:mt-4">Luxurious Room Collection</span>
          </h1>

          <!-- Subheading -->
          <p
            class="text-sm sm:text-base md:text-lg lg:text-xl text-slate-200 max-w-3xl mx-auto leading-relaxed drop-shadow-lg mb-6 sm:mb-8 md:mb-10"
          >
            Experience unparalleled comfort and elegance in our exquisitely designed rooms. Each
            accommodation is crafted with premium amenities, modern furnishings, and stunning views
            for an unforgettable stay.
          </p>

          <!-- Features Highlight -->
          <div
            class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3 sm:gap-4 md:gap-6 max-w-3xl mx-auto mb-8 sm:mb-10 md:mb-12"
          >
            <div class="flex flex-col items-center">
              <div class="w-10 sm:w-12 md:w-14 h-10 sm:h-12 md:h-14 mb-1 sm:mb-2 text-amber-400">
                <Bed class="w-full h-full" />
              </div>
              <span class="text-xs sm:text-sm text-slate-300 font-medium">Luxury Beds</span>
            </div>
            <div class="flex flex-col items-center">
              <div class="w-10 sm:w-12 md:w-14 h-10 sm:h-12 md:h-14 mb-1 sm:mb-2 text-amber-400">
                <Droplet class="w-full h-full" />
              </div>
              <span class="text-xs sm:text-sm text-slate-300 font-medium">Modern Bath</span>
            </div>
            <div class="flex flex-col items-center">
              <div class="w-10 sm:w-12 md:w-14 h-10 sm:h-12 md:h-14 mb-1 sm:mb-2 text-amber-400">
                <Tv class="w-full h-full" />
              </div>
              <span class="text-xs sm:text-sm text-slate-300 font-medium">Smart TV</span>
            </div>
            <div class="flex flex-col items-center">
              <div class="w-10 sm:w-12 md:w-14 h-10 sm:h-12 md:h-14 mb-1 sm:mb-2 text-amber-400">
                <Wifi class="w-full h-full" />
              </div>
              <span class="text-xs sm:text-sm text-slate-300 font-medium">Fast WiFi</span>
            </div>
          </div>

          <!-- CTA Buttons -->
          <div
            class="flex flex-col sm:flex-row gap-3 sm:gap-4 md:gap-6 justify-center items-center"
          >
            <button
              @click="exploreRooms"
              class="w-full sm:w-auto px-8 sm:px-10 md:px-12 py-3 sm:py-3.5 md:py-4 bg-amber-500 hover:bg-amber-600 text-white text-sm sm:text-base md:text-lg font-bold rounded-lg md:rounded-xl transition-all duration-300 transform hover:scale-105 shadow-xl hover:shadow-2xl cursor-pointer"
            >
              ✨ Explore Rooms
            </button>
          </div>

          <!-- Scroll Indicator -->
          <div class="mt-12 sm:mt-16 md:mt-20 animate-bounce">
            <div class="text-slate-400 text-xs sm:text-sm mb-2">Scroll to see rooms</div>
            <div class="text-2xl">↓</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { Bed, Droplet, Tv, Wifi } from 'lucide-vue-next'

// Scroll to rooms section
const exploreRooms = () => {
  const roomsSection = document.getElementById('rooms-section')
  if (roomsSection) {
    roomsSection.scrollIntoView({ behavior: 'smooth' })
  }
}
</script>

<style scoped>
/* Responsive hero section */
</style>
