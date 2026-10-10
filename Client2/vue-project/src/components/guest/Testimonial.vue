<script setup lang="ts">
import { ref } from 'vue'
import { Star, Quote } from 'lucide-vue-next'
import { useLanguageStore } from '@/stores/language'
import { useGuestHotelStore } from '@/stores/guestHotelStore'

const languageStore = useLanguageStore()
const guestHotelStore = useGuestHotelStore()

interface Testimonial {
  id: string
  name: string
  country: string
  image: string
  rating: number
  comment: string
}

const testimonials: Testimonial[] = [
  {
    id: '1',
    name: 'Emily Johnson',
    country: 'United States',
    image: 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=200&h=200&fit=crop',
    rating: 5,
    comment:
      'The hotel exceeded every expectation. The room was spotless, the staff were incredibly friendly, and the restaurant served amazing food. I will definitely return.',
  },
  {
    id: '2',
    name: 'Michael Brown',
    country: 'Canada',
    image: 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=200&h=200&fit=crop',
    rating: 5,
    comment:
      'Beautiful hotel with excellent facilities. The check-in process was smooth, and the panoramic view from our executive suite was unforgettable.',
  },
  {
    id: '3',
    name: 'Sophia Martinez',
    country: 'Spain',
    image: 'https://images.unsplash.com/photo-1517841905240-472988babdf9?w=200&h=200&fit=crop',
    rating: 5,
    comment:
      "One of the best luxury hotels I've ever stayed in Africa. Comfortable rooms, professional staff, and exceptional hospitality throughout our stay.",
  },
]
</script>

<template>
  <section
    class="bg-slate-50 dark:bg-slate-950 py-12 sm:py-16 md:py-20 lg:py-24 border-b border-slate-200 dark:border-slate-800 transition-colors duration-300 font-sans"
  >
    <div class="mx-auto w-full max-w-7xl px-4 sm:px-6 md:px-8 lg:px-10 space-y-12">
      <!-- Header -->
      <div class="mx-auto max-w-3xl text-center space-y-3">
        <span
          class="px-3.5 py-1 rounded-full text-xs font-black uppercase tracking-wider bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20"
        >
          {{ languageStore.t('guest_reviews', 'Guest Reviews') }}
        </span>

        <h2
          class="text-3xl sm:text-4xl lg:text-5xl font-black text-slate-900 dark:text-white tracking-tight"
        >
          {{ languageStore.t('what_guests_say', 'What Our Guests Say') }}
        </h2>

        <p class="text-sm sm:text-base text-slate-600 dark:text-slate-400 font-medium">
          {{
            languageStore.t(
              'what_guests_say_desc',
              'Read real reviews from international travelers who experienced the luxury of ' +
                guestHotelStore.hotelName +
                '.',
            )
          }}
        </p>
      </div>

      <!-- Testimonials Grid -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div
          v-for="item in testimonials"
          :key="item.id"
          class="rounded-3xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-6 shadow-xs flex flex-col justify-between space-y-4 hover:border-amber-500/40 transition duration-300"
        >
          <div class="space-y-3">
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-1 text-amber-400">
                <Star v-for="i in item.rating" :key="i" class="w-4 h-4 fill-amber-400" />
              </div>
              <Quote class="w-6 h-6 text-slate-300 dark:text-slate-700" />
            </div>

            <p
              class="text-xs text-slate-600 dark:text-slate-300 leading-relaxed font-medium italic"
            >
              "{{ item.comment }}"
            </p>
          </div>

          <div class="pt-4 border-t border-slate-100 dark:border-slate-800 flex items-center gap-3">
            <img
              :src="item.image"
              :alt="item.name"
              class="w-10 h-10 rounded-full object-cover border border-amber-500/30"
            />
            <div>
              <h3 class="text-xs font-black text-slate-900 dark:text-white">{{ item.name }}</h3>
              <p class="text-[10px] text-slate-400 font-bold uppercase">{{ item.country }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>
