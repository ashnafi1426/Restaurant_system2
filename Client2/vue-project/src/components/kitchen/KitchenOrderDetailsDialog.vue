<template>
  <Transition name="dialog">
    <div
      v-if="visible"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-2 sm:p-4 backdrop-blur-sm"
    >
      <div
        class="bg-white dark:bg-gray-800 rounded-xl sm:rounded-2xl shadow-2xl max-w-2xl w-full max-h-[95vh] sm:max-h-[90vh] overflow-hidden flex flex-col"
      >
        <!-- Header -->
        <div
          class="flex items-center justify-between px-4 sm:px-6 py-3 sm:py-4 border-b border-gray-200 dark:border-gray-700 flex-shrink-0"
        >
          <div class="flex items-center gap-2 sm:gap-3 min-w-0">
            <div class="p-1.5 sm:p-2 bg-blue-50 dark:bg-blue-900/20 rounded-lg flex-shrink-0">
              <span class="text-base sm:text-lg">📋</span>
            </div>
            <div class="min-w-0">
              <h2
                class="text-base sm:text-lg font-semibold text-gray-900 dark:text-gray-100 truncate"
              >
                Order Details
              </h2>
              <p class="text-xs text-gray-500 dark:text-gray-400">
                Order #{{ selectedOrder?.order_number || '--' }}
              </p>
            </div>
          </div>
          <button
            @click="close"
            class="p-1.5 sm:p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition flex-shrink-0"
          >
            ✕
          </button>
        </div>

        <!-- Content -->
        <div class="px-4 sm:px-6 py-4 sm:py-6 overflow-y-auto flex-1">
          <div v-if="selectedOrder">
            <!-- Order Info -->
            <div class="grid grid-cols-2 gap-2 sm:gap-4 mb-4 sm:mb-6">
              <div class="p-2 sm:p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                <p class="text-xs text-gray-500 dark:text-gray-400">Room</p>
                <p
                  class="text-xs sm:text-sm font-semibold text-gray-900 dark:text-gray-100 truncate"
                >
                  {{ roomDisplay }}
                </p>
              </div>
              <div class="p-2 sm:p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                <p class="text-xs text-gray-500 dark:text-gray-400">Status</p>
                <span
                  :class="[
                    'inline-flex items-center gap-1 sm:gap-1.5 px-2 sm:px-2.5 py-0.5 sm:py-1 text-xs font-medium rounded-full mt-1',
                    statusColor.bg,
                    statusColor.text,
                  ]"
                >
                  <span :class="['w-1 sm:w-1.5 h-1 sm:h-1.5 rounded-full', statusColor.dot]"></span>
                  {{ selectedOrder?.status?.toUpperCase() || 'UNKNOWN' }}
                </span>
              </div>
              <div class="p-2 sm:p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                <p class="text-xs text-gray-500 dark:text-gray-400">Guest</p>
                <p
                  class="text-xs sm:text-sm font-semibold text-gray-900 dark:text-gray-100 truncate"
                >
                  {{ guestName }}
                </p>
              </div>
              <div class="p-2 sm:p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                <p class="text-xs text-gray-500 dark:text-gray-400">Time</p>
                <p class="text-xs sm:text-sm font-semibold text-gray-900 dark:text-gray-100">
                  {{ formattedTime }}
                </p>
              </div>
            </div>

            <!-- Order Items -->
            <div class="mb-4 sm:mb-6">
              <h3
                class="text-xs sm:text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2 sm:mb-3"
              >
                Order Items
              </h3>
              <div v-if="selectedOrder?.items?.length" class="space-y-1 sm:space-y-2">
                <div
                  v-for="item in selectedOrder.items"
                  :key="item.id"
                  class="flex items-center justify-between gap-2 p-2 sm:p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg"
                >
                  <div class="flex items-start gap-2 sm:gap-3 flex-1 min-w-0">
                    <span
                      class="text-xs sm:text-sm font-medium text-gray-600 dark:text-gray-400 flex-shrink-0"
                      >{{ item.quantity }}x</span
                    >
                    <div class="flex-1 min-w-0">
                      <p
                        class="text-xs sm:text-sm font-medium text-gray-800 dark:text-gray-200 truncate"
                      >
                        {{ item.name }}
                      </p>
                      <p
                        v-if="item.notes"
                        class="text-xs text-gray-500 dark:text-gray-400 truncate"
                      >
                        {{ item.notes }}
                      </p>
                    </div>
                  </div>
                  <span
                    class="text-xs sm:text-sm font-medium text-gray-600 dark:text-gray-400 flex-shrink-0 whitespace-nowrap"
                    >${{ (item.line_total || 0).toFixed(2) }}</span
                  >
                </div>
              </div>
              <div v-else class="text-center py-3 sm:py-4 text-gray-500 text-xs sm:text-sm">
                No items
              </div>
            </div>

            <!-- Special Notes -->
            <div
              v-if="selectedOrder?.notes"
              class="p-2 sm:p-3 bg-orange-50 dark:bg-orange-900/20 border border-orange-200 dark:border-orange-800/30 rounded-lg mb-4 sm:mb-6"
            >
              <div class="flex items-start gap-2">
                <span class="text-orange-500 mt-0.5 flex-shrink-0">⚠️</span>
                <div class="min-w-0">
                  <p class="text-xs font-medium text-orange-700 dark:text-orange-300">
                    Special Instructions
                  </p>
                  <p class="text-xs sm:text-sm text-orange-600 dark:text-orange-400">
                    {{ selectedOrder.notes }}
                  </p>
                </div>
              </div>
            </div>

            <!-- Order Summary -->
            <div class="border-t border-gray-200 dark:border-gray-700 pt-3 sm:pt-4">
              <div class="flex justify-between text-xs sm:text-sm">
                <span class="text-gray-600 dark:text-gray-400">Subtotal</span>
                <span class="font-medium text-gray-900 dark:text-gray-100"
                  >${{ (selectedOrder.subtotal || 0).toFixed(2) }}</span
                >
              </div>
              <div class="flex justify-between text-xs sm:text-sm mt-1">
                <span class="text-gray-600 dark:text-gray-400">Tax</span>
                <span class="font-medium text-gray-900 dark:text-gray-100"
                  >${{ (selectedOrder.tax || 0).toFixed(2) }}</span
                >
              </div>
              <div class="flex justify-between text-xs sm:text-sm mt-1">
                <span class="text-gray-600 dark:text-gray-400">Discount</span>
                <span class="font-medium text-gray-900 dark:text-gray-100"
                  >-${{ (selectedOrder.discount || 0).toFixed(2) }}</span
                >
              </div>
              <div
                class="flex justify-between text-base sm:text-lg font-bold mt-2 sm:mt-3 pt-2 sm:pt-3 border-t border-gray-200 dark:border-gray-700"
              >
                <span class="text-gray-900 dark:text-gray-100">Total</span>
                <span class="text-blue-600 dark:text-blue-400"
                  >${{ (selectedOrder.total || 0).toFixed(2) }}</span
                >
              </div>
            </div>
          </div>
        </div>

        <!-- Actions -->
        <div
          class="px-4 sm:px-6 py-3 sm:py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50 flex flex-col sm:flex-row gap-2 sm:gap-3 flex-shrink-0"
        >
          <button
            @click="close"
            class="flex-1 px-3 sm:px-4 py-2 text-xs sm:text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600 rounded-lg transition"
          >
            Close
          </button>
        </div>
      </div>
    </div>
  </Transition>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import type { KitchenOrder } from '@/types/kitchen'

const visible = ref(false)
const selectedOrder = ref<KitchenOrder | null>(null)

const getStatusColor = (status: string) => {
  switch (status) {
    case 'pending':
      return { bg: 'bg-yellow-50', text: 'text-yellow-700', dot: 'bg-yellow-400' }
    case 'preparing':
      return { bg: 'bg-blue-50', text: 'text-blue-700', dot: 'bg-blue-400' }
    case 'ready':
      return { bg: 'bg-green-50', text: 'text-green-700', dot: 'bg-green-400' }
    case 'served':
      return { bg: 'bg-slate-50', text: 'text-slate-700', dot: 'bg-slate-400' }
    default:
      return { bg: 'bg-gray-50', text: 'text-gray-700', dot: 'bg-gray-400' }
  }
}

const statusColor = computed(() => getStatusColor(selectedOrder.value?.status || 'pending'))

const formattedTime = computed(() => {
  if (!selectedOrder.value?.order_time) return '--'
  const date = new Date(selectedOrder.value.order_time)
  const now = new Date()
  const diff = Math.floor((now.getTime() - date.getTime()) / 60000)
  return `${diff} mins ago`
})

const roomDisplay = computed(() => {
  return selectedOrder.value?.room?.room_number
    ? `ROOM ${selectedOrder.value.room.room_number}`
    : '🍽️ TAKEOUT'
})

const guestName = computed(() => {
  return selectedOrder.value?.guest?.full_name || 'Unknown Guest'
})

const open = (order: KitchenOrder) => {
  selectedOrder.value = order
  visible.value = true
}

const close = () => {
  visible.value = false
  selectedOrder.value = null
}

defineExpose({
  open,
  close,
})
</script>

<style scoped>
.dialog-enter-active,
.dialog-leave-active {
  transition: all 0.3s ease;
}

.dialog-enter-from,
.dialog-leave-to {
  opacity: 0;
  transform: scale(0.95) translateY(10px);
}
</style>
