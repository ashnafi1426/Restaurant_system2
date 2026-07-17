<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import categoryService from '@/services/categoryService'

interface Category {
  id: string
  name: string
  slug: string
  description?: string
  icon?: string
  display_order: number
  is_active: boolean
  menu_items_count?: number
}

interface Props {
  modelValue: boolean
}

const props = defineProps<Props>()
const emit = defineEmits<{
  (e: 'update:modelValue', value: boolean): void
  (e: 'category-created'): void
}>()

// State
const categories = ref<Category[]>([])
const isLoading = ref(false)
const showAddForm = ref(false)
const editingId = ref<string | null>(null)
const formData = ref({
  name: '',
  description: '',
  icon: '🍽️',
  display_order: 0,
  is_active: true,
})

// Common Emojis for categories
const commonEmojis = ['☀️', '🍔', '🍲', '🍕', '🍝', '🍰', '🍹', '🥗', '🍜', '🥘', '🍱', '🥗', '🍛']

// Computed
const isEditing = computed(() => editingId.value !== null)
const formTitle = computed(() => (isEditing.value ? 'Edit Category' : 'Add New Category'))
const submitButtonText = computed(() => (isEditing.value ? 'Update Category' : 'Create Category'))

// Methods
async function loadCategories() {
  isLoading.value = true
  try {
    const response = await categoryService.getCategories()
    categories.value = response.data.data || []
  } catch (error) {
    console.error('Failed to load categories:', error)
    alert('Failed to load categories')
  } finally {
    isLoading.value = false
  }
}

function resetForm() {
  formData.value = {
    name: '',
    description: '',
    icon: '🍽️',
    display_order: categories.value.length,
    is_active: true,
  }
  editingId.value = null
}

function editCategory(category: Category) {
  formData.value = {
    name: category.name,
    description: category.description || '',
    icon: category.icon || '🍽️',
    display_order: category.display_order,
    is_active: category.is_active,
  }
  editingId.value = category.id
  showAddForm.value = true
}

async function submitForm() {
  if (!formData.value.name.trim()) {
    alert('Please enter a category name')
    return
  }

  try {
    if (isEditing.value) {
      // Update category
      await categoryService.updateCategory(editingId.value!, formData.value)
      alert('Category updated successfully!')
    } else {
      // Create new category
      await categoryService.createCategory(formData.value)
      alert('Category created successfully!')
      emit('category-created')
    }

    await loadCategories()
    showAddForm.value = false
    resetForm()
  } catch (error: any) {
    const message = error.response?.data?.message || 'Failed to save category'
    alert(message)
    console.error(error)
  }
}

async function deleteCategory(id: string, name: string) {
  if (!window.confirm(`Are you sure you want to delete "${name}"?`)) {
    return
  }

  try {
    await categoryService.deleteCategory(id)
    alert('Category deleted successfully!')
    await loadCategories()
  } catch (error: any) {
    const message = error.response?.data?.message || 'Failed to delete category'
    alert(message)
    console.error(error)
  }
}

async function toggleActive(category: Category) {
  try {
    await categoryService.toggleCategory(category.id)
    category.is_active = !category.is_active
    alert(category.is_active ? 'Category activated!' : 'Category deactivated!')
  } catch (error: any) {
    const message = error.response?.data?.message || 'Failed to toggle category'
    alert(message)
    console.error(error)
  }
}

function closeDialog() {
  emit('update:modelValue', false)
  showAddForm.value = false
  resetForm()
}

function cancelForm() {
  showAddForm.value = false
  resetForm()
}

function openAddForm() {
  showAddForm.value = true
  resetForm()
}

// Lifecycle
onMounted(() => {
  if (props.modelValue) {
    loadCategories()
  }
})
</script>

<template>
  <Teleport to="body">
    <Transition name="fade">
      <div
        v-if="modelValue"
        class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4"
        @click.self="closeDialog"
      >
        <div class="bg-white rounded-2xl shadow-2xl max-w-4xl w-full max-h-[90vh] overflow-hidden flex flex-col">
          <!-- Header -->
          <div class="bg-gradient-to-r from-indigo-600 to-indigo-700 px-6 py-4 flex items-center justify-between">
            <h2 class="text-xl font-bold text-white">Manage Categories</h2>
            <button
              @click="closeDialog"
              class="text-white hover:bg-white/20 p-2 rounded-lg transition-colors"
            >
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
              </svg>
            </button>
          </div>

          <!-- Main Content -->
          <div class="flex-1 overflow-y-auto">
            <div class="p-6">
              <!-- Add Form -->
              <div v-if="showAddForm" class="mb-8 p-4 bg-indigo-50 rounded-lg border border-indigo-200">
                <h3 class="text-lg font-bold text-gray-900 mb-4">{{ formTitle }}</h3>

                <div class="space-y-4">
                  <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Category Name *</label>
                    <input
                      v-model="formData.name"
                      type="text"
                      placeholder="e.g., Breakfast"
                      class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-indigo-600"
                    />
                  </div>

                  <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Description</label>
                    <textarea
                      v-model="formData.description"
                      placeholder="Brief description of the category"
                      class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-indigo-600 h-20 resize-none"
                    ></textarea>
                  </div>

                  <div class="grid grid-cols-2 gap-4">
                    <div>
                      <label class="block text-sm font-semibold text-gray-700 mb-2">Icon</label>
                      <select
                        v-model="formData.icon"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-indigo-600"
                      >
                        <option v-for="emoji in commonEmojis" :key="emoji" :value="emoji">
                          {{ emoji }} {{ emoji }}
                        </option>
                      </select>
                    </div>

                    <div>
                      <label class="block text-sm font-semibold text-gray-700 mb-2">Display Order</label>
                      <input
                        v-model.number="formData.display_order"
                        type="number"
                        min="0"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-indigo-600"
                      />
                    </div>
                  </div>

                  <div>
                    <label class="flex items-center gap-2 cursor-pointer">
                      <input
                        v-model="formData.is_active"
                        type="checkbox"
                        class="w-4 h-4 border border-gray-300 rounded focus:outline-none focus:border-indigo-600"
                      />
                      <span class="text-sm font-semibold text-gray-700">Active</span>
                    </label>
                  </div>

                  <div class="flex gap-3 pt-4">
                    <button
                      @click="submitForm"
                      class="flex-1 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-lg transition"
                    >
                      {{ submitButtonText }}
                    </button>
                    <button
                      @click="cancelForm"
                      class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 font-semibold rounded-lg hover:bg-gray-50 transition"
                    >
                      Cancel
                    </button>
                  </div>
                </div>
              </div>

              <!-- Categories List -->
              <div v-if="!showAddForm">
                <!-- Add Button -->
                <button
                  @click="openAddForm"
                  class="mb-6 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-lg transition flex items-center gap-2"
                >
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                  </svg>
                  Add New Category
                </button>

                <!-- Loading State -->
                <div v-if="isLoading" class="flex justify-center py-8">
                  <div class="w-8 h-8 border-4 border-indigo-200 border-t-indigo-600 rounded-full animate-spin"></div>
                </div>

                <!-- Categories Table -->
                <div v-else-if="categories.length > 0" class="overflow-x-auto">
                  <table class="w-full border-collapse">
                    <thead>
                      <tr class="bg-gray-50 border-b border-gray-200">
                        <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Icon</th>
                        <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Name</th>
                        <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Description</th>
                        <th class="px-4 py-3 text-center text-sm font-semibold text-gray-700">Items</th>
                        <th class="px-4 py-3 text-center text-sm font-semibold text-gray-700">Status</th>
                        <th class="px-4 py-3 text-center text-sm font-semibold text-gray-700">Actions</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="category in categories" :key="category.id" class="border-b border-gray-200 hover:bg-gray-50">
                        <td class="px-4 py-3">
                          <span class="text-2xl">{{ category.icon }}</span>
                        </td>
                        <td class="px-4 py-3 font-semibold text-gray-900">{{ category.name }}</td>
                        <td class="px-4 py-3 text-sm text-gray-600">
                          {{ category.description || '-' }}
                        </td>
                        <td class="px-4 py-3 text-center">
                          <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                            {{ category.menu_items_count || 0 }}
                          </span>
                        </td>
                        <td class="px-4 py-3 text-center">
                          <button
                            @click="toggleActive(category)"
                            :class="[
                              'px-3 py-1 rounded-full text-sm font-semibold transition',
                              category.is_active
                                ? 'bg-green-100 text-green-800 hover:bg-green-200'
                                : 'bg-gray-100 text-gray-800 hover:bg-gray-200',
                            ]"
                          >
                            {{ category.is_active ? 'Active' : 'Inactive' }}
                          </button>
                        </td>
                        <td class="px-4 py-3 text-center space-x-2">
                          <button
                            @click="editCategory(category)"
                            class="text-indigo-600 hover:text-indigo-800 font-semibold transition"
                            title="Edit"
                          >
                            ✏️
                          </button>
                          <button
                            @click="deleteCategory(category.id, category.name)"
                            class="text-red-600 hover:text-red-800 font-semibold transition"
                            title="Delete"
                          >
                            🗑️
                          </button>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>

                <!-- Empty State -->
                <div v-else class="text-center py-8">
                  <span class="text-4xl block mb-3">📭</span>
                  <p class="text-gray-600 font-semibold">No categories found. Create your first one!</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
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
