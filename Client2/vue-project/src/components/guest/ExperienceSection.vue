<script setup lang="ts">
import { useRouter } from 'vue-router'
import { ref } from 'vue'

const router = useRouter()

// Hotel highlights - these are mostly static but structured for potential backend integration
const highlights = [
  {
    icon: '🏨',
    title: 'Luxury Accommodation',
    description: 'Elegant rooms and suites designed for exceptional comfort.',
  },
  {
    icon: '🌅',
    title: 'Scenic Views',
    description: 'Enjoy breathtaking city and landscape views from every floor.',
  },
  {
    icon: '🍽️',
    title: 'Fine Dining',
    description: 'International cuisine prepared by experienced chefs.',
  },
  {
    icon: '💆',
    title: 'Spa & Wellness',
    description: 'Relax with premium spa treatments and wellness facilities.',
  },
]

// Experience section images - could be fetched from backend
const experienceImage = ref('/images/facilities/restaurant.jpg')

function discoverMore() {
  router.push('/about')
}
</script>

<template>
  <section class="bg-white py-12 sm:py-16 md:py-20 lg:py-24">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 md:px-8 lg:px-10">
      <div class="grid items-center gap-8 sm:gap-10 md:gap-12 lg:gap-16 grid-cols-1 lg:grid-cols-2">
        <!-- ================================= -->
        <!-- Images -->
        <!-- ================================= -->

        <div class="relative order-2 lg:order-1">
          <!-- Main Image -->

          <img
            :src="experienceImage"
            alt="Luxury Hotel"
            class="h-48 sm:h-56 md:h-64 lg:h-80 w-full rounded-2xl sm:rounded-3xl lg:rounded-4xl object-cover shadow-lg sm:shadow-xl md:shadow-2xl"
          />

          <!-- Floating Card -->

          <div
            class="absolute -bottom-6 sm:-bottom-8 -right-4 sm:-right-6 md:-right-8 rounded-2xl sm:rounded-3xl bg-white p-4 sm:p-6 md:p-8 shadow-lg sm:shadow-xl md:shadow-2xl"
          >
            <p class="text-3xl sm:text-4xl md:text-5xl font-bold text-amber-600">25+</p>

            <p
              class="mt-2 text-xs sm:text-sm uppercase tracking-[2px] sm:tracking-[3px] text-slate-500"
            >
              Years of Hospitality
            </p>
          </div>
        </div>

        <!-- ================================= -->
        <!-- Content -->
        <!-- ================================= -->

        <div class="order-1 lg:order-2">
          <p
            class="mb-2 sm:mb-3 md:mb-4 uppercase tracking-[2px] sm:tracking-[3px] md:tracking-[4px] lg:tracking-[6px] text-amber-600 text-xs sm:text-sm"
          >
            Hotel Experience
          </p>

          <h2
            class="text-2xl sm:text-3xl md:text-4xl lg:text-5xl font-light leading-tight text-slate-900"
          >
            More Than
            <br />

            Just A Stay
          </h2>

          <p
            class="mt-4 sm:mt-5 md:mt-6 lg:mt-8 text-sm sm:text-base md:text-lg lg:text-lg leading-6 sm:leading-7 md:leading-8 text-slate-500"
          >
            Every moment at Grand Horizon Hotel is designed to deliver exceptional comfort,
            unforgettable experiences, and genuine hospitality. From elegant rooms to premium dining
            and wellness facilities, your stay becomes a memorable journey.
          </p>

          <!-- Features -->

          <div
            class="mt-8 sm:mt-10 md:mt-12 lg:mt-16 grid gap-4 sm:gap-6 md:gap-8 grid-cols-1 md:grid-cols-2"
          >
            <div
              v-for="feature in highlights"
              :key="feature.title"
              class="flex gap-3 sm:gap-4 md:gap-5"
            >
              <div
                class="flex h-12 sm:h-13 md:h-14 lg:h-16 w-12 sm:w-13 md:w-14 lg:w-16 items-center justify-center rounded-full bg-amber-100 text-2xl sm:text-3xl flex-shrink-0"
              >
                {{ feature.icon }}
              </div>

              <div>
                <h3 class="text-sm sm:text-base md:text-lg lg:text-xl font-semibold text-slate-900">
                  {{ feature.title }}
                </h3>

                <p class="mt-1 sm:mt-1.5 md:mt-2 text-xs sm:text-sm md:text-base text-slate-500">
                  {{ feature.description }}
                </p>
              </div>
            </div>
          </div>

          <button
            class="mt-8 sm:mt-10 md:mt-12 lg:mt-16 rounded-full bg-amber-600 px-6 sm:px-8 md:px-10 lg:px-12 py-2.5 sm:py-3 md:py-4 lg:py-5 font-semibold text-xs sm:text-sm md:text-base text-white transition hover:bg-amber-700 hover:-translate-y-1 sm:hover:-translate-y-1.5 md:hover:-translate-y-2"
            @click="discoverMore"
          >
            Discover More
          </button>
        </div>
      </div>

      <!-- ================================= -->
      <!-- Statistics -->
      <!-- ================================= -->

      <div
        class="mt-12 sm:mt-16 md:mt-20 lg:mt-24 grid gap-6 sm:gap-8 md:gap-10 lg:gap-12 rounded-2xl sm:rounded-3xl lg:rounded-4xl bg-slate-900 p-6 sm:p-8 md:p-10 lg:p-12 text-center text-white grid-cols-2 md:grid-cols-2 lg:grid-cols-4"
      >
        <div>
          <h3 class="text-2xl sm:text-3xl md:text-4xl lg:text-5xl font-bold text-amber-400">
            120+
          </h3>

          <p
            class="mt-2 sm:mt-3 md:mt-4 uppercase tracking-[1px] sm:tracking-[2px] md:tracking-[2.5px] lg:tracking-[3px] text-slate-300 text-xs sm:text-sm md:text-base"
          >
            Luxury Rooms
          </p>
        </div>

        <div>
          <h3 class="text-2xl sm:text-3xl md:text-4xl lg:text-5xl font-bold text-amber-400">
            15K+
          </h3>

          <p
            class="mt-2 sm:mt-3 md:mt-4 uppercase tracking-[1px] sm:tracking-[2px] md:tracking-[2.5px] lg:tracking-[3px] text-slate-300 text-xs sm:text-sm md:text-base"
          >
            Happy Guests
          </p>
        </div>

        <div>
          <h3 class="text-2xl sm:text-3xl md:text-4xl lg:text-5xl font-bold text-amber-400">25+</h3>

          <p
            class="mt-2 sm:mt-3 md:mt-4 uppercase tracking-[1px] sm:tracking-[2px] md:tracking-[2.5px] lg:tracking-[3px] text-slate-300 text-xs sm:text-sm md:text-base"
          >
            Years Experience
          </p>
        </div>

        <div>
          <h3 class="text-2xl sm:text-3xl md:text-4xl lg:text-5xl font-bold text-amber-400">
            4.9★
          </h3>

          <p
            class="mt-2 sm:mt-3 md:mt-4 uppercase tracking-[1px] sm:tracking-[2px] md:tracking-[2.5px] lg:tracking-[3px] text-slate-300 text-xs sm:text-sm md:text-base"
          >
            Guest Rating
          </p>
        </div>
      </div>
    </div>
  </section>
</template>
