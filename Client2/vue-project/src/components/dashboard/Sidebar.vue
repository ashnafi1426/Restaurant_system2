<script setup lang="ts">
import { computed, type Component } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '../../stores/auth'
import { useSidebarStore } from '../../stores/sidebarStore'

// Import necessary Lucide components
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
  console.log('📱 Navigation clicked - closing sidebar')
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
}

const menus = computed(() => {
  switch (auth.user?.role) {
    case 'admin':
      return [
        { name: 'Dashboard', path: '/admin', icon: 'Dashboard'},
        { name: 'Users', path: '/users', icon: 'Users'},
        { name: 'Rooms', path: '/Admin/rooms', icon: 'Rooms'},
        { name: 'Room Types', path: '/room-types', icon: 'Room Types'},
        { name: 'Menu Management', path: '/menu-management', icon: 'Restaurant'},
        { name: 'Reports', path: '/reports', icon: 'Reports'},
      ]
    case 'receptionist':
      return [
        { name: 'Dashboard', path: '/receptionist', icon: 'Dashboard'},
        { name: 'Guests', path: '/guests', icon: 'Guests'},
        { name: 'Reservations', path: '/reservations', icon: 'Reservations'},
        { name: 'Check In', path: '/check-in', icon: 'Check In'},
        { name: 'Check Out', path: '/check-out', icon: 'Check Out'},
        { name: 'Reports', path: '/reports', icon: 'Reports'},
      ]
    case 'cashier':
      return [
        { name: 'Dashboard', path: '/cashier/dashboard', icon: 'Dashboard'},
        { name: 'Payments', path: '/cashier/payments', icon: 'Payments'},
        { name: 'Reports', path: '/cashier/reports', icon: 'Reports'},
      ]
    case 'chef':
      return [
        { name: 'Dashboard', path: '/chef', icon: 'Dashboard'},
        { name: 'Food Orders', path: '/chef/food-orders', icon: 'Food Orders', section: 'Orders' },
        { name: 'Pending Orders', path: '/chef/pending-orders', icon: 'Pending Orders', section: 'Orders' },
        { name: 'Preparing Orders', path: '/chef/preparing-orders', icon: 'Preparing Orders', section: 'Orders' },
        { name: 'Served Orders', path: '/chef/served-orders', icon: 'Served Orders', section: 'Orders' },
      ]
    case 'manager':
      return [
        { name: 'Dashboard', path: '/manager', icon: 'Dashboard'},
        { name: 'Waiter Management', path: '/manager/waiters', icon: 'Users2',},
        { name: 'Assign Floors', path: '/manager/floor-assignment', icon: 'Hotel'},
        { name: 'Daily Operations', path: '/manager/operations', icon: 'Operations'},
        { name: 'Room Service', path: '/manager/delivery-management', icon: 'Truck'},
        { name: 'Reports', path: '/manager/analytics', icon: 'Analytics'}
      ]
    case 'waiter':
      return [
        { name: 'Dashboard', path: '/waiter', icon: 'Dashboard'},
        { name: 'Assigned Orders', path: '/waiter/assigned-orders', icon: 'Room Service'},
        { name: 'Ready for Pickup', path: '/waiter/ready-pickup', icon: 'Pending Orders'},
        { name: 'On Delivery', path: '/waiter/on-delivery', icon: 'Truck'},
        { name: 'Completed Orders', path: '/waiter/completed-orders', icon: 'Served Orders'},
        { name: 'Delivery History', path: '/waiter/delivery-history', icon: 'FileSpreadsheet'},
        { name: 'Performance', path: '/waiter/performance', icon: 'TrendingUp'}
      ]
    default:
      return []
  }
})

// Group menus by section
const groupedMenus = computed(() => {
  const groups: Record<string, typeof menus.value> = {}
  menus.value.forEach(menu => {
    const section = menu.section || 'Main'
    if (!groups[section]) {
      groups[section] = []
    }
    groups[section].push(menu)
  })
  return groups
})

const isActive = (path: string): boolean => {
  return route.path === path || route.path.startsWith(path + '/')
}
</script>

<template>
  <aside
  @mouseenter="sidebarStore.onMouseEnter()"
  @mouseleave="sidebarStore.onMouseLeave()"
  :class="[
    'h-screen bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 flex flex-col relative select-none flex-shrink-0 shadow-lg dark:shadow-black/50 transition-all duration-300 overflow-hidden border-r border-slate-200 dark:border-slate-800',
    sidebarStore.sidebarWidth
  ]"
>
    <!-- Header / Brand -->
    <div
      class="flex items-center bg-white dark:bg-slate-900 transition-all duration-300"
      :class="sidebarStore.isExpanded ? 'h-16 px-6 justify-between' : 'h-auto py-4 px-3 flex-col justify-center'"
    >
      <!-- Expanded: Logo + Text + Collapse Button -->
      <template v-if="sidebarStore.isExpanded">
        <div class="flex items-center gap-3">
          <img 
            src="/images/Hotel logo.png" 
            alt="Hotel Logo" 
            class="w-10 h-10 object-contain"
          />
        </div>
        
        <!-- Collapse/Expand Button - Desktop Only -->
        <button
          @click="sidebarStore.toggleCollapse()"
          class="hidden lg:flex items-center justify-center w-10 h-10 rounded-lg border border-slate-200 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors flex-shrink-0"
          :title="sidebarStore.sidebarCollapsed ? 'Expand sidebar permanently' : 'Collapse sidebar'"
        >
          <PanelLeft
            class="w-5 h-5 text-slate-600 dark:text-slate-400 transition-transform duration-300"
            :class="sidebarStore.sidebarCollapsed ? 'rotate-180' : ''"
            :stroke-width="2"
          />
        </button>
      </template>

      <!-- Collapsed: Only Expand Button (No Logo) -->
      <template v-else>
        <!-- Expand Button - Desktop Only - Centered -->
        <button
          @click="sidebarStore.toggleCollapse()"
          class="hidden lg:flex items-center justify-center w-12 h-12 rounded-lg border border-slate-200 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors mx-auto"
          title="Expand sidebar permanently"
        >
          <PanelLeft
            class="w-5 h-5 text-slate-600 dark:text-slate-400 transform rotate-180"
            :stroke-width="2"
          />
        </button>
      </template>
    </div>

    <!-- Navigation -->
    <nav 
      class="flex-1 py-4 overflow-y-auto transition-all duration-300"
      :class="sidebarStore.isExpanded ? 'px-3' : 'px-4'"
    >
      <div v-for="(items, section) in groupedMenus" :key="section">
        <!-- Three dot separator between sections (collapsed only) -->
        <div
          v-if="!sidebarStore.isExpanded && section !== 'Main'"
          class="flex items-center justify-center py-3 my-2"
        >
          <div class="flex gap-1">
            <span class="w-1 h-1 rounded-full bg-slate-300 dark:bg-slate-600"></span>
            <span class="w-1 h-1 rounded-full bg-slate-300 dark:bg-slate-600"></span>
            <span class="w-1 h-1 rounded-full bg-slate-300 dark:bg-slate-600"></span>
          </div>
        </div>

        <div>
          <!-- Section Title - Hidden when collapsed -->
          <p
            v-if="sidebarStore.isExpanded"
            class="px-3 text-[9px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-[0.12em] mb-3 transition-opacity duration-300"
          >
            {{ section }}
          </p>

          <div :class="sidebarStore.isExpanded ? 'space-y-1' : 'space-y-3'">
            <router-link
              v-for="menu in items"
              :key="menu.path"
              :to="menu.path"
              @click="handleNavigate"
              class="group relative flex items-center rounded-lg transition-all duration-200"
              :class="[
                sidebarStore.isExpanded 
                  ? 'gap-3 px-3 py-2.5' 
                  : 'justify-center py-3',
                isActive(menu.path)
                  ? sidebarStore.isExpanded
                    ? 'bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400'
                    : ''
                  : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-100 hover:bg-slate-50 dark:hover:bg-slate-800/50',
              ]"
              :title="!sidebarStore.isExpanded ? menu.name : ''"
            >
              <!-- Icon -->
              <component
                :is="menuIcons[menu.icon] || menuIcons['Dashboard']"
                class="flex-shrink-0 transition-all duration-200 w-5 h-5"
                :class="[
                  isActive(menu.path) && sidebarStore.isExpanded
                    ? 'text-blue-600 dark:text-blue-400' 
                    : !sidebarStore.isExpanded
                    ? 'text-slate-400 dark:text-slate-500'
                    : 'text-slate-400 dark:text-slate-500 group-hover:text-slate-600 dark:group-hover:text-slate-300',
                ]"
                :stroke-width="1.5"
              />

              <!-- Menu Name - Hidden when collapsed -->
              <span
                v-if="sidebarStore.isExpanded"
                class="flex-1 transition-all duration-200 truncate text-sm font-medium"
              >
                {{ menu.name }}
              </span>

              <!-- Badges - Hidden when collapsed -->
              <span
                v-if="sidebarStore.isExpanded && (menu.name === 'Pending Orders' || menu.name === 'Check In' || menu.name === 'Complaints')"
                class="text-[10px] font-bold bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400 px-2 py-0.5 rounded-full flex-shrink-0"
              >
                {{ menu.name === 'Check In' ? '5' : menu.name === 'Complaints' ? '3' : '12' }}
              </span>

              <!-- Tooltip for collapsed state (only when hover is NOT active) -->
              <div
                v-if="!sidebarStore.isExpanded && !sidebarStore.hoverExpand"
                class="absolute left-full ml-3 px-3 py-2 bg-slate-900 dark:bg-slate-700 text-white text-xs font-medium rounded-md shadow-lg opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 whitespace-nowrap z-50 pointer-events-none"
              >
                {{ menu.name }}
                <div class="absolute right-full top-1/2 -translate-y-1/2 border-4 border-transparent border-r-slate-900 dark:border-r-slate-700"></div>
              </div>
            </router-link>
          </div>
        </div>
      </div>
    </nav>

    <!-- Logout Button -->
    <div 
      class="py-3 border-t border-slate-200 dark:border-slate-700 flex-shrink-0 bg-white dark:bg-slate-900 transition-all duration-300"
      :class="sidebarStore.isCollapsed ? 'px-4' : 'px-3'"
    >
      <button
        @click="auth.logout()"
        class="w-full group relative flex items-center rounded-lg text-sm font-medium text-slate-600 dark:text-slate-400 hover:text-red-600 dark:hover:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/10 transition-all duration-200"
        :class="sidebarStore.isCollapsed ? 'justify-center py-3' : 'gap-3 px-3 py-2.5'"
        :title="sidebarStore.isCollapsed ? 'Logout' : ''"
      >
        <LogOut
          class="flex-shrink-0 text-slate-400 dark:text-slate-500 group-hover:text-red-600 dark:group-hover:text-red-400 transition-all duration-200"
          :class="sidebarStore.isCollapsed ? 'w-5 h-5' : 'w-5 h-5'"
          :stroke-width="1.5"
        />
        <span v-if="!sidebarStore.isCollapsed">Logout</span>

        <!-- Tooltip for collapsed state -->
        <div
          v-if="sidebarStore.isCollapsed"
          class="absolute left-full ml-3 px-3 py-2 bg-slate-900 dark:bg-slate-700 text-white text-xs font-medium rounded-md shadow-lg opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 whitespace-nowrap z-50 pointer-events-none"
        >
          Logout
          <div class="absolute right-full top-1/2 -translate-y-1/2 border-4 border-transparent border-r-slate-900 dark:border-r-slate-700"></div>
        </div>
      </button>
    </div>

    <!-- Footer -->
    <div
      class="py-3 border-t border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 flex-shrink-0 transition-all duration-300 overflow-hidden"
      :class="sidebarStore.isCollapsed ? 'px-4' : 'px-4 md:px-5'"
    >
      <!-- Collapsed: Just live indicator centered -->
      <div 
        v-if="sidebarStore.isCollapsed"
        class="flex justify-center"
      >
        <span class="relative flex h-2 w-2">
          <span
            class="absolute inline-flex h-full w-full rounded-full bg-emerald-400/60 dark:bg-emerald-500/60 animate-pulse"
          ></span>
          <span
            class="relative inline-flex rounded-full h-2 w-2 bg-emerald-400 dark:bg-emerald-500"
          ></span>
        </span>
      </div>

      <!-- Expanded: Full footer -->
      <div 
        v-else
        class="flex items-center justify-between"
      >
        <span class="text-[9px] font-medium text-slate-500 dark:text-slate-400 tracking-wide"
          >v2.0.0</span
        >
        <div class="flex items-center gap-2">
          <span class="relative flex h-2 w-2">
            <span
              class="absolute inline-flex h-full w-full rounded-full bg-emerald-400/60 dark:bg-emerald-500/60 animate-pulse"
            ></span>
            <span
              class="relative inline-flex rounded-full h-2 w-2 bg-emerald-400 dark:bg-emerald-500"
            ></span>
          </span>
          <span class="text-[9px] font-medium text-slate-500 dark:text-slate-400">Live</span>
        </div>
      </div>
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

/* Dark mode scrollbar */
.dark nav::-webkit-scrollbar-thumb {
  background: #475569;
}

.dark nav::-webkit-scrollbar-thumb:hover {
  background: #64748b;
}

.router-link-active:focus-visible,
button:focus-visible {
  outline: 2px solid rgb(37, 99, 235);
  outline-offset: 2px;
}

/* Smooth transitions */
.router-link-active,
button {
  transition: all 0.15s ease;
}
</style>
