<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../../stores/auth'
const auth = useAuthStore()
const router = useRouter()
const profileOpen = ref(false)
const toggleProfile = () => {
  profileOpen.value = !profileOpen.value
}
const logout = async () => {
  profileOpen.value = false

  await auth.logout()

  router.push('/')
}
</script>
<template>
  <header
    class="sticky top-0 z-40 flex h-14 sm:h-16 items-center justify-between border-b border-slate-200 bg-white px-4 sm:px-6 md:px-8 lg:px-10"
  >
    <!-- Left -->
    <div class="flex items-center gap-3 sm:gap-4 md:gap-6 min-w-0">
      <!-- Page Title -->
      <div class="min-w-0">
        <h1 class="text-lg sm:text-xl md:text-2xl font-semibold text-slate-800 truncate">Dashboard</h1>

        <p class="text-xs sm:text-sm text-slate-500 hidden sm:block">Hotel Management System</p>
      </div>
    </div>

    <!-- Right -->
    <div class="flex items-center gap-2 sm:gap-3 md:gap-4 flex-shrink-0">
      <!-- Search -->
      <div class="relative hidden lg:block">
        <span
          class="material-symbols-rounded absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm sm:text-base"
        >
          search
        </span>

        <input
          type="text"
          placeholder="Search..."
          class="w-48 sm:w-56 md:w-64 lg:w-72 rounded-lg border border-slate-300 py-2 pl-10 pr-4 text-xs sm:text-sm outline-none transition focus:border-slate-500"
        />
      </div>

      <!-- Notifications -->
      <button
        class="relative flex h-9 w-9 sm:h-10 sm:w-10 items-center justify-center rounded-lg border border-slate-200 transition hover:bg-slate-100 flex-shrink-0"
      >
        <span class="material-symbols-rounded text-slate-600 text-sm sm:text-base"> notifications </span>

        <span class="absolute right-1 top-1 sm:right-2 sm:top-2 h-1.5 sm:h-2 w-1.5 sm:w-2 rounded-full bg-slate-700"></span>
      </button>

      <!-- Settings -->
      <button
        class="flex h-9 w-9 sm:h-10 sm:w-10 items-center justify-center rounded-lg border border-slate-200 transition hover:bg-slate-100 flex-shrink-0 hidden sm:flex"
      >
        <span class="material-symbols-rounded text-slate-600 text-sm sm:text-base"> settings </span>
      </button>

      <!-- User -->
      <div class="relative">
        <button
          @click="toggleProfile"
          class="flex items-center gap-2 sm:gap-3 rounded-lg border border-slate-200 px-2 sm:px-3 py-1.5 sm:py-2 transition hover:bg-slate-100 min-w-0"
        >
          <!-- Avatar -->
          <div
            class="flex h-8 w-8 sm:h-10 sm:w-10 items-center justify-center rounded-full bg-slate-200 text-xs sm:text-sm font-semibold text-slate-700 flex-shrink-0"
          >
            {{ auth.user?.name?.charAt(0).toUpperCase() }}
          </div>

          <!-- User Info -->
          <div class="hidden text-left lg:block min-w-0">
            <h3 class="text-xs sm:text-sm font-semibold text-slate-800 truncate">
              {{ auth.user?.name }}
            </h3>

            <p class="text-xs text-slate-500 hidden md:block">Administrator</p>
          </div>

          <span class="material-symbols-rounded text-slate-500 text-sm sm:text-base flex-shrink-0 hidden sm:inline"> expand_more </span>
        </button>

        <!-- Profile Dropdown -->

        <div
          v-if="profileOpen"
          class="absolute right-0 mt-2 sm:mt-3 w-56 sm:w-60 md:w-64 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xl"
        >
          <!-- User -->

          <div class="border-b border-slate-200 p-3 sm:p-4 md:p-5">
            <div class="flex items-center gap-2 sm:gap-3">
              <div
                class="flex h-10 w-10 sm:h-12 sm:w-12 items-center justify-center rounded-full bg-slate-200 font-semibold text-slate-700 text-sm sm:text-base flex-shrink-0"
              >
                {{ auth.user?.name?.charAt(0).toUpperCase() }}
              </div>

              <div class="min-w-0">
                <h4 class="font-semibold text-slate-800 text-xs sm:text-sm truncate">
                  {{ auth.user?.name }}
                </h4>

                <p class="text-xs sm:text-sm text-slate-500 truncate">
                  {{ auth.user?.email }}
                </p>
              </div>
            </div>
          </div>
          <button
            class="flex w-full items-center gap-2 sm:gap-3 px-3 sm:px-4 md:px-5 py-2 sm:py-3 text-left text-xs sm:text-sm text-slate-700 transition hover:bg-slate-100"
          >
            <span class="material-symbols-rounded text-sm sm:text-base flex-shrink-0"> person </span>

            Profile
          </button>

          <button
            class="flex w-full items-center gap-2 sm:gap-3 px-3 sm:px-4 md:px-5 py-2 sm:py-3 text-left text-xs sm:text-sm text-slate-700 transition hover:bg-slate-100"
          >
            <span class="material-symbols-rounded text-sm sm:text-base flex-shrink-0"> settings </span>

            Settings
          </button>

          <button
            class="flex w-full items-center gap-2 sm:gap-3 px-3 sm:px-4 md:px-5 py-2 sm:py-3 text-left text-xs sm:text-sm text-slate-700 transition hover:bg-slate-100"
          >
            <span class="material-symbols-rounded text-sm sm:text-base flex-shrink-0"> lock </span>

            Change Password
          </button>

          <div class="border-t border-slate-200"></div>

          <button
            @click="logout"
            class="flex w-full items-center gap-2 sm:gap-3 px-3 sm:px-4 md:px-5 py-2 sm:py-3 text-left text-xs sm:text-sm text-slate-700 transition hover:bg-slate-100"
          >
            <span class="material-symbols-rounded text-sm sm:text-base flex-shrink-0"> logout </span>
            Logout
          </button>
        </div>
      </div>
    </div>
  </header>
</template>
