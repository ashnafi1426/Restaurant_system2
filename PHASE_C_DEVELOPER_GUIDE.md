# Phase C: Developer Quick Reference Guide

**For**: Frontend Developers  
**Last Updated**: August 9, 2026

---

## 🚀 Quick Start

### Prerequisites
1. Backend Phase A & B completed (database + API)
2. Frontend dependencies installed (`npm install`)
3. Backend server running on `http://127.0.0.1:8000`

### Running the Frontend
```bash
cd Client2/vue-project
npm run dev
```

---

## 📍 Key URLs

### Public Routes (No Auth Required)
- Room Service: `http://localhost:5173/order/:qrToken`
- Restaurant: `http://localhost:5173/restaurant-order/:qrToken`

### Manager Routes (Auth Required)
- Tables Management: `http://localhost:5173/manager/restaurant-tables`

---

## 🔧 Core Services

### 1. QR Service
**Import**:
```typescript
import { qrService } from '@/services/qrService'
```

**Usage**:
```typescript
// Resolve QR token
const result = await qrService.resolveQRToken('ABC12345')
if (result.success && result.context === 'room') {
  console.log('Room order:', result.data.room_number)
} else if (result.success && result.context === 'table') {
  console.log('Table order:', result.data.table_number)
}

// Validate QR token (lightweight)
const validation = await qrService.validateQRToken('ABC12345')
if (validation.valid) {
  console.log('QR code is valid')
}
```

---

### 2. Unified Order Service
**Import**:
```typescript
import { unifiedOrderService } from '@/services/unifiedOrderService'
```

**Usage**:
```typescript
// Create order (works for both room and table)
const order = await unifiedOrderService.createOrder({
  qr_token: 'ABC12345',
  items: [
    { menu_item_id: 'uuid-1', quantity: 2 },
    { menu_item_id: 'uuid-2', quantity: 1 }
  ],
  special_requests: 'No onions',
  payment_type: 'room_charge' // or 'cash' or 'card'
})

console.log('Order created:', order.data.order_number)
console.log('Order type:', order.data.order_type) // 'room_service' or 'walk_in'
```

---

### 3. Restaurant Table Service (Manager)
**Import**:
```typescript
import { restaurantTableService } from '@/services/manager/restaurantTableService'
```

**Usage**:
```typescript
// Get all tables
const tables = await restaurantTableService.getTables({
  search: 'T1',
  status: 'available',
  per_page: 10,
  page: 1
})

// Get single table
const table = await restaurantTableService.getTableById('uuid')

// Create table
const newTable = await restaurantTableService.createTable({
  table_number: 'T5',
  table_name: 'Window Table',
  capacity: 4,
  location: 'Main Dining',
  status: 'available',
  is_active: true
})

// Update table
const updated = await restaurantTableService.updateTable('uuid', {
  status: 'occupied'
})

// Delete table
await restaurantTableService.deleteTable('uuid')

// Regenerate QR
const tableWithNewQR = await restaurantTableService.regenerateQR('uuid')

// Get statistics
const stats = await restaurantTableService.getStatistics()
console.log('Total tables:', stats.total)
console.log('Available:', stats.available)
```

---

## 🗄️ Store Usage (Pinia)

### Restaurant Table Store
**Import**:
```typescript
import { useRestaurantTableStore } from '@/stores/restaurantTableStore'
import { storeToRefs } from 'pinia'
```

**Usage in Component**:
```vue
<script setup>
import { useRestaurantTableStore } from '@/stores/restaurantTableStore'
import { storeToRefs } from 'pinia'
import { onMounted } from 'vue'

const tableStore = useRestaurantTableStore()
const { tables, statistics, loading, error } = storeToRefs(tableStore)

onMounted(() => {
  tableStore.fetchTables()
  tableStore.fetchStatistics()
})

const handleCreate = async () => {
  await tableStore.createTable({
    table_number: 'T10',
    capacity: 4
  })
}
</script>

<template>
  <div v-if="loading">Loading...</div>
  <div v-else-if="error">Error: {{ error }}</div>
  <div v-else>
    <div v-for="table in tables" :key="table.id">
      {{ table.table_number }} - {{ table.status }}
    </div>
  </div>
</template>
```

---

## 📦 TypeScript Types

### Import Types
```typescript
import type {
  RestaurantTable,
  CreateTableRequest,
  UpdateTableRequest,
  TableFilters,
  TableStatistics,
  OrderContext
} from '@/types/restaurantTable'

import type {
  QRResolutionResult,
  QRValidationResult
} from '@/services/qrService'

import type {
  OrderItem,
  CreateOrderRequest,
  OrderResponse
} from '@/services/unifiedOrderService'
```

### Example Types
```typescript
// RestaurantTable
interface RestaurantTable {
  id: string
  table_number: string
  table_name: string | null
  capacity: number
  location: string | null
  status: 'available' | 'occupied' | 'reserved' | 'maintenance'
  is_active: boolean
  qr_token: string
  qr_code_url: string | null
  created_at: string
  updated_at: string
}

// OrderContext
interface OrderContext {
  type: 'room' | 'table'
  id: string
  displayName: string // "Room 101" or "Table 5"
  paymentOptions: Array<{ value: string; label: string }>
}
```

---

## 🎨 Component Examples

### Using QRMenu Component
```vue
<template>
  <QRMenu />
</template>

<script setup>
import QRMenu from '@/views/guest/QRMenu.vue'
// Component will automatically:
// 1. Detect QR token from route params
// 2. Resolve context (room or table)
// 3. Adapt UI based on context
// 4. Handle order creation
</script>
```

### Using RestaurantTableFormModal
```vue
<template>
  <button @click="showModal = true">Create Table</button>
  
  <RestaurantTableFormModal
    v-if="showModal"
    :table="selectedTable" <!-- null for create, table object for edit -->
    @close="showModal = false"
    @success="handleSuccess"
  />
</template>

<script setup>
import { ref } from 'vue'
import RestaurantTableFormModal from '@/components/manager/RestaurantTableFormModal.vue'

const showModal = ref(false)
const selectedTable = ref(null)

const handleSuccess = () => {
  showModal.value = false
  // Refresh table list
}
</script>
```

---

## 🔀 Routing

### Navigate to Routes
```typescript
import { useRouter } from 'vue-router'

const router = useRouter()

// Navigate to restaurant order
router.push({ name: 'restaurant-qr-order', params: { qrToken: 'ABC12345' } })

// Navigate to manager tables
router.push({ name: 'RestaurantTables' })

// Navigate with query params
router.push({
  name: 'RestaurantTables',
  query: { search: 'T1', status: 'available' }
})
```

### Get Route Params
```typescript
import { useRoute } from 'vue-router'

const route = useRoute()

// Get QR token from URL
const qrToken = route.params.qrToken
```

---

## 🐛 Debugging

### Enable Detailed Logging
```typescript
// In QRMenu.vue or any component
console.log('🔍 [DEBUG] QR Token:', qrToken.value)
console.log('📡 [DEBUG] Context:', orderContext.value)
console.log('📦 [DEBUG] Cart Items:', cartItems.value)
```

### Common Issues

**Issue**: QR code not resolving
```typescript
// Check backend is running
// Check QR token format (8 characters)
// Check console for API errors
const result = await qrService.resolveQRToken(token)
console.log('Resolution result:', result)
```

**Issue**: Order creation fails
```typescript
// Check items have valid menu_item_id
// Check payment_type is valid
// Check QR token is valid
try {
  const order = await unifiedOrderService.createOrder(data)
} catch (error) {
  console.error('Order error:', error)
  console.error('Error details:', error.errors)
}
```

**Issue**: Table not appearing in manager view
```typescript
// Check filters
tableStore.resetFilters()
await tableStore.fetchTables()

// Check backend data
const stats = await tableStore.fetchStatistics()
console.log('Total tables in DB:', stats.total)
```

---

## 🔌 API Endpoints Reference

### QR Resolution
```
GET /api/qr/resolve/{token}
POST /api/qr/validate
```

### Orders
```
POST /api/orders
```

### Restaurant Tables (Manager)
```
GET    /api/manager/restaurant-tables
GET    /api/manager/restaurant-tables/{id}
POST   /api/manager/restaurant-tables
PUT    /api/manager/restaurant-tables/{id}
DELETE /api/manager/restaurant-tables/{id}
POST   /api/manager/restaurant-tables/{id}/regenerate-qr
GET    /api/manager/restaurant-tables/statistics
```

---

## 🎯 Best Practices

### 1. Always Use Services
❌ **Bad**:
```typescript
const response = await axios.get('/api/qr/resolve/' + token)
```

✅ **Good**:
```typescript
const result = await qrService.resolveQRToken(token)
```

### 2. Use TypeScript Types
❌ **Bad**:
```typescript
const table = ref({})
```

✅ **Good**:
```typescript
const table = ref<RestaurantTable | null>(null)
```

### 3. Handle Errors Gracefully
❌ **Bad**:
```typescript
const order = await unifiedOrderService.createOrder(data)
```

✅ **Good**:
```typescript
try {
  const order = await unifiedOrderService.createOrder(data)
  showSuccess('Order created successfully!')
} catch (error) {
  showError(error.message || 'Failed to create order')
  console.error('Order creation error:', error)
}
```

### 4. Use Pinia Store for Shared State
❌ **Bad**:
```typescript
// Fetching data in every component
const tables = await restaurantTableService.getTables()
```

✅ **Good**:
```typescript
// Using store
const tableStore = useRestaurantTableStore()
const { tables } = storeToRefs(tableStore)
await tableStore.fetchTables()
```

### 5. Clean Up Resources
```typescript
import { onUnmounted } from 'vue'

let timeout: ReturnType<typeof setTimeout> | null = null

const search = () => {
  if (timeout) clearTimeout(timeout)
  timeout = setTimeout(() => {
    // Search logic
  }, 500)
}

onUnmounted(() => {
  if (timeout) clearTimeout(timeout)
})
```

---

## 📚 Additional Resources

### Documentation Files
- `PHASE_C_COMPLETE_SUMMARY.md` - Complete implementation summary
- `PHASE_C_PROGRESS_SUMMARY.md` - Progress tracking
- `PHASE_C_FRONTEND_PLAN.md` - Original implementation plan
- `COMPLETE_IMPLEMENTATION_SUMMARY.md` - Overall project status

### Code Locations
- Services: `Client2/vue-project/src/services/`
- Components: `Client2/vue-project/src/components/`
- Views: `Client2/vue-project/src/views/`
- Stores: `Client2/vue-project/src/stores/`
- Types: `Client2/vue-project/src/types/`
- Router: `Client2/vue-project/src/router/`

---

## 🎓 Learning Path

### For New Developers
1. Read `PHASE_C_COMPLETE_SUMMARY.md`
2. Study `qrService.ts` and `unifiedOrderService.ts`
3. Review `QRMenu.vue` component
4. Explore `RestaurantTables.vue` manager view
5. Test both flows (room service + walk-in)

### For Experienced Developers
1. Review TypeScript types in `restaurantTable.ts`
2. Study store implementation in `restaurantTableStore.ts`
3. Review API integration in services
4. Test edge cases and error handling

---

## ✅ Testing Checklist for Developers

Before marking Phase C as complete:
- [ ] QR token resolution works for both contexts
- [ ] Room service orders work end-to-end
- [ ] Walk-in orders work end-to-end
- [ ] Manager can CRUD tables
- [ ] QR code view/download/regenerate works
- [ ] Pagination works
- [ ] Search and filters work
- [ ] Error handling works
- [ ] Loading states display correctly
- [ ] TypeScript types are correct
- [ ] No console errors
- [ ] Responsive design works on mobile

---

**Happy Coding! 🚀**

For questions or issues, refer to the complete documentation or contact the backend team.
