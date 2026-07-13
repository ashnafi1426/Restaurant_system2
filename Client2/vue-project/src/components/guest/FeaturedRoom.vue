<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useRoomStore } from '@/stores/room'

/*
|--------------------------------------------------------------------------
| Router & Store
|--------------------------------------------------------------------------
*/

const router = useRouter()
const roomStore = useRoomStore()

/*
|--------------------------------------------------------------------------
| State
|--------------------------------------------------------------------------
*/

const loading = ref(false)

/*
|--------------------------------------------------------------------------
| Computed - Get featured rooms from store
|--------------------------------------------------------------------------
*/

const rooms = computed(() => roomStore.rooms)

const featuredRooms = computed(() => {
  // Get first 3 rooms that are available
  return rooms.value
    .filter((room: any) => {
      // Check availability status
      const status = room.status || 'available'
      return status === 'available'
    })
    .slice(0, 3)
})

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

function formatPrice(price: number): string {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
    maximumFractionDigits: 0,
  }).format(price)
}

function ratingStars(rating: number): string {
  return '★'.repeat(Math.round(rating))
}

// Safely get room properties from various formats
function getRoomProperty(room: any, property: string): any {
  const propertyMap: Record<string, string[]> = {
    name: ['room_name', 'name', 'room_number'],
    price: ['price_per_night', 'price', 'base_price_per_night'],
    type: ['room_type', 'type'],
    capacity: ['capacity'],
    bed_type: ['bed_type'],
    size: ['size'],
    image: ['image'],
    rating: ['rating'],
    description: ['description'],
  }

  const fields = propertyMap[property] || [property]
  for (const field of fields) {
    if (room[field] !== undefined && room[field] !== null) {
      return room[field]
    }
  }
  return null
}

/*
|--------------------------------------------------------------------------
| Navigation
|--------------------------------------------------------------------------
*/

function viewRoom(room: any) {
  router.push(`/rooms/${room.id}`)
}

function reserveRoom(room: any) {
  router.push({
    path: '/reservation',
    query: {
      room: room.id,
    },
  })
}

function viewAllRooms() {
  router.push('/rooms')
}

/*
|--------------------------------------------------------------------------
| Load Rooms - Fetch from backend via store
|--------------------------------------------------------------------------
*/

async function loadRooms() {
  loading.value = true

  try {
    await roomStore.fetchRooms({ per_page: 10 })
  } catch (error) {
    console.error('Failed to load featured rooms:', error)
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  // Only load if rooms not already loaded
  if (rooms.value.length === 0) {
    loadRooms()
  }
})
</script>
<template>
  <section class="bg-[#f8f5f0] py-12 sm:py-16 md:py-20 lg:py-24">
    <div class="mx-auto w-full max-w-7xl px-4 sm:px-6 md:px-8 lg:px-10">
      <!-- Section Header -->
      <div class="mx-auto mb-8 sm:mb-12 md:mb-16 max-w-3xl text-center">
        <p
          class="mb-2 sm:mb-4 text-xs sm:text-sm font-semibold uppercase tracking-[0.2em] sm:tracking-[0.3em] text-amber-600"
        >
          Luxury Accommodation
        </p>

        <h2 class="text-2xl sm:text-3xl md:text-4xl lg:text-5xl font-light text-slate-900">
          The Art of Rest
        </h2>

        <p
          class="mt-3 sm:mt-4 md:mt-6 text-sm sm:text-base md:text-lg leading-6 sm:leading-7 md:leading-8 text-slate-500"
        >
          Discover beautifully designed rooms that combine modern luxury, exceptional comfort and
          unforgettable hospitality.
        </p>
      </div>

      <!-- Loading -->
      <div v-if="loading" class="flex justify-center py-12 sm:py-16 md:py-24">
        <div
          class="h-12 sm:h-14 w-12 sm:w-14 animate-spin rounded-full border-4 border-amber-500 border-t-transparent"
        />
      </div>

      <!-- Room Cards -->
      <div
        v-if="!loading && featuredRooms.length > 0"
        class="grid gap-6 sm:gap-8 md:gap-10 grid-cols-1 sm:grid-cols-2 lg:grid-cols-3"
      >
        <article
          v-for="room in featuredRooms"
          :key="room.id"
          class="group overflow-hidden rounded-2xl sm:rounded-3xl bg-white shadow transition-all duration-500 hover:-translate-y-2 sm:hover:-translate-y-3 hover:shadow-xl md:hover:shadow-2xl"
        >
          <!-- Room Image -->
          <div class="relative overflow-hidden">
            <img
              :src="getRoomProperty(room, 'image') || '/images/placeholder.png'"
              :alt="getRoomProperty(room, 'name')"
              class="h-48 sm:h-56 md:h-64 lg:h-80 w-full object-cover transition duration-700 group-hover:scale-110"
            />

            <!-- Price Badge -->
            <div
              class="absolute right-3 sm:right-5 top-3 sm:top-5 rounded-full bg-white px-3 sm:px-5 py-1.5 sm:py-2 shadow-lg"
            >
              <span class="text-sm sm:text-lg font-bold text-amber-600">
                {{ formatPrice(getRoomProperty(room, 'price') || 0) }}
              </span>
              <div class="text-xs uppercase tracking-wider text-slate-500">per night</div>
            </div>

            <!-- Availability Badge -->
            <div
              class="absolute left-3 sm:left-5 top-3 sm:top-5 rounded-full bg-green-500 px-3 sm:px-4 py-1 sm:py-2 text-xs font-semibold uppercase tracking-wider text-white"
            >
              Available
            </div>
          </div>

          <!-- Content -->
          <div class="p-4 sm:p-6 md:p-8">
            <!-- Rating -->
            <div class="mb-2 sm:mb-3 flex items-center justify-between">
              <span class="text-amber-500 text-lg sm:text-xl">
                {{ ratingStars(getRoomProperty(room, 'rating') || 4.5) }}
              </span>
              <span class="text-xs sm:text-sm text-slate-500">
                {{ getRoomProperty(room, 'rating') || 4.5 }}/5
              </span>
            </div>

            <!-- Room Name -->
            <h3 class="text-lg sm:text-xl md:text-2xl font-semibold text-slate-900">
              {{ getRoomProperty(room, 'name') || `Room ${room.room_number}` }}
            </h3>

            <!-- Type -->
            <p
              class="mt-1 sm:mt-2 text-xs sm:text-sm uppercase tracking-[2px] sm:tracking-[4px] text-amber-600"
            >
              {{ getRoomProperty(room, 'type') || 'Standard' }}
            </p>

            <!-- Description -->
            <p
              class="mt-3 sm:mt-4 md:mt-5 leading-6 sm:leading-7 text-xs sm:text-sm md:text-base text-slate-500"
            >
              {{ getRoomProperty(room, 'description') || 'Comfortable and well-appointed room' }}
            </p>

            <!-- Amenities -->
            <div
              class="mt-6 sm:mt-8 grid grid-cols-2 gap-2 sm:gap-4 border-y border-slate-200 py-4 sm:py-6"
            >
              <div class="flex items-center gap-2">
                <span class="text-lg sm:text-xl">👥</span>
                <span class="text-xs sm:text-sm text-slate-600">
                  {{ getRoomProperty(room, 'capacity') || 2 }} Guests
                </span>
              </div>

              <div class="flex items-center gap-2">
                <span class="text-lg sm:text-xl">🛏️</span>
                <span class="text-xs sm:text-sm text-slate-600">
                  {{ getRoomProperty(room, 'bed_type') || 'Bed' }}
                </span>
              </div>

              <div class="flex items-center gap-2">
                <span class="text-lg sm:text-xl">📐</span>
                <span class="text-xs sm:text-sm text-slate-600">
                  {{ getRoomProperty(room, 'size') || 'Standard' }}
                </span>
              </div>

              <div class="flex items-center gap-2">
                <span class="text-lg sm:text-xl">📶</span>
                <span class="text-xs sm:text-sm text-slate-600"> Free WiFi </span>
              </div>
            </div>

            <!-- Buttons -->
            <div class="mt-6 sm:mt-8 flex flex-col sm:flex-row gap-2 sm:gap-4">
              <button
                class="flex-1 rounded-full border border-slate-300 px-3 sm:px-5 py-2 sm:py-3 text-xs sm:text-sm md:text-base font-medium transition hover:border-amber-500 hover:text-amber-600"
                @click="viewRoom(room)"
              >
                View Details
              </button>

              <button
                class="flex-1 rounded-full bg-amber-600 px-3 sm:px-5 py-2 sm:py-3 text-xs sm:text-sm md:text-base font-semibold text-white transition hover:bg-amber-700"
                @click="reserveRoom(room)"
              >
                Book Now
              </button>
            </div>
          </div>
        </article>
      </div>

      <!-- Bottom CTA -->
      <div class="mt-12 sm:mt-16 md:mt-20 text-center">
        <button
          class="rounded-full border border-slate-300 px-6 sm:px-8 md:px-10 py-2.5 sm:py-3 md:py-4 text-xs sm:text-sm md:text-base font-semibold uppercase tracking-wider md:tracking-widest text-slate-800 transition hover:border-amber-600 hover:bg-amber-600 hover:text-white"
          @click="viewAllRooms"
        >
          View All Rooms →
        </button>
      </div>
    </div>
  </section>
</template>
