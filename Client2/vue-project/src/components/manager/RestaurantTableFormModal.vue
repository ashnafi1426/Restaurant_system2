<template>
  <Teleport to="body">
    <Transition name="fade">
      <div
        class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4"
        @click.self="$emit('close')"
      >
        <div class="bg-white rounded-xl shadow-2xl max-w-lg w-full max-h-[90vh] overflow-y-auto">
          <div class="bg-gradient-to-r from-amber-500 to-amber-600 px-6 py-4 text-white">
            <h2 class="text-2xl font-bold">{{ isEdit ? 'Edit Table' : 'Create New Table' }}</h2>
            <p class="text-amber-100 text-sm">{{ isEdit ? 'Update table details' : 'Add a new restaurant table' }}</p>
          </div>

          <form @submit.prevent="handleSubmit" class="p-6 space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">
                Table Number <span class="text-red-500">*</span>
              </label>
              <input
                v-model="formData.table_number"
                type="text"
                required
                placeholder="e.g., T1, T2, A-1"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-transparent"
                :class="{ 'border-red-500': errors.table_number }"
              />
              <p v-if="errors.table_number" class="text-red-500 text-xs mt-1">{{ errors.table_number }}</p>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">
                Table Name (Optional)
              </label>
              <input
                v-model="formData.table_name"
                type="text"
                placeholder="e.g., Window Table, VIP Corner"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-transparent"
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">
                Capacity (Number of Seats)
              </label>
              <input
                v-model.number="formData.capacity"
                type="number"
                min="1"
                max="20"
                placeholder="e.g., 4"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-transparent"
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">
                Location
              </label>
              <select
                v-model="formData.location"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500"
              >
                <option value="">Select location</option>
                <option value="Main Dining">Main Dining</option>
                <option value="Terrace">Terrace</option>
                <option value="VIP Room">VIP Room</option>
                <option value="Bar Area">Bar Area</option>
                <option value="Outdoor">Outdoor</option>
                <option value="Private Room">Private Room</option>
              </select>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">
                Status
              </label>
              <select
                v-model="formData.status"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500"
              >
                <option value="available">Available</option>
                <option value="occupied">Occupied</option>
                <option value="reserved">Reserved</option>
                <option value="cleaning">Cleaning</option>
                <option value="out_of_service">Out of Service</option>
              </select>
            </div>

            <div class="flex items-center">
              <input
                v-model="formData.is_active"
                type="checkbox"
                id="is_active"
                class="w-4 h-4 text-amber-600 border-gray-300 rounded focus:ring-amber-500"
              />
              <label for="is_active" class="ml-2 text-sm text-gray-700">
                Table is active and available for orders
              </label>
            </div>

            <div v-if="submitError" class="bg-red-50 border border-red-200 rounded-lg p-3 text-red-700 text-sm">
              {{ submitError }}
            </div>

            <div v-if="submitSuccess" class="bg-green-50 border border-green-200 rounded-lg p-3 text-green-700 text-sm">
              {{ submitSuccess }}
            </div>

            <div class="flex gap-3 pt-4 border-t">
              <button
                type="button"
                @click="$emit('close')"
                :disabled="submitting"
                class="flex-1 px-4 py-2 border border-gray-300 rounded-lg font-medium text-gray-700 hover:bg-gray-50 transition-colors disabled:opacity-50"
              >
                Cancel
              </button>
              <button
                type="submit"
                :disabled="submitting"
                class="flex-1 px-4 py-2 bg-amber-600 text-white rounded-lg font-medium hover:bg-amber-700 transition-colors disabled:opacity-50 flex items-center justify-center gap-2"
              >
                <svg
                  v-if="submitting"
                  class="w-5 h-5 animate-spin"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M12 2v20m0-20a9.978 9.978 0 00-9 18m18 0a9.978 9.978 0 00-9-18"
                  ></path>
                </svg>
                <span>{{ submitting ? 'Saving...' : (isEdit ? 'Update Table' : 'Create Table') }}</span>
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
import type { RestaurantTable, CreateTableRequest, UpdateTableRequest } from '@/types/restaurantTable'

interface Props {
  table?: RestaurantTable | null
}

const props = defineProps<Props>()

const emit = defineEmits<{
  close: []
  success: []
}>()

const tableStore = useRestaurantTableStore()

const formData = ref<CreateTableRequest>({
  table_number: '',
  table_name: null,
  capacity: 4,
  location: null,
  status: 'available',
  is_active: true,
})

const errors = ref<Record<string, string>>({})
const submitError = ref<string | null>(null)
const submitSuccess = ref<string | null>(null)
const submitting = ref(false)

const isEdit = computed(() => !!props.table)

const handleSubmit = async () => {
  errors.value = {}
  submitError.value = null
  submitSuccess.value = null

  if (!formData.value.table_number) {
    errors.value.table_number = 'Table number is required'
    return
  }

  submitting.value = true

  try {
    if (isEdit.value && props.table) {
      await tableStore.updateTable(props.table.id, formData.value as UpdateTableRequest)
      submitSuccess.value = 'Table updated successfully!'
    } else {
      await tableStore.createTable(formData.value)
      submitSuccess.value = 'Table created successfully!'
    }

    setTimeout(() => {
      emit('success')
    }, 1000)
  } catch (error: any) {
    console.error('[RestaurantTableFormModal] Failed to save table:', error)
    submitError.value = error.message || 'Failed to save table'

    if (error.errors) {
      errors.value = error.errors
      
      const errorMessages: string[] = []
      Object.entries(error.errors).forEach(([field, messages]) => {
        const msgArray = Array.isArray(messages) ? messages : [messages]
        msgArray.forEach(msg => {
          errorMessages.push(String(msg))
        })
      })
      
      submitError.value = errorMessages.length > 0 
        ? errorMessages.join('. ')
        : (error.message || 'Validation failed')
    }
  } finally {
    submitting.value = false
  }
}

onMounted(() => {
  if (props.table) {
    formData.value = {
      table_number: props.table.table_number,
      table_name: props.table.table_name,
      capacity: props.table.capacity,
      location: props.table.location,
      status: props.table.status,
      is_active: props.table.is_active,
    }
  }
})
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

.animate-spin {
  animation: spin 1s linear infinite;
}
</style>
