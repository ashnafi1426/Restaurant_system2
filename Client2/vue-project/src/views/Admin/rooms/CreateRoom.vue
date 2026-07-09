<script setup lang="ts">
import { useRouter } from 'vue-router'

import DashboardLayout from '../../../layouts/DashboardLayout.vue'
import RoomForm from '../../../components/rooms/RoomForm.vue'

import { useRoomStore } from '../../../stores/room'

import type { Room } from '../../../types/room'

const router = useRouter()

const roomStore = useRoomStore()

const saveRoom = async (room: Room) => {
  try {
    console.log('Sending room data:', room)

    await roomStore.createRoom(room)

    alert('Room created successfully.')

    router.push('/rooms')
  } catch (error: any) {
    console.error('Room creation error:', error)
    console.error('Response data:', error.response?.data)
    console.error('Validation errors:', error.response?.data?.errors)

    // Show detailed error message
    const errorMsg = error.response?.data?.message || 'Unable to create room.'
    const validationErrors = error.response?.data?.errors

    if (validationErrors) {
      const errors = Object.values(validationErrors).flat().join('\n')
      alert(`Error: ${errorMsg}\n\n${errors}`)
    } else {
      alert(errorMsg)
    }
  }
}

const cancel = () => {
  router.push('/rooms')
}
</script>

<template>
  <DashboardLayout>
    <div class="max-w-5xl mx-auto">
      <!-- Header -->

      <div class="mb-8">
        <h1 class="text-3xl font-bold text-slate-800">Create Room</h1>
        <p class="text-slate-500 mt-2">Add a new hotel room.</p>
      </div>

      <!-- Card -->

      <div class="bg-white rounded-xl shadow border border-slate-200">
        <div class="px-6 py-5 border-b">
          <h2 class="text-xl font-semibold">Room Information</h2>
        </div>

        <div class="p-6">
          <RoomForm @submit="saveRoom" />
        </div>
      </div>

      <!-- Footer Buttons -->

      <div class="flex justify-end gap-4 mt-6">
        <button @click="cancel" class="px-6 py-2 border rounded-lg hover:bg-gray-100">
          Cancel
        </button>
      </div>
    </div>
  </DashboardLayout>
</template>
