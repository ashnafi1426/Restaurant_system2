<script setup lang="ts">
interface Value {
  icon: string
  title: string
  description: string
}

const values: Value[] = [
  {
    icon: '🎯',
    title: 'Mission',
    description:
      'To provide exceptional hospitality through luxurious accommodation, personalized service, and memorable experiences for every guest.',
  },
  {
    icon: '🌍',
    title: 'Vision',
    description:
      'To become one of the leading luxury hotels in Africa, recognized for excellence, innovation, and sustainable hospitality.',
  },
  {
    icon: '❤️',
    title: 'Core Values',
    description:
      'Integrity, Excellence, Respect, Innovation, Teamwork, and Customer Satisfaction guide everything we do every day.',
  },
]

const commitments = [
  'Luxury Accommodation',
  '24/7 Professional Service',
  'Guest Satisfaction',
  'Environmental Sustainability',
  'Innovation & Technology',
  'Safe & Secure Environment',
]
</script>

<template>
  <section class="bg-slate-50 py-12 sm:py-16 md:py-20 lg:py-24">
    <div class="mx-auto w-full max-w-7xl px-4 sm:px-6 md:px-8 lg:px-10">
      <!-- Heading -->
      <div class="mx-auto max-w-3xl text-center">
        <p
          class="text-xs sm:text-sm font-semibold uppercase tracking-[0.2em] sm:tracking-[0.3em] text-amber-600"
        >
          Our Philosophy
        </p>

        <h2
          class="mt-3 sm:mt-4 text-2xl sm:text-3xl md:text-4xl lg:text-5xl font-bold text-slate-900"
        >
          Mission, Vision & Values
        </h2>

        <p
          class="mt-4 sm:mt-6 text-sm sm:text-base md:text-lg leading-6 sm:leading-7 md:leading-8 text-slate-600"
        >
          Everything we do is guided by a commitment to excellence, genuine hospitality, and
          creating unforgettable experiences for every guest who stays with us.
        </p>
      </div>

      <!-- Cards -->
      <div
        class="mt-8 sm:mt-12 md:mt-16 grid gap-4 sm:gap-6 md:gap-8 grid-cols-1 sm:grid-cols-2 lg:grid-cols-3"
      >
        <div
          v-for="item in values"
          :key="item.title"
          class="rounded-2xl sm:rounded-3xl bg-white p-6 sm:p-8 md:p-10 shadow-md md:shadow-lg transition duration-300 hover:-translate-y-1 sm:hover:-translate-y-2 hover:shadow-lg md:hover:shadow-2xl"
        >
          <!-- Icon -->
          <div
            class="flex h-16 sm:h-18 md:h-20 w-16 sm:w-18 md:w-20 items-center justify-center rounded-full bg-amber-100 text-4xl sm:text-5xl"
          >
            {{ item.icon }}
          </div>

          <!-- Title -->
          <h3 class="mt-4 sm:mt-6 md:mt-8 text-lg sm:text-xl md:text-2xl font-bold text-slate-900">
            {{ item.title }}
          </h3>

          <!-- Description -->
          <p
            class="mt-3 sm:mt-4 md:mt-5 leading-6 sm:leading-7 md:leading-8 text-slate-600 text-sm sm:text-base"
          >
            {{ item.description }}
          </p>
        </div>
      </div>

      <!-- Bottom Section -->
      <div
        class="mt-12 sm:mt-16 md:mt-20 rounded-2xl sm:rounded-3xl bg-slate-900 p-6 sm:p-8 md:p-12 text-white"
      >
        <div class="grid gap-8 sm:gap-10 md:gap-12 grid-cols-1 md:grid-cols-2 md:items-center">
          <!-- Left -->
          <div>
            <p
              class="text-xs sm:text-sm uppercase tracking-[0.2em] sm:tracking-[0.3em] text-amber-400"
            >
              Our Commitment
            </p>

            <h2 class="mt-3 sm:mt-4 text-2xl sm:text-3xl md:text-4xl font-bold">
              Delivering Excellence in Every Stay
            </h2>

            <p
              class="mt-4 sm:mt-6 text-sm sm:text-base md:text-lg leading-6 sm:leading-7 md:leading-8 text-slate-300"
            >
              Our team is dedicated to exceeding guest expectations by combining luxury, comfort,
              innovation, and personalized hospitality in every interaction.
            </p>
          </div>

          <!-- Right -->
          <div class="grid gap-3 sm:gap-4 grid-cols-1 sm:grid-cols-2">
            <div
              v-for="item in commitments"
              :key="item"
              class="flex items-center gap-3 sm:gap-4 rounded-lg sm:rounded-xl bg-white/10 p-3 sm:p-4"
            >
              <div
                class="flex h-8 sm:h-10 w-8 sm:w-10 flex-shrink-0 items-center justify-center rounded-full bg-amber-500 text-xs sm:text-sm font-bold"
              >
                ✓
              </div>

              <span class="font-medium text-sm sm:text-base">
                {{ item }}
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>
