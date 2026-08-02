<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import { useThemeStore } from '../../stores/themeStore'
import { Sun, Moon } from 'lucide-vue-next'

const route = useRoute()
const theme = useThemeStore()

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
    title: 'Gallery',
    route: '/gallery',
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

const handleThemeToggle = () => {
  console.log('[GuestNavbar] 🎨 Theme toggle clicked')
  theme.toggleTheme()
  console.log('[GuestNavbar] 🎨 New theme:', theme.isDarkMode ? 'dark' : 'light')
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
        ? 'bg-white dark:bg-slate-900 shadow-lg dark:shadow-slate-950/50' 
        : 'bg-gradient-to-b from-black/70 dark:from-slate-950/90 via-black/20 dark:via-slate-900/40 to-transparent dark:to-transparent'
    "
  >
    <div class="mx-auto flex h-20 max-w-7xl items-center justify-between px-6 lg:px-10">
      <!-- =========================== -->
      <!-- Logo -->
      <!-- =========================== -->

      <RouterLink to="/" class="flex items-center gap-3">
        <div
          class="flex h-12 w-12 items-center justify-center rounded-full border border-amber-400 bg-white/10 backdrop-blur-md"
        >
          <span class="text-xl font-bold text-amber-400"> H </span>
        </div>

        <div>
          <h2
            class="text-xl font-bold tracking-wide transition-colors"
            :class="scrolled ? 'text-slate-900 dark:text-slate-100' : 'text-white'"
          >
            Grand Horizon
          </h2>

          <p
            class="text-xs uppercase tracking-[4px] transition-colors"
            :class="scrolled ? 'text-slate-500 dark:text-slate-400' : 'text-gray-300 dark:text-gray-400'"
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
              ? 'text-amber-500 dark:text-amber-400'
              : scrolled
                ? 'text-slate-700 dark:text-slate-300 hover:text-amber-500 dark:hover:text-amber-400'
                : 'text-white hover:text-amber-400'
          "
        >
          {{ menu.title }}
        </RouterLink>
      </nav>

      <!-- =========================== -->
      <!-- Theme Toggle & Buttons -->
      <!-- =========================== -->

      <div class="hidden items-center gap-3 lg:flex">
        <!-- Theme Toggle Button -->
        <button
          @click="handleThemeToggle"
          class="flex h-10 w-10 items-center justify-center rounded-lg border transition"
          :class="
            scrolled
              ? 'border-slate-300 dark:border-slate-600 hover:bg-slate-100 dark:hover:bg-slate-800'
              : 'border-white/30 hover:bg-white/10'
          "
          :title="theme.isDarkMode ? 'Switch to light mode' : 'Switch to dark mode'"
        >
          <Sun
            v-if="!theme.isDarkMode"
            class="w-5 h-5"
            :class="scrolled ? 'text-slate-700' : 'text-white'"
          />
          <Moon
            v-else
            class="w-5 h-5"
            :class="scrolled ? 'text-slate-600 dark:text-slate-300' : 'text-white'"
          />
        </button>

        <!-- Book Now Button -->
        <RouterLink
          to="/rooms"
          class="rounded-full border border-amber-500 px-6 py-3 text-sm font-semibold text-amber-500 transition hover:bg-amber-500 hover:text-white dark:border-amber-600 dark:text-amber-400 dark:hover:bg-amber-600"
        >
          Book Now
        </RouterLink>
      </div>

      <!-- =========================== -->
      <!-- Mobile Button -->
      <!-- =========================== -->

      <button class="lg:hidden" @click="toggleMenu">
        <svg
          xmlns="http://www.w3.org/2000/svg"
          class="h-8 w-8 transition-colors"
          :class="scrolled ? 'text-slate-900 dark:text-slate-100' : 'text-white'"
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
      <div v-if="mobileMenu" class="border-t border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 shadow-xl dark:shadow-slate-950/50 lg:hidden transition-colors">
        <div class="space-y-2 px-6 py-6">
          <RouterLink
            v-for="menu in menus"
            :key="menu.route"
            :to="menu.route"
            class="block rounded-lg px-4 py-3 font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
            @click="closeMenu"
          >
            {{ menu.title }}
          </RouterLink>

          <!-- Theme Toggle in Mobile Menu -->
          <button
            @click="handleThemeToggle"
            class="w-full flex items-center justify-center gap-2 rounded-lg px-4 py-3 font-medium text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors mt-4"
          >
            <Sun v-if="!theme.isDarkMode" class="w-5 h-5" />
            <Moon v-else class="w-5 h-5" />
            <span>{{ theme.isDarkMode ? 'Light Mode' : 'Dark Mode' }}</span>
          </button>

          <RouterLink
            to="/rooms"
            class="mt-4 block rounded-lg bg-amber-500 dark:bg-amber-600 py-3 text-center font-semibold text-white hover:bg-amber-600 dark:hover:bg-amber-700 transition-colors"
            @click="closeMenu"
          >
            Book Now
          </RouterLink>

          <RouterLink
            to="/my-reservation"
            class="block rounded-lg border border-amber-500 dark:border-amber-600 py-3 text-center font-semibold text-amber-600 dark:text-amber-400 hover:bg-amber-50 dark:hover:bg-amber-900/20 transition-colors"
            @click="closeMenu"
          >
            My Reservation
          </RouterLink>
        </div>
      </div>
    </transition>
  </header>
</template>
