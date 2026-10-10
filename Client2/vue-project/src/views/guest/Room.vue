<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import { useRoomStore } from '@/stores/room'
import { useGuestHotelStore } from '@/stores/guestHotelStore'
import { useLanguageStore } from '@/stores/language'
import RoomHero from '@/components/guest/RoomHero.vue'
import RoomSearchBar from '@/components/guest/RoomSearchBar.vue'
import RoomGrid from '@/components/guest/RoomGrid.vue'
import NoRoomsFound from '@/components/guest/NoRoomsFound.vue'
import RoomPagination from '@/components/guest/RoomPagination.vue'
import RoomCTA from '@/components/guest/RoomCTA.vue'
import GuestLayout from '@/Layouts/GuestLayout.vue'

const roomStore = useRoomStore()
const guestHotelStore = useGuestHotelStore()
const languageStore = useLanguageStore()

const search = ref('')
const selectedType = ref('All')
const selectedCapacity = ref('All')
const currentPage = ref(1)

// Get rooms from store
const rooms = computed(() => roomStore.rooms)
const loading = computed(() => roomStore.loading)
const error = computed(() => roomStore.error)

// Filter rooms based on search and filters
const filteredRooms = computed(() => {
  const roomList = Array.isArray(rooms.value) ? rooms.value : []
  return roomList.filter((room: any) => {
    if (!room) return false
    // Safely get room name from various possible fields
    const roomName = (room.room_name || room.name || `Room ${room.room_number || ''}`).toLowerCase()
    const matchesSearch = roomName.includes((search.value || '').toLowerCase())

    // Get room type - could be string or object
    const roomType =
      typeof room.room_type === 'string' ? room.room_type : room.room_type?.name || 'Standard'

    const matchesType = selectedType.value === 'All' || roomType === selectedType.value

    // Get capacity - could be direct or nested in room_type
    const capacity = room.capacity || room.room_type?.capacity || 2

    const matchesCapacity =
      selectedCapacity.value === 'All' || capacity >= Number(selectedCapacity.value)

    return matchesSearch && matchesType && matchesCapacity
  })
})

// Pagination
const itemsPerPage = ref(9)

const paginatedRooms = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage.value
  const end = start + itemsPerPage.value
  return filteredRooms.value.slice(start, end)
})

const pagination = computed(() => {
  const total = filteredRooms.value.length
  const lastPage = Math.ceil(total / itemsPerPage.value) || 1

  return {
    current_page: currentPage.value,
    last_page: lastPage,
    per_page: itemsPerPage.value,
    total: total,
    from: (currentPage.value - 1) * itemsPerPage.value + 1,
    to: Math.min(currentPage.value * itemsPerPage.value, total),
  }
})

// Load rooms from backend
async function loadRooms() {
  try {
    await roomStore.fetchRooms({ page: currentPage.value, per_page: itemsPerPage.value })
    currentPage.value = 1 // Reset to first page when filter changes
  } catch (err) {
    console.error('Failed to load rooms:', err)
  }
}

function loadPage(page: number) {
  currentPage.value = page
}

function clearFilters() {
  search.value = ''
  selectedType.value = 'All'
  selectedCapacity.value = 'All'
  currentPage.value = 1
}

// Fetch rooms on mount
onMounted(() => {
  loadRooms()
})

// Reset to page 1 when filters change
watch([search, selectedType, selectedCapacity], () => {
  currentPage.value = 1
})

// Re-fetch rooms when guest switches hotel
watch(
  () => guestHotelStore.hotelId,
  () => {
    clearFilters()
    loadRooms()
  },
)
</script>

<template>
  <GuestLayout>
    <div class="bg-slate-50">
      <!-- Hero -->
      <RoomHero />

      <!-- Unified Search + Filters -->
      <section class="mx-auto -mt-14 max-w-6xl px-4 sm:px-6 relative z-20">
        <RoomSearchBar v-model="search" :type="selectedType" @update:type="selectedType = $event" />
      </section>

      <!-- Loading State -->
      <section v-if="loading" class="mx-auto mt-10 max-w-7xl px-6">
        <div class="flex justify-center py-20">
          <div class="flex flex-col items-center gap-4">
            <div class="relative w-12 h-12">
              <svg class="absolute inset-0 w-full h-full" viewBox="0 0 100 100">
                <circle
                  cx="50"
                  cy="50"
                  r="45"
                  fill="none"
                  stroke="#0EA5E9"
                  stroke-width="6"
                  opacity="0.3"
                />
              </svg>
              <div
                class="absolute inset-0 animate-spin"
                style="animation: spin 1.5s linear infinite"
              >
                <svg viewBox="0 0 100 100" class="w-full h-full">
                  <circle
                    cx="50"
                    cy="50"
                    r="45"
                    fill="none"
                    stroke="#FBBF24"
                    stroke-width="8"
                    stroke-linecap="round"
                    stroke-dasharray="70 280"
                  />
                </svg>
              </div>
            </div>
            <p class="text-slate-700 dark:text-yellow-300 font-semibold text-sm">
              {{ languageStore.t('loading_rooms', 'Loading rooms...') }}
            </p>
          </div>
        </div>
      </section>

      <!-- Error State -->
      <section v-else-if="error" class="mx-auto mt-10 max-w-7xl px-6">
        <div class="rounded-lg bg-red-50 border border-red-200 p-6 text-center">
          <p class="text-red-700 font-medium">{{ error }}</p>
          <button
            @click="loadRooms"
            class="mt-4 px-6 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition cursor-pointer"
          >
            {{ languageStore.t('try_again', 'Try Again') }}
          </button>
        </div>
      </section>

      <!-- Rooms -->
      <section
        v-else
        id="rooms-section"
        class="mx-auto mt-8 max-w-7xl px-3 sm:px-4 md:px-6 lg:px-8"
      >
        <RoomGrid v-if="paginatedRooms.length" :rooms="paginatedRooms" />
        <NoRoomsFound v-else @clear-filters="clearFilters" />
      </section>

      <!-- Pagination -->
      <section
        v-if="paginatedRooms.length && pagination.last_page > 1"
        class="mx-auto mt-10 max-w-7xl px-3 sm:px-4 md:px-6 lg:px-8"
      >
        <RoomPagination :meta="pagination" @change-page="loadPage" />
      </section>

      <!-- CTA -->
      <section class="mx-auto mt-16 mb-16 max-w-7xl px-3 sm:px-4 md:px-6 lg:px-8">
        <RoomCTA />
      </section>
    </div>
  </GuestLayout>
</template>

<style scoped>
@keyframes spin {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}

.animate-spin {
  animation: spin 1.5s linear infinite;
}
</style>
