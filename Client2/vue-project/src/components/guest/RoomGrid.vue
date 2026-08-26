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
  <div class="w-full max-w-7xl mx-auto px-4 sm:px-6 md:px-8 lg:px-12">
    <!-- Results count bar -->
    <div class="results-bar">
      <div class="results-left">
        <span class="results-dot"></span>
        <span class="results-count">{{ rooms.length }}</span>
        <span class="results-label">{{ rooms.length === 1 ? 'room' : 'rooms' }} found</span>
      </div>
      <div class="results-line"></div>
    </div>

    <!-- Mobile-First Responsive Grid Layout with Proper Spacing -->
    <!-- Mobile: 1 column, Tablet: 2 columns, Desktop: 3 columns -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 md:gap-8">
      <div
        v-for="room in rooms"
        :key="room.id"
        class="bg-gradient-to-br from-white to-slate-50 rounded-3xl overflow-hidden shadow-lg hover:shadow-2xl transition-all duration-500 hover:scale-[1.01] border border-slate-200 h-[520px] md:h-[540px] flex flex-col"
      >
        <!-- Image Container - Larger Fixed Height -->
        <div class="relative w-full bg-slate-200 h-[240px] md:h-[260px] flex-shrink-0">
          <img
            :src="getRoomImage(room)"
            :alt="getRoomTypeName(room)"
            class="w-full h-full object-cover hover:scale-110 transition-transform duration-700"
            loading="lazy"
          />

          <!-- Dark Gradient Overlay -->
          <div class="absolute inset-0 bg-gradient-to-t from-black/40 via-transparent to-black/20"></div>

          <!-- Price Badge - Bottom Left, Larger -->
          <div
            class="absolute bottom-4 left-4 bg-white/95 backdrop-blur-md text-slate-900 px-3 py-2 rounded-xl shadow-2xl border border-white/50"
          >
            <div class="text-[10px] text-red-600 font-semibold uppercase tracking-wide leading-none mb-1">NIGHTLY RATE</div>
            <div class="flex items-baseline gap-1">
              <span class="text-red-600 text-xl md:text-2xl font-bold leading-none">ETB {{ getRoomPrice(room) }}</span>
              <span class="text-[10px] text-slate-500 font-normal">/Night</span>
            </div>
          </div>
        </div>

        <!-- Room Info Section - More Spacious -->
        <div class="p-5 md:p-6 space-y-3 flex flex-col flex-grow bg-white">
          <!-- Room Type Title -->
          <h3
            class="text-lg md:text-xl font-bold text-red-800 leading-tight"
          >
            {{ getRoomTypeName(room) }}
          </h3>

          <!-- Room Specs -->
          <div class="flex items-center gap-3 text-xs md:text-sm text-slate-600 font-medium">
            <span class="flex items-center gap-1">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/>
              </svg>
              30 M²
            </span>
            <span class="text-slate-300">|</span>
            <span class="flex items-center gap-1">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
              </svg>
              MAX {{ getRoomCapacity(room) }} GUESTS
            </span>
          </div>

          <!-- Amenities List - Clean Design -->
          <div class="space-y-2 flex-grow">
            <div class="flex items-start gap-2 text-xs md:text-sm text-slate-700">
              <span class="text-green-600 mt-0.5">✓</span>
              <span>Comfort and Privacy</span>
            </div>
            <div class="flex items-start gap-2 text-xs md:text-sm text-slate-700">
              <span class="text-green-600 mt-0.5">✓</span>
              <span>Complimentary Breakfast Served Daily</span>
            </div>
            <div class="flex items-start gap-2 text-xs md:text-sm text-slate-700">
              <span class="text-green-600 mt-0.5">✓</span>
              <span>Free dual-band high-speed Wi-Fi</span>
            </div>
            <div class="flex items-start gap-2 text-xs md:text-sm text-slate-700">
              <span class="text-green-600 mt-0.5">✓</span>
              <span>Fully Air-Conditioned Suite</span>
            </div>
            <div v-if="getRoomAmenities(room).length > 4" class="flex items-start gap-2 text-xs md:text-sm text-amber-600 font-medium">
              <span>+{{ getRoomAmenities(room).length - 4 }} More</span>
            </div>
          </div>

          <!-- Action Buttons - Bottom, Professional Style -->
          <div class="flex gap-3 pt-3 mt-auto border-t border-slate-100">
            <button
              @click="selectRoom(room)"
              class="flex-1 flex items-center justify-center gap-2 px-4 py-3 border-2 border-red-600 text-red-600 font-semibold rounded-xl hover:bg-red-50 transition-all text-xs md:text-sm"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
              </svg>
              SPECS
            </button>
            <button
              @click="openBookingModal(room)"
              class="flex-1 flex items-center justify-center gap-2 px-4 py-3 bg-gradient-to-r from-yellow-500 to-amber-500 hover:from-yellow-600 hover:to-amber-600 text-white font-semibold rounded-xl transition-all text-xs md:text-sm shadow-lg"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
              </svg>
              BOOK ROOM
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Details Modal - Professional Style -->
    <div
      v-if="showDetails && selectedRoom"
      class="fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center p-4 md:p-6 z-50"
    >
      <div
        class="bg-white rounded-3xl w-full max-w-md md:max-w-lg lg:max-w-2xl p-6 md:p-8 max-h-[90vh] overflow-y-auto shadow-2xl border border-slate-200"
      >
        <button
          @click="showDetails = false"
          class="float-right w-8 h-8 flex items-center justify-center rounded-full text-slate-400 hover:text-white hover:bg-red-500 font-bold text-xl leading-none transition-all"
        >
          ×
        </button>

        <h2 class="text-xl md:text-2xl lg:text-3xl font-bold text-red-800 mb-4 pr-10">
          {{ getRoomTypeName(selectedRoom) }}
        </h2>

        <p class="text-slate-600 mb-5 text-sm md:text-base leading-relaxed">
          {{ getRoomDescription(selectedRoom) || 'Premium room with all modern amenities for your comfort and convenience' }}
        </p>

        <div class="mb-6 space-y-3 text-sm md:text-base bg-slate-50 p-4 rounded-xl">
          <div class="flex items-center gap-3">
            <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
            </svg>
            <span class="text-slate-700"><strong class="font-semibold text-slate-900">Capacity:</strong> {{ getRoomCapacity(selectedRoom) }} guests</span>
          </div>
          <div class="flex items-center gap-3">
            <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span class="text-slate-700"><strong class="font-semibold text-slate-900">Price:</strong> ETB {{ getRoomPrice(selectedRoom) }} per night</span>
          </div>
        </div>

        <div class="flex flex-col md:flex-row gap-3">
          <button
            @click="showDetails = false"
            class="flex-1 px-5 py-3 bg-slate-100 hover:bg-slate-200 text-slate-900 font-semibold rounded-xl transition text-sm md:text-base"
          >
            Close
          </button>
          <button
            @click="openBookingModal(selectedRoom)"
            class="flex-1 px-5 py-3 bg-gradient-to-r from-yellow-500 to-amber-500 hover:from-yellow-600 hover:to-amber-600 text-white font-semibold rounded-xl transition text-sm md:text-base shadow-lg"
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
/* Results count bar */
.results-bar {
  display: flex;
  align-items: center;
  gap: 16px;
  margin-bottom: 32px;
}

.results-left {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-shrink: 0;
}

.results-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: linear-gradient(135deg, #c2a44e, #d4af6e);
  box-shadow: 0 0 0 3px rgba(194, 164, 78, 0.2);
  display: block;
}

.results-count {
  font-size: 22px;
  font-weight: 800;
  color: #1e293b;
  letter-spacing: -0.02em;
}

.results-label {
  font-size: 14px;
  color: #64748b;
  font-weight: 500;
}

.results-line {
  flex: 1;
  height: 1px;
  background: linear-gradient(to right, #e2e8f0 60%, transparent);
}
</style>
