<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'

import DashboardLayout from '@/layouts/DashboardLayout.vue'
import ReservationForm from '@/components/reservation/ReservationForm.vue'

import { useReservationStore } from '@/stores/reservationStore'
import { useGuestStore } from '@/stores/guestStore'
import { useRoomStore } from '@/stores/room'

import type { Reservation } from '@/types/reservation'
import type { Guest } from '@/types/guest'
import type { Room } from '@/types/room'

const route = useRoute()
const router = useRouter()
const reservationStore = useReservationStore()
const guestStore = useGuestStore()
const roomStore = useRoomStore()

const loading = ref(false)
const loadingData = ref(true)

const form = ref<Reservation | null>(null)

const guests = ref<Guest[]>([])
const rooms = ref<Room[]>([])

const loadReservation = async () => {
  const id = route.params.id as string
  try {
    await reservationStore.fetchReservation(id)
    if (reservationStore.reservation) {
      form.value = { ...reservationStore.reservation }
    }
  } catch (error) {}
}

const loadMeta = async () => {
  try {
    await guestStore.fetchGuests({ per_page: 1000 })
    guests.value = guestStore.guests

    await roomStore.fetchRooms({ per_page: 100 }) // Request all rooms for complete search
    rooms.value = roomStore.rooms
  } catch (error) {}
}

const submit = async () => {
  if (!form.value) {
    return
  }

  loading.value = true

  try {
    await reservationStore.updateReservation(form.value.id, form.value)

    router.push('/reservations')
  } catch (error: any) {
  } finally {
    loading.value = false
  }
}

onMounted(async () => {
  loadingData.value = true

  try {
    await loadMeta()

    await loadReservation()
  } finally {
    loadingData.value = false
  }
})
</script>

<template>
  <DashboardLayout>
    <div class="max-w-3xl mx-auto space-y-6 bg-white dark:bg-slate-900 p-6 rounded-lg">
      <!-- Header -->
      <div>
        <h1 class="text-2xl font-bold text-slate-800 dark:text-white">Edit Reservation</h1>
        <p class="text-slate-500 dark:text-slate-400 mt-1">Update reservation details</p>
      </div>

      <!-- Loading State -->
      <div v-if="loadingData" class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-700/50 rounded-lg p-4">
        <p class="text-blue-800 dark:text-blue-200">⏳ Loading reservation data...</p>
      </div>

      <!-- Form -->
      <ReservationForm
        v-else-if="form"
        v-model="form"
        :guests="guests"
        :rooms="rooms"
        :loading="loading || reservationStore.loading"
        @submit="submit"
      />

      <!-- Error State -->
      <div v-else class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-700/50 rounded-lg p-4">
        <p class="text-red-800 dark:text-red-200">Failed to load reservation</p>
        <button
          @click="router.push('/reservations')"
          class="mt-3 px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded transition"
        >
          Back to Reservations
        </button>
      </div>
    </div>
  </DashboardLayout>
</template>
