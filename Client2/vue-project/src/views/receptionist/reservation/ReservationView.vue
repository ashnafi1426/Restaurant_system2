<script setup lang="ts">
import { ref, onMounted, computed, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import { useReservationStore } from '@/stores/reservationStore'
import { useHotelStore } from '@/stores/hotelStore'
import { useLanguageStore } from '@/stores/language'
import reservationService from '@/services/reservationService'
import DeleteReservationDialog from '@/components/reservation/DeleteReservationDialog.vue'
import {
  ArrowLeft,
  Calendar,
  BedDouble,
  User,
  Mail,
  Phone,
  Globe,
  Clock,
  DollarSign,
  Building2,
  CheckCircle2,
  LogIn,
  LogOut,
  Edit,
  Trash2,
  Loader2,
  FileText,
  AlertCircle,
  Printer
} from 'lucide-vue-next'
import type { Reservation } from '@/types/reservation'

const route = useRoute()
const router = useRouter()
const store = useReservationStore()
const hotelStore = useHotelStore()
const languageStore = useLanguageStore()

const reservation = ref<Reservation | null>(null)
const loading = ref(true)
const actionLoading = ref(false)
const error = ref<string | null>(null)
const deleteDialog = ref(false)
const toastMessage = ref<string | null>(null)

const showToast = (msg: string) => {
  toastMessage.value = msg
  setTimeout(() => {
    toastMessage.value = null
  }, 3500)
}

const loadReservation = async () => {
  const currentId = String(route.params.id || '')
  if (!currentId || currentId === 'undefined') {
    error.value = 'Invalid reservation identifier.'
    loading.value = false
    return
  }
  loading.value = true
  error.value = null
  try {
    const data = await reservationService.getReservation(currentId)
    reservation.value = data?.data || data
  } catch (err: any) {
    console.error('[ReservationView] Error loading reservation:', err)
    error.value = err.response?.data?.message || 'Unable to load reservation details.'
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  loadReservation()
})

watch(() => route.params.id, (newId) => {
  if (newId) {
    loadReservation()
  }
})

watch(() => hotelStore.hotelId, () => {
  loadReservation()
})

const getStatusBadgeClass = (status?: string) => {
  switch (status?.toLowerCase()) {
    case 'confirmed':
      return 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-800'
    case 'pending':
      return 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-950/40 dark:text-amber-300 dark:border-amber-800'
    case 'checked_in':
      return 'bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-950/40 dark:text-blue-300 dark:border-blue-800'
    case 'checked_out':
      return 'bg-purple-50 text-purple-700 border-purple-200 dark:bg-purple-950/40 dark:text-purple-300 dark:border-purple-800'
    case 'cancelled':
      return 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-950/40 dark:text-rose-300 dark:border-rose-800'
    default:
      return 'bg-slate-50 text-slate-700 border-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700'
  }
}

const formatCurrency = (amount: number | string | undefined) => {
  const num = Number(amount) || 0
  return `${num.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} ${hotelStore.currency || 'ETB'}`
}

const formatDate = (dateString?: string) => {
  if (!dateString) return 'N/A'
  try {
    const d = new Date(dateString)
    return d.toLocaleDateString('en-US', {
      year: 'numeric',
      month: 'short',
      day: 'numeric',
    })
  } catch {
    return dateString
  }
}

const confirmReservation = async () => {
  if (!reservation.value) return
  actionLoading.value = true
  try {
    await store.confirmReservation(reservation.value.id)
    showToast('Reservation confirmed successfully!')
    await loadReservation()
  } catch (err: any) {
    showToast(err.response?.data?.message || 'Failed to confirm reservation')
  } finally {
    actionLoading.value = false
  }
}

const checkIn = async () => {
  if (!reservation.value) return
  actionLoading.value = true
  try {
    await store.checkInReservation(reservation.value.id)
    showToast('Guest checked in successfully!')
    await loadReservation()
  } catch (err: any) {
    showToast(err.response?.data?.message || 'Failed to check in guest')
  } finally {
    actionLoading.value = false
  }
}

const checkOut = async () => {
  if (!reservation.value) return
  actionLoading.value = true
  try {
    await store.checkOutReservation(reservation.value.id)
    showToast('Guest checked out successfully!')
    await loadReservation()
  } catch (err: any) {
    showToast(err.response?.data?.message || 'Failed to check out guest')
  } finally {
    actionLoading.value = false
  }
}

const deleteReservation = async () => {
  if (!reservation.value) return
  try {
    await store.deleteReservation(reservation.value.id)
    router.push('/reservations')
  } catch (err: any) {
    showToast(err.response?.data?.message || 'Failed to delete reservation')
  }
}

const printDetails = () => {
  window.print()
}
</script>

<template>
  <DashboardLayout>
    <div class="max-w-5xl mx-auto pb-16">
      <!-- Toast message -->
      <div
        v-if="toastMessage"
        class="fixed top-20 right-6 z-50 bg-slate-900 text-white text-xs px-4 py-3 rounded-xl shadow-xl flex items-center gap-2 animate-fadeIn"
      >
        <CheckCircle2 class="w-4 h-4 text-emerald-400 flex-shrink-0" />
        <span>{{ toastMessage }}</span>
      </div>

      <!-- Top Back Header -->
      <div class="mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex items-center gap-3">
          <button
            @click="router.push('/reservations')"
            class="p-2 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition cursor-pointer"
            title="Back to Reservations"
          >
            <ArrowLeft class="w-5 h-5" />
          </button>
          <div>
            <div class="flex items-center gap-2">
              <h1 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white">
                Reservation Details
              </h1>
              <span
                v-if="reservation?.booking_reference"
                class="px-2.5 py-0.5 rounded-full text-xs font-mono font-bold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700"
              >
                #{{ reservation.booking_reference }}
              </span>
            </div>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 flex items-center gap-1.5">
              <Building2 class="w-3.5 h-3.5 text-blue-600 dark:text-blue-400" />
              <span>{{ hotelStore.hotelName }}</span>
            </p>
          </div>
        </div>

        <!-- Action Buttons -->
        <div v-if="reservation" class="flex flex-wrap items-center gap-2">
          <!-- Confirm Button -->
          <button
            v-if="reservation.status === 'pending'"
            @click="confirmReservation"
            :disabled="actionLoading"
            class="px-3.5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold transition flex items-center gap-1.5 shadow-sm cursor-pointer disabled:opacity-50"
          >
            <CheckCircle2 class="w-4 h-4" />
            <span>Confirm Booking</span>
          </button>

          <!-- Check In Button -->
          <button
            v-if="reservation.status === 'confirmed'"
            @click="checkIn"
            :disabled="actionLoading"
            class="px-3.5 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold transition flex items-center gap-1.5 shadow-sm cursor-pointer disabled:opacity-50"
          >
            <LogIn class="w-4 h-4" />
            <span>Check In Guest</span>
          </button>

          <!-- Check Out Button -->
          <button
            v-if="reservation.status === 'checked_in'"
            @click="checkOut"
            :disabled="actionLoading"
            class="px-3.5 py-2 rounded-xl bg-purple-600 hover:bg-purple-700 text-white text-xs font-bold transition flex items-center gap-1.5 shadow-sm cursor-pointer disabled:opacity-50"
          >
            <LogOut class="w-4 h-4" />
            <span>Check Out</span>
          </button>

          <!-- Edit Button -->
          <button
            @click="router.push(`/reservations/${reservation.id}/edit`)"
            class="px-3.5 py-2 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-200 text-xs font-bold transition flex items-center gap-1.5 cursor-pointer"
          >
            <Edit class="w-3.5 h-3.5 text-amber-500" />
            <span>Edit</span>
          </button>

          <!-- Print Button -->
          <button
            @click="printDetails"
            class="px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-200 text-xs font-bold transition flex items-center gap-1.5 cursor-pointer"
            title="Print Summary"
          >
            <Printer class="w-3.5 h-3.5" />
          </button>

          <!-- Delete Button -->
          <button
            @click="deleteDialog = true"
            class="px-3 py-2 rounded-xl border border-red-200 dark:border-red-900/50 bg-red-50/50 dark:bg-red-950/30 hover:bg-red-100 text-red-600 dark:text-red-400 text-xs font-bold transition flex items-center gap-1.5 cursor-pointer"
            title="Cancel or Delete"
          >
            <Trash2 class="w-3.5 h-3.5" />
          </button>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="py-24 text-center flex flex-col items-center justify-center gap-3">
        <Loader2 class="w-10 h-10 text-blue-600 dark:text-blue-400 animate-spin" />
        <span class="text-sm font-bold text-slate-600 dark:text-slate-400">Loading reservation details...</span>
      </div>

      <!-- Error State -->
      <div
        v-else-if="error || !reservation"
        class="p-6 bg-red-50 dark:bg-red-950/30 border border-red-200 dark:border-red-800 rounded-2xl text-center space-y-3"
      >
        <AlertCircle class="w-10 h-10 text-red-600 dark:text-red-400 mx-auto" />
        <h2 class="text-base font-bold text-red-800 dark:text-red-300">{{ error || 'Reservation not found' }}</h2>
        <button
          @click="router.push('/reservations')"
          class="px-4 py-2 bg-slate-900 text-white text-xs font-bold rounded-xl cursor-pointer"
        >
          Return to Reservations
        </button>
      </div>

      <!-- Detail Cards Grid -->
      <div v-else class="space-y-6">
        <!-- Status & Highlight Banner -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4 shadow-xs">
          <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-2xl bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 flex items-center justify-center font-black text-lg">
              <Calendar class="w-6 h-6" />
            </div>
            <div>
              <div class="flex items-center gap-2">
                <span class="text-xs text-slate-500">Booking Status:</span>
                <span
                  class="px-2.5 py-0.5 rounded-full text-xs font-extrabold border uppercase tracking-wider"
                  :class="getStatusBadgeClass(reservation.status)"
                >
                  {{ (reservation.status || 'pending').replace('_', ' ') }}
                </span>
              </div>
              <p class="text-sm font-bold text-slate-900 dark:text-white mt-0.5">
                {{ formatDate(reservation.check_in_date) }} → {{ formatDate(reservation.check_out_date) }}
                <span class="text-xs font-normal text-slate-500">({{ reservation.total_nights || 1 }} night{{ (reservation.total_nights || 1) > 1 ? 's' : '' }})</span>
              </p>
            </div>
          </div>

          <div class="text-right">
            <p class="text-xs text-slate-500">Total Price</p>
            <p class="text-2xl font-black text-blue-600 dark:text-blue-400">
              {{ formatCurrency(reservation.total_price || reservation.total_amount || 0) }}
            </p>
            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">
              Payment: {{ reservation.payment_status || 'Pending' }}
            </span>
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <!-- Guest Information Card -->
          <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-xs space-y-4">
            <div class="flex items-center gap-2 pb-3 border-b border-slate-100 dark:border-slate-800">
              <User class="w-4 h-4 text-blue-600 dark:text-blue-400" />
              <h2 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider">Guest Information</h2>
            </div>

            <div class="space-y-3 text-xs">
              <div class="flex items-center justify-between">
                <span class="text-slate-500">Full Name</span>
                <span class="font-bold text-slate-900 dark:text-white text-sm">
                  {{ reservation.guest?.first_name || '' }} {{ reservation.guest?.last_name || 'Guest' }}
                </span>
              </div>
              <div class="flex items-center justify-between">
                <span class="text-slate-500 flex items-center gap-1">
                  <Mail class="w-3.5 h-3.5 text-slate-400" /> Email
                </span>
                <span class="font-medium text-slate-800 dark:text-slate-200">
                  {{ reservation.guest?.email || 'N/A' }}
                </span>
              </div>
              <div class="flex items-center justify-between">
                <span class="text-slate-500 flex items-center gap-1">
                  <Phone class="w-3.5 h-3.5 text-slate-400" /> Phone
                </span>
                <span class="font-medium text-slate-800 dark:text-slate-200">
                  {{ reservation.guest?.phone || 'N/A' }}
                </span>
              </div>
              <div class="flex items-center justify-between">
                <span class="text-slate-500 flex items-center gap-1">
                  <Globe class="w-3.5 h-3.5 text-slate-400" /> Nationality
                </span>
                <span class="font-medium text-slate-800 dark:text-slate-200">
                  {{ reservation.guest?.nationality || 'N/A' }}
                </span>
              </div>
              <div class="flex items-center justify-between">
                <span class="text-slate-500">Guests Included</span>
                <span class="font-bold text-slate-800 dark:text-slate-200">
                  {{ reservation.number_of_guests || 1 }} Guest(s)
                </span>
              </div>
            </div>
          </div>

          <!-- Room & Stay Details Card -->
          <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-xs space-y-4">
            <div class="flex items-center gap-2 pb-3 border-b border-slate-100 dark:border-slate-800">
              <BedDouble class="w-4 h-4 text-blue-600 dark:text-blue-400" />
              <h2 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider">Room & Accommodation</h2>
            </div>

            <div class="space-y-3 text-xs">
              <div class="flex items-center justify-between">
                <span class="text-slate-500">Assigned Room</span>
                <span class="font-extrabold text-blue-600 dark:text-blue-400 text-sm">
                  Room #{{ reservation.room?.room_number || 'TBD' }}
                </span>
              </div>
              <div class="flex items-center justify-between">
                <span class="text-slate-500">Room Type</span>
                <span class="font-bold text-slate-800 dark:text-slate-200">
                  {{ (typeof reservation.room?.room_type === 'string' ? reservation.room?.room_type : reservation.room?.room_type?.name) || (typeof reservation.room?.roomType === 'string' ? reservation.room?.roomType : reservation.room?.roomType?.name) || 'Standard' }}
                </span>
              </div>
              <div class="flex items-center justify-between">
                <span class="text-slate-500">Floor</span>
                <span class="font-medium text-slate-800 dark:text-slate-200">
                  {{ reservation.room?.floor ? `Floor ${reservation.room.floor}` : 'N/A' }}
                </span>
              </div>
              <div class="flex items-center justify-between">
                <span class="text-slate-500">Check-in Date</span>
                <span class="font-medium text-slate-800 dark:text-slate-200">
                  {{ formatDate(reservation.check_in_date) }}
                </span>
              </div>
              <div class="flex items-center justify-between">
                <span class="text-slate-500">Check-out Date</span>
                <span class="font-medium text-slate-800 dark:text-slate-200">
                  {{ formatDate(reservation.check_out_date) }}
                </span>
              </div>
            </div>
          </div>
        </div>

        <!-- Special Requests Card -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-xs space-y-3">
          <div class="flex items-center gap-2 pb-2 border-b border-slate-100 dark:border-slate-800">
            <FileText class="w-4 h-4 text-blue-600 dark:text-blue-400" />
            <h2 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider">Special Requests & Notes</h2>
          </div>
          <p class="text-xs text-slate-600 dark:text-slate-300 leading-relaxed bg-slate-50 dark:bg-slate-800/40 p-3.5 rounded-xl border border-slate-100 dark:border-slate-800">
            {{ reservation.special_requests || 'No special requests provided for this booking.' }}
          </p>
        </div>

        <!-- Metadata Card -->
        <div class="bg-slate-50 dark:bg-slate-900/40 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 flex flex-wrap items-center justify-between gap-3 text-[11px] text-slate-400">
          <span>Created on: <strong class="text-slate-600 dark:text-slate-300">{{ reservation.created_at || 'Recently' }}</strong></span>
          <span v-if="reservation.created_by">Created by: <strong class="text-slate-600 dark:text-slate-300">{{ typeof reservation.created_by === 'object' ? reservation.created_by?.name : reservation.created_by }}</strong></span>
          <span>System ID: <code class="text-slate-500 font-mono">{{ reservation.id }}</code></span>
        </div>
      </div>
    </div>

    <!-- Confirm Delete Modal -->
    <DeleteReservationDialog
      v-model="deleteDialog"
      :reservation="reservation"
      :loading="store.loading"
      @confirm="deleteReservation"
    />
  </DashboardLayout>
</template>
