# QRMenu.vue Refactoring Implementation Plan

## Overview

Refactor the large QRMenu.vue file (~717 lines) into clean, modular Vue 3 + TypeScript components and composables WITHOUT changing any existing functionality. Fix critical bugs: order ID tracking after order placement, currency formatting inconsistency.

**Current Structure Analysis:**
- **Total Lines:** 717 (Template: 1-171, Script: 173-717)
- **Template Sections:** Cart modal (22-147), Payment dialog (192-285), Success modal (288-346)
- **Script Sections:** Interfaces (175-186), State (188-213), Computed (215-226), Event handlers (228-262), Order placement logic (264-446), QR context detection (458-705)
- **Cart Calculations:** subtotal (sum), tax (subtotal * 0.15), service charge (subtotal * 0.1), total (subtotal + tax + serviceCharge)
- **Storage Keys:** `pending_order_data` (room charge orders), `walk_in_payment_data` (Chapa payments), `last_order_id`, `hotel_id`, `guest_qr_token`

**Critical Bugs Identified:**
1. **Order ID not passed to OrderStatusPage:** After room charge order creation, `pending_order_data` in localStorage is incomplete (missing `order_id` field). OrderStatusPage's useOrderStatus composable cannot find order ID, logs error: `[OrderPaymentSuccess] No order ID found after checking all sources!`
2. **tx_ref not found after Chapa redirect:** Walk-in payment data stored only to sessionStorage, which gets cleared during Chapa redirect. OrderPaymentSuccessPage cannot find `tx_ref`, logs error: `[OrderPaymentSuccess] No tx_ref found! Cannot verify payment.`
3. **Currency formatting inconsistency:** Mixed use of `$`, `ETB`, and `ETB $` formats. User requires consistent `ETB XX.XX` format throughout.

---

## Implementation Plan

### Step 1: Create TypeScript Types (src/types/qrMenu.ts)

**What:** Define all interfaces for QR menu domain: MenuItem, CartItem, OrderContext, PaymentForm.

**Files:**
- Create: `src/types/qrMenu.ts`

**Details:**
```typescript
export interface MenuItem {
  id: string | number
  name: string
  description: string
  price: number
  total_price?: number
  base_price?: number
  tax_amount?: number
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

export interface CartItem extends MenuItem {
  quantity: number
}

export interface OrderContext {
  type: 'room' | 'table'
  id: string
  displayName: string
  paymentOptions: Array<{ value: string; label: string }>
  isCheckedIn?: boolean
  canOrder?: boolean
  reservationStatus?: string
  eligibilityMessage?: string
}

export interface PaymentForm {
  first_name: string
  last_name: string
  email: string
  phone: string
}
```

**Reference:** Existing MenuItem structure in QRMenu.vue lines 58-72, OrderContext in restaurantTable.ts lines 78-86.

**Verify:** Run `npm run type-check` - should compile without errors.

---

### Step 2: Create Cart Management Composable (src/composables/useQRMenuCart.ts)

**What:** Extract cart state and operations into reusable composable.

**Files:**
- Create: `src/composables/useQRMenuCart.ts`

**Public API:**
```typescript
export function useQRMenuCart() {
  return {
    // State
    cartItems: Ref<CartItem[]>
    // Computed
    subtotal: ComputedRef<number>
    tax: ComputedRef<number>
    serviceCharge: ComputedRef<number>
    cartTotal: ComputedRef<number>
    // Methods
    addToCart: (item: MenuItem, quantity: number) => void
    removeFromCart: (itemId: string | number) => void
    incrementQuantity: (itemId: string | number) => void
    decrementQuantity: (itemId: string | number) => void
    clearCart: () => void
  }
}
```

**Logic:**
- `cartItems`: Reactive array of CartItem
- `subtotal`: `cartItems.reduce((sum, item) => sum + item.price * item.quantity, 0)`
- `tax`: `subtotal * 0.15`
- `serviceCharge`: `subtotal * 0.1`
- `cartTotal`: `subtotal + tax + serviceCharge`
- `addToCart`: Find existing item, increment quantity OR push new item with quantity
- `removeFromCart`: Filter out item by ID
- `incrementQuantity`: Find item, increment quantity
- `decrementQuantity`: Find item, if quantity > 1 decrement, else remove
- `clearCart`: Reset cartItems to empty array

**Reference:** QRMenu.vue lines 213-226 (computed), 285-311 (methods).

**Verify:** Run `npm run type-check`, import composable in a test component.

---

### Step 3: Create Order Context Composable (src/composables/useQRMenuContext.ts)

**What:** Extract QR token resolution and order context state.

**Files:**
- Create: `src/composables/useQRMenuContext.ts`

**Public API:**
```typescript
export function useQRMenuContext() {
  return {
    // State
    qrToken: Ref<string>
    orderContext: Ref<OrderContext | null>
    canOrderRoomService: Ref<boolean>
    eligibilityMessage: Ref<string>
    reservationStatusVal: Ref<string>
    roomNumber: Ref<string>
    guestName: Ref<string>
    guestEmail: Ref<string>
    guestAvatar: Ref<string>
    isLoadingContext: Ref<boolean>
    contextError: Ref<string | null>
    // Methods
    detectOrderContext: (token: string) => Promise<void>
  }
}
```

**Logic:**
- `detectOrderContext`: Call `qrService.resolveQRToken(token)`, parse response
- If context === 'room': Set orderContext.type = 'room', extract room_number, guest info, check is_checked_in for canOrderRoomService
- If context === 'table': Set orderContext.type = 'table', extract table_number/table_name
- **CRITICAL:** Store `hotel_id` from response to localStorage: `localStorage.setItem('hotel_id', result.data.hotel_id)` AND `localStorage.setItem('active_hotel_id', result.data.hotel_id)` for correct tenant isolation
- Handle errors gracefully, set fallback context if QR resolution fails

**Reference:** QRMenu.vue lines 597-705 (detectOrderContext method).

**Verify:** Run `npm run type-check`, test QR token resolution with valid token.

---

### Step 4: Create Order Placement Composable (src/composables/useQRMenuOrder.ts)

**What:** Extract order placement logic for both room charge and Chapa payment flows.

**Files:**
- Create: `src/composables/useQRMenuOrder.ts`

**Public API:**
```typescript
export function useQRMenuOrder() {
  return {
    // State
    isPlacingOrder: Ref<boolean>
    orderNumber: Ref<string>
    estimatedTime: Ref<number>
    showSuccessModal: Ref<boolean>
    // Methods
    placeOrderWithRoomCharge: (
      cartItems: CartItem[], 
      qrToken: string, 
      orderContext: OrderContext,
      router: Router
    ) => Promise<void>
    initializeWalkInPayment: (
      cartItems: CartItem[], 
      qrToken: string, 
      orderContext: OrderContext,
      paymentForm: PaymentForm
    ) => Promise<void>
  }
}
```

**Logic for `placeOrderWithRoomCharge`:**
1. Build orderItems array: `cartItems.map(item => ({ menu_item_id: String(item.id), quantity: item.quantity }))`
2. Call `unifiedOrderService.createOrder({ qr_token, items: orderItems, special_requests: '', payment_type: 'room_charge' })`
3. Extract order ID: `const createdOrderId = orderResponse.data.id || orderResponse.data.order_id`
4. **FIX BUG:** Build complete order data object with items array:
   ```typescript
   const completeOrderData = {
     ...orderResponse.data,
     id: createdOrderId,
     order_id: createdOrderId,
     items: cartItems.map(item => ({
       id: item.id,
       name: item.name,
       description: item.description,
       quantity: item.quantity,
       price: item.price,
       image: item.image,
       total: item.price * item.quantity
     })),
     subtotal: subtotal.value,
     tax: tax.value,
     service_charge: serviceCharge.value,
     total: cartTotal.value,
     status: 'pending',
     payment_status: 'pending',
     payment_type: 'room_charge',
     created_at: new Date().toISOString(),
     updated_at: new Date().toISOString()
   }
   ```
5. Store to localStorage: `localStorage.setItem('pending_order_data', JSON.stringify(completeOrderData))`
6. Store hotel_id: `if (orderResponse.data.hotel_id) localStorage.setItem('hotel_id', orderResponse.data.hotel_id)`
7. Store qr_token: `localStorage.setItem('guest_qr_token', qrToken)`
8. Navigate: `router.push({ name: 'order-status', params: { orderId: createdOrderId }, query: { hotel_id, qr_token, order_number } })`

**Logic for `initializeWalkInPayment`:**
1. Build orderItems array (same as above)
2. Call `unifiedOrderService.initializeWalkInPayment({ table_id: orderContext.id, qr_token, items: orderItems, special_requests: '', first_name, last_name, email, phone })`
3. **FIX BUG:** Build payment data object:
   ```typescript
   const paymentData = {
     payment_id: paymentResponse.payment_id,
     tx_ref: paymentResponse.tx_ref,
     amount: paymentResponse.amount,
     qr_token: qrToken,
     table_number: orderContext.displayName,
     items: cartItems.map(item => ({
       name: item.name,
       quantity: item.quantity,
       price: item.price,
       total: item.price * item.quantity
     })),
     calculation: paymentResponse.calculation
   }
   ```
4. Store to BOTH localStorage AND sessionStorage: 
   ```typescript
   localStorage.setItem('walk_in_payment_data', JSON.stringify(paymentData))
   sessionStorage.setItem('walk_in_payment_data', JSON.stringify(paymentData))
   ```
5. Redirect: `window.location.href = paymentResponse.checkout_url`

**Reference:** QRMenu.vue lines 313-446 (placeOrderWithRoomCharge, handlePlaceOrder for room), lines 469-591 (payment dialog and handlePlaceOrder for table).

**Verify:** Run `npm run type-check`, test order placement flows with console logging.

---

### Step 5: Create QRCartItem Component

**What:** Individual cart item card with quantity controls.

**Files:**
- Create: `src/components/guest/qr-menu/QRCartItem.vue`

**Props:**
- `item: CartItem` (required)
- `canOrder: boolean` (default: true)

**Emits:**
- `increment: void`
- `decrement: void`
- `remove: void`

**Template Structure:**
- Flex container with image (w-16 h-16 rounded-lg), item details (name, description, price), remove button (trash icon)
- Quantity controls row: flex with - button, quantity display, + button (bg-gray-100 rounded-lg px-2 py-1)
- Item total price (bold, right-aligned): `ETB ${(item.price * item.quantity).toFixed(2)}`

**Styling:** Match existing cart item design from QRMenu.vue lines 87-126. Use Tailwind utility classes.

**FIX:** Change item price display from `${{item.price.toFixed(2)}}` to `ETB {{item.price.toFixed(2)}}`. Change item total from `ETB ${{(item.price * item.quantity).toFixed(2)}}` to `ETB {{(item.price * item.quantity).toFixed(2)}}`.

**Reference:** QRMenu.vue lines 87-126.

**Verify:** Import in parent component, pass item prop, verify renders correctly with ETB formatting.

---

### Step 6: Create QRCartSummary Component

**What:** Cart totals display with payment action buttons.

**Files:**
- Create: `src/components/guest/qr-menu/QRCartSummary.vue`

**Props:**
- `subtotal: number`
- `tax: number`
- `serviceCharge: number`
- `total: number`
- `canOrder: boolean`
- `isPlacingOrder: boolean`
- `cartItemsCount: number`

**Emits:**
- `place-order-room-charge: void`
- `open-payment-dialog: void`
- `continue-shopping: void`

**Template Structure:**
- Subtotal row: flex justify-between (label, value with ETB format)
- Total row: flex justify-between with bold text and red total value
- Payment buttons container (flex flex-col gap-2):
  - "Order Now (Pay After Meal)" button: red bg, full width, disabled if !canOrder or isPlacingOrder or cartItemsCount === 0
  - "Pay Now with Chapa" button: yellow bg, full width, same disabled conditions
  - "Continue Shopping" button: gray border, full width
- Helper text: small gray text with payment choice description

**FIX:** Use helper function `formatPrice(price: number): string { return ETB ${price.toFixed(2)} }` for all price displays. Remove any `$` prefixes.

**Reference:** QRMenu.vue lines 149-189.

**Verify:** Import in parent, pass props, click buttons to verify emits work.

---

### Step 7: Create QRCartModal Component

**What:** Cart modal container with items list and checkout section.

**Files:**
- Create: `src/components/guest/qr-menu/QRCartModal.vue`

**Props:**
- `show: boolean`
- `cartItems: CartItem[]`
- `subtotal: number`
- `tax: number`
- `serviceCharge: number`
- `total: number`
- `canOrder: boolean`
- `isPlacingOrder: boolean`

**Emits:**
- `close: void`
- `place-order-room-charge: void`
- `open-payment-dialog: void`
- `increment: (itemId: string | number) => void`
- `decrement: (itemId: string | number) => void`
- `remove: (itemId: string | number) => void`

**Template Structure:**
- `<Teleport to="body">` with fade transition
- Modal backdrop: `fixed inset-0 bg-black/50 z-50` with @click.self="$emit('close')"
- Modal container: `bg-white rounded-xl shadow-2xl max-w-2xl w-full max-h-[90vh] flex flex-col`
- Header: amber gradient bg with title "Your Cart" and close button
- Items container: `flex-1 overflow-y-auto` with v-if for empty state or v-for QRCartItem components
- Footer: QRCartSummary component

**Empty State:** SVG icon, "Your cart is empty" heading, "Add items from menu" subtext, "Continue Shopping" button

**Reference:** QRMenu.vue lines 22-147.

**Verify:** Import in QRMenu.vue, bind props from composables, test open/close, add/remove items.

---

### Step 8: Create QRPaymentForm Component

**What:** Customer details input form for walk-in orders.

**Files:**
- Create: `src/components/guest/qr-menu/QRPaymentForm.vue`

**Props:**
- `modelValue: PaymentForm` (required)
- `orderContext: OrderContext`

**Emits:**
- `update:modelValue: (value: PaymentForm) => void`

**Template Structure:**
- Form fields container with space-y-3
- Each field: label (text-xs text-slate-600), input (border rounded-lg text-sm focus:ring-2 focus:ring-amber-500)
- Fields: first_name, last_name, email, phone
- Use computed getter/setter pattern for v-model two-way binding:
  ```typescript
  const localValue = computed({
    get: () => props.modelValue,
    set: (val) => emit('update:modelValue', val)
  })
  ```

**Validation:** Add HTML5 validation attributes (required, type="email" for email, type="tel" for phone, placeholder text).

**Reference:** QRMenu.vue lines 207-238.

**Verify:** Import in parent, use v-model binding, type in fields and verify parent state updates.

---

### Step 9: Create QRPaymentDialog Component

**What:** Payment method selection dialog with customer form and order summary.

**Files:**
- Create: `src/components/guest/qr-menu/QRPaymentDialog.vue`

**Props:**
- `show: boolean`
- `cartItems: CartItem[]`
- `subtotal: number`
- `tax: number`
- `serviceCharge: number`
- `total: number`
- `orderContext: OrderContext | null`
- `paymentForm: PaymentForm`
- `roomNumber: string`
- `guestName: string`
- `isPlacingOrder: boolean`

**Emits:**
- `close: void`
- `proceed: void`
- `update:payment-form: (value: PaymentForm) => void`

**Template Structure:**
- Teleport modal with amber gradient header (title "Payment Confirmation", subtitle based on orderContext.type)
- Scrollable body:
  - QRPaymentForm component (v-if orderContext.type === 'table')
  - Order summary section (room, items count, guest name)
  - Items list (v-for cartItems)
  - Price breakdown (subtotal, tax, service charge, total with ETB format)
  - Notices (walk-in order info, secure payment via Chapa)
- Footer: Cancel button (gray border) and Pay Now button (amber bg) with loading state

**FIX:** Ensure all price displays use helper function returning `ETB ${price.toFixed(2)}`.

**Reference:** QRMenu.vue lines 192-285.

**Verify:** Import in QRMenu.vue, test with both room and table orderContext types.

---

### Step 10: Create QROrderSuccessModal Component

**What:** Order success confirmation modal with order details.

**Files:**
- Create: `src/components/guest/qr-menu/QROrderSuccessModal.vue`

**Props:**
- `show: boolean`
- `orderNumber: string`
- `roomNumber: string`
- `estimatedTime: number`
- `total: number`

**Emits:**
- `close: void`
- `track-order: void`
- `back-to-menu: void`

**Template Structure:**
- Teleport modal centered
- Animated green checkmark icon (w-20 h-20 bg-gradient-to-br from-green-400 to-green-600 rounded-full with bounce animation)
- Success heading "Order Placed Successfully!"
- Description text
- Order details card (bg-amber-50 rounded-lg p-4):
  - Order Number: #{{orderNumber}}
  - Room Number: {{roomNumber}}
  - Estimated Time: {{estimatedTime}} mins
  - Total Amount: **ETB {{total.toFixed(2)}}** (amber-600 color)
- Buttons:
  - "Track Order" button: amber gradient bg with shadow
  - "Back to Menu" button: gray border

**FIX:** Format total amount as `ETB {{total.toFixed(2)}}` (not `$` or `ETB $`).

**Reference:** QRMenu.vue lines 288-346.

**Verify:** Import in QRMenu.vue, trigger after order placement, verify order details display correctly.

---

### Step 11: Refactor QRMenu.vue - Replace Script Logic

**What:** Import and use composables, remove duplicate state/logic.

**Files:**
- Modify: `src/views/guest/QRMenu.vue`

**Changes:**
1. Import composables:
   ```typescript
   import { useQRMenuCart } from '@/composables/useQRMenuCart'
   import { useQRMenuContext } from '@/composables/useQRMenuContext'
   import { useQRMenuOrder } from '@/composables/useQRMenuOrder'
   ```
2. Remove duplicate interfaces (lines 175-186) - now in src/types/qrMenu.ts
3. Replace cart state/computed with composable:
   ```typescript
   const {
     cartItems,
     subtotal,
     tax,
     serviceCharge,
     cartTotal,
     addToCart,
     removeFromCart,
     incrementQuantity,
     decrementQuantity,
     clearCart
   } = useQRMenuCart()
   ```
4. Replace order context state with composable:
   ```typescript
   const {
     qrToken,
     orderContext,
     canOrderRoomService,
     eligibilityMessage,
     reservationStatusVal,
     roomNumber,
     guestName,
     guestEmail,
     guestAvatar,
     isLoadingContext,
     contextError,
     detectOrderContext
   } = useQRMenuContext()
   ```
5. Replace order placement logic with composable:
   ```typescript
   const {
     isPlacingOrder,
     orderNumber,
     estimatedTime,
     showSuccessModal,
     placeOrderWithRoomCharge,
     initializeWalkInPayment
   } = useQRMenuOrder()
   ```
6. Keep modal visibility state (showCartModal, showPaymentDialog refs)
7. Update event handlers to call composable methods:
   - `handleAddToCart` -> `addToCart(item, quantity)`
   - `placeOrderWithRoomCharge` button -> call composable method with cart items, qrToken, orderContext, router
   - `proceedToPayment` -> call `initializeWalkInPayment` with form data
8. Update `onMounted` to call `detectOrderContext(qrToken.value)` if qrToken exists

**Expected Line Reduction:** From ~717 lines to ~250 lines (removing ~467 lines of duplicated logic).

**Verify:** Run `npm run type-check`, start dev server, test basic flow.

---

### Step 12: Refactor QRMenu.vue - Replace Template with Components

**What:** Replace inline modals with component imports.

**Files:**
- Modify: `src/views/guest/QRMenu.vue` template section

**Changes:**
1. Import components:
   ```typescript
   import QRCartModal from '@/components/guest/qr-menu/QRCartModal.vue'
   import QRPaymentDialog from '@/components/guest/qr-menu/QRPaymentDialog.vue'
   import QROrderSuccessModal from '@/components/guest/qr-menu/QROrderSuccessModal.vue'
   ```
2. Replace cart modal (lines 22-147) with:
   ```vue
   <QRCartModal
     :show="showCartModal"
     :cart-items="cartItems"
     :subtotal="subtotal"
     :tax="tax"
     :service-charge="serviceCharge"
     :total="cartTotal"
     :can-order="canOrderRoomService"
     :is-placing-order="isPlacingOrder"
     @close="showCartModal = false"
     @place-order-room-charge="placeOrderWithRoomCharge(cartItems, qrToken, orderContext, router)"
     @open-payment-dialog="openPaymentDialog"
     @increment="incrementQuantity"
     @decrement="decrementQuantity"
     @remove="removeFromCart"
   />
   ```
3. Replace payment dialog (lines 192-285) with:
   ```vue
   <QRPaymentDialog
     :show="showPaymentDialog"
     :cart-items="cartItems"
     :subtotal="subtotal"
     :tax="tax"
     :service-charge="serviceCharge"
     :total="cartTotal"
     :order-context="orderContext"
     v-model:payment-form="paymentForm"
     :room-number="roomNumber"
     :guest-name="guestName"
     :is-placing-order="isPlacingOrder"
     @close="closePaymentDialog"
     @proceed="proceedToPayment"
   />
   ```
4. Replace success modal (lines 288-346) with:
   ```vue
   <QROrderSuccessModal
     :show="showSuccessModal"
     :order-number="orderNumber"
     :room-number="roomNumber"
     :estimated-time="estimatedTime"
     :total="cartTotal"
     @close="showSuccessModal = false"
     @track-order="handleTrackOrder"
     @back-to-menu="handleBackToMenu"
   />
   ```

**Expected Line Reduction:** From ~250 lines to ~200 lines (removing ~50 lines of inline template code).

**Verify:** Start dev server, test all modals open/close correctly, verify props/emits work.

---

### Step 13: Fix Order ID Tracking Bug

**What:** Ensure order ID is correctly stored and passed to OrderStatusPage after order placement.

**Files:**
- Verify fix in: `src/composables/useQRMenuOrder.ts` (placeOrderWithRoomCharge method)

**Bug Details:**
- **Issue:** After room charge order placement, OrderStatusPage cannot find order ID. Console error: `[OrderPaymentSuccess] No order ID found after checking all sources!`
- **Root Cause:** `pending_order_data` in localStorage is missing `order_id` field. OrderStatusPage's useOrderStatus composable checks for `orderData.id` or `orderData.order_id` but doesn't find it.
- **Current Code:** QRMenu.vue lines 313-375 stores incomplete order data to `pending_order_data`

**Fix Implementation (already in Step 4):**
1. After `unifiedOrderService.createOrder` succeeds, extract order ID: `const createdOrderId = orderResponse.data.id || orderResponse.data.order_id`
2. Build complete order data object with `id` AND `order_id` fields:
   ```typescript
   const completeOrderData = {
     ...orderResponse.data,
     id: createdOrderId,
     order_id: createdOrderId,
     items: cartItems.map(item => ({
       id: item.id,
       name: item.name,
       description: item.description,
       quantity: item.quantity,
       price: item.price,
       image: item.image,
       total: item.price * item.quantity
     })),
     subtotal: subtotal.value,
     tax: tax.value,
     service_charge: serviceCharge.value,
     total: cartTotal.value,
     status: 'pending',
     payment_status: 'pending',
     payment_type: 'room_charge',
     created_at: new Date().toISOString(),
     updated_at: new Date().toISOString()
   }
   ```
3. Store to localStorage: `localStorage.setItem('pending_order_data', JSON.stringify(completeOrderData))`
4. Verify stored: `console.log('[QRMenu] Verified stored data:', localStorage.getItem('pending_order_data') ? 'Success ' : 'Failed ❌')`
5. Navigate with orderId in params: `router.push({ name: 'order-status', params: { orderId: createdOrderId }, query: { hotel_id, qr_token, order_number } })`

**Verification Test:**
1. Place room charge order
2. Check browser console: Should see `[QRMenu] Created order ID: <id>` and `[QRMenu] Verified stored data: Success `
3. OrderStatusPage should load without errors
4. Check OrderStatusPage console: Should see `[useOrderStatus]  Using stored order data from order creation` (not `[OrderPaymentSuccess] No order ID found`)

**Reference:** QRMenu.vue lines 313-375, OrderPaymentSuccessPage.vue lines 320-391, useOrderStatus.ts lines 108-140.

**Verify:** Run complete order flow, check console logs, verify no "No order ID found" errors.

---

### Step 14: Fix Chapa Payment Bug (tx_ref not found)

**What:** Ensure payment data persists through Chapa redirect by storing to both localStorage and sessionStorage.

**Files:**
- Verify fix in: `src/composables/useQRMenuOrder.ts` (initializeWalkInPayment method)

**Bug Details:**
- **Issue:** After Chapa redirect, OrderPaymentSuccessPage cannot find `tx_ref` or payment data. Console error: `[OrderPaymentSuccess] No tx_ref found! Cannot verify payment.` and `sessionStorage keys: []`
- **Root Cause:** Payment data stored only to sessionStorage, which gets cleared during Chapa external redirect
- **Current Code:** QRMenu.vue lines 538-565 stores to sessionStorage only

**Fix Implementation (already in Step 4):**
1. After `unifiedOrderService.initializeWalkInPayment` succeeds, build payment data object:
   ```typescript
   const paymentData = {
     payment_id: paymentResponse.payment_id,
     tx_ref: paymentResponse.tx_ref,
     amount: paymentResponse.amount,
     qr_token: qrToken,
     table_number: orderContext.displayName,
     items: cartItems.map(item => ({
       name: item.name,
       quantity: item.quantity,
       price: item.price,
       total: item.price * item.quantity
     })),
     calculation: paymentResponse.calculation
   }
   ```
2. Store to BOTH localStorage AND sessionStorage:
   ```typescript
   localStorage.setItem('walk_in_payment_data', JSON.stringify(paymentData))
   sessionStorage.setItem('walk_in_payment_data', JSON.stringify(paymentData))
   console.log('[QRMenu] Stored payment data before redirect:', paymentData)
   console.log('[QRMenu] Redirecting to Chapa:', paymentResponse.checkout_url)
   ```
3. Redirect: `window.location.href = paymentResponse.checkout_url`

**OrderPaymentSuccessPage Fix (already implemented):**
- Lines 173-229: Check BOTH localStorage AND sessionStorage:
  ```typescript
  const walkInData = localStorage.getItem('walk_in_payment_data') || sessionStorage.getItem('walk_in_payment_data')
  ```
- This ensures payment data is found even if sessionStorage was cleared

**Verification Test:**
1. Add items to cart for walk-in order
2. Click "Pay with Chapa", fill form, click "Pay Now"
3. Check browser console before redirect: Should see `[QRMenu] Stored payment data before redirect: {...}` with tx_ref
4. Check localStorage and sessionStorage: Both should contain `walk_in_payment_data`
5. After Chapa redirect (or simulate by navigating to `/order/payment/success?tx_ref=TEST123`):
   - Check OrderPaymentSuccessPage console: Should see `[OrderPaymentSuccess] walk_in_payment_data (localStorage): {...}` (not null)
   - Should NOT see `[OrderPaymentSuccess] No tx_ref found!` error

**Reference:** QRMenu.vue lines 538-565, OrderPaymentSuccessPage.vue lines 173-229.

**Verify:** Test walk-in payment flow with Chapa redirect, verify tx_ref is found.

---

### Step 15: Fix Currency Formatting Throughout

**What:** Update all price displays to use consistent `ETB XX.XX` format (remove `$` and `ETB $` mixed formats).

**Files:**
- Verify in all components: QRCartItem.vue, QRCartSummary.vue, QRCartModal.vue, QRPaymentDialog.vue, QROrderSuccessModal.vue, QRMenu.vue

**Current Issues:**
- QRMenu.vue line 311: `formatPrice` returns `$${price.toFixed(2)}`
- Cart modal line 120: Item price shows `${{item.price.toFixed(2)}}`
- Cart modal line 128: Item total shows `ETB ${{(item.price * item.quantity).toFixed(2)}}`
- User requirement: "Fix the currency formatting so it consistently displays ETB instead of mixing '$' and 'ETB $'"

**Fix Implementation:**
1. Update `formatPrice` helper function (in QRMenu.vue or create in composable):
   ```typescript
   const formatPrice = (price: number): string => {
     return `ETB ${price.toFixed(2)}`
   }
   ```
2. In QRCartItem.vue:
   - Change `${{item.price.toFixed(2)}}` to `{{formatPrice(item.price)}}`
   - Change `ETB ${{(item.price * item.quantity).toFixed(2)}}` to `{{formatPrice(item.price * item.quantity)}}`
3. In QRCartSummary.vue:
   - Use `{{formatPrice(subtotal)}}`, `{{formatPrice(tax)}}`, etc.
4. In QRPaymentDialog.vue:
   - Use `{{formatPrice(subtotal)}}` for all price displays in order summary
5. In QROrderSuccessModal.vue:
   - Use `{{formatPrice(total)}}` for total amount display
6. Search entire codebase for remaining `$` or `ETB $` patterns, replace with `formatPrice` helper

**Verification Test:**
1. Start dev server, add items to cart
2. Open cart modal, inspect all prices: item price, item total, subtotal, tax, service charge, total
3. Open payment dialog, inspect all prices in order summary
4. Complete order, inspect success modal total amount
5. ALL prices should display as `ETB XX.XX` format (e.g., `ETB 540.00`, `ETB 120.00`)
6. NO prices should show `$XX.XX` or `ETB $XX.XX`

**Reference:** User message: "Fix the currency formatting so it consistently displays ETB instead of mixing '$' and 'ETB $'."

**Verify:** Visual inspection of all price displays in all components.

---

### Step 16: Final Integration Testing

**What:** Comprehensive end-to-end testing of refactored QRMenu.vue with all components and bug fixes.

**Test Scenarios:**

#### Test 1: Room Charge Order Flow (Bug Fix Verification)
1. Navigate to QR menu with room QR token
2. Verify guest info displays (name, email, room number)
3. Add 3 items to cart with different quantities
4. Click "View Cart", verify:
   - All items display with correct quantities
   - All prices show `ETB XX.XX` format (no `$` or `ETB $`)
   - Subtotal, tax, service charge, total calculated correctly
5. Click "Order Now (Pay After Meal)"
6. **CRITICAL CHECK:** Browser console should show:
   - `[QRMenu] Created order ID: <id>`
   - `[QRMenu] Storing complete order data: {...}` with `id` and `order_id` fields
   - `[QRMenu] Verified stored data: Success `
   - `[QRMenu] Redirecting to order status with ID: <id>`
7. Verify redirect to OrderStatusPage with orderId in URL
8. **CRITICAL CHECK:** OrderStatusPage should load without errors, console should show:
   - `[useOrderStatus]  Using stored order data from order creation`
   - NOT `[OrderPaymentSuccess] No order ID found after checking all sources!`
9. Verify order status page displays order number, items, status

#### Test 2: Walk-in Chapa Payment Flow (Bug Fix Verification)
1. Navigate to QR menu with table QR token
2. Add 2 items to cart
3. Click "View Cart", verify ETB formatting
4. Click "Pay with Chapa"
5. Fill customer form: first name, last name, email (valid format), phone (10+ digits)
6. Click "Pay Now"
7. **CRITICAL CHECK:** Browser console should show:
   - `[QRMenu] Stored payment data before redirect: {...}` with `tx_ref` field
   - `[QRMenu] Redirecting to Chapa: <checkout_url>`
8. **CRITICAL CHECK:** Open browser DevTools, check Application > Local Storage and Session Storage:
   - Both should contain `walk_in_payment_data` with same JSON data
9. Simulate Chapa return: Navigate to `/order/payment/success?tx_ref=<tx_ref_from_storage>`
10. **CRITICAL CHECK:** OrderPaymentSuccessPage should load without errors, console should show:
    - `[OrderPaymentSuccess] walk_in_payment_data (localStorage): {...}`
    - `[OrderPaymentSuccess] Using tx_ref from localStorage: <tx_ref>`
    - NOT `[OrderPaymentSuccess] No tx_ref found! Cannot verify payment.`
11. Click "Track My Order", verify navigation to OrderStatusPage with order ID

#### Test 3: Currency Formatting Verification
1. Add items to cart
2. Open cart modal, inspect all price displays:
   - Item price: Should be `ETB XX.XX` (not `$XX.XX`)
   - Item total: Should be `ETB XX.XX` (not `ETB $XX.XX`)
   - Subtotal: Should be `ETB XX.XX`
   - Tax: Should be `ETB XX.XX`
   - Service Charge: Should be `ETB XX.XX`
   - Total: Should be `ETB XX.XX`
3. Open payment dialog, inspect order summary prices (all ETB format)
4. Complete order, inspect success modal total (ETB format)
5. **PASS CRITERIA:** All prices use consistent `ETB XX.XX` format, no `$` or `ETB $`

#### Test 4: Cart Operations
1. Add item A (quantity 1)
2. Click cart item's + button 3 times, verify quantity = 4
3. Click cart item's - button 2 times, verify quantity = 2
4. Add item B (quantity 2)
5. Click item A's trash icon, verify item A removed, item B remains
6. Verify cart total updates correctly after each operation
7. Remove last item, verify empty cart state displays

#### Test 5: Order Context Detection
1. Test with room QR token: Verify room context, guest info, "Order Now (Pay After Meal)" button enabled
2. Test with table QR token: Verify table context, "Pay with Chapa" flow available
3. Test with non-checked-in room: Verify eligibility message displays, ordering locked notice shown

#### Test 6: TypeScript Compilation
1. Run `npm run type-check`
2. **PASS CRITERIA:** No TypeScript errors, all types resolve correctly

**Verification Commands:**
```bash
npm run type-check
npm run dev
```

**Expected Outcomes:**
- QRMenu.vue reduced from ~717 lines to ~200 lines
- All existing functionality preserved
- Order ID tracking bug fixed (no "No order ID found" errors)
- Chapa payment bug fixed (no "No tx_ref found" errors)
- Currency formatting consistent (all `ETB XX.XX`, no `$` or `ETB $`)
- TypeScript compilation succeeds
- Dev server runs without runtime errors

**Verify:** Execute all test scenarios, check console logs, verify no errors.

---

## Summary of Changes

### New Files Created (9 files)
1. `src/types/qrMenu.ts` - TypeScript interfaces
2. `src/composables/useQRMenuCart.ts` - Cart state and operations
3. `src/composables/useQRMenuContext.ts` - QR token resolution and order context
4. `src/composables/useQRMenuOrder.ts` - Order placement logic
5. `src/components/guest/qr-menu/QRCartItem.vue` - Cart item component
6. `src/components/guest/qr-menu/QRCartSummary.vue` - Cart summary component
7. `src/components/guest/qr-menu/QRCartModal.vue` - Cart modal container
8. `src/components/guest/qr-menu/QRPaymentForm.vue` - Payment form component
9. `src/components/guest/qr-menu/QRPaymentDialog.vue` - Payment dialog modal
10. `src/components/guest/qr-menu/QROrderSuccessModal.vue` - Success modal

### Files Modified (1 file)
1. `src/views/guest/QRMenu.vue` - Refactored from ~717 lines to ~200 lines

### Bugs Fixed
1. **Order ID tracking:** Order data now includes `id` and `order_id` fields, stored to localStorage for OrderStatusPage
2. **Chapa payment tx_ref:** Payment data stored to both localStorage and sessionStorage for persistence through redirect
3. **Currency formatting:** Consistent `ETB XX.XX` format throughout (removed `$` and `ETB $` mixed formats)

### Key Patterns Followed
- Vue 3 Composition API with `<script setup lang="ts">`
- TypeScript interfaces in `src/types/`
- Composables return reactive refs and methods
- Components use `defineProps` and `defineEmits`
- Teleport for modals to `body`
- Tailwind utility classes for styling
- localStorage/sessionStorage for data persistence
- useLanguageStore for i18n

### Verification Steps
1. TypeScript compilation: `npm run type-check`
2. Dev server: `npm run dev`
3. Manual testing: All test scenarios in Step 16
4. Console log verification: Check for bug fix success messages
5. Visual inspection: Verify ETB currency formatting

---

## Migration Notes

### Circular Dependency Avoidance
- Types defined first in `src/types/qrMenu.ts`
- Composables import types (no circular deps)
- Components import composables and types
- QRMenu.vue imports everything

### Event Emits Pattern
- Child components emit simple events (increment, decrement, remove)
- Parent (QRMenu.vue) handles events by calling composable methods
- No business logic in component emits, only UI actions

### Prop Drilling
- Cart state passed from composables to modals via props
- OrderContext passed to payment dialog for conditional rendering
- PaymentForm uses v-model pattern for two-way binding

### State Management
- No Pinia store needed (cart is local to QR menu page)
- Composables provide reactive state shared across QRMenu.vue
- localStorage/sessionStorage for persistence across page navigation

### Styling Preservation
- All Tailwind classes copied exactly from original QRMenu.vue
- Transitions, animations, responsive classes maintained
- Color scheme preserved (amber primary, red for totals, green for success)

---

## Tricky Migration Notes

### tx_ref / Order ID Bug Root Cause
The original bug occurs because:
1. **Room Charge Orders:** API returns order data with `order_id` field, but QRMenu.vue stored incomplete data to localStorage without extracting the `id`/`order_id` field. OrderStatusPage's useOrderStatus composable checks `pending_order_data` for order ID but doesn't find it because the stored object was missing that field.
2. **Chapa Payments:** QRMenu.vue stored payment data to sessionStorage, but Chapa redirect is an external navigation (`window.location.href`) which clears sessionStorage in most browsers. OrderPaymentSuccessPage checked sessionStorage and found nothing.

**Fix:** Store complete data with explicit ID fields to localStorage (persists through navigation) and use both localStorage + sessionStorage for Chapa payments (belt-and-suspenders approach).

### Order Data Structure for OrderStatusPage
OrderStatusPage's useOrderStatus composable expects order data with this structure:
```typescript
{
  id: string,              // REQUIRED - primary order ID
  order_id: string,        // Alternative ID field
  order_number: string,
  hotel_id: string,        // REQUIRED for WebSocket channel
  status: 'pending' | 'preparing' | 'ready' | 'served' | 'cancelled',
  items: Array<{          // REQUIRED - for order details display
    id: string,
    name: string,
    quantity: number,
    price: number,
    total: number
  }>,
  subtotal: number,
  tax: number,
  service_charge: number,
  total: number,
  // ... other fields
}
```

The original QRMenu.vue only stored API response data, which didn't include the items array or explicit id field, causing the useOrderStatus composable to fail loading the order.

### Payment Data Structure for OrderPaymentSuccessPage
OrderPaymentSuccessPage expects payment data with this structure:
```typescript
{
  tx_ref: string,          // REQUIRED - Chapa transaction reference
  payment_id: string,
  amount: number,
  qr_token: string,
  table_number: string,
  items: Array<{
    name: string,
    quantity: number,
    price: number,
    total: number
  }>,
  calculation: {
    subtotal: number,
    tax: number,
    service_charge: number,
    total: number
  }
}
```

Stored to key `walk_in_payment_data` in both localStorage and sessionStorage.

### formatPrice Helper Location
Options:
1. Define in each component (duplication, not recommended)
2. Define in useQRMenuCart composable (recommended - keeps currency logic centralized)
3. Define as utility function in `src/utils/currency.ts` (good for larger apps)

**Recommendation:** Add to useQRMenuCart composable as exported helper:
```typescript
export function useQRMenuCart() {
  // ... existing code
  
  const formatPrice = (price: number): string => {
    return `ETB ${price.toFixed(2)}`
  }
  
  return {
    // ... existing returns
    formatPrice
  }
}
```

This way all components can import and use the same formatter, ensuring consistency.
