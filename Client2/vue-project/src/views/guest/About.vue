<script setup lang="ts">
import AboutHero from '@/components/guest/AboutHero.vue'
import HotelHistory from '@/components/guest/HotelHistory.vue'
import MissionVision from '@/components/guest/MissionVision.vue'
import HotelStatistics from '@/components/guest/HotelStatistics.vue'
import HotelGallery from '@/components/guest/HotelGallery.vue'
import MeetOurTeam from '@/components/guest/MeetOurTeam.vue'
import AwardsSection from '@/components/guest/AwardsSection.vue'
import WhyChooseUs from '@/components/guest/WhyChooseUs.vue'
import GuestLayout from '@/layouts/GuestLayout.vue'
</script>

<template>
  <GuestLayout>
    <div class="bg-white">
      <AboutHero />
      <HotelHistory />
      <!-- <MissionVision /> -->
      <HotelStatistics />
      <HotelGallery />
      <MeetOurTeam />
      <AwardsSection />
      <WhyChooseUs />
    </div>
  </GuestLayout>
</template>