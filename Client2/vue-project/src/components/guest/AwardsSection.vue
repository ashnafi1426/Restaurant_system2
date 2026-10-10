<script setup lang="ts">
import { computed } from 'vue'
import { Trophy, Star, Leaf, Utensils, Zap, Heart } from 'lucide-vue-next'
import { useLanguageStore } from '@/stores/language'
import { useGuestHotelStore } from '@/stores/guestHotelStore'

const languageStore = useLanguageStore()
const guestHotelStore = useGuestHotelStore()

interface Award {
  id: number
  title: string
  year: number
  icon: any
  description: string
  color: string
}

const awards = computed<Award[]>(() => [
  {
    id: 1,
    title: languageStore.t('Best Luxury Hotel', 'Best Luxury Hotel'),
    year: 2023,
    icon: Trophy,
    description: languageStore.t(
      'award_best_luxury_desc',
      'Awarded for exceptional service, suite luxury, and guest satisfaction.',
    ),
    color: 'text-amber-500 bg-amber-500/10 border-amber-500/20',
  },
  {
    id: 2,
    title: languageStore.t('Excellence in Hospitality', 'Excellence in Hospitality'),
    year: 2022,
    icon: Star,
    description: languageStore.t(
      'award_excellence_desc',
      'Continental recognition for outstanding front-desk service and staff care.',
    ),
    color: 'text-emerald-500 bg-emerald-500/10 border-emerald-500/20',
  },
  {
    id: 3,
    title: languageStore.t('Sustainable Tourism Award', 'Sustainable Tourism Award'),
    year: 2021,
    icon: Leaf,
    description: languageStore.t(
      'award_sustainable_desc',
      'Award for eco-friendly resort practices and energy conservation.',
    ),
    color: 'text-green-500 bg-green-500/10 border-green-500/20',
  },
  {
    id: 4,
    title: languageStore.t('Best Fine Dining Venue', 'Best Fine Dining Venue'),
    year: 2023,
    icon: Utensils,
    description: languageStore.t(
      'award_dining_desc',
      'Recognition for culinary excellence and international chef innovation.',
    ),
    color: 'text-rose-500 bg-rose-500/10 border-rose-500/20',
  },
  {
    id: 5,
    title: languageStore.t('Digital Innovation Award', 'Digital Innovation Award'),
    year: 2022,
    icon: Zap,
    description: languageStore.t(
      'award_digital_desc',
      'Award for smart room technology and QR guest Ordering integration.',
    ),
    color: 'text-blue-500 bg-blue-500/10 border-blue-500/20',
  },
  {
    id: 6,
    title: languageStore.t('Guest Choice Award', 'Guest Choice Award'),
    year: 2023,
    icon: Heart,
    description: languageStore.t(
      'award_guest_choice_desc',
      'Voted #1 luxury hospitality destination by our valued guests.',
    ),
    color: 'text-purple-500 bg-purple-500/10 border-purple-500/20',
  },
])
</script>

<template>
  <section
    class="bg-slate-50 dark:bg-slate-950 py-12 sm:py-16 md:py-20 lg:py-24 transition-colors duration-300"
  >
    <div class="mx-auto w-full max-w-7xl px-4 sm:px-6 md:px-8 lg:px-10 space-y-12">
      <div class="mx-auto max-w-3xl text-center space-y-3">
        <span
          class="px-3.5 py-1 rounded-full text-xs font-black uppercase tracking-wider bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20"
        >
          {{ guestHotelStore.hotelName }} •
          {{ languageStore.t('recognition_excellence', 'Recognition & Excellence') }}
        </span>
        <h2
          class="text-3xl sm:text-4xl lg:text-5xl font-black text-slate-900 dark:text-white tracking-tight"
        >
          {{ languageStore.t('awards_accolades', 'Awards & Accolades') }}
        </h2>
        <p class="text-sm sm:text-base text-slate-600 dark:text-slate-400 font-medium">
          {{
            languageStore.t(
              'awards_desc',
              'Our commitment to excellence at ' +
                guestHotelStore.hotelName +
                ' has been recognized by prestigious global hospitality organizations.',
            )
          }}
        </p>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        <div
          v-for="award in awards"
          :key="award.id"
          class="rounded-3xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-6 shadow-xs hover:border-amber-500/40 transition duration-300 text-center flex flex-col items-center space-y-3"
        >
          <div :class="['p-4 rounded-2xl border', award.color]">
            <component :is="award.icon" class="w-8 h-8" />
          </div>

          <span
            class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-slate-100 dark:bg-slate-800 text-amber-600 dark:text-amber-400 border border-slate-200 dark:border-slate-700"
          >
            {{ award.year }}
          </span>

          <h3 class="text-lg font-black text-slate-900 dark:text-white">
            {{ award.title }}
          </h3>

          <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed font-medium">
            {{ award.description }}
          </p>
        </div>
      </div>
    </div>
  </section>
</template>
