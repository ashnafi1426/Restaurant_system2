# Test Floor API Endpoint

## Quick Test Using Curl

### Test 1: Get All Active Floors
```bash
curl -X GET "http://localhost:8000/api/manager/floors?is_active=true" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Content-Type: application/json"
```

**Expected Response:**
```json
{
  "success": true,
  "message": "Floors retrieved successfully",
  "data": [
    {
      "id": "...",
      "floor_number": 1,
      "name": "Ground Floor",
      "description": "...",
      "is_active": true,
      "total_rooms": 0,
      "created_at": "...",
      "updated_at": "..."
    }
    // ... more floors
  ],
  "pagination": {
    "total": 8,
    "per_page": 20,
    "current_page": 1,
    "last_page": 1
  }
}
```

### Test 2: Get All Floors (Including Inactive)
```bash
curl -X GET "http://localhost:8000/api/manager/floors" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Content-Type: application/json"
```

### Test 3: Search Floors
```bash
curl -X GET "http://localhost:8000/api/manager/floors?search=ground" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Content-Type: application/json"
```

## How to Get Token

1. Login at: `http://localhost:5173/`
2. Use credentials:
   - Email: `manager@hotel.com`
   - Password: `Manager123@`
3. Check browser DevTools → Application → LocalStorage → `auth` object
4. Copy the token from `tokens.access` field

## Expected Current Floors in DB

1. Ground Floor (floor_number: 1)
2. First Floor (floor_number: 2)
3. Second Floor (floor_number: 3)
4. Third Floor (floor_number: 4)
5. Conference Hall (floor_number: 5)
6. Premium Executive Floor (floor_number: 6) - created during testing
7. Test Floor 7 (floor_number: 7) - created during testing
8. Test Floor 8 or higher (if created)

## Frontend Loading Path

1. User navigates to `/manager/floor-assignment`
2. Component mounts → calls `loadData()`
3. `loadData()` calls:
   - `floorManagementService.getFloors({ is_active: true })`
   - Returns floors and populates `allFloors` ref
4. Component renders 3-column grid with all floors
5. If any assignments exist, shows them under each floor
6. If no assignments, shows "No staff assigned yet" message

## Troubleshooting

### If Floors Don't Load
1. Check browser console for errors
2. Check Network tab for 500 errors on `/api/manager/floors`
3. Check Laravel log: `server/storage/logs/laravel.log`
4. Verify token is valid (not expired)
5. Verify database has floors: `php artisan tinker` → `\App\Models\HotelFloor::count()`

### If API Returns 500
1. Check error message in response
2. Check if `FloorResource.php` has proper serialization
3. Check if `HotelFloor` model has correct table/columns
4. Run migrations if needed: `php artisan migrate`

### If Stats Don't Load
- This is OK - component shows default stats with fallback values
- Stats aren't critical for floor display

## Browser Testing

1. Open DevTools → Network tab
2. Go to `/manager/floor-assignment`
3. Look for API calls:
   - `GET /api/manager/floors?is_active=true` (should be 200)
   - `GET /api/manager/floors/assignments/today` (may be 200 or error, both OK)
   - `GET /api/manager/floors/assignments/stats` (may be 200 or error, both OK)
4. Verify floors display in 3-column grid
5. Each floor card shows:
   - Floor name and number
   - ACTIVE/INACTIVE badge
   - Staff assignments or "No staff assigned yet"
   - "+ Add Staff" button

## Next Steps

 Verify floors load on assignment page
 Can click "Add New Floor" to create new floor
 Can see floor assignments (if any exist)
 Can add staff to floors (future feature)
