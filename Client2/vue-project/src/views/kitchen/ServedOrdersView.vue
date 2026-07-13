<script setup lang="ts">
import { onMounted } from 'vue'
import { useKitchenStore } from '@/stores/kitchenStore'
import { storeToRefs } from 'pinia'
import DashboardLayout from '@/layouts/DashboardLayout.vue'

const kitchenStore = useKitchenStore()
const { completedOrders, statistics } = storeToRefs(kitchenStore)

onMounted(async () => {
  await kitchenStore.fetchDashboard()
})
</script>

<template>
  <DashboardLayout>
    <div class="space-y-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold text-slate-900">✅ Served Orders</h1>
          <p class="mt-2 text-slate-600">Completed and served orders</p>
        </div>
        <div class="bg-slate-100 text-slate-700 px-6 py-3 rounded-lg font-bold text-2xl">
          {{ statistics?.served_orders ?? 0 }}
        </div>
      </div>

      <!-- Orders Grid -->
      <div
        v-if="completedOrders?.length"
        class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6"
      >
        <div
          v-for="order in completedOrders"
          :key="order.id"
          class="bg-white rounded-lg border-l-4 border-slate-400 p-6 shadow-md hover:shadow-lg transition opacity-90"
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
              <p class="text-xs text-slate-500">Served</p>
              <p class="text-sm font-semibold text-green-600">
                {{ new Date(order.order_time).toLocaleTimeString() }}
              </p>
            </div>
          </div>

          <!-- Guest Info -->
          <div v-if="order.guest" class="mb-4 pb-4 border-b border-slate-200">
            <p class="text-sm text-slate-600">
              Guest: <span class="font-semibold">{{ order.guest.full_name }}</span>
            </p>
          </div>

          <!-- Items Summary -->
          <div class="mb-4 space-y-2">
            <p class="text-xs font-semibold text-slate-500 uppercase">Items</p>
            <div v-for="item in order.items" :key="item.id" class="text-sm text-slate-600">
              <span class="font-semibold">{{ item.quantity }}x</span> {{ item.name }}
            </div>
          </div>

          <!-- Total -->
          <div class="pt-4 border-t border-slate-200">
            <div class="flex justify-between">
              <span class="text-slate-600 font-semibold">Total Amount:</span>
              <span class="font-bold text-slate-900 text-lg">${{ order.total }}</span>
            </div>
          </div>

          <!-- Status Badge -->
          <div class="mt-4">
            <span
              class="inline-block bg-green-100 text-green-700 px-3 py-1 rounded-full text-xs font-semibold"
            >
              ✅ Served
            </span>
          </div>
        </div>
      </div>

      <!-- Empty State -->
      <div v-else class="text-center py-16">
        <p class="text-6xl mb-4">📭</p>
        <p class="text-2xl font-bold text-slate-900">No Served Orders</p>
        <p class="text-slate-500 mt-2">No completed orders yet today</p>
      </div>
    </div>
  </DashboardLayout>
</template>
