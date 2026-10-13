<script setup lang="ts">
import { ref, onMounted, computed, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import { roomTypeService } from '@/services/roomTypeService'
import { useHotelStore } from '@/stores/hotelStore'
import { useLanguageStore } from '@/stores/language'
import ConfirmDeleteModal from '../../../components/room-types/DeleteRoomTypeModal.vue'
import {
  ArrowLeft,
  BedDouble,
  Users,
  DollarSign,
  CheckCircle2,
  XCircle,
  Calendar,
  Clock,
  Edit,
  Trash2,
  Building2,
  Sparkles,
  Loader2,
  AlertCircle,
  DoorClosed,
  Check,
} from 'lucide-vue-next'
import type { RoomType } from '../../../types/roomType'

const route = useRoute()
const router = useRouter()
const hotelStore = useHotelStore()
const languageStore = useLanguageStore()

const roomTypeId = computed(() => String(route.params.id))
const currency = computed(() => hotelStore.currentHotel?.currency || 'USD')

const loading = ref(true)
const error = ref<string | null>(null)
const roomType = ref<any>(null)
const deleteModalOpen = ref(false)
const isDeleting = ref(false)

const loadData = async () => {
  if (!roomTypeId.value) return
  try {
    loading.value = true
    error.value = null
    const res = await roomTypeService.getRoomType(roomTypeId.value)
    roomType.value = res.data?.data || res.data
  } catch (err: any) {
    console.error('[ViewRoomType] Failed to load room type:', err)
    error.value = err.response?.data?.message || err.message || 'Failed to load room type details'
  } finally {
    loading.value = false
  }
}

onMounted(loadData)
watch(() => hotelStore.hotelId, loadData)
watch(() => route.params.id, loadData)

const confirmDelete = async () => {
  if (!roomType.value?.id) return
  try {
    isDeleting.value = true
    await roomTypeService.deleteRoomType(roomType.value.id)
    deleteModalOpen.value = false
    router.push('/admin/room-types')
  } catch (err: any) {
    console.error('[ViewRoomType] Failed to delete room type:', err)
    alert(err.response?.data?.message || err.message || 'Failed to delete room type')
  } finally {
    isDeleting.value = false
  }
}
</script>

<template>
  <DashboardLayout>
    <div
      class="space-y-4 sm:space-y-6 bg-slate-50 dark:bg-slate-950 min-h-screen p-3 sm:p-6 max-w-full font-sans"
    >
      <!-- Top Action Bar -->
      <div
        class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 sm:gap-4 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-4 sm:p-6 rounded-2xl shadow-xs"
      >
        <div class="flex items-center gap-3">
          <button
            type="button"
            @click="router.push('/admin/room-types')"
            class="w-10 h-10 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/60 hover:bg-slate-100 dark:hover:bg-slate-800 flex items-center justify-center transition text-slate-600 dark:text-slate-300 cursor-pointer shrink-0"
            :title="languageStore.t('back_to_room_types', 'Back to Room Types')"
          >
            <ArrowLeft class="w-5 h-5" />
          </button>
          <div>
            <div class="flex flex-wrap items-center gap-2">
              <h1 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white">
                {{ roomType?.name || languageStore.t('room_type_details', 'Room Type Details') }}
              </h1>
              <span
                v-if="roomType?.id"
                class="px-2.5 py-0.5 rounded-full text-xs font-mono font-bold bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 border border-blue-200 dark:border-blue-800"
              >
                ID: #{{ roomType.id }}
              </span>
              <span
                v-if="hotelStore.hotelName"
                class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700"
              >
                <Building2 class="w-3 h-3" />
                <span class="truncate">{{ hotelStore.hotelName }}</span>
              </span>
            </div>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
              {{
                languageStore.t(
                  'room_type_view_desc',
                  'View comprehensive pricing, capacity, and amenities for this room category.',
                )
              }}
            </p>
          </div>
        </div>

        <div v-if="roomType" class="flex items-center gap-2 w-full sm:w-auto">
          <button
            type="button"
            @click="router.push(`/admin/room-types/${roomType.id}/edit`)"
            class="flex-1 sm:flex-initial inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-600 text-white font-bold text-xs sm:text-sm shadow-xs transition cursor-pointer"
          >
            <Edit class="w-4 h-4" />
            <span>{{ languageStore.t('edit', 'Edit Room Type') }}</span>
          </button>

          <button
            type="button"
            @click="deleteModalOpen = true"
            class="inline-flex items-center justify-center gap-1.5 px-3 sm:px-4 py-2.5 rounded-xl bg-rose-50 dark:bg-rose-950/30 hover:bg-rose-100 dark:hover:bg-rose-900/40 text-rose-600 dark:text-rose-400 border border-rose-200 dark:border-rose-900/50 font-bold text-xs sm:text-sm transition cursor-pointer"
            :title="languageStore.t('delete', 'Delete')"
          >
            <Trash2 class="w-4 h-4" />
            <span class="hidden sm:inline">{{ languageStore.t('delete', 'Delete') }}</span>
          </button>
        </div>
      </div>

      <!-- Loading State -->
      <div
        v-if="loading"
        class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-12 text-center"
      >
        <div class="flex flex-col items-center justify-center gap-3">
          <Loader2 class="w-8 h-8 text-blue-600 dark:text-blue-400 animate-spin" />
          <p class="text-sm font-bold text-slate-600 dark:text-slate-400">
            {{ languageStore.t('loading_room_type', 'Loading room type details...') }}
          </p>
        </div>
      </div>

      <!-- Error State -->
      <div
        v-else-if="error"
        class="rounded-2xl border border-rose-200 dark:border-rose-900/50 bg-rose-50 dark:bg-rose-950/30 p-6 flex flex-col items-center text-center gap-3"
      >
        <AlertCircle class="w-8 h-8 text-rose-600 dark:text-rose-400" />
        <h3 class="text-base font-bold text-rose-800 dark:text-rose-200">
          {{ languageStore.t('error_loading_room_type', 'Could not load room type') }}
        </h3>
        <p class="text-xs text-rose-700 dark:text-rose-300 max-w-md">
          {{ error }}
        </p>
        <div class="flex items-center gap-2 mt-2">
          <button
            type="button"
            @click="loadData"
            class="px-4 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold transition cursor-pointer"
          >
            {{ languageStore.t('retry', 'Retry') }}
          </button>
          <button
            type="button"
            @click="router.push('/admin/room-types')"
            class="px-4 py-2 rounded-xl border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 text-xs font-bold hover:bg-slate-100 dark:hover:bg-slate-800 transition cursor-pointer"
          >
            {{ languageStore.t('back_to_list', 'Back to Room Types') }}
          </button>
        </div>
      </div>

      <!-- Room Type Content -->
      <div v-else-if="roomType" class="space-y-4 sm:space-y-6">
        <!-- Metric Cards -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-2.5 sm:gap-4">
          <!-- Base Price Card -->
          <div
            class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-3.5 sm:p-5 shadow-xs flex items-center justify-between"
          >
            <div>
              <p
                class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider"
              >
                {{ languageStore.t('base_price', 'Base Price / Night') }}
              </p>
              <h3
                class="text-xl sm:text-2xl font-black text-emerald-600 dark:text-emerald-400 font-mono mt-0.5 sm:mt-1"
              >
                ${{ parseFloat(String(roomType.base_price_per_night || 0)).toFixed(2) }}
              </h3>
              <p class="text-[10px] text-slate-400 mt-0.5 font-medium">
                {{ currency }} {{ languageStore.t('per_night', 'per night') }}
              </p>
            </div>
            <div
              class="p-2.5 sm:p-3 rounded-xl sm:rounded-2xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 shrink-0"
            >
              <DollarSign class="w-4 h-4 sm:w-5 sm:h-5" />
            </div>
          </div>

          <!-- Capacity Card -->
          <div
            class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-3.5 sm:p-5 shadow-xs flex items-center justify-between"
          >
            <div>
              <p
                class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider"
              >
                {{ languageStore.t('max_capacity', 'Max Capacity') }}
              </p>
              <h3
                class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white mt-0.5 sm:mt-1"
              >
                {{ roomType.capacity || 1 }}
              </h3>
              <p class="text-[10px] text-slate-400 mt-0.5 font-medium">
                {{ languageStore.t('guests_allowed', 'Guests max allowed') }}
              </p>
            </div>
            <div
              class="p-2.5 sm:p-3 rounded-xl sm:rounded-2xl bg-blue-500/10 text-blue-600 dark:text-blue-400 shrink-0"
            >
              <Users class="w-4 h-4 sm:w-5 sm:h-5" />
            </div>
          </div>

          <!-- Status Card -->
          <div
            class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-3.5 sm:p-5 shadow-xs flex items-center justify-between"
          >
            <div>
              <p
                class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider"
              >
                {{ languageStore.t('status', 'Status') }}
              </p>
              <div class="mt-1">
                <span
                  v-if="roomType.is_active"
                  class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-extrabold bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20"
                >
                  <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                  <span>{{ languageStore.t('active', 'Active') }}</span>
                </span>
                <span
                  v-else
                  class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-extrabold bg-rose-500/10 text-rose-600 dark:text-rose-400 border border-rose-500/20"
                >
                  <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                  <span>{{ languageStore.t('inactive', 'Inactive') }}</span>
                </span>
              </div>
              <p class="text-[10px] text-slate-400 mt-1 font-medium">
                {{
                  roomType.is_active
                    ? languageStore.t('open_for_booking', 'Available for booking')
                    : languageStore.t('hidden_from_booking', 'Hidden from booking')
                }}
              </p>
            </div>
            <div
              class="p-2.5 sm:p-3 rounded-xl sm:rounded-2xl shrink-0"
              :class="
                roomType.is_active
                  ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400'
                  : 'bg-rose-500/10 text-rose-600 dark:text-rose-400'
              "
            >
              <CheckCircle2 v-if="roomType.is_active" class="w-4 h-4 sm:w-5 sm:h-5" />
              <XCircle v-else class="w-4 h-4 sm:w-5 sm:h-5" />
            </div>
          </div>

          <!-- Associated Rooms Card -->
          <div
            class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-3.5 sm:p-5 shadow-xs flex items-center justify-between"
          >
            <div>
              <p
                class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider"
              >
                {{ languageStore.t('associated_rooms', 'Total Rooms') }}
              </p>
              <h3
                class="text-xl sm:text-2xl font-black text-purple-600 dark:text-purple-400 mt-0.5 sm:mt-1"
              >
                {{ roomType.rooms_count ?? 0 }}
              </h3>
              <p class="text-[10px] text-slate-400 mt-0.5 font-medium">
                {{ languageStore.t('rooms_assigned_to_type', 'Rooms under this type') }}
              </p>
            </div>
            <div
              class="p-2.5 sm:p-3 rounded-xl sm:rounded-2xl bg-purple-500/10 text-purple-600 dark:text-purple-400 shrink-0"
            >
              <DoorClosed class="w-4 h-4 sm:w-5 sm:h-5" />
            </div>
          </div>
        </div>

        <!-- Description Card -->
        <div
          class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 sm:p-6 shadow-xs"
        >
          <div
            class="flex items-center gap-2 mb-3 border-b border-slate-100 dark:border-slate-800/80 pb-3"
          >
            <BedDouble class="w-5 h-5 text-blue-600 dark:text-blue-400" />
            <h2 class="text-sm sm:text-base font-extrabold text-slate-900 dark:text-white">
              {{ languageStore.t('description', 'Description') }}
            </h2>
          </div>
          <p
            class="text-xs sm:text-sm text-slate-600 dark:text-slate-300 leading-relaxed font-normal whitespace-pre-line"
          >
            {{
              roomType.description ||
              languageStore.t(
                'no_description_provided',
                'No description provided for this room type.',
              )
            }}
          </p>
        </div>

        <!-- Amenities & Features Card -->
        <div
          class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 sm:p-6 shadow-xs"
        >
          <div
            class="flex items-center justify-between mb-4 border-b border-slate-100 dark:border-slate-800/80 pb-3"
          >
            <div class="flex items-center gap-2">
              <Sparkles class="w-5 h-5 text-amber-500" />
              <h2 class="text-sm sm:text-base font-extrabold text-slate-900 dark:text-white">
                {{ languageStore.t('amenities_features', 'Amenities & Included Features') }}
              </h2>
            </div>
            <span
              class="text-xs font-bold text-slate-500 dark:text-slate-400 px-2.5 py-1 rounded-full bg-slate-100 dark:bg-slate-800"
            >
              {{ Array.isArray(roomType.amenities) ? roomType.amenities.length : 0 }}
              {{ languageStore.t('amenities', 'amenities') }}
            </span>
          </div>

          <div
            v-if="Array.isArray(roomType.amenities) && roomType.amenities.length > 0"
            class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2.5 sm:gap-3"
          >
            <div
              v-for="(amenity, idx) in roomType.amenities"
              :key="idx"
              class="flex items-center gap-2.5 p-3 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-800 text-slate-800 dark:text-slate-200 text-xs font-semibold"
            >
              <div
                class="w-5 h-5 rounded-full bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shrink-0"
              >
                <Check class="w-3 h-3 stroke-[3]" />
              </div>
              <span class="truncate">{{ amenity }}</span>
            </div>
          </div>

          <div
            v-else
            class="p-8 text-center text-slate-400 dark:text-slate-500 text-xs font-medium"
          >
            {{
              languageStore.t(
                'no_amenities_specified',
                'No specific amenities have been registered for this room category.',
              )
            }}
          </div>
        </div>

        <!-- Timestamps & System Info Card -->
        <div
          class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 sm:p-6 shadow-xs"
        >
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
            <div class="flex items-center gap-3 p-3 rounded-xl bg-slate-50 dark:bg-slate-800/40">
              <Calendar class="w-4 h-4 text-slate-400 shrink-0" />
              <div>
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">
                  {{ languageStore.t('created_at', 'Created At') }}
                </p>
                <p class="font-semibold text-slate-700 dark:text-slate-300 mt-0.5">
                  {{ roomType.created_at || '—' }}
                </p>
              </div>
            </div>

            <div class="flex items-center gap-3 p-3 rounded-xl bg-slate-50 dark:bg-slate-800/40">
              <Clock class="w-4 h-4 text-slate-400 shrink-0" />
              <div>
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">
                  {{ languageStore.t('last_updated', 'Last Updated') }}
                </p>
                <p class="font-semibold text-slate-700 dark:text-slate-300 mt-0.5">
                  {{ roomType.updated_at || '—' }}
                </p>
              </div>
            </div>
          </div>
        </div>

        <!-- Bottom Return Link -->
        <div class="pt-2">
          <button
            type="button"
            @click="router.push('/admin/room-types')"
            class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 text-xs sm:text-sm font-bold transition cursor-pointer"
          >
            <ArrowLeft class="w-4 h-4" />
            <span>{{ languageStore.t('back_to_room_types', 'Back to Room Types List') }}</span>
          </button>
        </div>
      </div>

      <!-- Delete Modal -->
      <ConfirmDeleteModal
        :open="deleteModalOpen"
        @close="deleteModalOpen = false"
        @confirm="confirmDelete"
      />
    </div>
  </DashboardLayout>
</template>
