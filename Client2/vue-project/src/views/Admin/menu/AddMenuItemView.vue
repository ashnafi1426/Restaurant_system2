<template>
  <DashboardLayout>
    <template #header>
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 sm:gap-4">
        <div>
          <h1 class="text-xl sm:text-2xl md:text-3xl font-bold text-slate-900">
            {{ isEditMode ? 'Edit Menu Item' : 'Add New Menu Item' }}
          </h1>
          <p class="text-xs sm:text-sm text-slate-600 mt-1">
            {{
              isEditMode
                ? 'Update the menu item details'
                : 'Create a new menu item for your restaurant'
            }}
          </p>
        </div>
        <button
          @click="goBack"
          class="px-3 sm:px-4 py-2 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50 transition-colors text-xs sm:text-sm font-medium whitespace-nowrap"
        >
          ← Back to Menu
        </button>
      </div>
    </template>

    <div v-if="loading" class="flex items-center justify-center py-12">
      <div class="flex flex-col items-center gap-4">
        <div
          class="w-12 h-12 border-4 border-emerald-200 border-t-emerald-500 rounded-full animate-spin"
        ></div>
        <p class="text-slate-600">Loading menu item...</p>
      </div>
    </div>

    <div v-else class="max-w-4xl mx-auto">
      <div v-if="errors.general" class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
        <p class="text-red-700 font-medium">{{ errors.general }}</p>
      </div>

      <form @submit.prevent="submitForm" class="space-y-4 sm:space-y-6">
        <div class="flex flex-col lg:flex-row lg:gap-6">
          <div class="w-full lg:flex-1 space-y-4 sm:space-y-6">
            <div
              class="bg-white rounded-lg sm:rounded-xl border border-slate-200 p-3 sm:p-6 shadow-sm"
            >
              <div class="space-y-4">
                <div>
                  <label
                    for="name"
                    class="block text-xs font-semibold text-slate-600 uppercase mb-2"
                  >
                    Item Name <span class="text-red-500">*</span>
                  </label>
                  <input
                    id="name"
                    v-model="formData.name"
                    type="text"
                    placeholder="e.g., Grilled Salmon with Asparagus"
                    class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent text-sm"
                    :class="{ 'border-red-500 focus:ring-red-500': errors.name }"
                  />
                  <p v-if="errors.name" class="text-red-600 text-xs mt-1">{{ errors.name }}</p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">
                  <div>
                    <div class="flex items-center justify-between mb-2">
                      <label
                        for="price"
                        class="block text-xs font-semibold text-slate-600 uppercase"
                      >
                        Price <span class="text-red-500">*</span>
                      </label>
                      <span
                        v-if="formData.price && parseFloat(formData.price) > 0"
                        class="text-xs font-bold px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200"
                      >
                        Total with Tax: ${{ pricePreview.totalPrice }}
                      </span>
                    </div>
                    <div class="flex items-center gap-2">
                      <span class="text-slate-600 font-medium">$</span>
                      <input
                        id="price"
                        v-model="formData.price"
                        type="number"
                        placeholder="0.00"
                        step="0.01"
                        min="0"
                        class="flex-1 px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent text-sm font-semibold"
                        :class="{ 'border-red-500 focus:ring-red-500': errors.price }"
                      />
                    </div>
                    <p v-if="errors.price" class="text-red-600 text-xs mt-1">{{ errors.price }}</p>
                    <p
                      v-else-if="formData.price && parseFloat(formData.price) > 0"
                      class="text-[11px] text-slate-500 mt-1"
                    >
                      Base: ${{ pricePreview.basePrice }} + Tax: ${{ pricePreview.taxAmount }} =
                      <strong class="text-slate-800">Total ${{ pricePreview.totalPrice }}</strong>
                    </p>
                  </div>

                  <div>
                    <div class="flex items-center justify-between mb-2 gap-2 flex-wrap">
                      <label
                        for="category"
                        class="block text-xs font-semibold text-slate-600 uppercase"
                      >
                        Category <span class="text-red-500">*</span>
                      </label>
                      <button
                        type="button"
                        @click="navigateToAddCategory"
                        class="text-xs font-semibold text-emerald-600 hover:text-emerald-700 transition-colors flex items-center gap-1 whitespace-nowrap"
                        title="Manage categories"
                      >
                        <svg
                          class="w-3 h-3 sm:w-4 sm:h-4"
                          fill="none"
                          stroke="currentColor"
                          viewBox="0 0 24 24"
                        >
                          <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M12 4v16m8-8H4"
                          ></path>
                        </svg>
                        <span class="hidden sm:inline">Manage</span>
                      </button>
                    </div>
                    <select
                      id="category"
                      v-model="formData.category"
                      :disabled="categoriesLoading"
                      class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent text-sm disabled:bg-slate-100 disabled:text-slate-500"
                      :class="{ 'border-red-500 focus:ring-red-500': errors.category }"
                    >
                      <option v-if="categoriesLoading" value="">Loading categories...</option>
                      <option v-else value="">Select category...</option>
                      <option v-for="cat in categoryOptions" :key="cat.value" :value="cat.value">
                        {{ cat.label }}
                      </option>
                    </select>
                    <p v-if="errors.category" class="text-red-600 text-xs mt-1">
                      {{ errors.category }}
                    </p>
                  </div>
                </div>
              </div>
            </div>

            <!-- Dedicated Tax & Pricing Configuration Card -->
            <div
              class="bg-white rounded-lg sm:rounded-xl border border-slate-200 p-4 sm:p-6 shadow-sm space-y-4"
            >
              <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div>
                  <h3 class="text-sm font-bold text-slate-800 flex items-center gap-2">
                    <span class="p-1 rounded-md bg-amber-500/10 text-amber-600 text-xs">%</span>
                    Tax & Pricing Configuration
                  </h3>
                  <p class="text-xs text-slate-500 mt-0.5">
                    Assign hotel tax rate & configure inclusive/exclusive pricing
                  </p>
                </div>
                <router-link
                  to="/admin/taxes"
                  target="_blank"
                  class="text-xs font-semibold text-amber-600 hover:text-amber-700 transition-colors"
                >
                  Manage Taxes ↗
                </router-link>
              </div>

              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                  <label
                    for="tax_rate"
                    class="block text-xs font-semibold text-slate-600 uppercase mb-2"
                  >
                    Applied Tax Rate
                  </label>
                  <select
                    id="tax_rate"
                    v-model="formData.tax_rate_id"
                    class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent text-sm"
                  >
                    <option value="">No Tax (0.00%)</option>
                    <option
                      v-for="tax in taxRateStore.activeTaxRates"
                      :key="tax.id"
                      :value="tax.id"
                    >
                      {{ tax.name }} ({{ tax.rate
                      }}{{ tax.type === 'percentage' ? '%' : ' Fixed' }})
                      {{ tax.is_default ? '★ Default' : '' }}
                    </option>
                  </select>
                  <p class="text-[11px] text-slate-400 mt-1">
                    Select from hotel pre-configured tax rates
                  </p>
                </div>

                <div>
                  <label class="block text-xs font-semibold text-slate-600 uppercase mb-2">
                    Tax Treatment
                  </label>
                  <div class="flex items-center gap-3 pt-2">
                    <input
                      id="tax_included"
                      v-model="formData.tax_included"
                      type="checkbox"
                      class="w-4 h-4 rounded accent-amber-500 cursor-pointer"
                    />
                    <label for="tax_included" class="cursor-pointer">
                      <span class="text-sm font-medium text-slate-700">Price Includes Tax</span>
                    </label>
                  </div>
                  <p class="text-[11px] text-slate-400 mt-1">
                    {{
                      formData.tax_included
                        ? 'Tax is extracted from price'
                        : 'Tax will be added on top at order checkout'
                    }}
                  </p>
                </div>
              </div>

              <!-- Live Price Breakdown Preview Card -->
              <div
                v-if="formData.price && parseFloat(formData.price) > 0"
                class="p-3.5 rounded-xl bg-slate-50 border border-slate-200/80 space-y-2"
              >
                <div
                  class="text-xs font-semibold text-slate-700 uppercase tracking-wider flex items-center justify-between"
                >
                  <span>Live Pricing Breakdown Preview</span>
                  <span class="text-[11px] font-normal text-slate-500">
                    {{
                      selectedTaxRate
                        ? `${selectedTaxRate.name} (${selectedTaxRate.rate}%)`
                        : 'No Tax'
                    }}
                  </span>
                </div>
                <div class="grid grid-cols-3 gap-2 text-center pt-1">
                  <div class="p-2 rounded-lg bg-white border border-slate-100">
                    <div class="text-[11px] text-slate-500">Base Net Price</div>
                    <div class="text-sm font-bold text-slate-800">
                      ${{ pricePreview.basePrice }}
                    </div>
                  </div>
                  <div class="p-2 rounded-lg bg-white border border-slate-100">
                    <div class="text-[11px] text-slate-500">Estimated Tax</div>
                    <div class="text-sm font-bold text-amber-600">
                      ${{ pricePreview.taxAmount }}
                    </div>
                  </div>
                  <div class="p-2 rounded-lg bg-white border border-slate-100">
                    <div class="text-[11px] text-slate-500">Total Customer Pays</div>
                    <div class="text-sm font-bold text-emerald-600">
                      ${{ pricePreview.totalPrice }}
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div
              class="bg-white rounded-lg sm:rounded-xl border border-slate-200 p-3 sm:p-6 shadow-sm"
            >
              <label
                for="description"
                class="block text-xs font-semibold text-slate-600 uppercase mb-2"
              >
                Description <span class="text-slate-400">(optional)</span>
              </label>
              <textarea
                id="description"
                v-model="formData.description"
                rows="4"
                placeholder="Add ingredients, preparation notes, or dietary information..."
                class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent resize-none text-sm"
              ></textarea>
            </div>

            <div
              class="bg-white rounded-lg sm:rounded-xl border border-slate-200 p-3 sm:p-6 shadow-sm"
            >
              <div
                class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 sm:gap-4"
              >
                <div>
                  <label class="text-sm font-semibold text-slate-900">Status</label>
                  <p class="text-xs text-slate-600 mt-1">Item visibility on menu</p>
                </div>
                <div class="flex items-center gap-3">
                  <input
                    id="is_available"
                    v-model="formData.is_available"
                    type="checkbox"
                    class="w-4 h-4 rounded accent-emerald-500 cursor-pointer"
                  />
                  <label for="is_available" class="cursor-pointer">
                    <span
                      class="text-sm font-medium"
                      :class="formData.is_available ? 'text-emerald-600' : 'text-slate-600'"
                    >
                      {{ formData.is_available ? '✓ Published' : '○ Draft' }}
                    </span>
                  </label>
                </div>
              </div>
            </div>
          </div>

          <div class="w-full lg:w-80 mt-4 sm:mt-6 lg:mt-0">
            <div
              class="bg-white rounded-lg sm:rounded-xl border border-slate-200 p-3 sm:p-6 shadow-sm lg:sticky lg:top-6"
            >
              <label class="block text-xs font-semibold text-slate-600 uppercase mb-4">
                Item Image
              </label>

              <div v-if="imagePreview" class="mb-4">
                <div class="relative inline-block w-full">
                  <img
                    :src="imagePreview"
                    alt="Preview"
                    class="w-full h-40 sm:h-48 object-cover rounded-lg border-2 border-emerald-200"
                  />
                  <button
                    type="button"
                    @click="removeImage"
                    class="absolute -top-2 -right-2 w-6 h-6 sm:w-7 sm:h-7 bg-red-500 text-white rounded-full flex items-center justify-center hover:bg-red-600 transition-colors shadow-lg text-xs sm:text-sm font-bold"
                  >
                    ✕
                  </button>
                </div>
              </div>

              <div class="flex gap-2 mb-4">
                <button
                  type="button"
                  @click="formData.image_input_type = 'upload'"
                  class="flex-1 px-3 py-2 rounded-lg font-semibold text-xs sm:text-sm transition-all"
                  :class="
                    formData.image_input_type === 'upload'
                      ? 'bg-emerald-500 text-white hover:bg-emerald-600'
                      : 'border border-slate-300 text-slate-700 hover:bg-slate-50'
                  "
                >
                  Upload
                </button>
                <button
                  type="button"
                  @click="formData.image_input_type = 'url'"
                  class="flex-1 px-3 py-2 rounded-lg font-semibold text-xs sm:text-sm transition-all"
                  :class="
                    formData.image_input_type === 'url'
                      ? 'bg-emerald-500 text-white hover:bg-emerald-600'
                      : 'border border-slate-300 text-slate-700 hover:bg-slate-50'
                  "
                >
                  URL
                </button>
              </div>

              <div v-show="formData.image_input_type === 'upload'" class="space-y-3">
                <div
                  @dragover="onDragOver"
                  @dragleave="onDragLeave"
                  @drop="onDrop"
                  class="border-2 border-dashed rounded-lg p-4 sm:p-6 text-center transition-colors cursor-pointer"
                  :class="[
                    isDragOver
                      ? 'border-emerald-500 bg-emerald-50'
                      : 'border-slate-300 bg-slate-50 hover:border-slate-400',
                  ]"
                >
                  <div class="flex flex-col items-center gap-2">
                    <svg
                      class="w-6 sm:w-8 h-6 sm:h-8 text-slate-400"
                      fill="none"
                      stroke="currentColor"
                      viewBox="0 0 24 24"
                    >
                      <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="1.5"
                        d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"
                      />
                    </svg>
                    <p class="text-xs sm:text-sm font-medium text-slate-900">Drop image here</p>
                    <p class="text-xs text-slate-500">or click to browse</p>
                  </div>
                  <input
                    type="file"
                    accept="image/*"
                    @change="onFileSelected"
                    class="hidden"
                    ref="fileInputRef"
                  />
                </div>

                <button
                  type="button"
                  @click="fileInputRef?.click()"
                  class="w-full px-3 py-2 border border-slate-300 rounded-lg hover:bg-slate-50 text-xs sm:text-sm font-medium text-slate-700 transition-colors"
                >
                  Choose file...
                </button>

                <p v-if="errors.image" class="text-red-600 text-xs">{{ errors.image }}</p>
                <p class="text-xs text-slate-500">PNG, JPG, GIF, WebP • Max 5MB</p>
              </div>

              <div v-show="formData.image_input_type === 'url'" class="space-y-3">
                <input
                  id="image_url"
                  v-model="formData.image_url"
                  type="url"
                  placeholder="https://example.com/image.jpg"
                  @input="(e) => handleUrlInput((e.target as HTMLInputElement).value)"
                  class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent text-sm"
                />
                <p v-if="errors.image_url" class="text-red-600 text-xs">{{ errors.image_url }}</p>
                <p class="text-xs text-slate-500">HTTPS recommended</p>
              </div>
            </div>
          </div>
        </div>

        <div
          class="flex flex-col sm:flex-row gap-3 items-stretch sm:items-center sm:justify-between mt-8 pt-6 border-t border-slate-200"
        >
          <div
            v-if="formData.price && parseFloat(formData.price) > 0"
            class="flex items-center gap-2 text-xs text-slate-600 bg-slate-50 px-3.5 py-2 rounded-xl border border-slate-200/80"
          >
            <span class="font-medium text-slate-500">Customer Selling Price:</span>
            <span class="font-black text-emerald-600 text-base font-mono"
              >${{ pricePreview.totalPrice }}</span
            >
            <span class="text-[11px] text-slate-400">
              ({{
                selectedTaxRate
                  ? formData.tax_included
                    ? `Includes ${selectedTaxRate.rate}% ${selectedTaxRate.name}`
                    : `$${pricePreview.basePrice} base + $${pricePreview.taxAmount} tax`
                  : 'No Tax'
              }})
            </span>
          </div>
          <div v-else class="hidden sm:block"></div>

          <div class="flex items-center gap-3 justify-end">
            <button
              type="button"
              @click="goBack"
              class="px-4 sm:px-6 py-2.5 rounded-xl border border-slate-300 text-slate-700 font-medium hover:bg-slate-50 transition-colors text-sm"
            >
              Cancel
            </button>
            <button
              type="submit"
              :disabled="!isFormValid || submitting"
              class="px-6 sm:px-8 py-2.5 rounded-xl bg-emerald-500 text-white font-medium hover:bg-emerald-600 disabled:bg-slate-300 disabled:cursor-not-allowed transition-colors flex items-center justify-center gap-2 text-sm shadow-md shadow-emerald-500/20"
            >
              <span
                v-if="submitting"
                class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"
              ></span>
              <span>{{ submitting ? 'Saving...' : isEditMode ? 'Update Item' : 'Add Item' }}</span>
            </button>
          </div>
        </div>
      </form>
    </div>
  </DashboardLayout>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import menuService from '@/services/menuService'
import categoryService from '@/services/categoryService'
import { useMenuStore } from '@/stores/menuStore'
import { useTaxRateStore } from '@/stores/taxRateStore'
import { useAuthStore } from '@/stores/auth'
import type { MenuItem } from '@/types/menu'

const route = useRoute()
const router = useRouter()

const menuStore = useMenuStore()
const taxRateStore = useTaxRateStore()
const authStore = useAuthStore()

const isMenuManagement = computed(() => {
  return (
    route.path.startsWith('/menu-management') ||
    (!authStore.isPlatformAdmin && !authStore.hasRole('admin'))
  )
})

const formData = ref({
  name: '',
  description: '',
  price: '',
  category: '',
  tax_rate_id: '',
  tax_included: false,
  is_available: true,
  dietary_tags: [] as string[],
  image: null as File | null,
  image_url: '',
  image_input_type: 'upload' as 'upload' | 'url',
})

const imagePreview = ref<string>('')
const loading = ref(false)
const submitting = ref(false)
const errors = ref<Record<string, string>>({})
const isDragOver = ref(false)
const categories = ref<Array<{ id: string; name: string; slug: string }>>([])
const categoriesLoading = ref(false)

const isEditMode = computed(() => !!route.query.id)
const editingItemId = computed(() => route.query.id as string)

const selectedTaxRate = computed(() => {
  return taxRateStore.taxRates.find((t) => t.id === formData.value.tax_rate_id) || null
})

const pricePreview = computed(() => {
  const price = parseFloat(formData.value.price) || 0
  const rate = selectedTaxRate.value ? Number(selectedTaxRate.value.rate) : 0

  if (price <= 0 || rate <= 0) {
    return {
      basePrice: price.toFixed(2),
      taxAmount: '0.00',
      totalPrice: price.toFixed(2),
    }
  }

  if (formData.value.tax_included) {
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

const dietaryTagsOptions = [
  { value: 'vegetarian', label: 'Vegetarian' },
  { value: 'vegan', label: 'Vegan' },
  { value: 'gluten-free', label: 'Gluten Free' },
  { value: 'dairy-free', label: 'Dairy Free' },
  { value: 'nut-free', label: 'Nut Free' },
  { value: 'spicy', label: 'Spicy' },
  { value: 'low-carb', label: 'Low Carb' },
]

const categoryOptions = computed(() => {
  return categories.value.map((cat) => ({
    value: cat.slug,
    label: cat.name,
  }))
})

const isFormValid = computed(() => {
  return (
    formData.value.name.trim().length > 0 &&
    formData.value.price &&
    parseFloat(formData.value.price) > 0 &&
    formData.value.category.length > 0
  )
})

const selectedDietaryTags = computed({
  get: () => formData.value.dietary_tags,
  set: (val) => {
    formData.value.dietary_tags = val
  },
})

const loadMenuItemForEdit = async () => {
  if (!isEditMode.value) return

  loading.value = true
  try {
    const response = await menuService.getMenus({ id: editingItemId.value })

    let item: MenuItem | null = null

    if (response.data.data && Array.isArray(response.data.data)) {
      item = response.data.data.find((i: MenuItem) => i.id === editingItemId.value)
    } else if (response.data.data && !Array.isArray(response.data.data)) {
      item = response.data.data
    }

    if (item) {
      formData.value.name = item.name
      formData.value.description = item.description || ''
      formData.value.price = String(item.price)
      formData.value.category = item.category
      formData.value.tax_rate_id = (item as any).tax_rate_id || (item as any).tax_rate?.id || ''
      formData.value.tax_included = Boolean((item as any).tax_included)
      formData.value.is_available = item.is_available
      formData.value.dietary_tags = item.dietary_tags || []

      if (item.image) {
        imagePreview.value = item.image
        if (item.image.startsWith('http')) {
          formData.value.image_url = item.image
          formData.value.image_input_type = 'url'
        } else {
          formData.value.image_input_type = 'upload'
        }
      }
    }
  } catch (error) {
    console.error('[AddMenuItemView] Error loading menu item:', error)
    errors.value.general = 'Failed to load menu item'
  } finally {
    loading.value = false
  }
}

const onFileSelected = (event: Event) => {
  const input = event.target as HTMLInputElement
  const files = input.files

  if (files && files.length > 0) {
    const file = files[0]
    processImageFile(file)
  }
}

const onDragOver = (event: DragEvent) => {
  event.preventDefault()
  isDragOver.value = true
}

const onDragLeave = (event: DragEvent) => {
  event.preventDefault()
  isDragOver.value = false
}

const onDrop = (event: DragEvent) => {
  event.preventDefault()
  isDragOver.value = false

  const files = event.dataTransfer?.files
  if (files && files.length > 0) {
    const file = files[0]
    if (file.type.startsWith('image/')) {
      processImageFile(file)
    } else {
      errors.value.image = 'Please drop a valid image file'
    }
  }
}

const fileInputRef = ref<HTMLInputElement | null>(null)

const processImageFile = (file: File) => {
  if (file.size > 5 * 1024 * 1024) {
    errors.value.image = 'Image must be less than 5MB'
    return
  }

  formData.value.image = file
  formData.value.image_url = ''
  formData.value.image_input_type = 'upload'
  errors.value.image = ''

  const reader = new FileReader()
  reader.onload = (e) => {
    imagePreview.value = e.target?.result as string
  }
  reader.readAsDataURL(file)
}

const handleUrlInput = (url: string) => {
  formData.value.image_url = url
  if (url) {
    formData.value.image = null
    imagePreview.value = url
  }
}

const removeImage = () => {
  formData.value.image = null
  formData.value.image_url = ''
  imagePreview.value = ''
  errors.value.image = ''
}

const validateForm = (): boolean => {
  errors.value = {}

  if (!formData.value.name.trim()) {
    errors.value.name = 'Name is required'
  }

  if (!formData.value.price || parseFloat(formData.value.price) <= 0) {
    errors.value.price = 'Price must be greater than 0'
  }

  if (!formData.value.category) {
    errors.value.category = 'Category is required'
  }

  return Object.keys(errors.value).length === 0
}

const submitForm = async () => {
  if (!validateForm()) {
    return
  }

  submitting.value = true

  try {
    let payload: FormData | Record<string, any>

    if (formData.value.image) {
      payload = new FormData()
      payload.append('name', formData.value.name)
      payload.append('description', formData.value.description)
      payload.append('price', formData.value.price)
      payload.append('category', formData.value.category)
      payload.append('is_available', formData.value.is_available ? '1' : '0')
      payload.append('image', formData.value.image)
      if (formData.value.tax_rate_id) {
        payload.append('tax_rate_id', formData.value.tax_rate_id)
      }
      payload.append('tax_included', formData.value.tax_included ? '1' : '0')

      if (formData.value.dietary_tags.length > 0) {
        formData.value.dietary_tags.forEach((tag) => {
          ;(payload as FormData).append('dietary_tags[]', tag)
        })
      }
    } else if (formData.value.image_url) {
      payload = {
        name: formData.value.name,
        description: formData.value.description,
        price: parseFloat(formData.value.price),
        category: formData.value.category,
        tax_rate_id: formData.value.tax_rate_id || null,
        tax_included: formData.value.tax_included,
        is_available: formData.value.is_available,
        image_url: formData.value.image_url,
        dietary_tags: formData.value.dietary_tags,
      }
    } else {
      errors.value.image = 'Please upload an image file or provide an image URL'
      submitting.value = false
      return
    }

    if (isEditMode.value) {
      await menuStore.updateMenuItem(editingItemId.value, payload)
    } else {
      await menuStore.createMenuItem(payload)
    }

    await menuStore.fetchMenuItems()
    if (isMenuManagement.value) {
      router.push('/menu-management')
    } else {
      router.push({ name: 'admin-menu' })
    }
  } catch (error: any) {
    console.error('[AddMenuItemView] Error saving menu item:', error)
    errors.value.general = error.response?.data?.message || 'Failed to save menu item'
  } finally {
    submitting.value = false
  }
}

const goBack = () => {
  if (isMenuManagement.value) {
    router.push('/menu-management')
  } else {
    router.push({ name: 'admin-menu' })
  }
}

const navigateToAddCategory = () => {
  if (isMenuManagement.value) {
    router.push('/menu-management/add-category')
  } else {
    router.push({ name: 'admin-menu-add-category' })
  }
}

const loadCategories = async () => {
  categoriesLoading.value = true
  try {
    const response = await categoryService.getCategories({ is_active: true })
    if (response.data?.data && Array.isArray(response.data.data)) {
      categories.value = response.data.data
      if (categories.value.length > 0 && !formData.value.category) {
        formData.value.category = categories.value[0].slug
      }
    }
  } catch (error) {
    console.error('[AddMenuItemView] Error loading categories:', error)
  } finally {
    categoriesLoading.value = false
  }
}

onMounted(async () => {
  await Promise.all([loadCategories(), taxRateStore.fetchTaxRates()])
  if (isEditMode.value) {
    await loadMenuItemForEdit()
  } else if (taxRateStore.defaultTaxRate) {
    formData.value.tax_rate_id = taxRateStore.defaultTaxRate.id
  }
})
</script>

<style scoped>
input:focus,
textarea:focus,
select:focus {
  transition: all 0.2s ease;
}

textarea::-webkit-scrollbar {
  width: 6px;
}

textarea::-webkit-scrollbar-track {
  background: #f1f5f9;
  border-radius: 3px;
}

textarea::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 3px;
}

textarea::-webkit-scrollbar-thumb:hover {
  background: #94a3b8;
}
</style>
