<script setup lang="ts">
import { RouterView } from 'vue-router'
import GuestNavbar from '@/components/guest/guestNavbar.vue'
import Footer from '@/components/guest/Footer.vue'
</script>
<template>
  <div class="min-h-screen bg-white text-gray-900">
    <!-- Navigation -->
    <GuestNavbar />
    <!-- Page Content -->
    <main class="min-h-screen">
      <slot />
    </main>

    <!-- Footer -->
    <Footer />
  </div>
</template>
