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

<template>
  <DashboardLayout>
    <div class="min-h-screen bg-gradient-to-br from-slate-50 via-white to-blue-50/30">
      <!-- PAGE HEADER -->
      <div class="mb-8 border-b border-slate-200/60 bg-white/80 backdrop-blur-sm rounded-lg p-6">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-3xl font-bold text-slate-900 mb-2">Food Orders Management</h1>
            <p class="text-slate-600">Monitor restaurant and room service orders</p>
          </div>
          <div class="w-12 h-12 bg-gradient-to-br from-orange-100 to-orange-50 rounded-xl flex items-center justify-center">
            <ChefHat class="w-6 h-6 text-orange-600" />
          </div>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="operationsStore.loading || manager.loading" class="flex justify-center items-center py-32">
        <div class="text-center">
          <div class="animate-spin rounded-full h-12 w-12 border-4 border-slate-200 border-t-blue-600 mx-auto mb-4"></div>
          <p class="text-slate-600">Loading order data...</p>
        </div>
      </div>

      <!-- Error State -->
      <div v-if="(operationsStore.error || manager.error) && !operationsStore.loading && !manager.loading" class="bg-red-50/80 backdrop-blur-sm border border-red-200/60 text-red-700 p-6 rounded-xl mb-6">
        {{ operationsStore.error || manager.error }}
      </div>

      <!-- Content -->
      <div v-if="!operationsStore.loading && !manager.loading" class="space-y-6">
        <!-- Restaurant Monitor Component -->
        <RestaurantMonitor />

        <!-- Order Statistics -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
          <div class="bg-white rounded-lg border border-slate-200/60 shadow-sm p-6 hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm text-slate-600 font-medium">Total Orders</p>
                <h3 class="mt-3 text-3xl font-bold text-slate-900">
                  {{ manager.orders.length }}
                </h3>
              </div>
              <div class="w-12 h-12 rounded-lg bg-purple-100 flex items-center justify-center">
                <ChefHat class="w-6 h-6 text-purple-600" />
              </div>
            </div>
          </div>

          <div class="bg-white rounded-lg border border-slate-200/60 shadow-sm p-6 hover:shadow-md transition-shadow">
            <p class="text-sm text-slate-600 font-medium">Pending</p>
            <h3 class="mt-3 text-3xl font-bold text-amber-600">
              {{ manager.pendingOrders.length }}
            </h3>
            <p class="text-xs text-slate-500 mt-2">Awaiting kitchen</p>
          </div>

          <div class="bg-white rounded-lg border border-slate-200/60 shadow-sm p-6 hover:shadow-md transition-shadow">
            <p class="text-sm text-slate-600 font-medium">Preparing</p>
            <h3 class="mt-3 text-3xl font-bold text-blue-600">
              {{ manager.preparingOrders.length }}
            </h3>
            <p class="text-xs text-slate-500 mt-2">In progress</p>
          </div>

          <div class="bg-white rounded-lg border border-slate-200/60 shadow-sm p-6 hover:shadow-md transition-shadow">
            <p class="text-sm text-slate-600 font-medium">Ready</p>
            <h3 class="mt-3 text-3xl font-bold text-emerald-600">
              {{ manager.readyOrders.length }}
            </h3>
            <p class="text-xs text-slate-500 mt-2">Ready for delivery</p>
          </div>
        </div>

        <!-- Orders List -->
        <div class="bg-white rounded-lg border border-slate-200/60 shadow-sm p-6">
          <h2 class="text-xl font-bold mb-6">Recent Orders</h2>
          
          <div v-if="manager.orders.length === 0" class="text-center py-12">
            <p class="text-slate-500">No orders at this time</p>
          </div>

          <div v-else class="space-y-4">
            <div
              v-for="order in manager.orders.slice(0, 10)"
              :key="order.id"
              class="flex items-center justify-between p-4 rounded-lg border border-slate-200 hover:border-blue-300 hover:bg-blue-50 transition"
            >
              <div class="flex items-center gap-4 flex-1">
                <div
                  :class="[
                    'w-10 h-10 rounded-lg flex items-center justify-center text-white font-bold text-xs',
                    order.status === 'pending' && 'bg-amber-500',
                    order.status === 'preparing' && 'bg-blue-500',
                    order.status === 'ready' && 'bg-emerald-500',
                    order.status === 'completed' && 'bg-slate-500',
                  ]"
                >
                  #{{ order.orderNumber.split('-').pop() }}
                </div>
                <div class="flex-1">
                  <p class="font-semibold">{{ order.guestName }}</p>
                  <p class="text-sm text-slate-500">Room {{ order.roomNumber }} • {{ order.itemCount }} items • {{ order.total }} Birr</p>
                </div>
              </div>

              <div class="flex items-center gap-4">
                <span
                  :class="[
                    'px-3 py-1 rounded-full text-xs font-medium',
                    order.status === 'pending' && 'bg-amber-100 text-amber-700',
                    order.status === 'preparing' && 'bg-blue-100 text-blue-700',
                    order.status === 'ready' && 'bg-emerald-100 text-emerald-700',
                    order.status === 'completed' && 'bg-slate-100 text-slate-700',
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
