<script setup lang="ts">
import { onMounted } from 'vue'
import GuestNavbar from '@/components/guest/GuestNavbar.vue'
import Footer from '@/components/guest/Footer.vue'
import { useThemeStore } from '@/stores/themeStore'
import { useGuestHotelStore } from '@/stores/guestHotelStore'

const theme = useThemeStore()
const guestHotelStore = useGuestHotelStore()

onMounted(() => {
  theme.initializeTheme()
  if (guestHotelStore.availableHotels.length === 0) {
    guestHotelStore.fetchAvailableHotels()
  }
})
</script>

<template>
  <div class="min-h-screen bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white transition-colors duration-300 font-sans">
    <GuestNavbar />
    <main class="min-h-screen">
      <slot />
    </main>
    <Footer />
  </div>
</template>
