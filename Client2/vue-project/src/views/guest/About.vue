<script setup lang="ts">
import AboutHero from '@/components/guest/AboutHero.vue'
import HotelHistory from '@/components/guest/HotelHistory.vue'
import HotelStatistics from '@/components/guest/HotelStatistics.vue'
import HotelGallery from '@/components/guest/HotelGallery.vue'
import MeetOurTeam from '@/components/guest/MeetOurTeam.vue'
import AwardsSection from '@/components/guest/AwardsSection.vue'
import WhyChooseUs from '@/components/guest/WhyChooseUs.vue'
import GuestLayout from '@/Layouts/GuestLayout.vue'
</script>

<template>
  <GuestLayout>
    <div class="bg-slate-50 dark:bg-slate-950 min-h-screen text-slate-900 dark:text-white transition-colors duration-300 font-sans">
      <AboutHero />
      <HotelHistory />
      <HotelStatistics />
      <HotelGallery />
      <MeetOurTeam />
      <AwardsSection />
      <WhyChooseUs />
    </div>
  </GuestLayout>
</template>
