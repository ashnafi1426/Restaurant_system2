<script setup lang="ts">
import type { RoomInMatrix } from '@/types/reception'

interface Props {
  rooms: RoomInMatrix[]
}

const props = defineProps<Props>()

const getRoomStatusColor = (status: string) => {
  const colors: Record<string, string> = {
    available: 'bg-teal-600 hover:bg-teal-700 text-white',
    occupied: 'bg-slate-600 hover:bg-slate-700 text-white',
    reserved: 'bg-red-600 hover:bg-red-700 text-white',
    cleaning: 'bg-yellow-600 hover:bg-yellow-700 text-white',
    maintenance: 'bg-red-700 hover:bg-red-800 text-white',
  }
  return colors[status] || 'bg-gray-400'
}

const statusLabels: Record<string, string> = {
  available: 'Available',
  occupied: 'Occupied',
  reserved: 'Reserved',
  cleaning: 'Cleaning',
  maintenance: 'Maintenance',
}
</script>

<template>
  <div class="bg-blue-50 rounded-lg border border-blue-200 p-4 sm:p-5 md:p-6 lg:p-8 shadow-sm">
    <!-- Header -->
    <div
      class="flex items-center justify-between mb-3 sm:mb-4 md:mb-5 pb-3 sm:pb-4 md:pb-5 border-b border-blue-200 flex-col sm:flex-row gap-2 sm:gap-0"
    >
      <h3 class="text-base sm:text-lg md:text-xl lg:text-2xl font-bold text-gray-900">
        Room Status Matrix
      </h3>
      <div class="flex gap-2 sm:gap-3 items-center flex-wrap justify-center sm:justify-end">
        <!-- Legend -->
        <div class="hidden sm:flex items-center gap-1 md:gap-2">
          <div class="w-2 h-2 md:w-3 md:h-3 rounded-full bg-teal-600"></div>
          <span class="text-xs md:text-sm text-gray-600 font-medium">Available</span>
        </div>
        <div class="hidden sm:flex items-center gap-1 md:gap-2">
          <div class="w-2 h-2 md:w-3 md:h-3 rounded-full bg-slate-600"></div>
          <span class="text-xs md:text-sm text-gray-600 font-medium">Occupied</span>
        </div>
        <div class="hidden md:flex items-center gap-1 md:gap-2">
          <div class="w-2 h-2 md:w-3 md:h-3 rounded-full bg-red-600"></div>
          <span class="text-xs md:text-sm text-gray-600 font-medium">Dirty</span>
        </div>
        <div class="hidden lg:flex items-center gap-1 md:gap-2">
          <div class="w-2 h-2 md:w-3 md:h-3 rounded-full bg-red-700"></div>
          <span class="text-xs md:text-sm text-gray-600 font-medium">Maintenance</span>
        </div>
        <button
          class="text-xs md:text-sm px-2 sm:px-3 md:px-4 py-1 md:py-1.5 text-teal-600 hover:text-teal-700 font-bold rounded transition min-h-10"
        >
          Filters
        </button>
      </div>
    </div>

    <!-- Room Grid -->
    <div
      v-if="props.rooms.length > 0"
      class="grid gap-2 sm:gap-2.5 md:gap-3 mb-4"
      style="grid-template-columns: repeat(auto-fill, minmax(48px, 1fr))"
    >
      <button
        v-for="room in props.rooms"
        :key="room.id"
        :class="`${getRoomStatusColor(room.status)} text-xs md:text-sm font-bold py-2 md:py-3 px-1 md:px-2 rounded transition duration-200 cursor-pointer min-h-10`"
        :title="`Room ${room.room_number} - ${statusLabels[room.status]}`"
      >
        {{ room.room_number }}
      </button>
    </div>

    <!-- Empty State -->
    <div v-else class="text-center py-6 sm:py-8 md:py-10">
      <p class="text-xs sm:text-sm md:text-base text-gray-500">No rooms available</p>
    </div>

    <!-- Footer -->
    <div
      class="flex justify-between items-center text-xs md:text-sm pt-3 sm:pt-4 md:pt-5 border-t border-gray-200 flex-col sm:flex-row gap-2 sm:gap-0"
    >
      <p class="text-gray-500 font-medium">Last updated: 2 mins ago</p>
      <button class="text-teal-600 hover:text-teal-700 font-bold min-h-10">Open Map</button>
    </div>
  </div>
</template>
