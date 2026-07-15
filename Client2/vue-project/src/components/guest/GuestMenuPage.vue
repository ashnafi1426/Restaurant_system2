<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import HotelHero from '@/components/guest/HotelHero.vue'
import WelcomeBanner from '@/components/guest/WelcomBanner.vue'
import SearchBar from '@/components/guest/SearchBar.vue'
import CategorySlider from '@/components/guest/CategorySlider.vue'
import FeaturedMenu from '@/components/guest/FeaturedMenu.vue'
import PopularItems from '@/components/guest/PopulaItems.vue'
import MenuGrid from '@/components/guest/MenuGrid.vue'
import FloatingCart from '@/components/guest/FloatingCart.vue'
import CartDrawer from '@/components/guest/CartDrawer.vue'
import OrderSummary from '@/components/guest/OrderSummery.vue'
import OrderStatusTimeline from '@/components/guest/OrderStatusTimeline.vue'
import OrderSuccessDialog from '@/components/guest/OrderSuccessDialog.vue'
import GuestFooter from '@/components/guest/GuestFooter.vue'
import QRInfoCard from '@/components/guest/QRInfoCard.vue'
import api from '@/api/auth'

interface MenuItem {
  id: string
  name: string
  description: string
  image?: string | null
  category: string
  price: number
  is_available: boolean
}

const route = useRoute()

/*
|--------------------------------------------------------------------------
| Guest QR Information
|--------------------------------------------------------------------------
*/
// Try to get QR token from route param first, then localStorage
const routeQrToken = route.params.qrToken ? String(route.params.qrToken) : ''
const qrToken = ref(routeQrToken || localStorage.getItem('qr_token') || '')
const roomNumber = ref(localStorage.getItem('room_number') || '')
const guestName = ref(localStorage.getItem('guest_name') || 'Guest')

// If QR token came from URL param, save it to localStorage
if (qrToken.value && !routeQrToken) {
  localStorage.setItem('qr_token', qrToken.value)
}

// DEBUG: Log current values
console.log('[GuestMenuPage Init]', {
  qrToken: qrToken.value,
  routeQrToken: routeQrToken,
  roomNumber: roomNumber.value,
  guestName: guestName.value,
  localStorage: typeof localStorage !== 'undefined' ? 'available' : 'unavailable'
})

/*
|--------------------------------------------------------------------------
| Menu State
|--------------------------------------------------------------------------
*/
const menuItems = ref<MenuItem[]>([])
const loading = ref(false)
const error = ref('')
const categories = ref<string[]>([])

/*
|--------------------------------------------------------------------------
| Search
|--------------------------------------------------------------------------
*/
const searchQuery = ref('')

/*
|--------------------------------------------------------------------------
| Categories
|--------------------------------------------------------------------------
*/
const selectedCategory = ref('all')

/*
|--------------------------------------------------------------------------
| Cart
|--------------------------------------------------------------------------
*/
const cartItems = ref<any[]>([])
const cartDrawer = ref(false)

/*
|--------------------------------------------------------------------------
| Order
|--------------------------------------------------------------------------
*/
const placingOrder = ref(false)
const orderSuccess = ref(false)
const createdOrder = ref<any>(null)
const orderStatus = ref('pending')
const orderError = ref('')

/*
|--------------------------------------------------------------------------
| Computed Menu Filtering
|--------------------------------------------------------------------------
*/
const filteredMenu = computed(() => {
  let items = menuItems.value

  if (selectedCategory.value !== 'all') {
    items = items.filter((item) => item.category === selectedCategory.value)
  }

  if (searchQuery.value) {
    items = items.filter((item) =>
      item.name.toLowerCase().includes(searchQuery.value.toLowerCase()),
    )
  }

  return items
})

/*
|--------------------------------------------------------------------------
| Cart Functions
|--------------------------------------------------------------------------
*/
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

function updateQuantity(item: any, quantity: number) {
  const product = cartItems.value.find((i) => i.id === item.id)

  if (product) {
    product.quantity = Math.max(1, quantity)
  }
}

function openCart() {
  cartDrawer.value = true
}

function closeCart() {
  cartDrawer.value = false
}

const cartCount = computed(() => {
  return cartItems.value.reduce((sum, item) => sum + item.quantity, 0)
})

const cartTotal = computed(() => {
  return cartItems.value.reduce((sum, item) => sum + item.price * item.quantity, 0)
})

/*
|--------------------------------------------------------------------------
| Order Submit
|--------------------------------------------------------------------------
*/
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

    // Format items for API
    const items = cartItems.value.map((item) => ({
      menu_item_id: item.id,
      quantity: item.quantity,
    }))

    // Submit order to API
    const response = await api.post('/guest/orders', {
      qr_token: qrToken.value,
      items: items,
      special_requests: orderData?.special_requests || null,
    })

    if (response.data.success && response.data.data) {
      createdOrder.value = response.data.data
      orderStatus.value = response.data.data.status || 'pending'
      orderSuccess.value = true

      // Clear cart
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

/*
|--------------------------------------------------------------------------
| Load Menu from API
|--------------------------------------------------------------------------
*/
async function loadMenu() {
  loading.value = true
  error.value = ''

  try {
    if (!qrToken.value) {
      throw new Error('⚠️ QR token not set. Please set it in localStorage first.')
    }

    console.log('[Menu Loading]', {
      qrToken: qrToken.value,
      timestamp: new Date().toISOString(),
    })

    // Use token-based menu endpoint for security
    console.log('[Loading menu with QR token validation]')
    const menuResponse = await api.get(`/guest/menu/${qrToken.value}/items`)

    if (menuResponse.data.success && menuResponse.data.data) {
      // Flatten categorized items into single array
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
            })),
          )
          if (categoryGroup.category) {
            cats.add(categoryGroup.category)
          }
        }
      })

      menuItems.value = allItems
      categories.value = Array.from(cats).sort()

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
      full_response: err.response?.data,
    })
  } finally {
    loading.value = false
  }
}

/*
|--------------------------------------------------------------------------
| Category Change
|--------------------------------------------------------------------------
*/
function changeCategory(category: string) {
  selectedCategory.value = category
}

/*
|--------------------------------------------------------------------------
| Lifecycle
|--------------------------------------------------------------------------
*/
onMounted(() => {
  if (!qrToken.value) {
    error.value = 'Invalid access: QR token not found. Please scan a valid QR code.'
    console.warn('[GuestMenuPage] No QR token found')
  } else {
    loadMenu()
  }
})


</script>



<template>
  <div class="min-h-screen bg-gray-50">
    <!-- ERROR DISPLAY -->
    <div v-if="error" class="bg-red-50 border-l-4 border-red-500 p-4 sticky top-0 z-40">
      <div class="flex items-center gap-3">
        <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M12 9v2m0 4v2m0-12a9 9 0 110 18 9 9 0 010-18z" />
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

    <!-- MAIN CONTENT -->
    <div v-else>
      <!-- HERO -->
      <HotelHero />

      <!-- Welcome -->
      <WelcomeBanner />

      <!-- QR INFORMATION -->
      <QRInfoCard :room-number="roomNumber" :qr-token="qrToken" />

      <!-- SEARCH -->
      <SearchBar v-model="searchQuery" />

      <!-- CATEGORY -->
      <CategorySlider
        :active="selectedCategory"
        :categories="categories"
        @change="changeCategory"
      />

      <!-- FEATURED -->
      <FeaturedMenu :items="menuItems.slice(0, 3)" @add="addToCart" />

      <!-- POPULAR -->
      <PopularItems :items="menuItems" @add="addToCart" />

      <!-- ALL MENU -->
      <MenuGrid
        :items="filteredMenu"
        :loading="loading"
        @add="addToCart"
      />

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

      <!-- ORDER SUMMARY -->
      <OrderSummary v-if="cartItems.length" :subtotal="cartTotal" />

      <!-- ORDER STATUS -->
      <OrderStatusTimeline v-if="createdOrder" :status="orderStatus" />

      <!-- SUCCESS -->
      <OrderSuccessDialog 
        v-model="orderSuccess" 
        :order-id="createdOrder?.id || ''"
        :room-number="roomNumber"
        :estimated-minutes="20"
        @track-order="openCart"
      />

      <!-- FOOTER -->
      <GuestFooter />
    </div>
  </div>
</template>