<script setup lang="ts">
import { computed } from 'vue'
import { ChefHat, Clock, CheckCircle2, AlertCircle } from 'lucide-vue-next'
import { useManagerOperationsStore } from '@/stores/manager/operationsStore'

const operationsStore = useManagerOperationsStore()

const orderStats = computed(() => {
  return {
    total: operationsStore.orders.length,
    pending: operationsStore.pendingOrders.length,
    preparing: operationsStore.preparingOrders.length,
    ready: operationsStore.readyOrders.length,
  }
})
</script>

<template>
  <section class="bg-white rounded-3xl border border-slate-200 shadow-sm p-6">
    <!-- HEADER -->
    <div class="flex justify-between items-center mb-8">
      <div>
        <h2 class="text-xl font-bold">Restaurant Orders</h2>
        <p class="text-sm text-slate-500">Order pipeline status</p>
      </div>
      <div class="w-12 h-12 rounded-2xl bg-orange-100 flex items-center justify-center">
        <ChefHat class="w-6 h-6 text-orange-600" />
      </div>
    </div>

    <!-- STATISTICS -->
    <div class="grid grid-cols-2 gap-4 mb-8">
      <div class="bg-purple-50 rounded-2xl p-4">
        <p class="text-sm text-slate-500">Total Orders</p>
        <h3 class="text-3xl font-bold text-purple-700 mt-2">{{ orderStats.total }}</h3>
      </div>

      <div class="bg-red-50 rounded-2xl p-4">
        <p class="text-sm text-slate-500">Pending</p>
        <h3 class="text-3xl font-bold text-red-700 mt-2">{{ orderStats.pending }}</h3>
      </div>

      <div class="bg-yellow-50 rounded-2xl p-4">
        <p class="text-sm text-slate-500">Preparing</p>
        <h3 class="text-3xl font-bold text-yellow-700 mt-2">{{ orderStats.preparing }}</h3>
      </div>

      <div class="bg-green-50 rounded-2xl p-4">
        <p class="text-sm text-slate-500">Ready</p>
        <h3 class="text-3xl font-bold text-green-700 mt-2">{{ orderStats.ready }}</h3>
      </div>
    </div>

    <!-- PROGRESS BAR -->
    <div class="mb-6">
      <p class="text-sm font-medium text-slate-600 mb-2">Pipeline Progress</p>
      <div class="flex gap-2 h-2 rounded-full overflow-hidden bg-slate-100">
        <div
          class="bg-red-500"
          :style="{ width: `${(orderStats.pending / (orderStats.total || 1)) * 100}%` }"
        ></div>
        <div
          class="bg-yellow-500"
          :style="{ width: `${(orderStats.preparing / (orderStats.total || 1)) * 100}%` }"
        ></div>
        <div
          class="bg-green-500"
          :style="{ width: `${(orderStats.ready / (orderStats.total || 1)) * 100}%` }"
        ></div>
      </div>
      <div class="flex justify-between mt-2 text-xs text-slate-500">
        <span>Pending</span>
        <span>Preparing</span>
        <span>Ready</span>
      </div>
    </div>

    <!-- RECENT ORDERS -->
    <div class="space-y-3">
      <p class="text-sm font-medium text-slate-600">Recent Orders</p>

      <div
        v-for="order in operationsStore.orders.slice(0, 4)"
        :key="order.id"
        class="flex items-center justify-between p-3 rounded-lg bg-slate-50 hover:bg-slate-100 transition"
      >
        <div class="flex items-center gap-3 flex-1">
          <div
            :class="[
              'w-8 h-8 rounded-lg flex items-center justify-center text-white text-sm font-medium',
              order.status === 'pending' && 'bg-red-500',
              order.status === 'preparing' && 'bg-yellow-500',
              order.status === 'ready' && 'bg-green-500',
              order.status === 'served' && 'bg-blue-500',
            ]"
          >
            #{{ order.orderNumber.split('-').pop() }}
          </div>
          <div class="flex-1">
            <p class="text-sm font-medium">{{ order.guestName }}</p>
            <p class="text-xs text-slate-500">Room {{ order.roomNumber }} • {{ order.itemCount }} items</p>
          </div>
        </div>

        <div class="flex items-center gap-2 text-xs">
          <Clock class="w-3 h-3 text-slate-400" />
          <span class="text-slate-500">{{ order.total }} Birr</span>
        </div>
      </div>

      <div v-if="operationsStore.orders.length === 0" class="py-6 text-center text-slate-500">
        No orders at this time
      </div>
    </div>
  </section>
</template>
