<script setup lang="ts">
import { reactive } from 'vue'
import { useRouter } from 'vue-router'

import DashboardLayout from '../../../layouts/DashboardLayout.vue'
import RoomTypeForm from '../../../components/room-types/RoomTypeForm.vue'
import { useRoomTypeStore } from '../../../stores/roomType'

import type { RoomType } from '../../../types/roomType'

const router = useRouter()
const store = useRoomTypeStore()

const form = reactive<RoomType>({
  name: '',
  description: '',
  base_price_per_night: 0,
  capacity: 0,
  amenities: [],
  is_active: true,
})

const submit = async () => {
  await store.createRoomType(form)
  router.push('/room-types')
}
</script>

<template>
  <DashboardLayout>
    <div class="max-w-2xl mx-auto">
      <h1 class="text-2xl font-bold mb-4">Create Room Type</h1>

      <RoomTypeForm v-model="form" @submit="submit" />
    </div>
  </DashboardLayout>
</template>
