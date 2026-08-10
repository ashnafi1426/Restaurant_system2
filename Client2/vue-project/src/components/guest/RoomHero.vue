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
          <!-- Main Heading with Animation -->
          <h1
            class="text-xl sm:text-2xl md:text-3xl lg:text-4xl xl:text-5xl font-medium text-white mb-2 sm:mb-3 md:mb-4 leading-tight drop-shadow-2xl"
          >
            Discover Our
            <span class="block text-amber-400 mt-1 sm:mt-1.5 md:mt-2 font-normal">Luxurious Room Collection</span>
          </h1>

          <!-- Subheading -->
          <p
            class="text-[11px] sm:text-xs md:text-sm lg:text-base text-slate-200 max-w-3xl mx-auto leading-relaxed drop-shadow-lg mb-4 sm:mb-5 md:mb-6 font-light"
          >
            Experience unparalleled comfort and elegance in our exquisitely designed rooms. Each
            accommodation is crafted with premium amenities, modern furnishings, and stunning views
            for an unforgettable stay.
          </p>

          <!-- Features Highlight -->
          <div
            class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-2 sm:gap-3 md:gap-4 max-w-3xl mx-auto mb-5 sm:mb-6 md:mb-8"
          >
            <div class="flex flex-col items-center">
              <div class="w-8 sm:w-9 md:w-10 h-8 sm:h-9 md:h-10 mb-1 text-amber-400">
                <Bed class="w-full h-full" />
              </div>
              <span class="text-[10px] sm:text-xs text-slate-300 font-light">Luxury Beds</span>
            </div>
            <div class="flex flex-col items-center">
              <div class="w-8 sm:w-9 md:w-10 h-8 sm:h-9 md:h-10 mb-1 text-amber-400">
                <Droplet class="w-full h-full" />
              </div>
              <span class="text-[10px] sm:text-xs text-slate-300 font-light">Modern Bath</span>
            </div>
            <div class="flex flex-col items-center">
              <div class="w-8 sm:w-9 md:w-10 h-8 sm:h-9 md:h-10 mb-1 text-amber-400">
                <Tv class="w-full h-full" />
              </div>
              <span class="text-[10px] sm:text-xs text-slate-300 font-light">Smart TV</span>
            </div>
            <div class="flex flex-col items-center">
              <div class="w-8 sm:w-9 md:w-10 h-8 sm:h-9 md:h-10 mb-1 text-amber-400">
                <Wifi class="w-full h-full" />
              </div>
              <span class="text-[10px] sm:text-xs text-slate-300 font-light">Fast WiFi</span>
            </div>
          </div>

          <!-- CTA Buttons -->
          <div
            class="flex flex-col sm:flex-row gap-2 sm:gap-3 md:gap-4 justify-center items-center"
          >
            <button
              @click="exploreRooms"
              class="w-full sm:w-auto px-5 sm:px-6 md:px-8 py-2 sm:py-2.5 md:py-3 bg-amber-500 hover:bg-amber-600 text-white text-xs sm:text-sm md:text-sm font-normal rounded-lg md:rounded-xl transition-all duration-300 transform hover:scale-105 shadow-xl hover:shadow-2xl cursor-pointer"
            >
              ✨ Explore Rooms
            </button>
          </div>

          <!-- Scroll Indicator -->
          <div class="mt-8 sm:mt-10 md:mt-12 animate-bounce">
            <div class="text-slate-400 text-[10px] sm:text-xs font-light mb-1.5">Scroll to see rooms</div>
            <div class="text-lg sm:text-xl">↓</div>
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
