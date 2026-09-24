<script setup lang="ts">
import HeroSection from '@/components/guest/HeroSection.vue'
import FeaturedRooms from '@/components/guest/FeaturedRoom.vue'
import HotelFacilitaties from '@/components/guest/HotelFacilitaties.vue'
import RestaurantSection from '@/components/guest/RestaurantSection.vue'
import Testimonial from '@/components/guest/Testimonial.vue'
import ContactSection from '@/components/landing/ContactSection.vue'
import GuestLayout from '@/Layouts/GuestLayout.vue'
</script>

<template>
  <GuestLayout>
    <div class="bg-slate-50 dark:bg-slate-950 min-h-screen text-slate-900 dark:text-white transition-colors duration-300">
      <HeroSection />
      <FeaturedRooms />
      <HotelFacilitaties />
      <RestaurantSection />
      <Testimonial />
      <ContactSection />
    </div>
  </GuestLayout>
</template>
