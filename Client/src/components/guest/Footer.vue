<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { RouterLink } from 'vue-router'
import { ShieldCheck, Mail, Phone, MapPin, ExternalLink, LogIn } from 'lucide-vue-next'
import { useLanguageStore } from '@/stores/language'
import { useGuestHotelStore } from '@/stores/guestHotelStore'

const languageStore = useLanguageStore()
const guestHotelStore = useGuestHotelStore()
const imageLoadFailed = ref(false)

watch(
  () => [guestHotelStore.hotelId, guestHotelStore.currentHotel?.logo],
  () => {
    imageLoadFailed.value = false
  },
  { immediate: true },
)

const hotelLogoUrl = computed(() => {
  if (imageLoadFailed.value) return null
  const logo = guestHotelStore.currentHotel?.logo
  if (logo && typeof logo === 'string' && logo.trim()) {
    const trimmed = logo.trim()
    if (
      trimmed.startsWith('http://') ||
      trimmed.startsWith('https://') ||
      trimmed.startsWith('data:') ||
      trimmed.startsWith('/images/')
    ) {
      return trimmed
    }
    if (trimmed.startsWith('/storage/')) {
      return `http://127.0.0.1:8000${trimmed}`
    }
    if (trimmed.startsWith('storage/')) {
      return `http://127.0.0.1:8000/${trimmed}`
    }
    if (trimmed.startsWith('hotels/')) {
      return `http://127.0.0.1:8000/storage/${trimmed}`
    }
    if (trimmed.startsWith('images/')) {
      return `/${trimmed}`
    }
    return `http://127.0.0.1:8000/storage/${trimmed}`
  }

  const name = (guestHotelStore.hotelName || '').toLowerCase()
  if (name.includes('qelem') || name.includes('meda')) {
    return '/images/qelem meda logo.png'
  }
  return '/images/Hotel logo.png'
})
</script>

<template>
  <footer
    class="bg-slate-900 border-t border-slate-800 text-slate-300 font-sans transition-colors duration-300"
  >
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 sm:py-16 space-y-12">
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">
        <div class="space-y-4">
          <div class="flex items-center gap-2.5">
            <div
              class="w-11 h-11 rounded-2xl bg-white dark:bg-slate-950 border border-slate-700/80 text-amber-400 flex items-center justify-center font-black text-lg shadow-md overflow-hidden p-1"
            >
              <img
                v-if="hotelLogoUrl && !imageLoadFailed"
                :src="hotelLogoUrl"
                :alt="guestHotelStore.hotelName"
                class="w-full h-full object-contain"
                @error="imageLoadFailed = true"
              />
              <span v-else>{{ (guestHotelStore.hotelName || 'H').charAt(0).toUpperCase() }}</span>
            </div>
            <div>
              <span class="text-base font-black text-white tracking-wider uppercase block">{{
                guestHotelStore.hotelName
              }}</span>
              <span
                class="text-[9px] font-extrabold text-amber-400 uppercase tracking-widest block"
                >{{ guestHotelStore.currentHotel?.city || 'Luxury Hotel & Resort' }}</span
              >
            </div>
          </div>

          <p class="text-xs text-slate-400 leading-relaxed font-medium">
            Experience world-class luxury, authentic Ethiopian hospitality, and unforgettable stays
            in our fine suites and dining venues.
          </p>

          <div class="space-y-2 text-xs font-medium text-slate-400">
            <div class="flex items-center gap-2">
              <MapPin class="w-4 h-4 text-amber-400 flex-shrink-0" />
              <span>{{ guestHotelStore.hotelAddress }}</span>
            </div>
            <div class="flex items-center gap-2">
              <Phone class="w-4 h-4 text-amber-400 flex-shrink-0" />
              <span>{{ guestHotelStore.hotelPhone }}</span>
            </div>
            <div class="flex items-center gap-2">
              <Mail class="w-4 h-4 text-amber-400 flex-shrink-0" />
              <span>{{ guestHotelStore.hotelEmail }}</span>
            </div>
          </div>
        </div>

        <div class="space-y-4">
          <h3
            class="text-xs font-black text-white uppercase tracking-wider border-b border-slate-800 pb-2"
          >
            Navigation
          </h3>
          <ul class="space-y-2.5 text-xs font-bold text-slate-400">
            <li>
              <RouterLink to="/" class="hover:text-amber-400 transition flex items-center gap-1.5">
                <span>{{ languageStore.t('home', 'Home') }}</span>
              </RouterLink>
            </li>
            <li>
              <RouterLink
                to="/rooms"
                class="hover:text-amber-400 transition flex items-center gap-1.5"
              >
                <span>{{ languageStore.t('rooms_and_suites', 'Luxury Rooms & Suites') }}</span>
              </RouterLink>
            </li>
            <li>
              <RouterLink
                to="/gallery"
                class="hover:text-amber-400 transition flex items-center gap-1.5"
              >
                <span>{{ languageStore.t('gallery', 'Photo Gallery') }}</span>
              </RouterLink>
            </li>
            <li>
              <RouterLink
                to="/about"
                class="hover:text-amber-400 transition flex items-center gap-1.5"
              >
                <span>{{ languageStore.t('about', 'About Our Resort') }}</span>
              </RouterLink>
            </li>
            <li>
              <RouterLink
                to="/contact"
                class="hover:text-amber-400 transition flex items-center gap-1.5"
              >
                <span>{{ languageStore.t('contact', 'Contact & Location') }}</span>
              </RouterLink>
            </li>
          </ul>
        </div>

        <div class="space-y-4">
          <h3
            class="text-xs font-black text-white uppercase tracking-wider border-b border-slate-800 pb-2"
          >
            Services & Amenities
          </h3>
          <ul class="space-y-2.5 text-xs font-bold text-slate-400">
            <li>
              <RouterLink
                to="/rooms"
                class="hover:text-amber-400 transition flex items-center gap-1.5"
              >
                <span>{{ languageStore.t('book_now', 'Online Room Reservation') }}</span>
              </RouterLink>
            </li>
            <li>
              <RouterLink
                to="/contact"
                class="hover:text-amber-400 transition flex items-center gap-1.5"
              >
                <span>{{ languageStore.t('reserve_table', 'Fine Dining Reservations') }}</span>
              </RouterLink>
            </li>
            <li>
              <RouterLink
                to="/contact"
                class="hover:text-amber-400 transition flex items-center gap-1.5"
              >
                <span>Conferences & Wedding Events</span>
              </RouterLink>
            </li>
            <li>
              <RouterLink
                to="/contact"
                class="hover:text-amber-400 transition flex items-center gap-1.5"
              >
                <span>Spa & Wellness Packages</span>
              </RouterLink>
            </li>
            <li>
              <RouterLink
                to="/contact"
                class="hover:text-amber-400 transition flex items-center gap-1.5"
              >
                <span>Airport Shuttle Transfers</span>
              </RouterLink>
            </li>
          </ul>
        </div>

        <div class="space-y-4">
          <h3
            class="text-xs font-black text-white uppercase tracking-wider border-b border-slate-800 pb-2"
          >
            Staff & System Portal
          </h3>
          <p class="text-xs text-slate-400 leading-relaxed font-medium">
            Authorized staff, receptionists, managers, and administrators can log in to access
            property management tools.
          </p>

          <RouterLink
            to="/login"
            class="inline-flex items-center justify-center gap-2 w-full px-4 py-3 rounded-2xl bg-amber-500 hover:bg-amber-600 text-slate-950 font-black text-xs shadow-md shadow-amber-500/20 transition cursor-pointer"
          >
            <LogIn class="w-4 h-4" />
            <span>{{
              languageStore.currentLanguage === 'am' ? 'የሰራተኞች መግቢያ' : 'Staff Portal Login'
            }}</span>
          </RouterLink>
        </div>
      </div>

      <div
        class="pt-8 border-t border-slate-800 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs font-medium text-slate-500"
      >
        <p>
          &copy; {{ new Date().getFullYear() }} {{ guestHotelStore.hotelName }}. All rights
          reserved.
        </p>
        <div class="flex items-center gap-4 text-slate-400">
          <RouterLink to="/contact" class="hover:text-white transition">Privacy Policy</RouterLink>
          <span>•</span>
          <RouterLink to="/contact" class="hover:text-white transition"
            >Terms of Service</RouterLink
          >
          <span>•</span>
          <RouterLink to="/contact" class="hover:text-white transition">Support</RouterLink>
        </div>
      </div>
    </div>
  </footer>
</template>
