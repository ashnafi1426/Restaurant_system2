<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { Calendar, Users, Search, Loader, AlertCircle, CheckCircle, X } from 'lucide-vue-next'

interface RoomType {
  id?: string | number
  name: string
  description?: string
  base_price_per_night: number
  capacity: number
  amenities?: string[]
}

interface AvailableRoom {
  id: string
  room_number: string
  floor: number
  room_type?: RoomType
  status: string
}

interface CheckAvailabilityRequest {
  qr_token: string
  check_in_date: string
  check_out_date: string
  num_guests: number
}

const props = defineProps<{
  qrToken: string
}>()

const emit = defineEmits<{
  roomSelected: [room: AvailableRoom]
}>()

const checkInDate = ref('')
const checkOutDate = ref('')
const numberOfGuests = ref(1)

const isCheckingAvailability = ref(false)
const availableRooms = ref<AvailableRoom[]>([])
const availabilityError = ref<string | null>(null)
const hasSearched = ref(false)

const selectedRoomId = ref<string | null>(null)

const today = computed(() => new Date().toISOString().split('T')[0])

const isValidDateRange = computed(() => {
  if (!checkInDate.value || !checkOutDate.value) return false
  return checkOutDate.value > checkInDate.value
})

const isPastDate = computed(() => {
  if (!checkInDate.value) return false
  return checkInDate.value < today.value
})

const canSearch = computed(() => {
  return (
    props.qrToken &&
    checkInDate.value &&
    checkOutDate.value &&
    isValidDateRange.value &&
    !isPastDate.value &&
    numberOfGuests.value >= 1
  )
})

const numberOfNights = computed(() => {
  if (!checkInDate.value || !checkOutDate.value) return 0
  const checkIn = new Date(checkInDate.value)
  const checkOut = new Date(checkOutDate.value)
  const nights = Math.floor((checkOut.getTime() - checkIn.getTime()) / (1000 * 60 * 60 * 24))
  return Math.max(nights, 1)
})

async function checkAvailability() {
  if (!canSearch.value) {
    availabilityError.value = 'Please fill in all required fields with valid dates'
    return
  }

  isCheckingAvailability.value = true
  availabilityError.value = null
  availableRooms.value = []
  hasSearched.value = false

  try {
    const response = await fetch('/api/guest/bookings/check-availability', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        qr_token: props.qrToken,
        check_in_date: checkInDate.value,
        check_out_date: checkOutDate.value,
        num_guests: numberOfGuests.value,
      } as CheckAvailabilityRequest),
    })

    const data = await response.json()

    if (!response.ok) {
      throw new Error(data.message || 'Failed to check availability')
    }

    hasSearched.value = true

    if (data.available && data.availableRooms && data.availableRooms.length > 0) {
      availableRooms.value = data.availableRooms
      availabilityError.value = null
    } else {
      availableRooms.value = []
      availabilityError.value = data.message || 'No rooms available for selected dates'
    }
  } catch (error: any) {
    console.error('[GuestBookingForm] Error checking availability:', error)
    hasSearched.value = true
    availableRooms.value = []
    availabilityError.value = error.message || 'Error checking availability. Please try again.'
  } finally {
    isCheckingAvailability.value = false
  }
}

function selectRoom(room: AvailableRoom) {
  selectedRoomId.value = room.id
  emit('roomSelected', room)
}

function resetSearch() {
  checkInDate.value = ''
  checkOutDate.value = ''
  numberOfGuests.value = 1
  availableRooms.value = []
  availabilityError.value = null
  hasSearched.value = false
  selectedRoomId.value = null
}

function setMinimumDate(days: number) {
  const date = new Date()
  date.setDate(date.getDate() + days)
  return date.toISOString().split('T')[0]
}

watch(
  () => props.qrToken,
  () => {
    if (props.qrToken && !checkInDate.value) {
      const tomorrow = new Date()
      tomorrow.setDate(tomorrow.getDate() + 1)
      checkInDate.value = tomorrow.toISOString().split('T')[0]

      const nextDay = new Date()
      nextDay.setDate(nextDay.getDate() + 2)
      checkOutDate.value = nextDay.toISOString().split('T')[0]
    }
  },
  { immediate: true }
)
</script>

<template>
  <div class="w-full space-y-6">
    <div class="bg-white rounded-2xl shadow-lg border border-slate-200/50 p-6 md:p-8">
      <h2 class="text-2xl md:text-3xl font-bold text-slate-900 mb-6 flex items-center gap-3">
        <Calendar class="w-7 h-7 text-amber-600" />
        Check Room Availability
      </h2>

      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="space-y-2">
          <label class="text-sm font-semibold text-slate-700">Check-in Date</label>
          <input
            v-model="checkInDate"
            type="date"
            :min="today"
            class="w-full px-4 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all"
            :class="isPastDate ? 'border-red-300 bg-red-50' : ''"
          />
          <p v-if="isPastDate" class="text-xs text-red-600 flex items-center gap-1">
            <AlertCircle class="w-3 h-3" />
            Date cannot be in the past
          </p>
        </div>

        <div class="space-y-2">
          <label class="text-sm font-semibold text-slate-700">Check-out Date</label>
          <input
            v-model="checkOutDate"
            type="date"
            :min="setMinimumDate(1)"
            class="w-full px-4 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all"
            :class="checkInDate && checkOutDate && !isValidDateRange ? 'border-red-300 bg-red-50' : ''"
          />
          <p v-if="checkInDate && checkOutDate && !isValidDateRange" class="text-xs text-red-600 flex items-center gap-1">
            <AlertCircle class="w-3 h-3" />
            Must be after check-in
          </p>
        </div>

        <div class="space-y-2">
          <label class="text-sm font-semibold text-slate-700">Guests</label>
          <div class="flex items-center gap-2">
            <button
              @click="numberOfGuests = Math.max(1, numberOfGuests - 1)"
              class="w-10 h-10 border border-slate-300 rounded-lg flex items-center justify-center hover:bg-slate-100 transition-colors"
              type="button"
            >
              −
            </button>
            <input
              v-model.number="numberOfGuests"
              type="number"
              min="1"
              max="10"
              class="flex-1 text-center px-3 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-transparent"
            />
            <button
              @click="numberOfGuests = Math.min(10, numberOfGuests + 1)"
              class="w-10 h-10 border border-slate-300 rounded-lg flex items-center justify-center hover:bg-slate-100 transition-colors"
              type="button"
            >
              +
            </button>
          </div>
        </div>

        <div class="space-y-2">
          <label class="text-sm font-semibold text-slate-700">Duration</label>
          <div
            class="w-full px-4 py-2.5 border border-slate-300 rounded-lg bg-slate-50 flex items-center justify-center"
          >
            <span class="font-bold text-slate-900">
              {{ numberOfNights }} Night{{ numberOfNights !== 1 ? 's' : '' }}
            </span>
          </div>
        </div>
      </div>

      <div class="flex gap-3">
        <button
          @click="checkAvailability"
          :disabled="!canSearch || isCheckingAvailability"
          class="flex-1 bg-gradient-to-r from-amber-500 to-orange-600 hover:from-amber-600 hover:to-orange-700 disabled:opacity-50 disabled:cursor-not-allowed text-white font-semibold py-3 rounded-lg transition-all flex items-center justify-center gap-2"
        >
          <Search v-if="!isCheckingAvailability" class="w-5 h-5" />
          <Loader v-else class="w-5 h-5 animate-spin" />
          {{ isCheckingAvailability ? 'Searching...' : 'Search Available Rooms' }}
        </button>
        <button
          v-if="hasSearched"
          @click="resetSearch"
          class="px-6 border border-slate-300 hover:bg-slate-50 text-slate-700 font-semibold py-3 rounded-lg transition-all"
          type="button"
        >
          <X class="w-5 h-5" />
        </button>
      </div>

      <div v-if="!canSearch && (checkInDate || checkOutDate || numberOfGuests)" class="mt-4 p-4 bg-amber-50 border border-amber-200 rounded-lg text-sm text-amber-800">
        Please fill in all fields with valid dates to search for available rooms.
      </div>
    </div>

    <div v-if="hasSearched" class="space-y-4">
      <div v-if="availabilityError" class="bg-red-50 border border-red-200 rounded-lg p-4 flex items-start gap-3">
        <AlertCircle class="w-5 h-5 text-red-600 flex-shrink-0 mt-0.5" />
        <div>
          <p class="font-semibold text-red-900">No Availability</p>
          <p class="text-sm text-red-700 mt-1">{{ availabilityError }}</p>
        </div>
      </div>

      <div v-if="availableRooms.length > 0">
        <h3 class="text-xl font-bold text-slate-900 mb-4 flex items-center gap-2">
          <CheckCircle class="w-6 h-6 text-emerald-600" />
          {{ availableRooms.length }} Room{{ availableRooms.length !== 1 ? 's' : '' }} Available
        </h3>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          <div
            v-for="room in availableRooms"
            :key="room.id"
            class="bg-white border-2 rounded-xl p-5 hover:border-amber-500 hover:shadow-lg transition-all cursor-pointer"
            :class="selectedRoomId === room.id ? 'border-amber-500 bg-amber-50 shadow-lg' : 'border-slate-200'"
            @click="selectRoom(room)"
          >
            <div class="flex items-start justify-between mb-4">
              <div>
                <h4 class="font-bold text-slate-900 text-lg">
                  {{ room.room_type?.name || 'Standard Room' }}
                </h4>
                <p class="text-sm text-slate-500">Room #{{ room.room_number }} • Floor {{ room.floor }}</p>
              </div>
              <div class="text-right">
                <p class="text-2xl font-bold text-amber-600">
                  ETB {{ room.room_type?.base_price_per_night || 0 }}
                </p>
                <p class="text-xs text-slate-500">/night</p>
              </div>
            </div>

            <div class="space-y-3 mb-4 pb-4 border-b border-slate-200">
              <div class="flex items-center gap-2 text-sm text-slate-600">
                <Users class="w-4 h-4 text-amber-500" />
                <span>Up to {{ room.room_type?.capacity || 2 }} guests</span>
              </div>

              <div class="bg-amber-50 rounded-lg p-3 space-y-1">
                <div class="flex justify-between text-sm">
                  <span class="text-slate-600">{{ numberOfNights }} night{{ numberOfNights !== 1 ? 's' : '' }}:</span>
                  <span class="font-semibold text-slate-900">
                    ETB {{ (room.room_type?.base_price_per_night || 0) * numberOfNights }}
                  </span>
                </div>
                <div class="flex justify-between text-xs text-slate-500">
                  <span>(ETB {{ room.room_type?.base_price_per_night || 0 }} × {{ numberOfNights }})</span>
                </div>
              </div>
            </div>

            <div v-if="room.room_type?.amenities && room.room_type.amenities.length > 0" class="mb-4">
              <p class="text-xs font-semibold text-slate-700 mb-2">Amenities:</p>
              <div class="flex flex-wrap gap-1.5">
                <span
                  v-for="amenity in room.room_type.amenities.slice(0, 3)"
                  :key="amenity"
                  class="inline-flex items-center gap-1 text-xs bg-slate-100 text-slate-700 px-2.5 py-1 rounded-full"
                >
                  <CheckCircle class="w-3 h-3 text-emerald-500" />
                  {{ amenity }}
                </span>
                <span
                  v-if="room.room_type.amenities.length > 3"
                  class="text-xs text-slate-400"
                >
                  +{{ room.room_type.amenities.length - 3 }}
                </span>
              </div>
            </div>

            <button
              class="w-full py-2.5 rounded-lg font-semibold transition-all"
              :class="
                selectedRoomId === room.id
                  ? 'bg-amber-600 text-white hover:bg-amber-700'
                  : 'bg-slate-100 text-slate-900 hover:bg-slate-200'
              "
            >
              {{ selectedRoomId === room.id ? '✓ Selected' : 'Select Room' }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <div v-if="!hasSearched && props.qrToken" class="bg-blue-50 border border-blue-200 rounded-lg p-4 flex items-start gap-3">
      <AlertCircle class="w-5 h-5 text-blue-600 flex-shrink-0 mt-0.5" />
      <div class="text-sm text-blue-800">
        <p class="font-semibold">Select your dates and number of guests to check availability</p>
      </div>
    </div>
  </div>
</template>

<style scoped></style>
