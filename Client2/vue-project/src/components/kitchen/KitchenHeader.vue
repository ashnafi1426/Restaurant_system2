<script setup lang="ts">
import { computed, ref, onMounted, onUnmounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { storeToRefs } from 'pinia'

defineProps<{
  loading?: boolean
  autoRefresh?: boolean
}>()

const emit = defineEmits<{
  (e: 'refresh'): void
  (e: 'toggle-auto-refresh'): void
  (e: 'update-menu'): void
  (e: 'new-ticket'): void
}>()

const authStore = useAuthStore()
const { user } = storeToRefs(authStore)

const now = ref(new Date())
let timer: number

onMounted(() => {
  timer = window.setInterval(() => {
    now.value = new Date()
  }, 1000)
})

onUnmounted(() => {
  clearInterval(timer)
})

const currentDate = computed(() => {
  return now.value.toLocaleDateString('en-US', {
    weekday: 'short',
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  })
})

const currentTime = computed(() => {
  return now.value.toLocaleTimeString('en-US', {
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit',
  })
})

const chefName = computed(() => {
  return user.value?.name || 'Executive Chef'
})

</script>

<template>
  <div
    class="border-b border-slate-200 bg-white px-4 sm:px-6 md:px-8 py-4 sm:py-5 md:py-6 shadow-sm"
  >
    <div
      class="flex flex-col items-start justify-between gap-3 sm:gap-4 md:flex-row md:items-center"
    >
      <!-- Left Section -->
      <div class="min-w-0 flex-1">
        <div class="flex items-center gap-2 sm:gap-3">
          <div
            class="flex h-10 sm:h-12 w-10 sm:w-12 items-center justify-center rounded-xl bg-gradient-to-br from-teal-500 to-teal-600 text-lg sm:text-2xl shadow-md flex-shrink-0"
          >
            👨‍🍳
          </div>
          <div class="min-w-0">
            <h1 class="text-lg sm:text-xl md:text-2xl font-bold text-slate-900 truncate">
              Kitchen Command
            </h1>
            <p class="mt-0.5 text-xs sm:text-sm text-slate-500 hidden sm:block truncate">
              Real-time order management for Main Restaurant & Room Service
            </p>
          </div>
        </div>
      </div>

      <!-- Right Section -->
      <div class="flex flex-wrap items-center justify-end gap-2 sm:gap-3 w-full md:w-auto">
        <!-- Search Bar -->
        <div class="relative hidden lg:block w-full md:w-auto">
          <input
            type="text"
            placeholder="Search orders or rooms..."
            class="rounded-full border border-slate-300 bg-slate-50 py-2 pl-3 sm:pl-4 pr-3 sm:pr-4 text-xs sm:text-sm text-slate-600 transition placeholder-slate-400 focus:border-teal-500 focus:bg-white focus:outline-none w-full md:w-48 lg:w-56"
          />
          <svg
            class="absolute right-3 top-1/2 h-3 w-3 sm:h-4 sm:w-4 -translate-y-1/2 text-slate-400"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
            />
          </svg>
        </div>

        <!-- Notification -->
        <button class="relative rounded-lg p-1.5 sm:p-2 hover:bg-slate-100 flex-shrink-0">
          <svg
            class="h-5 w-5 sm:h-6 sm:w-6 text-slate-600"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M15 17h5l-1.405-1.405A2.032 2.032 0 1118 14.158V11a6 6 0 00-9-5.532V5a6 6 0 006 6h.666v4.3a1.997 1.997 0 01-.666 1.4M9 17h6"
            />
          </svg>
          <span
            class="absolute top-0.5 right-0.5 h-1.5 sm:h-2 w-1.5 sm:w-2 rounded-full bg-red-500"
          ></span>
        </button>

        <!-- Settings -->
        <button class="rounded-lg p-1.5 sm:p-2 hover:bg-slate-100 hidden sm:block flex-shrink-0">
          <svg
            class="h-5 w-5 sm:h-6 sm:w-6 text-slate-600"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"
            />
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
            />
          </svg>
        </button>

        <!-- Help -->
        <button class="rounded-lg p-1.5 sm:p-2 hover:bg-slate-100 hidden sm:block flex-shrink-0">
          <svg
            class="h-5 w-5 sm:h-6 sm:w-6 text-slate-600"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
            />
          </svg>
        </button>

        <!-- User Profile -->
        <div
          class="hidden sm:flex items-center gap-2 rounded-lg border border-slate-200 bg-slate-50 px-2 sm:px-3 py-1.5 sm:py-2 flex-shrink-0"
        >
          <div
            class="h-7 w-7 sm:h-8 sm:w-8 rounded-full bg-gradient-to-br from-teal-500 to-teal-600 flex-shrink-0"
          ></div>
          <div class="hidden text-right md:block min-w-0">
            <p class="text-xs sm:text-sm font-semibold text-slate-900 truncate">{{ chefName }}</p>
            <p class="text-xs text-slate-500 hidden lg:block">Your Orders Only</p>
          </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex gap-1 sm:gap-2 w-full sm:w-auto">
          <button
            @click="emit('new-ticket')"
            class="flex items-center gap-1 sm:gap-2 rounded-lg bg-teal-600 px-2 sm:px-3 md:px-4 py-1.5 sm:py-2 font-semibold text-xs sm:text-sm text-white transition hover:bg-teal-700 shadow-md whitespace-nowrap flex-1 sm:flex-none"
          >
            <span>+</span> <span class="hidden sm:inline">NEW TICKET</span
            ><span class="sm:hidden">NEW</span>
          </button>
          <button
            @click="emit('update-menu')"
            class="hidden md:flex items-center rounded-lg border border-slate-300 px-2 sm:px-3 md:px-4 py-1.5 sm:py-2 font-semibold text-xs sm:text-sm text-slate-700 transition hover:bg-slate-50 whitespace-nowrap"
          >
            UPDATE MENU AVAILABILITY
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
