<script setup lang="ts">
const missions = [
  {
    icon: '🎯',
    title: 'Our Mission',
    description:
      'To provide exceptional hospitality through luxurious accommodation, personalized service, and memorable experiences for every guest who walks through our doors.',
  },
  {
    icon: '🌍',
    title: 'Our Vision',
    description:
      'To become one of the leading luxury hotels in Africa, recognized for excellence, innovation, and sustainable hospitality practices.',
  },
  {
    icon: '❤️',
    title: 'Our Values',
    description:
      'Integrity, Excellence, Respect, Innovation, Teamwork, and Customer Satisfaction guide every decision and action we take.',
  },
]

const coreValues = [
  { name: 'Integrity', emoji: '⚖️' },
  { name: 'Excellence', emoji: '🌟' },
  { name: 'Respect', emoji: '🤝' },
  { name: 'Innovation', emoji: '💡' },
  { name: 'Teamwork', emoji: '👥' },
  { name: 'Sustainability', emoji: '🌱' },
]
</script>

<template>
  <section class="bg-white py-12 sm:py-16 md:py-20 lg:py-24">
    <div class="mx-auto w-full max-w-7xl px-4 sm:px-6 md:px-8 lg:px-10">
      <!-- Main Cards -->
      <div class="grid gap-6 sm:gap-8 md:gap-10 lg:grid-cols-3 mb-12 sm:mb-16 md:mb-20">
        <div
          v-for="item in missions"
          :key="item.title"
          class="rounded-2xl sm:rounded-3xl bg-gradient-to-br from-slate-50 to-slate-100 p-6 sm:p-8 md:p-10 shadow-md md:shadow-lg hover:shadow-xl transition"
        >
          <!-- Icon -->
          <div class="text-4xl sm:text-5xl mb-4 sm:mb-6">
            {{ item.icon }}
          </div>

          <!-- Title -->
          <h3 class="text-xl sm:text-2xl font-bold text-slate-900 mb-3 sm:mb-4">
            {{ item.title }}
          </h3>

          <!-- Description -->
          <p class="text-sm sm:text-base text-slate-600 leading-6 sm:leading-7">
            {{ item.description }}
          </p>
        </div>
      </div>

      <!-- Core Values Section -->
      <div
        class="rounded-2xl sm:rounded-3xl bg-gradient-to-r from-amber-50 to-orange-50 p-6 sm:p-8 md:p-12"
      >
        <h3
          class="text-2xl sm:text-3xl md:text-4xl font-bold text-slate-900 text-center mb-8 sm:mb-10 md:mb-12"
        >
          Our Core Values
        </h3>

        <div class="grid gap-4 sm:gap-6 grid-cols-1 sm:grid-cols-2 lg:grid-cols-3">
          <div
            v-for="value in coreValues"
            :key="value.name"
            class="flex items-center gap-3 sm:gap-4 bg-white rounded-lg sm:rounded-xl p-4 sm:p-6 shadow-sm hover:shadow-md transition"
          >
            <div class="text-3xl sm:text-4xl flex-shrink-0">
              {{ value.emoji }}
            </div>
            <h4 class="text-base sm:text-lg font-semibold text-slate-900">
              {{ value.name }}
            </h4>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>
