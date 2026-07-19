<template>
  <div class="rounded-lg bg-white shadow-md overflow-hidden">
    <!-- Header -->
    <div class="bg-gradient-to-r from-slate-700 to-slate-900 px-6 py-4 text-white">
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
          <Activity :size="24" :stroke-width="2" />
          <h3 class="text-lg font-bold">Recently Updated Orders</h3>
        </div>
        <span class="text-xs bg-white/20 px-3 py-1 rounded-full">Live Feed</span>
      </div>
    </div>

    <!-- Content -->
    <div class="max-h-96 overflow-y-auto">
      <!-- Empty State -->
      <div v-if="recentOrders.length === 0" class="px-6 py-12 text-center text-slate-500">
        <ListX :size="32" class="mx-auto mb-3 text-slate-400" />
        <p class="text-sm font-medium">No recent orders</p>
      </div>

      <!-- Order Items -->
      <div v-else class="divide-y divide-slate-200">
        <div
          v-for="(order, idx) in recentOrders"
          :key="order.id"
          :class="[
            'px-6 py-4 border-l-4 transition hover:bg-slate-50',
            getOrderBorderColor(order.status),
          ]"
        >
          <!-- Timeline Indicator -->
          <div class="flex gap-4">
            <!-- Status Icon -->
            <div
              :class="[
                'flex-shrink-0 w-10 h-10 rounded-full flex items-center justify-center',
                getStatusBg(order.status),
              ]"
            >
              <component :is="getStatusIcon(order.status)" :size="20" :stroke-width="2" class="text-white" />
            </div>

            <!-- Order Details -->
            <div class="flex-1 min-w-0">
              <!-- Room & Order Number -->
              <div class="flex items-center justify-between gap-2 mb-1">
                <div class="flex items-center gap-2">
                  <span class="text-sm font-bold text-slate-900">
                    {{ order.room?.room_number ? `Room ${order.room.room_number}` : '🍽️ Takeout' }}
                  </span>
                  <span class="text-xs text-slate-500 font-mono">{{ order.order_number }}</span>
                </div>
                <span
                  :class="[
                    'text-xs font-bold px-2 py-1 rounded-full',
                    getStatusBadgeClass(order.status),
                  ]"
                >
                  {{ order.status.toUpperCase() }}
                </span>
              </div>

              <!-- Guest & Items -->
              <p class="text-xs text-slate-600 mb-2">
                <span class="font-medium">{{ order.guest?.full_name || 'Walk-in' }}</span>
                •
                <span class="text-slate-500">{{ order.items?.length || 0 }} items</span>
              </p>

              <!-- Quick Items Preview -->
              <div class="text-xs text-slate-700 mb-2">
                <div v-for="item in (order.items || []).slice(0, 2)" :key="item.id" class="text-slate-600">
                  {{ item.quantity }}x {{ item.name }}
                </div>
                <div v-if="(order.items || []).length > 2" class="text-slate-500">
                  +{{ (order.items || []).length - 2 }} more
                </div>
              </div>

              <!-- Footer: Time & Amount -->
              <div class="flex items-center justify-between">
                <span class="text-xs text-slate-500">{{ formatTime(order.updated_at || order.order_time) }}</span>
                <span class="text-xs font-bold text-slate-900">${{ parseFloat(order.total).toFixed(2) }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Footer -->
    <div class="bg-slate-50 px-6 py-3 border-t border-slate-200 text-center text-xs text-slate-600">
      Last 10 orders • Updates auto-refresh every 10s
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import {
  Clock,
  ChefHat,
  CheckCircle,
  XCircle,
  Activity,
  ListX,
} from 'lucide-vue-next'
import type { KitchenOrder } from '@/types/kitchen'

const props = defineProps<{
  orders?: KitchenOrder[]
}>()

// Get most recent 10 orders sorted by updated time
const recentOrders = computed(() => {
  return (props.orders || [])
    .sort((a, b) => {
      const timeA = new Date(a.updated_at || a.created_at).getTime()
      const timeB = new Date(b.updated_at || b.created_at).getTime()
      return timeB - timeA
    })
    .slice(0, 10)
})

function getStatusIcon(status: string) {
  switch (status) {
    case 'pending':
      return Clock
    case 'preparing':
      return ChefHat
    case 'ready':
      return CheckCircle
    case 'served':
      return CheckCircle
    default:
      return XCircle
  }
}

function getStatusBg(status: string) {
  switch (status) {
    case 'pending':
      return 'bg-amber-500'
    case 'preparing':
      return 'bg-blue-500'
    case 'ready':
      return 'bg-green-500'
    case 'served':
      return 'bg-slate-600'
    default:
      return 'bg-red-500'
  }
}

function getStatusBadgeClass(status: string) {
  switch (status) {
    case 'pending':
      return 'bg-amber-100 text-amber-800'
    case 'preparing':
      return 'bg-blue-100 text-blue-800'
    case 'ready':
      return 'bg-green-100 text-green-800'
    case 'served':
      return 'bg-slate-100 text-slate-800'
    default:
      return 'bg-red-100 text-red-800'
  }
}

function getOrderBorderColor(status: string) {
  switch (status) {
    case 'pending':
      return 'border-amber-400'
    case 'preparing':
      return 'border-blue-400'
    case 'ready':
      return 'border-green-400'
    case 'served':
      return 'border-slate-400'
    default:
      return 'border-red-400'
  }
}

function formatTime(dateTime: string): string {
  try {
    const date = new Date(dateTime)
    const now = new Date()
    const diffMs = now.getTime() - date.getTime()
    const diffMins = Math.floor(diffMs / 60000)

    if (diffMins < 1) return 'Just now'
    if (diffMins < 60) return `${diffMins}m ago`
    const diffHours = Math.floor(diffMins / 60)
    if (diffHours < 24) return `${diffHours}h ago`

    return date.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' })
  } catch {
    return '—'
  }
}
</script>

<style scoped>
/* Smooth scrollbar for recent orders */
::-webkit-scrollbar {
  width: 6px;
}

::-webkit-scrollbar-track {
  background: transparent;
}

::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 3px;
}

::-webkit-scrollbar-thumb:hover {
  background: #94a3b8;
}
</style>
