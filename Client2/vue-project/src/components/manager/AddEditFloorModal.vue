<script setup lang="ts">
import { ref, watch, computed } from 'vue'
import { X, Plus, Save, Hotel, AlertCircle, Loader2, CheckCircle2 } from 'lucide-vue-next'
import floorManagementService, { type Floor } from '@/services/manager/floorManagementService'
import { useLanguageStore } from '@/stores/language'

interface Props {
  isOpen: boolean
  floor?: Floor | null
  suggestedFloorNumber?: number
}

interface Emits {
  (e: 'close'): void
  (e: 'saved', floor: Floor): void
}

const props = withDefaults(defineProps<Props>(), {
  isOpen: false,
  floor: null,
  suggestedFloorNumber: 1,
})

const emit = defineEmits<Emits>()
const languageStore = useLanguageStore()

const isEditing = computed(() => Boolean(props.floor?.id))

const floorNumber = ref<number>(1)
const name = ref<string>('')
const description = ref<string>('')
const totalRooms = ref<number>(10)
const isActive = ref<boolean>(true)

const isSubmitting = ref<boolean>(false)
const errorMessage = ref<string | null>(null)

watch(
  () => [props.isOpen, props.floor],
  () => {
    if (props.isOpen) {
      errorMessage.value = null
      if (props.floor) {
        floorNumber.value = props.floor.floor_number
        name.value = props.floor.name || `Floor ${props.floor.floor_number}`
        description.value = props.floor.description || ''
        totalRooms.value = props.floor.total_rooms || props.floor.room_count || 10
        isActive.value = props.floor.is_active !== false
      } else {
        floorNumber.value = props.suggestedFloorNumber || 1
        name.value = `Floor ${props.suggestedFloorNumber || 1}`
        description.value = ''
        totalRooms.value = 10
        isActive.value = true
      }
    }
  },
  { immediate: true },
)

const handleSubmit = async () => {
  if (!name.value.trim()) {
    errorMessage.value = languageStore.t('floor_name_required', 'Floor name is required')
    return
  }

  if (floorNumber.value === null || floorNumber.value === undefined) {
    errorMessage.value = languageStore.t('floor_number_required', 'Floor number is required')
    return
  }

  isSubmitting.value = true
  errorMessage.value = null

  try {
    if (isEditing.value && props.floor?.id) {
      const updated = await floorManagementService.updateFloor(props.floor.id, {
        floor_number: Number(floorNumber.value),
        name: name.value.trim(),
        description: description.value.trim() || undefined,
        total_rooms: Number(totalRooms.value) || 0,
        is_active: isActive.value,
      })
      emit('saved', updated)
    } else {
      const created = await floorManagementService.createFloor({
        floor_number: Number(floorNumber.value),
        name: name.value.trim(),
        description: description.value.trim() || undefined,
        total_rooms: Number(totalRooms.value) || 0,
      })
      emit('saved', created)
    }
    emit('close')
  } catch (err: any) {
    console.error('Failed to save floor:', err)
    errorMessage.value =
      err?.response?.data?.message ||
      err?.response?.data?.errors?.floor_number?.[0] ||
      err?.response?.data?.errors?.name?.[0] ||
      err?.message ||
      'Failed to save floor'
  } finally {
    isSubmitting.value = false
  }
}
</script>

<template>
  <div
    v-if="isOpen"
    class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-3 sm:p-5 animate-in fade-in duration-200"
  >
    <div
      class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl w-full max-w-lg shadow-2xl overflow-hidden flex flex-col"
      @click.stop
    >
      <!-- Header -->
      <div
        class="p-5 sm:p-6 border-b border-slate-100 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-950/40 flex items-center justify-between"
      >
        <div class="flex items-center gap-3">
          <div
            class="w-10 h-10 rounded-2xl bg-blue-600/10 text-blue-600 dark:text-blue-400 flex items-center justify-center font-black"
          >
            <Hotel class="w-5 h-5" />
          </div>
          <div>
            <h3 class="text-base sm:text-lg font-black text-slate-900 dark:text-white">
              {{
                isEditing
                  ? languageStore.t('edit_floor', 'Edit Floor Level')
                  : languageStore.t('add_new_floor', 'Add New Floor')
              }}
            </h3>
            <p class="text-xs text-slate-500 dark:text-slate-400">
              {{
                languageStore.t(
                  'floor_modal_desc',
                  'Define building levels and guest service zone settings.',
                )
              }}
            </p>
          </div>
        </div>

        <button
          @click="emit('close')"
          class="w-9 h-9 rounded-xl flex items-center justify-center text-slate-400 hover:text-slate-700 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800 transition cursor-pointer"
        >
          <X class="w-5 h-5" />
        </button>
      </div>

      <!-- Form Body -->
      <form @submit.prevent="handleSubmit" class="p-6 space-y-4">
        <!-- Error Alert -->
        <div
          v-if="errorMessage"
          class="p-3.5 rounded-2xl bg-rose-50 dark:bg-rose-950/30 border border-rose-200 dark:border-rose-800 text-rose-700 dark:text-rose-300 text-xs flex items-start gap-2.5"
        >
          <AlertCircle class="w-4 h-4 text-rose-500 flex-shrink-0 mt-0.5" />
          <span>{{ errorMessage }}</span>
        </div>

        <!-- Floor Number & Name -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3.5">
          <div>
            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">
              {{ languageStore.t('floor_number', 'Floor #') }} <span class="text-rose-500">*</span>
            </label>
            <input
              v-model.number="floorNumber"
              type="number"
              min="0"
              max="200"
              required
              class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/80 text-slate-900 dark:text-white text-xs sm:text-sm font-black focus:ring-2 focus:ring-blue-500/40 outline-none transition"
              placeholder="1"
            />
          </div>

          <div class="sm:col-span-2">
            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">
              {{ languageStore.t('floor_name', 'Floor Name') }} <span class="text-rose-500">*</span>
            </label>
            <input
              v-model="name"
              type="text"
              required
              class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/80 text-slate-900 dark:text-white text-xs sm:text-sm font-bold focus:ring-2 focus:ring-blue-500/40 outline-none transition"
              :placeholder="
                languageStore.t('floor_name_placeholder', 'e.g. Ground Floor - Lobby & Bistro')
              "
            />
          </div>
        </div>

        <!-- Description -->
        <div>
          <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">
            {{ languageStore.t('description', 'Description') }}
          </label>
          <textarea
            v-model="description"
            rows="2"
            class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/80 text-slate-900 dark:text-white text-xs sm:text-sm focus:ring-2 focus:ring-blue-500/40 outline-none transition resize-none"
            :placeholder="
              languageStore.t(
                'description_placeholder',
                'Brief notes on this floor or department...',
              )
            "
          ></textarea>
        </div>

        <!-- Total Rooms Capacity & Status -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5 items-center">
          <div>
            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">
              {{ languageStore.t('total_rooms_capacity', 'Total Rooms Capacity') }}
            </label>
            <input
              v-model.number="totalRooms"
              type="number"
              min="0"
              class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/80 text-slate-900 dark:text-white text-xs sm:text-sm font-bold focus:ring-2 focus:ring-blue-500/40 outline-none transition"
              placeholder="10"
            />
          </div>

          <div class="pt-5 sm:pt-4">
            <label class="flex items-center gap-3 cursor-pointer">
              <input
                v-model="isActive"
                type="checkbox"
                class="w-4 h-4 rounded-md text-blue-600 focus:ring-blue-500 dark:bg-slate-800 border-slate-300 dark:border-slate-700"
              />
              <span class="text-xs font-bold text-slate-800 dark:text-slate-200">
                {{ languageStore.t('active_floor_status', 'Active & Operational') }}
              </span>
            </label>
            <p class="text-[11px] text-slate-400 dark:text-slate-500 pl-7 mt-0.5">
              {{
                languageStore.t('active_status_hint', 'Allow service tasks and waiter allocation')
              }}
            </p>
          </div>
        </div>

        <!-- Submit Footer -->
        <div
          class="pt-4 border-t border-slate-100 dark:border-slate-800 flex items-center justify-end gap-2.5"
        >
          <button
            type="button"
            @click="emit('close')"
            class="px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 font-bold text-xs transition cursor-pointer"
          >
            {{ languageStore.t('cancel', 'Cancel') }}
          </button>

          <button
            type="submit"
            :disabled="isSubmitting"
            class="px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs sm:text-sm shadow-md shadow-blue-500/20 transition flex items-center gap-2 cursor-pointer disabled:opacity-50"
          >
            <Loader2 v-if="isSubmitting" class="w-4 h-4 animate-spin" />
            <component :is="isEditing ? Save : Plus" v-else class="w-4 h-4 stroke-[2.5]" />
            <span>{{
              isEditing
                ? languageStore.t('save_changes', 'Save Changes')
                : languageStore.t('create_floor', 'Create Floor')
            }}</span>
          </button>
        </div>
      </form>
    </div>
  </div>
</template>
