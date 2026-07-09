<script setup lang="ts">
import { onMounted, reactive } from 'vue'
import { useRoute, useRouter } from 'vue-router'

import DashboardLayout from '../../../layouts/DashboardLayout.vue'

import { roomService } from '../../../services/roomService'

import type { Room } from '../../../types/room'

console.log(' [EDITROOM] Component setup starting')

const router = useRouter()
const route = useRoute()

console.log(' [EDITROOM] route.params:', route.params)
console.log(' [EDITROOM] route.params.id:', route.params.id)
console.log(' [EDITROOM] typeof route.params.id:', typeof route.params.id)

const roomId = String(route.params.id)

console.log(' [EDITROOM] roomId after String():', roomId)

const loading = reactive({
  page: true,
  saving: false,
})

const form = reactive<Room>({
  id: roomId,
  room_number: '',
  room_type_id: 1,
  room_type: {
    id: '',
    name: '',
    capacity: 0,
    base_price_per_night: 0,
  },
  floor: 1,
  description: '',
  status: 'available',
  is_active: true,
})

const loadRoom = async () => {
  try {
    const response = await roomService.getRoom(roomId)
    console.log('📡 Room response:', response)
    const roomData = response.data.data || response.data
    console.log('Room data:', roomData)
    Object.assign(form, roomData)
  } catch (error: any) {
    console.error('Error loading room:', error)
    alert('Unable to load room. The room may not exist.')
    router.push('/rooms')
  } finally {
    loading.page = false
  }
}

const updateRoom = async () => {
  loading.saving = true

  try {
    await roomService.updateRoom(roomId, form)
    alert('Room updated successfully.')
    router.push('/rooms')
  } catch (error: any) {
    console.error('Error updating room:', error)
    alert('Unable to update room.')
  } finally {
    loading.saving = false
  }
}

onMounted(() => {
  loadRoom()
})
</script>

<template>
  <DashboardLayout>
    <div class="max-w-5xl mx-auto">
      <!-- Page Header -->

      <div class="mb-8">
        <h1 class="text-3xl font-bold text-slate-800">Edit Room</h1>

        <p class="text-slate-500 mt-2">Update room information.</p>
      </div>

      <div v-if="loading.page" class="bg-white rounded-xl shadow p-10 text-center">
        Loading room...
      </div>

      <div v-else class="bg-white rounded-xl shadow border">
        <div class="border-b px-6 py-4">
          <h2 class="text-xl font-semibold">Room Details</h2>
        </div>

        <form @submit.prevent="updateRoom" class="p-6 space-y-6">
          <div class="grid md:grid-cols-2 gap-6">
            <div>
              <label class="block mb-2"> Room Number </label>

              <input v-model="form.room_number" class="w-full border rounded-lg px-4 py-2" />
            </div>

            <div>
              <label class="block mb-2"> Floor </label>

              <input
                v-model="form.floor"
                type="number"
                class="w-full border rounded-lg px-4 py-2"
              />
            </div>

            <div>
              <label class="block mb-2"> Capacity </label>

              <input
                v-model="form.capacity"
                type="number"
                class="w-full border rounded-lg px-4 py-2"
              />
            </div>

            <div>
              <label class="block mb-2"> Price </label>

              <input
                v-model="form.price"
                type="number"
                class="w-full border rounded-lg px-4 py-2"
              />
            </div>

            <div>
              <label class="block mb-2"> Room Type </label>

              <select v-model="form.room_type_id" class="w-full border rounded-lg px-4 py-2">
                <option :value="1">Standard</option>

                <option :value="2">Deluxe</option>

                <option :value="3">Suite</option>
              </select>
            </div>

            <div>
              <label class="block mb-2"> Status </label>

              <select v-model="form.status" class="w-full border rounded-lg px-4 py-2">
                <option value="available">Available</option>

                <option value="occupied">Occupied</option>

                <option value="reserved">Reserved</option>

                <option value="maintenance">Maintenance</option>
              </select>
            </div>
          </div>

          <div>
            <label class="block mb-2"> Description </label>

            <textarea
              v-model="form.description"
              rows="4"
              class="w-full border rounded-lg px-4 py-2"
            />
          </div>

          <div class="flex justify-end gap-3">
            <button
              type="button"
              @click="router.push('/rooms')"
              class="px-6 py-2 border rounded-lg"
            >
              Cancel
            </button>

            <button
              type="submit"
              :disabled="loading.saving"
              class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg"
            >
              {{ loading.saving ? 'Updating...' : 'Update Room' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </DashboardLayout>
</template>
