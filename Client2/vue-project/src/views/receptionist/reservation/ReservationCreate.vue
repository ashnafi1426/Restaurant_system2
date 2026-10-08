<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'

import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import ReservationForm from '@/components/reservation/ReservationForm.vue'

import { useReservationStore } from '@/stores/reservationStore'
import { useGuestStore } from '@/stores/guestStore'
import { useRoomStore } from '@/stores/room'
import { useHotelStore } from '@/stores/hotelStore'
import { useLanguageStore } from '@/stores/language'

import type { Reservation } from '@/types/reservation'
import type { Guest } from '@/types/guest'
import type { Room } from '@/types/room'

const router = useRouter()
const reservationStore = useReservationStore()
const guestStore = useGuestStore()
const roomStore = useRoomStore()
const hotelStore = useHotelStore()
const languageStore = useLanguageStore()

const loading = ref(false)

let form = ref<Reservation>({
  id: '',
  booking_reference: '',
  guest_id: '',
  room_id: '',
  check_in_date: '',
  check_out_date: '',
  number_of_guests: 1,
  status: 'confirmed',
  special_requests: '',
})

const guests = ref<Guest[]>([])
const rooms = ref<Room[]>([])

const loadMeta = async () => {
  try {
    await guestStore.fetchGuests({ per_page: 1000 })
    guests.value = guestStore.guests

    await roomStore.fetchRooms({ per_page: 100 })
    rooms.value = roomStore.rooms
  } catch (error: any) {
    console.error('[ReservationCreate] Error loading meta:', error)
  }
}

const submit = async () => {
  loading.value = true

  try {
    await reservationStore.createReservation(form.value)

    router.push('/reservations')
  } catch (error) {
    console.error('[ReservationCreate] Error creating reservation:', error)
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  loadMeta()
})

watch(
  () => hotelStore.hotelId,
  () => {
    loadMeta()
  }
)
</script>

<template>
  <DashboardLayout>
    <div class="max-w-3xl mx-auto space-y-6 bg-white dark:bg-slate-900 p-6 rounded-lg">
      <!-- Header -->
      <div>
        <h1 class="text-2xl font-bold text-slate-800 dark:text-white">{{ languageStore.t('create_reservation', 'Create Reservation') }}</h1>
        <p class="text-slate-500 dark:text-slate-400 mt-1">{{ languageStore.t('create_reservation_desc', 'Book a room for a new or existing guest.') }}</p>
      </div>

      <!-- Loading State -->
      <div
        v-if="guests.length === 0 || rooms.length === 0 || guestStore.loading || roomStore.loading"
        class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-700/50 rounded-lg p-4"
      >
        <p v-if="guestStore.loading || roomStore.loading" class="text-yellow-800 dark:text-yellow-200">
          <span class="inline-block animate-spin mr-2">⏳</span> {{ languageStore.t('loading_data', 'Loading data...') }}
        </p>
        <p v-else-if="guests.length === 0" class="text-red-800 dark:text-red-300">
          <span class="font-semibold">{{ languageStore.t('no_guests', 'No Guests') }}</span> - {{ languageStore.t('create_guest_first', 'Create a guest first before making a reservation.') }}
        </p>
        <p v-else-if="roomStore.error" class="text-red-800 dark:text-red-300">
          <span class="font-semibold">{{ languageStore.t('error', 'Error') }}:</span> {{ roomStore.error }}
        </p>
        <p v-else-if="rooms.length === 0" class="text-red-800 dark:text-red-300">
          <span class="font-semibold">{{ languageStore.t('no_rooms', 'No Rooms') }}</span> - {{ languageStore.t('no_available_rooms', 'No available rooms found. Please contact the administrator.') }}
        </p>
      </div>

      <!-- Form -->
      <ReservationForm
        v-model="form"
        :guests="guests"
        :rooms="rooms"
        :loading="loading || reservationStore.loading"
        @submit="submit"
      />
    </div>
  </DashboardLayout>
</template>
