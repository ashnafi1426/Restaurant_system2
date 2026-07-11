<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'
import { RouterLink, useRoute } from 'vue-router'

const route = useRoute()

const mobileMenu = ref(false)
const scrolled = ref(false)

const menus = [
  {
    title: 'Home',
    route: '/',
  },
  {
    title: 'Rooms',
    route: '/rooms',
  },
  {
    title: 'Restaurant',
    route: '/restaurant',
  },
  {
    title: 'About',
    route: '/about',
  },
  {
    title: 'Contact',
    route: '/contact',
  },
]

function toggleMenu() {
  mobileMenu.value = !mobileMenu.value
}

function closeMenu() {
  mobileMenu.value = false
}

function handleScroll() {
  scrolled.value = window.scrollY > 50
}

onMounted(() => {
  window.addEventListener('scroll', handleScroll)
})

onUnmounted(() => {
  window.removeEventListener('scroll', handleScroll)
})
</script>

<template>
  <header
    class="fixed inset-x-0 top-0 z-50 transition-all duration-300"
    :class="
      scrolled
        ? 'bg-white shadow-lg'
        : 'bg-gradient-to-b from-black/70 via-black/20 to-transparent'
    "
  >
    <div
      class="mx-auto flex h-20 max-w-7xl items-center justify-between px-6 lg:px-10"
    >
      <!-- =========================== -->
      <!-- Logo -->
      <!-- =========================== -->

      <RouterLink
        to="/"
        class="flex items-center gap-3"
      >
        <div
          class="flex h-12 w-12 items-center justify-center rounded-full border border-amber-400 bg-white/10 backdrop-blur-md"
        >
          <span class="text-xl font-bold text-amber-400">
            H
          </span>
        </div>

        <div>
          <h2
            class="text-xl font-bold tracking-wide"
            :class="scrolled ? 'text-slate-900' : 'text-white'"
          >
            Grand Horizon
          </h2>

          <p
            class="text-xs uppercase tracking-[4px]"
            :class="scrolled ? 'text-slate-500' : 'text-gray-300'"
          >
            Luxury Hotel
          </p>
        </div>
      </RouterLink>

      <!-- =========================== -->
      <!-- Desktop Navigation -->
      <!-- =========================== -->

      <nav class="hidden items-center gap-10 lg:flex">
        <RouterLink
          v-for="menu in menus"
          :key="menu.route"
          :to="menu.route"
          class="font-medium transition"
          :class="
            route.path === menu.route
              ? 'text-amber-500'
              : scrolled
              ? 'text-slate-700 hover:text-amber-500'
              : 'text-white hover:text-amber-400'
          "
        >
          {{ menu.title }}
        </RouterLink>
      </nav>

      <!-- =========================== -->
      <!-- Buttons -->
      <!-- =========================== -->

      <div class="hidden items-center gap-4 lg:flex">
        <RouterLink
          to="/reservation"
          class="rounded-full border border-amber-500 px-6 py-3 text-sm font-semibold text-amber-500 transition hover:bg-amber-500 hover:text-white"
        >
          Book Now
        </RouterLink>

        <RouterLink
          to="/my-reservation"
          class="rounded-full bg-amber-500 px-6 py-3 text-sm font-semibold text-white transition hover:bg-amber-600"
        >
          My Reservation
        </RouterLink>
      </div>

      <!-- =========================== -->
      <!-- Mobile Button -->
      <!-- =========================== -->

      <button
        class="lg:hidden"
        @click="toggleMenu"
      >
        <svg
          xmlns="http://www.w3.org/2000/svg"
          class="h-8 w-8"
          :class="scrolled ? 'text-slate-900' : 'text-white'"
          fill="none"
          viewBox="0 0 24 24"
          stroke="currentColor"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M4 6h16M4 12h16M4 18h16"
          />
        </svg>
      </button>
    </div>

    <!-- =========================== -->
    <!-- Mobile Menu -->
    <!-- =========================== -->

    <transition
      enter-active-class="duration-300"
      leave-active-class="duration-200"
      enter-from-class="opacity-0 -translate-y-3"
      enter-to-class="opacity-100 translate-y-0"
      leave-from-class="opacity-100 translate-y-0"
      leave-to-class="opacity-0 -translate-y-3"
    >
      <div
        v-if="mobileMenu"
        class="border-t bg-white shadow-xl lg:hidden"
      >
        <div class="space-y-2 px-6 py-6">
          <RouterLink
            v-for="menu in menus"
            :key="menu.route"
            :to="menu.route"
            class="block rounded-lg px-4 py-3 font-medium text-slate-700 hover:bg-slate-100"
            @click="closeMenu"
          >
            {{ menu.title }}
          </RouterLink>

          <RouterLink
            to="/reservation"
            class="mt-4 block rounded-lg bg-amber-500 py-3 text-center font-semibold text-white"
            @click="closeMenu"
          >
            Book Now
          </RouterLink>

          <RouterLink
            to="/my-reservation"
            class="block rounded-lg border border-amber-500 py-3 text-center font-semibold text-amber-600"
            @click="closeMenu"
          >
            My Reservation
          </RouterLink>
        </div>
      </div>
    </transition>
  </header>
</template>