# QR Menu Payment Integration - Implementation Plan

**Date:** August 3, 2026  
**Status:** 🔄 **IN PLANNING**  
**Priority:** **HIGH** - Security & Revenue Protection

---

## 🎯 OBJECTIVE

Integrate Chapa payment gateway into the QR menu ordering system to ensure **payment before order placement**. Currently, guests can place orders without paying, which is a security and revenue risk.

---

## 📋 CURRENT FLOW (WITHOUT PAYMENT)

```
1. Guest scans QR code
2. Guest browses menu
3. Guest adds items to cart
4. Guest clicks "Place Order"
5. ❌ Order created immediately (NO PAYMENT)
6. Kitchen receives order
```

###  **PROBLEM:** No payment validation before order creation!

---

## ✅ NEW FLOW (WITH PAYMENT)

```
1. Guest scans QR code
2. Guest browses menu
3. Guest adds items to cart
4. Guest clicks "Place Order"
5. 🔒 PAYMENT CONFIRMATION DIALOG appears
6. Guest reviews order details
7. Guest clicks "Pay Now"
8. ✅ Initialize Payment (Chapa API)
9. Redirect to Chapa checkout page
10. Guest completes payment on Chapa
11. ✅ Verify Payment (Backend)
12. ✅ Create Order (ONLY after payment verified)
13. Kitchen receives paid order
14. Guest sees success page
```

---

## 🔧 IMPLEMENTATION STEPS

### **BACKEND CHANGES**

#### 1. Create Order Payment Controller
**File:** `server/app/Http/Controllers/Api/OrderPaymentController.php`

**Responsibilities:**
- Initialize payment for orders
- Calculate order total (items + tax + service charge)
- Create payment record
- Verify payment with Chapa
- Create order ONLY after payment verification

**Endpoints:**
```php
POST   /api/order-payments/initialize     // Initialize payment for order
POST   /api/order-payments/complete/{tx_ref}  // Complete order after payment
GET    /api/order-payments/{tx_ref}       // Get payment status
```

#### 2. Update Payment Model
**File:** `server/app/Models/Payment.php`

**Add:**
- `order_id` field (nullable, for linkage)
- Metadata support for order items

#### 3. Update Order Model
**File:** `server/app/Models/Order.php`

**Add:**
- `payment_id` field (nullable initially)
- Relationship to Payment model
- Status: 'pending_payment' → 'paid' → 'confirmed'

#### 4. Database Migration
**File:** `server/database/migrations/2026_08_03_add_payment_to_orders.php`

```php
Schema::table('orders', function (Blueprint $table) {
    $table->uuid('payment_id')->nullable()->after('id');
    $table->foreign('payment_id')->references('id')->on('payments')->onDelete('set null');
});

Schema::table('payments', function (Blueprint $table) {
    $table->uuid('order_id')->nullable()->after('id');
    $table->foreign('order_id')->references('id')->on('orders')->onDelete('set null');
});
```

---

### **FRONTEND CHANGES**

#### 1. Update QRMenu.vue
**File:** `Client2/vue-project/src/views/guest/QRMenu.vue`

**Changes:**
- Add payment confirmation dialog (similar to BookingModal)
- Show order summary with breakdown:
  - Subtotal
  - Tax (15%)
  - Service Charge (10%)
  - **Total Amount**
- Replace `handlePlaceOrder()` with `handleInitiatePayment()`
- Redirect to payment checkout page
- Store order data in sessionStorage

**New Flow:**
```javascript
handlePlaceOrder()
  ↓
showPaymentConfirmation()
  ↓
handleProceedToPayment()
  ↓
initializePaymentAPI()
  ↓
redirectToChapa()
  ↓
returnFromChapa()
  ↓
verifyPayment()
  ↓
createOrder()
  ↓
showSuccessModal()
```

#### 2. Create OrderCheckout Page
**File:** `Client2/vue-project/src/views/payment/OrderCheckoutPage.vue`

**Purpose:**
- Display Chapa iframe for payment
- Handle payment success/failure
- Redirect to success page

#### 3. Create OrderPaymentSuccess Page
**File:** `Client2/vue-project/src/views/payment/OrderPaymentSuccessPage.vue`

**Purpose:**
- Show order confirmation
- Display order details
- Show receipt
- "Track Order" button

#### 4. Update Router
**File:** `Client2/vue-project/src/router/index.ts`

```typescript
{
  path: '/order/checkout',
  name: 'order-checkout',
  component: () => import('@/views/payment/OrderCheckoutPage.vue')
},
{
  path: '/order/payment/success',
  name: 'order-payment-success',
  component: () => import('@/views/payment/OrderPaymentSuccessPage.vue')
}
```

---

## 💰 PAYMENT CALCULATION

### Order Total Formula:

```
Subtotal = Σ(item.price × item.quantity)
Tax = Subtotal × 0.15  (15%)
Service Charge = Subtotal × 0.10  (10%)
Total = Subtotal + Tax + Service Charge
```

### Example:
```
Spring Rolls: $7.99 × 1 = $7.99
Pizza: $15.99 × 2 = $31.98
─────────────────────────────
Subtotal: $39.97
Tax (15%): $5.996 → $6.00
Service Charge (10%): $3.997 → $4.00
─────────────────────────────
TOTAL: $49.97
```

---

## 🔐 SECURITY CONSIDERATIONS

### 1. **Order Creation MUST Wait for Payment**
```php
// ❌ WRONG (Current)
$order = Order::create([...]);

// ✅ CORRECT (New)
$payment = Payment::where('tx_ref', $txRef)->first();
if ($payment->status !== 'verified') {
    throw new Exception('Payment not verified');
}
$order = Order::create([
    'payment_id' => $payment->id,
    ...
]);
```

### 2. **Prevent Duplicate Orders**
- Store payment_id with order
- Check if order already exists for payment before creating

### 3. **Handle Payment Failures**
- If payment fails, do NOT create order
- Allow user to retry payment
- Clean up abandoned payments after 24 hours

### 4. **Validate Payment Amount**
- Backend MUST recalculate total
- Never trust frontend amount
- Compare with payment amount from Chapa

---

## 📝 PAYMENT CONFIRMATION DIALOG DESIGN

### Layout (Similar to Booking Modal):

```
┌─────────────────────────────────────┐
│  💳 Payment Confirmation            │
│  Review your order before payment   │
├─────────────────────────────────────┤
│                                     │
│  Order Summary                      │
│  Room: 302                          │
│  QR Token: abc123                   │
│                                     │
│  Items:                             │
│  • Spring Rolls × 1    $7.99       │
│  • Margherita Pizza × 2  $31.98    │
│                                     │
│  Special Requests:                  │
│  ┌───────────────────────────────┐ │
│  │ Extra napkins please          │ │
│  └───────────────────────────────┘ │
│                                     │
│  ─────────────────────────────────  │
│  Price Breakdown                    │
│  Subtotal:          $39.97          │
│  Tax (15%):         $6.00           │
│  Service (10%):     $4.00           │
│  ─────────────────────────────────  │
│  Total Amount:      $49.97          │
│                                     │
│  ✓ Secure payment via Chapa        │
│                                     │
├─────────────────────────────────────┤
│  [Cancel]     [💳 Pay Now]         │
└─────────────────────────────────────┘
```

---

## 🗂️ FILES TO CREATE/MODIFY

### **Backend Files:**

1. ✅ **CREATE** `server/app/Http/Controllers/Api/OrderPaymentController.php`
2. ✅ **MODIFY** `server/app/Models/Payment.php` (add order_id)
3. ✅ **MODIFY** `server/app/Models/Order.php` (add payment_id)
4. ✅ **CREATE** `server/database/migrations/2026_08_03_add_payment_to_orders.php`
5. ✅ **MODIFY** `server/routes/api.php` (add payment routes)
6. ✅ **MODIFY** `server/app/Services/PaymentService.php` (add order payment methods)

### **Frontend Files:**

1. ✅ **MODIFY** `Client2/vue-project/src/views/guest/QRMenu.vue` (add payment flow)
2. ✅ **CREATE** `Client2/vue-project/src/views/payment/OrderCheckoutPage.vue`
3. ✅ **CREATE** `Client2/vue-project/src/views/payment/OrderPaymentSuccessPage.vue`
4. ✅ **MODIFY** `Client2/vue-project/src/router/index.ts` (add payment routes)
5. ✅ **CREATE** `Client2/vue-project/src/services/orderPaymentService.ts`

---

## 🧪 TESTING CHECKLIST

### Payment Flow:
- [ ] Guest can add items to cart
- [ ] Payment dialog appears with correct total
- [ ] Payment initializes successfully
- [ ] Chapa checkout loads correctly
- [ ] Payment success redirects properly
- [ ] Order created ONLY after payment verified
- [ ] Kitchen receives paid order
- [ ] Guest sees success confirmation

### Error Handling:
- [ ] Payment failure shows error message
- [ ] User can retry failed payment
- [ ] Duplicate payment prevention works
- [ ] Invalid QR token handled
- [ ] Validation errors display properly

### Edge Cases:
- [ ] Empty cart prevented
- [ ] Network timeout handled
- [ ] Browser back button during payment
- [ ] Payment window closed by user
- [ ] Multiple simultaneous payments

---

## 📊 DATABASE SCHEMA CHANGES

### **payments** table (existing):
```sql
ALTER TABLE payments ADD COLUMN order_id UUID NULL;
ALTER TABLE payments ADD FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL;
```

### **orders** table (existing):
```sql
ALTER TABLE orders ADD COLUMN payment_id UUID NULL;
ALTER TABLE orders ADD FOREIGN KEY (payment_id) REFERENCES payments(id) ON DELETE SET NULL;
ALTER TABLE orders ADD COLUMN payment_status ENUM('pending', 'paid', 'failed') DEFAULT 'pending';
```

---

## 🚀 IMPLEMENTATION PRIORITY

### Phase 1: Backend Foundation ⚡ **URGENT**
1. Create OrderPaymentController
2. Add payment_id to orders table
3. Update Payment model for orders
4. Test payment initialization API

### Phase 2: Frontend Integration
1. Add payment confirmation dialog to QRMenu
2. Create order checkout page
3. Create order payment success page
4. Test complete flow

### Phase 3: Testing & Refinement
1. End-to-end testing
2. Error handling verification
3. UI/UX improvements
4. Performance optimization

---

## ⚠️ CRITICAL NOTES

1. **NEVER create order before payment verification**
2. **ALWAYS recalculate totals on backend**
3. **VALIDATE payment amount matches order total**
4. **LOG all payment attempts for audit**
5. **PREVENT duplicate orders for same payment**

---

**Next Step:** Implement Phase 1 (Backend Foundation)

**Estimated Time:** 4-6 hours for complete implementation

**Risk Level:** 🔴 HIGH (Financial transactions - must be correct)

---

