<script setup lang="ts">
import { computed, ref, watch, onMounted, onUnmounted, type Component } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '../../stores/auth'
import { useHotelStore } from '../../stores/hotelStore'
import { useSidebarStore } from '../../stores/sidebarStore'
import { useLanguageStore } from '../../stores/language'
import { rbacService } from '../../services/rbacService'
import {
  LayoutDashboard,
  Users,
  BedDouble,
  FileText,
  UtensilsCrossed,
  Contact,
  CalendarDays,
  LogIn,
  LogOut,
  Receipt,
  CreditCard,
  ArrowLeftRight,
  RefreshCw,
  Utensils,
  Clock,
  CookingPot,
  CheckCircle2,
  CircleDollarSign,
  Percent,
  CalendarCheck,
  FileSpreadsheet,
  BriefcaseBusiness,
  UserCheck,
  ClipboardList,
  BellRing,
  Truck,
  PackageCheck,
  BedSingle,
  ShieldCheck,
  BarChart3,
  TrendingUp,
  Wallet,
  CircleAlert,
  Settings,
  Users2,
  PanelLeft,
  Grid,
  Key,
  Search,
  ChevronDown,
  X,
  Star,
  Building2,
  SlidersHorizontal,
} from 'lucide-vue-next'

const emit = defineEmits<{
  navigate: []
}>()

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()
const hotelStore = useHotelStore()
const sidebarStore = useSidebarStore()
const languageStore = useLanguageStore()

const handleNavigate = () => {
  emit('navigate')
}

const handleLogout = async () => {
  try {
    // Call logout to clear auth state
    await auth.logout()
    
    // Navigate immediately to login page
    await router.replace('/login')
    
    // Optionally reload to clear any cached state
    window.location.href = '/login'
  } catch (error) {
    console.error('[Sidebar] Logout error:', error)
    // Even if logout fails, redirect to login
    window.location.href = '/login'
  }
}

const menuIcons: Record<string, Component> = {
  Dashboard: LayoutDashboard,
  Users: Users,
  Rooms: BedDouble,
  'Room Types': BedDouble,
  Reports: FileText,
  Restaurant: UtensilsCrossed,
  Guests: Contact,
  Reservations: CalendarDays,
  'Check In': LogIn,
  'Check Out': LogOut,
  Invoices: Receipt,
  Payments: CreditCard,
  Transactions: ArrowLeftRight,
  Refunds: RefreshCw,
  'Kitchen Dashboard': CookingPot,
  CookingPot: CookingPot,
  'Food Orders': Utensils,
  'Pending Orders': Clock,
  'Preparing Orders': CookingPot,
  'Served Orders': CheckCircle2,
  'Revenue Report': CircleDollarSign,
  'Occupancy Report': Percent,
  'Reservation Report': CalendarCheck,
  'Payment Report': FileSpreadsheet,
  Staff: UserCheck,
  Operations: ClipboardList,
  'Room Service': Truck,
  Housekeeping: BedSingle,
  Laundry: PackageCheck,
  Complaints: CircleAlert,
  Inventory: PackageCheck,
  Finance: Wallet,
  Analytics: TrendingUp,
  Notifications: BellRing,
  Security: ShieldCheck,
  Settings: Settings,
  Manager: BriefcaseBusiness,
  Statistics: BarChart3,
  Waiters: Users2,
  Grid: Grid,
  Key: Key,
  Reviews: Star,
  Hotels: Building2,
  Building: Building2,
}

const sectionIcons: Record<string, Component> = {
  'General': LayoutDashboard,
  'Platform': Building2,
  'Administration': SlidersHorizontal,
  'Property Management': BedDouble,
  'Front Desk': LogIn,
  'Dining & Kitchen': Utensils,
  'Operations': ClipboardList,
  'Deliveries': Truck,
  'Billing': CreditCard,
  'Reports & Analytics': BarChart3,
  'Account': Settings,
}

interface MenuItem {
  name: string
  path: string
  icon: string
  section: string
  permission?: string
  superAdminOnly?: boolean
  isDashboard?: boolean
  isProfile?: boolean
  roleSlug?: string | string[]
}

const operationalMenuItems: MenuItem[] = [
  { name: 'Hotels', path: '/admin/hotels', icon: 'Hotels', superAdminOnly: true, section: 'Platform' },
  { name: 'Hotel Admins', path: '/admin/hotel-admins', icon: 'Staff', superAdminOnly: true, section: 'Platform' },
  { name: 'All Users & Staff', path: '/admin/platform-users', icon: 'Users', superAdminOnly: true, section: 'Platform' },
  { name: 'Users & Staff', path: '/users', icon: 'Users', permission: 'users.view', roleSlug: 'admin', section: 'Administration' },
  { name: 'Role Management', path: '/admin/roles', icon: 'Security', permission: 'roles.view', roleSlug: 'admin', section: 'Administration' },
  { name: 'Permission Catalog', path: '/admin/permissions', icon: 'Key', permission: 'permissions.view', roleSlug: 'admin', section: 'Administration' },
  { name: 'Permission Matrix', path: '/admin/permission-matrix', icon: 'Grid', permission: 'roles.assign_permissions', roleSlug: 'admin', section: 'Administration' },
  { name: 'User Role Assignments', path: '/admin/user-roles', icon: 'Staff', permission: 'users.update', roleSlug: 'admin', section: 'Administration' },
  { name: 'Rooms Management', path: '/admin/rooms', icon: 'Rooms', permission: 'rooms.view', roleSlug: ['admin', 'manager', 'receptionist'], section: 'Property Management' },
  { name: 'Room Types', path: '/admin/room-types', icon: 'Room Types', permission: 'rooms.view', roleSlug: ['admin', 'manager', 'receptionist'], section: 'Property Management' },
  { name: 'Reservations', path: '/reservations', icon: 'Reservations', permission: 'reservations.view', roleSlug: ['admin', 'manager', 'receptionist'], section: 'Front Desk' },
  { name: 'Check In', path: '/check-in', icon: 'Check In', permission: 'checkin.view', roleSlug: ['admin', 'manager', 'receptionist'], section: 'Front Desk' },
  { name: 'Check Out', path: '/check-out', icon: 'Check Out', permission: 'checkout.view', roleSlug: ['admin', 'manager', 'receptionist'], section: 'Front Desk' },
  { name: 'Orders Management', path: '/orders', icon: 'Utensils', permission: 'orders.view', roleSlug: ['admin', 'manager'], section: 'Dining & Kitchen' },
  { name: 'Menu Management', path: '/menu-management', icon: 'Restaurant', permission: 'menu.view', roleSlug: ['admin', 'manager', 'chef'], section: 'Dining & Kitchen' },
  { name: 'Tax Management', path: '/admin/taxes', icon: 'Percent', roleSlug: ['admin', 'manager'], section: 'Dining & Kitchen' },
  { name: 'Kitchen Dashboard', path: '/chef', icon: 'CookingPot',permission: 'kitchen.view', roleSlug: ['admin', 'chef', 'kitchen', 'manager'], section: 'Dining & Kitchen' },
  { name: 'Food Orders', path: '/chef/food-orders', icon: 'Food Orders', permission: 'kitchen.view', roleSlug: ['admin', 'chef'], section: 'Dining & Kitchen' },
  { name: 'Pending Orders', path: '/chef/pending-orders', icon: 'Pending Orders', permission: 'kitchen.accept', roleSlug: ['admin', 'chef'], section: 'Dining & Kitchen' },
  { name: 'Preparing Orders', path: '/chef/preparing-orders', icon: 'Preparing Orders', permission: 'kitchen.prepare', roleSlug: ['admin', 'chef'], section: 'Dining & Kitchen' },
  { name: 'Served Orders', path: '/chef/served-orders', icon: 'Served Orders', permission: 'kitchen.mark_ready', roleSlug: ['admin', 'chef'], section: 'Dining & Kitchen' },
  { name: 'Waiter Management', path: '/manager/waiters', icon: 'Waiters', permission: 'waiters.view', roleSlug: ['admin', 'manager'], section: 'Operations' },
  { name: 'Restaurant Tables', path: '/manager/restaurant-tables', icon: 'Restaurant', permission: 'tables.view', roleSlug: ['admin', 'manager'], section: 'Operations' },
  { name: 'Table Assignments', path: '/manager/table-assignments', icon: 'MapPin', permission: 'tables.assign', roleSlug: ['admin', 'manager'], section: 'Operations' },
  { name: 'Floor Management', path: '/manager/floor-assignment', icon: 'Manager', permission: 'floors.view', roleSlug: ['admin', 'manager'], section: 'Operations' },
  { name: 'Daily Operations', path: '/manager/operations', icon: 'Operations', permission: 'reports.occupancy', roleSlug: ['admin', 'manager'], section: 'Operations' },
  { name: 'Room Service Management', path: '/manager/delivery-management', icon: 'Truck', permission: 'delivery.reassign', roleSlug: ['admin', 'manager'], section: 'Deliveries' },
  { name: 'Assigned Orders', path: '/waiter/assigned-orders', icon: 'Room Service', permission: 'delivery.accept', roleSlug: ['admin', 'waiter'], section: 'Deliveries' },
  { name: 'Ready for Pickup', path: '/waiter/ready-pickup', icon: 'Pending Orders', permission: 'delivery.pickup', roleSlug: ['admin', 'waiter'], section: 'Deliveries' },
  { name: 'On Delivery', path: '/waiter/on-delivery', icon: 'Truck', permission: 'delivery.deliver', roleSlug: ['admin', 'waiter'], section: 'Deliveries' },
  { name: 'Delivery History', path: '/waiter/delivery-history', icon: 'Reports', permission: 'delivery.view', roleSlug: ['admin', 'waiter'], section: 'Deliveries' },
  { name: 'Payments & Billing', path: '/cashier/payments', icon: 'Payments', permission: 'payments.view', roleSlug: ['admin', 'manager', 'cashier'], section: 'Billing' },
  { name: 'Financial Reports', path: '/cashier/reports', icon: 'Reports', permission: 'reports.sales', roleSlug: ['admin', 'manager', 'cashier'], section: 'Billing' },
  { name: 'Reports & Analytics', path: '/reports', icon: 'Reports', permission: 'reports.view', roleSlug: ['admin', 'manager'], section: 'Reports & Analytics' },
  { name: 'Review Moderation', path: '/reviews/moderation', icon: 'Notifications', permission: 'reviews.moderate', roleSlug: ['admin', 'manager'], section: 'Reports & Analytics' },
  { name: 'Review Analytics', path: '/reviews/analytics', icon: 'Analytics', permission: 'reviews.analytics', roleSlug: ['admin', 'manager'], section: 'Reports & Analytics' },
]

const sidebarRefreshKey = ref(0)

const userRoleSlug = computed(() => {
  return String(
    hotelStore.currentHotel?.role || 
    auth.currentHotel?.role ||
    auth.currentRole || 
    auth.user?.role || 
    'guest'
  ).toLowerCase().trim()
})

const activeRouteRole = computed(() => {
  const parts = route.path.toLowerCase().split('/').filter(Boolean)
  return parts[0] || userRoleSlug.value
})

const isAdminUser = computed(() => {
  const role = userRoleSlug.value
  const hotelRole = String(hotelStore.currentHotel?.role || auth.currentHotel?.role || '').toLowerCase().trim()
  const authRole = String(auth.currentRole || '').toLowerCase().trim()
  const userRole = String(auth.user?.role || '').toLowerCase().trim()

  return (
    auth.isPlatformAdmin ||
    role === 'admin' ||
    role.includes('admin') ||
    hotelRole.includes('admin') ||
    authRole.includes('admin') ||
    userRole.includes('admin') ||
    auth.hasRole('admin') ||
    auth.hasRole('hotel_admin') ||
    auth.hasRole('hotel-admin')
  )
})

const userDashboardPath = computed(() => {
  if (isAdminUser.value) return '/admin'
  const role = userRoleSlug.value
  if (role === 'admin' || role.includes('admin')) return '/admin'
  if (role === 'chef' || role === 'kitchen') return '/chef'
  if (role === 'waiter') return '/waiter'
  if (role === 'cashier') return '/cashier'
  if (role === 'receptionist') return '/receptionist'
  if (role === 'manager') return '/manager'
  if (!role || role === 'guest') return '/orders'
  return `/${role}`
})
const userProfilePath = computed(() => {
  if (isAdminUser.value) return '/admin/profile'
  const role = userRoleSlug.value
  return `/${role}/profile`
})

const menus = computed(() => {
  const _ = sidebarRefreshKey.value

  const items: MenuItem[] = [
    { name: 'Dashboard', path: userDashboardPath.value, icon: 'Dashboard', section: 'General' }
  ]

  const filteredOps = operationalMenuItems.filter(item => {
    if (item.superAdminOnly) {
      return auth.isPlatformAdmin
    }

    if (auth.isPlatformAdmin) {
      return true
    }

    if (item.roleSlug) {
      const allowedRoles = Array.isArray(item.roleSlug) ? item.roleSlug : [item.roleSlug]
      const currentRole = userRoleSlug.value
      const hasMatchingRole = allowedRoles.some(r => r.toLowerCase() === currentRole)
      if (!hasMatchingRole && !auth.isPlatformAdmin && !isAdminUser.value) {
        return false
      }
    }

    if (isAdminUser.value && !item.superAdminOnly) {
      return true
    }

    if (item.permission) {
      const targetPerm = String(item.permission).toLowerCase().trim()
      return auth.can(targetPerm)
    }

    return true
  })

  items.push(...filteredOps)
  items.push({ name: 'Profile Settings', path: userProfilePath.value, icon: 'Settings', section: 'Account' })

  return items
})

const groupedMenus = computed(() => {
  if (!isAdminUser.value) {
    return {
      'General': menus.value
    }
  }

  const groups: Record<string, MenuItem[]> = {}
  menus.value.forEach(menu => {
    const section = menu.section || 'General'
    if (!groups[section]) {
      groups[section] = []
    }
    groups[section].push(menu)
  })
  return groups
})

const searchQuery = ref('')
const searchInputRef = ref<HTMLInputElement | null>(null)
const openSections = ref<Record<string, boolean>>({
  'General': true,
  'Platform': false,
  'Administration': false,
  'Property Management': false,
  'Front Desk': false,
  'Dining & Kitchen': false,
  'Operations': false,
  'Deliveries': false,
  'Billing': false,
  'Reports & Analytics': false,
  'Account': false,
})

const toggleSection = (section: string) => {
  openSections.value[section] = !openSections.value[section]
}

function isActive(path: string): boolean {
  const current = route.path
  if (path === '/admin' || path === '/manager' || path === '/receptionist' || path === '/chef' || path === '/waiter' || path === '/cashier/dashboard') {
    return current === path
  }
  if (path === '/menu-management') {
    return current.startsWith('/menu-management') || current.startsWith('/admin/menu')
  }
  return current === path || current.startsWith(path + '/')
}

const updateActiveSection = () => {
  Object.entries(groupedMenus.value).forEach(([section, items]) => {
    if (items.some(item => isActive(item.path))) {
      openSections.value[section] = true
    }
  })
}
watch(
  () => route.path,
  () => {
    updateActiveSection()
  },
  { immediate: true }
)

const refreshSidebar = () => {
  sidebarRefreshKey.value++
  updateActiveSection()
}

watch(
  () => [hotelStore.hotelId, hotelStore.currentHotel?.role, auth.currentRole, auth.userPermissions],
  () => {
    refreshSidebar()
  },
  { deep: true, immediate: true }
)

const handleHotelSwitched = () => {
  refreshSidebar()
}

const filteredGroupedMenus = computed(() => {
  const query = searchQuery.value.trim().toLowerCase()
  if (!query) return groupedMenus.value

  const filtered: Record<string, MenuItem[]> = {}
  Object.entries(groupedMenus.value).forEach(([section, items]) => {
    const matchingItems = items.filter(item => 
      item.name.toLowerCase().includes(query) || 
      section.toLowerCase().includes(query)
    )
    if (matchingItems.length > 0) {
      filtered[section] = matchingItems
      openSections.value[section] = true
    }
  })
  return filtered
})

const isMobile = ref(typeof window !== 'undefined' ? window.innerWidth < 1024 : false)

const updateIsMobile = () => {
  if (typeof window !== 'undefined') {
    isMobile.value = window.innerWidth < 1024
  }
}

const isSidebarExpanded = computed(() => {
  if (isMobile.value) return true
  return sidebarStore.isExpanded
})

const handleKeyDown = (e: KeyboardEvent) => {
  if ((e.metaKey || e.ctrlKey) && e.key.toLowerCase() === 'k') {
    e.preventDefault()
    searchInputRef.value?.focus()
  }
}

onMounted(() => {
  updateIsMobile()
  window.addEventListener('resize', updateIsMobile)
  window.addEventListener('keydown', handleKeyDown)
  window.addEventListener('hotel-switched', handleHotelSwitched)
  window.addEventListener('permissions-updated', handleHotelSwitched)
  updateActiveSection()
})

onUnmounted(() => {
  window.removeEventListener('resize', updateIsMobile)
  window.removeEventListener('keydown', handleKeyDown)
  window.removeEventListener('hotel-switched', handleHotelSwitched)
  window.removeEventListener('permissions-updated', handleHotelSwitched)
})
</script>

<template>
  <aside
    @mouseenter="sidebarStore.onMouseEnter()"
    @mouseleave="sidebarStore.onMouseLeave()"
    class="h-full w-full bg-white/95 dark:bg-slate-950/95 text-slate-800 dark:text-slate-100 flex flex-col relative select-none flex-shrink-0 shadow-md dark:shadow-xl border-r border-slate-200/80 dark:border-slate-800/80 transition-all duration-300 overflow-hidden backdrop-blur-md"
  >
    <div
      class="flex items-center bg-white dark:bg-slate-950 transition-all duration-300 border-b border-slate-200/80 dark:border-slate-800/80 flex-shrink-0"
      :class="isSidebarExpanded ? 'h-16 px-4 justify-between' : 'h-16 py-2 px-2 flex-col justify-center'"
    >
      <template v-if="isSidebarExpanded">
        <div class="flex items-center min-w-0">
          <div class="w-10 h-10 flex items-center justify-center flex-shrink-0">
            <img 
              src="/images/Hotel logo.png" 
              alt="Hotel Logo" 
              class="w-full h-full object-contain select-none"
            />
          </div>
        </div>
        
        <!-- Desktop Collapse Button (Circular style matching reference) -->
        <button
          @click="sidebarStore.toggleCollapse()"
          class="hidden lg:flex items-center justify-center w-8.5 h-8.5 rounded-full border border-slate-200/70 dark:border-slate-800 bg-slate-100/80 dark:bg-slate-800/60 hover:bg-slate-200/80 dark:hover:bg-slate-700 text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white transition cursor-pointer flex-shrink-0 shadow-xs"
          :title="sidebarStore.sidebarCollapsed ? 'Expand sidebar' : 'Collapse sidebar'"
        >
          <PanelLeft
            class="w-4 h-4 transition-transform duration-300"
            :class="sidebarStore.sidebarCollapsed ? 'rotate-180' : ''"
          />
        </button>

        <!-- Mobile Close Button -->
        <button
          @click="sidebarStore.closeMobile()"
          class="flex lg:hidden items-center justify-center w-8 h-8 rounded-full border border-slate-200/70 dark:border-slate-800 bg-slate-100/80 dark:bg-slate-800/60 text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white transition cursor-pointer flex-shrink-0"
          title="Close sidebar"
        >
          <X class="w-4 h-4" />
        </button>
      </template>

      <template v-else>
        <button
          @click="sidebarStore.toggleCollapse()"
          class="flex items-center justify-center w-8.5 h-8.5 rounded-full border border-slate-200/80 dark:border-slate-800 bg-slate-100/80 dark:bg-slate-800/60 hover:bg-slate-200/80 dark:hover:bg-slate-700 text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white transition cursor-pointer mx-auto shadow-xs"
          title="Expand sidebar"
        >
          <PanelLeft class="w-4 h-4 transform rotate-180" />
        </button>
      </template>
    </div>

    <nav 
      class="flex-1 min-h-0 py-3 overflow-y-auto transition-all duration-300 space-y-4 pr-1 pb-6"
      :class="isSidebarExpanded ? 'px-3' : 'px-2'"
    >
      <div v-if="isSidebarExpanded" class="px-0.5 pb-1">
        <div class="relative flex items-center">
          <Search class="absolute left-3 w-3.5 h-3.5 text-slate-400 dark:text-slate-500 pointer-events-none" />
          <input
            ref="searchInputRef"
            v-model="searchQuery"
            type="text"
            :placeholder="languageStore.t('search_menu', 'Search menu or type /')"
            class="w-full pl-8 pr-8 py-2 text-xs font-semibold bg-slate-100/90 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700/80 rounded-xl text-slate-800 dark:text-slate-100 placeholder-slate-400 dark:placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:bg-white dark:focus:bg-slate-800 transition-all"
          />
          <button
            v-if="searchQuery"
            @click="searchQuery = ''"
            class="absolute right-2.5 p-0.5 rounded-md hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 transition"
          >
            <X class="w-3.5 h-3.5" />
          </button>
          <kbd v-else class="absolute right-2 px-1.5 py-0.5 text-[9px] font-bold text-slate-500 dark:text-slate-400 bg-slate-200/80 dark:bg-slate-700/80 rounded border border-slate-300/80 dark:border-slate-600/80">
            ⌘K
          </kbd>
        </div>
      </div>

      <div v-for="(items, section) in filteredGroupedMenus" :key="section" class="space-y-0.5">
        <template v-if="!isAdminUser && section === 'General'">
          <router-link
            v-for="menu in items"
            :key="menu.path"
            :to="menu.path"
            @click="handleNavigate"
            class="group relative flex items-center gap-3 rounded-lg transition-all duration-200"
            :class="[
              isSidebarExpanded 
                ? 'px-4 py-3' 
                : 'justify-center py-3',
              isActive(menu.path)
                ? 'bg-blue-50 text-blue-700 dark:bg-blue-500/10 dark:text-blue-400 font-semibold'
                : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50 dark:text-slate-400 dark:hover:text-white dark:hover:bg-slate-800/50'
            ]"
            :title="!isSidebarExpanded ? languageStore.t(menu.name, menu.name) : ''"
          >
            <component
              :is="menuIcons[menu.icon] || menuIcons['Dashboard']"
              class="flex-shrink-0 w-5 h-5"
              :class="isActive(menu.path) ? 'text-blue-600 dark:text-blue-400' : 'text-slate-500 dark:text-slate-500'"
              :stroke-width="1.75"
            />
            <span
              v-if="isSidebarExpanded"
              class="text-sm font-medium"
            >
              {{ languageStore.t(menu.name, menu.name) }}
            </span>
            
            <div
              v-if="!isSidebarExpanded && !sidebarStore.hoverExpand"
              class="absolute left-full ml-3 px-3 py-1.5 bg-slate-900 dark:bg-slate-800 border border-slate-700 text-white text-xs font-semibold rounded-lg shadow-xl opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 whitespace-nowrap z-50 pointer-events-none"
            >
              {{ languageStore.t(menu.name, menu.name) }}
            </div>
          </router-link>
        </template>

        <template v-else-if="isAdminUser && section === 'General'">
          <router-link
            :to="items[0].path"
            @click="handleNavigate"
            class="group relative flex items-center gap-3 rounded-lg transition-all duration-200"
            :class="[
              isSidebarExpanded 
                ? 'px-4 py-3' 
                : 'justify-center py-3',
              isActive(items[0].path)
                ? 'bg-blue-50 text-blue-700 dark:bg-blue-500/10 dark:text-blue-400 font-semibold'
                : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50 dark:text-slate-400 dark:hover:text-white dark:hover:bg-slate-800/50'
            ]"
            :title="!isSidebarExpanded ? languageStore.t(items[0].name, items[0].name) : ''"
          >
            <component
              :is="menuIcons[items[0].icon] || menuIcons['Dashboard']"
              class="flex-shrink-0 w-5 h-5"
              :class="isActive(items[0].path) ? 'text-blue-600 dark:text-blue-400' : 'text-slate-500 dark:text-slate-500'"
              :stroke-width="1.75"
            />
            <span
              v-if="isSidebarExpanded"
              class="text-sm font-medium"
            >
              {{ languageStore.t(items[0].name, items[0].name) }}
            </span>
            
            <div
              v-if="!isSidebarExpanded && !sidebarStore.hoverExpand"
              class="absolute left-full ml-3 px-3 py-1.5 bg-slate-900 dark:bg-slate-800 border border-slate-700 text-white text-xs font-semibold rounded-lg shadow-xl opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 whitespace-nowrap z-50 pointer-events-none"
            >
              {{ languageStore.t(items[0].name, items[0].name) }}
            </div>
          </router-link>
        </template>

        <template v-else-if="isAdminUser && section !== 'General'">
          <div
            v-if="isSidebarExpanded"
            class="px-4 pt-4 pb-1"
          >
            <p class="text-[11px] font-extrabold uppercase tracking-wider text-slate-400 dark:text-slate-500">
              {{ languageStore.t(section, section) }}
            </p>
          </div>
          <div
            v-else
            class="flex items-center justify-center py-2 text-slate-300 dark:text-slate-600 select-none text-[8px] tracking-[0.25em]"
          >
            •••
          </div>

          <template v-if="!isSidebarExpanded">
            <router-link
              v-for="menu in items"
              :key="menu.path"
              :to="menu.path"
              @click="handleNavigate"
              class="group relative flex items-center justify-center py-3 rounded-lg transition-all duration-200"
              :class="
                isActive(menu.path)
                  ? 'bg-blue-50 text-blue-700 dark:bg-blue-500/10 dark:text-blue-400'
                  : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50 dark:text-slate-400 dark:hover:text-white dark:hover:bg-slate-800/50'
              "
              :title="languageStore.t(menu.name, menu.name)"
            >
              <component
                :is="menuIcons[menu.icon] || menuIcons['Dashboard']"
                class="flex-shrink-0 w-5 h-5"
                :class="isActive(menu.path) ? 'text-blue-600 dark:text-blue-400' : 'text-slate-500 dark:text-slate-500'"
                :stroke-width="1.75"
              />
              
              <div
                v-if="!sidebarStore.hoverExpand"
                class="absolute left-full ml-3 px-3 py-1.5 bg-slate-900 dark:bg-slate-800 border border-slate-700 text-white text-xs font-semibold rounded-lg shadow-xl opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 whitespace-nowrap z-50 pointer-events-none"
              >
                {{ languageStore.t(menu.name, menu.name) }}
              </div>
            </router-link>
          </template>

          <template v-else>
            <button
              v-if="items.length > 1"
              @click="toggleSection(section)"
              class="w-full flex items-center justify-between px-4 py-3 text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800/50 rounded-lg transition-all duration-200 group cursor-pointer"
            >
              <div class="flex items-center gap-3">
                <component
                  :is="sectionIcons[section] || SlidersHorizontal"
                  class="w-5 h-5 text-slate-500 dark:text-slate-500 flex-shrink-0"
                  :stroke-width="1.75"
                />
                <span class="text-sm font-medium">{{ languageStore.t(section, section) }}</span>
              </div>
              
              <ChevronDown
                class="w-4 h-4 text-slate-400 dark:text-slate-500 transition-transform duration-300 flex-shrink-0"
                :class="openSections[section] ? '' : '-rotate-90'"
              />
            </button>

            <div
              v-if="items.length > 1"
              v-show="openSections[section]"
              class="space-y-0.5 pl-4 transition-all duration-200"
            >
              <router-link
                v-for="menu in items"
                :key="menu.path"
                :to="menu.path"
                @click="handleNavigate"
                class="group relative flex items-center gap-3 px-4 py-2.5 rounded-lg transition-all duration-200"
                :class="
                  isActive(menu.path)
                    ? 'bg-blue-50 text-blue-700 dark:bg-blue-500/10 dark:text-blue-400 font-semibold'
                    : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50 dark:text-slate-400 dark:hover:text-white dark:hover:bg-slate-800/50'
                "
              >
                <component
                  :is="menuIcons[menu.icon] || menuIcons['Dashboard']"
                  class="flex-shrink-0 w-4.5 h-4.5"
                  :class="isActive(menu.path) ? 'text-blue-600 dark:text-blue-400' : 'text-slate-500 dark:text-slate-500'"
                  :stroke-width="1.75"
                />
                <span class="text-sm font-medium">
                  {{ languageStore.t(menu.name, menu.name) }}
                </span>
              </router-link>
            </div>

            <router-link
              v-if="items.length === 1"
              :to="items[0].path"
              @click="handleNavigate"
              class="group relative flex items-center gap-3 px-4 py-3 rounded-lg transition-all duration-200"
              :class="
                isActive(items[0].path)
                  ? 'bg-blue-50 text-blue-700 dark:bg-blue-500/10 dark:text-blue-400 font-semibold'
                  : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50 dark:text-slate-400 dark:hover:text-white dark:hover:bg-slate-800/50'
              "
            >
              <component
                :is="menuIcons[items[0].icon] || menuIcons['Dashboard']"
                class="flex-shrink-0 w-5 h-5"
                :class="isActive(items[0].path) ? 'text-blue-600 dark:text-blue-400' : 'text-slate-500 dark:text-slate-500'"
                :stroke-width="1.75"
              />
              <span class="text-sm font-medium">
                {{ languageStore.t(items[0].name, items[0].name) }}
              </span>
            </router-link>
          </template>
        </template>
      </div>
    </nav>

    <div class="p-3 border-t border-slate-200/80 dark:border-slate-800/80 bg-slate-50/90 dark:bg-slate-950/90 flex items-center justify-between flex-shrink-0">
      <div class="flex items-center gap-2.5 min-w-0 flex-1">
        <div class="w-8 h-8 rounded-xl bg-blue-500/10 dark:bg-blue-500/20 text-blue-600 dark:text-blue-400 border border-blue-500/20 dark:border-blue-500/30 flex items-center justify-center font-black text-xs flex-shrink-0 shadow-xs">
          {{ (auth.user?.full_name || auth.user?.first_name || 'U').charAt(0).toUpperCase() }}
        </div>
        <div v-if="isSidebarExpanded" class="flex-1 min-w-0 pr-1">
          <p class="text-xs font-bold text-slate-900 dark:text-white truncate leading-tight">{{ auth.user?.full_name || auth.user?.first_name || 'Administrator' }}</p>
          <p class="text-[10px] text-blue-600 dark:text-blue-400 font-semibold capitalize truncate mt-0.5 leading-tight">{{ languageStore.t(auth.user?.role || 'Admin', auth.user?.role || 'Admin') }}</p>
        </div>
      </div>

      <button
        @click="handleLogout"
        class="p-2 rounded-xl text-rose-500 dark:text-rose-400 hover:bg-rose-500/10 hover:text-rose-600 dark:hover:text-rose-300 transition cursor-pointer flex-shrink-0"
        :title="languageStore.t('sign_out', 'Sign Out')"
      >
        <LogOut class="w-4.5 h-4.5" :stroke-width="1.75" />
      </button>
    </div>
  </aside>
</template>
<style scoped>
nav::-webkit-scrollbar {
  width: 4px;
}
nav::-webkit-scrollbar-track {
  background: transparent;
}
nav::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 9999px;
}
nav::-webkit-scrollbar-thumb:hover {
  background: #94a3b8;
}
.dark nav::-webkit-scrollbar-thumb {
  background: #334155;
}
.dark nav::-webkit-scrollbar-thumb:hover {
  background: #475569;
}
</style>
