<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'

import { Bell, CalendarDays, Building2, CheckCircle2 } from 'lucide-vue-next'

/*
|--------------------------------------------------------------------------
| State
|--------------------------------------------------------------------------
*/

const currentTime = ref(new Date())

let timer: number

/*
|--------------------------------------------------------------------------
| Update Time
|--------------------------------------------------------------------------
*/

const updateTime = () => {
  currentTime.value = new Date()
}

onMounted(() => {
  timer = window.setInterval(updateTime, 1000)
})

onUnmounted(() => {
  clearInterval(timer)
})

/*
|--------------------------------------------------------------------------
| Computed
|--------------------------------------------------------------------------
*/

const formattedDate = computed(() => {
  return currentTime.value.toLocaleDateString('en-US', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  })
})

const formattedTime = computed(() => {
  return currentTime.value.toLocaleTimeString('en-US', {
    hour: '2-digit',
    minute: '2-digit',
  })
})
</script>

<template>
  <section class="bg-white rounded-3xl shadow-sm border border-slate-200 p-6">
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
      <!-- LEFT -->

      <div class="flex items-start gap-5">
        <div
          class="w-14 h-14 rounded-2xl bg-gradient-to-br from-blue-600 to-indigo-700 flex items-center justify-center shadow-lg"
        >
          <Building2 class="text-white w-7 h-7" />
        </div>

        <div>
          <h1 class="text-2xl font-bold text-slate-900">Good Morning, Manager</h1>

          <p class="mt-1 text-slate-500">Royal Horizon Hotel Management</p>

          <div class="flex items-center gap-2 mt-3 text-sm text-slate-600">
            <CalendarDays class="w-4 h-4" />

            <span>
              {{ formattedDate }}
            </span>

            <span> • </span>

            <span>
              {{ formattedTime }}
            </span>
          </div>
        </div>
      </div>

      <!-- RIGHT -->

      <div class="flex items-center gap-4">
        <!-- Hotel Status -->

        <div
          class="flex items-center gap-3 px-5 py-3 rounded-2xl bg-emerald-50 border border-emerald-200"
        >
          <div class="w-10 h-10 rounded-xl bg-emerald-500 flex items-center justify-center">
            <CheckCircle2 class="text-white w-5 h-5" />
          </div>

          <div>
            <p class="text-xs text-emerald-700 font-medium">Hotel Status</p>

            <p class="font-bold text-emerald-800">Operational</p>
          </div>
        </div>

        <!-- Notification -->

        <button
          class="relative w-12 h-12 rounded-xl bg-slate-100 hover:bg-slate-200 flex items-center justify-center transition"
        >
          <Bell class="w-5 h-5 text-slate-700" />

          <span
            class="absolute top-2 right-2 w-3 h-3 bg-red-500 rounded-full border-2 border-white"
          >
          </span>
        </button>
      </div>
    </div>
  </section>
</template>
