<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'
import GuestLayout from '@/Layouts/GuestLayout.vue'
import GalleryHero from '@/components/guest/GalleryHero.vue'
import GalleryCategories from '@/components/guest/GalleryCategories.vue'
import GalleryGrid from '@/components/guest/GalleryGrid.vue'
import { useLanguageStore } from '@/stores/language'
import { useGuestHotelStore } from '@/stores/guestHotelStore'
import { useRoomStore } from '@/stores/room'

const languageStore = useLanguageStore()
const guestHotelStore = useGuestHotelStore()
const roomStore = useRoomStore()

const selectedCategory = ref('All')
const galleryItems = ref<any[]>([])
const loading = ref(true)
const error = ref('')

// Default gallery items with rich resort imagery
const defaultGalleryItems = [
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
]

async function loadGallery() {
  loading.value = true
  error.value = ''
  try {
    // Fetch hotel rooms to display actual hotel rooms in gallery
    await roomStore.fetchRooms()
    const hotelRoomImages: any[] = []
    if (roomStore.rooms && roomStore.rooms.length > 0) {
      roomStore.rooms.forEach((room: any, index: number) => {
        if (room.images && Array.isArray(room.images) && room.images.length > 0) {
          room.images.forEach((img: string, imgIdx: number) => {
            hotelRoomImages.push({
              id: `room-${room.id}-${imgIdx}`,
              title: room.room_type?.name || `Room #${room.room_number}`,
              category: 'Rooms',
              src: img,
            })
          })
        }
      })
    }

    if (hotelRoomImages.length > 0) {
      // Merge active hotel room photos with facilities, restaurant, outdoor
      const otherCategories = defaultGalleryItems.filter((item) => item.category !== 'Rooms')
      galleryItems.value = [...hotelRoomImages, ...otherCategories]
    } else {
      galleryItems.value = defaultGalleryItems
    }
  } catch (e) {
    console.warn('[Gallery] Using fallback gallery items', e)
    galleryItems.value = defaultGalleryItems
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  loadGallery()
})

watch(
  () => guestHotelStore.hotelId,
  () => {
    loadGallery()
  },
)
</script>

<template>
  <GuestLayout>
    <GalleryHero />
    <GalleryCategories v-model="selectedCategory" />
    <div v-if="loading" class="flex items-center justify-center py-20">
      <div class="text-center">
        <div class="animate-spin mb-4">
          <svg
            class="w-12 h-12 text-amber-600"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8z"
            />
          </svg>
        </div>
        <p class="text-gray-600">{{ languageStore.t('loading_gallery', 'Loading gallery...') }}</p>
      </div>
    </div>
    <div v-else-if="error" class="flex items-center justify-center py-20">
      <div class="text-center text-red-600">
        <p>{{ error }}</p>
      </div>
    </div>
    <GalleryGrid v-else :category="selectedCategory" :items="galleryItems" />
  </GuestLayout>
</template>
