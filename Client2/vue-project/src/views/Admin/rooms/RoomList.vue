<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'

import DashboardLayout from '../../../layouts/DashboardLayout.vue'

import RoomSearch from '../../../components/rooms/RoomSearch.vue'
import RoomTable from '../../../components/rooms/RoomTable.vue'
import DeleteRoomModal from '../../../components/rooms/DeleteRoomModal.vue'

import { useRoomStore } from '../../../stores/room'

const router = useRouter()
const roomStore = useRoomStore()

const search = ref('')
const showDeleteModal = ref(false)
const selectedRoomId = ref<string | null>(null)

console.log(' [ROOMLIST] Component mounted, about to fetch rooms')

onMounted(async () => {
  console.log(' [ROOMLIST] onMounted hook fired')
  console.log('[ROOMLIST] roomStore before fetch:', roomStore.rooms)

  try {
    console.log(' [ROOMLIST] Calling roomStore.fetchRooms()...')
    // Don't filter by status - get ALL rooms for admin view
    await roomStore.fetchRooms()
    console.log(' [ROOMLIST] fetchRooms() completed')
    console.log('[ROOMLIST] roomStore after fetch:', roomStore.rooms)
    console.log(' [ROOMLIST] Rooms count:', roomStore.rooms.length)
  } catch (error) {
    console.error(' [ROOMLIST] Error fetching rooms:', error)
  }
})

const filteredRooms = computed(() => {
  const roomList = Array.isArray(roomStore.rooms) ? roomStore.rooms : []
  console.log('[ROOMLIST] Computing filtered rooms from:', roomList.length, 'rooms')

  if (!search.value.trim()) {
    return roomList
  }

  let searchQuery = search.value.toLowerCase()

  // Remove "room" or "rm" prefix if user typed it
  if (searchQuery.startsWith('room ')) {
    searchQuery = searchQuery.replace('room ', '').trim()
  }
  if (searchQuery.startsWith('rm ')) {
    searchQuery = searchQuery.replace('rm ', '').trim()
  }

  return roomList.filter((room) => {
    if (!room) return false

    try {
      const roomNumber = room.room_number ? String(room.room_number).toLowerCase() : ''
      const roomType = room.room_type?.name ? String(room.room_type.name).toLowerCase() : ''
      const floor = room.floor ? String(room.floor).toLowerCase() : ''
      const status = room.status ? String(room.status).toLowerCase() : ''
      const description = room.description ? String(room.description).toLowerCase() : ''

      return (
        roomNumber.includes(searchQuery) ||
        roomType.includes(searchQuery) ||
        floor.includes(searchQuery) ||
        status.includes(searchQuery) ||
        description.includes(searchQuery)
      )
    } catch (e) {
      console.error('Error filtering room:', room, e)
      return false
    }
  })
})

const createRoom = () => {
  console.log('➕ [ROOMLIST] Creating new room')
  router.push('/rooms/create')
}

const viewRoom = (room: any) => {
  console.log('👁️ [ROOMLIST] View - Room object:', room)
  console.log('👁️ [ROOMLIST] View - Room ID:', room.id)
  router.push(`/rooms/${room.id}`)
}

const editRoom = (room: any) => {
  console.log('✏️ [ROOMLIST] Edit - Room object:', room)
  console.log('✏️ [ROOMLIST] Edit - Room ID:', room.id)
  console.log('✏️ [ROOMLIST] Navigating to:', `/rooms/${room.id}/edit`)
  router.push(`/rooms/${room.id}/edit`)
}

const openDeleteModal = (room: any) => {
  console.log('[ROOMLIST] Delete - Room object:', room)
  console.log('[ROOMLIST] Delete - Room ID:', room.id)
  selectedRoomId.value = String(room.id)
  showDeleteModal.value = true
}

const deleteRoom = async () => {
  if (selectedRoomId.value) {
    await roomStore.deleteRoom(selectedRoomId.value)
  }
  showDeleteModal.value = false
}
</script>

<template>
  <DashboardLayout>
    <div class="space-y-6 bg-slate-50 dark:bg-slate-950 min-h-screen p-4 sm:p-6 max-w-full overflow-hidden font-sans">
      <div class="flex justify-between items-center bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-6 rounded-2xl shadow-xs">
        <div>
          <h1 class="text-2xl font-black text-slate-900 dark:text-white">Room Management</h1>
          <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Manage, filter, and track all hotel rooms and availability.</p>
        </div>
        <button
          @click="createRoom"
          class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-xl font-bold text-xs shadow-md shadow-blue-600/20 transition cursor-pointer"
        >
          + Add Room
        </button>
      </div>

      <div
        v-if="roomStore.error"
        class="p-4 bg-red-50 border border-red-200 rounded-lg text-red-700"
      >
        {{ roomStore.error }}
      </div>

      <RoomSearch v-model="search" />

      <RoomTable
        :rooms="filteredRooms"
        :loading="roomStore.loading"
        @view="viewRoom"
        @edit="editRoom"
        @delete="openDeleteModal"
      />

      <DeleteRoomModal
        :open="showDeleteModal"
        @close="showDeleteModal = false"
        @delete="deleteRoom"
      />
    </div>
  </DashboardLayout>
</template>
