<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'

import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import RoomTypeForm from '../../../components/room-types/RoomTypeForm.vue'
import { roomTypeService } from '../../../services/roomtypeService'
import { useRoomTypeStore } from '../../../stores/roomType'

import type { RoomType } from '../../../types/roomType'

const route = useRoute()
const router = useRouter()
const store = useRoomTypeStore()

const id = route.params.id as string

// Use ref so the whole object replacement triggers child watcher
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

onMounted(async () => {
  try {
    console.log('[EDIT] Fetching room type with ID:', id)
    const res = await roomTypeService.getRoomType(id as any)
    console.log('[EDIT] API Response:', res.data)
    
    const data = res.data.data
    console.log('[EDIT] Room type data:', data)
    
    // Replace the entire ref value so the child's deep watcher fires
    form.value = {
      name: data.name ?? '',
      description: data.description ?? '',
      // Ensure numeric types — HTML number inputs return strings via v-model
      base_price_per_night: Number(data.base_price_per_night) || 0,
      capacity: parseInt(String(data.capacity), 10) || 0,
      // Ensure amenities is always a plain string array
      amenities: Array.isArray(data.amenities)
        ? data.amenities.map((a: any) => (typeof a === 'string' ? a : String(a)))
        : [],
      // Ensure strict boolean (API may return 0/1)
      is_active: data.is_active === true || data.is_active === 1,
    }
    
    console.log('[EDIT] Form populated with:', form.value)
  } catch (err: any) {
    console.error('[EDIT] Error fetching room type:', err)
    error.value = err.response?.data?.message || 'Failed to load room type'
  } finally {
    loading.value = false
  }
})

const submit = async () => {
  try {
    const payload: RoomType = {
      name: form.value.name,
      description: form.value.description,
      // Cast to proper types to satisfy Laravel validation
      base_price_per_night: Number(form.value.base_price_per_night),
      capacity: parseInt(String(form.value.capacity), 10),
      amenities: form.value.amenities,
      is_active: Boolean(form.value.is_active),
    }
    
    console.log('[EDIT] Submitting payload:', payload)
    await store.updateRoomType(id, payload)
    router.push('/room-types')
  } catch (err: any) {
    console.error('[EDIT] Error updating room type:', err)
    error.value = err.response?.data?.message || 'Failed to update room type'
  }
}
</script>

<template>
  <DashboardLayout>
    <div class="max-w-2xl mx-auto">
      <h1 class="text-2xl font-bold mb-4">Edit Room Type</h1>

      <!-- Error Message -->
      <div v-if="error" class="mb-4 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl">
        <p class="text-sm text-red-600 dark:text-red-400">{{ error }}</p>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="flex items-center justify-center py-16">
        <div class="w-8 h-8 border-4 border-blue-600 border-t-transparent rounded-full animate-spin"></div>
      </div>

      <!-- Form -->
      <RoomTypeForm v-else v-model="form" @submit="submit" @cancel="router.push('/room-types')" />
    </div>
  </DashboardLayout>
</template>
