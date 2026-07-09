import { createRouter, createWebHistory } from 'vue-router'

import LoginView from '../views/LoginView.vue'
import AdminDashboard from '../views/Admin/AdminDashboard.vue'
import ReceptionDashboard from '../views/receptionist/reception/ReceptionDashboard.vue'
import MenuManagement from '../views/Admin/menu/MenuView.vue'
import AddMenuItemView from '../views/Admin/menu/AddMenuItemView.vue'
import CashierDashboard from '../views/Cashier/CashierDashboard.vue'
import ChefDashboard from '../views/chef/ChefDashboard.vue'
import ManagerDashboard from '../views/manager/ManagerDashboard.vue'
import UserList from '../views/admin/users/UserList.vue'
import CreateUser from '../views/admin/users/CreateUser.vue'
import EditUser from '../views/admin/users/EditUser.vue'
import RoomList from '../views/admin/rooms/RoomList.vue'
import CreateRoom from '../views/admin/rooms/CreateRoom.vue'
import EditRoom from '../views/admin/rooms/EditRoom.vue'
import ViewRoom from '../views/admin/rooms/ViewRoom.vue'
import RoomTypeList from '../views/admin/room-types/index.vue'
import CreateRoomType from '../views/admin/room-types/create.vue'
import EditRoomType from '../views/admin/room-types/edit.vue'
import ViewRoomType from '../views/admin/room-types/show.vue'
import GuestList from '../views/receptionist/guest/guestList.vue'
import CreateGuest from '../views/receptionist/guest/createGuest.vue'
import EditGuest from '../views/receptionist/guest/editGuest.vue'
import GuestDetails from '../views/receptionist/guest/guestDetail.vue'
import ReservationListPage from '../views/receptionist/reservation/ReservationListpage.vue'
import ReservationCreatePage from '../views/receptionist/reservation/ReservationCreate.vue'
import ReservationEditPage from '../views/receptionist/reservation/ReservationEdit.vue'
import CheckInView from '../views/receptionist/checkIn/checkInView.vue'
const router = createRouter({
  history: createWebHistory(),
  routes: [
    {
      path: '/',
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
      path: '/menu-management',
      name: 'menu-management',
      component: MenuManagement,
      meta: {
        requiresAuth: true,
        roles: ['admin'],
      },
    },
    {
      path: '/admin/menu',
      name: 'admin-menu',
      component: MenuManagement,
      meta: {
        requiresAuth: true,
        role: 'admin',
      },
    },
    {
      path: '/admin/menu/add',
      name: 'admin-menu-add',
      component: AddMenuItemView,
      meta: {
        requiresAuth: true,
        role: 'admin',
      },
    },
    {
      path: '/receptionist',
      name: 'receptionist-dashboard',
      component: ReceptionDashboard,
      meta: {
        requiresAuth: true,
        role: 'receptionist',
      },
    },
    {
      path: '/cashier',
      name: 'cashier-dashboard',
      component: CashierDashboard,
      meta: {
        requiresAuth: true,
        role: 'cashier',
      },
    },
    {
      path: '/chef',
      name: 'chef-dashboard',
      component: ChefDashboard,
      meta: {
        requiresAuth: true,
        role: 'chef',
      },
    },
    {
      path: '/manager',
      name: 'manager-dashboard',
      component: ManagerDashboard,
      meta: {
        requiresAuth: true,
        role: 'manager',
      },
    },
    {
      path: '/users',
      component: UserList,
      meta: {
        requiresAuth: true,
        role: 'admin',
      },
    },

    {
      path: '/users/create',
      component: CreateUser,
      meta: {
        requiresAuth: true,
        role: 'admin',
      },
    },

    {
      path: '/users/:id/edit',
      component: EditUser,
      meta: {
        requiresAuth: true,
        role: 'admin',
      },
    },
    {
      path: '/rooms',
      component: RoomList,
      meta: {
        requiresAuth: true,
        role: 'admin',
      },
    },
    {
      path: '/rooms/create',
      component: CreateRoom,
      meta: {
        requiresAuth: true,
        role: 'admin',
      },
    },
    {
      path: '/rooms/:id',
      component: ViewRoom,
      meta: {
        requiresAuth: true,
        role: 'admin',
      },
    },
    {
      path: '/rooms/:id/edit',
      component: EditRoom,
      meta: {
        requiresAuth: true,
        role: 'admin',
      },
    },
    {
      path: '/room-types',
      component: RoomTypeList,
      meta: {
        requiresAuth: true,
        role: 'admin',
      },
    },

    {
      path: '/room-types/create',
      component: CreateRoomType,
      meta: {
        requiresAuth: true,
        role: 'admin',
      },
    },

    {
      path: '/room-types/:id',
      component: ViewRoomType,
      meta: {
        requiresAuth: true,
        role: 'admin',
      },
    },

    {
      path: '/room-types/:id/edit',
      component: EditRoomType,
      meta: {
        requiresAuth: true,
        role: 'admin',
      },
    },
    {
      path: '/guests',
      component: GuestList,
      meta: {
        requiresAuth: true,
        role: 'receptionist',
      },
    },
    {
      path: '/guests/create',
      component: CreateGuest,
      meta: {
        requiresAuth: true,
        role: 'receptionist',
      },
    },
    {
      path: '/guests/:id',
      component: GuestDetails,
      meta: {
        requiresAuth: true,
        role: 'receptionist',
      },
    },
    {
      path: '/guests/:id/edit',
      component: EditGuest,
      meta: {
        requiresAuth: true,
        role: 'receptionist',
      },
    },
    {
      path: '/reservations',
      name: 'reservations.index',
      component: ReservationListPage,
      meta: {
        title: 'Reservations',
        requiresAuth: true,
      },
    },

    {
      path: '/reservations/create',
      name: 'reservations.create',
      component: ReservationCreatePage,
      meta: {
        title: 'Create Reservation',
        requiresAuth: true,
      },
    },

    {
      path: '/reservations/:id/edit',
      name: 'reservations.edit',
      component: ReservationEditPage,
      meta: {
        title: 'Edit Reservation',
        requiresAuth: true,
      },
    },
    {
      path: '/:pathMatch(.*)*',
      redirect: '/',
    },
    {
      path: '/check-in',
      name: 'check-ins',
      component: CheckInView,
      meta: {
        title: 'Check In Management',
        requiresAuth: true,
      },
    },
  ],
})

router.beforeEach((to) => {
  const token = localStorage.getItem('token')
  const user = JSON.parse(localStorage.getItem('user') || 'null')

  if (to.meta.requiresAuth && !token) {
    return '/'
  }

  if (to.meta.role && user?.role !== to.meta.role) {
    console.warn(
      ` Access Denied: User role '${user?.role}' is not allowed for '${to.path}'. Required role: '${to.meta.role}'`,
    )

    if (user?.role === 'receptionist') {
      return '/receptionist'
    } else if (user?.role === 'cashier') {
      return '/cashier'
    } else if (user?.role === 'manager') {
      return '/manager'
    } else if (user?.role === 'chef') {
      return '/chef'
    }
    return '/'
  }

  return true
})

export default router
