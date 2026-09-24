<script setup lang="ts">
import { computed } from 'vue'
import { Truck, MapPin, User, Clock } from 'lucide-vue-next'
import { useManagerOperationsStore } from '@/stores/manager/operationsStore'
import { useLanguageStore } from '@/stores/language'

const operationsStore = useManagerOperationsStore()
const languageStore = useLanguageStore()

const deliveryStats = computed(() => {
  return {
    total: operationsStore.deliveries.length,
    active: operationsStore.activeDeliveries.length,
    completed: operationsStore.deliveries.filter((d) => d.status === 'delivered').length,
  }
})
</script>

<template>
  <section class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-xs p-6 flex flex-col justify-between space-y-6">
    <div class="flex justify-between items-center pb-2">
      <div>
        <h2 class="text-lg font-black text-slate-900 dark:text-white">{{ languageStore.t('room_service', 'Room Service') }}</h2>
        <p class="text-xs text-slate-500 dark:text-slate-400 font-medium mt-0.5">{{ languageStore.t('active_deliveries_tracking', 'Active deliveries tracking') }}</p>
      </div>
      <div class="w-10 h-10 rounded-2xl bg-teal-500/10 text-teal-600 dark:text-teal-400 border border-teal-500/20 flex items-center justify-center">
        <Truck class="w-5 h-5" />
      </div>
    </div>

    <div class="grid grid-cols-3 gap-3">
      <div class="bg-blue-500/10 border border-blue-500/20 rounded-2xl p-4">
        <p class="text-[10px] font-extrabold uppercase text-blue-700 dark:text-blue-300 tracking-wider">{{ languageStore.t('total', 'Total') }}</p>
        <h3 class="text-2xl font-black text-blue-900 dark:text-blue-200 mt-1">{{ deliveryStats.total }}</h3>
      </div>

      <div class="bg-emerald-500/10 border border-emerald-500/20 rounded-2xl p-4">
        <p class="text-[10px] font-extrabold uppercase text-emerald-700 dark:text-emerald-300 tracking-wider">{{ languageStore.t('active', 'Active') }}</p>
        <h3 class="text-2xl font-black text-emerald-900 dark:text-emerald-200 mt-1">{{ deliveryStats.active }}</h3>
      </div>

      <div class="bg-purple-500/10 border border-purple-500/20 rounded-2xl p-4">
        <p class="text-[10px] font-extrabold uppercase text-purple-700 dark:text-purple-300 tracking-wider">{{ languageStore.t('completed', 'Completed') }}</p>
        <h3 class="text-2xl font-black text-purple-900 dark:text-purple-200 mt-1">{{ deliveryStats.completed }}</h3>
      </div>
    </div>

    <div class="space-y-2.5">
      <p class="text-xs font-extrabold text-slate-700 dark:text-slate-300 uppercase tracking-wider">{{ languageStore.t('active_deliveries', 'Active Deliveries') }}</p>

      <div
        v-for="delivery in operationsStore.activeDeliveries.slice(0, 4)"
        :key="delivery.id"
        class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-950 border border-slate-200/80 dark:border-slate-800 hover:bg-slate-100 dark:hover:bg-slate-800/60 transition space-y-3"
      >
        <div class="flex items-start justify-between">
          <div>
            <p class="font-black text-xs text-slate-900 dark:text-white">{{ languageStore.t('room', 'Room') }} {{ delivery.roomNumber }}</p>
            <p class="text-[10px] text-slate-500 dark:text-slate-400 font-medium">{{ languageStore.t('guest', 'Guest') }}: {{ delivery.guestName }}</p>
          </div>

          <span
            :class="[
              'px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider border',
              delivery.status === 'pending' && 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border-amber-500/20',
              delivery.status === 'in_transit' && 'bg-blue-500/10 text-blue-600 dark:text-blue-400 border-blue-500/20',
              delivery.status === 'delivered' && 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/20',
            ]"
          >
            {{ languageStore.t(delivery.status, delivery.status.replace('_', ' ').toUpperCase()) }}
          </span>
        </div>

        <div class="grid grid-cols-3 gap-2 text-[11px] font-semibold text-slate-600 dark:text-slate-300 bg-white dark:bg-slate-900 p-2 rounded-xl border border-slate-200/60 dark:border-slate-800">
          <div class="flex items-center gap-1.5 truncate">
            <Truck class="w-3.5 h-3.5 text-slate-400 shrink-0" />
            <span class="truncate">{{ delivery.items }}</span>
          </div>

          <div class="flex items-center gap-1.5 truncate">
            <User class="w-3.5 h-3.5 text-slate-400 shrink-0" />
            <span class="truncate">{{ delivery.waiterName || languageStore.t('unassigned', 'Unassigned') }}</span>
          </div>

          <div class="flex items-center gap-1.5 truncate">
            <Clock class="w-3.5 h-3.5 text-slate-400 shrink-0" />
            <span class="truncate">{{ languageStore.t('eta', 'ETA') }} {{ delivery.estimatedTime || '--' }}{{ languageStore.t('m', 'm') }}</span>
          </div>
        </div>
      </div>

      <div v-if="operationsStore.activeDeliveries.length === 0" class="py-8 text-center text-slate-500 dark:text-slate-400 text-xs font-bold bg-slate-50 dark:bg-slate-950 rounded-2xl border border-slate-200 dark:border-slate-800">
        {{ languageStore.t('no_active_deliveries', 'No active deliveries') }}
      </div>
    </div>
  </section>
</template>
