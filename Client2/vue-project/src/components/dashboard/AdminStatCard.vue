<script setup lang="ts">
import SvgIcon from '@jamescoyle/vue-icon'

interface Props {
  title: string
  value: string | number
  trend?: number
  icon: string
  color?: string
}

withDefaults(defineProps<Props>(), {
  color: 'teal',
  trend: 0,
})

const colorMap: Record<string, string> = {
  teal: 'text-teal-600',
  blue: 'text-blue-600',
  green: 'text-green-600',
  red: 'text-red-600',
  purple: 'text-purple-600',
}
</script>

<template>
  <div
    class="bg-white rounded-lg p-6 border border-gray-200 hover:shadow-md transition-all duration-300"
  >
    <div class="flex items-start justify-between mb-4">
      <div>
        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">{{ title }}</p>
        <h3 class="text-3xl font-bold text-gray-900 mb-1">{{ value }}</h3>
        <p v-if="trend && trend !== 0" class="text-xs text-green-600 font-semibold">
          <span v-if="trend > 0">↑ {{ trend }}%</span>
          <span v-else>↓ {{ Math.abs(trend) }}%</span>
          vs last month
        </p>
      </div>
      <SvgIcon :class="[colorMap[color] || colorMap.teal]" type="mdi" :path="icon" :size="32" />
    </div>
  </div>
</template>
