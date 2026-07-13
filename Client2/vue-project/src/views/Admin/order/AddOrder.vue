<script setup lang="ts">
import { ref, reactive, computed, onMounted, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import DashboardLayout from '@/layouts/DashboardLayout.vue'
import { useOrderStore } from '@/stores/orderStore'
import { useReservationStore } from '@/stores/reservationStore'
import { useGuestStore } from '@/stores/guestStore'
import { useRoomStore } from '@/stores/room'
import { useMenuStore } from '@/stores/menuStore'
import OrderItemsTable from '@/components/order/OrderItemsTable.vue'
import OrderSummaryCard from '@/components/order/OrderSummaryCard.vue'
import type { Order, OrderItem } from '@/types/order'

const router = useRouter()
const route = useRoute()
const orderStore = useOrderStore()
const reservationStore = useReservationStore()
const guestStore = useGuestStore()
const roomStore = useRoomStore()
const menuStore = useMenuStore()

// Get order ID from route params for edit/view
const orderId = computed(() => route.params.id as string)
const isEditMode = computed(() => !!orderId.value && route.query.mode !== 'view')
const isViewMode = computed(() => !!orderId.value && route.query.mode === 'view')
const isCreateMode = computed(() => !orderId.value)

// Loading states
const loading = ref(false)
const submitting = ref(false)
const pageLoading = ref(true)

// Order data
const currentOrder = ref<Order | null>(null)

// Form data
const TAX_RATE = 0.1

interface OrderForm {
  reservation_id: string
  guest_id: string
  room_id: string
  payment_type: string
  notes: string
  items: OrderItem[]
}

const form = reactive<OrderForm>({
  reservation_id: '',
  guest_id: '',
  room_id: '',
  payment_type: 'room_charge',
  notes: '',
  items: [],
})

// State for adding new items
const selectedMenuItemId = ref('')
const selectedQuantity = ref(1)

// Helper function for setTimeout (usable in template event handlers)
function setTimeoutHelper(callback: () => void, delay: number) {
  setTimeout(callback, delay)
}

// Search states
const guestSearch = ref('')
const reservationSearch = ref('')
const roomSearch = ref('')
const showGuestDropdown = ref(false)
const showReservationDropdown = ref(false)
const showRoomDropdown = ref(false)

// Filtered lists for search
const filteredGuests = computed(() => {
  if (!guestSearch.value.trim()) return guestStore.guests
  const query = guestSearch.value.toLowerCase()
  return guestStore.guests.filter(
    (guest) =>
      `${guest.first_name} ${guest.last_name}`.toLowerCase().includes(query) ||
      guest.id.toLowerCase().includes(query) ||
      (guest.email && guest.email.toLowerCase().includes(query)),
  )
})

const filteredReservations = computed(() => {
  if (!reservationSearch.value.trim()) return reservationStore.reservations
  const query = reservationSearch.value.toLowerCase()
  return reservationStore.reservations.filter(
    (res) =>
      res.booking_reference.toLowerCase().includes(query) ||
      (res.guest?.first_name &&
        `${res.guest.first_name} ${res.guest.last_name}`.toLowerCase().includes(query)),
  )
})

const filteredRooms = computed(() => {
  let query = roomSearch.value.trim().toLowerCase()

  console.log('🔍 [FILTER] Search query:', query)
  console.log('🔍 [FILTER] Total rooms available:', roomStore.rooms.length)

  // If no search query, return all active rooms
  if (!query) {
    const activeRooms = roomStore.rooms.filter((room) => room.is_active !== false)
    console.log('🔍 [FILTER] No search - returning active rooms:', activeRooms.length)
    return activeRooms
  }
  // Remove "room" or "rm" prefix if user typed it (e.g., "room 202" → "202")
  if (query.startsWith('room ')) {
    query = query.replace('room ', '').trim()
    console.log('🔍 [FILTER] Removed "room " prefix, new query:', query)
  }
  if (query.startsWith('rm ')) {
    query = query.replace('rm ', '').trim()
    console.log('🔍 [FILTER] Removed "rm " prefix, new query:', query)
  }
  const results = roomStore.rooms.filter((room) => {
    // Ensure room exists
    if (!room) {
      console.log('[FILTER] Room is falsy, skipping')
      return false
    }
    console.log('[FILTER] Checking room:', {
      id: room.id,
      room_number: room.room_number,
      floor: room.floor,
      status: room.status,
      room_type_name: room.room_type?.name,
    })
    try {
      // Safe string conversion for all fields
      const roomNumber = room.room_number ? String(room.room_number).toLowerCase() : ''
      const roomType = room.room_type?.name ? String(room.room_type.name).toLowerCase() : ''
      const floor = room.floor ? String(room.floor).toLowerCase() : ''
      const status = room.status ? String(room.status).toLowerCase() : ''
      const description = room.description ? String(room.description).toLowerCase() : ''
      const id = room.id ? String(room.id).toLowerCase() : ''
      const matches =
        roomNumber.includes(query) ||
        roomType.includes(query) ||
        floor.includes(query) ||
        status.includes(query) ||
        description.includes(query) ||
        id.includes(query)
      if (matches) {
        console.log(`✅ [FILTER] MATCH! Room ${room.room_number} matched query "${query}"`, {
          roomNumber,
          matches,
        })
      }
      return matches
    } catch (e) {
      console.error('❌ [FILTER] Error filtering room:', room, e)
      return false
    }
  })
  console.log(`🔍 [FILTER] Results: ${results.length} rooms matched "${query}"`)
  return results
})
// Get display text for selections
const selectedReservationText = computed(() => {
  const res = reservationStore.reservations.find((r) => r.id === form.reservation_id)
  return res ? res.booking_reference : ''
})
const selectedGuestText = computed(() => {
  const guest = guestStore.guests.find((g) => g.id === form.guest_id)
  return guest ? `${guest.first_name} ${guest.last_name}` : ''
})
const selectedRoomText = computed(() => {
  const room = roomStore.rooms.find((r) => r.id === form.room_id)
  if (!room) return ''
  if (room.room_type?.name) {
    return `Room ${room.room_number} (${room.room_type.name})`
  }
  return `Room ${room.room_number}`
})
// Select functions
function selectReservation(reservationId: string) {
  form.reservation_id = reservationId
  showReservationDropdown.value = false
  reservationSearch.value = ''
}
function selectGuest(guestId: string) {
  form.guest_id = guestId
  showGuestDropdown.value = false
  guestSearch.value = ''
}
function selectRoom(roomId: string) {
  form.room_id = roomId
  showRoomDropdown.value = false
  roomSearch.value = ''
}
// Handle search input changes
function handleReservationInput(value: string) {
  reservationSearch.value = value
  showReservationDropdown.value = true
}
function handleGuestInput(value: string) {
  guestSearch.value = value
  showGuestDropdown.value = true
}
function handleRoomInput(value: string) {
  roomSearch.value = value
  showRoomDropdown.value = true
  console.log('🔍 Room search:', value)
  console.log('📋 Filtered rooms count:', filteredRooms.value.length)
  console.log('📋 All rooms count:', roomStore.rooms.length)
  console.log('📋 First room:', roomStore.rooms[0])
}
// Computed totals
const subtotal = computed(() => {
  return form.items.reduce((total, item) => {
    return total + Number(item.price ?? item.item_price_at_order) * Number(item.quantity)
  }, 0)
})

const tax = computed(() => {
  return subtotal.value * TAX_RATE
})

const discount = computed(() => 0)

const grandTotal = computed(() => {
  return subtotal.value + tax.value - discount.value
})

// Page title
const pageTitle = computed(() => {
  if (isViewMode.value) return 'Order Details'
  if (isEditMode.value) return 'Edit Order'
  return 'Create New Order'
})

const pageSubtitle = computed(() => {
  if (isViewMode.value) return 'View order details and information'
  if (isEditMode.value) return 'Update existing order information'
  return 'Add a new restaurant order'
})

// Load order data for edit/view
async function loadOrder() {
  if (!orderId.value) return

  try {
    const order = await orderStore.fetchOrder(orderId.value)
    if (order) {
      currentOrder.value = order
      populateForm(order)
    }
  } catch (error) {
    console.error('Failed to load order:', error)
  }
}

// Populate form with order data
function populateForm(order: Order) {
  form.reservation_id = order.reservation_id || ''
  form.guest_id = order.guest_id || ''
  form.room_id = order.room_id || ''
  form.payment_type = order.payment_type || 'room_charge'
  form.notes = order.notes || ''
  form.items = order.items ? structuredClone(order.items) : []
}
function resetForm() {
  form.reservation_id = ''
  form.guest_id = ''
  form.room_id = ''
  form.payment_type = 'room_charge'
  form.notes = ''
  form.items = []
}

// Initialize page
async function initializePage() {
  pageLoading.value = true
  try {
    // Load all required data
    console.log('📦 Starting data initialization...')

    await Promise.all([
      reservationStore.fetchReservations(),
      guestStore.fetchGuests(),
      roomStore.fetchRooms({ per_page: 100 }), // Request all rooms for complete search
      menuStore.fetchMenuItems({ per_page: 100 }), // ✅ FETCH MENU ITEMS!
    ])

    console.log('Data loaded')
    console.log('Total rooms loaded:', roomStore.rooms.length)
    console.log(' First room:', roomStore.rooms[0])
    console.log(' All rooms:', roomStore.rooms)
    console.log('Total menu items loaded:', menuStore.menuItems.length)
    console.log(' Menu items:', menuStore.menuItems)
    if (menuStore.menuItems.length === 0) {
      console.warn('⚠️ WARNING: No menu items loaded! Check backend API.')
    }

    // If editing or viewing, load the order
    if (orderId.value) {
      await loadOrder()
    } else {
      resetForm()
    }
  } catch (error) {
    console.error('Failed to initialize page:', error)
  } finally {
    pageLoading.value = false
  }
}

// Handle item updates from OrderItemsTable
function updateItems(items: OrderItem[]) {
  form.items = items
}

// Add item to order
function addItemToOrder() {
  if (!selectedMenuItemId.value || selectedQuantity.value < 1) {
    alert('Please select a menu item and quantity')
    return
  }

  const menuItem = menuStore.menuItems.find((item) => item.id === selectedMenuItemId.value)
  if (!menuItem) {
    alert('Menu item not found')
    return
  }
  // Check if item already exists in order
  const existingItemIndex = form.items.findIndex(
    (item) => item.menu_item_id === selectedMenuItemId.value,
  )
  if (existingItemIndex >= 0) {
    // Update quantity if item already exists
    form.items[existingItemIndex].quantity += selectedQuantity.value
    form.items[existingItemIndex].line_total =
      form.items[existingItemIndex].quantity * form.items[existingItemIndex].item_price_at_order
  } else {
    // Add new item
    const newItem: OrderItem = {
      id: `temp-${Date.now()}`,
      menu_item_id: selectedMenuItemId.value,
      quantity: selectedQuantity.value,
      item_price_at_order: menuItem.price,
      line_total: menuItem.price * selectedQuantity.value,
      notes: '',
      name: menuItem.name,
      price: menuItem.price,
      menu_item: {
        id: menuItem.id,
        name: menuItem.name,
      },
    }
    form.items.push(newItem)
  }

  // Reset form
  selectedMenuItemId.value = ''
  selectedQuantity.value = 1
}

// Remove item
function removeItem(index: number) {
  form.items.splice(index, 1)
}

// Prepare payload for API
function preparePayload() {
  return {
    reservation_id: form.reservation_id,
    guest_id: form.guest_id,
    room_id: form.room_id,
    payment_type: form.payment_type,
    notes: form.notes,
    items: form.items.map((item) => ({
      menu_item_id: item.menu_item_id,
      quantity: item.quantity,
    })),
    preview_total: {
      subtotal: subtotal.value,
      tax: tax.value,
      discount: discount.value,
      total: grandTotal.value,
    },
  }
}

// Validate form
function validateForm(): boolean {
  if (!form.reservation_id) {
    alert('Please select a reservation')
    return false
  }
  if (!form.guest_id) {
    alert('Please select a guest')
    return false
  }
  if (!form.room_id) {
    alert('Please select a room')
    return false
  }
  if (form.items.length === 0) {
    alert('Please add at least one item to the order')
    return false
  }
  return true
}

// Save order (Create or Update)
async function saveOrder() {
  if (!validateForm()) return
  submitting.value = true
  try {
    const payload = preparePayload()

    if (isEditMode.value && orderId.value) {
      await orderStore.updateOrder(orderId.value, payload)
    } else {
      await orderStore.createOrder(payload)
    }

    // Navigate back to orders list
    router.push('/orders')
  } catch (error) {
    console.error('Failed to save order:', error)
    alert('Failed to save order. Please try again.')
  } finally {
    submitting.value = false
  }
}

// Delete order
async function deleteOrder() {
  if (!orderId.value) return

  if (!confirm('Are you sure you want to delete this order? This action cannot be undone.')) {
    return
  }

  submitting.value = true
  try {
    await orderStore.deleteOrder(orderId.value)
    router.push('/orders')
  } catch (error) {
    console.error('Failed to delete order:', error)
    alert('Failed to delete order. Please try again.')
  } finally {
    submitting.value = false
  }
}

// Cancel and go back
function cancelOrder() {
  if (form.items.length > 0 || form.notes) {
    if (!confirm('You have unsaved changes. Are you sure you want to leave?')) {
      return
    }
  }
  router.push('/orders')
}

// Get status badge class
function getStatusClass(status?: string): string {
  const classes: Record<string, string> = {
    pending: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
    preparing: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
    ready: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
    served: 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200',
    cancelled: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
  }
  return classes[status || ''] || 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200'
}

function getPaymentLabel(type: string): string {
  const labels: Record<string, string> = {
    room_charge: 'Room Charge',
    cash: 'Cash',
    card: 'Card',
  }
  return labels[type] || type
}

function formatDate(date: string): string {
  if (!date) return 'N/A'
  return new Date(date).toLocaleString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

// Watch for order ID changes
watch(
  () => route.params.id,
  () => {
    if (route.params.id) {
      initializePage()
    }
  },
  { immediate: true },
)

// Mounted
onMounted(() => {
  initializePage()
})
</script>

<template>
  <DashboardLayout>
    <div class="min-h-screen bg-gray-50 px-4 sm:px-6 py-6 sm:py-8">
      <!-- PAGE HEADER - Responsive -->
      <div class="mx-auto mb-6 max-w-7xl">
        <div class="flex flex-col gap-3 sm:gap-4 sm:flex-row sm:items-center sm:justify-between">
          <div>
            <h1 class="text-xl sm:text-2xl font-bold text-gray-900">
              {{ pageTitle }}
            </h1>
            <p class="mt-1 text-xs sm:text-sm text-gray-500">
              {{ pageSubtitle }}
            </p>
          </div>

          <div class="flex gap-2 sm:gap-3 flex-wrap">
            <button
              @click="cancelOrder"
              class="rounded-lg border border-gray-300 bg-white px-3 sm:px-5 py-2 sm:py-2.5 text-xs sm:text-sm font-medium text-gray-700 hover:bg-gray-50 transition"
            >
              Cancel
            </button>

            <button
              v-if="!isViewMode"
              @click="saveOrder"
              :disabled="submitting"
              class="rounded-lg bg-blue-600 px-3 sm:px-5 py-2 sm:py-2.5 text-xs sm:text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-50 transition"
            >
              {{ submitting ? 'Saving...' : isEditMode ? 'Update Order' : 'Create Order' }}
            </button>
          </div>
        </div>
      </div>

      <!-- MAIN CARD -->

      <div
        class="mx-auto max-w-7xl rounded-xl border border-gray-200 bg-white shadow-sm overflow-hidden"
      >
        <!-- ORDER INFO HEADER -->

        <div class="border-b border-gray-200 px-8 py-5">
          <div class="flex items-center justify-between">
            <div>
              <h2 class="text-lg font-semibold text-gray-900">Order Information</h2>

              <p class="text-sm text-gray-500">Select guest reservation and payment details</p>
            </div>

            <div
              v-if="isViewMode && currentOrder"
              class="rounded-full bg-blue-50 px-4 py-1.5 text-sm font-medium text-blue-700"
            >
              {{ currentOrder.status }}
            </div>
          </div>
        </div>

        <!-- FORM -->

        <div class="p-8">
          <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
            <!-- Reservation Search -->
            <div class="relative">
              <label class="mb-2 block text-sm font-medium text-gray-700">
                Reservation
                <span class="text-red-500"> * </span>
              </label>

              <div class="relative">
                <input
                  :value="
                    form.reservation_id && !showReservationDropdown
                      ? selectedReservationText
                      : reservationSearch
                  "
                  @input="(e) => handleReservationInput((e.target as HTMLInputElement).value)"
                  @focus="showReservationDropdown = !isViewMode"
                  @blur="() => setTimeoutHelper(() => (showReservationDropdown = false), 300)"
                  @keydown.escape="showReservationDropdown = false"
                  :disabled="isViewMode"
                  type="text"
                  placeholder="Search reservation..."
                  class="w-full rounded-lg border border-gray-300 bg-white px-4 py-3 pr-10 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 disabled:bg-gray-100"
                />
                <svg
                  class="absolute right-3 top-3.5 h-5 w-5 text-gray-400 pointer-events-none"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                  ></path>
                </svg>
              </div>

              <div
                v-if="
                  showReservationDropdown && (reservationSearch || filteredReservations.length > 0)
                "
                class="absolute top-full left-0 right-0 mt-1 max-h-60 overflow-y-auto rounded-lg border border-gray-200 bg-white shadow-xl z-50"
              >
                <div
                  v-if="filteredReservations.length === 0"
                  class="px-4 py-3 text-sm text-gray-500"
                >
                  No reservations found
                </div>
                <div
                  v-for="reservation in filteredReservations"
                  :key="reservation.id"
                  @click="selectReservation(reservation.id)"
                  class="cursor-pointer px-4 py-3 hover:bg-blue-50 border-b border-gray-100 last:border-0 transition"
                >
                  <div class="font-medium text-gray-900">{{ reservation.booking_reference }}</div>
                  <div class="text-xs text-gray-500">
                    {{ reservation.guest?.first_name }} {{ reservation.guest?.last_name }}
                  </div>
                </div>
              </div>
            </div>

            <!-- Guest Search -->
            <div class="relative">
              <label class="mb-2 block text-sm font-medium text-gray-700">
                Guest
                <span class="text-red-500"> * </span>
              </label>

              <div class="relative">
                <input
                  :value="form.guest_id && !showGuestDropdown ? selectedGuestText : guestSearch"
                  @input="(e) => handleGuestInput((e.target as HTMLInputElement).value)"
                  @focus="showGuestDropdown = !isViewMode"
                  @blur="() => setTimeoutHelper(() => (showGuestDropdown = false), 300)"
                  @keydown.escape="showGuestDropdown = false"
                  :disabled="isViewMode"
                  type="text"
                  placeholder="Search guest..."
                  class="w-full rounded-lg border border-gray-300 bg-white px-4 py-3 pr-10 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 disabled:bg-gray-100"
                />
                <svg
                  class="absolute right-3 top-3.5 h-5 w-5 text-gray-400 pointer-events-none"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                  ></path>
                </svg>
              </div>

              <div
                v-if="showGuestDropdown && (guestSearch || filteredGuests.length > 0)"
                class="absolute top-full left-0 right-0 mt-1 max-h-60 overflow-y-auto rounded-lg border border-gray-200 bg-white shadow-xl z-50"
              >
                <div v-if="filteredGuests.length === 0" class="px-4 py-3 text-sm text-gray-500">
                  No guests found
                </div>
                <div
                  v-for="guest in filteredGuests"
                  :key="guest.id"
                  @click="selectGuest(guest.id)"
                  class="cursor-pointer px-4 py-3 hover:bg-blue-50 border-b border-gray-100 last:border-0 transition"
                >
                  <div class="font-medium text-gray-900">
                    {{ guest.first_name }} {{ guest.last_name }}
                  </div>
                  <div class="text-xs text-gray-500">{{ guest.id }}</div>
                </div>
              </div>
            </div>

            <!-- Room Search -->
            <div class="relative">
              <label class="mb-2 block text-sm font-medium text-gray-700">
                Room
                <span class="text-red-500"> * </span>
              </label>

              <div class="relative">
                <input
                  :value="form.room_id && !showRoomDropdown ? selectedRoomText : roomSearch"
                  @input="(e) => handleRoomInput((e.target as HTMLInputElement).value)"
                  @focus="showRoomDropdown = !isViewMode"
                  @blur="() => setTimeoutHelper(() => (showRoomDropdown = false), 300)"
                  @keydown.escape="showRoomDropdown = false"
                  :disabled="isViewMode"
                  type="text"
                  placeholder="Search by room number, floor..."
                  class="w-full rounded-lg border border-gray-300 bg-white px-4 py-3 pr-10 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 disabled:bg-gray-100"
                />
                <svg
                  class="absolute right-3 top-3.5 h-5 w-5 text-gray-400 pointer-events-none"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                  ></path>
                </svg>
              </div>

              <!-- Dropdown with available rooms -->
              <div
                v-if="showRoomDropdown && filteredRooms.length > 0"
                class="absolute top-full left-0 right-0 mt-1 max-h-60 overflow-y-auto rounded-lg border border-gray-200 bg-white shadow-xl z-50"
              >
                <div
                  v-for="room in filteredRooms"
                  :key="room.id"
                  @click="selectRoom(room.id)"
                  class="cursor-pointer px-4 py-3 hover:bg-blue-50 border-b border-gray-100 last:border-0 transition"
                >
                  <!-- Room Number and Type -->
                  <div class="font-medium text-gray-900">
                    Room {{ room.room_number }}
                    <span v-if="room.room_type?.name" class="text-xs text-gray-600 ml-2">
                      ({{ room.room_type.name }})
                    </span>
                  </div>

                  <!-- Additional Info -->
                  <div class="text-xs text-gray-500 mt-1">
                    <span v-if="room.floor">Floor {{ room.floor }}</span>
                    <span v-if="room.status" class="ml-2">
                      <span
                        :class="{
                          'bg-green-100 text-green-700': room.status === 'available',
                          'bg-red-100 text-red-700': room.status === 'occupied',
                          'bg-yellow-100 text-yellow-700': room.status === 'reserved',
                          'bg-gray-100 text-gray-700': room.status === 'maintenance',
                        }"
                        class="px-2 py-1 rounded text-xs"
                      >
                        {{ room.status_label || room.status }}
                      </span>
                    </span>
                  </div>
                </div>
              </div>

              <!-- No results message -->
              <div
                v-if="showRoomDropdown && roomSearch && filteredRooms.length === 0"
                class="absolute top-full left-0 right-0 mt-1 rounded-lg border border-gray-200 bg-white shadow-xl z-50 p-4"
              >
                <p class="text-sm text-gray-500">No rooms found matching "{{ roomSearch }}"</p>
                <p class="text-xs text-gray-400 mt-2">
                  Try searching by room number, floor, or status
                </p>
              </div>
            </div>
          </div>
          <!-- CONTINUE INSIDE MAIN CARD -->

          <!-- Payment + Notes -->

          <div class="mt-8 grid grid-cols-1 gap-6 md:grid-cols-2">
            <!-- Payment Type -->

            <div>
              <label class="mb-2 block text-sm font-medium text-gray-700"> Payment Type </label>

              <select
                v-model="form.payment_type"
                :disabled="isViewMode"
                class="w-full rounded-lg border border-gray-300 bg-white px-4 py-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
              >
                <option value="room_charge">Room Charge</option>

                <option value="cash">Cash</option>

                <option value="card">Card</option>
              </select>
            </div>

            <!-- Mode -->

            <div>
              <label class="mb-2 block text-sm font-medium text-gray-700"> Order Mode </label>

              <div
                class="flex h-[46px] items-center rounded-lg border border-gray-200 bg-gray-50 px-4 text-sm text-gray-600"
              >
                <span v-if="isViewMode"> View Order </span>

                <span v-else-if="isEditMode"> Edit Order </span>

                <span v-else> Create Order </span>
              </div>
            </div>
          </div>

          <!-- Notes -->

          <div class="mt-6">
            <label class="mb-2 block text-sm font-medium text-gray-700"> Notes </label>

            <textarea
              v-model="form.notes"
              :disabled="isViewMode"
              rows="3"
              placeholder="Add special instructions..."
              class="w-full resize-none rounded-lg border border-gray-300 px-4 py-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
            ></textarea>
          </div>

          <!-- ORDER ITEMS -->

          <div class="mt-10 border-t border-gray-200 pt-8">
            <div class="mb-5 flex items-center justify-between">
              <div>
                <h3 class="text-lg font-semibold text-gray-900">Order Items</h3>

                <p class="text-sm text-gray-500">Add menu items and manage quantities</p>
              </div>

              <span class="rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-600">
                {{ form.items.length }} items
              </span>
            </div>

            <!-- ADD ITEM SECTION (only in create/edit mode) -->
            <div v-if="!isViewMode" class="mb-6 rounded-xl border border-gray-200 bg-gray-50 p-6">
              <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                <div>
                  <label class="mb-2 block text-sm font-medium text-gray-700">
                    Menu Item
                    <span class="text-red-500"> * </span>
                  </label>
                  <select
                    v-model="selectedMenuItemId"
                    class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                  >
                    <option value="">Select a menu item</option>
                    <option v-for="item in menuStore.menuItems" :key="item.id" :value="item.id">
                      {{ item.name }} - ${{ item.price.toFixed(2) }}
                    </option>
                  </select>
                </div>

                <div>
                  <label class="mb-2 block text-sm font-medium text-gray-700">
                    Quantity
                    <span class="text-red-500"> * </span>
                  </label>
                  <input
                    v-model.number="selectedQuantity"
                    type="number"
                    min="1"
                    max="100"
                    class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                  />
                </div>

                <div>
                  <label class="mb-2 block text-sm font-medium text-gray-700"> &nbsp; </label>
                  <button
                    @click="addItemToOrder"
                    :disabled="!selectedMenuItemId || selectedQuantity < 1"
                    class="w-full rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 disabled:bg-gray-300 disabled:cursor-not-allowed transition"
                  >
                    Add Item
                  </button>
                </div>
              </div>
            </div>

            <div class="rounded-xl border border-gray-200 overflow-hidden">
              <OrderItemsTable
                :items="form.items"
                :menu-items="menuStore.menuItems"
                :readonly="isViewMode"
                :editable="!isViewMode"
                @update-items="updateItems"
              />
            </div>
          </div>

          <!-- SUMMARY -->

          <div class="mt-10 border-t border-gray-200 pt-8">
            <h3 class="mb-5 text-lg font-semibold text-gray-900">Order Summary</h3>

            <div class="rounded-xl border border-gray-200 bg-gray-50 p-6">
              <OrderSummaryCard
                :subtotal="subtotal"
                :tax="tax"
                :discount="discount"
                :total="grandTotal"
              />
            </div>
          </div>

          <!-- VALIDATION -->

          <div
            v-if="
              !isViewMode &&
              (!form.reservation_id || !form.guest_id || !form.room_id || form.items.length === 0)
            "
            class="mt-8 rounded-lg border border-yellow-200 bg-yellow-50 p-5"
          >
            <h4 class="font-semibold text-yellow-800">Complete Required Information</h4>

            <ul class="mt-3 space-y-2 text-sm text-yellow-700">
              <li v-if="!form.reservation_id">• Reservation is required</li>

              <li v-if="!form.guest_id">• Guest is required</li>

              <li v-if="!form.room_id">• Room is required</li>

              <li v-if="form.items.length === 0">• Add at least one menu item</li>
            </ul>
          </div>
        </div>

        <!-- FOOTER -->

        <div
          class="flex items-center justify-between border-t border-gray-200 bg-gray-50 px-8 py-5"
        >
          <div>
            <button
              v-if="isEditMode || isViewMode"
              @click="deleteOrder"
              class="rounded-lg px-4 py-2 text-sm font-medium text-red-600 hover:bg-red-50 transition"
            >
              Delete Order
            </button>
          </div>

          <div class="flex gap-3">
            <button
              @click="cancelOrder"
              class="rounded-lg border border-gray-300 bg-white px-6 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 transition"
            >
              Cancel
            </button>

            <button
              v-if="!isViewMode"
              @click="saveOrder"
              :disabled="submitting"
              class="rounded-lg bg-blue-600 px-6 py-2.5 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-50 transition"
            >
              <span v-if="submitting"> Saving... </span>

              <span v-else>
                {{ isEditMode ? 'Update Order' : 'Create Order' }}
              </span>
            </button>
          </div>
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>

<style scoped>
select,
textarea,
input {
  transition:
    border-color 0.2s ease,
    box-shadow 0.2s ease;
}
</style>
