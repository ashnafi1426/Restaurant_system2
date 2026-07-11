<script setup lang="ts">
import { useRouter } from 'vue-router'

const router = useRouter()

const goToReservation = () => {
  router.push('/reservation')
}

const scrollToRooms = () => {
  const section = document.getElementById('rooms-section')

  if (section) {
    section.scrollIntoView({
      behavior: 'smooth'
    })
  }
}
</script>

<template>
  <section class="relative h-[70vh] overflow-hidden">

    <!-- Background Image -->
    <img
      src="https://images.unsplash.com/photo-1590490360182-c33d57733427?w=1920"
      alt="Luxury Hotel Room"
      class="absolute inset-0 h-full w-full object-cover"
    >

    <!-- Overlay -->
    <div class="absolute inset-0 bg-black/60" />

    <!-- Content -->
    <div
      class="relative z-10 mx-auto flex h-full max-w-7xl items-center px-6"
    >

      <div class="max-w-3xl text-white">

        <!-- Breadcrumb -->
        <div
          class="mb-6 flex items-center gap-3 text-sm uppercase tracking-[0.3em]"
        >

          <RouterLink
            to="/"
            class="transition hover:text-amber-400"
          >
            Home
          </RouterLink>

          <span>/</span>

          <span class="text-amber-400">
            Rooms
          </span>

        </div>

        <!-- Small Title -->
        <p
          class="text-sm font-semibold uppercase tracking-[0.35em] text-amber-400"
        >
          Luxury Accommodation
        </p>

        <!-- Main Heading -->
        <h1
          class="mt-5 text-5xl font-bold leading-tight md:text-6xl"
        >
          Luxury Rooms
          <span class="text-amber-400">
            &
          </span>
          Suites
        </h1>

        <!-- Description -->
        <p
          class="mt-8 max-w-2xl text-lg leading-8 text-gray-200"
        >
          Discover beautifully designed rooms and premium suites
          crafted for comfort, elegance, and unforgettable hospitality.
          Whether you are traveling for business or leisure,
          we have the perfect room waiting for you.
        </p>

        <!-- Buttons -->
        <div
          class="mt-10 flex flex-wrap gap-5"
        >

          <button
            @click="goToReservation"
            class="rounded-full bg-amber-500 px-8 py-4 font-semibold text-white transition duration-300 hover:bg-amber-600"
          >
            Book Your Stay
          </button>

          <button
            @click="scrollToRooms"
            class="rounded-full border border-white px-8 py-4 font-semibold text-white transition duration-300 hover:bg-white hover:text-slate-900"
          >
            Browse Rooms
          </button>

        </div>

      </div>

    </div>

    <!-- Scroll Indicator -->
    <div
      class="absolute bottom-8 left-1/2 -translate-x-1/2 text-white"
    >

      <div class="flex flex-col items-center">

        <span
          class="mb-2 text-xs uppercase tracking-[0.3em]"
        >
          Scroll
        </span>

        <div
          class="flex h-12 w-7 justify-center rounded-full border border-white"
        >

          <div
            class="mt-2 h-3 w-1 animate-bounce rounded-full bg-white"
          />

        </div>

      </div>

    </div>

  </section>
</template>