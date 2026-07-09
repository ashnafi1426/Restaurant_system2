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
    <div class="space-y-6">
      <!-- HEADER -->
      <div class="flex justify-between items-center">
        <h1 class="text-3xl font-bold">Room Types</h1>

        <button
          class="bg-blue-600 text-white px-4 py-2 rounded"
          @click="router.push('/room-types/create')"
        >
          + Add Room Type
        </button>
      </div>

      <!-- SEARCH COMPONENT -->
      <RoomTypeSearch v-model="search" />

      <!-- TABLE COMPONENT -->
      <RoomTypeTable :roomTypes="filtered" @view="view" @edit="edit" @delete="askDelete" />

      <!-- DELETE MODAL -->
      <ConfirmDeleteModal
        :open="deleteModalOpen"
        @close="deleteModalOpen = false"
        @confirm="confirmDelete"
      />
    </div>
  </DashboardLayout>
</template>
