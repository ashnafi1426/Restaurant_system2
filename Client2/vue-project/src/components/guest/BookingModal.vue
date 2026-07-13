<script setup lang="ts">
import { ref, computed, watch, onMounted, nextTick } from 'vue'
import { useGuestStore } from '../../stores/guestStore'
import { useReservationStore } from '../../stores/reservationStore'
import { useRoomStore } from '../../stores/room'
import { roomService } from '../../services/roomService'
import BookingSuccessPage from './BookingSuccessPage.vue'
import {
  X,
  Calendar,
  Users,
  User,
  Mail,
  Phone,
  Coffee,
  Star,
  CheckCircle,
  MapPin,
  Sparkles,
  Utensils,
  Loader,
} from 'lucide-vue-next'

interface RoomType {
  id?: string | number
  name: string
  description?: string
  base_price_per_night: number
  capacity: number
  amenities?: string[]
  amenities_count?: number
}

interface Room {
  id: string
  room_number?: string
  room_type_id?: number
  room_type?: RoomType | string
  description?: string
  floor?: number
  status?: string
  is_active?: boolean
  created_at?: string
  updated_at?: string
}

interface BookingSuccessData {
  booking_reference: string
  guest_name: string
  room_type: string
  check_in_date: string
  check_out_date: string
  number_of_guests: number
  total_price?: number
  special_requests?: string
}

const props = defineProps<{
  isOpen: boolean
  room: Room | null
}>()

const emit = defineEmits<{
  close: []
  submit: [data: any]
}>()

// Booking form data
const bookingForm = ref({
  checkInDate: '',
  checkOutDate: '',
  guests: 1,
  guestName: '',
  guestEmail: '',
  guestPhone: '',
  roomPreference: '',
  specialRequests: '',
  includeBreakfast: false,
  includeDinner: false,
  includeSpa: false,
})

const isBooking = ref(false)
// Animation state
const isVisible = ref(false)
const modalScale = ref(0.95)

// Success page state
const showSuccessPage = ref(false)
const bookingSuccess = ref<BookingSuccessData | null>(null)

// Room status state
const roomStatus = ref<'available' | 'booked' | 'maintenance' | 'loading'>('loading')
const roomStatusMessage = ref('Checking Status...')
const isFetchingStatus = ref(false)
const statusFetchError = ref<string | null>(null)

// Get room type name safely
const getRoomTypeName = (room: Room | null): string => {
  if (!room) return 'Luxury Suite'
  if (typeof room.room_type === 'string') {
    return room.room_type
  }
  return room.room_type?.name || 'Luxury Suite'
}

// Get room price safely
const getRoomPrice = (room: Room | null): number => {
  if (!room) return 0
  if (typeof room.room_type === 'object' && room.room_type?.base_price_per_night) {
    return Math.round(room.room_type.base_price_per_night)
  }
  return 0
}

// Get room capacity safely
const getRoomCapacity = (room: Room | null): number => {
  if (!room) return 2
  if (typeof room.room_type === 'object' && room.room_type?.capacity) {
    return room.room_type.capacity
  }
  return 2
}

// Get room description
const getRoomDescription = (room: Room | null): string => {
  if (!room) return 'Experience luxury and comfort in our premium accommodation.'
  if (typeof room.room_type === 'object' && room.room_type?.description) {
    return room.room_type.description
  }
  return room.description || 'Experience luxury and comfort in our premium accommodation.'
}

// Get image URL based on room type
const getRoomImage = (room: Room | null): string => {
  if (!room) return '/images/rooms/deluxe.jpg'
  const roomType = getRoomTypeName(room).toLowerCase()
  const imageMap: Record<string, string> = {
    deluxe: '/images/rooms/deluxe.jpg',
    vip: '/images/rooms/suite.jpg',
    wvip: '/images/rooms/suite.jpg',
    suite: '/images/rooms/suite.jpg',
    'twin small': '/images/rooms/family.jpg',
    'twin big': '/images/rooms/family.jpg',
    standard: '/images/rooms/deluxe.jpg',
    presidential: '/images/rooms/presidential.jpg',
    executive: '/images/rooms/executive.jpg',
  }

  for (const [key, value] of Object.entries(imageMap)) {
    if (roomType.includes(key)) {
      return value
    }
  }

  return '/images/rooms/deluxe.jpg'
}

// Get amenities
const getRoomAmenities = (room: Room | null): string[] => {
  if (!room) return ['Free Wi-Fi', 'Air Conditioning', 'Flat-screen TV', 'Mini Bar']
  if (typeof room.room_type === 'object' && room.room_type?.amenities) {
    return room.room_type.amenities
  }
  return ['Free Wi-Fi', 'Air Conditioning', 'Flat-screen TV', 'Mini Bar']
}

// Get room rating
const getRoomRating = (room: Room | null): number => {
  if (!room) return 4.8
  const ratings: Record<string, number> = {
    deluxe: 4.8,
    vip: 4.9,
    wvip: 4.9,
    suite: 4.9,
    'twin small': 4.6,
    'twin big': 4.7,
    standard: 4.5,
    presidential: 5.0,
    executive: 4.8,
  }
  const roomType = getRoomTypeName(room).toLowerCase()
  for (const [key, value] of Object.entries(ratings)) {
    if (roomType.includes(key)) {
      return value
    }
  }
  return 4.7
}

// Fetch room status from backend
async function fetchRoomStatusFromBackend() {
  if (!props.room) {
    roomStatus.value = 'available'
    roomStatusMessage.value = 'Available'
    return
  }

  isFetchingStatus.value = true
  statusFetchError.value = null

  try {
    console.log('🔄 [BOOKING] Fetching room status from backend for room:', props.room.id)

    // Fetch the specific room from backend to get real-time status
    const response = await roomService.getRoom(props.room.id)
    console.log('📡 [BOOKING] Room status response from backend:', response)

    // Extract room data from response (handle both direct and wrapped responses)
    let roomData = response.data
    if (response.data && response.data.data) {
      roomData = response.data.data
    }

    console.log('🏠 [BOOKING] Room data received:', roomData)
    console.log('📊 [BOOKING] Room status field:', roomData.status)
    console.log('🔑 [BOOKING] Room is_active field:', roomData.is_active)

    // Check the room status from backend
    const status = roomData.status ? String(roomData.status).toLowerCase() : 'available'
    const isActive = roomData.is_active !== false

    console.log('✅ [BOOKING] Processed status:', status, 'isActive:', isActive)

    // Map backend status values to display states
    if (status === 'available' && isActive) {
      console.log('✅ [BOOKING] Room is AVAILABLE')
      roomStatus.value = 'available'
      roomStatusMessage.value = 'Available'
    } else if (status === 'occupied' || !isActive) {
      console.log('🔴 [BOOKING] Room is OCCUPIED')
      roomStatus.value = 'booked'
      roomStatusMessage.value = 'Occupied'
    } else if (status === 'reserved') {
      console.log('⏱️ [BOOKING] Room is RESERVED')
      roomStatus.value = 'booked'
      roomStatusMessage.value = 'Reserved'
    } else if (status === 'maintenance') {
      console.log('🔧 [BOOKING] Room is MAINTENANCE')
      roomStatus.value = 'maintenance'
      roomStatusMessage.value = 'Under Maintenance'
    } else {
      console.log('❓ [BOOKING] Unknown status:', status)
      roomStatus.value = 'available'
      roomStatusMessage.value = 'Available'
    }
  } catch (error) {
    console.error('❌ [BOOKING] Error fetching room status:', error)
    statusFetchError.value = error instanceof Error ? error.message : 'Failed to fetch status'
    // Default to available on error
    roomStatus.value = 'available'
    roomStatusMessage.value = 'Available'
  } finally {
    isFetchingStatus.value = false
  }
}

// Calculate nights
function calculateNights(): number {
  if (!bookingForm.value.checkInDate || !bookingForm.value.checkOutDate) return 0
  const checkIn = new Date(bookingForm.value.checkInDate)
  const checkOut = new Date(bookingForm.value.checkOutDate)
  const nights = Math.floor((checkOut.getTime() - checkIn.getTime()) / (1000 * 60 * 60 * 24))
  return Math.max(nights, 1)
}

// Calculate room total
function calculateRoomTotal(): number {
  if (!props.room) return 0
  return getRoomPrice(props.room) * calculateNights()
}

// Calculate additional services
function calculateAdditionalServices(): number {
  let total = 0
  if (bookingForm.value.includeBreakfast) total += 0 // Free
  if (bookingForm.value.includeDinner) total += 45 * calculateNights()
  if (bookingForm.value.includeSpa) total += 35 * calculateNights()
  return total
}

// Calculate grand total
function calculateGrandTotal(): number {
  return calculateRoomTotal() + calculateAdditionalServices()
}

// Close modal with animation
function closeModal() {
  modalScale.value = 0.95
  isVisible.value = false
  setTimeout(() => {
    resetBookingForm()
    showSuccessPage.value = false
    bookingSuccess.value = null
    emit('close')
  }, 300)
}

// Close success page and return to booking
function closeSuccessPage() {
  showSuccessPage.value = false
  bookingSuccess.value = null
  closeModal()
}

// Reset form
function resetBookingForm() {
  bookingForm.value = {
    checkInDate: '',
    checkOutDate: '',
    guests: 1,
    guestName: '',
    guestEmail: '',
    guestPhone: '',
    roomPreference: '',
    specialRequests: '',
    includeBreakfast: false,
    includeDinner: false,
    includeSpa: false,
  }
}

// Submit booking - Fixed backend integration using stores
async function submitBooking() {
  if (isBooking.value) return

  if (
    !bookingForm.value.guestName ||
    !bookingForm.value.guestEmail ||
    !bookingForm.value.guestPhone
  ) {
    const fields = []
    if (!bookingForm.value.guestName) fields.push('Full Name')
    if (!bookingForm.value.guestEmail) fields.push('Email')
    if (!bookingForm.value.guestPhone) fields.push('Phone')

    alert(`Please fill in: ${fields.join(', ')}`)
    return
  }

  isBooking.value = true

  const guestStore = useGuestStore()
  const reservationStore = useReservationStore()

  try {
    // Step 1: Create guest
    const nameParts = bookingForm.value.guestName.trim().split(' ')
    const firstName = nameParts[0]
    const lastName = nameParts.slice(1).join(' ') || firstName

    console.log('📝 [BOOKING] Creating guest with:', { firstName, lastName })

    // First, check if guest with this email already exists
    console.log('🔍 [BOOKING] Checking if guest exists with email:', bookingForm.value.guestEmail)
    await guestStore.fetchGuests({ search: bookingForm.value.guestEmail })

    let createdGuest = guestStore.guests.find((g: any) => g.email === bookingForm.value.guestEmail)

    let guestId = createdGuest?.id

    if (!guestId) {
      // Guest doesn't exist, create new one
      console.log('📝 [BOOKING] Guest not found, creating new guest...')
      await guestStore.addGuest({
        first_name: firstName,
        last_name: lastName,
        email: bookingForm.value.guestEmail,
        phone: bookingForm.value.guestPhone,
        address: '',
        nationality: '',
        passport_number: '',
        date_of_birth: '',
        preferences: [],
      })

      // Get guest ID - fetch the most recent guest by email to ensure we get the right one
      await guestStore.fetchGuests({ search: bookingForm.value.guestEmail })
      createdGuest = guestStore.guests.find((g: any) => g.email === bookingForm.value.guestEmail)
      guestId = createdGuest?.id

      if (!guestId) {
        throw new Error('Failed to retrieve guest ID after creation')
      }

      console.log('✅ [BOOKING] Guest created with ID:', guestId)
    } else {
      console.log('✅ [BOOKING] Guest already exists with ID:', guestId)
    }

    // Step 2: Create reservation
    const reservationData = {
      guest_id: guestId,
      room_id: props.room?.id || '',
      check_in_date: bookingForm.value.checkInDate,
      check_out_date: bookingForm.value.checkOutDate,
      number_of_guests: bookingForm.value.guests,
      special_requests: bookingForm.value.specialRequests,
      status: 'pending' as const,
    }

    console.log('📝 [BOOKING] Creating reservation with:', reservationData)

    const reservationResult = await reservationStore.createReservation(reservationData)

    console.log('✅ [BOOKING] Reservation created:', reservationResult)

    // Prepare booking success data
    const successData: BookingSuccessData = {
      booking_reference:
        reservationResult.data?.booking_reference ||
        reservationResult.booking_reference ||
        'REF-' + Date.now(),
      guest_name: bookingForm.value.guestName,
      room_type: getRoomTypeName(props.room),
      check_in_date: bookingForm.value.checkInDate,
      check_out_date: bookingForm.value.checkOutDate,
      number_of_guests: bookingForm.value.guests,
      special_requests: bookingForm.value.specialRequests || undefined,
    }

    // Show success page instead of alert
    bookingSuccess.value = successData
    showSuccessPage.value = true

    // Emit event with booking data
    emit('submit', reservationResult.data || reservationResult)
  } catch (error: any) {
    console.error('❌ Booking error:', error)
    console.error('❌ Error response:', error.response?.data)
    console.error('❌ Error status:', error.response?.status)

    // Handle specific error cases
    let errorMessage = 'Something went wrong'

    if (error.response?.status === 422) {
      // Validation error - extract from errors object
      const responseData = error.response?.data
      if (responseData?.errors && typeof responseData.errors === 'object') {
        const errors = responseData.errors
        const firstKey = Object.keys(errors)[0]
        const firstError = errors[firstKey]
        if (Array.isArray(firstError)) {
          errorMessage = firstError[0]
        } else if (typeof firstError === 'string') {
          errorMessage = firstError
        }
      } else if (responseData?.message) {
        errorMessage = responseData.message
      }
    } else if (error.response?.data?.message) {
      errorMessage = error.response.data.message
    } else if (error.message) {
      errorMessage = error.message
    }

    alert(`❌ Error: ${errorMessage}`)
  }
}

// Initialize form
const initializeForm = () => {
  if (props.isOpen && props.room) {
    const today = new Date()
    const tomorrow = new Date(today)
    tomorrow.setDate(tomorrow.getDate() + 1)

    bookingForm.value.checkInDate = today.toISOString().split('T')[0]
    bookingForm.value.checkOutDate = tomorrow.toISOString().split('T')[0]
    bookingForm.value.guests = getRoomCapacity(props.room)

    // Fetch room status from backend
    fetchRoomStatusFromBackend()

    nextTick(() => {
      isVisible.value = true
      modalScale.value = 1
    })
  }
}

// Watch for modal open
watch(
  () => props.isOpen,
  (newVal) => {
    if (newVal) {
      initializeForm()
      document.body.style.overflow = 'hidden'
      document.body.style.touchAction = 'none'
    } else {
      document.body.style.overflow = 'auto'
      document.body.style.touchAction = 'auto'
    }
  },
)

// Cleanup
onMounted(() => {
  return () => {
    document.body.style.overflow = 'auto'
    document.body.style.touchAction = 'auto'
  }
})
</script>

<template>
  <Teleport to="body">
    <!-- Booking Modal -->
    <div
      v-if="isOpen && room"
      class="fixed inset-0 bg-black z-50 flex items-center justify-center p-3 md:p-4 overflow-hidden transition-opacity duration-300"
      :class="isVisible ? 'opacity-100' : 'opacity-0'"
      @click.self="closeModal"
    >
      <!-- Modal Container -->
      <div
        class="bg-white w-full max-w-6xl rounded-2xl md:rounded-3xl flex flex-col overflow-hidden max-h-[95vh] shadow-2xl transition-all duration-300"
        :style="{ transform: `scale(${modalScale})`, opacity: isVisible ? 1 : 0 }"
      >
        <!-- Header -->
        <div
          class="relative bg-gradient-to-r from-amber-500 via-orange-500 to-amber-600 px-4 md:px-6 lg:px-8 py-4 md:py-5 flex justify-between items-center flex-shrink-0"
        >
          <div class="flex items-center gap-3">
            <div class="p-1.5 bg-white/20 rounded-lg backdrop-blur-sm">
              <Sparkles class="w-5 h-5 text-white" />
            </div>
            <div>
              <h2 class="text-lg md:text-2xl font-bold text-white tracking-tight">
                Complete Your Booking
              </h2>
              <p class="text-xs text-amber-100/90 hidden sm:block">
                Secure your stay at Grand Horizon
              </p>
            </div>
          </div>
          <button
            @click="closeModal"
            class="text-white/80 hover:text-white hover:bg-white/20 p-2 rounded-lg transition-all duration-200 leading-none flex-shrink-0"
          >
            <X class="w-5 h-5 md:w-6 md:h-6" />
          </button>
        </div>

        <!-- Content Grid -->
        <div
          class="flex-1 grid grid-cols-1 lg:grid-cols-5 gap-4 md:gap-6 p-4 md:p-6 lg:p-8 overflow-hidden"
        >
          <!-- Left Column - Room Summary -->
          <div class="lg:col-span-2 flex flex-col space-y-4 overflow-y-auto order-2 lg:order-1">
            <!-- Room Image -->
            <div
              class="relative w-full bg-gradient-to-br from-slate-100 to-slate-200 rounded-xl overflow-hidden flex-shrink-0 shadow-lg"
              style="aspect-ratio: 4/3; min-height: 220px"
            >
              <img
                :src="getRoomImage(room)"
                :alt="getRoomTypeName(room)"
                class="w-full h-full object-cover"
              />
              <!-- Badge -->
              <div
                class="absolute top-3 right-3 bg-black/70 backdrop-blur-sm text-white text-xs font-semibold px-3 py-1.5 rounded-full flex items-center gap-1.5"
              >
                <Star class="w-3.5 h-3.5 fill-yellow-400 text-yellow-400" />
                {{ getRoomRating(room) }}
              </div>
              <!-- Availability Badge - Shows Real Status -->
              <div
                v-if="!isFetchingStatus"
                :class="[
                  'absolute bottom-3 left-3 backdrop-blur-sm text-white text-xs font-semibold px-3 py-1.5 rounded-full flex items-center gap-1.5',
                  roomStatus === 'available'
                    ? 'bg-emerald-500/90'
                    : roomStatus === 'booked'
                      ? 'bg-rose-500/90'
                      : 'bg-amber-500/90',
                ]"
              >
                <CheckCircle class="w-3.5 h-3.5" />
                {{ roomStatusMessage }}
              </div>
              <!-- Loading Status -->
              <div
                v-else
                class="absolute bottom-3 left-3 bg-slate-500/90 backdrop-blur-sm text-white text-xs font-semibold px-3 py-1.5 rounded-full flex items-center gap-1.5"
              >
                <Loader class="w-3.5 h-3.5 animate-spin" />
                Checking Status...
              </div>
            </div>

            <!-- Room Status Badge -->
            <div
              :class="[
                'rounded-xl p-3 flex items-center justify-between flex-shrink-0 shadow-sm border',
                roomStatus === 'available'
                  ? 'bg-emerald-50 border-emerald-200'
                  : roomStatus === 'booked'
                    ? 'bg-rose-50 border-rose-200'
                    : 'bg-amber-50 border-amber-200',
              ]"
            >
              <div class="flex items-center gap-2">
                <CheckCircle
                  :class="[
                    'w-5 h-5 flex-shrink-0',
                    roomStatus === 'available'
                      ? 'text-emerald-600'
                      : roomStatus === 'booked'
                        ? 'text-rose-600'
                        : 'text-amber-600',
                  ]"
                />
                <div>
                  <p
                    :class="[
                      'text-xs font-semibold',
                      roomStatus === 'available'
                        ? 'text-emerald-900'
                        : roomStatus === 'booked'
                          ? 'text-rose-900'
                          : 'text-amber-900',
                    ]"
                  >
                    Room Status
                  </p>
                  <p
                    :class="[
                      'text-sm font-bold',
                      roomStatus === 'available'
                        ? 'text-emerald-600'
                        : roomStatus === 'booked'
                          ? 'text-rose-600'
                          : 'text-amber-600',
                    ]"
                  >
                    {{ roomStatusMessage }}
                  </p>
                </div>
              </div>
              <span
                :class="[
                  'px-3 py-1 rounded-full text-xs font-bold',
                  roomStatus === 'available'
                    ? 'bg-emerald-100 text-emerald-700'
                    : roomStatus === 'booked'
                      ? 'bg-rose-100 text-rose-700'
                      : 'bg-amber-100 text-amber-700',
                ]"
              >
                {{ roomStatusMessage }}
              </span>
            </div>

            <!-- Room Details -->
            <div
              class="bg-gradient-to-br from-slate-50 to-white border border-slate-200/80 rounded-xl p-4 md:p-5 space-y-3 flex-shrink-0 shadow-sm"
            >
              <!-- Title -->
              <div class="border-b border-slate-200/80 pb-3">
                <div class="flex items-start justify-between">
                  <div>
                    <h3 class="text-lg md:text-xl font-bold text-slate-900 line-clamp-1">
                      {{ getRoomTypeName(room) }}
                    </h3>
                    <p class="text-sm text-slate-500">Room #{{ room.room_number }}</p>
                  </div>
                  <div class="text-right">
                    <span class="text-2xl font-bold text-amber-600"
                      >ETB {{ getRoomPrice(room) }}</span
                    >
                    <span class="text-xs text-slate-500 block">/ night</span>
                  </div>
                </div>
              </div>

              <!-- Amenities -->
              <div class="flex flex-wrap gap-1.5">
                <span
                  v-for="amenity in getRoomAmenities(room).slice(0, 4)"
                  :key="amenity"
                  class="inline-flex items-center gap-1 text-xs bg-slate-100 text-slate-700 px-2.5 py-1 rounded-full"
                >
                  <CheckCircle class="w-3 h-3 text-emerald-500" />
                  {{ amenity }}
                </span>
                <span
                  v-if="getRoomAmenities(room).length > 4"
                  class="text-xs text-slate-400 font-medium"
                >
                  +{{ getRoomAmenities(room).length - 4 }} more
                </span>
              </div>

              <!-- Description -->
              <p class="text-sm text-slate-600 leading-relaxed">
                {{ getRoomDescription(room) }}
              </p>

              <!-- Stay Summary -->
              <div class="bg-amber-50/80 border border-amber-200/60 rounded-xl p-3 space-y-2">
                <div class="flex justify-between items-center">
                  <div class="flex items-center gap-2">
                    <Calendar class="w-4 h-4 text-amber-600" />
                    <span class="text-xs font-semibold text-slate-700">Stay Duration</span>
                  </div>
                  <span class="text-sm font-bold text-slate-900"
                    >{{ calculateNights() }} Night{{ calculateNights() !== 1 ? 's' : '' }}</span
                  >
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-slate-600">Check-in</span>
                  <span class="font-medium text-slate-800">{{
                    bookingForm.checkInDate
                      ? new Date(bookingForm.checkInDate).toLocaleDateString('en-US', {
                          weekday: 'short',
                          month: 'short',
                          day: 'numeric',
                        })
                      : '--'
                  }}</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-slate-600">Check-out</span>
                  <span class="font-medium text-slate-800">{{
                    bookingForm.checkOutDate
                      ? new Date(bookingForm.checkOutDate).toLocaleDateString('en-US', {
                          weekday: 'short',
                          month: 'short',
                          day: 'numeric',
                        })
                      : '--'
                  }}</span>
                </div>
                <div class="flex justify-between text-sm pt-1.5 border-t border-amber-200/60">
                  <span class="text-slate-600">Guests</span>
                  <span class="font-medium text-slate-800"
                    >{{ bookingForm.guests }} Guest{{ bookingForm.guests !== 1 ? 's' : '' }}</span
                  >
                </div>
              </div>

              <!-- Price Breakdown -->
              <div class="border-t border-slate-200/80 pt-3 space-y-1.5">
                <div class="flex justify-between text-sm">
                  <span class="text-slate-600"
                    >Room ({{ calculateNights() }} × ETB {{ getRoomPrice(room) }})</span
                  >
                  <span class="font-semibold text-slate-900">ETB {{ calculateRoomTotal() }}</span>
                </div>
                <div
                  v-if="bookingForm.includeBreakfast"
                  class="flex justify-between text-sm text-emerald-600"
                >
                  <span class="flex items-center gap-1.5">
                    <Coffee class="w-3.5 h-3.5" />
                    Breakfast
                  </span>
                  <span>Free</span>
                </div>
                <div
                  v-if="bookingForm.includeDinner"
                  class="flex justify-between text-sm text-slate-700"
                >
                  <span class="flex items-center gap-1.5">
                    <Utensils class="w-3.5 h-3.5" />
                    Dinner ({{ calculateNights() }} × ETB 45)
                  </span>
                  <span>ETB {{ calculateNights() * 45 }}</span>
                </div>
                <div
                  v-if="bookingForm.includeSpa"
                  class="flex justify-between text-sm text-slate-700"
                >
                  <span class="flex items-center gap-1.5">
                    <Sparkles class="w-3.5 h-3.5" />
                    Spa ({{ calculateNights() }} × ETB 35)
                  </span>
                  <span>ETB {{ calculateNights() * 35 }}</span>
                </div>
              </div>

              <!-- Grand Total -->
              <div
                class="bg-gradient-to-r from-amber-50 to-orange-50 border border-amber-200 rounded-xl p-3 flex justify-between items-center"
              >
                <span class="text-sm font-bold text-slate-800">Grand Total</span>
                <span class="text-xl md:text-2xl font-bold text-amber-600"
                  >ETB {{ calculateGrandTotal() }}</span
                >
              </div>
            </div>
          </div>

          <!-- Right Column - Booking Form -->
          <div class="lg:col-span-3 flex flex-col space-y-3 overflow-hidden order-1 lg:order-2">
            <div class="flex items-center justify-between flex-shrink-0">
              <div>
                <h3 class="text-sm font-semibold text-slate-900">Guest Information</h3>
                <p class="text-xs text-slate-500">Fill in your details to complete the booking</p>
              </div>
              <span class="text-xs text-red-500 font-medium">* Required</span>
            </div>

            <!-- Mobile Summary -->
            <div
              class="bg-gradient-to-r from-slate-50 to-white border border-slate-200 rounded-xl overflow-hidden lg:hidden flex-shrink-0 shadow-sm"
            >
              <div class="flex items-center gap-3 p-3">
                <img
                  :src="getRoomImage(room)"
                  :alt="getRoomTypeName(room)"
                  class="w-20 h-20 object-cover rounded-lg flex-shrink-0"
                />
                <div class="flex-1 min-w-0">
                  <h4 class="text-sm font-bold text-slate-900 truncate">
                    {{ getRoomTypeName(room) }}
                  </h4>
                  <p class="text-xs text-slate-500">Room #{{ room.room_number }}</p>
                  <p class="text-sm font-bold text-amber-600 mt-0.5">
                    ETB {{ calculateGrandTotal() }}
                  </p>
                </div>
              </div>
            </div>

            <!-- Form Fields -->
            <div class="space-y-2.5 overflow-y-auto flex-1 pr-1.5 md:pr-2">
              <!-- Date Range -->
              <div class="grid grid-cols-2 gap-2">
                <div>
                  <label class="block text-xs font-semibold text-slate-700 mb-0.5">
                    Check-in <span class="text-red-500">*</span>
                  </label>
                  <input
                    v-model="bookingForm.checkInDate"
                    type="date"
                    class="w-full px-3 py-2 border-2 border-slate-200 rounded-lg focus:border-amber-400 focus:ring-2 focus:ring-amber-400/20 focus:outline-none text-sm transition-all"
                  />
                </div>
                <div>
                  <label class="block text-xs font-semibold text-slate-700 mb-0.5">
                    Check-out <span class="text-red-500">*</span>
                  </label>
                  <input
                    v-model="bookingForm.checkOutDate"
                    type="date"
                    class="w-full px-3 py-2 border-2 border-slate-200 rounded-lg focus:border-amber-400 focus:ring-2 focus:ring-amber-400/20 focus:outline-none text-sm transition-all"
                  />
                </div>
              </div>

              <!-- Guests -->
              <div>
                <label class="block text-xs font-semibold text-slate-700 mb-0.5">
                  Guests <span class="text-red-500">*</span>
                </label>
                <div class="flex items-center gap-2">
                  <Users class="w-4 h-4 text-slate-400 flex-shrink-0" />
                  <input
                    v-model.number="bookingForm.guests"
                    type="number"
                    min="1"
                    :max="getRoomCapacity(room)"
                    class="w-full px-3 py-2 border-2 border-slate-200 rounded-lg focus:border-amber-400 focus:ring-2 focus:ring-amber-400/20 focus:outline-none text-sm transition-all"
                  />
                </div>
              </div>

              <!-- Guest Name -->
              <div>
                <label class="block text-xs font-semibold text-slate-700 mb-0.5">
                  Full Name <span class="text-red-500">*</span>
                </label>
                <div class="flex items-center gap-2">
                  <User class="w-4 h-4 text-slate-400 flex-shrink-0" />
                  <input
                    v-model="bookingForm.guestName"
                    type="text"
                    placeholder="e.g., Almaz Bekele"
                    class="w-full px-3 py-2 border-2 border-slate-200 rounded-lg focus:border-amber-400 focus:ring-2 focus:ring-amber-400/20 focus:outline-none text-sm transition-all placeholder:text-slate-400"
                  />
                </div>
              </div>

              <!-- Email -->
              <div>
                <label class="block text-xs font-semibold text-slate-700 mb-0.5">
                  Email Address <span class="text-red-500">*</span>
                </label>
                <div class="flex items-center gap-2">
                  <Mail class="w-4 h-4 text-slate-400 flex-shrink-0" />
                  <input
                    v-model="bookingForm.guestEmail"
                    type="email"
                    placeholder="e.g., almaz.b@gmail.com"
                    class="w-full px-3 py-2 border-2 border-slate-200 rounded-lg focus:border-amber-400 focus:ring-2 focus:ring-amber-400/20 focus:outline-none text-sm transition-all placeholder:text-slate-400"
                  />
                </div>
              </div>

              <!-- Phone -->
              <div>
                <label class="block text-xs font-semibold text-slate-700 mb-0.5">
                  Phone Number <span class="text-red-500">*</span>
                </label>
                <div class="flex items-center gap-2">
                  <Phone class="w-4 h-4 text-slate-400 flex-shrink-0" />
                  <input
                    v-model="bookingForm.guestPhone"
                    type="tel"
                    placeholder="e.g., +251 911 234 567"
                    class="w-full px-3 py-2 border-2 border-slate-200 rounded-lg focus:border-amber-400 focus:ring-2 focus:ring-amber-400/20 focus:outline-none text-sm transition-all placeholder:text-slate-400"
                  />
                </div>
              </div>

              <!-- Room Preference -->
              <div>
                <label class="block text-xs font-semibold text-slate-700 mb-0.5">
                  Preferred Room (Optional)
                </label>
                <div class="flex items-center gap-2">
                  <MapPin class="w-4 h-4 text-slate-400 flex-shrink-0" />
                  <input
                    v-model="bookingForm.roomPreference"
                    type="text"
                    placeholder="e.g., 204"
                    class="w-full px-3 py-2 border-2 border-slate-200 rounded-lg focus:border-amber-400 focus:ring-2 focus:ring-amber-400/20 focus:outline-none text-sm transition-all placeholder:text-slate-400"
                  />
                </div>
              </div>

              <!-- Add-ons -->
              <div class="bg-slate-50/80 rounded-xl p-3 space-y-2 border border-slate-200/60">
                <p class="text-xs font-semibold text-slate-700">Add-on Services</p>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-1.5">
                  <label
                    class="flex items-center gap-2 bg-white px-3 py-2 rounded-lg border border-slate-200 hover:border-amber-300 transition-all cursor-pointer"
                  >
                    <input
                      v-model="bookingForm.includeBreakfast"
                      type="checkbox"
                      class="w-4 h-4 rounded border-slate-300 text-amber-500 focus:ring-amber-400 cursor-pointer"
                    />
                    <div class="flex items-center gap-1.5">
                      <Coffee class="w-3.5 h-3.5 text-amber-600" />
                      <span class="text-xs font-medium text-slate-700">Breakfast</span>
                    </div>
                  </label>
                  <label
                    class="flex items-center gap-2 bg-white px-3 py-2 rounded-lg border border-slate-200 hover:border-amber-300 transition-all cursor-pointer"
                  >
                    <input
                      v-model="bookingForm.includeDinner"
                      type="checkbox"
                      class="w-4 h-4 rounded border-slate-300 text-amber-500 focus:ring-amber-400 cursor-pointer"
                    />
                    <div class="flex items-center gap-1.5">
                      <Utensils class="w-3.5 h-3.5 text-amber-600" />
                      <span class="text-xs font-medium text-slate-700">Dinner</span>
                    </div>
                  </label>
                  <label
                    class="flex items-center gap-2 bg-white px-3 py-2 rounded-lg border border-slate-200 hover:border-amber-300 transition-all cursor-pointer"
                  >
                    <input
                      v-model="bookingForm.includeSpa"
                      type="checkbox"
                      class="w-4 h-4 rounded border-slate-300 text-amber-500 focus:ring-amber-400 cursor-pointer"
                    />
                    <div class="flex items-center gap-1.5">
                      <Sparkles class="w-3.5 h-3.5 text-amber-600" />
                      <span class="text-xs font-medium text-slate-700">Spa</span>
                    </div>
                  </label>
                </div>
              </div>

              <!-- Special Requests -->
              <div>
                <label class="block text-xs font-semibold text-slate-700 mb-0.5">
                  Special Requests (Optional)
                </label>
                <textarea
                  v-model="bookingForm.specialRequests"
                  placeholder="e.g., Ground floor, extra pillows, etc."
                  rows="2"
                  class="w-full px-3 py-2 border-2 border-slate-200 rounded-lg focus:border-amber-400 focus:ring-2 focus:ring-amber-400/20 focus:outline-none text-sm transition-all placeholder:text-slate-400 resize-none"
                />
              </div>

              <!-- Info Message -->
              <div
                class="bg-emerald-50/80 border border-emerald-200/60 rounded-lg p-2.5 text-xs text-emerald-800 flex items-start gap-2"
              >
                <CheckCircle class="w-4 h-4 text-emerald-500 flex-shrink-0 mt-0.5" />
                <p>
                  ✓ Your booking will generate a referral code. You'll receive a confirmation email
                  shortly.
                </p>
              </div>
            </div>
          </div>
        </div>

        <!-- Footer Actions -->
        <div
          class="border-t border-slate-200 bg-slate-50/80 px-4 md:px-6 lg:px-8 py-3 md:py-4 flex gap-3 flex-shrink-0"
        >
          <button
            @click="closeModal"
            class="flex-1 px-4 py-2.5 md:py-3 bg-white hover:bg-slate-50 text-slate-700 font-semibold rounded-xl border-2 border-slate-200 transition-all duration-200 text-sm hover:border-slate-300"
          >
            Cancel
          </button>
          <button
            @click="submitBooking"
            :disabled="isBooking || roomStatus !== 'available'"
            class="flex-1 px-4 py-3 rounded-xl font-bold text-sm transition-all duration-300 shadow-lg flex items-center justify-center gap-2 disabled:cursor-not-allowed disabled:opacity-70"
            :class="
              isBooking
                ? 'bg-slate-700 text-white'
                : roomStatus === 'available'
                  ? 'bg-gradient-to-r from-amber-500 via-orange-500 to-amber-600 hover:from-amber-600 hover:via-orange-600 hover:to-amber-700 text-white shadow-amber-500/30 hover:scale-[1.02]'
                  : 'bg-slate-300 text-slate-500'
            "
          >
            <!-- Loading -->
            <template v-if="isBooking">
              <Loader class="w-5 h-5 animate-spin" />
              <span>Processing Booking...</span>
            </template>

            <!-- Normal -->
            <template v-else>
              <Sparkles class="w-5 h-5" />
              <span>Complete Booking</span>
            </template>
          </button>
        </div>
      </div>
    </div>

    <!-- Booking Success Page -->
    <BookingSuccessPage
      :isOpen="showSuccessPage"
      :booking="bookingSuccess"
      @close="closeSuccessPage"
      @shareWhatsApp="() => {}"
      @downloadPDF="() => {}"
    />
  </Teleport>
</template>

<style scoped>
/* Smooth scrollbar */
.overflow-y-auto::-webkit-scrollbar {
  width: 4px;
}

.overflow-y-auto::-webkit-scrollbar-track {
  background: transparent;
}

.overflow-y-auto::-webkit-scrollbar-thumb {
  background: #e2e8f0;
  border-radius: 9999px;
}

.overflow-y-auto::-webkit-scrollbar-thumb:hover {
  background: #cbd5e1;
}
</style>
