# Walk-In Order Chapa Payment Integration

## Overview
Walk-in restaurant table orders now require prepayment via Chapa before the order is created and sent to the kitchen.

## Payment Flow

### Before (Old Flow - Direct Order Creation):
1. Customer scans table QR → Menu loads
2. Customer adds items to cart
3. Customer clicks "Place Order"
4. **Order created immediately** → Sent to kitchen
5. Payment happens later (cash/card at table)

### After (New Flow - Prepayment Required):
1. Customer scans table QR → Menu loads
2. Customer adds items to cart
3. Customer clicks "Proceed to Payment"
4. **Customer enters payment details** (name, email, phone)
5. **Redirect to Chapa checkout page**
6. **Customer completes payment on Chapa**
7. **Chapa verifies payment**
8. **ONLY THEN** is order created
9. Order sent to kitchen with "PAID" status
10. Customer redirected to success page

## Key Benefits

✅ **No Unpaid Orders** - Kitchen only receives paid orders  
✅ **Revenue Protection** - Payment guaranteed before food preparation  
✅ **Automated Process** - No manual payment collection needed  
✅ **Better Customer Experience** - Clear payment status  
✅ **Audit Trail** - All payments tracked in database

## Technical Implementation

### Backend

#### 1. New Controller: `WalkInOrderPaymentController.php`

**Location**: `server/app/Http/Controllers/Api/WalkInOrderPaymentController.php`

**Methods**:
- `initializePayment()` - Creates payment record and gets Chapa checkout URL
- `completeOrder()` - Creates order after payment verification
- `getOrderByPayment()` - Retrieves order by payment transaction reference

#### 2. New Routes

**Location**: `server/routes/api.php`

```php
Route::prefix('walk-in-payments')->group(function () {
    Route::post('/initialize', [WalkInOrderPaymentController::class, 'initializePayment']);
    Route::post('/complete/{txRef}', [WalkInOrderPaymentController::class, 'completeOrder']);
    Route::get('/{txRef}', [WalkInOrderPaymentController::class, 'getOrderByPayment']);
});
```

**Endpoints**:
- `POST /api/walk-in-payments/initialize` - Initialize payment
- `POST /api/walk-in-payments/complete/{txRef}` - Complete order after payment
- `GET /api/walk-in-payments/{txRef}` - Get order by payment reference

### Frontend

#### 1. Updated Service: `unifiedOrderService.ts`

**Location**: `Client2/vue-project/src/services/unifiedOrderService.ts`

**New Methods**:
```typescript
// Initialize walk-in payment (redirects to Chapa)
initializeWalkInPayment(paymentData: WalkInPaymentRequest)

// Get order by payment transaction reference
getOrderByPayment(txRef: string)
```

#### 2. Frontend Flow Changes

**QRMenu.vue needs to be updated to**:

1. **Detect walk-in context** ✅ (Already working)
2. **Show payment form** for walk-in orders
3. **Collect customer details**:
   - First Name
   - Last Name
   - Email
   - Phone Number
4. **Call payment API** instead of direct order creation
5. **Redirect to Chapa** checkout page
6. **Handle payment callback** when customer returns

## Database Schema

### Payment Record Structure

```json
{
  "id": "uuid",
  "tx_ref": "WALKIN-ABCD123456",
  "amount": 1500.00,
  "currency": "ETB",
  "first_name": "John",
  "last_name": "Doe",
  "email": "john@example.com",
  "phone": "+251912345678",
  "status": "verified",
  "payment_method": "chapa",
  "order_id": "uuid", // Linked after order creation
  "metadata": {
    "type": "walk_in_order",
    "table_id": "uuid",
    "qr_token": "table-2-GveD6NRGFa",
    "items": [
      {
        "menu_item_id": "uuid",
        "name": "Burger",
        "quantity": 2,
        "price": 250.00,
        "total": 500.00
      }
    ],
    "special_requests": "No onions",
    "calculation": {
      "subtotal": 500.00,
      "tax": 75.00,  // 15%
      "service_charge": 50.00,  // 10%
      "discount": 0.00,
      "total": 625.00
    }
  }
}
```

### Order Record (Created After Payment)

```json
{
  "id": "uuid",
  "order_number": "ORD-123456",
  "order_type": "walk_in",
  "table_id": "uuid",
  "room_id": null,
  "guest_id": null,
  "reservation_id": null,
  "total": 625.00,
  "subtotal": 500.00,
  "tax": 75.00,
  "service_charge": 50.00,
  "discount": 0.00,
  "payment_type": "card",  // Paid via Chapa
  "status": "pending",  // Waiting for kitchen
  "notes": "No onions"
}
```

## API Request/Response Examples

### 1. Initialize Payment

**Request**:
```http
POST /api/walk-in-payments/initialize
Content-Type: application/json

{
  "table_id": "a014a278-1def-4c4a-99c5-9678fb3bbc27",
  "qr_token": "table-2-GveD6NRGFa",
  "items": [
    {
      "menu_item_id": "abc-123",
      "quantity": 2
    }
  ],
  "special_requests": "Extra sauce",
  "first_name": "John",
  "last_name": "Doe",
  "email": "john@example.com",
  "phone": "+251912345678"
}
```

**Response**:
```json
{
  "success": true,
  "message": "Payment initialized successfully",
  "payment_id": "payment-uuid",
  "checkout_url": "https://checkout.chapa.co/checkout/payment/...",
  "tx_ref": "WALKIN-ABCD123456",
  "amount": 625.00,
  "calculation": {
    "subtotal": 500.00,
    "tax": 75.00,
    "service_charge": 50.00,
    "discount": 0.00,
    "total": 625.00,
    "items": [...]
  }
}
```

### 2. Complete Order (After Payment Verification)

**Request**:
```http
POST /api/walk-in-payments/complete/WALKIN-ABCD123456
```

**Response**:
```json
{
  "success": true,
  "message": "Order created successfully and sent to kitchen",
  "order": {
    "id": "order-uuid",
    "order_number": "ORD-123456",
    "order_type": "walk_in",
    "table_number": "2",
    "total": 625.00,
    "status": "pending",
    "orderItems": [...]
  },
  "payment": {
    "tx_ref": "WALKIN-ABCD123456",
    "amount": 625.00,
    "status": "verified"
  }
}
```

## Frontend Implementation Guide

### Step 1: Update QRMenu.vue - Add Payment Form

```vue
<template>
  <!-- Existing menu UI -->
  
  <!-- Payment Form (shown when ready to checkout) -->
  <div v-if="showPaymentForm && orderContext.type === 'table'" class="payment-form">
    <h3>Complete Your Payment</h3>
    <form @submit.prevent="handlePayment">
      <input v-model="paymentForm.first_name" placeholder="First Name" required />
      <input v-model="paymentForm.last_name" placeholder="Last Name" required />
      <input v-model="paymentForm.email" type="email" placeholder="Email" required />
      <input v-model="paymentForm.phone" type="tel" placeholder="Phone" required />
      
      <div class="order-summary">
        <p>Subtotal: {{ formatCurrency(calculation.subtotal) }}</p>
        <p>Tax (15%): {{ formatCurrency(calculation.tax) }}</p>
        <p>Service (10%): {{ formatCurrency(calculation.service_charge) }}</p>
        <h4>Total: {{ formatCurrency(calculation.total) }}</h4>
      </div>
      
      <button type="submit">Proceed to Payment</button>
    </form>
  </div>
</template>

<script setup lang="ts">
import { unifiedOrderService } from '@/services/unifiedOrderService'

const paymentForm = ref({
  first_name: '',
  last_name: '',
  email: '',
  phone: ''
})

const handlePayment = async () => {
  try {
    const response = await unifiedOrderService.initializeWalkInPayment({
      table_id: orderContext.id,
      qr_token: qrToken,
      items: cartItems.value,
      special_requests: specialRequests.value,
      ...paymentForm.value
    })
    
    if (response.success && response.checkout_url) {
      // Redirect to Chapa checkout
      window.location.href = response.checkout_url
    }
  } catch (error) {
    console.error('Payment initialization failed:', error)
  }
}
</script>
```

### Step 2: Handle Payment Return

Create or update the payment success page to handle walk-in orders:

**Location**: `Client2/vue-project/src/views/payment/OrderPaymentSuccessPage.vue`

```typescript
// Get tx_ref from URL query params
const route = useRoute()
const txRef = route.query.tx_ref

// Fetch order details
const orderData = await unifiedOrderService.getOrderByPayment(txRef)

// Show success message with order details
```

## Configuration

### Environment Variables

**Backend (.env)**:
```bash
# Chapa Configuration
CHAPA_PUBLIC_KEY=your_public_key
CHAPA_SECRET_KEY=your_secret_key
CHAPA_BASE_URL=https://api.chapa.co/v1
CHAPA_CURRENCY=ETB

# Return URL after payment (walk-in orders)
CHAPA_ORDER_RETURN_URL=http://localhost:5173/order/payment/success
```

### Chapa Configuration

The return URL should include transaction reference:
```
http://localhost:5173/order/payment/success?tx_ref=WALKIN-ABCD123456&status=success
```

## Testing

### Test Flow:

1. **Scan QR Code**:
   ```
   http://localhost:5173/qr-menu?token=table-2-GveD6NRGFa
   ```

2. **Add Items** to cart

3. **Click "Proceed to Payment"**

4. **Fill Payment Form**:
   - First Name: John
   - Last Name: Doe
   - Email: john@example.com
   - Phone: +251912345678

5. **Submit** → Redirect to Chapa

6. **Complete Payment** on Chapa test environment

7. **Redirect Back** to success page

8. **Verify**:
   - Payment record in `payments` table with status='verified'
   - Order record in `orders` table with order_type='walk_in'
   - Table status='occupied'
   - Order visible in kitchen dashboard

### Database Verification:

```sql
-- Check payment
SELECT * FROM payments WHERE tx_ref LIKE 'WALKIN-%' ORDER BY created_at DESC LIMIT 1;

-- Check linked order
SELECT o.* FROM orders o
JOIN payments p ON p.order_id = o.id
WHERE p.tx_ref = 'WALKIN-ABCD123456';

-- Check table status
SELECT * FROM restaurant_tables WHERE status = 'occupied';
```

## Migration from Old System

### For Existing Walk-In Orders:

**Option 1**: Allow both flows temporarily
- Cash/Card at table → Direct order creation (old flow)
- Online payment → Chapa prepayment (new flow)

**Option 2**: Force all to use new flow
- Remove direct order creation endpoint
- All orders must go through payment

**Recommendation**: Option 2 for consistency and revenue protection

## Next Steps

1. ✅ Create `WalkInOrderPaymentController.php`
2. ✅ Add routes to `api.php`
3. ✅ Update `unifiedOrderService.ts`
4. ⏳ Update `QRMenu.vue` with payment form
5. ⏳ Update `OrderPaymentSuccessPage.vue` to handle walk-in
6. ⏳ Test complete flow
7. ⏳ Deploy to production

## Notes

- **Tax**: 15% applied to all walk-in orders
- **Service Charge**: 10% applied to all walk-in orders  
- **Payment Method**: Always "card" since paid via Chapa
- **Order Status**: Starts as "pending" (waiting for kitchen)
- **Table Status**: Set to "occupied" after order creation

---

**Status**: Backend Complete, Frontend Needs Update  
**Created**: 2026-08-09  
**Last Updated**: 2026-08-09
