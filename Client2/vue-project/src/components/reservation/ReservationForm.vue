<script setup lang="ts">
import { ref, computed } from 'vue'
import axios from 'axios'

interface Room {
  id: number
  room_number: string | number
  floor: number
  status: string
  description: string
  room_type: {
    id: number
    name: string
    capacity: number
    base_price_per_night: number
  } | string
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

// Registration form
const newGuestForm = ref({
  first_name: '',
  last_name: '',
  email: '',
  phone: '',
})

const registrationError = ref('')
const registrationSuccess = ref('')

// Reservation form
const form = ref({
  guest_id: '',
  room_id: '',
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

// Get the selected room object
const selectedRoom = computed(() => {
  if (!form.value.room_id) return null
  return props.rooms.find(r => r.id === form.value.room_id)
})

// Get price per night from selected room
const pricePerNight = computed(() => {
  if (!selectedRoom.value) return 0
  return selectedRoom.value.room_type?.base_price_per_night || 0
})

// Calculate subtotal (nights × price per night)
const subtotal = computed(() => {
  return nights.value * pricePerNight.value
})

// Calculate tax (15%)
const taxAmount = computed(() => {
  return subtotal.value * 0.15
})

// Calculate total amount (subtotal + tax)
const totalAmount = computed(() => {
  return subtotal.value + taxAmount.value
})

// Open payment dialog - validates form before showing dialog
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

  // All validations passed, show dialog
  showPaymentDialog.value = true
}

// Close payment dialog without proceeding
function closePaymentDialog() {
  showPaymentDialog.value = false
}

// Proceed to payment - called when user clicks "Pay Now" in dialog
const proceedToPayment = async () => {
  paymentLoading.value = true

  try {
    console.log('[RESERVATION] Initiating payment with data:', {
      room_id: form.value.room_id,
      guest_id: form.value.guest_id,
      check_in_date: form.value.check_in_date,
      check_out_date: form.value.check_out_date,
      number_of_guests: form.value.number_of_guests,
      total_amount: totalAmount.value,
    })

    // Initialize payment with reservation details
    const paymentResponse = await axios.post(
      'http://127.0.0.1:8000/api/reservation-payments/initialize',
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

    console.log('[RESERVATION] Payment initialization response:', paymentResponse.data)

    if (paymentResponse.data.success && paymentResponse.data.checkout_url) {
      // Store reservation data in sessionStorage for post-payment verification
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

      console.log('[RESERVATION] Redirecting to Chapa checkout:', paymentResponse.data.checkout_url)

      // Close dialog and redirect to Chapa checkout
      showPaymentDialog.value = false
      window.location.href = paymentResponse.data.checkout_url
    } else {
      const message = paymentResponse.data.message || 'Failed to initialize payment'
      console.error('[RESERVATION] Payment initialization failed:', message)
      alert('Failed to initialize payment: ' + message)
      paymentLoading.value = false
    }
  } catch (error: any) {
    console.error('[RESERVATION] Payment error:', error)
    console.error('[RESERVATION] Full error response:', error.response?.data)
    
    let message = 'An error occurred'
    if (error.response?.data?.error) {
      message = error.response.data.error
    } else if (error.response?.data?.message) {
      message = error.response.data.message
    } else if (error.message) {
      message = error.message
    }
    
    // If there's debug info, log it
    if (error.response?.data?.debug_info) {
      console.error('[RESERVATION] Debug Info:', error.response.data.debug_info)
    }
    
    alert('Payment initialization failed: ' + message)
    paymentLoading.value = false
  }
}

// Register new guest
async function registerGuest() {
  registrationError.value = ''
  registrationSuccess.value = ''

  // Validate required fields
  if (!newGuestForm.value.first_name || !newGuestForm.value.last_name || !newGuestForm.value.phone) {
    registrationError.value = 'First name, last name, and phone are required'
    return
  }

  try {
    const response = await fetch('http://127.0.0.1:8000/api/guests', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(newGuestForm.value),
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
      Object.assign(newGuestForm.value, {
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
              placeholder="john@gmail.com"
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
              placeholder="+251912345678"
              class="w-full border border-slate-300 rounded-lg px-3 sm:px-3.5 py-2 sm:py-2.5 text-xs sm:text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            />
            <p class="text-xs text-slate-500 mt-1">Use format: +251912345678 or 0912345678</p>
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
          selectedRoom
            ? formatRoomDisplay(selectedRoom)
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
          Check-in cannot be in the past
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
          Check-out must be after check-in
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

    <!-- Price Breakdown (if room selected and dates valid) -->
    <div
      v-if="form.room_id && form.check_in_date && form.check_out_date && isValidDateRange && nights > 0"
      class="bg-gradient-to-r from-green-50 to-emerald-50 p-4 sm:p-5 rounded-lg border border-green-200"
    >
      <h3 class="font-semibold text-slate-900 text-sm mb-3">Price Breakdown</h3>
      <div class="space-y-2 text-xs sm:text-sm">
        <!-- Subtotal row -->
        <div class="flex justify-between text-slate-700">
          <span>{{ nights }} {{ nights === 1 ? 'night' : 'nights' }} × {{ pricePerNight }} ETB</span>
          <span class="font-medium">{{ subtotal.toFixed(2) }} ETB</span>
        </div>
        <!-- Tax row -->
        <div class="flex justify-between text-slate-700">
          <span>Tax (15%)</span>
          <span class="font-medium">{{ taxAmount.toFixed(2) }} ETB</span>
        </div>
        <!-- Total row -->
        <div class="border-t border-green-200 pt-2 flex justify-between">
          <span class="font-semibold text-slate-900">Total Amount</span>
          <span class="text-lg font-bold text-green-600">{{ totalAmount.toFixed(2) }} ETB</span>
        </div>
      </div>
      <p class="text-xs text-slate-500 mt-3">💳 You will pay this amount via Chapa Payment Gateway</p>
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
        @click="openPaymentDialog"
        :disabled="loading || !isValidDateRange || isPastDate || !form.guest_id || !form.room_id"
        class="px-4 sm:px-6 py-2 sm:py-2.5 text-xs sm:text-sm font-medium bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition duration-200"
      >
        <span v-if="loading" class="inline-flex items-center gap-2">
          <span class="animate-spin">⌛</span>
          Processing...
        </span>
        <span v-else>💳 Proceed to Payment</span>
      </button>
    </div>

    <!-- Payment Confirmation Dialog -->
    <div v-if="showPaymentDialog" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full overflow-hidden">
        <!-- Dialog Header -->
        <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-8 text-white">
          <h3 class="text-2xl font-bold mb-2">Payment Confirmation</h3>
          <p class="text-blue-100">Review your booking details before payment</p>
        </div>

        <!-- Dialog Content -->
        <div class="p-6 space-y-4">
          <!-- Booking Summary -->
          <div class="space-y-3">
            <h4 class="font-semibold text-slate-900">Booking Summary</h4>
            
            <!-- Room -->
            <div class="flex justify-between items-start text-sm">
              <span class="text-slate-600">Room:</span>
              <span class="font-medium text-slate-900">{{ selectedRoom?.room_number || 'N/A' }}</span>
            </div>

            <!-- Check-in -->
            <div class="flex justify-between items-start text-sm">
              <span class="text-slate-600">Check-in:</span>
              <span class="font-medium text-slate-900">{{ form.check_in_date }}</span>
            </div>

            <!-- Check-out -->
            <div class="flex justify-between items-start text-sm">
              <span class="text-slate-600">Check-out:</span>
              <span class="font-medium text-slate-900">{{ form.check_out_date }}</span>
            </div>

            <!-- Nights -->
            <div class="flex justify-between items-start text-sm">
              <span class="text-slate-600">Nights:</span>
              <span class="font-medium text-slate-900">{{ nights }}</span>
            </div>

            <!-- Guests -->
            <div class="flex justify-between items-start text-sm">
              <span class="text-slate-600">Guests:</span>
              <span class="font-medium text-slate-900">{{ form.number_of_guests }}</span>
            </div>
          </div>

          <!-- Divider -->
          <div class="border-t border-slate-200 pt-4"></div>

          <!-- Price Breakdown -->
          <div class="space-y-2">
            <div class="flex justify-between text-sm">
              <span class="text-slate-600">{{ nights }} nights × {{ pricePerNight }} ETB</span>
              <span class="font-medium text-slate-900">{{ subtotal.toFixed(2) }} ETB</span>
            </div>

            <div class="flex justify-between text-sm">
              <span class="text-slate-600">Tax (15%)</span>
              <span class="font-medium text-slate-900">{{ taxAmount.toFixed(2) }} ETB</span>
            </div>

            <div class="flex justify-between text-base font-bold pt-2 border-t border-slate-200">
              <span class="text-slate-900">Total Amount:</span>
              <span class="text-blue-600">{{ totalAmount.toFixed(2) }} ETB</span>
            </div>
          </div>

          <!-- Terms -->
          <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 text-xs text-blue-700">
            ✓ Your payment is secure and processed through Chapa payment gateway
          </div>
        </div>

        <!-- Dialog Actions -->
        <div class="bg-slate-50 px-6 py-4 flex gap-3">
          <button
            @click="closePaymentDialog"
            :disabled="paymentLoading"
            class="flex-1 px-4 py-2 text-sm font-medium border border-slate-300 rounded-lg hover:bg-slate-100 text-slate-700 transition duration-200 disabled:opacity-50"
          >
            Cancel
          </button>

          <button
            @click="proceedToPayment"
            :disabled="paymentLoading"
            class="flex-1 px-4 py-2 text-sm font-medium bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition duration-200 disabled:opacity-50 flex items-center justify-center gap-2"
          >
            <span v-if="paymentLoading" class="animate-spin">⌛</span>
            <span v-if="paymentLoading">Processing...</span>
            <span v-else>💳 Pay Now</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
