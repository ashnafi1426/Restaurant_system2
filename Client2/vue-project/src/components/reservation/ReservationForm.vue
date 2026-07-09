<script setup lang="ts">
import { computed, ref } from 'vue'

import type { Reservation } from '@/types/reservation'
import type { Guest } from '@/types/guest'
import type { Room } from '@/types/room'

interface Props {
  modelValue: Reservation
  guests: Guest[]
  rooms: Room[]
  loading?: boolean
}

const props = defineProps<Props>()
const emit = defineEmits<{
  (e: 'update:modelValue', value: Reservation): void
  (e: 'submit'): void
}>()

const form = computed({
  get: () => props.modelValue,
  set: (val) => emit('update:modelValue', val),
})

// Search states
const guestSearch = ref('')
const roomSearch = ref('')
const showGuestDropdown = ref(false)
const showRoomDropdown = ref(false)

// Get selected guest and room display info
const selectedGuest = computed(() => {
  return props.guests.find((g) => g.id === form.value.guest_id)
})

const selectedRoom = computed(() => {
  return props.rooms.find((r) => (r.id || r.roomid) === form.value.room_id)
})

// Filter guests based on search (by name, email, phone, or id)
const filteredGuests = computed(() => {
  if (!guestSearch.value.trim()) return props.guests

  const search = guestSearch.value.toLowerCase()
  return props.guests.filter(
    (g) =>
      `${g.first_name} ${g.last_name}`.toLowerCase().includes(search) ||
      (g.email && g.email.toLowerCase().includes(search)) ||
      (g.phone && g.phone.toLowerCase().includes(search)) ||
      (g.id && g.id.toLowerCase().includes(search)),
  )
})

// Filter rooms based on search (by room number, type, or id)
const filteredRooms = computed(() => {
  if (!roomSearch.value.trim()) return props.rooms

  const search = roomSearch.value.toLowerCase()
  return props.rooms.filter(
    (r) =>
      (r.room_number && r.room_number.toString().includes(search)) ||
      (r.room_type?.name && r.room_type.name.toLowerCase().includes(search)) ||
      (r.id && r.id.toLowerCase().includes(search)),
  )
})

const formatRoomDisplay = (room: Room): string => {
  const roomType = room.room_type?.name || 'Unknown'
  const capacity = room.room_type?.capacity || 0
  const price = room.room_type?.base_price_per_night || 0
  return `Room ${room.room_number} - ${roomType} (${capacity} guests, $${price}/night)`
}

const formatGuestDisplay = (guest: Guest): string => {
  return `${guest.first_name} ${guest.last_name} (${guest.email || guest.phone || 'No contact'})`
}

const selectGuest = (guest: Guest) => {
  form.value.guest_id = guest.id
  guestSearch.value = ''
  showGuestDropdown.value = false
}

const selectRoom = (room: Room) => {
  form.value.room_id = room.id || room.roomid
  roomSearch.value = ''
  showRoomDropdown.value = false
}

const today = new Date().toISOString().split('T')[0]

const isValidDateRange = computed(() => {
  if (!form.value.check_in_date || !form.value.check_out_date) return true
  return form.value.check_out_date > form.value.check_in_date
})

const isPastDate = computed(() => {
  if (!form.value.check_in_date) return false
  return form.value.check_in_date < today
})

const nights = computed(() => {
  if (!form.value.check_in_date || !form.value.check_out_date) return 0

  const start = new Date(form.value.check_in_date)
  const end = new Date(form.value.check_out_date)

  const diff = end.getTime() - start.getTime()

  return Math.max(diff / (1000 * 60 * 60 * 24), 0)
})

const submit = () => {
  if (isPastDate.value) {
    return
  }

  if (!isValidDateRange.value) {
    return
  }

  if (!form.value.guest_id) {
    alert('Please select a guest')
    return
  }

  if (!form.value.room_id) {
    alert('Please select a room')
    return
  }

  emit('submit')
}
</script>

<template>
  <div class="bg-white rounded-xl shadow-sm border p-6 space-y-6">
    <!-- Title -->
    <div>
      <h2 class="text-xl font-semibold">Reservation Form</h2>
      <p class="text-gray-500 text-sm">Create or update hotel reservation</p>
    </div>

    <!-- Guest + Room -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <!-- Guest Search -->
      <div class="relative">
        <label class="block text-sm font-medium text-gray-700 mb-2">
          Guest <span class="text-red-500">*</span>
        </label>

        <!-- Search Input -->
        <input
          type="text"
          v-model="guestSearch"
          @focus="showGuestDropdown = true"
          placeholder="Search by name, email, phone, or ID..."
          class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          :class="{ 'border-red-500': !form.guest_id && form.guest_id !== '' }"
        />

        <!-- Selected Guest Display -->
        <div v-if="selectedGuest && !showGuestDropdown" class="text-xs text-gray-600 mt-1">
          ✓ Selected: {{ formatGuestDisplay(selectedGuest) }}
        </div>

        <!-- Search Results Dropdown -->
        <div
          v-if="showGuestDropdown"
          class="absolute top-full left-0 right-0 mt-1 bg-white border border-gray-300 rounded-lg shadow-lg z-50 max-h-64 overflow-y-auto"
        >
          <!-- No results -->
          <div v-if="filteredGuests.length === 0" class="p-3 text-gray-500 text-center">
            No guests found
          </div>

          <!-- Guest options -->
          <div
            v-for="guest in filteredGuests"
            :key="guest.id"
            @click="selectGuest(guest)"
            class="p-3 hover:bg-blue-50 cursor-pointer border-b last:border-b-0 text-sm"
            :class="{ 'bg-blue-100': form.guest_id === guest.id }"
          >
            <div class="font-medium">{{ guest.first_name }} {{ guest.last_name }}</div>
            <div class="text-xs text-gray-600">
              {{ guest.email || guest.phone || 'No contact' }}
            </div>
            <div class="text-xs text-gray-500">ID: {{ guest.id }}</div>
          </div>
        </div>
      </div>

      <!-- Room Search -->
      <div class="relative">
        <label class="block text-sm font-medium text-gray-700 mb-2">
          Room <span class="text-red-500">*</span>
        </label>

        <!-- Search Input -->
        <input
          type="text"
          v-model="roomSearch"
          @focus="showRoomDropdown = true"
          placeholder="Search by room number, type, or ID..."
          class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          :class="{ 'border-red-500': !form.room_id && form.room_id !== '' }"
        />

        <!-- Selected Room Display -->
        <div v-if="selectedRoom && !showRoomDropdown" class="text-xs text-gray-600 mt-1">
          ✓ Selected: {{ formatRoomDisplay(selectedRoom) }}
        </div>

        <!-- Search Results Dropdown -->
        <div
          v-if="showRoomDropdown"
          class="absolute top-full left-0 right-0 mt-1 bg-white border border-gray-300 rounded-lg shadow-lg z-50 max-h-64 overflow-y-auto"
        >
          <!-- No results -->
          <div v-if="filteredRooms.length === 0" class="p-3 text-gray-500 text-center">
            No rooms found
          </div>

          <!-- Room options -->
          <div
            v-for="room in filteredRooms"
            :key="room.id || room.roomid"
            @click="selectRoom(room)"
            class="p-3 hover:bg-blue-50 cursor-pointer border-b last:border-b-0 text-sm"
            :class="{ 'bg-blue-100': form.room_id === (room.id || room.roomid) }"
          >
            <div class="font-medium">Room {{ room.room_number }}</div>
            <div class="text-xs text-gray-600">
              {{ room.room_type?.name }} - {{ room.room_type?.capacity }} guests
            </div>
            <div class="text-xs text-gray-500">{{ formatRoomDisplay(room) }}</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Dates -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <!-- Check In -->
      <div>
        <label class="text-sm font-medium">Check In</label>
        <input type="date" v-model="form.check_in_date" class="w-full border rounded px-3 py-2" />
        <p v-if="isPastDate" class="text-red-500 text-sm">Check-in cannot be in the past</p>
      </div>

      <!-- Check Out -->
      <div>
        <label class="text-sm font-medium">Check Out</label>
        <input type="date" v-model="form.check_out_date" class="w-full border rounded px-3 py-2" />
        <p v-if="!isValidDateRange" class="text-red-500 text-sm">
          Check-out must be after check-in
        </p>
      </div>
    </div>

    <!-- Guests Count -->
    <div>
      <label class="text-sm font-medium">Number of Guests</label>
      <input
        type="number"
        v-model="form.number_of_guests"
        class="w-full border rounded px-3 py-2"
        min="1"
      />
    </div>

    <!-- Special Requests -->
    <div>
      <label class="text-sm font-medium">Special Requests</label>
      <textarea
        v-model="form.special_requests"
        rows="3"
        class="w-full border rounded px-3 py-2"
        placeholder="Any special requirements..."
      ></textarea>
    </div>

    <!-- Nights Display -->
    <div class="bg-gray-50 p-3 rounded border text-sm">
      Total Nights:
      <span class="font-semibold">{{ nights }}</span>
    </div>

    <!-- Actions -->
    <div class="flex justify-end gap-3">
      <button type="button" class="px-5 py-2 border rounded hover:bg-gray-100">Cancel</button>

      <button
        type="button"
        @click="submit"
        :disabled="loading"
        class="px-6 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 disabled:opacity-50"
      >
        <span v-if="loading">Saving...</span>
        <span v-else>Save Reservation</span>
      </button>
    </div>
  </div>
</template>
