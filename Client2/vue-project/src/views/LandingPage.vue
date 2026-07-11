<template>
  <div class="landing-page">
    <LandingNavbar />
    <HeroSection />
    <ArtOfRestSection />
    <CuratedComfortSection />
    <GrandeurGallerySection />
    <HeritageKitchenSection />
    <TestimonialsSection />
    <ContactSection />
    <Footer />
  </div>
</template>

<script setup lang="ts">
import LandingNavbar from '@/components/landing/LandingNavbar.vue'
import HeroSection from '@/components/landing/HeroSection.vue'
import ArtOfRestSection from '@/components/landing/ArtOfRestSection.vue'
import CuratedComfortSection from '@/components/landing/CuratedComfortSection.vue'
import GrandeurGallerySection from '@/components/landing/GrandeurGallerySection.vue'
import HeritageKitchenSection from '@/components/landing/HeritageKitchenSection.vue'
import TestimonialsSection from '@/components/landing/TestimonialsSection.vue'
import ContactSection from '@/components/landing/ContactSection.vue'
import Footer from '@/components/landing/Footer.vue'
</script>

<style scoped>
.landing-page {
  width: 100%;
  min-height: 100vh;
  overflow-x: hidden;
}
</style>
