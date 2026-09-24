<script setup lang="ts">
import { computed, ref, onMounted, onUnmounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { storeToRefs } from 'pinia'
import { ChefHat, Search, Bell, Settings, HelpCircle, Plus, UtensilsCrossed } from 'lucide-vue-next'

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

const chefName = computed(() => {
  return user.value?.name || 'Executive Chef'
})
</script>

<template>
  <div
    class="border-b border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 px-4 sm:px-6 md:px-8 py-4 sm:py-5 md:py-6 shadow-sm transition-colors duration-300 rounded-2xl"
  >
    <div
      class="flex flex-col items-start justify-between gap-3 sm:gap-4 md:flex-row md:items-center"
    >
      <div class="min-w-0 flex-1">
        <div class="flex items-center gap-2 sm:gap-3">
          <div
            class="flex h-10 sm:h-12 w-10 sm:w-12 items-center justify-center rounded-xl bg-gradient-to-br from-amber-500 to-amber-600 text-white shadow-md flex-shrink-0"
          >
            <ChefHat class="w-6 h-6" />
          </div>
          <div class="min-w-0">
            <h1 class="text-lg sm:text-xl md:text-2xl font-black text-slate-900 dark:text-white truncate">
              Kitchen Command
            </h1>
            <p class="mt-0.5 text-xs sm:text-sm text-slate-500 dark:text-slate-400 hidden sm:block truncate font-medium">
              Real-time order management for Main Restaurant & Room Service
            </p>
          </div>
        </div>
      </div>

      <div class="flex flex-wrap items-center justify-end gap-2 sm:gap-3 w-full md:w-auto">
        <div class="relative hidden lg:block w-full md:w-auto">
          <input
            type="text"
            placeholder="Search orders or rooms..."
            class="rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/80 py-2 pl-3 sm:pl-4 pr-9 text-xs sm:text-sm text-slate-900 dark:text-white transition placeholder-slate-400 focus:border-amber-500 focus:outline-none w-full md:w-48 lg:w-56"
          />
          <Search class="absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
        </div>

        <button class="relative rounded-xl p-2 hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-600 dark:text-slate-300 transition flex-shrink-0">
          <Bell class="h-5 w-5" />
          <span class="absolute top-1 right-1 h-2 w-2 rounded-full bg-rose-500"></span>
        </button>

        <button class="rounded-xl p-2 hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-600 dark:text-slate-300 transition hidden sm:block flex-shrink-0">
          <Settings class="h-5 w-5" />
        </button>

        <button class="rounded-xl p-2 hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-600 dark:text-slate-300 transition hidden sm:block flex-shrink-0">
          <HelpCircle class="h-5 w-5" />
        </button>

        <div
          class="hidden sm:flex items-center gap-2.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/60 px-3 py-1.5 flex-shrink-0"
        >
          <div
            class="h-8 w-8 rounded-full bg-gradient-to-br from-amber-500 to-amber-600 flex items-center justify-center text-white font-black text-xs flex-shrink-0"
          >
            <ChefHat class="w-4 h-4" />
          </div>
          <div class="hidden text-right md:block min-w-0">
            <p class="text-xs sm:text-sm font-bold text-slate-900 dark:text-white truncate">{{ chefName }}</p>
            <p class="text-[10px] text-slate-500 dark:text-slate-400 font-medium">Executive Chef</p>
          </div>
        </div>

        <div class="flex gap-2 w-full sm:w-auto">
          <button
            @click="emit('new-ticket')"
            class="flex items-center justify-center gap-2 rounded-xl bg-amber-500 hover:bg-amber-600 text-slate-950 px-3.5 py-2 font-black text-xs sm:text-sm transition shadow-md shadow-amber-500/20 whitespace-nowrap flex-1 sm:flex-none cursor-pointer"
          >
            <Plus class="w-4 h-4 stroke-[3]" />
            <span>NEW TICKET</span>
          </button>
          <button
            @click="emit('update-menu')"
            class="hidden md:flex items-center justify-center gap-2 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-800/80 px-3.5 py-2 font-bold text-xs sm:text-sm text-slate-700 dark:text-slate-200 transition hover:bg-slate-50 dark:hover:bg-slate-700 whitespace-nowrap cursor-pointer"
          >
            <UtensilsCrossed class="w-4 h-4" />
            <span>UPDATE MENU AVAILABILITY</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
