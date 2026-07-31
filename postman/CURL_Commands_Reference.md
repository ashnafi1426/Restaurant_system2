# cURL Commands Reference - Waiter Backend Testing

Complete cURL commands for testing the waiter backend without Postman.

## 🔐 1. Authentication

### Login as Waiter
```bash
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "waiter1@example.com",
    "password": "password123"
  }'
```

**Save token:**
```bash
TOKEN="your_token_from_response"
```

## 📊 2. Dashboard Endpoints

### Get Dashboard Overview
```bash
curl -X GET http://localhost:8000/api/waiter/dashboard \
  -H "Authorization: Bearer $TOKEN"
```

### Get Today Stats
```bash
curl -X GET http://localhost:8000/api/waiter/dashboard/today \
  -H "Authorization: Bearer $TOKEN"
```

### Get Performance Stats
```bash
curl -X GET http://localhost:8000/api/waiter/dashboard/performance \
  -H "Authorization: Bearer $TOKEN"
```

### Get Recent Assignments
```bash
curl -X GET http://localhost:8000/api/waiter/dashboard/recent-assignments \
  -H "Authorization: Bearer $TOKEN"
```

### Get Kitchen Ready Orders
```bash
curl -X GET http://localhost:8000/api/waiter/dashboard/kitchen-ready-orders \
  -H "Authorization: Bearer $TOKEN"
```

### Get Ready for Pickup
```bash
curl -X GET http://localhost:8000/api/waiter/dashboard/ready-pickup \
  -H "Authorization: Bearer $TOKEN"
```

### Get Pending Pickup Orders
```bash
curl -X GET http://localhost:8000/api/waiter/dashboard/pending-pickup \
  -H "Authorization: Bearer $TOKEN"
```

### Get On Delivery
```bash
curl -X GET http://localhost:8000/api/waiter/dashboard/on-delivery \
  -H "Authorization: Bearer $TOKEN"
```

### Get Completed Deliveries
```bash
curl -X GET http://localhost:8000/api/waiter/dashboard/completed \
  -H "Authorization: Bearer $TOKEN"
```

### Get Failed Deliveries
```bash
curl -X GET http://localhost:8000/api/waiter/dashboard/failed \
  -H "Authorization: Bearer $TOKEN"
```

### Get Delivery Timeline
```bash
curl -X GET http://localhost:8000/api/waiter/dashboard/timeline \
  -H "Authorization: Bearer $TOKEN"
```

### Get Weekly Performance
```bash
curl -X GET http://localhost:8000/api/waiter/dashboard/weekly-performance \
  -H "Authorization: Bearer $TOKEN"
```

### Get Monthly Performance
```bash
curl -X GET http://localhost:8000/api/waiter/dashboard/monthly-performance \
  -H "Authorization: Bearer $TOKEN"
```

### Get Performance Comparison
```bash
curl -X GET http://localhost:8000/api/waiter/dashboard/performance-comparison \
  -H "Authorization: Bearer $TOKEN"
```

### Get Quick Stats
```bash
curl -X GET http://localhost:8000/api/waiter/dashboard/quick-stats \
  -H "Authorization: Bearer $TOKEN"
```

## 📋 3. Assignment Endpoints

### Get All Assignments
```bash
curl -X GET http://localhost:8000/api/waiter/assignments \
  -H "Authorization: Bearer $TOKEN"
```

### Get Single Assignment
```bash
curl -X GET http://localhost:8000/api/waiter/assignments/1 \
  -H "Authorization: Bearer $TOKEN"
```

### Get Pending Assignments
```bash
curl -X GET http://localhost:8000/api/waiter/assignments/pending/list \
  -H "Authorization: Bearer $TOKEN"
```

### Get Active Assignments
```bash
curl -X GET http://localhost:8000/api/waiter/assignments/active/list \
  -H "Authorization: Bearer $TOKEN"
```

### Get Today's Assignments
```bash
curl -X GET http://localhost:8000/api/waiter/assignments/today/list \
  -H "Authorization: Bearer $TOKEN"
```

## ✅ 4. Assignment Actions

### Accept Assignment
```bash
curl -X PATCH http://localhost:8000/api/waiter/assignments/1/accept \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{}'
```

### Reject Assignment
```bash
curl -X PATCH http://localhost:8000/api/waiter/assignments/1/reject \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "reason": "I am currently on a break"
  }'
```

### Pickup Order
```bash
curl -X PATCH http://localhost:8000/api/waiter/assignments/1/pickup \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{}'
```

### Start Delivery
```bash
curl -X PATCH http://localhost:8000/api/waiter/assignments/1/start-delivery \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{}'
```

### Complete Delivery
```bash
curl -X PATCH http://localhost:8000/api/waiter/assignments/1/deliver \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{}'
```

### Mark Delivery as Failed
```bash
curl -X PATCH http://localhost:8000/api/waiter/assignments/1/failed \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "reason": "Guest not in room"
  }'
```

## 🧪 5. Complete Workflow

### Full Delivery Cycle Script
```bash
#!/bin/bash

# Set variables
API="http://localhost:8000/api"
EMAIL="waiter1@example.com"
PASSWORD="password123"

# 1. Login
echo "🔐 Logging in..."
LOGIN_RESPONSE=$(curl -s -X POST $API/login \
  -H "Content-Type: application/json" \
  -d "{\"email\": \"$EMAIL\", \"password\": \"$PASSWORD\"}")

TOKEN=$(echo $LOGIN_RESPONSE | grep -o '"token":"[^"]*' | cut -d'"' -f4)
echo "Token: $TOKEN"

# 2. Get pending assignments
echo "📋 Getting pending assignments..."
curl -s -X GET $API/waiter/assignments/pending/list \
  -H "Authorization: Bearer $TOKEN" | jq '.'

# 3. Accept first assignment (ID: 1)
echo "✅ Accepting assignment..."
curl -s -X PATCH $API/waiter/assignments/1/accept \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{}' | jq '.'

# 4. Pickup order
echo "📦 Picking up order..."
curl -s -X PATCH $API/waiter/assignments/1/pickup \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{}' | jq '.'

# 5. Start delivery
echo "🚗 Starting delivery..."
curl -s -X PATCH $API/waiter/assignments/1/start-delivery \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{}' | jq '.'

# 6. Complete delivery
echo "🎉 Completing delivery..."
curl -s -X PATCH $API/waiter/assignments/1/deliver \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{}' | jq '.'

# 7. Check dashboard
echo "📊 Checking updated dashboard..."
curl -s -X GET $API/waiter/dashboard/today \
  -H "Authorization: Bearer $TOKEN" | jq '.'
```

## 🔍 6. Testing Tips

### Format JSON Output (Pretty Print)
```bash
curl -s ... | jq '.'
```

### Save Response to File
```bash
curl -s ... -o response.json
```

### Get Response Headers
```bash
curl -i ...
```

### Verbose Output (Debug)
```bash
curl -v ...
```

### Follow Redirects
```bash
curl -L ...
```

### Set Custom Headers
```bash
curl -H "X-Custom-Header: value" ...
```

### Send Form Data
```bash
curl -d "param1=value1&param2=value2" ...
```

## 📝 7. Common Variables

### Test Waiter Accounts
```bash
WAITER1_EMAIL="waiter1@example.com"
WAITER2_EMAIL="waiter2@example.com"
WAITER3_EMAIL="waiter3@example.com"
PASSWORD="password123"

API_URL="http://localhost:8000/api"
BASE_URL="http://localhost:8000"
```

## 🛠️ 8. Batch Testing

### Test All Dashboard Endpoints
```bash
#!/bin/bash

TOKEN="your_token_here"
API="http://localhost:8000/api/waiter"

endpoints=(
  "dashboard"
  "dashboard/today"
  "dashboard/performance"
  "dashboard/recent-assignments"
  "dashboard/kitchen-ready-orders"
  "dashboard/ready-pickup"
  "dashboard/pending-pickup"
  "dashboard/on-delivery"
  "dashboard/completed"
  "dashboard/failed"
  "dashboard/timeline"
  "dashboard/weekly-performance"
  "dashboard/monthly-performance"
  "dashboard/quick-stats"
)

for endpoint in "${endpoints[@]}"; do
  echo "Testing: $endpoint"
  curl -s -X GET $API/$endpoint \
    -H "Authorization: Bearer $TOKEN" | jq '.status'
  echo ""
done
```

## 📊 9. Response Examples

### Successful Login
```bash
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
  "user": {
    "id": 2,
    "name": "Ahmed Hassan",
    "email": "waiter1@example.com",
    "role": "waiter"
  }
}
```

### Dashboard Response
```bash
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

### Pending Assignments
```bash
{
  "data": [
    {
      "id": 1,
      "waiter_id": 2,
      "order_id": 1,
      "status": "pending",
      "assigned_at": "2024-01-29T08:00:00Z"
    }
  ]
}
```

## 🐛 10. Error Handling

### Check HTTP Status Code
```bash
curl -w "%{http_code}\n" -o /dev/null -s ...
```

### Get Response with Status
```bash
curl -w "\nStatus: %{http_code}\n" ...
```

### Handle Errors in Script
```bash
response=$(curl -s -w "\n%{http_code}" ...)
status=$(echo "$response" | tail -n 1)
body=$(echo "$response" | head -n -1)

if [ $status -eq 200 ]; then
  echo "Success: $body"
else
  echo "Error ($status): $body"
fi
```

## 💡 Quick Tips

1. **Save Token**: `TOKEN=$(curl ... | grep token | cut -d'"' -f4)`
2. **Pretty Print**: Pipe output to `jq '.'`
3. **Test Multiple**: Use for loops for batch testing
4. **Debug**: Use `-v` flag for verbose output
5. **Timing**: Use `-w` to see request time

## 📞 Common Commands

### Test Server Connection
```bash
curl -I http://localhost:8000/api/login
```

### Get User Info
```bash
curl http://localhost:8000/api/me \
  -H "Authorization: Bearer $TOKEN"
```

### Logout
```bash
curl -X POST http://localhost:8000/api/logout \
  -H "Authorization: Bearer $TOKEN"
```

---

**Use these commands to test the waiter backend from the command line!** 🚀
