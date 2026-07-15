<script setup lang="ts">
import { computed } from 'vue'

interface Props {
  guestName?: string
  roomNumber?: string
}

const props = withDefaults(defineProps<Props>(), {
  guestName: 'Guest',
  roomNumber: '---',
})

const greeting = computed(() => {
  const hour = new Date().getHours()

  if (hour < 12) {
    return 'Good Morning ☀️'
  }

  if (hour < 18) {
    return 'Good Afternoon 🌤️'
  }

  return 'Good Evening 🌙'
})

const currentDate = computed(() => {
  return new Date().toLocaleDateString('en-US', {
    weekday: 'long',
    month: 'long',
    day: 'numeric',
    year: 'numeric',
  })
})
</script>

<template>
  <div
    class="relative overflow-hidden rounded-3xl bg-white border border-slate-200 shadow-sm"
  >
    <div
      class="absolute inset-0 bg-gradient-to-r from-teal-50 via-white to-amber-50"
    />

    <div class="relative p-6 md:p-8">
      <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-6">

        <div class="flex items-center gap-4">

          <div
            class="w-16 h-16 rounded-2xl bg-teal-100 flex items-center justify-center text-2xl"
          >
            👤
          </div>

          <div>
            <h2 class="text-2xl font-bold text-slate-900">
              {{ props.guestName }}
            </h2>

            <p class="text-slate-600 font-medium">
              {{ greeting }}
            </p>

            <p class="text-slate-500 mt-2">
              Enjoy freshly prepared meals delivered directly to your room.
            </p>
          </div>

        </div>

        <div class="flex flex-col gap-3">

          <div
            class="px-4 py-2 rounded-xl bg-teal-600 text-white font-semibold"
          >
            Room {{ props.roomNumber }}
          </div>

          <div
            class="px-4 py-2 rounded-xl bg-slate-100 text-slate-700 text-sm"
          >
            {{ currentDate }}
          </div>
        </div>
      </div>
    </div>
  </div>
</template>