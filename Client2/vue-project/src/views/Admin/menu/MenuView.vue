<template>
  <DashboardLayout>
    <div class="min-h-screen bg-slate-50">
      <!-- Header Area -->
      <div
        class="bg-gradient-to-r from-slate-50 to-white border-b border-slate-200 px-4 sm:px-6 py-5 shadow-sm"
      >
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
          <div class="flex items-center gap-3">
            <div
              class="w-10 h-10 rounded-lg bg-gradient-to-br from-indigo-600 to-indigo-700 flex items-center justify-center shadow-md"
            >
              <!-- Replaced Vuetify Icon with pure HTML/Emoji Visual Anchor -->
              <span class="text-xl text-white">🍴</span>
            </div>
            <h1 class="text-xl sm:text-2xl font-black text-slate-900">Menu Management</h1>
          </div>
          <router-link
            to="/admin/menu/add"
            class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-lg transition"
          >
            ➕ Add Menu Item
          </router-link>
        </div>
      </div>

      <div class="w-full mx-auto max-w-7xl px-4 sm:px-6 py-8">
        <!-- Main Content Actions Header -->
        <MenuHeader
          :total="store.statistics.total_items"
          title="Menu Management"
          subtitle="Manage your restaurant's complete menu"
          @create="navigateToCreate"
          @add-item="navigateToCreate"
          @export="exportMenus"
          @manage-categories="manageCategories"
        />

        <!-- Performance Statistics Component -->
        <div class="mt-8">
          <MenuStats
            :statistics="store.statistics"
            :selected-category="selectedCategory"
            :menu-items="store.menuItems"
            @select="filterByCategory"
          />
        </div>

        <!-- Categorization Filter Section -->
        <div class="mt-8">
          <div class="mb-8">
            <MenuCategoryTabs :selected="selectedCategory" @select="filterByCategory" />
          </div>

          <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
            <div>
              <h2 class="text-xl font-bold text-slate-900">
                {{
                  selectedCategory
                    ? selectedCategory.charAt(0).toUpperCase() +
                      selectedCategory.slice(1) +
                      ' Items'
                    : 'All Items'
                }}
              </h2>
              <p class="text-sm text-slate-600 mt-1">{{ store.menuItems.length }} items</p>
            </div>
          </div>
        </div>

        <!-- Display Grid Status Area -->
        <div class="mt-8">
          <!-- Loading State Spinner built using raw Tailwind CSS animation -->
          <div v-if="store.loading" class="flex flex-col items-center justify-center py-16 gap-3">
            <div
              class="w-12 h-12 border-4 border-slate-200 border-t-indigo-600 rounded-full animate-spin"
            ></div>
            <span class="text-xs font-semibold text-slate-500 tracking-wider"
              >Loading Menu Data...</span
            >
          </div>

          <!-- Empty Fallback Interface Block -->
          <div
            v-else-if="store.menuItems.length === 0"
            class="text-center py-20 bg-white rounded-2xl border border-slate-200 shadow-sm px-4"
          >
            <span class="text-4xl block mb-3">🍽️</span>
            <p class="text-2xl font-bold text-slate-900 mb-1">No Items Found</p>
            <p class="text-sm text-slate-400 mb-6">
              There are no dishes matching this selection yet.
            </p>
            <router-link
              to="/admin/menu/add"
              class="inline-flex items-center gap-2 px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-sm rounded-xl shadow transition"
            >
              ➕ Create Item
            </router-link>
          </div>

          <!-- Table Display -->
          <div v-else class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <MenuTable
              :items="store.menuItems"
              :loading="store.loading"
              @edit="editMenu"
              @delete="deleteMenu"
              @toggle="toggleAvailability"
            />

            <!-- Pagination Info -->
            <div class="px-6 py-4 border-t border-slate-200 flex items-center justify-between gap-4">
              <p class="text-sm text-slate-600">
                Showing <span class="font-semibold">{{ store.pagination.from || 1 }}</span> to
                <span class="font-semibold">{{ store.pagination.to || store.menuItems.length }}</span> of
                <span class="font-semibold">{{ store.pagination.total }}</span> items
              </p>
              
              <!-- Pagination Controls -->
              <div class="flex items-center gap-2">
                <!-- Previous Button -->
                <button
                  @click="currentPage--; loadMenu()"
                  :disabled="currentPage === 1 || store.loading"
                  class="px-3 py-1 text-sm font-semibold rounded-lg border border-slate-200 transition"
                  :class="currentPage === 1
                    ? 'text-slate-400 bg-slate-50 cursor-not-allowed'
                    : 'text-slate-700 bg-white hover:bg-slate-50 cursor-pointer'"
                >
                  ← Previous
                </button>

                <!-- Page Numbers -->
                <div class="flex items-center gap-1">
                  <button
                    v-for="page in generatePageNumbers()"
                    :key="page"
                    @click="currentPage = page; loadMenu()"
                    :disabled="store.loading"
                    class="w-8 h-8 text-sm font-semibold rounded-lg border transition"
                    :class="currentPage === page
                      ? 'bg-indigo-600 text-white border-indigo-600'
                      : 'border-slate-200 text-slate-700 bg-white hover:bg-slate-50'"
                  >
                    {{ page }}
                  </button>
                </div>

                <!-- Next Button -->
                <button
                  @click="currentPage++; loadMenu()"
                  :disabled="currentPage === store.pagination.last_page || store.loading"
                  class="px-3 py-1 text-sm font-semibold rounded-lg border border-slate-200 transition"
                  :class="currentPage === store.pagination.last_page
                    ? 'text-slate-400 bg-slate-50 cursor-not-allowed'
                    : 'text-slate-700 bg-white hover:bg-slate-50 cursor-pointer'"
                >
                  Next →
                </button>
              </div>

              <!-- Page Size Selector -->
              <div class="flex items-center gap-2">
                <label class="text-sm font-semibold text-slate-600">Items per page:</label>
                <select
                  v-model.number="pageSize"
                  @change="currentPage = 1; loadMenu()"
                  :disabled="store.loading"
                  class="px-3 py-1 text-sm font-semibold rounded-lg border border-slate-200 bg-white text-slate-700"
                >
                  <option :value="10">10</option>
                  <option :value="25">25</option>
                  <option :value="50">50</option>
                  <option :value="100">100</option>
                </select>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- No more dialog here - using view instead -->
    </div>
  </DashboardLayout>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import DashboardLayout from '@/layouts/DashboardLayout.vue'
import MenuHeader from '@/components/menu/MenuHeader.vue'
import MenuStats from '@/components/menu/MenuStats.vue'
import MenuCategoryTabs from '@/components/menu/MenuCategoryTabs.vue'
import MenuCard from '@/components/menu/MenuCard.vue'
import MenuTable from '@/components/menu/MenuTable.vue'
import { useMenuStore } from '@/stores/menuStore'
import type { MenuItem } from '@/types/menu'

const router = useRouter()
const store = useMenuStore()
const selectedCategory = ref<string | null>(null)
const currentPage = ref<number>(1)
const pageSize = ref<number>(10)

// ================== Interface Operations Functions ==================

/**
 * Filter list targets by category keyword definitions
 */
function filterByCategory(category: string | null) {
  selectedCategory.value = category
  currentPage.value = 1  // Reset to page 1 when filtering
  loadMenu()
}

/**
 * Navigate to add menu item page
 */
function navigateToCreate() {
  router.push('/admin/menu/add')
}

/**
 * Navigate to edit menu item page
 */
function editMenu(item: MenuItem) {
  router.push(`/admin/menu/add?id=${item.id}`)
}
async function deleteMenu(item: MenuItem) {
  const confirmed = window.confirm(`Are you sure you want to delete "${item.name}"?`)
  if (!confirmed) return
  try {
    await store.deleteMenuItem(item.id)
    await refreshPage()
  } catch (error: any) {
    alert(error.response?.data?.message || 'Failed to delete menu item')
  }
}

/**
 * Flip target element presence indicator values
 */
async function toggleAvailability(item: MenuItem) {
  try {
    await store.toggleAvailability(item.id)
    await refreshPage()
  } catch (error: any) {
    alert(error.response?.data?.message || 'Failed to update availability')
  }
}

/**
 * Query structural state array indices from back-end
 */
async function loadMenu() {
  console.log('🔵 loadMenu() called with page:', currentPage.value)
  const filters: any = {
    page: currentPage.value,
    per_page: pageSize.value,
  }
  if (selectedCategory.value) {
    filters.category = selectedCategory.value
  }
  try {
    await store.fetchMenuItems(filters)
    console.log('✅ Menu loaded:', store.menuItems.length, 'items')
  } catch (error) {
    console.error('❌ Error loading menu:', error)
  }
}

/**
 * Fetch top header statistical metrics values from state storage API
 */
async function loadStatistics() {
  await store.fetchStatistics()
}

/**
 * Force refresh wrapper across all components
 */
async function refreshPage() {
  await Promise.all([loadMenu(), loadStatistics()])
}

// ================== Extra Utility Mock Handlers ==================
function exportMenus() {
  alert('Exporting structural summary list sheet...')
}

function manageCategories() {
  alert('Opening category configuration panel view drawers...')
}

/**
 * Generate page numbers for pagination display
 */
function generatePageNumbers() {
  const pages: number[] = []
  const totalPages = store.pagination.last_page
  const currentP = currentPage.value
  
  // Always show first page
  pages.push(1)
  
  // Show pages around current page
  for (let i = Math.max(2, currentP - 1); i <= Math.min(totalPages - 1, currentP + 1); i++) {
    if (!pages.includes(i)) pages.push(i)
  }
  
  // Always show last page if more than 1
  if (totalPages > 1 && !pages.includes(totalPages)) {
    pages.push(totalPages)
  }
  
  return pages.sort((a, b) => a - b)
}

// Lifecycle registration initialization hook
onMounted(async () => {
  await refreshPage()
})
</script>
