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
const isDeleting = ref(false)
const deleteErrorMessage = ref<string | null>(null)
const canForceDelete = ref(false)

// Track current filters for server-side filtering
const currentFilters = ref<Record<string, any>>({
  page: 1,
  per_page: 25,
})

const loadData = async (filters?: Record<string, any>) => {
  try {
    const filtersToUse = filters || currentFilters.value
    currentFilters.value = filtersToUse
    await roomStore.fetchRooms(filtersToUse)
  } catch (error) {
    console.error('Error fetching rooms:', error)
  }
}

onMounted(() => {
  loadData()
})

// Watch hotel changes and reload with current filters
watch(() => hotelStore.hotelId, () => {
  // Reset to page 1 when hotel changes
  currentFilters.value = { page: 1, per_page: currentFilters.value.per_page || 25 }
  loadData()
})

const createRoom = () => {
  router.push('/admin/rooms/create')
}

const viewRoom = (room: any) => {
  router.push(`/admin/rooms/${room.id}`)
}

const editRoom = (room: any) => {
  router.push(`/admin/rooms/${room.id}/edit`)
}

const openDeleteModal = (room: any) => {
  selectedRoomId.value = String(room.id)
  deleteErrorMessage.value = null
  canForceDelete.value = false
  showDeleteModal.value = true
}

const deleteRoom = async () => {
  if (!selectedRoomId.value) return
  isDeleting.value = true
  deleteErrorMessage.value = null
  try {
    await roomStore.deleteRoom(selectedRoomId.value, false)
    showDeleteModal.value = false
    selectedRoomId.value = null
    // Reload current page after delete
    await loadData()
  } catch (err: any) {
    const errorData = err.response?.data
    deleteErrorMessage.value = errorData?.message || err.message || 'Unable to delete room'
    canForceDelete.value = Boolean(errorData?.can_force)
  } finally {
    isDeleting.value = false
  }
}

const forceDeleteRoom = async () => {
  if (!selectedRoomId.value) return
  isDeleting.value = true
  deleteErrorMessage.value = null
  try {
    await roomStore.deleteRoom(selectedRoomId.value, true)
    showDeleteModal.value = false
    selectedRoomId.value = null
    // Reload current page after delete
    await loadData()
  } catch (err: any) {
    const errorData = err.response?.data
    deleteErrorMessage.value = errorData?.message || err.message || 'Force delete failed'
  } finally {
    isDeleting.value = false
  }
}

const deactivateRoom = async () => {
  if (!selectedRoomId.value) return
  isDeleting.value = true
  try {
    await roomStore.toggleStatus(selectedRoomId.value)
    showDeleteModal.value = false
    selectedRoomId.value = null
    // Reload current page after deactivation
    await loadData()
  } catch (err: any) {
    const errorData = err.response?.data
    deleteErrorMessage.value = errorData?.message || err.message || 'Failed to deactivate room'
  } finally {
    isDeleting.value = false
  }
}

const refresh = async () => {
  await loadData()
}

// Handle filter changes from RoomTable
const handleFilterChange = (filters: Record<string, any>) => {
  loadData(filters)
}

// Handle page changes from RoomTable
const handlePageChange = (page: number) => {
  loadData({ ...currentFilters.value, page })
}

// Handle per_page changes from RoomTable
const handlePerPageChange = (perPage: number) => {
  loadData({ ...currentFilters.value, per_page: perPage, page: 1 })
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
        :pagination="roomStore.pagination"
        @view="viewRoom"
        @edit="editRoom"
        @delete="openDeleteModal"
        @create="createRoom"
        @refresh="refresh"
        @filter-change="handleFilterChange"
        @page-change="handlePageChange"
        @per-page-change="handlePerPageChange"
      />

      <DeleteRoomModal
        :open="showDeleteModal"
        :is-deleting="isDeleting"
        :error-message="deleteErrorMessage"
        :can-force="canForceDelete"
        @close="showDeleteModal = false"
        @delete="deleteRoom"
        @force-delete="forceDeleteRoom"
        @deactivate="deactivateRoom"
      />
    </div>
  </DashboardLayout>
</template>
