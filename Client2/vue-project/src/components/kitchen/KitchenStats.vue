<template>
  <div class="grid grid-cols-2 gap-2 sm:gap-3 md:grid-cols-3 lg:grid-cols-5">
    <div
      v-for="stat in stats"
      :key="stat.key"
      :class="['rounded-lg border-l-4 px-3 sm:px-4 md:px-5 py-3 sm:py-4 bg-white shadow-sm hover:shadow-md transition', stat.color]"
    >
      <p class="text-xs font-bold uppercase tracking-widest text-slate-600">{{ stat.label }}</p>
      <div class="mt-1.5 sm:mt-2 flex items-end justify-between">
        <p class="text-2xl sm:text-3xl font-bold text-slate-900">
          {{ getStatValue(stat.key) }}
        </p>
        <span class="text-lg sm:text-xl">{{ stat.icon }}</span>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
const props = defineProps<{
  statistics?: Record<string, any>
  loading?: boolean
}>()

const stats = [
  {
    label: 'PENDING',
    key: 'pending_orders',
    color: 'border-amber-400 bg-amber-50',
    icon: '🕒',
  },
  {
    label: 'PREPARING',
    key: 'preparing_orders',
    color: 'border-blue-400 bg-blue-50',
    icon: '👨‍🍳',
  },
  {
    label: 'READY',
    key: 'ready_orders',
    color: 'border-green-400 bg-green-50',
    icon: '✅',
  },
  {
    label: 'SERVED',
    key: 'served_orders',
    color: 'border-slate-400 bg-slate-50',
    icon: '🍽️',
  },
  {
    label: 'CANCELLED',
    key: 'cancelled_orders',
    color: 'border-red-400 bg-red-50',
    icon: '❌',
  },
]

function getStatValue(key: string): number {
  return (props.statistics?.[key] as number) ?? 0
}
</script>
