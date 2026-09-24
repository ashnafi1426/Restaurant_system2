<script setup lang="ts">
import { computed } from 'vue'
import { BedDouble, Star, Award, Users } from 'lucide-vue-next'
import { useLanguageStore } from '@/stores/language'
import { useRoomStore } from '@/stores/room'
import { useGuestHotelStore } from '@/stores/guestHotelStore'

const languageStore = useLanguageStore()
const roomStore = useRoomStore()
const guestHotelStore = useGuestHotelStore()

const roomCount = computed(() => {
  const count = roomStore.rooms?.length || 0
  return count > 0 ? `${count}+` : '50+'
})

const statistics = computed(() => [
  {
    value: roomCount.value,
    label: languageStore.t('luxury_suites_rooms', 'Luxury Suites & Rooms'),
    icon: BedDouble,
    color: 'text-amber-400'
  },
  {
    value: '99%',
    label: languageStore.t('guest_satisfaction_rate', 'Guest Satisfaction Rate'),
    icon: Star,
    color: 'text-emerald-400'
  },
  {
    value: '25+',
    label: languageStore.t('years_of_excellence', 'Years of Excellence'),
    icon: Award,
    color: 'text-blue-400'
  },
  {
    value: '80+',
    label: languageStore.t('professional_staff', 'Professional Staff'),
    icon: Users,
    color: 'text-purple-400'
  },
])
</script>

<template>
  <section class="bg-slate-900 border-y border-slate-800 py-12 sm:py-16 text-white font-sans">
    <div class="mx-auto w-full max-w-7xl px-4 sm:px-6 md:px-8">
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-6 text-center">
        <div
          v-for="stat in statistics"
          :key="stat.label"
          class="p-6 rounded-3xl bg-slate-950/60 border border-slate-800 flex flex-col items-center justify-center space-y-3"
        >
          <div class="p-3 bg-slate-800/80 rounded-2xl border border-slate-700">
            <component :is="stat.icon" :class="['w-7 h-7', stat.color]" />
          </div>
          <h3 class="text-3xl sm:text-4xl font-black tracking-tight text-white">
            {{ stat.value }}
          </h3>
          <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">
            {{ stat.label }}
          </p>
        </div>
      </div>
    </div>
  </section>
</template>

