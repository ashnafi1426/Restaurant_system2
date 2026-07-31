# Manager Floor Assignment - Complete User Guide

**Status**: Active and Fully Functional   
**Last Updated**: 2026-07-26

---

## Overview

The **Floor Assignment** feature allows managers to:
- Assign waiters to specific floors
- Set priority levels (Primary, Secondary, Backup)
- Manage shift-based assignments
- View all assignments for today
- Remove or update assignments dynamically
- Monitor assignment statistics

---

## How to Access Floor Assignment

### Step 1: Login to Manager Dashboard
```
URL: http://localhost:5173/manager/dashboard
Email: manager@hotel.com
Password: Manager123@
```

### Step 2: Navigate to Floor Assignment
From the manager sidebar, click **"Floor Assignment"**

The page displays:
- 📊 Stats overview (Total, Primary, Secondary, Backup counts)
- 🎯 Shift selector dropdown
- 🏢 Floor cards for assignment
- 📋 Summary of today's assignments

---

## Step-by-Step: Assigning Waiters to Floors

### 1️⃣ Select a Shift

**Where**: Top section under "Assignment Controls"

```
Select Shift: [Dropdown Menu]
- Morning (6:00 - 14:00)
- Afternoon (14:00 - 22:00)
- Night (22:00 - 06:00)
```

**Why**: Each shift may have different waiters available. Changing the shift filters which assignments you see.

---

### 2️⃣ Choose Floor and Waiter

**Where**: Main grid with floor cards

Each **Floor Card** shows:
```
┌─────────────────────────────────┐
│ Ground Floor         [Floor 1]   │
├─────────────────────────────────┤
│ Primary:   [Select Waiter ▼]    │
│ Secondary: [Select Waiter ▼]    │
│ Backup:    [Select Waiter ▼]    │
└─────────────────────────────────┘
```

### What Each Priority Level Means:

| Priority | Role | Usage |
|----------|------|-------|
| **Primary** | Main waiter assigned to this floor | The primary waiter handles most orders on this floor |
| **Secondary** | Backup when primary is busy | Takes over if primary is at max capacity |
| **Backup** | Emergency coverage | Called in only if both primary and secondary are unavailable |

---

### 3️⃣ Select a Waiter

**Click** on the dropdown for Primary, Secondary, or Backup

You'll see a list of available waiters:
```
[Select Waiter ▼]
- John Smith (0/5)      ← (current orders / max orders)
- Sarah Johnson (2/5)
- Michael Brown (1/5)
- Emily Davis (3/5)
- David Wilson (0/5)
- Lisa Martinez (1/5)
```

**The numbers show**: Current workload vs capacity
- **0/5**: Waiter has no orders, can take 5 more
- **2/5**: Waiter has 2 orders, can take 3 more
- **5/5**: Waiter is at capacity

**Pro Tip**: Assign waiters with lower numbers to heavy-traffic floors

---

### 4️⃣ Complete Floor Assignment

Assign waiters to all three priority levels for each floor:

**Example - Ground Floor:**
```
Primary:   John Smith (0/5)     ✓
Secondary: Sarah Johnson (2/5)  ✓
Backup:    Michael Brown (1/5)  ✓
```

**Example - First Floor:**
```
Primary:   Emily Davis (3/5)    ✓
Secondary: David Wilson (0/5)   ✓
Backup:    Lisa Martinez (1/5)  ✓
```

---

## Floors in the System

The system has **5 Floors**:

| Floor | Number | Description | Use Case |
|-------|--------|-------------|----------|
| Ground Floor | 1 | Main restaurant & reception area | Dine-in & walk-in orders |
| First Floor | 2 | Room service area (101-110) | Room orders |
| Second Floor | 3 | Room service area (201-210) | Room orders |
| Third Floor | 4 | Room service area (301-310) | Room orders |
| Conference Hall | 5 | Banquet & conference area | Large events & meetings |

---

## Saving Assignments

### After Selecting All Waiters:

1. **Review** your assignments in the table
2. **Click** the **"Save Assignments"** button (top right)

```
[Refresh] [Save Assignments ✓]
```

**The system will:**
-  Validate all selections
-  Save to database
-  Update statistics
-  Show success message

**If validation fails**, you'll see an error message. Fix and try again.

---

## Summary Section

At the bottom, you'll see **"Today's Assignments Summary"**:

```
┌─────────────────────────────────────────┐
│ Today's Assignments Summary             │
├─────────────────────────────────────────┤
│ John Smith          Ground Floor - Primary    [Remove]
│ Sarah Johnson       Ground Floor - Secondary  [Remove]
│ Michael Brown       Ground Floor - Backup     [Remove]
│ Emily Davis         First Floor - Primary     [Remove]
│ David Wilson        First Floor - Secondary   [Remove]
│ Lisa Martinez       First Floor - Backup      [Remove]
│ ...                                      │
└─────────────────────────────────────────┘
```

### Actions:
- **View all assignments** at a glance
- **Click [Remove]** to delete an assignment
- **Confirm** when prompted

---

## Real-Time Statistics

The dashboard shows **4 key metrics**:

```
┌──────────────┐  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐
│     45       │  │     15       │  │     15       │  │     15       │
│   TOTAL      │  │   PRIMARY    │  │  SECONDARY   │  │    BACKUP    │
│ ASSIGNMENTS  │  │              │  │              │  │              │
└──────────────┘  └──────────────┘  └──────────────┘  └──────────────┘
```

**Updates automatically** as you make changes.

---

## Common Tasks

### ❓ Change a Waiter's Assignment

**Scenario**: "Emily Davis is now busy, I need to change her assignment"

**Steps**:
1. Find her assignment in the floor card
2. Click the dropdown (currently showing her name)
3. Select a different waiter
4. Click **Save Assignments**

### ❓ Remove an Assignment

**Scenario**: "A waiter called in sick, I need to remove their assignment"

**Steps**:
1. Find their assignment in the summary section
2. Click **[Remove]** button
3. Confirm deletion
4. Click **Save Assignments**

### ❓ Refresh Data

**Scenario**: "I want to see the latest data from the database"

**Steps**:
1. Click **[Refresh]** button (top right)
2. Page will reload the latest assignments
3. Statistics will update

---

## API Behind the Scenes

### Endpoints Used

| Method | Endpoint | Purpose |
|--------|----------|---------|
| GET | `/api/manager/floors/assignments/today` | Get today's assignments  |
| POST | `/api/manager/floors/assignments` | Create/update assignments |
| PATCH | `/api/manager/floors/assignments/{id}` | Update priority |
| DELETE | `/api/manager/floors/assignments/{id}` | Remove assignment |
| GET | `/api/manager/floors/assignments/stats` | Get statistics |

---

## Data Structure (Behind the Scenes)

### Assignment Object

```json
{
  "id": "123-456-789",
  "waiter": {
    "id": 13,
    "user": {
      "id": "uuid-123",
      "name": "John Smith",
      "email": "john.smith@waiter.com",
      "phone": "+1-555-0101"
    },
    "current_orders": 2,
    "maximum_orders": 5,
    "status": "active",
    "availability": "online"
  },
  "floor": {
    "id": "floor-uuid",
    "floor_number": 1,
    "name": "Ground Floor",
    "description": "Main restaurant and reception area",
    "is_active": true
  },
  "shift": {
    "id": "shift-uuid",
    "name": "Afternoon",
    "start_time": "2026-07-26T14:00:00Z",
    "end_time": "2026-07-26T22:00:00Z",
    "status": "active",
    "duration_hours": 8
  },
  "assignment_date": "2026-07-26",
  "status": "active",
  "priority": "primary",
  "assigned_by": {
    "id": "manager-uuid",
    "name": "David Manager"
  },
  "created_at": "2026-07-26 08:51:15",
  "updated_at": "2026-07-26 08:51:15"
}
```

---

## Error Handling

### Error Message: "Failed to load resource"
**Cause**: Server is down or not responding  
**Solution**: Refresh the page or contact IT

### Error Message: "No available waiters"
**Cause**: All waiters are at capacity or offline  
**Solution**: Wait for a waiter to complete deliveries or bring more staff online

### Error Message: "Invalid shift selected"
**Cause**: Selected shift doesn't exist  
**Solution**: Select a valid shift (Morning, Afternoon, or Night)

### Error Message: "Failed to save assignments"
**Cause**: Missing required fields or database error  
**Solution**: Ensure all three priority levels are assigned, then try again

---

## Performance Tips

### ⚡ For Smooth Operations

1. **Assign all priorities** - Don't leave Primary empty
2. **Balance workload** - Don't assign high-capacity waiters to light floors
3. **Consider availability** - Check waiter status before assigning
4. **Save frequently** - Don't make too many changes before saving
5. **Refresh daily** - Get fresh data each morning before assignments

---

## Keyboard Shortcuts

| Key | Action |
|-----|--------|
| `Ctrl + S` | Save assignments |
| `F5` | Refresh data |
| `Esc` | Close dropdowns |

---

## Troubleshooting

### Problem: Assignments Not Saving

**Check**:
1. All three priority levels are filled for each floor
2. You have selected different waiters for each floor
3. Click "Save Assignments" button (not just clicking elsewhere)
4. Check browser console for errors (F12)

### Problem: Old Data Showing

**Solution**: Click **[Refresh]** button to reload latest data

### Problem: Can't See Some Waiters

**Check**:
1. Waiter might be offline (unavailable)
2. Waiter might be at capacity (5/5 orders)
3. Waiter might be on a different shift
4. Waiter account might be inactive

### Problem: Assignment Disappeared After Refresh

**Cause**: Assignment was not properly saved  
**Solution**: Assign again and click **Save Assignments** to confirm

---

## Database Tables

### waiter_floor_assignments
Stores floor-to-waiter mappings:
- `waiter_id` - Which waiter
- `floor_id` - Which floor
- `shift_id` - Which shift
- `assignment_date` - Date of assignment
- `priority` - Primary/Secondary/Backup
- `status` - Active/Inactive
- `assigned_by` - Manager who made the assignment

### hotel_floors
Stores available floors:
- `id` - UUID
- `floor_number` - 1-5
- `name` - Display name
- `description` - Floor details
- `is_active` - Whether floor is active

### hotel_shifts
Stores available work shifts:
- `id` - UUID
- `name` - Morning/Afternoon/Night
- `start_time` - When shift starts
- `end_time` - When shift ends
- `duration_hours` - How long the shift is

---

## Best Practices

###  DO:
- Assign balanced workloads
- Review assignments before saving
- Use Refresh button daily
- Keep Primary assignment always filled
- Monitor waiter capacity

### ❌ DON'T:
- Overload one waiter with multiple floors
- Assign offline waiters
- Leave priority slots empty
- Forget to save after changes
- Assign same waiter to conflicting shifts

---

## Support & Documentation

**For Technical Issues**:
- Check Laravel logs: `storage/logs/laravel.log`
- Check browser console: F12 → Console tab
- Verify API endpoint: `GET /api/manager/floors/assignments/today`

**For Feature Requests**:
- Contact development team
- Submit through manager dashboard feedback

---

## Summary

The **Floor Assignment** feature provides:
-  Easy waiter-to-floor mapping
-  Priority-based assignment
-  Shift awareness
-  Real-time statistics
-  Remove/update flexibility
-  Daily management capability

**Current Status**: Fully operational and tested 

---

**Version**: 1.0  
**Last Tested**: 2026-07-26 23:24  
**API Status**: 200 OK   
**Database**: 45 active assignments  
