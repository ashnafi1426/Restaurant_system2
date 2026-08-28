<script setup lang="ts">
import { computed, ref, watch, onMounted, onUnmounted, type Component } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '../../stores/auth'
import { useSidebarStore } from '../../stores/sidebarStore'
import { rbacService } from '../../services/rbacService'
// Import Lucide components
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
} from 'lucide-vue-next'

// Define emits
const emit = defineEmits<{
  navigate: []
}>()

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()
const sidebarStore = useSidebarStore()

// Handler for navigation
const handleNavigate = () => {
  emit('navigate')
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
}

const sectionIcons: Record<string, Component> = {
  'General': LayoutDashboard,
  'Administration': ShieldCheck,
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
  isDashboard?: boolean
  isProfile?: boolean
  roleSlug?: string
}

const operationalMenuItems: MenuItem[] = [
  // Dynamic System Administration (Permission-Driven)
  { name: 'Users & Staff', path: '/users', icon: 'Users', permission: 'users.view', section: 'Administration' },
  { name: 'Role Management', path: '/admin/roles', icon: 'Security', permission: 'roles.view', section: 'Administration' },
  { name: 'Permission Catalog', path: '/admin/permissions', icon: 'Key', permission: 'permissions.view', section: 'Administration' },
  // { name: 'Permission Matrix', path: '/admin/permission-matrix', icon: 'Grid', permission: 'roles.assign_permissions', section: 'Administration' },
  { name: 'User Role Assignments', path: '/admin/user-roles', icon: 'Staff', permission: 'users.update', section: 'Administration' },
  // { name: 'Temporary Delegations', path: '/admin/temporary-roles', icon: 'Manager', permission: 'roles.assign_permissions', section: 'Administration' },
  // { name: 'Security Audit Logs', path: '/admin/audit-logs', icon: 'Reports', permission: 'audit_logs.view', section: 'Administration' },

  // Property & Front Desk (Permission-Driven)
  { name: 'Rooms Management', path: '/Admin/rooms', icon: 'Rooms', permission: 'rooms.view', section: 'Property Management' },
  { name: 'Room Types', path: '/room-types', icon: 'Room Types', permission: 'rooms.view', section: 'Property Management' },
  { name: 'Reservations', path: '/reservations', icon: 'Reservations', permission: 'reservations.view', section: 'Front Desk' },
  { name: 'Check In', path: '/check-in', icon: 'Check In', permission: 'checkin.view', section: 'Front Desk' },
  { name: 'Check Out', path: '/check-out', icon: 'Check Out', permission: 'checkout.view', section: 'Front Desk' },

  // Dining & Kitchen Operations (Permission-Driven)
  { name: 'Orders Management', path: '/orders', icon: 'Utensils', permission: 'orders.view', section: 'Dining & Kitchen' },
  { name: 'Menu Management', path: '/menu-management', icon: 'Restaurant', permission: 'menu.view', section: 'Dining & Kitchen' },
  { name: 'Food Orders', path: '/chef/food-orders', icon: 'Food Orders', permission: 'kitchen.view', section: 'Dining & Kitchen' },
  { name: 'Pending Orders', path: '/chef/pending-orders', icon: 'Pending Orders', permission: 'kitchen.accept', section: 'Dining & Kitchen' },
  { name: 'Preparing Orders', path: '/chef/preparing-orders', icon: 'Preparing Orders', permission: 'kitchen.prepare', section: 'Dining & Kitchen' },
  { name: 'Served Orders', path: '/chef/served-orders', icon: 'Served Orders', permission: 'kitchen.mark_ready', section: 'Dining & Kitchen' },

  // Restaurant & Floor Operations (Permission-Driven)
  { name: 'Waiter Management', path: '/manager/waiters', icon: 'Waiters', permission: 'waiters.view', section: 'Operations' },
  { name: 'Restaurant Tables', path: '/manager/restaurant-tables', icon: 'Restaurant', permission: 'tables.view', section: 'Operations' },
  { name: 'Table Assignments', path: '/manager/table-assignments', icon: 'MapPin', permission: 'tables.assign', section: 'Operations' },
  { name: 'Assign Floors', path: '/manager/floor-assignment', icon: 'Manager', permission: 'floors.view', section: 'Operations' },
  { name: 'Daily Operations', path: '/manager/operations', icon: 'Operations', permission: 'reports.occupancy', section: 'Operations' },

  // Deliveries & Room Service (Permission-Driven)
  { name: 'Room Service Management', path: '/manager/delivery-management', icon: 'Truck', permission: 'delivery.reassign', section: 'Deliveries' },
  { name: 'Assigned Orders', path: '/waiter/assigned-orders', icon: 'Room Service', permission: 'delivery.accept', section: 'Deliveries' },
  { name: 'Ready for Pickup', path: '/waiter/ready-pickup', icon: 'Pending Orders', permission: 'delivery.pickup', section: 'Deliveries' },
  { name: 'On Delivery', path: '/waiter/on-delivery', icon: 'Truck', permission: 'delivery.deliver', section: 'Deliveries' },
  { name: 'Delivery History', path: '/waiter/delivery-history', icon: 'Reports', permission: 'delivery.view', section: 'Deliveries' },

  // Financials & Billing (Permission-Driven)
  { name: 'Payments & Billing', path: '/cashier/payments', icon: 'Payments', permission: 'payments.view', section: 'Billing' },
  { name: 'Financial Reports', path: '/cashier/reports', icon: 'Reports', permission: 'reports.sales', section: 'Billing' },

  // Reports & Analytics (Permission-Driven)
  { name: 'Reports & Analytics', path: '/reports', icon: 'Reports', permission: 'reports.view', section: 'Reports & Analytics' },

  // Reviews & Feedback (No permission for "My Reviews" - all authenticated users can see it)
  // { name: 'My Reviews', path: '/reviews', icon: 'Reviews', section: 'Reports & Analytics' },
  { name: 'Review Moderation', path: '/reviews/moderation', icon: 'Notifications', permission: 'reviews.moderate', section: 'Reports & Analytics' },
  { name: 'Review Analytics', path: '/reviews/analytics', icon: 'Analytics', permission: 'reviews.analytics', section: 'Reports & Analytics' },
]

const rolePermissionsMap = ref<Record<string, string[]>>({})

const loadRolePermissions = async () => {
  try {
    const rolesData = await rbacService.getRoles()
    const map: Record<string, string[]> = {}
    if (Array.isArray(rolesData)) {
      rolesData.forEach(role => {
        const slug = String(role.slug || role.name || '').toLowerCase()
        const perms = role.permissions ? role.permissions.map((p: any) => String(p.slug || p.name || '').toLowerCase()) : []
        map[slug] = perms
      })
    }
    rolePermissionsMap.value = map
  } catch (e) {
    console.warn('[Sidebar] Could not load role permissions map:', e)
  }
}

const userRoleSlug = computed(() => {
  return String(auth.user?.role || 'guest').toLowerCase()
})

const activeRouteRole = computed(() => {
  const parts = route.path.toLowerCase().split('/').filter(Boolean)
  return parts[0] || userRoleSlug.value
})

const userDashboardPath = computed(() => {
  if (auth.isAdmin) return '/admin'
  const role = userRoleSlug.value
  if (!role || role === 'guest') return '/orders'
  if (role === 'cashier') return '/cashier/dashboard'
  return `/${role}`
})
const userProfilePath = computed(() => {
  const role = userRoleSlug.value
  return `/${role}/profile`
})

const checkRoleHasPerm = (perms: string[] | undefined, perm: string): boolean => {
  if (!perms || !Array.isArray(perms)) return false
  return perms.includes(perm)
}
const menus = computed(() => {
  const items: MenuItem[] = [
    { name: 'Dashboard', path: userDashboardPath.value, icon: 'Dashboard', section: 'General' }
  ]

  const filteredOps = operationalMenuItems.filter(item => {
    if (item.permission) {
      const targetPerm = String(item.permission).toLowerCase().trim()
      if (auth.isAdmin && activeRouteRole.value === 'admin') return auth.can(targetPerm)

      const rolePerms = rolePermissionsMap.value[userRoleSlug.value]
      if (rolePerms && Array.isArray(rolePerms)) {
        return checkRoleHasPerm(rolePerms, targetPerm)
      }
      return auth.can(targetPerm)
    }
    return true
  })

  items.push(...filteredOps)
  items.push({ name: 'Profile Settings', path: userProfilePath.value, icon: 'Settings', section: 'Account' })

  return items
})

// Check if current user is admin
const isAdminUser = computed(() => {
  return auth.isAdmin || userRoleSlug.value === 'admin'
})

// Group menus by section
const groupedMenus = computed(() => {
  // For non-admin users, return all items in a single "General" section (flat list)
  if (!isAdminUser.value) {
    return {
      'General': menus.value
    }
  }

  // For admin users, group by sections as usual
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
  'General': false,
  'Administration': false,
})

const toggleSection = (section: string) => {
  openSections.value[section] = !openSections.value[section]
}

// Function Hoisted to prevent ReferenceError
function isActive(path: string): boolean {
  const current = route.path
  if (path === '/admin' || path === '/manager' || path === '/receptionist' || path === '/chef' || path === '/waiter' || path === '/cashier/dashboard') {
    return current === path
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

const handleKeyDown = (e: KeyboardEvent) => {
  if ((e.metaKey || e.ctrlKey) && e.key.toLowerCase() === 'k') {
    e.preventDefault()
    searchInputRef.value?.focus()
  }
}

onMounted(() => {
  loadRolePermissions()
  window.addEventListener('keydown', handleKeyDown)
  updateActiveSection()
})

watch(
  () => [auth.user, route.path],
  () => {
    loadRolePermissions()
  }
)

onUnmounted(() => {
  window.removeEventListener('keydown', handleKeyDown)
})
</script>

<template>
  <aside
    @mouseenter="sidebarStore.onMouseEnter()"
    @mouseleave="sidebarStore.onMouseLeave()"
    :class="[
      'h-screen bg-white/95 dark:bg-slate-950/95 text-slate-800 dark:text-slate-100 flex flex-col relative select-none flex-shrink-0 shadow-md dark:shadow-xl border-r border-slate-200/80 dark:border-slate-800/80 transition-all duration-300 overflow-hidden backdrop-blur-md',
      sidebarStore.sidebarWidth
    ]"
  >
    <!-- Header / Brand -->
    <div
      class="flex items-center bg-white dark:bg-slate-950 transition-all duration-300 border-b border-slate-200/80 dark:border-slate-800/80 flex-shrink-0"
      :class="sidebarStore.isExpanded ? 'h-16 px-5 justify-between' : 'h-16 py-3 px-3 flex-col justify-center'"
    >
      <!-- Expanded Brand & Title -->
      <template v-if="sidebarStore.isExpanded">
        <div class="flex items-center gap-3">
          <div class="w-9 h-9 rounded-xl bg-slate-100 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700/80 flex items-center justify-center p-1.5 shadow-xs dark:shadow-slate-950/50 flex-shrink-0">
            <img 
              src="/images/Hotel logo.png" 
              alt="Grand Horizon Hotel Logo" 
              class="w-full h-full object-contain"
            />
          </div>
        </div>
        
        <!-- Collapse/Expand Button -->
        <button
          @click="sidebarStore.toggleCollapse()"
          class="hidden lg:flex items-center justify-center w-7 h-7 rounded-lg border border-slate-200 dark:border-slate-800 bg-slate-100 dark:bg-slate-800/50 hover:bg-slate-200 dark:hover:bg-slate-800 text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white transition cursor-pointer flex-shrink-0"
          :title="sidebarStore.sidebarCollapsed ? 'Expand sidebar' : 'Collapse sidebar'"
        >
          <PanelLeft
            class="w-4 h-4 transition-transform duration-300"
            :class="sidebarStore.sidebarCollapsed ? 'rotate-180' : ''"
          />
        </button>
      </template>

      <!-- Collapsed Toggle Button -->
      <template v-else>
        <button
          @click="sidebarStore.toggleCollapse()"
          class="hidden lg:flex items-center justify-center w-9 h-9 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-100 dark:bg-slate-800/50 hover:bg-slate-200 dark:hover:bg-slate-800 text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white transition mx-auto cursor-pointer"
          title="Expand sidebar"
        >
          <PanelLeft class="w-4.5 h-4.5 transform rotate-180" />
        </button>
      </template>
    </div>

    <!-- Navigation Menu Container -->
    <nav 
      class="flex-1 min-h-0 py-3 overflow-y-auto transition-all duration-300 space-y-4 pr-1 pb-6"
      :class="sidebarStore.isExpanded ? 'px-3' : 'px-2'"
    >
      <!-- Search Input Bar -->
      <div v-if="sidebarStore.isExpanded" class="px-0.5 pb-1">
        <div class="relative flex items-center">
          <Search class="absolute left-3 w-3.5 h-3.5 text-slate-400 dark:text-slate-500 pointer-events-none" />
          <input
            ref="searchInputRef"
            v-model="searchQuery"
            type="text"
            placeholder="Search menu or type /"
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

      <!-- Menu Sections -->
      <div v-for="(items, section) in filteredGroupedMenus" :key="section" class="space-y-0.5">
        
        <!-- For NON-ADMIN users: Render everything as a flat list (all in General section) -->
        <template v-if="!isAdminUser && section === 'General'">
          <router-link
            v-for="menu in items"
            :key="menu.path"
            :to="menu.path"
            @click="handleNavigate"
            class="group relative flex items-center gap-3 rounded-lg transition-all duration-200"
            :class="[
              sidebarStore.isExpanded 
                ? 'px-4 py-3' 
                : 'justify-center py-3',
              isActive(menu.path)
                ? 'bg-blue-50 text-blue-700 dark:bg-blue-500/10 dark:text-blue-400 font-semibold'
                : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50 dark:text-slate-400 dark:hover:text-white dark:hover:bg-slate-800/50'
            ]"
            :title="!sidebarStore.isExpanded ? menu.name : ''"
          >
            <component
              :is="menuIcons[menu.icon] || menuIcons['Dashboard']"
              class="flex-shrink-0 w-5 h-5"
              :class="isActive(menu.path) ? 'text-blue-600 dark:text-blue-400' : 'text-slate-500 dark:text-slate-500'"
              :stroke-width="1.75"
            />
            <span
              v-if="sidebarStore.isExpanded"
              class="text-sm font-medium"
            >
              {{ menu.name }}
            </span>
            
            <!-- Tooltip for collapsed state -->
            <div
              v-if="!sidebarStore.isExpanded && !sidebarStore.hoverExpand"
              class="absolute left-full ml-3 px-3 py-1.5 bg-slate-900 dark:bg-slate-800 border border-slate-700 text-white text-xs font-semibold rounded-lg shadow-xl opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 whitespace-nowrap z-50 pointer-events-none"
            >
              {{ menu.name }}
            </div>
          </router-link>
        </template>

        <!-- For ADMIN users: Dashboard in General section -->
        <template v-else-if="isAdminUser && section === 'General'">
          <router-link
            :to="items[0].path"
            @click="handleNavigate"
            class="group relative flex items-center gap-3 rounded-lg transition-all duration-200"
            :class="[
              sidebarStore.isExpanded 
                ? 'px-4 py-3' 
                : 'justify-center py-3',
              isActive(items[0].path)
                ? 'bg-blue-50 text-blue-700 dark:bg-blue-500/10 dark:text-blue-400 font-semibold'
                : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50 dark:text-slate-400 dark:hover:text-white dark:hover:bg-slate-800/50'
            ]"
            :title="!sidebarStore.isExpanded ? items[0].name : ''"
          >
            <component
              :is="menuIcons[items[0].icon] || menuIcons['Dashboard']"
              class="flex-shrink-0 w-5 h-5"
              :class="isActive(items[0].path) ? 'text-blue-600 dark:text-blue-400' : 'text-slate-500 dark:text-slate-500'"
              :stroke-width="1.75"
            />
            <span
              v-if="sidebarStore.isExpanded"
              class="text-sm font-medium"
            >
              {{ items[0].name }}
            </span>
            
            <!-- Tooltip for collapsed state -->
            <div
              v-if="!sidebarStore.isExpanded && !sidebarStore.hoverExpand"
              class="absolute left-full ml-3 px-3 py-1.5 bg-slate-900 dark:bg-slate-800 border border-slate-700 text-white text-xs font-semibold rounded-lg shadow-xl opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 whitespace-nowrap z-50 pointer-events-none"
            >
              {{ items[0].name }}
            </div>
          </router-link>
        </template>

        <!-- For ADMIN users: Collapsible Sections (other than General) -->
        <template v-else-if="isAdminUser && section !== 'General'">
          <!-- Section Label (uppercase text label) - Only when expanded -->
          <div
            v-if="sidebarStore.isExpanded"
            class="px-4 pt-4 pb-1"
          >
            <p class="text-[11px] font-extrabold uppercase tracking-wider text-slate-400 dark:text-slate-500">
              {{ section }}
            </p>
          </div>

          <!-- When COLLAPSED: Show all menu items as icons -->
          <template v-if="!sidebarStore.isExpanded">
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
              :title="menu.name"
            >
              <component
                :is="menuIcons[menu.icon] || menuIcons['Dashboard']"
                class="flex-shrink-0 w-5 h-5"
                :class="isActive(menu.path) ? 'text-blue-600 dark:text-blue-400' : 'text-slate-500 dark:text-slate-500'"
                :stroke-width="1.75"
              />
              
              <!-- Tooltip -->
              <div
                v-if="!sidebarStore.hoverExpand"
                class="absolute left-full ml-3 px-3 py-1.5 bg-slate-900 dark:bg-slate-800 border border-slate-700 text-white text-xs font-semibold rounded-lg shadow-xl opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 whitespace-nowrap z-50 pointer-events-none"
              >
                {{ menu.name }}
              </div>
            </router-link>
          </template>

          <!-- When EXPANDED: Show collapsible section with nested items -->
          <template v-else>
            <!-- Section Header Button (Collapsible Trigger) -->
            <button
              v-if="items.length > 1"
              @click="toggleSection(section)"
              class="w-full flex items-center justify-between px-4 py-3 text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800/50 rounded-lg transition-all duration-200 group"
            >
              <div class="flex items-center gap-3">
                <component
                  :is="sectionIcons[section] || ShieldCheck"
                  class="w-5 h-5 text-slate-500 dark:text-slate-500 flex-shrink-0"
                  :stroke-width="1.75"
                />
                <span class="text-sm font-medium">{{ items[0]?.name || section }}</span>
              </div>
              
              <ChevronDown
                class="w-4 h-4 text-slate-400 dark:text-slate-500 transition-transform duration-300 flex-shrink-0"
                :class="openSections[section] ? '' : '-rotate-90'"
              />
            </button>

            <!-- Expandable Sub-items (Indented) -->
            <div
              v-show="openSections[section]"
              class="space-y-0.5 pl-4 transition-all duration-200"
            >
              <router-link
                v-for="menu in items.slice(items.length > 1 ? 0 : 0)"
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
                  {{ menu.name }}
                </span>
              </router-link>
            </div>

            <!-- Single Item Section (no collapsing) -->
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
                {{ items[0].name }}
              </span>
            </router-link>
          </template>
        </template>
      </div>
    </nav>

    <!-- Sleek 1-Row Footer User & Logout Button -->
    <div class="p-3 border-t border-slate-200/80 dark:border-slate-800/80 bg-slate-50/90 dark:bg-slate-950/90 flex items-center justify-between flex-shrink-0">
      <!-- User Profile Info -->
      <div class="flex items-center gap-2.5 min-w-0 flex-1">
        <div class="w-8 h-8 rounded-xl bg-blue-500/10 dark:bg-blue-500/20 text-blue-600 dark:text-blue-400 border border-blue-500/20 dark:border-blue-500/30 flex items-center justify-center font-black text-xs flex-shrink-0 shadow-xs">
          {{ (auth.user?.full_name || auth.user?.first_name || auth.user?.name || 'U').charAt(0).toUpperCase() }}
        </div>
        <div v-if="sidebarStore.isExpanded" class="flex-1 min-w-0 pr-1">
          <p class="text-xs font-bold text-slate-900 dark:text-white truncate leading-tight">{{ auth.user?.full_name || auth.user?.first_name || auth.user?.name || 'Administrator' }}</p>
          <p class="text-[10px] text-blue-600 dark:text-blue-400 font-semibold capitalize truncate mt-0.5 leading-tight">{{ auth.userRoleName || auth.user?.role || 'Admin' }}</p>
        </div>
      </div>

      <!-- Compact Logout Button -->
      <button
        @click="auth.logout()"
        class="p-2 rounded-xl text-rose-500 dark:text-rose-400 hover:bg-rose-500/10 hover:text-rose-600 dark:hover:text-rose-300 transition cursor-pointer flex-shrink-0"
        title="Sign Out"
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
