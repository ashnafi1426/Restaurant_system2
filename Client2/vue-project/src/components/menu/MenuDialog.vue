<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import menuService from '@/services/menuService'
import { useMenuStore } from '@/stores/menuStore'
import type { MenuItem } from '@/types/menu'

const props = defineProps<{
  modelValue: boolean
  editMode: boolean
  menuItem: MenuItem | null
}>()

const emit = defineEmits<{
  'update:modelValue': [value: boolean]
}>()

const store = useMenuStore()

// Form state
const form = ref({
  name: '',
  price: 0,
  category: '',
  description: '',
  image: '',
  is_available: true,
})

const errors = ref({} as Record<string, string>)
const saving = ref(false)
const fieldErrors = ref({} as Record<string, string[]>)
const notification = ref({
  show: false,
  message: '',
  type: 'success' as 'success' | 'error',
})

// Computed
const isOpen = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value),
})

const dialogTitle = computed(() => {
  return editMode.value ? 'Edit Menu Item' : 'Add New Menu Item'
})

// Watch for menu item changes
watch(
  () => [props.modelValue, props.menuItem],
  () => {
    if (props.modelValue && props.menuItem && props.editMode) {
      form.value = {
        name: props.menuItem.name,
        price: props.menuItem.price,
        category: props.menuItem.category || '',
        description: props.menuItem.description || '',
        image: (props.menuItem as any).image || '',
        is_available: props.menuItem.is_available,
      }
    } else if (props.modelValue && !props.editMode) {
      resetForm()
    }
  },
  { deep: true },
)

function resetForm() {
  form.value = {
    name: '',
    price: 0,
    category: '',
    description: '',
    image: '',
    is_available: true,
  }
  errors.value = {}
  fieldErrors.value = {}
}

function showNotification(message: string, type: 'success' | 'error' = 'success') {
  notification.value = { show: true, message, type }
  setTimeout(() => {
    notification.value.show = false
  }, 3500)
}

function validate(): boolean {
  errors.value = {}
  fieldErrors.value = {}

  if (!form.value.name?.trim()) {
    fieldErrors.value.name = ['Item name is required']
  } else if (form.value.name.length < 2) {
    fieldErrors.value.name = ['Item name must be at least 2 characters']
  } else if (form.value.name.length > 50) {
    fieldErrors.value.name = ['Item name cannot exceed 50 characters']
  }

  if (!form.value.category) {
    fieldErrors.value.category = ['Please select a category']
  }

  if (form.value.price <= 0) {
    fieldErrors.value.price = ['Price must be greater than 0']
  } else if (form.value.price > 9999.99) {
    fieldErrors.value.price = ['Price cannot exceed $9,999.99']
  }

  if (form.value.description.length > 500) {
    fieldErrors.value.description = ['Description cannot exceed 500 characters']
  }

  return Object.keys(fieldErrors.value).length === 0
}

async function submit() {
  if (!validate()) {
    showNotification('Please fix the errors below', 'error')
    return
  }

  saving.value = true
  try {
    const payload = {
      name: form.value.name,
      price: form.value.price,
      category: form.value.category,
      description: form.value.description,
      image: form.value.image,
      is_available: form.value.is_available,
    }

    if (editMode && props.menuItem?.id) {
      // Update existing item
      await menuService.updateMenu(props.menuItem.id, payload)
      showNotification('✅ Menu item updated successfully!', 'success')
    } else {
      // Create new item
      await menuService.createMenu(payload)
      showNotification('✅ Menu item created successfully!', 'success')
    }

    // Refresh store data
    await store.fetchMenuItems()
    await store.fetchStatistics()

    // Close dialog
    isOpen.value = false
    resetForm()
  } catch (error: any) {
    console.error('Error submitting form:', error)

    const errorMessage =
      error.response?.data?.message ||
      error.response?.data?.error ||
      'Failed to save menu item. Please try again.'

    // Handle validation errors from backend
    if (error.response?.data?.errors) {
      fieldErrors.value = error.response.data.errors
      showNotification('⚠️ Please fix the validation errors', 'error')
    } else {
      showNotification(`❌ ${errorMessage}`, 'error')
    }
  } finally {
    saving.value = false
  }
}

function closeDialog() {
  isOpen.value = false
  resetForm()
}
</script>

<template>
  <v-dialog v-model="isOpen" max-width="600" persistent>
    <v-card class="rounded-2xl">
      <!-- Notification -->
      <transition name="fade">
        <v-alert
          v-if="notification.show"
          :type="notification.type"
          :title="notification.type === 'success' ? 'Success' : 'Error'"
          class="ma-0"
          closable
        >
          {{ notification.message }}
        </v-alert>
      </transition>

      <!-- Header with gradient -->
      <div
        class="bg-gradient-to-r from-blue-600 to-indigo-600 text-white py-3 sm:py-4 md:py-6 px-4 sm:px-6 flex items-center justify-between"
      >
        <div class="flex items-center gap-2 sm:gap-3 min-w-0">
          <span class="text-lg sm:text-xl md:text-2xl flex-shrink-0">{{ editMode ? '✏️' : '➕' }}</span>
          <span class="text-base sm:text-lg md:text-xl font-bold truncate">{{ dialogTitle }}</span>
        </div>
        <button
          @click="closeDialog"
          type="button"
          class="text-white/70 hover:text-white transition-colors flex-shrink-0 min-h-9 w-9 flex items-center justify-center"
        >
          ✕
        </button>
      </div>

      <!-- Content Area -->
      <v-card-text class="px-3 sm:px-6 py-3 sm:py-6">
        <div class="space-y-3 sm:space-y-5">
          <!-- Item Name Field -->
          <div>
            <label class="block text-xs sm:text-sm font-semibold text-slate-700 mb-1.5 sm:mb-2"> 🍽️ Item Name * </label>
            <v-text-field
              v-model="form.name"
              maxlength="50"
              variant="outlined"
              placeholder="e.g., Grilled Salmon"
              :error="!!fieldErrors.name"
              :error-messages="fieldErrors.name"
              density="compact"
              counter
              @blur="validate()"
            />
          </div>

          <!-- Price and Category Row -->
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">
            <!-- Price -->
            <div>
              <label class="block text-xs sm:text-sm font-semibold text-slate-700 mb-1.5 sm:mb-2">
                💵 Price ($) *
              </label>
              <v-text-field
                v-model.number="form.price"
                type="number"
                step="0.01"
                min="0"
                variant="outlined"
                placeholder="0.00"
                :error="!!fieldErrors.price"
                :error-messages="fieldErrors.price"
                density="compact"
                @blur="validate()"
              />
            </div>

            <!-- Category -->
            <div>
              <label class="block text-xs sm:text-sm font-semibold text-slate-700 mb-1.5 sm:mb-2"> 🏷️ Category * </label>
              <v-select
                v-model="form.category"
                :items="[
                  { title: '☀️ Breakfast', value: 'breakfast' },
                  { title: '🍔 Lunch', value: 'lunch' },
                  { title: '🍲 Dinner', value: 'dinner' },
                  { title: '🍹 Drinks', value: 'drinks' },
                  { title: '🍦 Dessert', value: 'dessert' },
                ]"
                variant="outlined"
                placeholder="Select..."
                :error="!!fieldErrors.category"
                :error-messages="fieldErrors.category"
                density="compact"
                @blur="validate()"
              />
            </div>
          </div>

          <!-- Description -->
          <div>
            <label class="block text-xs sm:text-sm font-semibold text-slate-700 mb-1.5 sm:mb-2"> 📝 Description </label>
            <v-textarea
              v-model="form.description"
              maxlength="500"
              variant="outlined"
              placeholder="Add recipe or special notes..."
              :error="!!fieldErrors.description"
              :error-messages="fieldErrors.description"
              rows="3"
              counter
              @blur="validate()"
            />
          </div>

          <!-- Image URL -->
          <div>
            <label class="block text-xs sm:text-sm font-semibold text-slate-700 mb-1.5 sm:mb-2"> 🖼️ Image URL </label>
            <v-text-field
              v-model="form.image"
              type="url"
              variant="outlined"
              placeholder="https://example.com/image.jpg"
              density="compact"
            />
          </div>

          <!-- Image Preview -->
          <div v-if="form.image" class="mt-2 sm:mt-4">
            <p class="text-xs font-semibold text-slate-600 mb-2">Preview:</p>
            <img
              :src="form.image"
              alt="Preview"
              class="w-full h-32 sm:h-40 object-cover rounded-lg border border-slate-200"
              @error="() => (form.image = '')"
            />
          </div>

          <!-- Available Toggle -->
          <div class="flex items-center gap-2 sm:gap-3 p-3 sm:p-4 bg-slate-50 rounded-lg border border-slate-200">
            <v-checkbox v-model="form.is_available" class="mt-0" />
            <span class="text-xs sm:text-sm font-medium text-slate-700"> Available for order </span>
            <span class="ml-auto text-lg">
              {{ form.is_available ? '✅' : '⭕' }}
            </span>
          </div>
        </div>
      </v-card-text>

      <!-- Footer with Actions -->
      <v-card-actions
        class="px-3 sm:px-6 py-3 sm:py-4 border-t border-slate-200 bg-slate-50 flex gap-2 sm:gap-3 justify-end"
      >
        <v-btn @click="closeDialog" :disabled="saving" variant="outlined" text="Cancel" />
        <v-btn
          @click="submit"
          :disabled="saving"
          :loading="saving"
          color="primary"
          :text="saving ? 'Saving...' : editMode ? 'Update Item' : 'Add Item'"
        />
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
