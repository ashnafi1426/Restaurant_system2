<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../../stores/auth'
import { useThemeStore } from '../../stores/theme'
import NotificationCenter from '@/components/reception/NotificationCenter.vue'
import { Sun, Moon } from 'lucide-vue-next'

const auth = useAuthStore()
const router = useRouter()
const themeStore = useThemeStore()
const profileOpen = ref(false)
const emit = defineEmits<{
  toggleSidebar: []
}>()

const toggleProfile = () => {
  profileOpen.value = !profileOpen.value
}

const handleThemeToggle = () => {
  console.log('[Navbar] 🎨 Theme toggle clicked')
  themeStore.toggleTheme()
  console.log('[Navbar] 🎨 New theme:', themeStore.isDark ? 'dark' : 'light')
}

const logout = async () => {
  profileOpen.value = false
  await auth.logout()
  router.push('/')
}

const toggleMobileSidebar = () => {
  emit('toggleSidebar')
}
</script>
<template>
  <header
    class="sticky top-0 z-40 flex h-14 sm:h-16 items-center justify-between border-b border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 px-4 sm:px-6 md:px-8 lg:px-10 transition-colors"
  >
    <!-- Left -->
    <div class="flex items-center gap-3 sm:gap-4 md:gap-6 min-w-0">
      <!-- Mobile Hamburger Menu -->
      <button
        @click="toggleMobileSidebar"
        class="flex lg:hidden h-9 w-9 items-center justify-center rounded-lg border border-slate-200 dark:border-slate-700 transition hover:bg-slate-100 dark:hover:bg-slate-800 flex-shrink-0"
        title="Toggle sidebar"
      >
        <span class="material-symbols-rounded text-slate-600 dark:text-slate-300 text-xl"> menu </span>
      </button>

      <!-- Page Title -->
      <div class="min-w-0">
        <h1 class="text-lg sm:text-xl md:text-2xl font-semibold text-slate-800 dark:text-slate-100 truncate">
          Dashboard
        </h1>

        <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 hidden sm:block">Hotel Management System</p>
      </div>
    </div>

    <!-- Right -->
    <div class="flex items-center gap-2 sm:gap-3 md:gap-4 flex-shrink-0">
      <!-- Search -->
      <div class="relative hidden lg:block">
        <span
          class="material-symbols-rounded absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 dark:text-slate-500 text-sm sm:text-base"
        >
          search
        </span>

        <input
          type="text"
          placeholder="Search..."
          class="w-48 sm:w-56 md:w-64 lg:w-72 rounded-lg border border-slate-300 dark:border-slate-700 py-2 pl-10 pr-4 text-xs sm:text-sm outline-none transition focus:border-slate-500 dark:focus:border-slate-500 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 placeholder-slate-500 dark:placeholder-slate-400"
        />
      </div>

      <!-- Notifications -->
      <NotificationCenter />

      <!-- Theme Toggle Button -->
      <button
        @click="handleThemeToggle"
        class="flex h-9 w-9 sm:h-10 sm:w-10 items-center justify-center rounded-lg border border-slate-200 transition hover:bg-slate-100 dark:border-slate-700 dark:hover:bg-slate-800 flex-shrink-0"
        :title="themeStore.isDark ? 'Switch to light mode' : 'Switch to dark mode'"
      >
        <Sun
          v-if="!themeStore.isDark"
          class="w-5 h-5 sm:w-6 sm:h-6 text-slate-600 transition-transform duration-300"
        />
        <Moon
          v-else
          class="w-5 h-5 sm:w-6 sm:h-6 text-slate-600 dark:text-yellow-400 transition-transform duration-300"
        />
      </button>

      <!-- Settings -->
      <button
        class="flex h-9 w-9 sm:h-10 sm:w-10 items-center justify-center rounded-lg border border-slate-200 transition hover:bg-slate-100 dark:border-slate-700 dark:hover:bg-slate-800 flex-shrink-0 hidden sm:flex"
      >
        <span class="material-symbols-rounded text-slate-600 dark:text-slate-300 text-sm sm:text-base"> settings </span>
      </button>

      <!-- User -->
      <div class="relative">
        <button
          @click="toggleProfile"
          class="flex items-center gap-2 sm:gap-3 rounded-lg border border-slate-200 px-2 sm:px-3 py-1.5 sm:py-2 transition hover:bg-slate-100 dark:border-slate-700 dark:hover:bg-slate-800 min-w-0"
        >
          <!-- Avatar -->
          <div
            class="flex h-8 w-8 sm:h-10 sm:w-10 items-center justify-center rounded-full bg-slate-200 dark:bg-slate-700 text-xs sm:text-sm font-semibold text-slate-700 dark:text-slate-200 flex-shrink-0"
          >
            {{ auth.user?.name?.charAt(0).toUpperCase() }}
          </div>

          <!-- User Info -->
          <div class="hidden text-left lg:block min-w-0">
            <h3 class="text-xs sm:text-sm font-semibold text-slate-800 dark:text-slate-100 truncate">
              {{ auth.user?.name }}
            </h3>

            <p class="text-xs text-slate-500 dark:text-slate-400 hidden md:block">Administrator</p>
          </div>

          <span
            class="material-symbols-rounded text-slate-500 dark:text-slate-400 text-sm sm:text-base flex-shrink-0 hidden sm:inline"
          >
            expand_more
          </span>
        </button>

        <!-- Profile Dropdown -->

        <div
          v-if="profileOpen"
          class="absolute right-0 mt-2 sm:mt-3 w-56 sm:w-60 md:w-64 overflow-hidden rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 shadow-xl"
        >
          <!-- User -->

          <div class="border-b border-slate-200 dark:border-slate-700 p-3 sm:p-4 md:p-5">
            <div class="flex items-center gap-2 sm:gap-3">
              <div
                class="flex h-10 w-10 sm:h-12 sm:w-12 items-center justify-center rounded-full bg-slate-200 dark:bg-slate-700 font-semibold text-slate-700 dark:text-slate-200 text-sm sm:text-base flex-shrink-0"
              >
                {{ auth.user?.name?.charAt(0).toUpperCase() }}
              </div>

              <div class="min-w-0">
                <h4 class="font-semibold text-slate-800 dark:text-slate-100 text-xs sm:text-sm truncate">
                  {{ auth.user?.name }}
                </h4>

                <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 truncate">
                  {{ auth.user?.email }}
                </p>
              </div>
            </div>
          </div>
          <button
            class="flex w-full items-center gap-2 sm:gap-3 px-3 sm:px-4 md:px-5 py-2 sm:py-3 text-left text-xs sm:text-sm text-slate-700 dark:text-slate-300 transition hover:bg-slate-100 dark:hover:bg-slate-800"
          >
            <span class="material-symbols-rounded text-sm sm:text-base flex-shrink-0">
              person
            </span>

            Profile
          </button>

          <button
            class="flex w-full items-center gap-2 sm:gap-3 px-3 sm:px-4 md:px-5 py-2 sm:py-3 text-left text-xs sm:text-sm text-slate-700 dark:text-slate-300 transition hover:bg-slate-100 dark:hover:bg-slate-800"
          >
            <span class="material-symbols-rounded text-sm sm:text-base flex-shrink-0">
              settings
            </span>

            Settings
          </button>

          <button
            class="flex w-full items-center gap-2 sm:gap-3 px-3 sm:px-4 md:px-5 py-2 sm:py-3 text-left text-xs sm:text-sm text-slate-700 dark:text-slate-300 transition hover:bg-slate-100 dark:hover:bg-slate-800"
          >
            <span class="material-symbols-rounded text-sm sm:text-base flex-shrink-0"> lock </span>

            Change Password
          </button>

          <div class="border-t border-slate-200 dark:border-slate-700"></div>

          <button
            @click="logout"
            class="flex w-full items-center gap-2 sm:gap-3 px-3 sm:px-4 md:px-5 py-2 sm:py-3 text-left text-xs sm:text-sm text-slate-700 dark:text-slate-300 transition hover:bg-slate-100 dark:hover:bg-slate-800"
          >
            <span class="material-symbols-rounded text-sm sm:text-base flex-shrink-0">
              logout
            </span>
            Logout
          </button>
        </div>
      </div>
    </div>
  </header>
</template>
