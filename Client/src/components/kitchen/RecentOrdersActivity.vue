<template>
  <div
    class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-md overflow-hidden"
  >
    <div class="bg-slate-900 dark:bg-slate-950 px-5 py-3.5 text-white border-b border-slate-800">
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-2.5">
          <Activity :size="20" class="text-amber-500" />
          <h3 class="text-sm font-black tracking-wide uppercase">
            {{ languageStore.t('recently_updated_orders', 'Recently Updated Orders') }}
          </h3>
        </div>
        <span
          class="text-[10px] font-black bg-amber-500/20 text-amber-400 border border-amber-500/30 px-2.5 py-0.5 rounded-full uppercase tracking-wider"
          >{{ languageStore.t('live_feed', 'Live Feed') }}</span
        >
      </div>
    </div>

    <div class="max-h-96 overflow-y-auto">
      <div
        v-if="recentOrders.length === 0"
        class="px-6 py-12 text-center text-slate-500 dark:text-slate-400"
      >
        <ListX :size="32" class="mx-auto mb-3 text-slate-400 dark:text-slate-600" />
        <p class="text-sm font-medium">
          {{ languageStore.t('no_recent_orders', 'No recent orders') }}
        </p>
      </div>

      <div v-else class="divide-y divide-slate-100 dark:divide-slate-800">
        <div
          v-for="(order, idx) in recentOrders"
          :key="order.id"
          :class="[
            'px-5 py-3.5 border-l-4 transition hover:bg-slate-50 dark:hover:bg-slate-800/50',
            getOrderBorderColor(order.status),
          ]"
        >
          <div class="flex gap-3.5">
            <div
              :class="[
                'flex-shrink-0 w-8 h-8 rounded-xl flex items-center justify-center',
                getStatusBg(order.status),
              ]"
            >
              <component
                :is="getStatusIcon(order.status)"
                :size="16"
                :stroke-width="2.5"
                class="text-white"
              />
            </div>

            <div class="flex-1 min-w-0">
              <div class="flex items-center justify-between gap-2 mb-1">
                <div class="flex items-center gap-2">
                  <span class="text-xs font-bold text-slate-900 dark:text-white">
                    <template v-if="order.table?.table_number || order.order_type === 'walk_in'">
                      <span
                        class="inline-flex items-center gap-1 text-emerald-600 dark:text-emerald-400"
                      >
                        <UtensilsCrossed class="w-3.5 h-3.5" />
                        {{
                          order.table?.table_name ||
                          `${languageStore.t('table', 'Table')} ${order.table?.table_number || ''}`
                        }}
                      </span>
                    </template>
                    <template v-else-if="order.room?.room_number">
                      {{ languageStore.t('room', 'Room') }} {{ order.room.room_number }}
                    </template>
                    <template v-else>
                      <span
                        class="inline-flex items-center gap-1 text-amber-600 dark:text-amber-400"
                      >
                        <ShoppingBag class="w-3.5 h-3.5" />
                        {{ languageStore.t('takeout', 'Takeout') }}
                      </span>
                    </template>
                  </span>
                  <span
                    class="text-[10px] text-slate-500 dark:text-slate-400 font-mono font-bold"
                    >{{ order.order_number }}</span
                  >
                </div>
                <span
                  :class="[
                    'text-xs font-bold px-2 py-1 rounded-full',
                    getStatusBadgeClass(order.status),
                  ]"
                >
                  {{ languageStore.t(order.status, order.status) }}
                </span>
              </div>

              <p class="text-xs text-slate-600 dark:text-slate-400 mb-2">
                <span class="font-medium text-slate-800 dark:text-slate-200">{{
                  order.guest?.full_name || languageStore.t('walk_in_guest', 'Walk-in Guest')
                }}</span>
                •
                <span class="text-slate-500 dark:text-slate-400"
                  >{{ order.items?.length || 0 }} {{ languageStore.t('items', 'items') }}</span
                >
              </p>

              <div class="text-xs text-slate-700 dark:text-slate-300 mb-2">
                <div
                  v-for="item in (order.items || []).slice(0, 2)"
                  :key="item.id"
                  class="text-slate-600 dark:text-slate-400"
                >
                  {{ item.quantity }}x {{ item.name }}
                </div>
                <div
                  v-if="(order.items || []).length > 2"
                  class="text-slate-500 dark:text-slate-400"
                >
                  +{{ (order.items || []).length - 2 }} {{ languageStore.t('more', 'more') }}
                </div>
              </div>

              <div class="flex items-center justify-between">
                <span class="text-xs text-slate-500 dark:text-slate-400">{{
                  formatTime(order.updated_at || order.order_time)
                }}</span>
                <span class="text-xs font-bold text-slate-900 dark:text-white"
                  >${{ Number(order.total).toFixed(2) }}</span
                >
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div
      class="bg-slate-50 dark:bg-slate-800/60 px-6 py-3 border-t border-slate-200 dark:border-slate-800 text-center text-xs text-slate-600 dark:text-slate-400"
    >
      {{ languageStore.t('last_10_orders', 'Last 10 orders • Updates auto-refresh every 10s') }}
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
  ShoppingBag,
  UtensilsCrossed,
} from 'lucide-vue-next'
import { useLanguageStore } from '@/stores/language'
import type { KitchenOrder } from '@/types/kitchen'

const languageStore = useLanguageStore()

const props = defineProps<{
  orders?: KitchenOrder[]
}>()

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
      return 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300'
    case 'preparing':
      return 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300'
    case 'ready':
      return 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300'
    case 'served':
      return 'bg-slate-100 text-slate-800 dark:bg-slate-800 dark:text-slate-300'
    default:
      return 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300'
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

    if (diffMins < 1) return languageStore.t('just_now', 'Just now')
    if (diffMins < 60) return `${diffMins}m ago`
    const diffHours = Math.floor(diffMins / 60)
    if (diffHours < 24) return `${diffHours}h ago`

    return date.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' })
  } catch (error) {
    console.error('[RecentOrdersActivity] Error formatting time:', error)
    return '—'
  }
}
</script>

<style scoped>
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
