<template>
  <Teleport to="body">
    <Transition name="fade">
      <div
        class="fixed inset-0 bg-black/60 backdrop-blur-xs z-50 flex items-center justify-center p-4"
        @click.self="$emit('close')"
      >
        <div
          class="bg-white dark:bg-[#0b1527] border border-slate-200 dark:border-slate-800 rounded-2xl shadow-2xl max-w-lg w-full max-h-[90vh] overflow-y-auto text-slate-900 dark:text-white"
        >
          <div
            class="px-6 py-4 border-b border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50 flex items-center justify-between"
          >
            <div>
              <h2 class="text-lg font-bold text-slate-900 dark:text-white">
                {{ isEdit ? 'Edit Restaurant Table' : 'Add New Table' }}
              </h2>
              <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                {{
                  isEdit
                    ? 'Update table configuration and section assignment'
                    : 'Configure a new dining table and generate QR code'
                }}
              </p>
            </div>
            <button
              type="button"
              @click="$emit('close')"
              class="p-2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition"
            >
              <X class="w-5 h-5" />
            </button>
          </div>

          <form @submit.prevent="handleSubmit" class="p-6 space-y-4">
            <div>
              <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                Table Number <span class="text-rose-500">*</span>
              </label>
              <input
                v-model="formData.table_number"
                type="text"
                required
                placeholder="e.g., T-10, A-1, 101"
                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-900 text-slate-900 dark:text-white text-xs sm:text-sm focus:ring-2 focus:ring-blue-500 outline-none"
                :class="{ 'border-rose-500 dark:border-rose-500': errors.table_number }"
              />
              <p v-if="errors.table_number" class="text-rose-500 text-[11px] mt-1">
                {{ errors.table_number }}
              </p>
            </div>

            <div>
              <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                Display Name (Optional)
              </label>
              <input
                v-model="formData.table_name"
                type="text"
                placeholder="e.g., Window Booth, VIP Terrace 1"
                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-900 text-slate-900 dark:text-white text-xs sm:text-sm focus:ring-2 focus:ring-blue-500 outline-none"
              />
            </div>

            <div>
              <div class="flex items-center justify-between mb-1">
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">
                  Restaurant Section <span class="text-rose-500">*</span>
                </label>
              </div>
              <select
                v-model="formData.section_id"
                @change="handleSectionChange"
                required
                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-900 text-slate-900 dark:text-white text-xs sm:text-sm focus:ring-2 focus:ring-blue-500 outline-none cursor-pointer"
                :class="{
                  'border-rose-500 dark:border-rose-500': errors.section_id || errors.section,
                }"
              >
                <option value="">-- Select Restaurant Section --</option>
                <option v-for="sec in sectionStore.sections" :key="sec.id" :value="sec.id">
                  {{ sec.name }} {{ sec.description ? `(${sec.description})` : '' }}
                </option>
              </select>
              <p v-if="errors.section_id || errors.section" class="text-rose-500 text-[11px] mt-1">
                {{ errors.section_id || errors.section }}
              </p>
              <p class="text-[11px] text-slate-400 mt-1">
                Waiters assigned to this section will automatically receive orders created at this
                table.
              </p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                  Seating Capacity <span class="text-rose-500">*</span>
                </label>
                <input
                  v-model.number="formData.capacity"
                  type="number"
                  min="1"
                  max="30"
                  required
                  placeholder="e.g., 4"
                  class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-900 text-slate-900 dark:text-white text-xs sm:text-sm focus:ring-2 focus:ring-blue-500 outline-none"
                  :class="{ 'border-rose-500 dark:border-rose-500': errors.capacity }"
                />
                <p v-if="errors.capacity" class="text-rose-500 text-[11px] mt-1">
                  {{ errors.capacity }}
                </p>
              </div>

              <div>
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                  Initial Status
                </label>
                <select
                  v-model="formData.status"
                  class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-900 text-slate-900 dark:text-white text-xs sm:text-sm focus:ring-2 focus:ring-blue-500 outline-none cursor-pointer"
                >
                  <option value="available">Available</option>
                  <option value="occupied">Occupied</option>
                  <option value="reserved">Reserved</option>
                  <option value="cleaning">Cleaning</option>
                  <option value="out_of_service">Out of Service</option>
                </select>
              </div>
            </div>

            <div class="flex items-center pt-1">
              <label
                class="flex items-center gap-2 cursor-pointer text-xs font-medium text-slate-700 dark:text-slate-300"
              >
                <input
                  v-model="formData.is_active"
                  type="checkbox"
                  id="is_active"
                  class="w-4 h-4 text-blue-600 rounded border-slate-300 dark:border-slate-700 focus:ring-blue-500"
                />
                <span>Table is active and available for customer QR orders</span>
              </label>
            </div>

            <div
              v-if="submitError"
              class="p-3 rounded-xl bg-rose-500/10 border border-rose-500/20 text-rose-600 dark:text-rose-400 text-xs"
            >
              {{ submitError }}
            </div>

            <div
              v-if="submitSuccess"
              class="p-3 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-600 dark:text-emerald-400 text-xs"
            >
              {{ submitSuccess }}
            </div>

            <div class="flex gap-3 pt-3 border-t border-slate-200 dark:border-slate-800">
              <button
                type="button"
                @click="$emit('close')"
                :disabled="submitting"
                class="flex-1 px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 text-xs font-semibold text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition disabled:opacity-50"
              >
                Cancel
              </button>
              <button
                type="submit"
                :disabled="submitting"
                class="flex-1 px-4 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold transition disabled:opacity-50 flex items-center justify-center gap-2 shadow-md shadow-blue-600/20"
              >
                <Loader2 v-if="submitting" class="w-4 h-4 animate-spin" />
                <span>{{
                  submitting ? 'Saving...' : isEdit ? 'Update Table' : 'Create Table'
                }}</span>
              </button>
            </div>
          </form>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRestaurantTableStore } from '@/stores/restaurantTableStore'
import { useRestaurantSectionStore } from '@/stores/restaurantSectionStore'
import type {
  RestaurantTable,
  CreateTableRequest,
  UpdateTableRequest,
} from '@/types/restaurantTable'
import { X, Loader2 } from 'lucide-vue-next'

interface Props {
  table?: RestaurantTable | null
}

const props = defineProps<Props>()

const emit = defineEmits<{
  close: []
  success: []
}>()

const tableStore = useRestaurantTableStore()
const sectionStore = useRestaurantSectionStore()

const formData = ref<CreateTableRequest>({
  table_number: '',
  table_name: null,
  capacity: 4,
  section_id: '',
  section: '',
  location: '',
  status: 'available',
  is_active: true,
})

const errors = ref<Record<string, string>>({})
const submitError = ref<string | null>(null)
const submitSuccess = ref<string | null>(null)
const submitting = ref(false)

const isEdit = computed(() => !!props.table)

const handleSectionChange = () => {
  if (formData.value.section_id) {
    const sec = sectionStore.sections.find((s) => s.id === formData.value.section_id)
    if (sec) {
      formData.value.section = sec.name
      formData.value.location = sec.name
    }
  }
}

const handleSubmit = async () => {
  errors.value = {}
  submitError.value = null
  submitSuccess.value = null

  if (!formData.value.table_number.trim()) {
    errors.value.table_number = 'Table number is required.'
    return
  }

  if (!formData.value.section_id && !formData.value.section) {
    errors.value.section_id = 'Please select a restaurant section.'
    return
  }

  if (!formData.value.capacity || formData.value.capacity < 1 || formData.value.capacity > 30) {
    errors.value.capacity = 'Capacity must be between 1 and 30 seats.'
    return
  }

  submitting.value = true

  try {
    const payload: CreateTableRequest = {
      table_number: formData.value.table_number.trim(),
      table_name: formData.value.table_name?.trim() || null,
      capacity: Number(formData.value.capacity),
      section_id: formData.value.section_id || null,
      section: formData.value.section || null,
      location: formData.value.section || formData.value.location || null,
      status: formData.value.status || 'available',
      is_active: formData.value.is_active,
    }

    if (isEdit.value && props.table) {
      await tableStore.updateTable(props.table.id, payload as UpdateTableRequest)
      submitSuccess.value = 'Table updated successfully!'
    } else {
      await tableStore.createTable(payload)
      submitSuccess.value = 'Table created successfully with QR code generated!'
    }

    setTimeout(() => {
      emit('success')
    }, 800)
  } catch (error: any) {
    console.error('[RestaurantTableFormModal] Failed to save table:', error)
    submitError.value = error.message || 'Failed to save table'

    if (error.errors) {
      errors.value = error.errors
      const errorMessages: string[] = []
      Object.entries(error.errors).forEach(([field, messages]) => {
        const msgArray = Array.isArray(messages) ? messages : [messages]
        msgArray.forEach((msg) => errorMessages.push(String(msg)))
      })
      submitError.value =
        errorMessages.length > 0 ? errorMessages.join('. ') : error.message || 'Validation failed'
    }
  } finally {
    submitting.value = false
  }
}

onMounted(async () => {
  await sectionStore.fetchSections()

  if (props.table) {
    let secId = props.table.section_id || ''
    if (!secId && props.table.section) {
      const match = sectionStore.sections.find(
        (s) => s.name.toLowerCase() === props.table?.section?.toLowerCase(),
      )
      if (match) secId = match.id
    }

    formData.value = {
      table_number: props.table.table_number,
      table_name: props.table.table_name,
      capacity: props.table.capacity || 4,
      section_id: secId,
      section: props.table.section_name || props.table.section || props.table.location || '',
      location: props.table.location || '',
      status: props.table.status,
      is_active: props.table.is_active,
    }
  } else if (sectionStore.sections.length > 0) {
    const firstSec = sectionStore.sections[0]
    formData.value.section_id = firstSec.id
    formData.value.section = firstSec.name
    formData.value.location = firstSec.name
  }
})
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
