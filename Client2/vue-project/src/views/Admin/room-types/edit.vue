<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'

import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import RoomTypeForm from '../../../components/room-types/RoomTypeForm.vue'
import { roomTypeService } from '../../../services/roomtypeService'
import { useRoomTypeStore } from '../../../stores/roomType'
import { useHotelStore } from '@/stores/hotelStore'
import { Building2 } from 'lucide-vue-next'

import type { RoomType } from '../../../types/roomType'

const route = useRoute()
const router = useRouter()
const store = useRoomTypeStore()
const hotelStore = useHotelStore()

const id = route.params.id as string

const form = ref<RoomType>({
  name: '',
  description: '',
  base_price_per_night: 0,
  capacity: 0,
  amenities: [],
  is_active: true,
})

const loading = ref(true)
const error = ref<string | null>(null)

const loadData = async () => {
  try {
    loading.value = true
    const res = await roomTypeService.getRoomType(id as any)
    const data = res.data.data
    
    form.value = {
      name: data.name ?? '',
      description: data.description ?? '',
      base_price_per_night: Number(data.base_price_per_night) || 0,
      capacity: parseInt(String(data.capacity), 10) || 0,
      amenities: Array.isArray(data.amenities)
        ? data.amenities.map((a: any) => (typeof a === 'string' ? a : String(a)))
        : [],
      is_active: data.is_active === true || data.is_active === 1,
    }
  } catch (err: any) {
    console.error('[RoomTypesEdit] Failed to load room type:', err)
    error.value = err.response?.data?.message || 'Failed to load room type'
  } finally {
    loading.value = false
  }
}

onMounted(loadData)

watch(() => hotelStore.hotelId, loadData)

const submit = async () => {
  try {
    const payload: RoomType = {
      name: form.value.name,
      description: form.value.description,
      base_price_per_night: Number(form.value.base_price_per_night),
      capacity: parseInt(String(form.value.capacity), 10),
      amenities: form.value.amenities,
      is_active: Boolean(form.value.is_active),
    }
    
    await store.updateRoomType(id, payload)
    router.push('/room-types')
  } catch (err: any) {
    console.error('[RoomTypesEdit] Failed to update room type:', err)
    error.value = err.response?.data?.message || 'Failed to update room type'
  }
}
</script>

<template>
  <DashboardLayout>
    <div class="max-w-2xl mx-auto">
      <div class="flex items-center gap-2 mb-4">
        <h1 class="text-2xl font-bold">Edit Room Type</h1>
        <span v-if="hotelStore.hotelName" class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300 border border-blue-200 dark:border-blue-700/50">
          <Building2 class="w-3 h-3" />
          {{ hotelStore.hotelName }}
        </span>
      </div>

      <div v-if="error" class="mb-4 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl">
        <p class="text-sm text-red-600 dark:text-red-400">{{ error }}</p>
      </div>

      <div v-if="loading" class="flex items-center justify-center py-16">
        <div class="w-8 h-8 border-4 border-blue-600 border-t-transparent rounded-full animate-spin"></div>
      </div>

      <RoomTypeForm v-else v-model="form" @submit="submit" @cancel="router.push('/room-types')" />
    </div>
  </DashboardLayout>
</template>
