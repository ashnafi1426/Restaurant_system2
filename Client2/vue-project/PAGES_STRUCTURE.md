# Restaurant Management System - Pages Structure

## Overview
Complete list of all pages/views in the application organized by role and functionality.

---

## 🏠 Public Pages

### Landing Page
- **File:** `src/views/LandingPage.vue`
- **Route:** `/`
- **Description:** Luxury hotel landing page with navbar, hero section, rooms showcase, amenities, gallery, heritage kitchen, testimonials, contact form, and footer
- **Status:** ✅ Newly Created

### Login Page
- **File:** `src/views/LoginView.vue`
- **Route:** `/login`
- **Description:** Authentication page for all users with email/password validation
- **Features:** Role-based login with error handling

---

## 👨‍💼 Admin Panel Pages (`/admin`, `/users`, `/rooms`, `/room-types`, `/menu-management`, `/orders`)

### 1. Admin Dashboard
- **File:** `src/views/Admin/AdminDashboard.vue`
- **Route:** `/admin`
- **Role:** Admin
- **Features:**
  - Real-time statistics (Total Users, Active Staff, Occupancy Rate, Today's Revenue)
  - Monthly Revenue Chart
  - Room Status Chart
  - Recent Reservations Table
  - Staff Activity Widget
  - Maintenance Alerts
  - Quick action buttons (New Booking, Add User, Add Room, Add Room Type)

### 2. User Management
- **List Page:** `src/views/Admin/users/UserList.vue` → `/users`
- **Create Page:** `src/views/Admin/users/CreateUser.vue` → `/users/create`
- **Edit Page:** `src/views/Admin/users/EditUser.vue` → `/users/:id/edit`
- **Features:** User CRUD operations, role assignment

### 3. Room Management
- **List Page:** `src/views/Admin/rooms/RoomList.vue` → `/rooms`
- **Create Page:** `src/views/Admin/rooms/CreateRoom.vue` → `/rooms/create`
- **View Page:** `src/views/Admin/rooms/ViewRoom.vue` → `/rooms/:id`
- **Edit Page:** `src/views/Admin/rooms/EditRoom.vue` → `/rooms/:id/edit`
- **Features:** Room CRUD, room details, availability management

### 4. Room Type Management
- **List Page:** `src/views/Admin/room-types/index.vue` → `/room-types`
- **Create Page:** `src/views/Admin/room-types/create.vue` → `/room-types/create`
- **View Page:** `src/views/Admin/room-types/show.vue` → `/room-types/:id`
- **Edit Page:** `src/views/Admin/room-types/edit.vue` → `/room-types/:id/edit`
- **Features:** Room type CRUD operations

### 5. Menu Management
- **List Page:** `src/views/Admin/menu/MenuView.vue` → `/admin/menu` or `/menu-management`
- **Add Item:** `src/views/Admin/menu/AddMenuItemView.vue` → `/admin/menu/add`
- **Features:** Menu items CRUD, category management, image uploads

### 6. Order Management
- **List Page:** `src/views/Admin/order/OrderManagment.vue` → `/orders`
- **Add Page:** `src/views/Admin/order/AddOrder.vue` → `/orders/create`, `/orders/:id/edit`, `/orders/:id/view`
- **Features:** Order creation, editing, viewing, order tracking

---

## 🔔 Receptionist Pages (`/receptionist`, `/guests`, `/reservations`, `/check-in`)

### 1. Reception Dashboard
- **File:** `src/views/receptionist/reception/ReceptionDashboard.vue`
- **Route:** `/receptionist`
- **Role:** Receptionist
- **Features:** Today's arrivals, check-in overview, key desk operations

### 2. Guest Management
- **List Page:** `src/views/receptionist/guest/guestList.vue` → `/guests`
- **Create Page:** `src/views/receptionist/guest/createGuest.vue` → `/guests/create`
- **Detail Page:** `src/views/receptionist/guest/guestDetail.vue` → `/guests/:id`
- **Edit Page:** `src/views/receptionist/guest/editGuest.vue` → `/guests/:id/edit`
- **Features:** Guest profile management, contact info, stay history

### 3. Reservation Management
- **List Page:** `src/views/receptionist/reservation/ReservationListpage.vue` → `/reservations`
- **Create Page:** `src/views/receptionist/reservation/ReservationCreate.vue` → `/reservations/create`
- **Edit Page:** `src/views/receptionist/reservation/ReservationEdit.vue` → `/reservations/:id/edit`
- **Features:** Reservation CRUD, date management, room availability

### 4. Check-In Management
- **View Page:** `src/views/receptionist/checkIn/checkInView.vue` → `/check-in`
- **Features:** Guest check-in process, room assignment, status tracking

---

## 👨‍🍳 Kitchen/Chef Pages (`/chef`, `/chef/*`)

### 1. Kitchen Dashboard
- **File:** `src/views/kitchen/kitchenDashboard.vue`
- **Route:** `/chef`
- **Role:** Chef
- **Features:** Order overview, kitchen stats, efficiency metrics

### 2. Food Orders
- **File:** `src/views/kitchen/FoodOrdersView.vue`
- **Route:** `/chef/food-orders`
- **Features:** All food orders in one view

### 3. Pending Orders
- **File:** `src/views/kitchen/PendingOrdersView.vue`
- **Route:** `/chef/pending-orders`
- **Features:** Orders awaiting preparation

### 4. Preparing Orders
- **File:** `src/views/kitchen/PreparingOrdersView.vue`
- **Route:** `/chef/preparing-orders`
- **Features:** Orders currently being prepared

### 5. Served Orders
- **File:** `src/views/kitchen/ServedOrdersView.vue`
- **Route:** `/chef/served-orders`
- **Features:** Completed and served orders history

---

## 💳 Cashier Pages

### 1. Cashier Dashboard
- **File:** `src/views/Cashier/CashierDashboard.vue`
- **Route:** `/cashier`
- **Role:** Cashier
- **Features:** Payment processing, billing, transaction history

---

## 📊 Manager Pages

### 1. Manager Dashboard
- **File:** `src/views/manager/ManagerDashboard.vue`
- **Route:** `/manager`
- **Role:** Manager
- **Features:** Performance analytics, reports, staff management overview

---

## 👥 Guest Portal Pages (Public Access)

### 1. Home Page
- **File:** `src/views/guest/Home.vue`
- **Route:** (Guest portal home)
- **Features:** Homepage for guests

### 2. Rooms
- **File:** `src/views/guest/Room.vue`
- **Route:** (Guest room listing)
- **Features:** Available rooms showcase

### 3. Reservation
- **File:** `src/views/guest/Reservation.vue`
- **Route:** (Guest reservation booking)
- **Features:** Self-service booking

### 4. About
- **File:** `src/views/guest/About.vue`
- **Route:** (About page)
- **Features:** Hotel information

### 5. Contact
- **File:** `src/views/guest/Contact.vue`
- **Route:** (Contact page)
- **Features:** Contact information and inquiry form

---

## 📁 Directory Structure Summary

```
src/views/
├── LandingPage.vue (NEW - Luxury Hotel Landing)
├── LoginView.vue
├── Admin/
│   ├── AdminDashboard.vue
│   ├── menu/
│   │   ├── MenuView.vue
│   │   └── AddMenuItemView.vue
│   ├── order/
│   │   ├── OrderManagment.vue
│   │   └── AddOrder.vue
│   ├── room-types/
│   │   ├── index.vue
│   │   ├── create.vue
│   │   ├── edit.vue
│   │   └── show.vue
│   ├── rooms/
│   │   ├── RoomList.vue
│   │   ├── CreateRoom.vue
│   │   ├── EditRoom.vue
│   │   └── ViewRoom.vue
│   └── users/
│       ├── UserList.vue
│       ├── CreateUser.vue
│       └── EditUser.vue
├── Cashier/
│   └── CashierDashboard.vue
├── guest/
│   ├── Home.vue
│   ├── Room.vue
│   ├── Reservation.vue
│   ├── About.vue
│   └── Contact.vue
├── kitchen/
│   ├── kitchenDashboard.vue
│   ├── FoodOrdersView.vue
│   ├── PendingOrdersView.vue
│   ├── PreparingOrdersView.vue
│   └── ServedOrdersView.vue
├── manager/
│   └── ManagerDashboard.vue
└── receptionist/
    ├── checkIn/
    │   └── checkInView.vue
    ├── guest/
    │   ├── guestList.vue
    │   ├── createGuest.vue
    │   ├── editGuest.vue
    │   └── guestDetail.vue
    └── reservation/
        ├── ReservationListpage.vue
        ├── ReservationCreate.vue
        └── ReservationEdit.vue
```

---

## 🎯 Key Features by Role

| Role | Dashboard | View Rooms | Manage Guests | Reservations | Check-in | Menu | Orders | Staff |
|------|-----------|-----------|---------------|--------------|----------|------|--------|-------|
| Admin | ✅ | ✅ | N/A | View | N/A | ✅ | ✅ | ✅ |
| Receptionist | ✅ | View | ✅ | ✅ | ✅ | View | View | N/A |
| Chef | ✅ | N/A | N/A | N/A | N/A | ✅ | ✅ | N/A |
| Cashier | ✅ | View | View | View | N/A | View | ✅ | N/A |
| Manager | ✅ | View | View | View | View | View | View | ✅ |

---

## 🎨 Landing Page Components (NEW)

The new luxury hotel landing page includes:
- **LandingNavbar:** Sticky navigation with smooth scrolling
- **HeroSection:** Full-screen hero with CTA buttons
- **ArtOfRestSection:** Room showcase with cards
- **CuratedComfortSection:** Amenities grid (spa, dining, pool, etc.)
- **GrandeurGallerySection:** Image gallery with hover effects
- **HeritageKitchenSection:** About our restaurant with image grid
- **TestimonialsSection:** Guest testimonials with carousel
- **ContactSection:** Contact form and information
- **Footer:** Links and social media

---

## 🔐 Authentication Flow

1. User lands on Landing Page (`/`)
2. Click "BOOK NOW" or navigate to Login (`/login`)
3. After authentication, redirect to role-specific dashboard:
   - Admin → `/admin`
   - Receptionist → `/receptionist`
   - Chef → `/chef`
   - Cashier → `/cashier`
   - Manager → `/manager`

---

## 📋 Total Page Count

- **Total Views:** 40+ pages
- **Components:** 50+ reusable components
- **Routes:** 50+ defined routes
- **Roles Supported:** 5 (Admin, Receptionist, Chef, Cashier, Manager)

