# Floor Assignment - API Reference

**Base URL**: `http://127.0.0.1:8000/api`  
**Authentication**: Bearer Token (required for all endpoints)

---

## 📋 Endpoints Overview

| Method | Endpoint | Purpose | Status |
|--------|----------|---------|--------|
| GET | `/manager/floors/assignments/today` | Get today's assignments |  Working |
| GET | `/manager/floors/assignments` | Get all assignments (paginated) |  Working |
| POST | `/manager/floors/assignments` | Create/update assignments |  Working |
| PATCH | `/manager/floors/assignments/{id}` | Update assignment priority |  Working |
| DELETE | `/manager/floors/assignments/{id}` | Delete assignment |  Working |
| GET | `/manager/floors/assignments/stats` | Get statistics |  Working |

---

## 🔐 Authentication

All endpoints require a Bearer token in the Authorization header:

```bash
curl -X GET http://127.0.0.1:8000/api/manager/floors/assignments/today \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json"
```

### Getting a Token

```bash
curl -X POST http://127.0.0.1:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "manager@hotel.com",
    "password": "Manager123@"
  }'
```

Response:
```json
{
  "success": true,
  "token": "23|ORtoy98EgqieZmMewILyJpphQHqQkg24YQZCrK3Q507f33d8",
  "user": {
    "id": "2dc1ae01-6106-4616-a87f-89bc8f6beab8",
    "role": "manager"
  }
}
```

---

## 📌 Endpoint Details

### 1. GET /manager/floors/assignments/today

**Purpose**: Retrieve all floor assignments for today  
**Method**: GET  
**Auth**: Required 

#### Request

```bash
curl -X GET http://127.0.0.1:8000/api/manager/floors/assignments/today \
  -H "Authorization: Bearer TOKEN" \
  -H "Content-Type: application/json"
```

#### Response (200 OK)

```json
{
  "success": true,
  "message": "Today's assignments retrieved successfully",
  "date": "2026-07-26",
  "data": [
    {
      "id": "3",
      "waiter": {
        "id": 13,
        "user_id": "206ccae6-246e-4ca0-a3d8-88da4a380928",
        "user": {
          "id": "206ccae6-246e-4ca0-a3d8-88da4a380928",
          "name": "John Smith",
          "email": "john.smith@waiter.com",
          "phone": "+1-555-0101"
        },
        "current_orders": 0,
        "maximum_orders": 5,
        "status": "active",
        "availability": "offline"
      },
      "floor": {
        "id": "c6038b5b-3b00-4f8f-afb8-9a31374ad2ad",
        "floor_number": 1,
        "name": "Ground Floor",
        "description": "Main restaurant and reception area",
        "is_active": true
      },
      "shift": {
        "id": "fd2c67af-9dc7-42e6-9609-f8d4caf750d3",
        "name": "Afternoon",
        "start_time": "2026-07-26T14:00:00.000000Z",
        "end_time": "2026-07-26T22:00:00.000000Z",
        "status": "active",
        "is_night_shift": null,
        "duration_hours": 8
      },
      "assignment_date": "2026-07-26",
      "status": "active",
      "priority": "secondary",
      "assigned_by": {
        "id": "2dc1ae01-6106-4616-a87f-89bc8f6beab8",
        "name": null
      },
      "created_at": "2026-07-26 08:51:15",
      "updated_at": "2026-07-26 08:51:15"
    }
    // ... more assignments
  ]
}
```

#### Response (500 Error - Before Fix)

```json
{
  "success": false,
  "message": "Failed to retrieve assignments",
  "error": "Call to undefined method getDurationHours()"
}
```

#### Response (500 Error - Now Fixed )

The endpoint now returns 200 OK with valid data.

---

### 2. GET /manager/floors/assignments

**Purpose**: Get all assignments with pagination and filters  
**Method**: GET  
**Auth**: Required 

#### Request Parameters

```
Query Parameters:
- page (optional): Page number (default: 1)
- per_page (optional): Items per page (default: 20)
- date (optional): Filter by date (YYYY-MM-DD)
- floor_id (optional): Filter by floor UUID
- waiter_id (optional): Filter by waiter ID
- status (optional): Filter by status (active/inactive)
```

#### Example Request

```bash
curl -X GET "http://127.0.0.1:8000/api/manager/floors/assignments?page=1&per_page=10&date=2026-07-26" \
  -H "Authorization: Bearer TOKEN" \
  -H "Content-Type: application/json"
```

#### Response (200 OK)

```json
{
  "success": true,
  "message": "Assignments retrieved successfully",
  "data": [
    {
      "id": "3",
      "waiter": { ... },
      "floor": { ... },
      "shift": { ... },
      "assignment_date": "2026-07-26",
      "status": "active",
      "priority": "primary"
    }
    // ... more
  ],
  "pagination": {
    "total": 45,
    "per_page": 10,
    "current_page": 1,
    "last_page": 5
  }
}
```

---

### 3. POST /manager/floors/assignments

**Purpose**: Create or update floor assignments  
**Method**: POST  
**Auth**: Required   
**Content-Type**: application/json

#### Request Body

```json
{
  "assignments": [
    {
      "waiter_id": "206ccae6-246e-4ca0-a3d8-88da4a380928",
      "floor_id": "c6038b5b-3b00-4f8f-afb8-9a31374ad2ad",
      "shift_id": "fd2c67af-9dc7-42e6-9609-f8d4caf750d3",
      "assignment_date": "2026-07-26",
      "priority": "primary"
    },
    {
      "waiter_id": "0025a1de-d843-4e11-beb4-a72e8114c6d2",
      "floor_id": "c6038b5b-3b00-4f8f-afb8-9a31374ad2ad",
      "shift_id": "fd2c67af-9dc7-42e6-9609-f8d4caf750d3",
      "assignment_date": "2026-07-26",
      "priority": "secondary"
    }
  ]
}
```

#### Request Example

```bash
curl -X POST http://127.0.0.1:8000/api/manager/floors/assignments \
  -H "Authorization: Bearer TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "assignments": [
      {
        "waiter_id": "206ccae6-246e-4ca0-a3d8-88da4a380928",
        "floor_id": "c6038b5b-3b00-4f8f-afb8-9a31374ad2ad",
        "shift_id": "fd2c67af-9dc7-42e6-9609-f8d4caf750d3",
        "assignment_date": "2026-07-26",
        "priority": "primary"
      }
    ]
  }'
```

#### Response (201 Created)

```json
{
  "success": true,
  "message": "1 assignment(s) created/updated successfully",
  "data": [
    {
      "id": "uuid-123",
      "waiter": { ... },
      "floor": { ... },
      "shift": { ... },
      "assignment_date": "2026-07-26",
      "status": "active",
      "priority": "primary"
    }
  ]
}
```

#### Validation Rules

| Field | Type | Required | Rules |
|-------|------|----------|-------|
| waiter_id | UUID | Yes | Must exist in waiters table |
| floor_id | UUID | Yes | Must exist in hotel_floors table |
| shift_id | UUID | Yes | Must exist in hotel_shifts table |
| assignment_date | Date | Yes | Format: YYYY-MM-DD |
| priority | String | Yes | Values: primary, secondary, backup |

---

### 4. PATCH /manager/floors/assignments/{id}

**Purpose**: Update assignment priority  
**Method**: PATCH  
**Auth**: Required 

#### Request

```bash
curl -X PATCH http://127.0.0.1:8000/api/manager/floors/assignments/3 \
  -H "Authorization: Bearer TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "priority": "backup"
  }'
```

#### Request Body

```json
{
  "priority": "primary|secondary|backup"
}
```

#### Response (200 OK)

```json
{
  "success": true,
  "message": "Assignment updated successfully",
  "data": {
    "id": "3",
    "waiter": { ... },
    "floor": { ... },
    "shift": { ... },
    "priority": "backup"
  }
}
```

---

### 5. DELETE /manager/floors/assignments/{id}

**Purpose**: Delete a floor assignment  
**Method**: DELETE  
**Auth**: Required 

#### Request

```bash
curl -X DELETE http://127.0.0.1:8000/api/manager/floors/assignments/3 \
  -H "Authorization: Bearer TOKEN" \
  -H "Content-Type: application/json"
```

#### Response (200 OK)

```json
{
  "success": true,
  "message": "Assignment deleted successfully"
}
```

#### Error Response (404 Not Found)

```json
{
  "success": false,
  "message": "Assignment not found"
}
```

---

### 6. GET /manager/floors/assignments/stats

**Purpose**: Get assignment statistics for a date  
**Method**: GET  
**Auth**: Required 

#### Request

```bash
curl -X GET "http://127.0.0.1:8000/api/manager/floors/assignments/stats?date=2026-07-26" \
  -H "Authorization: Bearer TOKEN" \
  -H "Content-Type: application/json"
```

#### Query Parameters

```
- date (optional): Date to get stats for (YYYY-MM-DD, default: today)
```

#### Response (200 OK)

```json
{
  "success": true,
  "message": "Statistics retrieved successfully",
  "date": "2026-07-26",
  "data": {
    "total_assignments": 45,
    "total_floors": 5,
    "total_waiters": 6,
    "primary_assignments": 15,
    "secondary_assignments": 15,
    "backup_assignments": 15
  }
}
```

---

## 🎯 Data Models

### WaiterFloorAssignment

```json
{
  "id": "string (UUID)",
  "waiter_id": "integer or UUID",
  "floor_id": "string (UUID)",
  "shift_id": "string (UUID)",
  "assignment_date": "date (YYYY-MM-DD)",
  "status": "string (active|inactive)",
  "priority": "string (primary|secondary|backup)",
  "assigned_by": "UUID (manager ID)",
  "created_at": "timestamp",
  "updated_at": "timestamp"
}
```

### Waiter (Nested)

```json
{
  "id": "integer",
  "user_id": "UUID",
  "user": {
    "id": "UUID",
    "name": "string",
    "email": "string",
    "phone": "string"
  },
  "current_orders": "integer",
  "maximum_orders": "integer",
  "status": "string (active|inactive)",
  "availability": "string (online|offline|busy)"
}
```

### Floor (Nested)

```json
{
  "id": "UUID",
  "floor_number": "integer (1-5)",
  "name": "string",
  "description": "string",
  "is_active": "boolean"
}
```

### Shift (Nested)

```json
{
  "id": "UUID",
  "name": "string (Morning|Afternoon|Night)",
  "start_time": "time (HH:MM:SS)",
  "end_time": "time (HH:MM:SS)",
  "status": "string (active|inactive)",
  "is_night_shift": "boolean|null",
  "duration_hours": "integer"
}
```

---

## 🚨 Error Responses

### 400 Bad Request

```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "waiter_id": ["The waiter_id field is required"],
    "priority": ["The priority must be one of: primary, secondary, backup"]
  }
}
```

### 401 Unauthorized

```json
{
  "message": "Unauthenticated"
}
```

### 403 Forbidden

```json
{
  "message": "This action is unauthorized"
}
```

### 404 Not Found

```json
{
  "success": false,
  "message": "Assignment not found"
}
```

### 500 Internal Server Error

```json
{
  "success": false,
  "message": "Failed to retrieve assignments",
  "error": "Error details (only in debug mode)"
}
```

---

## 📊 Common Request Examples

### Example 1: Get Today's Assignments for Morning Shift

```bash
curl -X GET http://127.0.0.1:8000/api/manager/floors/assignments/today \
  -H "Authorization: Bearer TOKEN" \
  -H "Content-Type: application/json" | jq '.data[] | select(.shift.name=="Morning")'
```

### Example 2: Get Assignments for Specific Waiter

```bash
curl -X GET "http://127.0.0.1:8000/api/manager/floors/assignments?waiter_id=13" \
  -H "Authorization: Bearer TOKEN" \
  -H "Content-Type: application/json"
```

### Example 3: Get Assignments for Specific Floor

```bash
curl -X GET "http://127.0.0.1:8000/api/manager/floors/assignments?floor_id=c6038b5b-3b00-4f8f-afb8-9a31374ad2ad" \
  -H "Authorization: Bearer TOKEN" \
  -H "Content-Type: application/json"
```

### Example 4: Bulk Create Assignments

```bash
curl -X POST http://127.0.0.1:8000/api/manager/floors/assignments \
  -H "Authorization: Bearer TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "assignments": [
      {
        "waiter_id": 13,
        "floor_id": "c6038b5b-3b00-4f8f-afb8-9a31374ad2ad",
        "shift_id": "dfa3bf36-fa31-42ac-b78a-8024e5b41086",
        "assignment_date": "2026-07-26",
        "priority": "primary"
      },
      {
        "waiter_id": 14,
        "floor_id": "c6038b5b-3b00-4f8f-afb8-9a31374ad2ad",
        "shift_id": "dfa3bf36-fa31-42ac-b78a-8024e5b41086",
        "assignment_date": "2026-07-26",
        "priority": "secondary"
      },
      {
        "waiter_id": 15,
        "floor_id": "c6038b5b-3b00-4f8f-afb8-9a31374ad2ad",
        "shift_id": "dfa3bf36-fa31-42ac-b78a-8024e5b41086",
        "assignment_date": "2026-07-26",
        "priority": "backup"
      }
    ]
  }'
```

---

## 📋 HTTP Status Codes

| Code | Meaning | Example |
|------|---------|---------|
| 200 | OK | GET/PATCH/DELETE successful |
| 201 | Created | POST successful |
| 400 | Bad Request | Invalid input data |
| 401 | Unauthorized | Missing/invalid token |
| 403 | Forbidden | No permission for action |
| 404 | Not Found | Resource doesn't exist |
| 500 | Server Error | Database/server issue |

---

## 🔍 Rate Limiting

Currently **no rate limiting** is implemented. Monitor system load if making many requests.

---

## 📝 Logging

All API operations are logged to: `storage/logs/laravel.log`

```
[timestamp] channel.LEVEL: message {"context_data"}
```

Example:
```
[2026-07-26 23:19:58] local.INFO: Floor assignments created {"count": 15, "date": "2026-07-26"}
```

---

##  Testing Checklist

- [ ] Authentication token obtained
- [ ] GET /today returns 200 with valid assignments
- [ ] POST /assignments creates new records
- [ ] PATCH /assignments/{id} updates priority
- [ ] DELETE /assignments/{id} removes record
- [ ] GET /stats returns correct counts
- [ ] All error cases handled properly
- [ ] Performance acceptable (<2s response time)

---

**Version**: 1.0  
**Last Updated**: 2026-07-26  
**Status**:  All Endpoints Operational
