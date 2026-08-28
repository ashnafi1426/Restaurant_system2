<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'

import DashboardLayout from '../../../layouts/DashboardLayout.vue'
import { useRoomTypeStore } from '../../../stores/roomType'

import RoomTypeTable from '../../../components/room-types/RoomTypeTable.vue'
import ConfirmDeleteModal from '../../../components/room-types/DeleteRoomTypeModal.vue'
import { BedDouble, Plus } from 'lucide-vue-next'

import type { RoomType } from '../../../types/roomType'

const router = useRouter()
const store = useRoomTypeStore()

const deleteModalOpen = ref(false)
const selectedId = ref<number | null>(null)

onMounted(() => {
  store.fetchRoomTypes()
})

const view = (rt: RoomType) => {
  router.push(`/room-types/${rt.id}`)
}

const edit = (rt: RoomType) => {
  router.push(`/room-types/${rt.id}/edit`)
}

const askDelete = (rt: RoomType) => {
  selectedId.value = rt.id!
  deleteModalOpen.value = true
}

const confirmDelete = async () => {
  if (selectedId.value) {
    await store.deleteRoomType(selectedId.value)
    deleteModalOpen.value = false
  }
}

const create = () => {
  router.push('/room-types/create')
}

const refresh = () => {
  store.fetchRoomTypes()
}
</script>

<template>
  <DashboardLayout>
    <div class="min-h-screen bg-slate-50 dark:bg-slate-950 p-4 sm:p-6 space-y-6 font-sans">
      <!-- Header Area -->
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-5 sm:p-6 rounded-2xl shadow-xs">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-600 to-blue-700 flex items-center justify-center shadow-md flex-shrink-0 text-white">
            <BedDouble class="w-5 h-5 stroke-[2.2]" />
          </div>
          <div>
            <h1 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white">Room Types</h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Manage room tiers, guest capacities, and pricing models.</p>
          </div>
        </div>

        <button
          @click="create"
          class="px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl transition text-xs sm:text-sm inline-flex items-center justify-center gap-2 cursor-pointer shadow-md shadow-blue-600/20"
        >
          <Plus class="w-4 h-4 stroke-[3]" />
          <span>Add Room Type</span>
        </button>
      </div>

      <!-- TABLE COMPONENT WITH INTEGRATED TOOLBAR & FILTER -->
      <RoomTypeTable
        :room-types="store.roomTypes || []"
        :loading="store.loading"
        @view="view"
        @edit="edit"
        @delete="askDelete"
        @create="create"
        @refresh="refresh"
      />

      <!-- DELETE MODAL -->
      <ConfirmDeleteModal
        :open="deleteModalOpen"
        @close="deleteModalOpen = false"
        @confirm="confirmDelete"
      />
    </div>
  </DashboardLayout>
</template>
