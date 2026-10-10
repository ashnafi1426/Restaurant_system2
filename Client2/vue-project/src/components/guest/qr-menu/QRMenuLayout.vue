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
            @category-selected="selectCategory"
          />
        </div>
      </aside>

      <main
        class="w-full lg:ml-60 bg-[#f9f8f6] dark:bg-slate-950 px-3 sm:px-5 lg:px-6 py-2 transition-colors pb-3"
      >
        <div class="mt-3">
          <MenuHero @view-specials="handleViewSpecials" />
        </div>

        <div
          v-if="!canOrder"
          class="mt-3 rounded-2xl bg-amber-50 dark:bg-amber-950/40 border-2 border-amber-300 dark:border-amber-700/60 p-4 shadow-sm"
        >
          <div class="flex items-start gap-3.5">
            <div
              class="w-10 h-10 rounded-full bg-amber-500/20 text-amber-600 dark:text-amber-400 flex items-center justify-center flex-shrink-0 mt-0.5"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                />
              </svg>
            </div>
            <div class="flex-1">
              <div class="flex items-center gap-2">
                <h3 class="text-sm sm:text-base font-bold text-amber-900 dark:text-amber-200">
                  Room Service Ordering Locked
                </h3>
                <span
                  v-if="reservationStatus"
                  class="px-2 py-0.5 text-[11px] font-semibold uppercase tracking-wider rounded-md bg-amber-200/60 dark:bg-amber-900/60 text-amber-800 dark:text-amber-300"
                >
                  {{ String(reservationStatus).replace('_', ' ') }}
                </span>
              </div>
              <p
                class="text-xs sm:text-sm text-amber-800 dark:text-amber-300/90 mt-1 leading-relaxed"
              >
                {{
                  eligibilityMessage ||
                  'Room service ordering is only available for checked-in guests. Please contact the front desk or complete check-in to place an order.'
                }}
              </p>
              <div
                class="mt-2 text-[11px] sm:text-xs text-amber-700/80 dark:text-amber-400 font-medium"
              >
                ℹ️ You can browse our menu. Ordering will be automatically enabled once your
                reservation is checked in.
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
            :class="getCategoryBtnClass(cat)"
          >
            <span>{{ languageStore.t(cat.name, cat.name) }}</span>
            <span v-if="cat.count !== undefined" :class="getCategoryBadgeClass(cat)">
              {{ cat.count }}
            </span>
          </button>
        </div>

        <div class="mt-3 mb-1.5 flex items-center justify-between">
          <div>
            <h2
              class="text-base sm:text-lg font-black text-slate-900 dark:text-white tracking-tight"
            >
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
            @view-reviews="handleViewReviews"
          />
        </div>

        <div
          class="mt-2.5 grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-4 gap-3 rounded-2xl bg-white dark:bg-slate-900 p-3 sm:p-4 border border-slate-200/80 dark:border-slate-800 shadow-xs mb-2 font-sans"
        >
          <div class="flex items-center gap-3">
            <div
              class="w-9 h-9 rounded-full bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800 flex items-center justify-center text-[#c29353] flex-shrink-0"
            >
              <Utensils class="w-4 h-4" />
            </div>
            <div>
              <h4 class="font-black text-xs text-slate-900 dark:text-white leading-tight">
                {{ languageStore.t('freshly_prepared', 'Freshly Prepared') }}
              </h4>
              <p class="text-[11px] text-slate-500 dark:text-slate-400 font-medium">
                {{ languageStore.t('premium_ingredients', 'Premium ingredients') }}
              </p>
            </div>
          </div>

          <div class="flex items-center gap-3">
            <div
              class="w-9 h-9 rounded-full bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800 flex items-center justify-center text-[#c29353] flex-shrink-0"
            >
              <Truck class="w-4 h-4" />
            </div>
            <div>
              <h4 class="font-black text-xs text-slate-900 dark:text-white leading-tight">
                {{ languageStore.t('fast_delivery', 'Fast Delivery') }}
              </h4>
              <p class="text-[11px] text-slate-500 dark:text-slate-400 font-medium">
                {{ languageStore.t('within_30_mins', 'Within 30 minutes') }}
              </p>
            </div>
          </div>

          <div class="flex items-center gap-3">
            <div
              class="w-9 h-9 rounded-full bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800 flex items-center justify-center text-[#c29353] flex-shrink-0"
            >
              <ShieldCheck class="w-4 h-4" />
            </div>
            <div>
              <h4 class="font-black text-xs text-slate-900 dark:text-white leading-tight">
                {{ languageStore.t('safe_hygienic', 'Safe & Hygienic') }}
              </h4>
              <p class="text-[11px] text-slate-500 dark:text-slate-400 font-medium">
                {{ languageStore.t('highest_standards', 'Highest standards') }}
              </p>
            </div>
          </div>

          <div class="flex items-center gap-3">
            <div
              class="w-9 h-9 rounded-full bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800 flex items-center justify-center text-[#c29353] flex-shrink-0"
            >
              <Clock class="w-4 h-4" />
            </div>
            <div>
              <h4 class="font-black text-xs text-slate-900 dark:text-white leading-tight">
                {{ languageStore.t('service_247', '24/7 Service') }}
              </h4>
              <p class="text-[11px] text-slate-500 dark:text-slate-400 font-medium">
                {{ languageStore.t('always_here', 'Always here to serve') }}
              </p>
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
            <svg
              class="w-6 h-6 sm:w-7 sm:h-7 text-white"
              fill="none"
              stroke="currentColor"
              stroke-width="2"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"
              />
            </svg>
            <div>
              <p class="text-white/70 text-xs sm:text-sm">
                {{ cartItems.length }} {{ languageStore.t('items_in_cart', 'Items in Cart') }}
              </p>
              <h3 class="text-lg sm:text-xl md:text-2xl font-bold">{{ formatPrice(cartTotal) }}</h3>
            </div>
          </div>

          <button
            v-if="canOrder"
            @click="handleViewCart"
            class="w-full sm:w-auto rounded-xl bg-amber-500 px-4 sm:px-6 md:px-10 py-2 sm:py-3 md:py-4 text-sm sm:text-base md:text-lg font-semibold text-white transition hover:bg-amber-600 cursor-pointer"
          >
            {{ languageStore.t('view_cart_checkout', 'View Cart & Checkout →') }}
          </button>
          <div
            v-else
            class="w-full sm:w-auto rounded-xl bg-slate-800 px-4 sm:px-6 py-2.5 sm:py-3.5 text-center text-xs sm:text-sm font-semibold text-amber-300 border border-amber-500/30"
          >
            🔒 Check-in Required to Order
          </div>
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
            <circle
              cx="50"
              cy="50"
              r="45"
              fill="none"
              stroke="#0EA5E9"
              stroke-width="6"
              opacity="0.3"
            />
          </svg>
          <div class="absolute inset-0 animate-spin" style="animation: spin 1.5s linear infinite">
            <svg viewBox="0 0 100 100" class="w-full h-full">
              <circle
                cx="50"
                cy="50"
                r="45"
                fill="none"
                stroke="#FBBF24"
                stroke-width="8"
                stroke-linecap="round"
                stroke-dasharray="70 280"
              />
            </svg>
          </div>
        </div>
        <p class="text-slate-700 dark:text-yellow-300 font-semibold text-sm">
          {{ languageStore.t('loading_menu', 'Loading menu...') }}
        </p>
      </div>
    </div>

    <GuestReviewModal
      :is-open="showReviewModal"
      :menu-item="selectedMenuItemForReview"
      :guest-name="guestName"
      :guest-email="guestEmail"
      :order-id="qrToken"
      @close="showReviewModal = false"
      @success="handleReviewSuccess"
      @error="handleReviewError"
    />

    <Teleport to="body">
      <Transition name="fade">
        <div
          v-if="showViewReviewsModal && selectedMenuItemForReview"
          class="fixed inset-0 bg-black/60 backdrop-blur-xs z-50 flex items-center justify-center p-4"
          @click.self="showViewReviewsModal = false"
        >
          <div
            class="bg-white dark:bg-slate-900 rounded-2xl shadow-2xl max-w-xl w-full max-h-[85vh] overflow-hidden border border-slate-200 dark:border-slate-800 flex flex-col"
          >
            <div
              class="p-4 sm:p-5 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between sticky top-0 bg-white dark:bg-slate-900 z-10"
            >
              <div class="flex items-center gap-3">
                <img
                  v-if="selectedMenuItemForReview.image"
                  :src="selectedMenuItemForReview.image"
                  :alt="selectedMenuItemForReview.name"
                  class="w-12 h-12 rounded-xl object-cover"
                />
                <div>
                  <h3 class="font-bold text-base text-slate-900 dark:text-white">
                    {{ selectedMenuItemForReview.name }}
                  </h3>
                  <p class="text-xs text-slate-500">
                    {{ languageStore.t('customer_reviews', 'Customer Ratings & Reviews') }}
                  </p>
                </div>
              </div>
              <button
                @click="showViewReviewsModal = false"
                class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 p-2 rounded-lg text-lg font-bold"
              >
                ✕
              </button>
            </div>

            <div class="p-4 sm:p-6 flex-1 overflow-y-auto">
              <PublicReviewsList :menu-item-id="String(selectedMenuItemForReview.id)" />
            </div>

            <div
              class="p-4 border-t border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 flex gap-3"
            >
              <button
                @click="showViewReviewsModal = false"
                class="flex-1 py-2.5 px-4 rounded-xl border border-slate-300 dark:border-slate-700 text-slate-700 dark:text-slate-300 font-semibold text-xs sm:text-sm hover:bg-slate-100 dark:hover:bg-slate-800 transition text-center"
              >
                {{ languageStore.t('close', 'Close') }}
              </button>
              <button
                @click="
                  showViewReviewsModal = false
                  showReviewModal = true
                "
                class="flex-1 py-2.5 px-4 rounded-xl bg-gradient-to-r from-amber-500 to-amber-600 text-white font-bold text-xs sm:text-sm shadow hover:shadow-md transition text-center"
              >
                ⭐ {{ languageStore.t('write_review', 'Write Review') }}
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { Utensils, Truck, ShieldCheck, Clock, ChevronRight } from 'lucide-vue-next'
import GuestNavbar from './GuestNavbar.vue'
import CategorySidebar from './CategorySidebar.vue'
import MenuSearch from './MenuSearch.vue'
import GuestReviewModal from '../GuestReviewModal.vue'
import PublicReviewsList from '@/components/reviews/PublicReviewsList.vue'
import MenuGrid from './MenuGrid.vue'
import MenuHero from './MenuHero.vue'
import { useLanguageStore } from '@/stores/language'
import { useGuestHotelStore } from '@/stores/guestHotelStore'
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
  hotelId?: string
  hotelName?: string
  heroImage?: string
  heroHeading?: string
  heroSubheading?: string
  itemsPerPage?: number
  canOrder?: boolean
  eligibilityMessage?: string
  reservationStatus?: string
}

const props = withDefaults(defineProps<Props>(), {
  guestName: 'Guest User',
  guestEmail: 'guest@royalhorizon.com',
  guestAvatar: '/images/avatar.png',
  roomNumber: '101',
  qrToken: '',
  hotelId: '',
  hotelName: '',
  heroImage: '/images/gallery/fine-dining.jpg',
  heroHeading: 'Good Food, Great Moments',
  heroSubheading: 'LUXURY DINING',
  itemsPerPage: 12,
  canOrder: true,
  eligibilityMessage: '',
  reservationStatus: '',
})

const languageStore = useLanguageStore()
const guestHotelStore = useGuestHotelStore()

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
const selectedMenuItemForReview = ref<any>(null)

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

const getMenuCacheKey = () =>
  `qr_menu_items_cache_${props.hotelId || guestHotelStore.hotelId || ''}_${props.qrToken || 'all'}`
const getCatCacheKey = () =>
  `qr_categories_cache_${props.hotelId || guestHotelStore.hotelId || ''}_${props.qrToken || 'all'}`

const loadFromClientCache = (): boolean => {
  try {
    const rawItems = localStorage.getItem(getMenuCacheKey())
    const rawCats = localStorage.getItem(getCatCacheKey())
    let hasData = false

    if (rawItems) {
      const parsedItems = JSON.parse(rawItems)
      if (Array.isArray(parsedItems) && parsedItems.length > 0) {
        allMenuItems.value = parsedItems
        hasData = true
      }
    }

    if (rawCats) {
      const parsedCats = JSON.parse(rawCats)
      if (Array.isArray(parsedCats) && parsedCats.length > 0) {
        categories.value = parsedCats
        hasData = true
      }
    }

    if (hasData) {
      isLoadingMenu.value = false
      return true
    }
  } catch (e) {
    // ignore parse error
  }
  return false
}

const saveToClientCache = () => {
  try {
    if (allMenuItems.value.length > 0) {
      localStorage.setItem(getMenuCacheKey(), JSON.stringify(allMenuItems.value))
    }
    if (categories.value.length > 0) {
      localStorage.setItem(getCatCacheKey(), JSON.stringify(categories.value))
    }
  } catch (e) {
    // ignore quota error
  }
}

const loadCategories = async (forceRefresh = false) => {
  loadingCategories.value = true
  try {
    const resolvedHotelId = props.hotelId || guestHotelStore.hotelId
    const params: Record<string, any> = {}
    if (props.qrToken) {
      params.qr_token = props.qrToken
    }
    if (resolvedHotelId) {
      params.hotel_id = resolvedHotelId
    }
    if (forceRefresh) {
      params.refresh = 1
    }

    const reqHeaders: Record<string, string> = {}
    if (forceRefresh) reqHeaders['X-Refresh'] = 'true'
    if (resolvedHotelId) reqHeaders['X-Hotel-ID'] = resolvedHotelId

    let rawCategories: any[] = []
    try {
      const response = await api.get('/guest/categories', {
        params,
        headers: Object.keys(reqHeaders).length > 0 ? reqHeaders : undefined,
      })
      rawCategories = response.data?.data || response.data || []
    } catch (guestErr) {
      console.warn(
        '[QRMenuLayout] /guest/categories endpoint unavailable, trying /categories:',
        guestErr,
      )
      const response = await api.get('/categories', {
        params,
        headers: Object.keys(reqHeaders).length > 0 ? reqHeaders : undefined,
      })
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
      saveToClientCache()
      return
    }
  } catch (error) {
    console.error('[QRMenuLayout] Error loading categories from API:', error)
  } finally {
    loadingCategories.value = false
  }

  deriveCategoriesFromMenuItems()
  updateCategoryCounts()
  saveToClientCache()
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

const loadMenuItems = async (forceRefresh = false) => {
  if (allMenuItems.value.length === 0) {
    isLoadingMenu.value = true
  }
  errorMessage.value = ''
  try {
    const resolvedHotelId = props.hotelId || guestHotelStore.hotelId
    let url = '/guest/menu/items'

    if (props.qrToken) {
      url = `/guest/menu/${props.qrToken}/items`
    }

    const params: Record<string, any> = {}
    if (forceRefresh) params.refresh = 1
    if (resolvedHotelId) params.hotel_id = resolvedHotelId
    if (props.qrToken) params.qr_token = props.qrToken

    const reqHeaders: Record<string, string> = {}
    if (forceRefresh) reqHeaders['X-Refresh'] = 'true'
    if (resolvedHotelId) reqHeaders['X-Hotel-ID'] = resolvedHotelId

    const response = await api.get(url, {
      params,
      headers: Object.keys(reqHeaders).length > 0 ? reqHeaders : undefined,
    })

    const rawData = response.data?.data ?? response.data
    if (rawData) {
      const isGrouped =
        Array.isArray(rawData) && rawData.length > 0 && Array.isArray((rawData[0] as any)?.items)
      if (isGrouped) {
        allMenuItems.value = rawData.flatMap((categoryGroup: any) => {
          const categoryName = categoryGroup.category || 'Other'
          return (categoryGroup.items || []).map((item: any) => {
            const rawPrice = parseFloat(item.price)
            const totalPrice =
              item.total_price != null
                ? parseFloat(item.total_price)
                : isNaN(rawPrice)
                  ? 0
                  : rawPrice
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
              rating:
                item.average_rating != null
                  ? Number(item.average_rating)
                  : item.rating != null
                    ? Number(item.rating)
                    : null,
              average_rating:
                item.average_rating != null
                  ? Number(item.average_rating)
                  : item.rating != null
                    ? Number(item.rating)
                    : null,
              review_count: item.review_count != null ? Number(item.review_count) : 0,
              is_available: item.is_available !== false,
            }
          })
        })
      } else if (Array.isArray(rawData)) {
        allMenuItems.value = rawData.map((item: any) => {
          const rawPrice = parseFloat(item.price)
          const totalPrice =
            item.total_price != null ? parseFloat(item.total_price) : isNaN(rawPrice) ? 0 : rawPrice
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
            rating:
              item.average_rating != null
                ? Number(item.average_rating)
                : item.rating != null
                  ? Number(item.rating)
                  : null,
            average_rating:
              item.average_rating != null
                ? Number(item.average_rating)
                : item.rating != null
                  ? Number(item.rating)
                  : null,
            review_count: item.review_count != null ? Number(item.review_count) : 0,
            is_available: item.is_available !== false,
          }
        })
      }
    }
    if (categories.value.length <= 1) {
      deriveCategoriesFromMenuItems()
    }
    updateCategoryCounts()
    saveToClientCache()
  } catch (error) {
    console.error('[QRMenuLayout] Error fetching menu items:', error)
    errorMessage.value = 'Failed to load menu items. Please try again.'
  } finally {
    isLoadingMenu.value = false
  }
}

function isCategoryActive(cat: any): boolean {
  if (!cat) return false
  if (cat.id === null && (selectedCategory.value === null || selectedCategory.value === 'all'))
    return true
  if (selectedCategory.value === cat.id) return true
  if (cat.slug && selectedCategory.value === cat.slug) return true
  return false
}

function getCategoryBtnClass(cat: any): string {
  const base =
    'px-3.5 py-1.5 rounded-full text-xs font-bold whitespace-nowrap transition cursor-pointer flex items-center gap-1.5 flex-shrink-0'
  return isCategoryActive(cat)
    ? `${base} bg-[#c29353] text-white shadow-xs font-black`
    : `${base} bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-300 border border-slate-200/80 dark:border-slate-800`
}

function getCategoryBadgeClass(cat: any): string {
  const base = 'px-1.5 py-0.5 rounded-full text-[10px] font-black'
  return isCategoryActive(cat)
    ? `${base} bg-white/20 text-white`
    : `${base} bg-slate-100 dark:bg-slate-800 text-slate-500`
}

function normalizeCat(str: string | number | null | undefined): string {
  if (!str) return ''
  return String(str)
    .toLowerCase()
    .trim()
    .replace(/[^a-z0-9]/g, '')
}

function matchCategory(
  itemCat: string | null | undefined,
  selectedCat: string | number | null | undefined,
): boolean {
  if (
    selectedCat === null ||
    selectedCat === undefined ||
    selectedCat === '' ||
    selectedCat === 'all'
  )
    return true

  let targetNameOrSlug = String(selectedCat)
  if (categories.value && categories.value.length > 0) {
    const matchedCatObj = categories.value.find(
      (c) =>
        String(c.id) === String(selectedCat) ||
        String(c.slug) === String(selectedCat) ||
        String(c.name).toLowerCase() === String(selectedCat).toLowerCase(),
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
      const count = allMenuItems.value.filter((item) =>
        matchCategory(item.category, cat.id || cat.name),
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
  const query =
    typeof rawQuery === 'string'
      ? rawQuery.toLowerCase().trim()
      : rawQuery && typeof rawQuery === 'object' && (rawQuery as any).name
        ? String((rawQuery as any).name)
            .toLowerCase()
            .trim()
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

const selectCategory = (catOrId: any) => {
  let catVal: string | null = null
  if (
    catOrId === null ||
    catOrId === undefined ||
    catOrId === 'all' ||
    catOrId === 'All Categories'
  ) {
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

const handleCategorySelected = selectCategory
const handleCategorySelectedMobile = selectCategory

const updateSearch = (query: any) => {
  if (typeof query === 'string') {
    searchQuery.value = query
  } else if (query && typeof query === 'object' && query.name) {
    searchQuery.value = String(query.name)
  } else {
    searchQuery.value = String(query || '')
  }
}

const handleSearchQueryChanged = updateSearch

const selectSuggestion = (suggestion: any) => {
  if (typeof suggestion === 'string') {
    searchQuery.value = suggestion
  } else if (suggestion && typeof suggestion === 'object' && suggestion.name) {
    searchQuery.value = String(suggestion.name)
  } else {
    searchQuery.value = ''
  }
}

const handleSuggestionSelected = selectSuggestion

const handleAddToCart = (item: MenuItem, quantity: number) => {
  if (props.canOrder === false) {
    alert(
      props.eligibilityMessage ||
        'Room service ordering is only available for checked-in guests. Please contact the front desk.',
    )
    return
  }
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

const showViewReviewsModal = ref(false)

const handleWriteReview = (item: MenuItem) => {
  selectedMenuItemForReview.value = item
  showReviewModal.value = true
}

const handleViewReviews = (item: MenuItem) => {
  selectedMenuItemForReview.value = item
  showViewReviewsModal.value = true
}

const handleReviewSuccess = (_message: string) => {
  showReviewModal.value = false
  window.dispatchEvent(new Event('review-stats-updated'))
  loadMenuItems(true)
}

const handleReviewError = (_message: string) => {}

const handleExplore = () => {
  const menuSection = document.getElementById('menu-grid')
  if (menuSection) {
    menuSection.scrollIntoView({ behavior: 'smooth', block: 'start' })
  }
}

const handleViewSpecials = (categoryName?: string) => {
  if (categoryName) {
    const matchedCategory = categories.value.find(
      (c) =>
        c.name.toLowerCase() === categoryName.toLowerCase() ||
        (c.slug && c.slug.toLowerCase() === categoryName.toLowerCase()) ||
        c.name.toLowerCase().includes(categoryName.toLowerCase()),
    )
    if (matchedCategory) {
      selectCategory(matchedCategory)
    } else {
      selectCategory(null)
    }
  } else {
    selectCategory(null)
  }
  handleExplore()
}

const formatPrice = (price: number): string => {
  return `${price.toFixed(2)}`
}

watch(
  allMenuItems,
  () => {
    if (categories.value.length <= 1) {
      deriveCategoriesFromMenuItems()
    }
    updateCategoryCounts()
  },
  { immediate: true, deep: true },
)

watch(
  () => props.qrToken,
  (newVal, oldVal) => {
    if (newVal && newVal !== oldVal) {
      loadFromClientCache()
      loadCategories(true)
      loadMenuItems(true)
    }
  },
)

watch(
  () => props.hotelId,
  (newVal, oldVal) => {
    if (newVal && newVal !== oldVal) {
      loadFromClientCache()
      loadCategories(true)
      loadMenuItems(true)
    }
  },
)

const handleReviewStatsUpdated = () => {
  loadMenuItems(true)
}

onMounted(() => {
  const hasCached = loadFromClientCache()
  if (hasCached) {
    isLoadingMenu.value = false
  }
  loadCategories(false)
  loadMenuItems(true)

  window.addEventListener('review-stats-updated', handleReviewStatsUpdated)
})

onUnmounted(() => {
  window.removeEventListener('review-stats-updated', handleReviewStatsUpdated)
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
