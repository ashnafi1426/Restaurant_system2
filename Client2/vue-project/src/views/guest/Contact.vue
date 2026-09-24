<script setup lang="ts">
import ContactSection from '@/components/landing/ContactSection.vue'
import GuestLayout from '@/Layouts/GuestLayout.vue'
</script>

<template>
  <GuestLayout>
    <div class="bg-slate-50 dark:bg-slate-950 min-h-screen text-slate-900 dark:text-white transition-colors duration-300">
      <ContactSection />
    </div>
  </GuestLayout>
</template>
