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
            <div
              class="sticky top-0 bg-gradient-to-r from-amber-500 to-amber-600 text-white px-6 py-4 flex items-center justify-between border-b border-amber-600 z-10"
            >
              <h2 class="text-2xl font-bold flex items-center gap-2">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"
                  ></path>
                </svg>
                Your Cart
              </h2>
              <button
                @click="closeCartModal"
                class="text-white hover:bg-white/20 p-2 rounded-lg transition-colors"
              >
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M6 18L18 6M6 6l12 12"
                  ></path>
                </svg>
              </button>
            </div>

            <!-- Cart Items -->
            <div class="divide-y divide-gray-200">
              <div v-if="cartItems.length === 0" class="px-6 py-12 text-center">
                <svg
                  class="w-16 h-16 text-gray-300 mx-auto mb-4"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="1.5"
                    d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"
                  ></path>
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
                          <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M20 12H4"
                          ></path>
                        </svg>
                      </button>
                      <span class="w-8 text-center font-semibold text-gray-800">{{
                        item.quantity
                      }}</span>
                      <button
                        @click="incrementQuantity(item.id)"
                        class="w-6 h-6 flex items-center justify-center text-gray-600 hover:text-gray-800 transition-colors rounded"
                      >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M12 4v16m8-8H4"
                          ></path>
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
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                    ></path>
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

              <div
                class="pt-3 border-t-2 border-gray-300 flex items-center justify-between bg-gradient-to-r from-amber-50 to-transparent p-3 rounded-lg"
              >
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
                  @click="openPaymentDialog"
                  :disabled="isPlacingOrder || cartItems.length === 0"
                  class="flex-1 px-4 py-3 bg-gradient-to-r from-amber-500 to-amber-600 text-white rounded-lg font-semibold hover:shadow-lg transition-shadow disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
                >
                  <svg
                    v-if="!isPlacingOrder"
                    class="w-5 h-5"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M5 13l4 4L19 7"
                    ></path>
                  </svg>
                  <svg
                    v-else
                    class="w-5 h-5 animate-spin"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M12 2v20m0-20a9.978 9.978 0 00-9 18m18 0a9.978 9.978 0 00-9-18"
                    ></path>
                  </svg>
                  💳 Proceed to Payment
                </button>
              </div>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

    <!-- Payment Confirmation Dialog -->
    <Teleport to="body">
      <Transition name="fade">
        <div
          v-if="showPaymentDialog"
          class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4"
          @click.self="closePaymentDialog"
        >
          <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full max-h-[85vh] flex flex-col">
            <!-- Header -->
            <div
              class="bg-gradient-to-r from-amber-500 to-amber-600 px-5 py-4 text-white flex-shrink-0 rounded-t-2xl"
            >
              <h3 class="text-xl font-bold mb-1">💳 Payment Confirmation</h3>
              <p class="text-amber-100 text-sm">Review your order before payment</p>
            </div>

            <!-- Content - Scrollable -->
            <div class="p-5 space-y-3 overflow-y-auto flex-1">
              <!-- Order Summary -->
              <div>
                <h4 class="font-semibold text-sm mb-2">Order Summary</h4>
                <div class="space-y-2 text-xs">
                  <div class="flex justify-between">
                    <span class="text-slate-600">Room:</span>
                    <span class="font-medium">{{ roomNumber }}</span>
                  </div>
                  <div class="flex justify-between">
                    <span class="text-slate-600">Items:</span>
                    <span class="font-medium">{{ cartItems.length }}</span>
                  </div>
                  <div class="flex justify-between">
                    <span class="text-slate-600">Guest:</span>
                    <span class="font-medium">{{ guestName }}</span>
                  </div>
                </div>
              </div>

              <!-- Cart Items -->
              <div class="border-t pt-3">
                <h4 class="font-semibold text-sm mb-2">Your Items</h4>
                <div class="space-y-2">
                  <div
                    v-for="item in cartItems"
                    :key="item.id"
                    class="flex justify-between text-xs"
                  >
                    <span class="text-slate-700">{{ item.name }} × {{ item.quantity }}</span>
                    <span class="font-medium">{{ formatPrice(item.price * item.quantity) }}</span>
                  </div>
                </div>
              </div>

              <!-- Price Breakdown -->
              <div class="border-t pt-3 space-y-1.5">
                <div class="flex justify-between text-xs">
                  <span class="text-slate-600">Subtotal:</span>
                  <span class="font-medium">{{ formatPrice(subtotal) }}</span>
                </div>
                <div class="flex justify-between text-xs">
                  <span class="text-slate-600">Tax (15%):</span>
                  <span class="font-medium">{{ formatPrice(tax) }}</span>
                </div>
                <div class="flex justify-between text-xs">
                  <span class="text-slate-600">Service (10%):</span>
                  <span class="font-medium">{{ formatPrice(serviceCharge) }}</span>
                </div>
                <div class="flex justify-between text-sm font-bold pt-1.5 border-t">
                  <span>Total:</span>
                  <span class="text-amber-600">{{ formatPrice(cartTotal) }}</span>
                </div>
              </div>

              <!-- Security Notice -->
              <div class="bg-blue-50 border border-blue-200 rounded-lg p-2 text-xs text-blue-700">
                ✓ Secure payment via Chapa gateway
              </div>
            </div>

            <!-- Actions -->
            <div class="bg-slate-50 px-5 py-3 flex gap-2.5 flex-shrink-0 border-t rounded-b-2xl">
              <button
                @click="closePaymentDialog"
                :disabled="isPlacingOrder"
                class="flex-1 px-4 py-2 text-sm font-medium border border-slate-300 rounded-lg hover:bg-slate-100 disabled:opacity-50 transition-colors"
              >
                Cancel
              </button>
              <button
                @click="proceedToPayment"
                :disabled="isPlacingOrder"
                class="flex-1 px-4 py-2 text-sm font-medium bg-amber-600 text-white rounded-lg hover:bg-amber-700 disabled:opacity-50 flex items-center justify-center gap-2 transition-colors"
              >
                <span v-if="isPlacingOrder">⌛ Processing...</span>
                <span v-else>💳 Pay Now</span>
              </button>
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
              <div
                class="w-20 h-20 bg-gradient-to-br from-green-400 to-green-600 rounded-full flex items-center justify-center animate-bounce"
              >
                <svg class="w-10 h-10 text-white" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17z"></path>
                </svg>
              </div>
            </div>

            <h2 class="text-2xl font-bold text-gray-800 mb-2">Order Placed Successfully!</h2>
            <p class="text-gray-600 mb-4">
              Your delicious meal is being prepared and will be delivered to your room shortly.
            </p>

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
const showPaymentDialog = ref(false) // NEW: Payment confirmation dialog
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
  return subtotal.value * 0.1
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
  const existingItem = cartItems.value.find((ci) => ci.id === item.id)
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
  cartItems.value = cartItems.value.filter((item) => item.id !== itemId)
}

const incrementQuantity = (itemId: string | number) => {
  const item = cartItems.value.find((i) => i.id === itemId)
  if (item) {
    item.quantity++
  }
}

const decrementQuantity = (itemId: string | number) => {
  const item = cartItems.value.find((i) => i.id === itemId)
  if (item && item.quantity > 1) {
    item.quantity--
  } else {
    removeFromCart(itemId)
  }
}

const formatPrice = (price: number): string => {
  return `$${price.toFixed(2)}`
}

// Open payment confirmation dialog
const openPaymentDialog = () => {
  if (cartItems.value.length === 0) {
    alert('Your cart is empty')
    return
  }
  showPaymentDialog.value = true
}

// Close payment confirmation dialog
const closePaymentDialog = () => {
  showPaymentDialog.value = false
}

// Proceed to payment (called from confirmation dialog)
const proceedToPayment = () => {
  showPaymentDialog.value = false
  handlePlaceOrder()
}

const handlePlaceOrder = async () => {
  if (isPlacingOrder.value) return
  if (cartItems.value.length === 0) {
    alert('Your cart is empty')
    return
  }

  isPlacingOrder.value = true

  try {
    console.log('🔒 [PAYMENT] Initializing payment for order...')

    const apiUrl = 'http://127.0.0.1:8000/api'

    // Step 1: Get guest ID and room ID from QR token
    console.log('📡 [PAYMENT] Fetching room/guest info from QR token:', qrToken.value)
    const roomResponse = await fetch(`${apiUrl}/guest/menu/${qrToken.value}`)
    const roomData = await roomResponse.json()

    console.log('📡 [PAYMENT] Room API response:', roomData)

    if (!roomResponse.ok || !roomData.success) {
      console.error('❌ [PAYMENT] Room verification failed:', roomData)
      throw new Error(roomData.message || 'Unable to verify room information')
    }

    const guestId = roomData.data.guest?.id
    const roomId = roomData.data.id

    if (!guestId || !roomId) {
      console.error('❌ [PAYMENT] Missing guest or room ID:', { guestId, roomId })
      throw new Error('Unable to retrieve guest or room information')
    }

    console.log('✅ [PAYMENT] Room verified - Room ID:', roomId, 'Guest ID:', guestId)

    // Step 2: Prepare order items
    const orderItems = cartItems.value.map((item) => ({
      menu_item_id: item.id,
      quantity: item.quantity,
    }))

    console.log('📦 [PAYMENT] Order items prepared:', orderItems)
    console.log('📦 [PAYMENT] Order items count:', orderItems.length)
    console.log('📦 [PAYMENT] First item ID type:', typeof orderItems[0]?.menu_item_id)
    console.log('📦 [PAYMENT] First item ID value:', orderItems[0]?.menu_item_id)

    // Step 3: Split guest name into first and last
    const nameParts = guestName.value.trim().split(' ')
    const firstName = nameParts[0] || 'Guest'
    const lastName = nameParts.slice(1).join(' ') || 'User'

    // Step 4: Initialize payment
    console.log('💳 [PAYMENT] Initializing payment with backend...')
    const paymentInitRequest = {
      guest_id: guestId,
      room_id: roomId,
      items: orderItems,
      first_name: firstName,
      last_name: lastName,
      email: guestEmail.value,
      phone: '+251912345678', // Default or from user profile if available
    }

    console.log('📤 [PAYMENT] Payment init request:', paymentInitRequest)

    const paymentResponse = await fetch(`${apiUrl}/order-payments/initialize`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(paymentInitRequest),
    })

    const paymentData = await paymentResponse.json()

    console.log('📡 [PAYMENT] Payment API response:', paymentData)
    console.log('📡 [PAYMENT] Response status:', paymentResponse.status)
    console.log('📡 [PAYMENT] Response ok:', paymentResponse.ok)

    if (!paymentResponse.ok || !paymentData.success) {
      console.error('❌ [PAYMENT] Payment initialization failed:', paymentData)
      
      // Extract detailed error message
      let errorMessage = 'Payment initialization failed'
      
      if (paymentData.message) {
        errorMessage = paymentData.message
      }
      
      // Check for Chapa-specific error
      if (paymentData.error) {
        errorMessage += ': ' + paymentData.error
      }
      
      // Check for Laravel validation errors
      if (paymentData.errors) {
        const firstError = Object.values(paymentData.errors)[0]
        if (Array.isArray(firstError) && firstError.length > 0) {
          errorMessage = firstError[0]
        }
      }
      
      // Check for detailed error info (development mode)
      if (paymentData.details) {
        console.error('❌ [PAYMENT] Error details:', paymentData.details)
      }
      
      console.error('❌ [PAYMENT] Detailed error:', errorMessage)
      console.error('❌ [PAYMENT] Full error object:', JSON.stringify(paymentData, null, 2))
      
      throw new Error(errorMessage)
    }

    console.log('✅ [PAYMENT] Payment initialized successfully')
    console.log('🔗 [PAYMENT] Checkout URL:', paymentData.checkout_url)

    // Step 5: Store order data for post-payment retrieval
    sessionStorage.setItem(
      'order_payment_data',
      JSON.stringify({
        payment_id: paymentData.payment_id,
        tx_ref: paymentData.tx_ref,
        amount: paymentData.amount,
        calculation: paymentData.calculation,
        items: cart.value.map(item => ({
          name: item.name,
          quantity: item.quantity,
          total: item.price * item.quantity,
        })),
        qr_token: qrToken.value,
        room_number: roomNumber.value,
        guest_name: guestName.value,
      })
    )

    console.log('📦 [PAYMENT] Order data stored in session storage')

    // Step 6: Redirect to Chapa checkout
    console.log('🔄 [PAYMENT] Redirecting to Chapa checkout...')
    window.location.href = paymentData.checkout_url
  } catch (error: any) {
    console.error('❌ [PAYMENT] Error:', error)
    console.error('❌ [PAYMENT] Error details:', error.message)

    let errorMessage = 'Something went wrong. Please try again.'

    if (error.message) {
      errorMessage = error.message
    }

    alert(`❌ Payment Error: ${errorMessage}`)
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
  0%,
  100% {
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