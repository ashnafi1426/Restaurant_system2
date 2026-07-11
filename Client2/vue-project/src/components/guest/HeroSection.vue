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
    class="relative flex h-screen min-h-[750px] items-center overflow-hidden"
  >
    <!-- ===================================================== -->
    <!-- Background Image -->
    <!-- ===================================================== -->

    <img
      src="/images/hero/hero.jpg"
      alt="Luxury Hotel"
      class="absolute inset-0 h-full w-full object-cover"
    >

    <!-- Dark Overlay -->

    <div
      class="absolute inset-0 bg-gradient-to-r from-black/70 via-black/45 to-black/20"
    />

    <!-- ===================================================== -->
    <!-- Content -->
    <!-- ===================================================== -->

    <div
      class="relative z-10 mx-auto flex w-full max-w-7xl items-center px-6 lg:px-10"
    >
      <div class="max-w-2xl">

        <!-- Small Label -->

        <p
          class="mb-4 uppercase tracking-[6px] text-amber-400"
        >
          Grand Horizon Hotel
        </p>

        <!-- Title -->

        <h1
          class="text-5xl font-light leading-tight text-white md:text-6xl lg:text-7xl"
        >
          Luxury
          <br>

          Hotel
          <br>

          Experience
        </h1>

        <!-- Description -->

        <p
          class="mt-8 max-w-xl text-lg leading-8 text-gray-200"
        >
          Experience exceptional hospitality, elegant rooms,
          world-class dining and unforgettable moments in the
          heart of the city.
        </p>

        <!-- Buttons -->

        <div
          class="mt-12 flex flex-wrap gap-5"
        >
          <button
            class="rounded-full bg-amber-500 px-8 py-4 text-sm font-semibold uppercase tracking-wider text-white transition duration-300 hover:bg-amber-600"
            @click="bookNow"
          >
            Book Your Stay
          </button>

          <button
            class="rounded-full border border-white px-8 py-4 text-sm font-semibold uppercase tracking-wider text-white transition duration-300 hover:bg-white hover:text-black"
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
      class="absolute bottom-10 left-0 right-0 z-20 mx-auto flex max-w-7xl items-center justify-between px-6 text-white lg:px-10"
    >
      <div>
        <p class="text-xs uppercase tracking-[4px] text-gray-300">
          Since 2026
        </p>

        <h3 class="mt-2 text-lg">
          Timeless Luxury
        </h3>
      </div>

      <!-- Scroll -->

      <div
        class="hidden items-center gap-4 md:flex"
      >
        <span
          class="text-xs uppercase tracking-[5px] text-gray-300"
        >
          Scroll Down
        </span>

        <div
          class="flex h-14 w-8 justify-center rounded-full border border-white"
        >
          <div
            class="mt-2 h-3 w-1 animate-bounce rounded-full bg-white"
          />
        </div>
      </div>
    </div>
  </section>
</template>