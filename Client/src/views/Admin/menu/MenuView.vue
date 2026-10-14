<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import MenuStats from '@/components/menu/MenuStats.vue'
import MenuTable from '@/components/menu/MenuTable.vue'
import { useMenuStore } from '@/stores/menuStore'
import { useAuthStore } from '@/stores/auth'
import { useHotelStore } from '@/stores/hotelStore'
import { useLanguageStore } from '@/stores/language'
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
  Building2,
} from 'lucide-vue-next'
import type { MenuItem } from '@/types/menu'

const route = useRoute()
const router = useRouter()
const store = useMenuStore()
const authStore = useAuthStore()
const hotelStore = useHotelStore()
const languageStore = useLanguageStore()

const userRoleName = computed(() => {
  const role = String(authStore.user?.role || 'Staff').toLowerCase()
  return role.charAt(0).toUpperCase() + role.slice(1)
})

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
  const isMenuMgmt =
    route.path.startsWith('/menu-management') ||
    (!authStore.isPlatformAdmin && !authStore.hasRole('admin'))
  router.push(isMenuMgmt ? '/menu-management/add' : '/admin/menu/add')
}

function editMenu(item: MenuItem) {
  const isMenuMgmt =
    route.path.startsWith('/menu-management') ||
    (!authStore.isPlatformAdmin && !authStore.hasRole('admin'))
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
      const catName = typeof cat === 'object' ? cat?.name || '' : String(cat || '')
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
      class="space-y-4 sm:space-y-6 bg-slate-50 dark:bg-slate-950 min-h-screen p-3 sm:p-6 max-w-full font-sans"
      :class="{ 'fixed inset-0 z-50 p-6 overflow-y-auto bg-white dark:bg-slate-950': isFullscreen }"
    >
      <!-- Header Card -->
      <div
        class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 sm:gap-4 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-4 sm:p-6 rounded-2xl shadow-xs"
      >
        <div class="flex items-center gap-3">
          <div
            class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-600 to-indigo-700 flex items-center justify-center shadow-md flex-shrink-0 text-white"
          >
            <UtensilsCrossed class="w-5 h-5 stroke-[2.2]" />
          </div>
          <div>
            <div class="flex flex-wrap items-center gap-2">
              <h1 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white">
                {{ languageStore.t('menu_management', 'Menu Management') }}
              </h1>
              <span
                v-if="hotelStore?.hotelName"
                class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300 border border-blue-200 dark:border-blue-700/50 flex-shrink-0"
              >
                <Building2 class="w-3 h-3" />
                <span class="truncate">{{ hotelStore.hotelName }}</span>
              </span>
              <span
                class="text-[10px] uppercase font-black bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 px-2 py-0.5 rounded-md border border-indigo-500/20"
              >
                {{ userRoleName }}
              </span>
            </div>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
              {{
                languageStore.t(
                  'menu_mgmt_desc',
                  'Manage dishes, categories, pricing, and stock availability.',
                )
              }}
            </p>
          </div>
        </div>

        <button
          type="button"
          @click="navigateToCreate"
          class="w-full sm:w-auto inline-flex items-center justify-center rounded-[10px] bg-[#1257A2] hover:bg-[#0e4884] active:bg-[#0b3c6f] text-white px-5 sm:px-6 py-2 sm:py-2.5 text-sm sm:text-[15px] font-semibold transition-colors duration-150 cursor-pointer flex-shrink-0 shadow-none border-0 focus:outline-none focus-visible:ring-2 focus-visible:ring-[#1257A2]/40"
        >
          <span class="text-white font-semibold">{{
            languageStore.t('add_menu_item', 'Add Menu Item')
          }}</span>
        </button>
      </div>

      <!-- Menu Category Stats -->
      <MenuStats
        :statistics="store.statistics"
        :selected-category="selectedCategory"
        :menu-items="store.menuItems"
        @select="filterByCategory"
      />

      <!-- Responsive Toolbar -->
      <div
        class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 rounded-2xl border border-slate-200 dark:border-slate-800/80 bg-white dark:bg-[#0b1527] p-3 sm:p-4 shadow-xs transition-all"
      >
        <div class="relative flex-1 min-w-0">
          <Search
            class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 dark:text-slate-500"
          />
          <input
            v-model="searchQuery"
            @keyup.enter="loadMenu"
            type="text"
            :placeholder="
              languageStore.t(
                'search_dishes_ph',
                'Search dishes by name, description, or category...',
              )
            "
            class="w-full rounded-xl border border-slate-200 dark:border-[#1e3455] bg-slate-50/80 dark:bg-[#13233c] text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 pl-10 pr-4 py-2 sm:py-2.5 text-xs sm:text-sm focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 dark:focus:border-blue-400 transition outline-none"
          />
        </div>

        <div
          class="flex items-center justify-between sm:justify-end gap-2 shrink-0 w-full sm:w-auto"
        >
          <button
            type="button"
            @click="toggleFilter"
            class="inline-flex items-center gap-1.5 sm:gap-2 rounded-xl px-3 sm:px-4 py-2 sm:py-2.5 text-xs sm:text-sm font-semibold transition cursor-pointer flex-shrink-0"
            :class="[
              isFilterOpen
                ? 'bg-blue-600/10 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 border border-blue-500/40'
                : 'border border-slate-200 dark:border-[#1e3455] bg-white dark:bg-[#13233c]/80 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-[#1c3356]',
            ]"
          >
            <component :is="isFilterOpen ? X : Filter" class="w-4 h-4" />
            <span>{{
              isFilterOpen
                ? languageStore.t('hide_filter', 'Filter')
                : languageStore.t('filter', 'Filter')
            }}</span>
          </button>

          <div class="flex items-center gap-1.5 sm:gap-2">
            <button
              type="button"
              @click="refreshPage"
              :disabled="store.loading"
              :title="languageStore.t('refresh', 'Refresh')"
              class="inline-flex items-center justify-center h-9 w-9 sm:h-10 sm:w-10 rounded-xl border border-slate-200 dark:border-[#1e3455] bg-white dark:bg-[#13233c]/80 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-[#1c3356] hover:text-slate-900 dark:hover:text-white transition disabled:opacity-50 cursor-pointer"
            >
              <RefreshCw class="w-4 h-4" :class="{ 'animate-spin': store.loading }" />
            </button>

            <button
              type="button"
              @click="toggleFullscreen"
              :title="languageStore.t('fullscreen', 'Toggle Fullscreen')"
              class="inline-flex items-center justify-center h-9 w-9 sm:h-10 sm:w-10 rounded-xl border border-slate-200 dark:border-[#1e3455] bg-white dark:bg-[#13233c]/80 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-[#1c3356] hover:text-slate-900 dark:hover:text-white transition cursor-pointer"
            >
              <component :is="isFullscreen ? Minimize2 : Maximize2" class="w-4 h-4" />
            </button>
          </div>

          <button
            type="button"
            @click="navigateToCreate"
            class="flex-1 sm:flex-initial inline-flex items-center justify-center gap-1.5 rounded-xl bg-blue-600 hover:bg-blue-700 dark:bg-[#0066FF] dark:hover:bg-[#0055DD] px-3.5 sm:px-4 py-2 sm:py-2.5 text-xs sm:text-sm font-bold text-white shadow-sm shadow-blue-600/30 transition active:scale-98 cursor-pointer flex-shrink-0"
          >
            <Plus class="w-4 h-4 text-white" />
            <span class="text-white">{{ languageStore.t('add_item', 'Add Item') }}</span>
          </button>
        </div>
      </div>

      <!-- Filter Drawer -->
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
            <div>
              <label class="mb-1.5 block text-xs font-semibold text-slate-600 dark:text-slate-300">
                {{ languageStore.t('availability', 'Availability') }}
              </label>
              <select
                v-model="availabilityFilter"
                class="w-full rounded-xl border border-slate-200 dark:border-[#1e3455] bg-slate-50/80 dark:bg-[#13233c] text-slate-900 dark:text-white px-3.5 py-2 text-xs sm:text-sm focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 dark:focus:border-blue-400 transition cursor-pointer font-medium outline-none"
              >
                <option value="all">{{ languageStore.t('all_items', 'All Items') }}</option>
                <option value="available">
                  {{ languageStore.t('available_in_kitchen', 'Available in Kitchen') }}
                </option>
                <option value="out_of_stock">
                  {{ languageStore.t('out_of_stock', 'Out of Stock') }}
                </option>
              </select>
            </div>

            <div>
              <label class="mb-1.5 block text-xs font-semibold text-slate-600 dark:text-slate-300">
                {{ languageStore.t('category', 'Category') }}
              </label>
              <select
                v-model="selectedCategory"
                @change="loadMenu"
                class="w-full rounded-xl border border-slate-200 dark:border-[#1e3455] bg-slate-50/80 dark:bg-[#13233c] text-slate-900 dark:text-white px-3.5 py-2 text-xs sm:text-sm focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 dark:focus:border-blue-400 transition cursor-pointer font-medium outline-none"
              >
                <option :value="null">
                  {{ languageStore.t('all_categories', 'All Categories') }}
                </option>
                <option value="Breakfast">{{ languageStore.t('breakfast', 'Breakfast') }}</option>
                <option value="Main Course">
                  {{ languageStore.t('main_course', 'Main Course') }}
                </option>
                <option value="Appetizers">
                  {{ languageStore.t('appetizers', 'Appetizers') }}
                </option>
                <option value="Desserts">{{ languageStore.t('desserts', 'Desserts') }}</option>
                <option value="Beverages">{{ languageStore.t('beverages', 'Beverages') }}</option>
              </select>
            </div>

            <div class="flex items-end">
              <button
                type="button"
                @click="resetFilters"
                class="w-full inline-flex items-center justify-center gap-1.5 rounded-xl border border-slate-200 dark:border-[#1e3455] bg-slate-100/70 dark:bg-[#13233c] px-3.5 py-2 text-xs font-semibold text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-[#1c3356] transition cursor-pointer h-[38px]"
              >
                <RotateCcw class="w-3.5 h-3.5" />
                <span>{{ languageStore.t('reset_filters', 'Reset Filters') }}</span>
              </button>
            </div>
          </div>
        </div>
      </Transition>

      <div class="space-y-4">
        <!-- Menu Table Component -->
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
          <div
            class="w-14 h-14 rounded-2xl bg-indigo-50 dark:bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 flex items-center justify-center mx-auto mb-3 border border-indigo-200 dark:border-indigo-500/20"
          >
            <Utensils class="w-7 h-7 stroke-[2]" />
          </div>
          <p class="text-base sm:text-lg font-bold text-slate-900 dark:text-white mb-1">
            {{ languageStore.t('no_menu_items_found', 'No Menu Items Found') }}
          </p>
          <p class="text-xs text-slate-500 dark:text-slate-400 mb-4">
            {{
              languageStore.t(
                'no_menu_items_desc',
                'Try adjusting your search criteria or create a new menu item.',
              )
            }}
          </p>
          <button
            @click="navigateToCreate"
            class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs rounded-xl shadow-md shadow-blue-600/20 transition cursor-pointer"
          >
            <Plus class="w-4 h-4 stroke-[3]" />
            <span>{{ languageStore.t('create_menu_item', 'Create Menu Item') }}</span>
          </button>
        </div>

        <!-- Responsive Pagination -->
        <div
          v-if="store.pagination && store.pagination.total > 0"
          class="border-t border-slate-200 dark:border-slate-800/80 px-3.5 sm:px-6 py-3 sm:py-4 bg-slate-50/50 dark:bg-[#0c182c] rounded-2xl border border-slate-200 dark:border-slate-800 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs"
        >
          <div class="text-slate-500 dark:text-slate-400 font-medium text-center sm:text-left">
            {{ languageStore.t('Showing', 'Showing') }}
            <span class="font-bold text-slate-900 dark:text-white">{{
              store.pagination.from || 1
            }}</span>
            {{ languageStore.t('to', 'to') }}
            <span class="font-bold text-slate-900 dark:text-white">{{
              store.pagination.to || store.menuItems.length
            }}</span>
            {{ languageStore.t('of', 'of') }}
            <span class="font-bold text-slate-900 dark:text-white">{{
              store.pagination.total
            }}</span>
            {{ languageStore.t('dishes', 'dishes') }}
          </div>

          <div
            class="flex flex-wrap items-center justify-center sm:justify-end gap-2 sm:gap-3 w-full sm:w-auto"
          >
            <div class="flex items-center gap-1.5">
              <span class="text-slate-500 dark:text-slate-400 font-medium">{{
                languageStore.t('Per page:', 'Per page:')
              }}</span>
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

              <!-- Mobile concise page display -->
              <div class="sm:hidden px-2 text-xs font-semibold text-slate-700 dark:text-slate-300">
                {{ currentPage }} / {{ store.pagination.last_page || 1 }}
              </div>

              <!-- Desktop page buttons -->
              <div class="hidden sm:flex items-center gap-1">
                <button
                  v-for="page in generatePageNumbers()"
                  :key="page"
                  @click="goToPage(page)"
                  class="px-2.5 py-1 rounded-lg text-xs font-bold transition cursor-pointer"
                  :class="[
                    currentPage === page
                      ? 'bg-blue-600 text-white'
                      : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800',
                  ]"
                >
                  {{ page }}
                </button>
              </div>

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
