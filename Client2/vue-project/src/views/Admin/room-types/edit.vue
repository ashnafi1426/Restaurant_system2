<script setup lang="ts">
import { reactive, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'

import DashboardLayout from '../../../layouts/DashboardLayout.vue'
import RoomTypeForm from '../../../components/room-types/RoomTypeForm.vue'
import { roomTypeService } from '../../../services/roomtypeService'
import { useRoomTypeStore } from '../../../stores/roomType'

import type { RoomType } from '../../../types/roomType'

const route = useRoute()
const router = useRouter()
const store = useRoomTypeStore()

const id = route.params.id as string
const form = reactive<RoomType>({
  name: '',
  description: '',
  base_price_per_night: 0,
  capacity: 0,
  amenities: [],
  is_active: true,
})

onMounted(async () => {
  const res = await roomTypeService.getRoomType(id)
  Object.assign(form, res.data.data)
})

const submit = async () => {
  await store.updateRoomType(id, form)
  router.push('/room-types')
}
</script>

<template>
  <DashboardLayout>
    <div class="max-w-2xl mx-auto">
      <h1 class="text-2xl font-bold mb-4">Edit Room Type</h1>

      <RoomTypeForm v-model="form" @submit="submit" />
    </div>
  </DashboardLayout>
</template>
