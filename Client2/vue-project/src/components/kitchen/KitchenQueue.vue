<script setup lang="ts">
import { ref, computed } from 'vue'
import type { KitchenOrder } from '@/types/kitchen'

const props = defineProps<{
  pendingOrders?: KitchenOrder[]
  preparingOrders?: KitchenOrder[]
  readyOrders?: KitchenOrder[]
  completedOrders?: KitchenOrder[]
  processing?: boolean
}>()

const emit = defineEmits<{
  (e: 'start', order: KitchenOrder): void
  (e: 'ready', order: KitchenOrder): void
  (e: 'served', order: KitchenOrder): void
  (e: 'view', order: KitchenOrder): void
}>()

const selectedFilter = ref('all')
const searchQuery = ref('')

function getTimeRemaining(orderTime: string): string {
  try {
    const orderDate = new Date(orderTime)
    const now = new Date()
    const diff = Math.floor((now.getTime() - orderDate.getTime()) / 1000 / 60)
    return `${String(diff).padStart(2, '0')}:${String(Math.floor(now.getSeconds() % 60)).padStart(2, '0')} MINS`
  } catch {
    return '-- MINS'
  }
}

function getItemsPreview(items: any[]): string {
  if (!items?.length) return 'No items'
  return items.map((i) => `${i.quantity}x ${i.name}`).join(', ')
}
</script>

<template>
  <div class="space-y-4 sm:space-y-5 md:space-y-6">
    <!-- Title and Filter -->
    <div
      class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-2 sm:gap-3"
    >
      <div class="min-w-0">
        <h2 class="text-lg sm:text-xl md:text-2xl font-bold text-slate-900 flex items-center gap-2">
          🍳 Live Kitchen Queue
          <span class="text-xs sm:text-sm text-red-500 font-semibold">●</span>
        </h2>
      </div>
      <button
        class="px-2 sm:px-3 md:px-4 py-1.5 sm:py-2 bg-blue-50 text-blue-600 font-bold text-xs sm:text-sm rounded-lg hover:bg-blue-100 transition whitespace-nowrap"
      >
        FILTER: ALL ORDERS
      </button>
    </div>

    <!-- Kanban Columns -->
    <div class="grid grid-cols-1 gap-2 sm:gap-3 md:gap-4 md:grid-cols-2 lg:grid-cols-4">
      <!-- PENDING COLUMN -->
      <div class="rounded-lg bg-white shadow-md overflow-hidden">
        <!-- Column Header -->
        <div class="bg-amber-50 border-b-4 border-amber-400 px-3 sm:px-4 py-2 sm:py-3">
          <h3 class="text-xs sm:text-sm font-bold uppercase tracking-widest text-amber-900">
            Pending
          </h3>
        </div>

        <!-- Orders List -->
        <div
          class="p-2 sm:p-3 space-y-2 sm:space-y-3 max-h-[300px] sm:max-h-[400px] md:max-h-[600px] overflow-y-auto"
        >
          <div
            v-for="order in pendingOrders"
            :key="order.id"
            class="bg-amber-50 border-2 border-amber-200 rounded-lg p-2 sm:p-3 md:p-4 cursor-pointer hover:shadow-lg transition-all"
            @click="emit('view', order)"
          >
            <!-- Priority Badge -->
            <div
              v-if="order.notes"
              class="inline-block bg-red-100 text-red-700 text-xs font-bold px-1.5 sm:px-2 py-0.5 sm:py-1 rounded mb-1 sm:mb-2"
            >
              ⚠️ <span class="hidden sm:inline">PRIORITY - ROOM</span
              ><span class="sm:hidden">PRIORITY</span>
            </div>
            <div v-else class="text-xs font-bold text-amber-600 mb-1 sm:mb-2">
              ROOM {{ order.room?.room_number || '?' }}
            </div>

            <!-- Chef Special Badge -->
            <div
              v-if="order.notes"
              class="inline-block ml-1 sm:ml-2 bg-amber-100 text-amber-700 text-xs font-bold px-1.5 sm:px-2 py-0.5 sm:py-1 rounded"
            >
              CHEF <span class="hidden sm:inline">SPECIAL</span>
            </div>

            <!-- Time -->
            <p class="text-xs sm:text-sm font-bold text-amber-900 mt-1 sm:mt-2">
              {{ getTimeRemaining(order.order_time) }}
            </p>

            <!-- Items -->
            <div class="mt-2 sm:mt-3 space-y-0.5 sm:space-y-1 text-xs text-amber-800">
              <p
                v-for="(item, idx) in (order.items || []).slice(0, 3)"
                :key="idx"
                class="font-medium truncate"
              >
                {{ item.quantity }}x {{ item.name }}
                <span v-if="item.notes" class="text-amber-600 hidden sm:inline"
                  >({{ item.notes }})</span
                >
              </p>
              <p v-if="(order.items || []).length > 3" class="text-amber-600 font-semibold text-xs">
                +{{ (order.items || []).length - 3 }} more
              </p>
            </div>

            <!-- Action Button -->
            <button
              @click.stop="emit('start', order)"
              :disabled="processing"
              class="mt-2 sm:mt-3 md:mt-4 w-full bg-teal-600 hover:bg-teal-700 disabled:opacity-50 text-white font-bold py-1.5 sm:py-2 rounded-lg transition text-xs sm:text-sm"
            >
              START <span class="hidden sm:inline">PREPARING</span>
            </button>
          </div>

          <div
            v-if="!pendingOrders?.length"
            class="text-center py-6 sm:py-8 md:py-12 text-amber-400"
          >
            <p class="text-xs sm:text-sm">✓ No pending orders</p>
          </div>
        </div>
      </div>

      <!-- PREPARING COLUMN -->
      <div class="rounded-lg bg-white shadow-md overflow-hidden">
        <!-- Column Header -->
        <div class="bg-blue-50 border-b-4 border-blue-400 px-3 sm:px-4 py-2 sm:py-3">
          <h3 class="text-xs sm:text-sm font-bold uppercase tracking-widest text-blue-900">
            Preparing
          </h3>
        </div>

        <!-- Orders List -->
        <div
          class="p-2 sm:p-3 space-y-2 sm:space-y-3 max-h-[300px] sm:max-h-[400px] md:max-h-[600px] overflow-y-auto"
        >
          <div
            v-for="order in preparingOrders"
            :key="order.id"
            class="bg-blue-50 border-2 border-blue-200 rounded-lg p-2 sm:p-3 md:p-4 cursor-pointer hover:shadow-lg transition-all"
            @click="emit('view', order)"
          >
            <!-- Room Info -->
            <div class="text-xs font-bold text-blue-600 mb-1 sm:mb-2 truncate">
              TABLE {{ order.room?.room_number || '?' }} -
              <span class="hidden sm:inline">MAIN HALL</span>
            </div>

            <!-- Time -->
            <p class="text-xs sm:text-sm font-bold text-blue-900 mt-1">
              {{ getTimeRemaining(order.order_time) }}
            </p>

            <!-- Items -->
            <div class="mt-2 sm:mt-3 space-y-0.5 sm:space-y-1 text-xs text-blue-800">
              <p
                v-for="(item, idx) in (order.items || []).slice(0, 3)"
                :key="idx"
                class="font-medium truncate"
              >
                {{ item.quantity }}x {{ item.name }}
              </p>
              <p v-if="(order.items || []).length > 3" class="text-blue-600 font-semibold text-xs">
                +{{ (order.items || []).length - 3 }} more
              </p>
            </div>

            <!-- Action Button -->
            <button
              @click.stop="emit('ready', order)"
              :disabled="processing"
              class="mt-2 sm:mt-3 md:mt-4 w-full bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white font-bold py-1.5 sm:py-2 rounded-lg transition text-xs sm:text-sm"
            >
              MARK <span class="hidden sm:inline">READY</span>
            </button>
          </div>

          <div
            v-if="!preparingOrders?.length"
            class="text-center py-6 sm:py-8 md:py-12 text-blue-400"
          >
            <p class="text-xs sm:text-sm">No orders cooking</p>
          </div>
        </div>
      </div>

      <!-- READY COLUMN -->
      <div class="rounded-lg bg-white shadow-md overflow-hidden">
        <!-- Column Header -->
        <div class="bg-green-50 border-b-4 border-green-400 px-3 sm:px-4 py-2 sm:py-3">
          <h3 class="text-xs sm:text-sm font-bold uppercase tracking-widest text-green-900">
            Ready
          </h3>
        </div>

        <!-- Orders List -->
        <div
          class="p-2 sm:p-3 space-y-2 sm:space-y-3 max-h-[300px] sm:max-h-[400px] md:max-h-[600px] overflow-y-auto"
        >
          <div
            v-for="order in readyOrders"
            :key="order.id"
            class="bg-green-50 border-2 border-green-200 rounded-lg p-2 sm:p-3 md:p-4 cursor-pointer hover:shadow-lg transition-all"
            @click="emit('view', order)"
          >
            <!-- Status Badge -->
            <span
              class="inline-block bg-green-200 text-green-700 text-xs font-bold px-1.5 sm:px-2 py-0.5 sm:py-1 rounded"
            >
              ✓ <span class="hidden sm:inline">READY</span>
            </span>

            <!-- Room Info -->
            <div class="text-xs font-bold text-green-600 mt-1 sm:mt-2 mb-1 truncate">
              TABLE {{ order.room?.room_number || '?' }} -
              <span class="hidden sm:inline">READY</span>
            </div>

            <!-- Time -->
            <p class="text-xs sm:text-sm font-bold text-green-900 mt-1">
              {{ getTimeRemaining(order.order_time) }}
            </p>

            <!-- Items -->
            <div class="mt-2 sm:mt-3 space-y-0.5 sm:space-y-1 text-xs text-green-800">
              <p
                v-for="(item, idx) in (order.items || []).slice(0, 3)"
                :key="idx"
                class="font-medium truncate"
              >
                {{ item.quantity }}x {{ item.name }}
              </p>
              <p v-if="(order.items || []).length > 3" class="text-green-600 font-semibold text-xs">
                +{{ (order.items || []).length - 3 }} more
              </p>
            </div>

            <!-- Action Button -->
            <button
              @click.stop="emit('served', order)"
              :disabled="processing"
              class="mt-2 sm:mt-3 md:mt-4 w-full bg-green-600 hover:bg-green-700 disabled:opacity-50 text-white font-bold py-1.5 sm:py-2 rounded-lg transition text-xs sm:text-sm"
            >
              MARK <span class="hidden sm:inline">SERVED</span>
            </button>
          </div>

          <div v-if="!readyOrders?.length" class="text-center py-6 sm:py-8 md:py-12 text-green-400">
            <p class="text-xs sm:text-sm">No orders ready</p>
          </div>
        </div>
      </div>

      <!-- SERVED COLUMN -->
      <div class="rounded-lg bg-white shadow-md overflow-hidden">
        <!-- Column Header -->
        <div class="bg-slate-50 border-b-4 border-slate-300 px-3 sm:px-4 py-2 sm:py-3">
          <h3 class="text-xs sm:text-sm font-bold uppercase tracking-widest text-slate-900">
            Served
          </h3>
        </div>

        <!-- Orders List -->
        <div
          class="p-2 sm:p-3 space-y-2 sm:space-y-3 max-h-[300px] sm:max-h-[400px] md:max-h-[600px] overflow-y-auto"
        >
          <div
            v-for="order in (completedOrders || []).slice(0, 10)"
            :key="order.id"
            class="bg-slate-50 border-2 border-slate-200 rounded-lg p-2 sm:p-3 cursor-pointer hover:shadow-lg transition-all opacity-75"
            @click="emit('view', order)"
          >
            <!-- Status -->
            <span class="text-xs font-bold text-slate-600"
              >✓ <span class="hidden sm:inline">Order</span> completed</span
            >

            <!-- Room Info -->
            <div class="text-xs font-bold text-slate-600 mt-1">
              ROOM {{ order.room?.room_number || '?' }}
            </div>

            <!-- Items Summary -->
            <p class="text-xs text-slate-700 mt-1 sm:mt-2 line-clamp-2 truncate">
              {{ getItemsPreview(order.items || []) }}
            </p>
          </div>

          <div
            v-if="!completedOrders?.length"
            class="text-center py-6 sm:py-8 md:py-12 text-slate-400"
          >
            <p class="text-xs sm:text-sm">No completed orders</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
