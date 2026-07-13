<script setup lang="ts">
const milestones = [
  {
    year: '1995',
    title: 'Founded',
    description: 'Our journey began with a vision to create a luxury hospitality destination.',
  },
  {
    year: '2005',
    title: 'Expansion',
    description: 'Added 50 new rooms and upgraded all facilities to international standards.',
  },
  {
    year: '2015',
    title: 'Recognition',
    description: 'Awarded Best Luxury Hotel in Africa for exceptional service and innovation.',
  },
  {
    year: '2023',
    title: 'Modern Era',
    description: 'Renovated with state-of-the-art technology while maintaining classic elegance.',
  },
]
</script>

<template>
  <section class="bg-white py-12 sm:py-16 md:py-20 lg:py-24">
    <div class="mx-auto w-full max-w-7xl px-4 sm:px-6 md:px-8 lg:px-10">
      <!-- Header -->
      <div class="mx-auto mb-8 sm:mb-12 md:mb-16 max-w-3xl text-center">
        <p
          class="mb-2 sm:mb-4 text-xs sm:text-sm uppercase tracking-[0.2em] sm:tracking-[0.3em] text-amber-600"
        >
          Our Journey
        </p>
        <h2 class="text-2xl sm:text-3xl md:text-4xl lg:text-5xl font-light text-slate-900">
          Hotel History & Milestones
        </h2>
        <p
          class="mt-3 sm:mt-4 md:mt-6 text-sm sm:text-base md:text-lg leading-6 sm:leading-7 md:leading-8 text-slate-500"
        >
          From our humble beginnings to becoming a leading luxury destination, discover the story of
          our growth and commitment to excellence.
        </p>
      </div>

      <!-- Timeline -->
      <div class="relative">
        <!-- Timeline Line -->
        <div
          class="absolute left-4 sm:left-1/2 top-0 h-full w-0.5 sm:w-1 -translate-x-0.5 sm:-translate-x-1/2 bg-gradient-to-b from-amber-500 to-amber-200"
        ></div>

        <!-- Timeline Items -->
        <div class="space-y-8 sm:space-y-12">
          <div v-for="(milestone, index) in milestones" :key="index" class="relative">
            <!-- Timeline Dot -->
            <div
              class="absolute left-0 sm:left-1/2 top-6 h-3 sm:h-4 w-3 sm:w-4 -translate-x-1 sm:-translate-x-1/2 rounded-full border-4 border-white bg-amber-500 shadow-lg"
            ></div>

            <!-- Content -->
            <div
              :class="[
                'pl-12 sm:pl-8',
                index % 2 === 0 ? 'sm:ml-0 sm:mr-1/2 sm:pr-12' : 'sm:ml-1/2 sm:pl-12',
              ]"
            >
              <div
                class="rounded-lg sm:rounded-xl bg-slate-50 p-4 sm:p-6 md:p-8 shadow-md transition hover:shadow-lg"
              >
                <h3 class="text-2xl sm:text-3xl font-bold text-amber-600">
                  {{ milestone.year }}
                </h3>
                <h4 class="mt-2 text-lg sm:text-xl font-semibold text-slate-900">
                  {{ milestone.title }}
                </h4>
                <p class="mt-2 sm:mt-3 text-sm sm:text-base text-slate-600">
                  {{ milestone.description }}
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>
