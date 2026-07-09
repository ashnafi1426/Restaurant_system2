<script setup lang="ts">
import { reactive, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'

import DashboardLayout from '../../../layouts/DashboardLayout.vue'
import RoomStatusBadge from '../../../components/rooms/RoomStatusBadge.vue'

import { roomService } from '../../../services/roomService'
import type { Room } from '../../../types/room'

const route = useRoute()
const router = useRouter()

const roomId = String(route.params.id)

const loading = reactive({
  page: true,
})

const room = reactive<Room>({
  id: roomId,
  room_number: '',
  room_type_id: 1,
  floor: 1,
  description: '',
  status: 'available',
  is_active: true,
  room_type: {
    id: '',
    name: '',
    capacity: 0,
    base_price_per_night: 0,
  },
  created_at: '',
  updated_at: '',
})

const loadRoom = async () => {
  try {
    const response = await roomService.getRoom(roomId)
    console.log('📡 Room response:', response)
    console.log('response.data:', response.data)
    const roomData = response.data.data || response.data
    console.log('Room data to assign:', roomData)
    Object.assign(room, roomData)
  } catch (error: any) {
    console.error('Error loading room:', error)
    alert('Unable to load room. The room may not exist.')
    router.push('/rooms')
  } finally {
    loading.page = false
  }
}

onMounted(loadRoom)
</script>

<template>
  <DashboardLayout>
    <div class="max-w-7xl mx-auto">
      <!-- Header -->

      <div class="mb-8 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <div>
          <h1 class="text-3xl font-bold text-slate-800">Room Details</h1>

          <p class="mt-2 text-slate-500">View complete room information.</p>
        </div>

        <button
          @click="router.push(`/rooms/${roomId}/edit`)"
          class="rounded-lg bg-amber-500 px-6 py-3 font-medium text-white transition hover:bg-amber-600"
        >
          Edit Room
        </button>
      </div>

      <!-- Loading -->

      <div v-if="loading.page" class="rounded-xl border bg-white p-12 text-center shadow">
        <div class="text-lg text-slate-500">Loading room information...</div>
      </div>

      <!-- Content -->

      <div v-else class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow">
        <!-- Title -->

        <div class="border-b bg-slate-50 px-6 py-5">
          <h2 class="text-xl font-semibold">Room Information</h2>
        </div>

        <!-- Cards -->

        <div class="grid grid-cols-1 gap-6 p-6 md:grid-cols-2 lg:grid-cols-3">
          <!-- Room Number -->

          <div class="rounded-xl border p-5">
            <p class="text-sm text-slate-500">Room Number</p>

            <h3 class="mt-2 text-2xl font-semibold">
              {{ room.room_number }}
            </h3>
          </div>

          <!-- Room Type -->

          <div class="rounded-xl border p-5">
            <p class="text-sm text-slate-500">Room Type</p>

            <h3 class="mt-2 text-xl font-semibold">
              {{ room.room_type?.name }}
            </h3>
          </div>

          <!-- Floor -->

          <div class="rounded-xl border p-5">
            <p class="text-sm text-slate-500">Floor</p>

            <h3 class="mt-2 text-xl font-semibold">
              {{ room.floor }}
            </h3>
          </div>

          <!-- Capacity -->

          <div class="rounded-xl border p-5">
            <p class="text-sm text-slate-500">Capacity</p>

            <h3 class="mt-2 text-xl font-semibold">{{ room.room_type?.capacity }} Guests</h3>
          </div>

          <!-- Base Price -->

          <div class="rounded-xl border p-5">
            <p class="text-sm text-slate-500">Base Price</p>

            <h3 class="mt-2 text-2xl font-bold text-green-600">
              ${{ room.room_type?.base_price_per_night }}
            </h3>
          </div>

          <!-- Status -->

          <div class="rounded-xl border p-5">
            <p class="mb-3 text-sm text-slate-500">Status</p>

            <RoomStatusBadge :status="room.status" />
          </div>
        </div>

        <!-- Description -->

        <div class="border-t p-6">
          <h3 class="mb-3 text-lg font-semibold">Description</h3>

          <p class="leading-7 text-slate-600">
            {{ room.description || 'No description available.' }}
          </p>
        </div>

        <!-- Footer -->

        <div class="grid gap-6 border-t bg-slate-50 p-6 md:grid-cols-2">
          <div>
            <p class="text-sm text-slate-500">Created At</p>

            <p class="mt-2 font-medium">
              {{ room.created_at }}
            </p>
          </div>

          <div>
            <p class="text-sm text-slate-500">Updated At</p>

            <p class="mt-2 font-medium">
              {{ room.updated_at }}
            </p>
          </div>
        </div>
      </div>

      <!-- Back -->

      <div class="mt-8">
        <button
          @click="router.push('/rooms')"
          class="rounded-lg border border-slate-300 bg-white px-6 py-3 font-medium transition hover:bg-slate-100"
        >
          ← Back to Rooms
        </button>
      </div>
    </div>
  </DashboardLayout>
</template>
