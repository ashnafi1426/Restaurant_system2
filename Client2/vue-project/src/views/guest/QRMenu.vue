<template>
  <div class="qr-menu-page">
    <!-- Main Layout -->
    <QRMenuLayout
      ref="menuLayoutRef"
      :guest-name="guestName"
      :guest-email="guestEmail"
      :guest-avatar="guestAvatar"
      :room-number="roomNumber"
      :qr-token="qrToken"
      :hero-image="heroImage"
      :hero-heading="heroHeading"
      :hero-subheading="heroSubheading"
      @room-selected="handleRoomSelected"
      @logout="handleLogout"
      @add-to-cart="handleAddToCart"
      @view-cart="handleViewCart"
    />

    <!-- Cart Modal/Drawer -->
    <Teleport to="body">
      <Transition name="fade">
        <div
          v-if="showCartModal"
          class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4"
          @click.self="closeCartModal"
        >
          <div class="bg-white rounded-xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <!-- Cart Modal Header -->
            <div class="sticky top-0 bg-gradient-to-r from-amber-500 to-amber-600 text-white px-6 py-4 flex items-center justify-between border-b border-amber-600 z-10">
              <h2 class="text-2xl font-bold flex items-center gap-2">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path>
                </svg>
                Your Cart
              </h2>
              <button
                @click="closeCartModal"
                class="text-white hover:bg-white/20 p-2 rounded-lg transition-colors"
              >
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
              </button>
            </div>

            <!-- Cart Items -->
            <div class="divide-y divide-gray-200">
              <div
                v-if="cartItems.length === 0"
                class="px-6 py-12 text-center"
              >
                <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                </svg>
                <p class="text-gray-500 text-lg font-medium">Your cart is empty</p>
                <p class="text-gray-400 text-sm mt-1">Add items from the menu to get started</p>
                <button
                  @click="closeCartModal"
                  class="mt-4 inline-flex items-center gap-2 bg-amber-100 text-amber-700 px-6 py-2 rounded-lg font-medium hover:bg-amber-200 transition-colors"
                >
                  Continue Shopping
                </button>
              </div>

              <!-- Items List -->
              <div
                v-else
                v-for="item in cartItems"
                :key="item.id"
                class="px-6 py-4 flex gap-4 hover:bg-gray-50 transition-colors"
              >
                <img
                  :src="item.image || '/images/placeholder.png'"
                  :alt="item.name"
                  class="w-20 h-20 rounded-lg object-cover flex-shrink-0"
                />

                <div class="flex-1 min-w-0">
                  <h3 class="font-semibold text-gray-800">{{ item.name }}</h3>
                  <p class="text-sm text-gray-600 mt-1 line-clamp-2">{{ item.description }}</p>
                  <div class="flex items-center justify-between mt-2">
                    <span class="text-amber-600 font-bold">{{ formatPrice(item.price) }}</span>
                    <div class="flex items-center gap-2 bg-gray-100 rounded-lg p-1">
                      <button
                        @click="decrementQuantity(item.id)"
                        class="w-6 h-6 flex items-center justify-center text-gray-600 hover:text-gray-800 transition-colors rounded"
                      >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path>
                        </svg>
                      </button>
                      <span class="w-8 text-center font-semibold text-gray-800">{{ item.quantity }}</span>
                      <button
                        @click="incrementQuantity(item.id)"
                        class="w-6 h-6 flex items-center justify-center text-gray-600 hover:text-gray-800 transition-colors rounded"
                      >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                      </button>
                    </div>
                  </div>
                </div>

                <button
                  @click="removeFromCart(item.id)"
                  class="text-red-500 hover:text-red-700 transition-colors p-2 flex-shrink-0"
                  title="Remove from cart"
                >
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                  </svg>
                </button>
              </div>
            </div>

            <!-- Cart Summary -->
            <div class="sticky bottom-0 bg-gray-50 border-t border-gray-200 p-6 space-y-3">
              <div class="flex items-center justify-between text-gray-700">
                <span>Subtotal:</span>
                <span class="font-semibold">{{ formatPrice(subtotal) }}</span>
              </div>

              <div class="flex items-center justify-between text-gray-700">
                <span>Tax (15%):</span>
                <span class="font-semibold">{{ formatPrice(tax) }}</span>
              </div>

              <div class="flex items-center justify-between text-gray-700">
                <span>Service Charge (10%):</span>
                <span class="font-semibold">{{ formatPrice(serviceCharge) }}</span>
              </div>

              <div class="pt-3 border-t-2 border-gray-300 flex items-center justify-between bg-gradient-to-r from-amber-50 to-transparent p-3 rounded-lg">
                <span class="text-lg font-bold text-gray-800">Total:</span>
                <span class="text-2xl font-bold text-amber-600">{{ formatPrice(cartTotal) }}</span>
              </div>

              <div class="flex gap-3 pt-4">
                <button
                  @click="closeCartModal"
                  class="flex-1 px-4 py-3 border-2 border-gray-300 rounded-lg font-semibold text-gray-700 hover:bg-gray-50 transition-colors"
                >
                  Continue Shopping
                </button>
                <button
                  @click="handlePlaceOrder"
                  :disabled="isPlacingOrder || cartItems.length === 0"
                  class="flex-1 px-4 py-3 bg-gradient-to-r from-amber-500 to-amber-600 text-white rounded-lg font-semibold hover:shadow-lg transition-shadow disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
                >
                  <svg v-if="!isPlacingOrder" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                  </svg>
                  <svg v-else class="w-5 h-5 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 2v20m0-20a9.978 9.978 0 00-9 18m18 0a9.978 9.978 0 00-9-18"></path>
                  </svg>
                  {{ isPlacingOrder ? 'Placing Order...' : 'Place Order' }}
                </button>
              </div>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

    <!-- Order Success Modal -->
    <Teleport to="body">
      <Transition name="fade">
        <div
          v-if="showSuccessModal"
          class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4"
          @click.self="showSuccessModal = false"
        >
          <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full p-8 text-center">
            <div class="mb-4 flex justify-center">
              <div class="w-20 h-20 bg-gradient-to-br from-green-400 to-green-600 rounded-full flex items-center justify-center animate-bounce">
                <svg class="w-10 h-10 text-white" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17z"></path>
                </svg>
              </div>
            </div>

            <h2 class="text-2xl font-bold text-gray-800 mb-2">Order Placed Successfully!</h2>
            <p class="text-gray-600 mb-4">Your delicious meal is being prepared and will be delivered to your room shortly.</p>

            <div class="bg-amber-50 rounded-lg p-4 mb-6 text-left space-y-2">
              <div class="flex justify-between">
                <span class="text-gray-600">Order Number:</span>
                <span class="font-bold text-gray-800">#{{ orderNumber }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-gray-600">Room Number:</span>
                <span class="font-bold text-gray-800">{{ roomNumber }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-gray-600">Estimated Time:</span>
                <span class="font-bold text-gray-800">{{ estimatedTime }} mins</span>
              </div>
              <div class="flex justify-between">
                <span class="text-gray-600">Total Amount:</span>
                <span class="font-bold text-amber-600">{{ formatPrice(cartTotal) }}</span>
              </div>
            </div>

            <div class="space-y-2">
              <button
                @click="handleTrackOrder"
                class="w-full px-4 py-3 bg-gradient-to-r from-amber-500 to-amber-600 text-white rounded-lg font-semibold hover:shadow-lg transition-shadow"
              >
                Track Order
              </button>
              <button
                @click="handleBackToMenu"
                class="w-full px-4 py-3 border-2 border-gray-300 text-gray-700 rounded-lg font-semibold hover:bg-gray-50 transition-colors"
              >
                Back to Menu
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '@/api/auth'
import QRMenuLayout from '@/components/guest/qr-menu/QRMenuLayout.vue'

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

// Router
const route = useRoute()
const router = useRouter()
const menuLayoutRef = ref<InstanceType<typeof QRMenuLayout> | null>(null)

// State
const qrToken = ref('')
const roomNumber = ref('101')
const guestName = ref('Guest User')
const guestEmail = ref('guest@royalhorizon.com')
const guestAvatar = ref('/images/avatar.png')
const heroImage = ref('/images/gallery/fine-dining.jpg')
const heroHeading = ref('Good Food, Great Moments')
const heroSubheading = ref('LUXURY DINING')
const cartItems = ref<CartItem[]>([])
const showCartModal = ref(false)
const showSuccessModal = ref(false)
const isPlacingOrder = ref(false)
const orderNumber = ref('')
const estimatedTime = ref(30)

// Computed
const subtotal = computed(() => {
  return cartItems.value.reduce((total, item) => total + item.price * item.quantity, 0)
})

const tax = computed(() => {
  return subtotal.value * 0.15
})

const serviceCharge = computed(() => {
  return subtotal.value * 0.10
})

const cartTotal = computed(() => {
  return subtotal.value + tax.value + serviceCharge.value
})

// Methods
const handleRoomSelected = (room: string | number) => {
  roomNumber.value = String(room)
  localStorage.setItem('roomNumber', String(room))
}

const handleLogout = () => {
  localStorage.removeItem('token')
  localStorage.removeItem('user')
  localStorage.removeItem('qrToken')
  localStorage.removeItem('roomNumber')
  localStorage.removeItem('guestInfo')
  router.push('/login')
}

const handleAddToCart = (item: MenuItem, quantity: number) => {
  const existingItem = cartItems.value.find(ci => ci.id === item.id)
  if (existingItem) {
    existingItem.quantity += quantity
  } else {
    cartItems.value.push({ ...item, quantity })
  }
}

const handleViewCart = () => {
  showCartModal.value = true
}

const closeCartModal = () => {
  showCartModal.value = false
}

const removeFromCart = (itemId: string | number) => {
  cartItems.value = cartItems.value.filter(item => item.id !== itemId)
}

const incrementQuantity = (itemId: string | number) => {
  const item = cartItems.value.find(i => i.id === itemId)
  if (item) {
    item.quantity++
  }
}

const decrementQuantity = (itemId: string | number) => {
  const item = cartItems.value.find(i => i.id === itemId)
  if (item && item.quantity > 1) {
    item.quantity--
  } else {
    removeFromCart(itemId)
  }
}

const formatPrice = (price: number): string => {
  return `$${price.toFixed(2)}`
}

const handlePlaceOrder = async () => {
  if (cartItems.value.length === 0) return

  isPlacingOrder.value = true
  try {
    const orderData = {
      qr_token: qrToken.value,
      items: cartItems.value.map(item => ({
        menu_item_id: item.id,
        quantity: item.quantity
      })),
      special_requests: ''
    }

    const response = await api.post('/guest/orders', orderData)

    if (response.data?.data) {
      const orderData = response.data.data
      orderNumber.value = orderData.order_number || orderData.id || 'N/A'
      estimatedTime.value = orderData.estimated_time || 30
      showSuccessModal.value = true
      showCartModal.value = false
      cartItems.value = []
    } else {
      showSuccessModal.value = true
      showCartModal.value = false
      cartItems.value = []
    }
  } catch (error: any) {
    console.error('Error placing order:', error)
    console.error('Full error response:', error.response?.data)
    if (error.response?.data?.messages) {
      console.error('Validation errors:', error.response.data.messages)
      alert('Validation error: ' + JSON.stringify(error.response.data.messages))
    } else if (error.response?.data?.message) {
      alert('Error: ' + error.response.data.message)
    } else {
      alert('Failed to place order. Please try again.')
    }
  } finally {
    isPlacingOrder.value = false
  }
}

const handleTrackOrder = () => {
  console.log('Tracking order:', orderNumber.value)
  showSuccessModal.value = false
}

const handleBackToMenu = () => {
  showSuccessModal.value = false
}

// Lifecycle
onMounted(() => {
  if (route.params.qrToken) {
    qrToken.value = String(route.params.qrToken)
  }
  if (route.query.token) {
    qrToken.value = String(route.query.token)
  }
  if (!qrToken.value) {
    qrToken.value = localStorage.getItem('qrToken') || ''
  }

  roomNumber.value = localStorage.getItem('roomNumber') || '101'

  const guestInfo = localStorage.getItem('guestInfo')
  if (guestInfo) {
    try {
      const info = JSON.parse(guestInfo)
      guestName.value = info.name || guestName.value
      guestEmail.value = info.email || guestEmail.value
      guestAvatar.value = info.avatar || guestAvatar.value
    } catch (e) {
      console.error('Failed to parse guest info:', e)
    }
  }
})
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

.animate-spin {
  animation: spin 1s linear infinite;
}

@keyframes bounce {
  0%, 100% {
    transform: translateY(0);
  }
  50% {
    transform: translateY(-10px);
  }
}

.animate-bounce {
  animation: bounce 1s ease-in-out infinite;
}

/* Line clamp for description */
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>