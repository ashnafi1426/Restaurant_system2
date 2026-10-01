<script setup lang="ts">
import { ref, onMounted, computed, watch } from 'vue'
import { useRouter } from 'vue-router'
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import { useRoomTypeStore } from '../../../stores/roomType'
import { useHotelStore } from '@/stores/hotelStore'
import RoomTypeTable from '../../../components/room-types/RoomTypeTable.vue'
import RoomTypeSearch from '../../../components/room-types/RoomTypeSearch.vue'
import ConfirmDeleteModal from '../../../components/room-types/DeleteRoomTypeModal.vue'
import { Plus, Building2 } from 'lucide-vue-next'
import type { RoomType } from '../../../types/roomType'

const router = useRouter()
const store = useRoomTypeStore()
const hotelStore = useHotelStore()

const search = ref('')
const deleteModalOpen = ref(false)
const selectedId = ref<string | number | null>(null)

const loadData = () => {
  store.fetchRoomTypes()
}

onMounted(loadData)

watch(() => hotelStore.hotelId, loadData)

const filtered = computed(() => {
  return (store.roomTypes || []).filter((rt) =>
    rt.name.toLowerCase().includes(search.value.toLowerCase()),
  )
})

const view = (rt: RoomType) => {
  router.push(`/admin/room-types/${rt.id}`)
}

const edit = (rt: RoomType) => {
  router.push(`/admin/room-types/${rt.id}/edit`)
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
        <div class="flex items-center gap-2">
          <h1 class="text-3xl font-bold">Room Types</h1>
          <span v-if="hotelStore.hotelName" class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300 border border-blue-200 dark:border-blue-700/50">
            <Building2 class="w-3 h-3" />
            {{ hotelStore.hotelName }}
          </span>
        </div>

        <button
          class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-xl flex items-center gap-1.5 font-bold text-sm cursor-pointer"
          @click="router.push('/admin/room-types/create')"
        >
          <Plus class="w-4 h-4 stroke-[3]" />
          <span>Add Room Type</span>
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
