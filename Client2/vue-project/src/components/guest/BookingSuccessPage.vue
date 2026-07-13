<script setup lang="ts">
import { ref, watch } from 'vue'
import {
  CheckCircle,
  Copy,
  Home,
  Download,
  X,
  Share2,
  Hotel,
  Award,
  Mail,
  Sparkles,
  PartyPopper,
} from 'lucide-vue-next'

interface BookingData {
  booking_reference: string
  guest_name: string
  room_type: string
  check_in_date: string
  check_out_date: string
  number_of_guests: number
  total_price?: number
  special_requests?: string
}

interface Props {
  isOpen: boolean
  booking?: BookingData | null
}

const props = withDefaults(defineProps<Props>(), {
  isOpen: false,
  booking: null,
})

const emit = defineEmits<{
  close: []
  shareWhatsApp: []
  downloadPDF: []
}>()

const copied = ref(false)
const showContent = ref(false)

watch(
  () => props.isOpen,
  (newVal) => {
    if (newVal) {
      setTimeout(() => {
        showContent.value = true
      }, 100)
    } else {
      showContent.value = false
    }
  },
)

function copyBookingReference() {
  if (props.booking?.booking_reference) {
    navigator.clipboard.writeText(props.booking.booking_reference)
    copied.value = true
    setTimeout(() => {
      copied.value = false
    }, 2000)
  }
}

function formatDate(dateStr: string): string {
  const date = new Date(dateStr)
  return date.toLocaleDateString('en-US', {
    weekday: 'short',
    month: 'short',
    day: 'numeric',
    year: 'numeric',
  })
}

function shareViaWhatsApp() {
  const message = `My booking is continue\n\nBooking Reference: ${props.booking?.booking_reference}\nGuest: ${props.booking?.guest_name}\nRoom: ${props.booking?.room_type}\nCheck-in: ${formatDate(props.booking?.check_in_date || '')}\nCheck-out: ${formatDate(props.booking?.check_out_date || '')}\n\nI'm excited about my stay at Dire Dawa Ras Hotels!`
  const whatsappUrl = `https://api.whatsapp.com/send?text=${encodeURIComponent(message)}`
  window.open(whatsappUrl, '_blank')
  emit('shareWhatsApp')
}

function downloadVoucher() {
  emit('downloadPDF')
}
</script>

<template>
  <Teleport to="body">
    <div
      v-if="isOpen && booking"
      class="fixed inset-0 bg-black/80 backdrop-blur-md z-50 flex items-center justify-center p-4 overflow-y-auto min-h-screen"
      @click.self="emit('close')"
    >
      <!-- Success Card -->
      <div
        class="relative w-full max-w-md bg-white rounded-2xl shadow-2xl overflow-hidden flex flex-col max-h-[95vh]"
        :class="showContent ? 'animate-scale-in' : 'opacity-0 scale-95'"
      >
        <!-- Close Button -->
        <button
          @click="emit('close')"
          class="absolute top-3 right-3 z-10 text-gray-400 hover:text-gray-600 hover:bg-gray-100 p-1.5 rounded-lg transition-colors"
        >
          <X class="w-5 h-5" />
        </button>

        <!-- Success Header -->
        <div class="text-center px-6 pt-8 pb-6 border-b border-gray-100">
          <div
            class="inline-flex items-center justify-center w-16 h-16 bg-emerald-50 rounded-full mb-4 relative"
          >
            <div class="absolute inset-0 bg-emerald-400/20 rounded-full animate-ping"></div>
            <CheckCircle class="w-8 h-8 text-emerald-600 relative" />
          </div>

          <h1 class="text-xl font-bold text-gray-900 mb-1">Booking is confirm by receptionist</h1>
          <p class="text-sm text-gray-500">Your reservation is complete and waiting up to reception approved</p>
        </div>
        <!-- Booking Details -->
        <div class="px-6 py-5 space-y-4 flex-1 overflow-y-auto">
          <!-- Hotel & Reference -->
          <div class="grid grid-cols-2 gap-4">
            <div>
              <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Hotel</p>
              <p class="text-sm font-bold text-gray-900 mt-1 flex items-center gap-1.5">
                <Hotel class="w-4 h-4 text-amber-500" />
                Garnd Horizon Lexury Hotel
              </p>
            </div>
            <div class="text-right">
              <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Reference</p>
              <div class="flex items-center justify-end gap-1.5 mt-1">
                <p class="text-sm font-mono font-bold text-amber-600">
                  {{ booking.booking_reference }}
                </p>
                <button
                  @click="copyBookingReference"
                  class="p-1 hover:bg-gray-100 rounded transition-colors"
                  :title="copied ? 'Copied!' : 'Copy'"
                >
                  <Copy
                    class="w-3.5 h-3.5"
                    :class="copied ? 'text-emerald-600' : 'text-gray-400'"
                  />
                </button>
              </div>
            </div>
          </div>

          <!-- Guest Details -->
          <div class="bg-gray-50 rounded-xl p-4 space-y-3">
            <div class="flex justify-between text-sm">
              <span class="text-gray-500">Guest Name</span>
              <span class="font-medium text-gray-900">{{ booking.guest_name }}</span>
            </div>
            <div class="flex justify-between text-sm">
              <span class="text-gray-500">Room Type</span>
              <span class="font-medium text-gray-900">{{ booking.room_type }}</span>
            </div>
            <div class="flex justify-between text-sm">
              <span class="text-gray-500">Check-in</span>
              <span class="font-medium text-gray-900">{{ formatDate(booking.check_in_date) }}</span>
            </div>
            <div class="flex justify-between text-sm">
              <span class="text-gray-500">Check-out</span>
              <span class="font-medium text-gray-900">{{
                formatDate(booking.check_out_date)
              }}</span>
            </div>
            <div class="flex justify-between text-sm pt-2 border-t border-gray-200">
              <span class="text-gray-500">Guests</span>
              <span class="font-medium text-gray-900"
                >{{ booking.number_of_guests }} guest{{
                  booking.number_of_guests !== 1 ? 's' : ''
                }}</span
              >
            </div>
          </div>

          <!-- Special Requests -->
          <div
            v-if="booking.special_requests"
            class="bg-amber-50 rounded-xl p-3 border border-amber-200"
          >
            <p class="text-xs font-semibold text-amber-700 flex items-center gap-1.5 mb-1">
              <Mail class="w-3.5 h-3.5" />
              Special Requests
            </p>
            <p class="text-sm text-amber-800">{{ booking.special_requests }}</p>
          </div>

          <!-- Confirmation Message -->
          <div class="bg-emerald-50 rounded-xl p-3 border border-emerald-200">
            <div class="flex items-start gap-2.5">
              <Mail class="w-4 h-4 text-emerald-600 flex-shrink-0 mt-0.5" />
              <div>
                <p class="text-sm font-semibold text-emerald-800">Confirmation Email Sent</p>
                <p class="text-xs text-emerald-700 mt-0.5">
                  A confirmation email has been sent to your registered email address. Don't worry.
                </p>
              </div>
            </div>
          </div>
        </div>

        <!-- Actions -->
        <div class="px-6 py-4 border-t border-gray-100 bg-gray-50/50 space-y-2.5">
          <!-- WhatsApp Button -->
          <button
            @click="shareViaWhatsApp"
            class="w-full px-4 py-3 bg-gradient-to-r from-emerald-500 to-emerald-600 hover:from-emerald-600 hover:to-emerald-700 text-white font-semibold rounded-xl transition-all duration-200 flex items-center justify-center gap-2 text-sm shadow-lg shadow-emerald-500/25 hover:shadow-emerald-500/40"
          >
            <Share2 class="w-4 h-4" />
            Complete Booking via WhatsApp
          </button>
          <p class="text-[10px] text-gray-400 text-center">
            Tap to open WhatsApp → Share with reception to confirm
          </p>

          <!-- Secondary Actions -->
          <div class="grid grid-cols-2 gap-2">
            <button
              @click="downloadVoucher"
              class="px-4 py-2.5 bg-white border border-gray-200 text-gray-700 font-medium rounded-xl hover:bg-gray-50 transition-colors flex items-center justify-center gap-2 text-sm"
            >
              <Download class="w-4 h-4" />
              Save Voucher
            </button>
            <button
              @click="emit('close')"
              class="px-4 py-2.5 bg-white border border-gray-200 text-gray-700 font-medium rounded-xl hover:bg-gray-50 transition-colors flex items-center justify-center gap-2 text-sm"
            >
              <Home class="w-4 h-4" />
              Back Home
            </button>
          </div>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<style scoped>
@keyframes scaleIn {
  from {
    opacity: 0;
    transform: scale(0.95) translateY(10px);
  }
  to {
    opacity: 1;
    transform: scale(1) translateY(0);
  }
}

.animate-scale-in {
  animation: scaleIn 0.3s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
}

/* Scrollbar */
.overflow-y-auto::-webkit-scrollbar {
  width: 3px;
}
.overflow-y-auto::-webkit-scrollbar-track {
  background: transparent;
}
.overflow-y-auto::-webkit-scrollbar-thumb {
  background: #d1d5db;
  border-radius: 9999px;
}
.overflow-y-auto::-webkit-scrollbar-thumb:hover {
  background: #9ca3af;
}
</style>
