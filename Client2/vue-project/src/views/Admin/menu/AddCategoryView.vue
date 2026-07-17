<template>
  <DashboardLayout>
    <!-- Page Header - Responsive -->
    <template #header>
      <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 sm:gap-4">
        <div>
          <h1 class="text-2xl sm:text-3xl font-bold text-slate-900">Add New Category</h1>
          <p class="text-xs sm:text-sm text-slate-600 mt-1">Create a new menu category</p>
        </div>
        <button
          @click="goBack"
          class="w-full sm:w-auto px-3 sm:px-4 py-2 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50 transition-colors text-sm sm:text-base"
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
        <p class="text-slate-600 text-sm">Loading...</p>
      </div>
    </div>

    <!-- Form Container - Responsive Width -->
    <div v-else class="w-full max-w-2xl mx-auto px-3 sm:px-6 py-4 sm:py-6">
      <!-- Error Alert -->
      <div v-if="errors.general" class="mb-4 sm:mb-6 p-3 sm:p-4 bg-red-50 border border-red-200 rounded-lg">
        <p class="text-red-700 font-medium text-sm sm:text-base">{{ errors.general }}</p>
      </div>

      <form @submit.prevent="submitForm" class="space-y-4 sm:space-y-6">
        <!-- Category Name -->
        <div class="bg-white rounded-xl border border-slate-200 p-4 sm:p-6 shadow-sm">
          <label
            for="name"
            class="block text-xs font-semibold text-slate-600 uppercase mb-2"
          >
            Category Name <span class="text-red-500">*</span>
          </label>
          <input
            id="name"
            v-model="formData.name"
            type="text"
            placeholder="e.g., Breakfast, Lunch, Appetizers"
            class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent text-sm sm:text-base"
            :class="{ 'border-red-500 focus:ring-red-500': errors.name }"
          />
          <p v-if="errors.name" class="text-red-600 text-xs mt-1">{{ errors.name }}</p>
        </div>

        <!-- Description -->
        <div class="bg-white rounded-xl border border-slate-200 p-4 sm:p-6 shadow-sm">
          <label
            for="description"
            class="block text-xs font-semibold text-slate-600 uppercase mb-2"
          >
            Description <span class="text-slate-400">(optional)</span>
          </label>
          <textarea
            id="description"
            v-model="formData.description"
            rows="3"
            placeholder="Add a description for this category..."
            class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent resize-none text-sm sm:text-base"
          ></textarea>
        </div>

        <!-- Icon & Display Order Row - Responsive Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">
          <!-- Icon Selection -->
          <div class="bg-white rounded-xl border border-slate-200 p-4 sm:p-6 shadow-sm">
            <label
              for="icon"
              class="block text-xs font-semibold text-slate-600 uppercase mb-2"
            >
              Icon <span class="text-slate-400">(optional)</span>
            </label>
            <select
              id="icon"
              v-model="formData.icon"
              class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent text-sm"
            >
              <option value="">Select an icon...</option>
              <option value="sun">☀️ Sun (Breakfast)</option>
              <option value="leaf">🍃 Leaf (Vegetarian)</option>
              <option value="soup">🍲 Soup</option>
              <option value="utensils">🍽️ Utensils (Main Course)</option>
              <option value="pizza">🍕 Pizza</option>
              <option value="flame">🔥 Flame (Spicy/Pasta)</option>
              <option value="cake">🍰 Cake (Dessert)</option>
              <option value="wine">🍷 Wine (Drinks)</option>
              <option value="coffee">☕ Coffee</option>
              <option value="apple">🍎 Apple (Healthy)</option>
            </select>
            <p v-if="errors.icon" class="text-red-600 text-xs mt-1">{{ errors.icon }}</p>
          </div>

          <!-- Display Order -->
          <div class="bg-white rounded-xl border border-slate-200 p-4 sm:p-6 shadow-sm">
            <label
              for="display_order"
              class="block text-xs font-semibold text-slate-600 uppercase mb-2"
            >
              Display Order <span class="text-slate-400">(optional)</span>
            </label>
            <input
              id="display_order"
              v-model="formData.display_order"
              type="number"
              min="0"
              placeholder="0"
              class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent text-sm"
            />
            <p class="text-xs text-slate-500 mt-1">Order in which category appears (0 = first)</p>
          </div>
        </div>

        <!-- Active Status -->
        <div class="bg-white rounded-xl border border-slate-200 p-4 sm:p-6 shadow-sm">
          <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
              <label class="text-sm font-semibold text-slate-900">Status</label>
              <p class="text-xs text-slate-600 mt-1">Category visibility</p>
            </div>
            <div class="flex items-center gap-3">
              <input
                id="is_active"
                v-model="formData.is_active"
                type="checkbox"
                class="w-4 h-4 rounded accent-emerald-500 cursor-pointer"
              />
              <label for="is_active" class="cursor-pointer">
                <span
                  class="text-sm font-medium"
                  :class="formData.is_active ? 'text-emerald-600' : 'text-slate-600'"
                >
                  {{ formData.is_active ? '✓ Active' : '○ Inactive' }}
                </span>
              </label>
            </div>
          </div>
        </div>

        <!-- Form Actions - Responsive -->
        <div class="flex flex-col-reverse sm:flex-row gap-2 sm:gap-3 items-stretch sm:items-center justify-end mt-6 sm:mt-8 pt-4 sm:pt-6 border-t border-slate-200">
          <button
            type="button"
            @click="goBack"
            class="px-4 sm:px-6 py-2 rounded-lg border border-slate-300 text-slate-700 font-medium hover:bg-slate-50 transition-colors text-sm sm:text-base"
          >
            Cancel
          </button>
          <button
            type="submit"
            :disabled="!isFormValid || submitting"
            class="px-6 sm:px-8 py-2 rounded-lg bg-emerald-500 text-white font-medium hover:bg-emerald-600 disabled:bg-slate-300 disabled:cursor-not-allowed transition-colors flex items-center justify-center gap-2 text-sm sm:text-base"
          >
            <span
              v-if="submitting"
              class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"
            ></span>
            <span>{{ submitting ? 'Creating...' : 'Create Category' }}</span>
          </button>
        </div>
      </form>
    </div>
  </DashboardLayout>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import DashboardLayout from '@/layouts/DashboardLayout.vue'
import categoryService from '@/services/categoryService'

// Router
const router = useRouter()

// State
const formData = ref({
  name: '',
  description: '',
  icon: '',
  display_order: 0,
  is_active: true,
})

const loading = ref(false)
const submitting = ref(false)
const errors = ref<Record<string, string>>({})

// Computed
const isFormValid = computed(() => {
  return formData.value.name.trim().length > 0
})

// Methods
const validateForm = (): boolean => {
  errors.value = {}

  if (!formData.value.name.trim()) {
    errors.value.name = 'Category name is required'
  }

  return Object.keys(errors.value).length === 0
}

const submitForm = async () => {
  if (!validateForm()) {
    return
  }

  submitting.value = true

  try {
    const payload = {
      name: formData.value.name,
      description: formData.value.description || null,
      icon: formData.value.icon || null,
      display_order: formData.value.display_order || 0,
      is_active: formData.value.is_active ? 1 : 0,
    }

    console.log('📤 Creating category with payload:', payload)

    await categoryService.createCategory(payload)

    console.log('✅ Category created successfully')

    // Redirect back to menu
    router.push({ name: 'admin-menu' })
  } catch (error: any) {
    console.error('❌ Error creating category:', error)

    if (error.response?.data?.message) {
      errors.value.general = error.response.data.message
    } else if (error.response?.data?.errors) {
      console.error('📋 Validation Errors:', error.response.data.errors)
      errors.value.general = 'Please check your input and try again'
    } else {
      errors.value.general = 'Failed to create category'
    }
  } finally {
    submitting.value = false
  }
}

const goBack = () => {
  router.push({ name: 'admin-menu' })
}
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
