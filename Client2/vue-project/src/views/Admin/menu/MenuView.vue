<template>
  <DashboardLayout>
    <div class="min-h-screen bg-slate-50">
      <!-- Header Area - Responsive -->
      <div
        class="bg-gradient-to-r from-slate-50 to-white border-b border-slate-200 px-4 sm:px-6 py-4 sm:py-5 shadow-sm"
      >
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 sm:gap-4">
          <div class="flex items-center gap-2 sm:gap-3">
            <div
              class="w-10 h-10 rounded-lg bg-gradient-to-br from-indigo-600 to-indigo-700 flex items-center justify-center shadow-md flex-shrink-0"
            >
              <span class="text-xl text-white">🍴</span>
            </div>
            <h1 class="text-lg sm:text-2xl font-black text-slate-900">Menu Management</h1>
          </div>
          <router-link
            to="/admin/menu/add"
            class="px-3 sm:px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-lg transition text-sm sm:text-base"
          >
            ➕ Add Menu Item
          </router-link>
        </div>
      </div>

      <!-- Main Content - Responsive -->
      <div class="w-full mx-auto max-w-7xl px-4 sm:px-6 py-6 sm:py-8">
        <!-- Header Section -->
        <MenuHeader
          :total="store.statistics.total_items"
          title="Menu Management"
          subtitle="Manage your restaurant's complete menu"
          @create="navigateToCreate"
          @add-item="navigateToCreate"
          @export="exportMenus"
          @manage-categories="manageCategories"
        />

        <!-- Statistics Section -->
        <div class="mt-6 sm:mt-8">
          <MenuStats
            :statistics="store.statistics"
            :selected-category="selectedCategory"
            :menu-items="store.menuItems"
            @select="filterByCategory"
          />
        </div>

        <!-- Filter Section -->
        <div class="mt-6 sm:mt-8">
          <div class="mb-6 sm:mb-8">
            <MenuCategoryTabs :selected="selectedCategory" @select="filterByCategory" />
          </div>

          <!-- Section Title -->
          <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
            <div>
              <h2 class="text-lg sm:text-xl font-bold text-slate-900">
                {{
                  selectedCategory
                    ? selectedCategory.charAt(0).toUpperCase() +
                      selectedCategory.slice(1) +
                      ' Items'
                    : 'All Items'
                }}
              </h2>
              <p class="text-xs sm:text-sm text-slate-600 mt-1">
                {{ store.menuItems.length }} items
              </p>
            </div>
          </div>
        </div>

        <!-- Display Content Area -->
        <div class="mt-8">
          <!-- Loading State -->
          <div
            v-if="store.loading"
            class="flex flex-col items-center justify-center py-12 sm:py-16 gap-3"
          >
            <div
              class="w-12 h-12 border-4 border-slate-200 border-t-indigo-600 rounded-full animate-spin"
            ></div>
            <span class="text-xs font-semibold text-slate-500 tracking-wider"
              >Loading Menu Data...</span
            >
          </div>

          <!-- Empty State -->
          <div
            v-else-if="store.menuItems.length === 0"
            class="text-center py-12 sm:py-20 bg-white rounded-2xl border border-slate-200 shadow-sm px-4"
          >
            <span class="text-3xl sm:text-4xl block mb-3">🍽️</span>
            <p class="text-lg sm:text-2xl font-bold text-slate-900 mb-1">No Items Found</p>
            <p class="text-xs sm:text-sm text-slate-400 mb-6">
              There are no dishes matching this selection yet.
            </p>
            <router-link
              to="/admin/menu/add"
              class="inline-flex items-center gap-2 px-4 sm:px-5 py-2 sm:py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs sm:text-sm rounded-xl shadow transition"
            >
              ➕ Create Item
            </router-link>
          </div>

          <!-- Table Display - Responsive Overflow -->
          <div v-else class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-x-auto">
            <MenuTable
              :items="store.menuItems"
              :loading="store.loading"
              @edit="editMenu"
              @delete="deleteMenu"
              @toggle="toggleAvailability"
            />

            <!-- Pagination Section - Responsive Layout -->
            <div class="border-t border-slate-200 px-4 sm:px-6 py-3 sm:py-4 space-y-4 sm:space-y-0">
              <!-- Info Row -->
              <p class="text-xs sm:text-sm text-slate-600">
                Showing <span class="font-semibold">{{ store.pagination.from || 1 }}</span> to
                <span class="font-semibold">{{
                  store.pagination.to || store.menuItems.length
                }}</span>
                of <span class="font-semibold">{{ store.pagination.total }}</span> items
              </p>

              <!-- Pagination Controls - Stack on Mobile -->
              <div
                class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 sm:gap-4"
              >
                <!-- Previous/Next Buttons -->
                <div class="flex items-center gap-2">
                  <button
                    @click="
                      () => {
                        currentPage--
                        loadMenu()
                      }
                    "
                    :disabled="currentPage === 1 || store.loading"
                    class="px-2 sm:px-3 py-1 text-xs sm:text-sm font-semibold rounded-lg border border-slate-200 transition"
                    :class="
                      currentPage === 1
                        ? 'text-slate-400 bg-slate-50 cursor-not-allowed'
                        : 'text-slate-700 bg-white hover:bg-slate-50 cursor-pointer'
                    "
                  >
                    ← Prev
                  </button>

                  <!-- Page Numbers - Hidden on very small screens -->
                  <div class="hidden sm:flex items-center gap-1">
                    <button
                      v-for="page in generatePageNumbers()"
                      :key="page"
                      @click="
                        () => {
                          currentPage = page
                          loadMenu()
                        }
                      "
                      :disabled="store.loading"
                      class="w-8 h-8 text-xs sm:text-sm font-semibold rounded-lg border transition"
                      :class="
                        currentPage === page
                          ? 'bg-indigo-600 text-white border-indigo-600'
                          : 'border-slate-200 text-slate-700 bg-white hover:bg-slate-50'
                      "
                    >
                      {{ page }}
                    </button>
                  </div>

                  <button
                    @click="
                      () => {
                        currentPage++
                        loadMenu()
                      }
                    "
                    :disabled="currentPage === store.pagination.last_page || store.loading"
                    class="px-2 sm:px-3 py-1 text-xs sm:text-sm font-semibold rounded-lg border border-slate-200 transition"
                    :class="
                      currentPage === store.pagination.last_page
                        ? 'text-slate-400 bg-slate-50 cursor-not-allowed'
                        : 'text-slate-700 bg-white hover:bg-slate-50 cursor-pointer'
                    "
                  >
                    Next →
                  </button>
                </div>

                <!-- Page Size Selector - Full Width on Mobile -->
                <div class="flex items-center gap-2">
                  <label class="text-xs sm:text-sm font-semibold text-slate-600 whitespace-nowrap"
                    >Per page:</label
                  >
                  <select
                    v-model.number="pageSize"
                    @change="
                      () => {
                        currentPage = 1
                        loadMenu()
                      }
                    "
                    :disabled="store.loading"
                    class="px-2 sm:px-3 py-1 text-xs sm:text-sm font-semibold rounded-lg border border-slate-200 bg-white text-slate-700"
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
      </div>
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
  currentPage.value = 1 // Reset to page 1 when filtering
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
