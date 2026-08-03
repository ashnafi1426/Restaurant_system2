# QR Menu System - Testing & Fixes

## Current Issues

### Issue 1: 401 on /categories endpoint
- **Status**: Under investigation
- **Error**: `GET /api/categories?is_active=true` returns 401 Unauthenticated
- **Expected**: Public endpoint should return categories without authentication
- **Root Cause**: Route middleware configuration or Sanctum middleware not properly excluded

### Issue 2: 404 on guest menu endpoint  
- **Status**: Partially fixed
- **Error**: `GET /api/guest/menu/ZZFHYZZI/items` returns 404 Not Found
- **Root Cause**: Test token "ZZFHYZZI" doesn't exist in `rooms` table
- **Solution**: Use actual QR tokens from database or walk-in tokens from `restaurant_tables`

### Issue 3: 422 on walk-in session initialization
- **Status**: Partially fixed
- **Error**: `POST /api/walk-in/session/initialize` with qr_token "ZZFHYZZI" returns 422
- **Root Cause**: Invalid token - must exist in `restaurant_tables` table
- **Solution**: Use real QR tokens from seeded restaurant tables

## Available Test Tokens

### Walk-in Customer Tokens (restaurant_tables)
Access via: `/api/testing/walk-in-tokens`

Example format: `table-1-XXXXXXXX`, `table-2-XXXXXXXX`, etc.

### Guest Room Tokens (rooms table)
Access via: `/api/testing/guest-tokens`

Access requires guest QR codes from seeded rooms.

## Seeded Data

- ✅ RestaurantTableSeeder: 15 tables with random QR tokens
- ✅ CategorySeeder: 9 menu categories
- ✅ MenuItemSeeder: 20 sample menu items

## Testing Steps

### Step 1: Verify Categories Endpoint
```bash
# This should return 200 without authentication
curl http://127.0.0.1:8000/api/categories?is_active=true
```

### Step 2: Get Available Tokens
```bash
# Get walk-in table tokens
curl http://127.0.0.1:8000/api/testing/walk-in-tokens

# Get guest room tokens  
curl http://127.0.0.1:8000/api/testing/guest-tokens
```

### Step 3: Test Walk-in Flow
```bash
# 1. Get a token from walk-in-tokens endpoint
# 2. Navigate to http://localhost:5173/menu?token=<TOKEN>
# 3. Select "I am visiting the restaurant"
# 4. System should create a session and show menu
```

### Step 4: Test Guest Flow
```bash
# 1. Need to create a guest with active room reservation first
# 2. Navigate to http://localhost:5173/order/<ROOM_QR_TOKEN>
# 3. Select "I am staying in the hotel"
# 4. Verify room (or enter room number)
# 5. System should show menu for that room
```

## Next Steps

1. Investigate why /categories endpoint returns 401
   - Check if Sanctum middleware is being applied despite empty middleware array
   - Consider using explicit `withoutMiddleware('auth:sanctum')`
   - Verify route registration in Kernel or bootstrap

2. Update frontend to use real tokens
   - Remove hardcoded "ZZFHYZZI" test token
   - Implement token discovery endpoint
   - Or implement QR code scan simulation for testing

3. Document token format and flow
   - Walk-in: Uses `restaurant_tables.qr_token`
   - Guest: Uses `rooms.qr_token`
