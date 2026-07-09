<script setup lang="ts">
import { computed } from 'vue'

const props = defineProps<{
  total: number
}>()

const today = computed(() => {
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
    class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-blue-600 via-blue-700 to-indigo-800 p-8 shadow-xl"
  >
    <!-- Background Decorations -->
    <div class="absolute -right-12 -top-12 h-44 w-44 rounded-full bg-white/10"></div>

    <div class="absolute -bottom-20 right-20 h-52 w-52 rounded-full bg-white/5"></div>

    <div class="relative flex flex-col justify-between gap-8 lg:flex-row lg:items-center">
      <!-- Left -->
      <div class="flex items-start gap-5">
        <div
          class="flex h-20 w-20 items-center justify-center rounded-2xl bg-white/20 backdrop-blur"
        >
          <v-icon size="42" color="white"> mdi-door-open </v-icon>
        </div>

        <div>
          <h1 class="text-4xl font-bold tracking-wide text-white">Check In Management</h1>

          <p class="mt-2 text-blue-100 text-lg">
            Manage guest arrivals, room occupancy and today's hotel activity.
          </p>

          <div
            class="mt-5 inline-flex items-center rounded-full bg-white/20 px-4 py-2 backdrop-blur"
          >
            <v-icon size="18" color="white" class="mr-2"> mdi-calendar-month </v-icon>

            <span class="text-sm font-medium text-white">
              {{ today }}
            </span>
          </div>
        </div>
      </div>

      <!-- Right -->
      <div class="grid grid-cols-2 gap-4">
        <div class="rounded-2xl bg-white/15 p-5 text-center backdrop-blur">
          <div class="mb-2">
            <v-icon color="white" size="32"> mdi-account-check </v-icon>
          </div>

          <div class="text-3xl font-bold text-white">
            {{ total }}
          </div>

          <div class="mt-1 text-sm text-blue-100">Active Guests</div>
        </div>

        <div class="rounded-2xl bg-white/15 p-5 text-center backdrop-blur">
          <div class="mb-2">
            <v-icon color="white" size="32"> mdi-bed </v-icon>
          </div>

          <div class="text-3xl font-bold text-white">Hotel</div>

          <div class="mt-1 text-sm text-blue-100">Check In Desk</div>
        </div>
      </div>
    </div>
  </div>
</template>
