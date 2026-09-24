<template>
  <div class="grid grid-cols-2 gap-3 sm:gap-4 md:grid-cols-3 lg:grid-cols-5">
    <div
      v-for="stat in stats"
      :key="stat.key"
      :class="[
        'rounded-2xl border-l-4 border-y border-r px-4 py-3.5 shadow-sm hover:shadow-md transition duration-200',
        stat.color,
      ]"
    >
      <p class="text-[11px] font-black uppercase tracking-wider opacity-80">{{ languageStore.t(stat.labelKey, stat.label) }}</p>
      <div class="mt-2 flex items-end justify-between">
        <p class="text-2xl sm:text-3xl font-black">
          {{ getStatValue(stat.key) }}
        </p>
        <component :is="stat.icon" :size="22" :stroke-width="2.5" :class="stat.iconColor" />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { Clock, ChefHat, CheckCircle, UtensilsCrossed, XCircle } from 'lucide-vue-next'
import { useLanguageStore } from '@/stores/language'

const languageStore = useLanguageStore()

const props = defineProps<{
  statistics?: Record<string, any>
  loading?: boolean
}>()

const stats = [
  {
    label: 'PENDING',
    labelKey: 'pending',
    key: 'pending_orders',
    color: 'border-amber-500 bg-amber-500/10 text-amber-900 dark:text-amber-300 border-amber-500/30',
    icon: Clock,
    iconColor: 'text-amber-500',
  },
  {
    label: 'PREPARING',
    labelKey: 'preparing',
    key: 'preparing_orders',
    color: 'border-blue-500 bg-blue-500/10 text-blue-900 dark:text-blue-300 border-blue-500/30',
    icon: ChefHat,
    iconColor: 'text-blue-500',
  },
  {
    label: 'READY',
    labelKey: 'ready',
    key: 'ready_orders',
    color: 'border-emerald-500 bg-emerald-500/10 text-emerald-900 dark:text-emerald-300 border-emerald-500/30',
    icon: CheckCircle,
    iconColor: 'text-emerald-500',
  },
  {
    label: 'SERVED',
    labelKey: 'served',
    key: 'served_orders',
    color: 'border-slate-400 bg-slate-500/10 text-slate-900 dark:text-slate-300 border-slate-500/30',
    icon: UtensilsCrossed,
    iconColor: 'text-slate-400',
  },
  {
    label: 'CANCELLED',
    labelKey: 'cancelled',
    key: 'cancelled_orders',
    color: 'border-rose-500 bg-rose-500/10 text-rose-900 dark:text-rose-300 border-rose-500/30',
    icon: XCircle,
    iconColor: 'text-rose-500',
  },
]

function getStatValue(key: string): number {
  return (props.statistics?.[key] as number) ?? 0
}
</script>
