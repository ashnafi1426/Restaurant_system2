<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'

import DashboardLayout from '@/layouts/DashboardLayout.vue'
import ReservationForm from '@/components/reservation/ReservationForm.vue'

import { useReservationStore } from '@/stores/reservationStore'
import { useGuestStore } from '@/stores/guestStore'
import { useRoomStore } from '@/stores/room'

import type { Reservation } from '@/types/reservation'
import type { Guest } from '@/types/guest'
import type { Room } from '@/types/room'

const router = useRouter()
const reservationStore = useReservationStore()
const guestStore = useGuestStore()
const roomStore = useRoomStore()

const loading = ref(false)

const form = ref<Reservation>({
  id: '',
  booking_reference: '',
  guest_id: '',
  room_id: '',
  check_in_date: '',
  check_out_date: '',
  number_of_guests: 1,
  status: 'pending',
  special_requests: '',
})

const guests = ref<Guest[]>([])
const rooms = ref<Room[]>([])

const loadMeta = async () => {
  try {
    await guestStore.fetchGuests({ per_page: 1000 })
    guests.value = guestStore.guests

    await roomStore.fetchRooms()
    rooms.value = roomStore.rooms
  } catch (error: any) {}
}

const submit = async () => {
  loading.value = true

  try {
    await reservationStore.createReservation(form.value)

    router.push('/reservations')
  } catch (error) {
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  loadMeta()
})
</script>

<template>
  <DashboardLayout>
    <div class="max-w-3xl mx-auto space-y-6">
      <!-- Header -->
      <div>
        <h1 class="text-2xl font-bold text-slate-800">Create Reservation</h1>
        <p class="text-slate-500 mt-1">Book a new room reservation</p>
      </div>

      <!-- Loading State -->
      <div
        v-if="guests.length === 0 || rooms.length === 0 || guestStore.loading || roomStore.loading"
        class="bg-yellow-50 border border-yellow-200 rounded-lg p-4"
      >
        <p v-if="guestStore.loading || roomStore.loading" class="text-yellow-800">
          <span class="inline-block animate-spin mr-2">⏳</span> Loading data...
        </p>
        <p v-else-if="guests.length === 0" class="text-red-800">
          <span class="font-semibold"> No Guests</span> - Create a guest first before making a
          reservation.
        </p>
        <p v-else-if="roomStore.error" class="text-red-800">
          <span class="font-semibold"> Error:</span> {{ roomStore.error }}
        </p>
        <p v-else-if="rooms.length === 0" class="text-red-800">
          <span class="font-semibold"> No Rooms</span> - No available rooms found. Please contact
          the administrator.
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
