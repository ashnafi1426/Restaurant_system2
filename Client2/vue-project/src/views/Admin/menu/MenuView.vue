<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import MenuStats from '@/components/menu/MenuStats.vue'
import MenuTable from '@/components/menu/MenuTable.vue'
import { useMenuStore } from '@/stores/menuStore'
import { useAuthStore } from '@/stores/auth'
import {
  UtensilsCrossed,
  Search,
  Filter,
  X,
  RefreshCw,
  Maximize2,
  Minimize2,
  Plus,
  RotateCcw,
  Utensils,
  ChevronLeft,
  ChevronRight,
} from 'lucide-vue-next'
import type { MenuItem } from '@/types/menu'

const route = useRoute()
const router = useRouter()
const store = useMenuStore()
const authStore = useAuthStore()

const isFilterOpen = ref(false)
const isFullscreen = ref(false)

const searchQuery = ref('')
const selectedCategory = ref<string | null>(null)
const availabilityFilter = ref<string>('all')
const currentPage = ref<number>(1)
const pageSize = ref<number>(10)

function filterByCategory(category: string | null) {
  selectedCategory.value = category
  currentPage.value = 1
  loadMenu()
}

function navigateToCreate() {
  const isMenuMgmt = route.path.startsWith('/menu-management') || (!authStore.isPlatformAdmin && !authStore.hasRole('admin'))
  router.push(isMenuMgmt ? '/menu-management/add' : '/admin/menu/add')
}

function editMenu(item: MenuItem) {
  const isMenuMgmt = route.path.startsWith('/menu-management') || (!authStore.isPlatformAdmin && !authStore.hasRole('admin'))
  const base = isMenuMgmt ? '/menu-management/add' : '/admin/menu/add'
  router.push(`${base}?id=${item.id}`)
}

async function deleteMenu(item: MenuItem) {
  const confirmed = window.confirm(`Are you sure you want to delete "${item.name}"?`)
  if (!confirmed) return
  try {
    await store.deleteMenuItem(item.id)
    await refreshPage()
  } catch (error: any) {
    console.error('[MenuView] Error deleting menu item:', error)
    alert(error.response?.data?.message || 'Failed to delete menu item')
  }
}

async function toggleAvailability(item: MenuItem) {
  try {
    await store.toggleAvailability(item.id)
    await refreshPage()
  } catch (error: any) {
    console.error('[MenuView] Error updating availability:', error)
    alert(error.response?.data?.message || 'Failed to update availability')
  }
}

async function loadMenu() {
  const filters: any = {
    page: currentPage.value,
    per_page: pageSize.value,
  }
  if (selectedCategory.value) {
    filters.category = selectedCategory.value
  }
  if (searchQuery.value.trim()) {
    filters.search = searchQuery.value.trim()
  }
  try {
    await store.fetchMenuItems(filters)
  } catch (error) {
    console.error('Error loading menu:', error)
  }
}

async function loadStatistics() {
  await store.fetchStatistics()
}

async function refreshPage() {
  await Promise.all([loadMenu(), loadStatistics()])
}

function resetFilters() {
  searchQuery.value = ''
  selectedCategory.value = null
  availabilityFilter.value = 'all'
  currentPage.value = 1
  loadMenu()
}

function toggleFilter() {
  isFilterOpen.value = !isFilterOpen.value
}

function toggleFullscreen() {
  isFullscreen.value = !isFullscreen.value
}

const filteredMenuItems = computed(() => {
  let list = store.menuItems || []
  if (availabilityFilter.value !== 'all') {
    const isAvail = availabilityFilter.value === 'available'
    list = list.filter((i) => Boolean(i.is_available) === isAvail)
  }
  if (searchQuery.value.trim()) {
    const q = searchQuery.value.toLowerCase().trim()
    list = list.filter((i) => {
      const cat: any = i.category
      const catName = typeof cat === 'object' ? (cat?.name || '') : String(cat || '')
      return (
        (i.name || '').toLowerCase().includes(q) ||
        (i.description || '').toLowerCase().includes(q) ||
        catName.toLowerCase().includes(q)
      )
    })
  }
  return list
})

function changePageSize(event: Event) {
  const target = event.target as HTMLSelectElement
  pageSize.value = Number(target.value)
  currentPage.value = 1
  loadMenu()
}

function goToPage(p: number) {
  if (p >= 1 && p <= store.pagination.last_page) {
    currentPage.value = p
    loadMenu()
  }
}

function prevPage() {
  if (currentPage.value > 1) {
    currentPage.value--
    loadMenu()
  }
}

function nextPage() {
  if (currentPage.value < store.pagination.last_page) {
    currentPage.value++
    loadMenu()
  }
}

function generatePageNumbers() {
  const pages: number[] = []
  const totalPages = store.pagination.last_page || 1
  const cur = currentPage.value

  for (let i = Math.max(1, cur - 2); i <= Math.min(totalPages, cur + 2); i++) {
    pages.push(i)
  }
  return pages
}

onMounted(async () => {
  await refreshPage()
})
</script>

<template>
  <DashboardLayout>
    <div
      class="space-y-6 bg-slate-50 dark:bg-slate-950 min-h-screen p-4 sm:p-6 max-w-full overflow-hidden font-sans"
      :class="{ 'fixed inset-0 z-50 p-6 overflow-y-auto bg-white dark:bg-slate-950': isFullscreen }"
    >
      <!-- Header -->
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-5 sm:p-6 rounded-2xl shadow-xs">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-600 to-indigo-700 flex items-center justify-center shadow-md flex-shrink-0 text-white">
            <UtensilsCrossed class="w-5 h-5 stroke-[2.2]" />
          </div>
          <div>
            <h1 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white">Menu Management</h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Manage dishes, categories, pricing, and stock availability.</p>
          </div>
        </div>

        <button
          @click="navigateToCreate"
          class="px-4 py-2.5 bg-blue-600 hover:bg-blue-700 dark:bg-[#0066FF] dark:hover:bg-[#0055DD] text-white font-bold rounded-xl transition text-xs sm:text-sm inline-flex items-center justify-center gap-2 cursor-pointer shadow-md shadow-blue-600/20"
        >
          <Plus class="w-4 h-4 stroke-[3]" />
          <span>Add Menu Item</span>
        </button>
      </div>

      <!-- Stats Cards -->
      <MenuStats
        :statistics="store.statistics"
        :selected-category="selectedCategory"
        :menu-items="store.menuItems"
        @select="filterByCategory"
      />

      <!-- Top Bar Toolbar -->
      <div
        class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-slate-200 dark:border-slate-800/80 bg-white dark:bg-[#0b1527] p-3 sm:p-4 shadow-xs transition-all"
      >
        <!-- Left: Search & Filter Toggle -->
        <div class="flex flex-1 items-center gap-2.5 min-w-[280px] max-w-2xl">
          <!-- Search Input -->
          <div class="relative flex-1">
            <Search class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 dark:text-slate-500" />
            <input
              v-model="searchQuery"
              @keyup.enter="loadMenu"
              type="text"
              placeholder="Search dishes by name, description, or category..."
              class="w-full rounded-xl border border-slate-200 dark:border-[#1e3455] bg-slate-50/80 dark:bg-[#13233c] text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 pl-10 pr-4 py-2.5 text-xs sm:text-sm focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 dark:focus:border-blue-400 transition outline-none"
            />
          </div>

          <!-- Filter Toggle Button -->
          <button
            type="button"
            @click="toggleFilter"
            class="inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-xs sm:text-sm font-semibold transition cursor-pointer flex-shrink-0"
            :class="[
              isFilterOpen
                ? 'bg-blue-600/10 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 border border-blue-500/40'
                : 'border border-slate-200 dark:border-[#1e3455] bg-white dark:bg-[#13233c]/80 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-[#1c3356]'
            ]"
          >
            <component :is="isFilterOpen ? X : Filter" class="w-4 h-4" />
            <span>{{ isFilterOpen ? 'Hide Filter' : 'Filter' }}</span>
          </button>
        </div>

        <!-- Right: Action Buttons -->
        <div class="flex items-center gap-2 sm:gap-2.5">
          <!-- Refresh Button -->
          <button
            type="button"
            @click="refreshPage"
            :disabled="store.loading"
            title="Refresh"
            class="inline-flex items-center justify-center h-9 w-9 sm:h-10 sm:w-10 rounded-xl border border-slate-200 dark:border-[#1e3455] bg-white dark:bg-[#13233c]/80 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-[#1c3356] hover:text-slate-900 dark:hover:text-white transition disabled:opacity-50 cursor-pointer"
          >
            <RefreshCw class="w-4 h-4" :class="{ 'animate-spin': store.loading }" />
          </button>

          <!-- Fullscreen Toggle -->
          <button
            type="button"
            @click="toggleFullscreen"
            title="Toggle Fullscreen"
            class="inline-flex items-center justify-center h-9 w-9 sm:h-10 sm:w-10 rounded-xl border border-slate-200 dark:border-[#1e3455] bg-white dark:bg-[#13233c]/80 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-[#1c3356] hover:text-slate-900 dark:hover:text-white transition cursor-pointer"
          >
            <component :is="isFullscreen ? Minimize2 : Maximize2" class="w-4 h-4" />
          </button>
        </div>
      </div>

      <!-- Expandable Filter Panel -->
      <Transition
        enter-active-class="transition duration-200 ease-out"
        enter-from-class="transform -translate-y-2 opacity-0 scale-98"
        enter-to-class="transform translate-y-0 opacity-100 scale-100"
        leave-active-class="transition duration-150 ease-in"
        leave-from-class="transform translate-y-0 opacity-100 scale-100"
        leave-to-class="transform -translate-y-2 opacity-0 scale-98"
      >
        <div
          v-if="isFilterOpen"
          class="rounded-2xl border border-slate-200 dark:border-slate-800/80 bg-white dark:bg-[#0b1527] p-4 sm:p-5 shadow-sm space-y-4"
        >
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-3.5 sm:gap-4">
            <!-- Availability Filter -->
            <div>
              <label class="mb-1.5 block text-xs font-semibold text-slate-600 dark:text-slate-300">
                Availability
              </label>
              <select
                v-model="availabilityFilter"
                class="w-full rounded-xl border border-slate-200 dark:border-[#1e3455] bg-slate-50/80 dark:bg-[#13233c] text-slate-900 dark:text-white px-3.5 py-2 text-xs sm:text-sm focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 dark:focus:border-blue-400 transition cursor-pointer font-medium outline-none"
              >
                <option value="all">All Items</option>
                <option value="available">Available in Kitchen</option>
                <option value="out_of_stock">Out of Stock</option>
              </select>
            </div>

            <!-- Category Filter -->
            <div>
              <label class="mb-1.5 block text-xs font-semibold text-slate-600 dark:text-slate-300">
                Category
              </label>
              <select
                v-model="selectedCategory"
                @change="loadMenu"
                class="w-full rounded-xl border border-slate-200 dark:border-[#1e3455] bg-slate-50/80 dark:bg-[#13233c] text-slate-900 dark:text-white px-3.5 py-2 text-xs sm:text-sm focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 dark:focus:border-blue-400 transition cursor-pointer font-medium outline-none"
              >
                <option :value="null">All Categories</option>
                <option value="Breakfast">Breakfast</option>
                <option value="Main Course">Main Course</option>
                <option value="Appetizers">Appetizers</option>
                <option value="Desserts">Desserts</option>
                <option value="Beverages">Beverages</option>
              </select>
            </div>

            <!-- Reset Filters -->
            <div class="flex items-end">
              <button
                type="button"
                @click="resetFilters"
                class="w-full inline-flex items-center justify-center gap-1.5 rounded-xl border border-slate-200 dark:border-[#1e3455] bg-slate-100/70 dark:bg-[#13233c] px-3.5 py-2 text-xs font-semibold text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-[#1c3356] transition cursor-pointer"
              >
                <RotateCcw class="w-3.5 h-3.5" />
                <span>Reset Filters</span>
              </button>
            </div>
          </div>
        </div>
      </Transition>

      <!-- Table Area -->
      <div class="space-y-4">
        <!-- Table Component -->
        <MenuTable
          :items="filteredMenuItems"
          :loading="store.loading"
          @edit="editMenu"
          @delete="deleteMenu"
          @toggle="toggleAvailability"
        />

        <!-- Empty State -->
        <div
          v-if="!store.loading && filteredMenuItems.length === 0"
          class="text-center py-12 sm:py-16 bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs px-4"
        >
          <div class="w-14 h-14 rounded-2xl bg-indigo-50 dark:bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 flex items-center justify-center mx-auto mb-3 border border-indigo-200 dark:border-indigo-500/20">
            <Utensils class="w-7 h-7 stroke-[2]" />
          </div>
          <p class="text-lg font-bold text-slate-900 dark:text-white mb-1">No Menu Items Found</p>
          <p class="text-xs text-slate-500 dark:text-slate-400 mb-4">
            Try adjusting your search criteria or create a new menu item.
          </p>
          <button
            @click="navigateToCreate"
            class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs rounded-xl shadow-md shadow-blue-600/20 transition cursor-pointer"
          >
            <Plus class="w-4 h-4 stroke-[3]" />
            <span>Create Menu Item</span>
          </button>
        </div>

        <!-- Pagination Footer -->
        <div
          v-if="store.pagination && store.pagination.total > 0"
          class="border-t border-slate-200 dark:border-slate-800/80 px-4 sm:px-6 py-3 sm:py-4 bg-slate-50/50 dark:bg-[#0c182c] rounded-2xl border border-slate-200 dark:border-slate-800 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs"
        >
          <div class="text-slate-500 dark:text-slate-400 font-medium">
            Showing <span class="font-bold text-slate-900 dark:text-white">{{ store.pagination.from || 1 }}</span> to
            <span class="font-bold text-slate-900 dark:text-white">{{ store.pagination.to || store.menuItems.length }}</span> of
            <span class="font-bold text-slate-900 dark:text-white">{{ store.pagination.total }}</span> dishes
          </div>

          <div class="flex items-center gap-2 sm:gap-3">
            <div class="flex items-center gap-1.5">
              <span class="text-slate-500 dark:text-slate-400 font-medium">Per page:</span>
              <select
                :value="pageSize"
                @change="changePageSize"
                class="rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-[#13233c] text-slate-900 dark:text-white px-2 py-1 text-xs outline-none"
              >
                <option :value="10">10</option>
                <option :value="20">20</option>
                <option :value="50">50</option>
              </select>
            </div>

            <div class="flex items-center gap-1">
              <button
                @click="prevPage"
                :disabled="currentPage === 1 || store.loading"
                class="p-1 rounded-lg border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 disabled:opacity-40 transition cursor-pointer"
              >
                <ChevronLeft class="w-4 h-4" />
              </button>

              <button
                v-for="page in generatePageNumbers()"
                :key="page"
                @click="goToPage(page)"
                class="px-2.5 py-1 rounded-lg text-xs font-bold transition cursor-pointer"
                :class="[
                  currentPage === page
                    ? 'bg-blue-600 text-white'
                    : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800'
                ]"
              >
                {{ page }}
              </button>

              <button
                @click="nextPage"
                :disabled="currentPage === store.pagination.last_page || store.loading"
                class="p-1 rounded-lg border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 disabled:opacity-40 transition cursor-pointer"
              >
                <ChevronRight class="w-4 h-4" />
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>
