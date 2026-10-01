<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { X } from 'lucide-vue-next'
import api from '@/api/auth'
import { useReservationStore } from '@/stores/reservationStore'
import { useLanguageStore } from '@/stores/language'

const languageStore = useLanguageStore()

interface Room {
  id: string | number
  hotel_id?: string | number
  room_number: string | number
  floor?: number
  status?: string
  description?: string
  room_type?: {
    id?: string | number
    name?: string
    capacity?: number
    base_price_per_night?: number
  } | string | null
}

interface Props {
  rooms: Room[]
  loading?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  loading: false,
})

const emit = defineEmits<{
  submit: [formData: any]
}>()

const newGuestForm = ref({
  first_name: '',
  last_name: '',
  email: '',
  phone: '',
})

const registrationError = ref('')
const registrationSuccess = ref('')

const form = ref({
  guest_id: '',
  room_id: '' as string | number,
  check_in_date: '',
  check_out_date: '',
  number_of_guests: 1,
  special_requests: '',
})

const roomSearch = ref('')
const showRoomDropdown = ref(false)
const showPaymentDialog = ref(false)
const paymentLoading = ref(false)

const filteredRooms = computed(() => {
  let search = roomSearch.value.toLowerCase().trim()

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
      console.error('[ReservationForm] Filter room search error:', e)
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

const selectedRoom = computed(() => {
  if (!form.value.room_id) return null
  return props.rooms.find(r => r.id === form.value.room_id)
})

const pricePerNight = computed(() => {
  if (!selectedRoom.value) return 0
  if (typeof selectedRoom.value.room_type === 'object' && selectedRoom.value.room_type) {
    return selectedRoom.value.room_type.base_price_per_night || 0
  }
  return 0
})

const subtotal = computed(() => {
  return nights.value * pricePerNight.value
})

const taxAmount = computed(() => {
  return subtotal.value * 0.15
})

const totalAmount = computed(() => {
  return subtotal.value + taxAmount.value
})

const reservationStore = useReservationStore()
const isCheckingAvailability = ref(false)
const availabilityStatus = ref<{ available: boolean; message: string } | null>(null)

const roomCapacity = computed(() => {
  if (!selectedRoom.value) return 99
  if (typeof selectedRoom.value.room_type === 'object' && selectedRoom.value.room_type?.capacity) {
    return Number(selectedRoom.value.room_type.capacity)
  }
  return 2
})

const isCapacityExceeded = computed(() => {
  return Boolean(form.value.room_id) && Number(form.value.number_of_guests) > roomCapacity.value
})

const checkRoomAvailability = async () => {
  if (
    !form.value.room_id ||
    !form.value.check_in_date ||
    !form.value.check_out_date ||
    !isValidDateRange.value ||
    isPastDate.value
  ) {
    availabilityStatus.value = null
    return
  }

  isCheckingAvailability.value = true
  try {
    const res = await reservationStore.checkAvailability({
      room_id: String(form.value.room_id),
      check_in_date: form.value.check_in_date,
      check_out_date: form.value.check_out_date,
    })
    availabilityStatus.value = {
      available: Boolean(res.available),
      message: res.message || (res.available ? 'Room is available for selected dates.' : 'Room is already booked for selected dates.'),
    }
  } catch (err: any) {
    console.error('[ReservationForm] Check room availability error:', err)
    availabilityStatus.value = {
      available: false,
      message: err.response?.data?.message || 'Selected room is not available for these dates.',
    }
  } finally {
    isCheckingAvailability.value = false
  }
}

watch(
  () => [form.value.room_id, form.value.check_in_date, form.value.check_out_date],
  () => {
    checkRoomAvailability()
  }
)

function openPaymentDialog() {
  if (isPastDate.value) {
    alert('Check-in date cannot be in the past')
    return
  }

  if (!isValidDateRange.value) {
    alert('Check-out date must be after check-in date')
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

  if (!selectedRoom.value) {
    alert('Room not found')
    return
  }

  if (isCapacityExceeded.value) {
    alert(`Selected room maximum capacity is ${roomCapacity.value} guest(s). Please choose another room or reduce number of guests.`)
    return
  }

  if (availabilityStatus.value && !availabilityStatus.value.available) {
    alert('This room is not available for the selected dates in this hotel.')
    return
  }

  showPaymentDialog.value = true
}

const directBookingLoading = ref(false)

const bookDirectly = async () => {
  if (!form.value.guest_id) {
    alert('Please select or register a guest')
    return
  }
  if (!form.value.check_in_date || !form.value.check_out_date) {
    alert('Please select both check-in and check-out dates')
    return
  }
  if (!isValidDateRange.value) {
    alert('Check-out date must be after check-in date')
    return
  }
  if (isPastDate.value) {
    alert('Check-in date cannot be in the past')
    return
  }
  if (!form.value.room_id) {
    alert('Please select a room')
    return
  }
  if (isCapacityExceeded.value) {
    alert(`Selected room maximum capacity is ${roomCapacity.value} guest(s).`)
    return
  }
  if (availabilityStatus.value && !availabilityStatus.value.available) {
    alert('This room is not available for the selected dates in this hotel.')
    return
  }

  directBookingLoading.value = true
  try {
    const payload = {
      ...form.value,
      status: 'confirmed',
      total_amount: totalAmount.value,
    }
    const response = await api.post('/reservations', payload)
    if (response.data) {
      alert('✓ Reservation created and automatically confirmed! Confirmation email sent to the guest.')
      window.location.href = '/reservations'
    }
  } catch (error: any) {
    console.error('[ReservationForm] Direct booking error:', error)
    const msg = error.response?.data?.message || error.message || 'Failed to create reservation'
    alert(msg)
  } finally {
    directBookingLoading.value = false
  }
}

function closePaymentDialog() {
  showPaymentDialog.value = false
}

const proceedToPayment = async () => {
  paymentLoading.value = true

  try {
    const paymentResponse = await api.post(
      '/reservation-payments/initialize',
      {
        room_id: form.value.room_id,
        guest_id: form.value.guest_id,
        check_in_date: form.value.check_in_date,
        check_out_date: form.value.check_out_date,
        number_of_guests: form.value.number_of_guests,
        special_requests: form.value.special_requests,
        first_name: newGuestForm.value.first_name,
        last_name: newGuestForm.value.last_name,
        email: newGuestForm.value.email,
        phone: newGuestForm.value.phone,
      }
    )

    if (paymentResponse.data.success && paymentResponse.data.checkout_url) {
      const reservationData = {
        payment_id: paymentResponse.data.payment_id,
        tx_ref: paymentResponse.data.tx_ref,
        room_id: form.value.room_id,
        guest_id: form.value.guest_id,
        check_in_date: form.value.check_in_date,
        check_out_date: form.value.check_out_date,
        number_of_guests: form.value.number_of_guests,
        special_requests: form.value.special_requests,
        first_name: newGuestForm.value.first_name,
        last_name: newGuestForm.value.last_name,
        email: newGuestForm.value.email,
        phone: newGuestForm.value.phone,
        total_amount: paymentResponse.data.amount,
        timestamp: new Date().toISOString(),
      }
      
      sessionStorage.setItem('reservationPaymentData', JSON.stringify(reservationData))

      showPaymentDialog.value = false
      window.location.href = paymentResponse.data.checkout_url
    } else {
      const message = paymentResponse.data.message || 'Failed to initialize payment'
      alert('Failed to initialize payment: ' + message)
      paymentLoading.value = false
    }
  } catch (error: any) {
    console.error('[ReservationForm] Payment initialization error:', error)
    let message = 'An error occurred'
    if (error.response?.data?.error) {
      message = error.response.data.error
    } else if (error.response?.data?.message) {
      message = error.response.data.message
    } else if (error.message) {
      message = error.message
    }
    
    alert('Payment initialization failed: ' + message)
    paymentLoading.value = false
  }
}

async function registerGuest() {
  registrationError.value = ''
  registrationSuccess.value = ''

  if (!newGuestForm.value.first_name || !newGuestForm.value.last_name || !newGuestForm.value.phone) {
    registrationError.value = 'First name, last name, and phone are required'
    return
  }

  try {
    const response = await api.post('/guests', newGuestForm.value)
    const newGuest = response.data?.data || response.data

    form.value.guest_id = newGuest.id

    registrationSuccess.value = `✓ Guest registered successfully! Welcome, ${newGuest.first_name} ${newGuest.last_name}`

    setTimeout(() => {
      registrationSuccess.value = ''
      Object.assign(newGuestForm.value, {
        first_name: '',
        last_name: '',
        email: '',
        phone: '',
      })
    }, 2000)
  } catch (error: any) {
    console.error('[ReservationForm] Guest registration error:', error)
    registrationError.value = error.response?.data?.message || error.message || 'Failed to register guest. Please try again.'
  }
}
</script>

<template>
  <div
    class="bg-white dark:bg-slate-800 rounded-lg sm:rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4 sm:p-5 md:p-6 space-y-5 sm:space-y-6"
  >
    <div>
      <h2 class="text-lg sm:text-xl md:text-2xl font-semibold text-slate-900 dark:text-white">{{ languageStore.t('reservation_form', 'Reservation Form') }}</h2>
      <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1">{{ languageStore.t('complete_booking_details', 'Complete your booking details') }}</p>
    </div>

    <div class="border-2 border-blue-300 dark:border-blue-700/60 bg-blue-50 dark:bg-blue-950/20 rounded-lg p-4 sm:p-6">
      <h3 class="text-base sm:text-lg font-semibold text-slate-900 dark:text-white mb-4">{{ languageStore.t('guest_information', 'Guest Information') }}</h3>

      <div
        v-if="registrationError"
        class="mb-4 rounded-lg bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 p-3 text-red-700 dark:text-red-300 text-xs sm:text-sm"
      >
        {{ registrationError }}
      </div>

      <div
        v-if="registrationSuccess"
        class="mb-4 rounded-lg bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800 p-3 text-green-700 dark:text-green-300 text-xs sm:text-sm"
      >
        {{ registrationSuccess }}
      </div>

      <div class="space-y-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">
          <div>
            <label class="block text-xs sm:text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">
              {{ languageStore.t('first_name', 'First Name') }} <span class="text-red-500">*</span>
            </label>
            <input
              v-model="newGuestForm.first_name"
              type="text"
              placeholder="John"
              class="w-full border border-slate-300 dark:border-slate-700 rounded-lg px-3 sm:px-3.5 py-2 sm:py-2.5 text-xs sm:text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-slate-900 text-slate-900 dark:text-white"
            />
          </div>
          <div>
            <label class="block text-xs sm:text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">
              {{ languageStore.t('last_name', 'Last Name') }} <span class="text-red-500">*</span>
            </label>
            <input
              v-model="newGuestForm.last_name"
              type="text"
              placeholder="Doe"
              class="w-full border border-slate-300 dark:border-slate-700 rounded-lg px-3 sm:px-3.5 py-2 sm:py-2.5 text-xs sm:text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-slate-900 text-slate-900 dark:text-white"
            />
          </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">
          <div>
            <label class="block text-xs sm:text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">
              {{ languageStore.t('email', 'Email') }} <span class="text-slate-500 text-xs">({{ languageStore.t('optional', 'Optional') }})</span>
            </label>
            <input
              v-model="newGuestForm.email"
              type="email"
              placeholder="john@gmail.com"
              class="w-full border border-slate-300 dark:border-slate-700 rounded-lg px-3 sm:px-3.5 py-2 sm:py-2.5 text-xs sm:text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-slate-900 text-slate-900 dark:text-white"
            />
          </div>
          <div>
            <label class="block text-xs sm:text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">
              {{ languageStore.t('phone', 'Phone') }} <span class="text-red-500">*</span>
            </label>
            <input
              v-model="newGuestForm.phone"
              type="tel"
              placeholder="+251912345678"
              class="w-full border border-slate-300 dark:border-slate-700 rounded-lg px-3 sm:px-3.5 py-2 sm:py-2.5 text-xs sm:text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-slate-900 text-slate-900 dark:text-white"
            />
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Use format: +251912345678 or 0912345678</p>
          </div>
        </div>

        <button
          type="button"
          @click="registerGuest"
          class="w-full px-4 sm:px-6 py-2.5 sm:py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-semibold text-xs sm:text-sm transition-all cursor-pointer"
        >
          ✓ {{ languageStore.t('register_and_continue', 'Register & Continue') }}
        </button>
      </div>

      <p class="mt-4 text-xs sm:text-sm text-slate-600 dark:text-slate-400 text-center">
        {{ languageStore.t('info_helps_service', 'Your information helps us provide better service during your stay') }}
      </p>
    </div>

    <div class="relative">
      <label class="block text-xs sm:text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5 sm:mb-2">
        {{ languageStore.t('room', 'Room') }} <span class="text-red-500">*</span>
      </label>

      <input
        type="text"
        v-model="roomSearch"
        @focus="showRoomDropdown = true"
        :placeholder="languageStore.t('search_room_placeholder', 'Search by room number, type...')"
        class="w-full border border-slate-300 dark:border-slate-700 rounded-lg px-3 sm:px-3.5 py-2 sm:py-2.5 text-xs sm:text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200 bg-white dark:bg-slate-900 text-slate-900 dark:text-white"
        :class="{ 'border-red-500 ring-2 ring-red-200': !form.room_id && form.room_id !== '' }"
      />

      <div v-if="form.room_id && !showRoomDropdown" class="text-xs text-slate-600 dark:text-slate-400 mt-1">
        ✓ {{ languageStore.t('selected', 'Selected') }}:
        {{
          selectedRoom
            ? formatRoomDisplay(selectedRoom)
            : languageStore.t('loading', 'Loading...')
        }}
      </div>

      <div
        v-if="showRoomDropdown"
        class="absolute top-full left-0 right-0 mt-1 bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg shadow-lg z-50 max-h-64 overflow-y-auto"
      >
        <div
          v-if="filteredRooms.length === 0"
          class="p-3 sm:p-4 text-slate-500 dark:text-slate-400 text-center text-xs sm:text-sm"
        >
          {{ languageStore.t('no_rooms_found', 'No rooms found') }}
        </div>

        <div
          v-for="room in filteredRooms"
          :key="room.id"
          @click="selectRoom(room)"
          class="p-2 sm:p-3 hover:bg-blue-50 dark:hover:bg-slate-800 cursor-pointer border-b border-slate-100 dark:border-slate-800 last:border-b-0 text-xs sm:text-sm transition duration-150"
          :class="{ 'bg-blue-100 dark:bg-slate-800': form.room_id === room.id }"
        >
          <div class="font-medium text-slate-900 dark:text-white">{{ languageStore.t('room', 'Room') }} {{ room.room_number }}</div>
          <div class="text-xs text-slate-600 dark:text-slate-400 mt-0.5">
            {{ typeof room.room_type === 'object' && room.room_type ? room.room_type.name : (room.room_type || 'Standard') }}
            <template v-if="typeof room.room_type === 'object' && room.room_type?.capacity">
              - {{ room.room_type.capacity }} {{ languageStore.t('guests', 'guests') }}
            </template>
          </div>
          <div class="text-xs text-slate-500 dark:text-slate-400">{{ formatRoomDisplay(room) }}</div>
        </div>
      </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4 md:gap-5">
      <div>
        <label class="block text-xs sm:text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5 sm:mb-2">
          {{ languageStore.t('check_in', 'Check In') }} <span class="text-red-500">*</span>
        </label>
        <input
          type="date"
          v-model="form.check_in_date"
          class="w-full border border-slate-300 dark:border-slate-700 rounded-lg px-3 sm:px-3.5 py-2 sm:py-2.5 text-xs sm:text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200 bg-white dark:bg-slate-900 text-slate-900 dark:text-white"
          :min="today"
        />
        <p v-if="isPastDate" class="text-red-500 text-xs sm:text-sm mt-1">
          {{ languageStore.t('check_in_past_error', 'Check-in cannot be in the past') }}
        </p>
      </div>

      <div>
        <label class="block text-xs sm:text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5 sm:mb-2">
          {{ languageStore.t('check_out', 'Check Out') }} <span class="text-red-500">*</span>
        </label>
        <input
          type="date"
          v-model="form.check_out_date"
          class="w-full border border-slate-300 dark:border-slate-700 rounded-lg px-3 sm:px-3.5 py-2 sm:py-2.5 text-xs sm:text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200 bg-white dark:bg-slate-900 text-slate-900 dark:text-white"
          :min="form.check_in_date || today"
        />
        <p v-if="!isValidDateRange" class="text-red-500 text-xs sm:text-sm mt-1">
          {{ languageStore.t('checkout_after_checkin', 'Check-out must be after check-in') }}
        </p>
      </div>
    </div>

    <div v-if="isCheckingAvailability || availabilityStatus" class="text-xs sm:text-sm font-medium">
      <span v-if="isCheckingAvailability" class="text-blue-600 dark:text-blue-400 flex items-center gap-1.5">
        <span class="animate-spin">⏳</span> {{ languageStore.t('checking_availability', 'Checking room availability for selected dates...') }}
      </span>
      <span v-else-if="availabilityStatus?.available" class="text-emerald-700 dark:text-emerald-300 flex items-center gap-1.5 bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-800 px-3 py-1.5 rounded-lg">
        ✓ {{ availabilityStatus.message }}
      </span>
      <span v-else-if="availabilityStatus && !availabilityStatus.available" class="text-rose-700 dark:text-rose-300 flex items-center gap-1.5 bg-rose-50 dark:bg-rose-950/30 border border-rose-200 dark:border-rose-800 px-3 py-1.5 rounded-lg">
        ✗ {{ availabilityStatus.message }}
      </span>
    </div>

    <div>
      <div class="flex justify-between items-center mb-1.5 sm:mb-2">
        <label class="block text-xs sm:text-sm font-medium text-slate-700 dark:text-slate-300">
          {{ languageStore.t('number_of_guests', 'Number of Guests') }} <span class="text-red-500">*</span>
        </label>
        <span v-if="selectedRoom && roomCapacity < 90" class="text-xs text-slate-500 dark:text-slate-400">
          Max Capacity: {{ roomCapacity }} {{ languageStore.t('guests', 'guests') }}
        </span>
      </div>
      <input
        type="number"
        v-model="form.number_of_guests"
        class="w-full border border-slate-300 dark:border-slate-700 rounded-lg px-3 sm:px-3.5 py-2 sm:py-2.5 text-xs sm:text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200 bg-white dark:bg-slate-900 text-slate-900 dark:text-white"
        :class="{ 'border-rose-500 focus:ring-rose-500': isCapacityExceeded }"
        min="1"
        max="99"
      />
      <p v-if="isCapacityExceeded" class="text-rose-600 dark:text-rose-400 text-xs sm:text-sm mt-1 font-medium">
        {{ languageStore.t('room_capacity_exceeded', `Room capacity exceeded (maximum ${roomCapacity} guests for this room).`) }}
      </p>
    </div>

    <div>
      <label class="block text-xs sm:text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5 sm:mb-2">
        {{ languageStore.t('special_requests', 'Special Requests') }} <span class="text-slate-500 text-xs">({{ languageStore.t('optional', 'Optional') }})</span>
      </label>
      <textarea
        v-model="form.special_requests"
        rows="3"
        class="w-full border border-slate-300 dark:border-slate-700 rounded-lg px-3 sm:px-3.5 py-2 sm:py-2.5 text-xs sm:text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200 resize-none bg-white dark:bg-slate-900 text-slate-900 dark:text-white"
        :placeholder="languageStore.t('special_requests_placeholder', 'Any special requirements or preferences...')"
      ></textarea>
    </div>

    <div
      class="bg-gradient-to-r from-blue-50 to-blue-100/50 dark:from-blue-950/30 dark:to-indigo-950/30 p-3 sm:p-4 rounded-lg border border-blue-200 dark:border-blue-800/50 text-xs sm:text-sm"
    >
      <div class="flex justify-between items-center">
        <span class="text-slate-700 dark:text-slate-300 font-medium">{{ languageStore.t('total_nights', 'Total Nights') }}:</span>
        <span class="text-lg sm:text-xl font-bold text-blue-600 dark:text-blue-400"
          >{{ nights }} {{ nights === 1 ? languageStore.t('night', 'night') : languageStore.t('nights', 'nights') }}</span
        >
      </div>
    </div>

    <div
      v-if="form.room_id && form.check_in_date && form.check_out_date && isValidDateRange && nights > 0"
      class="bg-gradient-to-r from-green-50 to-emerald-50 dark:from-emerald-950/30 dark:to-teal-950/30 p-4 sm:p-5 rounded-lg border border-green-200 dark:border-emerald-800/50"
    >
      <h3 class="font-semibold text-slate-900 dark:text-white text-sm mb-3">{{ languageStore.t('price_breakdown', 'Price Breakdown') }}</h3>
      <div class="space-y-2 text-xs sm:text-sm">
        <div class="flex justify-between text-slate-700 dark:text-slate-300">
          <span>{{ nights }} {{ nights === 1 ? languageStore.t('night', 'night') : languageStore.t('nights', 'nights') }} × {{ pricePerNight }} ETB</span>
          <span class="font-medium">{{ subtotal.toFixed(2) }} ETB</span>
        </div>
        <div class="flex justify-between text-slate-700 dark:text-slate-300">
          <span>{{ languageStore.t('tax', 'Tax') }} (15%)</span>
          <span class="font-medium">{{ taxAmount.toFixed(2) }} ETB</span>
        </div>
        <div class="border-t border-green-200 dark:border-emerald-800/50 pt-2 flex justify-between">
          <span class="font-semibold text-slate-900 dark:text-white">{{ languageStore.t('total_amount', 'Total Amount') }}</span>
          <span class="text-lg font-bold text-green-600 dark:text-emerald-400">{{ totalAmount.toFixed(2) }} ETB</span>
        </div>
      </div>
      <p class="text-xs text-slate-500 dark:text-slate-400 mt-3">💳 {{ languageStore.t('chapa_secure_msg', 'Your payment is secure and processed through Chapa payment gateway') }}</p>
    </div>

    <div class="flex flex-col-reverse sm:flex-row justify-end gap-2 sm:gap-3 pt-2">
      <button
        type="button"
        @click="$router ? $router.push('/reservations') : null"
        class="px-4 sm:px-5 py-2 sm:py-2.5 text-xs sm:text-sm font-medium border border-slate-300 dark:border-slate-600 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 transition duration-200 cursor-pointer"
      >
        {{ languageStore.t('cancel', 'Cancel') }}
      </button>

      <!-- Direct Book & Confirm (Pay at Desk) -->
      <button
        type="button"
        @click="bookDirectly"
        :disabled="directBookingLoading || loading || !isValidDateRange || isPastDate || !form.guest_id || !form.room_id || isCapacityExceeded || (availabilityStatus !== null && !availabilityStatus.available)"
        class="px-4 sm:px-6 py-2 sm:py-2.5 text-xs sm:text-sm font-bold bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg transition duration-200 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-1.5 shadow-sm"
      >
        <span v-if="directBookingLoading" class="animate-spin mr-1">⌛</span>
        <span>✓ {{ languageStore.t('book_and_confirm', 'Confirm & Book (Pay at Desk)') }}</span>
      </button>

      <!-- Pay Online via Chapa -->
      <button
        type="button"
        @click="openPaymentDialog"
        :disabled="loading || directBookingLoading || !isValidDateRange || isPastDate || !form.guest_id || !form.room_id || isCapacityExceeded || (availabilityStatus !== null && !availabilityStatus.available)"
        class="px-4 sm:px-6 py-2 sm:py-2.5 text-xs sm:text-sm font-medium bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition duration-200 cursor-pointer flex items-center justify-center gap-1.5"
      >
        <span v-if="loading" class="inline-flex items-center gap-2">
          <span class="animate-spin">⌛</span>
          {{ languageStore.t('processing', 'Processing...') }}
        </span>
        <span v-else>💳 {{ languageStore.t('pay_online_chapa', 'Pay Online (Chapa)') }}</span>
      </button>
    </div>

    <div v-if="showPaymentDialog" class="fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-50 p-3 sm:p-4 overflow-hidden" @click.self="closePaymentDialog">
      <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-2xl max-w-md w-full max-h-[90vh] flex flex-col overflow-hidden border border-slate-200 dark:border-slate-800 animate-in fade-in zoom-in-95 duration-200">
        <div class="bg-gradient-to-r from-blue-600 via-blue-700 to-indigo-700 px-5 py-4 text-white flex items-center justify-between flex-shrink-0">
          <div>
            <h3 class="text-lg sm:text-xl font-bold">{{ languageStore.t('payment_confirmation', 'Payment Confirmation') }}</h3>
            <p class="text-xs text-blue-100">{{ languageStore.t('complete_booking_details', 'Review your booking details before payment') }}</p>
          </div>
          <button
            @click="closePaymentDialog"
            class="text-white/80 hover:text-white hover:bg-white/20 p-1.5 rounded-lg transition cursor-pointer"
            title="Close"
          >
            <X class="w-5 h-5" />
          </button>
        </div>

        <div class="p-5 space-y-3.5 overflow-y-auto flex-1 text-slate-800 dark:text-slate-200">
          <div class="space-y-2.5 bg-slate-50 dark:bg-slate-800/50 p-3.5 rounded-xl border border-slate-200/80 dark:border-slate-700/60">
            <h4 class="text-xs uppercase tracking-wider font-extrabold text-slate-500 dark:text-slate-400">{{ languageStore.t('booking_summary', 'Booking Summary') }}</h4>
            
            <div class="flex justify-between items-start text-sm">
              <span class="text-slate-600 dark:text-slate-400">{{ languageStore.t('room', 'Room') }}:</span>
              <span class="font-bold text-slate-900 dark:text-white">#{{ selectedRoom?.room_number || 'N/A' }}</span>
            </div>

            <div class="flex justify-between items-start text-sm">
              <span class="text-slate-600 dark:text-slate-400">{{ languageStore.t('check_in', 'Check-in') }}:</span>
              <span class="font-medium text-slate-900 dark:text-white">{{ form.check_in_date }}</span>
            </div>

            <div class="flex justify-between items-start text-sm">
              <span class="text-slate-600 dark:text-slate-400">{{ languageStore.t('check_out', 'Check-out') }}:</span>
              <span class="font-medium text-slate-900 dark:text-white">{{ form.check_out_date }}</span>
            </div>

            <div class="flex justify-between items-start text-sm pt-1 border-t border-slate-200/60 dark:border-slate-700/60">
              <span class="text-slate-600 dark:text-slate-400">{{ languageStore.t('stay_history', 'Stay') }}:</span>
              <span class="font-semibold text-slate-900 dark:text-white">{{ nights }} {{ nights > 1 ? languageStore.t('nights', 'Nights') : languageStore.t('night', 'Night') }} • {{ form.number_of_guests }} {{ form.number_of_guests > 1 ? languageStore.t('guests', 'Guests') : languageStore.t('guest', 'Guest') }}</span>
            </div>
          </div>

          <div class="space-y-2 bg-slate-50 dark:bg-slate-800/50 p-3.5 rounded-xl border border-slate-200/80 dark:border-slate-700/60">
            <h4 class="text-xs uppercase tracking-wider font-extrabold text-slate-500 dark:text-slate-400">{{ languageStore.t('price_breakdown', 'Price Breakdown') }}</h4>

            <div class="flex justify-between text-sm">
              <span class="text-slate-600 dark:text-slate-400">{{ nights }} {{ languageStore.t('nights', 'nights') }} × {{ pricePerNight }} ETB</span>
              <span class="font-semibold text-slate-900 dark:text-white">{{ subtotal.toFixed(2) }} ETB</span>
            </div>

            <div class="flex justify-between text-sm">
              <span class="text-slate-600 dark:text-slate-400">{{ languageStore.t('tax', 'Tax') }} (15%)</span>
              <span class="font-medium text-slate-900 dark:text-white">{{ taxAmount.toFixed(2) }} ETB</span>
            </div>

            <div class="flex justify-between text-base font-extrabold pt-2 border-t border-slate-300 dark:border-slate-600 text-slate-900 dark:text-white">
              <span>{{ languageStore.t('total_amount', 'Total Amount') }}:</span>
              <span class="text-blue-600 dark:text-blue-400 text-lg">{{ totalAmount.toFixed(2) }} ETB</span>
            </div>
          </div>

          <div class="bg-blue-50 dark:bg-blue-950/40 border border-blue-200 dark:border-blue-800/60 rounded-xl p-3 text-xs text-blue-800 dark:text-blue-300">
            ✓ {{ languageStore.t('chapa_secure_msg', 'Your payment is secure and processed through Chapa payment gateway') }}
          </div>
        </div>

        <div class="bg-slate-50 dark:bg-slate-800/90 px-5 py-3.5 border-t border-slate-200 dark:border-slate-700 flex gap-3 flex-shrink-0 shadow-lg">
          <button
            @click="closePaymentDialog"
            :disabled="paymentLoading"
            class="flex-1 px-4 py-2.5 text-sm font-bold border-2 border-slate-300 dark:border-slate-600 rounded-xl hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 transition duration-200 disabled:opacity-50 cursor-pointer"
          >
            {{ languageStore.t('cancel', 'Cancel') }}
          </button>

          <button
            @click="proceedToPayment"
            :disabled="paymentLoading"
            class="flex-1 px-4 py-2.5 text-sm font-bold bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white rounded-xl transition duration-200 disabled:opacity-50 flex items-center justify-center gap-2 shadow-md shadow-blue-500/25 cursor-pointer"
          >
            <span v-if="paymentLoading" class="animate-spin">⌛</span>
            <span v-if="paymentLoading">{{ languageStore.t('processing', 'Processing...') }}</span>
            <span v-else>💳 {{ languageStore.t('pay_now', 'Pay Now') }} ({{ totalAmount.toFixed(2) }} ETB)</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
