# SRS - Multi-Hotel Restaurant Management System
**Version:** 1.0  
**Date:** September 2026  
**Status:** Complete Specification  
**Project Name:** Restaurant Management System with Multi-Hotel Support


---
## Executive Summary

The Restaurant Management System (RMS) is an enterprise-grade, multi-hotel platform designed to manage restaurant operations across multiple hotel chains. The system supports multiple properties under unified management while maintaining complete data isolation between hotels. It enables guests to book rooms, order food, manage payments, and provides staff with comprehensive operational dashboards.

---

## Table of Contents

1. [System Overview](#system-overview)
2. [Business Requirements](#business-requirements)
3. [System Architecture](#system-architecture)
4. [Functional Requirements](#functional-requirements)
5. [Non-Functional Requirements](#non-functional-requirements)
6. [Data Model & Schema](#data-model--schema)
7. [API Specifications](#api-specifications)
8. [Security & Access Control](#security--access-control)
9. [Integration Points](#integration-points)
10. [Deployment & DevOps](#deployment--devops)

---

## 1. System Overview

### 1.1 Purpose
Enable hotel chains to operate multiple properties through a single integrated platform while:
- Maintaining separate accounting per hotel
- Enforcing complete data isolation
- Sharing operational infrastructure
- Providing unified reporting and analytics
- Enabling seamless guest experiences across properties

### 1.2 Key Objectives

| Objective | Impact |
|-----------|--------|
| Multi-hotel support | Support unlimited hotel chains under one platform |
| Data isolation | 100% guarantee of cross-tenant data security |
| Guest experience | Seamless booking and ordering without authentication |
| Staff efficiency | Real-time dashboards and automated workflows |
| Scalability | Support growth to 1000+ properties |
| Reliability | 99.9% uptime SLA |

### 1.3 Scope

**In Scope:**
- Multi-hotel platform architecture
- Guest room booking management
- Restaurant order management
- Payment processing
- Staff role-based access control
- Reporting and analytics
- QR code-based guest ordering
- Multi-currency support

**Out of Scope:**
- Housekeeping management
- Laundry service tracking
- Complex financial accounting
- Payroll management
- HR systems

---

## 2. Business Requirements

### 2.1 Multi-Hotel Operations

**BR1: Hotel Onboarding**
- System must support onboarding of new hotels
- Each hotel has unique: branding, menu, staff, rooms, rates
- Onboarding must include initial admin user creation
- All hotel data must be isolated from other properties

**BR2: Data Isolation**
- No cross-hotel data leakage possible
- Even accidental queries must respect hotel boundaries
- Shared infrastructure must transparently enforce isolation
- Audit logs must track cross-hotel access attempts

**BR3: Shared Infrastructure**
- Single codebase supports all hotels
- Shared database infrastructure with tenant scoping
- Shared API endpoints with dynamic hotel routing
- Common authentication and authorization framework

### 2.2 Guest Operations

**BR4: QR-Based Booking**
- Guests can scan room QR codes to book without login
- Booking confirmation via email
- Guest history tracked without authentication
- One-click room service ordering from booking confirmation

**BR5: Order Management**
- Guests can order food via QR code from their room
- Walk-in customers can order via table QR code
- Real-time order tracking
- Payment at order completion

**BR6: Payment Processing**
- Multiple payment methods supported (Chapa, card, cash)
- Real-time payment verification
- Automatic order fulfillment on payment
- Failed payment retry mechanisms

### 2.3 Staff Operations

**BR7: Role-Based Access**
- Platform Admin: Full platform control
- Hotel Admin: Hotel-level administration
- Manager: Department-level management
- Receptionist: Check-in/check-out management
- Cashier: Payment and billing
- Chef: Kitchen order management
- Waiter: Guest service and table management

**BR8: Hotel Switching**
- Staff can be assigned to multiple hotels
- Seamless switching without re-authentication
- Context automatically updates when switching
- All queries automatically respect current hotel context

### 2.4 Business Analytics

**BR9: Multi-Hotel Reporting**
- Platform-level aggregated metrics
- Per-hotel detailed reports
- Cross-property comparison analytics
- Revenue tracking and forecasting

**BR10: Real-Time Dashboards**
- Kitchen: Orders by priority and preparation time
- Cashier: Payments and reconciliation
- Manager: Occupancy, revenue, staffing
- Platform Admin: Multi-hotel KPIs


---

## 3. System Architecture

### 3.1 High-Level Architecture

\\\
Frontend (Vue 3 + TypeScript)
+-- Responsive Web
+-- Mobile PWA
         |
         | REST API + Sanctum Auth
         |
        \/
API Gateway & Load Balancing
+-- Rate limiting per hotel
+-- Request logging
+-- SSL/TLS termination
         |
        \/
Laravel Backend (Multi-Tenant)
+-- Sanctum Authentication
+-- IdentifyTenant Middleware
+-- TenantContext Service
+-- Global Query Scoping
+-- Controllers -> Services -> Models
         |
        \/
PostgreSQL Database
+-- Shared schema with hotel_id
+-- Composite unique constraints
+-- Row-level security
+-- Automatic query scoping
         |
        \/
External Integrations
+-- Chapa Payment Gateway
+-- Email Service
+-- SMS Service
+-- QR Code Generation
+-- File Storage (S3/MinIO)
\\\

### 3.2 Multi-Tenancy Approach

**Strategy: Shared Database with Row-Level Security (RLS)**

- All hotels share one database
- Each table has 'hotel_id' column (UUID foreign key)
- Laravel Global Scope automatically appends WHERE hotel_id = current context
- TenantContext service manages current hotel context per request
- IdentifyTenant middleware sets context from request headers
- TenantScope trait automatically applied to all models

### 3.3 Request Flow

Request -> IdentifyTenant Middleware -> Sanctum Auth -> Authorization Policies -> Controller -> Service Layer -> Models with TenantScope -> Database

---

## 4. Functional Requirements

### 4.1 Hotel Onboarding & Management

**F1: Register New Hotel**
- Actor: Platform Admin
- Input: Hotel name, location, logo, contact info, admin credentials
- Process:
  1. Create Hotel record with UUID
  2. Create Hotel Admin user
  3. Create hotel_users membership record
  4. Send admin welcome email
- Output: Hotel ID, admin credentials
- Constraints: Admin cannot modify other hotels

**F2: Hotel Configuration**
- Actor: Hotel Admin
- Input: Menu categories, room types, staff roles, tax rates, currency
- Process:
  1. Update hotel settings (scoped to hotel)
  2. Create room types
  3. Configure tax structure
  4. Set payment methods
- Output: Configuration saved
- Constraints: Scoped to current hotel only

**F3: Staff Management**
- Actor: Hotel Admin
- Input: Staff name, email, role, hotel assignment
- Process:
  1. Create user (if new)
  2. Create hotel_users record
  3. Send welcome email
- Output: Staff added to team
- Constraints: Can only add to current hotel

### 4.2 Guest Operations

**F4: QR Code Resolution**
- Actor: Guest (unauthenticated)
- Input: QR token from room
- Process:
  1. Parse QR code
  2. Validate token against Room.qr_token
  3. Extract hotel_id and room_id
  4. Return room details
- Output: Room details, hotel info, guest context
- Error: Invalid token -> 404

**F5: Room Booking**
- Actor: Guest
- Input: Room ID, check-in, check-out, guest details, payment info
- Process:
  1. Validate room belongs to hotel
  2. Check availability
  3. Calculate total price
  4. Create Guest record (scoped to hotel)
  5. Create Reservation record
  6. Process payment
  7. Send confirmation email
- Output: Booking reference
- Error: Unavailable room -> 409

**F6: Booking Status Check**
- Actor: Guest
- Input: Confirmation token
- Process:
  1. Lookup reservation by token
  2. Return booking details
- Output: Booking status, room details
- Constraints: Token is private (email only)

### 4.3 Order Management

**F7: Guest Orders from QR**
- Actor: Guest
- Input: QR token, menu items with quantities
- Process:
  1. Resolve QR token
  2. Fetch menu items (scoped to hotel)
  3. Calculate totals
  4. Create Order record
  5. Create OrderItems
  6. Notify kitchen via WebSocket
- Output: Order created, order ID
- Error: Invalid items -> 400

**F8: Kitchen Dashboard**
- Actor: Chef
- Input: Filter by status, priority
- Process:
  1. Fetch orders (scoped to hotel)
  2. Filter by status
  3. Sort by priority
- Output: Real-time order list
- Constraints: Chef only sees hotel's orders

**F9: Payment Processing**
- Actor: Guest or Cashier
- Input: Order/Booking ID, payment method, amount
- Process:
  1. Validate amount
  2. Create Payment record
  3. Call Chapa API
  4. On webhook: update status
  5. Fulfill order
- Output: Payment status, receipt
- Error: Payment gateway down -> queue for retry

### 4.4 Reporting & Analytics

**F10: Hotel Dashboard**
- Actor: Manager, Hotel Admin
- Input: Date range, filters (optional)
- Process:
  1. Fetch hotel metrics (occupancy, revenue, orders)
  2. Aggregate by day/week/month
  3. Show trends
- Output: Dashboard with KPIs
- Constraints: Only current hotel's data

**F11: Platform Analytics**
- Actor: Platform Admin
- Input: Date range, hotel filter (optional)
- Process:
  1. Aggregate metrics across all hotels
  2. Show total revenue, occupancy
  3. Top performing hotels
- Output: Platform-wide analytics
- Constraints: Platform admin only

---

## 5. Non-Functional Requirements

### 5.1 Performance Requirements

| Requirement | Target | Justification |
|------------|--------|---------------|
| API Response Time (p95) | < 200ms | Guest satisfaction |
| Order Notification | < 2 sec | Kitchen responsiveness |
| Report Generation | < 5 sec | Usability |
| Login Time | < 1 sec | Staff efficiency |
| Mobile App Load | < 3 sec | Guest experience |
| Database Query | < 100ms | Scalability |

### 5.2 Reliability Requirements

| Requirement | SLA | Implementation |
|------------|-----|-----------------|
| System Uptime | 99.9% | Load balancing + failover |
| Data Durability | 99.999% | Daily backups + replication |
| Payment Retry | Automatic | Queue system (3 retries) |
| Order Recovery | Auto-rollback | Database transactions |

### 5.3 Security Requirements

| Requirement | Implementation |
|------------|-----------------|
| Cross-hotel isolation | Global Scope + policies |
| Payment encryption | TLS + PCI DSS |
| Authentication | Sanctum JWT tokens |
| Authorization | Role-based policies |
| Rate limiting | 100 req/min per hotel |
| Audit logging | All actions logged |
| SQL injection prevention | Parameterized queries |

### 5.4 Scalability Requirements

| Component | Capacity | Strategy |
|-----------|----------|----------|
| Hotels | 1000+ | Database sharding if needed |
| Users per hotel | 500+ | Horizontal scaling |
| Concurrent sessions | 5000+ | Load balancing |
| Orders per day | 100k+ | Caching + indexes |
| API requests/sec | 1000+ | CDN + caching |

### 5.5 Availability Requirements

| Metric | Target |
|--------|--------|
| MTTR | < 15 minutes |
| MTTF | > 720 hours |
| RPO | < 1 hour |
| RTO | < 4 hours |

---

## 6. Data Model & Schema

### 6.1 Core Tables

**hotels**
\\\sql
CREATE TABLE hotels (
    id UUID PRIMARY KEY,
    name VARCHAR(255) NOT NULL UNIQUE,
    location VARCHAR(255),
    city VARCHAR(100),
    phone VARCHAR(20),
    email VARCHAR(255),
    logo_url VARCHAR(500),
    currency VARCHAR(3) DEFAULT 'USD',
    tax_rate DECIMAL(5,2) DEFAULT 0,
    status ENUM('active','inactive','suspended') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
\\\

**users**
\\\sql
CREATE TABLE users (
    id UUID PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255),
    first_name VARCHAR(100),
    last_name VARCHAR(100),
    is_platform_admin BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    last_login_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
\\\

**hotel_users (Membership Pivot)**
\\\sql
CREATE TABLE hotel_users (
    id UUID PRIMARY KEY,
    hotel_id UUID NOT NULL REFERENCES hotels(id) ON DELETE CASCADE,
    user_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    role ENUM('admin','manager','receptionist','cashier','chef','waiter','guest') DEFAULT 'guest',
    is_active BOOLEAN DEFAULT TRUE,
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(hotel_id, user_id),
    INDEX idx_hotel_id(hotel_id),
    INDEX idx_role(role)
);
\\\

**rooms**
\\\sql
CREATE TABLE rooms (
    id UUID PRIMARY KEY,
    hotel_id UUID NOT NULL REFERENCES hotels(id) ON DELETE CASCADE,
    room_number VARCHAR(20) NOT NULL,
    room_type_id UUID NOT NULL,
    floor_number INT,
    qr_token VARCHAR(50) UNIQUE NOT NULL,
    status ENUM('active','inactive','maintenance') DEFAULT 'active',
    price_per_night DECIMAL(10,2),
    description TEXT,
    is_available BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(hotel_id, room_number),
    INDEX idx_hotel_id(hotel_id)
);
\\\

**reservations**
\\\sql
CREATE TABLE reservations (
    id UUID PRIMARY KEY,
    hotel_id UUID NOT NULL REFERENCES hotels(id) ON DELETE CASCADE,
    guest_id UUID NOT NULL,
    room_id UUID NOT NULL REFERENCES rooms(id) ON DELETE CASCADE,
    booking_reference VARCHAR(50) NOT NULL,
    confirmation_token VARCHAR(255) NOT NULL UNIQUE,
    check_in_date DATE NOT NULL,
    check_out_date DATE NOT NULL,
    status ENUM('pending','confirmed','cancelled','completed') DEFAULT 'pending',
    total_price DECIMAL(10,2),
    discount DECIMAL(10,2) DEFAULT 0,
    tax DECIMAL(10,2) DEFAULT 0,
    final_price DECIMAL(10,2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(hotel_id, booking_reference),
    INDEX idx_hotel_id(hotel_id),
    INDEX idx_status(status)
);
\\\

**orders**
\\\sql
CREATE TABLE orders (
    id UUID PRIMARY KEY,
    hotel_id UUID NOT NULL REFERENCES hotels(id) ON DELETE CASCADE,
    order_number VARCHAR(50) NOT NULL,
    guest_id UUID REFERENCES guests(id),
    room_id UUID REFERENCES rooms(id),
    order_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pending','confirmed','cooking','ready','completed','cancelled') DEFAULT 'pending',
    source ENUM('room','table','walk_in') DEFAULT 'room',
    subtotal DECIMAL(10,2),
    tax DECIMAL(10,2),
    total DECIMAL(10,2),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(hotel_id, order_number),
    INDEX idx_hotel_id(hotel_id),
    INDEX idx_status(status)
);
\\\

**menu_items**
\\\sql
CREATE TABLE menu_items (
    id UUID PRIMARY KEY,
    hotel_id UUID NOT NULL REFERENCES hotels(id) ON DELETE CASCADE,
    category_id UUID NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    is_available BOOLEAN DEFAULT TRUE,
    preparation_time_minutes INT DEFAULT 20,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(hotel_id, name),
    INDEX idx_hotel_id(hotel_id)
);
\\\

**payments**
\\\sql
CREATE TABLE payments (
    id UUID PRIMARY KEY,
    hotel_id UUID NOT NULL REFERENCES hotels(id) ON DELETE CASCADE,
    order_id UUID REFERENCES orders(id),
    reservation_id UUID REFERENCES reservations(id),
    amount DECIMAL(10,2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'USD',
    payment_method ENUM('chapa','cash','card') DEFAULT 'chapa',
    status ENUM('pending','completed','failed','refunded') DEFAULT 'pending',
    transaction_id VARCHAR(255),
    chapa_tx_ref VARCHAR(255),
    chapa_checkout_url VARCHAR(500),
    paid_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_hotel_id(hotel_id),
    INDEX idx_status(status)
);
\\\

### 6.2 Schema Relationships

\\\
Hotel (1) ---------------- (N) User (via HotelUser pivot)
  �
  +--(1:N)------ Room
  +--(1:N)------ Guest
  +--(1:N)------ Reservation
  +--(1:N)------ Order
  +--(1:N)------ MenuItem
  +--(1:N)------ Category
  +--(1:N)------ Payment

Room (1) ------------ (N) Reservation
Guest (1) ------------ (N) Reservation
Reservation (1) -------------- (N) Payment

Room (1) ------------ (N) Order
Guest (1) ------------ (N) Order
Order (1) -------------- (N) OrderItem
MenuItem (1) -------------- (N) OrderItem
Order (1) -------------- (N) Payment
\\\

### 6.3 Multi-Tenancy Constraints

All shared tables use composite unique constraints:

\\\sql
UNIQUE(hotel_id, room_number)
UNIQUE(hotel_id, booking_reference)
UNIQUE(hotel_id, order_number)
UNIQUE(hotel_id, menu_item_name)
UNIQUE(hotel_id, category_name)
\\\


---

## 7. API Specifications

### 7.1 Authentication Endpoints

**POST /api/auth/login**
\\\json
Request:
{
  "email": "user@example.com",
  "password": "password123"
}

Response (200):
{
  "token": "JWT_TOKEN_HERE",
  "user": {
    "id": "uuid",
    "email": "user@example.com",
    "first_name": "John",
    "hotels": [
      {
        "id": "hotel-uuid-1",
        "name": "Executive Horizon Hotel",
        "role": "admin"
      }
    ]
  }
}

Error (401):
{
  "error": "Unauthorized",
  "message": "Invalid credentials"
}
\\\

**POST /api/auth/switch-hotel**
\\\json
Request:
{
  "hotel_id": "hotel-uuid-2"
}

Response (200):
{
  "success": true,
  "current_hotel": {
    "id": "hotel-uuid-2",
    "name": "Grand Plaza Hotel"
  }
}

Error (403):
{
  "error": "Forbidden",
  "message": "User not assigned to this hotel"
}
\\\

### 7.2 Guest Booking Endpoints

**POST /api/guest/bookings**
\\\json
Request:
{
  "hotel_id": "hotel-uuid-1",
  "room_id": "room-uuid-1",
  "check_in_date": "2024-12-25",
  "check_out_date": "2024-12-26",
  "guest_details": {
    "first_name": "John",
    "last_name": "Doe",
    "email": "john@example.com",
    "phone": "+1234567890"
  },
  "payment_method": "chapa"
}

Response (201):
{
  "booking": {
    "id": "booking-uuid-1",
    "booking_reference": "BK20241225ABC1",
    "status": "pending",
    "total_price": 150.00
  },
  "payment": {
    "checkout_url": "https://chapa.co/checkout/...",
    "tx_ref": "chapa_tx_ref_12345"
  }
}

Error (409):
{
  "error": "Conflict",
  "message": "Room not available for selected dates"
}
\\\

**GET /api/guest/bookings/{confirmation_token}/status**
\\\json
Request:
GET /api/guest/bookings/secret_token_12345/status

Response (200):
{
  "booking": {
    "booking_reference": "BK20241225ABC1",
    "status": "confirmed",
    "room_number": "101",
    "check_in": "2024-12-25",
    "check_out": "2024-12-26",
    "total_price": 150.00,
    "hotel_name": "Executive Horizon Hotel"
  }
}

Error (404):
{
  "error": "Not Found",
  "message": "Booking not found or token invalid"
}
\\\

### 7.3 Guest Order Endpoints

**POST /api/guest/orders**
\\\json
Request:
{
  "qr_token": "HOTEL_ROOM_TOKEN",
  "items": [
    {
      "menu_item_id": "item-uuid-1",
      "quantity": 2,
      "special_instructions": "No onions"
    }
  ],
  "payment_method": "chapa",
  "notes": "Deliver in 20 minutes"
}

Response (201):
{
  "order": {
    "id": "order-uuid-1",
    "order_number": "ORD-001",
    "status": "confirmed",
    "subtotal": 450.00,
    "tax": 45.00,
    "total": 495.00,
    "estimated_delivery_time": "20 minutes"
  },
  "payment": {
    "status": "pending",
    "checkout_url": "https://chapa.co/checkout/..."
  }
}
\\\

**GET /api/guest/orders/{order_id}**
\\\json
Request:
GET /api/guest/orders/order-uuid-1

Response (200):
{
  "order": {
    "id": "order-uuid-1",
    "order_number": "ORD-001",
    "status": "cooking",
    "items": [
      {
        "name": "Grilled Fish",
        "quantity": 2,
        "price": 225.00
      }
    ],
    "total": 495.00,
    "estimated_completion_time": "18 minutes"
  }
}
\\\

### 7.4 Kitchen Dashboard Endpoints

**GET /api/kitchen/orders**
\\\json
Request:
GET /api/kitchen/orders?status=pending,cooking&sort=order_time

Headers:
X-Hotel-ID: hotel-uuid-1
Authorization: Bearer JWT_TOKEN

Response (200):
{
  "orders": [
    {
      "id": "order-uuid-1",
      "order_number": "ORD-001",
      "status": "pending",
      "room_number": "101",
      "items": [
        {
          "id": "item-uuid-1",
          "name": "Grilled Fish",
          "quantity": 2,
          "preparation_time": 20,
          "order_time": "2024-12-25T14:30:00Z"
        }
      ]
    }
  ],
  "total": 15,
  "pending_count": 8
}
\\\

**PATCH /api/kitchen/orders/{order_id}/items/{item_id}/status**
\\\json
Request:
{
  "status": "ready"
}

Response (200):
{
  "order_item": {
    "id": "item-uuid-1",
    "status": "ready",
    "completed_at": "2024-12-25T14:48:00Z"
  }
}
\\\

### 7.5 Admin/Staff Endpoints

**GET /api/admin/hotels** (Platform Admin Only)
\\\json
Request:
GET /api/admin/hotels

Headers:
Authorization: Bearer PLATFORM_ADMIN_TOKEN

Response (200):
{
  "hotels": [
    {
      "id": "hotel-uuid-1",
      "name": "Executive Horizon Hotel",
      "location": "New York",
      "status": "active",
      "total_rooms": 150,
      "total_staff": 45,
      "total_bookings": 1250,
      "revenue_today": 15000.00
    }
  ],
  "pagination": {
    "total": 25,
    "page": 1,
    "per_page": 10
  }
}
\\\

**GET /api/admin/dashboards/platform** (Platform Admin Only)
\\\json
Request:
GET /api/admin/dashboards/platform?date_from=2024-12-01&date_to=2024-12-31

Response (200):
{
  "summary": {
    "total_hotels": 25,
    "total_revenue": 5250000.00,
    "total_bookings": 31250,
    "total_orders": 125000,
    "average_occupancy": 0.85,
    "active_users": 1250
  },
  "top_hotels": [
    {
      "id": "hotel-uuid-1",
      "name": "Executive Horizon Hotel",
      "revenue": 225000.00,
      "bookings": 1250
    }
  ],
  "daily_metrics": [
    {
      "date": "2024-12-01",
      "revenue": 175000.00,
      "bookings": 1050,
      "orders": 4200
    }
  ]
}
\\\

**GET /api/admin/dashboards/hotel** (Hotel Admin/Manager)
\\\json
Request:
GET /api/admin/dashboards/hotel?date_from=2024-12-01&date_to=2024-12-31

Headers:
X-Hotel-ID: hotel-uuid-1

Response (200):
{
  "summary": {
    "revenue_today": 15000.00,
    "occupancy_rate": 0.95,
    "total_bookings": 1250,
    "total_orders": 5200,
    "pending_orders": 12,
    "staff_present": 38
  },
  "occupancy": [
    {
      "date": "2024-12-01",
      "occupied_rooms": 140,
      "total_rooms": 150,
      "occupancy_rate": 0.93
    }
  ],
  "revenue": [
    {
      "date": "2024-12-01",
      "room_revenue": 21000.00,
      "food_revenue": 8500.00,
      "other_revenue": 1200.00,
      "total": 30700.00
    }
  ]
}
\\\

---

## 8. Security & Access Control

### 8.1 Authentication Strategy

**Mechanism:** Laravel Sanctum (JWT tokens)

- Token-based authentication (stateless)
- 24-hour token expiration
- Refresh token for extending sessions
- Automatic token revocation on logout
- Support for API tokens for system integrations

### 8.2 Authorization & RBAC

**Role Hierarchy:**

\\\
Platform Admin (is_platform_admin = true)
+- Onboard new hotels
+- View platform analytics
+- Manage all hotels
+- Manage all users
+- Access platform settings

Hotel Admin (role = admin in hotel_users)
+- Manage hotel staff
+- Configure hotel (menu, rooms, rates)
+- View hotel analytics
+- Approve bookings
+- Manage hotel settings

Manager (role = manager)
+- View dashboard
+- View bookings & orders
+- Generate reports
+- Manage staff schedules
+- Handle complaints

Receptionist (role = receptionist)
+- Check-in/check-out guests
+- View room bookings
+- Manage reservations
+- Generate invoices

Cashier (role = cashier)
+- Process payments
+- View payment history
+- Print receipts
+- Generate payment reports

Chef (role = chef)
+- View kitchen orders
+- Mark items complete
+- View ingredient inventory
+- Manage kitchen staff

Waiter (role = waiter)
+- Take guest orders
+- Update order status
+- Serve tables
+- Process cash payments
\\\

### 8.3 Multi-Tenant Data Isolation

**Three-Layer Security:**

**Layer 1: Middleware Validation**
\\\
- Read X-Hotel-ID header
- Validate user is member of hotel
- Check active membership
- Reject if unauthorized (403)
- Set TenantContext for request
\\\

**Layer 2: Database Query Scoping**
\\\
- TenantScope trait on all models
- Automatically append WHERE hotel_id = context
- Works with relationships and eager loading
- Prevents accidental unscoped queries
\\\

**Layer 3: Policy Authorization**
\\\
- Resource policies verify hotel_id match
- RoomPolicy::view() checks room->hotel_id
- ReservationPolicy checks reservation->hotel_id
- All operations scoped to current hotel
\\\

### 8.4 Data Protection

| Data Type | Protection | Method |
|-----------|-----------|--------|
| Passwords | Hashed | bcrypt (Laravel default) |
| Tokens | Encrypted | AES-256 in database |
| Payment Data | Encrypted | TLS + PCI DSS compliance |
| API Keys | Encrypted | Environment variables + vault |
| Session Data | Secure | HttpOnly + SameSite cookies |
| Audit Logs | Immutable | Append-only with signatures |

### 8.5 API Security

- **Rate Limiting:** 100 requests/min per hotel
- **CORS:** Whitelist frontend domains only
- **CSRF:** Token validation on state-changing requests
- **SQL Injection:** Prepared statements (Eloquent ORM)
- **XSS:** Output escaping in templates
- **HTTPS:** TLS 1.2+ enforced on all endpoints
- **API Versioning:** Version in URL (/api/v1/)
- **Input Validation:** Strict schema validation
- **Output Filtering:** Only expose necessary fields

---

## 9. Integration Points

### 9.1 Payment Gateway (Chapa)

**Workflow:**
1. Order/Booking creation ? Generate checkout URL
2. Guest submits payment ? Redirect to Chapa
3. Chapa processes payment ? Webhook callback
4. Verify payment status ? Update order/reservation
5. Send receipt email

**Error Handling:**
- Payment gateway down ? Queue for retry (max 3 retries)
- Network timeout ? Retry after 5 seconds
- Invalid credentials ? Log and alert admin
- Duplicate payment ? Idempotency key prevents double-charging

**Webhook Security:**
- Verify Chapa signature on callback
- Validate transaction exists in database
- Prevent replay attacks with nonce
- Log all payment events

### 9.2 Email Service

**Triggered Events:**
- Guest booking confirmation
- Payment receipt and invoice
- Order status updates
- Staff notifications
- Admin alerts and reports

**Implementation:** Async queue (Redis) for reliable delivery

### 9.3 SMS Notifications (Optional)

**Supported Events:**
- Order ready notification
- Booking reminder (24 hours before)
- Payment failed alert
- Guest arrival reminder
- Special offers and promotions

### 9.4 File Storage

**Storage Options:**
- S3 (AWS) for production
- MinIO for self-hosted deployments
- Local storage for development

**Stored Files:**
- Guest receipts (PDF)
- Hotel logos and images
- Menu item photos
- Room images and galleries
- Reports (Excel/PDF exports)

**Security:** Signed URLs with time-limited expiry (15 minutes)

### 9.5 Real-Time Notifications (WebSocket)

**Real-Time Events:**
- Kitchen: New order notifications
- Waiter: Table assignments and status changes
- Guest: Order status updates
- Admin: Dashboard metrics and alerts

**Implementation Options:**
- Laravel WebSockets (self-hosted)
- Pusher (managed service)
- Redis Pub/Sub with polling fallback

---

## 10. Deployment & DevOps

### 10.1 Infrastructure Architecture

\\\
Domain: restaurant-system.com
          |
          v
    CDN (Cloudflare)
    - Image delivery
    - DDoS protection
          |
          v
Load Balancer (HAProxy/AWS ALB)
- SSL/TLS termination
- Request routing
- Session persistence
          |
    +-----+-----+
    |           |
    v           v
API Server 1  API Server 2  (2-5 replicas)
- Laravel app - Background jobs
- PHP-FPM     - Queue workers
- Session mgmt - Cache
    |           |
    +-----+-----+
          |
          v
PostgreSQL Cluster
- Primary (write)
- Replica (read-only)
- Automated backups
          |
          v
Redis Cache
- Session storage
- Job queue
- Real-time data
          |
          v
External Services
- Chapa API
- Email service
- S3 storage
- Monitoring
\\\

### 10.2 Environment Configuration

\\\
Development:
- Single server setup
- SQLite or local PostgreSQL
- File storage for uploads
- Email to log file

Staging:
- 2-3 replicated servers
- PostgreSQL with read replica
- S3 or MinIO storage
- Real email service
- Full monitoring enabled

Production:
- 5+ load-balanced servers
- PostgreSQL cluster with failover
- S3 storage with CDN
- Third-party email service
- Comprehensive monitoring & alerts
- Daily backups + disaster recovery
\\\

### 10.3 Backup & Disaster Recovery

**Database Backups:**
- Hourly incremental backups
- Daily full backups
- 30-day retention policy
- Encrypted and stored in S3
- Point-in-time recovery available

**Recovery Objectives:**
- RPO (Recovery Point Objective): < 1 hour
- RTO (Recovery Time Objective): < 4 hours
- Monthly restore tests to verify integrity

### 10.4 Monitoring & Alerting

**Key Metrics:**
- API response time (p50, p95, p99)
- Error rate (5xx errors)
- Database query time
- Cache hit ratio
- Queue depth and processing time
- Memory & CPU utilization
- Disk space usage
- Payment success rate
- Order processing time

**Alert Thresholds:**
- Response time p95 > 500ms ? Warning
- Error rate > 0.1% ? Alert
- Payment failures > 5% ? Critical
- Queue depth > 1000 ? Warning
- Disk space < 10% ? Critical
- Database connection > 90% ? Warning

**Monitoring Stack:** Prometheus + Grafana + Alertmanager

### 10.5 CI/CD Pipeline

**Stages:**
1. Code commit to Git
2. Automated tests (unit + integration)
3. Code quality analysis (SonarQube)
4. Security scanning
5. Build Docker image
6. Push to registry
7. Deploy to staging
8. Smoke tests
9. Load tests
10. Manual QA approval
11. Blue-green deployment to production
12. Health checks and monitoring


---

## 11. Testing & Quality Assurance

### 11.1 Unit Testing

**Coverage Target:** > 80%

- Test individual functions/methods
- Mock external dependencies
- Test edge cases and error conditions
- Use PHPUnit for backend
- Use Vitest for frontend

**Key Areas:**
- Authentication and authorization
- Payment processing
- Order management
- Data validation
- Multi-tenant isolation

### 11.2 Integration Testing

**Scope:** Test interaction between components

- API endpoint tests
- Database transaction tests
- Payment gateway integration
- Email service integration
- WebSocket real-time events

**Test Scenarios:**
- Complete booking flow
- Multi-hotel data isolation
- Staff role-based access
- Payment webhook processing
- Order fulfillment workflow

### 11.3 End-to-End Testing

**User Workflows:**
- Guest QR scan -> Room booking -> Payment -> Confirmation
- Guest room ordering -> Kitchen preparation -> Delivery
- Staff check-in -> Check-out process
- Payment reconciliation
- Report generation

**Tools:** Cypress, Selenium

### 11.4 Performance Testing

**Benchmarks:**
- Single API endpoint: < 200ms (p95)
- Database query: < 100ms
- Page load: < 3 seconds
- Concurrent users: 5000+
- Orders per second: 500+

**Tools:** Apache JMeter, Locust

### 11.5 Security Testing

- SQL injection vulnerability scanning
- Cross-site scripting (XSS) testing
- Cross-site request forgery (CSRF) testing
- Multi-tenant data isolation verification
- Payment data encryption verification
- API authentication/authorization testing

**Tools:** OWASP ZAP, Burp Suite

### 11.6 Load Testing

- 5000 concurrent users
- 100 orders per second
- Database query performance under load
- Payment gateway throughput
- WebSocket connections

---

## 12. Maintenance & Support

### 12.1 System Health Monitoring

**Daily Checks:**
- Uptime percentage
- Error rates
- Database health
- Backup status
- Disk space usage

**Weekly Reports:**
- Performance metrics
- Top errors
- Payment success rate
- API usage by hotel
- Infrastructure utilization

### 12.2 Incident Management

**Severity Levels:**

| Level | Response Time | Resolution Time | Example |
|-------|--------------|-----------------|---------|
| Critical | 15 min | 1 hour | Payment system down |
| High | 1 hour | 4 hours | Guest booking errors |
| Medium | 4 hours | 1 day | Performance degradation |
| Low | 1 day | 1 week | UI issues |

### 12.3 Change Management

**Process:**
1. Change request submitted
2. Impact assessment
3. Testing in staging
4. Approval from leadership
5. Deployment with monitoring
6. Post-deployment verification
7. Documentation update

**Rollback Plan:** Every change has a rollback procedure

### 12.4 Documentation

**Maintained Documents:**
- API documentation (auto-generated from code)
- Architecture guide (updated quarterly)
- Operations manual
- Troubleshooting guide
- Release notes
- Training materials

---

## 13. Scalability Roadmap

### 13.1 Phase 1 (Current - 50 Hotels)

- Single PostgreSQL instance
- 2-3 API servers
- Basic monitoring
- Manual scaling

### 13.2 Phase 2 (100-500 Hotels)

- PostgreSQL read replicas
- 5-10 API servers
- Advanced monitoring
- Auto-scaling groups
- Database optimization

### 13.3 Phase 3 (500-1000 Hotels)

- PostgreSQL sharding by hotel_id range
- 20+ API servers with auto-scaling
- Regional deployment
- Advanced caching strategies
- Dedicated kitchen service

### 13.4 Phase 4 (1000+ Hotels)

- Multi-datacenter deployment
- Full database sharding
- Microservices architecture
- Edge computing for order processing
- AI-powered demand forecasting

---

## 14. Glossary

| Term | Definition |
|------|-----------|
| Hotel | A property/tenant in the multi-hotel platform |
| Tenant | Logical isolation boundary (= Hotel) |
| Hotel Admin | User with admin role assigned to a hotel |
| Platform Admin | Super user with access to all hotels and platform settings |
| TenantContext | Request-scoped service managing current hotel |
| Global Scope | Eloquent feature auto-scoping database queries |
| BelongsToTenant | Trait that adds global scoping to models |
| QR Token | 8-character unique identifier in room/table QR code |
| Confirmation Token | Private token sent only via email for booking verification |
| Booking Reference | Public reference (e.g., BK20241225ABC1) |
| IDOR | Insecure Direct Object Reference (security vulnerability) |
| RLS | Row-Level Security enforcement |
| MTTR | Mean Time To Recover |
| MTTF | Mean Time To Failure |
| RPO | Recovery Point Objective |
| RTO | Recovery Time Objective |
| SLA | Service Level Agreement |

---

## 15. Appendices

### Appendix A: Common Workflows

**Workflow A1: Guest Books a Room**

\\\
1. Guest scans QR code on room
   -> QR token contains hotel_id and room_id

2. System resolves QR token
   -> Validates token
   -> Returns room details and hotel info
   -> Sets hotel context

3. Guest views room details
   -> Displays availability
   -> Shows price and amenities
   -> Shows special offers

4. Guest enters booking details
   -> Check-in date
   -> Check-out date
   -> Guest name and email
   -> Special requests

5. System calculates price
   -> Room rate per night
   -> Number of nights
   -> Tax calculation
   -> Apply discounts if applicable
   -> Final total

6. Guest selects payment method
   -> Choose Chapa
   -> Redirected to payment gateway

7. Payment processed
   -> Chapa processes card
   -> Returns confirmation

8. System creates records
   -> Create Guest record (scoped to hotel)
   -> Create Reservation record
   -> Update Payment status
   -> Generate booking reference

9. Confirmation email sent
   -> Booking reference
   -> Confirmation token (private)
   -> Check-in details
   -> Link to track booking

10. Guest receives email
    -> Can track booking using confirmation token
    -> Can view room details
    -> Can proceed to make room service order
\\\

**Workflow A2: Guest Orders Food**

\\\
1. Guest scans QR code in room
   -> QR token identifies room and hotel

2. System resolves QR
   -> Validates guest is checked in
   -> Returns available menu

3. Guest browses menu
   -> Filtered by hotel
   -> Shows prices and preparation time
   -> Shows dietary info

4. Guest selects items
   -> Adds quantity
   -> Special instructions per item

5. Guest reviews order
   -> Subtotal calculation
   -> Tax calculation
   -> Delivery fee (if applicable)
   -> Total amount

6. Guest selects payment
   -> Chapa online payment
   -> Or cash on delivery

7. Order created
   -> Order record with unique order_number
   -> OrderItems linked
   -> Status = pending
   -> Assigned to kitchen

8. Kitchen receives notification
   -> Real-time WebSocket alert
   -> Order appears in dashboard
   -> Sorted by priority/time

9. Kitchen processes order
   -> Marks items in-progress
   -> Sends updates to guest
   -> Marks items ready

10. Delivery
    -> Waiter receives notification
    -> Delivers to room
    -> Guest confirms receipt
    -> Marks order complete

11. Payment processed
    -> If Chapa: payment already done
    -> If cash: payment collected at delivery
    -> Receipt generated
\\\

**Workflow A3: Staff Switch Hotels**

\\\
1. Staff member logs in
   -> Email and password
   -> System validates credentials
   -> Returns list of assigned hotels

2. If single hotel assigned
   -> Automatically sets context to that hotel
   -> Redirects to dashboard

3. If multiple hotels assigned
   -> Displays hotel selector in navbar
   -> Shows current hotel
   -> Option to switch

4. Staff clicks switch hotel
   -> Submits request to /api/auth/switch-hotel
   -> System validates user is member
   -> Updates session context
   -> Sets X-Hotel-ID header

5. All subsequent queries
   -> Automatically scoped to selected hotel
   -> Cannot see other hotels' data
   -> Role permissions apply per hotel

6. Can switch back anytime
   -> Hotel selector available in navbar
   -> Context updates immediately
   -> No re-authentication needed
\\\

### Appendix B: Error Codes

| Code | Name | Meaning | Solution |
|------|------|---------|----------|
| 400 | Bad Request | Invalid request format | Check request format and parameters |
| 401 | Unauthorized | Missing or invalid token | Login again |
| 403 | Forbidden | Not allowed for this resource | Check permissions or switch hotel |
| 404 | Not Found | Resource doesn't exist | Verify resource ID or hotel context |
| 409 | Conflict | Resource already exists or unavailable | Check booking dates or try again |
| 422 | Unprocessable | Validation failed | Check field values |
| 429 | Too Many Requests | Rate limit exceeded | Wait before retrying |
| 500 | Server Error | Internal server error | Contact support |
| 503 | Service Unavailable | Service temporarily down | Try again later |

### Appendix C: Database Migration Steps

\\\ash
# 1. Create hotels table
php artisan migrate --path=database/migrations/2026_08_30_000001_create_hotels_table.php

# 2. Create hotel_users membership table
php artisan migrate --path=database/migrations/2026_08_30_000002_create_hotel_users_table.php

# 3. Create initial hotel and assign users
php artisan migrate --path=database/migrations/2026_08_30_000003_seed_default_hotel_and_attach_users.php

# 4. Add is_platform_admin flag to users
php artisan migrate --path=database/migrations/2026_08_30_000004_add_is_platform_admin_to_users_table.php

# 5. Add hotel_id foreign keys to all tenant tables
php artisan migrate --path=database/migrations/2026_08_30_000005_add_hotel_id_to_tenant_tables.php

# 6. Backfill existing rows with Hotel #1 id
php artisan migrate --path=database/migrations/2026_08_30_000006_backfill_existing_records_with_hotel_id.php

# 7. Update uniqueness constraints to composite
php artisan migrate --path=database/migrations/2026_08_30_000007_update_unique_constraints_for_multitenancy.php
\\\

### Appendix D: Configuration Examples

**Environment Variables (.env)**

\\\
# App
APP_NAME="Restaurant System"
APP_ENV=production
APP_DEBUG=false

# Database
DB_CONNECTION=pgsql
DB_HOST=db-primary.internal
DB_PORT=5432
DB_DATABASE=restaurant_system
DB_USERNAME=app_user
DB_PASSWORD=secure_password

# Redis
REDIS_HOST=redis.internal
REDIS_PORT=6379
REDIS_PASSWORD=redis_password

# Sanctum
SANCTUM_STATEFUL_DOMAINS=restaurant-system.com
SANCTUM_ALLOWED_ORIGINS=https://restaurant-system.com

# Payment (Chapa)
CHAPA_BASE_URL=https://api.chapa.co
CHAPA_SECRET_KEY=chapa_secret_key_here
CHAPA_PUBLIC_KEY=chapa_public_key_here

# Email
MAIL_DRIVER=smtp
MAIL_HOST=smtp.sendgrid.net
MAIL_PORT=587
MAIL_USERNAME=apikey
MAIL_PASSWORD=sendgrid_api_key
MAIL_FROM_ADDRESS=noreply@restaurant-system.com

# AWS S3
AWS_ACCESS_KEY_ID=aws_key
AWS_SECRET_ACCESS_KEY=aws_secret
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=restaurant-system-uploads

# Monitoring
SENTRY_DSN=sentry_dsn_url
DATADOG_API_KEY=datadog_key
\\\

### Appendix E: Deployment Checklist

- [ ] Database migrations completed
- [ ] Environment variables configured
- [ ] SSL certificates installed
- [ ] Redis cluster configured
- [ ] S3 buckets created
- [ ] Chapa API credentials verified
- [ ] Email service configured
- [ ] Monitoring agents installed
- [ ] Backup system tested
- [ ] Load balancer configured
- [ ] CDN configured
- [ ] API documentation deployed
- [ ] Frontend built and deployed
- [ ] Smoke tests passed
- [ ] Load tests passed
- [ ] Security scan passed
- [ ] Database backup restored and verified
- [ ] Staff training completed
- [ ] Support team briefed
- [ ] Go-live announcement sent

### Appendix F: Support & Contact

**Technical Support:** support@restaurant-system.com
**Sales:** sales@restaurant-system.com
**Emergency Hotline:** +1-XXX-XXX-XXXX
**Status Page:** status.restaurant-system.com

---

## Document History

| Version | Date | Author | Changes |
|---------|------|--------|---------|
| 1.0 | Sep 2026 | System | Initial comprehensive SRS for multi-hotel platform |

---

## Approvals

This Software Requirements Specification has been reviewed and approved by:

- [ ] Product Manager: _________________ Date: _______
- [ ] Technical Lead: _________________ Date: _______
- [ ] QA Lead: _________________ Date: _______
- [ ] Security Officer: _________________ Date: _______
- [ ] DevOps Lead: _________________ Date: _______
- [ ] Business Owner: _________________ Date: _______

---

## Revision Log

**Future Updates:**
- Quarterly reviews for performance metrics
- Annual security assessment
- Scalability roadmap updates
- Technology stack upgrades
- New feature requirements

---

## References

- [Laravel Documentation](https://laravel.com/docs)
- [Multi-Tenancy Architecture Patterns](https://www.postgresql.org/docs/current/row-security.html)
- [OWASP Security Guidelines](https://owasp.org/)
- [REST API Best Practices](https://restfulapi.net/)
- [Database Design Principles](https://use-the-index-luke.com/)

---

**END OF DOCUMENT**

