<template>
  <DashboardLayout>
    <!-- Page Header -->
    <template #header>
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold text-slate-900">
            {{ isEditMode ? 'Edit Menu Item' : 'Add New Menu Item' }}
          </h1>
          <p class="text-slate-600 mt-1">
            {{
              isEditMode
                ? 'Update the menu item details'
                : 'Create a new menu item for your restaurant'
            }}
          </p>
        </div>
        <button
          @click="goBack"
          class="px-4 py-2 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50 transition-colors"
        >
          ← Back to Menu
        </button>
      </div>
    </template>

    <!-- Loading State -->
    <div v-if="loading" class="flex items-center justify-center py-12">
      <div class="flex flex-col items-center gap-4">
        <div
          class="w-12 h-12 border-4 border-emerald-200 border-t-emerald-500 rounded-full animate-spin"
        ></div>
        <p class="text-slate-600">Loading menu item...</p>
      </div>
    </div>

    <!-- Form Container -->
    <div v-else class="max-w-4xl mx-auto">
      <!-- Error Alert -->
      <div v-if="errors.general" class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
        <p class="text-red-700 font-medium">{{ errors.general }}</p>
      </div>

      <form @submit.prevent="submitForm" class="space-y-6">
        <!-- Main Content Row: Left Form + Right Image -->
        <div class="flex gap-6">
          <!-- LEFT SIDE: Form Fields -->
          <div class="flex-1 space-y-6">
            <!-- Item Name & Price -->
            <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-sm">
              <div class="space-y-4">
                <!-- Item Name -->
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

                <!-- Price & Category Row -->
                <div class="grid grid-cols-2 gap-4">
                  <!-- Price -->
                  <div>
                    <label
                      for="price"
                      class="block text-xs font-semibold text-slate-600 uppercase mb-2"
                    >
                      Price <span class="text-red-500">*</span>
                    </label>
                    <div class="flex items-center gap-2">
                      <span class="text-slate-600 font-medium">$</span>
                      <input
                        id="price"
                        v-model="formData.price"
                        type="number"
                        placeholder="0.00"
                        step="0.01"
                        min="0"
                        class="flex-1 px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent text-sm"
                        :class="{ 'border-red-500 focus:ring-red-500': errors.price }"
                      />
                    </div>
                    <p v-if="errors.price" class="text-red-600 text-xs mt-1">{{ errors.price }}</p>
                  </div>

                  <!-- Category -->
                  <div>
                    <label
                      for="category"
                      class="block text-xs font-semibold text-slate-600 uppercase mb-2"
                    >
                      Category <span class="text-red-500">*</span>
                    </label>
                    <select
                      id="category"
                      v-model="formData.category"
                      class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent text-sm"
                      :class="{ 'border-red-500 focus:ring-red-500': errors.category }"
                    >
                      <option value="">Select category...</option>
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

            <!-- Description -->
            <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-sm">
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
            <!-- Publish Status -->
            <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-sm">
              <div class="flex items-center justify-between">
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

          <!-- RIGHT SIDE: Image Upload -->
          <div class="w-80">
            <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-sm sticky top-6">
              <label class="block text-xs font-semibold text-slate-600 uppercase mb-4">
                Item Image
              </label>

              <!-- Image Preview -->
              <div v-if="imagePreview" class="mb-4">
                <div class="relative inline-block w-full">
                  <img
                    :src="imagePreview"
                    alt="Preview"
                    class="w-full h-48 object-cover rounded-lg border-2 border-emerald-200"
                  />
                  <button
                    type="button"
                    @click="removeImage"
                    class="absolute -top-2 -right-2 w-7 h-7 bg-red-500 text-white rounded-full flex items-center justify-center hover:bg-red-600 transition-colors shadow-lg text-sm font-bold"
                  >
                    ✕
                  </button>
                </div>
              </div>

              <!-- Upload/URL Buttons -->
              <div class="flex gap-2 mb-4">
                <button
                  type="button"
                  @click="formData.image_input_type = 'upload'"
                  class="flex-1 px-3 py-2 rounded-lg font-semibold text-sm transition-all"
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
                  class="flex-1 px-3 py-2 rounded-lg font-semibold text-sm transition-all"
                  :class="
                    formData.image_input_type === 'url'
                      ? 'bg-emerald-500 text-white hover:bg-emerald-600'
                      : 'border border-slate-300 text-slate-700 hover:bg-slate-50'
                  "
                >
                  URL
                </button>
              </div>

              <!-- Upload Tab Content -->
              <div v-show="formData.image_input_type === 'upload'" class="space-y-3">
                <!-- Drag and Drop Zone -->
                <div
                  @dragover="onDragOver"
                  @dragleave="onDragLeave"
                  @drop="onDrop"
                  class="border-2 border-dashed rounded-lg p-6 text-center transition-colors cursor-pointer"
                  :class="[
                    isDragOver
                      ? 'border-emerald-500 bg-emerald-50'
                      : 'border-slate-300 bg-slate-50 hover:border-slate-400',
                  ]"
                >
                  <div class="flex flex-col items-center gap-2">
                    <svg
                      class="w-8 h-8 text-slate-400"
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
                    <p class="text-xs font-medium text-slate-900">Drop image here</p>
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
                  class="w-full px-3 py-2 border border-slate-300 rounded-lg hover:bg-slate-50 text-sm font-medium text-slate-700 transition-colors"
                >
                  Choose file...
                </button>

                <p v-if="errors.image" class="text-red-600 text-xs">{{ errors.image }}</p>
                <p class="text-xs text-slate-500">PNG, JPG, GIF, WebP • Max 5MB</p>
              </div>

              <!-- URL Tab Content -->
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

        <!-- Form Actions -->
        <div class="flex gap-3 items-center justify-end mt-8 pt-6 border-t border-slate-200">
          <button
            type="button"
            @click="goBack"
            class="px-6 py-2 rounded-lg border border-slate-300 text-slate-700 font-medium hover:bg-slate-50 transition-colors"
          >
            Cancel
          </button>
          <button
            type="submit"
            :disabled="!isFormValid || submitting"
            class="px-8 py-2 rounded-lg bg-emerald-500 text-white font-medium hover:bg-emerald-600 disabled:bg-slate-300 disabled:cursor-not-allowed transition-colors flex items-center gap-2"
          >
            <span
              v-if="submitting"
              class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"
            ></span>
            <span>{{ submitting ? 'Saving...' : isEditMode ? 'Update Item' : 'Add Item' }}</span>
          </button>
        </div>
      </form>
    </div>
  </DashboardLayout>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import DashboardLayout from '@/layouts/DashboardLayout.vue'
import menuService from '@/services/menuService'
import { useMenuStore } from '@/stores/menuStore'
import type { MenuItem } from '@/types/menu'

// Router & Route
const route = useRoute()
const router = useRouter()

// Store
const menuStore = useMenuStore()

// State
const formData = ref({
  name: '',
  description: '',
  price: '',
  category: 'breakfast',
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

// Edit mode
const isEditMode = computed(() => !!route.query.id)
const editingItemId = computed(() => route.query.id as string)

// Dietary tags options
const dietaryTagsOptions = [
  { value: 'vegetarian', label: 'Vegetarian' },
  { value: 'vegan', label: 'Vegan' },
  { value: 'gluten-free', label: 'Gluten Free' },
  { value: 'dairy-free', label: 'Dairy Free' },
  { value: 'nut-free', label: 'Nut Free' },
  { value: 'spicy', label: 'Spicy' },
  { value: 'low-carb', label: 'Low Carb' },
]

// Category options
const categoryOptions = [
  { value: 'breakfast', label: 'Breakfast' },
  { value: 'lunch', label: 'Lunch' },
  { value: 'dinner', label: 'Dinner' },
  { value: 'dessert', label: 'Dessert' },
  { value: 'drinks', label: 'Drinks' },
  { value: 'appetizers', label: 'Appetizers' },
]

// Computed
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

// Methods
const loadMenuItemForEdit = async () => {
  if (!isEditMode.value) return

  loading.value = true
  try {
    const response = await menuService.getMenus({ id: editingItemId.value })

    // Extract the item from response
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
      formData.value.is_available = item.is_available
      formData.value.dietary_tags = item.dietary_tags || []

      if (item.image) {
        imagePreview.value = item.image
        // Check if image is a URL or a local file
        if (item.image.startsWith('http')) {
          formData.value.image_url = item.image
          formData.value.image_input_type = 'url'
        } else {
          formData.value.image_input_type = 'upload'
        }
      }
    }
  } catch (error) {
    console.error('Error loading menu item:', error)
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
  // Validate file size (max 5MB)
  if (file.size > 5 * 1024 * 1024) {
    errors.value.image = 'Image must be less than 5MB'
    return
  }

  formData.value.image = file
  formData.value.image_url = ''
  formData.value.image_input_type = 'upload'
  errors.value.image = ''

  // Create preview
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
    // Prepare form data
    let payload: FormData | Record<string, any>

    if (formData.value.image) {
      // Use FormData for file upload
      payload = new FormData()
      payload.append('name', formData.value.name)
      payload.append('description', formData.value.description)
      payload.append('price', formData.value.price)
      payload.append('category', formData.value.category)
      payload.append('is_available', formData.value.is_available ? '1' : '0')
      payload.append('image', formData.value.image)

      console.log('📤 FormData payload prepared for file upload')
      console.log('✅ File:', formData.value.image.name)
      console.log('✅ Name:', formData.value.name)
      console.log('✅ Category:', formData.value.category)
      console.log('✅ Price:', formData.value.price)

      if (formData.value.dietary_tags.length > 0) {
        formData.value.dietary_tags.forEach((tag) => {
          ;(payload as FormData).append('dietary_tags[]', tag)
        })
      }
    } else if (formData.value.image_url) {
      // Use JSON with image_url
      payload = {
        name: formData.value.name,
        description: formData.value.description,
        price: parseFloat(formData.value.price),
        category: formData.value.category,
        is_available: formData.value.is_available,
        image_url: formData.value.image_url,
        dietary_tags: formData.value.dietary_tags,
      }
      console.log('📤 JSON payload prepared for URL image', payload)
    } else {
      // No image provided
      payload = {
        name: formData.value.name,
        description: formData.value.description,
        price: parseFloat(formData.value.price),
        category: formData.value.category,
        is_available: formData.value.is_available,
        dietary_tags: formData.value.dietary_tags,
      }
      console.log('⚠️ No image provided')
    }

    if (isEditMode.value) {
      // Update existing item
      await menuStore.updateMenuItem(editingItemId.value, payload)
    } else {
      // Create new item
      await menuStore.createMenuItem(payload)
    }

    // Refresh menu items
    await menuStore.fetchMenuItems()

    // Redirect to menu view
    router.push({ name: 'admin-menu' })
  } catch (error: any) {
    console.error('❌ Error submitting form:', error)

    // Log ALL error response data
    console.error(' Full error response:', error.response?.data)

    // Log validation errors if available
    if (error.response?.data?.errors) {
      console.error('📋 Validation Errors:', error.response.data.errors)
      console.table(error.response.data.errors)
    } else if (error.response?.data?.message) {
      console.error('📋 Error Message:', error.response.data.message)
    }

    errors.value.general = error.response?.data?.message || 'Failed to save menu item'
  } finally {
    submitting.value = false
  }
}

const goBack = () => {
  router.push({ name: 'admin-menu' })
}

// Lifecycle
onMounted(async () => {
  if (isEditMode.value) {
    await loadMenuItemForEdit()
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
