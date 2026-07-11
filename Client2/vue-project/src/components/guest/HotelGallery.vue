<script setup lang="ts">
interface GalleryImage {
  id: number
  src: string
  title: string
  category: string
}

const galleryImages: GalleryImage[] = [
  {
    id: 1,
    src: '/images/gallery/gallery1.jpg',
    title: 'Main Lobby',
    category: 'Facilities'
  },
  {
    id: 2,
    src: '/images/gallery/gallery2.jpg',
    title: 'Suite Room',
    category: 'Rooms'
  },
  {
    id: 3,
    src: '/images/gallery/gallery3.jpg',
    title: 'Fine Dining',
    category: 'Restaurant'
  },
  {
    id: 4,
    src: '/images/gallery/gallery4.jpg',
    title: 'Swimming Pool',
    category: 'Facilities'
  },
  {
    id: 5,
    src: '/images/gallery/gallery5.jpg',
    title: 'Spa & Wellness',
    category: 'Facilities'
  },
  {
    id: 6,
    src: '/images/gallery/gallery6.jpg',
    title: 'Garden View',
    category: 'Outdoor'
  }
]

const selectedImage = ref<GalleryImage | null>(null)

import { ref } from 'vue'
</script>

<template>
  <section class="bg-white py-12 sm:py-16 md:py-20 lg:py-24">
    <div class="mx-auto w-full max-w-7xl px-4 sm:px-6 md:px-8 lg:px-10">

      <!-- Header -->
      <div class="mx-auto mb-8 sm:mb-12 md:mb-16 max-w-3xl text-center">
        <p class="mb-2 sm:mb-4 text-xs sm:text-sm uppercase tracking-[0.2em] sm:tracking-[0.3em] text-amber-600">
          Gallery
        </p>
        <h2 class="text-2xl sm:text-3xl md:text-4xl lg:text-5xl font-light text-slate-900">
          Explore Our Facilities
        </h2>
        <p class="mt-3 sm:mt-4 md:mt-6 text-sm sm:text-base md:text-lg leading-6 sm:leading-7 md:leading-8 text-slate-500">
          Discover the beauty and elegance of our hotel through our carefully curated gallery
          of rooms, facilities, and dining experiences.
        </p>
      </div>

      <!-- Gallery Grid -->
      <div class="grid gap-3 sm:gap-4 md:gap-6 grid-cols-1 sm:grid-cols-2 lg:grid-cols-3">
        <div
          v-for="image in galleryImages"
          :key="image.id"
          class="group relative overflow-hidden rounded-lg sm:rounded-2xl"
        >
          <img
            :src="image.src"
            :alt="image.title"
            class="h-48 sm:h-56 md:h-72 w-full object-cover transition duration-500 group-hover:scale-110"
          >

          <!-- Overlay -->
          <div class="absolute inset-0 bg-black/40 opacity-0 transition duration-300 group-hover:opacity-100 flex items-center justify-center">
            <div class="text-center text-white px-4">
              <h3 class="text-lg sm:text-xl md:text-2xl font-semibold">
                {{ image.title }}
              </h3>
              <p class="mt-1 sm:mt-2 text-xs sm:text-sm md:text-base text-amber-400">
                {{ image.category }}
              </p>
            </div>
          </div>
        </div>
      </div>

    </div>
  </section>
</template>
