# Floor Assignment - Quick Start (5 Minutes)

## 🚀 Get Started in 5 Steps

### Step 1: Login
```
URL: http://localhost:5173/manager/dashboard
📧 Email: manager@hotel.com
🔒 Password: Manager123@
```

### Step 2: Click "Floor Assignment"
From the left sidebar, find and click **Floor Assignment**

### Step 3: Select a Shift
```
Dropdown: Select Shift
Options:
  • Morning (6:00 - 14:00)
  • Afternoon (14:00 - 22:00)  ← Pick one
  • Night (22:00 - 06:00)
```

### Step 4: Assign Waiters
For each floor card, select waiters:
```
Primary:   [Select Waiter] → John Smith
Secondary: [Select Waiter] → Sarah Johnson
Backup:    [Select Waiter] → Michael Brown
```

Repeat for all 5 floors:
- Ground Floor
- First Floor
- Second Floor
- Third Floor
- Conference Hall

### Step 5: Save
Click the **"Save Assignments"** button (blue button, top right)

 Done! Assignments saved.

---

## 📊 What You'll See

### Stats Cards
```
┌─────────────┬─────────────┬──────────────┬─────────────┐
│     45      │     15      │      15      │      15     │
│  TOTAL      │  PRIMARY    │  SECONDARY   │   BACKUP    │
└─────────────┴─────────────┴──────────────┴─────────────┘
```

### Floor Cards
```
┌──────────────────────────────────┐
│ Ground Floor        [Floor 1]     │
│─────────────────────────────────  │
│ Primary:   [John Smith (0/5)] ✓   │
│ Secondary: [Sarah Johnson (2/5)]✓ │
│ Backup:    [Michael Brown (1/5)]✓ │
└──────────────────────────────────┘
```

### Summary List
```
John Smith       → Ground Floor - Primary    [Remove]
Sarah Johnson    → Ground Floor - Secondary  [Remove]
Michael Brown    → Ground Floor - Backup     [Remove]
Emily Davis      → First Floor - Primary     [Remove]
... (and more)
```

---

## 🎯 Priority Levels Explained

| Level | Role | When to Use |
|-------|------|------------|
| 🟢 **PRIMARY** | Main person | Handles most orders |
| 🟡 **SECONDARY** | Backup person | Takes over if primary is busy |
| 🔴 **BACKUP** | Emergency | Only if primary & secondary unavailable |

---

## 📋 Your Floors

```
1️⃣ Ground Floor       → Main restaurant & reception
2️⃣ First Floor        → Room service (101-110)
3️⃣ Second Floor       → Room service (201-210)
4️⃣ Third Floor        → Room service (301-310)
5️⃣ Conference Hall    → Banquet & conference area
```

---

## 👥 Your Waiters

Currently available:
- John Smith (0/5) - Can take 5 more
- Sarah Johnson (2/5) - Can take 3 more
- Michael Brown (1/5) - Can take 4 more
- Emily Davis (3/5) - Can take 2 more
- David Wilson (0/5) - Can take 5 more
- Lisa Martinez (1/5) - Can take 4 more

*Numbers = current orders / max orders*

---

## ⚡ Quick Actions

### Change an Assignment
1. Click the dropdown showing current waiter
2. Select a different waiter
3. Click Save

### Remove an Assignment
1. Find it in the summary section
2. Click [Remove]
3. Confirm
4. Click Save

### Refresh Data
- Click [Refresh] button to reload from database

---

##  Checklist Before Saving

- [ ] Selected a shift
- [ ] Primary waiter assigned to all floors
- [ ] Secondary waiter assigned to all floors
- [ ] Backup waiter assigned to all floors
- [ ] No waiter is overloaded (5/5)
- [ ] All assignments are correct

---

## 🆘 Troubleshooting

### Nothing saving?
1. Check all 3 priority levels are filled
2. Make sure you clicked "Save Assignments" (blue button)
3. Wait 2 seconds for save to complete
4. Check browser console (F12) for errors

### Can't see waiters?
- They might be offline or at full capacity
- Try refreshing the page
- Check if waiter account is active

### Server error?
- Refresh the page (F5)
- Try again in a few seconds
- Contact IT if problem persists

---

## 📞 Need Help?

**Common Questions**:
- Q: Can I assign same waiter to multiple floors?
  A: Yes! One waiter can be primary on one floor, secondary on another

- Q: What if a waiter calls in sick?
  A: Remove their assignments and reassign to other waiters

- Q: Can I change assignments after saving?
  A: Yes, select different waiter and save again

- Q: What do the numbers (2/5) mean?
  A: Currently handling 2 out of 5 maximum orders

---

## 🎓 Pro Tips

1. **Balance workload**: Don't assign busy waiters (4/5, 5/5) to new floors
2. **Use primary wisely**: Primary gets most traffic, pick your best waiter
3. **Secondary as backup**: They should be available to help primary
4. **Backup is safety**: Someone with very low workload (0/5)
5. **Refresh daily**: Get fresh data each morning

---

## ⏱️ Time Breakdown

| Task | Time |
|------|------|
| Login | 30 sec |
| Navigate | 15 sec |
| Select shift | 10 sec |
| Assign 5 floors | 2 min |
| Save | 10 sec |
| **Total** | **~3 min** |

---

## 🔄 Daily Workflow

**Every Morning:**
```
1. Login to manager dashboard
2. Click "Floor Assignment"
3. Select shift for today
4. Review auto-populated assignments
5. Make any necessary changes
6. Save assignments
7. Done! Waiters will see their floor assignments
```

---

## 📱 Mobile Access

The Floor Assignment page is optimized for:
-  Desktop (best experience)
-  Tablet (condensed view)
- Mobile (limited, better on desktop)

---

## 📊 Dashboard Widgets

**After saving, these show automatically:**
- Total assignments for today
- Count by priority level
- Assigned waiters summary
- Unassigned floors (if any)

---

## 🎨 UI Elements Guide

```
[Blue Button]        → Primary action (Save)
[Gray Button]        → Secondary action (Refresh)
[Dropdown ▼]         → Select from list
[Yellow Card]        → Warning/info
[Red Card]           → Error/alert
[Green Checkmark]    → Success
[X Button]           → Close/remove
```

---

## 💾 Data Persistence

-  Assignments saved to database
-  Viewable by waiters in their dashboard
-  Visible in assignment history
-  Audited and logged

---

## 🚀 Ready to Start?

```
Step 1: Go to http://localhost:5173/manager/dashboard
Step 2: Login
Step 3: Click Floor Assignment
Step 4: Start assigning!
```

**It's that simple!** 🎉

---

**Last Updated**: 2026-07-26  
**Version**: 1.0  
**Status**:  Fully Operational
