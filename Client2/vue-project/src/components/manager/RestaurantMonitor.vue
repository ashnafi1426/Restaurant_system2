<script setup lang="ts">
import { computed } from 'vue'
import { ChefHat, Clock, CheckCircle2, AlertCircle, Sparkles } from 'lucide-vue-next'
import { useManagerOperationsStore } from '@/stores/manager/operationsStore'
import { useLanguageStore } from '@/stores/language'

const operationsStore = useManagerOperationsStore()
const languageStore = useLanguageStore()

const orderStats = computed(() => {
  return {
    total: operationsStore.orders.length,
    pending: operationsStore.pendingOrders.length,
    preparing: operationsStore.preparingOrders.length,
    ready: operationsStore.readyOrders.length,
  }
})
</script>

<template>
  <section class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-xs p-6 flex flex-col justify-between space-y-6">
    <div class="flex justify-between items-center pb-2">
      <div>
        <h2 class="text-lg font-black text-slate-900 dark:text-white">{{ languageStore.t('restaurant_orders', 'Restaurant Orders') }}</h2>
        <p class="text-xs text-slate-500 dark:text-slate-400 font-medium mt-0.5">{{ languageStore.t('order_pipeline_status', 'Order pipeline status') }}</p>
      </div>
      <div class="w-10 h-10 rounded-2xl bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20 flex items-center justify-center">
        <ChefHat class="w-5 h-5" />
      </div>
    </div>

    <div class="grid grid-cols-2 gap-3">
      <div class="bg-purple-500/10 border border-purple-500/20 rounded-2xl p-4">
        <p class="text-[10px] font-extrabold uppercase text-purple-700 dark:text-purple-300 tracking-wider">{{ languageStore.t('total_orders', 'Total Orders') }}</p>
        <h3 class="text-2xl font-black text-purple-900 dark:text-purple-200 mt-1">{{ orderStats.total }}</h3>
      </div>

      <div class="bg-rose-500/10 border border-rose-500/20 rounded-2xl p-4">
        <p class="text-[10px] font-extrabold uppercase text-rose-700 dark:text-rose-300 tracking-wider">{{ languageStore.t('pending', 'Pending') }}</p>
        <h3 class="text-2xl font-black text-rose-900 dark:text-rose-200 mt-1">{{ orderStats.pending }}</h3>
      </div>

      <div class="bg-amber-500/10 border border-amber-500/20 rounded-2xl p-4">
        <p class="text-[10px] font-extrabold uppercase text-amber-700 dark:text-amber-300 tracking-wider">{{ languageStore.t('preparing', 'Preparing') }}</p>
        <h3 class="text-2xl font-black text-amber-900 dark:text-amber-200 mt-1">{{ orderStats.preparing }}</h3>
      </div>

      <div class="bg-emerald-500/10 border border-emerald-500/20 rounded-2xl p-4">
        <p class="text-[10px] font-extrabold uppercase text-emerald-700 dark:text-emerald-300 tracking-wider">{{ languageStore.t('ready', 'Ready') }}</p>
        <h3 class="text-2xl font-black text-emerald-900 dark:text-emerald-200 mt-1">{{ orderStats.ready }}</h3>
      </div>
    </div>

    <div class="space-y-2">
      <div class="flex justify-between items-center text-xs font-bold text-slate-700 dark:text-slate-300">
        <span>{{ languageStore.t('pipeline_progress', 'Pipeline Progress') }}</span>
        <span class="text-[10px] text-slate-400 font-normal">{{ languageStore.t('real_time', 'Real-time') }}</span>
      </div>

      <div class="flex gap-1.5 h-2 rounded-full overflow-hidden bg-slate-100 dark:bg-slate-800 p-0.5">
        <div
          class="bg-rose-500 rounded-full transition-all duration-300"
          :style="{ width: `${(orderStats.pending / (orderStats.total || 1)) * 100}%` }"
        ></div>
        <div
          class="bg-amber-500 rounded-full transition-all duration-300"
          :style="{ width: `${(orderStats.preparing / (orderStats.total || 1)) * 100}%` }"
        ></div>
        <div
          class="bg-emerald-500 rounded-full transition-all duration-300"
          :style="{ width: `${(orderStats.ready / (orderStats.total || 1)) * 100}%` }"
        ></div>
      </div>

      <div class="flex justify-between text-[10px] font-bold text-slate-500 dark:text-slate-400">
        <span class="text-rose-600 dark:text-rose-400">{{ languageStore.t('pending', 'Pending') }}</span>
        <span class="text-amber-600 dark:text-amber-400">{{ languageStore.t('preparing', 'Preparing') }}</span>
        <span class="text-emerald-600 dark:text-emerald-400">{{ languageStore.t('ready', 'Ready') }}</span>
      </div>
    </div>

    <div class="space-y-2.5">
      <p class="text-xs font-extrabold text-slate-700 dark:text-slate-300 uppercase tracking-wider">{{ languageStore.t('recent_orders', 'Recent Orders') }}</p>

      <div
        v-for="order in operationsStore.orders.slice(0, 4)"
        :key="order.id"
        class="flex items-center justify-between p-3 rounded-2xl bg-slate-50 dark:bg-slate-950 border border-slate-200/80 dark:border-slate-800 hover:bg-slate-100 dark:hover:bg-slate-800/60 transition"
      >
        <div class="flex items-center gap-3 flex-1 min-w-0">
          <div
            :class="[
              'w-8 h-8 rounded-xl flex items-center justify-center text-white text-xs font-black shrink-0',
              order.status === 'pending' && 'bg-rose-500 shadow-xs shadow-rose-500/30',
              order.status === 'preparing' && 'bg-amber-500 shadow-xs shadow-amber-500/30',
              order.status === 'ready' && 'bg-emerald-500 shadow-xs shadow-emerald-500/30',
              order.status === 'served' && 'bg-blue-500 shadow-xs shadow-blue-500/30',
            ]"
          >
            #{{ (order.orderNumber || '').split('-').pop() || '1' }}
          </div>
          <div class="flex-1 min-w-0">
            <p class="text-xs font-extrabold text-slate-900 dark:text-white truncate">{{ order.guestName }}</p>
            <p class="text-[10px] text-slate-500 dark:text-slate-400 font-medium truncate">{{ languageStore.t('room', 'Room') }} {{ order.roomNumber }} • {{ order.itemCount }} {{ languageStore.t('items', 'items') }}</p>
          </div>
        </div>

        <div class="flex items-center gap-1.5 text-xs font-black text-slate-900 dark:text-white ml-2">
          <span class="px-2 py-0.5 rounded-lg bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20 text-[11px]">
            {{ order.total }} {{ languageStore.t('currency_birr', 'Birr') }}
          </span>
        </div>
      </div>

      <div v-if="operationsStore.orders.length === 0" class="py-8 text-center text-slate-500 dark:text-slate-400 text-xs font-bold bg-slate-50 dark:bg-slate-950 rounded-2xl border border-slate-200 dark:border-slate-800">
        {{ languageStore.t('no_orders_at_this_time', 'No orders at this time') }}
      </div>
    </div>
  </section>
</template>
