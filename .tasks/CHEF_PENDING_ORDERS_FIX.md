# Chef Pending Orders Issue - Investigation & Fix

## Date: August 4, 2026

## Issue Reported
Orders are being sent successfully but don't appear in chef's pending orders (shows 0). However, 44 orders show as "READY" status.

## Root Cause Analysis

### Order Creation Flow
There are TWO order creation paths in the system:

#### Path 1: Direct Order Creation (OLD - QR Without Payment)
**File:** `GuestOrderController->createOrder()` (line 298)
- Creates order immediately
- Status: `'pending'` ✅
- Used for: QR orders without payment integration

#### Path 2: Order After Payment (NEW - QR With Payment)
**File:** `PaymentService->handleOrderPaymentSuccess()` (line 244)  
- Creates order ONLY after payment verification
- Status: `Order::STATUS_PENDING` ✅  
- Used for: QR orders with Chapa payment integration

### The Problem
1. **Orders ARE being created with correct 'pending' status**
2. **The 44 "READY" orders are likely old test data**
3. **New orders might not be showing because:**
   - Payment completion flow isn't being called
   - Frontend isn't triggering the `completeOrder` endpoint
   - Orders stuck in payment verification stage

## Investigation Steps

### Check Database
Run this SQL query to check order statuses:
```sql
SELECT 
    status, 
    COUNT(*) as count,
    MAX(created_at) as latest_order
FROM orders 
GROUP BY status 
ORDER BY latest_order DESC;
```

### Check Recent Orders
```sql
SELECT 
    id,
    order_number,
    status,
    source,
    total,
    created_at
FROM orders 
ORDER BY created_at DESC 
LIMIT 20;
```

### Check Payment-Order Link
```sql
SELECT 
    p.id as payment_id,
    p.tx_ref,
    p.status as payment_status,
    p.order_id,
    o.status as order_status,
    o.order_number
FROM payments p
LEFT JOIN orders o ON p.order_id = o.id
ORDER BY p.created_at DESC
LIMIT 10;
```

## Possible Issues

### Issue 1: Payments Not Completing
- Orders created in PaymentService after verification
- If payment verification fails/incomplete, order never created
- **Solution:** Check payment verification logs

### Issue 2: Old Test Data
- 44 orders with "READY" status blocking view
- **Solution:** Clear old test orders:
```sql
-- CAREFUL - Only for development
DELETE FROM order_items WHERE order_id IN (
    SELECT id FROM orders WHERE created_at < '2026-08-01'
);
DELETE FROM orders WHERE created_at < '2026-08-01';
```

### Issue 3: Chef Filter Logic
**File:** `KitchenService->getOrdersByStatus()` (line 24)
```php
if ($authUser && isset($authUser->role) && $authUser->role === 'chef') {
    $query->where(function($q) use ($authUser) {
        $q->where('chef_id', $authUser->id)
          ->orWhereNull('chef_id');
    });
}
```

This filters orders by chef_id. New orders have `chef_id` = NULL, so they SHOULD show up.

**Potential Problem:** If somehow chef_id is being set during order creation, they won't show.

## Quick Fix: Clear Old Test Orders

Create a migration to clean up old/test orders:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // Delete order items first (foreign key constraint)
        DB::table('order_items')
            ->whereIn('order_id', function($query) {
                $query->select('id')
                    ->from('orders')
                    ->where('status', 'ready')
                    ->where('created_at', '<', now()->subDays(7));
            })
            ->delete();
        
        // Delete old ready orders
        DB::table('orders')
            ->where('status', 'ready')
            ->where('created_at', '<', now()->subDays(7))
            ->delete();
    }

    public function down()
    {
        // Cannot restore deleted data
    }
};
```

Run migration:
```bash
cd server
php artisan make:migration clean_old_test_orders
# Paste the code above
php artisan migrate
```

## Debugging Steps for User

### 1. Check Laravel Logs
```bash
cd server
tail -f storage/logs/laravel.log
```

### 2. Create a Test Order
- Go to QR menu
- Add items
- Complete payment
- Watch the logs for:
  - `[QR ORDER] Order created successfully`
  - `Order Created After Payment`

### 3. Check Chef Dashboard
- Refresh chef page
- Check if new order appears in "PENDING"

### 4. Force Refresh
- Clear browser cache (Ctrl+Shift+R)
- Check Network tab for API call to `/api/kitchen/orders`

## Recommended Fix

Since the order creation logic is CORRECT, the issue is likely:

1. **Old test data showing as "READY"** - Clean up database
2. **Payment flow not completing** - Check payment verification
3. **Frontend not refreshing** - Add auto-refresh on chef page

### Immediate Action

Run this in tinker to check current state:
```bash
cd server
php artisan tinker
```

```php
// Check total orders by status
\App\Models\Order::select('status', DB::raw('count(*) as total'))
    ->groupBy('status')
    ->get();

// Check most recent orders
\App\Models\Order::latest()->take(5)->get(['id', 'order_number', 'status', 'created_at']);

// Check pending orders specifically
\App\Models\Order::where('status', 'pending')->count();

// Check if chef_id is set on orders
\App\Models\Order::whereNotNull('chef_id')->count();
```

## Status
🔍 **INVESTIGATION COMPLETE**
⚠️ **AWAITING USER DATABASE CHECK**

---

**Next Steps:**
1. User should check database using SQL queries above
2. Clean old test data
3. Test new order creation
4. Verify orders appear in chef dashboard
