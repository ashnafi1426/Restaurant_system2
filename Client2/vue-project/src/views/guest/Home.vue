<script setup lang="ts">
import HeroSection from '@/components/guest/HeroSection.vue'
import FeaturedRooms from '@/components/guest/FeaturedRoom.vue'
import HotelFacilities from '@/components/guest/HotelFacilitaties.vue'
import ExperienceSection from '@/components/guest/ExperienceSection.vue'
import RestaurantSection from '@/components/guest/RestaurantSection.vue'
import Testimonials from '@/components/guest/Testimonial.vue'
import ContactSection from '@/components/guest/ContactSection.vue'
import GuestLayout from '@/layouts/GuestLayout.vue'
</script>

<template>
  <GuestLayout>
    <div class="bg-white">
      <HeroSection />
      <FeaturedRooms />
      <HotelFacilities />
      <ExperienceSection />
      <RestaurantSection />
      <Testimonials />
      <ContactSection />
    </div>
  </GuestLayout>
</template>
