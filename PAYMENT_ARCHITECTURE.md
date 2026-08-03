# Payment Integration - Architecture & Diagrams

## System Architecture

```
┌─────────────────────────────────────────────────────────────────────┐
│                        Hotel Management System                       │
└─────────────────────────────────────────────────────────────────────┘

┌──────────────────────────┐         ┌──────────────────────────┐
│   Vue 3 Frontend         │         │   Laravel 12 Backend     │
├──────────────────────────┤         ├──────────────────────────┤
│                          │         │                          │
│  Payment Service         │────────▶│  PaymentController       │
│  (paymentService.ts)     │         │                          │
│         │                │         │  ReservationPayment      │
│         │                │         │  GuestOrderPayment       │
│         ▼                │         │                          │
│  Payment Store           │         │  PaymentService (Logic)  │
│  (paymentStore.ts)       │         │                          │
│         │                │         │  ChapaService (API)      │
│         │                │         │                          │
│         ▼                │         ├──────────────────────────┤
│  Checkout Page           │────────▶│  Payment Model           │
│  Success Page            │         │  Reservation Model       │
│  Failed Page             │         │  Order Model             │
│  Pending Page            │         │  Guest Model             │
│                          │         │                          │
└──────────────────────────┘         └──────────────────────────┘
         │                                    │
         │                                    ▼
         │                           ┌──────────────────┐
         │                           │  MySQL Database  │
         │                           │  (payments)      │
         │                           │  (reservations)  │
         │                           │  (orders)        │
         │                           │  (order_items)   │
         │                           │  (guests)        │
         │                           └──────────────────┘
         │
         ▼
    ┌──────────────────────┐
    │  Chapa Payment       │
    │  Gateway             │
    │  (chapa.co)          │
    └──────────────────────┘
```

## Payment Flow - Hotel Reservation

```
┌─────────────────────────────────────────────────────────────────────┐
│ Step 1: Guest Initiates Booking                                     │
└─────────────────────────────────────────────────────────────────────┘
    │
    ▼
    Guest submits reservation form
    ├─ Room ID
    ├─ Check-in date
    ├─ Check-out date
    ├─ Number of guests
    └─ Customer details

┌─────────────────────────────────────────────────────────────────────┐
│ Step 2: Backend Calculates Price                                    │
└─────────────────────────────────────────────────────────────────────┘
    │
    ▼
    ReservationPaymentController::initializePayment()
    │
    ├─ Validate input
    │
    ├─ Get Room details
    │
    ├─ Calculate price:
    │  ├─ Days = check_out - check_in
    │  ├─ Subtotal = days × price_per_night
    │  ├─ Tax = subtotal × 15%
    │  └─ Total = subtotal + tax
    │
    ├─ PaymentService::createReservationPayment()
    │  ├─ Generate unique tx_ref
    │  ├─ Create Payment record (status: pending)
    │  └─ Store metadata
    │
    └─ ChapaService::initialize()
       ├─ Call Chapa API
       ├─ Get checkout_url
       └─ Update Payment with URL

┌─────────────────────────────────────────────────────────────────────┐
│ Step 3: Return Checkout URL to Frontend                             │
└─────────────────────────────────────────────────────────────────────┘
    │
    ▼
    Frontend receives:
    {
        "success": true,
        "checkout_url": "https://chapa.co/checkout/...",
        "tx_ref": "TX-20260803120000-XXXXX",
        "payment_id": "uuid",
        "amount": 5750.00,
        "price_breakdown": {
            "price_per_night": 1000,
            "number_of_nights": 5,
            "subtotal": 5000,
            "tax": 750,
            "total": 5750
        }
    }

┌─────────────────────────────────────────────────────────────────────┐
│ Step 4: Redirect to Chapa Checkout                                  │
└─────────────────────────────────────────────────────────────────────┘
    │
    ▼
    window.location.href = response.data.checkout_url
    │
    └─ Guest is redirected to Chapa payment page

┌─────────────────────────────────────────────────────────────────────┐
│ Step 5: Guest Completes Payment on Chapa                            │
└─────────────────────────────────────────────────────────────────────┘
    │
    ▼
    Guest enters card details
    Guest completes payment
    Chapa processes payment
    Payment is confirmed

┌─────────────────────────────────────────────────────────────────────┐
│ Step 6: Chapa Redirects Back to App                                 │
└─────────────────────────────────────────────────────────────────────┘
    │
    ▼
    Redirect to: http://localhost:5173/payment/success?tx_ref=TX-...

┌─────────────────────────────────────────────────────────────────────┐
│ Step 7: Frontend Verifies Payment                                   │
└─────────────────────────────────────────────────────────────────────┘
    │
    ▼
    PaymentSuccessPage mounts
    │
    ├─ Extract tx_ref from URL
    │
    └─ Call paymentStore.verifyPayment(tx_ref)
       │
       └─ PaymentController::verify(tx_ref)
          │
          ├─ Find Payment by tx_ref
          │
          ├─ Call ChapaService::verify(tx_ref)
          │  └─ Query Chapa API
          │
          ├─ If successful:
          │  ├─ Update Payment status: verified
          │  ├─ Call ReservationPaymentController::completeReservation()
          │  │  └─ PaymentService::handleReservationPaymentSuccess()
          │  │     ├─ Create Reservation record
          │  │     ├─ Link Payment to Reservation
          │  │     └─ Update status to confirmed
          │  │
          │  └─ Return success
          │
          └─ If failed:
             └─ Return error

┌─────────────────────────────────────────────────────────────────────┐
│ Step 8: Show Confirmation                                           │
└─────────────────────────────────────────────────────────────────────┘
    │
    ▼
    PaymentSuccessPage displays:
    ├─ ✓ Booking confirmed
    ├─ Transaction ID: TX-...
    ├─ Amount: 5750.00 ETB
    ├─ Booking Reference: BK-20260803-0001
    ├─ Customer details
    └─ Receipt download option
```

## Payment Flow - Guest QR Order

```
┌─────────────────────────────────────────────────────────────────────┐
│ Step 1: Guest Scans QR Code                                         │
└─────────────────────────────────────────────────────────────────────┘
    │
    ▼
    Guest scans room QR code
    │
    └─ QR contains encrypted room data

┌─────────────────────────────────────────────────────────────────────┐
│ Step 2: Guest Selects Menu Items                                    │
└─────────────────────────────────────────────────────────────────────┘
    │
    ▼
    Guest browses menu
    Guest adds items to cart:
    ├─ Item 1: Quantity 2
    ├─ Item 2: Quantity 1
    └─ Item 3: Quantity 3

┌─────────────────────────────────────────────────────────────────────┐
│ Step 3: Guest Proceeds to Checkout                                  │
└─────────────────────────────────────────────────────────────────────┘
    │
    ▼
    Guest clicks "Proceed to Payment"
    │
    └─ Frontend collects guest details if needed

┌─────────────────────────────────────────────────────────────────────┐
│ Step 4: Backend Calculates Total                                    │
└─────────────────────────────────────────────────────────────────────┘
    │
    ▼
    GuestOrderPaymentController::initializePayment()
    │
    ├─ Validate order items
    │
    ├─ For each item:
    │  ├─ Get menu item price
    │  ├─ Multiply by quantity
    │  └─ Add to subtotal
    │
    ├─ Calculate:
    │  ├─ Subtotal = sum of items
    │  ├─ Tax = subtotal × 15%
    │  └─ Total = subtotal + tax
    │
    ├─ PaymentService::createOrderPayment()
    │  ├─ Generate tx_ref
    │  ├─ Create Payment record
    │  └─ Store items in metadata
    │
    └─ ChapaService::initialize()
       └─ Get checkout_url

┌─────────────────────────────────────────────────────────────────────┐
│ Step 5: Return Checkout URL                                         │
└─────────────────────────────────────────────────────────────────────┘
    │
    ▼
    Frontend receives checkout_url
    │
    └─ Display payment amount: 345.00 ETB

┌─────────────────────────────────────────────────────────────────────┐
│ Step 6-8: Customer Pays (Same as Reservation)                       │
└─────────────────────────────────────────────────────────────────────┘
    │
    ├─ Redirect to Chapa
    ├─ Guest completes payment
    └─ Chapa redirects back

┌─────────────────────────────────────────────────────────────────────┐
│ Step 9: Verify Payment & Create Order                               │
└─────────────────────────────────────────────────────────────────────┘
    │
    ▼
    Frontend verifies payment
    │
    └─ PaymentController::verify(tx_ref)
       │
       ├─ Query Chapa API
       │
       ├─ If successful:
       │  ├─ Update Payment: verified
       │  ├─ GuestOrderPaymentController::completeOrder()
       │  │  └─ PaymentService::handleOrderPaymentSuccess()
       │  │     ├─ Create Order record
       │  │     ├─ Create OrderItem records
       │  │     ├─ Link Payment to Order
       │  │     └─ Order goes to Chef Dashboard
       │  │
       │  └─ Return success
       │
       └─ If failed:
          └─ Return error

┌─────────────────────────────────────────────────────────────────────┐
│ Step 10: Order Visible to Chef                                      │
└─────────────────────────────────────────────────────────────────────┘
    │
    ▼
    Chef Dashboard receives new order
    ├─ Room number
    ├─ Order items
    ├─ Special instructions
    └─ Status: pending

    Chef cannot see unpaid orders
    (Only verified payments create orders)
```

## Database Relationships

```
┌──────────────────────────┐
│   Guests                 │
├──────────────────────────┤
│ id (UUID) [PK]          │
│ first_name              │
│ last_name               │
│ email                   │
│ phone                   │
│ ...                     │
└──────────────┬───────────┘
               │
               │ 1:N
               │
    ┌──────────▼───────────┐
    │   Payments           │
    ├─────────────────────┤
    │ id (UUID) [PK]      │
    │ tx_ref              │
    │ amount              │
    │ status              │
    │ reservation_id ─────┼──────────┐
    │ order_id ───────────┼──┐       │
    │ guest_id ───────────┼┐ │       │
    │ ...                 │ │ │       │
    └─────────────────────┘ │ │       │
         │                  │ │       │
         │                  │ │       │
    ┌────┴──────────────┐   │ │       │
    │ 1:1               │   │ │       │
    │                   │   │ │       │
    ▼                   ▼   ▼ ▼       │
┌───────────────┐  ┌────────────┐   │
│ Reservations  │  │   Orders   │   │
├───────────────┤  ├────────────┤   │
│ id (UUID)     │  │ id (UUID)  │   │
│ guest_id  ────┼──┤ guest_id ──┼───┘
│ room_id       │  │ room_id    │
│ status        │  │ status     │
│ check_in      │  │ total      │
│ check_out     │  │ items...   │
│ ...           │  │ ...        │
└───────────────┘  └────────────┘
                        │
                        │ 1:N
                        │
                        ▼
                  ┌────────────────┐
                  │ OrderItems     │
                  ├────────────────┤
                  │ order_id       │
                  │ menu_item_id   │
                  │ quantity       │
                  │ price          │
                  │ ...            │
                  └────────────────┘
```

## State Management (Pinia Store)

```
┌─────────────────────────────────────────────────────┐
│         usePaymentStore (Pinia)                      │
├─────────────────────────────────────────────────────┤
│                                                     │
│  State:                                             │
│  ├─ currentPayment: IPayment | null                │
│  ├─ currentCheckoutUrl: string | null              │
│  ├─ currentTxRef: string | null                    │
│  ├─ paymentHistory: IPayment[]                     │
│  ├─ isLoading: boolean                             │
│  ├─ isInitializing: boolean                        │
│  ├─ isVerifying: boolean                           │
│  ├─ isPolling: boolean                             │
│  ├─ error: string | null                           │
│  └─ successMessage: string | null                  │
│                                                     │
│  Computed:                                          │
│  ├─ isPaymentPending: boolean                      │
│  ├─ isPaymentVerified: boolean                     │
│  ├─ isPaymentFailed: boolean                       │
│  ├─ paymentStatus: PaymentStatus                   │
│  ├─ paymentAmount: number                          │
│  ├─ formattedAmount: string                        │
│  └─ lastPayment: IPayment                          │
│                                                     │
│  Actions:                                           │
│  ├─ initializePayment()                            │
│  ├─ verifyPayment()                                │
│  ├─ getPaymentStatus()                             │
│  ├─ pollPaymentStatus()                            │
│  ├─ redirectToCheckout()                           │
│  ├─ openCheckout()                                 │
│  ├─ clearCurrentPayment()                          │
│  ├─ clearError()                                   │
│  ├─ clearSuccess()                                 │
│  └─ reset()                                        │
│                                                     │
└─────────────────────────────────────────────────────┘
```

## API Request/Response Flow

```
┌──────────────────────────────────────────────────────┐
│ 1. Initialize Payment                                │
└──────────────────────────────────────────────────────┘

Request:
POST /api/payments/initialize
{
    "amount": 1000,
    "first_name": "John",
    "last_name": "Doe",
    "email": "john@example.com",
    "phone": "+251912345678",
    "metadata": { "type": "reservation" }
}

Response:
{
    "success": true,
    "payment_id": "uuid-payment",
    "checkout_url": "https://chapa.co/checkout/...",
    "tx_ref": "TX-20260803120000-XXXXX",
    "amount": 1000.00
}

┌──────────────────────────────────────────────────────┐
│ 2. Verify Payment                                    │
└──────────────────────────────────────────────────────┘

Request:
GET /api/payments/verify/{txRef}

Response:
{
    "success": true,
    "payment": {
        "id": "uuid-payment",
        "tx_ref": "TX-...",
        "amount": 1000.00,
        "status": "verified",
        "customer": {
            "name": "John Doe",
            "email": "john@example.com",
            "phone": "+251912345678"
        },
        "is_verified": true,
        "verified_at": "2026-08-03T12:00:00Z",
        ...
    },
    "message": "Payment verified successfully"
}

┌──────────────────────────────────────────────────────┐
│ 3. Complete Reservation (After Payment Verified)    │
└──────────────────────────────────────────────────────┘

Request:
POST /api/reservation-payments/complete/{txRef}
Authorization: Bearer token

Response:
{
    "success": true,
    "reservation": {
        "id": "uuid-reservation",
        "booking_reference": "BK-20260803-0001",
        "room_id": "uuid-room",
        "guest_id": "uuid-guest",
        "check_in_date": "2026-08-15",
        "check_out_date": "2026-08-20",
        "status": "confirmed",
        ...
    },
    "payment": { /* Payment object */ },
    "message": "Reservation created successfully"
}
```

## Error Handling Flow

```
Error Occurs
    │
    ├─ Validation Error (422)
    │  ├─ Invalid input
    │  └─ Return errors array
    │
    ├─ Not Found Error (404)
    │  ├─ Payment not found
    │  └─ Resource not found
    │
    ├─ Server Error (500)
    │  ├─ Unexpected exception
    │  ├─ Log error
    │  └─ Return generic error
    │
    └─ Business Logic Error (400)
       ├─ Payment initialization failed
       ├─ Verification failed
       ├─ Record creation failed
       └─ Return detailed error

All Errors:
├─ Logged with context
├─ Return user-friendly message
├─ Include error code
└─ Frontend displays appropriate UI
```

## Page Flow

```
┌─────────────────────────────────────────────────────┐
│ Reservation Flow                                    │
└─────────────────────────────────────────────────────┘

ReservationForm
    │
    ├─ Fill details
    │
    ├─ Click "Book Room"
    │
    ▼
CheckoutPage
    │
    ├─ Show amount
    │
    ├─ Click "Proceed to Payment"
    │
    ▼
Chapa Checkout (External)
    │
    ├─ Guest pays
    │
    ▼
PaymentPendingPage (Optional)
    │
    ├─ Auto-polling
    │
    ├─ OR
    │
    ▼
PaymentSuccessPage
    │
    ├─ Show confirmation
    │
    ├─ Reservation created
    │
    ├─ Display booking reference
    │
    └─ Back to Dashboard

    OR

PaymentFailedPage
    │
    ├─ Show error
    │
    ├─ No reservation created
    │
    ├─ Retry option
    │
    └─ Back to Form
```

## Summary

This architecture ensures:
- ✅ Clear separation of concerns
- ✅ Atomic operations with transactions
- ✅ Secure payment processing
- ✅ Complete audit trails
- ✅ Scalable design
- ✅ User-friendly experience
- ✅ Error recovery mechanisms
- ✅ Admin oversight capabilities
