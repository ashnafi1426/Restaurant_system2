<script setup lang="ts">
import { onMounted } from 'vue'
import { useKitchenStore } from '@/stores/kitchenStore'
import { storeToRefs } from 'pinia'
import DashboardLayout from '@/layouts/DashboardLayout.vue'

const kitchenStore = useKitchenStore()
const { preparingOrders, statistics, actionLoading } = storeToRefs(kitchenStore)

onMounted(async () => {
  await kitchenStore.fetchDashboard()
})

async function markReady(orderId: string) {
  await kitchenStore.markReady(orderId)
  await kitchenStore.fetchDashboard()
}
</script>

<template>
  <DashboardLayout>
    <div class="space-y-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold text-slate-900">👨‍🍳 Preparing Orders</h1>
          <p class="mt-2 text-slate-600">Orders currently being prepared</p>
        </div>
        <div class="bg-blue-100 text-blue-700 px-6 py-3 rounded-lg font-bold text-2xl">
          {{ statistics?.preparing_orders ?? 0 }}
        </div>
      </div>

      <!-- Orders Grid -->
      <div v-if="preparingOrders?.length" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div
          v-for="order in preparingOrders"
          :key="order.id"
          class="bg-white rounded-lg border-l-4 border-blue-400 p-6 shadow-md hover:shadow-lg transition"
        >
          <!-- Room Info -->
          <div class="flex items-center justify-between mb-4">
            <div>
              <h3 class="text-lg font-bold text-slate-900">
                {{ order.room?.room_number ? `ROOM ${order.room.room_number}` : '🍽️ TAKEOUT' }}
              </h3>
              <p class="text-sm text-slate-500">Order #{{ order.order_number }}</p>
            </div>
            <div class="text-right">
              <p class="text-xs text-slate-500">Time</p>
              <p class="text-sm font-semibold text-blue-600">{{ new Date(order.order_time).toLocaleTimeString() }}</p>
            </div>
          </div>

          <!-- Guest Info -->
          <div v-if="order.guest" class="mb-4 pb-4 border-b border-slate-200">
            <p class="text-sm text-slate-600">Guest: <span class="font-semibold">{{ order.guest.full_name }}</span></p>
          </div>

          <!-- Items -->
          <div class="mb-4 space-y-2">
            <p class="text-xs font-semibold text-slate-500 uppercase">Items</p>
            <div v-for="item in order.items" :key="item.id" class="text-sm text-slate-700">
              <span class="font-semibold">{{ item.quantity }}x</span> {{ item.name }}
              <span v-if="item.notes" class="block text-xs text-blue-600 mt-1">📝 {{ item.notes }}</span>
            </div>
          </div>

          <!-- Special Notes -->
          <div v-if="order.notes" class="mb-4 p-3 bg-amber-50 border border-amber-200 rounded">
            <p class="text-xs text-amber-700">⚠️ {{ order.notes }}</p>
          </div>

          <!-- Total -->
          <div class="mb-4 pb-4 border-b border-slate-200">
            <div class="flex justify-between">
              <span class="text-slate-600">Total:</span>
              <span class="font-bold text-slate-900">${{ order.total }}</span>
            </div>
          </div>

          <!-- Action Button -->
          <button
            @click="markReady(order.id)"
            :disabled="actionLoading === order.id"
            class="w-full bg-green-600 hover:bg-green-700 disabled:opacity-50 text-white font-bold py-2 rounded-lg transition"
          >
            {{ actionLoading === order.id ? 'Marking...' : '✅ MARK READY' }}
          </button>
        </div>
      </div>

      <!-- Empty State -->
      <div v-else class="text-center py-16">
        <p class="text-6xl mb-4">⏳</p>
        <p class="text-2xl font-bold text-slate-900">No Preparing Orders</p>
        <p class="text-slate-500 mt-2">All orders are ready or pending!</p>
      </div>
    </div>
  </DashboardLayout>
</template>
