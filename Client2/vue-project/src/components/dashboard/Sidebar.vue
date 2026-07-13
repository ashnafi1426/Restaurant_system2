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

// Map string identifiers to their respective Lucide component definitions
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
}

const menus = computed(() => {
  switch (auth.user?.role) {
    case 'admin':
      return [
        { name: 'Dashboard', path: '/admin', icon: 'Dashboard' },
        { name: 'Users', path: '/users', icon: 'Users' },
        { name: 'Rooms', path: '/Admin/rooms', icon: 'Rooms' },
        { name: 'Room Types', path: '/room-types', icon: 'Room Types' },
        { name: 'Reports', path: '/reports', icon: 'Reports' },
        { name: 'Menu Management', path: '/menu-management', icon: 'Restaurant' },
      ]
    case 'receptionist':
      return [
        { name: 'Dashboard', path: '/receptionist', icon: 'Dashboard' },
        { name: 'Guests', path: '/guests', icon: 'Guests' },
        { name: 'Reservations', path: '/reservations', icon: 'Reservations' },
        { name: 'Check In', path: '/check-in', icon: 'Check In' },
        { name: 'Check Out', path: '/check-out', icon: 'Check Out' },
        { name: 'Orders', path: '/orders', icon: 'Food Orders' },
      ]
    case 'cashier':
      return [
        { name: 'Dashboard', path: '/cashier', icon: 'Dashboard' },
        { name: 'Invoices', path: '/invoices', icon: 'Invoices' },
        { name: 'Payments', path: '/payments', icon: 'Payments' },
        { name: 'Transactions', path: '/transactions', icon: 'Transactions' },
        { name: 'Refunds', path: '/refunds', icon: 'Refunds' },
      ]
    case 'chef':
      return [
        { name: 'Dashboard', path: '/chef', icon: 'Dashboard' },
        { name: 'Food Orders', path: '/chef/food-orders', icon: 'Food Orders' },
        { name: 'Pending Orders', path: '/chef/pending-orders', icon: 'Pending Orders' },
        { name: 'Preparing Orders', path: '/chef/preparing-orders', icon: 'Preparing Orders' },
        { name: 'Served Orders', path: '/chef/served-orders', icon: 'Served Orders' },
      ]
    case 'manager':
      return [
        { name: 'Dashboard', path: '/manager', icon: 'Dashboard' },
        { name: 'Revenue Report', path: '/revenue-report', icon: 'Revenue Report' },
        { name: 'Occupancy Report', path: '/occupancy-report', icon: 'Occupancy Report' },
        { name: 'Reservation Report', path: '/reservation-report', icon: 'Reservation Report' },
        { name: 'Payment Report', path: '/payment-report', icon: 'Payment Report' },
      ]
    default:
      return []
  }
})

const isActive = (path: string): boolean => {
  return route.path === path || route.path.startsWith(path + '/')
}
</script>

<template>
  <aside
    class="w-64 h-screen bg-white border-r border-gray-200 text-gray-700 flex flex-col relative select-none flex-shrink-0 shadow-sm"
  >
    <!-- Header / Brand -->
    <div
      class="flex items-center gap-2 sm:gap-3 px-3 sm:px-4 md:px-5 h-14 sm:h-16 border-b border-gray-200 flex-shrink-0 bg-gradient-to-r from-blue-50 to-indigo-50"
    >
      <div
        class="w-8 sm:w-9 h-8 sm:h-9 bg-gradient-to-br from-blue-600 via-blue-700 to-indigo-700 rounded-xl flex items-center justify-center shadow-lg shadow-blue-500/20 flex-shrink-0"
      >
        <Hotel class="w-4 sm:w-5 h-4 sm:h-5 text-white" :stroke-width="2.5" />
      </div>

      <div class="min-w-0 flex flex-col">
        <h1
          class="font-bold text-sm sm:text-base text-gray-800 tracking-tight leading-tight truncate"
        >
          Hotel HMS
        </h1>
        <p
          class="text-[9px] sm:text-[10px] text-gray-500 font-semibold uppercase tracking-[0.08em] mt-0.5"
        >
          {{ auth.user?.role || 'Management' }}
        </p>
      </div>
    </div>

    <!-- Navigation -->
    <nav class="flex-1 px-2 sm:px-3 py-4 sm:py-5 overflow-y-auto space-y-4 sm:space-y-6">
      <div>
        <p
          class="px-2 sm:px-3 text-[9px] sm:text-[10px] font-bold text-gray-400 uppercase tracking-[0.12em] mb-2 sm:mb-3"
        >
          Main Menu
        </p>

        <div class="space-y-1">
          <router-link
            v-for="menu in menus"
            :key="menu.path"
            :to="menu.path"
            @click="handleNavigate"
            class="group relative flex items-center gap-2 sm:gap-3 px-2 sm:px-3 py-2 sm:py-2.5 rounded-lg transition-all duration-200 text-xs sm:text-sm font-medium border border-transparent"
            :class="[
              isActive(menu.path)
                ? 'bg-blue-50 text-blue-700 border-blue-200/60 shadow-sm'
                : 'text-gray-600 hover:text-gray-800 hover:bg-gray-50',
            ]"
          >
            <!-- Active indicator bar -->
            <div
              class="absolute left-0 top-1.5 bottom-1.5 w-[3px] rounded-r-full bg-blue-600 transition-transform scale-y-0"
              :class="{ 'scale-y-100': isActive(menu.path) }"
            ></div>

            <!-- Icon -->
            <component
              :is="menuIcons[menu.icon] || menuIcons['Dashboard']"
              class="w-4 sm:w-[18px] h-4 sm:h-[18px] flex-shrink-0 transition-colors duration-200"
              :class="[
                isActive(menu.path) ? 'text-blue-600' : 'text-gray-400 group-hover:text-gray-600',
              ]"
              :stroke-width="1.75"
            />

            <!-- Menu Name -->
            <span
              class="flex-1 transition-transform group-hover:translate-x-0.5 duration-200 truncate"
            >
              {{ menu.name }}
            </span>

            <!-- Badges -->
            <span
              v-if="menu.name === 'Pending Orders'"
              class="text-[8px] sm:text-[10px] font-bold bg-amber-100 text-amber-700 px-1.5 sm:px-2 py-0.5 rounded-full flex-shrink-0"
            >
              12
            </span>
            <span
              v-if="menu.name === 'Check In'"
              class="text-[8px] sm:text-[10px] font-bold bg-emerald-100 text-emerald-700 px-1.5 sm:px-2 py-0.5 rounded-full flex-shrink-0"
            >
              5
            </span>
          </router-link>
        </div>
      </div>
    </nav>

    <!-- Logout Button -->
    <div class="px-2 sm:px-3 py-2 sm:py-3 border-t border-gray-200 flex-shrink-0 bg-gray-50/50">
      <button
        @click="auth.logout()"
        class="w-full group flex items-center gap-2 sm:gap-3 px-2 sm:px-3 py-2 sm:py-2.5 rounded-lg text-xs sm:text-sm font-medium text-gray-600 hover:text-gray-800 hover:bg-gray-100 border border-transparent transition-all duration-200"
      >
        <LogOut
          class="w-4 sm:w-[18px] h-4 sm:h-[18px] flex-shrink-0 text-gray-400 group-hover:text-gray-600 transition-colors duration-200"
          :stroke-width="1.75"
        />
        <span class="hidden sm:inline">Logout</span>
      </button>
    </div>

    <!-- Footer -->
    <div
      class="px-3 sm:px-4 md:px-5 py-2 sm:py-3 border-t border-gray-200 bg-gray-50/50 flex-shrink-0"
    >
      <div class="flex items-center justify-between">
        <span class="text-[8px] sm:text-[10px] font-medium text-gray-400 tracking-wide"
          >v2.0.0</span
        >
        <div class="flex items-center gap-1.5 sm:gap-2">
          <span class="relative flex h-1.5 sm:h-2 w-1.5 sm:w-2">
            <span
              class="absolute inline-flex h-full w-full rounded-full bg-emerald-400/60 animate-ping"
            ></span>
            <span
              class="relative inline-flex rounded-full h-1.5 sm:h-2 w-1.5 sm:w-2 bg-emerald-500"
            ></span>
          </span>
          <span class="text-[8px] sm:text-[10px] font-medium text-gray-500 hidden sm:inline"
            >System Live</span
          >
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
  background: #e5e7eb;
  border-radius: 9999px;
}
nav::-webkit-scrollbar-thumb:hover {
  background: #d1d5db;
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
