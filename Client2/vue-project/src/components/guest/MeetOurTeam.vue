<script setup lang="ts">
import { computed } from 'vue'
import { useLanguageStore } from '@/stores/language'
import { useGuestHotelStore } from '@/stores/guestHotelStore'

const languageStore = useLanguageStore()
const guestHotelStore = useGuestHotelStore()

interface TeamMember {
  id: number
  name: string
  position: string
  image: string
  bio: string
}

const teamMembers = computed<TeamMember[]>(() => [
  {
    id: 1,
    name: 'Abebe Tadesse',
    position: languageStore.t('General Manager', 'General Manager'),
    image: 'https://images.unsplash.com/photo-1560250097-0b93528c311a?w=600&h=800&fit=crop',
    bio: languageStore.t('gm_bio', 'With 20+ years in luxury hospitality, leading ' + guestHotelStore.hotelName + ' with vision, passion, and operational excellence.'),
  },
  {
    id: 2,
    name: 'Chef Michael Vance',
    position: languageStore.t('Executive Chef', 'Executive Chef'),
    image: 'https://images.unsplash.com/photo-1577219491135-ce391730fb2c?w=600&h=800&fit=crop',
    bio: languageStore.t('chef_bio', 'Award-winning international chef specializing in gourmet fine dining and authentic Ethiopian culinary innovation.'),
  },
  {
    id: 3,
    name: 'Sara Mengistu',
    position: languageStore.t('Front Desk Manager', 'Front Desk Manager'),
    image: 'https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?w=600&h=800&fit=crop',
    bio: languageStore.t('fd_manager_bio', 'Dedicated to providing 24/7 seamless check-ins, guest relations, and personalized concierge care.'),
  },
  {
    id: 4,
    name: 'Emma Wilson',
    position: languageStore.t('Guest Relations Director', 'Guest Relations Director'),
    image: 'https://images.unsplash.com/photo-1580489944761-15a19d654956?w=600&h=800&fit=crop',
    bio: languageStore.t('gr_director_bio', 'Ensures every guest stay at ' + guestHotelStore.hotelName + ' is memorable, tailored to perfection, and exceeds luxury standards.'),
  },
])
</script>

<template>
  <section class="bg-white dark:bg-slate-900 py-12 sm:py-16 md:py-20 lg:py-24 border-t border-slate-200 dark:border-slate-800 transition-colors duration-300">
    <div class="mx-auto w-full max-w-7xl px-4 sm:px-6 md:px-8 lg:px-10 space-y-12">
      <!-- Header -->
      <div class="mx-auto max-w-3xl text-center space-y-3">
        <span class="px-3.5 py-1 rounded-full text-xs font-black uppercase tracking-wider bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20">
          {{ guestHotelStore.hotelName }} • {{ languageStore.t('our_team', 'Our Team') }}
        </span>
        <h2 class="text-3xl sm:text-4xl lg:text-5xl font-black text-slate-900 dark:text-white tracking-tight">
          {{ languageStore.t('meet_our_team', 'Meet Our Executive Leadership') }}
        </h2>
        <p class="text-sm sm:text-base text-slate-600 dark:text-slate-400 font-medium">
          {{ languageStore.t('meet_our_team_desc', 'Our dedicated team at ' + guestHotelStore.hotelName + ' is committed to delivering exceptional service and creating unforgettable stays.') }}
        </p>
      </div>

      <!-- Team Grid -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <div
          v-for="member in teamMembers"
          :key="member.id"
          class="group bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-3xl overflow-hidden shadow-xs hover:border-amber-500/40 transition duration-300 text-center"
        >
          <!-- Member Photo -->
          <div class="relative h-72 overflow-hidden bg-slate-900">
            <img
              :src="member.image"
              :alt="member.name"
              class="h-full w-full object-cover transition duration-500 group-hover:scale-105"
            />
            <div class="absolute inset-0 bg-slate-950/70 opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex items-center justify-center p-5 text-white text-xs leading-relaxed font-medium">
              {{ member.bio }}
            </div>
          </div>

          <!-- Info -->
          <div class="p-5 space-y-1">
            <h3 class="text-base font-black text-slate-900 dark:text-white">
              {{ member.name }}
            </h3>
            <p class="text-xs font-bold text-amber-600 dark:text-amber-400 uppercase tracking-wider">
              {{ member.position }}
            </p>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

