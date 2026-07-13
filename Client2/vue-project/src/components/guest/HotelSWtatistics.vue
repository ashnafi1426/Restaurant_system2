<script setup lang="ts">
interface Statistic {
  value: string
  label: string
  description: string
  icon: string
}

const statistics: Statistic[] = [
  {
    value: '25+',
    label: 'Years Experience',
    description: 'Providing luxury hospitality since 1998.',
    icon: '🏨',
  },
  {
    value: '150+',
    label: 'Luxury Rooms',
    description: 'Elegant rooms and premium suites.',
    icon: '🛏️',
  },
  {
    value: '50+',
    label: 'Professional Staff',
    description: 'Dedicated hospitality professionals.',
    icon: '👨‍💼',
  },
  {
    value: '15K+',
    label: 'Happy Guests',
    description: 'Guests served from around the world.',
    icon: '😊',
  },
  {
    value: '4.9★',
    label: 'Guest Rating',
    description: 'Average rating across booking platforms.',
    icon: '⭐',
  },
  {
    value: '98%',
    label: 'Satisfaction',
    description: 'Customer satisfaction rate.',
    icon: '❤️',
  },
]
</script>

<template>
  <section class="bg-white py-24">
    <div class="mx-auto max-w-7xl px-6">
      <!-- Heading -->
      <div class="mx-auto max-w-3xl text-center">
        <p class="text-sm font-semibold uppercase tracking-[0.35em] text-amber-600">
          Our Achievement
        </p>

        <h2 class="mt-4 text-4xl font-bold text-slate-900">Hotel Statistics</h2>

        <p class="mt-6 text-lg leading-8 text-slate-600">
          Numbers that reflect our commitment to excellence, guest satisfaction, and world-class
          hospitality.
        </p>
      </div>

      <!-- Statistics Grid -->
      <div class="mt-16 grid gap-8 sm:grid-cols-2 lg:grid-cols-3">
        <div
          v-for="item in statistics"
          :key="item.label"
          class="group rounded-3xl border border-slate-200 bg-white p-10 text-center shadow-md transition-all duration-300 hover:-translate-y-2 hover:border-amber-500 hover:shadow-2xl"
        >
          <!-- Icon -->
          <div
            class="mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-amber-100 text-5xl transition group-hover:bg-amber-500 group-hover:text-white"
          >
            {{ item.icon }}
          </div>

          <!-- Number -->
          <h3 class="mt-8 text-5xl font-bold text-amber-600">
            {{ item.value }}
          </h3>

          <!-- Title -->
          <h4 class="mt-4 text-2xl font-semibold text-slate-900">
            {{ item.label }}
          </h4>

          <!-- Description -->
          <p class="mt-4 leading-7 text-slate-600">
            {{ item.description }}
          </p>
        </div>
      </div>

      <!-- Bottom Banner -->
      <div
        class="mt-20 rounded-3xl bg-gradient-to-r from-amber-500 to-amber-600 px-10 py-14 text-center text-white shadow-xl"
      >
        <h2 class="text-4xl font-bold">Trusted by Thousands of Guests Worldwide</h2>

        <p class="mx-auto mt-6 max-w-3xl text-lg leading-8">
          Every stay is an opportunity to create unforgettable experiences. We continue to improve
          our services, facilities, and hospitality to exceed the expectations of every guest.
        </p>
      </div>
    </div>
  </section>
</template>
