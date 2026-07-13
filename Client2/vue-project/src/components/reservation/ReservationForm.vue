<script setup lang="ts">
import { computed, ref, reactive } from 'vue'

import type { Reservation } from '@/types/reservation'
import type { Room } from '@/types/room'

interface Props {
  modelValue: Reservation
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

// Guest registration
const registrationError = ref('')
const registrationSuccess = ref('')

// New guest registration form
const newGuestForm = reactive({
  first_name: '',
  last_name: '',
  email: '',
  phone: '',
})

// Room search states
const roomSearch = ref('')
const showRoomDropdown = ref(false)

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
      let roomType = ''
      if (typeof r.room_type === 'string' && r.room_type) {
        roomType = (r.room_type as string).toLowerCase()
      } else if (r.room_type && typeof r.room_type === 'object' && 'name' in r.room_type) {
        const name = (r.room_type as any).name
        roomType = String(name).toLowerCase()
      }
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
  const roomNumber = room.room_number || 'N/A'
  const roomType =
    typeof room.room_type === 'string' ? room.room_type : room.room_type?.name || 'Unknown'
  const capacity =
    room.room_type && typeof room.room_type === 'object' ? room.room_type.capacity : 0
  const price =
    room.room_type && typeof room.room_type === 'object' ? room.room_type.base_price_per_night : 0
  return `Room ${roomNumber} - ${roomType} (${capacity} guests, $${price}/night)`
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

// Register new guest
async function registerGuest() {
  registrationError.value = ''
  registrationSuccess.value = ''

  // Validate required fields
  if (!newGuestForm.first_name || !newGuestForm.last_name || !newGuestForm.phone) {
    registrationError.value = 'First name, last name, and phone are required'
    return
  }

  try {
    const response = await fetch('http://127.0.0.1:8000/api/guests', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(newGuestForm),
    })

    if (!response.ok) {
      const error = await response.json()
      registrationError.value = error.message || 'Failed to register guest'
      return
    }

    const data = await response.json()
    const newGuest = data.data

    // Set the guest_id in the form
    form.value.guest_id = newGuest.id

    registrationSuccess.value = `✓ Guest registered successfully! Welcome, ${newGuest.first_name} ${newGuest.last_name}`

    // Reset form after 2 seconds
    setTimeout(() => {
      registrationSuccess.value = ''
      // Reset registration form
      Object.assign(newGuestForm, {
        first_name: '',
        last_name: '',
        email: '',
        phone: '',
      })
    }, 2000)
  } catch (error: any) {
    registrationError.value = error.message || 'Failed to register guest. Please try again.'
  }
}

const submit = () => {
  if (props.loading) {
    return
  }

  if (isPastDate.value) {
    return
  }

  if (!isValidDateRange.value) {
    return
  }

  if (!form.value.guest_id) {
    alert('Please register as a guest to continue')
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
  <div
    class="bg-white rounded-lg sm:rounded-xl shadow-sm border border-slate-200 p-4 sm:p-5 md:p-6 space-y-5 sm:space-y-6"
  >
    <!-- Title -->
    <div>
      <h2 class="text-lg sm:text-xl md:text-2xl font-semibold text-slate-900">Reservation Form</h2>
      <p class="text-xs sm:text-sm text-slate-500 mt-1">Complete your booking details</p>
    </div>

    <!-- Guest Registration Form (Required) -->
    <div class="border-2 border-blue-300 bg-blue-50 rounded-lg p-4 sm:p-6">
      <!-- Header -->
      <h3 class="text-base sm:text-lg font-semibold text-slate-900 mb-4">Guest Information</h3>

      <!-- Error Alert -->
      <div
        v-if="registrationError"
        class="mb-4 rounded-lg bg-red-50 border border-red-200 p-3 text-red-700 text-xs sm:text-sm"
      >
        {{ registrationError }}
      </div>

      <!-- Success Alert -->
      <div
        v-if="registrationSuccess"
        class="mb-4 rounded-lg bg-green-50 border border-green-200 p-3 text-green-700 text-xs sm:text-sm"
      >
        {{ registrationSuccess }}
      </div>

      <!-- Registration Form Fields -->
      <div class="space-y-4">
        <!-- First & Last Name -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">
          <div>
            <label class="block text-xs sm:text-sm font-medium text-slate-700 mb-1.5">
              First Name <span class="text-red-500">*</span>
            </label>
            <input
              v-model="newGuestForm.first_name"
              type="text"
              placeholder="John"
              class="w-full border border-slate-300 rounded-lg px-3 sm:px-3.5 py-2 sm:py-2.5 text-xs sm:text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            />
          </div>
          <div>
            <label class="block text-xs sm:text-sm font-medium text-slate-700 mb-1.5">
              Last Name <span class="text-red-500">*</span>
            </label>
            <input
              v-model="newGuestForm.last_name"
              type="text"
              placeholder="Doe"
              class="w-full border border-slate-300 rounded-lg px-3 sm:px-3.5 py-2 sm:py-2.5 text-xs sm:text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            />
          </div>
        </div>

        <!-- Email & Phone -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">
          <div>
            <label class="block text-xs sm:text-sm font-medium text-slate-700 mb-1.5">
              Email <span class="text-slate-500 text-xs">(Optional)</span>
            </label>
            <input
              v-model="newGuestForm.email"
              type="email"
              placeholder="john@example.com"
              class="w-full border border-slate-300 rounded-lg px-3 sm:px-3.5 py-2 sm:py-2.5 text-xs sm:text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            />
          </div>
          <div>
            <label class="block text-xs sm:text-sm font-medium text-slate-700 mb-1.5">
              Phone <span class="text-red-500">*</span>
            </label>
            <input
              v-model="newGuestForm.phone"
              type="tel"
              placeholder="+1 (555) 123-4567"
              class="w-full border border-slate-300 rounded-lg px-3 sm:px-3.5 py-2 sm:py-2.5 text-xs sm:text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            />
          </div>
        </div>

        <!-- Register Button -->
        <button
          type="button"
          @click="registerGuest"
          class="w-full px-4 sm:px-6 py-2.5 sm:py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-semibold text-xs sm:text-sm transition-all"
        >
          ✓ Register & Continue
        </button>
      </div>

      <!-- Info Text -->
      <p class="mt-4 text-xs sm:text-sm text-slate-600 text-center">
        Your information helps us provide better service during your stay
      </p>
    </div>

    <!-- Room Selection -->
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
      <div v-if="form.room_id && !showRoomDropdown" class="text-xs text-slate-600 mt-1">
        ✓ Selected:
        {{
          filteredRooms.find((r) => r.id === form.room_id)
            ? formatRoomDisplay(filteredRooms.find((r) => r.id === form.room_id)!)
            : 'Loading...'
        }}
      </div>

      <!-- Search Results Dropdown -->
      <div
        v-if="showRoomDropdown"
        class="absolute top-full left-0 right-0 mt-1 bg-white border border-slate-300 rounded-lg shadow-lg z-50 max-h-64 overflow-y-auto"
      >
        <!-- No results -->
        <div
          v-if="filteredRooms.length === 0"
          class="p-3 sm:p-4 text-slate-500 text-center text-xs sm:text-sm"
        >
          No rooms found
        </div>

        <!-- Room options -->
        <div
          v-for="room in filteredRooms"
          :key="room.id"
          @click="selectRoom(room)"
          class="p-2 sm:p-3 hover:bg-blue-50 cursor-pointer border-b border-slate-100 last:border-b-0 text-xs sm:text-sm transition duration-150"
          :class="{ 'bg-blue-100': form.room_id === room.id }"
        >
          <div class="font-medium text-slate-900">Room {{ room.room_number }}</div>
          <div class="text-xs text-slate-600 mt-0.5">
            {{ room.room_type?.name }} - {{ room.room_type?.capacity }} guests
          </div>
          <div class="text-xs text-slate-500">{{ formatRoomDisplay(room) }}</div>
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
    <div
      class="bg-gradient-to-r from-blue-50 to-blue-100/50 p-3 sm:p-4 rounded-lg border border-blue-200 text-xs sm:text-sm"
    >
      <div class="flex justify-between items-center">
        <span class="text-slate-700 font-medium">Total Nights:</span>
        <span class="text-lg sm:text-xl font-bold text-blue-600"
          >{{ nights }} {{ nights === 1 ? 'night' : 'nights' }}</span
        >
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
