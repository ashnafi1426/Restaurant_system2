<script setup lang="ts">
import { computed, ref, watch, onMounted } from 'vue'
import menuService from '@/services/menuService'
import { useMenuStore } from '@/stores/menuStore'
import { useTaxRateStore } from '@/stores/taxRateStore'
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
const taxRateStore = useTaxRateStore()

const form = ref({
  name: '',
  price: 0,
  category: '',
  description: '',
  image: '',
  tax_rate_id: '',
  tax_included: false,
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

const isOpen = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value),
})

const dialogTitle = computed(() => {
  return props.editMode ? 'Edit Menu Item' : 'Add New Menu Item'
})

const selectedTaxRate = computed(() => {
  return taxRateStore.taxRates.find((t) => t.id === form.value.tax_rate_id) || null
})

const pricePreview = computed(() => {
  const price = Number(form.value.price) || 0
  const rate = selectedTaxRate.value ? Number(selectedTaxRate.value.rate) : 0

  if (price <= 0 || rate <= 0) {
    return {
      basePrice: price.toFixed(2),
      taxAmount: '0.00',
      totalPrice: price.toFixed(2),
    }
  }

  if (form.value.tax_included) {
    const base = price / (1 + rate / 100)
    const tax = price - base
    return {
      basePrice: base.toFixed(2),
      taxAmount: tax.toFixed(2),
      totalPrice: price.toFixed(2),
    }
  } else {
    const tax = price * (rate / 100)
    const total = price + tax
    return {
      basePrice: price.toFixed(2),
      taxAmount: tax.toFixed(2),
      totalPrice: total.toFixed(2),
    }
  }
})

watch(
  () => [props.modelValue, props.menuItem],
  async () => {
    if (taxRateStore.taxRates.length === 0) {
      await taxRateStore.fetchTaxRates()
    }

    if (props.modelValue && props.menuItem && props.editMode) {
      form.value = {
        name: props.menuItem.name,
        price: props.menuItem.price,
        category: props.menuItem.category || '',
        description: props.menuItem.description || '',
        image: (props.menuItem as any).image || '',
        tax_rate_id:
          (props.menuItem as any).tax_rate_id || (props.menuItem as any).tax_rate?.id || '',
        tax_included: Boolean((props.menuItem as any).tax_included),
        is_available: props.menuItem.is_available,
      }
    } else if (props.modelValue && !props.editMode) {
      resetForm()
      if (taxRateStore.defaultTaxRate) {
        form.value.tax_rate_id = taxRateStore.defaultTaxRate.id
      }
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
    tax_rate_id: '',
    tax_included: false,
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
      tax_rate_id: form.value.tax_rate_id || null,
      tax_included: form.value.tax_included,
      description: form.value.description,
      image: form.value.image,
      is_available: form.value.is_available,
    }

    if (props.editMode && props.menuItem?.id) {
      await menuService.updateMenu(props.menuItem.id, payload)
      showNotification(' Menu item updated successfully!', 'success')
    } else {
      await menuService.createMenu(payload)
      showNotification(' Menu item created successfully!', 'success')
    }

    await store.fetchMenuItems()
    await store.fetchStatistics()

    isOpen.value = false
    resetForm()
  } catch (error: any) {
    console.error('[MenuDialog] Error saving menu item:', error)
    const errorMessage =
      error.response?.data?.message ||
      error.response?.data?.error ||
      'Failed to save menu item. Please try again.'

    if (error.response?.data?.errors) {
      fieldErrors.value = error.response.data.errors
      showNotification('Please fix the validation errors', 'error')
    } else {
      showNotification(` ${errorMessage}`, 'error')
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

      <div
        class="bg-gradient-to-r from-blue-600 to-indigo-600 text-white py-3 sm:py-4 md:py-6 px-4 sm:px-6 flex items-center justify-between"
      >
        <div class="flex items-center gap-2 sm:gap-3 min-w-0">
          <span class="text-lg sm:text-xl md:text-2xl flex-shrink-0">{{
            editMode ? '✏️' : '➕'
          }}</span>
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

      <v-card-text class="px-3 sm:px-6 py-3 sm:py-6">
        <div class="space-y-3 sm:space-y-5">
          <div>
            <label
              class="block text-xs sm:text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5 sm:mb-2"
            >
              Item Name *
            </label>
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

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">
            <div>
              <label
                class="block text-xs sm:text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5 sm:mb-2"
              >
                Price ($) *
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

            <div>
              <label
                class="block text-xs sm:text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5 sm:mb-2"
              >
                Category *
              </label>
              <v-select
                v-model="form.category"
                :items="[
                  { title: 'Breakfast', value: 'breakfast' },
                  { title: 'Lunch', value: 'lunch' },
                  { title: 'Dinner', value: 'dinner' },
                  { title: 'Drinks', value: 'drinks' },
                  { title: 'Dessert', value: 'dessert' },
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

          <div
            class="p-3 sm:p-4 rounded-xl bg-slate-50 dark:bg-zinc-800 border border-slate-200 dark:border-zinc-700 space-y-3"
          >
            <div
              class="flex items-center justify-between text-xs font-semibold text-slate-700 dark:text-slate-300"
            >
              <span>Tax Configuration</span>
              <span class="text-[11px] text-amber-600 font-medium">Auto-calculated</span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <div>
                <label class="block text-xs text-slate-600 dark:text-slate-400 mb-1">
                  Tax Rate
                </label>
                <select
                  v-model="form.tax_rate_id"
                  class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 dark:border-zinc-600 bg-white dark:bg-zinc-900 text-slate-900 dark:text-white focus:outline-none focus:ring-1 focus:ring-amber-500"
                >
                  <option value="">No Tax (0%)</option>
                  <option v-for="tax in taxRateStore.activeTaxRates" :key="tax.id" :value="tax.id">
                    {{ tax.name }} ({{ tax.rate }}{{ tax.type === 'percentage' ? '%' : ' Fixed' }})
                  </option>
                </select>
              </div>

              <div class="flex items-center gap-2 pt-4">
                <input
                  id="dialog_tax_inc"
                  v-model="form.tax_included"
                  type="checkbox"
                  class="w-4 h-4 rounded accent-amber-500 cursor-pointer"
                />
                <label
                  for="dialog_tax_inc"
                  class="text-xs font-medium text-slate-700 dark:text-slate-300 cursor-pointer"
                >
                  Price Includes Tax
                </label>
              </div>
            </div>

            <div
              v-if="form.price > 0"
              class="flex items-center justify-between text-xs pt-1 border-t border-slate-200/60 dark:border-zinc-700/60 text-slate-600 dark:text-slate-400"
            >
              <span>Base: ${{ pricePreview.basePrice }}</span>
              <span>Tax: ${{ pricePreview.taxAmount }}</span>
              <span class="font-bold text-emerald-600 dark:text-emerald-400"
                >Total: ${{ pricePreview.totalPrice }}</span
              >
            </div>
          </div>

          <div>
            <label
              class="block text-xs sm:text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5 sm:mb-2"
            >
              Description
            </label>
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

          <div>
            <label
              class="block text-xs sm:text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5 sm:mb-2"
            >
              Image URL
            </label>
            <v-text-field
              v-model="form.image"
              type="url"
              variant="outlined"
              placeholder="https://example.com/image.jpg"
              density="compact"
            />
          </div>

          <div v-if="form.image" class="mt-2 sm:mt-4">
            <p class="text-xs font-semibold text-slate-600 mb-2">Preview:</p>
            <img
              :src="form.image"
              alt="Preview"
              class="w-full h-32 sm:h-40 object-cover rounded-lg border border-slate-200"
              @error="() => (form.image = '')"
            />
          </div>

          <div
            class="flex items-center gap-2 sm:gap-3 p-3 sm:p-4 bg-slate-50 rounded-lg border border-slate-200"
          >
            <v-checkbox v-model="form.is_available" class="mt-0" />
            <span class="text-xs sm:text-sm font-medium text-slate-700"> Available for order </span>
            <span class="ml-auto text-lg">
              {{ form.is_available ? '' : '⭕' }}
            </span>
          </div>
        </div>
      </v-card-text>

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
