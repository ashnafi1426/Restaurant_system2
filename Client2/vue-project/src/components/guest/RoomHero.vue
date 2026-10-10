<script setup lang="ts">
import { Bed, Droplet, Tv, Wifi, Sparkles, ChevronDown } from 'lucide-vue-next'
import { useLanguageStore } from '@/stores/language'
import { useGuestHotelStore } from '@/stores/guestHotelStore'

const languageStore = useLanguageStore()
const guestHotelStore = useGuestHotelStore()

const exploreRooms = () => {
  const roomsSection = document.getElementById('rooms-section')
  if (roomsSection) {
    roomsSection.scrollIntoView({ behavior: 'smooth' })
  }
}
</script>

<template>
  <section
    class="relative min-h-[75vh] flex items-center justify-center overflow-hidden bg-slate-950 font-sans pt-16"
  >
    <!-- Hero Background Image (Fully Visible & Vibrant) -->
    <div class="absolute inset-0 z-0">
      <img
        src="https://images.unsplash.com/photo-1618773928121-c32242e63f39?w=1920&h=1080&fit=crop"
        :alt="guestHotelStore.hotelName + ' Luxury Suite'"
        class="h-full w-full object-cover scale-105 transition-all duration-700 opacity-90 dark:opacity-85 brightness-95 dark:brightness-80"
        @error="(e: any) => (e.target.src = '/images/hero/hero.jpg')"
      />
      <!-- Soft Vignette Overlay for High-Contrast Text Readability -->
      <div
        class="absolute inset-0 bg-gradient-to-t from-slate-950 via-slate-950/55 to-slate-950/30"
      ></div>
    </div>

    <!-- Content Container -->
    <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 text-center space-y-6">
      <!-- Badge Pill -->
      <div
        class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-slate-950/75 text-amber-400 border border-amber-500/40 text-xs font-black uppercase tracking-widest backdrop-blur-md shadow-md"
      >
        <span
          >{{ guestHotelStore.hotelName }} •
          {{ languageStore.t('hero_badge', 'Luxury Suites & Rooms') }}</span
        >
      </div>

      <!-- Main Title -->
      <h1
        class="text-3xl sm:text-5xl lg:text-6xl font-black text-white tracking-tight leading-tight max-w-4xl mx-auto drop-shadow-md"
      >
        {{ languageStore.t('discover_luxury_rooms', 'Discover Our Luxurious Rooms') }}
      </h1>

      <!-- Description -->
      <p
        class="text-sm sm:text-base lg:text-lg text-slate-200 font-medium max-w-2xl mx-auto leading-relaxed drop-shadow-xs"
      >
        {{
          languageStore.t(
            'discover_rooms_desc',
            'Experience unparalleled comfort and elegance at ' +
              guestHotelStore.hotelName +
              ' in ' +
              guestHotelStore.hotelCity +
              '. Each accommodation is crafted with premium amenities, modern furnishings, and stunning views.',
          )
        }}
      </p>

      <!-- Key Amenities Highlight -->
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 max-w-3xl mx-auto pt-2">
        <div
          class="p-3.5 rounded-2xl bg-slate-950/75 border border-slate-800/80 backdrop-blur-md flex items-center justify-center gap-2.5 text-white shadow-md"
        >
          <Bed class="w-4 h-4 text-amber-400 flex-shrink-0" />
          <span class="text-xs font-bold">{{ languageStore.t('luxury_beds', 'Luxury Beds') }}</span>
        </div>

        <div
          class="p-3.5 rounded-2xl bg-slate-950/75 border border-slate-800/80 backdrop-blur-md flex items-center justify-center gap-2.5 text-white shadow-md"
        >
          <Droplet class="w-4 h-4 text-amber-400 flex-shrink-0" />
          <span class="text-xs font-bold">{{ languageStore.t('modern_bath', 'Modern Bath') }}</span>
        </div>

        <div
          class="p-3.5 rounded-2xl bg-slate-950/75 border border-slate-800/80 backdrop-blur-md flex items-center justify-center gap-2.5 text-white shadow-md"
        >
          <Tv class="w-4 h-4 text-amber-400 flex-shrink-0" />
          <span class="text-xs font-bold">{{ languageStore.t('smart_tv', 'Smart HD TV') }}</span>
        </div>

        <div
          class="p-3.5 rounded-2xl bg-slate-950/75 border border-slate-800/80 backdrop-blur-md flex items-center justify-center gap-2.5 text-white shadow-md"
        >
          <Wifi class="w-4 h-4 text-amber-400 flex-shrink-0" />
          <span class="text-xs font-bold">{{ languageStore.t('free_wifi', 'Fast Wi-Fi') }}</span>
        </div>
      </div>

      <!-- Action CTA Button -->
      <div class="pt-4 flex justify-center">
        <button
          @click="exploreRooms"
          class="px-8 py-3.5 rounded-2xl bg-amber-500 hover:bg-amber-600 text-slate-950 font-black text-xs uppercase tracking-wider shadow-lg shadow-amber-500/20 transition flex items-center gap-2 cursor-pointer"
        >
          <Sparkles class="w-4 h-4" />
          <span>{{ languageStore.t('explore_rooms', 'Explore Available Suites') }}</span>
        </button>
      </div>

      <!-- Scroll Indicator -->
      <div
        @click="exploreRooms"
        class="pt-6 animate-bounce cursor-pointer inline-flex flex-col items-center text-slate-300 hover:text-white transition"
      >
        <span class="text-[10px] uppercase font-bold tracking-widest text-slate-400"
          >Scroll Down</span
        >
        <ChevronDown class="w-5 h-5 text-amber-400 mt-1" />
      </div>
    </div>
  </section>
</template>
