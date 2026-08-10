# Phase C: Frontend - Progress Summary

**Date**: August 9, 2026  
**Status**: ✅ Core Services Complete | ⏳ UI Components Pending

---

## ✅ Completed (Phase C.1: Core Services)

### 1. QR Service ✅
**File**: `Client2/vue-project/src/services/qrService.ts`

**Features**:
- `resolveQRToken(token)` - Resolves QR token to room or table context
- `validateQRToken(token)` - Lightweight validation
- Proper TypeScript interfaces
- Error handling

**Usage**:
```typescript
import { qrService } from '@/services/qrService'

const result = await qrService.resolveQRToken('ABC12345')
if (result.success && result.context === 'room') {
  // Handle room order
} else if (result.success && result.context === 'table') {
  // Handle table order
}
```

---

### 2. Unified Order Service ✅
**File**: `Client2/vue-project/src/services/unifiedOrderService.ts`

**Features**:
- `createOrder(orderData)` - Single endpoint for both order types
- Backend automatically determines room_service vs walk_in
- TypeScript interfaces for requests and responses
- Error handling

**Usage**:
```typescript
import { unifiedOrderService } from '@/services/unifiedOrderService'

const order = await unifiedOrderService.createOrder({
  qr_token: 'ABC12345',
  items: [
    { menu_item_id: 'uuid1', quantity: 2 },
    { menu_item_id: 'uuid2', quantity: 1 }
  ],
  special_requests: 'No onions',
  payment_type: 'room_charge' // or 'cash' or 'card'
})
```

---

### 3. Restaurant Table Types ✅
**File**: `Client2/vue-project/src/types/restaurantTable.ts`

**Interfaces Defined**:
- `RestaurantTable` - Table entity
- `CreateTableRequest` - Create table payload
- `UpdateTableRequest` - Update table payload
- `TableFilters` - Filter/search/sort params
- `TableStatistics` - Stats response
- `PaginatedTablesResponse` - Paginated list
- `OrderContext` - Context helper

---

### 4. Manager Restaurant Table Service ✅
**File**: `Client2/vue-project/src/services/manager/restaurantTableService.ts`

**Methods**:
- `getTables(filters)` - List with pagination/filtering
- `getTableById(id)` - Get single table
- `createTable(data)` - Create new table
- `updateTable(id, data)` - Update table
- `deleteTable(id)` - Delete table
- `regenerateQR(id)` - Regenerate QR code
- `getStatistics()` - Get table stats
- `downloadQRCode(url, tableNumber)` - Download QR image

---

## ⏳ Remaining Tasks

### Phase C.2: Update QRMenu Component (45 min)
**File**: `Client2/vue-project/src/views/guest/QRMenu.vue`

**Changes Needed**:
1. Import qrService and unifiedOrderService
2. Add context detection in onMounted()
3. Adapt UI based on context:
   - Show room number OR table number
   - Show appropriate payment options
   - Adjust header/footer text
4. Update order creation to use unifiedOrderService
5. Test with both room and table QR codes

**Implementation Approach**:
- Add minimal changes to existing component
- Use v-if to show context-specific UI
- Maintain backward compatibility

---

### Phase C.3: Manager Tables UI (60 min)

#### 3.1 Restaurant Tables List View
**File**: `Client2/vue-project/src/views/manager/RestaurantTables.vue`

**Features**:
- Table showing all restaurant tables
- Pagination controls
- Search by table number/name/location
- Filter by status (available/occupied/reserved/maintenance)
- Sort by various columns
- Actions: View QR, Edit, Delete
- Statistics cards at top
- Create New Table button

---

#### 3.2 Table Form Modal
**File**: `Client2/vue-project/src/components/manager/RestaurantTableFormModal.vue`

**Features**:
- Form for creating/editing tables
- Fields: table_number, table_name, capacity, location, status, is_active
- Validation
- Success/error messages
- Loading states

---

#### 3.3 Pinia Store
**File**: `Client2/vue-project/src/stores/restaurantTableStore.ts`

**State**:
- tables: RestaurantTable[]
- currentTable: RestaurantTable | null
- statistics: TableStatistics | null
- filters: TableFilters
- loading: boolean
- error: string | null

**Actions**:
- fetchTables()
- fetchTableById(id)
- createTable(data)
- updateTable(id, data)
- deleteTable(id)
- regenerateQR(id)
- fetchStatistics()
- setFilters(filters)

---

### Phase C.4: Router Updates (5 min)
**File**: `Client2/vue-project/src/router/index.ts`

**Add Routes**:
```typescript
// Public route
{
  path: '/restaurant-order/:token',
  name: 'RestaurantOrder',
  component: () => import('@/views/guest/QRMenu.vue')
}

// Manager route (inside manager group)
{
  path: 'restaurant-tables',
  name: 'RestaurantTables',
  component: () => import('@/views/manager/RestaurantTables.vue'),
  meta: { requiresAuth: true, role: 'manager' }
}
```

---

## Implementation Priority

### High Priority (Must Have)
1. ✅ Core services (qrService, unifiedOrderService) - DONE
2. ⏳ Update QRMenu component for context awareness - NEXT
3. ⏳ Manager Tables List view - NEXT
4. ⏳ Router updates - NEXT

### Medium Priority (Should Have)
5. ⏳ Table Form Modal
6. ⏳ Pinia Store
7. ⏳ Statistics display

### Low Priority (Nice to Have)
8. ⏳ Dedicated RestaurantQRMenu component (cleaner separation)
9. ⏳ QR code preview modal
10. ⏳ Table status real-time updates

---

## Quick Next Steps

### To Complete Phase C Quickly:

**Step 1**: Update QRMenu.vue (30 min)
- Add context detection
- Show room/table info appropriately
- Use unified order service

**Step 2**: Create basic RestaurantTables.vue (45 min)
- Simple table list
- Create/Edit/Delete buttons
- Use restaurantTableService directly (skip store for now)

**Step 3**: Add routes (5 min)
- Add restaurant-order route
- Add manager/restaurant-tables route

**Step 4**: Test (20 min)
- Test room QR ordering
- Test table QR ordering
- Test manager CRUD

**Total Time**: ~2 hours for MVP

---

## Files Ready to Use

All these files are created and ready:
- ✅ `services/qrService.ts`
- ✅ `services/unifiedOrderService.ts`
- ✅ `types/restaurantTable.ts`
- ✅ `services/manager/restaurantTableService.ts`

**Next File to Create**:
Update `views/guest/QRMenu.vue` with context detection

---

## Testing Strategy

### Unit Tests (Optional)
- qrService.resolveQRToken()
- unifiedOrderService.createOrder()
- restaurantTableService methods

### Integration Tests (Required)
1. Scan room QR → see room UI → place order → success
2. Scan table QR → see table UI → place order → success
3. Manager → create table → see in list
4. Manager → edit table → changes saved
5. Manager → delete table → removed from list
6. Manager → regenerate QR → new QR code

---

## Known Issues / Considerations

### 1. Payment Options
- Room orders: Only "room_charge" available
- Table orders: "cash" and "card" available
- Need to adapt payment UI based on context

### 2. Guest Information
- Room orders: Have guest info from reservation
- Table orders: No guest info (anonymous)
- UI should not ask for guest info in table context

### 3. Order Confirmation
- Room orders: "Order will be delivered to Room X"
- Table orders: "Order for Table X"
- Different confirmation messages

### 4. Error Handling
- Invalid QR token → show clear error
- QR token for inactive table → show error
- Network errors → retry mechanism

---

## Success Metrics

Phase C is successful when:
- [ ] QR tokens resolve correctly (room vs table)
- [ ] Orders can be placed from both contexts
- [ ] UI adapts based on context
- [ ] Manager can view all tables
- [ ] Manager can create new tables
- [ ] Manager can edit tables
- [ ] Manager can view QR codes
- [ ] No regressions in existing room ordering

---

## Next Phase Preview

**Phase D**: Kitchen & Waiter Integration
- Show order type in kitchen view
- Waiter assignment for table orders
- Table status management

**Estimated Time**: 1-2 hours

---

**Status**: Ready to continue with Phase C.2 (Update QRMenu Component)

**Would you like me to**:
1. Continue creating the remaining components?
2. Create just the QRMenu updates?
3. Create just the Manager Tables view?
4. Create all remaining files?

Let me know how you'd like to proceed!
