<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import { X, ZoomIn } from 'lucide-vue-next'
import { useLanguageStore } from '@/stores/language'
import { useGuestHotelStore } from '@/stores/guestHotelStore'
import { useRoomStore } from '@/stores/room'

const languageStore = useLanguageStore()
const guestHotelStore = useGuestHotelStore()
const roomStore = useRoomStore()

interface GalleryImage {
  id: string | number
  src: string
  title: string
  category: string
}

const defaultImages: GalleryImage[] = [
  {
    id: 1,
    src: 'https://images.unsplash.com/photo-1631049307264-da0ec9d70304?w=1000&h=800&fit=crop',
    title: 'Luxury Master Suite',
    category: 'Rooms',
  },
  {
    id: 2,
    src: 'https://images.unsplash.com/photo-1590490360182-c33d57733427?w=1000&h=800&fit=crop',
    title: 'Executive Deluxe Room',
    category: 'Rooms',
  },
  {
    id: 3,
    src: 'https://images.unsplash.com/photo-1582719478250-c89cae4dc85b?w=1000&h=800&fit=crop',
    title: 'Presidential Penthouse',
    category: 'Rooms',
  },
  {
    id: 4,
    src: 'https://images.unsplash.com/photo-1576610616656-d3aa5d1f4fab?w=1000&h=800&fit=crop',
    title: 'Infinity Swimming Pool',
    category: 'Facilities',
  },
  {
    id: 5,
    src: 'https://images.unsplash.com/photo-1540555700478-4be289fbecef?w=1000&h=800&fit=crop',
    title: 'Luxury Spa & Wellness',
    category: 'Facilities',
  },
  {
    id: 6,
    src: 'https://images.unsplash.com/photo-1517248135467-4d71bcdd2d59?w=1000&h=800&fit=crop',
    title: 'Fine Dining Restaurant',
    category: 'Restaurant',
  },
]

const galleryImages = ref<GalleryImage[]>(defaultImages)

async function populateImages() {
  if (roomStore.rooms && roomStore.rooms.length > 0) {
    const roomPhotos: GalleryImage[] = []
    roomStore.rooms.slice(0, 3).forEach((r: any, idx: number) => {
      if (r.images?.[0]) {
        roomPhotos.push({
          id: `hg-${r.id}-${idx}`,
          src: r.images[0],
          title: r.room_type?.name || `Suite #${r.room_number}`,
          category: 'Rooms',
        })
      }
    })
    if (roomPhotos.length > 0) {
      const nonRooms = defaultImages.filter((img) => img.category !== 'Rooms')
      galleryImages.value = [...roomPhotos, ...nonRooms]
      return
    }
  }
  galleryImages.value = defaultImages
}

onMounted(() => {
  populateImages()
})

watch(
  () => [guestHotelStore.hotelId, roomStore.rooms],
  () => {
    populateImages()
  },
)

const categories = ['All', 'Rooms', 'Facilities', 'Restaurant']
const selectedCategory = ref('All')
const selectedImage = ref<GalleryImage | null>(null)

const filteredImages = computed(() => {
  if (selectedCategory.value === 'All') return galleryImages.value
  return galleryImages.value.filter((img) => img.category === selectedCategory.value)
})

function openImage(img: GalleryImage) {
  selectedImage.value = img
}

function closeImage() {
  selectedImage.value = null
}
</script>

<template>
  <section
    class="bg-slate-50 dark:bg-slate-950 py-12 sm:py-16 md:py-20 lg:py-24 transition-colors duration-300"
  >
    <div class="mx-auto w-full max-w-7xl px-4 sm:px-6 md:px-8 lg:px-10 space-y-8">
      <div class="mx-auto max-w-3xl text-center space-y-3">
        <span
          class="px-3.5 py-1 rounded-full text-xs font-black uppercase tracking-wider bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20"
        >
          {{ guestHotelStore.hotelName }} • {{ languageStore.t('visual_tour', 'Visual Tour') }}
        </span>
        <h2
          class="text-3xl sm:text-4xl lg:text-5xl font-black text-slate-900 dark:text-white tracking-tight"
        >
          {{ languageStore.t('hotel_gallery', 'Hotel Gallery') }}
        </h2>
        <p class="text-sm sm:text-base text-slate-600 dark:text-slate-400 font-medium">
          {{
            languageStore.t(
              'hotel_gallery_desc',
              'Take a glimpse into our luxurious rooms, world-class amenities, and exquisite dining spaces at ' +
                guestHotelStore.hotelName +
                '.',
            )
          }}
        </p>
      </div>

      <div class="flex flex-wrap items-center justify-center gap-2">
        <button
          v-for="cat in categories"
          :key="cat"
          @click="selectedCategory = cat"
          :class="[
            'px-4 py-2 rounded-2xl text-xs font-black transition cursor-pointer',
            selectedCategory === cat
              ? 'bg-amber-500 text-slate-950 shadow-md shadow-amber-500/20'
              : 'bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-800 hover:bg-slate-100 dark:hover:bg-slate-800',
          ]"
        >
          {{ languageStore.t(cat, cat) }}
        </button>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        <div
          v-for="img in filteredImages"
          :key="img.id"
          @click="openImage(img)"
          class="group relative overflow-hidden rounded-3xl bg-slate-900 cursor-pointer shadow-xs border border-slate-200 dark:border-slate-800 h-64"
        >
          <img
            :src="img.src"
            :alt="languageStore.t(img.title, img.title)"
            class="h-full w-full object-cover transition duration-500 group-hover:scale-110"
          />
          <div
            class="absolute inset-0 bg-slate-950/60 opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex flex-col items-center justify-center p-4 text-center space-y-2 text-white"
          >
            <ZoomIn class="w-8 h-8 text-amber-400" />
            <h3 class="text-sm font-black">{{ languageStore.t(img.title, img.title) }}</h3>
            <span
              class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-500/20 text-amber-300 border border-amber-500/40 uppercase"
            >
              {{ languageStore.t(img.category, img.category) }}
            </span>
          </div>
        </div>
      </div>
    </div>

    <div
      v-if="selectedImage"
      class="fixed inset-0 z-50 bg-slate-950/90 backdrop-blur-sm flex items-center justify-center p-4"
      @click.self="closeImage"
    >
      <div
        class="relative max-w-4xl w-full bg-slate-900 rounded-3xl overflow-hidden border border-slate-800 shadow-2xl"
      >
        <button
          @click="closeImage"
          class="absolute top-4 right-4 p-2.5 rounded-full bg-slate-800 hover:bg-slate-700 text-white transition cursor-pointer z-10"
        >
          <X class="w-5 h-5" />
        </button>

        <img
          :src="selectedImage.src"
          :alt="languageStore.t(selectedImage.title, selectedImage.title)"
          class="max-h-[75vh] w-full object-cover"
        />

        <div class="p-6 bg-slate-900 text-white flex items-center justify-between">
          <div>
            <h3 class="text-lg font-black">
              {{ languageStore.t(selectedImage.title, selectedImage.title) }}
            </h3>
            <p class="text-xs text-amber-400 font-bold uppercase tracking-wider">
              {{ languageStore.t(selectedImage.category, selectedImage.category) }}
            </p>
          </div>
          <button
            @click="closeImage"
            class="px-4 py-2 rounded-xl bg-amber-500 hover:bg-amber-600 text-slate-950 font-bold text-xs transition cursor-pointer"
          >
            {{ languageStore.t('close', 'Close') }}
          </button>
        </div>
      </div>
    </div>
  </section>
</template>
