<script setup lang="ts">
import { reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import RoomTypeForm from '../../../components/room-types/RoomTypeForm.vue'
import { useRoomTypeStore } from '../../../stores/roomType'
import { useHotelStore } from '@/stores/hotelStore'
import { Building2, AlertTriangle, CheckCircle2, X } from 'lucide-vue-next'
import type { RoomType } from '../../../types/roomType'

const router = useRouter()
const store = useRoomTypeStore()
const hotelStore = useHotelStore()

const isSubmitting = ref(false)
const serverErrors = ref<Record<string, string[]>>({})
const errorMessage = ref<string | null>(null)
const successMessage = ref<string | null>(null)

const form = reactive<RoomType>({
  name: '',
  description: '',
  base_price_per_night: 0,
  capacity: 2,
  amenities: [],
  is_active: true,
})

const submit = async () => {
  isSubmitting.value = true
  serverErrors.value = {}
  errorMessage.value = null
  successMessage.value = null

  try {
    if (hotelStore.hotelId && !(form as any).hotel_id) {
      (form as any).hotel_id = hotelStore.hotelId
    }
    await store.createRoomType(form)
    successMessage.value = `Room type "${form.name}" created successfully for ${hotelStore.hotelName}!`
    
    setTimeout(() => {
      router.push('/admin/room-types')
    }, 1200)
  } catch (err: any) {
    console.error('[CreateRoomType] Failed to create room type:', err)
    const errorData = err.response?.data
    if (errorData?.errors) {
      serverErrors.value = errorData.errors
      errorMessage.value = errorData.message || 'Validation failed. Please correct the highlighted errors.'
    } else {
      errorMessage.value = errorData?.message || err.message || 'Failed to create room type'
    }
  } finally {
    isSubmitting.value = false
  }
}
</script>

<template>
  <DashboardLayout>
    <div class="max-w-2xl mx-auto pb-12">
      <!-- Header -->
      <div class="flex items-center gap-2 mb-4">
        <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Create Room Type</h1>
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
        v-if="errorMessage"
        class="mb-4 p-4 bg-red-50 dark:bg-red-950/40 border border-red-200 dark:border-red-800 rounded-xl flex items-start justify-between gap-3 text-red-800 dark:text-red-300 text-sm shadow-sm animate-fadeIn"
      >
        <div class="flex items-start gap-2.5">
          <AlertTriangle class="w-5 h-5 text-red-600 dark:text-red-400 flex-shrink-0 mt-0.5" />
          <div>
            <p class="font-semibold">{{ errorMessage }}</p>
            <ul v-if="Object.keys(serverErrors).length > 0" class="mt-1 list-disc list-inside text-xs space-y-0.5 text-red-700 dark:text-red-400">
              <li v-for="(errList, field) in serverErrors" :key="field">
                {{ errList[0] }}
              </li>
            </ul>
          </div>
        </div>
        <button
          type="button"
          @click="errorMessage = null"
          class="text-red-500 hover:text-red-700 cursor-pointer p-1"
        >
          <X class="w-4 h-4" />
        </button>
      </div>

      <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-800 p-6">
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
