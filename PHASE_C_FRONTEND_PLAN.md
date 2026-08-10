# Phase C: Frontend Components & Views - Implementation Plan

**Status**: Ready to implement  
**Dependencies**: Phase A & B complete  
**Estimated Time**: 2-3 hours

---

## Overview

Phase C creates the frontend infrastructure to support QR-based food ordering for both room service (existing) and walk-in restaurant customers (new). The key challenge is making the existing QRMenu component context-aware while maintaining backward compatibility.

---

## Architecture Strategy

### Context Detection Flow
```
User scans QR code
    ↓
URL: /order/{token} (room) OR /restaurant-order/{token} (table)
    ↓
Frontend routes to appropriate component
    ↓
Component calls GET /api/qr/resolve/{token}
    ↓
Backend returns context ('room' or 'table') + data
    ↓
Frontend adapts UI based on context
    ↓
User orders → POST /api/orders with qr_token
    ↓
Backend creates appropriate order type
```

---

## Components to Create/Modify

### 1. ✅ QR Resolution Service (NEW)
**File**: `src/services/qrService.ts`

**Purpose**: Handle QR token resolution and validation

**Methods**:
- `resolveQRToken(token: string)` - Resolve token to context
- `validateQRToken(token: string)` - Quick validation check

---

### 2. ✅ Unified Order Service (NEW)
**File**: `src/services/unifiedOrderService.ts`

**Purpose**: Handle order creation for both contexts

**Methods**:
- `createOrder(qrToken, items, specialRequests, paymentType)` - Create order (auto-detects type)

---

### 3. ✅ Restaurant Table Types (NEW)
**File**: `src/types/restaurantTable.ts`

**Purpose**: TypeScript interfaces for restaurant tables

**Interfaces**:
- `RestaurantTable`
- `QRResolutionResult`
- `OrderContext`

---

### 4. ⚠️ Update Existing QRMenu Component (MODIFY)
**File**: `src/views/guest/QRMenu.vue`

**Changes Needed**:
- Add context detection on mount
- Show different header based on context (room number vs table number)
- Adapt payment options (room_charge vs cash/card)
- Use unified order service

**Strategy**: Minimal changes to preserve existing functionality

---

### 5. ✅ Restaurant QR Menu Component (NEW - OPTIONAL)
**File**: `src/views/restaurant/RestaurantQRMenu.vue`

**Purpose**: Dedicated component for restaurant table ordering (cleaner separation)

**Features**:
- Table-specific UI
- Cash/card payment only
- No guest/reservation info
- Simpler layout

**Note**: Can reuse QRMenuLayout component with different props

---

### 6. ✅ Manager: Restaurant Tables List (NEW)
**File**: `src/views/manager/RestaurantTables.vue`

**Purpose**: Manager interface to manage restaurant tables

**Features**:
- List all tables with pagination
- Create new tables
- Edit existing tables
- View/download QR codes
- Table statistics
- Search and filter

---

### 7. ✅ Manager: Restaurant Table Form Modal (NEW)
**File**: `src/components/manager/RestaurantTableFormModal.vue`

**Purpose**: Form for creating/editing tables

**Fields**:
- Table number
- Table name
- Capacity
- Location
- Status
- Active toggle

---

### 8. ✅ Manager Table Service (NEW)
**File**: `src/services/manager/restaurantTableService.ts`

**Purpose**: API calls for table management

**Methods**:
- `getTables(params)` - List with filters
- `getTableById(id)` - Get single table
- `createTable(data)` - Create new table
- `updateTable(id, data)` - Update table
- `deleteTable(id)` - Delete table
- `regenerateQR(id)` - Regenerate QR code
- `getStatistics()` - Get table stats

---

### 9. ✅ Restaurant Table Store (NEW)
**File**: `src/stores/restaurantTableStore.ts`

**Purpose**: Pinia store for table state management

**State**:
- tables list
- current table
- loading states
- filters

---

### 10. ⚠️ Update Router (MODIFY)
**File**: `src/router/index.ts`

**Add Routes**:
```typescript
// Public routes
{
  path: '/restaurant-order/:token',
  name: 'RestaurantOrder',
  component: () => import('@/views/restaurant/RestaurantQRMenu.vue')
}

// Manager routes (existing group)
{
  path: 'restaurant-tables',
  name: 'RestaurantTables',
  component: () => import('@/views/manager/RestaurantTables.vue')
}
```

---

## Detailed Implementation

### File 1: QR Service

```typescript
// src/services/qrService.ts
import axios from './axios'

export interface QRResolutionResult {
  success: boolean
  context: 'room' | 'table' | null
  data: {
    room_id?: string
    room_number?: string
    table_id?: string
    table_number?: string
    table_name?: string
    capacity?: number
    location?: string
    status?: string
  } | null
  message: string
}

export const qrService = {
  async resolveQRToken(token: string): Promise<QRResolutionResult> {
    const response = await axios.get(`/qr/resolve/${token}`)
    return response.data
  },

  async validateQRToken(token: string): Promise<{ valid: boolean; context: string | null }> {
    const response = await axios.post('/qr/validate', { qr_token: token })
    return response.data
  }
}
```

---

### File 2: Unified Order Service

```typescript
// src/services/unifiedOrderService.ts
import axios from './axios'

export interface OrderItem {
  menu_item_id: string
  quantity: number
}

export interface CreateOrderRequest {
  qr_token: string
  items: OrderItem[]
  special_requests?: string
  payment_type?: 'room_charge' | 'cash' | 'card'
}

export const unifiedOrderService = {
  async createOrder(orderData: CreateOrderRequest) {
    const response = await axios.post('/orders', orderData)
    return response.data
  }
}
```

---

### File 3: Restaurant Table Types

```typescript
// src/types/restaurantTable.ts
export interface RestaurantTable {
  id: string
  table_number: string
  table_name: string | null
  capacity: number
  location: string | null
  status: 'available' | 'occupied' | 'reserved' | 'maintenance'
  is_active: boolean
  qr_token: string
  qr_image_path: string | null
  qr_code_url: string | null
  qr_generated_at: string | null
  created_at: string
  updated_at: string
}

export interface TableFilters {
  search?: string
  status?: string
  location?: string
  is_active?: boolean
  sort_by?: string
  sort_order?: 'asc' | 'desc'
  per_page?: number
  page?: number
}

export interface TableStatistics {
  total: number
  active: number
  available: number
  occupied: number
  reserved: number
  maintenance: number
}
```

---

### File 4: Update QRMenu Component

**Key Changes**:

```vue
<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { qrService } from '@/services/qrService'
import { unifiedOrderService } from '@/services/unifiedOrderService'

const route = useRoute()
const qrToken = ref('')
const orderContext = ref<'room' | 'table' | null>(null)
const contextData = ref<any>(null)

// Detect context on mount
onMounted(async () => {
  qrToken.value = route.params.token as string
  
  try {
    const result = await qrService.resolveQRToken(qrToken.value)
    if (result.success) {
      orderContext.value = result.context
      contextData.value = result.data
      
      // Set display values based on context
      if (orderContext.value === 'room') {
        roomNumber.value = result.data.room_number
        // ... existing room logic
      } else if (orderContext.value === 'table') {
        // Show table info instead
        tableNumber.value = result.data.table_number
        tableName.value = result.data.table_name
      }
    }
  } catch (error) {
    // Handle error
  }
})

// Update order creation to use unified service
const placeOrder = async () => {
  try {
    const orderData = {
      qr_token: qrToken.value,
      items: cartItems.value.map(item => ({
        menu_item_id: item.id,
        quantity: item.quantity
      })),
      special_requests: specialRequests.value,
      payment_type: orderContext.value === 'room' ? 'room_charge' : selectedPaymentMethod.value
    }
    
    const response = await unifiedOrderService.createOrder(orderData)
    // Handle success
  } catch (error) {
    // Handle error
  }
}
</script>

<template>
  <!-- Show different header based on context -->
  <div v-if="orderContext === 'room'" class="room-header">
    Room {{ roomNumber }}
  </div>
  <div v-else-if="orderContext === 'table'" class="table-header">
    Table {{ tableNumber }}
    <span v-if="tableName">- {{ tableName }}</span>
  </div>
  
  <!-- Show appropriate payment options -->
  <div v-if="orderContext === 'room'" class="payment-options">
    <option value="room_charge">Charge to Room</option>
  </div>
  <div v-else class="payment-options">
    <option value="cash">Cash</option>
    <option value="card">Card</option>
  </div>
</template>
```

---

## Implementation Order

### Phase C.1: Core Services (30 minutes)
1. Create `qrService.ts`
2. Create `unifiedOrderService.ts`
3. Create `restaurantTable.ts` types
4. Test services with existing backend

### Phase C.2: Update QRMenu (45 minutes)
1. Add context detection to QRMenu.vue
2. Adapt UI based on context
3. Update order creation logic
4. Test with both room and table QR codes

### Phase C.3: Manager Tables UI (60 minutes)
1. Create `RestaurantTables.vue` (list view)
2. Create `RestaurantTableFormModal.vue`
3. Create `restaurantTableService.ts`
4. Create `restaurantTableStore.ts`
5. Add route to router
6. Test CRUD operations

### Phase C.4: Optional - Dedicated Restaurant Component (30 minutes)
1. Create `RestaurantQRMenu.vue`
2. Customize for table ordering
3. Add route
4. Test separately

---

## Testing Checklist

### QR Resolution
- [ ] Resolve room QR token → shows room UI
- [ ] Resolve table QR token → shows table UI
- [ ] Invalid token → shows error

### Order Creation
- [ ] Room service order → charges to room
- [ ] Walk-in order → cash/card payment
- [ ] Cart functionality works in both contexts

### Manager Tables
- [ ] List tables with pagination
- [ ] Create new table
- [ ] Edit existing table
- [ ] Delete table
- [ ] View QR code
- [ ] Download QR code
- [ ] Search and filter

---

## UI/UX Considerations

### Room Service UI
- Show room number prominently
- Display guest name if available
- Default payment: Charge to Room
- Show "Order will be delivered to your room"

### Walk-In UI
- Show table number prominently
- Show table name if available
- Payment options: Cash or Card
- Show "Order for Table {number}"
- Optional: Ask for name/phone

### Manager UI
- Table-centric dashboard
- QR code preview/download
- Status indicators (available/occupied)
- Quick actions (edit, regenerate QR, delete)

---

## Backward Compatibility

✅ **Existing room QR ordering continues to work**
- Existing `/order/{token}` routes unchanged
- QRMenu component enhanced, not replaced
- Old API endpoints still functional

---

## Next Steps After Phase C

**Phase D**: Kitchen & Waiter Integration
- Update kitchen view to show order type
- Waiter assignment for walk-in orders
- Table status management

**Phase E**: Permissions & Roles
- Manager permissions for table management
- Cashier access to walk-in orders

**Phase F**: Testing & Polish
- End-to-end testing
- UI refinements
- Performance optimization

---

## Success Criteria

Phase C is complete when:
- [ ] QR resolution service working
- [ ] QRMenu component context-aware
- [ ] Orders can be created for both contexts
- [ ] Manager can manage restaurant tables
- [ ] QR codes can be viewed/downloaded
- [ ] All routes configured
- [ ] TypeScript types defined
- [ ] No regressions in existing functionality

---

**Ready to implement!** Starting with Phase C.1: Core Services...
