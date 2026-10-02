# COMPREHENSIVE REQUIREMENTS DOCUMENT
## Multi-Hotel Restaurant & Guest Management System

**Document Type:** Business Requirements Specification (BRS)  
**Prepared By:** Requirements Analyst  
**Date:** September 2026  
**Version:** 2.0  
**Status:** Complete Code Analysis  
**Scope:** End-to-End System Requirements

---

## EXECUTIVE SUMMARY

This document provides a comprehensive analysis of the **Multi-Hotel Restaurant & Guest Management System**, capturing all functional and non-functional requirements through deep code analysis. The system is an enterprise-grade, multi-tenant platform designed to manage hotels with integrated restaurant operations, guest services, staff management, and business analytics.

### System Scope
The system encompasses:
- Multi-hotel/multi-property management with complete data isolation
- Guest room management with QR code-based booking and check-in
- Restaurant ordering system (room service + walk-in)
- Payment processing integration
- Staff role-based access control (7+ roles)
- Real-time dashboards and reporting
- Complaint and notification management
- Advanced analytics and business intelligence

### Key Stakeholders
- **Platform Admin:** System-wide management and monitoring
- **Hotel Admin:** Hotel configuration and staff management
- **Manager:** Operational oversight and reporting
- **Receptionist:** Check-in/check-out and guest services
- **Chef:** Kitchen order management
- **Waiter:** Guest service and table management
- **Cashier:** Payment processing and reconciliation
- **Guests:** Room booking, ordering, and reviews

---

## 1. SYSTEM ARCHITECTURE & DATA MODEL

### 1.1 Multi-Tenancy Architecture

The system uses a **Shared Database with Row-Level Security (RLS)** multi-tenancy model:

\\\
+-------------------------------------+
¦   Single Laravel Application        ¦
¦   Single PostgreSQL Database        ¦
¦   Row-Level Security via Scopes     ¦
+-------------------------------------+
        ¦
        +- Hotel 1 (completely isolated)
        +- Hotel 2 (completely isolated)
        +- Hotel 3 (completely isolated)
        +- Hotel N (completely isolated)
\\\

**Isolation Mechanism:**
- Every tenant table has a \hotel_id\ foreign key
- \TenantScope\ global Eloquent scope auto-appends \WHERE hotel_id = current_hotel_id\
- \IdentifyTenant\ middleware validates hotel context per request
- Users can be assigned to multiple hotels with different roles

### 1.2 Core Data Model

**Primary Entities (57+ models identified):**

#### Tenant Core (Hotel Management)
- **Hotel:** Main hotel entity with configuration
- **HotelUser:** User-to-hotel membership with role assignment
- **HotelFloor:** Floor management within hotel
- **HotelShift:** Shift configuration for staff scheduling
- **PlatformSetting:** Platform-level configuration
- **CancellationPolicy:** Hotel-specific cancellation rules
- **TaxRate:** Tax configuration per hotel

#### User & Authorization (RBAC)
- **User:** Central user account (can belong to multiple hotels)
- **Role:** Defined roles (Admin, Manager, Receptionist, Chef, Waiter, Cashier, Guest)
- **Permission:** Granular permission definitions
- **UserPermission:** Direct permission assignment to users
- **TemporaryRoleAssignment:** Temporary role elevation
- **RbacAuditLog:** Audit trail for RBAC changes
- **AuditLog:** General system audit log

#### Room Management
- **Room:** Individual room entity with QR token
- **RoomType:** Room type configuration (single, double, suite, etc.)
- **RoomServiceAssignment:** Room service staff assignments
- **RoomServiceDelivery:** Delivery tracking for room service

#### Guest & Reservation
- **Guest:** Guest information (name, email, phone, passport, etc.)
- **Reservation:** Room booking with complete lifecycle (pending ? confirmed ? checked_in ? checked_out/cancelled)
- **CheckIn:** Check-in/check-out events and tracking
- **Complaint:** Guest complaints with priority and status

#### Restaurant Operations
- **MenuItem:** Menu items with pricing, tax rate, availability
- **Category:** Menu categories
- **Order:** Guest orders (room service, walk-in, table)
- **OrderItem:** Line items in orders with pricing
- **RestaurantCharge:** Charges linked to room bills
- **RestaurantTable:** Dining table management
- **WaiterTableAssignment:** Table assignments to waiters
- **WaiterFloorAssignment:** Floor assignments to waiters

#### Staff Management  
- **Chef:** Chef profile and performance tracking
- **Waiter:** Waiter profile and assignment tracking
- **Cashier:** Cashier profile and payment tracking
- **Receptionist:** Receptionist profile
- **Manager:** Manager profile with dashboard settings
- **Administrator:** Admin-specific configurations
- **WaiterAssignment:** Waiter task assignments
- **WaiterPerformance:** Performance metrics for waiters
- **WaiterNotification:** Real-time notifications for waiters
- **DeliveryLog:** Delivery history tracking

#### Payment & Financial
- **Payment:** Payment transactions (reservation, order, walk-in)
- **WalkInPayment:** Walk-in customer payment tracking
- **RestaurantCharge:** Restaurant charges for billing

#### Reviews & Feedback
- **MenuItemReview:** Guest reviews of menu items (1-5 stars)
- **ReviewResponse:** Management responses to reviews
- **ReviewHelpfulnessVote:** Guest votes on review helpfulness
- **ReviewNotification:** Notification system for reviews

#### Analytics & Reporting
- **ManagerReport:** Reports generated by managers
- **ManagerActivityLog:** Activity tracking for managers
- **ManagerAnnouncement:** Announcements to hotel staff
- **ManagerDashboardSetting:** Dashboard customization
- **PerformanceMetric:** KPI and performance tracking
- **ManagerNotification:** Notifications for managers

#### Tasks & Operations
- **DeliveryTask:** Delivery task management
- **HousekeepingTask:** Housekeeping task management
- **LaundryRequest:** Laundry service requests
- **InventoryManagement:** Inventory tracking
- **ComplaintTicket:** Complaint resolution tracking

---

## 2. FUNCTIONAL REQUIREMENTS BY MODULE

### MODULE 2.1: AUTHENTICATION & AUTHORIZATION

#### 2.1.1 User Authentication (AuthController)

**Requirement:** System must provide secure user authentication with JWT tokens

**Features:**
- Email/password login with validation
- JWT token generation (Sanctum)
- Token-based authentication for API requests
- 24-hour token expiration
- Logout functionality with token revocation
- Password reset with email verification
- Account activation workflow

**API Endpoints:**
- \POST /auth/login\ - Authenticate user
- \POST /logout\ - Logout user
- \POST /auth/update-password\ - Change password
- \POST /forgot-password\ - Initiate password reset
- \POST /reset-password\ - Complete password reset
- \GET /activation/{token}\ - Validate activation token
- \POST /activate-account\ - Activate user account

#### 2.1.2 Multi-Hotel Context Switching

**Requirement:** Users assigned to multiple hotels must seamlessly switch between contexts

**Implementation:**
- \POST /auth/switch-hotel\ - Change active hotel context
- \GET /auth/my-hotels\ - List assigned hotels
- \X-Hotel-ID\ header validates hotel context per request
- \TenantContext\ service manages current hotel globally
- \IdentifyTenant\ middleware enforces context per request

**Business Rules:**
- User can only switch to hotels where assigned
- Platform admins can switch to any hotel
- Context persists across requests via header/session
- All queries automatically scoped to current context

#### 2.1.3 Role-Based Access Control (RBAC)

**Requirement:** Granular permission-based access control system

**Roles Defined:**
1. **Platform Admin** - System-wide management
   - Hotel onboarding/management
   - Platform settings
   - Cross-hotel reporting
   - Admin account management

2. **Hotel Admin** - Hotel-level administration
   - Staff management
   - Hotel configuration
   - Room/menu management
   - Hotel analytics

3. **Manager** - Operational management
   - Dashboard & metrics
   - Staff scheduling
   - Kitchen operations
   - Complaint management
   - Performance analytics

4. **Receptionist** - Guest services
   - Check-in/check-out
   - Reservation management
   - Guest information
   - Room status

5. **Chef** - Kitchen operations
   - Order queue management
   - Order status updates
   - Kitchen performance tracking
   - Inventory (limited)

6. **Waiter** - Guest service
   - Table management
   - Order taking
   - Guest service
   - Delivery tracking

7. **Cashier** - Payment processing
   - Payment collection
   - Receipt generation
   - Payment reports
   - Reconciliation

8. **Guest** - Customer interface
   - Room booking
   - Room service ordering
   - Review submission
   - Reservation management

**Implementation:**
- \Permission::class\ - Granular permission definitions
- \Role::class\ - Role aggregations
- \UserPermission::class\ - Direct permission assignment
- \TemporaryRoleAssignment::class\ - Temporary role elevation
- Middleware: \permission:...\ - Permission-based access
- Middleware: \ole:...\ - Role-based access

**API Endpoints:**
- \GET /roles\ - List roles
- \POST /roles\ - Create role
- \PUT /roles/{role}\ - Update role
- \DELETE /roles/{role}\ - Delete role
- \POST /roles/{role}/permissions\ - Assign permissions to role
- \POST /users/{user}/roles\ - Assign role to user
- \DELETE /users/{user}/roles/{role}\ - Remove role from user
- \POST /users/{user}/direct-permissions\ - Assign direct permissions
- \GET /audit-logs\ - View audit trail

---

### MODULE 2.2: ROOM MANAGEMENT

#### 2.2.1 Room Entity Management

**Requirement:** Complete room lifecycle management with QR code generation

**Data Fields:**
- Room ID (UUID)
- Hotel ID (multi-tenant)
- Room Number (unique per hotel)
- Room Type (single, double, suite, etc.)
- Floor (floor number or HotelFloor reference)
- Description (amenities, features)
- Status (active, inactive, maintenance)
- Is Active (boolean flag)
- QR Token (8-character unique identifier)
- QR Image Path (storage location)
- QR Generated At (timestamp)

**Key Features:**
- Automatic QR code generation on room creation
- QR token uniqueness validation
- QR code image storage in filesystem
- Room search by number, description, status, floor, room type
- Room status toggle (active/inactive/maintenance)

**QR Code Implementation:**
- Tokens generated as 8-character uppercase random strings
- Uniqueness guaranteed via database validation
- QR codes generated using QRCodeService
- Images stored in Laravel storage (public/storage)
- QR URLs publicly accessible via \storage/...\ path
- Regeneration capability for damaged/lost QR codes

**API Endpoints (Room Management):**
- \GET /rooms\ - List all rooms
- \GET /rooms/{room}\ - Get room details
- \POST /rooms\ - Create room
- \PUT /rooms/{room}\ - Update room
- \DELETE /rooms/{room}\ - Delete room
- \PATCH /rooms/{room}/toggle-status\ - Toggle room status
- \GET /qr-codes/download/{roomId}\ - Download QR code
- \GET /qr-codes/print/{roomId}\ - Get print template
- \POST /admin/qr-codes/{roomId}/regenerate\ - Regenerate QR code
- \GET /admin/qr-codes/all\ - Get all QR codes

#### 2.2.2 Room Type Management

**Requirement:** Configurable room types with pricing

**Data Model:**
- Room Type ID
- Hotel ID (multi-tenant)
- Name (Single, Double, Suite, etc.)
- Description
- Price (per night)
- Capacity (number of guests)
- Status (active/inactive)

**API Endpoints:**
- \GET /room-types\ - List room types
- \GET /room-types/{roomType}\ - Get room type details
- \POST /room-types\ - Create room type
- \PUT /room-types/{roomType}\ - Update room type
- \DELETE /room-types/{roomType}\ - Delete room type
- \PATCH /room-types/{roomType}/toggle-status\ - Toggle availability

#### 2.2.3 Hotel Floor Management

**Requirement:** Floor-level organization for room assignment and staff

**Entities:**
- **HotelFloor:** Floor entity with number and name
- **WaiterFloorAssignment:** Waiter assignment to specific floors

**API Endpoints:**
- Floor CRUD operations
- Floor assignment to rooms
- Waiter-to-floor assignments

---

### MODULE 2.3: GUEST MANAGEMENT & RESERVATIONS

#### 2.3.1 Guest Profile Management

**Requirement:** Complete guest information storage and lifecycle management

**Data Fields:**
- Guest ID (UUID)
- Hotel ID (multi-tenant)
- First Name, Last Name
- Email (unique per hotel)
- Phone Number
- Address
- Nationality
- Passport Number
- Date of Birth
- Preferences (JSON array)
- Created/Updated timestamps
- Soft delete support

**Guest Relations:**
- Multiple reservations
- Multiple check-in/check-out records
- Multiple orders
- Reviews of menu items
- Complaints and feedback

**API Endpoints:**
- \POST /guests\ - Create guest (public)
- \GET /guests\ - List guests (staff)
- \GET /admin-guests\ - Admin guest list
- \POST /admin-guests\ - Create guest (admin)
- \GET /admin-guests/{guest}\ - Get guest details
- \PUT /admin-guests/{guest}\ - Update guest
- \DELETE /admin-guests/{guest}\ - Delete guest
- \GET /admin-guests/{guest}/reservations\ - Guest's reservations

#### 2.3.2 Reservation Management

**Requirement:** Complete reservation lifecycle from booking to check-out

**Data Model:**
- Reservation ID (UUID)
- Hotel ID (multi-tenant)
- Guest ID (foreign key)
- Room ID (foreign key)
- Booking Reference (unique per hotel, e.g., "BK20240915ABC1")
- Check-in Date
- Check-out Date
- Number of Guests
- Status (pending ? confirmed ? checked_in ? checked_out / cancelled)
- Total Amount
- Special Requests
- Notes
- Created/Updated/Cancelled timestamps

**Reservation Lifecycle:**
1. **Pending** - Initial state after booking
2. **Confirmed** - Admin confirmation
3. **Checked In** - Guest checked in
4. **Checked Out** - Guest completed stay
5. **Cancelled** - Booking cancelled with reason

**Business Rules:**
- Check-out date must be after check-in date
- Rooms cannot be double-booked
- Cancellations have policies (CancellationPolicy model)
- Reservation creator tracked (creator_id)
- Total nights automatically calculated
- Total amount based on room type price × nights + tax

**Calculated Fields:**
- \	otal_nights\ = checkout_date - checkin_date
- \	otal_amount\ = room_type.price × total_nights + tax

**API Endpoints:**
- \GET /reservations\ - List reservations
- \POST /reservations\ - Create reservation
- \GET /reservations/{reservation}\ - Get details
- \PUT /reservations/{reservation}\ - Update reservation
- \DELETE /reservations/{reservation}\ - Cancel reservation
- \POST /reservations/{reservation}/confirm\ - Confirm booking
- \POST /reservations/{reservation}/check-in\ - Check in guest
- \POST /reservations/{reservation}/check-out\ - Check out guest
- \POST /reservations/{reservation}/cancel\ - Cancel reservation
- \GET /reservations/availability\ - Check room availability
- \POST /admin-reservations/\ - Admin reservation creation
- \GET /admin/bookings\ - Admin booking management

#### 2.3.3 Availability Checking

**Requirement:** Real-time room availability checking for date ranges

**Algorithm:**
\\\
For requested check-in and check-out dates:
  1. Find all reservations for requested room_id
  2. Where status IN ('pending', 'confirmed', 'checked_in')
  3. Where checkout_date > requested_checkin AND checkin_date < requested_checkout
  4. If any conflicts found: unavailable
  5. Else: available
\\\

**API Endpoint:**
- \GET /reservations/availability\ - Check availability with date range

---

### MODULE 2.4: QR CODE SYSTEM

#### 2.4.1 QR Code Generation & Management

**Requirement:** Secure, unique QR code generation for rooms and guest access

**QR Code Properties:**
- **Token Format:** 8-character uppercase alphanumeric
- **Uniqueness:** Database-level uniqueness constraint
- **Content:** Encodes \hotel_id\, \oom_id\, \	oken\
- **Storage:** Images stored in \storage/public/\
- **Regeneration:** Supports regeneration if codes are damaged
- **Public Accessibility:** Accessible via public storage URLs

**QR Resolution Workflow:**
1. Guest scans QR code
2. QR code redirects to \/qr/resolve/{qrToken}\
3. System resolves:
   - Room details
   - Room number
   - Hotel information
   - Active reservation (if any)
   - Guest information (if reserved)
4. Response includes:
   - Room number and status
   - Guest name/email/phone (if reserved)
   - Hotel context

**API Endpoints:**
- \GET /qr/resolve/{qrToken}\ - Resolve QR code from URL
- \POST /qr/resolve\ - Resolve QR code from POST
- \POST /qr/validate\ - Validate QR token
- \GET /qr-code/generate/{roomId}\ - Generate QR code
- \GET /qr-code/data/{roomId}\ - Get QR code metadata
- \POST /admin/qr-codes/{roomId}/regenerate\ - Regenerate QR code

#### 2.4.2 QR-Based Guest Access

**Requirement:** Guests access room services via QR code without authentication

**Access Points:**
1. **Room Menu Access:**
   - Scan room QR code
   - View menu without login
   - Browse available food items
   - Place order

2. **Room Status View:**
   - Scan QR code
   - View room details
   - View order history
   - Track current orders

**Security:**
- QR token validates room existence
- Room must have active reservation
- Guest must be checked in
- No authentication required (public access)

---

### MODULE 2.5: RESTAURANT & ORDERING SYSTEM

#### 2.5.1 Menu Management

**Requirement:** Comprehensive menu system with categories, items, pricing, and tax

**Data Model:**

**Category Entity:**
- Category ID (UUID)
- Hotel ID (multi-tenant)
- Name (e.g., "Appetizers", "Main Course", "Dessert")
- Slug (URL-friendly version)
- Icon (category icon identifier)
- Description
- Order (sort order)
- Status (active/inactive)

**MenuItem Entity:**
- Menu Item ID (UUID)
- Hotel ID (multi-tenant)
- Name (e.g., "Grilled Salmon")
- Description (ingredients, preparation notes)
- Category (loose string) or Category ID (foreign key)
- Price (decimal with 2 places)
- Tax Rate ID (foreign key to TaxRate)
- Tax Included (boolean - price includes tax?)
- Image (stored image path or URL)
- Is Available (boolean)
- Created/Updated timestamps

**TaxRate Entity:**
- Tax Rate ID (UUID)
- Hotel ID (multi-tenant)
- Name (e.g., "VAT 15%", "Service Charge 10%")
- Rate (percentage, e.g., 15.00)
- Type (enum: vat, service_charge, luxury_tax, etc.)
- Status (active/inactive)

**Pricing Logic:**
\\\
If tax_included = true:
  Base Price = Item Price / (1 + (Tax Rate / 100))
  Tax Amount = Item Price - Base Price
  Total = Item Price

If tax_included = false:
  Base Price = Item Price
  Tax Amount = Item Price × (Tax Rate / 100)
  Total = Item Price + Tax Amount
\\\

**API Endpoints:**
- \GET /menu-items\ - List available menu items
- \GET /menu-items/{menuItem}\ - Get item details
- \POST /menu-items\ - Create menu item
- \PUT /menu-items/{menuItem}\ - Update menu item
- \DELETE /menu-items/{menuItem}\ - Delete menu item
- \PATCH /menu-items/{menuItem}/toggle-availability\ - Toggle availability
- \GET /categories\ - List categories
- \POST /categories\ - Create category
- \PUT /categories/{category}\ - Update category
- \DELETE /categories/{category}\ - Delete category
- \PATCH /categories/{category}/toggle\ - Toggle category
- \POST /categories/reorder\ - Reorder categories
- \GET /tax-rates\ - List tax rates
- \POST /tax-rates\ - Create tax rate
- \PUT /tax-rates/{taxRate}\ - Update tax rate
- \PATCH /tax-rates/{taxRate}/toggle\ - Toggle tax rate

#### 2.5.2 Guest Ordering (Room Service)

**Requirement:** Guests order food from rooms via QR code

**Workflow:**
1. Guest scans room QR code ? \guest/menu/{qrToken}\
2. System validates:
   - QR token exists
   - Room exists
   - Active reservation exists
   - Guest is checked in
3. System returns:
   - Menu items for hotel
   - Categories
   - Pricing with tax calculations
4. Guest selects items and quantities
5. Guest submits order

**Order Creation:**
- Room service order source = "guest_qr"
- Guest ID linked to reservation
- Room ID linked to room
- Order time recorded
- Items stored in OrderItem table
- Total calculated with tax
- Order status = "pending"

**Edge Cases:**
- No active reservation ? Error "Please check in first"
- Room not found ? Error "Invalid QR code"
- Menu item unavailable ? Validation error
- Quantity > 100 ? Validation error

**API Endpoints:**
- \GET /guest/menu/{qrToken}\ - Get room info
- \GET /guest/menu/{qrToken}/items\ - Get menu items for room
- \POST /guest/orders\ - Create guest order
- \GET /guest/orders/{qrToken}/status\ - Get order status
- \POST /guest/unified-orders\ - Create order (unified endpoint)

#### 2.5.3 Order Management (Kitchen & Staff)

**Requirement:** Complete order lifecycle from kitchen to delivery

**Order Entity:**
- Order ID (UUID)
- Hotel ID (multi-tenant)
- Order Number (unique per hotel, e.g., "ORD-20240915-001")
- Guest ID (nullable for walk-in)
- Room ID (nullable for table orders)
- RestaurantTable ID (nullable for table orders)
- Order Time
- Status (pending ? confirmed ? cooking ? ready ? served ? completed/cancelled)
- Source (enum: guest_qr, table, walk_in)
- Payment Method (chapa, cash, card, hotel_bill)
- Subtotal
- Tax
- Discount (optional)
- Total
- Special Requests
- Completed At (timestamp for completed/cancelled orders)

**OrderItem Entity:**
- Item ID
- Order ID (foreign key)
- Menu Item ID
- Quantity (1-100)
- Item Price at Order (capture price at order time)
- Line Total (quantity × price)

**Order Lifecycle:**

1. **Pending** - Order received, awaiting kitchen confirmation
2. **Confirmed** - Kitchen accepted order
3. **Cooking** - Chef actively preparing
4. **Ready** - Ready for delivery
5. **Served** - Delivered to guest/table
6. **Completed** - Payment collected
7. **Cancelled** - Order cancelled

**Kitchen Operations:**
- View pending orders
- Sort by priority/time
- Start cooking (mark as "cooking")
- Mark as ready
- Complete/serve order
- View statistics (average prep time, orders per hour, etc.)
- Delayed order alerts

**Order Statistics:**
- Total orders per period
- Average preparation time
- Queue depth
- Chef workload distribution
- Top selling items
- Orders by source (guest_qr, table, walk_in)

**API Endpoints (Kitchen):**
- \GET /kitchen/orders\ - List pending/cooking orders
- \GET /kitchen/statistics\ - Kitchen metrics
- \PATCH /kitchen/orders/{order}/start\ - Start cooking
- \PATCH /kitchen/orders/{order}/ready\ - Mark as ready
- \PATCH /kitchen/orders/{order}/complete\ - Complete order

**API Endpoints (Manager Kitchen View):**
- \GET /manager/kitchen/orders\ - Orders with full details
- \GET /manager/kitchen/metrics\ - Kitchen KPIs
- \GET /manager/kitchen/delayed-orders\ - Orders taking too long
- \GET /manager/kitchen/performance\ - Performance analytics
- \GET /manager/kitchen/chef-workload\ - Chef load distribution
- \GET /manager/kitchen/top-items\ - Top selling items

#### 2.5.4 Walk-In Customer Orders

**Requirement:** Support orders from customers without room reservations

**Walk-In Order Flow:**
1. Waiter/Cashier creates walk-in order
2. No guest ID required
3. No room ID required
4. May be assigned to restaurant table
5. Payment collected immediately or on completion

**Data Captured:**
- Table ID (if table service)
- Waiter ID (who took order)
- Items ordered
- Total price
- Payment method
- Payment status

**API Endpoints:**
- \POST /guest/orders\ - Walk-in order creation (guest_orders endpoint)
- \POST /guest/unified-orders\ - Unified order creation
- \POST /orders\ - Admin order creation

#### 2.5.5 Table Management (Restaurant Seating)

**Requirement:** Restaurant table management for walk-in customers

**RestaurantTable Entity:**
- Table ID (UUID)
- Hotel ID (multi-tenant)
- Table Number (unique per hotel)
- Capacity (number of guests)
- Status (available, occupied, reserved, maintenance)
- Location (e.g., "Main Dining", "Patio", etc.)

**WaiterTableAssignment Entity:**
- Assignment ID
- Waiter ID
- Table ID (or Floor if managing entire floor)
- Hotel ID (multi-tenant)
- Assigned Date
- Status (active, completed, archived)

**API Endpoints:**
- \GET /manager/restaurant-tables\ - List tables
- \POST /manager/restaurant-tables\ - Create table
- \PUT /manager/restaurant-tables/{id}\ - Update table
- \DELETE /manager/restaurant-tables/{id}\ - Delete table
- \POST /manager/waiter-table-assignments\ - Assign table to waiter
- \DELETE /manager/waiter-table-assignments/{id}\ - Remove assignment

---

### MODULE 2.6: PAYMENT PROCESSING

#### 2.6.1 Payment Gateway Integration (Chapa)

**Requirement:** Integrate with Chapa payment processor for card payments

**Payment Entity:**
- Payment ID (UUID)
- Hotel ID (multi-tenant)
- Order ID (nullable - for order payments)
- Reservation ID (nullable - for reservation payments)
- Amount (decimal)
- Currency (e.g., USD, ETB)
- Payment Method (chapa, cash, card, bank_transfer)
- Status (pending ? processing ? completed / failed / refunded)
- Transaction ID (from Chapa)
- Reference Number (internal)
- Chapa TX Ref (Chapa transaction reference)
- Chapa Checkout URL (redirect URL)
- Metadata (JSON - order details, reservation details, etc.)
- Error Message (if failed)
- Retry Count (number of retry attempts)
- Paid At (completion timestamp)

**Payment Workflow:**

1. **Initialization:**
   - \POST /payments/initialize\ with order/reservation ID
   - Create Payment record with status=pending
   - Call Chapa API to generate checkout URL
   - Store Chapa tx_ref in payment record
   - Return checkout URL to frontend

2. **Payment Processing:**
   - Guest redirected to Chapa hosted payment page
   - Guest enters card details
   - Chapa processes payment
   - Chapa sends webhook callback

3. **Payment Verification:**
   - Webhook received with tx_ref
   - \POST /payments/callback\ or webhook endpoint
   - Call Chapa API to verify payment status
   - Update Payment record with status=completed/failed

4. **Order/Reservation Completion:**
   - If payment successful:
     - Update Order/Reservation status
     - Send confirmation email
     - Trigger order to kitchen
   - If payment failed:
     - Notify user
     - Provide retry option
     - Log error

**Error Handling:**
- Payment gateway down ? Queue payment for retry
- Network timeout ? Retry after 5 seconds (max 3 retries)
- Invalid credentials ? Log and alert admin
- Duplicate payment ? Check Chapa idempotency

**API Endpoints (Guest/Public):**
- \POST /payments/initialize\ - Initialize payment
- \GET /payments/verify/{txRef}\ - Verify payment
- \GET /payments/status/{txRef}\ - Get payment status
- \GET /payments/callback\ - Webhook callback handler

**API Endpoints (Reservation Payments):**
- \POST /reservation-payments/initialize\ - Initialize reservation payment
- \POST /reservation-payments/complete/{txRef}\ - Complete reservation after payment
- \GET /reservation-payments/{txRef}\ - Get reservation by payment

**API Endpoints (Order Payments):**
- \POST /order-payments/initialize\ - Initialize order payment
- \POST /order-payments/complete/{txRef}\ - Complete order after payment
- \GET /order-payments/{txRef}\ - Get order by payment

**API Endpoints (Walk-In Payments):**
- \POST /walk-in-payments/initialize\ - Initialize walk-in payment
- \POST /walk-in-payments/complete/{txRef}\ - Complete walk-in payment
- \GET /walk-in-payments/{txRef}\ - Get walk-in order by payment

#### 2.6.2 Cashier Payment Processing

**Requirement:** Cashier collects and manages payments (cash, card, online)

**Payment Methods:**
1. **Online (Chapa):** Automatic via checkout link
2. **Cash:** Manual entry by cashier
3. **Card:** Processed through Chapa or gateway
4. **Hotel Bill:** Charge to guest account

**Cashier Dashboard:**
- Pending payments awaiting collection
- Payment history (last 24h, week, month)
- Payment reconciliation
- Revenue reports
- Payment method breakdown

**API Endpoints:**
- \GET /payments\ - List payments (authenticated)
- \GET /payments/{paymentId}\ - Get payment status
- \GET /cashier/payments\ - Cashier payment list
- \POST /cashier/payments\ - Record cash payment

---

### MODULE 2.7: CHECK-IN & CHECK-OUT

#### 2.7.1 Check-In Operations

**Requirement:** Seamless guest check-in process

**CheckIn Entity:**
- CheckIn ID (UUID)
- Hotel ID (multi-tenant)
- Guest ID (foreign key)
- Reservation ID (foreign key)
- Room ID (foreign key)
- Checked In At (timestamp)
- Checked Out At (nullable until check-out)
- Notes
- Created/Updated timestamps

**Check-In Workflow:**
1. Receptionist selects guest/reservation
2. Validates reservation status = "confirmed"
3. Validates check-in date = today
4. Validates room is available (not already occupied)
5. Creates CheckIn record
6. Updates Reservation status to "checked_in"
7. Room status updated to "occupied"
8. Guest receives check-in confirmation
9. Welcome message sent

**Check-In Validations:**
- Reservation must exist and be confirmed
- Guest already checked in? ? Show active check-in
- Check-in date <= today <= check-out date
- Room must be ready for occupancy

**API Endpoints:**
- \POST /check-ins\ - Create check-in
- \GET /check-ins\ - List active check-ins
- \GET /check-ins/{checkIn}\ - Get check-in details
- \POST /check-ins/{checkIn}/checkout\ - Check out guest
- \DELETE /check-ins/{checkIn}\ - Cancel check-in
- \GET /check-ins/statistics\ - Check-in/out statistics
- \POST /reservations/{reservation}/check-in\ - Check in via reservation
- \POST /reservations/{reservation}/check-out\ - Check out via reservation

#### 2.7.2 Receptionist Dashboard

**Requirement:** Real-time overview of check-ins, check-outs, and occupancy

**Dashboard Components:**
- Today's check-ins (expected arrivals)
- Today's check-outs (departures)
- Current occupancy rate
- Rooms needing cleaning/preparation
- VIP guests
- Special requests
- Pending confirmations

**API Endpoints:**
- \GET /reception/dashboard\ - Receptionist dashboard data
- \GET /check-ins/statistics\ - Check-in/out metrics

---

### MODULE 2.8: STAFF MANAGEMENT

#### 2.8.1 User Management (Unified)

**Requirement:** Create, manage, and provision staff accounts

**User Entity:**
- User ID (UUID)
- Email (unique across platform)
- Password (bcrypt hashed)
- First Name, Last Name
- Phone
- Avatar URL
- Is Active (boolean)
- Last Login At
- Created/Updated timestamps

**Staff Assignment:**
- User assigned to hotel via HotelUser
- HotelUser has role (Admin, Manager, Chef, Waiter, Cashier, Receptionist)
- User can have different roles in different hotels
- Supports temporary role elevation

**API Endpoints:**
- \GET /users\ - List users (permission-based)
- \POST /users\ - Create user
- \GET /users/{user}\ - Get user details
- \PUT /users/{user}\ - Update user
- \DELETE /users/{user}\ - Delete/deactivate user
- \PATCH /users/{user}/toggle-status\ - Toggle active status

#### 2.8.2 Chef Management & Profile

**Requirement:** Chef-specific profile and performance tracking

**Chef Entity:**
- Chef ID (extends User)
- Hotel ID
- Specialization (e.g., "Asian Cuisine")
- Experience Level
- Status (on-duty, off-duty)
- Performance metrics (orders completed, avg prep time, etc.)

**Chef Dashboard:**
- Orders in queue (sorted by priority)
- Current order details
- Estimated completion times
- Daily statistics
- Performance metrics
- Colleague notifications

**API Endpoints:**
- \GET /chef/profile\ - Get chef profile
- \PUT /chef/profile\ - Update profile
- \POST /chef/profile/change-password\ - Change password
- \POST /chef/profile/photo\ - Upload profile photo
- \POST /chef/profile/status\ - Update chef status (on-duty/off-duty)
- \GET /chef/profile/stats\ - Chef performance stats

#### 2.8.3 Waiter Management & Assignment

**Requirement:** Waiter task assignment and performance tracking

**Waiter Entity:**
- Waiter ID (extends User)
- Hotel ID
- Floor Assignments (WaiterFloorAssignment)
- Table Assignments (WaiterTableAssignment)
- Performance metrics

**Waiter Features:**
- Assigned to specific floors/tables
- Receives table assignments
- Takes guest orders
- Tracks deliveries
- Performance metrics (avg table turnover, orders per shift, ratings)

**Waiter Workflow:**
1. Log in
2. View assigned tables/floors
3. Receive table assignments from manager
4. Take orders from guests
5. Send orders to kitchen
6. Track order status
7. Deliver orders
8. Collect payment
9. Clear table

**API Endpoints:**
- \GET /waiter/profile\ - Waiter profile
- \PUT /waiter/profile\ - Update profile
- \POST /waiter/profile/change-password\ - Change password
- \GET /waiter/dashboard\ - Waiter dashboard (assignments, orders)
- \GET /waiter/assignments\ - Assigned tables/floors
- \POST /waiter/assignments/{id}/complete\ - Complete assignment
- \GET /waiter/history\ - Order history
- \GET /waiter/notifications\ - Notifications

#### 2.8.4 Cashier Management

**Requirement:** Cashier payment processing and reconciliation

**Cashier Entity:**
- Cashier ID (extends User)
- Hotel ID

**Cashier Features:**
- Process payments (cash, card, online)
- Print receipts
- Payment reconciliation
- Cash drawer management
- Daily reports

**API Endpoints:**
- \GET /cashier/profile\ - Cashier profile
- \PUT /cashier/profile\ - Update profile
- \POST /cashier/profile/change-password\ - Change password
- \GET /cashier/dashboard\ - Cashier dashboard
- \GET /cashier/payments\ - Payment list
- \POST /cashier/payments\ - Record payment
- \GET /cashier/reports\ - Payment reports

---

### MODULE 2.9: MANAGER DASHBOARD & ANALYTICS

#### 2.9.1 Manager Dashboard

**Requirement:** Real-time operational overview and KPIs

**Dashboard Sections:**

1. **Quick Stats:**
   - Current occupancy rate
   - Revenue today/week/month
   - Total orders today
   - Pending orders
   - Guests checked in
   - Staff present

2. **Occupancy Metrics:**
   - Rooms occupied/available/maintenance
   - Occupancy trend (graph)
   - Forecast occupancy

3. **Revenue Metrics:**
   - Revenue by source (rooms, food, services)
   - Revenue trend (graph)
   - Average revenue per room
   - Forecast revenue

4. **Kitchen Operations:**
   - Current queue depth
   - Average prep time
   - Orders per hour
   - Delayed orders (>estimated time)
   - Top items

5. **Staff Performance:**
   - Chef workload
   - Waiter performance (avg table turnover)
   - Cashier transactions
   - Staff attendance

6. **Guest Satisfaction:**
   - Recent reviews
   - Average rating
   - Complaint volume
   - Response rate

**API Endpoints:**
- \GET /manager/dashboard\ - Main dashboard
- \GET /manager/dashboard/statistics\ - Statistical overview
- \GET /manager/dashboard/daily-trends\ - Daily trends
- \GET /manager/dashboard/performance\ - Performance summary
- \GET /manager/dashboard/top-items\ - Top selling items

#### 2.9.2 Manager Kitchen Operations

**Requirement:** Kitchen monitoring and optimization

**Kitchen View:**
- Real-time order queue
- Average prep time
- Delayed orders alert
- Chef workload distribution
- Quality metrics
- Performance trends

**API Endpoints:**
- \GET /manager/kitchen/orders\ - Current orders
- \GET /manager/kitchen/metrics\ - Kitchen KPIs
- \GET /manager/kitchen/delayed-orders\ - Orders exceeding time
- \GET /manager/kitchen/performance\ - Performance data
- \GET /manager/kitchen/chef-workload\ - Chef load distribution
- \GET /manager/kitchen/queue-status\ - Queue depth and metrics

#### 2.9.3 Manager Waiter & Staff Operations

**Requirement:** Staff assignment and performance tracking

**Waiter Management:**
- List all waiters
- Assign tables/floors
- View performance metrics
- Track orders assigned
- Monitor ratings/complaints

**API Endpoints:**
- \GET /manager/waiters\ - List waiters
- \POST /manager/waiter-assignments\ - Assign table/floor
- \GET /manager/waiters/{id}/performance\ - Waiter stats
- \GET /manager/floor-assignments\ - Floor assignments
- \POST /manager/floor-assignments\ - Assign floor to waiter

#### 2.9.4 Complaint Management

**Requirement:** Centralized complaint tracking and resolution

**Complaint Entity:**
- Complaint ID
- Hotel ID (multi-tenant)
- Guest ID (may be anonymous)
- Category (food quality, service, cleanliness, etc.)
- Severity (low, medium, high, critical)
- Description
- Status (open, in-progress, resolved, closed)
- Resolution (if resolved)
- Created/Updated/Resolved dates

**Complaint Workflow:**
1. Guest/staff submits complaint
2. Manager receives notification
3. Manager assigns to responsible party
4. Work towards resolution
5. Follow-up with guest
6. Close complaint
7. Track metrics

**API Endpoints:**
- \GET /manager/complaints\ - List complaints
- \POST /manager/complaints\ - Create complaint
- \PUT /manager/complaints/{id}\ - Update complaint
- \POST /manager/complaints/{id}/resolve\ - Resolve complaint
- \GET /manager/complaints/statistics\ - Complaint metrics

---

### MODULE 2.10: REVIEWS & FEEDBACK SYSTEM

#### 2.10.1 Menu Item Reviews

**Requirement:** Guests review menu items after orders

**MenuItemReview Entity:**
- Review ID (UUID)
- Hotel ID (multi-tenant)
- Guest ID (foreign key)
- Menu Item ID (foreign key)
- Order ID (foreign key - which order contained this item)
- Rating (1-5 stars)
- Title (short review title)
- Content (detailed review text)
- Status (pending ? approved / rejected)
- Response (management response)
- Votes (helpful/unhelpful votes from other guests)
- Created/Updated/Approved dates

**Review Creation:**
- Available to guests after order completion
- Only guests who ordered item can review
- One review per guest per menu item
- Reviews moderated before display

**Review Display:**
- Average rating displayed
- Review count
- Rating distribution (1-star count, 2-star, etc.)
- Top helpful reviews featured
- Guest name displayed
- Review date displayed

**Management Response:**
- Manager can respond to reviews
- Response displayed with review
- Thank you for feedback
- Issue resolution information

**API Endpoints:**
- \GET /reviews\ - List reviews (public)
- \POST /reviews\ - Create review (guest)
- \PUT /reviews/{review}\ - Edit own review
- \DELETE /reviews/{review}\ - Delete own review
- \POST /reviews/{review}/vote\ - Vote helpful/unhelpful
- \POST /manager/reviews/{review}/approve\ - Approve review
- \POST /manager/reviews/{review}/reject\ - Reject review
- \POST /manager/reviews/{review}/respond\ - Add management response

#### 2.10.2 Review Moderation

**Requirement:** Automated and manual review moderation

**Moderation Features:**
- Flag inappropriate content
- Automatic word filtering
- Admin review queue
- Bulk approval/rejection
- Moderation audit log

**API Endpoints:**
- \GET /moderation/reviews/pending\ - Pending review queue
- \POST /moderation/reviews/{id}/approve\ - Approve
- \POST /moderation/reviews/{id}/reject\ - Reject
- \GET /moderation/audit-logs\ - Moderation history

#### 2.10.3 Review Notifications

**Requirement:** Notify management of new reviews

**ReviewNotification Entity:**
- Notification ID
- Hotel ID
- Review ID
- Manager ID (recipient)
- Type (new_review, response_reply, helpful_vote)
- Read Status
- Created timestamp

**Notification Features:**
- Real-time notifications
- Email summaries
- In-app notification center
- Mark as read/unread
- Delete notification

---

### MODULE 2.11: NOTIFICATIONS & REAL-TIME UPDATES

#### 2.11.1 Notification System

**Requirement:** Real-time push notifications for all users

**Notification Entity:**
- Notification ID (UUID)
- User ID (recipient)
- Hotel ID (multi-tenant)
- Type (order_ready, payment_received, complaint, reservation, review, etc.)
- Title
- Message
- Metadata (JSON - related IDs, data)
- Read Status
- Created/Read At timestamps

**Notification Types:**
- **Kitchen:** Order received, special requests, queue alerts
- **Waiter:** Table assignment, order ready, guest request
- **Manager:** Complaint filed, high order volume, payment failed
- **Receptionist:** Check-in expected, check-out, late departure
- **Cashier:** Payment pending, reconciliation needed
- **Guest:** Order status, payment confirmation, review reminder

**API Endpoints:**
- \GET /notifications\ - List notifications
- \GET /notifications/latest\ - Latest unread
- \GET /notifications/unread-count\ - Unread count
- \PUT /notifications/{id}/read\ - Mark as read
- \PUT /notifications/read-all\ - Mark all as read
- \DELETE /notifications/{id}\ - Delete notification
- \DELETE /notifications/clear-all\ - Clear all

#### 2.11.2 Real-Time WebSocket Integration

**Requirement:** Real-time updates without polling

**WebSocket Channels:**
- **Kitchen Channel:** Order updates (new orders, ready, completed)
- **Waiter Channel:** Assignments, order status, guest requests
- **Manager Channel:** Dashboard updates, alerts
- **Guest Channel:** Order status, room service updates
- **Hotel Channel:** Broadcasts (announcements, alerts)

**WebSocket Implementation:**
- Laravel WebSockets or Pusher
- Pusher channels for real-time events
- Presence channels for online staff
- Private channels for user-specific data

**Broadcasting Events:**
- Order placed ? Kitchen channel
- Order ready ? Waiter, Manager, Guest channels
- Payment processed ? Cashier, Manager channels
- Complaint filed ? Manager channel
- Review submitted ? Manager channel

---

## 3. ADMINISTRATIVE & PLATFORM MANAGEMENT

### MODULE 3.1: PLATFORM ADMIN FUNCTIONS

#### 3.1.1 Multi-Hotel Management

**Requirement:** Platform admins manage all hotels

**Platform Admin Functions:**
- View all hotels (statistics, metrics)
- Create new hotel (onboarding)
- Update hotel configuration
- Change hotel status (active, inactive, suspended)
- View cross-hotel analytics
- Generate platform reports
- Manage platform settings

**Hotel Onboarding:**
- Create Hotel record
- Assign Hotel Admin user
- Generate admin credentials
- Send welcome email
- Configure initial settings

**API Endpoints:**
- \GET /platform/hotels\ - List all hotels
- \POST /platform/hotels\ - Create hotel
- \GET /platform/hotels/{id}\ - Get hotel details
- \PUT /platform/hotels/{id}\ - Update hotel
- \PATCH /platform/hotels/{id}/status\ - Change status
- \POST /platform/hotels/{id}/archive\ - Archive hotel
- \DELETE /platform/hotels/{id}\ - Delete hotel
- \GET /platform/statistics\ - Cross-hotel statistics

#### 3.1.2 Platform Analytics

**Requirement:** Aggregated reporting across all hotels

**Platform Metrics:**
- Total revenue (all hotels)
- Total bookings
- Total orders
- Average occupancy rate
- Performance by hotel
- Top performing hotels
- System health
- User activity

**API Endpoints:**
- \GET /platform/statistics\ - Platform statistics
- \GET /analytics\ - Platform analytics
- \GET /analytics/hotels\ - Analytics by hotel
- \GET /analytics/revenue\ - Revenue analytics
- \GET /analytics/bookings\ - Booking analytics

---

### MODULE 3.2: HOTEL ADMIN FUNCTIONS

#### 3.2.1 Hotel Configuration

**Requirement:** Hotel admins configure hotel settings

**Configuration Options:**
- Hotel name, logo, contact info
- Room types and pricing
- Menu categories and items
- Tax rates
- Cancellation policies
- Operating hours
- Staff roles and permissions
- Service charges
- Payment methods
- Currency

**API Endpoints:**
- Hotel settings CRUD operations
- Room type management
- Menu management
- Tax rate management

#### 3.2.2 Staff Management

**Requirement:** Hotel admins manage hotel staff

**Staff Management:**
- Invite staff members
- Assign roles (Manager, Chef, Waiter, etc.)
- Manage permissions
- Deactivate/remove staff
- View staff activity
- Performance tracking

**API Endpoints:**
- \POST /users\ - Create user
- \PUT /users/{user}\ - Update user
- \DELETE /users/{user}\ - Delete user
- \POST /users/{user}/roles\ - Assign role
- \DELETE /users/{user}/roles/{role}\ - Remove role
- \GET /audit-logs\ - Staff audit logs

---

### MODULE 3.3: AUDIT LOGGING

#### 3.3.1 System Audit Trails

**Requirement:** Complete audit trail of all system actions

**AuditLog Entity:**
- Log ID
- Hotel ID (multi-tenant)
- User ID (who performed action)
- Entity Type (Room, Reservation, Order, etc.)
- Entity ID (record being modified)
- Action (create, update, delete)
- Old Values (previous state)
- New Values (new state)
- Timestamp
- IP Address
- User Agent

**RbacAuditLog Entity:**
- Log ID
- User ID (subject of action)
- Target User ID (if changing another user)
- Action (role_assigned, permission_granted, etc.)
- Role/Permission ID
- Timestamp
- Admin ID (who made change)

**Audit Query:**
- View all actions by user
- View all changes to entity
- View all role/permission changes
- Export audit logs
- Search by date range, user, entity type

**API Endpoints:**
- \GET /audit-logs\ - Audit log query
- \GET /rbac-audit-logs\ - RBAC audit logs
- \GET /manager/activity\ - Manager activity log

---

## 4. NON-FUNCTIONAL REQUIREMENTS

### 4.1 PERFORMANCE REQUIREMENTS

| Metric | Target | Critical? |
|--------|--------|-----------|
| API Response Time (p95) | < 200ms | Yes |
| Page Load (FCP) | < 1.5s | Yes |
| Database Query | < 100ms | Yes |
| Menu Item Load | < 500ms | Yes |
| Order Creation | < 1s | Yes |
| Check-in/Check-out | < 2s | Yes |
| QR Code Scan | < 1s | Yes |
| Kitchen Queue Update | < 2s | Real-time |
| Waiter Notification | < 3s | Real-time |
| Payment Verification | < 5s | Yes |

### 4.2 SCALABILITY REQUIREMENTS

**Current Capacity:**
- 50+ hotels
- 5000+ concurrent users
- 500+ orders per hour
- 100+ orders in kitchen queue

**Scaling Strategy:**
- Horizontal scaling of API servers
- Read replicas for database
- Caching layer (Redis)
- CDN for static assets
- Message queue for background jobs

### 4.3 SECURITY REQUIREMENTS

- **Authentication:** Sanctum JWT (24-hour expiry)
- **Authorization:** Role-based access control
- **Encryption:** TLS for all traffic
- **Password:** bcrypt hashing
- **Multi-Tenancy:** Complete row-level isolation
- **API Keys:** Secure credential management
- **Audit:** Complete action logging
- **CORS:** Whitelist frontend domains

### 4.4 AVAILABILITY & RELIABILITY

- **Uptime SLA:** 99.9% monthly
- **Backup:** Daily automated backups
- **Recovery:** Point-in-time restore capability
- **Disaster Recovery:** RPO < 1 hour, RTO < 4 hours
- **Data Durability:** 99.999%

### 4.5 COMPLIANCE & DATA GOVERNANCE

- **Data Privacy:** GDPR-compliant
- **Soft Deletes:** Preserve guest/staff data
- **Audit Trail:** Complete action history
- **Retention:** Configurable data retention policies
- **Export:** Guest data export capability

---

## 5. INTEGRATION REQUIREMENTS

### 5.1 PAYMENT GATEWAY (CHAPA)

**Integration Points:**
- Payment initialization
- Checkout URL generation
- Payment verification
- Webhook callbacks
- Error handling & retries
- Idempotency checking

### 5.2 EMAIL SERVICE

**Events Triggering Email:**
- Account activation
- Password reset
- Reservation confirmation
- Order confirmation
- Order status updates
- Payment receipts
- Review notifications
- Complaint updates

### 5.3 FILE STORAGE

**Files Stored:**
- Menu item images
- Room images
- Guest documents (optional)
- QR code images
- Invoice/receipts
- Reports (PDF exports)

**Storage Backend:**
- Local filesystem (development)
- S3 (production)
- CDN for public images

---

## 6. USER WORKFLOWS & SCENARIOS

### 6.1 GUEST BOOKING WORKFLOW

\\\
1. Guest visits public website
2. Searches available rooms (date range)
3. Selects room and books
4. Enters guest details
5. Selects payment method
6. Completes payment
7. Receives confirmation email
8. Arrives at hotel
9. Checks in with receptionist
10. Proceeds to room
\\\

### 6.2 GUEST ORDERING WORKFLOW

\\\
1. Guest scans QR code in room
2. Views menu
3. Selects items and quantities
4. Submits order
5. Order appears in kitchen
6. Chef prepares food
7. Waiter delivers to room
8. Guest receives order
9. Option to rate/review food items
\\\

### 6.3 KITCHEN WORKFLOW

\\\
1. Chef logs in
2. Views pending orders
3. Selects order and starts cooking
4. Updates status: cooking ? ready
5. Waiter collects ready order
6. Delivers to guest
7. Returns to kitchen
8. Chef marks complete
\\\

### 6.4 MANAGER OVERSIGHT WORKFLOW

\\\
1. Manager logs in
2. Views dashboard (KPIs, metrics)
3. Monitors kitchen operations
4. Reviews complaints
5. Checks revenue metrics
6. Views staff performance
7. Generates reports
8. Makes operational decisions
\\\

---

## 7. DATA VALIDATION & ERROR HANDLING

### 7.1 INPUT VALIDATION

- Email format validation
- Phone number validation
- UUID format validation
- Date range validation (check-out > check-in)
- Quantity validation (1-100)
- Price validation (decimal 2 places)
- String length limits

### 7.2 BUSINESS LOGIC VALIDATION

- Room availability checking
- Duplicate booking prevention
- Active reservation requirement for QR access
- Menu item availability
- Tax rate calculation accuracy
- Payment amount verification
- User permission verification

### 7.3 ERROR RESPONSES

All errors return JSON with:
- \success: false\
- \error: error_code\
- \message: human_readable_message\
- \errors: validation_errors_if_applicable\

---

## 8. TESTING REQUIREMENTS

### 8.1 Unit Tests

- Model relationships
- Business logic calculations
- Scope filtering (tenant isolation)
- Validation rules
- Tax calculations
- Pricing logic

### 8.2 Integration Tests

- API endpoints
- Database transactions
- Payment gateway integration
- Email service integration
- Multi-tenant isolation
- Role-based access control

### 8.3 End-to-End Tests

- Complete booking workflow
- Complete ordering workflow
- Complete payment workflow
- Multi-hotel operations

### 8.4 Load Tests

- 5000 concurrent users
- 500 orders per hour
- Payment processing at scale
- Report generation performance
- Dashboard rendering speed

---

## APPENDIX A: GLOSSARY

| Term | Definition |
|------|-----------|
| Hotel | Primary tenant entity, managed property |
| Reservation | Room booking with dates and guest |
| Check-in | Guest arrival and room occupation |
| QR Token | 8-character unique room identifier |
| Order | Food order from guest or walk-in |
| Order Item | Individual food item in order |
| Payment | Payment transaction (reservation, order) |
| Booking Reference | Public reference number (BK-...) |
| TenantScope | Global Eloquent scope enforcing hotel_id |
| Multi-Tenancy | Single DB serving multiple isolated hotels |
| RLS | Row-Level Security at application level |
| RBAC | Role-Based Access Control |
| Chef | Kitchen staff preparing food |
| Waiter | Service staff delivering orders |
| Cashier | Payment processing staff |

---

**END OF COMPREHENSIVE REQUIREMENTS DOCUMENT**

Version 2.0 - Based on deep code analysis of implemented system
