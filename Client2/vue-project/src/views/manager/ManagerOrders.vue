<script setup lang="ts">
import { onMounted } from 'vue'
import { useManagerStore } from '@/stores/managerStore'
import { useManagerOperationsStore } from '@/stores/manager/operationsStore'
import RestaurantMonitor from '@/components/manager/RestaurantMonitor.vue'
import DashboardLayout from '../../Layouts/DashboardLayout.vue'
import { ChefHat } from 'lucide-vue-next'

const manager = useManagerStore()
const operationsStore = useManagerOperationsStore()

onMounted(async () => {
  // Load from main manager store first (has aggregated data)
  if (!manager.orders || manager.orders.length === 0) {
    await manager.loadOrders()
  }
  // Also load operations store
  await operationsStore.loadOrders()
})
</script>

<style scoped>
@keyframes spin {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}

.animate-spin {
  animation: spin 1.5s linear infinite;
}
</style>

<template>
  <DashboardLayout>
    <div class="min-h-screen bg-gradient-to-br from-slate-50 via-white to-blue-50/30 dark:from-slate-950 dark:via-slate-900 dark:to-slate-900 py-4 md:py-6 transition-colors duration-300">
      <!-- PAGE HEADER -->
      <div class="mb-6 md:mb-8 border-b border-slate-200/60 dark:border-slate-700 bg-white/80 dark:bg-slate-900/80 backdrop-blur-sm rounded-lg p-4 md:p-6 transition-colors duration-300">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-2xl md:text-3xl font-bold text-slate-900 dark:text-slate-100 mb-2">Food Orders Management</h1>
            <p class="text-sm md:text-base text-slate-600 dark:text-slate-400">Monitor restaurant and room service orders</p>
          </div>
          <div class="w-12 h-12 bg-gradient-to-br from-orange-100 to-orange-50 dark:from-orange-900 dark:to-orange-800 rounded-xl flex items-center justify-center">
            <ChefHat class="w-6 h-6 text-orange-600 dark:text-orange-400" />
          </div>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="operationsStore.loading || manager.loading" class="flex justify-center items-center py-32">
        <div class="text-center">
          <div class="relative w-12 h-12">
            <svg class="absolute inset-0 w-full h-full" viewBox="0 0 100 100">
              <circle cx="50" cy="50" r="45" fill="none" stroke="#0EA5E9" stroke-width="6" opacity="0.3" />
            </svg>
            <div class="absolute inset-0 animate-spin" style="animation: spin 1.5s linear infinite;">
              <svg viewBox="0 0 100 100" class="w-full h-full">
                <circle cx="50" cy="50" r="45" fill="none" stroke="#FBBF24" stroke-width="8" stroke-linecap="round" stroke-dasharray="70 280" />
              </svg>
            </div>
          </div>
          <p class="text-slate-700 dark:text-yellow-300 font-semibold text-sm mt-4">Loading order data...</p>
        </div>
      </div>

      <!-- Error State -->
      <div v-if="(operationsStore.error || manager.error) && !operationsStore.loading && !manager.loading" class="bg-red-50/80 dark:bg-red-900/20 backdrop-blur-sm border border-red-200/60 dark:border-red-800 text-red-700 dark:text-red-400 p-6 rounded-xl mb-6">
        {{ operationsStore.error || manager.error }}
      </div>

      <!-- Content -->
      <div v-if="!operationsStore.loading && !manager.loading" class="space-y-6">
        <!-- Restaurant Monitor Component -->
        <RestaurantMonitor />

        <!-- Order Statistics -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
          <div class="bg-white dark:bg-slate-800 rounded-lg border border-slate-200/60 dark:border-slate-700 shadow-sm p-6 hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm text-slate-600 dark:text-slate-400 font-medium">Total Orders</p>
                <h3 class="mt-3 text-3xl font-bold text-slate-900 dark:text-slate-100">
                  {{ manager.orders.length }}
                </h3>
              </div>
              <div class="w-12 h-12 rounded-lg bg-purple-100 dark:bg-purple-900/50 flex items-center justify-center">
                <ChefHat class="w-6 h-6 text-purple-600 dark:text-purple-400" />
              </div>
            </div>
          </div>

          <div class="bg-white dark:bg-slate-800 rounded-lg border border-slate-200/60 dark:border-slate-700 shadow-sm p-6 hover:shadow-md transition-shadow">
            <p class="text-sm text-slate-600 dark:text-slate-400 font-medium">Pending</p>
            <h3 class="mt-3 text-3xl font-bold text-amber-600 dark:text-amber-400">
              {{ manager.pendingOrders.length }}
            </h3>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-2">Awaiting kitchen</p>
          </div>

          <div class="bg-white dark:bg-slate-800 rounded-lg border border-slate-200/60 dark:border-slate-700 shadow-sm p-6 hover:shadow-md transition-shadow">
            <p class="text-sm text-slate-600 dark:text-slate-400 font-medium">Preparing</p>
            <h3 class="mt-3 text-3xl font-bold text-blue-600 dark:text-blue-400">
              {{ manager.preparingOrders.length }}
            </h3>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-2">In progress</p>
          </div>

          <div class="bg-white dark:bg-slate-800 rounded-lg border border-slate-200/60 dark:border-slate-700 shadow-sm p-6 hover:shadow-md transition-shadow">
            <p class="text-sm text-slate-600 dark:text-slate-400 font-medium">Ready</p>
            <h3 class="mt-3 text-3xl font-bold text-emerald-600 dark:text-emerald-400">
              {{ manager.readyOrders.length }}
            </h3>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-2">Ready for delivery</p>
          </div>
        </div>

        <!-- Orders List -->
        <div class="bg-white dark:bg-slate-800 rounded-lg border border-slate-200/60 dark:border-slate-700 shadow-sm p-6">
          <h2 class="text-xl font-bold text-slate-900 dark:text-slate-100 mb-6">Recent Orders</h2>
          
          <div v-if="manager.orders.length === 0" class="text-center py-12">
            <p class="text-slate-500 dark:text-slate-400">No orders at this time</p>
          </div>

          <div v-else class="space-y-4">
            <div
              v-for="order in manager.orders.slice(0, 10)"
              :key="order.id"
              class="flex items-center justify-between p-4 rounded-lg border border-slate-200 dark:border-slate-700 hover:border-blue-300 dark:hover:border-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 transition"
            >
              <div class="flex items-center gap-4 flex-1">
                <div
                  :class="[
                    'w-10 h-10 rounded-lg flex items-center justify-center text-white font-bold text-xs',
                    order.status === 'pending' && 'bg-amber-500 dark:bg-amber-600',
                    order.status === 'preparing' && 'bg-blue-500 dark:bg-blue-600',
                    order.status === 'ready' && 'bg-emerald-500 dark:bg-emerald-600',
                    order.status === 'completed' && 'bg-slate-500 dark:bg-slate-600',
                  ]"
                >
                  #{{ order.orderNumber.split('-').pop() }}
                </div>
                <div class="flex-1">
                  <p class="font-semibold text-slate-900 dark:text-slate-100">{{ order.guestName }}</p>
                  <p class="text-sm text-slate-500 dark:text-slate-400">Room {{ order.roomNumber }} • {{ order.itemCount }} items • {{ order.total }} Birr</p>
                </div>
              </div>

              <div class="flex items-center gap-4">
                <span
                  :class="[
                    'px-3 py-1 rounded-full text-xs font-medium',
                    order.status === 'pending' && 'bg-amber-100 dark:bg-amber-900/50 text-amber-700 dark:text-amber-300',
                    order.status === 'preparing' && 'bg-blue-100 dark:bg-blue-900/50 text-blue-700 dark:text-blue-300',
                    order.status === 'ready' && 'bg-emerald-100 dark:bg-emerald-900/50 text-emerald-700 dark:text-emerald-300',
                    order.status === 'completed' && 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300',
                  ]"
                >
                  {{ order.status }}
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>
