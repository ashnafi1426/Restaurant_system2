<script setup lang="ts">
import { onMounted } from 'vue'
import { useRouter } from 'vue-router'

import DashboardLayout from '../../../layouts/DashboardLayout.vue'
import RoomTable from '../../../components/rooms/RoomTable.vue'
import DeleteRoomModal from '../../../components/rooms/DeleteRoomModal.vue'

import { useRoomStore } from '../../../stores/room'
import { BedDouble, Plus } from 'lucide-vue-next'
import { ref } from 'vue'

const router = useRouter()
const roomStore = useRoomStore()

const showDeleteModal = ref(false)
const selectedRoomId = ref<string | null>(null)

onMounted(async () => {
  try {
    await roomStore.fetchRooms()
  } catch (error) {
    console.error('Error fetching rooms:', error)
  }
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
            <h1 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white">Room Management</h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Manage, filter, and track all hotel rooms and live occupancy.</p>
          </div>
        </div>

        <button
          @click="createRoom"
          class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2.5 rounded-xl font-bold text-xs sm:text-sm shadow-md shadow-blue-600/20 transition cursor-pointer inline-flex items-center justify-center gap-2"
        >
          <Plus class="w-4 h-4 stroke-[3]" />
          <span>Add Room</span>
        </button>
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
