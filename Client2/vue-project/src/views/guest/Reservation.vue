<script setup lang="ts">
import LandingNavbar from '@/components/landing/LandingNavbar.vue'
import ReservationForm from '@/components/reservation/ReservationForm.vue'
import Footer from '@/components/landing/Footer.vue'
</script>

<template>
  <div class="bg-white">
    <LandingNavbar />
    <div class="py-16 px-6">
      <div class="mx-auto max-w-4xl">
        <h1 class="text-4xl font-bold text-center mb-12">Book Your Stay</h1>
        <ReservationForm />
      </div>
    </div>
    <Footer />
  </div>
</template>