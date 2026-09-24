<script setup lang="ts">
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import { useLanguageStore } from '@/stores/language'
import { useGuestHotelStore } from '@/stores/guestHotelStore'
import { useRoomStore } from '@/stores/room'

const router = useRouter()
const languageStore = useLanguageStore()
const guestHotelStore = useGuestHotelStore()
const roomStore = useRoomStore()

const roomCount = computed(() => {
  const count = roomStore.rooms?.length || 0
  return count > 0 ? `${count}+` : '60+'
})

function goToRooms() {
  router.push('/rooms')
}

function goToReservation() {
  router.push('/rooms')
}
</script>

<template>
  <section class="relative min-h-[500px] overflow-hidden sm:h-[450px] md:h-[550px] lg:h-[600px] bg-slate-950 font-sans pt-16">
    <!-- Background Image -->
    <img
      src="/images/gallery/luxury-suite.jpg"
      :alt="guestHotelStore.hotelName + ' Gallery'"
      class="absolute inset-0 h-full w-full object-cover brightness-75"
    />

    <!-- Dark Overlay -->
    <div class="absolute inset-0 bg-gradient-to-r from-slate-950/90 via-slate-950/70 to-slate-950/40" />

    <!-- Decorative Gradient -->
    <div class="absolute inset-0 bg-gradient-to-t from-slate-950 via-transparent to-transparent" />

    <!-- Hero Content -->
    <div
      class="relative z-10 mx-auto flex h-full max-w-7xl items-center px-4 sm:px-6 py-12 sm:py-0"
    >
      <div class="max-w-3xl w-full space-y-4">
        <span class="inline-block px-3.5 py-1 rounded-full text-xs font-black uppercase tracking-wider bg-amber-500/20 text-amber-400 border border-amber-500/30">
          {{ guestHotelStore.hotelName }} • {{ languageStore.t('gallery', 'Visual Showcase') }}
        </span>

        <!-- Heading -->
        <h1
          class="text-3xl sm:text-4xl md:text-5xl lg:text-6xl font-black leading-tight text-white tracking-tight"
        >
          {{ languageStore.t('explore_every', 'Explore Every') }}
          <span class="text-amber-400"> {{ languageStore.t('corner', 'Corner') }} </span>
          <br />
          {{ languageStore.t('of_our_hotel', 'of Our Hotel') }}
        </h1>

        <!-- Description -->
        <p
          class="max-w-2xl text-xs sm:text-sm md:text-base leading-relaxed text-slate-200 font-medium"
        >
          {{ languageStore.t('gallery_hero_desc', 'Discover our luxurious suites, elegant dining rooms, spa center, and world-class guest facilities at ' + guestHotelStore.hotelName + ' before your stay.') }}
        </p>

        <!-- Buttons -->
        <div class="pt-2 flex flex-col sm:flex-row flex-wrap gap-3 sm:gap-4">
          <button
            @click="goToReservation"
            class="rounded-2xl bg-amber-500 hover:bg-amber-600 px-6 sm:px-8 py-3.5 font-black text-slate-950 shadow-lg shadow-amber-500/20 transition text-xs uppercase tracking-wider cursor-pointer"
          >
            {{ languageStore.t('book_your_stay', 'Book Your Stay') }}
          </button>

          <button
            @click="goToRooms"
            class="rounded-2xl border border-slate-700 bg-slate-900/80 hover:bg-slate-900 px-6 sm:px-8 py-3.5 font-black text-white transition text-xs uppercase tracking-wider cursor-pointer backdrop-blur-md"
          >
            {{ languageStore.t('view_rooms', 'View Rooms') }}
          </button>
        </div>

        <!-- Statistics -->
        <div class="pt-4 grid grid-cols-2 sm:grid-cols-4 gap-3 sm:gap-6">
          <div class="p-3 rounded-2xl bg-slate-900/70 border border-slate-800">
            <h3 class="text-xl sm:text-2xl font-black text-amber-400">{{ roomCount }}</h3>
            <p class="mt-0.5 text-[10px] sm:text-xs font-bold text-slate-300 uppercase">{{ languageStore.t('luxury_rooms', 'Luxury Rooms') }}</p>
          </div>

          <div class="p-3 rounded-2xl bg-slate-900/70 border border-slate-800">
            <h3 class="text-xl sm:text-2xl font-black text-amber-400">5★</h3>
            <p class="mt-0.5 text-[10px] sm:text-xs font-bold text-slate-300 uppercase">{{ languageStore.t('hotel_rating', 'Hotel Rating') }}</p>
          </div>

          <div class="p-3 rounded-2xl bg-slate-900/70 border border-slate-800">
            <h3 class="text-xl sm:text-2xl font-black text-amber-400">20+</h3>
            <p class="mt-0.5 text-[10px] sm:text-xs font-bold text-slate-300 uppercase">{{ languageStore.t('premium_facilities', 'Facilities') }}</p>
          </div>

          <div class="p-3 rounded-2xl bg-slate-900/70 border border-slate-800">
            <h3 class="text-xl sm:text-2xl font-black text-amber-400">15K+</h3>
            <p class="mt-0.5 text-[10px] sm:text-xs font-bold text-slate-300 uppercase">{{ languageStore.t('happy_guests', 'Happy Guests') }}</p>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>