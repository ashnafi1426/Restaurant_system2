<script setup lang="ts">
import { onMounted } from 'vue'
import { useManagerOperationsStore } from '@/stores/manager/operationsStore'
import RestaurantMonitor from '@/components/manager/RestaurantMonitor.vue'
import RoomServiceMonitor from '@/components/manager/RoomServiceMonitor.vue'
import DashboardLayout from '../../Layouts/DashboardLayout.vue'
import {
  Clock,
  ChefHat,
  Truck,
  Shirt,
  Loader2,
  AlertCircle
} from 'lucide-vue-next'

const operationsStore = useManagerOperationsStore()

onMounted(async () => {
  await operationsStore.initialize()
})
</script>

<template>
  <DashboardLayout>
    <div class="space-y-6 bg-slate-50 dark:bg-slate-950 min-h-screen p-4 sm:p-6 max-w-full overflow-hidden font-sans">
      <!-- Welcome Header -->
      <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-6 rounded-3xl shadow-xs">
        <h1 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">Daily Operations</h1>
        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Real-time monitoring of all hotel restaurant, room service, and laundry operations.</p>
      </div>

      <!-- Loading State -->
      <div v-if="operationsStore.loading" class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 p-16 text-center space-y-3">
        <Loader2 class="w-8 h-8 text-amber-500 animate-spin mx-auto" />
        <p class="text-xs font-bold text-slate-500 dark:text-slate-400">Initializing live operations dashboard...</p>
      </div>

      <!-- Error State -->
      <div v-else-if="operationsStore.error" class="p-4 rounded-2xl bg-rose-500/10 border border-rose-500/30 text-rose-700 dark:text-rose-300 text-xs font-bold flex items-center gap-2.5">
        <AlertCircle class="w-4 h-4 text-rose-500 flex-shrink-0" />
        <span>{{ operationsStore.error }}</span>
      </div>

      <!-- Content Grid -->
      <div v-else class="space-y-6">
        <!-- Restaurant & Room Service Dual Monitor -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <RestaurantMonitor />
          <RoomServiceMonitor />
        </div>

        <!-- Summary Stats Metrics -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
          <!-- Pending Orders -->
          <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-5 shadow-xs flex items-center justify-between">
            <div>
              <p class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Pending Orders</p>
              <h3 class="text-2xl font-black text-rose-600 dark:text-rose-400 mt-1">{{ operationsStore.pendingOrders.length }}</h3>
            </div>
            <div class="p-3 rounded-2xl bg-rose-500/10 text-rose-600 dark:text-rose-400">
              <Clock class="w-5 h-5" />
            </div>
          </div>

          <!-- Preparing Orders -->
          <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-5 shadow-xs flex items-center justify-between">
            <div>
              <p class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Preparing Orders</p>
              <h3 class="text-2xl font-black text-amber-600 dark:text-amber-400 mt-1">{{ operationsStore.preparingOrders.length }}</h3>
            </div>
            <div class="p-3 rounded-2xl bg-amber-500/10 text-amber-600 dark:text-amber-400">
              <ChefHat class="w-5 h-5" />
            </div>
          </div>

          <!-- Active Deliveries -->
          <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-5 shadow-xs flex items-center justify-between">
            <div>
              <p class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Active Deliveries</p>
              <h3 class="text-2xl font-black text-blue-600 dark:text-blue-400 mt-1">{{ operationsStore.activeDeliveries.length }}</h3>
            </div>
            <div class="p-3 rounded-2xl bg-blue-500/10 text-blue-600 dark:text-blue-400">
              <Truck class="w-5 h-5" />
            </div>
          </div>

          <!-- Pending Laundry -->
          <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-5 shadow-xs flex items-center justify-between">
            <div>
              <p class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Pending Laundry</p>
              <h3 class="text-2xl font-black text-purple-600 dark:text-purple-400 mt-1">{{ operationsStore.pendingLaundry.length }}</h3>
            </div>
            <div class="p-3 rounded-2xl bg-purple-500/10 text-purple-600 dark:text-purple-400">
              <Shirt class="w-5 h-5" />
            </div>
          </div>
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>
