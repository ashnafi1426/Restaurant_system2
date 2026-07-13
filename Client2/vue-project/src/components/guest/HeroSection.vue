<script setup lang="ts">
import { useRouter } from 'vue-router'

const router = useRouter()

function bookNow() {
  router.push('/reservation')
}

function exploreRooms() {
  router.push('/rooms')
}
</script>

<template>
  <section
    class="relative flex h-screen min-h-[600px] sm:min-h-[700px] md:min-h-[750px] items-center overflow-hidden"
  >
    <!-- ===================================================== -->
    <!-- Background Image -->
    <!-- ===================================================== -->

    <img
      src="/images/hero/hero.jpg"
      alt="Luxury Hotel"
      class="absolute inset-0 h-full w-full object-cover"
    />

    <!-- Dark Overlay -->

    <div class="absolute inset-0 bg-gradient-to-r from-black/70 via-black/45 to-black/20" />

    <!-- ===================================================== -->
    <!-- Content -->
    <!-- ===================================================== -->

    <div
      class="relative z-10 mx-auto flex w-full max-w-7xl items-center px-4 sm:px-6 md:px-8 lg:px-10"
    >
      <div class="max-w-2xl">
        <!-- Small Label -->

        <p
          class="mb-3 sm:mb-4 text-xs sm:text-sm uppercase tracking-[4px] sm:tracking-[6px] text-amber-400"
        >
          Grand Horizon Hotel
        </p>

        <!-- Title -->

        <h1
          class="text-3xl sm:text-4xl md:text-5xl lg:text-6xl xl:text-7xl font-light leading-tight text-white"
        >
          Luxury
          <br />

          Hotel
          <br />

          Experience
        </h1>

        <!-- Description -->

        <p
          class="mt-5 sm:mt-6 md:mt-8 max-w-xl text-sm sm:text-base md:text-lg leading-6 sm:leading-7 md:leading-8 text-gray-200"
        >
          Experience exceptional hospitality, elegant rooms, world-class dining and unforgettable
          moments in the heart of the city.
        </p>

        <!-- Buttons -->

        <div
          class="mt-8 sm:mt-10 md:mt-12 flex flex-col sm:flex-row flex-wrap gap-3 sm:gap-4 md:gap-5"
        >
          <button
            class="rounded-full bg-amber-500 px-6 sm:px-8 py-3 sm:py-4 text-xs sm:text-sm font-semibold uppercase tracking-wider text-white transition duration-300 hover:bg-amber-600 w-full sm:w-auto text-center"
            @click="bookNow"
          >
            Book Your Stay
          </button>

          <button
            class="rounded-full border border-white px-6 sm:px-8 py-3 sm:py-4 text-xs sm:text-sm font-semibold uppercase tracking-wider text-white transition duration-300 hover:bg-white hover:text-black w-full sm:w-auto text-center"
            @click="exploreRooms"
          >
            Explore Rooms
          </button>
        </div>
      </div>
    </div>

    <!-- ===================================================== -->
    <!-- Bottom Information -->
    <!-- ===================================================== -->

    <div
      class="absolute bottom-6 sm:bottom-8 md:bottom-10 left-0 right-0 z-20 mx-auto flex max-w-7xl items-center justify-between px-4 sm:px-6 md:px-8 lg:px-10 text-white"
    >
      <div>
        <p class="text-xs uppercase tracking-[3px] sm:tracking-[4px] text-gray-300">Since 2026</p>

        <h3 class="mt-1 sm:mt-2 text-base sm:text-lg">Timeless Luxury</h3>
      </div>

      <!-- Scroll -->

      <div class="hidden items-center gap-3 sm:gap-4 md:flex">
        <span class="text-xs uppercase tracking-[3px] sm:tracking-[5px] text-gray-300">
          Scroll Down
        </span>

        <div class="flex h-12 sm:h-14 w-7 sm:w-8 justify-center rounded-full border border-white">
          <div class="mt-2 h-2.5 sm:h-3 w-0.5 sm:w-1 animate-bounce rounded-full bg-white" />
        </div>
      </div>
    </div>
  </section>
</template>
