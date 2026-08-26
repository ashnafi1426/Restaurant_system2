<script setup lang="ts">
import { onMounted } from 'vue'
import { useKitchenStore } from '@/stores/kitchenStore'
import { storeToRefs } from 'pinia'
import DashboardLayout from '@/layouts/DashboardLayout.vue'
import { CookingPot, Check, ShoppingBag, FileText, Clock, Loader2 } from 'lucide-vue-next'

const kitchenStore = useKitchenStore()
const { preparingOrders, statistics, actionLoading } = storeToRefs(kitchenStore)

onMounted(async () => {
  await kitchenStore.fetchDashboard()
})
async function markReady(orderId: string) {
  await kitchenStore.markReady(orderId)
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
          <h1 class="text-3xl font-bold text-slate-900 dark:text-white flex items-center gap-3">
            <CookingPot class="w-8 h-8 text-amber-500" />
            <span>Preparing Orders</span>
          </h1>
          <p class="mt-2 text-slate-600 dark:text-slate-400">Orders currently being prepared</p>
        </div>
        <div class="bg-amber-500/10 border border-amber-500/20 text-amber-600 dark:text-amber-400 px-6 py-3 rounded-2xl font-black text-2xl">
          {{ statistics?.preparing_orders ?? 0 }}
        </div>
      </div>

      <!-- Table Container -->
      <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-xs overflow-hidden overflow-x-auto">
        <table v-if="preparingOrders?.length" class="w-full text-sm">
          <!-- Table Header -->
          <thead>
            <tr class="bg-slate-50 dark:bg-slate-950 border-b border-slate-200 dark:border-slate-800 text-xs font-black text-slate-400 dark:text-slate-500 uppercase tracking-wider select-none">
              <th class="px-4 py-3.5 text-left">Order ID</th>
              <th class="px-4 py-3.5 text-left">Room</th>
              <th class="px-4 py-3.5 text-left">Guest</th>
              <th class="px-4 py-3.5 text-left">Items</th>
              <th class="px-4 py-3.5 text-left">Notes</th>
              <th class="px-4 py-3.5 text-left">Time</th>
              <th class="px-4 py-3.5 text-right">Total</th>
              <th class="px-4 py-3.5 text-center">Action</th>
            </tr>
          </thead>

          <!-- Table Body -->
          <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
            <tr
              v-for="order in preparingOrders"
              :key="order.id"
              class="transition hover:bg-slate-50/50 dark:hover:bg-slate-800/30"
            >
              <!-- Order ID -->
              <td class="px-4 py-3">
                <div class="font-mono text-xs font-bold text-slate-900 dark:text-white">
                  {{ order.order_number }}
                </div>
              </td>

              <!-- Room -->
              <td class="px-4 py-3">
                <div class="font-bold text-slate-900 dark:text-slate-100 text-xs flex items-center gap-1.5">
                  <span v-if="order.room?.room_number">ROOM {{ order.room.room_number }}</span>
                  <span v-else class="inline-flex items-center gap-1 text-amber-600 dark:text-amber-400 font-bold">
                    <ShoppingBag class="w-3.5 h-3.5" />
                    <span>TAKEOUT</span>
                  </span>
                </div>
              </td>

              <!-- Guest -->
              <td class="px-4 py-3">
                <div class="text-slate-700 dark:text-slate-300 text-xs font-medium">
                  {{ order.guest?.full_name || 'Walk-in Guest' }}
                </div>
              </td>

              <!-- Items -->
              <td class="px-4 py-3">
                <div class="text-slate-700 dark:text-slate-300 space-y-1">
                  <div
                    v-for="(item, idx) in (order.items || []).slice(0, 2)"
                    :key="idx"
                    class="text-xs font-medium"
                  >
                    <span class="font-bold text-amber-600 dark:text-amber-400">{{ item.quantity }}x</span> {{ item.name }}
                    <span v-if="item.notes" class="flex items-center gap-1 text-[11px] text-amber-600 dark:text-amber-400 font-semibold mt-0.5">
                      <FileText class="w-3 h-3" />
                      <span>{{ item.notes }}</span>
                    </span>
                  </div>
                  <div
                    v-if="(order.items || []).length > 2"
                    class="text-[10px] text-slate-400 font-bold"
                  >
                    +{{ (order.items || []).length - 2 }} more items
                  </div>
                </div>
              </td>

              <!-- Notes -->
              <td class="px-4 py-3">
                <div v-if="order.notes" class="text-xs text-amber-600 dark:text-amber-400 font-bold flex items-center gap-1">
                  <FileText class="w-3.5 h-3.5 flex-shrink-0" />
                  <span>{{ order.notes }}</span>
                </div>
                <div v-else class="text-xs text-slate-400">—</div>
              </td>

              <!-- Time -->
              <td class="px-4 py-3">
                <div class="font-bold text-slate-900 dark:text-slate-100 text-xs">
                  {{ formatTime(order.order_time) }}
                </div>
              </td>

              <!-- Total -->
              <td class="px-4 py-3 text-right">
                <div class="font-black text-slate-900 dark:text-white text-xs font-mono">
                  ${{ parseFloat(order.total).toFixed(2) }}
                </div>
              </td>

              <!-- Action -->
              <td class="px-4 py-3 text-center">
                <button
                  @click="markReady(order.id)"
                  :disabled="actionLoading === order.id"
                  class="px-3.5 py-1.5 bg-emerald-600 hover:bg-emerald-700 disabled:bg-emerald-400 disabled:cursor-not-allowed text-white font-bold rounded-xl text-xs transition inline-flex items-center justify-center gap-1.5 shadow-xs cursor-pointer"
                >
                  <Loader2 v-if="actionLoading === order.id" class="w-3.5 h-3.5 animate-spin" />
                  <Check v-else class="w-3.5 h-3.5 stroke-[3]" />
                  <span class="hidden sm:inline">{{
                    actionLoading === order.id ? 'MARKING...' : 'READY'
                  }}</span>
                </button>
              </td>
            </tr>
          </tbody>
        </table>

        <!-- Empty State -->
        <div v-else class="text-center py-16 bg-slate-50 dark:bg-slate-950">
          <Clock class="w-12 h-12 text-slate-400 dark:text-slate-600 mx-auto mb-3" />
          <p class="text-2xl font-bold text-slate-900 dark:text-white">No Preparing Orders</p>
          <p class="text-slate-500 dark:text-slate-400 mt-1 text-xs">All orders are ready or pending!</p>
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>
