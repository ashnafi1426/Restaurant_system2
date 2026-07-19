<template>
  <div class="min-h-screen bg-white">
    <!-- Header -->
    <div class="sticky top-0 z-50 bg-white border-b border-gray-100">
      <div class="flex items-center justify-between px-4">
        <!-- Sidebar Toggle Button (Mobile) -->
        <button 
          @click="sidebarOpen = !sidebarOpen"
          class="lg:hidden p-2 hover:bg-gray-100 rounded-lg transition"
        >
          <svg v-if="!sidebarOpen" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
          </svg>
          <svg v-else class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
          </svg>
        </button>

        <!-- Navbar -->
        <div class="flex-1">
          <GuestNavbar
            :guest-name="guestName"
            :guest-email="guestEmail"
            :guest-avatar="guestAvatar"
            :initial-room="roomNumber"
            :categories="categories"
            @search="handleSearch"
            @room-selected="handleRoomSelected"
            @category-selected="handleCategorySelected"
            @logout="handleLogout"
            class="bg-white border-0"
          />
        </div>
      </div>
    </div>

    <!-- Main Content Container -->
    <div class="flex relative">

      <!-- LEFT SIDEBAR - FIXED NO SCROLL (Desktop Only) -->
      <aside class="hidden lg:block fixed left-0 top-20 w-48 h-auto bg-white border-r border-gray-100 z-40 overflow-hidden">
        
        <!-- Sidebar Wrapper - Exactly sized content -->
        <div class="w-full p-2 space-y-2">
          
          <!-- Part 1: Categories - Fixed Height -->
          <div class="w-full max-h-max">
            <CategorySidebar
              :categories="categories"
              :selected-category-id="selectedCategory"
              :total-items="allMenuItems.length"
              @category-selected="handleCategorySelected"
            />
          </div>
        </div>

      </aside>

      <!-- Mobile Sidebar (Slide-out) -->
      <div
        v-if="sidebarOpen"
        class="fixed inset-0 bg-black/50 z-30 lg:hidden top-20"
        @click="sidebarOpen = false"
      ></div>

      <aside
        class="fixed left-0 top-20 w-64 sm:w-72 h-auto bg-white border-r border-gray-100 z-40 overflow-y-auto lg:hidden transition-transform duration-300"
        :class="[sidebarOpen ? 'translate-x-0' : '-translate-x-full']"
      >
        <div class="w-full p-4 sm:p-6 space-y-4">
          <CategorySidebar
            :categories="categories"
            :selected-category-id="selectedCategory"
            :total-items="allMenuItems.length"
            @category-selected="handleCategorySelectedMobile"
          />
        </div>
      </aside>

      <!-- RIGHT CONTENT - Full Width on Mobile, Adjusted on Desktop -->
      <main class="w-full lg:ml-48 bg-white px-4 lg:px-6">

          <!-- Hero Section - Compact Height -->
          <div class="relative h-48 sm:h-56 md:h-64 lg:h-72 rounded-2xl sm:rounded-3xl overflow-hidden shadow-lg mt-6">
            
            <!-- Background Image -->
            <div class="absolute inset-0 bg-slate-800">
              <img
                :src="heroImage"
                alt="Restaurant Hero"
                class="w-full h-full object-cover"
                loading="eager"
              />
              <div class="absolute inset-0 bg-gradient-to-r from-black/80 via-black/60 to-black/40"></div>
            </div>

            <!-- Content -->
            <div class="absolute inset-0 flex items-center">
              <div class="container mx-auto px-4 sm:px-6 md:px-8">
                <div class="max-w-2xl">
                  <!-- Badge -->
                  <div class="flex items-center gap-2 mb-1 sm:mb-2">
                    <div class="w-6 sm:w-8 h-0.5 bg-amber-400 rounded-full"></div>
                    <span class="text-[10px] sm:text-xs font-semibold text-amber-300 tracking-widest uppercase">
                      {{ heroSubheading || 'LUXURY DINING' }}
                    </span>
                  </div>

                  <!-- Heading -->
                  <h1 class="text-xl sm:text-2xl md:text-3xl lg:text-4xl font-serif font-bold text-white leading-tight">
                    <span class="block">Good Food,</span>
                    <span class="block text-amber-400 drop-shadow-lg">Great Moments</span>
                  </h1>

                  <!-- Description -->
                  <p class="hidden sm:block text-xs sm:text-sm text-gray-200 font-light mt-1 sm:mt-2 max-w-md leading-relaxed">
                    Indulge in our meticulously curated menu, crafted by award-winning chefs.
                  </p>

                  <!-- Buttons -->
                  <div class="flex flex-wrap gap-2 sm:gap-3 mt-2 sm:mt-3">
                    <button
                      @click="handleExplore"
                      class="inline-flex items-center gap-1.5 sm:gap-2 px-4 sm:px-6 py-1.5 sm:py-2.5 bg-gradient-to-r from-amber-400 to-amber-600 hover:from-amber-500 hover:to-amber-700 text-gray-900 font-bold rounded-full shadow-lg hover:shadow-amber-500/50 transition-all duration-300 text-xs sm:text-sm"
                    >
                      <span>Explore Menu</span>
                      <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                      </svg>
                    </button>
                    <button
                      @click="handleViewSpecials"
                      class="inline-flex items-center gap-1.5 sm:gap-2 px-3 sm:px-5 py-1.5 sm:py-2.5 border-2 border-amber-400 text-amber-300 font-semibold rounded-full hover:bg-white/10 transition-all duration-300 text-xs sm:text-sm"
                    >
                      <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"></path>
                      </svg>
                      <span class="hidden xs:inline">View Specials</span>
                      <span class="xs:hidden">Specials</span>
                    </button>
                  </div>
                </div>
              </div>
            </div>

            <!-- Bottom accent -->
            <div class="absolute bottom-0 left-0 right-0 h-0.5 bg-gradient-to-r from-amber-400 via-amber-500 to-transparent"></div>
          </div>

          <!-- Search -->
          <div class="mt-4 sm:mt-6">
            <MenuSearch
              :menu-items="allMenuItems"
              @search="handleSearchQueryChanged"
              @suggestion-selected="handleSuggestionSelected"
            />
          </div>

          <!-- Quick Category Pills (Mobile) -->
          <!-- Hidden for now - categories shown in mobile sidebar instead -->
          <!-- Will be re-enabled if needed for mobile view -->

          <!-- Filter Bar -->
          <!-- <div class="mt-3 sm:mt-4"> -->
            <!-- <MenuFilter
              :sort-options="sortOptions"
              :initial-view-mode="viewMode"
              @sort-changed="handleSortChanged"
              @view-mode-changed="handleViewModeChanged"
              @filters-applied="handleFiltersApplied"
            />
          </div> -->

          <!-- Section Header -->
          <div class="mt-6 sm:mt-8 mb-3 sm:mb-4 flex flex-wrap items-center justify-between gap-2">
            <div>
              <h2 class="text-lg sm:text-2xl md:text-3xl font-bold text-slate-900">
                Recommended for You
              </h2>
              <p class="text-xs sm:text-sm text-slate-500 mt-0.5 sm:mt-1">
                Fresh ingredients, expertly prepared
              </p>
            </div>
            <button 
              v-if="filteredMenuItems.length > itemsPerPage"
              class="text-sm sm:text-base font-semibold text-amber-600 hover:text-amber-700 transition-colors"
            >
              View All →
            </button>
          </div>

          <!-- Menu Grid -->
          <div id="menu-grid">
            <MenuGrid
              :items="filteredMenuItems"
              :view-mode="viewMode"
              :is-loading="isLoadingMenu"
              :items-per-page="itemsPerPage"
              @add-to-cart="handleAddToCart"
              @toggle-favorite="handleToggleFavorite"
            />
          </div>

          <!-- Features -->
          <div class="mt-8 sm:mt-12 grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 rounded-2xl sm:rounded-3xl bg-white p-4 sm:p-6 md:p-8 shadow-sm mb-6">
            
            <div class="flex items-center gap-2 sm:gap-3">
              <div class="text-2xl sm:text-3xl">🍽️</div>
              <div>
                <h4 class="font-bold text-xs sm:text-sm">Freshly Prepared</h4>
                <p class="hidden sm:block text-xs text-slate-500">Premium ingredients</p>
              </div>
            </div>

            <div class="flex items-center gap-2 sm:gap-3">
              <div class="text-2xl sm:text-3xl">🚚</div>
              <div>
                <h4 class="font-bold text-xs sm:text-sm">Fast Delivery</h4>
                <p class="hidden sm:block text-xs text-slate-500">Within 30 minutes</p>
              </div>
            </div>

            <div class="flex items-center gap-2 sm:gap-3">
              <div class="text-2xl sm:text-3xl">🛡️</div>
              <div>
                <h4 class="font-bold text-xs sm:text-sm">Safe & Hygienic</h4>
                <p class="hidden sm:block text-xs text-slate-500">Highest standards</p>
              </div>
            </div>

            <div class="flex items-center gap-2 sm:gap-3">
              <div class="text-2xl sm:text-3xl">⏰</div>
              <div>
                <h4 class="font-bold text-xs sm:text-sm">24/7 Service</h4>
                <p class="hidden sm:block text-xs text-slate-500">Always here to serve</p>
              </div>
            </div>

          </div>

        </main>

      </div>

    <!-- Floating Cart -->
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
        <div class="flex flex-col sm:flex-row items-center justify-between gap-3 sm:gap-4 rounded-2xl bg-neutral-900 px-4 sm:px-6 md:px-8 py-3 sm:py-4 md:py-5 shadow-2xl">
          
          <div class="flex items-center gap-3 sm:gap-4 md:gap-5 text-white w-full sm:w-auto justify-between sm:justify-start">
            <svg class="w-6 h-6 sm:w-7 sm:h-7" fill="white" viewBox="0 0 24 24">
              <path d="M7 18c-1.1 0-1.99.9-1.99 2S5.9 22 7 22s2-.9 2-2-. 9-2-2zm10 0c-1.1 0-1.99.9-1.99 2s.89 2 1.99 2 2-.9 2-2-.9-2-2-2zM7.17 14.75l.03-.12.9-1.63h7.45c.75 0 1.41-.41 1.75-1.03l3.86-7.01H5.21l-.94-2H1v2h2l3.6 7.59-1.35 2.45c-.16.28-.25.61-.25.96 0 1.1.9 2 2 2h12v-2H7.42c-.14 0-.25-.11-.25-.25z" />
            </svg>
            <div>
              <p class="text-white/70 text-xs sm:text-sm">{{ cartItems.length }} Items in Cart</p>
              <h3 class="text-lg sm:text-xl md:text-2xl font-bold">{{ formatPrice(cartTotal) }}</h3>
            </div>
          </div>

          <button
            @click="handleViewCart"
            class="w-full sm:w-auto rounded-xl bg-amber-500 px-4 sm:px-6 md:px-10 py-2 sm:py-3 md:py-4 text-sm sm:text-base md:text-lg font-semibold text-white transition hover:bg-amber-600"
          >
            View Cart & Checkout →
          </button>

        </div>
      </div>
    </Transition>

    <!-- Loading Overlay -->
    <div v-if="isLoadingMenu" class="fixed inset-0 bg-white/80 backdrop-blur-sm z-40 flex items-center justify-center">
      <div class="flex flex-col items-center gap-4">
        <div class="w-12 h-12 border-4 border-amber-500 border-t-transparent rounded-full animate-spin"></div>
        <p class="text-amber-700 font-semibold">Loading menu...</p>
      </div>
    </div>

  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import GuestNavbar from './GuestNavbar.vue'
import CategorySidebar from './CategorySidebar.vue'
import MenuSearch from './MenuSearch.vue'
// import MenuFilter from './MenuFilter.vue'
import MenuGrid from './MenuGrid.vue'
import api from '@/api/auth'

interface Category {
  id: string | null
  name: string
  icon: string
  count?: number
}

interface MenuItem {
  id: string | number
  name: string
  description: string
  price: number
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
  itemsPerPage: 12
})

const emit = defineEmits<{
  'room-selected': [room: string | number]
  'logout': []
  'add-to-cart': [item: MenuItem, quantity: number]
  'view-cart': [items: CartItem[]]
}>()

// State
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

// Categories - Fetch from backend
const categories = ref<Category[]>([])
const loadingCategories = ref(false)

/**
 * Load categories from backend API
 */
const loadCategories = async () => {
  loadingCategories.value = true
  try {
    // Debug logging
    console.log('[QRMenuLayout] Fetching categories from API...')
    
    const response = await api.get('/api/categories?is_active=true')
    
    console.log('[QRMenuLayout] API Response:', response)
    
    if (response.data?.data && Array.isArray(response.data.data)) {
      // Map backend categories to frontend format
      const backendCategories = response.data.data.map((cat: any) => ({
        id: cat.slug || cat.id, // Use slug as ID for filtering
        name: cat.name,
        icon: cat.icon || '🍽️',
        count: cat.menu_items_count || 0 // Menu items count from backend
      }))
      
      // Add "All Categories" option at the beginning
      categories.value = [
        { id: null, name: 'All Categories', icon: '📋', count: 0 },
        ...backendCategories
      ]
      
      console.log(`[QRMenuLayout] ✅ Loaded ${backendCategories.length} categories from backend:`, categories.value)
    } else {
      console.warn('[QRMenuLayout] ⚠️ No categories in response, using fallback defaults')
      // Use fallback categories if no data
      categories.value = [
        { id: null, name: 'All Categories', icon: '📋', count: 0 },
        { id: 'breakfast', name: 'Breakfast', icon: '☀️', count: 0 },
        { id: 'lunch', name: 'Lunch', icon: '🍔', count: 0 },
        { id: 'dinner', name: 'Dinner', icon: '🍲', count: 0 },
        { id: 'appetizers', name: 'Appetizers', icon: '🥗', count: 0 },
        { id: 'pizza', name: 'Pizza', icon: '🍕', count: 0 },
        { id: 'pasta', name: 'Pasta', icon: '🍝', count: 0 },
        { id: 'desserts', name: 'Desserts', icon: '🍰', count: 0 },
        { id: 'drinks', name: 'Beverages', icon: '🍷', count: 0 }
      ]
    }
  } catch (error: any) {
    console.error('[QRMenuLayout] ❌ Error loading categories:', error.message, error.response?.data)
    
    // Use fallback categories on error
    console.log('[QRMenuLayout] Using fallback categories due to API error')
    categories.value = [
      { id: null, name: 'All Categories', icon: '📋', count: 0 },
      { id: 'breakfast', name: 'Breakfast', icon: '☀️', count: 0 },
      { id: 'lunch', name: 'Lunch', icon: '🍔', count: 0 },
      { id: 'dinner', name: 'Dinner', icon: '🍲', count: 0 },
      { id: 'appetizers', name: 'Appetizers', icon: '🥗', count: 0 },
      { id: 'pizza', name: 'Pizza', icon: '🍕', count: 0 },
      { id: 'pasta', name: 'Pasta', icon: '🍝', count: 0 },
      { id: 'desserts', name: 'Desserts', icon: '🍰', count: 0 },
      { id: 'drinks', name: 'Beverages', icon: '🍷', count: 0 }
    ]
  } finally {
    loadingCategories.value = false
  }
}

const sortOptions = [
  { value: 'popular', label: 'Most Popular' },
  { value: 'price-low', label: 'Price: Low to High' },
  { value: 'price-high', label: 'Price: High to Low' },
  { value: 'rating', label: 'Highest Rated' },
  { value: 'newest', label: 'Newest' }
]

// Load menu items from API - DEFINED BEFORE BEING USED
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
            const price = parseFloat(item.price)
            return {
              id: item.id,
              name: item.name || 'Unnamed Item',
              description: item.description || '',
              price: isNaN(price) ? 0 : price,
              image: item.image || '/images/placeholder.png',
              category: categoryName || 'Other',
              rating: item.rating || 4.5,
              is_available: item.is_available !== false
            }
          })
        })
      } else if (Array.isArray(data)) {
        allMenuItems.value = data.map((item: any) => {
          const price = parseFloat(item.price)
          return {
            id: item.id,
            name: item.name || 'Unnamed Item',
            description: item.description || '',
            price: isNaN(price) ? 0 : price,
            image: item.image || '/images/placeholder.png',
            category: item.category || 'Other',
            rating: item.rating || 4.5,
            is_available: item.is_available !== false
          }
        })
      }
    } else if (response.data && Array.isArray(response.data)) {
      allMenuItems.value = response.data.map((item: any) => {
        const price = parseFloat(item.price)
        return {
          id: item.id,
          name: item.name || 'Unnamed Item',
          description: item.description || '',
          price: isNaN(price) ? 0 : price,
          image: item.image || '/images/placeholder.png',
          category: item.category || 'Other',
          rating: item.rating || 4.5,
          is_available: item.is_available !== false
        }
      })
    } else {
      errorMessage.value = 'Unexpected data format from server'
    }
  } catch (error: any) {
    console.error('Error loading menu items:', error)
    errorMessage.value = 'Failed to load menu items. Please try again.'
  } finally {
    isLoadingMenu.value = false
  }
}

// Update category counts - DEFINED BEFORE BEING USED
const updateCategoryCounts = () => {
  categories.value.forEach(cat => {
    if (cat.id) {
      const count = allMenuItems.value.filter(
        item => item.category.toLowerCase() === cat.id?.toLowerCase()
      ).length
      cat.count = count
    } else {
      cat.count = allMenuItems.value.length
    }
  })
}

// Filtered and sorted menu items
const filteredMenuItems = computed(() => {
  let items = [...allMenuItems.value]

  items = items.filter(item => item.is_available !== false)

  if (selectedCategory.value) {
    items = items.filter(
      item => item.category.toLowerCase() === selectedCategory.value?.toLowerCase()
    )
  }

  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    items = items.filter(item =>
      item.name.toLowerCase().includes(query) ||
      item.description.toLowerCase().includes(query)
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

// Cart total
const cartTotal = computed(() => {
  return cartItems.value.reduce((total, item) => total + item.price * item.quantity, 0)
})

// Event handlers
const handleSearch = () => {}

const handleRoomSelected = (room: string | number) => {
  emit('room-selected', room)
}

const handleLogout = () => {
  emit('logout')
}

const handleCategorySelected = (categoryId: string | null) => {
  selectedCategory.value = categoryId
}

const handleCategorySelectedMobile = (categoryId: string | null) => {
  selectedCategory.value = categoryId
  sidebarOpen.value = false // Close sidebar after selection
}

const handleSearchQueryChanged = (query: string) => {
  searchQuery.value = query
}

const handleSuggestionSelected = (suggestion: string) => {
  searchQuery.value = suggestion
}

const handleSortChanged = (value: string) => {
  selectedSort.value = value
}

const handleViewModeChanged = (mode: 'grid' | 'list') => {
  viewMode.value = mode
}

const handleFiltersApplied = () => {}

const handleAddToCart = (item: MenuItem, quantity: number) => {
  const existingItem = cartItems.value.find(ci => ci.id === item.id)
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

const handleExplore = () => {
  const menuSection = document.getElementById('menu-grid')
  if (menuSection) {
    menuSection.scrollIntoView({ behavior: 'smooth', block: 'start' })
  }
}

const handleViewSpecials = () => {
  console.log('View specials clicked')
}

const formatPrice = (price: number): string => {
  return `$${price.toFixed(2)}`
}

// Watch for menu items change - AFTER functions are defined
watch(allMenuItems, updateCategoryCounts, { immediate: true, deep: true })

// Lifecycle - Load menu items and categories on mount
onMounted(() => {
  loadCategories()
  loadMenuItems()
})

// Expose methods for parent - AFTER functions are defined
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
  handleViewCart
})
</script>

<style scoped>
/* Hide scrollbar for category pills */
.hide-scrollbar::-webkit-scrollbar {
  display: none;
}

.hide-scrollbar {
  -ms-overflow-style: none;
  scrollbar-width: none;
}

/* Loading spinner animation */
@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}
.animate-spin {
  animation: spin 0.8s linear infinite;
}
</style>