<script setup lang="ts">
import { useRouter } from 'vue-router'

const router = useRouter()

function goToRooms() {
  router.push('/rooms')
}

function goToReservation() {
  router.push('/reservation')
}
</script>

<template>
  <section class="relative min-h-[600px] overflow-hidden sm:h-[500px] md:h-[650px] lg:h-[700px]">
    <!-- Background Image -->
    <img
      src="/images/gallery/luxury-suite.jpg"
      alt="Hotel Gallery"
      class="absolute inset-0 h-full w-full object-cover"
    />

    <!-- Dark Overlay -->
    <div class="absolute inset-0 bg-gradient-to-r from-black/80 via-black/60 to-black/40" />

    <!-- Decorative Gradient -->
    <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent" />

    <!-- Hero Content -->
    <div
      class="relative z-10 mx-auto flex h-full max-w-7xl items-center px-4 sm:px-6 py-12 sm:py-0"
    >
      <div class="max-w-3xl w-full">
        <!-- Badge -->
        <div
          class="mb-4 sm:mb-6 inline-flex items-center rounded-full border border-amber-400/40 bg-amber-500/10 px-3 sm:px-5 py-2 backdrop-blur-sm"
        >
          <span class="text-xs sm:text-sm font-semibold uppercase tracking-[0.3em] text-amber-300">
            Hotel Gallery
          </span>
        </div>

        <!-- Heading -->
        <h1
          class="text-3xl sm:text-4xl md:text-5xl lg:text-7xl font-extrabold leading-tight text-white"
        >
          Explore Every
          <span class="text-amber-400"> Corner </span>
          <br />
          of Our Hotel
        </h1>

        <!-- Description -->
        <p
          class="mt-4 sm:mt-8 max-w-2xl text-sm sm:text-base md:text-lg leading-7 sm:leading-9 text-gray-200"
        >
          Discover our luxurious rooms, elegant suites, modern interiors, breathtaking exteriors,
          fine dining restaurant, swimming pool, conference halls, and premium guest facilities
          before your stay.
        </p>

        <!-- Buttons -->
        <div class="mt-6 sm:mt-10 flex flex-col sm:flex-row flex-wrap gap-3 sm:gap-5">
          <button
            @click="goToReservation"
            class="rounded-xl bg-amber-500 px-6 sm:px-8 py-3 sm:py-4 font-semibold text-white shadow-xl transition duration-300 hover:-translate-y-1 hover:bg-amber-600 text-sm sm:text-base"
          >
            Book Your Stay
          </button>

          <button
            @click="goToRooms"
            class="rounded-xl border border-white px-6 sm:px-8 py-3 sm:py-4 font-semibold text-white transition duration-300 hover:bg-white hover:text-slate-900 text-sm sm:text-base"
          >
            View Rooms
          </button>
        </div>

        <!-- Statistics -->
        <div class="mt-8 sm:mt-14 grid grid-cols-2 sm:grid-cols-4 gap-4 sm:gap-8">
          <div>
            <h3 class="text-2xl sm:text-4xl font-bold text-amber-400">120+</h3>
            <p class="mt-1 sm:mt-2 text-xs sm:text-base text-gray-300">Luxury Rooms</p>
          </div>

          <div>
            <h3 class="text-2xl sm:text-4xl font-bold text-amber-400">5★</h3>
            <p class="mt-1 sm:mt-2 text-xs sm:text-base text-gray-300">Hotel Rating</p>
          </div>

          <div>
            <h3 class="text-2xl sm:text-4xl font-bold text-amber-400">20+</h3>
            <p class="mt-1 sm:mt-2 text-xs sm:text-base text-gray-300">Premium Facilities</p>
          </div>

          <div>
            <h3 class="text-2xl sm:text-4xl font-bold text-amber-400">15K+</h3>
            <p class="mt-1 sm:mt-2 text-xs sm:text-base text-gray-300">Happy Guests</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Scroll Indicator -->
    <div class="absolute bottom-8 left-1/2 -translate-x-1/2 animate-bounce hidden sm:block">
      <div class="flex h-12 w-7 justify-center rounded-full border-2 border-white">
        <div class="mt-2 h-3 w-1 rounded-full bg-white" />
      </div>
    </div>

    <!-- Bottom Wave -->
    <div class="absolute bottom-0 left-0 right-0 hidden sm:block">
      <svg viewBox="0 0 1440 120" class="w-full fill-white">
        <path
          d="M0,64L80,69.3C160,75,320,85,480,90.7C640,96,800,96,960,80C1120,64,1280,32,1360,16L1440,0L1440,160L1360,160C1280,160,1120,160,960,160C800,160,640,160,480,160C320,160,160,160,80,160L0,160Z"
        />
      </svg>
    </div>
  </section>
</template>
