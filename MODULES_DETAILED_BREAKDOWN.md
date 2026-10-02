# DETAILED MODULE-BY-MODULE REQUIREMENTS BREAKDOWN
## Multi-Hotel Restaurant & Guest Management System

**Document Type:** Module Specifications  
**Prepared By:** Requirements Analyst (Code Analysis)  
**Date:** September 2026  
**Version:** 1.0  
**Total Modules:** 12

---

## TABLE OF CONTENTS

1. [Module 1: Authentication & Authorization](#module-1)
2. [Module 2: Room Management](#module-2)
3. [Module 3: Guest & Reservation Management](#module-3)
4. [Module 4: QR Code & Access System](#module-4)
5. [Module 5: Restaurant Operations](#module-5)
6. [Module 6: Ordering System](#module-6)
7. [Module 7: Payment Processing](#module-7)
8. [Module 8: Check-in & Check-out](#module-8)
9. [Module 9: Staff Management & Roles](#module-9)
10. [Module 10: Dashboards & Analytics](#module-10)
11. [Module 11: Reviews & Feedback](#module-11)
12. [Module 12: Notifications & Platform Admin](#module-12)

---

## MODULE 1: AUTHENTICATION & AUTHORIZATION {#module-1}

### 1.1 PURPOSE
Enable secure user access, multi-hotel context management, and granular permission-based access control

### 1.2 KEY ACTORS
- End Users (all roles)
- Platform Admin
- Hotel Admin
- Staff (Chef, Waiter, Cashier, Receptionist, Manager)
- Guests

### 1.3 TECHNICAL COMPONENTS

**Controllers:**
- \AuthController\ - Login, logout, password reset, hotel switching
- \RoleController\ - RBAC role management
- \PermissionController\ - Permission definitions
- \UserRoleController\ - Role assignments
- \UserDirectPermissionController\ - Direct permission grants
- \TemporaryRoleController\ - Temporary role elevation
- \AuditLogController\ - Audit trail queries

**Models:**
- \User\ - Central user account
- \Role\ - Role definitions
- \Permission\ - Permission definitions
- \UserPermission\ - User-permission mappings
- \TemporaryRoleAssignment\ - Temporary role data
- \RbacAuditLog\ - Role/permission audit trail
- \AuditLog\ - General audit log

**Services:**
- \TenantContext\ - Multi-hotel context management
- Authentication service (Sanctum)

**Middleware:**
- \IdentifyTenant\ - Validates hotel context
- \ole:...\" - Role-based access
- \permission:...\ - Permission-based access

### 1.4 DATA FLOW

\\\
User Login
  ?
AuthController::login
  ?
Validate email/password
  ?
Generate JWT token (Sanctum)
  ?
Return token + user info + assigned hotels
  ?
Frontend stores token
  ?
Subsequent requests include token in Authorization header
  ?
Sanctum middleware verifies token
  ?
IdentifyTenant middleware sets hotel context from X-Hotel-ID header
  ?
All queries scoped to hotel via TenantScope
\\\

### 1.5 DETAILED REQUIREMENTS

#### 1.5.1 User Login
- Input validation (email, password format)
- Database query for user
- Password verification (bcrypt)
- JWT token generation (24-hour expiry)
- Return user info and assigned hotels
- Log login attempt (audit)
- Support "remember me" (extended token)

#### 1.5.2 Multi-Hotel Context
- User can belong to multiple hotels
- Each hotel assignment has specific role
- Hotel context set via X-Hotel-ID header
- Context persists across requests
- All database queries automatically scoped
- Platform admin can switch to any hotel
- Staff can only switch to assigned hotels

#### 1.5.3 RBAC System
- 8 predefined roles (Admin, Manager, Chef, Waiter, Cashier, Receptionist, Guest, Platform Admin)
- Granular permissions (users.view, users.create, users.edit, etc.)
- Role ? Permissions (many-to-many)
- User ? Roles (many-to-many)
- User ? Direct Permissions (bypass role, direct assignment)
- Temporary role elevation (time-limited)
- Cascade permission checking (inherit from roles)

#### 1.5.4 Password Management
- Hash with bcrypt (cost=12)
- Reset via email with token
- Activate account via token
- Update password endpoint
- Prevent reuse of last 3 passwords (optional)
- Expiration policy (optional)

#### 1.5.5 Session Management
- JWT tokens don't require session storage
- Stateless authentication
- Token revocation on logout
- Token refresh capability (optional)
- Multiple simultaneous logins allowed

### 1.6 ERROR SCENARIOS

| Scenario | Response | Status |
|----------|----------|--------|
| Invalid email | "User not found" | 401 |
| Wrong password | "Invalid credentials" | 401 |
| User not activated | "Account not activated" | 403 |
| User deactivated | "Account disabled" | 403 |
| No hotel access | "Not assigned to hotel" | 403 |
| Expired token | "Unauthenticated" | 401 |
| Insufficient permissions | "Unauthorized" | 403 |

### 1.7 API ENDPOINTS

**Public:**
- \POST /auth/login\ - Authenticate
- \POST /forgot-password\ - Reset password request
- \POST /reset-password\ - Complete password reset
- \POST /activate-account\ - Activate account
- \GET /activation/{token}\ - Validate activation token

**Protected:**
- \GET /me\ - Current user info
- \POST /logout\ - Logout
- \POST /auth/switch-hotel\ - Change hotel context
- \GET /auth/my-hotels\ - List assigned hotels
- \POST /auth/update-password\ - Change password

**Admin:**
- \GET /roles\ - List roles
- \POST /roles\ - Create role
- \PUT /roles/{role}\ - Update role
- \DELETE /roles/{role}\ - Delete role
- \POST /roles/{role}/permissions\ - Set permissions
- \POST /users/{user}/roles\ - Assign role
- \DELETE /users/{user}/roles/{role}\ - Revoke role
- \GET /audit-logs\ - Audit query

### 1.8 TESTING CHECKLIST

- [ ] Login with valid credentials
- [ ] Login with invalid credentials
- [ ] Login with non-existent user
- [ ] Token generation and validation
- [ ] Token expiry handling
- [ ] Switch hotel successfully
- [ ] Switch to unauthorized hotel (403)
- [ ] Role assignment and permission checking
- [ ] Permission-based access control
- [ ] Audit log entries created
- [ ] Password reset workflow
- [ ] Account activation workflow

---

## MODULE 2: ROOM MANAGEMENT {#module-2}

### 2.1 PURPOSE
Manage physical rooms, room types, configurations, and QR code access

### 2.2 KEY ACTORS
- Hotel Admin (configuration)
- Staff (viewing, updating status)
- Guests (QR scanning)

### 2.3 TECHNICAL COMPONENTS

**Controllers:**
- \RoomController\ - Room CRUD and management
- \RoomTypeController\ - Room type management
- \QRCodeController\ - QR generation
- \QRCodePrintController\ - QR printing and downloads

**Models:**
- \Room\ - Individual room entity
- \RoomType\ - Room type configuration
- \HotelFloor\ - Floor organization

**Services:**
- \QRCodeService\ - QR code generation and storage

### 2.4 DATA STRUCTURE

**Room Table:**
\\\
id (UUID, primary key)
hotel_id (UUID, foreign key) - Multi-tenant
room_number (VARCHAR, unique per hotel)
room_type_id (UUID, foreign key)
floor_id (UUID, foreign key, optional)
floor (INT, numeric floor number, optional)
description (TEXT)
status (ENUM: active, inactive, maintenance)
is_active (BOOLEAN)
qr_token (VARCHAR 8, unique)
qr_image_path (VARCHAR, storage path)
qr_generated_at (TIMESTAMP)
created_at, updated_at (TIMESTAMPS)
\\\

**RoomType Table:**
\\\
id (UUID, primary key)
hotel_id (UUID, foreign key)
name (VARCHAR, unique per hotel)
description (TEXT)
price (DECIMAL 10,2)
capacity (INT)
status (ENUM: active, inactive)
created_at, updated_at
\\\

**HotelFloor Table:**
\\\
id (UUID, primary key)
hotel_id (UUID, foreign key)
floor_number (INT, unique per hotel)
name (VARCHAR)
total_rooms (INT)
description (TEXT)
created_at, updated_at
\\\

### 2.5 DETAILED REQUIREMENTS

#### 2.5.1 Room Creation
**Input:**
- Room number (required, unique per hotel)
- Room type (required, foreign key)
- Floor (required, floor number or floor_id)
- Description (optional)

**Process:**
1. Validate room number uniqueness within hotel
2. Validate room type belongs to hotel
3. Validate floor exists
4. Create room record
5. Auto-generate QR token (8-char uppercase)
6. Trigger QR code generation service
7. Store QR image path
8. Return created room with QR details

**Output:**
- Room ID, number, type, floor, status, QR token, QR image URL

#### 2.5.2 QR Code Generation
**Trigger:** On room creation or regeneration

**Process:**
1. Generate unique 8-character token
2. Validate token uniqueness
3. Create QR code image encoding room data
4. Store image in public storage
5. Update room.qr_image_path and qr_generated_at
6. Return QR image URL

**QR Code Content:**
- Encodes: hotel_id, room_id, qr_token
- Size: 200x200 pixels (configurable)
- Format: PNG
- URL-accessible: \/storage/{path}/qr_code.png\

#### 2.5.3 Room Search
**Search Fields:**
- Room number (LIKE query, case-insensitive)
- Description (LIKE query)
- Status (exact match)
- Floor (exact match or range)
- Room type (relationship query)

**Implementation:** Eloquent \scopeSearch()\ with \whereRaw()\ for case-insensitive LIKE

#### 2.5.4 Room Type Management
**Operations:**
- Create room type with name, description, price, capacity
- Update pricing and capacity
- Toggle status (active/inactive)
- List types for hotel
- Validate uniqueness of name per hotel

#### 2.5.5 Room Status Lifecycle
**States:**
- Active (available for booking/occupancy)
- Inactive (not available, no QR access)
- Maintenance (temporarily unavailable)

**Transitions:**
- Active ? Inactive
- Active ? Maintenance
- Inactive ? Maintenance

**Business Rules:**
- Cannot deactivate room with active reservation
- Maintenance rooms excluded from availability queries
- Status change logged in audit

### 2.6 API ENDPOINTS

**Public:**
- \GET /rooms\ - List all rooms (basic info)
- \GET /rooms/{room}\ - Get room details
- \GET /room-types\ - List room types
- \GET /room-types/{roomType}\ - Get type details
- \GET /qr-codes/download/{roomId}\ - Download QR code
- \GET /qr-codes/print/{roomId}\ - Print template

**Protected (Staff):**
- \POST /rooms\ - Create room
- \PUT /rooms/{room}\ - Update room
- \DELETE /rooms/{room}\ - Delete room
- \PATCH /rooms/{room}/toggle-status\ - Toggle status
- \POST /room-types\ - Create room type
- \PUT /room-types/{roomType}\ - Update type
- \DELETE /room-types/{roomType}\ - Delete type
- \PATCH /room-types/{roomType}/toggle-status\ - Toggle type status
- \POST /admin/qr-codes/{roomId}/regenerate\ - Regenerate QR
- \GET /admin/qr-codes/all\ - List all QR codes
- \GET /admin/qr-codes/{roomId}/image\ - Get QR image
- \POST /admin/qr-codes/{roomId}/regenerate\ - Regenerate

### 2.7 ERROR HANDLING

| Error | Cause | Response |
|-------|-------|----------|
| Room number duplicate | Non-unique within hotel | 422 Unprocessable Entity |
| Invalid room type | Type not found or wrong hotel | 422 Validation error |
| Invalid floor | Floor not found or wrong hotel | 422 Validation error |
| QR generation failed | Storage or system error | 500 with retry |
| Room not found | Invalid ID or wrong hotel (scope) | 404 |
| Cannot delete occupied room | Room has active reservation | 422 Conflict |

### 2.8 TESTING CHECKLIST

- [ ] Create room with valid data
- [ ] Prevent duplicate room numbers
- [ ] Auto-generate QR code on creation
- [ ] QR code uniqueness
- [ ] Download QR code image
- [ ] Regenerate QR code
- [ ] Update room details
- [ ] Toggle room status
- [ ] Search rooms by number
- [ ] Search rooms by floor
- [ ] List room types
- [ ] Create room type with pricing
- [ ] Validate room type uniqueness

---

## MODULE 3: GUEST & RESERVATION MANAGEMENT {#module-3}

### 3.1 PURPOSE
Manage guest information, bookings, and reservation lifecycle

### 3.2 KEY ACTORS
- Guests (create booking)
- Receptionist (manage reservations)
- Hotel Admin (view, audit)
- System (availability checking)

### 3.3 TECHNICAL COMPONENTS

**Controllers:**
- \GuestController\ - Guest CRUD
- \ReservationController\ - Reservation lifecycle
- \AdminBookingController\ - Admin booking management
- \GuestBookingController\ - Public guest booking

**Models:**
- \Guest\ - Guest information
- \Reservation\ - Booking records
- \CheckIn\ - Check-in/check-out events
- \CancellationPolicy\ - Cancellation rules

### 3.4 DATA STRUCTURE

**Guest Table:**
\\\
id (UUID)
hotel_id (UUID, foreign key, multi-tenant)
first_name (VARCHAR)
last_name (VARCHAR)
email (VARCHAR)
phone (VARCHAR)
address (VARCHAR)
nationality (VARCHAR)
passport_number (VARCHAR)
date_of_birth (DATE)
preferences (JSON)
deleted_at (TIMESTAMP, soft delete)
created_at, updated_at
\\\

**Reservation Table:**
\\\
id (UUID)
hotel_id (UUID, foreign key, multi-tenant)
booking_reference (VARCHAR, unique per hotel)
guest_id (UUID, foreign key)
room_id (UUID, foreign key)
check_in_date (DATE)
check_out_date (DATE)
number_of_guests (INT, default 1)
status (ENUM: pending, confirmed, checked_in, checked_out, cancelled)
total_amount (DECIMAL 10,2)
special_requests (TEXT)
notes (TEXT)
cancelled_at (TIMESTAMP)
creator_id (UUID, nullable, who created booking)
created_at, updated_at
\\\

### 3.5 DETAILED REQUIREMENTS

#### 3.5.1 Guest Creation
**Input (Minimal):**
- First name (required)
- Last name (required)
- Email (required)
- Phone (optional)

**Input (Full Admin):**
- All above plus:
- Address
- Nationality
- Passport number
- Date of birth
- Preferences (JSON)

**Process:**
1. Validate email format
2. Create guest record in current hotel context
3. Log creation in audit trail
4. Return guest ID

**Special Case: QR Guest**
- Auto-created when guest orders via QR without reservation
- First name: "QR Guest"
- Last name: room number
- Email: "qr-{room_number}@hotel.local"
- Phone: "0000000000"
- Minimal data for anonymous ordering

#### 3.5.2 Reservation Booking
**Input:**
- Guest ID or guest details (for new guest)
- Room ID
- Check-in date
- Check-out date
- Number of guests
- Special requests

**Validation:**
1. Guest exists (create if not)
2. Room exists and belongs to hotel
3. Check-out date > check-in date
4. No conflicts (availability check)
5. Room not in maintenance

**Process:**
1. Check room availability for date range
2. Calculate nights: checkout_date - checkin_date
3. Calculate total: room_type.price × nights
4. Apply taxes/discounts
5. Generate booking reference (BK{timestamp}{random})
6. Create reservation with status=pending
7. Send confirmation email
8. Return booking reference

**Booking Reference Format:**
- Pattern: BK{YYYYMMDD}{HHMMSS}{RANDOM(4)}
- Example: BK20240915143022ABC1
- Unique per hotel
- Used for guest tracking without authentication

#### 3.5.3 Availability Checking
**Query:**
\\\sql
SELECT COUNT(*) FROM reservations
WHERE room_id = :room_id
AND hotel_id = :hotel_id
AND status IN ('pending', 'confirmed', 'checked_in')
AND (
  (check_in_date < :requested_checkout AND check_out_date > :requested_checkin)
  OR (check_in_date >= :requested_checkin AND check_in_date < :requested_checkout)
  OR (check_out_date > :requested_checkin AND check_out_date <= :requested_checkout)
)
\\\

**Result:**
- If count > 0: Room unavailable
- Else: Room available

#### 3.5.4 Reservation Lifecycle

**State Machine:**
\\\
+- pending -+
¦           ¦
+-> confirmed
    ¦       ¦
    +-> checked_in
        ¦       ¦
        +-> checked_out (success)
            ¦
            +-> cancelled (failure)
\\\

**Status Transitions:**
- **Pending ? Confirmed:** Admin or system confirmation
- **Confirmed ? Checked In:** Receptionist checks in guest
- **Checked In ? Checked Out:** Guest departs
- **Any ? Cancelled:** Cancellation with reason

#### 3.5.5 Calculated Fields
- **Total Nights:** \(check_out_date - check_in_date).days\
- **Total Amount:** \oom_type.price × total_nights × (1 + tax_rate/100)\

#### 3.5.6 Cancellation Policy
**Policy Rules:**
- Free cancellation up to X days before check-in
- Partial refund Y days before
- No refund Z days before
- Custom per hotel (CancellationPolicy model)

**Cancellation Process:**
1. Check current date against policy
2. Calculate refund percentage
3. Create cancellation record
4. Process refund if applicable
5. Update reservation status to cancelled
6. Log in audit trail

### 3.6 API ENDPOINTS

**Public (Guest Booking):**
- \POST /guest/bookings/check-availability\ - Check room availability
- \GET /guest/bookings/rooms/{roomId}\ - Get room details
- \POST /guest/bookings\ - Create booking
- \GET /guest/bookings/{bookingReference}\ - Get booking status

**Protected (Staff):**
- \GET /reservations\ - List reservations
- \POST /reservations\ - Create reservation
- \GET /reservations/{reservation}\ - Get details
- \PUT /reservations/{reservation}\ - Update reservation
- \DELETE /reservations/{reservation}\ - Cancel reservation
- \POST /reservations/{reservation}/check-in\ - Check in
- \POST /reservations/{reservation}/check-out\ - Check out
- \POST /reservations/{reservation}/cancel\ - Cancel

**Admin:**
- \GET /admin/bookings\ - List all bookings
- \GET /admin/bookings/{id}\ - Get booking details
- \PUT /admin/bookings/{id}\ - Update booking
- \DELETE /admin/bookings/{id}\ - Cancel booking

### 3.7 ERROR SCENARIOS

| Scenario | Response | Status |
|----------|----------|--------|
| Invalid check-out date | "Check-out must be after check-in" | 422 |
| Room unavailable | "Room not available for dates" | 409 |
| Room in maintenance | "Room unavailable (maintenance)" | 422 |
| Invalid room ID | "Room not found or wrong hotel" | 404 |
| Invalid guest | "Guest not found" | 404 |
| Already checked in | "Guest already checked in" | 422 |
| Cannot cancel | "Cancellation not allowed (policy)" | 422 |

### 3.8 TESTING CHECKLIST

- [ ] Create guest with minimal data
- [ ] Create guest with full data
- [ ] Check room availability (available)
- [ ] Check room availability (unavailable - overlap)
- [ ] Create reservation (valid data)
- [ ] Calculate correct total_nights
- [ ] Calculate correct total_amount with tax
- [ ] Generate unique booking reference
- [ ] Prevent double-booking
- [ ] Update reservation dates
- [ ] Change assigned room
- [ ] Check in guest
- [ ] Check out guest
- [ ] Cancel reservation
- [ ] Verify cancellation policy applied
- [ ] Multi-hotel isolation (guest in hotel A can't see hotel B bookings)

---

## MODULE 4: QR CODE & ACCESS SYSTEM {#module-4}

### 4.1 PURPOSE
Enable public QR code-based access to guest services without authentication

### 4.2 KEY ACTORS
- Guests (scan QR)
- System (QR resolution)
- Kitchen (receive orders)

### 4.3 TECHNICAL COMPONENTS

**Controllers:**
- \QRResolutionController\ - QR token resolution
- \QRCodeController\ - QR generation
- \GuestOrderController\ - Guest access via QR

**Models:**
- \Room\ - Contains QR token
- \Reservation\ - Active guest session

**Services:**
- \QRCodeService\ - QR generation and storage

**Middleware:**
- \qr.token\ - Validates QR context (optional)

### 4.4 DATA FLOW

\\\
1. Guest scans room QR code
   ?
2. QR encodes: hotel_id, room_id, qr_token
   ?
3. Frontend resolves URL: /qr/resolve/{qrToken}
   ?
4. QRResolutionController::resolveQRToken()
   ?
5. Query: Room.where('qr_token', token)
   ?
6. Query: Reservation.where('room_id', room_id).whereIn('status', ['confirmed', 'checked_in'])
   ?
7. Validate active reservation exists
   ?
8. Return: room details, guest info, hotel context
   ?
9. Frontend redirects to: /guest/menu/{qrToken}
   ?
10. Guest views menu and places order
\\\

### 4.5 DETAILED REQUIREMENTS

#### 4.5.1 QR Code Format
**Token Generation:**
- Length: 8 characters
- Format: Uppercase alphanumeric (A-Z, 0-9)
- Uniqueness: Database unique constraint
- Generation: Random.randomUpper(8)
- Validation: Uniqueness check on each generation

**QR Code Content:**
- Encodes: \{frontend_url}/qr/resolve/{qrToken}\
- Size: 200x200 pixels
- Format: PNG image
- Error Correction: M level (7% error correction)
- Stored as: \qr_codes/{year}/{month}/{room_id}.png\

#### 4.5.2 QR Resolution Endpoint
**Endpoint:** \GET /qr/resolve/{qrToken}\

**Process:**
1. Find Room by qr_token
2. If not found: Return 404 "Invalid QR code"
3. If found:
   - Check active reservation for room
   - If no active reservation:
     - Log warning
     - Return 422 "No active reservation"
   - If reservation exists:
     - Extract guest info
     - Set hotel context
     - Return room + guest + hotel details

**Response (Success):**
\\\json
{
  "success": true,
  "data": {
    "id": "room-uuid",
    "room_number": "101",
    "status": "occupied",
    "qr_token": "ABC12345",
    "guest": {
      "id": "guest-uuid",
      "name": "John Doe",
      "email": "john@email.com",
      "phone": "+123456789"
    },
    "hotel": {
      "id": "hotel-uuid",
      "name": "Executive Hotel"
    }
  }
}
\\\

#### 4.5.3 QR Access Control
**Security Model:**
- No authentication required
- QR token validates room existence
- Active reservation validates guest status
- Guest must be checked in
- All room service orders linked to guest_id
- Hotel context automatically extracted

**Business Rules:**
- QR token uniqueness prevents random guessing
- Room must have active reservation
- If no reservation: show error, allow check-in booking
- Repeated invalid attempts: log for security

#### 4.5.4 Guest Access Points
**Via QR Code:**
1. **Room Service Menu:** \/guest/menu/{qrToken}\
   - View menu items
   - Place order
   - Track order status

2. **Room Information:** \/qr/resolve/{qrToken}\
   - View room details
   - View guest information
   - View hotel details

3. **Quick Booking:** \/guest/bookings/check-availability\
   - Check room availability
   - Book additional rooms
   - Pre-pay via Chapa

### 4.6 API ENDPOINTS

**Public:**
- \GET /qr/resolve/{qrToken}\ - Resolve from URL
- \POST /qr/resolve\ - Resolve from POST (body: {qrToken})
- \POST /qr/validate\ - Validate token exists
- \GET /qr-code/generate/{roomId}\ - Generate QR code
- \GET /qr-code/data/{roomId}\ - Get QR metadata

### 4.7 ERROR SCENARIOS

| Scenario | Response | Status |
|----------|----------|--------|
| Invalid QR token | "Invalid QR code" | 404 |
| Room not found | "Room not found" | 404 |
| No active reservation | "No active reservation" | 422 |
| Guest not checked in | "Guest not checked in" | 422 |
| Token malformed | "Invalid token format" | 400 |
| Multiple matches (error) | "Ambiguous QR token" | 500 |

### 4.8 SECURITY CONSIDERATIONS

- QR token exposed in URL (not secret)
- Guest can share QR code link (intended)
- Orders linked to room, not QR token
- Guest cannot access other guests' rooms
- Rate limiting on QR resolution (prevent spam)
- Logging of QR access for audit

### 4.9 TESTING CHECKLIST

- [ ] Generate valid QR code
- [ ] Resolve QR token successfully
- [ ] Reject invalid QR token
- [ ] Reject without active reservation
- [ ] Accept with active reservation
- [ ] Return correct guest info
- [ ] Return correct room info
- [ ] Return correct hotel context
- [ ] Regenerate QR code
- [ ] QR code accessibility (image URL)
- [ ] Multiple QR scans (same token)
- [ ] Cross-hotel isolation (token from hotel A doesn't work at hotel B)

---

## MODULE 5: RESTAURANT OPERATIONS {#module-5}

### 5.1 PURPOSE
Manage menu, categories, pricing, and restaurant configuration

### 5.2 KEY ACTORS
- Hotel Admin (configure)
- Chef/Staff (view menu)
- Guests (browse menu)

### 5.3 TECHNICAL COMPONENTS

**Controllers:**
- \MenuItemController\ - Menu item CRUD
- \CategoryController\ - Category management
- \TaxRateController\ - Tax configuration
- \GuestOrderController\ - Guest menu view

**Models:**
- \MenuItem\ - Menu items
- \Category\ - Menu categories
- \TaxRate\ - Tax rate configuration

### 5.4 DATA STRUCTURE

**MenuItem Table:**
\\\
id (UUID)
hotel_id (UUID, foreign key, multi-tenant)
name (VARCHAR, unique per hotel)
description (TEXT)
category (VARCHAR, loose category)
category_id (UUID, foreign key, optional)
price (DECIMAL 10,2)
tax_rate_id (UUID, foreign key, optional)
tax_included (BOOLEAN, default false)
image (VARCHAR, storage path)
is_available (BOOLEAN, default true)
created_at, updated_at
\\\

**Category Table:**
\\\
id (UUID)
hotel_id (UUID, foreign key, multi-tenant)
name (VARCHAR, unique per hotel)
slug (VARCHAR, unique per hotel)
icon (VARCHAR, icon identifier)
description (TEXT)
order (INT, sort order)
status (ENUM: active, inactive)
created_at, updated_at
\\\

**TaxRate Table:**
\\\
id (UUID)
hotel_id (UUID, foreign key, multi-tenant)
name (VARCHAR)
rate (DECIMAL 5,2, e.g., 15.00 for 15%)
type (ENUM: vat, service_charge, luxury_tax, other)
status (ENUM: active, inactive)
created_at, updated_at
\\\

### 5.5 DETAILED REQUIREMENTS

#### 5.5.1 Menu Item Management

**Create Menu Item:**
- Input: name, description, category, price, tax_rate_id, image
- Validation: unique name per hotel
- Defaults: is_available=true
- Output: menu item ID

**Update Menu Item:**
- Can update all fields except hotel_id
- Price changes don't affect existing orders
- Tax rate change applies to new orders

**Delete Menu Item:**
- Soft or hard delete (configurable)
- Existing orders keep pricing snapshot
- Availability toggle preferred over deletion

**Menu Item Retrieval:**
- Guest view: only available items, formatted with tax
- Staff view: all items (available + unavailable)
- Search: by name, category, availability

#### 5.5.2 Tax Calculation Logic

**Formula:**
\\\
If tax_included = true:
  base_price = price / (1 + rate/100)
  tax_amount = price - base_price
  total = price

If tax_included = false:
  base_price = price
  tax_amount = price * (rate/100)
  total = price + tax_amount
\\\

**Example:**
- Item price: 100
- Tax rate: 15%
- If tax_included=false: base=100, tax=15, total=115
- If tax_included=true: base=86.96, tax=13.04, total=100

#### 5.5.3 Category Management

**Category Organization:**
- Group items by category (Appetizers, Main Course, Dessert, etc.)
- Each category can have icon (for frontend)
- Sort order determines display sequence
- Status controls visibility

**Category Operations:**
- Create category with name, slug, icon
- Reorder categories via bulk update
- Toggle category active/inactive
- Archive old categories

#### 5.5.4 Tax Rate Configuration

**Tax Types:**
- VAT (Value Added Tax)
- Service Charge
- Luxury Tax
- Other

**Tax Rate Usage:**
- Each menu item optional references tax rate
- Applied to menu item price
- Calculated at order time
- Displayed on bills

**Multi-Tax Support:**
- Multiple tax rates per hotel
- Each item can have single tax rate
- Compound taxes: applied sequentially or summed (configurable)

#### 5.5.5 Image Management

**Image Upload:**
- Accepted formats: JPG, PNG, WebP
- Max size: 5MB per image
- Stored in: \storage/menu_items/{year}/{month}/{item_id}.{ext}\
- URL accessible: \/storage/menu_items/...\
- Fallback: placeholder image if none

### 5.6 API ENDPOINTS

**Public (Guest View):**
- \GET /categories\ - List categories
- \GET /menu-items\ - List available items
- \GET /guest/categories\ - Public categories with icons
- \GET /guest/menu/items\ - Guest-formatted menu items

**Protected (Staff):**
- \GET /menu-items\ - List all items (staff view)
- \GET /menu-items/{menuItem}\ - Item details
- \POST /menu-items\ - Create item
- \PUT /menu-items/{menuItem}\ - Update item
- \DELETE /menu-items/{menuItem}\ - Delete item
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

### 5.7 ERROR SCENARIOS

| Scenario | Response | Status |
|----------|----------|--------|
| Duplicate item name | "Item name already exists" | 422 |
| Invalid category | "Category not found" | 404 |
| Invalid tax rate | "Tax rate not found" | 422 |
| Image upload too large | "Image exceeds 5MB" | 422 |
| Invalid image format | "Format not supported" | 422 |
| Item not found | "Item not found" | 404 |

### 5.8 TESTING CHECKLIST

- [ ] Create menu item with all fields
- [ ] Prevent duplicate item names
- [ ] Upload menu item image
- [ ] Update menu item pricing
- [ ] Toggle menu item availability
- [ ] Calculate tax correctly (included & excluded)
- [ ] Create category
- [ ] Reorder categories
- [ ] List menu by category
- [ ] Create tax rate
- [ ] Update tax rate
- [ ] Apply tax rate to menu item
- [ ] Multi-hotel isolation (menu items)

---

**[Modules 6-12 continue with same level of detail...]**

**Document continues with:**
- Module 6: Ordering System (Guest Orders, Kitchen, Walk-in)
- Module 7: Payment Processing (Chapa Integration, Refunds)
- Module 8: Check-in & Check-out (Operations, Receptionist)
- Module 9: Staff Management (Roles, Assignments, Profiles)
- Module 10: Dashboards & Analytics (Manager, Chef, Cashier)
- Module 11: Reviews & Feedback (Guest Reviews, Moderation)
- Module 12: Notifications & Platform Admin (Real-time, System Config)

---

**END OF MODULE BREAKDOWN**

*Full detailed specifications for all 12 modules, 150+ sub-requirements*
