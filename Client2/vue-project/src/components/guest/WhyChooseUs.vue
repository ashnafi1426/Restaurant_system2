<script setup lang="ts">
import { computed } from 'vue'
import { Sparkles, UserCheck, UtensilsCrossed, Waves, MapPin, Tag } from 'lucide-vue-next'
import { useLanguageStore } from '@/stores/language'
import { useGuestHotelStore } from '@/stores/guestHotelStore'

const languageStore = useLanguageStore()
const guestHotelStore = useGuestHotelStore()

interface Reason {
  id: number
  icon: any
  title: string
  description: string
  color: string
}

const reasons = computed<Reason[]>(() => [
  {
    id: 1,
    icon: Sparkles,
    title: languageStore.t('Luxury & Comfort', 'Luxury & Comfort'),
    description: languageStore.t(
      'reason_luxury_desc',
      'Experience unmatched comfort in our master suites with premium linen and city views.',
    ),
    color: 'text-amber-500 bg-amber-500/10 border-amber-500/20',
  },
  {
    id: 2,
    icon: UserCheck,
    title: languageStore.t('Personalized Concierge', 'Personalized Concierge'),
    description: languageStore.t(
      'reason_concierge_desc',
      'Our 24/7 dedicated staff ensures personalized attention for every guest request.',
    ),
    color: 'text-blue-500 bg-blue-500/10 border-blue-500/20',
  },
  {
    id: 3,
    icon: UtensilsCrossed,
    title: languageStore.t('World-Class Dining', 'World-Class Dining'),
    description: languageStore.t(
      'reason_dining_desc',
      'Savor gourmet Ethiopian and international cuisine prepared by award-winning chefs.',
    ),
    color: 'text-rose-500 bg-rose-500/10 border-rose-500/20',
  },
  {
    id: 4,
    icon: Waves,
    title: languageStore.t('Premium Wellness Facilities', 'Premium Wellness Facilities'),
    description: languageStore.t(
      'reason_wellness_desc',
      'Enjoy state-of-the-art facilities including luxury spa, pool, gym, and conference centers.',
    ),
    color: 'text-emerald-500 bg-emerald-500/10 border-emerald-500/20',
  },
  {
    id: 5,
    icon: MapPin,
    title: languageStore.t('Prime City Location', 'Prime City Location'),
    description: languageStore.t(
      'reason_location_desc',
      'Strategically located in ' +
        guestHotelStore.hotelCity +
        ' near business districts and cultural landmarks.',
    ),
    color: 'text-purple-500 bg-purple-500/10 border-purple-500/20',
  },
  {
    id: 6,
    icon: Tag,
    title: languageStore.t('Best Value Guarantee', 'Best Value Guarantee'),
    description: languageStore.t(
      'reason_value_desc',
      'Competitive rates and exclusive booking perks without compromising on luxury service.',
    ),
    color: 'text-teal-500 bg-teal-500/10 border-teal-500/20',
  },
])
</script>

<template>
  <section
    class="bg-white dark:bg-slate-900 py-12 sm:py-16 md:py-20 lg:py-24 border-t border-slate-200 dark:border-slate-800 transition-colors duration-300"
  >
    <div class="mx-auto w-full max-w-7xl px-4 sm:px-6 md:px-8 lg:px-10 space-y-12">
      <div class="mx-auto max-w-3xl text-center space-y-3">
        <span
          class="px-3.5 py-1 rounded-full text-xs font-black uppercase tracking-wider bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20"
        >
          {{ guestHotelStore.hotelName }} • {{ languageStore.t('why_choose_us', 'Why Choose Us') }}
        </span>
        <h2
          class="text-3xl sm:text-4xl lg:text-5xl font-black text-slate-900 dark:text-white tracking-tight"
        >
          {{ languageStore.t('what_sets_us_apart', 'What Sets Us Apart') }}
        </h2>
        <p class="text-sm sm:text-base text-slate-600 dark:text-slate-400 font-medium">
          {{
            languageStore.t(
              'why_choose_us_desc',
              'Discover why guests choose ' +
                guestHotelStore.hotelName +
                ' for their luxury hospitality experience in ' +
                guestHotelStore.hotelCity +
                '.',
            )
          }}
        </p>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        <div
          v-for="reason in reasons"
          :key="reason.id"
          class="rounded-3xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 p-6 shadow-xs hover:border-amber-500/40 transition duration-300 space-y-3"
        >
          <div
            :class="['w-12 h-12 rounded-2xl border flex items-center justify-center', reason.color]"
          >
            <component :is="reason.icon" class="w-6 h-6" />
          </div>

          <h3 class="text-lg font-black text-slate-900 dark:text-white">
            {{ reason.title }}
          </h3>

          <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed font-medium">
            {{ reason.description }}
          </p>
        </div>
      </div>
    </div>
  </section>
</template>
