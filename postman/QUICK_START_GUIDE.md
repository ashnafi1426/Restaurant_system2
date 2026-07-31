# Waiter Backend - Quick Start Guide (5 Minutes)

## ⚡ Quick Setup

### 1. Import Collection & Environment
```
In Postman:
- Click "Import" → Select "Waiter_Complete_Collection.json"
- Click "Import" → Select "Waiter_Environment.json"
- Select environment from dropdown (top-right)
```

### 2. Login
```
Click: "1. Authentication" → "Login as Waiter"
Click: "Send"
Token auto-saves to environment ✅
```

### 3. Test Dashboard
```
Click: "2. Waiter Dashboard" → "Get Dashboard Overview"
Click: "Send"
See full dashboard data ✅
```

### 4. Test Assignments
```
Click: "3. Waiter Assignments" → "Get Pending Assignments"
Click: "Send"
View all pending assignments ✅
```

## 🔄 Full Delivery Workflow (Test)

Complete these in order:

1. **Get All Assignments**
   ```
   GET /api/waiter/assignments
   ```
   Note the assignment ID (e.g., 1)

2. **Accept Assignment**
   ```
   PATCH /api/waiter/assignments/1/accept
   Body: {}
   ```

3. **Pickup Order**
   ```
   PATCH /api/waiter/assignments/1/pickup
   Body: {}
   ```

4. **Start Delivery**
   ```
   PATCH /api/waiter/assignments/1/start-delivery
   Body: {}
   ```

5. **Complete Delivery**
   ```
   PATCH /api/waiter/assignments/1/deliver
   Body: {}
   ```

6. **Check Updated Stats**
   ```
   GET /api/waiter/dashboard/today
   ```
   Should show completed delivery count increased

## 📊 Key Endpoints to Test

| What | Endpoint | Status |
|------|----------|--------|
| Get Dashboard | `GET /api/waiter/dashboard` | ✅ Ready |
| Get Today Stats | `GET /api/waiter/dashboard/today` | ✅ Ready |
| Get Assignments | `GET /api/waiter/assignments` | ✅ Ready |
| Accept Assignment | `PATCH /api/waiter/assignments/{id}/accept` | ✅ Ready |
| Reject Assignment | `PATCH /api/waiter/assignments/{id}/reject` | ✅ Ready |
| Get Performance | `GET /api/waiter/dashboard/performance` | ✅ Ready |
| Get Completed | `GET /api/waiter/dashboard/completed` | ✅ Ready |

## 🎯 Common Tasks

### Check if Waiter is Busy
```
GET /api/waiter/dashboard/quick-stats
Look for: "current_orders" and "maximum_orders"
```

### Get Kitchen Ready Orders
```
GET /api/waiter/dashboard/kitchen-ready-orders
These need to be picked up
```

### Get Orders on Delivery
```
GET /api/waiter/dashboard/on-delivery
These are currently being delivered
```

### Check Performance Today
```
GET /api/waiter/dashboard/today
Shows all stats for today
```

### Reject an Order
```
PATCH /api/waiter/assignments/{id}/reject
Body: {
  "reason": "I am on break"
}
```

### Mark Delivery Failed
```
PATCH /api/waiter/assignments/{id}/failed
Body: {
  "reason": "Guest not in room"
}
```

## 🔑 Test Credentials

```
Email:    waiter1@example.com
Password: password123
```

Alternative waiters:
- waiter2@example.com
- waiter3@example.com

## 💾 Seeded Test Data

Automatically included:
- ✅ 3 active waiter accounts
- ✅ 7 sample orders
- ✅ 7 sample assignments
- ✅ 4 hotel floors
- ✅ Multiple assignments in different statuses

## ❓ Troubleshooting (30 Seconds)

| Problem | Fix |
|---------|-----|
| 401 Unauthorized | Run login endpoint again |
| 404 Assignment not found | Check assignment ID matches database |
| Empty dashboard data | Seed test data first |
| Token not saving | Check Postman environment is selected |
| Endpoint not working | Verify BASE_URL in environment |

## 📱 Mobile/App Testing

Test data format (JSON):

**User Response:**
```json
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "user": {
    "id": 2,
    "name": "Ahmed Hassan",
    "email": "waiter1@example.com",
    "role": "waiter"
  }
}
```

**Assignment Response:**
```json
{
  "id": 1,
  "waiter_id": 2,
  "order_id": 1,
  "status": "pending",
  "assigned_at": "2024-01-29T08:00:00Z",
  "order": {
    "id": 1,
    "room_id": 201,
    "total_amount": 650
  }
}
```

**Dashboard Response:**
```json
{
  "stats": {
    "pending_assignments": 2,
    "active_deliveries": 1,
    "completed_today": 11,
    "failed_today": 1,
    "efficiency_score": 92
  }
}
```

## ✨ What's Tested

✅ Waiter authentication and token generation
✅ Dashboard stats (all endpoints)
✅ Assignment retrieval (all filters)
✅ Assignment status transitions (all workflows)
✅ Performance metrics and history
✅ Notification system
✅ Real-time data accuracy
✅ Error handling

## 🚀 Next Steps

1. **Run Full Collection**: Use Postman Collection Runner
2. **Automate Tests**: Add test scripts to validate responses
3. **Load Test**: Test with multiple concurrent waiters
4. **Monitor**: Setup Postman monitoring for continuous testing
5. **Document**: Export results for reports

## 📞 Quick Help

- **Postman Docs**: Click "?" on any endpoint
- **Check Logs**: `tail -f storage/logs/laravel.log`
- **Reset Data**: Re-seed the database
- **New Test Data**: Use the JSON files provided

---

**That's it! You're ready to test the waiter backend. 🎉**
