<script setup lang="ts">
interface Award {
  id: number
  title: string
  year: number
  icon: string
  description: string
}

const awards: Award[] = [
  {
    id: 1,
    title: 'Best Luxury Hotel',
    year: 2023,
    icon: '🏆',
    description: 'Awarded for exceptional service and luxury experience'
  },
  {
    id: 2,
    title: 'Excellence in Hospitality',
    year: 2022,
    icon: '⭐',
    description: 'Recognition for outstanding guest satisfaction'
  },
  {
    id: 3,
    title: 'Sustainable Tourism',
    year: 2021,
    icon: '🌱',
    description: 'Award for environmental responsibility and green practices'
  },
  {
    id: 4,
    title: 'Best Fine Dining',
    year: 2023,
    icon: '🍽️',
    description: 'Recognition for culinary excellence and innovation'
  },
  {
    id: 5,
    title: 'Innovation Award',
    year: 2022,
    icon: '💡',
    description: 'Award for technological advancement and digital experience'
  },
  {
    id: 6,
    title: 'Guest Choice Award',
    year: 2023,
    icon: '❤️',
    description: 'Voted favorite hotel by our valued guests'
  }
]
</script>

<template>
  <section class="bg-slate-50 py-12 sm:py-16 md:py-20 lg:py-24">
    <div class="mx-auto w-full max-w-7xl px-4 sm:px-6 md:px-8 lg:px-10">

      <!-- Header -->
      <div class="mx-auto mb-8 sm:mb-12 md:mb-16 max-w-3xl text-center">
        <p class="mb-2 sm:mb-4 text-xs sm:text-sm uppercase tracking-[0.2em] sm:tracking-[0.3em] text-amber-600">
          Recognition
        </p>
        <h2 class="text-2xl sm:text-3xl md:text-4xl lg:text-5xl font-light text-slate-900">
          Awards & Accolades
        </h2>
        <p class="mt-3 sm:mt-4 md:mt-6 text-sm sm:text-base md:text-lg leading-6 sm:leading-7 md:leading-8 text-slate-500">
          Our commitment to excellence has been recognized by prestigious organizations
          and satisfied guests worldwide.
        </p>
      </div>

      <!-- Awards Grid -->
      <div class="grid gap-6 sm:gap-8 md:gap-10 grid-cols-1 sm:grid-cols-2 lg:grid-cols-3">
        <div
          v-for="award in awards"
          :key="award.id"
          class="flex flex-col items-center rounded-lg sm:rounded-2xl bg-white p-6 sm:p-8 md:p-10 shadow-md transition hover:shadow-xl text-center"
        >
          <!-- Icon -->
          <div class="text-5xl sm:text-6xl md:text-7xl mb-3 sm:mb-4">
            {{ award.icon }}
          </div>

          <!-- Year -->
          <p class="text-xs sm:text-sm font-semibold text-amber-600 uppercase tracking-widest mb-2 sm:mb-3">
            {{ award.year }}
          </p>

          <!-- Title -->
          <h3 class="text-lg sm:text-xl md:text-2xl font-semibold text-slate-900 mb-2 sm:mb-3">
            {{ award.title }}
          </h3>

          <!-- Description -->
          <p class="text-xs sm:text-sm md:text-base text-slate-600 leading-relaxed">
            {{ award.description }}
          </p>
        </div>
      </div>

    </div>
  </section>
</template>
