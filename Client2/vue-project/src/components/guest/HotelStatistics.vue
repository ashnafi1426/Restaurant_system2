<script setup lang="ts">
const statistics = [
  {
    value: '150+',
    label: 'Luxury Rooms',
    icon: '🛏️'
  },
  {
    value: '98%',
    label: 'Guest Satisfaction',
    icon: '⭐'
  },
  {
    value: '25+',
    label: 'Years Experience',
    icon: '🎖️'
  },
  {
    value: '50+',
    label: 'Staff Members',
    icon: '👥'
  }
]
</script>

<template>
  <section class="bg-gradient-to-r from-slate-900 to-slate-800 py-12 sm:py-16 md:py-20">
    <div class="mx-auto w-full max-w-7xl px-4 sm:px-6 md:px-8 lg:px-10">

      <!-- Stats Grid -->
      <div class="grid gap-8 sm:gap-10 md:gap-12 grid-cols-2 sm:grid-cols-2 lg:grid-cols-4">
        <div
          v-for="stat in statistics"
          :key="stat.label"
          class="flex flex-col items-center text-center text-white py-4 sm:py-6 md:py-8"
        >
          <!-- Icon -->
          <div class="text-5xl sm:text-6xl md:text-7xl mb-3 sm:mb-4 md:mb-6">
            {{ stat.icon }}
          </div>

          <!-- Value -->
          <h3 class="text-3xl sm:text-4xl md:text-5xl font-bold text-amber-400">
            {{ stat.value }}
          </h3>

          <!-- Label -->
          <p class="mt-2 sm:mt-3 text-xs sm:text-sm md:text-lg text-slate-300">
            {{ stat.label }}
          </p>
        </div>
      </div>

    </div>
  </section>
</template>
