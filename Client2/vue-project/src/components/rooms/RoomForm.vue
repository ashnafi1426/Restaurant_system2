<script setup lang="ts">
import { reactive, onMounted, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import axios from '@/services/axios'
import { useRoomTypeStore } from '../../stores/roomType'
import { useHotelStore } from '@/stores/hotelStore'
import { useLanguageStore } from '@/stores/language'
import { roomService } from '../../services/roomService'
import SearchableSelect from '@/components/common/SearchableSelect.vue'
import { 
  CheckCircle2, 
  AlertCircle, 
  Loader2, 
  Sparkles, 
  Plus, 
  Building2,
  Layers,
  BedDouble
} from 'lucide-vue-next'

const props = defineProps<{
  isSubmitting?: boolean
  serverErrors?: Record<string, string[]>
  initialData?: any
}>()

const emit = defineEmits(['submit', 'cancel'])

const router = useRouter()
const roomTypeStore = useRoomTypeStore()
const hotelStore = useHotelStore()
const languageStore = useLanguageStore()

const roomTypes = ref<any[]>([])
const floors = ref<any[]>([])
const existingRoomNumbers = ref<string[]>([])
const loadingRoomTypes = ref(false)
const loadingFloors = ref(false)
const loadingRoomNumbers = ref(false)

const showAddFloorInline = ref(false)
const newFloorNumber = ref<number | null>(null)
const newFloorName = ref('')
const isCreatingFloor = ref(false)
const floorCreateError = ref<string | null>(null)

const form = reactive({
  room_number: '',
  room_type_id: '',
  floor_id: '',
  floor: null as number | null,
  description: '',
  status: 'available',
  is_active: true,
})

const populateInitial = (data: any) => {
  if (!data) return
  if (data.room_number) form.room_number = String(data.room_number)
  if (data.room_type_id) form.room_type_id = String(data.room_type_id)
  if (data.floor_id) form.floor_id = String(data.floor_id)
  if (data.floor !== undefined && data.floor !== null) form.floor = Number(data.floor)
  if (data.description) form.description = String(data.description)
  if (data.status) form.status = String(data.status)
  if (data.is_active !== undefined) form.is_active = Boolean(data.is_active)

  if (!form.floor_id && form.floor !== null && floors.value.length > 0) {
    const matched = floors.value.find((f: any) => Number(f.floor_number) === Number(form.floor))
    if (matched) form.floor_id = String(matched.id)
  }
}

const loadRoomTypes = async (activeHotelId?: string) => {
  loadingRoomTypes.value = true
  try {
    await roomTypeStore.fetchRoomTypes({
      hotel_id: activeHotelId || undefined,
      per_page: 50,
      is_active: 1,
    })
    roomTypes.value = roomTypeStore.roomTypes || []
  } catch (e) {
    console.error('[RoomForm] Error fetching room types:', e)
  } finally {
    loadingRoomTypes.value = false
  }
}

const loadFloors = async (activeHotelId?: string) => {
  loadingFloors.value = true
  try {
    const floorParams = {
      is_active: 1,
      per_page: 50,
      hotel_id: activeHotelId || undefined,
    }
    let res: any
    try {
      res = await axios.get('/floors', { params: floorParams })
    } catch {
      res = await axios.get('/manager/floors', { params: floorParams })
    }
    const rawFloors = res.data?.data?.data || res.data?.data || res.data || []
    floors.value = Array.isArray(rawFloors) ? rawFloors : []

    // Auto-match or auto-select floor
    if (form.floor_id) {
      // Already selected
    } else if (form.floor !== null && floors.value.length > 0) {
      const matched = floors.value.find((f: any) => Number(f.floor_number) === Number(form.floor))
      if (matched) form.floor_id = String(matched.id)
    } else if (floors.value.length === 1) {
      form.floor_id = String(floors.value[0].id)
      form.floor = Number(floors.value[0].floor_number)
    }
  } catch (err) {
    console.error('[RoomForm] Error fetching floors:', err)
    floors.value = []
  } finally {
    loadingFloors.value = false
  }
}

const loadExistingRoomNumbers = async (activeHotelId?: string) => {
  loadingRoomNumbers.value = true
  try {
    const response = await roomService.getRooms({
      hotel_id: activeHotelId || undefined,
      per_page: 50,
    })
    const rooms = response.data?.data || response.data || []
    existingRoomNumbers.value = Array.isArray(rooms)
      ? rooms.map((room: any) => String(room.room_number || '').trim()).filter(Boolean)
      : []
  } catch (err) {
    console.error('[RoomForm] Error fetching existing rooms:', err)
  } finally {
    loadingRoomNumbers.value = false
  }
}

const loadHotelData = () => {
  const activeHotelId = hotelStore.hotelId

  // Fire parallel requests so each dropdown unlocks as soon as its data is ready
  Promise.allSettled([
    loadRoomTypes(activeHotelId),
    loadFloors(activeHotelId),
    loadExistingRoomNumbers(activeHotelId),
  ]).then(() => {
    if (props.initialData) {
      populateInitial(props.initialData)
    }
  })
}

const quickCreateFloor = async () => {
  if (!newFloorNumber.value || newFloorNumber.value <= 0) return
  isCreatingFloor.value = true
  floorCreateError.value = null
  try {
    const activeHotelId = hotelStore.hotelId
    const payload = {
      floor_number: Number(newFloorNumber.value),
      name: newFloorName.value?.trim() || `Floor ${newFloorNumber.value}`,
      hotel_id: activeHotelId || undefined,
    }
    let res: any
    try {
      res = await axios.post('/floors', payload)
    } catch {
      res = await axios.post('/manager/floors', payload)
    }
    const created = res.data?.data || res.data
    if (created && created.id) {
      floors.value.push(created)
      floors.value.sort((a, b) => Number(a.floor_number) - Number(b.floor_number))
      form.floor_id = String(created.id)
      form.floor = Number(created.floor_number)
      showAddFloorInline.value = false
      newFloorNumber.value = null
      newFloorName.value = ''
    }
  } catch (err: any) {
    console.error('[RoomForm] Quick create floor failed:', err)
    floorCreateError.value = err.response?.data?.message || err.message || 'Failed to create floor'
  } finally {
    isCreatingFloor.value = false
  }
}

onMounted(() => {
  loadHotelData()
})

watch(() => hotelStore.hotelId, () => {
  // Invalidate cache when hotel changes
  roomTypeStore.invalidateCache()
  loadHotelData()
})

watch(() => props.initialData, (newVal) => {
  if (newVal) {
    populateInitial(newVal)
  }
}, { deep: true })

const isRoomNumberTaken = (roomNumber: string) => {
  const clean = String(roomNumber || '').trim()
  if (!clean) return false
  if (props.initialData?.room_number && clean.toLowerCase() === String(props.initialData.room_number).trim().toLowerCase()) {
    return false
  }
  return existingRoomNumbers.value.some((r) => r.toLowerCase() === clean.toLowerCase())
}

const suggestNextRoomNumber = () => {
  const numbers = existingRoomNumbers.value
    .map((num) => parseInt(num.trim(), 10))
    .filter((num) => !isNaN(num) && num > 0)
    .sort((a, b) => a - b)

  if (numbers.length === 0) {
    return '101'
  }

  const hasHundreds = numbers.some((n) => n >= 100)

  if (hasHundreds) {
    let candidate = 101
    while (numbers.includes(candidate)) {
      candidate++
    }
    return String(candidate)
  } else {
    let candidate = 1
    while (numbers.includes(candidate)) {
      candidate++
    }
    return String(candidate)
  }
}

const autoFillRoomNumber = () => {
  form.room_number = suggestNextRoomNumber()
}

const save = () => {
  if (isRoomNumberTaken(form.room_number) || !form.floor_id) {
    return
  }

  const selectedFloor = floors.value.find((f) => String(f.id) === String(form.floor_id))

  // Notice: hotel_id is strictly resolved on backend via TenantContext/session, not manually picked
  emit('submit', {
    room_number: form.room_number.trim(),
    floor_id: form.floor_id,
    floor: selectedFloor ? Number(selectedFloor.floor_number) : form.floor,
    room_type_id: form.room_type_id,
    status: form.status,
    description: form.description?.trim() || '',
    is_active: form.is_active,
  })
}
</script>

<template>
  <form @submit.prevent="save" class="space-y-4 sm:space-y-5 md:space-y-6">
    <!-- Notice for No Floors in Active Hotel -->
    <div
      v-if="floors.length === 0 && !loadingFloors"
      class="p-4 bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800 rounded-xl flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 text-amber-800 dark:text-amber-300 text-xs sm:text-sm"
    >
      <div class="flex items-center gap-2">
        <AlertCircle class="w-5 h-5 text-amber-600 flex-shrink-0" />
        <span>
          No floors found for <strong>{{ hotelStore.hotelName }}</strong>. Every room must belong to an existing floor for room service & waiter assignments.
        </span>
      </div>
      <div class="flex items-center gap-2">
        <button
          type="button"
          @click="showAddFloorInline = true"
          class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-amber-600 hover:bg-amber-700 text-white font-medium rounded-lg text-xs whitespace-nowrap cursor-pointer transition-colors shadow-sm"
        >
          <Plus class="w-3.5 h-3.5" />
          Add Floor
        </button>
        <button
          type="button"
          @click="router.push('/manager/floor-assignment')"
          class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white dark:bg-slate-800 border border-amber-300 dark:border-amber-700 text-amber-800 dark:text-amber-200 hover:bg-amber-100 rounded-lg text-xs whitespace-nowrap cursor-pointer transition-colors shadow-sm"
        >
          Manage Floors
        </button>
      </div>
    </div>

    <!-- Notice for No Room Types in Active Hotel -->
    <div
      v-if="roomTypes.length === 0 && !loadingRoomTypes"
      class="p-4 bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800 rounded-xl flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 text-amber-800 dark:text-amber-300 text-xs sm:text-sm"
    >
      <div class="flex items-center gap-2">
        <AlertCircle class="w-5 h-5 text-amber-600 flex-shrink-0" />
        <span>
          No room types found for <strong>{{ hotelStore.hotelName }}</strong>. You must create a room type before adding rooms.
        </span>
      </div>
      <button
        type="button"
        @click="router.push('/admin/room-types/create')"
        class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-amber-600 hover:bg-amber-700 text-white font-medium rounded-lg text-xs whitespace-nowrap cursor-pointer transition-colors shadow-sm"
      >
        <Plus class="w-3.5 h-3.5" />
        Create Room Type
      </button>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4 md:gap-5">
      <!-- Room Number Field -->
      <div>
        <label class="block mb-1.5 sm:mb-2 text-xs sm:text-sm font-semibold text-slate-900 dark:text-slate-100">
          {{ languageStore.t('room_number', 'Room Number') }} <span class="text-red-500">*</span>
        </label>
        <div class="flex gap-2">
          <input
            v-model="form.room_number"
            type="text"
            placeholder="e.g., 101"
            class="flex-1 border rounded-lg px-3 sm:px-3.5 py-2 sm:py-2.5 text-xs sm:text-sm transition duration-200 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100"
            :class="[
              serverErrors?.room_number || (form.room_number && isRoomNumberTaken(form.room_number))
                ? 'border-red-400 bg-red-50/30 dark:bg-red-950/20 focus:ring-2 focus:ring-red-500'
                : form.room_number
                ? 'border-emerald-400 bg-emerald-50/20 dark:bg-emerald-950/20 focus:ring-2 focus:ring-emerald-500'
                : 'border-slate-300 dark:border-slate-700 focus:ring-2 focus:ring-blue-500'
            ]"
            required
          />
          <button
            type="button"
            @click="autoFillRoomNumber"
            class="px-3 sm:px-4 py-2 sm:py-2.5 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 rounded-lg text-xs sm:text-sm font-medium text-slate-700 dark:text-slate-300 transition-colors duration-200 cursor-pointer inline-flex items-center gap-1.5 border border-slate-200 dark:border-slate-700"
            :title="languageStore.t('auto_fill', 'Auto-fill with next available room number')"
          >
            <Sparkles class="w-3.5 h-3.5 text-blue-600 dark:text-blue-400" />
            <span class="hidden sm:inline">{{ languageStore.t('auto_fill', 'Auto') }}</span>
          </button>
        </div>

        <!-- Room Number Live Status & Errors -->
        <div v-if="serverErrors?.room_number" class="text-red-500 text-xs mt-1.5 flex items-center gap-1">
          <AlertCircle class="w-3.5 h-3.5 flex-shrink-0" />
          <span>{{ serverErrors.room_number[0] }}</span>
        </div>
        <div v-else-if="form.room_number && isRoomNumberTaken(form.room_number)" class="text-red-500 text-xs mt-1.5 flex items-center gap-1 font-medium">
          <AlertCircle class="w-3.5 h-3.5 flex-shrink-0" />
          <span>Room #{{ form.room_number }} is already taken in {{ hotelStore.hotelName }}.</span>
        </div>
        <div v-else-if="form.room_number" class="text-emerald-600 dark:text-emerald-400 text-xs mt-1.5 flex items-center gap-1 font-medium">
          <CheckCircle2 class="w-3.5 h-3.5 flex-shrink-0" />
          <span>Room #{{ form.room_number }} is available in {{ hotelStore.hotelName }}.</span>
        </div>
        <p class="text-slate-500 text-xs mt-1 flex items-center gap-1">
          <span>{{ languageStore.t('next_available', 'Next available:') }}</span>
          <strong class="text-slate-700 dark:text-slate-300">#{{ suggestNextRoomNumber() }}</strong>
        </p>
      </div>

      <!-- Floor Field -->
      <div>
        <div class="flex items-center justify-between mb-1.5 sm:mb-2">
          <label class="block text-xs sm:text-sm font-semibold text-slate-900 dark:text-slate-100">
            {{ languageStore.t('floor', 'Floor') }} <span class="text-red-500">*</span>
          </label>
          <button
            v-if="!showAddFloorInline"
            type="button"
            @click="showAddFloorInline = true"
            class="text-xs text-blue-600 dark:text-blue-400 hover:underline inline-flex items-center gap-1 cursor-pointer font-medium"
          >
            <Plus class="w-3.5 h-3.5" />
            <span>{{ languageStore.t('new_floor', 'New Floor') }}</span>
          </button>
        </div>

        <!-- Inline quick add floor -->
        <div v-if="showAddFloorInline" class="mb-3 p-3 bg-blue-50/70 dark:bg-blue-950/40 border border-blue-200 dark:border-blue-800 rounded-xl space-y-2">
          <div class="flex items-center justify-between text-xs font-semibold text-blue-900 dark:text-blue-200">
            <span>Quick Add Floor</span>
            <button type="button" @click="showAddFloorInline = false" class="text-slate-400 hover:text-slate-600 cursor-pointer">✕</button>
          </div>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
            <input
              v-model.number="newFloorNumber"
              type="number"
              min="0"
              placeholder="Floor # (e.g. 1, 2)"
              class="border border-slate-300 dark:border-slate-700 rounded-lg px-2.5 py-1.5 text-xs bg-white dark:bg-slate-900 text-slate-900 dark:text-white"
            />
            <input
              v-model="newFloorName"
              type="text"
              placeholder="Floor Name (optional)"
              class="border border-slate-300 dark:border-slate-700 rounded-lg px-2.5 py-1.5 text-xs bg-white dark:bg-slate-900 text-slate-900 dark:text-white"
            />
          </div>
          <div v-if="floorCreateError" class="text-xs text-red-500">{{ floorCreateError }}</div>
          <div class="flex justify-end gap-2 pt-1">
            <button
              type="button"
              @click="showAddFloorInline = false"
              class="px-2.5 py-1 text-xs text-slate-600 dark:text-slate-400 hover:text-slate-800 cursor-pointer"
            >
              Cancel
            </button>
            <button
              type="button"
              @click="quickCreateFloor"
              :disabled="!newFloorNumber || isCreatingFloor"
              class="px-3 py-1 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-xs font-medium inline-flex items-center gap-1 disabled:opacity-50 cursor-pointer"
            >
              <Loader2 v-if="isCreatingFloor" class="w-3 h-3 animate-spin" />
              <span>{{ isCreatingFloor ? 'Adding...' : 'Create & Select' }}</span>
            </button>
          </div>
        </div>

        <SearchableSelect
          v-model="form.floor_id"
          :options="floors"
          label-key="name"
          value-key="id"
          :icon="Layers"
          item-type="floor"
          :placeholder="loadingFloors ? 'Loading existing floors...' : languageStore.t('select_floor', 'Select an existing floor')"
          search-placeholder="Search floors by name or number..."
          empty-text="No matching floors found"
          :disabled="loadingFloors"
          :has-error="Boolean(serverErrors?.floor_id)"
          :format-option-label="(fl) => fl.name ? `${fl.name} (Floor ${fl.floor_number})` : `Floor ${fl.floor_number}`"
          @change="(opt) => { if (opt) form.floor = Number(opt.floor_number); }"
        >
          <template #option="{ option }">
            <div class="flex items-center justify-between w-full min-w-0">
              <div class="flex items-center gap-2 min-w-0">
                <div class="w-6 h-6 rounded-md bg-blue-100 dark:bg-blue-950/60 text-blue-700 dark:text-blue-300 flex items-center justify-center font-bold text-[11px] flex-shrink-0">
                  {{ option.floor_number }}
                </div>
                <div class="min-w-0">
                  <div class="font-semibold truncate text-slate-800 dark:text-slate-100">
                    {{ option.name || `Floor ${option.floor_number}` }}
                  </div>
                  <div class="text-[10px] text-slate-400 dark:text-slate-500 truncate">
                    Floor Level {{ option.floor_number }}
                  </div>
                </div>
              </div>
              <span
                v-if="option.total_rooms !== undefined && option.total_rooms > 0"
                class="text-[10px] px-1.5 py-0.5 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 ml-2 font-medium whitespace-nowrap"
              >
                {{ option.total_rooms }} {{ option.total_rooms === 1 ? 'room' : 'rooms' }}
              </span>
            </div>
          </template>
        </SearchableSelect>

        <p v-if="serverErrors?.floor_id" class="text-red-500 text-xs mt-1 flex items-center gap-1">
          <AlertCircle class="w-3.5 h-3.5 flex-shrink-0" />
          <span>{{ serverErrors.floor_id[0] }}</span>
        </p>
        <p v-else-if="!form.floor_id && floors.length > 0" class="text-slate-500 text-xs mt-1">
          Search or select an existing building floor
        </p>
      </div>

      <!-- Room Type Field -->
      <div>
        <label class="block mb-1.5 sm:mb-2 text-xs sm:text-sm font-semibold text-slate-900 dark:text-slate-100">
          {{ languageStore.t('room_type', 'Room Type') }} <span class="text-red-500">*</span>
        </label>
        <SearchableSelect
          v-model="form.room_type_id"
          :options="roomTypes"
          label-key="name"
          value-key="id"
          :icon="BedDouble"
          item-type="room type"
          :placeholder="loadingRoomTypes ? 'Loading room types...' : languageStore.t('select_room_type', 'Select Room Type')"
          search-placeholder="Search room types or price..."
          empty-text="No room types matching search"
          :disabled="loadingRoomTypes"
          :has-error="Boolean(serverErrors?.room_type_id)"
          :format-option-label="(type) => `${type.name} - ${parseFloat(type.base_price_per_night || 0).toLocaleString('en-US')} ${hotelStore.currency || 'ETB'}/night`"
        >
          <template #option="{ option }">
            <div class="flex items-center justify-between w-full min-w-0">
              <div class="flex items-center gap-2 min-w-0">
                <div class="w-6 h-6 rounded-md bg-emerald-100 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 flex items-center justify-center flex-shrink-0">
                  <BedDouble class="w-3.5 h-3.5" />
                </div>
                <div class="min-w-0">
                  <div class="font-semibold truncate text-slate-800 dark:text-slate-100">
                    {{ option.name }}
                  </div>
                  <div class="text-[10px] text-slate-400 dark:text-slate-500 truncate">
                    Max {{ option.max_occupancy || 2 }} guests
                  </div>
                </div>
              </div>
              <span class="text-xs font-bold text-emerald-600 dark:text-emerald-400 ml-2 whitespace-nowrap">
                {{ parseFloat(option.base_price_per_night || 0).toLocaleString('en-US') }} {{ hotelStore.currency || 'ETB' }}<span class="text-[10px] font-normal text-slate-400">/night</span>
              </span>
            </div>
          </template>
        </SearchableSelect>
        <p v-if="serverErrors?.room_type_id" class="text-red-500 text-xs mt-1 flex items-center gap-1">
          <AlertCircle class="w-3.5 h-3.5 flex-shrink-0" />
          <span>{{ serverErrors.room_type_id[0] }}</span>
        </p>
        <p v-else-if="!form.room_type_id && roomTypes.length > 0" class="text-slate-500 text-xs mt-1">
          {{ languageStore.t('select_room_type_prompt', 'Please select a room type') }}
        </p>
      </div>

      <!-- Status Field -->
      <div>
        <label class="block mb-1.5 sm:mb-2 text-xs sm:text-sm font-semibold text-slate-900 dark:text-slate-100">
          {{ languageStore.t('status', 'Status') }} <span class="text-red-500">*</span>
        </label>
        <select
          v-model="form.status"
          class="w-full border border-slate-300 dark:border-slate-700 rounded-lg px-3 sm:px-3.5 py-2 sm:py-2.5 text-xs sm:text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100"
          required
        >
          <option value="available">✓ {{ languageStore.t('available', 'Available') }}</option>
          <option value="reserved">🔒 {{ languageStore.t('reserved', 'Reserved') }}</option>
          <option value="occupied">👤 {{ languageStore.t('occupied', 'Occupied') }}</option>
          <option value="cleaning">🧹 {{ languageStore.t('cleaning', 'Cleaning') }}</option>
          <option value="maintenance">🔧 {{ languageStore.t('maintenance', 'Maintenance') }}</option>
        </select>
      </div>
    </div>

    <!-- Description Field -->
    <div>
      <label class="block mb-1.5 sm:mb-2 text-xs sm:text-sm font-semibold text-slate-900 dark:text-slate-100">
        {{ languageStore.t('description', 'Description') }} <span class="text-slate-400 text-xs">({{ languageStore.t('optional', 'Optional') }})</span>
      </label>
      <textarea
        v-model="form.description"
        rows="3"
        :placeholder="languageStore.t('special_requests_placeholder', 'Add room details, amenities, special features, etc...')"
        class="w-full border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 rounded-lg px-3 sm:px-3.5 py-2 sm:py-2.5 text-xs sm:text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200 resize-none"
      />
    </div>

    <!-- Active Room Checkbox -->
    <div
      class="flex items-start gap-2 sm:gap-3 p-3 sm:p-4 bg-slate-50 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-800 rounded-lg"
    >
      <input
        type="checkbox"
        v-model="form.is_active"
        id="active-checkbox"
        class="w-4 h-4 sm:w-5 sm:h-5 mt-0.5 sm:mt-0 accent-blue-600 cursor-pointer"
      />
      <div class="flex-1 min-w-0">
        <label
          for="active-checkbox"
          class="text-xs sm:text-sm font-semibold text-slate-900 dark:text-slate-100 cursor-pointer"
        >
          {{ languageStore.t('active_room_label', 'Active Room') }}
        </label>
        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">{{ languageStore.t('active_room_desc', 'Room will be available for bookings and occupancy') }}</p>
      </div>
    </div>

    <!-- Action Buttons -->
    <div class="flex flex-col-reverse sm:flex-row gap-2 sm:gap-3 pt-2 sm:pt-4">
      <button
        type="button"
        @click="$emit('cancel')"
        :disabled="isSubmitting"
        class="px-4 sm:px-6 py-2 sm:py-2.5 border border-slate-300 dark:border-slate-700 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-800 text-xs sm:text-sm font-medium text-slate-700 dark:text-slate-300 transition-colors duration-200 cursor-pointer disabled:opacity-50"
      >
        {{ languageStore.t('cancel', 'Cancel') }}
      </button>
      <button
        type="submit"
        :disabled="!form.room_number || !form.room_type_id || !form.floor_id || isRoomNumberTaken(form.room_number) || isSubmitting"
        class="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 disabled:bg-slate-300 dark:disabled:bg-slate-800 disabled:text-slate-500 disabled:cursor-not-allowed text-white text-xs sm:text-sm font-semibold rounded-lg transition-colors duration-200 cursor-pointer inline-flex items-center gap-2 shadow-sm"
      >
        <Loader2 v-if="isSubmitting" class="w-4 h-4 animate-spin" />
        <span v-if="isSubmitting">{{ languageStore.t('saving', 'Saving Room...') }}</span>
        <span v-else-if="isRoomNumberTaken(form.room_number)">❌ {{ languageStore.t('room_taken', 'Room Number Taken') }}</span>
        <span v-else>💾 {{ languageStore.t('save_room', 'Save Room') }}</span>
      </button>
    </div>

    <!-- Loading State Indicator -->
    <div v-if="loadingRooms" class="text-center text-slate-500 text-xs sm:text-sm py-2">
      <span class="inline-flex items-center gap-2">
        <Loader2 class="w-4 h-4 animate-spin text-blue-600" />
        {{ languageStore.t('loading_rooms', 'Loading existing rooms for this hotel...') }}
      </span>
    </div>

    <!-- Existing Rooms Badge List -->
    <div
      v-if="existingRoomNumbers.length > 0"
      class="p-3 sm:p-4 bg-blue-50/70 dark:bg-blue-950/20 rounded-xl border border-blue-200/80 dark:border-blue-900/40"
    >
      <div class="flex items-center gap-2 mb-2">
        <Building2 class="w-4 h-4 text-blue-700 dark:text-blue-400" />
        <p class="text-xs sm:text-sm font-semibold text-blue-950 dark:text-blue-300">
          {{ languageStore.t('existing_rooms_label', 'Existing Rooms in') }} {{ hotelStore.hotelName }} ({{ existingRoomNumbers.length }}):
        </p>
      </div>
      <div class="flex flex-wrap gap-1.5 max-h-36 overflow-y-auto">
        <span
          v-for="num in existingRoomNumbers"
          :key="num"
          class="px-2 py-0.5 rounded-md text-xs font-medium border"
          :class="[
            num.toLowerCase() === form.room_number.trim().toLowerCase()
              ? 'bg-red-100 text-red-800 border-red-300 dark:bg-red-900/40 dark:text-red-300 dark:border-red-800 font-bold'
              : 'bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-300 border-slate-200 dark:border-slate-800'
          ]"
        >
          #{{ num }}
        </span>
      </div>
    </div>
    <div
      v-else-if="!loadingRooms"
      class="p-3 sm:p-4 bg-slate-50 dark:bg-slate-900/40 rounded-xl border border-slate-200 dark:border-slate-800 text-xs sm:text-sm text-slate-500"
    >
      📍 No rooms exist in <strong>{{ hotelStore.hotelName }}</strong> yet. This will be the first room!
    </div>
  </form>
</template>
