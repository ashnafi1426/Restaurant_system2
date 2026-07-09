<script setup lang="ts">
import { computed, type Component } from 'vue'
import { useRoute } from 'vue-router'
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
  FileSpreadsheet
} from 'lucide-vue-next'

const route = useRoute()
const auth = useAuthStore()

// Map string identifiers to their respective Lucide component definitions
const menuIcons: Record<string, Component> = {
  Dashboard: LayoutDashboard,
  Users: Users,
  Rooms: Hotel,
  'Room Types': BedDouble,
  Reports: FileText,
  Restaurant: UtensilsCrossed, // Fixed missing mapping from the original script
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
        { name: 'Rooms', path: '/rooms', icon: 'Rooms' },
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
        { name: 'Food Orders', path: '/food-orders', icon: 'Food Orders' },
        { name: 'Pending Orders', path: '/pending-orders', icon: 'Pending Orders' },
        { name: 'Preparing Orders', path: '/preparing-orders', icon: 'Preparing Orders' },
        { name: 'Served Orders', path: '/served-orders', icon: 'Served Orders' },
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
    class="w-64 h-screen sticky top-0 left-0 bg-zinc-950 border-r border-zinc-800/60 text-zinc-200 flex flex-col relative select-none flex-shrink-0"
  >
    <div class="flex items-center gap-3 px-5 h-16 border-b border-zinc-800/50 flex-shrink-0">
      <div
        class="w-8 h-8 bg-gradient-to-br from-indigo-500 via-blue-600 to-blue-700 rounded-lg flex items-center justify-center shadow-lg shadow-blue-500/10 flex-shrink-0 ring-1 ring-white/10"
      >
        <Hotel class="w-4 h-4 text-white" :stroke-width="2.5" />
      </div>

      <div class="min-w-0 flex flex-col">
        <h1 class="font-semibold text-sm text-zinc-100 tracking-tight leading-tight">Hotel HMS</h1>
        <p class="text-[10px] text-zinc-400 font-semibold uppercase tracking-[0.10em] mt-0.5">
          {{ auth.user?.role || 'Management' }}
        </p>
      </div>
    </div>

    <nav class="flex-1 px-3 py-4 overflow-y-auto space-y-6">
      <div>
        <p class="px-3 text-[10px] font-bold text-zinc-500 uppercase tracking-widest mb-2">
          Main Menu
        </p>

        <div class="space-y-1">
          <router-link
            v-for="menu in menus"
            :key="menu.path"
            :to="menu.path"
            class="group relative flex items-center gap-3 px-3 py-2 rounded-md transition-all duration-200 text-[13px] font-medium border border-transparent"
            :class="[
              isActive(menu.path)
                ? 'bg-zinc-800 text-white border-zinc-700/50 shadow-sm'
                : 'text-zinc-400 hover:text-zinc-100 hover:bg-zinc-900',
            ]"
          >
            <div
              class="absolute left-0 top-2 bottom-2 w-[3px] rounded-r bg-blue-500 transition-transform scale-y-0"
              :class="{ 'scale-y-100': isActive(menu.path) }"
            ></div>

            <component
              :is="menuIcons[menu.icon] || menuIcons['Dashboard']"
              class="w-[18px] h-[18px] flex-shrink-0 transition-colors duration-200"
              :class="[
                isActive(menu.path) ? 'text-blue-400' : 'text-zinc-500 group-hover:text-zinc-400',
              ]"
              :stroke-width="1.75"
            />

            <span class="flex-1 transition-transform group-hover:translate-x-0.5 duration-200">
              {{ menu.name }}
            </span>

            <span
              v-if="menu.name === 'Pending Orders'"
              class="text-[10px] font-bold bg-amber-500/10 text-amber-400 border border-amber-500/20 px-2 py-0.5 rounded-full"
            >
              12
            </span>
            <span
              v-if="menu.name === 'Check In'"
              class="text-[10px] font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 px-2 py-0.5 rounded-full"
            >
              5
            </span>
          </router-link>
        </div>
      </div>
    </nav>

    <div class="px-3 py-3 border-t border-zinc-800/50 flex-shrink-0">
      <button
        @click="auth.logout()"
        class="w-full group flex items-center gap-3 px-3 py-2 rounded-md text-[13px] font-medium text-zinc-400 hover:text-zinc-100 hover:bg-zinc-900 border border-transparent transition-all duration-200"
      >
        <LogOut 
          class="w-[18px] h-[18px] flex-shrink-0 text-zinc-500 group-hover:text-zinc-400 transition-colors duration-200" 
          :stroke-width="1.75" 
        />
        <span>Logout</span>
      </button>
    </div>

    <div class="px-5 py-3 border-t border-zinc-800/50 bg-zinc-950 flex-shrink-0">
      <div class="flex items-center justify-between">
        <span class="text-[10px] font-medium text-zinc-600 tracking-wide">v2.0.0</span>
        <div class="flex items-center gap-2">
          <span class="relative flex h-1.5 w-1.5">
            <span
              class="absolute inline-flex h-full w-full rounded-full bg-emerald-500/40 opacity-75 animate-ping"
            ></span>
            <span class="relative inline-flex rounded-full h-1.5 w-1.5 bg-emerald-500"></span>
          </span>
          <span class="text-[10px] font-medium text-zinc-400">System Live</span>
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
  background: rgba(255, 255, 255, 0.05);
  border-radius: 9999px;
}
nav::-webkit-scrollbar-thumb:hover {
  background: rgba(255, 255, 255, 0.12);
}
.router-link-active:focus-visible,
button:focus-visible {
  outline: 2px solid rgb(59, 130, 246);
  outline-offset: -1px;
}
</style>