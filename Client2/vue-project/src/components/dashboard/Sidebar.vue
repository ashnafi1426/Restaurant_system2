<script setup lang="ts">
import { computed, type Component } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '../../stores/auth'

// Import necessary Lucide components
import {
  LayoutDashboard,
  Users,
  Hotel,
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
} from 'lucide-vue-next'

// Define emits
const emit = defineEmits<{
  navigate: []
}>()

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()

// Handler for navigation
const handleNavigate = () => {
  emit('navigate')
  console.log('📱 Navigation clicked - closing sidebar')
}
const menuIcons: Record<string, Component> = {
  Dashboard: LayoutDashboard,
  Users: Users,
  Rooms: Hotel,
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
        // { name: 'Orders', path: '/orders', icon: 'Food Orders'},
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
    class="w-64 h-screen bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 flex flex-col relative select-none flex-shrink-0 shadow-lg dark:shadow-slate-950/50 transition-colors"
  >
    <!-- Header / Brand -->
    <div
      class="flex items-center gap-3 px-4 md:px-5 h-16 border-b border-slate-200 dark:border-slate-700 flex-shrink-0 bg-white dark:bg-slate-900 transition-colors"
    >
      <div
        class="w-9 h-9 bg-gradient-to-br from-blue-500 via-blue-600 to-indigo-700 rounded-xl flex items-center justify-center shadow-lg shadow-blue-500/30 flex-shrink-0"
      >
        <Hotel class="w-5 h-5 text-white" :stroke-width="2.5" />
      </div>

      <div class="min-w-0 flex flex-col">
        <h1
          class="font-bold text-sm text-slate-900 dark:text-slate-100 tracking-tight leading-tight truncate"
        >
          Executive Horizon
        </h1>
        <p
          class="text-[9px] text-slate-500 dark:text-slate-400 font-semibold uppercase tracking-[0.08em] mt-0.5"
        >
          Hospitality Suite
        </p>
      </div>
    </div>

    <!-- Navigation -->
    <nav class="flex-1 px-3 py-5 overflow-y-auto space-y-6">
      <template v-for="(items, section) in groupedMenus" :key="section">
        <div>
          <p
            class="px-3 text-[9px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-[0.12em] mb-3"
          >
            {{ section }}
          </p>

          <div class="space-y-1">
            <router-link
              v-for="menu in items"
              :key="menu.path"
              :to="menu.path"
              @click="handleNavigate"
              class="group relative flex items-center gap-3 px-3 py-2.5 rounded-lg transition-all duration-300 text-sm font-medium border border-transparent"
              :class="[
                isActive(menu.path)
                  ? 'bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300 border-blue-300 dark:border-blue-700 shadow-sm'
                  : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-100 hover:bg-slate-100 dark:hover:bg-slate-800 hover:border-slate-200 dark:hover:border-slate-700',
              ]"
            >
              <!-- Active indicator bar - Left side -->
              <div
                class="absolute left-0 top-1.5 bottom-1.5 w-1 rounded-r-full bg-gradient-to-b from-blue-400 to-blue-600 transition-all duration-300 scale-y-0"
                :class="{ 'scale-y-100': isActive(menu.path) }"
              ></div>

              <!-- Icon -->
              <component
                :is="menuIcons[menu.icon] || menuIcons['Dashboard']"
                class="w-5 h-5 flex-shrink-0 transition-all duration-300"
                :class="[
                  isActive(menu.path) 
                    ? 'text-blue-600 dark:text-blue-400 scale-110' 
                    : 'text-slate-400 dark:text-slate-500 group-hover:text-slate-600 dark:group-hover:text-slate-300 group-hover:scale-105',
                ]"
                :stroke-width="1.75"
              />

              <!-- Menu Name -->
              <span
                class="flex-1 transition-all duration-300 group-hover:translate-x-1 truncate"
              >
                {{ menu.name }}
              </span>

              <!-- Badges -->
              <span
                v-if="menu.name === 'Pending Orders' || menu.name === 'Check In' || menu.name === 'Complaints'"
                class="text-[9px] font-bold bg-gradient-to-r from-amber-500/30 dark:from-amber-600/30 to-amber-600/30 dark:to-amber-700/30 text-amber-700 dark:text-amber-300 px-2 py-0.5 rounded-full flex-shrink-0 border border-amber-500/20 dark:border-amber-600/30"
              >
                {{ menu.name === 'Check In' ? '5' : menu.name === 'Complaints' ? '3' : '12' }}
              </span>
            </router-link>
          </div>
        </div>
      </template>
    </nav>

    <!-- Logout Button -->
    <div class="px-3 py-3 border-t border-slate-200 dark:border-slate-700 flex-shrink-0 bg-slate-50 dark:bg-slate-800 transition-colors">
      <button
        @click="auth.logout()"
        class="w-full group flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-slate-600 dark:text-slate-300 hover:text-red-700 dark:hover:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 border border-transparent hover:border-red-200 dark:hover:border-red-700/50 transition-all duration-300"
      >
        <LogOut
          class="w-5 h-5 flex-shrink-0 text-slate-400 dark:text-slate-500 group-hover:text-red-600 dark:group-hover:text-red-400 transition-colors duration-300"
          :stroke-width="1.75"
        />
        <span>Logout</span>
      </button>
    </div>

    <!-- Footer -->
    <div
      class="px-4 md:px-5 py-3 border-t border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 flex-shrink-0 transition-colors"
    >
      <div class="flex items-center justify-between">
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
