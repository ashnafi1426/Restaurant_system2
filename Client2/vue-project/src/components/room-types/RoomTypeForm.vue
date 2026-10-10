<script setup lang="ts">
import { reactive, ref, watch, onMounted, computed } from 'vue'
import { Save, X, Plus, Loader2, AlertCircle, CheckCircle2 } from 'lucide-vue-next'
import { useHotelStore } from '@/stores/hotelStore'
import { roomTypeService } from '@/services/roomTypeService'
import type { RoomType } from '../../types/roomType'

const props = defineProps<{
  modelValue: RoomType
  isSubmitting?: boolean
  serverErrors?: Record<string, string[]>
}>()

const emit = defineEmits(['update:modelValue', 'submit', 'cancel'])

const hotelStore = useHotelStore()
const form = reactive<RoomType>({ ...props.modelValue })
const existingNames = ref<string[]>([])
const loadingExisting = ref(false)

const loadExistingTypes = async () => {
  try {
    loadingExisting.value = true
    const res = await roomTypeService.getRoomTypes({
      hotel_id: hotelStore.hotelId,
      per_page: 100,
    })
    const list = res.data?.data || []
    existingNames.value = list
      .filter((item: any) => !form.id || item.id !== form.id)
      .map((item: any) => String(item.name || '').trim())
  } catch (e) {
    console.error('[RoomTypeForm] Error loading existing types:', e)
  } finally {
    loadingExisting.value = false
  }
}

onMounted(() => {
  loadExistingTypes()
})

watch(
  () => hotelStore.hotelId,
  () => {
    loadExistingTypes()
  },
)

watch(
  () => props.modelValue,
  (val) => {
    if (val) {
      Object.assign(form, {
        ...val,
        base_price_per_night: Number(val.base_price_per_night) || 0,
        capacity: parseInt(String(val.capacity), 10) || 0,
        amenities: Array.isArray(val.amenities) ? [...val.amenities] : [],
        is_active: Boolean(val.is_active),
      })
    }
  },
  { immediate: true, deep: true },
)

watch(
  form,
  () =>
    emit('update:modelValue', {
      ...form,
      base_price_per_night: Number(form.base_price_per_night) || 0,
      capacity: parseInt(String(form.capacity), 10) || 0,
      is_active: Boolean(form.is_active),
    }),
  { deep: true },
)

const isNameTaken = computed(() => {
  const clean = String(form.name || '')
    .trim()
    .toLowerCase()
  if (!clean) return false
  return existingNames.value.some((n) => n.toLowerCase() === clean)
})

const newAmenity = ref('')
const addAmenity = () => {
  const trimmed = newAmenity.value.trim()
  if (trimmed && !form.amenities.includes(trimmed)) {
    form.amenities = [...form.amenities, trimmed]
  }
  newAmenity.value = ''
}
const removeAmenity = (index: number) => {
  form.amenities = form.amenities.filter((_, i) => i !== index)
}
const onAmenityKeydown = (e: KeyboardEvent) => {
  if (e.key === 'Enter') {
    e.preventDefault()
    addAmenity()
  }
}

const submit = () => {
  if (isNameTaken.value || props.isSubmitting) return
  emit('submit', {
    ...form,
    base_price_per_night: Number(form.base_price_per_night) || 0,
    capacity: parseInt(String(form.capacity), 10) || 0,
    is_active: Boolean(form.is_active),
  })
}
</script>

<template>
  <form @submit.prevent="submit" class="space-y-4 sm:space-y-5 md:space-y-6">
    <!-- Room Type Name Field -->
    <div>
      <label
        class="block mb-1.5 sm:mb-2 text-xs sm:text-sm font-semibold text-slate-900 dark:text-slate-100"
      >
        Room Type Name <span class="text-red-500">*</span>
      </label>
      <input
        v-model="form.name"
        type="text"
        placeholder="e.g., Deluxe Room, Executive Suite"
        class="w-full border rounded-xl px-3.5 py-2.5 text-xs sm:text-sm transition duration-200 bg-white dark:bg-slate-950 text-slate-900 dark:text-white"
        :class="[
          serverErrors?.name || isNameTaken
            ? 'border-red-400 bg-red-50/20 focus:ring-2 focus:ring-red-500'
            : form.name
              ? 'border-emerald-400 focus:ring-2 focus:ring-emerald-500'
              : 'border-slate-300 dark:border-slate-800 focus:ring-2 focus:ring-blue-500',
        ]"
        required
      />
      <!-- Name Live Feedback -->
      <div v-if="serverErrors?.name" class="text-red-500 text-xs mt-1.5 flex items-center gap-1">
        <AlertCircle class="w-3.5 h-3.5 flex-shrink-0" />
        <span>{{ serverErrors.name[0] }}</span>
      </div>
      <div
        v-else-if="isNameTaken"
        class="text-red-500 text-xs mt-1.5 flex items-center gap-1 font-medium"
      >
        <AlertCircle class="w-3.5 h-3.5 flex-shrink-0" />
        <span
          >A room type named "{{ form.name }}" already exists in {{ hotelStore.hotelName }}.</span
        >
      </div>
      <div
        v-else-if="form.name && !loadingExisting"
        class="text-emerald-600 dark:text-emerald-400 text-xs mt-1.5 flex items-center gap-1 font-medium"
      >
        <CheckCircle2 class="w-3.5 h-3.5 flex-shrink-0" />
        <span>Name is available for {{ hotelStore.hotelName }}.</span>
      </div>
    </div>

    <!-- Description Field -->
    <div>
      <label
        class="block mb-1.5 sm:mb-2 text-xs sm:text-sm font-semibold text-slate-900 dark:text-slate-100"
      >
        Description <span class="text-slate-400 text-xs">(Optional)</span>
      </label>
      <textarea
        v-model="form.description"
        rows="3"
        placeholder="Describe the room type features, views, amenities, etc..."
        class="w-full border border-slate-300 dark:border-slate-800 bg-white dark:bg-slate-950 text-slate-900 dark:text-white rounded-xl px-3.5 py-2.5 text-xs sm:text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200 resize-none"
      />
    </div>

    <!-- Price and Capacity Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4 md:gap-5">
      <div>
        <label
          class="block mb-1.5 sm:mb-2 text-xs sm:text-sm font-semibold text-slate-900 dark:text-slate-100"
        >
          Price Per Night ({{ hotelStore.currency || 'ETB' }}) <span class="text-red-500">*</span>
        </label>
        <div class="relative">
          <input
            type="number"
            v-model.number="form.base_price_per_night"
            placeholder="e.g., 2500"
            class="w-full border rounded-xl px-3.5 py-2.5 text-xs sm:text-sm transition duration-200 bg-white dark:bg-slate-950 text-slate-900 dark:text-white"
            :class="[
              serverErrors?.base_price_per_night
                ? 'border-red-400 focus:ring-red-500'
                : 'border-slate-300 dark:border-slate-800 focus:ring-blue-500',
            ]"
            min="0"
            step="any"
            required
          />
        </div>
        <p
          v-if="serverErrors?.base_price_per_night"
          class="text-red-500 text-xs mt-1 flex items-center gap-1"
        >
          <AlertCircle class="w-3.5 h-3.5 flex-shrink-0" />
          <span>{{ serverErrors.base_price_per_night[0] }}</span>
        </p>
      </div>

      <div>
        <label
          class="block mb-1.5 sm:mb-2 text-xs sm:text-sm font-semibold text-slate-900 dark:text-slate-100"
        >
          Capacity (Guests) <span class="text-red-500">*</span>
        </label>
        <input
          type="number"
          v-model.number="form.capacity"
          placeholder="e.g., 2"
          class="w-full border rounded-xl px-3.5 py-2.5 text-xs sm:text-sm transition duration-200 bg-white dark:bg-slate-950 text-slate-900 dark:text-white"
          :class="[
            serverErrors?.capacity
              ? 'border-red-400 focus:ring-red-500'
              : 'border-slate-300 dark:border-slate-800 focus:ring-blue-500',
          ]"
          min="1"
          max="20"
          required
        />
        <p v-if="serverErrors?.capacity" class="text-red-500 text-xs mt-1 flex items-center gap-1">
          <AlertCircle class="w-3.5 h-3.5 flex-shrink-0" />
          <span>{{ serverErrors.capacity[0] }}</span>
        </p>
      </div>
    </div>

    <!-- Amenities Field -->
    <div>
      <label
        class="block mb-1.5 sm:mb-2 text-xs sm:text-sm font-semibold text-slate-900 dark:text-slate-100"
      >
        Amenities <span class="text-slate-400 text-xs">(Optional)</span>
      </label>

      <div class="flex flex-wrap gap-1.5 mb-2" v-if="form.amenities.length">
        <span
          v-for="(amenity, index) in form.amenities"
          :key="index"
          class="inline-flex items-center gap-1 px-2.5 py-1 bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300 text-xs font-medium rounded-lg"
        >
          {{ amenity }}
          <button
            type="button"
            @click="removeAmenity(index)"
            class="ml-0.5 text-blue-500 hover:text-red-500 transition-colors cursor-pointer"
          >
            <X class="w-3 h-3" />
          </button>
        </span>
      </div>

      <div class="flex gap-2">
        <input
          v-model="newAmenity"
          @keydown="onAmenityKeydown"
          type="text"
          placeholder="e.g., WiFi, Flat Screen TV, Mini Bar, Jacuzzi..."
          class="flex-1 border border-slate-300 dark:border-slate-800 bg-white dark:bg-slate-950 text-slate-900 dark:text-white rounded-xl px-3.5 py-2.5 text-xs sm:text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200"
        />
        <button
          type="button"
          @click="addAmenity"
          class="px-3 py-2.5 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 rounded-xl transition duration-200 cursor-pointer"
        >
          <Plus class="w-4 h-4" />
        </button>
      </div>
      <p class="text-xs text-slate-400 mt-1">Press Enter or click + to add each amenity</p>
    </div>

    <!-- Active Room Type Checkbox -->
    <div
      class="flex items-start gap-2 sm:gap-3 p-3 sm:p-4 bg-slate-50 dark:bg-slate-950/60 border border-slate-200 dark:border-slate-800 rounded-xl"
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
          Active Room Type
        </label>
        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
          Available for new room creation and reservations
        </p>
      </div>
    </div>

    <!-- Submit and Cancel Buttons -->
    <div class="flex flex-col-reverse sm:flex-row gap-2 sm:gap-3 pt-2 sm:pt-4">
      <button
        type="button"
        @click="$emit('cancel')"
        :disabled="isSubmitting"
        class="px-4 sm:px-6 py-2 sm:py-2.5 border border-slate-300 dark:border-slate-800 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-800 text-xs sm:text-sm font-medium text-slate-700 dark:text-slate-300 transition duration-200 cursor-pointer disabled:opacity-50"
      >
        Cancel
      </button>
      <button
        type="submit"
        :disabled="!form.name || isNameTaken || isSubmitting"
        class="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 disabled:bg-slate-300 dark:disabled:bg-slate-800 disabled:cursor-not-allowed text-white text-xs sm:text-sm font-bold rounded-xl transition duration-200 inline-flex items-center gap-2 cursor-pointer shadow-md shadow-blue-600/20"
      >
        <Loader2 v-if="isSubmitting" class="w-4 h-4 animate-spin" />
        <Save v-else class="w-4 h-4 stroke-[2.5]" />
        <span v-if="isSubmitting">Saving Room Type...</span>
        <span v-else-if="isNameTaken">Room Type Name Taken</span>
        <span v-else>Save Room Type</span>
      </button>
    </div>
  </form>
</template>
