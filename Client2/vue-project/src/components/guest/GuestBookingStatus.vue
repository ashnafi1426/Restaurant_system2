<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import {
  Calendar,
  Clock,
  User,
  Phone,
  Mail,
  DollarSign,
  AlertCircle,
  Loader,
  Home,
} from 'lucide-vue-next'
interface BookingDetails {
  id: string
  booking_reference: string
  status: 'pending' | 'confirmed' | 'checked_in' | 'checked_out' | 'cancelled'
  check_in_date: string
  check_out_date: string
  number_of_guests: number
  special_requests?: string
  created_at: string
  guest?: {
    id: string
    first_name: string
    last_name: string
    email: string
    phone: string
  }
  room?: {
    id: string
    room_number: string
    floor: number
    room_type?: {
      name: string
      capacity: number
      base_price_per_night: number
    }
  }
  total?: number
  cancelled_at?: string
}

const props = defineProps<{
  bookingReference: string
  qrToken: string
}>()

const emit = defineEmits<{
  cancel: []
  refresh: []
}>()

const booking = ref<BookingDetails | null>(null)
const isLoading = ref(true)
const error = ref<string | null>(null)
const isCancelling = ref(false)
const showCancelConfirm = ref(false)
const pollInterval = ref<any>(null)

const statusBadgeClass = computed(() => {
  const statusClasses: Record<string, string> = {
    pending: 'bg-yellow-100 text-yellow-800 border-yellow-300',
    confirmed: 'bg-green-100 text-green-800 border-green-300',
    checked_in: 'bg-blue-100 text-blue-800 border-blue-300',
    checked_out: 'bg-slate-100 text-slate-800 border-slate-300',
    cancelled: 'bg-red-100 text-red-800 border-red-300',
  }
  return statusClasses[booking.value?.status || 'pending']
})

const statusLabel = computed(() => {
  const labels: Record<string, string> = {
    pending: 'Pending Confirmation',
    confirmed: 'Confirmed',
    checked_in: 'Checked In',
    checked_out: 'Checked Out',
    cancelled: 'Cancelled',
  }
  return labels[booking.value?.status || 'pending']
})

const canCancel = computed(() => {
  if (!booking.value) return false
  if (booking.value.status === 'cancelled') return false
  if (booking.value.status === 'checked_out') return false

  const checkInDate = new Date(booking.value.check_in_date)
  const now = new Date()
  const hoursUntilCheckIn = (checkInDate.getTime() - now.getTime()) / (1000 * 60 * 60)

  return hoursUntilCheckIn > 48
})

const numberOfNights = computed(() => {
  if (!booking.value) return 0
  const checkIn = new Date(booking.value.check_in_date)
  const checkOut = new Date(booking.value.check_out_date)
  const nights = Math.floor((checkOut.getTime() - checkIn.getTime()) / (1000 * 60 * 60 * 24))
  return Math.max(nights, 1)
})

const roomPricePerNight = computed(() => {
  if (!booking.value?.room?.room_type) return 0
  return booking.value.room.room_type.base_price_per_night
})

const calculatedTotal = computed(() => {
  return roomPricePerNight.value * numberOfNights.value
})

const formattedCheckIn = computed(() => {
  if (!booking.value) return ''
  return new Date(booking.value.check_in_date).toLocaleDateString('en-US', {
    weekday: 'short',
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  })
})

const formattedCheckOut = computed(() => {
  if (!booking.value) return ''
  return new Date(booking.value.check_out_date).toLocaleDateString('en-US', {
    weekday: 'short',
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  })
})

async function fetchBookingStatus() {
  if (!props.bookingReference || !props.qrToken) {
    error.value = 'Invalid booking reference or QR token'
    isLoading.value = false
    return
  }

  try {
    isLoading.value = true
    error.value = null

    const response = await fetch(`/api/guest/bookings/${props.bookingReference}`, {
      method: 'GET',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        qr_token: props.qrToken,
      }),
    })

    const data = await response.json()

    if (!response.ok) {
      throw new Error(data.message || 'Failed to fetch booking details')
    }

    booking.value = data.data || data
  } catch (err: any) {
    console.error('[GuestBookingStatus] Failed to fetch booking details:', err)
    error.value = err.message || 'Failed to load booking details'
  } finally {
    isLoading.value = false
  }
}

async function cancelBooking() {
  if (!canCancel.value || !booking.value) {
    return
  }

  isCancelling.value = true

  try {
    const response = await fetch(`/api/guest/bookings/${props.bookingReference}/cancel`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        qr_token: props.qrToken,
        cancellation_reason: 'Guest requested cancellation',
      }),
    })

    const data = await response.json()

    if (!response.ok) {
      throw new Error(data.message || 'Failed to cancel booking')
    }

    if (booking.value) {
      booking.value.status = 'cancelled'
      booking.value.cancelled_at = new Date().toISOString()
    }

    showCancelConfirm.value = false
    emit('cancel')
  } catch (err: any) {
    console.error('[GuestBookingStatus] Failed to cancel booking:', err)
    alert(err.message || 'Failed to cancel booking')
  } finally {
    isCancelling.value = false
  }
}

function startPolling() {
  pollInterval.value = setInterval(() => {
    fetchBookingStatus()
  }, 30000)
}

function stopPolling() {
  if (pollInterval.value) {
    clearInterval(pollInterval.value)
    pollInterval.value = null
  }
}

function refreshStatus() {
  fetchBookingStatus()
  emit('refresh')
}

onMounted(() => {
  fetchBookingStatus()
  startPolling()
})

watch(
  () => props.bookingReference,
  () => {
    stopPolling()
    fetchBookingStatus()
    startPolling()
  },
)

watch(
  () => booking.value?.status,
  (newStatus) => {
    if (newStatus === 'checked_out' || newStatus === 'cancelled') {
      stopPolling()
    }
  },
)
</script>

<template>
  <div class="w-full space-y-6">
    <div
      v-if="isLoading"
      class="bg-white rounded-2xl shadow-lg p-8 flex items-center justify-center min-h-96"
    >
      <div class="text-center">
        <Loader class="w-12 h-12 text-amber-600 animate-spin mx-auto mb-4" />
        <p class="text-lg font-semibold text-slate-900">Loading booking details...</p>
      </div>
    </div>

    <div
      v-else-if="error"
      class="bg-red-50 border-2 border-red-200 rounded-2xl p-6 flex items-start gap-4"
    >
      <AlertCircle class="w-6 h-6 text-red-600 flex-shrink-0 mt-0.5" />
      <div>
        <p class="font-bold text-red-900">Error Loading Booking</p>
        <p class="text-sm text-red-700 mt-2">{{ error }}</p>
        <button
          @click="refreshStatus"
          class="mt-3 text-sm font-semibold text-red-700 hover:text-red-900 underline"
        >
          Try Again
        </button>
      </div>
    </div>

    <div v-else-if="booking" class="space-y-4">
      <div
        class="bg-gradient-to-r from-amber-500 via-orange-500 to-amber-600 rounded-2xl shadow-lg p-6 md:p-8 text-white"
      >
        <div class="flex items-start justify-between mb-4">
          <div>
            <h2 class="text-3xl md:text-4xl font-bold mb-2">Booking Reference</h2>
            <p class="text-2xl font-mono font-bold text-amber-100 tracking-wider break-all">
              {{ booking.booking_reference }}
            </p>
          </div>
          <div :class="['px-4 py-2 rounded-lg border-2 font-bold text-sm', statusBadgeClass]">
            {{ statusLabel }}
          </div>
        </div>
      </div>

      <div class="bg-white rounded-2xl shadow-lg border border-slate-200/50 p-6 md:p-8">
        <h3 class="text-xl font-bold text-slate-900 mb-6 flex items-center gap-3">
          <User class="w-6 h-6 text-amber-600" />
          Guest Information
        </h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div>
            <p class="text-sm text-slate-500 font-semibold mb-1">Guest Name</p>
            <p class="text-lg font-bold text-slate-900">
              {{ booking.guest?.first_name }} {{ booking.guest?.last_name }}
            </p>
          </div>
          <div>
            <p class="text-sm text-slate-500 font-semibold mb-1">Number of Guests</p>
            <p class="text-lg font-bold text-slate-900">{{ booking.number_of_guests }}</p>
          </div>
          <div class="flex items-center gap-2 text-slate-600">
            <Mail class="w-5 h-5 text-amber-600" />
            <div>
              <p class="text-sm text-slate-500 font-semibold">Email</p>
              <p class="font-semibold text-slate-900">{{ booking.guest?.email }}</p>
            </div>
          </div>
          <div class="flex items-center gap-2 text-slate-600">
            <Phone class="w-5 h-5 text-amber-600" />
            <div>
              <p class="text-sm text-slate-500 font-semibold">Phone</p>
              <p class="font-semibold text-slate-900">{{ booking.guest?.phone }}</p>
            </div>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-2xl shadow-lg border border-slate-200/50 p-6 md:p-8">
        <h3 class="text-xl font-bold text-slate-900 mb-6 flex items-center gap-3">
          <Home class="w-6 h-6 text-amber-600" />
          Room Details
        </h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div>
            <p class="text-sm text-slate-500 font-semibold mb-1">Room Type</p>
            <p class="text-lg font-bold text-slate-900">
              {{ booking.room?.room_type?.name || 'Standard Room' }}
            </p>
          </div>
          <div>
            <p class="text-sm text-slate-500 font-semibold mb-1">Room Number</p>
            <p class="text-lg font-bold text-slate-900">#{{ booking.room?.room_number }}</p>
          </div>
          <div>
            <p class="text-sm text-slate-500 font-semibold mb-1">Floor</p>
            <p class="text-lg font-bold text-slate-900">{{ booking.room?.floor }}</p>
          </div>
          <div>
            <p class="text-sm text-slate-500 font-semibold mb-1">Capacity</p>
            <p class="text-lg font-bold text-slate-900">
              {{ booking.room?.room_type?.capacity || 2 }} guests
            </p>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-2xl shadow-lg border border-slate-200/50 p-6 md:p-8">
        <h3 class="text-xl font-bold text-slate-900 mb-6 flex items-center gap-3">
          <Calendar class="w-6 h-6 text-amber-600" />
          Stay Duration
        </h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
          <div class="flex items-center gap-4 p-4 bg-blue-50 rounded-lg border border-blue-200">
            <div class="p-3 bg-blue-100 rounded-lg">
              <Calendar class="w-6 h-6 text-blue-600" />
            </div>
            <div>
              <p class="text-xs text-blue-600 font-semibold">Check-in</p>
              <p class="text-lg font-bold text-slate-900">{{ formattedCheckIn }}</p>
            </div>
          </div>
          <div class="flex items-center gap-4 p-4 bg-amber-50 rounded-lg border border-amber-200">
            <div class="p-3 bg-amber-100 rounded-lg">
              <Clock class="w-6 h-6 text-amber-600" />
            </div>
            <div>
              <p class="text-xs text-amber-600 font-semibold">Duration</p>
              <p class="text-lg font-bold text-slate-900">
                {{ numberOfNights }} Night{{ numberOfNights !== 1 ? 's' : '' }}
              </p>
            </div>
          </div>
          <div class="flex items-center gap-4 p-4 bg-orange-50 rounded-lg border border-orange-200">
            <div class="p-3 bg-orange-100 rounded-lg">
              <Calendar class="w-6 h-6 text-orange-600" />
            </div>
            <div>
              <p class="text-xs text-orange-600 font-semibold">Check-out</p>
              <p class="text-lg font-bold text-slate-900">{{ formattedCheckOut }}</p>
            </div>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-2xl shadow-lg border border-slate-200/50 p-6 md:p-8">
        <h3 class="text-xl font-bold text-slate-900 mb-6 flex items-center gap-3">
          <DollarSign class="w-6 h-6 text-amber-600" />
          Pricing Summary
        </h3>
        <div class="space-y-3 border-b border-slate-200 pb-4 mb-4">
          <div class="flex justify-between items-center">
            <span class="text-slate-600">Room Rate (per night)</span>
            <span class="font-semibold text-slate-900">ETB {{ roomPricePerNight }}</span>
          </div>
          <div class="flex justify-between items-center">
            <span class="text-slate-600"
              >{{ numberOfNights }} Night{{ numberOfNights !== 1 ? 's' : '' }} × ETB
              {{ roomPricePerNight }}</span
            >
            <span class="font-semibold text-slate-900">ETB {{ calculatedTotal }}</span>
          </div>
        </div>
        <div class="flex justify-between items-center text-lg font-bold">
          <span>Total</span>
          <span class="text-amber-600">ETB {{ booking.total || calculatedTotal }}</span>
        </div>
      </div>

      <div
        v-if="booking.special_requests"
        class="bg-blue-50 rounded-2xl border border-blue-200 p-6 md:p-8"
      >
        <p class="text-sm font-semibold text-blue-600 mb-2">Special Requests</p>
        <p class="text-slate-900">{{ booking.special_requests }}</p>
      </div>

      <div
        v-if="booking.status === 'cancelled' && booking.cancelled_at"
        class="bg-red-50 rounded-2xl border border-red-200 p-6 md:p-8"
      >
        <p class="text-sm font-semibold text-red-600 mb-2">Booking Cancelled</p>
        <p class="text-slate-900">
          This booking was cancelled on
          {{ new Date(booking.cancelled_at).toLocaleDateString() }}.
        </p>
      </div>

      <div class="flex gap-3">
        <button
          v-if="canCancel"
          @click="showCancelConfirm = true"
          :disabled="isCancelling"
          class="flex-1 bg-red-50 hover:bg-red-100 disabled:opacity-50 disabled:cursor-not-allowed border-2 border-red-200 text-red-700 font-semibold py-3 rounded-lg transition-all"
        >
          {{ isCancelling ? 'Cancelling...' : 'Cancel Booking' }}
        </button>
        <button
          @click="refreshStatus"
          class="flex-1 bg-blue-50 hover:bg-blue-100 border-2 border-blue-200 text-blue-700 font-semibold py-3 rounded-lg transition-all"
        >
          Refresh Status
        </button>
      </div>

      <div v-if="booking.status !== 'cancelled'" class="text-xs text-slate-500 text-center">
        <p class="font-semibold mb-1">Cancellation Policy</p>
        <p>
          Bookings can only be cancelled 48 hours before check-in. Contact support for assistance.
        </p>
      </div>

      <div
        v-if="showCancelConfirm"
        class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4"
      >
        <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full p-6">
          <div class="flex items-center gap-3 mb-4">
            <div class="p-3 bg-red-100 rounded-lg">
              <AlertCircle class="w-6 h-6 text-red-600" />
            </div>
            <h3 class="text-xl font-bold text-slate-900">Cancel Booking?</h3>
          </div>
          <p class="text-slate-600 mb-6">
            Are you sure you want to cancel this booking? This action cannot be undone.
          </p>
          <div class="flex gap-3">
            <button
              @click="showCancelConfirm = false"
              class="flex-1 px-4 py-2 border border-slate-300 text-slate-700 font-semibold rounded-lg hover:bg-slate-50 transition-all"
            >
              No, Keep It
            </button>
            <button
              @click="cancelBooking"
              :disabled="isCancelling"
              class="flex-1 px-4 py-2 bg-red-600 hover:bg-red-700 disabled:opacity-50 text-white font-semibold rounded-lg transition-all"
            >
              {{ isCancelling ? 'Cancelling...' : 'Yes, Cancel' }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped></style>
