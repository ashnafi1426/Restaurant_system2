# Waiter Management - Quick Testing Guide

## 🎯 What Was Fixed

The **Edit Modal** was not populating waiter data when you clicked the Edit button.

### The Problem
```
Click Edit → Modal Opens → Form Fields Stay Empty ❌
```

### The Solution
Fixed the prop name mismatch so data flows correctly:
```
Click Edit → Modal Opens → Form Populates with Data 
```

---

##  How to Test Each Feature

### 1️⃣ TEST: Register New Waiter

**Steps**:
1. Navigate to **Manager → Waiters** page
2. Click **"Register New Waiter"** button (top right)
3. Modal should open with title: **"Register New Waiter"**
4. Fill in the form:
   - First Name: John
   - Last Name: Doe
   - Email: john@example.com
   - Phone: +1-555-0123
   - Section: Restaurant A
   - Shift: Morning
   - Experience: Senior
   - Max Orders: 10
   - Status: Active
5. Click **"Register"** button
6. Should see:  Success message "John Doe has been created..."
7. New waiter appears in table at bottom

**Expected Result**: ✓ New waiter added to list

---

### 2️⃣ TEST: Edit Waiter (THE MAIN FIX)

**Steps**:
1. In the waiters table, find any waiter
2. Click the **⋮ (three dots)** menu on the right
3. Click **"Edit Details"**
4. Modal opens with title: **"Edit Waiter"**

**VERIFY THE FIX** ← This is what was broken before:
- [ ] First Name field shows waiter's name (disabled/read-only)
- [ ] Last Name field shows waiter's last name (disabled/read-only)
- [ ] Email field shows email (disabled/read-only)
- [ ] Phone field shows phone number (editable)
- [ ] Section field shows current section (editable)
- [ ] Shift field shows current shift (editable)
- [ ] Experience Level shows current level (editable)
- [ ] Status shows current status (editable)
- [ ] Max Orders shows current limit (editable)

**Edit Test**:
1. Change Section to "Restaurant B"
2. Change Shift to "Afternoon"
3. Click **"Update"** button
4. Should see:  Success message
5. Go back to table - verify changes are reflected

**Expected Result**: ✓ All form fields populated correctly, edits saved

---

### 3️⃣ TEST: Delete Waiter

**Steps**:
1. In the waiters table, find a waiter to delete (preferably one you just added)
2. Click the **⋮ (three dots)** menu
3. Click **"Delete"**
4. Confirmation dialog appears: "Are you sure you want to delete [Name]?"
5. Click **"OK"** to confirm
6. Should see:  Success message "[Name] has been deleted"
7. Waiter disappears from table

**Expected Result**: ✓ Waiter removed from list

---

### 4️⃣ TEST: Data Loading & Display

**Steps**:
1. Navigate to Waiters page
2. Check the table columns:

| Column | Expected | Issue If Shows "N/A" |
|--------|----------|-------------------|
| Name | First + Last name | ✓ Should work |
| Status | active, inactive, on_break | ✓ Should work |
| Section | Restaurant A, Table 1, etc. | Database might be empty |
| Shift | Morning, Afternoon, Evening, Night | Database might be empty |
| Experience | Junior, Senior, Head | ✓ Should work |
| Phone | Phone number | ✓ Should work |

**If Section/Shift show "N/A"**:
- This means database records don't have section/shift assigned
- Not a code problem, a data problem
- Should be populated when creating new waiters

**Expected Result**: ✓ All fields display correctly (section/shift should have values)

---

### 5️⃣ TEST: Stats Cards Update

**Steps**:
1. Note the stats at top: Total Staff, Active, On Break, Inactive
2. Add a new waiter with Status: Active
3. Check if "Active" count increased by 1
4. Delete that waiter
5. Check if "Total Staff" count decreased by 1

**Expected Result**: ✓ Stats update automatically

---

## 🔍 Browser Console Checks

Open Browser Developer Tools (F12) and go to **Console** tab:

**Look for logs like**:
```
[WaiterStore] Loading waiters...
[WaiterStore] API response received: [...]
[ManagerService] getWaiters() response: [...]
[WaiterFormModal] Loading edit data: {...}
```

**Errors to watch for**:
```
❌ undefined is not a function
❌ Cannot read property 'section' of undefined
❌ Invalid prop type
```

---

## 📋 What Each Fix Does

| Issue | What Was Wrong | What's Fixed |
|-------|---|---|
| Edit Modal Empty | Prop name mismatch (`:selected-waiter` vs `waiterData`) | ✓ Correct prop name passed |
| Modal Doesn't Populate | Modal wasn't receiving waiter data | ✓ Data flows correctly to modal |
| isEditMode Error | Template used `isEditMode` but it wasn't defined | ✓ Now uses `props.isEditMode` |
| Duplicate emit | `emit` declared twice in script | ✓ Removed duplicate |

---

## 🎬 Full Testing Flow (5 minutes)

```
1. Load page
   ↓
2. Add new waiter
   ↓
3. Edit that waiter (VERIFY FIX HERE)
   ↓
4. Check table shows updated data
   ↓
5. Delete waiter
   ↓
6. Confirm removed from list
   ↓
 DONE - All features working
```

---

## 🐛 If Something Goes Wrong

### Symptom: Edit modal still doesn't populate

**Try**:
1. Hard refresh page: `Ctrl+Shift+R`
2. Clear browser cache (DevTools → Storage → Clear All)
3. Check browser console for errors
4. Check Network tab - is API call successful?

### Symptom: Form fields disabled in create mode

**This shouldn't happen** - they should only be disabled in edit mode
- Verify modal has `isEditMode="false"` prop

### Symptom: Delete button does nothing

**Try**:
1. Check Network tab for API response
2. Look for error in console
3. Verify waiter ID is valid
4. Try refresh and retry

---

## 📞 Quick Reference

### Files Changed
- `src/views/manager/ManagerWaiters.vue` - Updated prop name
- `src/components/manager/WaiterFormModal.vue` - Fixed prop usage

### API Endpoints Used
- `GET /manager/waiters` - Load all waiters
- `POST /manager/waiters` - Create new waiter
- `PUT /manager/waiters/{id}` - Update waiter
- `DELETE /manager/waiters/{id}` - Delete waiter

### Key Properties
- `isEditMode` - Boolean, tells modal if in edit or create mode
- `waiterData` - Waiter object passed to modal for edit
- `selectedWaiter` - Currently selected waiter in main page

---

**Build Status**:  Build passed successfully  
**Ready to Test**:  YES  
**Last Updated**: July 27, 2026

