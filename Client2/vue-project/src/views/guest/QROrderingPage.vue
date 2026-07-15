<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import SearchBar from '@/components/guest/SearchBar.vue'
import FloatingCart from '@/components/guest/FloatingCart.vue'
import CartDrawer from '@/components/guest/CartDrawer.vue'
import OrderSuccessDialog from '@/components/guest/OrderSuccessDialog.vue'
import api from '@/api/auth'

// Type definitions
interface MenuItem {
  id: string
  name: string
  description: string
  image?: string | null
  category: string
  price: number
  is_available: boolean
}

interface CartItem extends MenuItem {
  quantity: number
}

// Route and QR Info
const route = useRoute()
const routeQrToken = route.params.qrToken ? String(route.params.qrToken) : ''
const qrToken = ref(routeQrToken || localStorage.getItem('qr_token') || '')
const roomNumber = ref(localStorage.getItem('room_number') || '')
const guestName = ref(localStorage.getItem('guest_name') || 'Guest')

// Save QR token if from URL
if (qrToken.value && routeQrToken) {
  localStorage.setItem('qr_token', qrToken.value)
}

console.log('[QROrderingPage Init]', {
  qrToken: qrToken.value,
  routeQrToken,
  roomNumber: roomNumber.value,
  guestName: guestName.value,
})

// Menu state
const menuItems = ref<MenuItem[]>([])
const loading = ref(false)
const error = ref('')
const categories = ref<string[]>([])

const searchQuery = ref('')
const selectedCategory = ref('all')
const cartItems = ref<CartItem[]>([])
const cartDrawer = ref(false)
const placingOrder = ref(false)
const orderSuccess = ref(false)
const createdOrder = ref<any>(null)
const orderError = ref('')

// Computed properties
const filteredMenu = computed(() => {
  let items = menuItems.value

  // Filter by category
  if (selectedCategory.value && selectedCategory.value !== 'all') {
    items = items.filter((item) =>
      item.category.toLowerCase() === selectedCategory.value.toLowerCase()
    )
  }

  // Filter by search query
  const trimmedSearch = searchQuery.value?.trim() || ''
  if (trimmedSearch) {
    const query = trimmedSearch.toLowerCase()
    items = items.filter((item) => {
      const name = item.name.toLowerCase()
      const description = item.description?.toLowerCase() || ''
      return name.includes(query) || description.includes(query)
    })
  }

  return items
})

const cartCount = computed(() => {
  return cartItems.value.reduce((sum, item) => sum + item.quantity, 0)
})

const cartTotal = computed(() => {
  return cartItems.value.reduce((sum, item) => sum + item.price * item.quantity, 0)
})

const cartTotalWithTax = computed(() => {
  const subtotal = cartTotal.value
  const tax = subtotal * 0.15
  const serviceCharge = subtotal * 0.10
  return subtotal + tax + serviceCharge
})

const featuredItems = computed(() => {
  return menuItems.value.slice(0, 3)
})

// Cart operations
function addToCart(item: MenuItem) {
  const existing = cartItems.value.find((i) => i.id === item.id)

  if (existing) {
    existing.quantity++
  } else {
    cartItems.value.push({
      ...item,
      quantity: 1,
    })
  }
}

function removeFromCart(id: string) {
  cartItems.value = cartItems.value.filter((item) => item.id !== id)
}

function updateQuantity(payload: { id: string; quantity: number }) {
  const product = cartItems.value.find((i) => i.id === payload.id)
  if (product) {
    product.quantity = Math.max(1, payload.quantity)
  }
}

function openCart() {
  cartDrawer.value = true
}

function closeCart() {
  cartDrawer.value = false
}

// Category change - no longer needed, direct button click updates selectedCategory

// Load menu from API
async function loadMenu() {
  loading.value = true
  error.value = ''

  try {
    if (!qrToken.value) {
      throw new Error('⚠️ QR token not set. Please scan a valid QR code.')
    }

    console.log('[Menu Loading]', {
      qrToken: qrToken.value,
      timestamp: new Date().toISOString(),
    })

    const menuResponse = await api.get(`/guest/menu/${qrToken.value}/items`)

    if (menuResponse.data.success && menuResponse.data.data) {
      const allItems: MenuItem[] = []
      const cats = new Set<string>()

      menuResponse.data.data.forEach((categoryGroup: any) => {
        if (categoryGroup.items && Array.isArray(categoryGroup.items)) {
          allItems.push(
            ...categoryGroup.items.map((item: any) => ({
              id: String(item.id),
              name: item.name,
              description: item.description || '',
              image: item.image || null,
              category: categoryGroup.category || 'other',
              price: parseFloat(item.price),
              is_available: true,
            }))
          )
          if (categoryGroup.category) {
            cats.add(categoryGroup.category)
          }
        }
      })

      menuItems.value = allItems
      categories.value = Array.from(cats)

      console.log('[✅ Menu Loaded Successfully]', {
        qr_token: qrToken.value,
        total_items: allItems.length,
        categories: categories.value,
      })
    } else {
      throw new Error('Invalid menu response format')
    }
  } catch (err: any) {
    const apiMessage = err.response?.data?.message
    const apiError = err.response?.data?.error
    const userMessage = apiMessage || apiError || err.message || 'Failed to load menu'

    error.value = userMessage

    console.error('[❌ Menu Load Error]', {
      error: userMessage,
      qr_token: qrToken.value,
      status: err.response?.status,
    })
  } finally {
    loading.value = false
  }
}

// Submit order
async function submitOrder(orderData: any) {
  placingOrder.value = true
  orderError.value = ''

  try {
    if (!qrToken.value) {
      throw new Error('QR token not found. Please scan a valid QR code.')
    }

    if (cartItems.value.length === 0) {
      throw new Error('Please add items to your order.')
    }

    const items = cartItems.value.map((item) => ({
      menu_item_id: item.id,
      quantity: item.quantity,
    }))

    const response = await api.post('/guest/orders', {
      qr_token: qrToken.value,
      items: items,
      special_requests: orderData?.special_requests || null,
    })

    if (response.data.success && response.data.data) {
      createdOrder.value = response.data.data
      orderSuccess.value = true
      cartItems.value = []
      cartDrawer.value = false

      console.log('[Order Success]', {
        order_id: createdOrder.value.id,
        room: createdOrder.value.room_number,
        total: createdOrder.value.total,
      })
    } else {
      throw new Error(response.data.message || 'Failed to create order')
    }
  } catch (err: any) {
    const message = err.response?.data?.message || err.message || 'Failed to submit order'
    orderError.value = message
    console.error('[Order Error]', {
      error: message,
      details: err.response?.data,
    })
  } finally {
    placingOrder.value = false
  }
}

// Lifecycle
onMounted(() => {
  if (!qrToken.value) {
    error.value = 'Invalid access: QR token not found. Please scan a valid QR code.'
    console.warn('[QROrderingPage] No QR token found')
  } else {
    loadMenu()
  }
})
</script>

<template>
  <div class="min-h-screen bg-white flex">
    <aside class="w-32 bg-gradient-to-b from-amber-50 to-white border-r border-gray-200 px-4 py-8 flex flex-col gap-8 sticky top-0 h-screen overflow-y-auto">
      <div class="text-center border-b pb-6">
        <div class="w-16 h-16 mx-auto mb-3 rounded-full bg-amber-100 flex items-center justify-center text-2xl font-bold text-amber-700">
          GR
        </div>
        <p class="text-xs font-semibold text-gray-700">{{ roomNumber || 'Guest' }}</p>
        <p class="text-xs text-gray-500 text-center break-words">{{ guestName }}</p>
      </div>
      <nav class="flex flex-col gap-4 flex-1">
        <button
          @click="selectedCategory = 'all'"
          :class="[
            'flex items-center gap-2 px-3 py-2 rounded-lg text-xs font-medium transition',
            selectedCategory === 'all'
              ? 'bg-amber-100 text-amber-700'
              : 'text-gray-600 hover:bg-gray-100'
          ]"
        >
          <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
            <path d="M3 13h2v8H3zm4-8h2v16H7zm4-2h2v18h-2zm4 4h2v14h-2zm4-4h2v18h-2z" />
          </svg>
          <span>Menu</span>
        </button>
        <button
          class="flex items-center gap-2 px-3 py-2 rounded-lg text-xs font-medium text-gray-600 hover:bg-gray-100 transition"
        >
          <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z" />
          </svg>
          <span>Concierge</span>
        </button>
        <button
          class="flex items-center gap-2 px-3 py-2 rounded-lg text-xs font-medium text-gray-600 hover:bg-gray-100 transition"
        >
          <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
            <path d="M17 3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V7l-4-4zm-5 16c-1.66 0-3-1.34-3-3s1.34-3 3-3 3 1.34 3 3-1.34 3-3 3zm3-10H5V5h10v4z" />
          </svg>
          <span>Wellness</span>
        </button>

        <button
          class="flex items-center gap-2 px-3 py-2 rounded-lg text-xs font-medium text-gray-600 hover:bg-gray-100 transition"
        >
          <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
            <path d="M18.92 6.01C18.72 5.42 18.16 5 17.5 5h-11c-.66 0-1.21.42-1.42 1.01L3 12v8c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-1h12v1c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-8l-2.08-5.99zM6.5 16c-.83 0-1.5-.67-1.5-1.5S5.67 13 6.5 13s1.5.67 1.5 1.5-1.67 1.5-1.5 1.5zm11 0c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zM5 11l1.5-4.5h11L19 11H5z" />
          </svg>
          <span>Valet</span>
        </button>
        <button
          class="flex items-center gap-2 px-3 py-2 rounded-lg text-xs font-medium text-gray-600 hover:bg-gray-100 transition"
        >
          <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
            <path d="M9 11H7v2h2v-2zm8 0h-2v2h2v-2zm4-7h-1V2h-2v2h-4V2h-2v2H8V2H6v2H5c-1.11 0-1.99.9-1.99 2L3 20c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 16H5V9h14v11z" />
          </svg>
          <span>Receipts</span>
        </button>
      </nav>

      <!-- Call Button -->
      <button class="w-full bg-amber-700 text-white px-3 py-2 rounded-lg text-xs font-semibold hover:bg-amber-800 transition">
        Call Room Service
      </button>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="flex-1 overflow-y-auto">
      <!-- ERROR DISPLAY -->
      <div v-if="error" class="bg-red-50 border-l-4 border-red-500 p-4 sticky top-0 z-40">
        <div class="flex items-center gap-3">
          <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M12 9v2m0 4v2m0-12a9 9 0 110 18 9 9 0 010-18z"
            />
          </svg>
          <div>
            <h3 class="font-semibold text-red-800">Error Loading Menu</h3>
            <p class="text-sm text-red-700">{{ error }}</p>
          </div>
        </div>
      </div>
      <!-- LOADING STATE -->
      <div v-if="loading" class="flex items-center justify-center min-h-screen">
        <div class="text-center">
          <div class="animate-spin text-4xl mb-3">⏳</div>
          <p class="text-gray-600">Loading menu...</p>
        </div>
      </div>
      <!-- MENU CONTENT -->
      <div v-else>
        <!-- HERO BANNER -->
        <div class="relative h-48 bg-gradient-to-r from-amber-900 to-amber-700 flex items-center justify-center text-center px-8">
          <div class="absolute inset-0 opacity-20">
            <img src="/images/hero/hero.jpg" class="w-full h-full object-cover" alt="" />
          </div>
          <div class="relative z-10">
            <h1 class="text-4xl font-serif text-white font-bold mb-2">Enjoy Delicious Dining From Your Room</h1>
            <p class="text-amber-50">Order breakfast, lunch and dinner directly from our restaurant</p>
          </div>
        </div>

        <!-- SEARCH & FILTERS -->
        <div class="px-8 py-8 bg-white border-b border-gray-100">
          <!-- Search -->
          <SearchBar v-model="searchQuery" />

          <!-- Categories -->
          <div class="mt-6 flex gap-3 flex-wrap">
            <button
              v-if="selectedCategory !== 'all'"
              @click="selectedCategory = 'all'"
              class="px-4 py-2 rounded-full border-2 border-gray-300 text-gray-700 text-sm font-medium hover:border-amber-700 transition"
            >
              All
            </button>
            <button
              v-for="category in categories"
              :key="category"
              @click="selectedCategory = category"
              :class="[
                'px-4 py-2 rounded-full text-sm font-medium transition',
                selectedCategory === category
                  ? 'bg-amber-700 text-white'
                  : 'border-2 border-gray-300 text-gray-700 hover:border-amber-700'
              ]"
            >
              {{ category }}
            </button>
          </div>
        </div>

        <!-- FEATURED ITEMS -->
        <section v-if="featuredItems.length > 0" class="px-8 py-12 bg-white border-b border-gray-100">
          <h3 class="text-2xl font-serif font-bold text-gray-900 mb-8 flex items-center gap-3">
            <span class="text-3xl">⭐</span> Chef Recommended
          </h3>

          <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <div
              v-for="item in featuredItems"
              :key="item.id"
              class="group cursor-pointer"
              @click="addToCart(item)"
            >
              <!-- Image -->
              <div class="relative h-64 rounded-xl overflow-hidden mb-4 shadow-lg hover:shadow-xl transition">
                <img
                  v-if="item.image"
                  :src="item.image"
                  :alt="item.name"
                  class="w-full h-full object-cover group-hover:scale-110 transition duration-300"
                />
                <div v-else class="w-full h-full bg-gradient-to-br from-gray-200 to-gray-300 flex items-center justify-center">
                  <span class="text-6xl">🍽️</span>
                </div>
                <!-- Price Badge -->
                <div class="absolute top-4 right-4 bg-white/95 backdrop-blur-sm px-3 py-1 rounded-full shadow-lg">
                  <span class="font-bold text-amber-700 text-sm">${{ item.price.toFixed(2) }}</span>
                </div>
              </div>

              <!-- Content -->
              <h4 class="text-base font-semibold text-gray-900 mb-2 group-hover:text-amber-700 transition">
                {{ item.name }}
              </h4>
              <p class="text-xs text-gray-600 mb-3 line-clamp-2">{{ item.description }}</p>

              <!-- Category Badge -->
              <div class="flex items-center justify-between mb-3">
                <span class="text-xs text-amber-700 font-medium">{{ item.category.toUpperCase() }}</span>
              </div>

              <!-- Add Button -->
              <button
                @click.stop="addToCart(item)"
                class="w-full bg-amber-700 text-white px-3 py-2 rounded-lg hover:bg-amber-800 text-xs font-semibold transition flex items-center justify-center gap-2"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Add
              </button>
            </div>
          </div>
        </section>

        <!-- ALL ITEMS SECTION -->
        <section class="px-8 py-12 bg-gray-50">
          <div class="flex items-center justify-between mb-8">
            <h3 class="text-2xl font-serif font-bold text-gray-900">All Day Classics</h3>
            <span
              v-if="searchQuery || selectedCategory !== 'all'"
              class="text-sm text-gray-600 bg-gray-200 px-3 py-1 rounded-full"
            >
              {{ filteredMenu.length }} result{{ filteredMenu.length !== 1 ? 's' : '' }}
            </span>
          </div>

          <!-- Empty State -->
          <div v-if="filteredMenu.length === 0" class="text-center py-16">
            <div class="text-7xl mb-6">🍽️</div>
            <p class="text-gray-600 text-lg mb-3 font-semibold">No items found.</p>
            <p class="text-sm text-gray-500 max-w-md mx-auto">
              <span v-if="searchQuery && selectedCategory !== 'all'">
                Try a different search in {{ selectedCategory }}
              </span>
              <span v-else-if="searchQuery"> Try a different search term </span>
              <span v-else> Try a different category </span>
            </p>
          </div>

          <!-- Menu Grid -->
          <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <div
              v-for="item in filteredMenu"
              :key="item.id"
              class="bg-white rounded-xl overflow-hidden hover:shadow-lg transition group border border-gray-200 hover:border-amber-300"
            >
              <!-- Image -->
              <div class="h-48 bg-gray-300 overflow-hidden relative">
                <img
                  v-if="item.image"
                  :src="item.image"
                  :alt="item.name"
                  class="w-full h-full object-cover group-hover:scale-110 transition duration-300"
                />
                <div v-else class="w-full h-full flex items-center justify-center bg-gradient-to-br from-gray-300 to-gray-400">
                  <span class="text-4xl">🍽️</span>
                </div>
              </div>

              <!-- Content -->
              <div class="p-4">
                <p class="text-xs text-amber-700 font-semibold mb-1">{{ item.category.toUpperCase() }}</p>
                <h4 class="font-semibold text-gray-900 text-sm mb-2 line-clamp-1">{{ item.name }}</h4>
                <p class="text-xs text-gray-600 mb-3 line-clamp-2">{{ item.description }}</p>

                <!-- Footer with Price and Action -->
                <div class="flex items-center justify-between pt-3 border-t border-gray-200">
                  <span class="font-bold text-amber-700">${{ item.price.toFixed(2) }}</span>
                  <button
                    @click="addToCart(item)"
                    class="bg-amber-700 text-white px-3 py-2 rounded-lg hover:bg-amber-800 text-xs font-medium transition flex items-center gap-1"
                  >
                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 24 24">
                      <path d="M12 4v16m8-8H4" />
                    </svg>
                    Add
                  </button>
                </div>
              </div>
            </div>
          </div>
        </section>

        <!-- Footer -->
        <footer class="bg-gray-900 text-gray-400 px-8 py-12">
          <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-8">
            <div>
              <h3 class="text-white font-serif font-bold mb-3">The Grand Regent</h3>
              <p class="text-xs">Luxury hospitality at its finest. Experience exceptional service and world-class dining.</p>
            </div>
            <div>
              <p class="text-xs">©2026 The Grand Regent. All rights reserved.</p>
            </div>
            <div>
              <p class="text-xs">Contact | Privacy Policy</p>
            </div>
          </div>
        </footer>
      </div>
    </main>

    <!-- FLOATING CART -->
    <FloatingCart :count="cartCount" @open="openCart" />

    <!-- CART DRAWER -->
    <CartDrawer
      v-model="cartDrawer"
      :items="cartItems"
      :submitting="placingOrder"
      :error="orderError"
      @remove="removeFromCart"
      @update="updateQuantity"
      @checkout="submitOrder"
    />

    <!-- ORDER SUCCESS DIALOG -->
    <OrderSuccessDialog
      v-model="orderSuccess"
      :orderId="createdOrder?.id"
      :roomNumber="roomNumber"
      :estimatedMinutes="20"
      @track-order="openCart"
    />
  </div>
</template>

<style scoped>
/* Add any custom styles here if needed */
</style>
