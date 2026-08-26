<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'

import DashboardLayout from '../../../layouts/DashboardLayout.vue'
import { useRoomTypeStore } from '../../../stores/roomType'

import RoomTypeTable from '../../../components/room-types/RoomTypeTable.vue'
import RoomTypeSearch from '../../../components/room-types/RoomTypeSearch.vue'
import ConfirmDeleteModal from '../../../components/room-types/DeleteRoomTypeModal.vue'
import { BedDouble, Plus } from 'lucide-vue-next'

import type { RoomType } from '../../../types/roomType'

const router = useRouter()
const store = useRoomTypeStore()

const search = ref('')
const deleteModalOpen = ref(false)
const selectedId = ref<number | null>(null)

onMounted(() => {
  store.fetchRoomTypes()
})

const filtered = computed(() => {
  return (store.roomTypes || []).filter((rt) =>
    rt.name.toLowerCase().includes(search.value.toLowerCase()),
  )
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
</script>

<template>
  <DashboardLayout>
    <div class="min-h-screen bg-slate-50 dark:bg-slate-950">
      <!-- Header Area - Responsive -->
      <div
        class="bg-white dark:bg-slate-900 border-b border-slate-200 dark:border-slate-800 px-4 sm:px-6 py-4 sm:py-5 shadow-xs"
      >
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 sm:gap-4">
          <div class="flex items-center gap-2 sm:gap-3">
            <div
              class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-600 to-blue-700 flex items-center justify-center shadow-md flex-shrink-0 text-white"
            >
              <BedDouble class="w-5 h-5 stroke-[2.2]" />
            </div>
            <h1 class="text-lg sm:text-2xl font-black text-slate-900 dark:text-white">Room Types</h1>
          </div>
          <button
            @click="router.push('/room-types/create')"
            class="px-3 sm:px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl transition text-sm sm:text-base inline-flex items-center justify-center gap-2 cursor-pointer shadow-md shadow-blue-600/20"
          >
            <Plus class="w-4 h-4 stroke-[3]" />
            <span>Add Room Type</span>
          </button>
        </div>
      </div>

      <!-- Main Content - Responsive -->
      <div class="w-full mx-auto max-w-7xl px-4 sm:px-6 py-6 sm:py-8 space-y-6">
        <!-- Search Section - Responsive -->
        <div>
          <RoomTypeSearch v-model="search" />
        </div>

        <!-- Results Info -->
        <div>
          <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-400 font-medium">
            Found <span class="font-bold text-slate-900 dark:text-white">{{ filtered.length }}</span> room type(s)
          </p>
        </div>

        <!-- TABLE COMPONENT -->
        <RoomTypeTable :roomTypes="filtered" @view="view" @edit="edit" @delete="askDelete" />

        <!-- Empty State -->
        <div
          v-if="filtered.length === 0"
          class="text-center py-12 sm:py-16 bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs px-4"
        >
          <div class="w-16 h-16 rounded-2xl bg-blue-50 dark:bg-blue-500/10 text-blue-600 dark:text-blue-400 flex items-center justify-center mx-auto mb-3 border border-blue-200 dark:border-blue-500/20">
            <BedDouble class="w-8 h-8 stroke-[2]" />
          </div>
          <p class="text-lg sm:text-xl font-bold text-slate-900 dark:text-white mb-1">No Room Types Found</p>
          <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mb-6">
            {{
              search
                ? 'Try adjusting your search criteria'
                : 'Create your first room type to get started'
            }}
          </p>
          <button
            @click="router.push('/room-types/create')"
            class="inline-flex items-center gap-2 px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs sm:text-sm rounded-xl shadow-md shadow-blue-600/20 transition cursor-pointer"
          >
            <Plus class="w-4 h-4 stroke-[3]" />
            <span>Create Room Type</span>
          </button>
        </div>

        <!-- DELETE MODAL -->
        <ConfirmDeleteModal
          :open="deleteModalOpen"
          @close="deleteModalOpen = false"
          @confirm="confirmDelete"
        />
      </div>
    </div>
  </DashboardLayout>
</template>
