# Floor Creation Integration Test & Diagnostics

**Date**: July 27, 2026  
**Status**:  Backend WORKING |  Frontend INTEGRATED | ⏳ Testing in progress

---

## Current State

### Backend
- **Route**: `POST /api/manager/floors`
- **Controller**: `FloorManagementController@store`
- **Validation**: Checks floor_number and name uniqueness
- **Status**:  Working correctly

### Frontend
- **Component**: `AddFloor.vue`
- **Store**: `useAddFloorStore()`
- **Service**: `floorManagementService`
- **Status**:  Integrated with backend

### Known Existing Floors
Floors 1-5 are pre-seeded:
- Floor 1: Ground Floor
- Floor 2: First Floor
- Floor 3: Second Floor
- Floor 4: Third Floor
- Floor 5: Fourth Floor

---

## Test Steps

### 1. Test Floor Creation with Valid Data 

**Action**: Create a floor with floor_number = 6

```bash
curl -X POST http://127.0.0.1:8000/api/manager/floors \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "floor_number": 6,
    "name": "Fifth Floor",
    "description": "Fifth floor with premium rooms"
  }'
```

**Expected Response**:
```json
{
  "success": true,
  "message": "Floor created successfully",
  "data": {
    "id": "uuid-here",
    "floor_number": 6,
    "name": "Fifth Floor",
    "description": "Fifth floor with premium rooms",
    "is_active": true,
    "created_at": "2026-07-27T...",
    "updated_at": "2026-07-27T..."
  }
}
```

**Status Code**: 201 (Created)

---

### 2. Test Floor Creation with Duplicate Floor Number ❌

**Action**: Try to create floor with floor_number = 1 (already exists)

```bash
curl -X POST http://127.0.0.1:8000/api/manager/floors \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "floor_number": 1,
    "name": "Ground Floor New",
    "description": "Try to duplicate"
  }'
```

**Expected Response**:
```json
{
  "success": false,
  "message": "Failed to create floor",
  "errors": {
    "floor_number": ["The floor number has already been taken."]
  }
}
```

**Status Code**: 422 (Validation Error)

---

### 3. Test Floor Creation with Duplicate Name ❌

**Action**: Try to create floor with name that already exists

```bash
curl -X POST http://127.0.0.1:8000/api/manager/floors \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "floor_number": 99,
    "name": "Ground Floor",
    "description": "Try to duplicate name"
  }'
```

**Expected Response**:
```json
{
  "success": false,
  "message": "Failed to create floor",
  "errors": {
    "name": ["The name has already been taken."]
  }
}
```

**Status Code**: 422 (Validation Error)

---

### 4. Test Floor Creation - Missing Required Fields ❌

**Action**: Create floor without floor_number

```bash
curl -X POST http://127.0.0.1:8000/api/manager/floors \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Floor Without Number",
    "description": "Missing floor number"
  }'
```

**Expected Response**:
```json
{
  "success": false,
  "message": "Failed to create floor",
  "errors": {
    "floor_number": ["The floor number field is required."]
  }
}
```

**Status Code**: 422 (Validation Error)

---

## Frontend Testing Steps

### Step 1: Open Add Floor Page
1. Go to Manager Dashboard
2. Click "Assign Floors" (Floors page)
3. Click "Add New Floor" button
4. Should see the Add Floor form

### Step 2: Fill Form with Valid Data
```
Floor Number: 6
Zone Name: Premium Level
Description: Sixth floor with executive services
```

### Step 3: Submit Form
- Click "Create Floor" button
- Should see loading state ("Creating Floor...")
- On success: Green success message appears
- On error: Red error message with validation details

### Step 4: Verify Redirection
- After 2 seconds, should redirect to Floor Assignment page
- New floor should appear in the list

---

## Error Handling

### Backend Errors
The backend returns proper HTTP status codes:
- **201**: Floor created successfully
- **422**: Validation error (duplicate floor_number/name, missing fields)
- **500**: Server error (see laravel.log)

### Frontend Error Handling
The AddFloor component shows:
1. **Validation Errors**: Below each field in red
2. **Submission Errors**: Red alert box at bottom
3. **Success Messages**: Green alert box at bottom
4. **Uniqueness Check**: On blur of floor_number field

---

## Database Schema

### hotel_floors table
```sql
CREATE TABLE hotel_floors (
  id UUID PRIMARY KEY,
  floor_number INT UNIQUE NOT NULL,
  name VARCHAR(100) UNIQUE NOT NULL,
  description TEXT,
  is_active BOOLEAN DEFAULT 1,
  total_rooms INT DEFAULT 0,
  created_at TIMESTAMP,
  updated_at TIMESTAMP
);
```

---

## Troubleshooting

### 1. "The floor number has already been taken"
**Problem**: Trying to create a floor with an existing floor_number  
**Solution**: Use a different floor_number (6, 7, 8, etc.)

### 2. "The name has already been taken"
**Problem**: Trying to create a floor with an existing name  
**Solution**: Use a unique floor name

### 3. 500 Internal Server Error
**Problem**: Server-side error  
**Check**: 
- Look in `server/storage/logs/laravel.log`
- Ensure floor_number is numeric
- Ensure name is a string
- Ensure database connection is working

### 4. Frontend shows "Cannot find module" error
**Problem**: Build errors due to import paths  
**Status**: Pre-existing casing issues in codebase (not related to AddFloor)  
**Solution**: Run `npm run build` - it will pass with warnings

---

## API Endpoints Reference

| Method | Endpoint | Purpose | Status |
|--------|----------|---------|--------|
| GET | `/api/manager/floors` | List all floors |  Working |
| POST | `/api/manager/floors` | Create new floor |  Working |
| GET | `/api/manager/floors/{id}` | Get floor details |  Working |
| PUT | `/api/manager/floors/{id}` | Update floor |  Working |
| DELETE | `/api/manager/floors/{id}` | Delete floor |  Working |
| PATCH | `/api/manager/floors/{id}/activate` | Activate floor |  Working |
| PATCH | `/api/manager/floors/{id}/deactivate` | Deactivate floor |  Working |

---

## Next Steps

1.  Test floor creation in browser
2.  Verify error handling works
3.  Verify floor appears in assignments list
4.  Test waiter assignment to new floor
5. Test full floor management workflow

---

## Notes for User

**IMPORTANT**: The existing seeded floors are 1-5. When testing:
- **Do NOT use floor numbers 1-5** (they already exist)
- **Use floor numbers 6 and above** for testing
- Each floor must have a unique floor_number and name
- Validation happens both on frontend (client-side) and backend (server-side)

---

