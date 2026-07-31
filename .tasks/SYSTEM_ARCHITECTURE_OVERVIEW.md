# System Architecture Overview - Waiter Management System

## Complete System Flow Diagram

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                      WAITER MANAGEMENT SYSTEM ARCHITECTURE                   │
└─────────────────────────────────────────────────────────────────────────────┘

┌────────────────────────────────────────────────────────────────────────────┐
│                         FRONTEND (Vue 3 + TypeScript)                       │
├────────────────────────────────────────────────────────────────────────────┤
│                                                                             │
│  ┌──────────────────────────────────────────────────────────────────────┐ │
│  │  ROUTER CONFIGURATION                                                │ │
│  ├──────────────────────────────────────────────────────────────────────┤ │
│  │  src/router/index.ts                                                │ │
│  │  ├─ Import waiterRoutes                                             │ │
│  │  ├─ Import managerRoutes                                            │ │
│  │  └─ Spread both in routes array                                    │ │
│  │                                                                     │ │
│  │  src/router/waiterRouter.ts                                         │ │
│  │  ├─ /waiter → WaiterDashboard.vue                                  │ │
│  │  ├─ /waiter/assigned-orders → AssignedOrders.vue                   │ │
│  │  ├─ /waiter/ready-pickup → ReadyPickup.vue                         │ │
│  │  ├─ /waiter/on-delivery → OnDelivery.vue                           │ │
│  │  ├─ /waiter/completed-orders → CompletedOrders.vue                 │ │
│  │  ├─ /waiter/delivery-history → DeliveryHistory.vue                 │ │
│  │  ├─ /waiter/performance → Performance.vue                          │ │
│  │  ├─ /waiter/profile → WaiterProfile.vue                            │ │
│  │  ├─ /waiter/settings → WaiterSettings.vue                          │ │
│  │  └─ /waiter/notifications → Notifications.vue                      │ │
│  └──────────────────────────────────────────────────────────────────────┘ │
│                                                                             │
│  ┌──────────────────────────────────────────────────────────────────────┐ │
│  │  LOGIN PAGE (LoginView.vue)                                           │ │
│  ├──────────────────────────────────────────────────────────────────────┤ │
│  │  User enters credentials → submitLogin()                            │ │
│  │  ↓                                                                   │ │
│  │  Backend validates → returns user + token                           │ │
│  │  ↓                                                                   │ │
│  │  Check role in roleRoutes object:                                   │ │
│  │  - admin: /admin                                                    │ │
│  │  - receptionist: /receptionist                                      │ │
│  │  - manager: /manager                                                │ │
│  │  - waiter: /waiter  NEW                                           │ │
│  │  ↓                                                                   │ │
│  │  Router.push to role-specific dashboard                             │ │
│  └──────────────────────────────────────────────────────────────────────┘ │
│                                                                             │
│  ┌──────────────────────────────────────────────────────────────────────┐ │
│  │  WAITER PAGES (All with DashboardLayout + Backend Integration)       │ │
│  ├──────────────────────────────────────────────────────────────────────┤ │
│  │                                                                     │ │
│  │  Each Page Structure:                                              │ │
│  │  ┌─────────────────────────────────────────────────────────────┐  │ │
│  │  │ <template>                                                 │  │ │
│  │  │   <DashboardLayout>                   ← Sidebar + Navbar   │  │ │
│  │  │     <div class="gradient bg">        ← Consistent styling  │  │ │
│  │  │       <div v-if="loading">           ← Loading spinner     │  │ │
│  │  │       <div v-else-if="error">        ← Error alert         │  │ │
│  │  │       <div v-else-if="!data">        ← Empty state         │  │ │
│  │  │       <div v-else>                   ← Content display     │  │ │
│  │  │   </DashboardLayout>                                       │  │ │
│  │  │ </template>                                               │  │ │
│  │  │                                                             │  │ │
│  │  │ <script setup>                                             │  │ │
│  │  │   waiterService.getXXX()             ← API call           │  │ │
│  │  │   .then(data => display)                                  │  │ │
│  │  │   .catch(err => showError)                                │  │ │
│  │  │ </script>                                                 │  │ │
│  │  └─────────────────────────────────────────────────────────────┘  │ │
│  │                                                                     │ │
│  │  Pages (10 Total):                                                 │ │
│  │  1. WaiterDashboard - Stats & recent assignments                   │ │
│  │  2. AssignedOrders - Pending orders to accept                      │ │
│  │  3. ReadyPickup - Ready-to-pickup from kitchen                     │ │
│  │  4. OnDelivery - Active deliveries in progress                     │ │
│  │  5. CompletedOrders - Completed delivery history                   │ │
│  │  6. DeliveryHistory - Detailed history with date filter            │ │
│  │  7. Performance - Performance metrics & stats                       │ │
│  │  8. WaiterProfile - Profile information display                    │ │
│  │  9. WaiterSettings - Settings & preferences                        │ │
│  │  10. Notifications - Notifications list                            │ │
│  └──────────────────────────────────────────────────────────────────────┘ │
│                                                                             │
│  ┌──────────────────────────────────────────────────────────────────────┐ │
│  │  MANAGER FLOOR ASSIGNMENT MODULE                                     │ │
│  ├──────────────────────────────────────────────────────────────────────┤ │
│  │                                                                     │ │
│  │  FloorAssignment.vue                                               │ │
│  │  ├─ Loads all floors                                              │ │
│  │  ├─ Displays floor cards (3-column grid)                         │ │
│  │  ├─ Shows current assignments per floor                          │ │
│  │  ├─ "Add Staff" button per floor                                 │ │
│  │  │  └─ Opens AddStaffToFloorModal                                │ │
│  │  │     ├─ Load Waiters from API                                  │ │
│  │  │     ├─ Load Shifts from API                                   │ │
│  │  │     ├─ Select Waiter (dropdown)                               │ │
│  │  │     ├─ Select Shift (dropdown)                                │ │
│  │  │     ├─ Select Priority (radio: primary/secondary/backup)      │ │
│  │  │     └─ "Assign Staff" button                                  │ │
│  │  │        └─ POST to /api/manager/floors/assignments             │ │
│  │  │           └─ Backend saves to DB                              │ │
│  │  └─ "Save Assignments" button                                    │ │
│  │  └─ Success/error messages                                       │ │
│  └──────────────────────────────────────────────────────────────────────┘ │
│                                                                             │
│  ┌──────────────────────────────────────────────────────────────────────┐ │
│  │  SERVICES LAYER (waiterService.ts)                                  │ │
│  ├──────────────────────────────────────────────────────────────────────┤ │
│  │                                                                     │ │
│  │  Dashboard Methods:                                                │ │
│  │  • getDashboard() → GET /api/waiter/dashboard                    │ │
│  │  • getTodayStats() → GET /api/waiter/dashboard/today             │ │
│  │  • getPerformance() → GET /api/waiter/dashboard/performance      │ │
│  │                                                                     │ │
│  │  Assignment Methods:                                               │ │
│  │  • getRecentAssignments(limit)                                    │ │
│  │  • getReadyForPickup()                                            │ │
│  │  • getOnDelivery()                                                │ │
│  │  • getCompletedDeliveries(limit)                                  │ │
│  │                                                                     │ │
│  │  Action Methods:                                                   │ │
│  │  • acceptAssignment(id)                                           │ │
│  │  • pickupOrder(id)                                                │ │
│  │  • startDelivery(id)                                              │ │
│  │  • deliverOrder(id, remarks)                                      │ │
│  │                                                                     │ │
│  │  History & Profile:                                                │ │
│  │  • getHistory(params)                                             │ │
│  │  • getProfile()                                                   │ │
│  │  • getSettings()                                                  │ │
│  │  • updateSettings(data)                                           │ │
│  │  • changePassword(data)                                           │ │
│  └──────────────────────────────────────────────────────────────────────┘ │
│                                                                             │
└────────────────────────────────────────────────────────────────────────────┘
                                 ↓
                            API LAYER
                                 ↓
┌────────────────────────────────────────────────────────────────────────────┐
│                      BACKEND (Laravel + MySQL)                              │
├────────────────────────────────────────────────────────────────────────────┤
│                                                                             │
│  ┌──────────────────────────────────────────────────────────────────────┐ │
│  │  CONTROLLERS                                                          │ │
│  ├──────────────────────────────────────────────────────────────────────┤ │
│  │                                                                     │ │
│  │  Waiter Controllers:                                                │ │
│  │  • WaiterDashboardController                                       │ │
│  │    - GET /waiter/dashboard → Return dashboard stats                │ │
│  │    - GET /waiter/dashboard/today → Today's stats                   │ │
│  │    - GET /waiter/dashboard/performance → Performance metrics       │ │
│  │                                                                     │ │
│  │  • WaiterAssignmentController                                      │ │
│  │    - GET /waiter/assignments → Get all assignments                 │ │
│  │    - GET /waiter/assignments/pending → Get pending                 │ │
│  │    - GET /waiter/assignments/active → Get active                   │ │
│  │    - PATCH /waiter/assignments/{id}/accept → Accept order          │ │
│  │    - PATCH /waiter/assignments/{id}/pickup → Pickup order          │ │
│  │    - PATCH /waiter/assignments/{id}/start-delivery → Start         │ │
│  │    - PATCH /waiter/assignments/{id}/deliver → Complete delivery    │ │
│  │                                                                     │ │
│  │  • WaiterHistoryController                                         │ │
│  │    - GET /waiter/history → Get delivery history                    │ │
│  │    - GET /waiter/history/export → Export to file                   │ │
│  │                                                                     │ │
│  │  • WaiterProfileController                                         │ │
│  │    - GET /waiter/profile → Get profile                             │ │
│  │    - PUT /waiter/profile → Update profile                          │ │
│  │    - GET /waiter/settings → Get settings                           │ │
│  │    - PUT /waiter/settings → Update settings                        │ │
│  │    - POST /waiter/profile/change-password → Change password        │ │
│  │                                                                     │ │
│  │  Manager Controllers:                                               │ │
│  │  • FloorAssignmentController                                       │ │
│  │    - POST /manager/floors/assignments → Create assignments         │ │
│  │    - GET /manager/floors/assignments/today → Today's assignments   │ │
│  │    - GET /manager/floors/assignments → Get all (with filters)      │ │
│  │    - PATCH /manager/floors/assignments/{id} → Update priority      │ │
│  │    - DELETE /manager/floors/assignments/{id} → Delete assignment   │ │
│  │    - GET /manager/floors/assignments/stats → Get statistics        │ │
│  │                                                                     │ │
│  └──────────────────────────────────────────────────────────────────────┘ │
│                                                                             │
│  ┌──────────────────────────────────────────────────────────────────────┐ │
│  │  SERVICES LAYER                                                      │ │
│  ├──────────────────────────────────────────────────────────────────────┤ │
│  │  • WaiterDashboardService - Dashboard data aggregation              │ │
│  │  • WaiterAssignmentService - Assignment business logic              │ │
│  │  • WaiterPerformanceService - Performance calculations              │ │
│  │  • WaiterManagementService - Waiter management logic                │ │
│  └──────────────────────────────────────────────────────────────────────┘ │
│                                                                             │
│  ┌──────────────────────────────────────────────────────────────────────┐ │
│  │  MODELS (Eloquent ORM)                                               │ │
│  ├──────────────────────────────────────────────────────────────────────┤ │
│  │  • User - System users with roles                                   │ │
│  │  • Waiter - Waiter profile & status                                 │ │
│  │  • HotelFloor - Hotel floors/sections                               │ │
│  │  • HotelShift - Work shifts                                         │ │
│  │  • WaiterFloorAssignment - Assignment records                       │ │
│  │  • WaiterAssignment - Order assignments                             │ │
│  │  • DeliveryTask - Delivery tracking                                 │ │
│  │  • DeliveryLog - Delivery history                                   │ │
│  │  • WaiterPerformance - Performance metrics                          │ │
│  │  • Order - Orders in system                                         │ │
│  │  • Room - Hotel rooms                                               │ │
│  └──────────────────────────────────────────────────────────────────────┘ │
│                                                                             │
└────────────────────────────────────────────────────────────────────────────┘
                                 ↓
┌────────────────────────────────────────────────────────────────────────────┐
│                         DATABASE (MySQL)                                    │
├────────────────────────────────────────────────────────────────────────────┤
│                                                                             │
│  Core Tables:                                                              │
│  ├─ users                          - System users (with roles)              │
│  ├─ waiters                         - Waiter records                        │
│  ├─ hotel_floors                    - Floor/section definitions             │
│  ├─ hotel_shifts                    - Shift definitions                     │
│  │                                                                         │
│  Assignment Tables:                                                        │
│  ├─ waiter_floor_assignments     - Floor assignment records              │
│  │  ├─ id (UUID)                                                           │
│  │  ├─ waiter_id (FK)                                                      │
│  │  ├─ floor_id (FK)                                                       │
│  │  ├─ shift_id (FK)                                                       │
│  │  ├─ assignment_date                                                     │
│  │  ├─ status (assigned/active/completed/cancelled)                        │
│  │  ├─ priority (primary/secondary/backup)                                 │
│  │  ├─ assigned_by (FK - manager)                                          │
│  │  ├─ UNIQUE(floor_id, shift_id, assignment_date, priority)               │
│  │  └─ Indexes on: waiter_id, floor_id, shift_id, status, priority        │
│  │                                                                         │
│  │ ├─ waiter_assignments             - Order assignments                   │
│  │ └─ delivery_tasks                 - Delivery tracking                   │
│  │                                                                         │
│  History Tables:                                                           │
│  ├─ delivery_logs                   - Delivery history                     │
│  ├─ waiter_performance               - Performance metrics                  │
│  └─ waiter_notifications             - Notifications                       │
│                                                                             │
│  Order Tables:                                                             │
│  ├─ orders                          - Customer orders                       │
│  ├─ order_items                     - Order line items                      │
│  └─ room_service_deliveries         - Room service orders                  │
│                                                                             │
│  Reference Tables:                                                         │
│  ├─ rooms                           - Hotel rooms                           │
│  └─ [Other tables...]               - Menu, categories, etc.                │
│                                                                             │
└────────────────────────────────────────────────────────────────────────────┘

```

---

## Data Flow Examples

### Example 1: Waiter Login & Dashboard Load
```
User Types Credentials
    ↓
POST /api/login
    ↓
Backend validates user, creates token
    ↓
Frontend: Store token + user in localStorage
    ↓
Check user.role = 'waiter'
    ↓
Router.push('/waiter')
    ↓
WaiterDashboard.vue mounts
    ↓
onMounted() calls:
  • waiterService.getDashboard()
  • waiterService.getRecentAssignments(5)
    ↓
Backend Controllers respond:
  • WaiterDashboardController.show()
  • WaiterAssignmentController.recent()
    ↓
Services aggregate data:
  • Query WaiterPerformance table
  • Query WaiterAssignment table
  • Calculate stats
    ↓
Response sent to frontend
    ↓
Frontend displays:
  • Loading spinner → disappears
  • Dashboard stats cards
  • Recent assignments list
```

### Example 2: Manager Assigns Waiter to Floor
```
Manager Opens /manager/floor-assignment
    ↓
Page loads:
  • GET /api/manager/floors (all floors)
  • GET /api/manager/floors/assignments/today (today's assignments)
    ↓
Page displays 3-column grid with floor cards
    ↓
Manager clicks "Add Staff" on Floor 1
    ↓
AddStaffToFloorModal opens
    ↓
Modal loads:
  • GET /manager/waiters (all waiters)
  • GET /manager/shifts (all shifts)
    ↓
Manager selects:
  • Waiter: John (ID: 1)
  • Shift: Morning
  • Priority: Primary
    ↓
Manager clicks "Assign Staff"
    ↓
Modal sends:
  POST /api/manager/floors/assignments
  {
    assignments: [{
      waiter_id: 1,
      floor_id: "uuid",
      shift_id: "uuid",
      assignment_date: "2026-07-27",
      priority: "primary"
    }]
  }
    ↓
Backend processes:
  • FloorAssignmentController@store
  • Validates request
  • Checks if assignment exists
  • If new: Create with UUID
  • If exists: Update priority
  • Begin transaction
  • Save to waiter_floor_assignments
  • Commit transaction
    ↓
Response: 201 Created
{
  success: true,
  data: [created_assignment]
}
    ↓
Frontend Modal:
  • Closes modal
  • Shows success message
  • Floor card updates with new waiter
    ↓
Data persists in database forever
```

### Example 3: Waiter Accepts & Completes Delivery
```
Waiter Views AssignedOrders page
    ↓
GET /api/waiter/assignments/pending/list
    ↓
Backend returns list of pending assignments
    ↓
Frontend displays orders
    ↓
Waiter clicks "Accept Order"
    ↓
PATCH /api/waiter/assignments/{id}/accept
    ↓
Backend:
  • Updates assignment status
  • Increments waiter's current_orders
  • Logs action
    ↓
Frontend refreshes order list
    ↓
Order now shows in ReadyPickup page
    ↓
Waiter clicks "Pickup Order"
    ↓
PATCH /api/waiter/assignments/{id}/pickup
    ↓
Order moves to OnDelivery page
    ↓
Waiter delivers to room
    ↓
Waiter clicks "Mark Delivered"
    ↓
PATCH /api/waiter/assignments/{id}/deliver
    ↓
Backend:
  • Updates status to 'delivered'
  • Decrements current_orders
  • Creates delivery log entry
  • Updates performance metrics
  • Logs action
    ↓
Order now in CompletedOrders
    ↓
Appears in DeliveryHistory
    ↓
Performance stats updated
```

---

## Technology Stack

### Frontend
- **Framework:** Vue 3
- **Language:** TypeScript
- **Styling:** Tailwind CSS
- **State Management:** Pinia (if used)
- **HTTP Client:** Axios/Fetch API
- **UI Icons:** Lucide Vue Next
- **Router:** Vue Router

### Backend
- **Framework:** Laravel 10+
- **Language:** PHP 8.1+
- **Database:** MySQL 8.0+
- **Authentication:** Laravel Sanctum
- **ORM:** Eloquent
- **Validation:** Laravel Validation
- **Services:** Service Layer Pattern

### Database
- **DBMS:** MySQL 8.0+
- **Tables:** 30+ (core + extensions)
- **Relationships:** Fully relational
- **Indexes:** Optimized for performance
- **Constraints:** Unique, Foreign Keys

---

## Security Architecture

```
├─ Authentication
│  ├─ Login validation
│  ├─ Token generation (Sanctum)
│  └─ Token verification on each request
│
├─ Authorization
│  ├─ Role-based access (admin, manager, waiter, etc.)
│  ├─ Route guards in frontend
│  └─ Middleware on backend
│
├─ Data Validation
│  ├─ Frontend form validation
│  ├─ Backend request validation
│  └─ Type checking (TypeScript/PHP)
│
├─ Database Security
│  ├─ UUID usage (not predictable IDs)
│  ├─ Foreign key constraints
│  ├─ Transactions for data integrity
│  └─ Index optimization
│
└─ Error Handling
   ├─ Try-catch blocks
   ├─ Error logging
   ├─ User-friendly messages
   └─ Graceful degradation
```

---

## Performance Considerations

### Frontend Optimization
-  Lazy loading routes
-  Component code splitting
-  Minimal re-renders (Vue 3)
-  Image optimization

### Backend Optimization
-  Database indexes on query columns
-  Eager loading (avoid N+1)
-  Query result caching
-  Pagination on list endpoints

### Database Optimization
-  Proper indexing strategy
-  Relationship optimization
-  Query optimization
-  Connection pooling

---

## Deployment Architecture

```
┌─────────────────────────────────────────────────┐
│           PRODUCTION ENVIRONMENT                │
├─────────────────────────────────────────────────┤
│                                                 │
│  ┌─────────────────────────────────────────┐   │
│  │  Frontend (Vue Build)                    │   │
│  │  - Served via nginx/Apache               │   │
│  │  - Static files with cache headers       │   │
│  │  - HTTPS enabled                         │   │
│  └─────────────────────────────────────────┘   │
│           ↓                                     │
│  ┌─────────────────────────────────────────┐   │
│  │  Backend (Laravel)                       │   │
│  │  - PHP-FPM / Apache                      │   │
│  │  - Running as service                    │   │
│  │  - Environment config (.env)             │   │
│  │  - HTTPS enabled                         │   │
│  └─────────────────────────────────────────┘   │
│           ↓                                     │
│  ┌─────────────────────────────────────────┐   │
│  │  MySQL Database                          │   │
│  │  - Secure connection                     │   │
│  │  - Regular backups                       │   │
│  │  - Replication for HA                    │   │
│  └─────────────────────────────────────────┘   │
│                                                 │
└─────────────────────────────────────────────────┘
```

---

## Summary

This architecture provides:
 Clean separation of concerns
 Scalable component structure
 Type-safe development (TypeScript)
 Database persistence
 Secure authentication & authorization
 RESTful API design
 Responsive user interface
 Comprehensive error handling
 Performance optimized
 Production ready

**Total System: 10 Waiter Pages + Manager Floor Assignment + Complete Backend**
