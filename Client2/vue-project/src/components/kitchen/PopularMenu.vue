<template>
  <div
    class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-md overflow-hidden"
  >
    <div
      class="border-b border-slate-200 dark:border-slate-800 px-4 sm:px-5 py-3 sm:py-3.5 bg-slate-50 dark:bg-slate-800/80"
    >
      <div class="flex items-center gap-2">
        <TrendingUp class="w-5 h-5 text-amber-500" />
        <h2
          class="text-xs sm:text-sm font-black uppercase tracking-wider text-slate-900 dark:text-white"
        >
          {{ languageStore.t('popular_today', 'Popular Today') }}
        </h2>
      </div>
    </div>

    <div class="p-3 sm:p-4 space-y-2 sm:space-y-3">
      <div v-if="popularItems?.length" class="space-y-2 sm:space-y-3">
        <div
          v-for="(item, index) in popularItems"
          :key="index"
          class="flex items-center justify-between rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/50 p-2.5 sm:p-3 hover:shadow-md transition"
        >
          <div class="flex items-center gap-2.5 sm:gap-3 flex-1 min-w-0">
            <img
              v-if="item.image"
              :src="item.image"
              :alt="item.name"
              class="h-8 w-8 sm:h-10 sm:w-10 rounded-lg object-cover flex-shrink-0 border border-slate-200 dark:border-slate-700"
              @error="handleImageError"
            />
            <div
              v-else
              class="h-8 w-8 sm:h-10 sm:w-10 rounded-lg bg-gradient-to-br from-amber-500 to-amber-600 flex items-center justify-center text-white flex-shrink-0"
            >
              <Utensils class="w-4 h-4" />
            </div>

            <div class="flex-1 min-w-0">
              <p class="font-bold text-slate-900 dark:text-white text-xs sm:text-sm truncate">
                {{ item.name }}
              </p>
              <p
                class="text-[10px] text-slate-500 dark:text-slate-400 truncate font-medium hidden sm:block"
              >
                {{ item.category }}
              </p>
            </div>
          </div>

          <div class="text-right flex-shrink-0 ml-2">
            <p class="text-base sm:text-lg font-black text-amber-600 dark:text-amber-400">
              {{ item.orders }}
            </p>
            <p
              class="text-[10px] text-slate-500 dark:text-slate-400 font-bold hidden sm:block uppercase"
            >
              {{ languageStore.t('orders', 'Orders') }}
            </p>
          </div>
        </div>
      </div>
      <div v-else class="text-center py-6 sm:py-8 text-slate-400 dark:text-slate-600">
        <p class="text-xs sm:text-sm font-medium">
          {{ languageStore.t('no_order_data_yet', 'No order data yet') }}
        </p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useKitchenStore } from '@/stores/kitchenStore'
import { useLanguageStore } from '@/stores/language'
import { storeToRefs } from 'pinia'
import { TrendingUp, Utensils } from 'lucide-vue-next'

const kitchenStore = useKitchenStore()
const languageStore = useLanguageStore()
const { orders } = storeToRefs(kitchenStore)

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
            image: item.image || null,
          }
        }
        itemCount[item.name].orders += item.quantity || 1
      }
    })
  })

  return Object.values(itemCount)
    .sort((a, b) => b.orders - a.orders)
    .slice(0, 3)
})

function handleImageError(event: Event) {
  const img = event.target as HTMLImageElement
  img.style.display = 'none'
}
</script>
