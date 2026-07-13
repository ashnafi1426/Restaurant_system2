<script setup lang="ts">
interface Testimonial {
  id: number
  name: string
  country: string
  image: string
  rating: number
  comment: string
}

const testimonials: Testimonial[] = [
  {
    id: 1,
    name: 'Emily Johnson',
    country: 'United States',
    image: '/images/testimonials/guest1.jpg',
    rating: 5,
    comment:
      'The hotel exceeded every expectation. The room was spotless, the staff were incredibly friendly, and the restaurant served amazing food. I will definitely return.',
  },
  {
    id: 2,
    name: 'Michael Brown',
    country: 'Canada',
    image: '/images/testimonials/guest2.jpg',
    rating: 5,
    comment:
      'Beautiful hotel with excellent facilities. The check-in process was smooth, and the panoramic view from our room was unforgettable.',
  },
  {
    id: 3,
    name: 'Sophia Martinez',
    country: 'Spain',
    image: '/images/testimonials/guest3.jpg',
    rating: 5,
    comment:
      "One of the best luxury hotels I've ever stayed in. Comfortable rooms, professional staff, and exceptional hospitality throughout our stay.",
  },
]

function stars(count: number) {
  return '★'.repeat(count)
}
</script>

<template>
  <section class="bg-white py-12 sm:py-16 md:py-20 lg:py-24">
    <div class="mx-auto w-full max-w-7xl px-4 sm:px-6 md:px-8 lg:px-10">
      <!-- Header -->
      <div class="mx-auto mb-8 sm:mb-12 md:mb-16 max-w-3xl text-center">
        <p
          class="mb-2 sm:mb-4 text-xs sm:text-sm uppercase tracking-[0.2em] sm:tracking-[0.3em] text-amber-600"
        >
          Guest Reviews
        </p>

        <h2 class="text-2xl sm:text-3xl md:text-4xl lg:text-5xl font-light text-slate-900">
          What Our Guests Say
        </h2>

        <p
          class="mt-3 sm:mt-4 md:mt-6 text-sm sm:text-base md:text-lg leading-6 sm:leading-7 md:leading-8 text-slate-500"
        >
          Every guest experience matters. Read what visitors from around the world have shared about
          their stay at our hotel.
        </p>
      </div>

      <!-- Testimonials -->
      <div class="grid gap-6 sm:gap-8 md:gap-10 grid-cols-1 sm:grid-cols-2 lg:grid-cols-3">
        <article
          v-for="guest in testimonials"
          :key="guest.id"
          class="rounded-lg sm:rounded-2xl lg:rounded-3xl bg-[#faf8f4] p-4 sm:p-6 md:p-8 shadow transition duration-500 hover:-translate-y-1 sm:hover:-translate-y-2 hover:shadow-lg md:hover:shadow-2xl"
        >
          <!-- Rating -->
          <div class="mb-3 sm:mb-4 md:mb-6 text-lg sm:text-2xl text-amber-500">
            {{ stars(guest.rating) }}
          </div>

          <!-- Review -->
          <p
            class="leading-6 sm:leading-7 md:leading-8 italic text-xs sm:text-sm md:text-base text-slate-600"
          >
            "{{ guest.comment }}"
          </p>

          <!-- Guest -->
          <div class="mt-6 sm:mt-8 flex items-center gap-3 sm:gap-4">
            <img
              :src="guest.image"
              :alt="guest.name"
              class="h-12 sm:h-14 md:h-16 w-12 sm:w-14 md:w-16 flex-shrink-0 rounded-full object-cover ring-2 sm:ring-4 ring-white"
            />

            <div class="min-w-0">
              <h3 class="text-sm sm:text-base md:text-lg font-semibold text-slate-900">
                {{ guest.name }}
              </h3>

              <p class="text-xs sm:text-sm text-slate-500">
                {{ guest.country }}
              </p>
            </div>
          </div>
        </article>
      </div>

      <!-- Bottom Statistics -->
      <div
        class="mt-12 sm:mt-16 md:mt-20 lg:mt-24 grid gap-4 sm:gap-6 md:gap-8 rounded-lg sm:rounded-2xl lg:rounded-3xl bg-slate-900 p-4 sm:p-6 md:p-8 lg:p-12 text-center text-white grid-cols-2 sm:grid-cols-2 lg:grid-cols-4"
      >
        <div>
          <h3 class="text-2xl sm:text-3xl md:text-4xl lg:text-5xl font-bold text-amber-400">
            4.9★
          </h3>

          <p
            class="mt-1 sm:mt-2 md:mt-3 text-xs sm:text-sm uppercase tracking-[0.15em] sm:tracking-[0.2em] text-slate-300"
          >
            Average Rating
          </p>
        </div>

        <div>
          <h3 class="text-2xl sm:text-3xl md:text-4xl lg:text-5xl font-bold text-amber-400">
            15K+
          </h3>

          <p
            class="mt-1 sm:mt-2 md:mt-3 text-xs sm:text-sm uppercase tracking-[0.15em] sm:tracking-[0.2em] text-slate-300"
          >
            Happy Guests
          </p>
        </div>

        <div>
          <h3 class="text-2xl sm:text-3xl md:text-4xl lg:text-5xl font-bold text-amber-400">98%</h3>

          <p
            class="mt-1 sm:mt-2 md:mt-3 text-xs sm:text-sm uppercase tracking-[0.15em] sm:tracking-[0.2em] text-slate-300"
          >
            Satisfaction Rate
          </p>
        </div>

        <div>
          <h3 class="text-2xl sm:text-3xl md:text-4xl lg:text-5xl font-bold text-amber-400">25+</h3>

          <p
            class="mt-1 sm:mt-2 md:mt-3 text-xs sm:text-sm uppercase tracking-[0.15em] sm:tracking-[0.2em] text-slate-300"
          >
            Years Experience
          </p>
        </div>
      </div>
    </div>
  </section>
</template>
