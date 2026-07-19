<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useRoomStore } from '@/stores/room'
import type { Room } from '@/types/room'

const router = useRouter()
const roomStore = useRoomStore()
const loading = ref(false)
const error = ref<string | null>(null)
const rooms = computed(() => roomStore.rooms)
const featuredRooms = computed(() => {
  if (!Array.isArray(rooms.value)) {
    console.warn('❌ [FeaturedRoom] rooms is not an array:', rooms.value)
    return []
  }

  // Get first 3 rooms that are available and active
  const filtered = rooms.value
    .filter((room: Room) => {
      const isActive = room.is_active !== false
      return isActive
    })
    .slice(0, 3)
  
  // Log data for debugging
  if (filtered.length > 0) {
    console.log('✅ [FeaturedRoom] Featured rooms loaded:', filtered)
    filtered.forEach((room: Room, idx: number) => {
      console.log(`Room ${idx + 1}:`, {
        id: room.id,
        number: room.room_number,
        status: room.status,
        price: room.room_type?.base_price_per_night,
        type: room.room_type?.name,
      })
    })
  }
  
  return filtered
})

/*
|--------------------------------------------------------------------------
| Helpers - Property Extraction
|--------------------------------------------------------------------------
*/
/**
 * Extracts room name with fallback chain
 */
function getRoomName(room: Room): string {
  if (!room) return 'Room'
  
  // Try room_name first
  if (room.room_number) {
    return `Room ${room.room_number}`
  }
  
  // Fallback to generic
  return 'Luxury Suite'
}

/**
 * Extracts room price with proper fallback
 */
function getRoomPrice(room: Room): number {
  if (!room) return 0
  
  // Price comes from room_type.base_price_per_night
  if (room.room_type && typeof room.room_type === 'object') {
    const price = room.room_type.base_price_per_night
    if (price) {
      return Math.round(parseFloat(String(price)))
    }
  }
  
  return 299 // Default fallback
}

/**
 * Extracts room type/category
 */
function getRoomType(room: Room): string {
  if (!room) return 'Standard'
  
  // Get room type name - room_type is an object with name property
  if (room.room_type && typeof room.room_type === 'object') {
    if (room.room_type.name) {
      return room.room_type.name
    }
  }
  
  // Fallback
  return 'Standard Room'
}

/**
 * Extracts room capacity
 */
function getRoomCapacity(room: Room): number {
  if (!room) return 2
  
  // Capacity is in room_type object
  if (room.room_type && typeof room.room_type === 'object') {
    if (room.room_type.capacity) {
      return room.room_type.capacity
    }
  }
  
  return 2 // Default fallback
}

/**
 * Extracts room description
 */
function getRoomDescription(room: Room): string {
  if (!room) return 'Comfortable and well-appointed room'
  
  // Try description from room_type first
  if (room.room_type && typeof room.room_type === 'object') {
    if (room.room_type.description) {
      return room.room_type.description
    }
  }
  
  // Try room description
  if (room.description) {
    return room.description
  }
  
  return 'Discover luxury comfort in our carefully designed room'
}

/**
 * Gets image URL based on room type - matches RoomGrid pattern
 */
function getRoomImage(room: Room): string {
  if (!room) return '/images/rooms/deluxe.jpg'
  
  // Get room type name safely
  const roomType = getRoomType(room).toLowerCase()
  
  const imageMap: Record<string, string> = {
    deluxe: '/images/rooms/deluxe.jpg',
    vip: '/images/rooms/suite.jpg',
    wvip: '/images/rooms/suite.jpg',
    suite: '/images/rooms/suite.jpg',
    'twin small': '/images/rooms/family.jpg',
    'twin big': '/images/rooms/family.jpg',
    standard: '/images/rooms/deluxe.jpg',
  }

  // Try to match the room type with image map
  for (const [key, value] of Object.entries(imageMap)) {
    if (roomType.includes(key)) {
      return value
    }
  }

  return '/images/rooms/deluxe.jpg'
}

/**
 * Gets room status - defaults to available if not set
 */
function getRoomStatus(room: Room): string {
  if (!room) return 'available'
  
  // Status can be: available, occupied, reserved, maintenance
  return room.status || 'available'
}

/**
 * Gets status badge styling
 */
function getStatusBadgeClass(status: string): string {
  const statusMap: Record<string, string> = {
    available: 'bg-green-500 text-white',
    occupied: 'bg-red-500 text-white',
    reserved: 'bg-yellow-500 text-white',
    maintenance: 'bg-gray-500 text-white',
  }
  
  return statusMap[status] || 'bg-green-500 text-white'
}

/**
 * Gets room rating (default 4.5)
 */
function getRoomRating(room: Room): number {
  // For now, return a default rating since it's not in the model
  // You can update this if you add rating to the database later
  return 4.5
}

/*
|--------------------------------------------------------------------------
| Formatting Helpers
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

/*
|--------------------------------------------------------------------------
| Navigation
|--------------------------------------------------------------------------
*/

function viewRoom(room: Room) {
  if (room && room.id) {
    router.push(`/rooms/${room.id}`)
  }
}

function reserveRoom(room: Room) {
  if (room && room.id) {
    router.push({
      path: '/reservation',
      query: {
        room: room.id,
      },
    })
  }
}

function viewAllRooms() {
  router.push('/rooms')
}
async function loadRooms() {
  loading.value = true
  error.value = null

  try {
    console.log('🔄 [FeaturedRoom] Loading featured rooms...')
    await roomStore.fetchRooms({ per_page: 10 })
    console.log('✅ [FeaturedRoom] Rooms loaded successfully:', rooms.value)
    
    if (rooms.value.length === 0) {
      console.warn('⚠️ [FeaturedRoom] No rooms returned from API')
    }
  } catch (err: any) {
    console.error('❌ [FeaturedRoom] Failed to load featured rooms:', err)
    error.value = err.message || 'Failed to load rooms. Please try again.'
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  // Load rooms on component mount
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

      <!-- Error Message -->
      <div v-if="error" class="mb-6 rounded-lg bg-red-50 p-4 border border-red-200">
        <p class="text-sm text-red-700">{{ error }}</p>
        <button
          @click="loadRooms"
          class="mt-2 text-sm font-semibold text-red-600 hover:text-red-800 underline"
        >
          Retry
        </button>
      </div>

      <!-- Loading -->
      <div v-if="loading" class="flex justify-center py-12 sm:py-16 md:py-24">
        <div
          class="h-12 sm:h-14 w-12 sm:w-14 animate-spin rounded-full border-4 border-amber-500 border-t-transparent"
        />
      </div>

      <!-- Empty State -->
      <div
        v-if="!loading && !error && featuredRooms.length === 0"
        class="flex flex-col items-center justify-center py-12 sm:py-16 md:py-24"
      >
        <div class="rounded-full bg-slate-100 p-4 mb-4">
          <svg
            class="h-8 w-8 text-slate-400"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M3 12a9 9 0 010-18 9 9 0 010 18z"
            />
          </svg>
        </div>
        <p class="text-lg text-slate-600">No rooms available at the moment</p>
        <p class="text-sm text-slate-500">Please check back later or view all rooms</p>
      </div>

      <!-- Room Cards -->
      <div
        v-if="!loading && !error && featuredRooms.length > 0"
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
              :src="getRoomImage(room)"
              :alt="getRoomName(room)"
              class="h-48 sm:h-56 md:h-64 lg:h-80 w-full object-cover transition duration-700 group-hover:scale-110"
              @error="(e: any) => (e.target.src = '/images/placeholder.png')"
            />
            <div
              class="absolute right-3 sm:right-5 top-3 sm:top-5 rounded-full bg-white px-3 sm:px-5 py-1.5 sm:py-2 shadow-lg"
            >
              <span class="text-sm sm:text-lg font-bold text-amber-600">
                {{ formatPrice(getRoomPrice(room)) }}
              </span>
              <div class="text-xs uppercase tracking-wider text-slate-500">per night</div>
            </div>

            <!-- Availability Badge -->
            <div
              class="absolute left-3 sm:left-5 top-3 sm:top-5 rounded-full px-3 sm:px-4 py-1 sm:py-2 text-xs font-semibold uppercase tracking-wider text-white"
              :class="getStatusBadgeClass(getRoomStatus(room))"
            >
              {{ getRoomStatus(room) }}
            </div>
          </div>

          <!-- Content -->
          <div class="p-4 sm:p-6 md:p-8">
            <!-- Rating -->
            <div class="mb-2 sm:mb-3 flex items-center justify-between">
              <span class="text-amber-500 text-lg sm:text-xl">
                {{ ratingStars(getRoomRating(room)) }}
              </span>
              <span class="text-xs sm:text-sm text-slate-500">
                {{ getRoomRating(room) }}/5
              </span>
            </div>

            <!-- Room Name -->
            <h3 class="text-lg sm:text-xl md:text-2xl font-semibold text-slate-900">
              {{ getRoomName(room) }}
            </h3>

            <!-- Type -->
            <p
              class="mt-1 sm:mt-2 text-xs sm:text-sm uppercase tracking-[2px] sm:tracking-[4px] text-amber-600"
            >
              {{ getRoomType(room) }}
            </p>

            <!-- Description -->
            <p
              class="mt-3 sm:mt-4 md:mt-5 leading-6 sm:leading-7 text-xs sm:text-sm md:text-base text-slate-500"
            >
              {{ getRoomDescription(room) }}
            </p>

            <!-- Amenities -->
            <div
              class="mt-6 sm:mt-8 grid grid-cols-2 gap-2 sm:gap-4 border-y border-slate-200 py-4 sm:py-6"
            >
              <div class="flex items-center gap-2">
                <span class="text-lg sm:text-xl">👥</span>
                <span class="text-xs sm:text-sm text-slate-600">
                  {{ getRoomCapacity(room) }} Guests
                </span>
              </div>

              <div class="flex items-center gap-2">
                <span class="text-lg sm:text-xl">🛏️</span>
                <span class="text-xs sm:text-sm text-slate-600">
                  Double Bed
                </span>
              </div>

              <div class="flex items-center gap-2">
                <span class="text-lg sm:text-xl">📐</span>
                <span class="text-xs sm:text-sm text-slate-600">
                  Spacious
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
