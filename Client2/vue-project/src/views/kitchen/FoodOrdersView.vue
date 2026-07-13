<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useKitchenStore } from '@/stores/kitchenStore'
import { storeToRefs } from 'pinia'
import DashboardLayout from '@/layouts/DashboardLayout.vue'

const kitchenStore = useKitchenStore()
const { orders, statistics, loading } = storeToRefs(kitchenStore)

const selectedStatus = ref<string>('all')

onMounted(async () => {
  await kitchenStore.fetchDashboard()
})

const filteredOrders = () => {
  if (selectedStatus.value === 'all') {
    return orders.value
  }
  return orders.value.filter((order) => order.status === selectedStatus.value)
}

const getStatusColor = (status: string) => {
  switch (status) {
    case 'pending':
      return 'border-amber-400 text-amber-700'
    case 'preparing':
      return 'border-blue-400 text-blue-700'
    case 'ready':
      return 'border-green-400 text-green-700'
    case 'served':
      return 'border-slate-400 text-slate-700'
    default:
      return 'border-slate-400 text-slate-700'
  }
}

const getStatusIcon = (status: string) => {
  switch (status) {
    case 'pending':
      return '🕒'
    case 'preparing':
      return '👨‍🍳'
    case 'ready':
      return '✅'
    case 'served':
      return '🍽️'
    default:
      return '📋'
  }
}
</script>

<template>
  <DashboardLayout>
    <div class="space-y-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold text-slate-900">🍜 All Food Orders</h1>
          <p class="mt-2 text-slate-600">Complete kitchen order history</p>
        </div>
        <div class="bg-teal-100 text-teal-700 px-6 py-3 rounded-lg font-bold text-2xl">
          {{ orders?.length ?? 0 }}
        </div>
      </div>

      <!-- Status Filter -->
      <div class="flex gap-3 flex-wrap">
        <button
          v-for="status in ['all', 'pending', 'preparing', 'ready', 'served']"
          :key="status"
          @click="selectedStatus = status"
          :class="[
            'px-4 py-2 rounded-lg font-semibold transition',
            selectedStatus === status
              ? 'bg-teal-600 text-white'
              : 'bg-slate-100 text-slate-700 hover:bg-slate-200',
          ]"
        >
          {{ status.charAt(0).toUpperCase() + status.slice(1) }}
        </button>
      </div>

      <!-- Orders Grid -->
      <div
        v-if="filteredOrders()?.length"
        class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6"
      >
        <div
          v-for="order in filteredOrders()"
          :key="order.id"
          :class="[
            'bg-white rounded-lg border-l-4 p-6 shadow-md hover:shadow-lg transition',
            getStatusColor(order.status),
          ]"
        >
          <!-- Status Badge & Room -->
          <div class="flex items-start justify-between mb-3">
            <div>
              <h3 class="text-lg font-bold text-slate-900">
                {{ order.room?.room_number ? `ROOM ${order.room.room_number}` : '🍽️ TAKEOUT' }}
              </h3>
              <p class="text-xs text-slate-500">{{ order.order_number }}</p>
            </div>
            <span class="text-2xl">{{ getStatusIcon(order.status) }}</span>
          </div>

          <!-- Status -->
          <div class="mb-3 pb-3 border-b border-slate-200">
            <span
              :class="[
                'inline-block px-3 py-1 rounded-full text-xs font-semibold',
                getStatusColor(order.status),
              ]"
            >
              {{ order.status.toUpperCase() }}
            </span>
          </div>

          <!-- Guest Info -->
          <div v-if="order.guest" class="mb-3 text-sm">
            <p class="text-slate-600">👤 {{ order.guest.full_name }}</p>
          </div>

          <!-- Quick Items Preview -->
          <div class="mb-3 space-y-1">
            <p class="text-xs font-semibold text-slate-500 uppercase">Items</p>
            <div class="text-sm text-slate-700">
              <div
                v-for="item in order.items.slice(0, 2)"
                :key="item.id"
                class="flex justify-between"
              >
                <span>{{ item.quantity }}x {{ item.name }}</span>
              </div>
              <div v-if="order.items.length > 2" class="text-slate-500 text-xs italic">
                +{{ order.items.length - 2 }} more items
              </div>
            </div>
          </div>

          <!-- Time & Total -->
          <div class="pt-3 border-t border-slate-200 text-sm">
            <div class="flex justify-between mb-2">
              <span class="text-slate-600">Time:</span>
              <span class="font-semibold">{{
                new Date(order.order_time).toLocaleTimeString()
              }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-slate-600">Total:</span>
              <span class="font-bold text-slate-900">${{ order.total }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Empty State -->
      <div v-else class="text-center py-16">
        <p class="text-6xl mb-4">📭</p>
        <p class="text-2xl font-bold text-slate-900">
          No {{ selectedStatus === 'all' ? 'Orders' : selectedStatus + ' Orders' }}
        </p>
        <p class="text-slate-500 mt-2">No orders found in this status</p>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="text-center py-8">
        <p class="text-slate-600">Loading orders...</p>
      </div>
    </div>
  </DashboardLayout>
</template>
