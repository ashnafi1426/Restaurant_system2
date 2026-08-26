<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useRoomStore } from '@/stores/room'
import type { Room } from '@/types/room'
import { BedDouble, Users, ArrowRight, Check } from 'lucide-vue-next'

const router = useRouter()
const roomStore = useRoomStore()
const loading = ref(false)

const fallbackRooms = [
  {
    id: 'f1',
    room_number: '101',
    room_type: { name: 'Executive Deluxe Suite', base_price_per_night: 2500, max_occupancy: 2 },
    images: ['https://images.unsplash.com/photo-1631049307264-da0ec9d70304?w=800&h=600&fit=crop'],
    amenities: ['King Bed', 'City View', 'Free Wi-Fi', 'Jacuzzi'],
  },
  {
    id: 'f2',
    room_number: '202',
    room_type: { name: 'Presidential Family Suite', base_price_per_night: 4500, max_occupancy: 4 },
    images: ['https://images.unsplash.com/photo-1582719478250-c89cae4dc85b?w=800&h=600&fit=crop'],
    amenities: ['2 Bedrooms', 'Balcony', 'Breakfast Included', 'Mini Bar'],
  },
  {
    id: 'f3',
    room_number: '303',
    room_type: { name: 'Standard King Room', base_price_per_night: 1800, max_occupancy: 2 },
    images: ['https://images.unsplash.com/photo-1590490360182-c33d57733427?w=800&h=600&fit=crop'],
    amenities: ['King Bed', 'Work Desk', 'Smart TV', 'Room Service'],
  },
]

const rooms = computed(() => {
  if (roomStore.rooms && roomStore.rooms.length > 0) {
    return roomStore.rooms.slice(0, 3)
  }
  return fallbackRooms
})

function getRoomName(room: any): string {
  return room.room_type?.name || `Luxury Suite #${room.room_number || ''}`
}

function getRoomPrice(room: any): string {
  const price = room.room_type?.base_price_per_night || room.price_per_night || 2500
  return `${Number(price).toLocaleString()} ETB`
}

function goToRooms() {
  router.push('/rooms')
}

onMounted(async () => {
  loading.value = true
  try {
    await roomStore.fetchRooms()
  } catch (err) {
    console.error('Error loading rooms:', err)
  } finally {
    loading.value = false
  }
})
</script>

<template>
  <section class="bg-white dark:bg-slate-900 py-12 sm:py-16 lg:py-24 transition-colors duration-300 font-sans border-b border-slate-200 dark:border-slate-800">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-12">
      <!-- Section Header -->
      <div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
        <div class="space-y-2">
          <span class="px-3.5 py-1 rounded-full text-xs font-black uppercase tracking-wider bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20">
            Featured Accommodation
          </span>
          <h2 class="text-3xl sm:text-4xl lg:text-5xl font-black text-slate-900 dark:text-white tracking-tight">
            Explore Our Fine Rooms
          </h2>
          <p class="text-sm text-slate-600 dark:text-slate-400 font-medium">
            Designed for luxury, comfort, and peaceful relaxation during your stay.
          </p>
        </div>

        <button
          @click="goToRooms"
          class="px-5 py-2.5 rounded-2xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-900 dark:text-white font-extrabold text-xs transition flex items-center gap-2 cursor-pointer self-start md:self-auto border border-slate-200 dark:border-slate-700"
        >
          <span>View All Rooms</span>
          <ArrowRight class="w-4 h-4" />
        </button>
      </div>

      <!-- Rooms Grid -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div
          v-for="room in rooms"
          :key="room.id"
          class="group bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-3xl overflow-hidden shadow-xs hover:border-amber-500/40 transition duration-300 flex flex-col justify-between"
        >
          <!-- Image -->
          <div class="relative h-64 overflow-hidden bg-slate-900">
            <img
              :src="room.images?.[0] || 'https://images.unsplash.com/photo-1631049307264-da0ec9d70304?w=800&h=600&fit=crop'"
              :alt="getRoomName(room)"
              class="h-full w-full object-cover transition duration-500 group-hover:scale-105"
            />
            <div class="absolute top-4 right-4 px-3 py-1 bg-slate-950/80 backdrop-blur-md text-amber-400 font-black text-xs rounded-full border border-amber-500/30">
              {{ getRoomPrice(room) }} <span class="text-[10px] text-slate-300 font-medium">/ night</span>
            </div>
          </div>

          <!-- Content -->
          <div class="p-6 space-y-4 flex-1 flex flex-col justify-between">
            <div class="space-y-2">
              <h3 class="text-lg font-black text-slate-900 dark:text-white group-hover:text-amber-500 transition">
                {{ getRoomName(room) }}
              </h3>

              <div class="flex items-center gap-4 text-xs font-bold text-slate-500 dark:text-slate-400">
                <div class="flex items-center gap-1.5">
                  <Users class="w-4 h-4 text-amber-500" />
                  <span>Up to {{ room.room_type?.max_occupancy || 2 }} Guests</span>
                </div>
                <div class="flex items-center gap-1.5">
                  <BedDouble class="w-4 h-4 text-amber-500" />
                  <span>Room #{{ room.room_number }}</span>
                </div>
              </div>
            </div>

            <!-- Amenities -->
            <div class="pt-4 border-t border-slate-200/60 dark:border-slate-800 flex flex-wrap gap-2">
              <span
                v-for="amenity in (room.amenities || ['King Bed', 'City View', 'Free Wi-Fi'])"
                :key="amenity"
                class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-slate-200/60 dark:bg-slate-900 text-slate-700 dark:text-slate-300 border border-slate-300/60 dark:border-slate-800"
              >
                {{ amenity }}
              </span>
            </div>

            <button
              @click="goToRooms"
              class="w-full mt-4 py-3 rounded-2xl bg-amber-500 hover:bg-amber-600 text-slate-950 font-black text-xs uppercase tracking-wider transition cursor-pointer flex items-center justify-center gap-2"
            >
              <span>Book Suite</span>
              <ArrowRight class="w-4 h-4" />
            </button>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>
