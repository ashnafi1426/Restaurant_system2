<script setup lang="ts">
import { onMounted, watch, ref } from 'vue'
import { useRouter } from 'vue-router'

import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import RoomTable from '../../../components/rooms/RoomTable.vue'
import DeleteRoomModal from '../../../components/rooms/DeleteRoomModal.vue'

import { useRoomStore } from '../../../stores/room'
import { useHotelStore } from '../../../stores/hotelStore'
import { useLanguageStore } from '@/stores/language'
import { BedDouble, Plus, Building2 } from 'lucide-vue-next'

const router = useRouter()
const roomStore = useRoomStore()
const hotelStore = useHotelStore()
const languageStore = useLanguageStore()

const showDeleteModal = ref(false)
const selectedRoomId = ref<string | null>(null)

const loadData = async () => {
  try {
    await roomStore.fetchRooms()
  } catch (error) {
    console.error('Error fetching rooms:', error)
  }
}

onMounted(loadData)

watch(() => hotelStore.hotelId, () => {
  loadData()
})

const createRoom = () => {
  router.push('/rooms/create')
}

const viewRoom = (room: any) => {
  router.push(`/rooms/${room.id}`)
}

const editRoom = (room: any) => {
  router.push(`/rooms/${room.id}/edit`)
}

const openDeleteModal = (room: any) => {
  selectedRoomId.value = String(room.id)
  showDeleteModal.value = true
}

const deleteRoom = async () => {
  if (selectedRoomId.value) {
    await roomStore.deleteRoom(selectedRoomId.value)
  }
  showDeleteModal.value = false
}

const refresh = async () => {
  await roomStore.fetchRooms()
}
</script>

<template>
  <DashboardLayout>
    <div class="space-y-6 bg-slate-50 dark:bg-slate-950 min-h-screen p-4 sm:p-6 max-w-full overflow-hidden font-sans">
      <!-- Header -->
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-5 sm:p-6 rounded-2xl shadow-xs">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-600 to-blue-700 flex items-center justify-center shadow-md flex-shrink-0 text-white">
            <BedDouble class="w-5 h-5 stroke-[2.2]" />
          </div>
          <div>
            <div class="flex items-center gap-2">
              <h1 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white">
                {{ languageStore.t('room_management', 'Room Management') }}
              </h1>
              <span v-if="hotelStore.hotelName" class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300 border border-blue-200 dark:border-blue-700/50">
                <Building2 class="w-3 h-3" />
                {{ hotelStore.hotelName }}
              </span>
            </div>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
              {{ languageStore.t('room_management_desc', 'Manage, filter, and track all hotel rooms and live occupancy.') }}
            </p>
          </div>
        </div>


      </div>

      <div
        v-if="roomStore.error"
        class="p-4 bg-red-50 border border-red-200 rounded-xl text-red-700 text-xs font-semibold"
      >
        {{ roomStore.error }}
      </div>

      <!-- TABLE COMPONENT WITH INTEGRATED TOOLBAR & FILTER -->
      <RoomTable
        :rooms="roomStore.rooms || []"
        :loading="roomStore.loading"
        @view="viewRoom"
        @edit="editRoom"
        @delete="openDeleteModal"
        @create="createRoom"
        @refresh="refresh"
      />

      <DeleteRoomModal
        :open="showDeleteModal"
        @close="showDeleteModal = false"
        @delete="deleteRoom"
      />
    </div>
  </DashboardLayout>
</template>

