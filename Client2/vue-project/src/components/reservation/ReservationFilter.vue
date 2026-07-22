<script setup lang="ts">
import { reactive, watch } from 'vue'

import type { ReservationFilter } from '@/types/reservation'

interface Guest {
  id: string
  first_name: string
  last_name: string
}
interface Room {
  id: string
  room_number: string
}

interface Props {
  filters: ReservationFilter

  guests: Guest[]

  rooms: Room[]
}

const props = defineProps<Props>()

const emit = defineEmits<{
  (e: 'update:filters', value: ReservationFilter): void

  (e: 'search'): void

  (e: 'reset'): void
}>()

const localFilters = reactive({
  ...props.filters,
})

watch(
  () => props.filters,

  (value) => {
    Object.assign(localFilters, value)
  },

  {
    deep: true,
  },
)

watch(
  localFilters,

  () => {
    emit(
      'update:filters',

      {
        ...localFilters,
      },
    )
  },

  {
    deep: true,
  },
)

const search = () => {
  emit('search')
}

const reset = () => {
  emit('reset')
}
</script>

<template>
  <div class="bg-white rounded-xl shadow-sm border p-6">
    <div class="mb-5">
      <h2 class="text-xl font-semibold">Reservation Filters</h2>

      <p class="text-gray-500">Search reservations quickly using multiple criteria.</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
      <!-- Search -->

      <div>
        <label class="block text-sm font-medium mb-2"> Booking Reference </label>

        <input
          v-model="localFilters.search"
          type="text"
          placeholder="Search booking..."
          class="w-full rounded-lg border px-4 py-2 focus:ring-2 focus:ring-blue-500"
        />
      </div>

      <!-- Guest -->

      <div>
        <label class="block text-sm font-medium mb-2"> Guest </label>

        <select v-model="localFilters.guest_id" class="w-full rounded-lg border px-4 py-2">
          <option value="">All Guests</option>

          <option v-for="guest in guests" :key="guest.id" :value="guest.id">
            {{ guest.first_name }}

            {{ guest.last_name }}
          </option>
        </select>
      </div>

      <!-- Room -->

      <div>
        <label class="block text-sm font-medium mb-2"> Room </label>

        <select v-model="localFilters.room_id" class="w-full rounded-lg border px-4 py-2">
          <option value="">All Rooms</option>

          <option v-for="room in rooms" :key="room.id" :value="room.id">
            Room

            {{ room.room_number }}
          </option>
        </select>
      </div>

      <!-- Status -->

      <div>
        <label class="block text-sm font-medium mb-2"> Status </label>

        <select v-model="localFilters.status" class="w-full rounded-lg border px-4 py-2">
          <option value="">All Status</option>

          <option value="pending">Pending</option>

          <option value="confirmed">Confirmed</option>

          <option value="checked_in">Checked In</option>

          <option value="checked_out">Checked Out</option>

          <option value="cancelled">Cancelled</option>
        </select>
      </div>

      <!-- Check In -->

      <div>
        <label class="block text-sm font-medium mb-2"> Check In </label>

        <input
          type="date"
          v-model="localFilters.check_in_date"
          class="w-full rounded-lg border px-4 py-2"
        />
      </div>

      <!-- Check Out -->

      <div>
        <label class="block text-sm font-medium mb-2"> Check Out </label>

        <input
          type="date"
          v-model="localFilters.check_out_date"
          class="w-full rounded-lg border px-4 py-2"
        />
      </div>
    </div>

    <div class="flex justify-end gap-3 mt-6">
      <button @click="reset" class="px-5 py-2 rounded-lg border hover:bg-gray-100">Reset</button>

      <button @click="search" class="px-6 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white">
        Search
      </button>
    </div>
  </div>
</template>
