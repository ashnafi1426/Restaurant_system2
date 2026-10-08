<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'

import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import RoomTypeForm from '../../../components/room-types/RoomTypeForm.vue'
import { roomTypeService } from '@/services/roomTypeService'
import { useRoomTypeStore } from '../../../stores/roomType'
import { useHotelStore } from '@/stores/hotelStore'
import { getErrorMessage, getValidationErrors } from '@/utils/error'
import { Building2, AlertTriangle, CheckCircle2, X } from 'lucide-vue-next'

import type { RoomType } from '../../../types/roomType'

const route = useRoute()
const router = useRouter()
const store = useRoomTypeStore()
const hotelStore = useHotelStore()

const id = route.params.id as string

let form = ref<RoomType>({
  name: '',
  description: '',
  base_price_per_night: 0,
  capacity: 0,
  amenities: [],
  is_active: true,
})

const loading = ref(true)
const isSubmitting = ref(false)
const serverErrors = ref<Record<string, string[]>>({})
const error = ref<string | null>(null)
const successMessage = ref<string | null>(null)

const loadData = async () => {
  try {
    loading.value = true
    const res = await roomTypeService.getRoomType(id as any)
    const data = res.data.data
    
    form.value = {
      id: data.id,
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
    error.value = getErrorMessage(err, 'Failed to load room type')
  } finally {
    loading.value = false
  }
}

onMounted(loadData)

watch(() => hotelStore.hotelId, loadData)

const submit = async () => {
  isSubmitting.value = true
  serverErrors.value = {}
  error.value = null
  successMessage.value = null

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
    successMessage.value = `Room type "${form.value.name}" updated successfully!`
    
    setTimeout(() => {
      router.push('/admin/room-types')
    }, 1200)
  } catch (err: any) {
    console.error('[RoomTypesEdit] Failed to update room type:', err)
    serverErrors.value = getValidationErrors(err)
    error.value = getErrorMessage(err, 'Failed to update room type')
  } finally {
    isSubmitting.value = false
  }
}
</script>

<template>
  <DashboardLayout>
    <div class="max-w-2xl mx-auto pb-12">
      <div class="flex items-center gap-2 mb-4">
        <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Edit Room Type</h1>
        <span v-if="hotelStore.hotelName" class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300 border border-blue-200 dark:border-blue-700/50">
          <Building2 class="w-3 h-3" />
          {{ hotelStore.hotelName }}
        </span>
      </div>

      <!-- Success Banner -->
      <div
        v-if="successMessage"
        class="mb-4 p-4 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 rounded-xl flex items-center gap-2.5 text-emerald-800 dark:text-emerald-300 text-sm shadow-sm animate-fadeIn"
      >
        <CheckCircle2 class="w-5 h-5 text-emerald-600 dark:text-emerald-400 flex-shrink-0" />
        <span>{{ successMessage }}</span>
      </div>

      <!-- Error Banner -->
      <div
        v-if="error"
        class="mb-4 p-4 bg-red-50 dark:bg-red-950/40 border border-red-200 dark:border-red-800 rounded-xl flex items-start justify-between gap-3 text-red-800 dark:text-red-300 text-sm shadow-sm animate-fadeIn"
      >
        <div class="flex items-start gap-2.5">
          <AlertTriangle class="w-5 h-5 text-red-600 dark:text-red-400 flex-shrink-0 mt-0.5" />
          <div>
            <p class="font-semibold">{{ error }}</p>
            <ul v-if="Object.keys(serverErrors).length > 0" class="mt-1 list-disc list-inside text-xs space-y-0.5 text-red-700 dark:text-red-400">
              <li v-for="(errList, field) in serverErrors" :key="field">
                {{ errList[0] }}
              </li>
            </ul>
          </div>
        </div>
        <button
          type="button"
          @click="error = null"
          class="text-red-500 hover:text-red-700 cursor-pointer p-1"
        >
          <X class="w-4 h-4" />
        </button>
      </div>

      <div v-if="loading" class="flex items-center justify-center py-16">
        <div class="w-8 h-8 border-4 border-blue-600 border-t-transparent rounded-full animate-spin"></div>
      </div>

      <div v-else class="bg-white dark:bg-slate-900 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-800 p-6">
        <RoomTypeForm
          v-model="form"
          :is-submitting="isSubmitting"
          :server-errors="serverErrors"
          @submit="submit"
          @cancel="router.push('/admin/room-types')"
        />
      </div>
    </div>
  </DashboardLayout>
</template>
