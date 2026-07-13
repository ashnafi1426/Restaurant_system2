<script setup lang="ts">
import { ref } from 'vue'

interface GalleryImage {
  id: number
  src: string
  title: string
  category: string
}

const galleryImages: GalleryImage[] = [
  // === ROOMS CATEGORY ===
  {
    id: 1,
    src: 'https://images.unsplash.com/photo-1631049307264-da0ec9d70304?w=1000&h=800&fit=crop',
    title: 'Luxury Suite Room',
    category: 'Rooms',
  },
  {
    id: 2,
    src: 'https://images.unsplash.com/photo-1593642632823-8f785ba67e45?w=1000&h=800&fit=crop',
    title: 'Deluxe Double Room',
    category: 'Rooms',
  },
  {
    id: 3,
    src: 'https://images.unsplash.com/photo-1631049307264-da0ec9d70304?w=1000&h=800&fit=crop',
    title: 'Executive Master Suite',
    category: 'Rooms',
  },

  // === FACILITIES CATEGORY ===
  {
    id: 4,
    src: 'https://images.unsplash.com/photo-1576610616656-d3aa5d1f4fabc?w=1000&h=800&fit=crop',
    title: 'Olympic Swimming Pool',
    category: 'Facilities',
  },
  {
    id: 5,
    src: 'https://images.unsplash.com/photo-1568084308-94de50617011?w=1000&h=800&fit=crop',
    title: 'Spa & Wellness Center',
    category: 'Facilities',
  },
  {
    id: 6,
    src: 'https://images.unsplash.com/photo-1599073990936-e04d4b647a11?w=1000&h=800&fit=crop',
    title: 'Elegant Lobby',
    category: 'Facilities',
  },
  {
    id: 7,
    src: 'https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?w=1000&h=800&fit=crop',
    title: 'Conference Hall',
    category: 'Facilities',
  },
  {
    id: 8,
    src: 'https://images.unsplash.com/photo-1590080876/8a208e8d2e3c?w=1000&h=800&fit=crop',
    title: 'Fitness Center',
    category: 'Facilities',
  },

  // === RESTAURANT CATEGORY ===
  {
    id: 9,
    src: 'https://images.unsplash.com/photo-1504674900769-8a8a3f3d7131?w=1000&h=800&fit=crop',
    title: 'Fine Dining Restaurant',
    category: 'Restaurant',
  },
  {
    id: 10,
    src: 'https://images.unsplash.com/photo-1517248135467-4d71bcdd2d59?w=1000&h=800&fit=crop',
    title: 'Elegant Restaurant Interior',
    category: 'Restaurant',
  },

  // === OUTDOOR CATEGORY ===
  {
    id: 11,
    src: 'https://images.unsplash.com/photo-1552462881-23dde8faf51f?w=1000&h=800&fit=crop',
    title: 'Beautiful Landscape Garden',
    category: 'Outdoor',
  },
  {
    id: 12,
    src: 'https://images.unsplash.com/photo-1519904981063-b0cf448d479e?w=1000&h=800&fit=crop',
    title: 'Outdoor Terrace Seating',
    category: 'Outdoor',
  },
]

const selectedImage = ref<GalleryImage | null>(null)

function openImage(image: GalleryImage) {
  selectedImage.value = image
}

function closeImage() {
  selectedImage.value = null
}
</script>

<template>
  <section class="bg-white py-12 sm:py-16 md:py-20 lg:py-24">
    <div class="mx-auto w-full max-w-7xl px-4 sm:px-6 md:px-8 lg:px-10">
      <!-- Header -->
      <div class="mx-auto mb-8 sm:mb-12 md:mb-16 max-w-3xl text-center">
        <p
          class="mb-2 sm:mb-4 text-xs sm:text-sm uppercase tracking-[0.2em] sm:tracking-[0.3em] text-amber-600"
        >
          Gallery
        </p>
        <h2 class="text-2xl sm:text-3xl md:text-4xl lg:text-5xl font-light text-slate-900">
          Explore Our Facilities
        </h2>
        <p
          class="mt-3 sm:mt-4 md:mt-6 text-sm sm:text-base md:text-lg leading-6 sm:leading-7 md:leading-8 text-slate-500"
        >
          Discover the beauty and elegance of our hotel through our carefully curated gallery of
          rooms, facilities, and dining experiences.
        </p>
      </div>

      <!-- Gallery Grid -->
      <div class="grid gap-3 sm:gap-4 md:gap-6 grid-cols-1 sm:grid-cols-2 lg:grid-cols-3">
        <div
          v-for="image in galleryImages"
          :key="image.id"
          class="group relative overflow-hidden rounded-lg sm:rounded-2xl cursor-pointer"
          @click="openImage(image)"
        >
          <img
            :src="image.src"
            :alt="image.title"
            class="h-48 sm:h-56 md:h-72 w-full object-cover transition duration-500 group-hover:scale-110"
          />

          <!-- Overlay -->
          <div
            class="absolute inset-0 bg-black/40 opacity-0 transition duration-300 group-hover:opacity-100 flex items-center justify-center"
          >
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

    <!-- Lightbox Modal -->
    <Teleport to="body">
      <Transition
        enter-active-class="transition duration-300"
        leave-active-class="transition duration-200"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
      >
        <div
          v-if="selectedImage"
          class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/90 p-6"
          @click.self="closeImage"
        >
          <!-- Close Button -->
          <button
            @click="closeImage"
            class="absolute right-8 top-8 flex h-12 w-12 items-center justify-center rounded-full bg-white text-2xl font-bold text-slate-900 shadow-xl transition hover:rotate-90 hover:bg-red-500 hover:text-white"
          >
            ×
          </button>

          <!-- Image Container -->
          <div class="mx-auto w-full max-w-4xl">
            <img
              :src="selectedImage.src"
              :alt="selectedImage.title"
              class="mx-auto max-h-[80vh] w-auto rounded-3xl shadow-2xl"
            />

            <!-- Information -->
            <div class="mt-8 rounded-3xl bg-white p-8 shadow-2xl">
              <div class="flex flex-wrap items-center justify-between gap-6">
                <div>
                  <span
                    class="rounded-full bg-amber-500 px-4 py-2 text-sm font-semibold text-white"
                  >
                    {{ selectedImage.category }}
                  </span>

                  <h2 class="mt-5 text-4xl font-bold text-slate-900">
                    {{ selectedImage.title }}
                  </h2>

                  <p class="mt-3 text-slate-500">Grand Horizon Hotel Gallery Collection</p>
                </div>

                <button
                  @click="closeImage"
                  class="rounded-xl bg-amber-500 px-8 py-4 font-semibold text-white transition hover:bg-amber-600"
                >
                  Close Gallery
                </button>
              </div>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>
  </section>
</template>
