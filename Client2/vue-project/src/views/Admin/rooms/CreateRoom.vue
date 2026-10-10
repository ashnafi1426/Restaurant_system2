<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import RoomForm from '../../../components/rooms/RoomForm.vue'
import { useRoomStore } from '../../../stores/room'
import { useHotelStore } from '@/stores/hotelStore'
import { useLanguageStore } from '@/stores/language'
import { Building2, AlertTriangle, CheckCircle2, X } from 'lucide-vue-next'
import type { Room } from '../../../types/room'

const router = useRouter()
const roomStore = useRoomStore()
const hotelStore = useHotelStore()
const languageStore = useLanguageStore()

const isSubmitting = ref(false)
const serverErrors = ref<Record<string, string[]>>({})
const errorMessage = ref<string | null>(null)
const successMessage = ref<string | null>(null)

const saveRoom = async (room: Room) => {
  isSubmitting.value = true
  serverErrors.value = {}
  errorMessage.value = null
  successMessage.value = null

  try {
    const payload = { ...room }
    delete (payload as any).hotel_id

    await roomStore.createRoom(payload as any)

    successMessage.value = languageStore.t(
      'room_created_success',
      `Room #${room.room_number} created successfully for ${hotelStore.hotelName}!`,
    )

    setTimeout(() => {
      router.push('/admin/rooms')
    }, 1200)
  } catch (error: any) {
    console.error('[CreateRoom] Error creating room:', error)
    const errorData = error.response?.data
    if (errorData?.errors) {
      serverErrors.value = errorData.errors
      errorMessage.value =
        errorData.message || 'Validation failed. Please correct the highlighted errors.'
    } else {
      errorMessage.value = errorData?.message || error.message || 'Unable to create room.'
    }
  } finally {
    isSubmitting.value = false
  }
}

const cancel = () => {
  router.push('/admin/rooms')
}
</script>

<template>
  <DashboardLayout>
    <div class="max-w-5xl mx-auto pb-12">
      <!-- Page Header -->
      <div class="mb-6">
        <div class="flex items-center gap-2.5">
          <h1 class="text-2xl sm:text-3xl font-bold text-slate-900 dark:text-white">
            {{ languageStore.t('create_room', 'Create Room') }}
          </h1>
          <span
            v-if="hotelStore.hotelName"
            class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-blue-100 dark:bg-blue-900/40 text-blue-800 dark:text-blue-300 border border-blue-200 dark:border-blue-700/50 shadow-sm"
          >
            <Building2 class="w-3.5 h-3.5" />
            {{ hotelStore.hotelName }}
          </span>
        </div>
        <p class="text-slate-500 dark:text-slate-400 text-sm mt-1">
          {{ languageStore.t('add_new_hotel_room', 'Add a new room to') }}
          <span class="font-medium text-slate-700 dark:text-slate-300">{{
            hotelStore.hotelName
          }}</span
          >.
        </p>
      </div>

      <!-- Success Notification Banner -->
      <div
        v-if="successMessage"
        class="mb-6 p-4 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 rounded-xl flex items-center justify-between gap-3 text-emerald-800 dark:text-emerald-300 text-sm shadow-sm transition-all animate-fadeIn"
      >
        <div class="flex items-center gap-2.5">
          <CheckCircle2 class="w-5 h-5 text-emerald-600 dark:text-emerald-400 flex-shrink-0" />
          <span>{{ successMessage }}</span>
        </div>
      </div>

      <!-- Error Notification Banner -->
      <div
        v-if="errorMessage"
        class="mb-6 p-4 bg-red-50 dark:bg-red-950/40 border border-red-200 dark:border-red-800 rounded-xl flex items-start justify-between gap-3 text-red-800 dark:text-red-300 text-sm shadow-sm transition-all animate-fadeIn"
      >
        <div class="flex items-start gap-2.5">
          <AlertTriangle class="w-5 h-5 text-red-600 dark:text-red-400 flex-shrink-0 mt-0.5" />
          <div>
            <p class="font-semibold">{{ errorMessage }}</p>
            <ul
              v-if="Object.keys(serverErrors).length > 0"
              class="mt-1 list-disc list-inside text-xs space-y-0.5 text-red-700 dark:text-red-400"
            >
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

      <!-- Room Form Card -->
      <div
        class="bg-white dark:bg-slate-900 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-800 overflow-hidden"
      >
        <div
          class="px-6 py-4 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between"
        >
          <h2 class="text-lg font-semibold text-slate-800 dark:text-slate-100">
            {{ languageStore.t('room_information', 'Room Information') }}
          </h2>
          <span class="text-xs text-slate-400">
            Target Hotel:
            <strong class="text-slate-600 dark:text-slate-300">{{ hotelStore.hotelName }}</strong>
          </span>
        </div>

        <div class="p-6">
          <RoomForm
            :is-submitting="isSubmitting"
            :server-errors="serverErrors"
            @submit="saveRoom"
            @cancel="cancel"
          />
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>
