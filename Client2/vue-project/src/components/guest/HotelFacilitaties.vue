<script setup lang="ts">
interface Facility {
  id: number
  icon: string
  title: string
  description: string
}

const facilities: Facility[] = [
  {
    id: 1,
    icon: '📶',
    title: 'Free High-Speed WiFi',
    description:
      'Stay connected throughout the hotel with complimentary high-speed internet access.',
  },
  {
    id: 2,
    icon: '🍽️',
    title: 'Fine Dining Restaurant',
    description:
      'Enjoy gourmet cuisine prepared by experienced chefs using fresh local ingredients.',
  },
  {
    id: 3,
    icon: '🏊',
    title: 'Infinity Swimming Pool',
    description:
      'Relax and unwind in our luxurious outdoor pool overlooking beautiful surroundings.',
  },
  {
    id: 4,
    icon: '💆',
    title: 'Spa & Wellness',
    description:
      'Refresh your body and mind with premium spa treatments and wellness therapies.',
  },
  {
    id: 5,
    icon: '🏋️',
    title: 'Modern Fitness Center',
    description:
      'Fully equipped gym with the latest fitness equipment available every day.',
  },
  {
    id: 6,
    icon: '🚗',
    title: 'Free Secure Parking',
    description:
      'Safe and spacious parking area with 24-hour security for all hotel guests.',
  },
  {
    id: 7,
    icon: '🚐',
    title: 'Airport Shuttle',
    description:
      'Convenient airport transportation service available upon request.',
  },
  {
    id: 8,
    icon: '🛎️',
    title: '24/7 Room Service',
    description:
      'Order delicious meals and hotel services any time during your stay.',
  },
]
</script>

<template>
  <section class="bg-white py-12 sm:py-16 md:py-20 lg:py-24">

    <div class="mx-auto max-w-7xl px-4 sm:px-6 md:px-8 lg:px-10">

      <!-- ========================================= -->
      <!-- Section Header -->
      <!-- ========================================= -->

      <div class="mx-auto mb-12 sm:mb-14 md:mb-16 lg:mb-20 max-w-3xl text-center">

        <p class="mb-2 sm:mb-3 md:mb-4 uppercase tracking-[3px] sm:tracking-[4px] md:tracking-[5px] lg:tracking-[6px] text-amber-600 text-xs sm:text-sm md:text-base">
          Curated Comfort
        </p>

        <h2 class="text-2xl sm:text-3xl md:text-4xl lg:text-5xl font-light text-slate-900">
          World-Class Hotel Facilities
        </h2>

        <p class="mt-4 sm:mt-5 md:mt-6 text-sm sm:text-base md:text-lg lg:text-lg leading-6 sm:leading-7 md:leading-8 text-slate-500">
          Every facility has been thoughtfully designed to provide
          comfort, relaxation and unforgettable experiences throughout
          your stay.
        </p>

      </div>

      <!-- ========================================= -->
      <!-- Facilities Grid -->
      <!-- ========================================= -->

      <div
        class="grid gap-4 sm:gap-6 md:gap-8 grid-cols-1 sm:grid-cols-2 lg:grid-cols-4"
      >

        <article
          v-for="facility in facilities"
          :key="facility.id"
          class="group rounded-lg sm:rounded-xl md:rounded-2xl lg:rounded-3xl border border-slate-200 bg-white p-4 sm:p-6 md:p-8 lg:p-10 transition-all duration-300 hover:-translate-y-1 sm:hover:-translate-y-2 md:hover:-translate-y-3 hover:border-amber-500 hover:shadow-lg md:hover:shadow-xl lg:hover:shadow-2xl"
        >

          <!-- Icon -->

          <div
            class="mb-4 sm:mb-5 md:mb-6 flex h-14 sm:h-16 md:h-18 lg:h-20 w-14 sm:w-16 md:w-18 lg:w-20 items-center justify-center rounded-full bg-amber-100 text-3xl sm:text-4xl md:text-5xl transition group-hover:bg-amber-500"
          >
            <span class="group-hover:scale-110 transition">
              {{ facility.icon }}
            </span>
          </div>

          <!-- Title -->

          <h3
            class="mb-3 sm:mb-4 text-base sm:text-lg md:text-xl lg:text-2xl font-semibold text-slate-900"
          >
            {{ facility.title }}
          </h3>

          <!-- Description -->

          <p
            class="text-xs sm:text-sm md:text-base leading-5 sm:leading-6 md:leading-7 text-slate-500"
          >
            {{ facility.description }}
          </p>

        </article>

      </div>

      <!-- ========================================= -->
      <!-- Bottom CTA -->
      <!-- ========================================= -->

      <div class="mt-12 sm:mt-16 md:mt-20 lg:mt-24">

        <div
          class="overflow-hidden rounded-2xl sm:rounded-2xl md:rounded-3xl lg:rounded-4xl bg-slate-900 px-4 sm:px-6 md:px-8 lg:px-10 py-8 sm:py-10 md:py-12 lg:py-14 text-center text-white"
        >

          <p class="uppercase tracking-[2px] sm:tracking-[3px] md:tracking-[4px] lg:tracking-[5px] text-amber-400 text-xs sm:text-sm md:text-base">
            Luxury Redefined
          </p>

          <h2 class="mt-3 sm:mt-4 md:mt-5 lg:mt-6 text-2xl sm:text-3xl md:text-4xl lg:text-5xl font-light">
            Everything You Need for the Perfect Stay
          </h2>

          <p class="mx-auto mt-4 sm:mt-5 md:mt-6 lg:mt-8 max-w-3xl text-sm sm:text-base md:text-lg lg:text-xl leading-6 sm:leading-7 md:leading-8 text-slate-300">
            Whether you're travelling for business, leisure or a family
            vacation, our exceptional facilities ensure comfort and
            unforgettable memories from check-in to check-out.
          </p>

          <RouterLink
            to="/reservation"
            class="mt-6 sm:mt-8 md:mt-10 lg:mt-12 inline-flex rounded-full bg-amber-500 px-6 sm:px-8 md:px-10 lg:px-12 py-2.5 sm:py-3 md:py-4 lg:py-5 font-semibold text-xs sm:text-sm md:text-base text-white transition hover:bg-amber-600 hover:-translate-y-1 sm:hover:-translate-y-1.5 md:hover:-translate-y-2"
          >
            Book Your Stay
          </RouterLink>

        </div>

      </div>

    </div>

  </section>
</template>