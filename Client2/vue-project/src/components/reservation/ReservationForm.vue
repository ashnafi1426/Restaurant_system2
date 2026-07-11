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

// Filter rooms based on search (by room number, floor, type, status, or id)
const filteredRooms = computed(() => {
  if (!roomSearch.value.trim()) return props.rooms

  let search = roomSearch.value.toLowerCase()
  
  // Remove "room" or "rm" prefix if user typed it
  if (search.startsWith('room ')) {
    search = search.replace('room ', '').trim()
  }
  if (search.startsWith('rm ')) {
    search = search.replace('rm ', '').trim()
  }
  
  return props.rooms.filter((r) => {
    if (!r) return false
    
    try {
      const roomNumber = r.room_number ? String(r.room_number).toLowerCase() : ''
      const roomType = r.room_type?.name ? String(r.room_type.name).toLowerCase() : ''
      const floor = r.floor ? String(r.floor).toLowerCase() : ''
      const status = r.status ? String(r.status).toLowerCase() : ''
      const description = r.description ? String(r.description).toLowerCase() : ''
      const id = r.id ? String(r.id).toLowerCase() : ''
      
      return (
        roomNumber.includes(search) ||
        roomType.includes(search) ||
        floor.includes(search) ||
        status.includes(search) ||
        description.includes(search) ||
        id.includes(search)
      )
    } catch (e) {
      console.error('Error filtering room:', r, e)
      return false
    }
  })
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
  form.value.room_id = room.id
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
  <div class="bg-white rounded-lg sm:rounded-xl shadow-sm border border-slate-200 p-4 sm:p-5 md:p-6 space-y-5 sm:space-y-6">
    <!-- Title -->
    <div>
      <h2 class="text-lg sm:text-xl md:text-2xl font-semibold text-slate-900">Reservation Form</h2>
      <p class="text-xs sm:text-sm text-slate-500 mt-1">Create or update hotel reservation</p>
    </div>

    <!-- Guest + Room -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4 md:gap-5">
      <!-- Guest Search -->
      <div class="relative">
        <label class="block text-xs sm:text-sm font-medium text-slate-700 mb-1.5 sm:mb-2">
          Guest <span class="text-red-500">*</span>
        </label>

        <!-- Search Input -->
        <input
          type="text"
          v-model="guestSearch"
          @focus="showGuestDropdown = true"
          placeholder="Search by name, email, phone..."
          class="w-full border border-slate-300 rounded-lg px-3 sm:px-3.5 py-2 sm:py-2.5 text-xs sm:text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200"
          :class="{ 'border-red-500 ring-2 ring-red-200': !form.guest_id && form.guest_id !== '' }"
        />

        <!-- Selected Guest Display -->
        <div v-if="selectedGuest && !showGuestDropdown" class="text-xs text-slate-600 mt-1">
          ✓ Selected: {{ formatGuestDisplay(selectedGuest) }}
        </div>

        <!-- Search Results Dropdown -->
        <div
          v-if="showGuestDropdown"
          class="absolute top-full left-0 right-0 mt-1 bg-white border border-slate-300 rounded-lg shadow-lg z-50 max-h-64 overflow-y-auto"
        >
          <!-- No results -->
          <div v-if="filteredGuests.length === 0" class="p-3 sm:p-4 text-slate-500 text-center text-xs sm:text-sm">
            No guests found
          </div>

          <!-- Guest options -->
          <div
            v-for="guest in filteredGuests"
            :key="guest.id"
            @click="selectGuest(guest)"
            class="p-2 sm:p-3 hover:bg-blue-50 cursor-pointer border-b border-slate-100 last:border-b-0 text-xs sm:text-sm transition duration-150"
            :class="{ 'bg-blue-100': form.guest_id === guest.id }"
          >
            <div class="font-medium text-slate-900">{{ guest.first_name }} {{ guest.last_name }}</div>
            <div class="text-xs text-slate-600 mt-0.5">
              {{ guest.email || guest.phone || 'No contact' }}
            </div>
            <div class="text-xs text-slate-500">ID: {{ guest.id }}</div>
          </div>
        </div>
      </div>

      <!-- Room Search -->
      <div class="relative">
        <label class="block text-xs sm:text-sm font-medium text-slate-700 mb-1.5 sm:mb-2">
          Room <span class="text-red-500">*</span>
        </label>

        <!-- Search Input -->
        <input
          type="text"
          v-model="roomSearch"
          @focus="showRoomDropdown = true"
          placeholder="Search by room number, type..."
          class="w-full border border-slate-300 rounded-lg px-3 sm:px-3.5 py-2 sm:py-2.5 text-xs sm:text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200"
          :class="{ 'border-red-500 ring-2 ring-red-200': !form.room_id && form.room_id !== '' }"
        />

        <!-- Selected Room Display -->
        <div v-if="selectedRoom && !showRoomDropdown" class="text-xs text-slate-600 mt-1">
          ✓ Selected: {{ formatRoomDisplay(selectedRoom) }}
        </div>

        <!-- Search Results Dropdown -->
        <div
          v-if="showRoomDropdown"
          class="absolute top-full left-0 right-0 mt-1 bg-white border border-slate-300 rounded-lg shadow-lg z-50 max-h-64 overflow-y-auto"
        >
          <!-- No results -->
          <div v-if="filteredRooms.length === 0" class="p-3 sm:p-4 text-slate-500 text-center text-xs sm:text-sm">
            No rooms found
          </div>

          <!-- Room options -->
          <div
            v-for="room in filteredRooms"
            :key="room.id || room.roomid"
            @click="selectRoom(room)"
            class="p-2 sm:p-3 hover:bg-blue-50 cursor-pointer border-b border-slate-100 last:border-b-0 text-xs sm:text-sm transition duration-150"
            :class="{ 'bg-blue-100': form.room_id === (room.id || room.roomid) }"
          >
            <div class="font-medium text-slate-900">Room {{ room.room_number }}</div>
            <div class="text-xs text-slate-600 mt-0.5">
              {{ room.room_type?.name }} - {{ room.room_type?.capacity }} guests
            </div>
            <div class="text-xs text-slate-500">{{ formatRoomDisplay(room) }}</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Dates -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4 md:gap-5">
      <!-- Check In -->
      <div>
        <label class="block text-xs sm:text-sm font-medium text-slate-700 mb-1.5 sm:mb-2">
          Check In <span class="text-red-500">*</span>
        </label>
        <input
          type="date"
          v-model="form.check_in_date"
          class="w-full border border-slate-300 rounded-lg px-3 sm:px-3.5 py-2 sm:py-2.5 text-xs sm:text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200"
          :min="today"
        />
        <p v-if="isPastDate" class="text-red-500 text-xs sm:text-sm mt-1">
          ⚠️ Check-in cannot be in the past
        </p>
      </div>

      <!-- Check Out -->
      <div>
        <label class="block text-xs sm:text-sm font-medium text-slate-700 mb-1.5 sm:mb-2">
          Check Out <span class="text-red-500">*</span>
        </label>
        <input
          type="date"
          v-model="form.check_out_date"
          class="w-full border border-slate-300 rounded-lg px-3 sm:px-3.5 py-2 sm:py-2.5 text-xs sm:text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200"
          :min="form.check_in_date || today"
        />
        <p v-if="!isValidDateRange" class="text-red-500 text-xs sm:text-sm mt-1">
          ⚠️ Check-out must be after check-in
        </p>
      </div>
    </div>

    <!-- Guests Count -->
    <div>
      <label class="block text-xs sm:text-sm font-medium text-slate-700 mb-1.5 sm:mb-2">
        Number of Guests <span class="text-red-500">*</span>
      </label>
      <input
        type="number"
        v-model="form.number_of_guests"
        class="w-full border border-slate-300 rounded-lg px-3 sm:px-3.5 py-2 sm:py-2.5 text-xs sm:text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200"
        min="1"
        max="99"
      />
    </div>

    <!-- Special Requests -->
    <div>
      <label class="block text-xs sm:text-sm font-medium text-slate-700 mb-1.5 sm:mb-2">
        Special Requests <span class="text-slate-500 text-xs">(Optional)</span>
      </label>
      <textarea
        v-model="form.special_requests"
        rows="3"
        class="w-full border border-slate-300 rounded-lg px-3 sm:px-3.5 py-2 sm:py-2.5 text-xs sm:text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200 resize-none"
        placeholder="Any special requirements or preferences..."
      ></textarea>
    </div>

    <!-- Nights Display -->
    <div class="bg-gradient-to-r from-blue-50 to-blue-100/50 p-3 sm:p-4 rounded-lg border border-blue-200 text-xs sm:text-sm">
      <div class="flex justify-between items-center">
        <span class="text-slate-700 font-medium">Total Nights:</span>
        <span class="text-lg sm:text-xl font-bold text-blue-600">{{ nights }} {{ nights === 1 ? 'night' : 'nights' }}</span>
      </div>
    </div>

    <!-- Actions -->
    <div class="flex flex-col-reverse sm:flex-row justify-end gap-2 sm:gap-3 pt-2">
      <button
        type="button"
        class="px-4 sm:px-5 py-2 sm:py-2.5 text-xs sm:text-sm font-medium border border-slate-300 rounded-lg hover:bg-slate-50 text-slate-700 transition duration-200"
      >
        Cancel
      </button>

      <button
        type="button"
        @click="submit"
        :disabled="loading || !isValidDateRange || isPastDate || !form.guest_id || !form.room_id"
        class="px-4 sm:px-6 py-2 sm:py-2.5 text-xs sm:text-sm font-medium bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition duration-200"
      >
        <span v-if="loading" class="inline-flex items-center gap-2">
          <span class="animate-spin">⌛</span>
          Saving...
        </span>
        <span v-else>💾 Save Reservation</span>
      </button>
    </div>
  </div>
</template>
