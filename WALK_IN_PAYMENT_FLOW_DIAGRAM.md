# Walk-In Order Payment Flow - Visual Diagram

## 🎯 Complete Payment Flow

```
┌─────────────────────────────────────────────────────────────────────┐
│                        CUSTOMER JOURNEY                              │
└─────────────────────────────────────────────────────────────────────┘

┌──────────────┐
│   Customer   │
│  Sits at     │
│   Table 2    │
└──────┬───────┘
       │
       │ 📱 Scans QR Code on table
       ▼
┌──────────────────────────────────────────────────────────────────────┐
│  Frontend: QRMenu.vue                                                │
│  • Resolves QR token: "table-2-GveD6NRGFa"                          │
│  • Loads menu items                                                  │
│  • Context: { type: 'table', id: 'uuid', displayName: 'Table 2' }  │
└──────┬───────────────────────────────────────────────────────────────┘
       │
       │ 🍕 Adds items to cart
       ▼
┌──────────────────────────────────────────────────────────────────────┐
│  Cart Summary                                                        │
│  • Item 1: Margherita Pizza x2 = $30.00                            │
│  • Item 2: Caesar Salad x1 = $12.00                                │
│  ───────────────────────────────────────────────                    │
│  Subtotal:              $42.00                                       │
│  Tax (15%):             $6.30                                        │
│  Service Charge (10%):  $4.20                                        │
│  ───────────────────────────────────────────────                    │
│  TOTAL:                 $52.50                                       │
└──────┬───────────────────────────────────────────────────────────────┘
       │
       │ 💳 Clicks "Proceed to Payment"
       ▼
┌──────────────────────────────────────────────────────────────────────┐
│  Payment Form Dialog (QRMenu.vue)                                   │
│  ┌────────────────────────────────────────────────────────┐        │
│  │  💳 Payment Confirmation                               │        │
│  │                                                         │        │
│  │  First Name:    [John________________]                 │        │
│  │  Last Name:     [Doe_________________]                 │        │
│  │  Email:         [john@example.com____]                 │        │
│  │  Phone:         [+251912345678_______]                 │        │
│  │                                                         │        │
│  │  Order Summary:                                        │        │
│  │  • Table: Table 2                                      │        │
│  │  • Items: 2                                            │        │
│  │  • Total: $52.50                                       │        │
│  │                                                         │        │
│  │  [Cancel]  [💳 Pay Now]                                │        │
│  └────────────────────────────────────────────────────────┘        │
└──────┬───────────────────────────────────────────────────────────────┘
       │
       │ ✅ Validates form & clicks "Pay Now"
       ▼
┌──────────────────────────────────────────────────────────────────────┐
│  Frontend: handlePlaceOrder()                                        │
│  • Detects orderContext.type === 'table'                            │
│  • Calls unifiedOrderService.initializeWalkInPayment()              │
└──────┬───────────────────────────────────────────────────────────────┘
       │
       │ 📡 POST /api/walk-in-payments/initialize
       ▼
┌──────────────────────────────────────────────────────────────────────┐
│  Backend: WalkInOrderPaymentController::initializePayment()         │
│                                                                      │
│  1️⃣ Validate request data                                           │
│     • table_id, qr_token, items, customer info                      │
│                                                                      │
│  2️⃣ Calculate order total                                           │
│     • Subtotal: sum(price × quantity)                               │
│     • Tax: subtotal × 0.15                                          │
│     • Service: subtotal × 0.10                                      │
│     • Total: subtotal + tax + service                               │
│                                                                      │
│  3️⃣ Create Payment record (status: 'pending')                       │
│     • tx_ref: "WALKIN-A1B2C3D4E5F6"                                 │
│     • amount: 52.50                                                  │
│     • metadata: {type, table_id, items, calculation}                │
│                                                                      │
│  4️⃣ Initialize payment with Chapa                                   │
│     • ChapaService::initialize()                                    │
│     • Get checkout URL                                               │
│                                                                      │
│  5️⃣ Update payment (status: 'initialized')                          │
│     • Store checkout_url                                             │
│                                                                      │
│  6️⃣ Return response                                                  │
│     {                                                                │
│       success: true,                                                 │
│       checkout_url: "https://checkout.chapa.co/...",               │
│       tx_ref: "WALKIN-A1B2C3D4E5F6",                                │
│       amount: 52.50                                                  │
│     }                                                                │
└──────┬───────────────────────────────────────────────────────────────┘
       │
       │ ✅ Success response received
       ▼
┌──────────────────────────────────────────────────────────────────────┐
│  Frontend: Store data & redirect                                    │
│  • sessionStorage.setItem('walk_in_payment_data', {                 │
│      payment_id, tx_ref, amount, table_number, items, calculation   │
│    })                                                                │
│  • window.location.href = checkout_url                              │
└──────┬───────────────────────────────────────────────────────────────┘
       │
       │ 🌐 Redirect to Chapa
       ▼
┌──────────────────────────────────────────────────────────────────────┐
│  Chapa Payment Gateway                                               │
│  https://checkout.chapa.co/checkout/...                             │
│                                                                      │
│  Customer enters payment details:                                    │
│  • Card number                                                       │
│  • Expiry date                                                       │
│  • CVV                                                               │
│  • Or mobile money                                                   │
└──────┬───────────────────────────────────────────────────────────────┘
       │
       │ 💳 Payment successful
       ▼
┌──────────────────────────────────────────────────────────────────────┐
│  Chapa Callback (Webhook)                                           │
│  POST /api/payments/webhook                                         │
│  • Updates payment status to 'verified'                             │
└──────┬───────────────────────────────────────────────────────────────┘
       │
       │ ↩️ Redirect customer back
       ▼
┌──────────────────────────────────────────────────────────────────────┐
│  Frontend: OrderPaymentSuccessPage.vue                              │
│  URL: /order/payment/success?tx_ref=WALKIN-A1B2C3D4E5F6            │
│                                                                      │
│  1️⃣ Read tx_ref from URL                                            │
│  2️⃣ Read walk_in_payment_data from sessionStorage                   │
│  3️⃣ Detect order type: isWalkInOrder = true                         │
└──────┬───────────────────────────────────────────────────────────────┘
       │
       │ 🔍 Verify payment
       ▼
┌──────────────────────────────────────────────────────────────────────┐
│  Backend: GET /api/payments/verify/{txRef}                          │
│  • Calls Chapa API to verify payment status                         │
│  • Returns: { success: true, status: 'verified' }                   │
└──────┬───────────────────────────────────────────────────────────────┘
       │
       │ ✅ Payment verified
       ▼
┌──────────────────────────────────────────────────────────────────────┐
│  Frontend: Complete order                                           │
│  POST /api/walk-in-payments/complete/{txRef}                        │
└──────┬───────────────────────────────────────────────────────────────┘
       │
       ▼
┌──────────────────────────────────────────────────────────────────────┐
│  Backend: WalkInOrderPaymentController::completeOrder()             │
│                                                                      │
│  1️⃣ Find payment by tx_ref                                          │
│  2️⃣ Verify payment status = 'verified'                              │
│  3️⃣ Get metadata (table_id, items, calculation)                     │
│                                                                      │
│  🔥 START DATABASE TRANSACTION                                       │
│                                                                      │
│  4️⃣ Create Order record                                              │
│     • order_type: 'walk_in'                                          │
│     • table_id: from metadata                                        │
│     • total, subtotal, tax, service_charge                           │
│     • status: 'pending'                                              │
│     • payment_type: 'card'                                           │
│                                                                      │
│  5️⃣ Create OrderItem records                                         │
│     • For each item in metadata                                      │
│     • menu_item_id, quantity, price, total                           │
│                                                                      │
│  6️⃣ Link payment to order                                            │
│     • payment.order_id = order.id                                    │
│                                                                      │
│  7️⃣ Update table status                                              │
│     • status: 'occupied'                                             │
│                                                                      │
│  🔥 COMMIT TRANSACTION                                                │
│                                                                      │
│  8️⃣ Return response                                                  │
│     {                                                                │
│       success: true,                                                 │
│       order: { order_number, items, table, ... }                     │
│     }                                                                │
└──────┬───────────────────────────────────────────────────────────────┘
       │
       │ ✅ Order created in database
       ▼
┌──────────────────────────────────────────────────────────────────────┐
│  🎉 ORDER NOW VISIBLE TO KITCHEN STAFF                               │
│                                                                      │
│  Chef Dashboard shows:                                               │
│  ┌────────────────────────────────────────────────────┐            │
│  │ 🍕 NEW ORDER #ORD-12345                            │            │
│  │                                                     │            │
│  │ Table: 2                                            │            │
│  │ Type: Walk-In                                       │            │
│  │ Status: Pending                                     │            │
│  │ Payment: Paid ($52.50)                              │            │
│  │                                                     │            │
│  │ Items:                                              │            │
│  │ • Margherita Pizza x2                               │            │
│  │ • Caesar Salad x1                                   │            │
│  │                                                     │            │
│  │ [Start Preparing]                                   │            │
│  └────────────────────────────────────────────────────┘            │
└──────┬───────────────────────────────────────────────────────────────┘
       │
       ▼
┌──────────────────────────────────────────────────────────────────────┐
│  Frontend: Success Page Display                                     │
│  ┌────────────────────────────────────────────────────┐            │
│  │ ✅ Payment Successful!                              │            │
│  │                                                     │            │
│  │ Order Number: #ORD-12345                            │            │
│  │ Table Number: Table 2                               │            │
│  │ Status: CONFIRMED                                   │            │
│  │ Estimated Time: 30 minutes                          │            │
│  │                                                     │            │
│  │ Payment Details:                                    │            │
│  │ TX Ref: WALKIN-A1B2C3D4E5F6                         │            │
│  │ Amount Paid: $52.50                                 │            │
│  │ Payment Status: ✓ PAID                              │            │
│  │                                                     │            │
│  │ What's Next?                                        │            │
│  │ 1. Kitchen Preparing                                │            │
│  │ 2. Waiter Assignment                                │            │
│  │ 3. Enjoy at Your Table                              │            │
│  │                                                     │            │
│  │ [💳 Download Receipt] [🍽️ Order More]             │            │
│  └────────────────────────────────────────────────────┘            │
└──────────────────────────────────────────────────────────────────────┘
```

---

## 🔑 Key Points

### ✅ Order Creation Timing
- **BEFORE:** Order was created immediately
- **NOW:** Order created ONLY after payment verified
- **BENEFIT:** No unpaid orders in kitchen queue

### ✅ Payment States
```
pending → initialized → verified → order_created
```

### ✅ Database Records
```sql
-- 1. Payment record (created at initialization)
payments {
  tx_ref: 'WALKIN-A1B2C3D4E5F6',
  status: 'pending' → 'initialized' → 'verified',
  order_id: NULL → '...' (linked after order created)
}

-- 2. Order record (created ONLY after payment verified)
orders {
  order_number: 'ORD-12345',
  order_type: 'walk_in',
  table_id: '...',
  status: 'pending',
  payment_type: 'card'
}

-- 3. Order items
order_items {
  order_id: '...',
  menu_item_id: '...',
  quantity: 2,
  line_total: 30.00
}
```

### ✅ Security Features
- Server-side QR token validation
- Payment verification before order creation
- Transaction reference uniqueness
- Form validation on frontend and backend
- CSRF protection disabled for guest routes
- No authentication required (walk-in customers)

---

## 📊 Data Flow Summary

```
Customer → QR Scan → Menu → Cart → Payment Form → Initialize Payment
    ↓
Create Payment Record (status: pending)
    ↓
Redirect to Chapa → Customer Pays → Webhook Updates (status: verified)
    ↓
Return to Success Page → Verify Payment → Complete Order
    ↓
Create Order Record → Create Order Items → Link Payment
    ↓
Order Visible in Kitchen → Chef Prepares → Waiter Delivers → Customer Enjoys
```

---

## 🎯 Benefits

1. **No Unpaid Orders** - Kitchen only sees verified paid orders
2. **Customer Confidence** - Pay before ordering
3. **Table Tracking** - Track which table ordered what
4. **Revenue Security** - Payment collected upfront
5. **Seamless UX** - One-click payment with Chapa
6. **Order History** - All orders linked to payment records

---

## 🚀 Ready for Production

All components tested and integrated. The flow is complete and ready for real-world use!
