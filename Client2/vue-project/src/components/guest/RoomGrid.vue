<script setup lang="ts">
import { ref, computed } from 'vue'
import BookingModal from './BookingModal.vue'

interface RoomType {
  id?: string | number
  name: string
  description?: string
  base_price_per_night: number
  capacity: number
  amenities?: string[]
  amenities_count?: number
}

interface Room {
  id: string
  room_number?: string
  room_type_id?: number
  room_type?: RoomType | string
  description?: string
  floor?: number
  status?: string
  is_active?: boolean
  created_at?: string
  updated_at?: string
}

const props = defineProps<{
  rooms: Room[]
}>()

const selectedRoom = ref<Room | null>(null)
const showDetails = ref(false)
const showBookingModal = ref(false)
const roomToBook = ref<Room | null>(null)

// Get room type name safely
const getRoomTypeName = (room: Room): string => {
  if (typeof room.room_type === 'string') {
    return room.room_type
  }
  return room.room_type?.name || 'Standard Room'
}

// Get room price safely
const getRoomPrice = (room: Room): number => {
  if (typeof room.room_type === 'object' && room.room_type?.base_price_per_night) {
    return Math.round(room.room_type.base_price_per_night)
  }
  return 0
}

// Get room capacity safely
const getRoomCapacity = (room: Room): number => {
  if (typeof room.room_type === 'object' && room.room_type?.capacity) {
    return room.room_type.capacity
  }
  return 2
}

// Get room amenities safely
const getRoomAmenities = (room: Room): string[] => {
  if (typeof room.room_type === 'object' && room.room_type?.amenities) {
    return room.room_type.amenities
  }
  return []
}

// Get room description
const getRoomDescription = (room: Room): string => {
  if (typeof room.room_type === 'object' && room.room_type?.description) {
    return room.room_type.description
  }
  return room.description || ''
}

// Get image URL based on room type
const getRoomImage = (room: Room): string => {
  const roomType = getRoomTypeName(room).toLowerCase()
  const imageMap: Record<string, string> = {
    deluxe: '/images/rooms/deluxe.jpg',
    vip: '/images/rooms/suite.jpg',
    wvip: '/images/rooms/suite.jpg',
    suite: '/images/rooms/suite.jpg',
    'twin small': '/images/rooms/family.jpg',
    'twin big': '/images/rooms/family.jpg',
    standard: '/images/rooms/deluxe.jpg',
  }

  for (const [key, value] of Object.entries(imageMap)) {
    if (roomType.includes(key)) {
      return value
    }
  }

  return '/images/rooms/deluxe.jpg'
}

function selectRoom(room: Room) {
  selectedRoom.value = room
  showDetails.value = true
}

function openBookingModal(room: Room) {
  roomToBook.value = room
  showBookingModal.value = true
}

function closeBookingModal() {
  showBookingModal.value = false
  roomToBook.value = null
}

function handleBookingSubmit(bookingData: any) {
  console.log('Booking submitted:', bookingData)
  // Handle booking submission if needed
}
</script>

<template>
  <div class="w-full">
    <!-- Section Header -->
    <div class="mb-6 md:mb-8 lg:mb-10">
      <h2
        class="text-xl md:text-2xl lg:text-3xl xl:text-4xl font-bold text-slate-900 leading-tight"
      >
        Available Rooms
      </h2>
      <p class="mt-2 text-slate-600 text-xs md:text-sm lg:text-base">
        {{ rooms.length }} rooms available
      </p>
    </div>

    <!-- Mobile-First Responsive Grid Layout -->
    <!-- Mobile: 1 column, Tablet: 2 columns, Desktop: 3 columns -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3 md:gap-4 lg:gap-6">
      <div
        v-for="room in rooms"
        :key="room.id"
        class="bg-white rounded-lg md:rounded-xl lg:rounded-2xl overflow-hidden shadow-sm md:shadow-md hover:shadow-lg md:hover:shadow-xl transition-all duration-300 hover:scale-105"
      >
        <!-- Image Container - Properly Sized for Mobile -->
        <div class="relative w-full bg-slate-200 aspect-video md:aspect-square">
          <img
            :src="getRoomImage(room)"
            :alt="getRoomTypeName(room)"
            class="w-full h-full object-cover hover:scale-105 transition-transform duration-300"
            loading="lazy"
          />

          <!-- Best Rate Badge - Mobile Optimized -->
          <div
            class="absolute top-1.5 md:top-2 lg:top-3 right-1.5 md:right-2 lg:right-3 bg-slate-800/90 text-white px-1.5 md:px-2.5 lg:px-3 py-0.5 md:py-1 lg:py-1.5 rounded-full text-xs md:text-xs lg:text-sm font-semibold flex items-center gap-0.5 md:gap-1 shadow-md"
          >
            <span class="inline-block w-1 md:w-1.5 h-1 md:h-1.5 bg-yellow-400 rounded-full"></span>
            <span class="hidden sm:inline">BEST RATE</span>
            <span class="sm:hidden">RATE</span>
          </div>

          <!-- Price Badge - Mobile Optimized -->
          <div
            class="absolute bottom-1.5 md:bottom-2 lg:bottom-3 left-1.5 md:left-2 lg:left-3 bg-white/95 text-slate-900 px-1.5 md:px-2.5 lg:px-3 py-1 md:py-1.5 lg:py-2 rounded-md md:rounded-lg lg:rounded-lg font-bold text-xs md:text-xs lg:text-sm shadow-md"
          >
            <div class="text-xs text-slate-600 font-semibold leading-none">RATE</div>
            <div class="text-red-600 text-xs md:text-sm lg:text-base leading-tight">
              ETB {{ getRoomPrice(room) }}
            </div>
          </div>
        </div>

        <!-- Room Info Section - Compact for Mobile -->
        <div class="p-2.5 md:p-3 lg:p-4 space-y-2 md:space-y-2.5 lg:space-y-3">
          <!-- Room Type Title - Properly Sized -->
          <h3
            class="text-base md:text-lg lg:text-xl font-bold text-red-900 leading-snug line-clamp-2"
          >
            {{ getRoomTypeName(room) }}
          </h3>

          <!-- Room Specs - Mobile Readable -->
          <div class="text-xs md:text-xs lg:text-sm text-slate-700 font-medium leading-snug">
            <span>30 M²</span>
            <span class="text-slate-400 mx-1">·</span>
            <span
              >{{ getRoomCapacity(room) }} GUEST{{ getRoomCapacity(room) !== 1 ? 'S' : '' }}</span
            >
          </div>

          <!-- Room Description - Truncated on Mobile -->
          <p
            v-if="getRoomDescription(room)"
            class="text-xs md:text-xs lg:text-sm text-slate-600 leading-snug line-clamp-2"
          >
            {{ getRoomDescription(room) }}
          </p>

          <!-- Default Amenities - Compact Display -->
          <div
            v-if="!getRoomDescription(room)"
            class="text-xs md:text-xs lg:text-sm text-slate-700 space-y-0.5 md:space-y-1"
          >
            <div class="flex items-center gap-1">
              <span class="text-yellow-500">✓</span>
              <span class="truncate">Comfort & Privacy</span>
            </div>
            <div class="flex items-center gap-1">
              <span class="text-yellow-500">✓</span>
              <span class="truncate">Free WiFi</span>
            </div>
          </div>

          <!-- Amenities List - Truncated -->
          <div
            v-else-if="getRoomAmenities(room).length"
            class="text-xs md:text-xs lg:text-sm text-slate-700 space-y-0.5"
          >
            <div
              v-for="(amenity, idx) in getRoomAmenities(room).slice(0, 2)"
              :key="idx"
              class="flex items-center gap-1"
            >
              <span class="text-yellow-500">✓</span>
              <span class="truncate">{{ amenity }}</span>
            </div>
          </div>

          <!-- Action Buttons - Full Width on Mobile -->
          <div class="flex flex-col md:flex-row gap-1.5 md:gap-2 pt-2 md:pt-2.5 lg:pt-3">
            <button
              @click="selectRoom(room)"
              class="flex-1 px-2 md:px-3 lg:px-4 py-1.5 md:py-1.5 lg:py-2 border-2 border-red-600 text-red-600 font-semibold rounded-md md:rounded-lg lg:rounded-lg hover:bg-red-50 transition-all text-xs md:text-xs lg:text-sm leading-tight"
            >
              SPECS
            </button>
            <button
              @click="openBookingModal(room)"
              class="flex-1 px-2 md:px-3 lg:px-4 py-1.5 md:py-1.5 lg:py-2 bg-amber-500 hover:bg-amber-600 text-white font-semibold rounded-md md:rounded-lg lg:rounded-lg transition-all text-xs md:text-xs lg:text-sm text-center leading-tight"
            >
              BOOK
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Details Modal -->
    <div
      v-if="showDetails && selectedRoom"
      class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-3 md:p-4 z-50"
    >
      <div
        class="bg-white rounded-lg md:rounded-xl w-full max-w-sm md:max-w-md lg:max-w-xl p-4 md:p-6 lg:p-8 max-h-[90vh] overflow-y-auto"
      >
        <button
          @click="showDetails = false"
          class="float-right text-xl md:text-2xl text-slate-500 hover:text-slate-700 font-bold leading-none"
        >
          ×
        </button>

        <h2 class="text-lg md:text-xl lg:text-2xl font-bold text-red-900 mb-3 md:mb-4 pr-6">
          {{ getRoomTypeName(selectedRoom) }}
        </h2>

        <p class="text-slate-600 mb-3 md:mb-4 text-sm md:text-base leading-relaxed">
          {{ getRoomDescription(selectedRoom) || 'Premium room with all amenities' }}
        </p>

        <div class="mb-4 md:mb-6 space-y-2 text-sm md:text-base">
          <p class="text-slate-700">
            <strong>Capacity:</strong> {{ getRoomCapacity(selectedRoom) }} guests
          </p>
          <p class="text-slate-700">
            <strong>Price:</strong> ETB {{ getRoomPrice(selectedRoom) }}/night
          </p>
        </div>

        <div class="flex flex-col md:flex-row gap-2 md:gap-3">
          <button
            @click="showDetails = false"
            class="flex-1 px-3 md:px-4 py-2 md:py-3 bg-slate-200 hover:bg-slate-300 text-slate-900 font-semibold rounded-lg transition text-sm md:text-base"
          >
            Close
          </button>
          <button
            @click="openBookingModal(selectedRoom)"
            class="flex-1 px-3 md:px-4 py-2 md:py-3 bg-amber-500 hover:bg-amber-600 text-white font-semibold rounded-lg transition text-sm md:text-base"
          >
            Book Now
          </button>
        </div>
      </div>
    </div>

    <!-- Booking Modal Component -->
    <BookingModal
      :isOpen="showBookingModal"
      :room="roomToBook"
      @close="closeBookingModal"
      @submit="handleBookingSubmit"
    />
  </div>
</template>

<style scoped>
/* Responsive room grid */
</style>
