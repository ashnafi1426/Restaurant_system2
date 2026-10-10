<script setup lang="ts">
import { reactive, onMounted, watch, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import RoomStatusBadge from '../../../components/rooms/RoomStatusBadge.vue'
import QRCodeDownload from '../../../components/rooms/QRCodeDownload.vue'
import { roomService } from '../../../services/roomService'
import { useHotelStore } from '@/stores/hotelStore'
import { useLanguageStore } from '@/stores/language'
import { Building2 } from 'lucide-vue-next'
import type { Room } from '../../../types/room'

const route = useRoute()
const router = useRouter()
const hotelStore = useHotelStore()
const languageStore = useLanguageStore()

const roomId = String(route.params.id)
const currency = computed(() => hotelStore.currentHotel?.currency || 'ETB')

const loading = reactive({
  page: true,
})

const room = reactive<Room>({
  id: roomId,
  room_number: '',
  room_type_id: 1,
  floor: 1,
  description: '',
  status: 'available',
  is_active: true,
  room_type: {
    id: '',
    name: '',
    capacity: 0,
    base_price_per_night: 0,
  },
  created_at: '',
  updated_at: '',
})

const loadRoom = async () => {
  try {
    const response = await roomService.getRoom(roomId)
    const roomData = response.data.data || response.data
    Object.assign(room, roomData)
  } catch (error: any) {
    console.error('[ViewRoom] Error loading room:', error)
    alert('Unable to load room. The room may not exist.')
    router.push('/admin/rooms')
  } finally {
    loading.page = false
  }
}

onMounted(loadRoom)

watch(() => hotelStore.hotelId, loadRoom)
</script>

<template>
  <DashboardLayout>
    <div class="w-full px-4 sm:px-0 font-sans">
      <div
        class="mb-6 sm:mb-8 flex flex-col gap-3 sm:gap-4 sm:flex-row sm:items-center sm:justify-between"
      >
        <div>
          <div class="flex items-center gap-2">
            <h1 class="text-2xl sm:text-3xl font-bold text-slate-800 dark:text-white">
              {{ languageStore.t('room_details', 'Room Details') }}
            </h1>
            <span
              v-if="hotelStore.hotelName"
              class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300 border border-blue-200 dark:border-blue-700/50"
            >
              <Building2 class="w-3 h-3" />
              {{ hotelStore.hotelName }}
            </span>
          </div>
          <p class="mt-1 sm:mt-2 text-sm sm:text-base text-slate-500 dark:text-slate-400">
            {{ languageStore.t('view_room_desc', 'View complete room information.') }}
          </p>
        </div>

        <button
          @click="router.push(`/admin/rooms/${roomId}/edit`)"
          class="rounded-lg bg-amber-500 px-4 sm:px-6 py-2 sm:py-3 font-medium text-white text-sm sm:text-base transition hover:bg-amber-600 cursor-pointer"
        >
          {{ languageStore.t('edit_room', 'Edit Room') }}
        </button>
      </div>

      <div
        v-if="loading.page"
        class="rounded-xl border bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800 p-8 sm:p-12 text-center shadow"
      >
        <div class="text-base sm:text-lg text-slate-500 dark:text-slate-400">
          {{ languageStore.t('loading_room_info', 'Loading room information...') }}
        </div>
      </div>

      <div
        v-else
        class="overflow-hidden rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow"
      >
        <div
          class="border-b border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 px-4 sm:px-6 py-4 sm:py-5"
        >
          <h2 class="text-lg sm:text-xl font-semibold text-slate-900 dark:text-white">
            {{ languageStore.t('room_information', 'Room Information') }}
          </h2>
        </div>

        <div
          class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 sm:gap-4 lg:gap-6 p-4 sm:p-6"
        >
          <div class="rounded-xl border border-slate-200 dark:border-slate-800 p-4 sm:p-5">
            <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400">
              {{ languageStore.t('room_number', 'Room Number') }}
            </p>
            <h3 class="mt-2 text-xl sm:text-2xl font-semibold text-slate-900 dark:text-white">
              {{ room.room_number }}
            </h3>
          </div>

          <div class="rounded-xl border border-slate-200 dark:border-slate-800 p-4 sm:p-5">
            <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400">
              {{ languageStore.t('room_type', 'Room Type') }}
            </p>
            <h3 class="mt-2 text-lg sm:text-xl font-semibold text-slate-900 dark:text-white">
              {{ room.room_type?.name }}
            </h3>
          </div>

          <div class="rounded-xl border border-slate-200 dark:border-slate-800 p-4 sm:p-5">
            <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400">
              {{ languageStore.t('floor', 'Floor') }}
            </p>
            <h3 class="mt-2 text-xl sm:text-2xl font-semibold text-slate-900 dark:text-white">
              {{ room.floor }}
            </h3>
          </div>

          <div class="rounded-xl border border-slate-200 dark:border-slate-800 p-4 sm:p-5">
            <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400">
              {{ languageStore.t('capacity', 'Capacity') }}
            </p>
            <h3 class="mt-2 text-lg sm:text-xl font-semibold text-slate-900 dark:text-white">
              {{ room.room_type?.capacity }} {{ languageStore.t('guests', 'Guests') }}
            </h3>
          </div>

          <div class="rounded-xl border border-slate-200 dark:border-slate-800 p-4 sm:p-5">
            <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400">
              {{ languageStore.t('base_price', 'Base Price') }}
            </p>
            <h3 class="mt-2 text-xl sm:text-2xl font-bold text-emerald-600 dark:text-emerald-400">
              {{ (room.room_type?.base_price_per_night || 0).toLocaleString() }} {{ currency }}
            </h3>
          </div>

          <div class="rounded-xl border border-slate-200 dark:border-slate-800 p-4 sm:p-5">
            <p class="mb-2 sm:mb-3 text-xs sm:text-sm text-slate-500 dark:text-slate-400">
              {{ languageStore.t('status', 'Status') }}
            </p>
            <RoomStatusBadge :status="room.status" />
          </div>
        </div>

        <div class="border-t border-slate-200 dark:border-slate-800 p-4 sm:p-6">
          <h3 class="mb-3 text-base sm:text-lg font-semibold text-slate-900 dark:text-white">
            {{ languageStore.t('description', 'Description') }}
          </h3>
          <p class="leading-6 sm:leading-7 text-sm sm:text-base text-slate-600 dark:text-slate-300">
            {{ room.description || languageStore.t('no_description', 'No description available.') }}
          </p>
        </div>

        <div class="border-t border-slate-200 dark:border-slate-800">
          <QRCodeDownload :room="room" />
        </div>

        <div
          class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6 border-t border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 p-4 sm:p-6"
        >
          <div>
            <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400">
              {{ languageStore.t('created_at', 'Created At') }}
            </p>
            <p class="mt-2 font-medium text-sm sm:text-base text-slate-900 dark:text-slate-200">
              {{ room.created_at }}
            </p>
          </div>

          <div>
            <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400">
              {{ languageStore.t('updated_at', 'Updated At') }}
            </p>
            <p class="mt-2 font-medium text-sm sm:text-base text-slate-900 dark:text-slate-200">
              {{ room.updated_at }}
            </p>
          </div>
        </div>
      </div>

      <div class="mt-6 sm:mt-8">
        <button
          @click="router.push('/admin/rooms')"
          class="rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-200 px-4 sm:px-6 py-2 sm:py-3 font-medium text-sm sm:text-base transition hover:bg-slate-100 dark:hover:bg-slate-700 cursor-pointer"
        >
          ← {{ languageStore.t('back_to_rooms', 'Back to Rooms') }}
        </button>
      </div>
    </div>
  </DashboardLayout>
</template>
