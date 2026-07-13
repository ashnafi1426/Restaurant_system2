<script setup lang="ts">
import { ref } from 'vue'
import GuestLayout from '@/Layouts/GuestLayout.vue'
import GalleryHero from '@/components/guest/GalleryHero.vue'
import GalleryCategories from '@/components/guest/GalleryCategories.vue'
import GalleryGrid from '@/components/guest/GalleryGrid.vue'

const selectedCategory = ref('All')

const galleryItems = ref([
  // === ROOMS CATEGORY ===
  {
    id: 1,
    title: 'Luxury Suite Room',
    category: 'Rooms',
    src: '/images/gallery/luxury-suite.jpg',
  },
  {
    id: 2,
    title: 'Deluxe Double Room',
    category: 'Rooms',
    src: '/images/gallery/deluxe-double.jpg',
  },
  {
    id: 3,
    title: 'Executive Master Suite',
    category: 'Rooms',
    src: '/images/gallery/executive-suite.jpg',
  },

  // === FACILITIES CATEGORY ===
  {
    id: 4,
    title: 'Olympic Swimming Pool',
    category: 'Facilities',
    src: '/images/gallery/swimming-pool.jpg',
  },
  {
    id: 5,
    title: 'Spa & Wellness Center',
    category: 'Facilities',
    src: '/images/gallery/spa-center.jpg',
  },
  {
    id: 6,
    title: 'Elegant Lobby',
    category: 'Facilities',
    src: '/images/gallery/elegant-lobby.jpg',
  },
  {
    id: 7,
    title: 'Conference Hall',
    category: 'Facilities',
    src: '/images/gallery/conference-hall.jpg',
  },
  {
    id: 8,
    title: 'Fitness Center',
    category: 'Facilities',
    src: '/images/gallery/fitness-center.jpg',
  },

  // === RESTAURANT CATEGORY ===
  {
    id: 9,
    title: 'Fine Dining Restaurant',
    category: 'Restaurant',
    src: '/images/gallery/fine-dining.jpg',
  },
  {
    id: 10,
    title: 'Elegant Restaurant Interior',
    category: 'Restaurant',
    src: '/images/gallery/restaurant-interior.jpg',
  },

  // === OUTDOOR CATEGORY ===
  {
    id: 11,
    title: 'Beautiful Landscape Garden',
    category: 'Outdoor',
    src: '/images/gallery/landscape-garden.jpg',
  },
  {
    id: 12,
    title: 'Outdoor Terrace Seating',
    category: 'Outdoor',
    src: '/images/gallery/terrace-seating.jpg',
  },
])
</script>

<template>
  <GuestLayout>
    <GalleryHero />
    <GalleryCategories v-model="selectedCategory" />
    <GalleryGrid :category="selectedCategory" :items="galleryItems" />
  </GuestLayout>
</template>
