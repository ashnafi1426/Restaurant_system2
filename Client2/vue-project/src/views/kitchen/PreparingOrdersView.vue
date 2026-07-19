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
  // Don't refetch - store updates locally via updateOrder()
}

const formatTime = (dateTime: string) => {
  try {
    const date = new Date(dateTime)
    return date.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' })
  } catch {
    return '—'
  }
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

      <!-- Table Container -->
      <div class="rounded-lg bg-white shadow-md overflow-hidden overflow-x-auto">
        <table v-if="preparingOrders?.length" class="w-full text-sm">
          <!-- Table Header -->
          <thead>
            <tr class="bg-slate-100 border-b-2 border-slate-200">
              <th class="px-4 py-3 text-left font-bold text-slate-700">Order ID</th>
              <th class="px-4 py-3 text-left font-bold text-slate-700">Room</th>
              <th class="px-4 py-3 text-left font-bold text-slate-700">Guest</th>
              <th class="px-4 py-3 text-left font-bold text-slate-700">Items</th>
              <th class="px-4 py-3 text-left font-bold text-slate-700">Notes</th>
              <th class="px-4 py-3 text-left font-bold text-slate-700">Time</th>
              <th class="px-4 py-3 text-right font-bold text-slate-700">Total</th>
              <th class="px-4 py-3 text-center font-bold text-slate-700">Action</th>
            </tr>
          </thead>

          <!-- Table Body -->
          <tbody>
            <tr
              v-for="order in preparingOrders"
              :key="order.id"
              class="border-b border-slate-200 transition hover:bg-blue-50"
            >
              <!-- Order ID -->
              <td class="px-4 py-3">
                <div class="font-mono text-xs font-semibold text-slate-900">
                  {{ order.order_number }}
                </div>
              </td>

              <!-- Room -->
              <td class="px-4 py-3">
                <div class="font-semibold text-slate-900">
                  {{ order.room?.room_number ? `ROOM ${order.room.room_number}` : '🍽️ TAKEOUT' }}
                </div>
              </td>

              <!-- Guest -->
              <td class="px-4 py-3">
                <div class="text-slate-700">
                  {{ order.guest?.full_name || 'Walk-in Guest' }}
                </div>
              </td>

              <!-- Items -->
              <td class="px-4 py-3">
                <div class="text-slate-700 space-y-1">
                  <div
                    v-for="(item, idx) in (order.items || []).slice(0, 2)"
                    :key="idx"
                    class="text-sm"
                  >
                    <span class="font-semibold">{{ item.quantity }}x</span> {{ item.name }}
                    <span v-if="item.notes" class="block text-xs text-blue-600 mt-0.5">
                      📝 {{ item.notes }}
                    </span>
                  </div>
                  <div v-if="(order.items || []).length > 2" class="text-xs text-slate-500 font-semibold">
                    +{{ (order.items || []).length - 2 }} more items
                  </div>
                </div>
              </td>

              <!-- Notes -->
              <td class="px-4 py-3">
                <div v-if="order.notes" class="text-xs text-amber-600 font-bold">
                  ⚠️ {{ order.notes }}
                </div>
                <div v-else class="text-xs text-slate-400">—</div>
              </td>

              <!-- Time -->
              <td class="px-4 py-3">
                <div class="font-semibold text-slate-900">
                  {{ formatTime(order.order_time) }}
                </div>
              </td>

              <!-- Total -->
              <td class="px-4 py-3 text-right">
                <div class="font-bold text-slate-900">
                  ${{ parseFloat(order.total).toFixed(2) }}
                </div>
              </td>

              <!-- Action -->
              <td class="px-4 py-3 text-center">
                <button
                  @click="markReady(order.id)"
                  :disabled="actionLoading === order.id"
                  class="px-3 py-1.5 bg-green-600 hover:bg-green-700 disabled:bg-green-400 disabled:cursor-not-allowed text-white font-bold rounded text-xs transition flex items-center justify-center gap-1"
                >
                  <span v-if="actionLoading === order.id" class="inline-block animate-spin">⟳</span>
                  <span v-else>✓</span>
                  <span class="hidden sm:inline">{{ actionLoading === order.id ? 'MARKING...' : 'READY' }}</span>
                </button>
              </td>
            </tr>
          </tbody>
        </table>

        <!-- Empty State -->
        <div v-else class="text-center py-16 bg-slate-50">
          <p class="text-6xl mb-4">⏳</p>
          <p class="text-2xl font-bold text-slate-900">No Preparing Orders</p>
          <p class="text-slate-500 mt-2">All orders are ready or pending!</p>
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>
