<script setup lang="ts">
import { useRouter } from 'vue-router'
import { Calendar, BedDouble } from 'lucide-vue-next'

const router = useRouter()

function bookNow() {
  router.push('/rooms')
}

function exploreRooms() {
  router.push('/rooms')
}
</script>

<template>
  <section class="relative min-h-[85vh] flex items-center justify-center overflow-hidden bg-slate-950 font-sans pt-16">
    <!-- Hero Background Image (Vibrant & Fully Visible) -->
    <div class="absolute inset-0 z-0">
      <img
        src="https://images.unsplash.com/photo-1542314831-068cd1dbfeeb?w=1920&h=1080&fit=crop"
        alt="Grand Horizon Luxury Resort"
        class="h-full w-full object-cover scale-105 transition-all duration-700 opacity-90 dark:opacity-85 brightness-95 dark:brightness-80"
      />
      <!-- Soft Vignette Gradient Overlay for Crisp Text Readability -->
      <div class="absolute inset-0 bg-gradient-to-t from-slate-950 via-slate-950/55 to-slate-950/30"></div>
    </div>

    <!-- Hero Content -->
    <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 text-center space-y-6">
      <!-- Badge Pill -->
      <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-slate-950/70 text-amber-400 border border-amber-500/40 text-xs font-black uppercase tracking-widest backdrop-blur-md shadow-md">
        <span>Grand Horizon Hotel & Resort</span>
      </div>

      <!-- Main Title -->
      <h1 class="text-3xl sm:text-5xl lg:text-6xl font-black text-white tracking-tight leading-tight max-w-4xl mx-auto drop-shadow-md">
        Timeless Luxury & <span class="text-amber-400 drop-shadow-sm">Unmatched Comfort</span>
      </h1>

      <!-- Description -->
      <p class="text-sm sm:text-base lg:text-lg text-slate-200 font-medium max-w-2xl mx-auto leading-relaxed drop-shadow-xs">
        Experience 5-star Ethiopian hospitality, elegant master suites, fine dining, and personalized concierge service in the heart of Addis Ababa.
      </p>

      <!-- CTA Action Buttons -->
      <div class="flex flex-col sm:flex-row items-center justify-center gap-3.5 pt-3">
        <button
          @click="bookNow"
          class="w-full sm:w-auto px-8 py-3.5 rounded-2xl bg-amber-500 hover:bg-amber-600 text-slate-950 font-black text-xs uppercase tracking-wider shadow-lg shadow-amber-500/20 transition flex items-center justify-center gap-2 cursor-pointer"
        >
          <Calendar class="w-4 h-4" />
          <span>Book Your Stay</span>
        </button>

        <button
          @click="exploreRooms"
          class="w-full sm:w-auto px-8 py-3.5 rounded-2xl bg-slate-900/80 hover:bg-slate-900 text-white font-black text-xs uppercase tracking-wider border border-slate-700 shadow-md backdrop-blur-md transition flex items-center justify-center gap-2 cursor-pointer"
        >
          <BedDouble class="w-4 h-4 text-amber-400" />
          <span>Explore Rooms & Suites</span>
        </button>
      </div>

      <!-- Quick Highlights Bar -->
      <div class="pt-10 grid grid-cols-2 md:grid-cols-4 gap-4 max-w-4xl mx-auto text-left">
        <div class="p-4 rounded-2xl bg-slate-950/75 border border-slate-800/80 shadow-md backdrop-blur-md space-y-1">
          <p class="text-[10px] font-extrabold uppercase text-amber-400 tracking-wider">Check-In</p>
          <p class="text-xs font-black text-white">02:00 PM Daily</p>
        </div>

        <div class="p-4 rounded-2xl bg-slate-950/75 border border-slate-800/80 shadow-md backdrop-blur-md space-y-1">
          <p class="text-[10px] font-extrabold uppercase text-amber-400 tracking-wider">Location</p>
          <p class="text-xs font-black text-white">Bolé Road, Addis Ababa</p>
        </div>

        <div class="p-4 rounded-2xl bg-slate-950/75 border border-slate-800/80 shadow-md backdrop-blur-md space-y-1">
          <p class="text-[10px] font-extrabold uppercase text-amber-400 tracking-wider">Guest Rating</p>
          <p class="text-xs font-black text-white">★ 4.9 / 5.0 (2,400+ Reviews)</p>
        </div>

        <div class="p-4 rounded-2xl bg-slate-950/75 border border-slate-800/80 shadow-md backdrop-blur-md space-y-1">
          <p class="text-[10px] font-extrabold uppercase text-amber-400 tracking-wider">Concierge</p>
          <p class="text-xs font-black text-white">24/7 Personal Service</p>
        </div>
      </div>
    </div>
  </section>
</template>
