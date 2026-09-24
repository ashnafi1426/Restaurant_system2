<script setup lang="ts">
import { reactive } from 'vue'
import { useRouter } from 'vue-router'
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import RoomTypeForm from '../../../components/room-types/RoomTypeForm.vue'
import { useRoomTypeStore } from '../../../stores/roomType'
import { useHotelStore } from '@/stores/hotelStore'
import { Building2 } from 'lucide-vue-next'
import type { RoomType } from '../../../types/roomType'

const router = useRouter()
const store = useRoomTypeStore()
const hotelStore = useHotelStore()

const form = reactive<RoomType>({
  name: '',
  description: '',
  base_price_per_night: 0,
  capacity: 0,
  amenities: [],
  is_active: true,
})

const submit = async () => {
  try {
    if (hotelStore.hotelId && !(form as any).hotel_id) {
      (form as any).hotel_id = hotelStore.hotelId
    }
    await store.createRoomType(form)
    router.push('/room-types')
  } catch (err: any) {
    console.error('Failed to create room type:', err)
    const msg = err.response?.data?.message || err.message || 'Failed to create room type'
    alert(msg)
  }
}
</script>

<template>
  <DashboardLayout>
    <div class="max-w-2xl mx-auto">
      <div class="flex items-center gap-2 mb-4">
        <h1 class="text-2xl font-bold">Create Room Type</h1>
        <span v-if="hotelStore.hotelName" class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300 border border-blue-200 dark:border-blue-700/50">
          <Building2 class="w-3 h-3" />
          {{ hotelStore.hotelName }}
        </span>
      </div>

      <RoomTypeForm v-model="form" @submit="submit" />
    </div>
  </DashboardLayout>
</template>
