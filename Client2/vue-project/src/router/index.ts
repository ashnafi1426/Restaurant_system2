import { createRouter, createWebHistory } from 'vue-router'
import AboutPage from '../views/guest/About.vue'
import roomPage from '../views/guest/Room.vue'
// import RestaurantPage from '../views/guest/Reservation.vue'
import GuestHome from '../views/guest/Home.vue'
import contactPage from '../views/guest/Contact.vue'
import GalleryPage from '../views/guest/Gallary.vue'
// import QROrderingPage from '../views/guest/QROrderingPage.vue'
import LoginView from '../views/LoginView.vue'
import ActivationPage from '../views/ActivationPage.vue'
import AdminDashboard from '../views/Admin/AdminDashboard.vue'
import orderManagment from '../views/Admin/order/OrderManagment.vue'
import ReceptionDashboard from '../views/receptionist/reception/ReceptionDashboard.vue'
import MenuManagement from '../views/Admin/menu/MenuView.vue'
import AddMenuItemView from '../views/Admin/menu/AddMenuItemView.vue'
import AddCategoryView from '../views/Admin/menu/AddCategoryView.vue'
import CashierDashboard from '../views/Cashier/CashierDashboard.vue'
import kitchenDashboard from '../views/kitchen/kitchenDashboard.vue'
import FoodOrdersView from '../views/kitchen/FoodOrdersView.vue'
import PendingOrdersView from '../views/kitchen/PendingOrdersView.vue'
import PreparingOrdersView from '../views/kitchen/PreparingOrdersView.vue'
import ServedOrdersView from '../views/kitchen/ServedOrdersView.vue'
import UserList from '../views/Admin/users/UserList.vue'
import CreateUser from '../views/Admin/users/CreateUser.vue'
import EditUser from '../views/Admin/users/EditUser.vue'
import RoomList from '../views/Admin/rooms/RoomList.vue'
import CreateRoom from '../views/Admin/rooms/CreateRoom.vue'
import EditRoom from '../views/Admin/rooms/EditRoom.vue'
import ViewRoom from '../views/Admin/rooms/ViewRoom.vue'
import RoomTypeList from '../views/Admin/room-types/index.vue'
import CreateRoomType from '../views/Admin/room-types/create.vue'
import EditRoomType from '../views/Admin/room-types/edit.vue'
import ViewRoomType from '../views/Admin/room-types/show.vue'
import GuestList from '../views/receptionist/guest/guestList.vue'
import CreateGuest from '../views/receptionist/guest/createGuest.vue'
import EditGuest from '../views/receptionist/guest/editGuest.vue'
import GuestDetails from '../views/receptionist/guest/guestDetail.vue'
import ReservationListPage from '../views/receptionist/reservation/ReservationListpage.vue'
import ReservationCreatePage from '../views/receptionist/reservation/ReservationCreate.vue'
import ReservationEditPage from '../views/receptionist/reservation/ReservationEdit.vue'
import CheckInView from '../views/receptionist/checkIn/checkInView.vue'
import CheckOutView from '../views/receptionist/checkOut/CheckOutView.vue'
import ReportsPage from '../views/receptionist/reports/ReportsPage.vue'
import AddOrder from '@/views/Admin/order/AddOrder.vue'
import QRMenu from '../views/guest/QRMenu.vue'
import PaymentSuccessPage from '../views/payment/PaymentSuccessPage.vue'
import PaymentFailedPage from '../views/payment/PaymentFailedPage.vue'
import PaymentPendingPage from '../views/payment/PaymentPendingPage.vue'
import CheckoutPage from '../views/payment/CheckoutPage.vue'
import OrderPaymentSuccessPage from '../views/payment/OrderPaymentSuccessPage.vue'
import managerRoutes from './managerRouter.ts'
import waiterRoutes from './waiterRouter'
import cashierRoutes from './cashierRouter'
import reviewRoutes from './reviewRouter'
import RoleManagementView from '@/views/Admin/rbac/RoleManagementView.vue'
import PermissionManagementView from '@/views/Admin/rbac/PermissionManagementView.vue'
import RolePermissionMatrixView from '@/views/Admin/RolePermissionManagement.vue'
import UserRoleAssignmentView from '@/views/Admin/rbac/UserRoleAssignmentView.vue'
import TemporaryRoleAssignmentView from '@/views/Admin/rbac/TemporaryRoleAssignmentView.vue'
import AuditLogView from '@/views/Admin/rbac/AuditLogView.vue'
import UnauthorizedView from '@/views/UnauthorizedView.vue'
import { useAuthStore } from '../stores/auth'
const router = createRouter({
  history: createWebHistory(),
  routes: [
    {
      path: '/',
      name: 'home',
      component: GuestHome,
      beforeEnter: (to, from, next) => {
        const token = localStorage.getItem('token')
        const user = JSON.parse(localStorage.getItem('user') || 'null')

        // If user is authenticated, redirect dynamically to their role path
        if (token && user?.role) {
          return next(`/${user.role}`)
        }

        // If not authenticated, show guest home page
        next()
      },
    },
    {
      path: '/activate/:token',
      name: 'activation',
      component: ActivationPage,
      meta: { public: true }
    },
    {
      path: '/forgot-password',
      name: 'forgot-password',
      component: () => import('../views/ForgotPasswordPage.vue'),
      meta: { public: true }
    },
    {
      path: '/reset-password',
      name: 'reset-password',
      component: () => import('../views/ResetPasswordPage.vue'),
      meta: { public: true }
    },
    // {
    //   path: '/reservation',
    //   name: 'guest-reservation',
    //   component: RestaurantPage,
    // },
    // {
    //   path: '/my-reservation',
    //   name: 'my-reservations',
    //   component: RestaurantPage,
    // },
    {
      path: '/rooms',
      name: 'guest-rooms',
      component: roomPage,
    },
    {
      path: '/roomsPage',
      name: 'room',
      component: roomPage,
    },
    {
      path: '/about',
      name: 'About',
      component: AboutPage,
    },

    {
      path: '/contact',
      name: 'contact',
      component: contactPage,
    },
    {
      path: '/gallery',
      name: 'gallery',
      component: GalleryPage,
    },
    {
      path: '/login',
      name: 'login',
      component: LoginView,
    },
    {
      path: '/admin',
      name: 'admin-dashboard-new',
      component: AdminDashboard,
      meta: {
        requiresAuth: true,
        role: 'admin',
      },
    },
    {
      path: '/admin/profile',
      name: 'admin-profile',
      component: () => import('../views/Admin/AdminProfile.vue'),
      meta: {
        requiresAuth: true,
        role: 'admin',
        title: 'Admin Profile',
      },
    },
    {
      path: '/menu-management',
      name: 'menu-management',
      component: MenuManagement,
      meta: {
        requiresAuth: true,
        permission: 'menu.view',
      },
    },
    {
      path: '/admin/menu',
      name: 'admin-menu',
      component: MenuManagement,
      meta: {
        requiresAuth: true,
        permission: 'menu.view',
      },
    },
    {
      path: '/admin/menu/add',
      name: 'admin-menu-add',
      component: AddMenuItemView,
      meta: {
        requiresAuth: true,
        permission: 'menu.create',
      },
    },
    {
      path: '/admin/menu/add-category',
      name: 'admin-menu-add-category',
      component: AddCategoryView,
      meta: {
        requiresAuth: true,
        permission: 'menu.create',
      },
    },
    {
      path: '/receptionist',
      name: 'receptionist-dashboard',
      component: ReceptionDashboard,
      meta: {
        requiresAuth: true,
        permission: 'dashboard.view',
      },
    },
    {
      path: '/receptionist/profile',
      name: 'receptionist-profile',
      component: () => import('../views/receptionist/ReceptionistProfile.vue'),
      meta: {
        requiresAuth: true,
        title: 'Receptionist Profile',
      },
    },
    {
      path: '/cashier',
      name: 'cashier-dashboard',
      component: CashierDashboard,
      meta: {
        requiresAuth: true,
        permission: 'dashboard.view',
      },
    },
    {
      path: '/chef',
      name: 'chef-dashboard',
      component: kitchenDashboard,
      meta: {
        requiresAuth: true,
        permission: 'dashboard.view',
      },
    },
    {
      path: '/chef/profile',
      name: 'chef-profile',
      component: () => import('../views/kitchen/ChefProfile.vue'),
      meta: {
        requiresAuth: true,
        title: 'Chef Profile',
      },
    },
    {
      path: '/chef/food-orders',
      name: 'chef-food-orders',
      component: FoodOrdersView,
      meta: {
        requiresAuth: true,
        permission: 'kitchen.view',
      },
    },
    {
      path: '/chef/pending-orders',
      name: 'chef-pending-orders',
      component: PendingOrdersView,
      meta: {
        requiresAuth: true,
        permission: 'kitchen.view',
      },
    },
    {
      path: '/chef/preparing-orders',
      name: 'chef-preparing-orders',
      component: PreparingOrdersView,
      meta: {
        requiresAuth: true,
        permission: 'kitchen.prepare',
      },
    },
    {
      path: '/chef/served-orders',
      name: 'chef-served-orders',
      component: ServedOrdersView,
      meta: {
        requiresAuth: true,
        permission: 'kitchen.mark_ready',
      },
    },

    {
      path: '/users',
      component: UserList,
      meta: {
        requiresAuth: true,
        permission: 'users.view',
      },
    },

    {
      path: '/users/create',
      component: CreateUser,
      meta: {
        requiresAuth: true,
        permission: 'users.create',
      },
    },

    {
      path: '/users/:id/edit',
      component: EditUser,
      meta: {
        requiresAuth: true,
        permission: 'users.update',
      },
    },
    {
      path: '/admin/rooms',
      name: 'admin-rooms',
      component: RoomList,
      meta: {
        requiresAuth: true,
        permission: 'rooms.view',
      },
    },
    {
      path: '/rooms/create',
      component: CreateRoom,
      meta: {
        requiresAuth: true,
        permission: 'rooms.create',
      },
    },
    {
      path: '/rooms/:id',
      component: ViewRoom,
      meta: {
        requiresAuth: true,
        permission: 'rooms.view',
      },
    },
    {
      path: '/rooms/:id/edit',
      component: EditRoom,
      meta: {
        requiresAuth: true,
        permission: 'rooms.update',
      },
    },
    {
      path: '/room-types',
      component: RoomTypeList,
      meta: {
        requiresAuth: true,
        permission: 'rooms.view',
      },
    },

    {
      path: '/room-types/create',
      component: CreateRoomType,
      meta: {
        requiresAuth: true,
        permission: 'rooms.create',
      },
    },

    {
      path: '/room-types/:id',
      component: ViewRoomType,
      meta: {
        requiresAuth: true,
        permission: 'rooms.view',
      },
    },

    {
      path: '/room-types/:id/edit',
      component: EditRoomType,
      meta: {
        requiresAuth: true,
        permission: 'rooms.update',
      },
    },
    {
      path: '/guests',
      component: GuestList,
      meta: {
        requiresAuth: true,
        permission: 'guests.view',
      },
    },
    {
      path: '/guests/create',
      component: CreateGuest,
      meta: {
        requiresAuth: true,
        permission: 'guests.create',
      },
    },
    {
      path: '/guests/:id',
      component: GuestDetails,
      meta: {
        requiresAuth: true,
        permission: 'guests.view',
      },
    },
    {
      path: '/guests/:id/edit',
      component: EditGuest,
      meta: {
        requiresAuth: true,
        permission: 'guests.update',
      },
    },
    {
      path: '/reservations',
      name: 'reservations.index',
      component: ReservationListPage,
      meta: {
        title: 'Reservations',
        requiresAuth: true,
        permission: 'reservations.view',
      },
    },

    {
      path: '/reservations/create',
      name: 'reservations.create',
      component: ReservationCreatePage,
      meta: {
        title: 'Create Reservation',
        requiresAuth: true,
        permission: 'reservations.create',
      },
    },

    {
      path: '/reservations/:id/edit',
      name: 'reservations.edit',
      component: ReservationEditPage,
      meta: {
        title: 'Edit Reservation',
        requiresAuth: true,
        permission: 'reservations.update',
      },
    },
    {
      path: '/check-in',
      name: 'check-ins',
      component: CheckInView,
      meta: {
        title: 'Check In Management',
        requiresAuth: true,
        permission: 'reservations.checkin',
      },
    },
    {
      path: '/check-out',
      name: 'check-outs',
      component: CheckOutView,
      meta: {
        title: 'Check Out Management',
        requiresAuth: true,
        permission: 'reservations.checkout',
      },
    },
    {
      path: '/reports',
      name: 'reports',
      component: ReportsPage,
      meta: {
        title: 'Reception Reports',
        requiresAuth: true,
        permission: 'reports.view',
      },
    },

    {
      path: '/orders',
      name: 'orders',
      component: orderManagment,
      meta: {
        title: 'Order Management',
        requiresAuth: true,
        permission: 'orders.view',
      },
    },
    {
      path: '/orders/create',
      name: 'create-order',
      component: AddOrder,
      meta: {
        title: 'Create Order',
        requiresAuth: true,
        permission: 'orders.create',
      },
    },
    {
      path: '/orders/:id/edit',
      name: 'edit-order',
      component: AddOrder,
      meta: {
        title: 'Edit Order',
        requiresAuth: true,
        permission: 'orders.update',
      },
    },
    {
      path: '/orders/:id/view',
      name: 'view-order',
      component: AddOrder,
      meta: {
        title: 'View Order',
        requiresAuth: true,
        permission: 'orders.view',
      },
    },
    {
      path: '/order/:qrToken',
      name: 'guest-qr-order',
      component: QRMenu,
      meta: {
        title: 'Room Service Menu',
        requiresAuth: false,
      },
    },
    {
      path: '/restaurant-order/:qrToken',
      name: 'restaurant-qr-order',
      component: QRMenu,
      meta: {
        title: 'Restaurant Menu',
        requiresAuth: false,
      },
    },
    {
      path: '/menu',
      name: 'guest-qr-menu',
      component: QRMenu,
      meta: {
        title: 'Restaurant Menu',
        requiresAuth: false,
      },
    },

    // ============================================================================
    // Payment Routes
    // ============================================================================
    // Payment checkout page - Initialize payment
    {
      path: '/payment/checkout',
      name: 'payment-checkout',
      component: CheckoutPage,
      meta: {
        title: 'Payment Checkout',
        requiresAuth: false,
      },
    },

    // Payment success page - After successful payment (Room Booking)
    {
      path: '/payment/success',
      name: 'payment-success',
      component: PaymentSuccessPage,
      meta: {
        title: 'Payment Successful',
        requiresAuth: false,
      },
    },

    // Order payment success page - After successful order payment
    {
      path: '/order/payment/success',
      name: 'order-payment-success',
      component: OrderPaymentSuccessPage,
      meta: {
        title: 'Order Payment Successful',
        requiresAuth: false,
      },
    },

    // Payment failed page - If payment failed
    {
      path: '/payment/failed',
      name: 'payment-failed',
      component: PaymentFailedPage,
      meta: {
        title: 'Payment Failed',
        requiresAuth: false,
      },
    },

    // Payment pending page - Payment is being processed
    {
      path: '/payment/pending',
      name: 'payment-pending',
      component: PaymentPendingPage,
      meta: {
        title: 'Payment Pending',
        requiresAuth: false,
      },
    },

    // ============================================================================
    // Dynamic RBAC Routes & Unauthorized View
    // ============================================================================
    {
      path: '/unauthorized',
      name: 'unauthorized',
      component: UnauthorizedView,
      meta: { requiresAuth: false, title: '403 Unauthorized' },
    },
    {
      path: '/admin/hotels',
      name: 'admin-hotels',
      component: () => import('@/views/Admin/hotels/HotelManagementView.vue'),
      meta: { requiresAuth: true, superAdminOnly: true, title: 'Hotel Management' },
    },
    {
      path: '/admin/hotel-admins',
      name: 'admin-hotel-admins',
      component: () => import('@/views/Admin/hotels/HotelAdminManagementView.vue'),
      meta: { requiresAuth: true, superAdminOnly: true, title: 'Hotel Admins' },
    },
    {
      path: '/admin/platform-users',
      name: 'admin-platform-users',
      component: () => import('@/views/Admin/hotels/PlatformUsersView.vue'),
      meta: { requiresAuth: true, superAdminOnly: true, title: 'All Users & Staff' },
    },
    {
      path: '/admin/roles',
      name: 'admin-roles',
      component: RoleManagementView,
      meta: { requiresAuth: true, permission: 'roles.view', title: 'Role Management' },
    },
    {
      path: '/admin/permissions',
      name: 'admin-permissions',
      component: PermissionManagementView,
      meta: { requiresAuth: true, permission: 'permissions.view', title: 'Permission Catalog' },
    },
    {
      path: '/admin/permission-matrix',
      name: 'admin-permission-matrix',
      component: RolePermissionMatrixView,
      meta: { requiresAuth: true, permission: 'roles.assign_permissions', title: 'Permission Matrix' },
    },
    {
      path: '/admin/user-roles',
      name: 'admin-user-roles',
      component: UserRoleAssignmentView,
      meta: { requiresAuth: true, permission: 'users.update', title: 'User Roles' },
    },
    {
      path: '/admin/temporary-roles',
      name: 'admin-temporary-roles',
      component: TemporaryRoleAssignmentView,
      meta: { requiresAuth: true, superAdminOnly: true, title: 'Temporary Roles' },
    },
    {
      path: '/admin/audit-logs',
      name: 'admin-audit-logs',
      component: AuditLogView,
      meta: { requiresAuth: true, superAdminOnly: true, title: 'Audit Logs' },
    },

    ...managerRoutes,
    ...reviewRoutes,
    ...waiterRoutes,
    ...cashierRoutes,
    {
      path: '/:pathMatch(.*)*',
      redirect: '/login',
    },
  ],
})

router.beforeEach(async (to) => {
  const authStore = useAuthStore()
  const token = authStore.token || localStorage.getItem('token')

  // Unauthenticated route protection
  if (to.meta.requiresAuth && !token) {
    return '/login'
  }

  // Hydrate auth store user & permissions if token exists and state not initialized
  if (token && (!authStore.user || !authStore.isInitialized)) {
    try {
      await authStore.initializeAuth()
    } catch (err) {
      console.error('[RBAC GUARD] Session initialization error:', err)
    }
  }

  // Allow public routes
  if (!to.meta.requiresAuth && !to.meta.permission && !to.meta.role && !to.meta.roles) {
    return true
  }

  // 0. Super Admin exclusivity check (Platform / Multi-hotel routes strictly for Super Admin)
  if (to.meta.superAdminOnly && !authStore.isPlatformAdmin) {
    console.warn(`[RBAC GUARD] Access denied to ${to.path}. Platform Super Admin privileges required.`)
    return '/admin'
  }

  // SUPER ADMIN OVERRIDE: Platform Super Admin user has unlimited access to all system routes
  if (authStore.isPlatformAdmin) {
    return true
  }

  // 1. Permission authorization check
  if (to.meta.permission && typeof to.meta.permission === 'string') {
    if (authStore.can(to.meta.permission)) {
      return true
    } else {
      console.warn(`[RBAC GUARD] Access denied to ${to.path}. Required permission: ${to.meta.permission}`)
      return '/unauthorized'
    }
  }

  // 2. Single role authorization check
  if (to.meta.role && typeof to.meta.role === 'string') {
    if (authStore.hasRole(to.meta.role)) {
      return true
    } else {
      console.warn(`[RBAC GUARD] Access denied to ${to.path}. Required role: ${to.meta.role}`)
      return '/unauthorized'
    }
  }

  // 3. Multiple roles authorization check (to.meta.roles array)
  if (to.meta.roles && Array.isArray(to.meta.roles)) {
    const hasAnyRequiredRole = (to.meta.roles as string[]).some(r => authStore.hasRole(r))
    if (hasAnyRequiredRole) {
      return true
    } else {
      console.warn(`[RBAC GUARD] Access denied to ${to.path}. Required one of roles:`, to.meta.roles)
      return '/unauthorized'
    }
  }

  return true
})

export default router
