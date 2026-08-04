# 📡 Cashier Module - API Reference

## Base URL
```
http://127.0.0.1:8000/api
```

## Authentication
All endpoints require:
- **Authentication**: Bearer token (Laravel Sanctum)
- **Role**: `cashier` or `admin`

```http
Authorization: Bearer {token}
```

---

## 📊 Dashboard Endpoints

### 1. Get Dashboard Statistics
```http
GET /cashier/dashboard
```

**Response:**
```json
{
  "success": true,
  "data": {
    "today_revenue": 12450.00,
    "weekly_revenue": 45200.00,
    "monthly_revenue": 185000.00,
    "pending_payments": 18,
    "completed_payments": 126,
    "failed_payments": 5,
    "refund_requests": 3,
    "total_transactions": 149
  }
}
```

### 2. Get Recent Payments
```http
GET /cashier/dashboard/recent-payments
```

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": "uuid",
      "tx_ref": "TX-20260804123456-ABC12345",
      "amount": 1500.00,
      "currency": "ETB",
      "customer_name": "John Doe",
      "email": "john@example.com",
      "status": "paid",
      "payment_provider": "chapa",
      "payment_method": "mobile_money",
      "type": "Reservation",
      "reference": "reservation-uuid",
      "paid_at": "2026-08-04 12:34:56",
      "created_at": "2026-08-04 12:30:00"
    }
  ]
}
```

### 3. Get Pending Payments
```http
GET /cashier/dashboard/pending-payments
```

### 4. Get Recent Transactions
```http
GET /cashier/dashboard/recent-transactions
```

### 5. Get Revenue Chart (Last 7 Days)
```http
GET /cashier/dashboard/revenue-chart
```

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "date": "2026-08-01",
      "label": "Mon",
      "revenue": 2500.00
    },
    {
      "date": "2026-08-02",
      "label": "Tue",
      "revenue": 3200.00
    }
  ]
}
```

### 6. Get Payment Method Distribution
```http
GET /cashier/dashboard/payment-method-chart
```

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "method": "mobile_money",
      "count": 45
    },
    {
      "method": "card",
      "count": 32
    }
  ]
}
```

### 7. Get Refund Requests
```http
GET /cashier/dashboard/refund-requests
```

---

## 💳 Payment Management Endpoints

### 1. Get All Payments (with Filters)
```http
GET /cashier/payments
```

**Query Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| `search` | string | Search by tx_ref, email, name |
| `status` | string | Filter by status (paid, pending, failed, refunded) |
| `provider` | string | Filter by provider (chapa) |
| `type` | string | Filter by type (reservation, order) |
| `date_from` | date | Start date (YYYY-MM-DD) |
| `date_to` | date | End date (YYYY-MM-DD) |
| `filter` | string | Quick filter (today, week, month, paid, pending, failed, refunded) |
| `sort_by` | string | Column to sort by (default: created_at) |
| `sort_order` | string | Sort order (asc, desc) |
| `per_page` | integer | Items per page (default: 15) |
| `page` | integer | Page number |

**Example:**
```http
GET /cashier/payments?filter=today&status=paid&per_page=20&page=1
```

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": "uuid",
      "tx_ref": "TX-20260804123456-ABC12345",
      "chapa_transaction_id": "CH-123456789",
      "amount": 1500.00,
      "currency": "ETB",
      "formatted_amount": "1,500.00 ETB",
      "customer_name": "John Doe",
      "email": "john@example.com",
      "phone": "+251912345678",
      "status": "paid",
      "payment_provider": "chapa",
      "payment_method": "mobile_money",
      "type": "Reservation",
      "reference_id": "reservation-uuid",
      "guest": {
        "id": "guest-uuid",
        "name": "John Doe",
        "email": "john@example.com"
      },
      "paid_at": "2026-08-04 12:34:56",
      "verified_at": "2026-08-04 12:35:10",
      "created_at": "2026-08-04 12:30:00"
    }
  ],
  "pagination": {
    "current_page": 1,
    "last_page": 5,
    "per_page": 15,
    "total": 73,
    "from": 1,
    "to": 15
  }
}
```

### 2. Get Single Payment Details
```http
GET /cashier/payments/{id}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "id": "uuid",
    "tx_ref": "TX-20260804123456-ABC12345",
    "chapa_transaction_id": "CH-123456789",
    "amount": 1500.00,
    "currency": "ETB",
    "formatted_amount": "1,500.00 ETB",
    "first_name": "John",
    "last_name": "Doe",
    "email": "john@example.com",
    "phone": "+251912345678",
    "status": "paid",
    "payment_provider": "chapa",
    "payment_method": "mobile_money",
    "checkout_url": "https://checkout.chapa.co/...",
    "callback_url": "http://localhost/api/payments/callback",
    "return_url": "http://localhost/payment/success",
    "type": "Reservation",
    "guest": {
      "id": "guest-uuid",
      "name": "John Doe",
      "email": "john@example.com",
      "phone": "+251912345678"
    },
    "reservation": {
      "id": "reservation-uuid",
      "check_in_date": "2026-08-10",
      "check_out_date": "2026-08-15",
      "number_of_guests": 2,
      "room": {
        "room_number": "101",
        "floor": "1"
      }
    },
    "order": null,
    "paid_at": "2026-08-04 12:34:56",
    "verified_at": "2026-08-04 12:35:10",
    "created_at": "2026-08-04 12:30:00",
    "updated_at": "2026-08-04 12:35:10",
    "metadata": {}
  }
}
```

### 3. Process Refund
```http
POST /cashier/payments/{id}/refund
```

**Requirements:**
- Payment must have status: `paid` or `verified`

**Response (Success):**
```json
{
  "success": true,
  "message": "Payment marked as refunded successfully",
  "data": {
    "id": "payment-uuid",
    "status": "refunded"
  }
}
```

**Response (Error):**
```json
{
  "success": false,
  "message": "Only paid payments can be refunded"
}
```

---

## 📊 Report Endpoints

### 1. Revenue Report
```http
GET /cashier/reports/revenue
```

**Query Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| `period` | string | daily, weekly, monthly, yearly |
| `date_from` | date | Start date (YYYY-MM-DD) |
| `date_to` | date | End date (YYYY-MM-DD) |

**Example:**
```http
GET /cashier/reports/revenue?period=daily&date_from=2026-08-01&date_to=2026-08-04
```

**Response:**
```json
{
  "success": true,
  "data": {
    "period": "daily",
    "date_from": "2026-08-01",
    "date_to": "2026-08-04",
    "total_revenue": 12450.00,
    "total_transactions": 45,
    "average_transaction": 276.67,
    "reservation_revenue": 8500.00,
    "order_revenue": 3950.00,
    "revenue_by_method": [
      {
        "method": "mobile_money",
        "total": 7200.00
      },
      {
        "method": "card",
        "total": 5250.00
      }
    ],
    "daily_breakdown": [
      {
        "date": "2026-08-01",
        "revenue": 2500.00,
        "transactions": 10
      },
      {
        "date": "2026-08-02",
        "revenue": 3200.00,
        "transactions": 12
      }
    ]
  }
}
```

### 2. Payment Report
```http
GET /cashier/reports/payment
```

**Query Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| `date_from` | date | Start date (YYYY-MM-DD) |
| `date_to` | date | End date (YYYY-MM-DD) |

**Response:**
```json
{
  "success": true,
  "data": {
    "date_from": "2026-08-01",
    "date_to": "2026-08-04",
    "status_breakdown": [
      {
        "status": "paid",
        "count": 126,
        "total": 42000.00
      },
      {
        "status": "pending",
        "count": 18,
        "total": 7500.00
      },
      {
        "status": "failed",
        "count": 5,
        "total": 2200.00
      }
    ],
    "provider_breakdown": [
      {
        "provider": "chapa",
        "count": 149,
        "total": 51700.00
      }
    ],
    "method_breakdown": [
      {
        "method": "mobile_money",
        "count": 85,
        "total": 32000.00
      },
      {
        "method": "card",
        "count": 41,
        "total": 19700.00
      }
    ]
  }
}
```

### 3. Refund Report
```http
GET /cashier/reports/refund
```

**Query Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| `date_from` | date | Start date (YYYY-MM-DD) |
| `date_to` | date | End date (YYYY-MM-DD) |

**Response:**
```json
{
  "success": true,
  "data": {
    "date_from": "2026-08-01",
    "date_to": "2026-08-04",
    "total_refunded": 3500.00,
    "total_count": 7,
    "refunds_by_type": {
      "reservation": 2500.00,
      "order": 1000.00
    },
    "refunds_list": [
      {
        "id": "uuid",
        "tx_ref": "TX-20260803123456-XYZ98765",
        "amount": 500.00,
        "currency": "ETB",
        "customer_name": "Jane Smith",
        "type": "Restaurant Order",
        "refunded_at": "2026-08-03 15:45:00"
      }
    ]
  }
}
```

---

## 🔐 Authentication Header

All requests must include the Bearer token:

```javascript
// JavaScript/TypeScript Example
const headers = {
  'Authorization': `Bearer ${localStorage.getItem('token')}`,
  'Content-Type': 'application/json'
}
```

---

## ⚠️ Error Responses

### Authentication Error (401)
```json
{
  "message": "Unauthenticated."
}
```

### Authorization Error (403)
```json
{
  "message": "Access denied. Insufficient permissions."
}
```

### Not Found (404)
```json
{
  "success": false,
  "message": "Payment not found"
}
```

### Server Error (500)
```json
{
  "success": false,
  "message": "Failed to fetch payments",
  "error": "Error details..."
}
```

---

## 📝 Notes

1. **Currency**: All amounts are in Ethiopian Birr (ETB)
2. **Dates**: All dates are in `YYYY-MM-DD HH:MM:SS` format
3. **UUIDs**: All IDs are UUIDs, not integers
4. **Pagination**: Default is 15 items per page
5. **Timezone**: All timestamps are in server timezone

---

## 🧪 Testing with Postman/Thunder Client

### Example Request (Get Payments):
```http
GET http://127.0.0.1:8000/api/cashier/payments?filter=today
Authorization: Bearer your-token-here
Content-Type: application/json
```

### Example Request (Process Refund):
```http
POST http://127.0.0.1:8000/api/cashier/payments/{payment-uuid}/refund
Authorization: Bearer your-token-here
Content-Type: application/json
```

---

**API Version**: 1.0  
**Last Updated**: August 4, 2026
