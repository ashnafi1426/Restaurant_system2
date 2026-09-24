<template>
  <div class="min-h-screen bg-white">
    <div class="sticky top-0 z-50 bg-white border-b border-gray-100">
      <div class="flex items-center justify-between px-4">
        <button
          @click="sidebarOpen = !sidebarOpen"
          class="lg:hidden p-2 hover:bg-gray-100 rounded-lg transition"
        >
          <svg
            v-if="!sidebarOpen"
            class="w-6 h-6"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M4 6h16M4 12h16M4 18h16"
            ></path>
          </svg>
          <svg v-else class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M6 18L18 6M6 6l12 12"
            ></path>
          </svg>
        </button>

        <div class="flex-1">
          <GuestNavbar
            :guest-name="guestName"
            :guest-email="guestEmail"
            :guest-avatar="guestAvatar"
            :initial-room="roomNumber"
            :categories="categories"
            :selected-category-id="selectedCategory"
            @search="handleSearch"
            @room-selected="handleRoomSelected"
            @category-selected="handleCategorySelected"
            @logout="handleLogout"
            class="bg-white border-0"
          />
        </div>
      </div>
    </div>

    <div class="flex relative">
      <aside
        class="hidden lg:block fixed left-0 top-16 w-60 h-[calc(100vh-4rem)] bg-white dark:bg-slate-900 border-r border-slate-200/80 dark:border-slate-800/80 z-40 overflow-y-auto p-2 transition-colors"
      >
        <CategorySidebar
          :categories="categories"
          :selected-category-id="selectedCategory"
          :total-items="allMenuItems.length"
          @category-selected="handleCategorySelected"
        />
      </aside>

      <div
        v-if="sidebarOpen"
        class="fixed inset-0 bg-black/50 z-30 lg:hidden top-16"
        @click="sidebarOpen = false"
      ></div>

      <aside
        class="fixed left-0 top-16 w-56 sm:w-64 h-[calc(100vh-4rem)] bg-white dark:bg-slate-900 border-r border-slate-200/80 dark:border-slate-800/80 z-40 overflow-y-auto lg:hidden transition-transform duration-300"
        :class="[sidebarOpen ? 'translate-x-0' : '-translate-x-full']"
      >
        <div class="w-full p-2">
          <CategorySidebar
            :categories="categories"
            :selected-category-id="selectedCategory"
            :total-items="allMenuItems.length"
            @category-selected="handleCategorySelectedMobile"
          />
        </div>
      </aside>

      <main class="w-full lg:ml-60 bg-[#f9f8f6] dark:bg-slate-950 px-3 sm:px-5 lg:px-6 py-2 transition-colors pb-3">
        <div
          class="relative h-48 sm:h-60 md:h-64 lg:h-72 rounded-2xl overflow-hidden shadow-md bg-slate-950 border border-slate-800/80 font-sans mt-3"
        >
          <div class="absolute inset-0 z-0">
            <img
              src="https://images.unsplash.com/photo-1519708227418-c8fd9a32b7a2?w=1200&h=800&fit=crop"
              alt="Good Food Great Moments"
              class="w-full h-full object-cover object-right opacity-100 brightness-105 contrast-105 transition-transform duration-500 hover:scale-105"
            />
            <div class="absolute inset-0 bg-gradient-to-r from-slate-950/95 via-slate-950/70 to-transparent"></div>
          </div>

          <div class="relative z-10 h-full flex items-center px-6 sm:px-8 md:px-10">
            <div class="max-w-md space-y-2 sm:space-y-3 text-white">
              <h1 class="text-2xl sm:text-3xl md:text-4xl font-serif font-bold tracking-tight leading-tight">
                <span class="block text-white">{{ languageStore.t('good_food', 'Good Food,') }} </span>
                <span class="block text-[#c29353] drop-shadow-md">{{ languageStore.t('great_moments', 'Great Moments') }}</span>
              </h1>

              <p class="text-xs sm:text-sm text-slate-200 font-medium leading-relaxed max-w-sm">
                {{ languageStore.t('culinary_desc', 'Fresh ingredients, expertly prepared. Delivered directly to your room.') }}
              </p>

              <div class="pt-2">
                <button
                  @click="handleViewSpecials"
                  class="px-5 py-2.5 sm:px-6 sm:py-3 rounded-full bg-[#c29353] hover:bg-[#b08244] text-white font-black text-xs sm:text-sm shadow-lg hover:shadow-xl transition cursor-pointer inline-flex items-center gap-2 transform hover:-translate-y-0.5"
                >
                  <span>{{ languageStore.t('view_specials', 'View Specials') }}</span>
                  <ChevronRight class="w-4 h-4" />
                </button>
              </div>
            </div>
          </div>
        </div>

        <div class="mt-2.5">
          <MenuSearch
            :menu-items="allMenuItems"
            @search="handleSearchQueryChanged"
            @suggestion-selected="handleSuggestionSelected"
          />
        </div>

        <div class="lg:hidden mt-2.5 overflow-x-auto hide-scrollbar flex items-center gap-2 py-1">
          <button
            v-for="cat in categories"
            :key="cat.id ?? 'all'"
            @click="handleCategorySelected(cat.id)"
            :class="[
              'px-3.5 py-1.5 rounded-full text-xs font-bold whitespace-nowrap transition cursor-pointer flex items-center gap-1.5 flex-shrink-0',
              (selectedCategory === cat.id || (cat.slug && selectedCategory === cat.slug) || (cat.id === null && selectedCategory === null))
                ? 'bg-[#c29353] text-white shadow-xs font-black'
                : 'bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-300 border border-slate-200/80 dark:border-slate-800'
            ]"
          >
            <span>{{ languageStore.t(cat.name, cat.name) }}</span>
            <span
              v-if="cat.count !== undefined"
              :class="[
                'px-1.5 py-0.2 rounded-full text-[10px] font-black',
                (selectedCategory === cat.id || (cat.slug && selectedCategory === cat.slug) || (cat.id === null && selectedCategory === null))
                  ? 'bg-white/20 text-white'
                  : 'bg-slate-100 dark:bg-slate-800 text-slate-500'
              ]"
            >
              {{ cat.count }}
            </span>
          </button>
        </div>

        <div class="mt-3 mb-1.5 flex items-center justify-between">
          <div>
            <h2 class="text-base sm:text-lg font-black text-slate-900 dark:text-white tracking-tight">
              {{ languageStore.t('recommended_for_you', 'Recommended for You') }}
            </h2>
            <p class="text-[11px] text-slate-500 dark:text-slate-400">
              {{ languageStore.t('fresh_ingredients', 'Fresh ingredients, expertly prepared') }}
            </p>
          </div>
          <button
            v-if="filteredMenuItems.length > itemsPerPage"
            class="text-xs font-bold text-[#c29353] hover:underline transition-colors"
          >
            {{ languageStore.t('view_all', 'View All') }} →
          </button>
        </div>

        <div id="menu-grid">
          <MenuGrid
            :items="filteredMenuItems"
            :view-mode="viewMode"
            :is-loading="isLoadingMenu"
            :items-per-page="itemsPerPage"
            :guest-name="guestName"
            :guest-email="guestEmail"
            :order-id="qrToken"
            @add-to-cart="handleAddToCart"
            @toggle-favorite="handleToggleFavorite"
            @write-review="handleWriteReview"
          />
        </div>

        <div
          class="mt-2.5 grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-4 gap-3 rounded-2xl bg-white dark:bg-slate-900 p-3 sm:p-4 border border-slate-200/80 dark:border-slate-800 shadow-xs mb-2 font-sans"
        >
          <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-full bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800 flex items-center justify-center text-[#c29353] flex-shrink-0">
              <Utensils class="w-4 h-4" />
            </div>
            <div>
              <h4 class="font-black text-xs text-slate-900 dark:text-white leading-tight">{{ languageStore.t('freshly_prepared', 'Freshly Prepared') }}</h4>
              <p class="text-[11px] text-slate-500 dark:text-slate-400 font-medium">{{ languageStore.t('premium_ingredients', 'Premium ingredients') }}</p>
            </div>
          </div>

          <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-full bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800 flex items-center justify-center text-[#c29353] flex-shrink-0">
              <Truck class="w-4 h-4" />
            </div>
            <div>
              <h4 class="font-black text-xs text-slate-900 dark:text-white leading-tight">{{ languageStore.t('fast_delivery', 'Fast Delivery') }}</h4>
              <p class="text-[11px] text-slate-500 dark:text-slate-400 font-medium">{{ languageStore.t('within_30_mins', 'Within 30 minutes') }}</p>
            </div>
          </div>

          <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-full bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800 flex items-center justify-center text-[#c29353] flex-shrink-0">
              <ShieldCheck class="w-4 h-4" />
            </div>
            <div>
              <h4 class="font-black text-xs text-slate-900 dark:text-white leading-tight">{{ languageStore.t('safe_hygienic', 'Safe & Hygienic') }}</h4>
              <p class="text-[11px] text-slate-500 dark:text-slate-400 font-medium">{{ languageStore.t('highest_standards', 'Highest standards') }}</p>
            </div>
          </div>

          <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-full bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800 flex items-center justify-center text-[#c29353] flex-shrink-0">
              <Clock class="w-4 h-4" />
            </div>
            <div>
              <h4 class="font-black text-xs text-slate-900 dark:text-white leading-tight">{{ languageStore.t('service_247', '24/7 Service') }}</h4>
              <p class="text-[11px] text-slate-500 dark:text-slate-400 font-medium">{{ languageStore.t('always_here', 'Always here to serve') }}</p>
            </div>
          </div>
        </div>
      </main>
    </div>

    <Transition
      enter-active-class="transition duration-300 ease-out"
      enter-from-class="translate-y-full opacity-0"
      enter-to-class="translate-y-0 opacity-100"
      leave-active-class="transition duration-300 ease-in"
      leave-from-class="translate-y-0 opacity-100"
      leave-to-class="translate-y-full opacity-0"
    >
      <div
        v-if="cartItems.length"
        class="fixed bottom-4 sm:bottom-5 left-1/2 z-50 w-[95%] max-w-6xl -translate-x-1/2"
      >
        <div
          class="flex flex-col sm:flex-row items-center justify-between gap-3 sm:gap-4 rounded-2xl bg-neutral-900 px-4 sm:px-6 md:px-8 py-3 sm:py-4 md:py-5 shadow-2xl"
        >
          <div
            class="flex items-center gap-3 sm:gap-4 md:gap-5 text-white w-full sm:w-auto justify-between sm:justify-start"
          >
            <svg class="w-6 h-6 sm:w-7 sm:h-7 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
            </svg>
            <div>
              <p class="text-white/70 text-xs sm:text-sm">{{ cartItems.length }} {{ languageStore.t('items_in_cart', 'Items in Cart') }}</p>
              <h3 class="text-lg sm:text-xl md:text-2xl font-bold">{{ formatPrice(cartTotal) }}</h3>
            </div>
          </div>

          <button
            @click="handleViewCart"
            class="w-full sm:w-auto rounded-xl bg-amber-500 px-4 sm:px-6 md:px-10 py-2 sm:py-3 md:py-4 text-sm sm:text-base md:text-lg font-semibold text-white transition hover:bg-amber-600"
          >
            {{ languageStore.t('view_cart_checkout', 'View Cart & Checkout →') }}
          </button>
        </div>
      </div>
    </Transition>

    <div
      v-if="isLoadingMenu"
      class="fixed inset-0 bg-white/80 dark:bg-slate-900/80 backdrop-blur-sm z-40 flex items-center justify-center"
    >
      <div class="flex flex-col items-center gap-4">
        <div class="relative w-12 h-12">
          <svg class="absolute inset-0 w-full h-full" viewBox="0 0 100 100">
            <circle cx="50" cy="50" r="45" fill="none" stroke="#0EA5E9" stroke-width="6" opacity="0.3" />
          </svg>
          <div class="absolute inset-0 animate-spin" style="animation: spin 1.5s linear infinite;">
            <svg viewBox="0 0 100 100" class="w-full h-full">
              <circle cx="50" cy="50" r="45" fill="none" stroke="#FBBF24" stroke-width="8" stroke-linecap="round" stroke-dasharray="70 280" />
            </svg>
          </div>
        </div>
        <p class="text-slate-700 dark:text-yellow-300 font-semibold text-sm">{{ languageStore.t('loading_menu', 'Loading menu...') }}</p>
      </div>
    </div>

    <GuestReviewModal
      :is-open="showReviewModal"
      :menu-item="(selectedMenuItemForReview as any)"
      :guest-name="guestName"
      :guest-email="guestEmail"
      :order-id="qrToken"
      @close="showReviewModal = false"
      @success="handleReviewSuccess"
      @error="handleReviewError"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import { Utensils, Truck, ShieldCheck, Clock, ChevronRight } from 'lucide-vue-next'
import GuestNavbar from './GuestNavbar.vue'
import CategorySidebar from './CategorySidebar.vue'
import MenuSearch from './MenuSearch.vue'
import GuestReviewModal from '../GuestReviewModal.vue'
import MenuGrid from './MenuGrid.vue'
import { useLanguageStore } from '@/stores/language'
import api from '@/api/auth'

interface Category {
  id: string | null
  slug?: string
  name: string
  icon: string
  count?: number
}

interface MenuItem {
  id: string | number
  name: string
  description: string
  price: number
  base_price?: number
  tax_amount?: number
  total_price?: number
  tax_rate?: any
  tax_included?: boolean
  image: string | null
  category: string
  rating?: number
  badge?: string
  dietary?: string[]
  calories?: number
  preparationTime?: number
  is_available?: boolean
}

interface CartItem extends MenuItem {
  quantity: number
}

interface Props {
  guestName?: string
  guestEmail?: string
  guestAvatar?: string
  roomNumber?: string | number
  qrToken?: string
  heroImage?: string
  heroHeading?: string
  heroSubheading?: string
  itemsPerPage?: number
}

const props = withDefaults(defineProps<Props>(), {
  guestName: 'Guest User',
  guestEmail: 'guest@royalhorizon.com',
  guestAvatar: '/images/avatar.png',
  roomNumber: '101',
  heroImage: '/images/gallery/fine-dining.jpg',
  heroHeading: 'Good Food, Great Moments',
  heroSubheading: 'LUXURY DINING',
  itemsPerPage: 12,
})

const languageStore = useLanguageStore()

const emit = defineEmits<{
  'room-selected': [room: string | number]
  logout: []
  'add-to-cart': [item: MenuItem, quantity: number]
  'view-cart': [items: CartItem[]]
}>()

const selectedCategory = ref<string | null>(null)
const searchQuery = ref('')
const selectedSort = ref('popular')
const viewMode = ref<'grid' | 'list'>('grid')
const isLoadingMenu = ref(false)
const cartItems = ref<CartItem[]>([])
const favorites = ref<Set<string>>(new Set())
const allMenuItems = ref<MenuItem[]>([])
const errorMessage = ref('')
const sidebarOpen = ref(false)

const showReviewModal = ref(false)
const selectedMenuItemForReview = ref<MenuItem | null>(null)

const categories = ref<Category[]>([])
const loadingCategories = ref(false)

function deriveCategoriesFromMenuItems() {
  const catMap: { [key: string]: { name: string; count: number; icon: string } } = {}

  if (allMenuItems.value && allMenuItems.value.length > 0) {
    allMenuItems.value.forEach((item) => {
      const rawCat = item.category || 'Other'
      const norm = normalizeCat(rawCat)
      let title = rawCat.replace(/-/g, ' ').replace(/\b\w/g, (l) => l.toUpperCase())

      if (!catMap[norm]) {
        catMap[norm] = { name: title, count: 0, icon: norm }
      }
      catMap[norm].count++
    })
  }

  const derived: Category[] = Object.keys(catMap).map((key) => ({
    id: key,
    slug: key,
    name: catMap[key].name,
    icon: catMap[key].icon,
    count: catMap[key].count,
  }))

  categories.value = [
    { id: null, name: 'All Categories', icon: 'grid', count: allMenuItems.value.length },
    ...derived,
  ]
}

const loadCategories = async () => {
  loadingCategories.value = true
  try {
    let rawCategories: any[] = []
    try {
      const response = await api.get('/guest/categories')
      rawCategories = response.data?.data || response.data || []
    } catch (guestErr) {
      console.warn('[QRMenuLayout] /guest/categories endpoint unavailable, trying /categories:', guestErr)
      const response = await api.get('/categories')
      rawCategories = response.data?.data || response.data || []
    }

    if (Array.isArray(rawCategories) && rawCategories.length > 0) {
      const backendCategories = rawCategories.map((cat: any) => ({
        id: cat.id ?? cat.slug,
        slug: cat.slug || (typeof cat.id === 'string' ? cat.id : ''),
        name: cat.name,
        icon: cat.icon || '',
        count: cat.count ?? cat.menu_items_count ?? 0,
      }))

      categories.value = [
        { id: null, name: 'All Categories', icon: 'grid', count: allMenuItems.value.length },
        ...backendCategories,
      ]
      updateCategoryCounts()
      return
    }
  } catch (error) {
    console.error('[QRMenuLayout] Error loading categories from API:', error)
  } finally {
    loadingCategories.value = false
  }

  deriveCategoriesFromMenuItems()
  updateCategoryCounts()
}

const sortOptions = [
  { value: 'popular', label: 'Most Popular' },
  { value: 'price-low', label: 'Price: Low to High' },
  { value: 'price-high', label: 'Price: High to Low' },
  { value: 'rating', label: 'Highest Rated' },
  { value: 'newest', label: 'Newest' },
]

function parseCategoryName(item: any): string {
  if (!item) return 'Other'
  if (item.category_name) return String(item.category_name)
  if (typeof item.category === 'string') return item.category
  if (typeof item.category === 'object' && item.category !== null) {
    return item.category.name || item.category.slug || item.category.title || 'Other'
  }
  return 'Other'
}

const loadMenuItems = async () => {
  isLoadingMenu.value = true
  errorMessage.value = ''
  try {
    let url = '/guest/menu/items'

    if (props.qrToken) {
      url = `/guest/menu/${props.qrToken}/items`
    }

    const response = await api.get(url)

    if (response.data?.data) {
      const data = response.data.data

      if (Array.isArray(data) && data.length > 0 && data[0].category && data[0].items) {
        allMenuItems.value = data.flatMap((categoryGroup: any) => {
          const categoryName = categoryGroup.category
          return categoryGroup.items.map((item: any) => {
            const rawPrice = parseFloat(item.price)
            const totalPrice = item.total_price != null ? parseFloat(item.total_price) : (isNaN(rawPrice) ? 0 : rawPrice)
            return {
              id: item.id,
              name: item.name || 'Unnamed Item',
              description: item.description || '',
              price: totalPrice,
              base_price: item.base_price != null ? parseFloat(item.base_price) : rawPrice,
              tax_amount: item.tax_amount != null ? parseFloat(item.tax_amount) : 0,
              total_price: totalPrice,
              tax_rate: item.tax_rate,
              tax_included: item.tax_included,
              image: item.image || '/images/placeholder.png',
              category: categoryName || parseCategoryName(item),
              rating: item.rating || 4.5,
              is_available: item.is_available !== false,
            }
          })
        })
      } else if (Array.isArray(data)) {
        allMenuItems.value = data.map((item: any) => {
          const rawPrice = parseFloat(item.price)
          const totalPrice = item.total_price != null ? parseFloat(item.total_price) : (isNaN(rawPrice) ? 0 : rawPrice)
          return {
            id: item.id,
            name: item.name || 'Unnamed Item',
            description: item.description || '',
            price: totalPrice,
            base_price: item.base_price != null ? parseFloat(item.base_price) : rawPrice,
            tax_amount: item.tax_amount != null ? parseFloat(item.tax_amount) : 0,
            total_price: totalPrice,
            tax_rate: item.tax_rate,
            tax_included: item.tax_included,
            image: item.image || '/images/placeholder.png',
            category: parseCategoryName(item),
            rating: item.rating || 4.5,
            is_available: item.is_available !== false,
          }
        })
      }
    } else if (response.data && Array.isArray(response.data)) {
      allMenuItems.value = response.data.map((item: any) => {
        const rawPrice = parseFloat(item.price)
        const totalPrice = item.total_price != null ? parseFloat(item.total_price) : (isNaN(rawPrice) ? 0 : rawPrice)
        return {
          id: item.id,
          name: item.name || 'Unnamed Item',
          description: item.description || '',
          price: totalPrice,
          base_price: item.base_price != null ? parseFloat(item.base_price) : rawPrice,
          tax_amount: item.tax_amount != null ? parseFloat(item.tax_amount) : 0,
          total_price: totalPrice,
          tax_rate: item.tax_rate,
          tax_included: item.tax_included,
          image: item.image || '/images/placeholder.png',
          category: parseCategoryName(item),
          rating: item.rating || 4.5,
          is_available: item.is_available !== false,
        }
      })
    } else {
      errorMessage.value = 'Unexpected data format from server'
    }
  } catch (error) {
    console.error('[QRMenuLayout] Error fetching menu items:', error)
    errorMessage.value = 'Failed to load menu items. Please try again.'
  } finally {
    isLoadingMenu.value = false
  }
}

function normalizeCat(str: string | number | null | undefined): string {
  if (!str) return ''
  return String(str)
    .toLowerCase()
    .trim()
    .replace(/[^a-z0-9]/g, '')
}

function matchCategory(itemCat: string | null | undefined, selectedCat: string | number | null | undefined): boolean {
  if (selectedCat === null || selectedCat === undefined || selectedCat === '' || selectedCat === 'all') return true

  let targetNameOrSlug = String(selectedCat)
  if (categories.value && categories.value.length > 0) {
    const matchedCatObj = categories.value.find(
      (c) => String(c.id) === String(selectedCat) || String(c.slug) === String(selectedCat) || String(c.name).toLowerCase() === String(selectedCat).toLowerCase()
    )
    if (matchedCatObj) {
      targetNameOrSlug = matchedCatObj.name + ' ' + (matchedCatObj.slug || '')
    }
  }

  const normItem = normalizeCat(itemCat)
  const normSelected = normalizeCat(selectedCat)
  const normTarget = normalizeCat(targetNameOrSlug)

  if (!normItem) return false
  if (normItem === normSelected || normItem === normTarget) return true

  const itemStem = normItem.replace(/s$/, '')
  const selectedStem = normSelected.replace(/s$/, '')
  const targetStem = normTarget.replace(/s$/, '')

  return (
    itemStem === selectedStem ||
    itemStem === targetStem ||
    normItem.includes(normSelected) ||
    normSelected.includes(normItem) ||
    normItem.includes(normTarget) ||
    normTarget.includes(normItem) ||
    itemStem.includes(selectedStem) ||
    selectedStem.includes(itemStem) ||
    itemStem.includes(targetStem) ||
    targetStem.includes(itemStem)
  )
}

const updateCategoryCounts = () => {
  categories.value.forEach((cat) => {
    if (cat.id) {
      const count = allMenuItems.value.filter(
        (item) => matchCategory(item.category, cat.id || cat.name),
      ).length
      cat.count = count
    } else {
      cat.count = allMenuItems.value.length
    }
  })
}

const filteredMenuItems = computed(() => {
  let items = [...allMenuItems.value]

  items = items.filter((item) => item.is_available !== false)

  if (selectedCategory.value) {
    items = items.filter((item) => matchCategory(item.category, selectedCategory.value))
  }

  const rawQuery = searchQuery.value
  const query = typeof rawQuery === 'string'
    ? rawQuery.toLowerCase().trim()
    : (rawQuery && typeof rawQuery === 'object' && (rawQuery as any).name)
      ? String((rawQuery as any).name).toLowerCase().trim()
      : ''

  if (query) {
    items = items.filter(
      (item) =>
        (item.name && item.name.toLowerCase().includes(query)) ||
        (item.description && item.description.toLowerCase().includes(query)) ||
        (item.category && item.category.toLowerCase().includes(query)),
    )
  }

  if (selectedSort.value === 'price-low') {
    items.sort((a, b) => a.price - b.price)
  } else if (selectedSort.value === 'price-high') {
    items.sort((a, b) => b.price - a.price)
  } else if (selectedSort.value === 'rating') {
    items.sort((a, b) => (b.rating || 0) - (a.rating || 0))
  }

  return items
})

const cartTotal = computed(() => {
  return cartItems.value.reduce((total, item) => total + item.price * item.quantity, 0)
})

const handleSearch = (query?: any) => {
  if (typeof query === 'string') {
    searchQuery.value = query
  } else if (query && typeof query === 'object' && query.name) {
    searchQuery.value = String(query.name)
  } else if (query && typeof query === 'object' && query.target) {
    searchQuery.value = String(query.target.value || '')
  } else {
    searchQuery.value = ''
  }
}

const handleRoomSelected = (room: string | number) => {
  emit('room-selected', room)
}

const handleLogout = () => {
  emit('logout')
}

const handleCategorySelected = (catOrId: any) => {
  let catVal: string | null = null
  if (catOrId === null || catOrId === undefined || catOrId === 'all' || catOrId === 'All Categories') {
    catVal = null
  } else if (typeof catOrId === 'object' && catOrId !== null) {
    if (catOrId.name === 'All Categories' || catOrId.id === null || catOrId.slug === 'all') {
      catVal = null
    } else {
      catVal = catOrId.slug || catOrId.id || catOrId.name
    }
  } else {
    catVal = String(catOrId)
  }

  selectedCategory.value = catVal
  sidebarOpen.value = false
}

const handleCategorySelectedMobile = (catOrId: any) => {
  handleCategorySelected(catOrId)
}

const handleSearchQueryChanged = (query: any) => {
  if (typeof query === 'string') {
    searchQuery.value = query
  } else if (query && typeof query === 'object' && query.name) {
    searchQuery.value = String(query.name)
  } else {
    searchQuery.value = String(query || '')
  }
}

const handleSuggestionSelected = (suggestion: any) => {
  if (typeof suggestion === 'string') {
    searchQuery.value = suggestion
  } else if (suggestion && typeof suggestion === 'object' && suggestion.name) {
    searchQuery.value = String(suggestion.name)
  } else {
    searchQuery.value = ''
  }
}

const handleSortChanged = (value: string) => {
  selectedSort.value = value
}

const handleViewModeChanged = (mode: 'grid' | 'list') => {
  viewMode.value = mode
}

const handleFiltersApplied = () => {}

const handleAddToCart = (item: MenuItem, quantity: number) => {
  const existingItem = cartItems.value.find((ci) => ci.id === item.id)
  if (existingItem) {
    existingItem.quantity += quantity
  } else {
    cartItems.value.push({ ...item, quantity })
  }
  emit('add-to-cart', item, quantity)
}

const handleToggleFavorite = (itemId: string | number, isFavorite: boolean) => {
  const id = String(itemId)
  if (isFavorite) {
    favorites.value.add(id)
  } else {
    favorites.value.delete(id)
  }
}

const handleViewCart = () => {
  emit('view-cart', cartItems.value)
}

const handleWriteReview = (item: MenuItem) => {
  selectedMenuItemForReview.value = item
  showReviewModal.value = true
}

const handleReviewSuccess = (_message: string) => {
  showReviewModal.value = false
  window.dispatchEvent(new Event('review-stats-updated'))
}

const handleReviewError = (_message: string) => {}

const handleExplore = () => {
  const menuSection = document.getElementById('menu-grid')
  if (menuSection) {
    menuSection.scrollIntoView({ behavior: 'smooth', block: 'start' })
  }
}

const handleViewSpecials = () => {}

const formatPrice = (price: number): string => {
  return `$${price.toFixed(2)}`
}

watch(allMenuItems, updateCategoryCounts, { immediate: true, deep: true })

onMounted(() => {
  loadCategories()
  loadMenuItems()
})

defineExpose({
  loadMenuItems,
  allMenuItems,
  categories,
  updateCategoryCounts,
  cartItems,
  cartTotal,
  filteredMenuItems,
  isLoadingMenu,
  selectedCategory,
  viewMode,
  handleCategorySelected,
  handleAddToCart,
  handleViewCart,
})
</script>

<style scoped>
.hide-scrollbar::-webkit-scrollbar {
  display: none;
}

.hide-scrollbar {
  -ms-overflow-style: none;
  scrollbar-width: none;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}
.animate-spin {
  animation: spin 0.8s linear infinite;
}

aside::-webkit-scrollbar {
  width: 4px;
}
aside::-webkit-scrollbar-track {
  background: transparent;
}
aside::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 9999px;
}
aside::-webkit-scrollbar-thumb:hover {
  background: #c29353;
}
</style>
