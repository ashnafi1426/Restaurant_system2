<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'

import DashboardLayout from '../../../layouts/DashboardLayout.vue'
import { useRoomTypeStore } from '../../../stores/roomType'

import RoomTypeTable from '../../../components/room-types/RoomTypeTable.vue'
import RoomTypeSearch from '../../../components/room-types/RoomTypeSearch.vue'
import ConfirmDeleteModal from '../../../components/room-types/DeleteRoomTypeModal.vue'

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
    <div class="min-h-screen bg-slate-50">
      <!-- Header Area - Responsive -->
      <div
        class="bg-gradient-to-r from-slate-50 to-white border-b border-slate-200 px-4 sm:px-6 py-4 sm:py-5 shadow-sm"
      >
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 sm:gap-4">
          <div class="flex items-center gap-2 sm:gap-3">
            <div
              class="w-10 h-10 rounded-lg bg-gradient-to-br from-blue-600 to-blue-700 flex items-center justify-center shadow-md flex-shrink-0"
            >
              <span class="text-xl text-white">🏨</span>
            </div>
            <h1 class="text-lg sm:text-2xl font-black text-slate-900">Room Types</h1>
          </div>
          <button
            @click="router.push('/room-types/create')"
            class="px-3 sm:px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition text-sm sm:text-base"
          >
            ➕ Add Room Type
          </button>
        </div>
      </div>

      <!-- Main Content - Responsive -->
      <div class="w-full mx-auto max-w-7xl px-4 sm:px-6 py-6 sm:py-8">
        <!-- Search Section - Responsive -->
        <div class="mb-6 sm:mb-8">
          <RoomTypeSearch v-model="search" />
        </div>

        <!-- Results Info -->
        <div class="mb-4 sm:mb-6">
          <p class="text-xs sm:text-sm text-slate-600">
            Found <span class="font-semibold">{{ filtered.length }}</span> room type(s)
          </p>
        </div>

        <!-- TABLE COMPONENT -->
        <RoomTypeTable :roomTypes="filtered" @view="view" @edit="edit" @delete="askDelete" />

        <!-- Empty State -->
        <div
          v-if="filtered.length === 0"
          class="text-center py-12 sm:py-16 bg-white rounded-xl border border-slate-200 shadow-sm px-4"
        >
          <span class="text-3xl sm:text-4xl block mb-3">🏨</span>
          <p class="text-lg sm:text-xl font-bold text-slate-900 mb-1">No Room Types Found</p>
          <p class="text-xs sm:text-sm text-slate-500 mb-6">
            {{
              search
                ? 'Try adjusting your search criteria'
                : 'Create your first room type to get started'
            }}
          </p>
          <button
            @click="router.push('/room-types/create')"
            class="inline-flex items-center gap-2 px-4 sm:px-5 py-2 sm:py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs sm:text-sm rounded-lg shadow transition"
          >
            ➕ Create Room Type
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
