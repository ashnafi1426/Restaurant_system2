<script setup lang="ts">
interface Reason {
  id: number
  icon: string
  title: string
  description: string
}

const reasons: Reason[] = [
  {
    id: 1,
    icon: '✨',
    title: 'Luxury Comfort',
    description:
      'Experience unmatched comfort in our beautifully appointed rooms with premium amenities.',
  },
  {
    id: 2,
    icon: '👥',
    title: 'Personal Service',
    description:
      'Our dedicated staff provides personalized attention to every guest ensuring exceptional care.',
  },
  {
    id: 3,
    icon: '🍽️',
    title: 'World-Class Dining',
    description:
      'Savor international cuisine prepared by award-winning chefs in our signature restaurants.',
  },
  {
    id: 4,
    icon: '🏊',
    title: 'Premium Facilities',
    description:
      'Enjoy state-of-the-art facilities including spa, pool, gym, and conference centers.',
  },
  {
    id: 5,
    icon: '🌍',
    title: 'Perfect Location',
    description:
      'Strategically located near major attractions and business districts for your convenience.',
  },
  {
    id: 6,
    icon: '🎯',
    title: 'Best Value',
    description:
      'Competitive pricing without compromising on quality, service, or luxury experience.',
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
          Why Choose Us
        </p>
        <h2 class="text-2xl sm:text-3xl md:text-4xl lg:text-5xl font-light text-slate-900">
          What Sets Us Apart
        </h2>
        <p
          class="mt-3 sm:mt-4 md:mt-6 text-sm sm:text-base md:text-lg leading-6 sm:leading-7 md:leading-8 text-slate-500"
        >
          Discover why thousands of guests choose us for their luxury hospitality experience year
          after year.
        </p>
      </div>

      <!-- Reasons Grid -->
      <div
        class="grid gap-6 sm:gap-8 md:gap-10 grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 mb-12 sm:mb-16 md:mb-20"
      >
        <div
          v-for="reason in reasons"
          :key="reason.id"
          class="rounded-lg sm:rounded-2xl bg-slate-50 p-6 sm:p-8 md:p-10 transition hover:-translate-y-1 sm:hover:-translate-y-2 hover:shadow-lg"
        >
          <!-- Icon -->
          <div class="text-4xl sm:text-5xl mb-4 sm:mb-6">
            {{ reason.icon }}
          </div>

          <!-- Title -->
          <h3 class="text-lg sm:text-xl md:text-2xl font-semibold text-slate-900 mb-2 sm:mb-4">
            {{ reason.title }}
          </h3>

          <!-- Description -->
          <p class="text-sm sm:text-base text-slate-600 leading-6 sm:leading-7">
            {{ reason.description }}
          </p>
        </div>
      </div>

      <!-- CTA Section -->
      <div
        class="rounded-lg sm:rounded-2xl md:rounded-3xl bg-gradient-to-r from-amber-500 to-amber-600 p-6 sm:p-8 md:p-12 text-center text-white"
      >
        <h3 class="text-xl sm:text-2xl md:text-3xl font-semibold mb-3 sm:mb-4 md:mb-5">
          Ready for an Exceptional Experience?
        </h3>
        <p
          class="mb-6 sm:mb-7 md:mb-8 text-sm sm:text-base md:text-lg text-amber-50 max-w-2xl mx-auto"
        >
          Book your stay today and discover why we are the preferred choice for luxury hospitality.
        </p>
        <button
          class="rounded-full bg-white px-6 sm:px-8 md:px-10 py-2.5 sm:py-3 md:py-4 text-sm sm:text-base font-semibold text-amber-600 transition hover:bg-amber-50"
        >
          Reserve Now
        </button>
      </div>
    </div>
  </section>
</template>
