# Payment Integration Guide - Chapa Payment Gateway

## Overview

This document provides complete guidance for implementing Chapa payment integration in the Hotel Management System for:
1. Hotel Reservations
2. Guest QR Food Orders

## Table of Contents

1. [Architecture Overview](#architecture-overview)
2. [Payment Flow](#payment-flow)
3. [Database Structure](#database-structure)
4. [Backend Implementation](#backend-implementation)
5. [Frontend Implementation](#frontend-implementation)
6. [API Endpoints](#api-endpoints)
7. [Integration Examples](#integration-examples)
8. [Payment Status Handling](#payment-status-handling)
9. [Error Handling](#error-handling)

---

## Architecture Overview

### Payment Module Components

```
Backend:
├── Controllers/
│   ├── PaymentController.php (Core payment operations)
│   ├── ReservationPaymentController.php (Reservation flow)
│   └── GuestOrderPaymentController.php (Order flow)
├── Requests/
│   └── InitializePaymentRequest.php (Validation)
├── Resources/
│   └── PaymentResource.php (API response formatting)
├── Services/
│   ├── ChapaService.php (Chapa API integration)
│   ├── PaymentService.php (Business logic)
│   └── Models/Payment.php (Data model)
└── Routes/
    └── api.php (Payment endpoints)

Frontend (Vue 3 + TypeScript):
├── Services/
│   └── paymentService.ts (API communication)
├── Stores/
│   └── paymentStore.ts (Pinia state management)
└── Views/
    ├── CheckoutPage.vue (Payment form)
    ├── PaymentSuccessPage.vue (Success confirmation)
    ├── PaymentFailedPage.vue (Failure handling)
    └── PaymentPendingPage.vue (Status polling)
```

### Key Principles

**CRITICAL**: Reservations and Orders are NEVER created before successful payment verification.

1. **Payment First**: Initialize payment → Collect payment → Verify payment → Create record
2. **Transaction Safety**: All operations use database transactions to ensure atomicity
3. **Immutable Records**: Payment records are immutable for audit trail
4. **Status Tracking**: Complete payment status history is maintained
5. **Error Recovery**: Failed payments can be retried without side effects

---

## Payment Flow

### Flow 1: Hotel Reservation Payment

```
1. Guest fills Reservation Form
   ├── Check-in Date
   ├── Check-out Date
   ├── Room Selection
   └── Customer Details

2. Backend Calculates Price
   ├── Calculate nights: check_out - check_in
   ├── Get room price
   ├── Calculate: subtotal = price × nights
   ├── Calculate: tax = subtotal × 15%
   └── Total = subtotal + tax

3. Initialize Payment
   ├── Create Payment record (status=pending)
   ├── Generate unique tx_ref
   ├── Call Chapa Initialize API
   ├── Update Payment with checkout_url
   └── Return checkout URL to frontend

4. Redirect to Chapa
   └── Frontend redirects to Chapa checkout page

5. Customer Pays
   └── Customer completes payment on Chapa

6. Return to App
   ├── Chapa redirects to return_url
   └── Frontend checks payment status

7. Verify Payment
   ├── Call backend verify endpoint
   ├── Backend queries Chapa API
   ├── If verified, update Payment status
   └── If failed, update Payment status

8. Create Reservation
   ├── IF payment verified
   ├── Create Reservation record
   ├── Link Payment to Reservation
   ├── Change Reservation status to "confirmed"
   └── Return success to frontend
   OR
   ├── IF payment failed
   ├── Return error to frontend
   └── Guest can retry payment

9. Confirmation
   └── Show confirmation page with booking reference
```

### Flow 2: Guest QR Food Order Payment

```
1. Guest Scans Room QR Code
   └── QR contains encrypted room data

2. Guest Selects Items
   ├── Browse menu items
   ├── Add items to cart
   ├── Specify quantities
   └── Add special instructions

3. Guest Checkout
   ├── Review order
   ├── See calculated total
   └── Proceed to payment

4. Backend Calculates Total
   ├── For each item: price × quantity
   ├── Subtotal = sum of all items
   ├── Tax = subtotal × 15%
   └── Total = subtotal + tax

5. Initialize Payment
   ├── Create Payment record (status=pending)
   ├── Store order items metadata
   ├── Call Chapa Initialize
   └── Return checkout URL

6. Redirect to Chapa
   └── Frontend redirects to checkout

7. Customer Pays
   └── Customer completes payment

8. Return to App
   └── Frontend verifies payment

9. Verify Payment
   ├── Query Chapa API
   ├── Update Payment status
   └── Return result

10. Create Order
    ├── IF payment verified
    ├── Create Order record
    ├── Create OrderItem records for each item
    ├── Link Payment to Order
    ├── Send to Chef Dashboard
    └── Return success
    OR
    ├── IF payment failed
    ├── Return error
    └── Guest can retry

11. Chef Receives Order
    └── Chef can ONLY see orders with verified payments
```

---

## Database Structure

### Payments Table

```sql
CREATE TABLE payments (
    id UUID PRIMARY KEY,
    
    -- Transaction Reference
    tx_ref VARCHAR(255) UNIQUE NOT NULL,
    chapa_transaction_id VARCHAR(255) NULLABLE UNIQUE,
    
    -- Amount and Currency
    amount DECIMAL(10,2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'ETB',
    
    -- Customer Information
    first_name VARCHAR(255) NOT NULL,
    last_name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL INDEX,
    phone VARCHAR(20) NOT NULL,
    
    -- Payment Details
    payment_provider ENUM('chapa') DEFAULT 'chapa',
    payment_method VARCHAR(255) NULLABLE,
    status ENUM('pending', 'initialized', 'processing', 'paid', 'verified', 
                'failed', 'cancelled', 'expired', 'refunded') DEFAULT 'pending' INDEX,
    
    -- URLs
    checkout_url TEXT NULLABLE,
    callback_url TEXT NULLABLE,
    return_url TEXT NULLABLE,
    
    -- Foreign Keys
    reservation_id UUID NULLABLE,
    order_id UUID NULLABLE,
    guest_id UUID NULLABLE,
    
    -- Timestamps
    paid_at TIMESTAMP NULLABLE,
    verified_at TIMESTAMP NULLABLE,
    
    -- Data
    raw_response JSON NULLABLE,
    metadata JSON NULLABLE,
    
    -- Audit
    created_at TIMESTAMP NOT NULL,
    updated_at TIMESTAMP NOT NULL
)
```

### Payment Status Lifecycle

```
pending
  ↓
initialized (after Chapa initialize)
  ↓
processing (during customer payment)
  ↓
paid (Chapa confirms payment)
  ↓
verified (Backend verifies with Chapa) ✓ CREATE RECORD
  ↓
completed (Reservation/Order created)

OR

failed (Payment declined)
cancelled (Customer cancelled)
expired (Payment timeout)
refunded (Payment refunded)
```

---

## Backend Implementation

### 1. Payment Model Relationships

```php
// app/Models/Payment.php

class Payment extends Model {
    // Relationships
    public function reservation() {
        return $this->belongsTo(Reservation::class);
    }
    
    public function order() {
        return $this->belongsTo(Order::class);
    }
    
    public function guest() {
        return $this->belongsTo(Guest::class);
    }
    
    // Status Methods
    public function isVerified(): bool { /* ... */ }
    public function isFailed(): bool { /* ... */ }
    
    // Status Setters
    public function markAsVerified(array $response): void { /* ... */ }
    public function markAsFailed(array $response): void { /* ... */ }
}
```

### 2. PaymentService - Business Logic

```php
// app/Services/PaymentService.php

class PaymentService {
    
    /**
     * Create Payment for Reservation
     */
    public function createReservationPayment(array $data): Payment {
        // Generate tx_ref, create Payment record
        // Store metadata with reservation details
    }
    
    /**
     * Create Payment for Order
     */
    public function createOrderPayment(array $data): Payment {
        // Generate tx_ref, create Payment record
        // Store metadata with order items
    }
    
    /**
     * Handle Successful Reservation Payment
     * Creates Reservation after payment verification
     */
    public function handleReservationPaymentSuccess(
        Payment $payment, 
        array $reservationData
    ): array {
        // Use database transaction
        // Create Reservation
        // Link to Payment
        // Update statuses
        // Return result
    }
    
    /**
     * Handle Successful Order Payment
     * Creates Order after payment verification
     */
    public function handleOrderPaymentSuccess(
        Payment $payment,
        array $orderData,
        array $orderItems
    ): array {
        // Use database transaction
        // Create Order
        // Create OrderItems
        // Link to Payment
        // Send to Chef Dashboard
        // Return result
    }
}
```

### 3. PaymentController - Core Operations

```php
// app/Http/Controllers/Api/PaymentController.php

public function initialize(InitializePaymentRequest $request): JsonResponse {
    // 1. Validate input
    // 2. Generate tx_ref
    // 3. Create Payment record
    // 4. Call Chapa API
    // 5. Update Payment with checkout_url
    // 6. Return checkout_url to frontend
}

public function verify(string $txRef): JsonResponse {
    // 1. Find Payment by tx_ref
    // 2. Query Chapa API for verification
    // 3. Update Payment status
    // 4. Return result
}

public function callback(Request $request): JsonResponse {
    // 1. Extract tx_ref from callback
    // 2. Call verify()
    // 3. Return to Chapa
}
```

### 4. ReservationPaymentController - Reservation Flow

```php
// app/Http/Controllers/Api/ReservationPaymentController.php

public function initializePayment(Request $request): JsonResponse {
    // 1. Validate reservation details
    // 2. Calculate price (nights × room_rate + tax)
    // 3. Create payment with $paymentService
    // 4. Initialize with Chapa
    // 5. Return checkout_url
}

public function completeReservation(string $txRef): JsonResponse {
    // 1. Find Payment
    // 2. Verify payment is verified
    // 3. Create Reservation with $paymentService
    // 4. Return confirmation
}
```

### 5. GuestOrderPaymentController - Order Flow

```php
// app/Http/Controllers/Api/GuestOrderPaymentController.php

public function initializePayment(Request $request): JsonResponse {
    // 1. Validate order items exist
    // 2. Calculate total (items + tax)
    // 3. Create payment with $paymentService
    // 4. Initialize with Chapa
    // 5. Return checkout_url
}

public function completeOrder(string $txRef): JsonResponse {
    // 1. Find Payment
    // 2. Verify payment is verified
    // 3. Create Order and OrderItems with $paymentService
    // 4. Send to Chef Dashboard
    // 5. Return confirmation
}
```

---

## Frontend Implementation

### 1. Payment Service

```typescript
// src/services/paymentService.ts

class PaymentService {
    
    // Initialize payment
    async initializePayment(data: IInitializePaymentRequest) {
        return await axios.post('/api/payments/initialize', data);
    }
    
    // Verify payment
    async verifyPayment(txRef: string) {
        return await axios.get(`/api/payments/verify/${txRef}`);
    }
    
    // Poll payment status
    async pollPaymentStatus(txRef: string, maxAttempts = 30) {
        // Continuously check until verified or failed
    }
    
    // Redirect to Chapa
    redirectToCheckout(checkoutUrl: string) {
        window.location.href = checkoutUrl;
    }
}
```

### 2. Payment Store (Pinia)

```typescript
// src/stores/paymentStore.ts

export const usePaymentStore = defineStore('payment', () => {
    
    const currentPayment = ref(null);
    const isPaymentVerified = computed(() => currentPayment.value?.is_verified);
    
    // Initialize payment
    async function initializePayment(data) {
        // Call service
        // Store checkout_url
        // Store tx_ref
    }
    
    // Verify payment
    async function verifyPayment(txRef) {
        // Call service
        // Update current payment
        // Return result
    }
    
    // Poll for status
    async function pollPaymentStatus(txRef) {
        // Continuously check until done
    }
    
    return {
        currentPayment,
        isPaymentVerified,
        initializePayment,
        verifyPayment,
        pollPaymentStatus,
    };
});
```

### 3. Checkout Page

```vue
<!-- src/views/payment/CheckoutPage.vue -->

<template>
  <form @submit.prevent="submitPayment">
    <!-- Customer info form -->
    <input v-model="formData.first_name" required />
    <input v-model="formData.last_name" required />
    <input v-model="formData.email" required />
    <input v-model="formData.phone" required />
    
    <!-- Amount display -->
    <div>{{ formatAmount(formData.amount) }}</div>
    
    <!-- Submit -->
    <button type="submit" :disabled="isLoading">
      Proceed to Payment
    </button>
  </form>
</template>

<script setup>
import { ref } from 'vue';
import { usePaymentStore } from '@/stores/paymentStore';

const paymentStore = usePaymentStore();
const formData = ref({ /* ... */ });

async function submitPayment() {
    // 1. Initialize payment
    const response = await paymentStore.initializePayment(formData.value);
    
    // 2. Redirect to Chapa
    paymentStore.redirectToCheckout();
}
</script>
```

### 4. Payment Success Page

```vue
<!-- src/views/payment/PaymentSuccessPage.vue -->

<template>
  <div v-if="isLoading" class="loading">Loading...</div>
  <div v-else-if="payment">
    <h1>✓ Payment Successful!</h1>
    <p>Transaction ID: {{ payment.tx_ref }}</p>
    <p>Amount: {{ payment.formatted_amount }}</p>
    <p>Status: {{ payment.status }}</p>
    <button @click="downloadReceipt">Download Receipt</button>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { useRoute } from 'vue-router';
import { usePaymentStore } from '@/stores/paymentStore';

const route = useRoute();
const paymentStore = usePaymentStore();
const isLoading = ref(false);

onMounted(async () => {
    isLoading.value = true;
    const txRef = route.query.tx_ref;
    
    // Verify payment
    await paymentStore.verifyPayment(txRef);
    isLoading.value = false;
});
</script>
```

---

## API Endpoints

### Payment Endpoints

#### Initialize Payment
```
POST /api/payments/initialize
Authorization: Not required (public endpoint)

Body:
{
    "amount": 1000.00,
    "first_name": "John",
    "last_name": "Doe",
    "email": "john@example.com",
    "phone": "+251912345678",
    "title": "Hotel Payment",
    "description": "Payment for services",
    "metadata": { /* custom data */ }
}

Response:
{
    "success": true,
    "payment_id": "uuid",
    "checkout_url": "https://chapa.co/checkout/...",
    "tx_ref": "TX-20260803120000-XXXXX",
    "amount": 1000.00
}
```

#### Verify Payment
```
GET /api/payments/verify/{txRef}
Authorization: Not required

Response:
{
    "success": true,
    "payment": { /* Payment object */ },
    "message": "Payment verified successfully"
}
```

#### Get Payment Status
```
GET /api/payments/{paymentId}
Authorization: Bearer token (authenticated)

Response:
{
    "success": true,
    "payment": { /* Payment object */ }
}
```

#### Callback from Chapa
```
GET /api/payments/callback?tx_ref=TX-...
Authorization: Not required

Response:
{
    "success": true,
    "payment": { /* Payment object */ }
}
```

### Reservation Payment Endpoints

#### Initialize Reservation Payment
```
POST /api/reservation-payments/initialize
Authorization: Bearer token

Body:
{
    "room_id": "uuid",
    "guest_id": "uuid",
    "check_in_date": "2026-08-15",
    "check_out_date": "2026-08-20",
    "number_of_guests": 2,
    "special_requests": "Extra pillows",
    "first_name": "John",
    "last_name": "Doe",
    "email": "john@example.com",
    "phone": "+251912345678"
}

Response:
{
    "success": true,
    "payment_id": "uuid",
    "checkout_url": "...",
    "tx_ref": "...",
    "amount": 5000.00,
    "price_breakdown": {
        "price_per_night": 1000,
        "number_of_nights": 5,
        "subtotal": 5000,
        "tax": 750,
        "total": 5750
    }
}
```

#### Complete Reservation After Payment
```
POST /api/reservation-payments/complete/{txRef}
Authorization: Bearer token

Response:
{
    "success": true,
    "reservation": { /* Reservation object */ },
    "payment": { /* Payment object */ },
    "message": "Reservation created successfully"
}
```

### Order Payment Endpoints

#### Initialize Order Payment
```
POST /api/order-payments/initialize
Authorization: Not required

Body:
{
    "guest_id": "uuid",
    "room_id": "uuid",
    "items": [
        {
            "menu_item_id": "uuid",
            "quantity": 2,
            "special_instructions": "No onions"
        }
    ],
    "notes": "Order notes",
    "first_name": "John",
    "last_name": "Doe",
    "email": "john@example.com",
    "phone": "+251912345678"
}

Response:
{
    "success": true,
    "payment_id": "uuid",
    "checkout_url": "...",
    "tx_ref": "...",
    "amount": 350.00,
    "calculation": {
        "subtotal": 300,
        "tax": 45,
        "discount": 0,
        "total": 345
    }
}
```

#### Complete Order After Payment
```
POST /api/order-payments/complete/{txRef}
Authorization: Not required

Response:
{
    "success": true,
    "order": { /* Order object */ },
    "payment": { /* Payment object */ },
    "message": "Order created successfully and sent to kitchen"
}
```

---

## Integration Examples

### Example 1: Reservation Booking with Payment

**Frontend:**
```vue
<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { usePaymentStore } from '@/stores/paymentStore';

const router = useRouter();
const paymentStore = usePaymentStore();

const reservationData = ref({
    room_id: '',
    guest_id: '',
    check_in_date: '',
    check_out_date: '',
    number_of_guests: 1,
    first_name: '',
    last_name: '',
    email: '',
    phone: '',
});

async function bookRoom() {
    try {
        // Initialize payment
        const response = await axios.post(
            '/api/reservation-payments/initialize',
            reservationData.value
        );
        
        // Store payment info
        sessionStorage.setItem('reservation_payment', 
            JSON.stringify(response.data));
        
        // Redirect to Chapa
        window.location.href = response.data.checkout_url;
        
    } catch (error) {
        alert('Booking failed: ' + error.message);
    }
}

// After customer returns from Chapa
async function confirmReservation() {
    const txRef = new URLSearchParams(window.location.search)
        .get('tx_ref');
    
    try {
        const response = await axios.post(
            `/api/reservation-payments/complete/${txRef}`
        );
        
        // Show success
        router.push('/payment/success?tx_ref=' + txRef);
        
    } catch (error) {
        router.push('/payment/failed?tx_ref=' + txRef);
    }
}
</script>

<template>
  <form @submit.prevent="bookRoom">
    <input v-model="reservationData.room_id" required />
    <input v-model="reservationData.check_in_date" type="date" required />
    <input v-model="reservationData.check_out_date" type="date" required />
    <!-- More fields -->
    <button type="submit">Book Room</button>
  </form>
</template>
```

### Example 2: Guest Order with Payment

**Frontend:**
```vue
<script setup>
import { ref } from 'vue';
import paymentService from '@/services/paymentService';

const cart = ref([
    { menu_item_id: 'uuid-1', quantity: 2 },
    { menu_item_id: 'uuid-2', quantity: 1 },
]);

async function checkout() {
    try {
        // Initialize payment
        const response = await paymentService.initializePayment({
            guest_id: route.params.guestId,
            room_id: route.params.roomId,
            items: cart.value,
            first_name: 'John',
            last_name: 'Doe',
            email: 'john@example.com',
            phone: '+251912345678',
        });
        
        // Redirect to payment
        paymentService.redirectToCheckout(response.checkout_url);
        
    } catch (error) {
        alert('Failed: ' + error.message);
    }
}
</script>

<template>
  <div>
    <h2>Order Summary</h2>
    <!-- Cart items -->
    <button @click="checkout">Proceed to Payment</button>
  </div>
</template>
```

---

## Payment Status Handling

### Checking Payment Status

```typescript
// Periodic polling
async function checkPaymentStatus(txRef: string) {
    const maxAttempts = 30;
    let attempts = 0;
    
    while (attempts < maxAttempts) {
        try {
            const payment = await paymentService.verifyPayment(txRef);
            
            if (payment.is_verified) {
                // Payment successful
                return 'SUCCESS';
            }
            
            if (payment.is_failed) {
                // Payment failed
                return 'FAILED';
            }
            
            // Still pending, wait and retry
            await new Promise(r => setTimeout(r, 2000));
            attempts++;
            
        } catch (error) {
            console.error('Check failed:', error);
            attempts++;
        }
    }
    
    // Timeout
    return 'TIMEOUT';
}
```

### Auto-complete on Return

```typescript
// When customer returns from Chapa
onMounted(async () => {
    const txRef = route.query.tx_ref;
    
    if (txRef) {
        try {
            // Verify payment
            const response = await axios.post(
                `/api/reservation-payments/complete/${txRef}`
            );
            
            if (response.data.success) {
                // Show success
                router.push('/success?tx_ref=' + txRef);
            } else {
                router.push('/failed?tx_ref=' + txRef);
            }
            
        } catch (error) {
            router.push('/failed?tx_ref=' + txRef);
        }
    }
});
```

---

## Error Handling

### Backend Error Handling

```php
try {
    // Validate input
    $validated = $request->validate([...]);
    
    // Create payment
    $payment = Payment::create($validated);
    
    // Call Chapa
    $response = $chapaService->initialize($data);
    
    if (!$response['success']) {
        Log::error('Chapa failed', $response);
        $payment->markAsFailed($response);
        return error_response();
    }
    
    return success_response();
    
} catch (ValidationException $e) {
    return validation_error_response($e);
} catch (Exception $e) {
    Log::error('Unexpected error', [
        'message' => $e->getMessage(),
        'trace'   => $e->getTraceAsString(),
    ]);
    return error_response();
}
```

### Frontend Error Handling

```typescript
async function initializePayment(data) {
    try {
        const response = await axios.post('/api/payments/initialize', data);
        return response.data;
    } catch (error) {
        if (error.response?.status === 422) {
            // Validation error
            showValidationErrors(error.response.data.errors);
        } else {
            // Network or server error
            showError(error.response?.data?.message || error.message);
        }
        throw error;
    }
}
```

---

## Testing

### Manual Testing Checklist

- [ ] Initialize payment with valid data
- [ ] Verify payment with successful Chapa response
- [ ] Verify payment with failed Chapa response
- [ ] Create reservation after verified payment
- [ ] Verify reservation was NOT created before payment
- [ ] Create order after verified payment
- [ ] Verify order was NOT created before payment
- [ ] Test with invalid payment data
- [ ] Test with network errors
- [ ] Test payment polling timeout
- [ ] Test multiple payment attempts
- [ ] Test concurrent payments

### Example Test Cases

```php
// Test: Verify payment creates records
public function test_verified_payment_creates_reservation()
{
    $payment = Payment::factory()->verified()->create();
    $metadata = $payment->metadata;
    
    $response = $this->post(
        "/api/reservation-payments/complete/{$payment->tx_ref}"
    );
    
    $this->assertDatabaseHas('reservations', [
        'guest_id' => $payment->guest_id,
    ]);
}

// Test: Failed payment doesn't create records
public function test_failed_payment_does_not_create_order()
{
    $payment = Payment::factory()->failed()->create();
    
    $response = $this->post(
        "/api/order-payments/complete/{$payment->tx_ref}"
    );
    
    $this->assertDatabaseMissing('orders', [
        'guest_id' => $payment->guest_id,
    ]);
}
```

---

## Summary

This payment integration provides:

✓ Secure payment processing with Chapa  
✓ Atomic transactions (all-or-nothing)  
✓ Complete audit trail  
✓ Payment status tracking  
✓ Error recovery  
✓ Multi-platform support (Reservation + Order)  
✓ Production-ready code  
✓ Comprehensive logging  
✓ Transaction verification  

The system ensures that records are NEVER created before successful payment verification, maintaining data integrity and preventing fraud.
