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

const routeQrToken = route.params.qrToken ? String(route.params.qrToken) : ''
const qrToken = ref(routeQrToken || localStorage.getItem('qr_token') || '')
const roomNumber = ref(localStorage.getItem('room_number') || '')
const guestName = ref(localStorage.getItem('guest_name') || 'Guest')

if (qrToken.value && !routeQrToken) {
  localStorage.setItem('qr_token', qrToken.value)
}

const menuItems = ref<MenuItem[]>([])
const loading = ref(false)
const error = ref('')
const categories = ref<string[]>([])

const searchQuery = ref('')
const selectedCategory = ref('all')

const cartItems = ref<any[]>([])
const cartDrawer = ref(false)

const placingOrder = ref(false)
const orderSuccess = ref(false)
const createdOrder = ref<any>(null)
const orderStatus = ref('pending')
const orderError = ref('')

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
      orderStatus.value = response.data.data.status || 'pending'
      orderSuccess.value = true

      cartItems.value = []
      cartDrawer.value = false
    } else {
      throw new Error(response.data.message || 'Failed to create order')
    }
  } catch (err: any) {
    console.error('[GuestMenuPage] Failed to submit order:', err)
    const message = err.response?.data?.message || err.message || 'Failed to submit order'
    orderError.value = message
  } finally {
    placingOrder.value = false
  }
}

async function loadMenu() {
  loading.value = true
  error.value = ''

  try {
    if (!qrToken.value) {
      throw new Error('QR token not set. Please set it in localStorage first.')
    }

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
              price: item.total_price != null ? parseFloat(item.total_price) : parseFloat(item.price),
              total_price: item.total_price != null ? parseFloat(item.total_price) : parseFloat(item.price),
              base_price: item.base_price != null ? parseFloat(item.base_price) : parseFloat(item.price),
              tax_amount: item.tax_amount != null ? parseFloat(item.tax_amount) : 0,
              tax_rate: item.tax_rate,
              tax_included: item.tax_included,
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
    } else {
      throw new Error('Invalid menu response format')
    }
  } catch (err: any) {
    console.error('[GuestMenuPage] Failed to load menu:', err)
    const apiMessage = err.response?.data?.message
    const apiError = err.response?.data?.error
    const userMessage = apiMessage || apiError || err.message || 'Failed to load menu'

    error.value = userMessage
  } finally {
    loading.value = false
  }
}

function changeCategory(category: string) {
  selectedCategory.value = category
}

onMounted(() => {
  if (!qrToken.value) {
    error.value = 'Invalid access: QR token not found. Please scan a valid QR code.'
  } else {
    loadMenu()
  }
})
</script>

<template>
  <div class="min-h-screen bg-gray-50">
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

    <div v-if="loading" class="flex items-center justify-center min-h-screen">
      <div class="text-center">
        <div class="relative w-12 h-12 mx-auto mb-3">
          <svg class="absolute inset-0 w-full h-full" viewBox="0 0 100 100">
            <circle cx="50" cy="50" r="40" fill="none" stroke="#0EA5E9" stroke-width="5" opacity="0.3" />
          </svg>
          
          <div class="absolute inset-0 animate-spin" style="animation: spin 1.5s linear infinite;">
            <svg viewBox="0 0 100 100" class="w-full h-full">
              <circle cx="50" cy="50" r="40" fill="none" stroke="#FBBF24" stroke-width="6" stroke-linecap="round" stroke-dasharray="60 240" />
            </svg>
          </div>
        </div>
        <p class="text-gray-600">Loading menu...</p>
      </div>
    </div>

    <div v-else>
      <HotelHero />
      <WelcomeBanner />
      <QRInfoCard :room-number="roomNumber" :qr-token="qrToken" />
      <SearchBar v-model="searchQuery" />
      <CategorySlider
        :active="selectedCategory"
        :categories="categories"
        @change="changeCategory"
      />
      <FeaturedMenu :items="menuItems.slice(0, 3)" @add="addToCart" />
      <PopularItems :items="menuItems" @add="addToCart" />
      <MenuGrid :items="filteredMenu" :loading="loading" @add="addToCart" />
      <FloatingCart :count="cartCount" @open="openCart" />
      <CartDrawer
        v-model="cartDrawer"
        :items="cartItems"
        :submitting="placingOrder"
        :error="orderError"
        @remove="removeFromCart"
        @update="updateQuantity"
        @checkout="submitOrder"
      />
      <OrderSummary v-if="cartItems.length" :subtotal="cartTotal" />
      <OrderStatusTimeline v-if="createdOrder" :status="orderStatus" />
      <OrderSuccessDialog
        v-model="orderSuccess"
        :order-id="createdOrder?.id || ''"
        :room-number="roomNumber"
        :estimated-minutes="20"
        @track-order="openCart"
      />
      <GuestFooter />
    </div>
  </div>
</template>

<style scoped>
@keyframes spin {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}
</style>
