<script setup lang="ts">
import { useRouter } from 'vue-router'
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import RoomForm from '../../../components/rooms/RoomForm.vue'
import { useRoomStore } from '../../../stores/room'
import { useHotelStore } from '@/stores/hotelStore'
import { useLanguageStore } from '@/stores/language'
import { Building2 } from 'lucide-vue-next'
import type { Room } from '../../../types/room'

const router = useRouter()
const roomStore = useRoomStore()
const hotelStore = useHotelStore()
const languageStore = useLanguageStore()

const saveRoom = async (room: Room) => {
  try {
    if (hotelStore.hotelId && !room.hotel_id) {
      room.hotel_id = hotelStore.hotelId
    }
    await roomStore.createRoom(room)
    alert(languageStore.t('room_created_success', 'Room created successfully.'))
    router.push('/rooms')
  } catch (error: any) {
    console.error('[CreateRoom] Error creating room:', error)
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
      <div class="mb-8">
        <div class="flex items-center gap-2">
          <h1 class="text-3xl font-bold text-slate-800">{{ languageStore.t('create_room', 'Create Room') }}</h1>
          <span v-if="hotelStore.hotelName" class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300 border border-blue-200 dark:border-blue-700/50">
            <Building2 class="w-3 h-3" />
            {{ hotelStore.hotelName }}
          </span>
        </div>
        <p class="text-slate-500 mt-2">{{ languageStore.t('add_new_hotel_room', 'Add a new hotel room.') }}</p>
      </div>

      <div class="bg-white rounded-xl shadow border border-slate-200">
        <div class="px-6 py-5 border-b">
          <h2 class="text-xl font-semibold">{{ languageStore.t('room_information', 'Room Information') }}</h2>
        </div>

        <div class="p-6">
          <RoomForm @submit="saveRoom" @cancel="cancel" />
        </div>
      </div>

      <div class="flex justify-end gap-4 mt-6">
        <button @click="cancel" class="px-6 py-2 border rounded-lg hover:bg-gray-100 cursor-pointer">
          {{ languageStore.t('cancel', 'Cancel') }}
        </button>
      </div>
    </div>
  </DashboardLayout>
</template>
