import { createRouter, createWebHistory } from 'vue-router'
const AboutPage = () => import('../views/guest/About.vue')
const roomPage = () => import('../views/guest/Room.vue')
const GuestHome = () => import('../views/guest/Home.vue')
const contactPage = () => import('../views/guest/Contact.vue')
const GalleryPage = () => import('../views/guest/Gallary.vue')
const LoginView = () => import('../views/LoginView.vue')
const ActivationPage = () => import('../views/ActivationPage.vue')
const AdminDashboard = () => import('../views/Admin/AdminDashboard.vue')
const orderManagment = () => import('../views/Admin/order/OrderManagment.vue')
const ReceptionDashboard = () => import('../views/receptionist/reception/ReceptionDashboard.vue')
const MenuManagement = () => import('../views/Admin/menu/MenuView.vue')
const AddMenuItemView = () => import('../views/Admin/menu/AddMenuItemView.vue')
const AddCategoryView = () => import('../views/Admin/menu/AddCategoryView.vue')
const CashierDashboard = () => import('../views/Cashier/CashierDashboard.vue')
const KitchenDashboard = () => import('../views/kitchen/KitchenDashboard.vue')
const FoodOrdersView = () => import('../views/kitchen/FoodOrdersView.vue')
const PendingOrdersView = () => import('../views/kitchen/PendingOrdersView.vue')
const PreparingOrdersView = () => import('../views/kitchen/PreparingOrdersView.vue')
const ServedOrdersView = () => import('../views/kitchen/ServedOrdersView.vue')
const UserList = () => import('../views/Admin/users/UserList.vue')
const CreateUser = () => import('../views/Admin/users/CreateUser.vue')
const EditUser = () => import('../views/Admin/users/EditUser.vue')
const ViewUser = () => import('../views/Admin/users/ViewUser.vue')
const RoomList = () => import('../views/Admin/rooms/RoomList.vue')
const CreateRoom = () => import('../views/Admin/rooms/CreateRoom.vue')
const EditRoom = () => import('../views/Admin/rooms/EditRoom.vue')
const ViewRoom = () => import('../views/Admin/rooms/ViewRoom.vue')
const RoomTypeList = () => import('../views/Admin/room-types/index.vue')
const CreateRoomType = () => import('../views/Admin/room-types/create.vue')
const EditRoomType = () => import('../views/Admin/room-types/edit.vue')
const ViewRoomType = () => import('../views/Admin/room-types/show.vue')
const GuestList = () => import('../views/receptionist/guest/GuestList.vue')
const CreateGuest = () => import('../views/receptionist/guest/CreateGuest.vue')
const EditGuest = () => import('../views/receptionist/guest/EditGuest.vue')
const GuestDetails = () => import('../views/receptionist/guest/GuestDetail.vue')
const CheckInView = () => import('../views/receptionist/checkIn/CheckInView.vue')
const CheckOutView = () => import('../views/receptionist/checkOut/CheckOutView.vue')
const ReportsPage = () => import('../views/receptionist/reports/ReportsPage.vue')
const AddOrder = () => import('@/views/Admin/order/AddOrder.vue')
const QRMenu = () => import('../views/guest/QRMenu.vue')
const PaymentSuccessPage = () => import('../views/payment/PaymentSuccessPage.vue')
const PaymentFailedPage = () => import('../views/payment/PaymentFailedPage.vue')
const PaymentPendingPage = () => import('../views/payment/PaymentPendingPage.vue')
const CheckoutPage = () => import('../views/payment/CheckoutPage.vue')
const OrderPaymentSuccessPage = () => import('../views/payment/OrderPaymentSuccessPage.vue')
const OrderPaymentPage = () => import('../views/payment/OrderPaymentPage.vue')
const RoleManagementView = () => import('@/views/Admin/rbac/RoleManagementView.vue')
const PermissionManagementView = () => import('@/views/Admin/rbac/PermissionManagementView.vue')
const RolePermissionMatrixView = () => import('@/views/Admin/rbac/RolePermissionMatrixView.vue')
const UserRoleAssignmentView = () => import('@/views/Admin/rbac/UserRoleAssignmentView.vue')
const TemporaryRoleAssignmentView = () => import('@/views/Admin/rbac/TemporaryRoleAssignmentView.vue')
const AuditLogView = () => import('@/views/Admin/rbac/AuditLogView.vue')
const UnauthorizedView = () => import('@/views/UnauthorizedView.vue')

import managerRoutes from './managerRouter.ts'
import waiterRoutes from './waiterRouter'
import cashierRoutes from './cashierRouter'
import reviewRoutes from './reviewRouter'
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
        if (token && user?.role) {
          return next(`/${user.role}`)
        }
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
    {
      path: '/rooms',
      name: 'guest-rooms',
      component: roomPage,
      meta: { public: true }
    },
    {
      path: '/roomsPage',
      name: 'room',
      component: roomPage,
      meta: { public: true }
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
      path: '/menu-management/add',
      name: 'menu-management-add',
      component: AddMenuItemView,
      meta: {
        requiresAuth: true,
        permission: 'menu.create',
      },
    },
    {
      path: '/menu-management/add-category',
      name: 'menu-management-add-category',
      component: AddCategoryView,
      meta: {
        requiresAuth: true,
        permission: 'menu.create',
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
      path: '/admin/taxes',
      name: 'admin-taxes',
      component: () => import('../views/Admin/taxes/TaxManagementView.vue'),
      meta: {
        requiresAuth: true,
        role: 'admin',
        title: 'Tax Management',
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
      alias: ['/kitchen', '/chef/dashboard', '/kitchen/dashboard'],
      name: 'chef-dashboard',
      component: KitchenDashboard,
      meta: {
        requiresAuth: true,
        title: 'Kitchen Dashboard',
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
      path: '/users/:id',
      component: ViewUser,
      meta: {
        requiresAuth: true,
        permission: 'users.view',
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
      path: '/admin/rooms/create',
      name: 'admin-create-room',
      alias: ['/rooms/create'],
      component: CreateRoom,
      meta: {
        requiresAuth: true,
        permission: 'rooms.create',
      },
    },
    {
      path: '/admin/rooms/:id',
      name: 'admin-view-room',
      alias: ['/rooms/:id'],
      component: ViewRoom,
      meta: {
        requiresAuth: true,
        permission: 'rooms.view',
      },
    },
    {
      path: '/admin/rooms/:id/edit',
      name: 'admin-edit-room',
      alias: ['/rooms/:id/edit'],
      component: EditRoom,
      meta: {
        requiresAuth: true,
        permission: 'rooms.update',
      },
    },
    {
      path: '/admin/room-types',
      name: 'admin-room-types',
      alias: ['/room-types'],
      component: RoomTypeList,
      meta: {
        requiresAuth: true,
        permission: 'rooms.view',
      },
    },
    {
      path: '/admin/room-types/create',
      name: 'admin-create-room-type',
      alias: ['/room-types/create'],
      component: CreateRoomType,
      meta: {
        requiresAuth: true,
        permission: 'rooms.create',
      },
    },
    {
      path: '/admin/room-types/:id',
      name: 'admin-view-room-type',
      alias: ['/room-types/:id'],
      component: ViewRoomType,
      meta: {
        requiresAuth: true,
        permission: 'rooms.view',
      },
    },
    {
      path: '/admin/room-types/:id/edit',
      name: 'admin-edit-room-type',
      alias: ['/room-types/:id/edit'],
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
      component: () => import('../views/receptionist/reservation/ReservationListpage.vue'),
      meta: {
        title: 'Reservations',
        requiresAuth: true,
        permission: 'reservations.view',
      },
    },
    {
      path: '/reservations/create',
      name: 'reservations.create',
      component: () => import('../views/receptionist/reservation/ReservationCreate.vue'),
      meta: {
        title: 'Create Reservation',
        requiresAuth: true,
        permission: 'reservations.create',
      },
    },
    {
      path: '/reservations/:id',
      name: 'reservations.show',
      component: () => import('../views/receptionist/reservation/ReservationView.vue'),
      meta: {
        title: 'View Reservation',
        requiresAuth: true,
        permission: 'reservations.view',
      },
    },
    {
      path: '/reservations/:id/edit',
      name: 'reservations.edit',
      component: () => import('../views/receptionist/reservation/ReservationEdit.vue'),
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
    {
      path: '/order-status/:orderId',
      name: 'order-status',
      component: () => import('../views/guest/OrderStatusPage.vue'),
      meta: {
        title: 'Order Status - Live Tracking',
        requiresAuth: false,
      },
    },
    {
      path: '/payment/checkout',
      name: 'payment-checkout',
      component: CheckoutPage,
      meta: {
        title: 'Payment Checkout',
        requiresAuth: false,
      },
    },
    {
      path: '/payment/success',
      name: 'payment-success',
      component: PaymentSuccessPage,
      meta: {
        title: 'Payment Successful',
        requiresAuth: false,
      },
    },
    {
      path: '/order/payment',
      name: 'order-payment',
      component: OrderPaymentPage,
      meta: {
        title: 'Pay Your Order',
        requiresAuth: false,
      },
    },
    {
      path: '/order/payment/success',
      name: 'order-payment-success',
      component: OrderPaymentSuccessPage,
      meta: {
        title: 'Order Payment Successful',
        requiresAuth: false,
      },
    },
    {
      path: '/payment/failed',
      name: 'payment-failed',
      component: PaymentFailedPage,
      meta: {
        title: 'Payment Failed',
        requiresAuth: false,
      },
    },
    {
      path: '/payment/pending',
      name: 'payment-pending',
      component: PaymentPendingPage,
      meta: {
        title: 'Payment Pending',
        requiresAuth: false,
      },
    },
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

  // If going to login page, allow immediately (avoid auth initialization)
  if (to.path === '/login' && !token) {
    return true
  }

  // If trying to access protected route without token, redirect to login
  if (to.meta.requiresAuth && !token) {
    return '/login'
  }

  // Initialize auth only if we have a token and haven't initialized yet
  if (token && (!authStore.user || !authStore.isInitialized)) {
    try {
      await authStore.initializeAuth()
    } catch (e) {
      console.error('[Router] Auth initialization guard error:', e)
      // If auth init fails, redirect to login
      if (to.meta.requiresAuth) {
        return '/login'
      }
    }
  }

  // Allow public routes without further checks
  if (!to.meta.requiresAuth && !to.meta.permission && !to.meta.role && !to.meta.roles) {
    return true
  }

  // Super admin check
  if (to.meta.superAdminOnly && !authStore.isPlatformAdmin) {
    return '/admin'
  }

  // Platform admins and admins have full access
  if (authStore.isPlatformAdmin || authStore.hasRole('admin')) {
    return true
  }

  const targetPermission = typeof to.meta.permission === 'string' ? to.meta.permission : null
  const targetRole = typeof to.meta.role === 'string' ? to.meta.role : null
  const targetRoles = Array.isArray(to.meta.roles) ? (to.meta.roles as string[]) : null

  const hasPermission = targetPermission ? authStore.can(targetPermission) : true

  const hasRole = targetRole ? authStore.hasRole(targetRole) : true
  const hasAnyRole = targetRoles ? targetRoles.some(r => authStore.hasRole(r)) : true

  const isAdmin = authStore.hasRole('admin') || authStore.currentRole === 'admin' || authStore.user?.role === 'admin' || authStore.isPlatformAdmin
  if (isAdmin) {
    return true
  }

  const isManagerSection = (to.path.startsWith('/manager') || to.path.startsWith('/admin/rooms') || to.path.startsWith('/admin/room-types')) && (authStore.hasRole('manager') || authStore.currentRole === 'manager' || authStore.user?.role === 'manager')
  const isAdminSection = to.path.startsWith('/admin') && (authStore.hasRole('admin') || authStore.currentRole === 'admin' || authStore.user?.role === 'admin')
  const isReceptionistSection = (to.path.startsWith('/receptionist') || to.path.startsWith('/reservations') || to.path.startsWith('/check-in') || to.path.startsWith('/check-out') || to.path.startsWith('/guests') || to.path.startsWith('/admin/rooms') || to.path.startsWith('/admin/room-types')) && (authStore.hasRole('receptionist') || authStore.currentRole === 'receptionist' || authStore.user?.role === 'receptionist')
  const isCashierSection = to.path.startsWith('/cashier') && (authStore.hasRole('cashier') || authStore.currentRole === 'cashier' || authStore.user?.role === 'cashier')
  const isChefSection = to.path.startsWith('/chef') && (authStore.hasRole('chef') || authStore.currentRole === 'chef' || authStore.user?.role === 'chef')
  const isWaiterSection = to.path.startsWith('/waiter') && (authStore.hasRole('waiter') || authStore.currentRole === 'waiter' || authStore.user?.role === 'waiter')

  const isRoleSectionMatch = isManagerSection || isAdminSection || isReceptionistSection || isCashierSection || isChefSection || isWaiterSection

  if (isRoleSectionMatch) {
    return true
  }

  if (targetRole && !hasRole) {
    return '/unauthorized'
  }

  if (targetRoles && !hasAnyRole) {
    return '/unauthorized'
  }

  if (targetPermission && !hasPermission) {
    return '/unauthorized'
  }

  return true
})

export default router
