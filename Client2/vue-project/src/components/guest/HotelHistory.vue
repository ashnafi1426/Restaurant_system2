<script setup lang="ts">
const milestones = [
  {
    year: '1995',
    title: 'Grand Foundation',
    description: 'Our journey began with a vision to build a world-class luxury hotel in the heart of Africa.',
  },
  {
    year: '2005',
    title: 'International Expansion',
    description: 'Added 50 executive suites and upgraded all fine dining, spa, and conference facilities.',
  },
  {
    year: '2015',
    title: 'Continental Recognition',
    description: 'Awarded Best Luxury Hotel in East Africa for exceptional guest satisfaction and service innovation.',
  },
  {
    year: '2023',
    title: 'The Modern Era',
    description: 'Completely renovated with smart room technology while preserving classic architectural elegance.',
  },
]
</script>

<template>
  <section class="bg-white dark:bg-slate-900 py-12 sm:py-16 md:py-20 lg:py-24 border-y border-slate-200 dark:border-slate-800 transition-colors duration-300">
    <div class="mx-auto w-full max-w-7xl px-4 sm:px-6 md:px-8 lg:px-10">
      <!-- Header -->
      <div class="mx-auto mb-12 max-w-3xl text-center space-y-3">
        <span class="px-3.5 py-1 rounded-full text-xs font-black uppercase tracking-wider bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20">
          Our Journey
        </span>
        <h2 class="text-3xl sm:text-4xl lg:text-5xl font-black text-slate-900 dark:text-white tracking-tight">
          Hotel History & Milestones
        </h2>
        <p class="text-sm sm:text-base text-slate-600 dark:text-slate-400 font-medium">
          From our humble beginnings to becoming a leading luxury destination, discover the story of our growth and commitment to excellence.
        </p>
      </div>

      <!-- Timeline -->
      <div class="relative">
        <div class="absolute left-4 sm:left-1/2 top-0 h-full w-0.5 -translate-x-0.5 sm:-translate-x-1/2 bg-amber-500/40"></div>

        <div class="space-y-8">
          <div v-for="(milestone, index) in milestones" :key="index" class="relative">
            <!-- Timeline Dot -->
            <div class="absolute left-0 sm:left-1/2 top-6 h-4 w-4 -translate-x-1.5 sm:-translate-x-1/2 rounded-full border-2 border-white dark:border-slate-900 bg-amber-500 shadow-md"></div>

            <!-- Content Card -->
            <div
              :class="[
                'pl-10 sm:pl-0',
                index % 2 === 0 ? 'sm:mr-1/2 sm:pr-10' : 'sm:ml-1/2 sm:pl-10',
              ]"
            >
              <div class="rounded-3xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 p-6 shadow-xs space-y-2">
                <span class="text-xl font-black text-amber-600 dark:text-amber-400 font-mono">{{ milestone.year }}</span>
                <h4 class="text-lg font-black text-slate-900 dark:text-white">
                  {{ milestone.title }}
                </h4>
                <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed font-medium">
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
