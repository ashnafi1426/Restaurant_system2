<script setup lang="ts">
import { onMounted } from 'vue'
import GuestNavbar from '@/components/guest/guestNavbar.vue'
import Footer from '@/components/guest/Footer.vue'
import { useThemeStore } from '@/stores/themeStore'

const theme = useThemeStore()

onMounted(() => {
  theme.initializeTheme()
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
