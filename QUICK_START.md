# Walk-In Order System - Quick Start Guide

## 🚀 5-Minute Setup

### 1. Verify Setup (30 seconds)

```bash
# Backend running?
curl http://127.0.0.1:8000/api/health

# Frontend running?
# Open: http://localhost:5173

# Database has tables?
php artisan tinker
>>> \App\Models\RestaurantTable::count()
# Should return > 0
```

### 2. Test QR Menu (1 minute)

```
1. Open: http://localhost:5173/qr-menu/table-2-GveD6NRGFa
2. Add any item to cart
3. Click cart icon
4. Verify total shows tax (15%) and service charge (10%)
```

### 3. Test Payment (2 minutes)

```
1. Click "Proceed to Payment"
2. Fill form:
   - First: John
   - Last: Doe  
   - Email: john@test.com
   - Phone: +251912345678
3. Click "Pay Now"
4. Complete payment on Chapa (test mode)
5. Verify success page shows
```

### 4. Check Kitchen (1 minute)

```
1. Login as chef
2. Go to kitchen dashboard
3. Check "Pending" tab
4. Verify walk-in order appears
5. Click "Start Preparing"
6. Click "Mark as Ready"
```

### 5. Waiter Delivery (1 minute)

```
1. Login as manager
2. Go to delivery management
3. Assign waiter to order
4. Login as waiter
5. Accept and complete delivery
```

---

## ✅ Success Indicators

| Check | Expected Result |
|-------|----------------|
| QR Menu loads | Shows "Table 2" in header |
| Cart works | Shows tax (15%) + service (10%) |
| Payment redirects | Goes to Chapa checkout |
| Success page | Shows order number + table |
| Kitchen | Order in Pending queue |
| Waiter | Can accept and deliver |

---

## 🐛 Quick Troubleshooting

### QR Menu Not Loading?
```bash
# Check route
php artisan route:list | grep "qr/resolve"

# Seed tables if missing
php artisan db:seed --class=RestaurantTableSeeder
```

### Payment Fails?
```bash
# Check Chapa keys
grep CHAPA server/.env

# Clear config
php artisan config:clear
```

### Order Not in Kitchen?
```sql
-- Check payment
SELECT * FROM payments WHERE tx_ref LIKE 'WALKIN-%' ORDER BY created_at DESC LIMIT 1;

-- Check order
SELECT * FROM orders WHERE order_type = 'walk_in' ORDER BY created_at DESC LIMIT 1;
```

---

## 📋 Test URLs

```
QR Menu: http://localhost:5173/qr-menu/table-2-GveD6NRGFa
Kitchen: http://localhost:5173/kitchen/dashboard
Waiter: http://localhost:5173/waiter/dashboard
Manager: http://localhost:5173/manager/delivery
```

---

## 🎯 What You Get

✅ Customer scans QR → Pays via Chapa → Order sent to kitchen  
✅ Chef prepares → Marks ready → Waiter delivers  
✅ Complete order tracking from start to finish  

**No configuration needed - works out of the box!**

---

## 📖 Full Documentation

- `WALK_IN_ORDER_COMPLETE_GUIDE.md` - Complete guide
- `TESTING_CHECKLIST.md` - Detailed testing
- `IMPLEMENTATION_STATUS.md` - Implementation details

---

**Status:** ✅ READY TO TEST NOW!
