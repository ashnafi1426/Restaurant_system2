<template>
  <div class="qr-menu-page">
    <QRMenuLayout
      ref="menuLayoutRef"
      :guest-name="guestName"
      :guest-email="guestEmail"
      :guest-avatar="guestAvatar"
      :room-number="roomNumber"
      :qr-token="qrToken"
      :hotel-id="hotelId"
      :hotel-name="hotelName"
      :hero-image="heroImage"
      :hero-heading="heroHeading"
      :hero-subheading="heroSubheading"
      :can-order="canOrderRoomService"
      :eligibility-message="eligibilityMessage"
      :reservation-status="reservationStatusVal"
      @room-selected="handleRoomSelected"
      @logout="handleLogout"
      @add-to-cart="handleAddToCart"
      @view-cart="handleViewCart"
    />

    <Teleport to="body">
      <Transition name="fade">
        <div
          v-if="showCartModal"
          class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4"
          @click.self="closeCartModal"
        >
          <div class="bg-white rounded-xl shadow-2xl max-w-2xl w-full max-h-[90vh] flex flex-col">
            <div
              class="flex-shrink-0 bg-gradient-to-r from-amber-500 to-amber-600 text-white px-6 py-4 flex items-center justify-between border-b border-amber-600 rounded-t-xl"
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
                {{ languageStore.t('your_cart', 'Your Cart') }}
              </h2>
              <button
                @click="closeCartModal"
                class="text-white hover:bg-white/20 p-2 rounded-lg transition-colors cursor-pointer"
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
            <div class="flex-1 overflow-y-auto bg-white">
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
                <p class="text-gray-500 text-lg font-medium">
                  {{ languageStore.t('cart_empty', 'Your cart is empty') }}
                </p>
                <p class="text-gray-400 text-sm mt-1">
                  {{
                    languageStore.t('add_items_from_menu', 'Add items from the menu to get started')
                  }}
                </p>
                <button
                  @click="closeCartModal"
                  class="mt-4 inline-flex items-center gap-2 bg-amber-100 text-amber-700 px-6 py-2 rounded-lg font-medium hover:bg-amber-200 transition-colors cursor-pointer"
                >
                  {{ languageStore.t('continue_shopping', 'Continue Shopping') }}
                </button>
              </div>

              <div v-if="cartItems.length > 0" class="divide-y divide-gray-200">
                <div
                  v-for="(item, index) in cartItems"
                  :key="`cart-item-${item.id}-${index}`"
                  class="px-4 py-4 flex gap-3 bg-white hover:bg-gray-50 transition-colors"
                >
                  <img
                    :src="item.image || '/images/placeholder.png'"
                    :alt="item.name"
                    class="w-16 h-16 rounded-lg object-cover flex-shrink-0"
                  />

                  <div class="flex-1 min-w-0">
                    <div class="flex items-start justify-between mb-2">
                      <div class="flex-1">
                        <h3 class="font-bold text-gray-900 text-base">{{ item.name }}</h3>
                        <p class="text-xs text-gray-500 mt-0.5">{{ item.description || 'ETB' }}</p>
                        <p class="text-sm font-bold text-gray-900 mt-1">
                          ${{ item.price.toFixed(2) }}
                        </p>
                      </div>

                      <button
                        @click="removeFromCart(item.id)"
                        class="text-red-500 hover:text-red-700 transition-colors p-1 cursor-pointer"
                        :title="languageStore.t('remove', 'Remove')"
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

                    <div class="flex items-center justify-between mt-2">
                      <div class="flex items-center gap-2 bg-gray-100 rounded-lg px-2 py-1">
                        <button
                          @click="decrementQuantity(item.id)"
                          class="w-8 h-8 flex items-center justify-center text-gray-700 hover:bg-gray-200 rounded transition-colors cursor-pointer font-bold text-lg"
                          :title="item.quantity === 1 ? 'Remove item' : 'Decrease'"
                        >
                          −
                        </button>

                        <span
                          class="text-lg font-bold text-gray-900 px-3 min-w-[2rem] text-center"
                          >{{ item.quantity }}</span
                        >

                        <button
                          @click="incrementQuantity(item.id)"
                          class="w-8 h-8 flex items-center justify-center text-gray-700 hover:bg-gray-200 rounded transition-colors cursor-pointer font-bold text-lg"
                          title="Increase"
                        >
                          +
                        </button>
                      </div>

                      <span class="text-base font-bold text-gray-900"
                        >ETB ${{ (item.price * item.quantity).toFixed(2) }}</span
                      >
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div class="flex-shrink-0 bg-white border-t border-gray-200 px-4 py-4 space-y-2">
              <div class="flex items-center justify-between text-gray-700 text-sm">
                <span>{{ languageStore.t('subtotal', 'Subtotal') }}</span>
                <span class="font-semibold">{{ formatPrice(subtotal) }}</span>
              </div>

              <div
                class="flex items-center justify-between text-gray-900 text-lg font-bold pt-2 border-t border-gray-200"
              >
                <span>{{ languageStore.t('total', 'Total') }}</span>
                <span class="text-red-600">{{ formatPrice(cartTotal) }}</span>
              </div>

              <div class="flex flex-col gap-2 pt-3">
                <button
                  @click="placeOrderWithRoomCharge"
                  :disabled="isPlacingOrder || cartItems.length === 0"
                  class="w-full px-4 py-3 bg-red-500 hover:bg-red-600 text-white rounded-lg font-bold text-base transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2 cursor-pointer"
                >
                  <svg
                    v-if="!isPlacingOrder"
                    class="w-6 h-6"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m-3-6h6"
                    ></path>
                  </svg>
                  <svg
                    v-else
                    class="w-6 h-6 animate-spin"
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
                  🍽️ {{ languageStore.t('order_now_pay_after', 'Order Now (Pay After Meal)') }}
                </button>

                <button
                  @click="openPaymentDialog"
                  :disabled="isPlacingOrder || cartItems.length === 0"
                  class="w-full px-4 py-3 bg-yellow-500 hover:bg-yellow-600 text-white rounded-lg font-bold text-base transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2 cursor-pointer border-2 border-yellow-600"
                >
                  <svg
                    v-if="!isPlacingOrder"
                    class="w-6 h-6"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"
                    ></path>
                  </svg>
                  💳 {{ languageStore.t('pay_now_with_chapa', 'Pay Now with Chapa') }}
                </button>

                <button
                  @click="closeCartModal"
                  class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg font-semibold text-gray-700 hover:bg-gray-50 transition-colors cursor-pointer"
                >
                  {{ languageStore.t('continue_shopping', 'Continue Shopping') }}
                </button>

                <p class="text-xs text-gray-500 text-center mt-2">
                  {{
                    languageStore.t(
                      'payment_choice_desc',
                      'Choose: Order now and pay after eating, or pay online now',
                    )
                  }}
                </p>
              </div>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

    <Teleport to="body">
      <Transition name="fade">
        <div
          v-if="showPaymentDialog"
          class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4"
          @click.self="closePaymentDialog"
        >
          <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full max-h-[85vh] flex flex-col">
            <div
              class="bg-gradient-to-r from-amber-500 to-amber-600 px-5 py-4 text-white flex-shrink-0 rounded-t-2xl"
            >
              <h3 class="text-xl font-bold mb-1">
                💳 {{ languageStore.t('payment_confirmation', 'Payment Confirmation') }}
              </h3>
              <p class="text-amber-100 text-sm">
                {{
                  orderContext?.type === 'table'
                    ? languageStore.t('enter_details_proceed', 'Enter details to proceed')
                    : languageStore.t(
                        'review_order_before_payment',
                        'Review your order before payment',
                      )
                }}
              </p>
            </div>

            <div class="p-5 space-y-3 overflow-y-auto flex-1">
              <div v-if="orderContext?.type === 'table'" class="space-y-3">
                <h4 class="font-semibold text-sm mb-2">
                  {{ languageStore.t('customer_details', 'Customer Details') }}
                </h4>

                <div>
                  <label class="text-xs text-slate-600 mb-1 block"
                    >{{ languageStore.t('first_name', 'First Name') }} *</label
                  >
                  <input
                    v-model="paymentForm.first_name"
                    type="text"
                    required
                    placeholder="John"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-amber-500 focus:border-transparent"
                  />
                </div>

                <div>
                  <label class="text-xs text-slate-600 mb-1 block"
                    >{{ languageStore.t('last_name', 'Last Name') }} *</label
                  >
                  <input
                    v-model="paymentForm.last_name"
                    type="text"
                    required
                    placeholder="Doe"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-amber-500 focus:border-transparent"
                  />
                </div>

                <div>
                  <label class="text-xs text-slate-600 mb-1 block"
                    >{{ languageStore.t('email', 'Email') }} *</label
                  >
                  <input
                    v-model="paymentForm.email"
                    type="email"
                    required
                    placeholder="john@example.com"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-amber-500 focus:border-transparent"
                  />
                </div>

                <div>
                  <label class="text-xs text-slate-600 mb-1 block"
                    >{{ languageStore.t('phone_number', 'Phone Number') }} *</label
                  >
                  <input
                    v-model="paymentForm.phone"
                    type="tel"
                    required
                    placeholder="+251912345678"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-amber-500 focus:border-transparent"
                  />
                </div>

                <div class="border-t pt-3"></div>
              </div>

              <div>
                <h4 class="font-semibold text-sm mb-2">
                  {{ languageStore.t('order_summary', 'Order Summary') }}
                </h4>
                <div class="space-y-2 text-xs">
                  <div class="flex justify-between">
                    <span class="text-slate-600">{{ languageStore.t('room', 'Room') }}:</span>
                    <span class="font-medium">{{ roomNumber }}</span>
                  </div>
                  <div class="flex justify-between">
                    <span class="text-slate-600">{{ languageStore.t('items', 'Items') }}:</span>
                    <span class="font-medium">{{ cartItems.length }}</span>
                  </div>
                  <div class="flex justify-between">
                    <span class="text-slate-600">{{ languageStore.t('guest', 'Guest') }}:</span>
                    <span class="font-medium">{{ guestName }}</span>
                  </div>
                </div>
              </div>

              <div class="border-t pt-3">
                <h4 class="font-semibold text-sm mb-2">
                  {{ languageStore.t('your_items', 'Your Items') }}
                </h4>
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

              <div class="border-t pt-3 space-y-1.5">
                <div class="flex justify-between text-xs">
                  <span class="text-slate-600">{{ languageStore.t('subtotal', 'Subtotal') }}:</span>
                  <span class="font-medium">{{ formatPrice(subtotal) }}</span>
                </div>
                <div class="flex justify-between text-xs">
                  <span class="text-slate-600">{{ languageStore.t('tax', 'Tax') }} (15%):</span>
                  <span class="font-medium">{{ formatPrice(tax) }}</span>
                </div>
                <div class="flex justify-between text-xs">
                  <span class="text-slate-600"
                    >{{ languageStore.t('service_charge', 'Service Charge') }} (10%):</span
                  >
                  <span class="font-medium">{{ formatPrice(serviceCharge) }}</span>
                </div>
                <div class="flex justify-between text-sm font-bold pt-1.5 border-t">
                  <span>{{ languageStore.t('total', 'Total') }}:</span>
                  <span class="text-amber-600">{{ formatPrice(cartTotal) }}</span>
                </div>
              </div>

              <div
                v-if="orderContext?.type === 'table'"
                class="bg-amber-50 border border-amber-200 rounded-lg p-2 text-xs text-amber-700"
              >
                {{ languageStore.t('walk_in_order_for', 'Walk-in order for') }}
                {{ orderContext.displayName }}
              </div>

              <div class="bg-blue-50 border border-blue-200 rounded-lg p-2 text-xs text-blue-700">
                ✓ {{ languageStore.t('secure_payment_notice', 'Secure payment via Chapa gateway') }}
              </div>
            </div>

            <div class="bg-slate-50 px-5 py-3 flex gap-2.5 flex-shrink-0 border-t rounded-b-2xl">
              <button
                @click="closePaymentDialog"
                :disabled="isPlacingOrder"
                class="flex-1 px-4 py-2 text-sm font-medium border border-slate-300 rounded-lg hover:bg-slate-100 disabled:opacity-50 transition-colors cursor-pointer"
              >
                {{ languageStore.t('cancel', 'Cancel') }}
              </button>
              <button
                @click="proceedToPayment"
                :disabled="isPlacingOrder"
                class="flex-1 px-4 py-2 text-sm font-medium bg-amber-600 text-white rounded-lg hover:bg-amber-700 disabled:opacity-50 flex items-center justify-center gap-2 transition-colors cursor-pointer"
              >
                <span v-if="isPlacingOrder"
                  >⌛ {{ languageStore.t('processing', 'Processing...') }}</span
                >
                <span v-else>💳 {{ languageStore.t('pay_now', 'Pay Now') }}</span>
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

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

            <h2 class="text-2xl font-bold text-gray-800 mb-2">
              {{ languageStore.t('order_placed_success', 'Order Placed Successfully!') }}
            </h2>
            <p class="text-gray-600 mb-4">
              {{
                languageStore.t(
                  'order_placed_desc',
                  'Your delicious meal is being prepared and will be delivered to your room shortly.',
                )
              }}
            </p>

            <div class="bg-amber-50 rounded-lg p-4 mb-6 text-left space-y-2">
              <div class="flex justify-between">
                <span class="text-gray-600"
                  >{{ languageStore.t('order_number', 'Order Number') }}:</span
                >
                <span class="font-bold text-gray-800">#{{ orderNumber }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-gray-600"
                  >{{ languageStore.t('room_number', 'Room Number') }}:</span
                >
                <span class="font-bold text-gray-800">{{ roomNumber }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-gray-600"
                  >{{ languageStore.t('estimated_time', 'Estimated Time') }}:</span
                >
                <span class="font-bold text-gray-800">{{ estimatedTime }} mins</span>
              </div>
              <div class="flex justify-between">
                <span class="text-gray-600"
                  >{{ languageStore.t('total_amount', 'Total Amount') }}:</span
                >
                <span class="font-bold text-amber-600">{{ formatPrice(cartTotal) }}</span>
              </div>
            </div>

            <div class="space-y-2">
              <button
                @click="handleTrackOrder"
                class="w-full px-4 py-3 bg-gradient-to-r from-amber-500 to-amber-600 text-white rounded-lg font-semibold hover:shadow-lg transition-shadow cursor-pointer"
              >
                {{ languageStore.t('track_order', 'Track Order') }}
              </button>
              <button
                @click="handleBackToMenu"
                class="w-full px-4 py-3 border-2 border-gray-300 text-gray-700 rounded-lg font-semibold hover:bg-gray-50 transition-colors cursor-pointer"
              >
                {{ languageStore.t('back_to_menu', 'Back to Menu') }}
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
import { qrService } from '@/services/qrService'
import { unifiedOrderService } from '@/services/unifiedOrderService'
import type { OrderContext } from '@/types/restaurantTable'
import { useLanguageStore } from '@/stores/language'
import { useGuestHotelStore } from '@/stores/guestHotelStore'

const languageStore = useLanguageStore()
const guestHotelStore = useGuestHotelStore()

interface MenuItem {
  id: string | number
  name: string
  description: string
  price: number
  total_price?: number
  image: string | null
  category: string
  rating?: number
  badge?: string
  dietary?: string[]
  calories?: number
  preparationTime?: number
  is_available?: boolean
  tax_rate?: {
    id?: string | number
    name?: string
    rate: number
    is_active?: boolean
  } | null
  tax_included?: boolean
  tax_amount?: number
}

interface CartItem extends MenuItem {
  quantity: number
}

const route = useRoute()
const router = useRouter()
const menuLayoutRef = ref<InstanceType<typeof QRMenuLayout> | null>(null)

const qrToken = ref(
  (route.params.qrToken as string) ||
    (route.query.token as string) ||
    localStorage.getItem('qrToken') ||
    '',
)
const hotelId = ref<string>(guestHotelStore.hotelId || localStorage.getItem('hotel_id') || '')
const hotelName = ref<string>(guestHotelStore.hotelName || '')
const roomNumber = ref('101')
const guestName = ref('Guest User')
const guestEmail = ref('guest@royalhorizon.com')
const guestAvatar = ref('/images/avatar.png')
const heroImage = ref('/images/gallery/fine-dining.jpg')
const heroHeading = ref('Good Food, Great Moments')
const heroSubheading = ref('LUXURY DINING')
const cartItems = ref<CartItem[]>([])
const showCartModal = ref(false)
const showPaymentDialog = ref(false)
const showSuccessModal = ref(false)
const isPlacingOrder = ref(false)
const orderNumber = ref('')
const estimatedTime = ref(30)

const orderContext = ref<OrderContext | null>(null)
const isLoadingContext = ref(true)
const contextError = ref<string | null>(null)
const canOrderRoomService = ref(true)
const eligibilityMessage = ref('')
const reservationStatusVal = ref('')

const paymentForm = ref({
  first_name: '',
  last_name: '',
  email: '',
  phone: '+251',
})

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
  if (orderContext.value?.type === 'room' && !canOrderRoomService.value) {
    alert(
      eligibilityMessage.value ||
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
    const oldQuantity = item.quantity
    item.quantity++
    console.log(`[QRMenu] Increased ${item.name} quantity from ${oldQuantity} to ${item.quantity}`)

    // Optional: Add a brief visual feedback
    // You could add a toast notification here if desired
  }
}

const decrementQuantity = (itemId: string | number) => {
  const item = cartItems.value.find((i) => i.id === itemId)
  if (item && item.quantity > 1) {
    const oldQuantity = item.quantity
    item.quantity--
    console.log(`[QRMenu] Decreased ${item.name} quantity from ${oldQuantity} to ${item.quantity}`)
  } else {
    console.log(`[QRMenu] Removing ${item?.name} from cart`)
    removeFromCart(itemId)
  }
}

const formatPrice = (price: number): string => {
  return `${price.toFixed(2)}`
}

// New method for "Order Now (Pay After Meal)" - places order with room charge
const placeOrderWithRoomCharge = async () => {
  if (isPlacingOrder.value) return
  if (cartItems.value.length === 0) {
    alert('Your cart is empty')
    return
  }

  if (!orderContext.value) {
    alert('Order context not loaded. Please refresh the page.')
    return
  }

  isPlacingOrder.value = true

  try {
    const orderItems = cartItems.value.map((item) => ({
      menu_item_id: String(item.id),
      quantity: item.quantity,
    }))

    // Always use room_charge payment type (pay after meal)
    const orderResponse = await unifiedOrderService.createOrder({
      qr_token: qrToken.value,
      items: orderItems,
      special_requests: '',
      payment_type: 'room_charge', // Pay after meal
    })

    if (orderResponse && orderResponse.success && orderResponse.data) {
      const createdOrderId = orderResponse.data.id || orderResponse.data.order_id
      orderNumber.value = orderResponse.data.order_number
      roomNumber.value = orderResponse.data.room_number || roomNumber.value
      estimatedTime.value = 30

      // Build complete order data object with items for OrderStatusPage
      const completeOrderData = {
        ...orderResponse.data,
        id: createdOrderId,
        order_id: createdOrderId,
        items: cartItems.value.map((item) => ({
          id: item.id,
          name: item.name,
          description: item.description,
          quantity: item.quantity,
          price: item.price,
          image: item.image,
          total: item.price * item.quantity,
        })),
        subtotal: subtotal.value,
        tax: tax.value,
        service_charge: serviceCharge.value,
        total: cartTotal.value,
        status: 'pending',
        payment_status: 'pending',
        payment_type: 'room_charge',
        created_at: new Date().toISOString(),
        updated_at: new Date().toISOString(),
      }

      // Store order data for OrderStatusPage
      if (orderResponse.data.hotel_id) {
        localStorage.setItem('hotel_id', orderResponse.data.hotel_id)
      }
      if (qrToken.value) {
        localStorage.setItem('guest_qr_token', qrToken.value)
      }

      // Store the complete order data for immediate display
      console.log('[QRMenu] Storing complete order data:', completeOrderData)
      console.log('[QRMenu] Created order ID:', createdOrderId)
      localStorage.setItem('pending_order_data', JSON.stringify(completeOrderData))

      // Verify it was stored
      const verifyStored = localStorage.getItem('pending_order_data')
      console.log('[QRMenu] Verified stored data:', verifyStored ? 'Success ' : 'Failed ❌')

      // Clear cart
      cartItems.value = []
      showPaymentDialog.value = false
      showCartModal.value = false

      // Redirect to real-time Order Status page with all necessary params
      console.log('[QRMenu] Redirecting to order status with ID:', createdOrderId)
      router.push({
        name: 'order-status',
        params: { orderId: createdOrderId },
        query: {
          hotel_id: orderResponse.data.hotel_id,
          qr_token: qrToken.value,
          order_number: orderResponse.data.order_number,
        },
      })
      return
    } else {
      throw new Error(orderResponse.message || 'Failed to place order')
    }
  } catch (error: any) {
    console.error('[QRMenu] Error placing order with room charge:', error)
    alert(error.message || 'Failed to place order. Please try again.')
  } finally {
    isPlacingOrder.value = false
  }
}

const openPaymentDialog = () => {
  if (cartItems.value.length === 0) {
    alert('Your cart is empty')
    return
  }

  // Close cart modal
  showCartModal.value = false

  // For "Pay with Chapa" button: ALWAYS go to OrderPaymentPage (with tip selection)
  // This works for both table orders and general menu access

  // Store payment data for the OrderPaymentPage (without customer details)
  const paymentData = {
    qr_token: qrToken.value,
    table_number: orderContext.value?.displayName || 'Table 11',
    table_id: orderContext.value?.id || '',
    customer_name: 'Guest', // Default name
    customer_phone: '', // Will be collected by Chapa
    customer_email: '', // Will be collected by Chapa
    items: cartItems.value.map((item) => ({
      id: item.id,
      name: item.name,
      quantity: item.quantity,
      price: item.price,
    })),
    calculation: {
      subtotal: subtotal.value,
      tax: 0,
      service_charge: 0,
      total: subtotal.value,
    },
  }

  localStorage.setItem('walk_in_payment_data', JSON.stringify(paymentData))
  if (qrToken.value) {
    localStorage.setItem('guest_qr_token', qrToken.value)
    sessionStorage.setItem('guest_qr_token', qrToken.value)
  }
  console.log(
    '[QRMenu] Pay with Chapa clicked - Stored payment data, navigating to OrderPaymentPage:',
    paymentData,
  )

  // Navigate directly to OrderPaymentPage (dark theme with tip selection)
  router.push({
    path: '/order/payment',
    query: {
      qr_token: qrToken.value,
    },
  })
}

const closePaymentDialog = () => {
  showPaymentDialog.value = false
  showCartModal.value = true
}

const proceedToPayment = () => {
  // This function is now only used for room orders
  // Table orders go directly from cart to OrderPaymentPage
  if (orderContext.value?.type === 'room') {
    showPaymentDialog.value = false
    handlePlaceOrder()
  }
}

const handlePlaceOrder = async () => {
  if (isPlacingOrder.value) return
  if (orderContext.value?.type === 'room' && !canOrderRoomService.value) {
    alert(
      eligibilityMessage.value || 'Room service ordering is only available for checked-in guests.',
    )
    return
  }
  if (cartItems.value.length === 0) {
    alert('Your cart is empty')
    return
  }

  if (!orderContext.value) {
    alert('Order context not loaded. Please refresh the page.')
    return
  }

  isPlacingOrder.value = true

  try {
    const orderItems = cartItems.value.map((item) => ({
      menu_item_id: String(item.id),
      quantity: item.quantity,
    }))

    if (orderContext.value.type === 'table') {
      const paymentResponse = await unifiedOrderService.initializeWalkInPayment({
        table_id: orderContext.value.id,
        qr_token: qrToken.value,
        items: orderItems,
        special_requests: '',
        first_name: paymentForm.value.first_name,
        last_name: paymentForm.value.last_name,
        email: paymentForm.value.email,
        phone: paymentForm.value.phone,
      })

      if (paymentResponse.success && paymentResponse.checkout_url) {
        // Use localStorage instead of sessionStorage to persist through Chapa redirect
        const paymentData = {
          payment_id: paymentResponse.payment_id,
          tx_ref: paymentResponse.tx_ref,
          amount: paymentResponse.amount,
          qr_token: qrToken.value,
          table_number: orderContext.value.displayName,
          items: cartItems.value.map((item) => ({
            name: item.name,
            quantity: item.quantity,
            price: item.price,
            total: item.price * item.quantity,
          })),
          calculation: paymentResponse.calculation,
        }

        localStorage.setItem('walk_in_payment_data', JSON.stringify(paymentData))
        sessionStorage.setItem('walk_in_payment_data', JSON.stringify(paymentData))
        if (qrToken.value) {
          localStorage.setItem('guest_qr_token', qrToken.value)
          sessionStorage.setItem('guest_qr_token', qrToken.value)
        }

        console.log('[QRMenu] Stored payment data before redirect:', paymentData)
        console.log('[QRMenu] Redirecting to Chapa:', paymentResponse.checkout_url)

        window.location.href = paymentResponse.checkout_url
        return
      } else {
        throw new Error(paymentResponse.message || 'Failed to initialize payment')
      }
    }

    if (orderContext.value.type === 'room') {
      const orderResponse = await unifiedOrderService.createOrder({
        qr_token: qrToken.value,
        items: orderItems,
        special_requests: '',
        payment_type: 'room_charge',
      })

      if (orderResponse && orderResponse.success && orderResponse.data) {
        const createdOrderId = orderResponse.data.id || orderResponse.data.order_id
        orderNumber.value = orderResponse.data.order_number
        roomNumber.value = orderResponse.data.room_number || roomNumber.value
        estimatedTime.value = 30

        // Store hotel_id for OrderStatusPage
        if (orderResponse.data.hotel_id) {
          localStorage.setItem('hotel_id', orderResponse.data.hotel_id)
        }

        // Clear cart
        cartItems.value = []
        showPaymentDialog.value = false
        showCartModal.value = false

        // Redirect to real-time Order Status page instead of showing modal
        router.push({
          name: 'order-status',
          params: { orderId: createdOrderId },
        })
        return
      } else {
        throw new Error(orderResponse.message || 'Failed to place room order')
      }
    }

    throw new Error('Invalid order context')
  } catch (error: any) {
    console.error('[QRMenu] Error placing order:', error)
    let errorMessage = 'Something went wrong. Please try again.'

    if (error.message) {
      errorMessage = error.message
    }
    alert(` Payment Error: ${errorMessage}`)
  } finally {
    isPlacingOrder.value = false
  }
}

const handleTrackOrder = () => {
  showSuccessModal.value = false
}

const handleBackToMenu = () => {
  showSuccessModal.value = false
}

const detectOrderContext = async () => {
  isLoadingContext.value = true
  contextError.value = null

  try {
    const result = await qrService.resolveQRToken(qrToken.value)

    if (!result.success || !result.context || !result.data) {
      throw new Error(result.message || 'Invalid QR code')
    }

    if (result.context === 'room') {
      const isCheckedIn = result.data.is_checked_in === true || result.data.can_order === true
      const resStatus = result.data.reservation_status || (isCheckedIn ? 'checked_in' : 'none')
      const eligMsg =
        result.data.eligibility_message ||
        (isCheckedIn ? '' : 'Only checked-in guests can place room-service orders.')

      canOrderRoomService.value = isCheckedIn
      eligibilityMessage.value = eligMsg
      reservationStatusVal.value = resStatus

      // CRITICAL: Store hotel_id from QR resolution to ensure correct tenant isolation
      if (result.data.hotel_id) {
        hotelId.value = result.data.hotel_id
        hotelName.value = result.data.hotel_name || ''
        localStorage.setItem('hotel_id', result.data.hotel_id)
        localStorage.setItem('guest_hotel_id', result.data.hotel_id)
        localStorage.setItem('active_hotel_id', result.data.hotel_id)
        console.log('[QRMenu] Stored hotel_id from QR resolution:', result.data.hotel_id)
        guestHotelStore.selectHotelById(result.data.hotel_id)
      }

      orderContext.value = {
        type: 'room',
        id: result.data.room_id!,
        displayName: `Room ${result.data.room_number}`,
        paymentOptions: [{ value: 'room_charge', label: 'Charge to Room' }],
        isCheckedIn: isCheckedIn,
        canOrder: isCheckedIn,
        reservationStatus: resStatus,
        eligibilityMessage: eligMsg,
      }
      roomNumber.value = result.data.room_number || '101'
      heroHeading.value = 'Room Service Menu'
      heroSubheading.value = `Room ${result.data.room_number}`

      if (result.data.guest) {
        guestName.value = result.data.guest.guest_name
        guestEmail.value = result.data.guest.guest_email || 'guest@hotel.com'
      } else {
        guestName.value = 'Hotel Guest'
        guestEmail.value = 'guest@hotel.com'
      }
    } else if (result.context === 'table') {
      canOrderRoomService.value = true
      eligibilityMessage.value = ''
      reservationStatusVal.value = 'not_applicable'

      // CRITICAL: Store hotel_id from QR resolution for table orders
      if (result.data.hotel_id) {
        hotelId.value = result.data.hotel_id
        hotelName.value = result.data.hotel_name || ''
        localStorage.setItem('hotel_id', result.data.hotel_id)
        localStorage.setItem('guest_hotel_id', result.data.hotel_id)
        localStorage.setItem('active_hotel_id', result.data.hotel_id)
        console.log('[QRMenu] Stored hotel_id from table QR resolution:', result.data.hotel_id)
        guestHotelStore.selectHotelById(result.data.hotel_id)
      }

      orderContext.value = {
        type: 'table',
        id: result.data.table_id!,
        displayName: result.data.table_name || `Table ${result.data.table_number}`,
        paymentOptions: [
          { value: 'cash', label: 'Pay with Cash' },
          { value: 'card', label: 'Pay with Card' },
        ],
        isCheckedIn: true,
        canOrder: true,
        reservationStatus: 'not_applicable',
        eligibilityMessage: '',
      }
      roomNumber.value = result.data.table_name || `Table ${result.data.table_number}`
      heroHeading.value = 'Restaurant Menu'
      heroSubheading.value = result.data.table_name || `Table ${result.data.table_number}`
      guestName.value = 'Walk-in Guest'
      guestEmail.value = 'walkin@restaurant.com'
    }
  } catch (error: any) {
    console.error('[QRMenu] Failed to detect order context:', error)
    contextError.value = error.message || 'Failed to load menu'

    if (!orderContext.value || !orderContext.value.type) {
      orderContext.value = {
        type: 'table',
        id: '',
        displayName: 'Restaurant Dining',
        paymentOptions: [
          { value: 'cash', label: 'Pay with Cash' },
          { value: 'card', label: 'Pay with Card' },
        ],
      }
      roomNumber.value = 'Restaurant Dining'
      heroHeading.value = 'Restaurant Menu'
      heroSubheading.value = 'Dining & Takeout'
      guestName.value = 'Walk-in Guest'
      guestEmail.value = 'walkin@restaurant.com'
    }
  } finally {
    isLoadingContext.value = false
  }
}

watch(
  () => route.params.qrToken,
  async (newVal) => {
    if (newVal && newVal !== qrToken.value) {
      qrToken.value = String(newVal)
      await detectOrderContext()
    }
  },
)

onMounted(async () => {
  if (route.params.qrToken) {
    qrToken.value = String(route.params.qrToken)
  }
  if (route.query.token) {
    qrToken.value = String(route.query.token)
  }
  if (!qrToken.value) {
    qrToken.value = localStorage.getItem('qrToken') || ''
  }

  if (qrToken.value) {
    await detectOrderContext()
  } else {
    const qHotel = (route.query.hotel_id as string) || (route.query.hotel as string)
    if (qHotel) {
      await guestHotelStore.selectHotelById(qHotel)
      hotelId.value = guestHotelStore.hotelId
      hotelName.value = guestHotelStore.hotelName
    } else if (guestHotelStore.hotelId) {
      hotelId.value = guestHotelStore.hotelId
      hotelName.value = guestHotelStore.hotelName
    }
    roomNumber.value = localStorage.getItem('roomNumber') || '101'
    isLoadingContext.value = false
  }

  const guestInfo = localStorage.getItem('guestInfo')
  if (guestInfo) {
    try {
      const info = JSON.parse(guestInfo)
      guestName.value = info.name || guestName.value
      guestEmail.value = info.email || guestEmail.value
      guestAvatar.value = info.avatar || guestAvatar.value
    } catch (e) {
      console.error('[QRMenu] Error parsing guestInfo from storage:', e)
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

.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>
