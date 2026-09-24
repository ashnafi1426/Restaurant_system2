<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'

import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import { useRoomTypeStore } from '../../../stores/roomType'
import { useHotelStore } from '../../../stores/hotelStore'
import { useLanguageStore } from '@/stores/language'

import RoomTypeTable from '../../../components/room-types/RoomTypeTable.vue'
import ConfirmDeleteModal from '../../../components/room-types/DeleteRoomTypeModal.vue'
import { BedDouble, Plus, Building2 } from 'lucide-vue-next'

import type { RoomType } from '../../../types/roomType'

const router = useRouter()
const store = useRoomTypeStore()
const hotelStore = useHotelStore()
const languageStore = useLanguageStore()

const deleteModalOpen = ref(false)
const selectedId = ref<string | number | null>(null)

const loadData = () => {
  store.fetchRoomTypes()
}

onMounted(loadData)

watch(() => hotelStore.hotelId, () => {
  loadData()
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
            <div class="flex items-center gap-2">
              <h1 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white">
                {{ languageStore.t('room_types', 'Room Types') }}
              </h1>
              <span v-if="hotelStore.hotelName" class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300 border border-blue-200 dark:border-blue-700/50">
                <Building2 class="w-3 h-3" />
                {{ hotelStore.hotelName }}
              </span>
            </div>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
              {{ languageStore.t('room_types_desc', 'Manage room tiers, guest capacities, and pricing models.') }}
            </p>
          </div>
        </div>


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

