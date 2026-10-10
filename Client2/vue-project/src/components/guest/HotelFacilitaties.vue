<script setup lang="ts">
import { computed } from 'vue'
import {
  Wifi,
  Utensils,
  Waves,
  Sparkles,
  Dumbbell,
  Car,
  ShieldCheck,
  ConciergeBell,
} from 'lucide-vue-next'
import { useLanguageStore } from '@/stores/language'
import { useGuestHotelStore } from '@/stores/guestHotelStore'

const languageStore = useLanguageStore()
const guestHotelStore = useGuestHotelStore()

interface Facility {
  id: number
  icon: any
  title: string
  description: string
  color: string
}

const facilities = computed<Facility[]>(() => [
  {
    id: 1,
    icon: Wifi,
    title: languageStore.t('free_wifi', 'Free High-Speed Wi-Fi'),
    description:
      languageStore.currentLanguage === 'am'
        ? 'በሆቴሉ ውስጥ በሙሉ ፈጣን የፋይበር ኢንተርኔት በነጻ ያግኙ።'
        : 'Stay connected throughout the hotel with complimentary high-speed fiber internet.',
    color: 'text-blue-500 bg-blue-500/10 border-blue-500/20',
  },
  {
    id: 2,
    icon: Utensils,
    title: languageStore.t('signature_dining', 'Fine Dining Restaurant'),
    description:
      languageStore.currentLanguage === 'am'
        ? 'በተዋጣላቸው ሼፎች ትኩስ ግብዓቶች የተዘጋጁ ምርጥ ምግቦችን ያጣጥሙ።'
        : 'Enjoy gourmet cuisine prepared by experienced chefs using fresh local ingredients.',
    color: 'text-amber-500 bg-amber-500/10 border-amber-500/20',
  },
  {
    id: 3,
    icon: Waves,
    title: languageStore.currentLanguage === 'am' ? 'የዋና ገንዳ' : 'Infinity Swimming Pool',
    description:
      languageStore.currentLanguage === 'am'
        ? 'ውብ የከተማ እይታ ባለው የዋና ገንዳችን ውስጥ ዘና ይበሉ።'
        : 'Relax and unwind in our luxury outdoor pool overlooking stunning city panoramas.',
    color: 'text-teal-500 bg-teal-500/10 border-teal-500/20',
  },
  {
    id: 4,
    icon: Sparkles,
    title: languageStore.currentLanguage === 'am' ? 'ስፓ እና ጤና' : 'Luxury Spa & Wellness',
    description:
      languageStore.currentLanguage === 'am'
        ? 'በማሳጅ እና በሳውና አገልግሎቶቻችን ሰውነትዎን ያድሱ።'
        : 'Refresh your body and mind with premium massage treatments and thermal saunas.',
    color: 'text-purple-500 bg-purple-500/10 border-purple-500/20',
  },
  {
    id: 5,
    icon: Dumbbell,
    title: languageStore.currentLanguage === 'am' ? 'የአካል ብቃት ማዕከል' : 'Modern Fitness Center',
    description:
      languageStore.currentLanguage === 'am'
        ? 'ዘመናዊ መሣሪያዎች የተሟሉለት የጂም ማዕከል 24/7 ክፍት ነው።'
        : 'Fully equipped gym with modern cardio & strength training gear open 24/7.',
    color: 'text-rose-500 bg-rose-500/10 border-rose-500/20',
  },
  {
    id: 6,
    icon: Car,
    title:
      languageStore.currentLanguage === 'am' ? 'አስተማማኝ የመኪና ማቆሚያ' : 'Free Valet & Secure Parking',
    description:
      languageStore.currentLanguage === 'am'
        ? 'የ24 ሰዓት ጥበቃ ያለው አስተማማኝና ሰፊ የመኪና ማቆሚያ።'
        : 'Safe and spacious parking area with 24-hour security & valet service.',
    color: 'text-emerald-500 bg-emerald-500/10 border-emerald-500/20',
  },
  {
    id: 7,
    icon: ShieldCheck,
    title:
      languageStore.currentLanguage === 'am' ? 'የአውሮፕላን ማረፊያ ትራንስፖርት' : 'Airport Luxury Transfer',
    description:
      languageStore.currentLanguage === 'am'
        ? 'ከአውሮፕላን ማረፊያ ነጻ የትራንስፖርት አገልግሎት።'
        : 'Complimentary airport pickup and shuttle services available upon request.',
    color: 'text-indigo-500 bg-indigo-500/10 border-indigo-500/20',
  },
  {
    id: 8,
    icon: ConciergeBell,
    title: languageStore.t('concierge_service', '24/7 Personal Concierge'),
    description:
      languageStore.currentLanguage === 'am'
        ? 'ጣፋጭ ምግቦችን እና አገልግሎቶችን በማንኛውም ሰዓት በQR ኮድ ይዘዙ።'
        : 'Order delicious meals and guest services anytime directly through QR room menu.',
    color: 'text-amber-500 bg-amber-500/10 border-amber-500/20',
  },
])
</script>

<template>
  <section
    class="bg-white dark:bg-slate-900 py-12 sm:py-16 md:py-20 lg:py-24 border-b border-slate-200 dark:border-slate-800 transition-colors duration-300 font-sans"
  >
    <div class="mx-auto max-w-7xl px-4 sm:px-6 md:px-8 lg:px-10 space-y-12">
      <!-- Section Header -->
      <div class="mx-auto max-w-3xl text-center space-y-3">
        <span
          class="px-3.5 py-1 rounded-full text-xs font-black uppercase tracking-wider bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20"
        >
          {{ guestHotelStore.hotelName }} •
          {{ languageStore.t('curated_comfort', 'Curated Comfort') }}
        </span>

        <h2
          class="text-3xl sm:text-4xl lg:text-5xl font-black text-slate-900 dark:text-white tracking-tight"
        >
          {{ languageStore.t('world_class_facilities', 'World-Class Hotel Facilities') }}
        </h2>

        <p class="text-sm sm:text-base text-slate-600 dark:text-slate-400 font-medium">
          {{
            languageStore.t(
              'facilities_desc',
              'Every facility at ' +
                guestHotelStore.hotelName +
                ' has been thoughtfully designed to provide comfort, relaxation, and unforgettable luxury experiences.',
            )
          }}
        </p>
      </div>

      <!-- Facilities Grid -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <div
          v-for="facility in facilities"
          :key="facility.id"
          class="group rounded-3xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 p-6 transition-all duration-300 hover:border-amber-500/40 hover:shadow-md space-y-3"
        >
          <div
            :class="[
              'w-12 h-12 rounded-2xl border flex items-center justify-center',
              facility.color,
            ]"
          >
            <component :is="facility.icon" class="w-6 h-6" />
          </div>

          <h3
            class="text-base font-black text-slate-900 dark:text-white group-hover:text-amber-500 transition"
          >
            {{ facility.title }}
          </h3>

          <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed font-medium">
            {{ facility.description }}
          </p>
        </div>
      </div>
    </div>
  </section>
</template>
