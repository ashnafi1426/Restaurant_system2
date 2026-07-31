# Waiter Backend Complete Testing Collection

Complete Postman collection and test data for testing all waiter-related API endpoints in the Restaurant Management System.

## 📦 Files Included

### 1. **Waiter_Complete_Collection.json**
Main Postman collection with all waiter API endpoints organized by functionality.

**Sections:**
- **Authentication** - Login endpoint
- **Waiter Dashboard** - All dashboard statistics and performance endpoints
- **Waiter Assignments** - Assignment management endpoints

### 2. **Waiter_Environment.json**
Environment configuration file with all necessary variables and placeholders.

**Key Variables:**
```
- BASE_URL: http://localhost:8000
- WAITER_TOKEN: (Auto-populated after login)
- WAITER_ID: 1
- ASSIGNMENT_ID: 1
- ORDER_ID: 1
- FLOOR_ID: 1
- SHIFT_ID: 1
- ROOM_ID: 101
- WAITER_EMAIL: waiter1@example.com
- WAITER_PASSWORD: password123
```

### 3. **Test_Data_Waiters.json**
Sample waiter data and login credentials for testing.

**Contains:**
- 4 sample waiter profiles with different statuses
- Login credentials for testing
- Waiter details (name, email, role, phone, etc.)

### 4. **Test_Data_Assignments.json**
Sample waiter assignments and order data.

**Contains:**
- 7 sample assignments in different statuses (pending, accepted, picked_up, on_delivery, delivered, rejected, failed)
- Assignment action payloads for testing state transitions
- Expected responses for each action

### 5. **Test_Data_Floors.json**
Floor and floor assignment data.

**Contains:**
- 4 hotel floor records
- Floor assignment relationships with waiters
- Floor assignment actions (create, update, delete)

### 6. **Test_Data_Performance.json**
Performance metrics and notification data.

**Contains:**
- Waiter performance metrics (delivery time, success rate, satisfaction score)
- Waiter notifications (5 sample notifications)
- Dashboard stats and performance overview
- Quick stats, today's stats, performance metrics

### 7. **Test_Data_Orders.json**
Order and delivery task data.

**Contains:**
- 7 sample orders with items and pricing
- 6 delivery tasks in different statuses
- Room and guest information
- Delivery notes and failure reasons

## 🚀 Quick Start

### Step 1: Import into Postman

1. Open Postman
2. Click "Import" button
3. Select **Waiter_Complete_Collection.json**
4. Import the environment by clicking Import → Select **Waiter_Environment.json**
5. Now you'll have the collection and environment ready

### Step 2: Set Up the Environment

1. In Postman, select **Waiter Testing Environment** from the dropdown (top-right)
2. Verify the BASE_URL is correct (default: http://localhost:8000)
3. The WAITER_TOKEN will be auto-populated after the first login

### Step 3: Run Tests

1. Click on "Login as Waiter" endpoint
2. Update the email/password if your test credentials are different
3. Click "Send"
4. The token will be automatically saved to the environment
5. Now you can run any other endpoint in the collection

## 📋 API Endpoints Overview

### Authentication
```
POST /api/login
```

### Waiter Dashboard
```
GET /api/waiter/dashboard                           # Main dashboard overview
GET /api/waiter/dashboard/today                     # Today's stats
GET /api/waiter/dashboard/performance               # Performance metrics
GET /api/waiter/dashboard/recent-assignments        # Recent assignments
GET /api/waiter/dashboard/kitchen-ready-orders      # Orders ready from kitchen
GET /api/waiter/dashboard/ready-pickup              # Orders ready for pickup
GET /api/waiter/dashboard/pending-pickup            # Pending pickup orders
GET /api/waiter/dashboard/on-delivery               # Currently on delivery
GET /api/waiter/dashboard/completed                 # Completed deliveries
GET /api/waiter/dashboard/failed                    # Failed deliveries
GET /api/waiter/dashboard/timeline                  # Delivery timeline
GET /api/waiter/dashboard/weekly-performance        # Weekly performance
GET /api/waiter/dashboard/monthly-performance       # Monthly performance
GET /api/waiter/dashboard/performance-comparison    # Performance comparison
GET /api/waiter/dashboard/quick-stats               # Quick stats summary
```

### Waiter Assignments
```
GET    /api/waiter/assignments                      # Get all assignments
GET    /api/waiter/assignments/1                    # Get single assignment
GET    /api/waiter/assignments/pending/list         # Get pending assignments
GET    /api/waiter/assignments/active/list          # Get active assignments
GET    /api/waiter/assignments/today/list           # Get today's assignments
PATCH  /api/waiter/assignments/1/accept             # Accept an assignment
PATCH  /api/waiter/assignments/1/reject             # Reject an assignment
PATCH  /api/waiter/assignments/1/pickup             # Pickup an order
PATCH  /api/waiter/assignments/1/start-delivery     # Start delivery
PATCH  /api/waiter/assignments/1/deliver            # Complete delivery
PATCH  /api/waiter/assignments/1/failed             # Mark delivery as failed
```

## 📊 Assignment Status Workflow

```
pending → accepted → picked_up → on_delivery → delivered
    ↓
  rejected
    ↓
  failed (at any point after acceptance)
```

### Status Transitions

| From | To | Endpoint | Payload |
|------|----|-----------| --------|
| pending | accepted | `PATCH /assignments/{id}/accept` | `{}` |
| pending | rejected | `PATCH /assignments/{id}/reject` | `{"reason": "text"}` |
| accepted | picked_up | `PATCH /assignments/{id}/pickup` | `{}` |
| picked_up | on_delivery | `PATCH /assignments/{id}/start-delivery` | `{}` |
| on_delivery | delivered | `PATCH /assignments/{id}/deliver` | `{}` |
| any (except final) | failed | `PATCH /assignments/{id}/failed` | `{"reason": "text"}` |

## 🔐 Authentication

All protected endpoints require the `Authorization` header:
```
Authorization: Bearer {{WAITER_TOKEN}}
```

The token is automatically extracted from the login response and stored in the environment variable.

## 📝 Test Scenarios

### Scenario 1: Full Order Delivery Cycle
1. Login as waiter
2. Get pending assignments
3. Accept an assignment
4. Pickup order
5. Start delivery
6. Complete delivery
7. Check updated performance stats

### Scenario 2: Reject Assignment
1. Login as waiter
2. Get pending assignments
3. Reject with reason
4. Verify status changed to "rejected"

### Scenario 3: Failed Delivery
1. Login as waiter
2. Get active assignments
3. Start delivery
4. Mark as failed with reason
5. Check failed deliveries list

### Scenario 4: Dashboard Analytics
1. Login as waiter
2. Get quick stats
3. Get today's stats
4. Get weekly performance
5. Get monthly performance

## 🛠️ Database Seeding

To populate test data in your database, use the provided test data files:

### Option 1: Manual Database Seeding
Use the data from the test files to create database records directly.

### Option 2: Create Seeders
```bash
php artisan make:seeder WaiterTestSeeder
```

Add the test data from `Test_Data_Waiters.json` to the seeder and run:
```bash
php artisan db:seed --class=WaiterTestSeeder
```

## 🔍 Troubleshooting

### Login Fails
- **Issue**: 401 Unauthorized
- **Solution**: Verify waiter credentials in `Test_Data_Waiters.json`
- Check if waiter user exists in database with correct role

### Token Expires
- **Issue**: 401 Unauthorized on subsequent requests
- **Solution**: Re-run the login endpoint to get a new token
- The environment will auto-update with the new token

### Assignment Not Found
- **Issue**: 404 error when accessing assignment
- **Solution**: Verify the assignment ID exists in database
- Check the status - only certain statuses allow certain operations

### Endpoints Return Empty Data
- **Issue**: Dashboard returns empty arrays
- **Solution**: Ensure test data is seeded in database
- Check that waiter has assignments for the date

## 📈 Performance Metrics

The dashboard provides:
- **Total Deliveries**: Count of all assignments today
- **Success Rate**: Percentage of successful deliveries
- **Average Delivery Time**: Mean time from assignment to delivery (minutes)
- **On-Time Percentage**: % of deliveries completed within target time
- **Customer Satisfaction**: Average rating from guests (1-5)
- **Efficiency Score**: Overall performance score (0-100)

## 🔄 Workflow Example

```bash
# 1. Login
POST /api/login
Body: {"email": "waiter1@example.com", "password": "password123"}

# 2. Get pending assignments
GET /api/waiter/assignments/pending/list
Headers: Authorization: Bearer {{WAITER_TOKEN}}

# 3. Accept assignment
PATCH /api/waiter/assignments/1/accept
Headers: Authorization: Bearer {{WAITER_TOKEN}}
Body: {}

# 4. Pickup order
PATCH /api/waiter/assignments/1/pickup
Headers: Authorization: Bearer {{WAITER_TOKEN}}
Body: {}

# 5. Start delivery
PATCH /api/waiter/assignments/1/start-delivery
Headers: Authorization: Bearer {{WAITER_TOKEN}}
Body: {}

# 6. Complete delivery
PATCH /api/waiter/assignments/1/deliver
Headers: Authorization: Bearer {{WAITER_TOKEN}}
Body: {}

# 7. Check dashboard stats
GET /api/waiter/dashboard/today
Headers: Authorization: Bearer {{WAITER_TOKEN}}
```

## 💡 Tips

1. **Auto-populate Token**: The login endpoint automatically saves the token to the environment
2. **Use Variables**: Replace hard-coded IDs with environment variables (e.g., `{{WAITER_ID}}`)
3. **Test Scripts**: Add pre-request and test scripts to validate responses
4. **Export Results**: Use Postman's collection runner to export test results
5. **Monitor Performance**: Track average response times using Postman's monitoring features

## 📞 Support

For issues or questions:
1. Check the troubleshooting section above
2. Verify all test data is properly seeded
3. Check Laravel logs: `storage/logs/laravel.log`
4. Verify API routes in `routes/api.php`
5. Check waiter controller implementations

## ✅ Validation Checklist

Before production testing:
- [ ] All test data seeded correctly
- [ ] Environment variables configured
- [ ] Login endpoint returns valid token
- [ ] Dashboard endpoints return data
- [ ] Assignment workflows complete successfully
- [ ] Performance metrics calculated correctly
- [ ] Notifications created and retrieved
- [ ] Historical data accessible

## 📚 Related Files

- Backend Routes: `server/routes/api.php`
- Waiter Controller: `server/app/Http/Controllers/Api/Waiter/WaiterAssignmentController.php`
- Waiter Model: `server/app/Models/Waiter.php`
- Assignment Model: `server/app/Models/WaiterAssignment.php`
- Dashboard Service: `server/app/Services/Waiter/WaiterDashboardService.php`
