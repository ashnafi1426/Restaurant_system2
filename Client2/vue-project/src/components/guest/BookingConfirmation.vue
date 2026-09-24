<script setup lang="ts">
import { ref, computed } from 'vue'
import {
  CheckCircle,
  Copy,
  Mail,
  Phone,
  Calendar,
  MapPin,
  Users,
  DollarSign,
  Home,
  ArrowRight,
  Printer,
  Download,
} from 'lucide-vue-next'

interface BookingConfirmationData {
  booking_reference: string
  hotel_name: string
  hotel_phone?: string
  hotel_email?: string
  guest_name: string
  guest_email: string
  guest_phone: string
  room_type: string
  room_number: string
  check_in_date: string
  check_out_date: string
  number_of_guests: number
  total: number
  number_of_nights: number
}

const props = defineProps<{
  booking: BookingConfirmationData
}>()

const emit = defineEmits<{
  viewBooking: []
  printBooking: []
}>()

const copiedField = ref<string | null>(null)

const formattedCheckIn = computed(() => {
  return new Date(props.booking.check_in_date).toLocaleDateString('en-US', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  })
})

const formattedCheckOut = computed(() => {
  return new Date(props.booking.check_out_date).toLocaleDateString('en-US', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  })
})

function copyToClipboard(text: string, field: string) {
  navigator.clipboard.writeText(text)
  copiedField.value = field
  setTimeout(() => {
    copiedField.value = null
  }, 2000)
}

function printBooking() {
  window.print()
  emit('printBooking')
}

function downloadBooking() {
  const content = `
BOOKING CONFIRMATION

Booking Reference: ${props.booking.booking_reference}
Date: ${new Date().toLocaleDateString()}

GUEST INFORMATION
Name: ${props.booking.guest_name}
Email: ${props.booking.guest_email}
Phone: ${props.booking.guest_phone}

BOOKING DETAILS
Check-in: ${formattedCheckIn}
Check-out: ${formattedCheckOut}
Number of Guests: ${props.booking.number_of_guests}
Duration: ${props.booking.number_of_nights} night(s)

ROOM INFORMATION
Room Type: ${props.booking.room_type}
Room Number: ${props.booking.room_number}

TOTAL: ETB ${props.booking.total}

Thank you for your booking!
`

  const blob = new Blob([content], { type: 'text/plain' })
  const url = window.URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  a.download = `booking-${props.booking.booking_reference}.txt`
  document.body.appendChild(a)
  a.click()
  window.URL.revokeObjectURL(url)
  document.body.removeChild(a)
}
</script>

<template>
  <div class="w-full space-y-6 print:space-y-4">
    <div class="bg-gradient-to-b from-emerald-50 to-white rounded-3xl shadow-2xl border-2 border-emerald-200 overflow-hidden print:border-0 print:shadow-none print:rounded-none">
      <div class="bg-gradient-to-r from-emerald-600 via-green-600 to-emerald-600 px-6 md:px-8 lg:px-10 py-8 md:py-12 text-center text-white">
        <div class="flex justify-center mb-4">
          <div class="relative">
            <div class="absolute inset-0 bg-white/30 rounded-full animate-ping opacity-75" style="animation-duration: 2s;"></div>
            <div class="relative p-3 bg-white/20 rounded-full">
              <CheckCircle class="w-12 h-12 md:w-16 md:h-16" />
            </div>
          </div>
        </div>
        <h1 class="text-3xl md:text-4xl lg:text-5xl font-bold mb-3">Booking Confirmed!</h1>
        <p class="text-emerald-100 text-lg md:text-xl">Your reservation has been successfully created</p>
      </div>

      <div class="px-6 md:px-8 lg:px-10 py-8 md:py-12 space-y-8">
        <div class="bg-white border-2 border-emerald-200 rounded-2xl p-6 md:p-8">
          <p class="text-sm text-slate-500 font-semibold uppercase tracking-wider mb-3">Booking Reference</p>
          <div class="flex items-center gap-3 p-4 bg-emerald-50 rounded-lg border border-emerald-300 mb-4">
            <code class="font-mono text-xl md:text-2xl font-bold text-emerald-700 flex-1 break-all">
              {{ booking.booking_reference }}
            </code>
            <button
              @click="copyToClipboard(booking.booking_reference, 'reference')"
              class="flex-shrink-0 p-2 hover:bg-emerald-200 rounded-lg transition-colors"
              :class="copiedField === 'reference' ? 'bg-emerald-200' : ''"
            >
              <Copy
                class="w-5 h-5"
                :class="copiedField === 'reference' ? 'text-emerald-700' : 'text-slate-500'"
              />
            </button>
          </div>
          <p class="text-sm text-slate-600">
            Please keep this reference number safe. You'll need it to check your booking status or make changes.
          </p>
        </div>

        <div class="space-y-4">
          <h2 class="text-2xl font-bold text-slate-900 flex items-center gap-3">
            <div class="p-2 bg-blue-100 rounded-lg">
              <Users class="w-6 h-6 text-blue-600" />
            </div>
            Guest Information
          </h2>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="bg-slate-50 rounded-lg p-4 border border-slate-200">
              <p class="text-xs text-slate-500 font-semibold uppercase mb-2">Guest Name</p>
              <p class="text-lg font-bold text-slate-900">{{ booking.guest_name }}</p>
            </div>
            <div class="bg-slate-50 rounded-lg p-4 border border-slate-200">
              <p class="text-xs text-slate-500 font-semibold uppercase mb-2">Number of Guests</p>
              <p class="text-lg font-bold text-slate-900">{{ booking.number_of_guests }}</p>
            </div>
            <div class="bg-slate-50 rounded-lg p-4 border border-slate-200 md:col-span-2">
              <div class="flex items-center gap-2 mb-2">
                <Mail class="w-4 h-4 text-slate-400" />
                <p class="text-xs text-slate-500 font-semibold uppercase">Email</p>
              </div>
              <p class="text-lg font-bold text-slate-900 break-all">{{ booking.guest_email }}</p>
            </div>
            <div class="bg-slate-50 rounded-lg p-4 border border-slate-200 md:col-span-2">
              <div class="flex items-center gap-2 mb-2">
                <Phone class="w-4 h-4 text-slate-400" />
                <p class="text-xs text-slate-500 font-semibold uppercase">Phone</p>
              </div>
              <p class="text-lg font-bold text-slate-900 break-all">{{ booking.guest_phone }}</p>
            </div>
          </div>
        </div>

        <div class="space-y-4">
          <h2 class="text-2xl font-bold text-slate-900 flex items-center gap-3">
            <div class="p-2 bg-amber-100 rounded-lg">
              <Calendar class="w-6 h-6 text-amber-600" />
            </div>
            Booking Details
          </h2>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-amber-50 rounded-lg p-4 border-2 border-amber-200">
              <p class="text-xs text-amber-600 font-semibold uppercase mb-2">Check-in</p>
              <p class="text-lg font-bold text-slate-900 mb-1">{{ formattedCheckIn }}</p>
              <p class="text-xs text-slate-500">After 2:00 PM</p>
            </div>
            <div class="bg-orange-50 rounded-lg p-4 border-2 border-orange-200">
              <p class="text-xs text-orange-600 font-semibold uppercase mb-2">Duration</p>
              <p class="text-lg font-bold text-slate-900">{{ booking.number_of_nights }} Night{{ booking.number_of_nights !== 1 ? 's' : '' }}</p>
              <p class="text-xs text-slate-500">({{ booking.number_of_guests }} guest{{ booking.number_of_guests !== 1 ? 's' : '' }})</p>
            </div>
            <div class="bg-red-50 rounded-lg p-4 border-2 border-red-200">
              <p class="text-xs text-red-600 font-semibold uppercase mb-2">Check-out</p>
              <p class="text-lg font-bold text-slate-900 mb-1">{{ formattedCheckOut }}</p>
              <p class="text-xs text-slate-500">Before 11:00 AM</p>
            </div>
          </div>
        </div>

        <div class="space-y-4">
          <h2 class="text-2xl font-bold text-slate-900 flex items-center gap-3">
            <div class="p-2 bg-purple-100 rounded-lg">
              <Home class="w-6 h-6 text-purple-600" />
            </div>
            Room Information
          </h2>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="bg-slate-50 rounded-lg p-4 border border-slate-200">
              <p class="text-xs text-slate-500 font-semibold uppercase mb-2">Room Type</p>
              <p class="text-lg font-bold text-slate-900">{{ booking.room_type }}</p>
            </div>
            <div class="bg-slate-50 rounded-lg p-4 border border-slate-200">
              <p class="text-xs text-slate-500 font-semibold uppercase mb-2">Room Number</p>
              <p class="text-lg font-bold text-slate-900">#{{ booking.room_number }}</p>
            </div>
          </div>
        </div>

        <div class="bg-gradient-to-r from-amber-50 to-orange-50 border-2 border-amber-300 rounded-2xl p-6 md:p-8">
          <h2 class="text-2xl font-bold text-slate-900 mb-6 flex items-center gap-3">
            <DollarSign class="w-6 h-6 text-amber-600" />
            Booking Amount
          </h2>
          <div class="space-y-3 mb-6 pb-6 border-b-2 border-amber-200">
            <div class="flex justify-between items-center">
              <span class="text-slate-600">Room Rate</span>
              <span class="font-semibold text-slate-900">ETB {{ Math.round(booking.total / booking.number_of_nights) }} / night</span>
            </div>
            <div class="flex justify-between items-center">
              <span class="text-slate-600">{{ booking.number_of_nights }} Night{{ booking.number_of_nights !== 1 ? 's' : '' }}</span>
              <span class="font-semibold text-slate-900">ETB {{ booking.total }}</span>
            </div>
          </div>
          <div class="flex justify-between items-center text-2xl md:text-3xl font-bold">
            <span>Total Amount</span>
            <span class="text-amber-600">ETB {{ booking.total }}</span>
          </div>
        </div>

        <div v-if="booking.hotel_phone || booking.hotel_email" class="space-y-4">
          <h2 class="text-2xl font-bold text-slate-900 flex items-center gap-3">
            <div class="p-2 bg-green-100 rounded-lg">
              <MapPin class="w-6 h-6 text-green-600" />
            </div>
            Hotel Contact Information
          </h2>
          <div class="bg-slate-50 rounded-lg p-6 border border-slate-200 space-y-3">
            <p class="text-lg font-bold text-slate-900">{{ booking.hotel_name }}</p>
            <div v-if="booking.hotel_phone" class="flex items-center gap-3">
              <Phone class="w-5 h-5 text-slate-400" />
              <p class="text-slate-600">{{ booking.hotel_phone }}</p>
            </div>
            <div v-if="booking.hotel_email" class="flex items-center gap-3">
              <Mail class="w-5 h-5 text-slate-400" />
              <p class="text-slate-600">{{ booking.hotel_email }}</p>
            </div>
          </div>
        </div>

        <div class="bg-blue-50 border-2 border-blue-300 rounded-2xl p-6 md:p-8">
          <h2 class="text-2xl font-bold text-blue-900 mb-4">What's Next?</h2>
          <div class="space-y-4">
            <div class="flex gap-4">
              <div class="flex-shrink-0 flex items-center justify-center w-8 h-8 bg-blue-600 text-white rounded-full font-bold">1</div>
              <div>
                <p class="font-semibold text-slate-900">Confirmation Email</p>
                <p class="text-sm text-slate-600 mt-1">A confirmation has been sent to {{ booking.guest_email }}</p>
              </div>
            </div>
            <div class="flex gap-4">
              <div class="flex-shrink-0 flex items-center justify-center w-8 h-8 bg-blue-600 text-white rounded-full font-bold">2</div>
              <div>
                <p class="font-semibold text-slate-900">Check-in Instructions</p>
                <p class="text-sm text-slate-600 mt-1">You'll receive check-in details 24 hours before arrival</p>
              </div>
            </div>
            <div class="flex gap-4">
              <div class="flex-shrink-0 flex items-center justify-center w-8 h-8 bg-blue-600 text-white rounded-full font-bold">3</div>
              <div>
                <p class="font-semibold text-slate-900">Early Check-in</p>
                <p class="text-sm text-slate-600 mt-1">Contact the hotel to request early check-in availability</p>
              </div>
            </div>
          </div>
        </div>

        <div class="bg-slate-100 rounded-lg p-4 border border-slate-300 text-sm text-slate-700">
          <p class="font-semibold mb-2">Cancellation Policy</p>
          <p>Bookings can be cancelled up to 48 hours before check-in for a full refund. Late cancellations may incur charges.</p>
        </div>
      </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 print:hidden">
      <button
        @click="emit('viewBooking')"
        class="bg-gradient-to-r from-amber-600 to-orange-600 hover:from-amber-700 hover:to-orange-700 text-white font-bold py-3 rounded-lg transition-all flex items-center justify-center gap-2"
      >
        <ArrowRight class="w-5 h-5" />
        View Booking Details
      </button>
      <button
        @click="printBooking"
        class="bg-blue-50 hover:bg-blue-100 border-2 border-blue-300 text-blue-700 font-bold py-3 rounded-lg transition-all flex items-center justify-center gap-2"
      >
        <Printer class="w-5 h-5" />
        Print Confirmation
      </button>
    </div>

    <div class="text-center print:hidden">
      <button
        @click="downloadBooking"
        class="inline-flex items-center gap-2 text-sm text-slate-600 hover:text-slate-900 font-semibold transition-colors"
      >
        <Download class="w-4 h-4" />
        Download Confirmation
      </button>
    </div>
  </div>
</template>

<style scoped>
@media print {
  :root {
    --tw-text-opacity: 1;
  }
}
</style>
