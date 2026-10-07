<template>
  <div class="min-h-screen w-full bg-gradient-to-br from-slate-50 via-emerald-50/30 to-teal-50/20 dark:from-slate-950 dark:via-slate-900 dark:to-emerald-950 flex flex-col justify-center py-2 sm:py-4 lg:py-6 px-3 sm:px-6 transition-colors duration-300">

    <div class="max-w-4xl w-full mx-auto">
      <!-- Main single-page ticket card -->
      <div
        class="relative bg-white dark:bg-slate-900 rounded-2xl sm:rounded-3xl shadow-[0_15px_50px_-15px_rgba(16,185,129,0.25)] border border-slate-200/80 dark:border-slate-800 overflow-hidden transition-all duration-500 ease-out"
        :class="showHeader ? 'opacity-100 scale-100' : 'opacity-0 scale-[0.98]'"
      >
        <!-- Top decorative micro accent bar -->
        <div class="h-1.5 w-full bg-gradient-to-r from-emerald-400 via-teal-500 to-green-500"></div>

        <!-- ====================================================== -->
        <!-- COMPACT SUCCESS HEADER BAR                             -->
        <!-- ====================================================== -->
        <div class="relative bg-gradient-to-r from-emerald-600 via-teal-600 to-green-600 px-4 sm:px-6 py-3.5 sm:py-4 text-white overflow-hidden">
          <!-- Subtle background decorative glow -->
          <div class="absolute -right-10 -top-10 w-44 h-44 rounded-full bg-white/10 blur-xl pointer-events-none"></div>

          <div class="relative flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 sm:gap-4">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 sm:w-11 sm:h-11 bg-white rounded-full flex items-center justify-center shadow-md flex-shrink-0">
                <svg class="w-6 h-6 text-emerald-600" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
              </div>
              <div>
                <div class="flex items-center gap-2 flex-wrap">
                  <h1 class="text-lg sm:text-xl font-bold tracking-tight text-white leading-tight">
                    Payment Successful!
                  </h1>
                  <span class="inline-flex items-center gap-1 bg-white/20 backdrop-blur-sm px-2 py-0.5 rounded-full text-[10px] font-semibold text-emerald-100">
                    <svg class="w-3 h-3 text-emerald-200" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M2.166 4.999A11.954 11.954 0 0010 1.944 11.954 11.954 0 0017.834 5c.11.65.166 1.32.166 2.001 0 5.225-3.34 9.67-8 11.317C5.34 16.67 2 12.225 2 7c0-.682.057-1.35.166-2.001zm11.541 3.708a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                    Verified & Confirmed
                  </span>
                </div>
                <p class="text-emerald-100 text-xs mt-0.5">
                  Your reservation is confirmed. Download your receipt or return home below.
                </p>
              </div>
            </div>

            <!-- Total Paid Hero badge on right -->
            <div class="flex items-center sm:items-end justify-between sm:justify-center sm:flex-col bg-white/15 backdrop-blur-md rounded-xl px-3 py-1.5 border border-white/20 self-start sm:self-auto w-full sm:w-auto">
              <span class="text-[10px] font-semibold tracking-wider text-emerald-100 uppercase">Amount Paid</span>
              <div class="flex items-baseline gap-1">
                <span class="text-lg sm:text-xl font-black text-white tracking-tight">
                  {{ Number(reservationData?.total_amount || 0).toLocaleString() }}
                </span>
                <span class="text-xs font-bold text-emerald-200">ETB</span>
              </div>
            </div>
          </div>
        </div>

        <!-- ====================================================== -->
        <!-- COMPACT 2-COLUMN BODY (FIT-TO-PAGE)                    -->
        <!-- ====================================================== -->
        <div class="p-3.5 sm:p-5 grid grid-cols-1 lg:grid-cols-12 gap-3.5">

          <!-- LEFT COLUMN: Booking & Payment Information (7 cols) -->
          <div class="lg:col-span-7 space-y-2.5 sm:space-y-3">

            <!-- Booking Overview Card -->
            <div class="bg-slate-50 dark:bg-slate-800/60 rounded-xl p-3 border border-slate-200/80 dark:border-slate-700/60">
              <div class="flex items-center justify-between pb-2 mb-2 border-b border-slate-200 dark:border-slate-700">
                <div class="flex items-center gap-1.5">
                  <span class="w-5 h-5 rounded bg-emerald-100 dark:bg-emerald-900/50 flex items-center justify-center text-emerald-700 dark:text-emerald-300">
                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                      <path d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z"/>
                    </svg>
                  </span>
                  <span class="text-xs font-bold text-slate-900 dark:text-white">Booking Details</span>
                </div>
                <div class="flex items-center gap-1">
                  <span class="text-[10px] text-slate-500 dark:text-slate-400 font-semibold uppercase">Ref:</span>
                  <span class="font-mono text-xs font-bold text-slate-900 dark:text-emerald-300 bg-white dark:bg-slate-900 px-1.5 py-0.5 rounded border border-slate-200 dark:border-slate-700">
                    {{ reservationData?.booking_reference || 'REF-' + (txRef?.substring(0, 8).toUpperCase() || 'CONFIRMED') }}
                  </span>
                </div>
              </div>

              <!-- Itinerary bar (Check-in & Check-out) -->
              <div class="grid grid-cols-2 gap-2 bg-white dark:bg-slate-900 rounded-lg p-2 border border-slate-200/70 dark:border-slate-800 mb-2">
                <div>
                  <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider">Check-in</p>
                  <p class="text-xs sm:text-sm font-bold text-slate-900 dark:text-white">
                    {{ formatDate(reservationData?.check_in_date) }}
                  </p>
                </div>
                <div class="border-l border-slate-100 dark:border-slate-800 pl-2">
                  <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider">Check-out</p>
                  <p class="text-xs sm:text-sm font-bold text-slate-900 dark:text-white">
                    {{ formatDate(reservationData?.check_out_date) }}
                  </p>
                </div>
              </div>

              <!-- Room & Guest Specs -->
              <div class="grid grid-cols-2 sm:grid-cols-4 gap-1.5 text-xs">
                <div class="bg-white dark:bg-slate-900 p-1.5 rounded-lg border border-slate-200/60 dark:border-slate-800">
                  <span class="text-[10px] text-slate-400 uppercase font-semibold block">Room</span>
                  <span class="font-bold text-slate-900 dark:text-slate-100 text-xs">
                    {{ reservationData?.room_number || (reservationData?.room_id ? 'Room ' + reservationData.room_id.substring(0, 4) : 'Deluxe Room') }}
                  </span>
                </div>
                <div class="bg-white dark:bg-slate-900 p-1.5 rounded-lg border border-slate-200/60 dark:border-slate-800">
                  <span class="text-[10px] text-slate-400 uppercase font-semibold block">Guests</span>
                  <span class="font-bold text-slate-900 dark:text-slate-100 text-xs">
                    {{ reservationData?.number_of_guests || 1 }} {{ reservationData?.number_of_guests === 1 ? 'Guest' : 'Guests' }}
                  </span>
                </div>
                <div class="bg-white dark:bg-slate-900 p-1.5 rounded-lg border border-slate-200/60 dark:border-slate-800">
                  <span class="text-[10px] text-slate-400 uppercase font-semibold block">Status</span>
                  <span class="font-bold text-emerald-600 dark:text-emerald-400 text-xs">✓ Confirmed</span>
                </div>
                <div class="bg-white dark:bg-slate-900 p-1.5 rounded-lg border border-slate-200/60 dark:border-slate-800">
                  <span class="text-[10px] text-slate-400 uppercase font-semibold block">Gateway</span>
                  <span class="font-bold text-slate-900 dark:text-slate-100 text-xs">Chapa</span>
                </div>
              </div>
            </div>

            <!-- Guest & Contact Info Card -->
            <div class="bg-blue-50/50 dark:bg-blue-950/20 rounded-xl p-3 border border-blue-100 dark:border-blue-900/40">
              <div class="flex items-center gap-1.5 mb-1.5">
                <span class="w-4 h-4 rounded bg-blue-100 dark:bg-blue-900/50 flex items-center justify-center text-blue-600 dark:text-blue-400">
                  <svg class="w-2.5 h-2.5" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z"/>
                  </svg>
                </span>
                <span class="text-xs font-bold text-slate-900 dark:text-white">Guest Information</span>
              </div>
              <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 text-xs">
                <div>
                  <span class="text-[10px] font-semibold text-blue-600 dark:text-blue-400 uppercase tracking-wider block">Name</span>
                  <span class="font-semibold text-slate-900 dark:text-slate-100 truncate block text-xs">
                    {{ reservationData?.first_name || 'Guest' }} {{ reservationData?.last_name || '' }}
                  </span>
                </div>
                <div>
                  <span class="text-[10px] font-semibold text-blue-600 dark:text-blue-400 uppercase tracking-wider block">Email</span>
                  <span class="text-slate-800 dark:text-slate-200 truncate block font-mono text-[11px]">
                    {{ reservationData?.email || 'N/A' }}
                  </span>
                </div>
                <div class="col-span-2 sm:col-span-1">
                  <span class="text-[10px] font-semibold text-blue-600 dark:text-blue-400 uppercase tracking-wider block">Phone</span>
                  <span class="text-slate-800 dark:text-slate-200 block text-xs">
                    {{ reservationData?.phone || 'N/A' }}
                  </span>
                </div>
              </div>
              <div v-if="reservationData?.special_requests" class="mt-1.5 pt-1.5 border-t border-blue-100/70 dark:border-blue-900/30 text-[11px]">
                <span class="font-semibold text-blue-700 dark:text-blue-400">Requests:</span>
                <span class="text-slate-700 dark:text-slate-300 ml-1">{{ reservationData.special_requests }}</span>
              </div>
            </div>

            <!-- Transaction Reference snippet -->
            <div class="bg-amber-50/60 dark:bg-amber-950/20 rounded-xl px-3 py-1.5 border border-amber-200/70 dark:border-amber-900/40 flex items-center justify-between text-xs">
              <div class="flex items-center gap-1.5 truncate">
                <span class="text-[10px] font-bold text-amber-700 dark:text-amber-400 uppercase">TX Ref:</span>
                <span class="font-mono text-[11px] font-semibold text-slate-800 dark:text-slate-200 truncate">{{ txRef || 'TX-CHAPA-SUCCESS' }}</span>
              </div>
              <span class="text-[10px] font-semibold text-amber-700 dark:text-amber-400 bg-amber-100 dark:bg-amber-900/50 px-2 py-0.5 rounded-full flex-shrink-0">
                {{ new Date().toLocaleDateString('en-ET', { month: 'short', day: 'numeric' }) }} • {{ new Date().toLocaleTimeString('en-ET', { hour: '2-digit', minute: '2-digit' }) }}
              </span>
            </div>

          </div>

          <!-- RIGHT COLUMN: Next Steps, Key Notes & Action Buttons (5 cols) -->
          <div class="lg:col-span-5 flex flex-col justify-between space-y-2.5 sm:space-y-3">

            <!-- What's Next 3-step checklist -->
            <div class="bg-slate-50 dark:bg-slate-800/60 rounded-xl p-3 border border-slate-200/80 dark:border-slate-700/60">
              <h3 class="text-xs font-bold text-slate-900 dark:text-white mb-2 flex items-center gap-1.5">
                <span class="w-4 h-4 rounded bg-purple-100 dark:bg-purple-900/50 flex items-center justify-center text-purple-600 dark:text-purple-400">
                  <svg class="w-2.5 h-2.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/></svg>
                </span>
                What's Next?
              </h3>
              <div class="space-y-1.5 text-xs">
                <div class="flex items-center gap-2 bg-white dark:bg-slate-900 p-1.5 rounded-lg border border-slate-100 dark:border-slate-800">
                  <span class="w-4 h-4 rounded-full bg-emerald-500 text-white flex items-center justify-center text-[10px] font-bold flex-shrink-0">1</span>
                  <span class="text-slate-700 dark:text-slate-200 font-medium">Receipt & confirmation sent to your email</span>
                </div>
                <div class="flex items-center gap-2 bg-white dark:bg-slate-900 p-1.5 rounded-lg border border-slate-100 dark:border-slate-800">
                  <span class="w-4 h-4 rounded-full bg-blue-500 text-white flex items-center justify-center text-[10px] font-bold flex-shrink-0">2</span>
                  <span class="text-slate-700 dark:text-slate-200 font-medium">Arrive 30 mins before check-in time</span>
                </div>
                <div class="flex items-center gap-2 bg-white dark:bg-slate-900 p-1.5 rounded-lg border border-slate-100 dark:border-slate-800">
                  <span class="w-4 h-4 rounded-full bg-purple-500 text-white flex items-center justify-center text-[10px] font-bold flex-shrink-0">3</span>
                  <span class="text-slate-700 dark:text-slate-200 font-medium">Present your Ref at reception desk</span>
                </div>
              </div>
            </div>

            <!-- Important Information Micro Callout -->
            <div class="bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-950/30 dark:to-indigo-950/30 rounded-xl p-2.5 border border-blue-200 dark:border-blue-800/50 text-[11px] text-blue-900 dark:text-blue-200 leading-snug">
              <div class="font-bold flex items-center gap-1 text-xs text-blue-950 dark:text-blue-100 mb-0.5">
                <span>ℹ️</span> Important Notice
              </div>
              <p>Keep your reference handy for check-in. For cancellations or changes, contact front desk at least 48 hours ahead.</p>
            </div>

            <!-- Primary and Secondary Actions -->
            <div class="space-y-2 pt-0.5">
              <!-- Track My Order Button (for food orders) -->
              <button
                v-if="isOrderPayment"
                @click="trackOrder"
                :disabled="isLoading"
                class="w-full bg-gradient-to-r from-red-600 to-orange-600 hover:from-red-700 hover:to-orange-700 disabled:from-red-400 disabled:to-orange-400 text-white font-bold py-3 rounded-xl shadow-md hover:shadow-lg transition-all duration-200 flex items-center justify-center gap-2 text-sm active:scale-[0.99] cursor-pointer"
              >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                </svg>
                <span>Track My Order</span>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                </svg>
              </button>

              <button
                @click="downloadReceipt"
                :disabled="isLoading"
                class="w-full bg-gradient-to-r from-emerald-600 via-teal-600 to-green-600 hover:from-emerald-700 hover:to-green-700 disabled:from-emerald-400 disabled:to-green-400 text-white font-bold py-2.5 rounded-xl shadow-md hover:shadow-lg transition-all duration-200 flex items-center justify-center gap-2 text-xs sm:text-sm active:scale-[0.99] cursor-pointer"
              >
                <svg v-if="!isLoading" class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                  <path d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z"/>
                </svg>
                <svg v-else class="w-4 h-4 animate-spin" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M4.293 5.293a1 1 0 011.414 0A7 7 0 0116.414 11a1 1 0 11-1.415 1.414A5 5 0 105.707 6.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                </svg>
                <span>{{ isLoading ? 'Generating Receipt...' : '💳 Download Official PDF Receipt' }}</span>
              </button>

              <button
                @click="goHome"
                :disabled="isLoading"
                class="w-full bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 font-semibold py-2 rounded-xl border border-slate-200 dark:border-slate-700 transition-all text-xs sm:text-sm active:scale-[0.99] cursor-pointer"
              >
                Back to Home
              </button>
            </div>

          </div>

        </div>

      </div>

      <!-- Compact Single-Line Footer -->
      <div class="mt-2.5 text-center text-[11px] text-slate-500 dark:text-slate-400">
        Thank you for choosing our hotel! Need assistance? Our 24/7 support team is here to help.
      </div>
    </div>

  </div>
</template>

<script setup lang="ts">
import { onMounted, onUnmounted, ref } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { generateAndDownloadReceipt } from '@/services/receiptService'

const router = useRouter()
const route = useRoute()

const txRef = ref<string>('')
const reservationData = ref<any>(null)
const orderData = ref<any>(null)
const isLoading = ref(false)
const isOrderPayment = ref(false)

const showHeader = ref(false)
const showSuccess = ref(false)
const showDetails = ref(false)
const showPayment = ref(false)
const showNextSteps = ref(false)
const showButtons = ref(false)

onMounted(async () => {
  txRef.value = route.query.tx_ref as string

  // Check if this is an order payment (from food ordering system)
  const orderIdFromQuery = route.query.order_id as string
  const orderIdFromStorage = localStorage.getItem('last_order_id')
  const pendingOrderData = localStorage.getItem('pending_order_data')
  const pendingPaymentOrder = localStorage.getItem('pending_payment_order')
  const orderPaymentData = localStorage.getItem('order_payment_data') || localStorage.getItem('walk_in_payment_data')
  
  const rawOrderStr = pendingOrderData || pendingPaymentOrder || orderPaymentData
  if (rawOrderStr) {
    try {
      orderData.value = JSON.parse(rawOrderStr)
    } catch (_) {}
  }

  if (orderIdFromQuery || orderIdFromStorage || pendingOrderData || pendingPaymentOrder || orderPaymentData) {
    isOrderPayment.value = true
    console.log('[PaymentSuccess] Detected as ORDER payment (food ordering system)')
    
    // Store order ID for tracking
    if (orderIdFromQuery) {
      localStorage.setItem('last_order_id', orderIdFromQuery)
      sessionStorage.setItem('payment_order_id', orderIdFromQuery)
    }
  } else {
    isOrderPayment.value = false
    console.log('[PaymentSuccess] Detected as RESERVATION payment (hotel booking system)')
  }

  let storedData = sessionStorage.getItem('reservationPaymentData')
  if (!storedData) {
    storedData = sessionStorage.getItem('booking_session')
  }
  
  if (storedData) {
    try {
      const parsed = JSON.parse(storedData)
      reservationData.value = parsed
      
      if (!txRef.value && parsed.tx_ref) {
        txRef.value = parsed.tx_ref
      }
    } catch (error) {
      console.error('[PaymentSuccess] Error parsing stored reservation data:', error)
    }
  }

  setTimeout(() => {
    showHeader.value = true
  }, 100)

  setTimeout(() => {
    showSuccess.value = true
    showDetails.value = true
    showPayment.value = true
    showNextSteps.value = true
    showButtons.value = true
  }, 250)

  if (txRef.value) {
    setTimeout(() => {
      completeReservationAndFetchDetails().catch((err) => {
        console.error('[PaymentSuccess] Error completing reservation:', err)
      })
    }, 800)
  }
})

async function completeReservationAndFetchDetails(): Promise<void> {
  try {
    isLoading.value = true
    
    await publicAxios.get(`/payments/verify/${txRef.value}`)

    const completeResponse = await publicAxios.post(`/reservation-payments/complete/${txRef.value}`)
    const completeData = completeResponse.data
      
    if (completeData?.success && completeData.reservation) {
      reservationData.value = {
        booking_reference: completeData.reservation.booking_reference || 'REF-' + txRef.value?.substring(0, 8).toUpperCase(),
        check_in_date: completeData.reservation.check_in_date,
        check_out_date: completeData.reservation.check_out_date,
        room_number: completeData.reservation.room_number || completeData.reservation.room?.room_number,
        room_id: completeData.reservation.room_id,
        number_of_guests: completeData.reservation.number_of_guests,
        first_name: completeData.reservation.first_name,
        last_name: completeData.reservation.last_name,
        email: completeData.reservation.email,
        phone: completeData.reservation.phone,
        special_requests: completeData.reservation.special_requests,
        total_amount: completeData.reservation.total_amount || completeData.payment?.amount,
      }
      return
    }

    await fetchReservationDetails()
  } catch (error) {
    console.error('[PaymentSuccess] Error completing reservation and fetching details:', error)
  } finally {
    isLoading.value = false
  }
}

async function fetchReservationDetails(): Promise<void> {
  try {
    isLoading.value = true

    const response = await publicAxios.get(`/reservation-payments/${txRef.value}`)
    const data = response.data

    if (data?.success && data.reservation) {
      reservationData.value = {
        ...reservationData.value,
        booking_reference: data.reservation.booking_reference || 'REF-' + txRef.value?.substring(0, 8).toUpperCase(),
        check_in_date: data.reservation.check_in_date,
        check_out_date: data.reservation.check_out_date,
        room_number: data.reservation.room_number || data.reservation.room?.room_number,
          room_id: data.reservation.room_id,
          number_of_guests: data.reservation.number_of_guests,
          first_name: data.reservation.first_name,
          last_name: data.reservation.last_name,
          email: data.reservation.email,
          phone: data.reservation.phone,
          special_requests: data.reservation.special_requests,
          total_amount: data.reservation.total_amount || data.payment?.amount,
        }
      }
    }
  } catch (error) {
    console.error('[PaymentSuccess] Error fetching reservation details:', error)
  } finally {
    isLoading.value = false
  }
}

function formatDate(dateString: string): string {
  if (!dateString) return 'N/A'
  return new Intl.DateTimeFormat('en-ET', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  }).format(new Date(dateString))
}

function goHome(): void {
  sessionStorage.removeItem('reservationPaymentData')
  router.push('/')
}

function trackOrder(): void {
  // Get order ID from multiple sources
  const orderId = route.query.order_id as string || 
                  localStorage.getItem('last_order_id') || 
                  sessionStorage.getItem('payment_order_id')
  
  if (orderId) {
    const qrToken = orderData.value?.qr_token || 
                    (route.query.qr_token as string) || 
                    localStorage.getItem('guest_qr_token')
    if (qrToken) {
      localStorage.setItem('guest_qr_token', qrToken)
    }
    const hotelId = orderData.value?.hotel_id || 
                    (route.query.hotel_id as string) || 
                    localStorage.getItem('hotel_id') || 
                    localStorage.getItem('active_hotel_id')
    
    console.log('[PaymentSuccess] Navigating to order status:', orderId)
    
    router.push({
      name: 'order-status',
      params: { orderId },
      query: {
        qr_token: qrToken || undefined,
        hotel_id: hotelId || undefined
      }
    })
  } else {
    console.error('[PaymentSuccess] No order ID found for tracking')
    alert('Unable to track order. Order ID not found.')
  }
}

async function downloadReceipt(): Promise<void> {
  if (!txRef.value) {
    alert('Error: Transaction reference not found. Please refresh the page.')
    return
  }

  if (!reservationData.value) {
    alert('Error: Reservation details not found. Please refresh the page and try again.')
    return
  }

  try {
    isLoading.value = true
    
    await generateAndDownloadReceipt({
      booking_reference: reservationData.value.booking_reference || 'REF-' + txRef.value?.substring(0, 8).toUpperCase(),
      first_name: reservationData.value.first_name || 'Guest',
      last_name: reservationData.value.last_name || '',
      email: reservationData.value.email || 'N/A',
      phone: reservationData.value.phone || 'N/A',
      check_in_date: reservationData.value.check_in_date,
      check_out_date: reservationData.value.check_out_date,
      room_number: reservationData.value.room_number || 'TBD',
      number_of_guests: reservationData.value.number_of_guests || 1,
      total_amount: reservationData.value.total_amount || 0,
      currency: 'ETB',
      status: 'Confirmed',
      tx_ref: txRef.value,
      payment_date: new Date().toISOString(),
      special_requests: reservationData.value.special_requests,
    })
  } catch (error: any) {
    console.error('[PaymentSuccess] Failed to generate receipt:', error)
    alert('Failed to generate receipt: ' + error.message)
  } finally {
    isLoading.value = false
  }
}
</script>

<style scoped>
/* Smooth scrolling */
html {
  scroll-behavior: smooth;
}
</style>
