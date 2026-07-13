<template>
  <div class="rounded-lg bg-white shadow-md">
    <div class="border-b border-slate-200 px-4 sm:px-5 py-3 sm:py-4 bg-slate-50">
      <div class="flex items-center gap-2">
        <span class="text-lg sm:text-2xl">📊</span>
        <h2 class="text-base sm:text-lg font-bold text-slate-900">Popular Today</h2>
      </div>
    </div>

    <div class="p-3 sm:p-4 space-y-2 sm:space-y-3">
      <div v-if="popularItems?.length" class="space-y-2 sm:space-y-3">
        <div
          v-for="(item, index) in popularItems"
          :key="index"
          class="flex items-center justify-between rounded-lg border border-slate-200 bg-slate-50 p-2 sm:p-3 hover:shadow-md transition"
        >
          <!-- Image -->
          <div class="flex items-center gap-2 sm:gap-3 flex-1 min-w-0">
            <img
              v-if="item.image"
              :src="item.image"
              :alt="item.name"
              class="h-8 w-8 sm:h-10 sm:w-10 rounded object-cover flex-shrink-0"
              @error="handleImageError"
            />
            <div
              v-else
              class="h-8 w-8 sm:h-10 sm:w-10 rounded bg-gradient-to-br from-slate-300 to-slate-400 flex-shrink-0"
            ></div>

            <!-- Item Info -->
            <div class="flex-1 min-w-0">
              <p class="font-semibold text-slate-900 text-xs sm:text-sm truncate">
                {{ item.name }}
              </p>
              <p class="text-xs text-slate-500 truncate hidden sm:block">{{ item.category }}</p>
            </div>
          </div>

          <!-- Count -->
          <div class="text-right flex-shrink-0 ml-2">
            <p class="text-base sm:text-lg font-bold text-teal-600">{{ item.orders }}</p>
            <p class="text-xs text-slate-500 hidden sm:block">Orders</p>
          </div>
        </div>
      </div>
      <div v-else class="text-center py-6 sm:py-8 text-slate-400">
        <p class="text-xs sm:text-sm">No order data yet</p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useKitchenStore } from '@/stores/kitchenStore'
import { storeToRefs } from 'pinia'

const kitchenStore = useKitchenStore()
const { orders } = storeToRefs(kitchenStore)

/**
 * Calculate popular items from actual orders with real images
 * Groups items by name and counts occurrences, uses image from first occurrence
 */
const popularItems = computed(() => {
  const itemCount: Record<
    string,
    { name: string; category: string; orders: number; image: string | null }
  > = {}

  orders.value.forEach((order) => {
    order.items?.forEach((item) => {
      if (item.name) {
        if (!itemCount[item.name]) {
          itemCount[item.name] = {
            name: item.name,
            category: item.category || 'Unknown',
            orders: 0,
            image: item.image || null, // Use actual image from backend
          }
        }
        itemCount[item.name].orders += item.quantity || 1
      }
    })
  })

  // Return top 3 items by order count
  return Object.values(itemCount)
    .sort((a, b) => b.orders - a.orders)
    .slice(0, 3)
})

/**
 * Handle image load errors - show placeholder
 */
function handleImageError(event: Event) {
  const img = event.target as HTMLImageElement
  img.style.display = 'none'
}
</script>
