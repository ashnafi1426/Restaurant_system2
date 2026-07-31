# Waiter Management - Quick Reference Guide

## 🎯 Quick Links

| Feature | How To | Status |
|---------|--------|--------|
| **Register Waiter** | Click "Register New Waiter" button → Fill form → Submit |  Ready |
| **Edit Waiter** | Click ⋮ menu → Edit Details → Update → Submit |  Ready |
| **Delete Waiter** | Click ⋮ menu → Delete → Confirm |  Ready |
| **Search Waiter** | Type in search box (name/section) |  Ready |
| **Filter by Status** | Click status buttons (All/Active/On Break/Inactive) |  Ready |
| **Export to CSV** | Click "Export CSV" button |  Ready |
| **View Details** | Click waiter row (hover for preview) |  Ready |

---

## 📊 What You Can Do

### ✨ Register New Waiter
**Required Fields:**
- First Name
- Last Name
- Email
- Phone
- Password (min 8 chars)
- Section (e.g., "Restaurant A")
- Shift (Morning/Afternoon/Evening/Night)
- Experience Level (Junior/Senior/Head)
- Maximum Orders (5/8/10/15/20)

### 🔄 Edit Waiter
**Editable Fields:**
- Phone
- Section
- Shift
- Experience Level
- Maximum Orders
- Status (Active/Inactive)

**Read-only Fields:**
- First Name
- Last Name
- Email

### 🗑️ Delete Waiter
- Confirmation required
- Cannot be undone
- Waiter removed immediately

### 🔍 Search & Filter
- **Search**: Filters by name or section in real-time
- **Status Filters**: Click buttons to show only:
  - All: Show all waiters
  - Active: Ready for duty
  - On Break: Currently off
  - Inactive: Off duty

---

## 📈 Statistics Dashboard

Four stat cards show real-time counts:

```
┌─────────────┐  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐
│   TOTAL     │  │   ACTIVE    │  │  ON BREAK   │  │  INACTIVE   │
│     15      │  │     12      │  │      2      │  │      1      │
│   Staff     │  │   Ready     │  │   Off Duty  │  │  Off Duty   │
└─────────────┘  └─────────────┘  └─────────────┘  └─────────────┘
```

---

## 🎨 Status Indicators

| Status | Color | Icon | Meaning |
|--------|-------|------|---------|
| Active | 🟢 Green | ✓ | Ready for duty |
| On Break | 🟡 Amber | ⏸ | Currently off |
| Inactive | ⚪ Gray | ✕ | Off duty |

---

## 📱 Table Columns

| Column | Shows |
|--------|-------|
| **Staff Member** | Name + Avatar + ID |
| **Status** | Active/On Break/Inactive |
| **Section** | Restaurant section assignment |
| **Shift** | Morning/Afternoon/Evening/Night |
| **Experience** | Junior/Senior/Head |
| **Phone** | Contact number |
| **Actions** | Edit/Delete menu |

---

## ⚙️ Configuration

### Available Shifts
- 🌅 Morning (6:00 - 14:00)
- 🌤️ Afternoon (14:00 - 22:00)
- 🌆 Evening (18:00 - 02:00)
- 🌙 Night (22:00 - 06:00)

### Experience Levels
- 📚 Junior (Entry level)
- ⭐ Senior (Experienced)
- 👑 Head (Leadership role)

### Maximum Orders
- 5 orders (Low capacity)
- 8 orders (Medium capacity)
- 10 orders (Standard capacity)
- 15 orders (High capacity)
- 20 orders (Premium capacity)

---

## 🔐 Security Features

 **Password Requirements**
- Minimum 8 characters
- Required for all new accounts
- Encrypted in database

 **Email Validation**
- Unique email addresses
- Prevents duplicates
- Valid format required

 **Phone Validation**
- Valid phone format
- Can be updated anytime

 **Access Control**
- Only managers can manage waiters
- Role-based permissions
- Audit logging available

---

## 📋 Form Validation

### Create Form - All Required 
- [ ] First Name
- [ ] Last Name
- [ ] Email (valid format)
- [ ] Phone
- [ ] Password (8+ chars)
- [ ] Section
- [ ] Shift
- [ ] Experience Level
- [ ] Maximum Orders
- [ ] Status

### Edit Form - Partial Required 
- [ ] Phone (optional)
- [ ] Section (required)
- [ ] Shift (required)
- [ ] Experience Level (required)
- [ ] Maximum Orders (required)
- [ ] Status (required)

---

## 🎯 Common Tasks

### Task 1: Add New Waiter
```
1. Click "Register New Waiter" button
2. Fill in name, email, phone
3. Set password (min 8 chars)
4. Choose section and shift
5. Set experience level
6. Set maximum orders
7. Click "Register"
8. Success! 
```

### Task 2: Change Waiter Status
```
1. Click ⋮ menu next to waiter
2. Click "Edit Details"
3. Change Status (Active/Inactive)
4. Click "Update"
5. Done! 
```

### Task 3: Find Specific Waiter
```
1. Type waiter name in search box
2. Results filter in real-time
3. Click waiter row for details
4. Click ⋮ for actions
```

### Task 4: Export Staff List
```
1. (Optional) Apply filters
2. Click "Export CSV" button
3. File downloads automatically
4. Open in Excel/Sheets
```

---

## 🐛 Troubleshooting

### Problem: "Email already exists"
**Solution**: Email is already registered. Use a different email address.

### Problem: "Password too short"
**Solution**: Password must be at least 8 characters long.

### Problem: Waiter not appearing
**Solution**: 
- Refresh the page
- Check status filter is correct
- Try clearing search box

### Problem: Can't edit certain fields
**Solution**: Personal info (name, email) is read-only for security. Contact admin if changes needed.

### Problem: Export button not working
**Solution**:
- Check browser pop-up blocker
- Ensure browser has download permissions
- Try different file location

---

## 📞 API Details

### Create Waiter
```
POST /manager/waiters
Body: {
  first_name, last_name, email, phone, password,
  section, shift, experience_level, maximum_orders, status
}
Response: Waiter object with ID
```

### Get All Waiters
```
GET /manager/waiters
Response: Array of waiter objects
```

### Update Waiter
```
PUT /manager/waiters/{id}
Body: { field to update }
Response: Updated waiter object
```

### Delete Waiter
```
DELETE /manager/waiters/{id}
Response: Success message
```

---

## 🎬 Keyboard Shortcuts

| Key | Action |
|-----|--------|
| `Ctrl+K` | Focus search box |
| `Esc` | Close modal/menu |
| `Enter` | Submit form |

---

## 📊 Performance Tips

 **For Better Performance:**
- Close unused modals
- Use search to filter large lists
- Export data regularly
- Clear browser cache if slow

 **Optimal Page Size:**
- Shows 10 waiters per page
- Adjustable in settings
- Pagination provided

---

## 🎓 Learning Resources

**Backend**:
- `/server/app/Http/Controllers/Api/Manager/WaiterController.php`
- `/server/app/Models/Waiter.php`

**Frontend**:
- `/src/stores/manager/waiterStore.ts`
- `/src/services/managerService.ts`
- `/src/views/manager/ManagerWaiters.vue`
- `/src/components/manager/WaiterFormModal.vue`

**Database**:
- `waiters` table
- `users` table (relationships)

---

##  Verification Checklist

After changes, verify:
- [ ] Data saved to database
- [ ] Success message shown
- [ ] Table updated immediately
- [ ] Stats refreshed
- [ ] Can edit/delete/search

---

**Last Updated**: July 27, 2026
**Version**: 2.0
**Status**: Production Ready 
