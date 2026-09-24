# Multi-Hotel / Multi-Tenant Platform Architecture Guide

This document outlines the multi-hotel architecture implemented in the Hotel Management System (HMS), transforming the single-hotel application into an enterprise multi-tenant platform with shared tables and complete tenant data isolation.

---

## 1. Database Architecture & Schema Design

### 1.1 Model & Key Conventions
- All primary keys across tenant tables use **UUIDs** (UUIDv4) via Laravel's `HasUuids` trait.
- All tenant-owned records have a direct foreign key:
  ```sql
  hotel_id UUID NOT NULL REFERENCES hotels(id) ON DELETE CASCADE
  ```
- Indexes are maintained on `hotel_id` across every tenant-owned table for query performance.

### 1.2 Constraint Realignment (Global $\rightarrow$ Composite Unique)
To permit identical room numbers, menu categories, or shifts across different hotels:
- `rooms`: `UNIQUE(hotel_id, room_number)`
- `room_types`: `UNIQUE(hotel_id, name)`
- `hotel_floors`: `UNIQUE(hotel_id, floor_number)`
- `hotel_shifts`: `UNIQUE(hotel_id, name)`
- `categories`: `UNIQUE(hotel_id, slug)` and `UNIQUE(hotel_id, name)`
- `restaurant_tables`: `UNIQUE(hotel_id, table_number)`
- `orders`: `UNIQUE(hotel_id, order_number)`

---

## 2. User & Hotel Membership Architecture

Instead of locking a user to a single hotel, the platform implements a **Membership Architecture**:

```
[Users] (Global Identity)
   |
   +---< [hotel_users] (Tenant Membership Pivot)
            |-- hotel_id (UUID)
            |-- user_id (UUID)
            |-- role (admin, manager, receptionist, cashier, chef, waiter)
            |-- is_active (boolean)
            +-- UNIQUE(hotel_id, user_id)
```

- **Platform Admin**: Users with `is_platform_admin = true` can manage platform settings, onboard hotels, and supervise all tenants.
- **Hotel Admin**: Users assigned role `admin` in `hotel_users` have full administrative privileges strictly within their hotel.
- **Cross-Hotel Roles**: A user can be a `manager` in Hotel A and a `receptionist` in Hotel B.

---

## 3. Tenant Context & Request Flow

```
HTTP Request
     |
     v
[Sanctum Authentication]
     |
     v
[IdentifyTenant Middleware]
     |-- Reads 'X-Hotel-ID' header
     |-- Validates user has active membership in target hotel (or is platform admin)
     |-- Fallback: auto-binds hotel if user belongs to exactly one hotel
     |-- Rejects unauthorized cross-tenant requests with 403 Forbidden
     |
     v
[TenantContext Service] (Request-Scoped Singleton)
     |-- TenantContext::getHotelId()
     |-- TenantContext::getHotel()
     |-- TenantContext::getCurrentRole()
     |
     v
[Eloquent Global Scope: TenantScope]
     |-- Appends `WHERE hotel_id = ?` to all model queries
     |-- Automatically attaches `hotel_id` on model creation
     |
     v
[Resource Policy & Controller Execution]
```

---

## 4. Eloquent Multi-Tenancy Implementation

### 4.1 Global Scope (`TenantScope`)
Implemented in `App\Models\Scopes\TenantScope`.
When a tenant context is active, every read, update, or delete query automatically includes:
```sql
WHERE {table}.hotel_id = '<current-tenant-uuid>'
```

### 4.2 Trait (`BelongsToTenant`)
Applied to `Room`, `RoomType`, `Guest`, `Reservation`, `Order`, `MenuItem`, `Category`, `RestaurantTable`, `Payment`, `HotelFloor`, and `HotelShift`:
```php
use App\Models\Traits\BelongsToTenant;

class Room extends Model
{
    use HasUuids, BelongsToTenant;
    // ...
}
```
- Automatically adds `TenantScope`.
- Automatically populates `$model->hotel_id = TenantContext::getHotelId()` on creation.
- Provides `$query->withoutTenant()` for platform administrator tasks.

---

## 5. Security & Cross-Tenant Attack Prevention

### 5.1 IDOR Prevention
If a user in Hotel A attempts to access or modify a resource belonging to Hotel B:
- `GET /api/rooms/{HotelB_RoomId}`:
  Because `TenantScope` executes `WHERE id = ? AND hotel_id = HotelA`, the database returns `null`, resulting in a safe `404 Not Found`.

### 5.2 Cross-Tenant Relationship Guarding
- **Reservations**: When booking, `ReservationController` verifies that both `room->hotel_id` and `guest->hotel_id` belong to `TenantContext::getHotelId()`.
- **Orders**: `UnifiedOrderController` validates that every `menu_item->hotel_id` matches the room or table's `hotel_id`. Foreign items cause immediate rejection.
- **Policies**: Policies (`RoomPolicy`, `ReservationPolicy`, `OrderPolicy`, `PaymentPolicy`, `GuestPolicy`, `MenuItemPolicy`) explicitly verify that `$resource->hotel_id === TenantContext::getHotelId()`.

---

## 6. QR Code Architecture

1. **Room QR Codes**:
   - Each physical room has an 8-character `qr_token`.
   - Resolving the token via `QRResolutionService` returns the room and its `hotel_id` and `hotel_name`.
2. **Table QR Codes**:
   - Each dining table has an 8-character `qr_token`.
   - Resolving the token returns the table and its `hotel_id` and `hotel_name`.
3. **Ordering**:
   - When a guest or walk-in customer orders, the backend determines the hotel context from the resolved room/table, ensuring all food orders and payments route to the correct hotel kitchen and cashier.

---

## 7. Frontend Integration

### 7.1 Pinia Store (`useHotelStore`)
- Manages `currentHotel` and `availableHotels`.
- Persists to `localStorage` (`current_hotel`, `availableHotels`).
- `switchHotel(hotelId)`: calls `POST /api/auth/switch-hotel`, updates context, and triggers views to reload.

### 7.2 Axios Interceptor
In `Client2/vue-project/src/services/axios.ts`:
```typescript
const currentHotelRaw = localStorage.getItem('current_hotel')
if (currentHotelRaw) {
  const currentHotel = JSON.parse(currentHotelRaw)
  if (currentHotel?.id) {
    config.headers['X-Hotel-ID'] = currentHotel.id
  }
}
```
All API requests automatically carry the active hotel context.

### 7.3 Navbar Hotel Switcher
- If user belongs to 1 hotel: displays the current hotel badge.
- If user belongs to multiple hotels: displays an interactive dropdown switcher in the navbar to seamlessly switch hotel context without logging out.

---

## 8. Platform Administration API

Protected by `auth:sanctum` and `platform.admin` middleware:
- `GET /api/platform/statistics`: Aggregated cross-hotel metrics.
- `GET /api/platform/hotels`: List all onboarded hotels.
- `POST /api/platform/hotels`: Onboard a new hotel and assign its initial administrator.
- `GET /api/platform/hotels/{id}`: View hotel details, total rooms, and total bookings.
- `PUT /api/platform/hotels/{id}`: Update hotel configuration.
- `PATCH /api/platform/hotels/{id}/status`: Activate, deactivate, or suspend a hotel tenant.

---

## 9. Data Migration Strategy

Run the migrations in sequential order:
```bash
# 1. Create hotels table
php artisan migrate --path=database/migrations/2026_08_30_000001_create_hotels_table.php

# 2. Create hotel_users membership table
php artisan migrate --path=database/migrations/2026_08_30_000002_create_hotel_users_table.php

# 3. Seed Hotel #1 ('Executive Horizon Hotel') and attach existing users
php artisan migrate --path=database/migrations/2026_08_30_000003_seed_default_hotel_and_attach_users.php

# 4. Add is_platform_admin flag to users
php artisan migrate --path=database/migrations/2026_08_30_000004_add_is_platform_admin_to_users_table.php

# 5. Add hotel_id foreign keys to all tenant tables
php artisan migrate --path=database/migrations/2026_08_30_000005_add_hotel_id_to_tenant_tables.php

# 6. Backfill existing rows with Hotel #1 id (Zero Data Loss)
php artisan migrate --path=database/migrations/2026_08_30_000006_backfill_existing_records_with_hotel_id.php

# 7. Update uniqueness constraints to composite (hotel_id + attribute)
php artisan migrate --path=database/migrations/2026_08_30_000007_update_unique_constraints_for_multitenancy.php
```

---

## 10. Automated Tests

Run the tenant isolation test suite:
```bash
php artisan test --filter=TenantIsolationTest
```
Scenarios verified:
- Hotel A user cannot view Hotel B rooms (404).
- User cannot switch to unauthorized hotel (403).
- Platform admin can access platform statistics (200).
- Regular hotel admin is blocked from platform statistics (403).
- Cross-tenant menu item order creation is strictly rejected.
