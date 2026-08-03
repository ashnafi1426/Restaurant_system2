# Manager Dashboard - Quick Start Guide

## 🚀 Quick Testing (5 minutes)

### 1. Start Servers
```bash
# Terminal 1: Backend
cd server
php artisan serve

# Terminal 2: Frontend  
cd Client2/vue-project
npm run dev
```

### 2. Login & Navigate
- Go to `http://localhost:5173`
- Login with manager account
- Go to Dashboard (should be default page)

### 3. Check Console (F12)
- Open DevTools
- Go to Console tab
- You should see this sequence:

```
========== [ManagerDashboard.vue] MOUNT START ==========
>>> PART 1: Authentication Check
[ManagerDashboard] ✅ PASS: Authentication verified

>>> PART 2: Store Initial State
[ManagerDashboard] Store dashboardStats BEFORE fetch: { ... all zeros ... }

>>> PART 3: Initialize Dashboard
[ManagerDashboard] ✅ Initialization complete in XXms

>>> PART 4: Store State After Fetch
[ManagerDashboard] Store dashboardStats AFTER fetch: { ... with real data ... }

========== [ManagerDashboard.vue] MOUNT COMPLETE ==========
```

### 4. Verify Dashboard Stats
Check these values are **NOT hardcoded**:
- ✅ Total Reservations - Real number from database
- ✅ Today's Check-ins - Real count from database
- ✅ Available Rooms - Real count from database
- ✅ Occupied Rooms - Real count from database
- ✅ Today's Revenue - Real amount from orders
- ✅ Recent Activity - From backend activities

---

## 📊 Data Sources

| Stat | Source | Database |
|------|--------|----------|
| Total Reservations | `ReceptionStats` | `reservations` table |
| Today's Check-ins | `OccupancyStats` | `check_ins` table |
| Today's Check-outs | `OccupancyStats` | `check_ins` table |
| Available Rooms | `OccupancyStats` | `rooms` table |
| Occupied Rooms | `OccupancyStats` | `check_ins` table |
| Today's Revenue | `RevenueStats` | `orders` table |
| Active Waiters | `WaiterStats` | `waiters` table |
| Kitchen Ready | `KitchenStats` | `orders` table |

---

## 🔍 Debugging

### Issue: Stats show as 0
**Solution:** Check backend database
```bash
php artisan tinker
>>> \App\Models\Room::count()  # Should be > 0
>>> \App\Models\Order::count()  # Should be > 0
>>> \App\Models\Waiter::count()  # Should be > 0
```

### Issue: API Error 401 (Unauthorized)
**Solution:** Check authentication
```bash
# Check localStorage in DevTools
# Application → LocalStorage → token should exist
# Log out and log back in if needed
```

### Issue: API Error 404 (Not Found)
**Solution:** Restart Laravel
```bash
# Kill php artisan serve
# Restart: php artisan serve
```

### Issue: API Error 500 (Server Error)
**Solution:** Check Laravel logs
```bash
tail -f storage/logs/laravel.log
# Check for any error messages
```

---

## 📝 Console Log Map

When everything is working, you'll see:

```
Component Mount
  ↓
Authentication Check ✅
  ↓
Store State Before ✅ (all zeros)
  ↓
Initialize Dashboard
  ├─ Load Statistics
  │  ├─ Service API Call ✅
  │  ├─ Response 200 OK ✅
  │  └─ Map Data ✅
  │
  └─ Load Activities
     ├─ Service API Call ✅
     ├─ Response 200 OK ✅
     └─ Extract Data ✅
  ↓
Store State After ✅ (with real data!)
  ↓
Component Renders with Real Data ✅
```

---

## ✅ Checklist

When you see all these in console:
- [x] `MOUNT START`
- [x] Authentication Check ✅ PASS
- [x] Store Initial State Before fetch
- [x] Initialize Dashboard
- [x] `[API INTERCEPTOR] Response received: 200`
- [x] Stats extracted and mapped
- [x] Activities loaded
- [x] Store State After fetch (with numbers!)
- [x] `MOUNT COMPLETE`

**Then dashboard is working! ✅**

---

## 🎯 What Changed

**Before (❌ Mock):**
```typescript
const stats = ref({
  total_reservations: 124,    // Hardcoded!
  rooms_occupied: 176,         // Hardcoded!
  active_waiters: 42,          // Hardcoded!
})
```

**After (✅ Real):**
```typescript
const stats = computed(() => ({
  total_reservations: manager.dashboardStats.totalReservations,  // From API!
  rooms_occupied: manager.dashboardStats.occupiedRooms,           // From API!
  active_waiters: manager.dashboardStats.activeStaff,             // From API!
}))
```

---

## 📞 Support

If dashboard doesn't fetch from backend:

1. **Check Console First**
   - Look for error messages
   - Check log sequence matches expected pattern
   - Note any 401, 404, or 500 errors

2. **Check Backend**
   - `php artisan serve` is running
   - Database has records
   - No errors in `storage/logs/laravel.log`

3. **Check Frontend**
   - `npm run dev` is running
   - No errors in console
   - Token exists in localStorage

4. **Test API Directly**
   ```bash
   curl http://127.0.0.1:8000/api/manager/dashboard/statistics \
     -H "Authorization: Bearer YOUR_TOKEN" \
     -H "Content-Type: application/json"
   ```

---

**Dashboard Integration: ✅ Complete and Ready for Testing**
