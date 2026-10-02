<template>
  <div>
    <nav
      class="guest-navbar fixed top-0 left-0 right-0 z-50 bg-white dark:bg-slate-900 border-b border-gray-100 dark:border-slate-700 shadow-sm dark:shadow-slate-950/50 transition-colors"
    >
      <div
        class="absolute bottom-0 left-0 right-0 h-[2px] bg-gradient-to-r from-transparent via-amber-400/60 dark:via-amber-500/40 to-transparent"
      ></div>

      <div class="px-4 md:px-6 h-16 md:h-20 flex items-center justify-between">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 md:w-11 md:h-11 flex items-center justify-center flex-shrink-0">
            <img 
              v-if="hotelLogoUrl && !imageLoadFailed" 
              :src="hotelLogoUrl" 
              :alt="guestHotelStore.hotelName" 
              class="w-full h-full object-contain select-none" 
              @error="imageLoadFailed = true" 
            />
            <span v-else class="text-amber-600 dark:text-amber-400 font-serif font-black text-xl">
              {{ (guestHotelStore.hotelName || 'H').charAt(0).toUpperCase() }}
            </span>
          </div>
          <div class="hidden sm:block">
            <h1 class="text-sm md:text-base font-bold text-slate-900 dark:text-slate-100 transition-colors uppercase tracking-[0.14em] font-serif leading-tight">
              {{ guestHotelStore.hotelName }}
            </h1>
            <p
              class="text-[8px] md:text-[9.5px] text-amber-600 dark:text-amber-400 font-semibold tracking-[0.25em] uppercase transition-colors mt-0.5"
            >
              {{ guestHotelStore.currentHotel?.city || 'Hotel & Resort' }}
            </p>
          </div>
        </div>

        <div class="hidden md:flex flex-col items-center">
          <h2 class="text-lg lg:text-xl font-serif font-bold text-slate-900 dark:text-slate-100 transition-colors">{{ languageStore.t('digital_menu', 'Our Menu') }}</h2>
          <p class="text-xs text-slate-500 dark:text-slate-400 transition-colors">{{ languageStore.t('culinary_desc', 'Delicious meals, delivered to your room') }}</p>
        </div>

        <div class="flex items-center gap-2 sm:gap-2.5">
          <div class="flex items-center gap-1.5 sm:gap-2">
            <button
              @click="handleThemeToggle"
              class="flex items-center justify-center w-9 h-9 rounded-full border border-slate-200/80 dark:border-slate-800 bg-white dark:bg-slate-900 hover:bg-slate-50 transition cursor-pointer shadow-2xs shrink-0"
              :title="theme.isDark ? languageStore.t('light_mode', 'Switch to Light Mode') : languageStore.t('dark_mode', 'Switch to Dark Mode')"
            >
              <Sun v-if="theme.isDark" class="w-4 h-4 text-amber-400" />
              <Moon v-else class="w-4 h-4 text-slate-700" />
            </button>

            <button
              @click="toggleFullscreen"
              class="flex items-center justify-center w-9 h-9 rounded-full border border-slate-200/80 dark:border-slate-800 bg-white dark:bg-slate-900 hover:bg-slate-50 transition-all cursor-pointer shadow-2xs shrink-0"
              :title="isFullscreen ? languageStore.t('exit_fullscreen', 'Exit Fullscreen') : languageStore.t('fullscreen', 'Enter Fullscreen')"
            >
              <Maximize v-if="!isFullscreen" class="w-4 h-4 text-amber-500" />
              <Minimize v-else class="w-4 h-4 text-amber-500" />
            </button>

            <!-- Language Selector -->
            <LanguageSelector variant="compact" />

            <button
              @click="toggleProfileDropdown"
              class="flex items-center gap-2 h-9 px-4 rounded-full bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-400 hover:to-amber-500 text-slate-950 text-xs font-black shadow-sm transition cursor-pointer shrink-0"
            >
              <User class="w-3.5 h-3.5" />
              <span class="max-w-[120px] truncate">{{ guestName || languageStore.t('walk_in_guest', 'Walk-in Guest') }}</span>
              <ChevronDown class="w-3 h-3 text-slate-950/70" />
            </button>

            <Transition name="dropdown">
              <div
                v-if="showProfileDropdown"
                class="absolute right-0 mt-2 top-full w-64 bg-white dark:bg-slate-900 rounded-2xl shadow-xl border border-slate-200/80 dark:border-slate-800 z-50 overflow-hidden font-sans"
              >
                <div class="p-4 bg-[#fffdf5] dark:bg-slate-800/80 border-b border-amber-100 dark:border-slate-800 flex items-center gap-3">
                  <div class="w-11 h-11 rounded-full border-2 border-[#c29353] bg-[#c29353]/10 flex items-center justify-center text-amber-700 dark:text-amber-400 flex-shrink-0">
                    <User class="w-5 h-5" />
                  </div>
                  <div>
                    <p class="text-sm font-black text-slate-900 dark:text-white leading-tight">{{ guestName || languageStore.t('walk_in_guest', 'Walk-in Guest') }}</p>
                    <p class="text-xs text-slate-500 dark:text-slate-400 font-medium">{{ languageStore.t('room', 'Room') }} {{ currentRoom || '101' }}</p>
                  </div>
                </div>

                <div class="py-1">
                  <button
                    @click="handleViewProfile"
                    class="w-full px-5 py-3 text-left text-xs font-bold text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800 transition flex items-center gap-3 cursor-pointer"
                  >
                    <User class="w-4 h-4 text-purple-600 dark:text-purple-400" />
                    <span>{{ languageStore.t('my_profile', 'My Profile') }}</span>
                  </button>
                  <button
                    @click="handleMyOrders"
                    class="w-full px-5 py-3 text-left text-xs font-bold text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800 transition flex items-center gap-3 cursor-pointer"
                  >
                    <ShoppingBag class="w-4 h-4 text-amber-600 dark:text-amber-400" />
                    <span>{{ languageStore.t('my_orders', 'My Orders') }}</span>
                  </button>
                  <button
                    @click="handleSettings"
                    class="w-full px-5 py-3 text-left text-xs font-bold text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800 transition flex items-center gap-3 cursor-pointer"
                  >
                    <Settings class="w-4 h-4 text-slate-500 dark:text-slate-400" />
                    <span>{{ languageStore.t('settings', 'Settings') }}</span>
                  </button>
                  <div class="border-t border-slate-100 dark:border-slate-800 my-1"></div>
                  <button
                    @click="handleLogout"
                    class="w-full px-5 py-3 text-left text-xs font-bold text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-950/30 transition flex items-center gap-3 cursor-pointer"
                  >
                    <LogOut class="w-4 h-4 text-rose-500" />
                    <span>{{ languageStore.t('logout', 'Logout') }}</span>
                  </button>
                </div>
              </div>
            </Transition>
          </div>

          <button
            @click="toggleMobileMenu"
            class="md:hidden flex items-center justify-center w-10 h-10 rounded-xl border border-slate-200 dark:border-slate-800 hover:bg-amber-50 dark:hover:bg-slate-800 transition-colors shrink-0"
            :title="showMobileMenu ? 'Close menu' : 'Open menu'"
          >
            <svg
              v-if="!showMobileMenu"
              class="w-6 h-6 text-slate-600 dark:text-slate-300"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M4 6h16M4 12h16M4 18h16"
              />
            </svg>
            <svg
              v-else
              class="w-6 h-6 text-slate-600 dark:text-slate-300"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M6 18L18 6M6 6l12 12"
              />
            </svg>
          </button>
        </div>
      </div>
    </nav>

    <Teleport to="body">
      <!-- Backdrop Dimmer -->
      <Transition name="fade">
        <div
          v-if="showMobileMenu"
          class="fixed inset-0 bg-black/40 backdrop-blur-xs z-[90] md:hidden transition-opacity"
          @click="closeMobileMenu"
        ></div>
      </Transition>

      <!-- Minimized Mobile Drawer (Slides from Right, compact width ~72-80 / max 82vw) -->
      <Transition name="slide-right">
        <div
          v-if="showMobileMenu"
          class="fixed top-0 right-0 bottom-0 w-72 sm:w-80 max-w-[85vw] bg-white dark:bg-slate-900 md:hidden z-[100] shadow-2xl flex flex-col border-l border-slate-200/80 dark:border-slate-800 transition-colors font-sans"
        >
          <!-- Compact Drawer Header -->
          <div class="px-4 py-3.5 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between flex-shrink-0">
            <div class="flex items-center gap-2">
              <span class="w-2 h-2 rounded-full bg-[#c29353]"></span>
              <span class="text-xs font-black text-slate-800 dark:text-slate-100 uppercase tracking-wider">
                {{ languageStore.t('categories', 'Categories') }}
              </span>
            </div>
            <div class="flex items-center gap-2">
              <LanguageSelector variant="compact" />
              <button
                type="button"
                @click.stop="closeMobileMenu"
                class="p-1.5 rounded-lg text-slate-400 hover:text-slate-700 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800 transition cursor-pointer"
                title="Close menu"
              >
                <X class="w-4 h-4" />
              </button>
            </div>
          </div>

          <!-- Drawer Body with Scroll -->
          <div class="p-3 space-y-2.5 flex-1 overflow-y-auto">
            <!-- Mobile Search Input (Compact) -->
            <div class="relative">
              <input
                v-model="mobileSearchQuery"
                @input="handleMobileSearch"
                type="text"
                :placeholder="languageStore.t('search_placeholder', 'Search menu...')"
                class="w-full pl-8 pr-3 py-2 bg-slate-100 dark:bg-slate-800 text-slate-900 dark:text-white placeholder-slate-400 text-xs rounded-xl border border-slate-200 dark:border-slate-700 focus:outline-none focus:border-[#c29353] font-medium"
              />
              <Search class="absolute left-2.5 top-2.5 w-3.5 h-3.5 text-[#c29353]" />
            </div>

            <!-- Loading Categories -->
            <div
              v-if="loadingCategories && mobileCategories.length === 0"
              class="flex items-center justify-center py-6 text-slate-400 dark:text-slate-500 text-xs"
            >
              <div class="w-4 h-4 border-2 border-[#c29353] border-t-transparent rounded-full animate-spin mr-2"></div>
              Loading...
            </div>

            <!-- Categories List (Compact) -->
            <div class="space-y-1">
              <button
                v-for="category in mobileCategories"
                :key="category.id || category.slug || category.name"
                type="button"
                @click.stop="selectCategoryMobile(category)"
                class="flex items-center gap-2.5 w-full px-3 py-2 rounded-xl transition-all duration-200 text-left cursor-pointer relative z-10"
                :class="[
                  isCategoryActive(category)
                    ? 'bg-[#c29353] text-white font-black shadow-xs'
                    : 'bg-slate-50/80 dark:bg-slate-800/50 text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 font-medium'
                ]"
              >
                <div
                  class="flex items-center justify-center flex-shrink-0 w-5"
                  :class="{ 'text-white': isCategoryActive(category), 'text-slate-500 dark:text-slate-400': !isCategoryActive(category) }"
                >
                  <component :is="getCategoryIcon(category)" :size="16" :stroke-width="2" />
                </div>
                <span class="text-xs flex-1 truncate">{{ category.name }}</span>
                <span
                  v-if="category.count !== undefined && category.count !== null"
                  class="text-[10px] px-1.5 py-0.5 rounded-full font-bold transition-colors"
                  :class="[
                    isCategoryActive(category)
                      ? 'bg-white/20 text-white'
                      : 'bg-slate-200 dark:bg-slate-700 text-slate-600 dark:text-slate-300'
                  ]"
                >
                  {{ category.count }}
                </span>
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

    <div class="h-16 md:h-20"></div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted, onBeforeUnmount } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../../../stores/auth'
import { useThemeStore } from '../../../stores/themeStore'
import { useLanguageStore } from '@/stores/language'
import { useGuestHotelStore } from '@/stores/guestHotelStore'
import LanguageSelector from '@/components/common/LanguageSelector.vue'
import api from '../../../api/auth'
import { Sun, Moon, Maximize, Minimize, User, ShoppingBag, Settings, LogOut, ChevronDown, Search, X } from 'lucide-vue-next'
import {
  Clock,
  Utensils,
  Leaf,
  Soup,
  Salad,
  UtensilsCrossed,
  Layers,
  Pizza,
  Sandwich,
  Cake,
  Wine,
  Menu,
  Grid,
  Coffee,
} from 'lucide-vue-next'

interface Room {
  id: string
  room_number: string
  status: string
  is_active: boolean
}

interface Category {
  id: string | null
  slug?: string
  name: string
  icon: string
  count?: number
}

interface Props {
  guestName?: string
  guestEmail?: string
  guestAvatar?: string
  initialRoom?: string | number
  categories?: Category[]
  selectedCategoryId?: string | null
}

const props = withDefaults(defineProps<Props>(), {
  guestName: '',
  guestEmail: '',
  guestAvatar: '',
  initialRoom: '',
  categories: () => [],
  selectedCategoryId: null,
})

const emit = defineEmits<{
  search: [query: string]
  logout: []
  settings: []
  orders: []
  'view-profile': []
  'room-selected': [room: string | number]
  'category-selected': [categoryId: string | null]
}>()

const router = useRouter()
const authStore = useAuthStore()
const theme = useThemeStore()
const languageStore = useLanguageStore()
const guestHotelStore = useGuestHotelStore()

const currentRoom = ref<string | number>(props.initialRoom || '')
const showRoomDropdown = ref(false)
const showProfileDropdown = ref(false)
const showMobileMenu = ref(false)
const availableRooms = ref<Room[]>([])
const roomsLoading = ref(false)
const selectedMobileCategory = ref<string | null>(props.selectedCategoryId)
const isFullscreen = ref(false)
const mobileSearchQuery = ref('')
const imageLoadFailed = ref(false)

watch(
  () => [guestHotelStore.hotelId, guestHotelStore.currentHotel?.logo],
  () => {
    imageLoadFailed.value = false
  },
  { immediate: true }
)

const hotelLogoUrl = computed(() => {
  if (imageLoadFailed.value) return null
  const logo = guestHotelStore.currentHotel?.logo
  if (logo && typeof logo === 'string' && logo.trim()) {
    const trimmed = logo.trim()
    if (
      trimmed.startsWith('http://') ||
      trimmed.startsWith('https://') ||
      trimmed.startsWith('data:') ||
      trimmed.startsWith('/images/')
    ) {
      return trimmed
    }
    if (trimmed.startsWith('/storage/')) {
      return `http://127.0.0.1:8000${trimmed}`
    }
    if (trimmed.startsWith('storage/') || trimmed.startsWith('hotels/')) {
      return `http://127.0.0.1:8000/storage/${trimmed.replace(/^storage\//, '')}`
    }
    if (trimmed.startsWith('images/')) {
      return `/${trimmed}`
    }
    return `http://127.0.0.1:8000/storage/${trimmed}`
  }

  const name = (guestHotelStore.hotelName || '').toLowerCase()
  if (name.includes('qelem') || name.includes('meda')) {
    return '/images/qelem meda logo.png'
  }
  return '/images/Hotel logo.png'
})

watch(
  () => props.selectedCategoryId,
  (newVal) => {
    selectedMobileCategory.value = newVal
  },
  { immediate: true },
)

const backendCategories = ref<Category[]>([])
const loadingCategories = ref(false)

async function fetchCategoriesFromBackend() {
  loadingCategories.value = true
  try {
    let list: any[] = []
    try {
      const res = await api.get('/guest/categories')
      list = res.data?.data || res.data || []
    } catch (guestErr) {
      console.warn('[GuestNavbar] /guest/categories endpoint unavailable, trying /categories:', guestErr)
      const res = await api.get('/categories')
      list = res.data?.data || res.data || []
    }

    if (Array.isArray(list) && list.length > 0) {
      backendCategories.value = [
        { id: null, name: 'All Categories', icon: 'menu', count: 0 },
        ...list.map((cat: any) => ({
          id: cat.slug || cat.id || String(cat.name || '').toLowerCase(),
          name: cat.name || cat.slug || 'Category',
          icon: cat.icon || cat.slug || 'utensils',
          count: cat.count ?? cat.menu_items_count ?? undefined,
        })),
      ]
    }
  } catch (err) {
    console.error('[GuestNavbar] Error fetching categories from backend:', err)
  } finally {
    loadingCategories.value = false
  }
}

watch(
  () => props.categories,
  (newVal) => {
    if (!newVal || newVal.length === 0) {
      fetchCategoriesFromBackend()
    }
  },
  { immediate: true },
)

function handleMobileSearch() {
  emit('search', mobileSearchQuery.value)
}

function toggleFullscreen() {
  if (!document.fullscreenElement) {
    document.documentElement.requestFullscreen().then(() => {
      isFullscreen.value = true
    }).catch((err) => {
      console.error('[GuestNavbar] Request fullscreen error:', err)
    })
  } else {
    if (document.exitFullscreen) {
      document.exitFullscreen().then(() => {
        isFullscreen.value = false
      }).catch((err) => {
        console.error('[GuestNavbar] Exit fullscreen error:', err)
      })
    }
  }
}

const getIconComponent = (categoryId: string | null) => {
  if (!categoryId) return Menu
  const key = String(categoryId).toLowerCase().trim().replace(/_/g, '-')
  const iconMap: { [key: string]: any } = {
    menu: Menu,
    grid: Grid,
    all: Grid,
    clock: Clock,
    breakfast: Clock,
    utensils: Utensils,
    lunch: Utensils,
    dinner: Utensils,
    starter: Leaf,
    starters: Leaf,
    leaf: Leaf,
    appetizers: Leaf,
    appetizer: Leaf,
    soup: Soup,
    soups: Soup,
    salad: Salad,
    salads: Salad,
    'utensils-crossed': UtensilsCrossed,
    'main-course': UtensilsCrossed,
    'main course': UtensilsCrossed,
    'main-courses': UtensilsCrossed,
    main: UtensilsCrossed,
    layers: Layers,
    pasta: Layers,
    pizza: Pizza,
    sandwich: Sandwich,
    sandwiches: Sandwich,
    burger: Sandwich,
    burgers: Sandwich,
    cake: Cake,
    dessert: Cake,
    desserts: Cake,
    wine: Wine,
    beverage: Wine,
    beverages: Wine,
    drinks: Wine,
    drink: Wine,
    coffee: Coffee,
    tea: Coffee,
  }
  return iconMap[key] || Utensils
}

const getCategoryIcon = (category: any) => {
  if (!category) return Menu

  const name = String(category.name || '').toLowerCase().trim()
  const slug = String(category.slug || '').toLowerCase().trim()
  const icon = String(category.icon || '').toLowerCase().trim()
  const id = String(category.id || '').toLowerCase().trim()

  if (category.id === null || name === 'all categories' || slug === 'all' || name === 'all') {
    return Grid
  }

  if (icon && icon !== 'grid' && icon !== 'menu') {
    const matched = getIconComponent(icon)
    if (matched !== Utensils && matched !== Menu) return matched
  }

  const text = `${slug} ${name} ${id} ${icon}`

  if (text.includes('break') || text.includes('egg') || text.includes('morn') || text.includes('pancake') || text.includes('toast') || text.includes('clock')) {
    return Clock
  }
  if (text.includes('soup') || text.includes('broth') || text.includes('stew') || text.includes('ramen') || text.includes('chowder')) {
    return Soup
  }
  if (text.includes('appetiz') || text.includes('starter') || text.includes('snack') || text.includes('finger') || text.includes('leaf') || text.includes('bruschetta')) {
    return Leaf
  }
  if (text.includes('salad') || text.includes('green') || text.includes('veg')) {
    return Salad
  }
  if (text.includes('sandw') || text.includes('burger') || text.includes('wrap') || text.includes('sub') || text.includes('panini')) {
    return Sandwich
  }
  if (text.includes('pasta') || text.includes('noodl') || text.includes('spaghetti') || text.includes('layer') || text.includes('lasagna')) {
    return Layers
  }
  if (text.includes('pizza') || text.includes('pie') || text.includes('calzone')) {
    return Pizza
  }
  if (text.includes('dessert') || text.includes('cake') || text.includes('sweet') || text.includes('pastry') || text.includes('ice cream') || text.includes('chocolate') || text.includes('pudding')) {
    return Cake
  }
  if (text.includes('bev') || text.includes('drink') || text.includes('wine') || text.includes('beer') || text.includes('cocktail') || text.includes('bar') || text.includes('juice') || text.includes('smoothie')) {
    return Wine
  }
  if (text.includes('coffee') || text.includes('tea') || text.includes('latte') || text.includes('cappuccino') || text.includes('cafe')) {
    return Coffee
  }
  if (text.includes('main') || text.includes('dinner') || text.includes('lunch') || text.includes('entree') || text.includes('steak') || text.includes('grill') || text.includes('meat') || text.includes('seafood') || text.includes('fish') || text.includes('chicken')) {
    return UtensilsCrossed
  }

  return Utensils
}

const guestName = computed(() => props.guestName || authStore.user?.name || 'Guest User')
const guestEmail = computed(
  () => props.guestEmail || authStore.user?.email || 'guest@royalhorizon.com',
)
const guestAvatar = computed(() => props.guestAvatar || authStore.user?.avatar || '')
const mobileCategories = computed(() => {
  if (props.categories && props.categories.length > 0) {
    return props.categories
  }
  return backendCategories.value
})

async function fetchAvailableRooms() {
  roomsLoading.value = true
  try {
    const response = await api.get('/rooms', { params: { status: 'available', per_page: 100 } })
    if (response.data?.data) availableRooms.value = response.data.data
  } catch (err) {
    console.error('[GuestNavbar] Error fetching available rooms:', err)
  } finally {
    roomsLoading.value = false
  }
}

function toggleRoomDropdown() {
  showRoomDropdown.value = !showRoomDropdown.value
  showProfileDropdown.value = false
}

function toggleProfileDropdown() {
  showProfileDropdown.value = !showProfileDropdown.value
  showRoomDropdown.value = false
}

function toggleMobileMenu() {
  showMobileMenu.value = !showMobileMenu.value
}

function closeMobileMenu() {
  showMobileMenu.value = false
}

function selectRoom(room: string) {
  currentRoom.value = room
  showRoomDropdown.value = false
  emit('room-selected', room)
}

function isCategoryActive(cat: any): boolean {
  if (!cat) return false
  const catName = String(cat.name || '').toLowerCase().trim()
  const catSlug = String(cat.slug || '').toLowerCase().trim()
  const catId = cat.id !== undefined && cat.id !== null ? String(cat.id).toLowerCase().trim() : null

  const selected = selectedMobileCategory.value

  if (selected === null || selected === '' || selected === 'all') {
    return cat.id === null || catName === 'all categories' || catSlug === 'all'
  }

  const selStr = String(selected).toLowerCase().trim()
  return (
    catId === selStr ||
    catSlug === selStr ||
    catName === selStr ||
    (catSlug && selStr.includes(catSlug)) ||
    (catName && selStr.includes(catName))
  )
}

function selectCategoryMobile(cat: any) {
  let categoryId: string | null = null
  if (typeof cat === 'object' && cat !== null) {
    if (cat.name === 'All Categories' || cat.id === null || cat.slug === 'all') {
      categoryId = null
    } else {
      categoryId = cat.slug || cat.id || cat.name
    }
  } else {
    categoryId = (cat === 'All Categories' || cat === 'all') ? null : cat
  }

  selectedMobileCategory.value = categoryId
  showMobileMenu.value = false
  emit('category-selected', categoryId)

  setTimeout(() => {
    const el = document.getElementById('menu-grid')
    if (el) {
      el.scrollIntoView({ behavior: 'smooth', block: 'start' })
    }
  }, 100)
}

function handleViewProfile() {
  showProfileDropdown.value = false
  showMobileMenu.value = false
  emit('view-profile')
  router.push('/profile')
}

function handleMyOrders() {
  showProfileDropdown.value = false
  showMobileMenu.value = false
  emit('orders')
  router.push('/orders')
}

function handleSettings() {
  showProfileDropdown.value = false
  showMobileMenu.value = false
  emit('settings')
  router.push('/settings')
}

function handleSearchClick() {
  showMobileMenu.value = false
  emit('search', '')
}

function handleLogout() {
  showProfileDropdown.value = false
  showMobileMenu.value = false
  authStore.logout()
  emit('logout')
  router.push('/login')
}

function handleOutsideClick(event: MouseEvent) {
  const target = event.target as HTMLElement
  if (!target.closest('.guest-navbar')) {
    showRoomDropdown.value = false
    showProfileDropdown.value = false
  }
}

const handleThemeToggle = () => {
  theme.toggleTheme()
}

onMounted(() => {
  window.addEventListener('click', handleOutsideClick)
  fetchAvailableRooms()
  if (!props.categories || props.categories.length === 0) {
    fetchCategoriesFromBackend()
  }
})

onBeforeUnmount(() => {
  window.removeEventListener('click', handleOutsideClick)
})
</script>

<style scoped>
.dropdown-enter-active,
.dropdown-leave-active {
  transition: all 0.15s ease;
}

.dropdown-enter-from,
.dropdown-leave-to {
  opacity: 0;
  transform: scale(0.95) translateY(-8px);
}

.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

.slide-in-enter-active,
.slide-in-leave-active {
  transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.slide-in-enter-from,
.slide-in-leave-to {
  transform: translateY(-100%);
}

.slide-right-enter-active,
.slide-right-leave-active {
  transition: transform 0.25s cubic-bezier(0.16, 1, 0.3, 1), opacity 0.2s ease;
}

.slide-right-enter-from,
.slide-right-leave-to {
  transform: translateX(100%);
  opacity: 0;
}

.guest-navbar {
  transition: box-shadow 0.3s ease;
}

.guest-navbar:hover {
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
}
</style>
